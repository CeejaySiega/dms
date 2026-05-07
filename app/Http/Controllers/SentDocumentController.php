<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Mail;
use App\Mail\DocumentNotification;
use App\Models\Document;
use App\Models\DocumentRoute;
use App\Models\Recipient;
use App\Models\ReceivedDocument;
use App\Models\SentDocument;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SentDocumentController extends Controller
{
    public function documentTrail(Request $request)
    {
        $userId = Auth::id();
        $search = trim((string) $request->input('search', ''));
        $sendMode = trim((string) $request->input('send_mode', ''));
        $perPage = (int) $request->input('per_page', 15);

        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $documents = Document::query()
            ->whereNull('unsend_at')
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', 'archived');
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($docQuery) use ($search) {
                    $docQuery->where('tracking_code', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%")
                        ->orWhere('file_name', 'like', "%{$search}%");
                });
            })
            ->when($sendMode === 'group', function ($query) {
                $query->whereHas('routes', function ($routeQuery) {
                    $routeQuery->whereNull('unsend_at')->whereNotNull('group_id');
                });
            })
            ->when($sendMode === 'individual', function ($query) {
                $query->whereHas('routes', function ($routeQuery) {
                    $routeQuery->whereNull('unsend_at')->whereNull('group_id');
                });
            })
            ->where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                    ->orWhereHas('routes', function ($routeQuery) use ($userId) {
                        $routeQuery->whereNull('unsend_at')
                            ->where(function ($routeUserQuery) use ($userId) {
                                $routeUserQuery->where('sender_id', $userId)
                                    ->orWhere('receiver_id', $userId);
                            });
                    })
                    ->orWhereHas('recipients', function ($recipientQuery) use ($userId) {
                        $recipientQuery->where('user_id', $userId)
                            ->whereNull('deleted_at')
                            ->whereHas('route', function ($routeQuery) {
                                $routeQuery->whereNull('unsend_at');
                            })
                            ->whereIn('recipient_id', function ($sentQuery) {
                                $sentQuery->select('recipient_id')
                                    ->from('sent_documents')
                                    ->whereNull('unsend_at');
                            });
                    });
            })
            ->with([
                'documentType',
                'user.employee',
                'routes' => function ($query) {
                    $query->whereNull('unsend_at')
                        ->select(['route_id', 'document_id', 'group_id', 'receiver_id'])
                        ->with(['receiverUser.employee']);
                },
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('content.trail.document-trail', compact('documents'));
    }

    public function trailData(Document $document): JsonResponse
    {
        $isOwner = (int) Auth::id() === (int) $document->user_id;

        $isRecipientSide = Recipient::where('user_id', Auth::id())
            ->whereNull('deleted_at')
            ->whereHas('route', function ($query) use ($document) {
                $query->where('document_id', $document->document_id)
                    ->whereNull('unsend_at');
            })
            ->exists();

        $isReceivedSide = ReceivedDocument::where('document_id', $document->document_id)
            ->where('user_id', Auth::id())
            ->exists();

        $canView = $isOwner || $isRecipientSide || $isReceivedSide;

        if (!$canView) {
            return response()->json([
                'message' => 'Unauthorized to view this trail.'
            ], 403);
        }

        $routes = DocumentRoute::where('document_id', $document->document_id)
            ->whereNull('unsend_at')
            ->with([
                'sender.employee.department',
                'receiverUser.employee.department',
                'group',
            ])
            ->orderBy('route_id')
            ->get();

        $routeIds = $routes->pluck('route_id');

        $recipients = Recipient::whereIn('route_id', $routeIds)
            ->whereNull('deleted_at')
            ->with(['user.employee.department'])
            ->get();
        $sentDocs = SentDocument::whereIn('route_id', $routeIds)
            ->whereNull('unsend_at')
            ->get()
            ->groupBy('route_id');

        $toIso = static function ($value): ?string {
            if (is_null($value)) {
                return null;
            }

            if ($value instanceof \Carbon\CarbonInterface) {
                return $value->toIso8601String();
            }

            $ts = strtotime((string) $value);

            return $ts !== false ? date(DATE_ATOM, $ts) : null;
        };

        $trail = [];
        $pendingCandidates = [];

        $isGroupSend = $routes->contains(fn ($route) => !is_null($route->group_id));
        $groupNames = $routes->pluck('group.position')->filter()->unique()->values();
        $recipientCount = $recipients->pluck('user_id')->unique()->count();

        foreach ($routes as $route) {
            $sender = $route->sender;
            $receiver = $route->receiverUser;

            $routeRecipients = $recipients->where('route_id', $route->route_id);
            $receiverRecipient = $routeRecipients
                ->first(fn ($r) => (int) $r->user_id === (int) ($route->receiver_id ?? 0))
                ?? $routeRecipients->first();

            if (!$receiver && $receiverRecipient) {
                $receiver = $receiverRecipient->user;
            }

            $routeSentAt = $receiverRecipient?->sent_at
                ?? optional($sentDocs->get($route->route_id))->min('sent_at')
                ?? $document->created_at;

            $isInitialSend = is_null($route->forward_at) && (int) ($route->sender_id ?? 0) === (int) $document->user_id;

            $trail[] = [
                'type' => $isInitialSend ? 'sent' : 'forwarded',
                'user_id' => $sender?->user_id,
                'actor_name' => $this->trailUserName($sender),
                'department' => $this->trailDepartment($sender),
                'campus' => $this->trailCampus($sender),
                'action_at' => $toIso($route->forward_at ?? $routeSentAt),
                'forwarded_to' => $this->trailUserName($receiver),
                'remarks' => null,
                '_route_id' => $route->route_id,
            ];

            $action = strtolower(trim((string) ($receiverRecipient?->action ?? '')));
            $hasActed = in_array($action, ['receive', 'received', 'rejected'], true)
                || !is_null($receiverRecipient?->receive_at);

            if ($hasActed) {
                $trail[] = [
                    'type' => 'received',
                    'user_id' => $receiver?->user_id,
                    'actor_name' => $this->trailUserName($receiver),
                    'department' => $this->trailDepartment($receiver),
                    'campus' => $this->trailCampus($receiver),
                    'action_at' => $toIso($receiverRecipient?->receive_at ?? $routeSentAt),
                    'forwarded_to' => null,
                    'remarks' => in_array($action, ['rejected'], true) ? ucfirst($action) : null,
                    '_route_id' => $route->route_id,
                ];
            } elseif ($receiver) {
                $pendingCandidates[] = [
                    'route_id' => $route->route_id,
                    'user' => $receiver,
                    'at' => $route->forward_at ?? $routeSentAt,
                ];
            }
        }

        if (!empty($pendingCandidates)) {
            usort($pendingCandidates, function (array $a, array $b) {
                $ta = $a['at'] ? strtotime((string) $a['at']) : 0;
                $tb = $b['at'] ? strtotime((string) $b['at']) : 0;

                if ($ta === $tb) {
                    return ($a['route_id'] ?? 0) <=> ($b['route_id'] ?? 0);
                }

                return $ta <=> $tb;
            });

            $activeCandidate = array_pop($pendingCandidates);

            $trail[] = [
                'type' => 'active',
                'user_id' => $activeCandidate['user']->user_id,
                'actor_name' => $this->trailUserName($activeCandidate['user']),
                'department' => $this->trailDepartment($activeCandidate['user']),
                'campus' => $this->trailCampus($activeCandidate['user']),
                'action_at' => $toIso($activeCandidate['at']),
                'forwarded_to' => null,
                'remarks' => null,
                '_route_id' => $activeCandidate['route_id'],
            ];

            foreach ($pendingCandidates as $candidate) {
                $trail[] = [
                    'type' => 'pending',
                    'user_id' => $candidate['user']->user_id,
                    'actor_name' => $this->trailUserName($candidate['user']),
                    'department' => $this->trailDepartment($candidate['user']),
                    'campus' => $this->trailCampus($candidate['user']),
                    'action_at' => $toIso($candidate['at']),
                    'forwarded_to' => null,
                    'remarks' => null,
                    '_route_id' => $candidate['route_id'],
                ];
            }
        }

        $typeOrder = [
            'sent' => 1,
            'forwarded' => 2,
            'received' => 3,
            'active' => 4,
            'pending' => 5,
        ];

        usort($trail, function (array $a, array $b) use ($typeOrder) {
            $ta = $a['action_at'] ? strtotime((string) $a['action_at']) : 0;
            $tb = $b['action_at'] ? strtotime((string) $b['action_at']) : 0;

            if ($ta === $tb) {
                $typeCmp = ($typeOrder[$a['type']] ?? 99) <=> ($typeOrder[$b['type']] ?? 99);
                if ($typeCmp !== 0) {
                    return $typeCmp;
                }

                return ($a['_route_id'] ?? 0) <=> ($b['_route_id'] ?? 0);
            }

            return $ta <=> $tb;
        });

        // For batch/group sends, multiple routes can create identical sender steps.
        // Keep only the first "sent" entry per sender in the final trail output.
        $seenSenders = [];
        $trail = array_values(array_filter($trail, function (array $step) use (&$seenSenders) {
            if (($step['type'] ?? null) !== 'sent') {
                return true;
            }

            $senderKey = $step['user_id'] ?? ('name:' . ($step['actor_name'] ?? 'unknown'));

            if (isset($seenSenders[$senderKey])) {
                return false;
            }

            $seenSenders[$senderKey] = true;

            return true;
        }));

        $trail = array_map(function (array $step) {
            unset($step['_route_id']);

            return $step;
        }, $trail);

        return response()->json([
            'document_id' => $document->document_id,
            'trail' => $trail,
            'meta' => [
                'is_group_send' => $isGroupSend,
                'send_mode' => $isGroupSend ? 'group' : 'individual',
                'group_names' => $groupNames,
                'recipient_count' => $recipientCount,
            ],
        ]);
    }

    public function sent(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $perPage = (int) $request->input('per_page', 15);

        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        // Get documents sent/forwarded by current user that are NOT archived for this user
        $documents = Document::whereIn('document_id', function ($query) {
                $query->select('document_id')
                    ->from('sent_documents')
                    ->where('user_id', Auth::id())
                    ->whereNull('unsend_at');
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($docQuery) use ($search) {
                    $docQuery->where('tracking_code', 'like', "%{$search}%")
                        ->orWhere('file_name', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->whereNotIn('document_id', function($query) {
                $query->select('document_id')
                      ->from('archives')
                      ->where('user_id', Auth::id());
            })
            ->whereNull('unsend_at')
            ->whereHas('recipients', function ($query) {
                $query->whereNull('unsend_at');
            })
            ->with(['documentType', 'recipients' => function ($query) {
                $query->whereNull('unsend_at');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        return view('content.documents.sent-documents', compact('documents'));
    }

    /**
     * Delete (unsend) a document for pending recipients only.
     */
    public function delete(Document $document)
    {
        if (Auth::id() !== $document->user_id) {
            logActivity(Auth::id(), 'delete_attempt', 'Attempted to delete document without authorization');
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
            logActivity(Auth::id(), 'delete_attempt', 'Attempted to delete document without authorization');
            return response()->json([
                'success' => false,
                'message' => 'Only the owner can delete this document'
            ], 403);
        }

        try {
            logActivity(Auth::id(), 'delete', 'Deleted document: ' . $document->file_name);
            $routes   = DocumentRoute::where('document_id', $document->document_id)->get();
            $routeIds = $routes->pluck('route_id');

            $hasReceived = Recipient::withTrashed()
                ->whereIn('route_id', $routeIds)
                ->whereIn('action', ['receive', 'rejected'])
                ->exists();

            if (!$hasReceived && \App\Models\ReceivedDocument::whereIn('route_id', $routeIds)->exists()) {
                $hasReceived = true;
            }

            if ($hasReceived) {
                $pendingRecipients = Recipient::withTrashed()
                    ->whereIn('route_id', $routeIds)
                    ->where(function ($q) {
                        $q->whereNull('action')->orWhere('action', 'pending');
                    })
                    ->get();

               
                SentDocument::whereIn('route_id', $routeIds)
                    ->whereIn('recipient_id', $pendingRecipients->pluck('recipient_id'))
                    ->update([
                        'unsend_at' => now()
                    ]);
                
                SentDocument::whereIn('route_id', $routeIds)
                    ->whereIn('recipient_id', $pendingRecipients->pluck('recipient_id'))
                    ->delete();

             
                Recipient::whereIn('recipient_id', $pendingRecipients->pluck('recipient_id'))
                    ->delete();

                $document->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Document removed for sender. Receivers keep their copy.'
                ]);
            }

            foreach ($routes as $route) {
                if (\App\Models\ReceivedDocument::where('route_id', $route->route_id)->exists()) {
                    $document->delete();

                    return response()->json([
                        'success' => true,
                        'message' => 'Document removed for sender. Receivers keep their copy.'
                    ]);
                }
                SentDocument::where('route_id', $route->route_id)
                    ->update([
                        'unsend_at' => now()
                    ]);
                
                SentDocument::where('route_id', $route->route_id)->delete();
            
                Recipient::withTrashed()
                    ->where('route_id', $route->route_id)
                    ->delete();
                
              
                $route->delete();
            }

            if (Storage::disk('public')->exists($document->file_path)) {
                Storage::disk('public')->delete($document->file_path);
            }

           
            $document->update([
                'unsend_at' => now()
            ]);
            $document->delete();

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
     */
    public function unsendRecipient(Document $document, Recipient $recipient)
    {
    
        $routeIds = DocumentRoute::where('document_id', $document->document_id)
            ->pluck('route_id');

        $recipientModel = Recipient::withTrashed()
            ->where('recipient_id', $recipient->recipient_id)
            ->whereIn('route_id', $routeIds)
            ->first();

        if (!$recipientModel) {
            logActivity(Auth::id(), 'unsend_attempt', 'Attempted to unsend to non-existent recipient');
            return response()->json([
                'success' => false,
                'message' => 'Recipient not found for this document.'
            ], 404);
        }

        // Only the document owner can remove a recipient
        if (Auth::id() !== $document->user_id) {
            logActivity(Auth::id(), 'unsend_attempt', 'Attempted to unsend document without authorization');
            return response()->json([
                'success' => false,
                'message' => 'Only the owner can remove a recipient.'
            ], 403);
        }

        // Cannot remove a recipient who already acted on the document
        if (in_array($recipientModel->action, ['rejected', 'receive', 'received'], true)) {
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
            logActivity(Auth::id(), 'unsend', 'Unsent document: ' . $document->file_name);
            // Mark as unsent and keep the record for filtering/audit.
            SentDocument::where('route_id', $recipientModel->route_id)
                ->where('recipient_id', $recipientModel->recipient_id)
                ->update([
                    'unsend_at' => now()
                ]);

            // Soft delete recipient using the trait's delete method
            $recipientModel->delete();

            // Count remaining non-deleted recipients across all routes for this document
            $remaining = Recipient::whereIn('route_id', $routeIds)
                ->count();

            if ($remaining === 0) {
                // No more recipients: mark related records as unsent instead of hard deleting.
                $routes = DocumentRoute::where('document_id', $document->document_id)->get();
                foreach ($routes as $route) {
                    SentDocument::where('route_id', $route->route_id)
                        ->whereNull('unsend_at')
                        ->update(['unsend_at' => now()]);

                    $route->update(['unsend_at' => now()]);
                }

                $document->update([
                    'unsend_at' => now(),
                    'due_date' => null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Unsend Success.'
                ]);
            }

            // Still has other recipients — recalculate status
            $newStatus = $this->getStatusFromRecipients($document);
            $updateData = [
                'status' => $newStatus,
                'due_date' => null,
            ];

            $document->update($updateData);

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

        $recipientModel = Recipient::withTrashed()
            ->where('user_id', Auth::id())
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

        if (in_array($recipientModel->action, ['rejected'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot unsend after rejection.'
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
            // Mark as unsent and keep the record for filtering/audit.
            SentDocument::where('route_id', $recipientModel->route_id)
                ->where('recipient_id', $recipientModel->recipient_id)
                ->update([
                    'unsend_at' => now()
                ]);

            // Soft delete this specific recipient
            $recipientModel->delete();

            // Check if this route still has any active recipients
            $remainingInRoute = Recipient::where('route_id', $recipientModel->route_id)
                ->count();

            // If this route has no more recipients, delete the route
            if ($remainingInRoute === 0) {
                $route = DocumentRoute::find($recipientModel->route_id);
                if ($route) {
                    SentDocument::where('route_id', $route->route_id)
                        ->whereNull('unsend_at')
                        ->update(['unsend_at' => now()]);

                    $route->update(['unsend_at' => now()]);
                }
            }

            // Check if document still has any active recipients across ALL routes
            $allRemainingRecipients = Recipient::whereIn('route_id', $routeIds)
                ->count();

            if ($allRemainingRecipients === 0) {
                // No more recipients anywhere: mark all related records as unsent.
                $routes = DocumentRoute::where('document_id', $document->document_id)->get();
                foreach ($routes as $route) {
                    SentDocument::where('route_id', $route->route_id)
                        ->whereNull('unsend_at')
                        ->update(['unsend_at' => now()]);

                    $route->update(['unsend_at' => now()]);
                }

                $document->update([
                    'unsend_at' => now(),
                    'due_date' => null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Document unsent successfully.'
                ]);
            }

            // Update document status based on remaining recipients
            $newStatus = $this->getStatusFromRecipients($document);
            $updateData = [
                'status' => $newStatus,
                'due_date' => null,
            ];

            $document->update($updateData);

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
        $routes = DocumentRoute::where('document_id', $document->document_id)
            ->get(['route_id', 'forward_at']);

        $routeIds = $routes->pluck('route_id');
        $hasForwarded = $routes->contains(fn ($route) => !is_null($route->forward_at));

        $actions = Recipient::whereIn('route_id', $routeIds)
            ->whereNull('deleted_at')
            ->pluck('action')
            ->filter()
            ->map(fn ($a) => strtolower(trim((string) $a)))
            ->unique();

        $hasReceive = $actions->contains('receive')
            || $actions->contains('received')
            || Recipient::whereIn('route_id', $routeIds)
                ->whereNull('deleted_at')
                ->whereNotNull('receive_at')
                ->exists();

        if ($hasForwarded)                  return 'forward';
        if ($hasReceive)                    return 'receive';
        if ($actions->contains('rejected')) return 'rejected';

        return 'pending';
    }

    private function trailUserName(?User $user): string
    {
        if (!$user) {
            return 'Unknown User';
        }

        $employee = $user->employee;
        if ($employee && ($employee->firstname || $employee->lastname)) {
            return trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? ''));
        }

        return $user->email ?? ('User #' . $user->user_id);
    }

    private function trailDepartment(?User $user): string
    {
        return $user?->employee?->department?->department_name
            ?? $user?->employee?->department?->name
            ?? 'N/A';
    }

    private function trailCampus(?User $user): string
    {
        return getCampusName($user?->employee?->campus);
    }
}