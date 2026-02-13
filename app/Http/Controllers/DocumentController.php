<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\Department;
use App\Models\Group;
use App\Models\Group_user;
use App\Models\SentDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    /**
     * Show the form for sending a new document
     */
    public function create()
    {
        $documentTypes = DocumentType::all();
        return view('content.documents.send-document', compact('documentTypes'));
    }

    /**
     * Review document before sending
     */
    public function review(Request $request)
    {
        $validated = $request->validate([
            'tracking_code' => 'required|string',
            'documenttype_id' => 'required|string',
            'purpose' => 'required|string',
            'purpose_others' => 'nullable|string|max:250',
            'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,txt,png,jpg,jpeg|max:10240',
        ]);

        // Store file temporarily
        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $filePath = $file->store('temp/documents', 'public');

        // Store document data in session for review
        session([
            'document_data' => [
                'tracking_code' => $validated['tracking_code'],
                'documenttype_id' => $validated['documenttype_id'], // This is the document type name, not ID
                'purpose' => $validated['purpose'],
                'purpose_others' => $validated['purpose_others'] ?? null,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
            ]
        ]);

        return view('content.documents.send-review');
    }

    /**
     * Show review page (GET)
     */
    public function showReview()
    {
        // Check if document data exists in session
        if (!session()->has('document_data')) {
            return redirect()->route('documents.send')->with('error', 'Please fill in document details first.');
        }

        return view('content.documents.send-review');
    }

    /**
     * Show individual send form
     */
    public function sendIndividual(Request $request)
    {
        // Check if document data exists in session
        if (!session()->has('document_data')) {
            return redirect()->route('documents.send')->with('error', 'Please fill in document details first.');
        }

        // Load users and departments for selection
        $users = User::with('employee')
            ->where('user_id', '!=', Auth::id())
            ->get();
        $departments = Department::all();

        return view('content.documents.send-individual', compact('users', 'departments'));
    }

    /**
     * Show group send form
     */
    public function sendGroup(Request $request)
    {
        // Check if document data exists in session
        if (!session()->has('document_data')) {
            return redirect()->route('documents.send')->with('error', 'Please fill in document details first.');
        }

        $groups = Group::withCount('members')->get();

        return view('content.documents.send-group', compact('groups'));
    }

    /**
     * Store a newly sent document
     */
    public function store(Request $request)
    {
        // Check if document data exists in session
        if (!session()->has('document_data')) {
            return response()->json([
                'success' => false,
                'message' => 'Document data not found. Please start from the beginning.'
            ], 422);
        }

        // Decrypt user_ids from request
        if ($request->has('user_ids')) {
            $decryptedUserIds = array_map(function($id) {
                return decryptId($id);
            }, $request->input('user_ids'));
            $request->merge(['user_ids' => $decryptedUserIds]);
        }

        $validated = $request->validate([
            'user_ids' => 'required|array|min:1|max:5',
            'user_ids.*' => 'exists:users,user_id',
            'notes' => 'nullable|string|max:500',
            'priority' => 'required|in:low,normal,high,urgent',
            'due_date' => 'nullable|date|after_or_equal:today',
        ]);

        // Get document data from session
        $documentData = session('document_data');

        // Find or create document type by name
        $documentType = DocumentType::firstOrCreate(
            ['type_name' => $documentData['documenttype_id']],
            ['type_name' => $documentData['documenttype_id']]
        );

        // Move file from temp to permanent storage
        $tempPath = $documentData['file_path'];
        $newPath = str_replace('temp/documents/', 'documents/', $tempPath);
        Storage::disk('public')->move($tempPath, $newPath);

        // Create document record
        $purpose = $documentData['purpose'];
        if ($purpose === 'others' && !empty($documentData['purpose_others'])) {
            $purpose = 'others: ' . $documentData['purpose_others'];
        }

        $document = Document::create([
            'user_id' => Auth::id(),
            'documenttype_id' => $documentType->documenttype_id,
            'tracking_code' => $documentData['tracking_code'],
            'file_name' => $documentData['file_name'],
            'file_path' => $newPath,
            'purpose' => $purpose,
            'status' => 'pending',
        ]);

        // Create document route
        $route = \App\Models\DocumentRoute::create([
            'user_id' => Auth::id(),
            'document_id' => $document->document_id,
            'group_id' => $validated['group_id'] ?? null,
            'action' => 'pending',
            'priority' => $validated['priority'],
        ]);

        // Create recipient
        foreach ($validated['user_ids'] as $userId) {
            $recipient = \App\Models\Recipient::create([
                'route_id' => $route->route_id,
                'user_id' => $userId,
                'role' => 'recipient',
                'action' => 'pending',
                'sent_at' => now(),
            ]);

            SentDocument::create([
                'user_id' => Auth::id(),
                'document_id' => $document->document_id,
                'route_id' => $route->route_id,
                'recipient_id' => $recipient->recipient_id,
                'file_path' => $document->file_path,
                'purpose' => $document->purpose,
                'status' => 'pending',
                'sent_at' => now(),
            ]);
        }

        // Clear session data
        session()->forget('document_data');

        return response()->json([
            'success' => true,
            'message' => 'Document sent successfully!',
            'document_id' => $document->document_id,
            'tracking_code' => $document->tracking_code,
            'redirect_url' => route('documents.receipt', encryptId($document->document_id))
        ]);
    }

    /**
     * Store a newly sent document to a group
     */
    public function storeGroup(Request $request)
    {
        if (!session()->has('document_data')) {
            return response()->json([
                'success' => false,
                'message' => 'Document data not found. Please start from the beginning.'
            ], 422);
        }

        // Decrypt group_id from request
        if ($request->has('group_id')) {
            $request->merge(['group_id' => decryptId($request->input('group_id'))]);
        }

        $validated = $request->validate([
            'group_id' => 'required|exists:groups,group_id',
            'notes' => 'nullable|string|max:500',
            'priority' => 'required|in:low,normal,high,urgent',
            'due_date' => 'nullable|date|after_or_equal:today',
        ]);

        $userIds = Group_user::where('group_id', $validated['group_id'])
            ->pluck('user_id')
            ->filter(fn ($id) => $id !== Auth::id())
            ->unique()
            ->values()
            ->all();

        if (empty($userIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected group has no eligible members.'
            ], 422);
        }

        $documentData = session('document_data');

        $documentType = DocumentType::firstOrCreate(
            ['type_name' => $documentData['documenttype_id']],
            ['type_name' => $documentData['documenttype_id']]
        );

        $tempPath = $documentData['file_path'];
        $newPath = str_replace('temp/documents/', 'documents/', $tempPath);
        Storage::disk('public')->move($tempPath, $newPath);

        $purpose = $documentData['purpose'];
        if ($purpose === 'others' && !empty($documentData['purpose_others'])) {
            $purpose = 'others: ' . $documentData['purpose_others'];
        }

        $document = Document::create([
            'user_id' => Auth::id(),
            'documenttype_id' => $documentType->documenttype_id,
            'tracking_code' => $documentData['tracking_code'],
            'file_name' => $documentData['file_name'],
            'file_path' => $newPath,
            'purpose' => $purpose,
            'status' => 'pending',
        ]);

        $route = \App\Models\DocumentRoute::create([
            'user_id' => Auth::id(),
            'document_id' => $document->document_id,
            'group_id' => $validated['group_id'],
            'action' => 'pending',
            'priority' => $validated['priority'],
        ]);

        foreach ($userIds as $userId) {
            $recipient = \App\Models\Recipient::create([
                'route_id' => $route->route_id,
                'user_id' => $userId,
                'role' => 'recipient',
                'action' => 'pending',
                'sent_at' => now(),
            ]);

            SentDocument::create([
                'user_id' => Auth::id(),
                'document_id' => $document->document_id,
                'route_id' => $route->route_id,
                'recipient_id' => $recipient->recipient_id,
                'file_path' => $document->file_path,
                'purpose' => $document->purpose,
                'status' => 'pending',
                'sent_at' => now(),
            ]);
        }

        session()->forget('document_data');

        return response()->json([
            'success' => true,
            'message' => 'Document sent to group successfully!',
            'document_id' => $document->document_id,
            'tracking_code' => $document->tracking_code,
            'redirect_url' => route('documents.receipt', encryptId($document->document_id))
        ]);
    }

    /**
     * Show document send receipt
     */
    public function showReceipt($documentId)
    {
        $documentId = decryptId($documentId);
        $document = Document::with(['user.employee', 'documentType'])->findOrFail($documentId);
        
        // Get the route for this document
        $route = \App\Models\DocumentRoute::where('document_id', $documentId)->first();
        
        // Get all recipients for this document
        $recipients = \App\Models\Recipient::with('user.employee')
            ->where('route_id', $route->route_id)
            ->get();

        return view('content.documents.send-receipt', compact('document', 'route', 'recipients'));
    }

    /**
     * Display the specified document
     */
    public function show(Document $document)
    {
        // Check authorization (sender or recipient)
        if (!$this->canAccessDocument($document)) {
            abort(403, 'Unauthorized to view this document');
        }

        return view('content.documents.show-document', compact('document'));
    }


    /**
     * Display all documents
     */
    public function all(Request $request)
    {
        $query = Document::where('user_id', Auth::id())
            ->with('documentType');

        // Search by tracking code or file name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tracking_code', 'like', '%' . $search . '%')
                  ->orWhere('file_name', 'like', '%' . $search . '%')
                  ->orWhere('purpose', 'like', '%' . $search . '%');
            });
        }

        // Filter by document type
        if ($request->filled('document_type')) {
            $query->whereHas('documentType', function($q) use ($request) {
                $q->where('type_name', $request->document_type);
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', '!=', 'archived');
        }

        $documents = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('content.documents.all-documents', compact('documents'));
    }

    /**
     * Download a document file
     */
    public function download(Document $document)
    {
        // Check authorization (sender or recipient)
        if (!$this->canAccessDocument($document)) {
            abort(403, 'Unauthorized to download this document');
        }

        // Check if file exists
        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found. The document file may have been moved or deleted.');
        }

        try {
            $filePath = Storage::disk('public')->path($document->file_path);
            
            return response()->download($filePath, $document->file_name);
        } catch (\Exception $e) {
            abort(500, 'Failed to download file: ' . $e->getMessage());
        }
    }


    /**
     * Get document statistics
     */
    public function getStats()
    {
        $stats = [
            'pending' => Document::where('user_id', Auth::id())
                ->where('status', 'pending')
                ->count(),
            'sent' => Document::where('user_id', Auth::id())
                ->where('status', 'sent')
                ->count(),
            'archived' => Document::where('user_id', Auth::id())
                ->where('status', 'archived')
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Check if the current user can access a document
     */
    private function canAccessDocument(Document $document): bool
    {
        if (Auth::id() === $document->user_id) {
            return true;
        }

        return $document->recipients()->where('recipients.user_id', Auth::id())->exists();
    }
}
