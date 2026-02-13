<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Recipient;
use App\Models\SentDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SentDocumentController extends Controller
{
    /**
     * Display all sent documents by current user
     */
    public function sent()
    {
        $documents = Document::where('user_id', Auth::id())
            ->where('status', '!=', 'archived')
            ->with(['documentType'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('content.documents.sent-documents', compact('documents'));
    }

    /**
     * Delete (unsend) a document for pending recipients only
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

        try {
            $routes = \App\Models\DocumentRoute::where('document_id', $document->document_id)->get();
            $routeIds = $routes->pluck('route_id');

            $actedCount = Recipient::whereIn('route_id', $routeIds)
                ->whereIn('action', ['receive', 'approved', 'rejected'])
                ->count();

            $pendingRecipients = Recipient::whereIn('route_id', $routeIds)
                ->where(function ($query) {
                    $query->whereNull('action')->orWhere('action', 'pending');
                })
                ->get();

            if ($pendingRecipients->isEmpty()) {
                if (request()->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No pending recipients to unsend.'
                    ], 400);
                }

                return redirect()->back()->with('error', 'No pending recipients to unsend.');
            }

            SentDocument::whereIn('route_id', $routeIds)
                ->whereIn('recipient_id', $pendingRecipients->pluck('recipient_id'))
                ->delete();

            Recipient::whereIn('recipient_id', $pendingRecipients->pluck('recipient_id'))
                ->delete();

            $remainingCount = Recipient::whereIn('route_id', $routeIds)->count();

            if ($remainingCount === 0 && $actedCount === 0) {
                SentDocument::whereIn('route_id', $routeIds)->delete();
                foreach ($routes as $route) {
                    $route->delete();
                }

                if (Storage::disk('public')->exists($document->file_path)) {
                    Storage::disk('public')->delete($document->file_path);
                }

                SentDocument::where('document_id', $document->document_id)->delete();
                $document->forceDelete();
            } else {
                $document->update(['status' => $this->getStatusFromRecipients($document)]);
            }

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $actedCount > 0
                        ? 'Pending recipients were removed. Recipients who already acted remain.'
                        : 'Document unsent successfully!'
                ]);
            }

            return redirect()->back()->with('success', $actedCount > 0
                ? 'Pending recipients were removed. Recipients who already acted remain.'
                : 'Document deleted successfully!');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete document: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to delete document: ' . $e->getMessage());
        }
    }

    /**
     * Permanently delete a document (sender only)
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
            $routes = \App\Models\DocumentRoute::where('document_id', $document->document_id)->get();
            $routeIds = $routes->pluck('route_id');

            $hasReceived = Recipient::whereIn('route_id', $routeIds)
                ->whereIn('action', ['receive', 'approved', 'rejected'])
                ->exists();

            if (!$hasReceived && \App\Models\ReceivedDocument::whereIn('route_id', $routeIds)->exists()) {
                $hasReceived = true;
            }

            if ($hasReceived) {
                $pendingRecipients = Recipient::whereIn('route_id', $routeIds)
                    ->where(function ($query) {
                        $query->whereNull('action')->orWhere('action', 'pending');
                    })
                    ->get();

                SentDocument::whereIn('route_id', $routeIds)
                    ->whereIn('recipient_id', $pendingRecipients->pluck('recipient_id'))
                    ->delete();

                Recipient::whereIn('recipient_id', $pendingRecipients->pluck('recipient_id'))
                    ->delete();

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
     * Unsend a document to a specific recipient
     */
    public function unsendRecipient(Document $document, Recipient $recipient)
    {
        $isOwner = Auth::id() === $document->user_id;
        $isSelf = Auth::id() === $recipient->user_id;

        if (!$isOwner && !$isSelf) {
            return response()->json([
                'success' => false,
                'message' => 'Only the owner can remove a recipient'
            ], 403);
        }

        $isRecipient = $document->recipients()
            ->where('recipients.recipient_id', $recipient->recipient_id)
            ->exists();

        if (!$isRecipient) {
            return response()->json([
                'success' => false,
                'message' => 'Recipient not found for this document'
            ], 404);
        }

        if (in_array($recipient->action, ['approved', 'rejected'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot remove a recipient who already approved or rejected this document'
            ], 400);
        }

        $sent = SentDocument::where('route_id', $recipient->route_id)
            ->where('recipient_id', $recipient->recipient_id)
            ->first();

        if ($sent && \App\Models\ReceivedDocument::where('sent_id', $sent->sent_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot remove a recipient who already received this document'
            ], 400);
        }

        try {
            SentDocument::where('route_id', $recipient->route_id)
                ->where('recipient_id', $recipient->recipient_id)
                ->delete();

            $recipient->delete();

            $remaining = $document->recipients()->count();
            if ($remaining === 0) {
                $routes = \App\Models\DocumentRoute::where('document_id', $document->document_id)->get();
                foreach ($routes as $route) {
                    SentDocument::where('route_id', $route->route_id)->delete();
                    $route->delete();
                }

                if (Storage::disk('public')->exists($document->file_path)) {
                    Storage::disk('public')->delete($document->file_path);
                }

                SentDocument::where('document_id', $document->document_id)->delete();
                $document->forceDelete();
            } else {
                $document->update(['status' => $this->getStatusFromRecipients($document)]);
            }

            return response()->json([
                'success' => true,
                'message' => $remaining === 0
                    ? 'Recipient removed and document unsent.'
                    : 'Recipient removed successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove recipient: ' . $e->getMessage()
            ], 500);
        }
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
}
