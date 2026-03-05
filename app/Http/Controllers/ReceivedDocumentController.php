<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Recipient;
use App\Models\ReceivedDocument;
use App\Models\SentDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReceivedDocumentController extends Controller
{
    /**
     * Display incoming documents (inbox)
     */
    public function index()
    {
        $inbox = Recipient::with([
                'route.document.documentType',
                'route.document.user.employee'
            ])
            ->where('user_id', Auth::id())
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereNull('action')
                      ->orWhere('action', 'pending')
                      ->orWhere('action', 'read');
            })
            ->orderByRaw("CASE WHEN (SELECT priority FROM document_routes WHERE document_routes.route_id = recipients.route_id) = 'urgent' THEN 0 ELSE 1 END")
            ->orderBy('sent_at', 'desc')
            ->paginate(15);

        $inboxCount = Recipient::where('user_id', Auth::id())
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereNull('action')->orWhere('action', 'pending');
            })
            ->count();

        return view('content.documents.incoming-documents', compact('inbox', 'inboxCount'));
    }

    /**
     * Approve a received document
     */
    public function approve(Document $document)
    {
        $recipient = $this->getRecipientOrFail($document);

        $this->updateRouteAndDocument(
            $document,
            $recipient,
            'approved',
            now(),
            null
        );

        return redirect()->back()->with('success', 'Document approved successfully.');
    }

    /**
     * Mark document as received
     */
    public function receive(Document $document)
    {
        $recipient = $this->getRecipientOrFail($document);
        $receiveAt = now();

        $this->updateRouteAndDocument(
            $document,
            $recipient,
            'receive',
            null,
            $receiveAt
        );

        $this->storeReceivedDocument($document, $recipient, $receiveAt);

        return redirect()->route('documents.received')->with('success', 'Document marked as received.');
    }

    /**
     * Display received documents
     */
    public function received()
    {
        $received = ReceivedDocument::with([
                'document.documentType',
                'document.user.employee'
            ])
            ->where('user_id', Auth::id())
            ->whereNull('archive_at')
            ->orderBy('receive_at', 'desc')
            ->paginate(15);

        return view('content.documents.received-documents', compact('received'));
    }


    /**
     * Archive/Soft delete a received document from the receiver's list
     */
    public function deleteReceived(ReceivedDocument $receivedDocument)
    {
        try {
            // Soft delete with archive_at timestamp
            $receivedDocument->update([
                'archive_at' => now()
            ]);
            $receivedDocument->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document archive successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to archive document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark document as read (AJAX)
     */
    public function markAsRead($recipientId)
    {
        try {
            $recipient = Recipient::where('recipient_id', $recipientId)
                ->where('user_id', Auth::id())
                ->first();

            if (!$recipient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recipient not found or unauthorized.'
                ], 404);
            }

            // Only update if status is pending or null
            if (is_null($recipient->action) || $recipient->action === 'pending') {
                $recipient->update(['action' => 'read']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Document marked as read.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark document as read: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get count of pending documents for notification (AJAX)
     */
    public function getPendingCount()
    {
        try {
            $pendingCount = Recipient::where('user_id', Auth::id())
                ->whereNull('deleted_at')
                ->where(function ($query) {
                    $query->whereNull('action')->orWhere('action', 'pending');
                })
                ->count();

            return response()->json([
                'success' => true,
                'count' => $pendingCount
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'count' => 0,
                'message' => 'Failed to get pending count: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending documents list for notification dropdown (AJAX)
     */
    public function getPendingDocuments()
    {
        try {
            $documents = Recipient::with([
                    'route.document.documentType',
                    'route.document.user.employee'
                ])
                ->where('user_id', Auth::id())
                ->whereNull('deleted_at')
                ->where(function ($query) {
                    $query->whereNull('action')->orWhere('action', 'pending');
                })
                ->orderByRaw("CASE WHEN (SELECT priority FROM document_routes WHERE document_routes.route_id = recipients.route_id) = 'urgent' THEN 0 ELSE 1 END")
                ->orderBy('sent_at', 'desc')
                ->limit(5)
                ->get();

            $documents = $documents->map(function ($recipient) {
                $document = optional($recipient->route)->document;
                if (!$document) return null;
                
                $sender = optional($document->user)->employee;
                $senderName = $sender
                    ? ($sender->firstname . ' ' . $sender->lastname)
                    : (optional($document->user)->name ?? 'N/A');

                return [
                    'recipient_id' => $recipient->recipient_id,
                    'sender_name' => $senderName,
                    'document_type' => optional($document->documentType)->type_name ?? 'Document',
                    'purpose' => $document->purpose,
                    'tracking_code' => $document->tracking_code,
                    'sent_at' => optional($recipient->sent_at)->format('M d, Y')
                ];
            })->filter()->values();

            return response()->json([
                'success' => true,
                'count' => $documents->count(),
                'documents' => $documents
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'count' => 0,
                'documents' => [],
                'message' => 'Failed to get pending documents: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ensure current user is a recipient for the document
     */
    private function getRecipientOrFail(Document $document): Recipient
    {
        $recipient = Recipient::where('user_id', Auth::id())
            ->whereHas('route', function ($query) use ($document) {
                $query->where('document_id', $document->document_id);
            })
            ->with('route')
            ->first();

        if (!$recipient) {
            abort(403, 'Unauthorized to act on this document');
        }

        return $recipient;
    }

    /**
     * Update the recipient action state and document status
     */
    private function updateRouteAndDocument(
        Document $document,
        Recipient $recipient,
        string $routeAction,
        $approveAt,
        $receiveAt
    ): void {
        DB::transaction(function () use ($document, $recipient, $routeAction, $approveAt, $receiveAt) {
            $recipient->update([
                'action' => $routeAction,
                'approve_at' => $approveAt,
                'receive_at' => $receiveAt,
            ]);

            $document->update(['status' => $this->getStatusFromRecipients($document)]);
        });
    }

    /**
     * Derive document status from recipient actions.
     */
    private function getStatusFromRecipients(Document $document): string
    {
        $actions = $document->recipients()
            ->pluck('recipients.action')
            ->filter()
            ->map(fn ($action) => strtolower(trim((string) $action)))
            ->unique();

        $hasReceive = $actions->contains('receive')
            || $actions->contains('received')
            || $document->recipients()->whereNotNull('recipients.receive_at')->exists();

        if ($hasReceive) {
            return 'receive';
        }

        if ($actions->contains('approved')) {
            return 'approved';
        }

        if ($actions->contains('rejected')) {
            return 'rejected';
        }

        return 'pending';
    }

    /**
     * Store received document snapshot per recipient.
     */
    private function storeReceivedDocument(Document $document, Recipient $recipient, $receiveAt): void
    {
        $sent = SentDocument::where('route_id', $recipient->route_id)
            ->where('recipient_id', $recipient->recipient_id)
            ->first();

        if (!$sent) {
            $sent = SentDocument::create([
                'user_id' => $document->user_id,
                'document_id' => $document->document_id,
                'route_id' => $recipient->route_id,
                'recipient_id' => $recipient->recipient_id,
                'file_path' => $document->file_path,
                'purpose' => $document->purpose,
                'status' => 'receive',
                'sent_at' => $recipient->sent_at ?? now(),
                
            ]);
        }

        $sent->update(['status' => 'receive']);

        ReceivedDocument::updateOrCreate(
            [
                'sent_id' => $sent->sent_id,
            ],
            [
                'user_id' => $recipient->user_id,
                'document_id' => $document->document_id,
                'route_id' => $recipient->route_id,
                'purpose' => $document->purpose,
                'file_path' => $document->file_path,
                'status' => 'receive',
                'receive_at' => $receiveAt,
            ]
        );
    
    }

}
