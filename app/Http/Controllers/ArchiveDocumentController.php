<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Archive;
use App\Models\SentDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Illuminate\Http\Request;
class ArchiveDocumentController extends Controller
{
    /**
     * Display archived documents
     */
    public function index(Request $request)
    {
        $currentUserId = Auth::user()->user_id;
        $search = trim((string) $request->input('search', ''));
        $documentType = trim((string) $request->input('document_type', ''));
        $perPage = (int) $request->input('per_page', 15);

        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $documents = Archive::where(function($query) use ($currentUserId) {
                $query->where('user_id', $currentUserId)
                      ->orWhereHas('document', function($q) use ($currentUserId) {
                          $q->where('user_id', $currentUserId);
                      });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($archiveQuery) use ($search) {
                    $archiveQuery->where('file_name', 'like', "%{$search}%")
                        ->orWhereHas('document', function ($docQuery) use ($search) {
                            $docQuery->where('tracking_code', 'like', "%{$search}%")
                                ->orWhere('purpose', 'like', "%{$search}%")
                                ->orWhere('file_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($documentType !== '', function ($query) use ($documentType) {
                $query->whereHas('document.documentType', function ($docTypeQuery) use ($documentType) {
                    $docTypeQuery->where('type_name', $documentType);
                });
            })
            ->whereNull('deleted_at')
            ->whereNull('restored_at')
            ->with('document', 'document.documentType')
            ->orderBy('archive_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('content.documents.archived-documents', compact('documents'));
    }

    /**
     * Display restored documents
     */
    public function restored()
    {
        $currentUserId = Auth::user()->user_id;
        $search = trim((string) request('search'));
        $perPage = max(1, (int) request('per_page', 10));

        // Get all archive records that have been restored by this user
        $restoredDocuments = Archive::where('user_id', $currentUserId)
            ->whereNotNull('restored_at')
            ->whereNull('deleted_at')
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('document', function ($q) use ($search) {
                    $q->where('tracking_code', 'like', "%{$search}%")
                      ->orWhere('file_name', 'like', "%{$search}%")
                      ->orWhere('purpose', 'like', "%{$search}%");
                })->orWhere('file_name', 'like', "%{$search}%");
            })
            ->with('document.documentType')
            ->orderBy('restored_at', 'desc')
            ->paginate($perPage);

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
            logActivity(Auth::id(), 'archive', 'Archived document: ' . $document->file_name);
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

            // Update document status to 'archived'
            $document->update(['status' => 'archived']);

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
                logActivity(Auth::id(), 'archive_attempt', 'Attempted to archive document without authorization');
                return response()->json([
                    'success' => false,
                    'message' => 'You are not a recipient of this document'
                ], 403);
            }
            abort(403, 'You are not a recipient of this document');
        }

        try {
            // Check if an active (non-restored) archive record already exists
            $existingArchive = Archive::where('document_id', $document->document_id)
                ->where('user_id', $currentUserId)
                ->whereNull('deleted_at')
                ->first();

            if ($existingArchive && is_null($existingArchive->restored_at)) {
                if (request()->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Document is already archived'
                    ], 400);
                }
                return redirect()->back()->with('error', 'Document is already archived');
            }

            if ($existingArchive && $existingArchive->restored_at) {
                // Re-archive a previously restored document
                $existingArchive->update([
                    'archive_at'  => now(),
                    'restored_at' => null,
                ]);
            } else {
                // Create a fresh archive record for this receiver
                Archive::create([
                    'user_id'     => $currentUserId,
                    'document_id' => $document->document_id,
                    'file_path'   => $document->file_path,
                    'file_name'   => $document->file_name,
                    'archive_at'  => now(),
                ]);
            }

            // Remove the received document entry for this user (if still present)
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
            // Mark as restored — keep the archive record as history
            $archive->update(['restored_at' => now()]);

            // Only update document status to 'restored' if the archiver is the sender
            $document = Document::where('document_id', $archive->document_id)->first();
            if ($document && $document->user_id === $currentUserId) {
                $document->update([
                    'status' => 'restored',
                    'restored_at' => now(),
                ]);
            }

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document restored successfully'
                ]);
            }

            return redirect()->route('documents.restored')->with('success', 'Document restored successfully');
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
     * Soft delete an archived document (only marks as deleted, doesn't restore)
     */
    public function softDeleteArchive($archiveId)
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

            // Soft delete the archive record (marks deleted_at timestamp)
            $archive->delete();

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Archive record deleted'
                ]);
            }

            return redirect()->back()->with('success', 'Archive record deleted');
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

            $documentId = $archive->document_id;

            // Force delete only the archive record, not the document
            $archive->forceDelete();

            // Update document status to active (no longer archived)
            Document::where('document_id', $documentId)->update(['status' => 'active']);

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
