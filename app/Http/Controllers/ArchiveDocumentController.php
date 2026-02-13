<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Archive;
use App\Models\SentDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ArchiveDocumentController extends Controller
{
    /**
     * Display archived documents
     */
    public function index()
    {
        $documents = Archive::where('user_id', Auth::user()->user_id)
            ->with('document', 'document.documentType')
            ->orderBy('archive_at', 'desc')
            ->paginate(15);

        return view('content.documents.archived-documents', compact('documents'));
    }

    /**
     * Archive a document
     */
    public function archive(Document $document)
    {
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
            // Create archive record
            Archive::create([
                'user_id' => $document->user_id,
                'document_id' => $document->document_id,
                'file_path' => $document->file_path,
                'file_name' => $document->file_name,
                'archive_at' => now(),
            ]);

            // Remove from received list for this user, if present
            \App\Models\ReceivedDocument::where('document_id', $document->document_id)
                ->where('user_id', Auth::user()->user_id)
                ->delete();

            // Only mark document as archived for the sender's own view
            if (Auth::user()->user_id === $document->user_id) {
                $document->update(['status' => 'archived']);
            }

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
        $documentId = decryptId($documentId);
        
        $archive = Archive::where('document_id', $documentId)
            ->where('user_id', Auth::user()->user_id)
            ->firstOrFail();

        try {
            // Delete archive record
            $archive->delete();

            // Restore document status to 'sent'
            $document = Document::where('document_id', $documentId)->first();
            if ($document) {
                $document->update(['status' => 'sent']);
            }

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
        $documentId = decryptId($documentId);
        
        try {
            // Find the archive record for this document
            $archive = Archive::where('document_id', $documentId)
                ->where('user_id', Auth::user()->user_id)
                ->firstOrFail();

            // Force delete only the archive record, not the document
            $archive->forceDelete();

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Archive record permanently deleted'
                ]);
            }

            return redirect()->back()->with('success', 'Archive record permanently deleted');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archive record not found'
                ], 404);
            }
            return redirect()->back()->with('error', 'Archive record not found');
        } catch (\Exception $e) {
            report($e);
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete archive record: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Failed to delete archive record: ' . $e->getMessage());
        }
    }

    /**
     * Check if the current user can archive documents
     */
    private function canArchive(Document $document): bool
    {
        $userId = Auth::user()->user_id ;
        
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
}
