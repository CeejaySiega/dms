<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Recipient;
use App\Models\ReceivedDocument;
use App\Models\SentDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DocumentInboxController extends Controller
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
            ->where(function ($query) {
                $query->whereNull('action')->orWhere('action', 'pending');
            })
            ->orderByRaw("CASE WHEN (SELECT priority FROM document_routes WHERE document_routes.route_id = recipients.route_id) = 'urgent' THEN 0 ELSE 1 END")
            ->orderBy('sent_at', 'desc')
            ->paginate(15);

        $inboxCount = Recipient::where('user_id', Auth::id())
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
            ->orderBy('receive_at', 'desc')
            ->paginate(15);

        return view('content.documents.received-documents', compact('received'));
    }

    /**
     * Remove a received document from the receiver's list
     */
    public function deleteReceived(ReceivedDocument $receivedDocument)
    {
        if ($receivedDocument->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to remove this received document'
            ], 403);
        }

        try {
            $receivedDocument->delete();

            return response()->json([
                'success' => true,
                'message' => 'Received document removed successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove received document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disapprove a received document
     */
    public function disapprove(Document $document)
    {
        $recipient = $this->getRecipientOrFail($document);

        $this->updateRouteAndDocument(
            $document,
            $recipient,
            'rejected',
            null
        );

        return redirect()->back()->with('success', 'Document disapproved successfully.');
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
        $receiveAt
    ): void {
        DB::transaction(function () use ($document, $recipient, $routeAction, $receiveAt) {
            $recipient->update([
                'action' => $routeAction,
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

        if ($sent) {
            $sent->update(['status' => 'receive']);
        }

        ReceivedDocument::updateOrCreate(
            [
                'user_id' => $recipient->user_id,
                'document_id' => $document->document_id,
            ],
            [
                'route_id' => $recipient->route_id,
                'status' => 'receive',
                'receive_at' => $receiveAt,
            ]
        );
    }
}
