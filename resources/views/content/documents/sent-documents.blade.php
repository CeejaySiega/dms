@extends('layouts.contentNavbarLayout')

@section('title', 'Sent Documents')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-md-12">
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bx bx-send me-2"></i>Sent Documents
                        @if(($inboxCount ?? 0) > 0)
                            <span class="badge bg-danger ms-2">{{ $inboxCount }}</span>
                        @endif
                    </h5>
                    <a href="{{ route('documents.send') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i> Send New Document
                    </a>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="card mb-4">
                <div class="card-body">
                    <ul class="nav nav-pills mb-0" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a href="{{ route('documents.all') }}" class="nav-link">
                                <i class="bx bx-list-ul me-1"></i> All Documents
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="{{ route('documents.incoming') }}" class="nav-link">
                                <i class="bx bxs-inbox me-1"></i> Inbox
                                @if(($inboxCount ?? 0) > 0)
                                    <span class="badge bg-warning text-dark ms-1">{{ $inboxCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="{{ route('documents.received') }}" class="nav-link">
                                <i class="bx bxs-download me-1"></i> Received
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="{{ route('documents.sent') }}" class="nav-link active">
                                <i class="bx bx-send me-1"></i> Sent
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Documents List -->
            <div class="card">
                <div class="card-body">
                    @if($documents->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center">
                            <thead>
                                <tr>
                                    <th>Tracking Code</th>
                                    <th>Sent To</th>
                                    <th>Document Type</th>
                                    <th>Purpose</th>
                                    <th>File Name</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Sent Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($documents as $document)
                                @php
                                    $route = \App\Models\DocumentRoute::with('group')
                                        ->where('document_id', $document->document_id)
                                        ->first();
                                    $recipients = $route ? \App\Models\Recipient::with('user.employee')
                                        ->where('route_id', $route->route_id)
                                        ->get() : collect();
                                    $hasPendingRecipients = $recipients->contains(function ($recipient) {
                                        return is_null($recipient->action) || $recipient->action === 'pending';
                                    });
                                    $groupName = $route?->group 
                                        ? ($route->group->position . ' - ' . getCampusName($route->group->campus))
                                        : null;
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge bg-primary">{{ $document->tracking_code ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        @if($groupName)
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#recipientsModal-{{ $document->document_id }}"
                                                    title="View Recipients">
                                                <i class="bx bx-group me-1"></i>{{ $groupName }}
                                            </button>
                                        @elseif($recipients->count() > 1)
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#recipientsModal-{{ $document->document_id }}"
                                                    title="View Recipients">
                                                <i class="bx bx-group me-1"></i>Recipients
                                            </button>
                                        @elseif($recipients->count() === 1)
                                            @php
                                                $recipient = $recipients->first();
                                                $receiveStatus = $recipient->action ?: 'pending';
                                                $receiveClass = match($receiveStatus) {
                                                    'receive' => 'bg-success',
                                                    'approved' => 'bg-primary',
                                                    'rejected' => 'bg-danger',
                                                    default => 'bg-warning'
                                                };
                                            @endphp
                                            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                                <i class="bx bx-user-circle"></i>
                                                <span>
                                                    @if($recipient->user->employee)
                                                        {{ $recipient->user->employee->firstname }} {{ $recipient->user->employee->lastname }}
                                                    @else
                                                        {{ $recipient->user->name }}
                                                    @endif
                                                </span>
                                                @if($receiveStatus === 'pending')
                                                    <button type="button"
                                                            class="btn btn-sm btn-outline-danger unsend-recipient-btn"
                                                            data-document-id="{{ encryptId($document->document_id) }}"
                                                            data-recipient-id="{{ encryptId($recipient->recipient_id) }}"
                                                            data-recipient-name="{{ $recipient->user->employee ? $recipient->user->employee->firstname . ' ' . $recipient->user->employee->lastname : $recipient->user->name }}"
                                                            title="Unsend to this recipient">
                                                        <i class="bx bx-user-x"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted"><i>No recipients</i></span>
                                        @endif
                                    </td>
                                    <td>{{ $document->documentType?->type_name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $document->purpose }}">
                                            {{ $document->purpose }}
                                        </span>
                                    </td>
                                    <td>
                                        <i class="bx bx-file me-1"></i>{{ $document->file_name ?? 'N/A' }}
                                    </td>
                                    <td>
                                        @php
                                            $priorityValue = $route?->priority ?? 'normal';
                                            $priorityClass = match($priorityValue) {
                                                'urgent' => 'bg-danger',
                                                'high' => 'bg-warning',
                                                'low' => 'bg-secondary',
                                                default => 'bg-primary'
                                            };
                                        @endphp
                                        <span class="badge {{ $priorityClass }}">{{ ucfirst($priorityValue) }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $statusValue = $document->status;
                                            if ($recipients->isNotEmpty()) {
                                                $actions = $recipients->pluck('action')
                                                    ->filter()
                                                    ->map(fn ($action) => strtolower(trim((string) $action)))
                                                    ->unique();

                                                $hasPending = $recipients->contains(function ($recipient) {
                                                    return is_null($recipient->action) || $recipient->action === 'pending';
                                                });

                                                $hasReceive = $actions->contains('receive')
                                                    || $actions->contains('received')
                                                    || $recipients->whereNotNull('receive_at')->isNotEmpty();

                                                if ($hasPending) {
                                                    $statusValue = 'pending';
                                                } elseif ($hasReceive) {
                                                    $statusValue = 'receive';
                                                } elseif ($actions->contains('approved')) {
                                                    $statusValue = 'approved';
                                                } elseif ($actions->contains('rejected')) {
                                                    $statusValue = 'rejected';
                                                } else {
                                                    $statusValue = 'pending';
                                                }
                                            }

                                            $statusClass = match($statusValue) {
                                                'pending' => 'bg-warning',
                                                'approved' => 'bg-success',
                                                'rejected' => 'bg-danger',
                                                'receive', 'received' => 'bg-info',
                                                'archived' => 'bg-secondary',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ ucfirst($statusValue) }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $sentAt = $document?->created_at;
                                        @endphp
                                        <small>{{ $sentAt ? $sentAt->format('M d, Y h:i A') : 'N/A' }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @if($hasPendingRecipients)
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger unsend-btn" 
                                                    data-document-id="{{ encryptId($document->document_id) }}"
                                                    data-tracking-code="{{ $document->tracking_code }}"
                                                    title="Unsend Pending Recipients">
                                                <i class="bx bx-x-circle"></i>
                                            </button>
                                            @endif
                                            <a href="{{ route('documents.download', encryptId($document->document_id)) }}" 
                                               class="btn btn-sm btn-outline-success" 
                                               title="Download">
                                                <i class="bx bx-download"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Modals for Recipients -->
                    @foreach($documents as $document)
                        @php
                            $route = \App\Models\DocumentRoute::with('group')
                                ->where('document_id', $document->document_id)
                                ->first();
                            $recipients = $route ? \App\Models\Recipient::with('user.employee')
                                ->where('route_id', $route->route_id)
                                ->get() : collect();
                        @endphp
                        @if($route?->group_id || $recipients->count() > 1)
                        <div class="modal fade" id="recipientsModal-{{ $document->document_id }}" tabindex="-1" aria-labelledby="recipientsModalLabel-{{ $document->document_id }}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="recipientsModalLabel-{{ $document->document_id }}">
                                            Recipients - {{ $document->tracking_code }}
                                            @if($route?->group_id && $route->group)
                                                <small class="text-muted ms-2">({{ $route->group->position }} - {{ getCampusName($route->group->campus) }})</small>
                                            @endif
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <ul class="list-group">
                                            @foreach($recipients as $recipient)
                                                @php
                                                    $receiveStatus = $recipient->action ?: 'pending';
                                                    $receiveClass = match($receiveStatus) {
                                                        'receive' => 'bg-success',
                                                        'approved' => 'bg-primary',
                                                        'rejected' => 'bg-danger',
                                                        default => 'bg-warning'
                                                    };
                                                @endphp
                                                <li class="list-group-item d-flex align-items-center justify-content-between">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="bx bx-user-circle fs-4"></i>
                                                        <div>
                                                            <div class="fw-semibold">
                                                                @if($recipient->user->employee)
                                                                    {{ $recipient->user->employee->firstname }} {{ $recipient->user->employee->lastname }}
                                                                @else
                                                                    {{ $recipient->user->name }}
                                                                @endif
                                                            </div>
                                                            <small class="text-muted">
                                                                <i class="bx bx-envelope me-1"></i>{{ $recipient->user->email }}
                                                            </small>
                                                        </div>
                                                        <span class="badge {{ $receiveClass }}">{{ ucfirst($receiveStatus) }}</span>
                                                    </div>
                                                    @if($receiveStatus === 'pending')
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-danger unsend-recipient-btn"
                                                                data-document-id="{{ encryptId($document->document_id) }}"
                                                                data-recipient-id="{{ encryptId($recipient->recipient_id) }}"
                                                                data-recipient-name="{{ $recipient->user->employee ? $recipient->user->employee->firstname . ' ' . $recipient->user->employee->lastname : $recipient->user->name }}"
                                                                title="Unsend to this recipient">
                                                            <i class="bx bx-user-x"></i>
                                                        </button>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    @endforeach

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $documents->links() }}
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="bx bx-send" style="font-size: 64px; color: #ccc;"></i>
                        <p class="text-muted mt-3">No sent documents found.</p>
                        <a href="{{ route('documents.send') }}" class="btn btn-primary mt-2">
                            <i class="bx bx-plus me-1"></i> Send Your First Document
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Setup AJAX headers
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    });

    // Unsend document confirmation
    $('.unsend-btn').on('click', function() {
        const documentId = $(this).data('document-id');
        const trackingCode = $(this).data('tracking-code');

        Swal.fire({
            title: 'Unsend Document?',
            html: `Remove all <strong>pending</strong> recipients from this document?<br><br><strong>Tracking Code:</strong> ${trackingCode}<br><br>Recipients who already received/approved/rejected will remain.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bx bx-trash"></i> Yes, remove pending',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we remove pending recipients.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: '/documents/' + documentId,
                    type: 'DELETE',
                    dataType: 'json',
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Unsent!',
                            text: response.message || 'The document has been successfully unsent.',
                            confirmButtonColor: '#3085d6'
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        console.error('Error:', xhr);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'Failed to unsend document. Please try again.',
                            confirmButtonColor: '#d33'
                        });
                    }
                });
            }
        });
    });

    // Archive document confirmation
    $('.archive-form').on('submit', function(e) {
        e.preventDefault();
        const form = this;

        Swal.fire({
            title: 'Archive Document?',
            text: 'Are you sure you want to archive this document?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, archive it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    // Unsend per-recipient confirmation
    $('.unsend-recipient-btn').on('click', function() {
        const documentId = $(this).data('document-id');
        const recipientId = $(this).data('recipient-id');
        const recipientName = $(this).data('recipient-name');

        Swal.fire({
            title: 'Unsend to Recipient?'
            ,html: `Remove <strong>${recipientName}</strong> from this document?`
            ,icon: 'warning'
            ,showCancelButton: true
            ,confirmButtonColor: '#d33'
            ,cancelButtonColor: '#6c757d'
            ,confirmButtonText: 'Yes, unsend'
            ,cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Removing...'
                    ,text: 'Please wait while we remove this recipient.'
                    ,allowOutsideClick: false
                    ,didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    url: `/documents/${documentId}/recipients/${recipientId}`
                    ,type: 'DELETE'
                    ,dataType: 'json'
                    ,success: function(response) {
                        Swal.fire({
                            icon: 'success'
                            ,title: 'Removed!'
                            ,text: response.message || 'Recipient removed successfully.'
                            ,confirmButtonColor: '#3085d6'
                        }).then(() => {
                            location.reload();
                        });
                    }
                    ,error: function(xhr) {
                        Swal.fire({
                            icon: 'error'
                            ,title: 'Error!'
                            ,text: xhr.responseJSON?.message || 'Failed to remove recipient.'
                            ,confirmButtonColor: '#d33'
                        });
                    }
                });
            }
        });
    });
});
</script>
@endsection
