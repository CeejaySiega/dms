<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Archive;
use App\Models\SentDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
class ArchiveDocumentController extends Controller
{
    /**
     * Display archived documents
     */
    public function index()
    {
        $currentUserId = Auth::user()->user_id;
        $documents = Archive::where(function($query) use ($currentUserId) {
                $query->where('user_id', $currentUserId)
                      ->orWhereHas('document', function($q) use ($currentUserId) {
                          $q->where('user_id', $currentUserId);
                      });
            })
            ->with('document', 'document.documentType')
            ->orderBy('archive_at', 'desc')
            ->paginate(15);

        return view('content.documents.archived-documents', compact('documents'));
    }

    /**
     * Display restored documents
     */
    /**
     * Display restored documents
     */
    public function restored()
    {
        $currentUserId = Auth::user()->user_id;
        $search = trim((string) request('search'));

        // Get documents that were archived and then restored by this user
        $restoredDocuments = Document::where('user_id', $currentUserId)
            ->whereNotNull('restored_at')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('tracking_code', 'like', "%{$search}%")
                      ->orWhere('file_name', 'like', "%{$search}%")
                      ->orWhere('purpose', 'like', "%{$search}%");
                });
            })
            ->with('documentType')
            ->orderBy('restored_at', 'desc')
            ->paginate(15);

        return view('content.documents.restored-documents', compact('restoredDocuments'));
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
            // Create archive record with current user (the person archiving)
            Archive::create([
                'user_id' => Auth::user()->user_id,
                'document_id' => $document->document_id,
                'file_path' => $document->file_path,
                'file_name' => $document->file_name,
                'archive_at' => now(),
            ]);

            // Remove from received list for this user, if present
            \App\Models\ReceivedDocument::where('document_id', $document->document_id)
                ->where('user_id', Auth::user()->user_id)
                ->delete();

            // DO NOT soft-delete the document - it's still needed by recipients!
            // Only archive it in the Archive table for the sender

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

    public function archiveAsReceiver(Document $document)
    {
        $currentUserId = Auth::user()->user_id;
        
        // Check if user is a recipient of this document
        $isRecipient = \App\Models\Recipient::whereHas('route', function($query) use ($document) {
            $query->where('document_id', $document->document_id);
        })
        ->where('user_id', $currentUserId)
        ->exists();

        if (!$isRecipient) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not a recipient of this document'
                ], 403);
            }
            abort(403, 'You are not a recipient of this document');
        }

        try {
            // Check if already archived by checking ReceivedDocument archive_at timestamp
            $checkArchived = \App\Models\ReceivedDocument::where('document_id', $document->document_id)
                ->where('user_id', $currentUserId)
                ->whereNotNull('archive_at')
                ->first();

            if ($checkArchived) {
                if (request()->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Document is already archived'
                    ], 400);
                }
                return redirect()->back()->with('error', 'Document is already archived');
            }

            // Create archive record for this receiver
            Archive::create([
                'user_id' => $currentUserId,
                'document_id' => $document->document_id,
                'file_path' => $document->file_path,
                'file_name' => $document->file_name,
                'archive_at' => now(),
            ]);

            // Soft delete ReceivedDocument for this user
            \App\Models\ReceivedDocument::where('document_id', $document->document_id)
                ->where('user_id', $currentUserId)
                ->update([
                    'archive_at' => now()
                ]);
            
            // Soft delete the received document
            \App\Models\ReceivedDocument::where('document_id', $document->document_id)
                ->where('user_id', $currentUserId)
                ->delete();

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document archived successfully'
                ]);
            }

            return redirect()->back()->with('success', 'Document archived to your personal archive');
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
    public function restore($archiveId)
    {
        $currentUserId = Auth::user()->user_id;
        
        // Find archive by archive_id with authorization check
        $archive = Archive::where('archive_id', $archiveId)
            ->where('user_id', $currentUserId)
            ->firstOrFail();

        try {
            // Delete archive record
            $archive->delete();

            // Mark document as restored
            $document = Document::where('document_id', $archive->document_id)->first();
            if ($document) {
                $document->update(['restored_at' => now()]);
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
    public function destroy($archiveId)
    {
        $currentUserId = Auth::user()->user_id;
        
        try {
            // Find archive by archive_id with authorization check
            $archive = Archive::where('archive_id', $archiveId)
                ->where(function($query) use ($currentUserId) {
                    $query->where('user_id', $currentUserId)
                          ->orWhereHas('document', function($q) use ($currentUserId) {
                              $q->where('user_id', $currentUserId);
                          });
                })
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
