<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\Department;
use App\Models\Group;
use App\Models\Group_user;
use App\Models\SentDocument;
use App\Models\Archive;
use App\Models\DocumentRoute;
use App\Models\Recipient;
use Illuminate\Support\Facades\Mail;
use App\Mail\DocumentNotification;
use App\Mail\DocumentForwardedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    /**
     * Show the form for sending a new document
     */
    public function create()
    {
        $documentTypes = DocumentType::all();
        $trackingCode = $this->buildTrackingCode();

        return view('content.documents.send-document', compact('documentTypes', 'trackingCode'));
    }

    /**
     * Build tracking code in CAMPUS-DATEYEAR-SEQUENCE format (e.g., SG-03162026-01).
     */
    private function buildTrackingCode(): string
    {
        $campuses = getCampuses();
        $campusAbbr = 'SG';
        $userCampusId = auth()->user()->employee->campus ?? null;

        if ($userCampusId) {
            foreach ($campuses as $abbr => $campus) {
                if (($campus['ID'] ?? null) == $userCampusId) {
                    $campusAbbr = $abbr;
                    break;
                }
            }
        }

        $dateYear = now()->format('mdY');
        $prefix = $campusAbbr . '-' . $dateYear;

        $maxSequence = Document::where('tracking_code', 'like', $prefix . '-%')
            ->pluck('tracking_code')
            ->map(function ($code) {
                $parts = explode('-', (string) $code);
                $lastPart = end($parts);

                return is_numeric($lastPart) ? (int) $lastPart : 0;
            })
            ->max() ?? 0;

        $nextSequence = str_pad((string) ($maxSequence + 1), 2, '0', STR_PAD_LEFT);

        return $prefix . '-' . $nextSequence;
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
        // logActivity(auth()->id(), 'send', 'Opened individual send form');
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
        // logActivity(auth()->id(), 'send', 'Opened group send form');
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
            'due_date' => 'nullable|date|after_or_equal:today|required_if:priority,urgent',
        ]);

        $dueDate = ($validated['priority'] ?? null) === 'urgent'
            ? ($validated['due_date'] ?? null)
            : null;

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
            'due_date' => $dueDate,
        ]);
        logActivity(auth()->id(), 'send', 'Created and sent document');

        // Create document route for each recipient
        foreach ($validated['user_ids'] as $userId) {
            $route = \App\Models\DocumentRoute::create([
                'sender_id' => Auth::id(),
                'document_id' => $document->document_id,
                'receiver_id' => $userId,
                'action' => 'pending',
                'priority' => $validated['priority'],
            ]);
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
                'status' => 'pending',
                'sent_at' => now(),
            ]);

            // Send email notification
            $recipientUser = \App\Models\User::find($userId);
            if ($recipientUser && $recipientUser->email) {
                $link = $recipientUser->getInboxLink();
                Mail::to($recipientUser->email)->send(
                    new DocumentNotification($document, $recipientUser->name ?? 'User', $link)
                );
            }
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
            'due_date' => 'nullable|date|after_or_equal:today|required_if:priority,urgent',
        ]);

        $dueDate = ($validated['priority'] ?? null) === 'urgent'
            ? ($validated['due_date'] ?? null)
            : null;

        $userIds = Group_user::query()
            ->join('users', 'users.user_id', '=', 'group_users.user_id')
            ->where('group_users.group_id', $validated['group_id'])
            ->pluck('group_users.user_id')
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
            'due_date' => $dueDate,
        ]);
        logActivity(auth()->id(), 'send', 'Created and sent document to group');

        // Create one route per user in the group.
        // receiver_id must reference users.user_id (foreign key).
        foreach ($userIds as $userId) {
            $route = \App\Models\DocumentRoute::create([
                'sender_id' => Auth::id(),
                'document_id' => $document->document_id,
                'group_id' => $validated['group_id'],
                'receiver_id' => $userId,
                'action' => 'pending',
                'priority' => $validated['priority'],
            ]);

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
                'status' => 'pending',
                'sent_at' => now(),
            ]);

            $recipientUser = \App\Models\User::find($userId);
            if ($recipientUser && $recipientUser->email) {
                $link = $recipientUser->getInboxLink();
                Mail::to($recipientUser->email)->send(
                    new DocumentNotification(
                        $document,
                        $recipientUser->name ?? 'User',
                        $link
                    )
                );
            }
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

        if (!$this->canAccessDocument($document)) {
            abort(403, 'Unauthorized to view this receipt');
        }
        
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
     * Show forward form for an existing document
     */
    public function forwardForm(string $documentId, Request $request)
    {
        $decryptedDocumentId = decryptId($documentId);
        $document = Document::with(['documentType', 'user.employee'])->findOrFail($decryptedDocumentId);

        if (!$this->canAccessDocument($document)) {
            abort(403, 'Unauthorized to forward this document');
        }

        $baseRouteId = null;
        if ($request->filled('base_route')) {
            $baseRouteId = decryptId((string) $request->input('base_route'));
        }

        $users = User::with('employee')
            ->where('user_id', '!=', Auth::id())
            ->get();

        return view('content.documents.forward-document', [
            'document' => $document,
            'users' => $users,
            'baseRouteId' => $baseRouteId,
            'source' => (string) $request->input('source', 'unknown'),
        ]);
    }

    /**
     * Store forwarded recipients for an existing document
     */
    public function forwardStore(string $documentId, Request $request)
    {
        $decryptedDocumentId = decryptId($documentId);
        $document = Document::findOrFail($decryptedDocumentId);

        if (!$this->canAccessDocument($document)) {
            abort(403, 'Unauthorized to forward this document');
        }

        if ($request->has('user_ids')) {
            $decryptedUserIds = array_map(function ($id) {
                return decryptId($id);
            }, (array) $request->input('user_ids', []));
            $request->merge(['user_ids' => $decryptedUserIds]);
        }

        $validated = $request->validate([
            'user_ids' => 'required|array|min:1|max:5',
            'user_ids.*' => 'required|integer|exists:users,user_id|different:' . Auth::id(),
            'priority' => 'required|in:low,normal,high,urgent',
            'base_route_id' => 'nullable|integer|exists:document_routes,route_id',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($document, $validated) {
            // Create route for each individual recipient
            foreach ($validated['user_ids'] as $userId) {
                $route = DocumentRoute::create([
                    'sender_id' => Auth::id(),
                    'document_id' => $document->document_id,
                    'receiver_id' => $userId,
                    'action' => 'pending',
                    'priority' => $validated['priority'],
                    'forward_at' => now(),
                ]);

                $recipient = Recipient::create([
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
                    'status' => 'pending',
                    'sent_at' => now(),
                ]);

                $recipientUser = User::find($userId);
                if ($recipientUser && $recipientUser->email) {
                    $link = $recipientUser->getInboxLink();
                    Mail::to($recipientUser->email)->send(
                        new DocumentNotification($document, $recipientUser->name ?? 'User', $link)
                    );
                }

                // Notify original sender when someone else forwards their document.
                $originalSender = $document->user;
                if ($originalSender && $originalSender->email && (int) $originalSender->user_id !== (int) Auth::id()) {
                    $senderEmployee = optional($originalSender->employee);
                    $senderName = $senderEmployee->firstname
                        ? $senderEmployee->firstname . ' ' . $senderEmployee->lastname
                        : ($originalSender->name ?? $originalSender->email);

                    $forwarder = Auth::user();
                    $forwarderEmployee = optional($forwarder->employee);
                    $forwarderName = $forwarderEmployee->firstname
                        ? $forwarderEmployee->firstname . ' ' . $forwarderEmployee->lastname
                        : ($forwarder->name ?? $forwarder->email);

                    $newRecipientEmployee = optional($recipientUser)->employee;
                    $newRecipientName = $newRecipientEmployee && $newRecipientEmployee->firstname
                        ? $newRecipientEmployee->firstname . ' ' . $newRecipientEmployee->lastname
                        : (optional($recipientUser)->name ?? optional($recipientUser)->email ?? 'Recipient');

                    Mail::to($originalSender->email)->send(
                        new DocumentForwardedNotification(
                            $document,
                            $senderName,
                            $forwarderName,
                            $newRecipientName,
                            route('documents.sent')
                        )
                    );
                }
            }
        });

        logActivity(auth()->id(), 'send', 'Forwarded document to new recipient(s)');

        return redirect()->route('documents.sent')->with('success', 'Document forwarded successfully.');
    }


    /**
     * Display all documents
     */
    public function all(Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        $query = Document::where('user_id', Auth::id())
            ->whereNull('unsend_at')
            ->where('status', '!=', 'restored')  // Exclude restored documents
            ->whereHas('recipients', function ($q) {
                $q->whereNull('unsend_at');
            })
            ->with('documentType');

        // Exclude archived documents (via Archive table - both active and soft-deleted)
        $query->whereNotIn('document_id', function($subquery) {
            $subquery->select('document_id')
                     ->from('archives')
                     ->where('user_id', Auth::id());
        });

        // Search by tracking code or file name
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
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
        }

        $documents = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return view('content.documents.all-documents', compact('documents'));
    }

    /**
     * Navbar quick-search (returns JSON)
     */
    public function search(Request $request)
    {
        $q      = trim((string) $request->query('q', ''));
        $userId = \Illuminate\Support\Facades\Auth::id();

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        // Documents the user sent or received
        $documents = Document::where(function ($query) use ($userId) {
                $query->where('documents.user_id', $userId)
                      ->orWhereHas('recipients', function ($q2) use ($userId) {
                          $q2->where('recipients.user_id', $userId)
                             ->whereNull('recipients.deleted_at')
                             ->whereHas('route', function ($q3) {
                                 $q3->whereNull('unsend_at');
                             });
                      });
            })
            ->where(function ($query) use ($q) {
                $query->where('documents.tracking_code', 'like', "%{$q}%")
                      ->orWhere('documents.purpose', 'like', "%{$q}%")
                      ->orWhere('documents.file_name', 'like', "%{$q}%")
                      ->orWhereHas('documentType', function ($q2) use ($q) {
                          $q2->where('type_name', 'like', "%{$q}%");
                      });
            })
            ->with('documentType')
            ->limit(8)
            ->get();

        $results = $documents->map(function ($doc) {
            return [
                'title'    => $doc->tracking_code ?? 'No tracking code',
                'sub'      => ($doc->documentType->type_name ?? 'Document') . ' — ' . \Illuminate\Support\Str::limit($doc->purpose ?? '', 50),
                'url'      => route('documents.receipt', encryptId($doc->document_id)),
            ];
        });

        return response()->json($results);
    }

    /**
     * Download a document file
     */
    public function download(Document $document)
    {
        // Check authorization (sender or recipient)
        if (!$this->canAccessDocument($document)) {
            logActivity(Auth::id(), 'download_attempt', 'Attempted to download document without authorization');
            abort(403, 'Unauthorized to download this document');
        }

        // Check if file exists
        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found. The document file may have been moved or deleted.');
        }

        try {
            logActivity(Auth::id(), 'download', 'Downloaded document: ' . $document->file_name);
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
            'received' => Document::where('user_id', Auth::id())
                ->where('status', 'received')
                ->count(),
            'archived' => Archive::where('user_id', Auth::id())
                ->whereNull('deleted_at')
                ->count(),
            'restored' => Document::where('user_id', Auth::id())
                ->where('status', 'restored')
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

        return $document->recipients()
            ->where('recipients.user_id', Auth::id())
            ->whereNull('recipients.deleted_at')
            ->whereHas('route', function ($query) {
                $query->whereNull('unsend_at');
            })
            ->exists();
    }
}
