<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentArchiveController extends Controller
{
    /**
     * Display archived documents
     */
    public function index()
    {
        $documents = Document::where('user_id', Auth::id())
            ->where('status', 'archived')
            ->with('documentType')
            ->orderBy('updated_at', 'desc')
            ->paginate(15);

        return view('content.documents.archived-documents', compact('documents'));
    }

    /**
     * Archive a document
     */
    public function archive(Document $document)
    {
        // Check authorization
        if (!$this->canArchive($document)) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to archive this document'
                ], 403);
            }
            abort(403, 'You are not authorized to archive this document');
        }

        try {
            $document->update(['status' => 'archived']);

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document archived successfully'
                ]);
            }

            return redirect()->back()->with('success', 'Document archived successfully');
        } catch (\Exception $e) {
            report($e);
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to archive document: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to archive document: ' . $e->getMessage());
        }
    }

    /**
     * Restore an archived document
     */
    public function restore($documentId)
    {
        $document = Document::where('document_id', $documentId)
            ->firstOrFail();

        // Check authorization
        if (!$this->canArchive($document)) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to restore this document'
                ], 403);
            }
            abort(403, 'You are not authorized to restore this document');
        }

        try {
            $document->update(['status' => $this->getRestoreStatus($document)]);

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document restored successfully'
                ]);
            }

            return redirect()->back()->with('success', 'Document restored successfully');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to restore document: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to restore document');
        }
    }

    /**
     * Permanently delete an archived document
     */
    public function destroy($documentId)
    {
        $document = Document::withTrashed()
            ->where('document_id', $documentId)
            ->firstOrFail();

        // Check authorization
        if (!$this->canArchive($document)) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to delete this document'
                ], 403);
            }
            abort(403, 'You are not authorized to delete this document');
        }

        // Only allow deletion of archived documents
        if ($document->status !== 'archived') {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only archived documents can be permanently deleted'
                ], 400);
            }
            return redirect()->back()->with('error', 'Only archived documents can be permanently deleted');
        }

        try {
            // Delete associated routes and recipients
            $routes = \App\Models\DocumentRoute::where('document_id', $document->document_id)->get();
            foreach ($routes as $route) {
                \App\Models\Recipient::where('route_id', $route->route_id)->delete();
                $route->delete();
            }

            // Delete file from storage
            if (\Storage::disk('public')->exists($document->file_path)) {
                \Storage::disk('public')->delete($document->file_path);
            }

            $document->delete();

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document permanently deleted'
                ]);
            }

            return redirect()->back()->with('success', 'Document permanently deleted');
        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete document: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to delete document');
        }
    }

    /**
     * Check if the current user can archive documents
     */
    private function canArchive(Document $document): bool
    {
        $userId = Auth::id();
        
        // Allow if user is the document sender
        if ($userId === $document->user_id) {
            return true;
        }
        
        // Allow if user is a recipient of this document (sent to them)
        $isRecipient = \App\Models\Recipient::whereHas('route', function($query) use ($document) {
            $query->where('document_id', $document->document_id);
        })
        ->where('user_id', $userId)
        ->exists();
        
        return $isRecipient;
    }

    /**
     * Derive a reasonable status for a restored document.
     */
    private function getRestoreStatus(Document $document): string
    {
        $actions = $document->routes()
            ->pluck('action')
            ->filter()
            ->map(fn ($action) => strtolower((string) $action))
            ->unique()
            ->values();

        if ($actions->contains('receive') || $actions->contains('received')) {
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
