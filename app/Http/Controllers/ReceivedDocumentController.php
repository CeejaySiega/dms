<?php

namespace App\Http\Controllers;

use App\Mail\DocumentReceivedNotification;
use App\Models\Document;
use App\Models\DocumentRoute;
use App\Models\Recipient;
use App\Models\ReceivedDocument;
use App\Models\SentDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ReceivedDocumentController extends Controller
{
    /**
     * Display incoming documents (inbox)
     */
    public function index()
    {
        // Get direct individual recipients (for this user)
        $latestInboxRecipientIds = Recipient::query()
            ->join('document_routes', 'document_routes.route_id', '=', 'recipients.route_id')
            ->where('recipients.user_id', Auth::id())
            ->whereNull('recipients.deleted_at')
            ->where('document_routes.receiver_id', Auth::id())
            ->where(function ($query) {
                $query->whereNull('recipients.action')
                    ->orWhere('recipients.action', 'pending')
                    ->orWhere('recipients.action', 'read');
            })
            ->selectRaw('MAX(recipients.recipient_id) as recipient_id')
            ->groupBy('document_routes.document_id');

        $inbox = Recipient::with([
                'route.document.documentType',
                'route.document.user.employee'
            ])
            ->whereIn('recipient_id', $latestInboxRecipientIds)
            ->orderByRaw("CASE WHEN (SELECT priority FROM document_routes WHERE document_routes.route_id = recipients.route_id) = 'urgent' THEN 0 ELSE 1 END")
            ->orderBy('sent_at', 'desc')
            ->paginate(15);

        $inboxCount = Recipient::query()
            ->join('document_routes', 'document_routes.route_id', '=', 'recipients.route_id')
            ->where('recipients.user_id', Auth::id())
            ->whereNull('recipients.deleted_at')
            ->where('document_routes.receiver_id', Auth::id())
            ->where(function ($query) {
                $query->whereNull('recipients.action')->orWhere('recipients.action', 'pending');
            })
            ->distinct('document_routes.document_id')
            ->count('document_routes.document_id');

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

        $recipients = Recipient::where('user_id', Auth::id())
            ->whereHas('route', function ($query) use ($document) {
                $query->where('document_id', $document->document_id);
            })
            ->where(function ($query) {
                $query->whereNull('action')
                    ->orWhere('action', 'pending')
                    ->orWhere('action', 'read')
                    ->orWhere('action', 'receive');
            })
            ->get();

        $this->updateRouteAndDocument(
            $document,
            $recipient,
            'receive',
            $receiveAt
        );

        if ($recipients->isEmpty()) {
            $this->storeReceivedDocument($document, $recipient, $receiveAt);
        } else {
            foreach ($recipients as $recipientItem) {
                $this->storeReceivedDocument($document, $recipientItem, $receiveAt);
            }
        }

        $this->notifySender($document, $recipient);

        return redirect()->route('documents.received')->with('success', 'Document marked as received.');
    }

    /**
     * Display received documents
     */
    public function received()
    {
        $latestReceivedIds = ReceivedDocument::query()
            ->where('user_id', Auth::id())
            ->whereNull('archive_at')
            ->selectRaw('MAX(received_id) as received_id')
            ->groupBy('document_id');

        $received = ReceivedDocument::with([
                'document.documentType',
            'document.user.employee',
            'route.sender.employee',
            ])
            ->whereIn('received_id', $latestReceivedIds)
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
            $pendingCount = Recipient::query()
                ->join('document_routes', 'document_routes.route_id', '=', 'recipients.route_id')
                ->where('recipients.user_id', Auth::id())
                ->whereNull('recipients.deleted_at')
                ->where('document_routes.receiver_id', Auth::id())
                ->where(function ($query) {
                    $query->whereNull('recipients.action')->orWhere('recipients.action', 'pending');
                })
                ->distinct('document_routes.document_id')
                ->count('document_routes.document_id');

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
            $latestInboxRecipientIds = Recipient::query()
                ->join('document_routes', 'document_routes.route_id', '=', 'recipients.route_id')
                ->where('recipients.user_id', Auth::id())
                ->whereNull('recipients.deleted_at')
                ->where('document_routes.receiver_id', Auth::id())
                ->where(function ($query) {
                    $query->whereNull('recipients.action')->orWhere('recipients.action', 'pending');
                })
                ->selectRaw('MAX(recipients.recipient_id) as recipient_id')
                ->groupBy('document_routes.document_id');

            $documents = Recipient::with([
                    'route.document.documentType',
                    'route.document.user.employee'
                ])
                ->whereIn('recipient_id', $latestInboxRecipientIds)
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
                    'recipient_id'  => $recipient->recipient_id,
                    'sender_name'   => $senderName,
                    'document_type' => optional($document->documentType)->type_name ?? 'Document',
                    'tracking_code' => $document->tracking_code,
                    'sent_at'       => optional($recipient->sent_at)->format('M d, Y'),
                    'sent_at_raw'   => optional($recipient->sent_at)->toIso8601String(),
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
     * Get documents the current user sent that have been received by recipients (for sender navbar notifications).
     */
    public function getReceivedByOthersNotifications()
    {
        try {
            $received = ReceivedDocument::with([
                    'document.documentType',
                    'user.employee',
                ])
                ->whereHas('document', function ($q) {
                    $q->where('user_id', Auth::id());
                })
                ->whereNotNull('receive_at')
                ->orderBy('receive_at', 'desc')
                ->limit(5)
                ->get();

            $newCount = ReceivedDocument::whereHas('document', function ($q) {
                    $q->where('user_id', Auth::id());
                })
                ->whereNotNull('receive_at')
                ->where('receive_at', '>=', now()->subHours(24))
                ->count();

            $documents = $received->map(function ($rec) {
                $document = $rec->document;
                if (!$document) return null;

                $receiverEmp  = optional($rec->user)->employee;
                $receiverName = $receiverEmp && $receiverEmp->firstname
                    ? $receiverEmp->firstname . ' ' . $receiverEmp->lastname
                    : (optional($rec->user)->name ?? 'Unknown');

                return [
                    'received_id'    => $rec->received_id,
                    'receiver_name'  => $receiverName,
                    'document_type'  => optional($document->documentType)->type_name ?? 'Document',
                    'tracking_code'  => $document->tracking_code,
                    'receive_at'     => optional($rec->receive_at)->format('M d, Y g:i A'),
                    'receive_at_raw' => optional($rec->receive_at)->toIso8601String(),
                ];
            })->filter()->values();

            return response()->json([
                'success'   => true,
                'new_count' => $newCount,
                'documents' => $documents,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success'   => false,
                'new_count' => 0,
                'documents' => [],
                'message'   => 'Failed to get received notifications: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get documents originally sent by current user but forwarded by other users.
     */
    public function getForwardedByOthersNotifications()
    {
        try {
            $forwarded = DocumentRoute::with([
                    'document.documentType',
                    'sender.employee',
                    'receiverUser.employee',
                ])
                ->whereHas('document', function ($q) {
                    $q->where('user_id', Auth::id());
                })
                ->whereNotNull('forward_at')
                ->where('sender_id', '!=', Auth::id())
                ->orderBy('forward_at', 'desc')
                ->limit(5)
                ->get();

            $newCount = DocumentRoute::whereHas('document', function ($q) {
                    $q->where('user_id', Auth::id());
                })
                ->whereNotNull('forward_at')
                ->where('sender_id', '!=', Auth::id())
                ->where('forward_at', '>=', now()->subHours(24))
                ->count();

            $documents = $forwarded->map(function ($route) {
                $document = $route->document;
                if (!$document) return null;

                $forwarderEmp  = optional($route->sender)->employee;
                $forwarderName = $forwarderEmp && $forwarderEmp->firstname
                    ? $forwarderEmp->firstname . ' ' . $forwarderEmp->lastname
                    : (optional($route->sender)->name ?? 'Unknown');

                $receiverEmp  = optional($route->receiverUser)->employee;
                $receiverName = $receiverEmp && $receiverEmp->firstname
                    ? $receiverEmp->firstname . ' ' . $receiverEmp->lastname
                    : (optional($route->receiverUser)->name ?? 'Unknown');

                return [
                    'route_id'        => $route->route_id,
                    'forwarder_name'  => $forwarderName,
                    'receiver_name'   => $receiverName,
                    'document_type'   => optional($document->documentType)->type_name ?? 'Document',
                    'tracking_code'   => $document->tracking_code,
                    'forward_at'      => optional($route->forward_at)->format('M d, Y g:i A'),
                    'forward_at_raw'  => optional($route->forward_at)->toIso8601String(),
                ];
            })->filter()->values();

            return response()->json([
                'success'   => true,
                'new_count' => $newCount,
                'documents' => $documents,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success'   => false,
                'new_count' => 0,
                'documents' => [],
                'message'   => 'Failed to get forwarded notifications: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send an email notification to the document sender when a recipient marks it as received.
     */
    private function notifySender(Document $document, Recipient $recipient): void
    {
        $sender = $document->user;
        if (!$sender || !$sender->email) {
            return;
        }

        $senderEmployee = optional($sender->employee);
        $senderName     = $senderEmployee->firstname
            ? $senderEmployee->firstname . ' ' . $senderEmployee->lastname
            : ($sender->name ?? $sender->email);

        $receiverUser     = Auth::user();
        $receiverEmployee = optional($receiverUser->employee);
        $recipientName    = $receiverEmployee->firstname
            ? $receiverEmployee->firstname . ' ' . $receiverEmployee->lastname
            : ($receiverUser->name ?? $receiverUser->email);

        $link = route('documents.sent');

        Mail::to($sender->email)
            ->send(new DocumentReceivedNotification($document, $senderName, $recipientName, $link));
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
            ->orderByDesc('recipient_id')
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
        $receiveAt
    ): void {
        DB::transaction(function () use ($document, $recipient, $routeAction, $receiveAt) {
            if ($routeAction === 'receive') {
                Recipient::where('user_id', Auth::id())
                    ->whereHas('route', function ($query) use ($document) {
                        $query->where('document_id', $document->document_id);
                    })
                    ->where(function ($query) {
                        $query->whereNull('action')
                            ->orWhere('action', 'pending')
                            ->orWhere('action', 'read')
                            ->orWhere('action', 'receive');
                    })
                    ->update([
                        'action' => 'receive',
                        'receive_at' => $receiveAt,
                    ]);
            } else {
                $recipient->update([
                    'action' => $routeAction,
                    'receive_at' => $receiveAt,
                ]);
            }

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
                'status' => 'receive',
                'sent_at' => $recipient->sent_at ?? now(),
                
            ]);
        }

        $sent->update(['status' => 'receive']);

        ReceivedDocument::updateOrCreate(
            [
                'user_id' => $recipient->user_id,
                'document_id' => $document->document_id,
            ],
            [
                'sent_id' => $sent->sent_id,
                'user_id' => $recipient->user_id,
                'document_id' => $document->document_id,
                'route_id' => $recipient->route_id,
                'status' => 'receive',
                'receive_at' => $receiveAt,
            ]
        );
    
    }

}
