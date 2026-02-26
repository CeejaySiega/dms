<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Mail\DocumentNotification;
use App\Models\Document;
use App\Models\DocumentRoute;
use App\Models\Recipient;
use App\Models\SentDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SentDocumentController extends Controller
{
    public function sent()
    {
        $documents = Document::where('user_id', Auth::id())
            ->where('status', '!=', 'archived')
            ->with(['documentType', 'recipients'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('content.documents.sent-documents', compact('documents'));
    }

    /**
     * Delete (unsend) a document for pending recipients only.
     */
    public function delete(Document $document)
    {
        if (Auth::id() !== $document->user_id) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only the owner can delete this document'
                ], 403);
            }
            abort(403, 'Only the owner can delete this document');
        }
    }

    /**
     * Permanently delete a document (sender only).
     */
    public function deleteDocument(Document $document)
    {
        if (Auth::id() !== $document->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the owner can delete this document'
            ], 403);
        }

        try {
            $routes   = DocumentRoute::where('document_id', $document->document_id)->get();
            $routeIds = $routes->pluck('route_id');

            $hasReceived = Recipient::whereIn('route_id', $routeIds)
                ->whereIn('action', ['receive', 'approved', 'rejected'])
                ->exists();

            if (!$hasReceived && \App\Models\ReceivedDocument::whereIn('route_id', $routeIds)->exists()) {
                $hasReceived = true;
            }

            if ($hasReceived) {
                $pendingRecipients = Recipient::whereIn('route_id', $routeIds)
                    ->where(function ($q) {
                        $q->whereNull('action')->orWhere('action', 'pending');
                    })
                    ->get();

                SentDocument::whereIn('route_id', $routeIds)
                    ->whereIn('recipient_id', $pendingRecipients->pluck('recipient_id'))
                    ->delete();

                Recipient::whereIn('recipient_id', $pendingRecipients->pluck('recipient_id'))->delete();

                $document->update(['status' => 'archived']);

                return response()->json([
                    'success' => true,
                    'message' => 'Document removed for sender. Receivers keep their copy.'
                ]);
            }

            foreach ($routes as $route) {
                if (\App\Models\ReceivedDocument::where('route_id', $route->route_id)->exists()) {
                    $document->update(['status' => 'archived']);

                    return response()->json([
                        'success' => true,
                        'message' => 'Document removed for sender. Receivers keep their copy.'
                    ]);
                }

                SentDocument::where('route_id', $route->route_id)->delete();
                Recipient::where('route_id', $route->route_id)->delete();
                $route->delete();
            }

            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

            SentDocument::where('document_id', $document->document_id)->delete();
            $document->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unsend a document to a specific recipient.
     *
     * Route: DELETE /documents/{document}/recipients/{recipient}
     *
     * Both {document} and {recipient} are encrypted IDs resolved by route model binding
     * via the decryptId() helper registered in RouteServiceProvider.
     */
    public function unsendRecipient(Document $document, Recipient $recipient)
    {
        // Verify the recipient actually belongs to this document (via DocumentRoute)
        $routeIds = DocumentRoute::where('document_id', $document->document_id)
            ->pluck('route_id');

        $recipientModel = Recipient::where('recipient_id', $recipient->recipient_id)
            ->whereIn('route_id', $routeIds)
            ->first();

        if (!$recipientModel) {
            return response()->json([
                'success' => false,
                'message' => 'Recipient not found for this document.'
            ], 404);
        }

        // Only the document owner can remove a recipient
        if (Auth::id() !== $document->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the owner can remove a recipient.'
            ], 403);
        }

        // Cannot remove a recipient who already acted on the document
        if (in_array($recipientModel->action, ['approved', 'rejected', 'receive', 'received'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot remove a recipient who already acted on this document.'
            ], 400);
        }

        // Cannot remove if the recipient already has a received-document record
        $sent = SentDocument::where('route_id', $recipientModel->route_id)
            ->where('recipient_id', $recipientModel->recipient_id)
            ->first();

        if ($sent && \App\Models\ReceivedDocument::where('sent_id', $sent->sent_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot remove a recipient who already received this document.'
            ], 400);
        }

        try {
            // Remove SentDocument record for this recipient
            SentDocument::where('route_id', $recipientModel->route_id)
                ->where('recipient_id', $recipientModel->recipient_id)
                ->delete();

            $recipientModel->delete();

            // Count remaining recipients across all routes for this document
            $remaining = Recipient::whereIn('route_id', $routeIds)->count();

            if ($remaining === 0) {
                // No more recipients — clean up routes and the document itself
                $routes = DocumentRoute::where('document_id', $document->document_id)->get();
                foreach ($routes as $route) {
                    SentDocument::where('route_id', $route->route_id)->delete();
                    $route->delete();
                }

                if (Storage::disk('public')->exists($document->file_path)) {
                    Storage::disk('public')->delete($document->file_path);
                }

                SentDocument::where('document_id', $document->document_id)->delete();
                $document->forceDelete();

                return response()->json([
                    'success' => true,
                    'message' => 'Unsend Success.'
                ]);
            }

            // Still has other recipients — recalculate status
            $document->update(['status' => $this->getStatusFromRecipients($document)]);

            return response()->json([
                'success' => true,
                'message' => 'Recipient removed successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove recipient: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unsend a document for the currently authenticated recipient (self-removal).
     *
     * Route: DELETE /documents/{document}/unsend-individual
     */
    public function unsendIndividual(Document $document)
    {
        $routeIds = DocumentRoute::where('document_id', $document->document_id)
            ->pluck('route_id');

        $recipientModel = Recipient::where('user_id', Auth::id())
            ->whereIn('route_id', $routeIds)
            ->first();

        if (!$recipientModel) {
            return response()->json([
                'success' => false,
                'message' => 'Recipient not found for this document.'
            ], 404);
        }

        $isOwner = Auth::id() === $document->user_id;
        $isSelf  = Auth::id() === $recipientModel->user_id;

        if (!$isOwner && !$isSelf) {
            return response()->json([
                'success' => false,
                'message' => 'Only the owner or recipient can unsend this document.'
            ], 403);
        }

        if (in_array($recipientModel->action, ['approved', 'rejected'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot unsend after approval or rejection.'
            ], 400);
        }

        $sent = SentDocument::where('route_id', $recipientModel->route_id)
            ->where('recipient_id', $recipientModel->recipient_id)
            ->first();

        if ($sent && \App\Models\ReceivedDocument::where('sent_id', $sent->sent_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot unsend after the document is received.'
            ], 400);
        }

        try {
            SentDocument::where('route_id', $recipientModel->route_id)
                ->where('recipient_id', $recipientModel->recipient_id)
                ->delete();

            $recipientModel->delete();

            $remaining = Recipient::whereIn('route_id', $routeIds)->count();

            if ($remaining === 0) {
                $routes = DocumentRoute::where('document_id', $document->document_id)->get();
                foreach ($routes as $route) {
                    SentDocument::where('route_id', $route->route_id)->delete();
                    $route->delete();
                }

                if (Storage::disk('public')->exists($document->file_path)) {
                    Storage::disk('public')->delete($document->file_path);
                }

                SentDocument::where('document_id', $document->document_id)->delete();
                $document->forceDelete();

                return response()->json([
                    'success' => true,
                    'message' => 'Document unsent and deleted.'
                ]);
            }

            $document->update(['status' => $this->getStatusFromRecipients($document)]);

            return response()->json([
                'success' => true,
                'message' => 'Document unsent for this recipient.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unsend: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Derive document status from remaining recipient actions.
     */
    private function getStatusFromRecipients(Document $document): string
    {
        $routeIds = DocumentRoute::where('document_id', $document->document_id)
            ->pluck('route_id');

        $actions = Recipient::whereIn('route_id', $routeIds)
            ->pluck('action')
            ->filter()
            ->map(fn ($a) => strtolower(trim((string) $a)))
            ->unique();

        $hasReceive = $actions->contains('receive')
            || $actions->contains('received')
            || Recipient::whereIn('route_id', $routeIds)->whereNotNull('receive_at')->exists();

        if ($hasReceive)                    return 'receive';
        if ($actions->contains('approved')) return 'approved';
        if ($actions->contains('rejected')) return 'rejected';

        return 'pending';
    }
}