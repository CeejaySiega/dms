@extends('layouts.contentNavbarLayout')

@section('title', 'All Documents')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header with Breadcrumb -->
    <div class="mb-4">
        <h4 class="fw-bold mb-2"><i class="bx bx-file me-2"></i>My Documents</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb-style1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard-analytics') }}">Home</a>
                </li>
                <li class="breadcrumb-item active">My Documents</li>
            </ol>
        </nav>
    </div>

{{-- 
    {{-- <div class="row">
        <div class="col-md-12">
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bx bx-file me-2"></i>My Documents
                        @if(($inboxCount ?? 0) > 0)
                            <span class="badge bg-danger ms-2">{{ $inboxCount }}</span>
                        @endif
                    </h5>
                    <a href="{{ route('documents.send') }}" class="btn btn-primary" title="Send New Document">
                        <i class="bx bx-plus"></i>
                    </a>
                </div> 
            </div> --}}

            <!-- Search Bar -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('documents.all') }}" id="searchForm">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Search by Tracking Code or File Name</label>
                                <input type="text" 
                                       class="form-control" 
                                       name="search" 
                                       placeholder="Enter tracking code or file name..."
                                       value="{{ request('search') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Document Type</label>
                                <select class="form-select" name="document_type">
                                    <option value="">All Types</option>
                                    <option value="Memorandum" {{ request('document_type') == 'Memorandum' ? 'selected' : '' }}>Memorandum</option>
                                    <option value="Request Letter" {{ request('document_type') == 'Request Letter' ? 'selected' : '' }}>Request Letter</option>
                                    <option value="Office Order" {{ request('document_type') == 'Office Order' ? 'selected' : '' }}>Office Order</option>
                                    <option value="Endorsement" {{ request('document_type') == 'Endorsement' ? 'selected' : '' }}>Endorsement</option>
                                    <option value="Circular" {{ request('document_type') == 'Circular' ? 'selected' : '' }}>Circular</option>
                                    <option value="Report" {{ request('document_type') == 'Report' ? 'selected' : '' }}>Report</option>
                                    <option value="Communication Letter" {{ request('document_type') == 'Communication Letter' ? 'selected' : '' }}>Communication Letter</option>
                                    <option value="Travel Order" {{ request('document_type') == 'Travel Order' ? 'selected' : '' }}>Travel Order</option>
                                    <option value="Purchase Request" {{ request('document_type') == 'Purchase Request' ? 'selected' : '' }}>Purchase Request</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Sent</option>
                                    <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Receive</option>
                                    <option value="restored" {{ request('status') == 'restored' ? 'selected' : '' }}>Restored from archive</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex justify-content-end align-items-center gap-2">
                                    <button type="submit" class="btn btn-primary" title="Search">
                                        <i class="bx bx-search"></i>
                                    </button>
                                    <a href="{{ route('documents.all') }}" class="btn btn-outline-secondary" title="Reset Search">
                                        <i class="bx bx-reset"></i>
                                    </a>
                                    <a href="{{ route('documents.send') }}" class="btn btn-primary" title="Send New Document">
                                         <i class="bx bx-plus"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
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
                                <tr>
                                    <td>
                                        <span class="badge bg-primary">{{ $document->tracking_code }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $route = \App\Models\DocumentRoute::where('document_id', $document->document_id)->first();
                                            $recipients = $route ? \App\Models\Recipient::with('user.employee')
                                                ->where('route_id', $route->route_id)
                                                ->get() : collect();
                                            $isGroupSend = $recipients->count() > 1;
                                        @endphp
                                        @if($isGroupSend)
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#recipientsModal{{ $document->document_id }}">
                                                <i class="bx bx-group me-1"></i> Group ({{ $recipients->count() }})
                                            </button>
                                        @elseif($recipients->count() > 0)
                                            @php
                                                $recipient = $recipients->first();
                                            @endphp
                                            @if($recipient->user->employee)
                                                {{ $recipient->user->employee->firstname }} {{ $recipient->user->employee->lastname }}
                                            @else
                                                {{ $recipient->user->name }}
                                            @endif
                                        @else
                                            <span class="text-muted"><i>No recipients</i></span>
                                        @endif
                                    </td>
                                    <td>{{ $document->documentType->type_name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $document->purpose }}">
                                            {{ $document->purpose }}
                                        </span>
                                    </td>
                                    <td>
                                        <i class="bx bx-file me-1"></i>{{ $document->file_name }}
                                    </td>
                                    <td>
                                        @php
                                            $route = $route ?? \App\Models\DocumentRoute::where('document_id', $document->document_id)->first();
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
                                            
                                            // Check if document itself has restored status first
                                            if ($document->status === 'restored') {
                                                $statusValue = 'restored';
                                            } elseif ($recipients->isNotEmpty()) {
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
                                                } else {
                                                    $statusValue = 'pending';
                                                }
                                            }

                                            $statusClass = match($statusValue) {
                                                'pending' => 'bg-warning',
                                                'receive', 'received' => 'bg-info',
                                                'archived' => 'bg-secondary',
                                                'restored' => 'bg-success',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ ucfirst($statusValue) }}</span>
                                    </td>
                                    <td>
                                        <small>{{ $document->created_at->format('M d, Y h:i A') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            @if($statusValue !== 'pending')
                                            <form action="{{ route('documents.delete-document', encryptId($document->document_id)) }}"
                                                  method="POST"
                                                  class="d-inline delete-document-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-danger"
                                                        title="Delete">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </form>
                                            @if($document->status !== 'archived')
                                            <form action="{{ route('documents.archive', encryptId($document->document_id)) }}"
                                                  method="POST"
                                                  class="d-inline archive-form">
                                                @csrf
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        title="Archive">
                                                    <i class="bx bx-archive"></i>
                                                </button>
                                            </form>
                                            @endif
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
                            $route = \App\Models\DocumentRoute::where('document_id', $document->document_id)->first();
                            $recipients = $route ? \App\Models\Recipient::with('user.employee')
                                ->where('route_id', $route->route_id)
                                ->get() : collect();
                            $isGroupSend = $recipients->count() > 1;
                        @endphp
                        @if($isGroupSend)
                        <div class="modal fade" id="recipientsModal{{ $document->document_id }}" tabindex="-1" aria-labelledby="recipientsModalLabel{{ $document->document_id }}" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="recipientsModalLabel{{ $document->document_id }}">
                                            <i class="bx bx-group me-2"></i>Recipients - {{ $document->tracking_code }}
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <ul class="list-group">
                                            @foreach($recipients as $recipient)
                                                <li class="list-group-item">
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
                                                            <div>
                                                                <div class="d-flex align-items-center gap-2">
                                                                <span class="badge bg-info">{{ ucfirst($recipient->action ?? 'pending') }}</span>
                                                                </div>
                                                            </div>
                                                            <small class="text-muted">
                                                                <i class="bx bx-envelope me-1"></i>{{ $recipient->user->email }}
                                                            </small>
                                                        </div>
                                                    </div>
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
                        {{ $documents->appends(request()->query())->links() }}
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="bx bx-folder-open" style="font-size: 64px; color: #ccc;"></i>
                        <p class="text-muted mt-3">No documents found.</p>
                        {{-- <a href="{{ route('documents.send') }}" class="btn btn-primary mt-2">
                            <i class="bx bx-plus me-1"></i> Send Your First Document
                        </a> --}}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

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

    // Delete document confirmation (sender only)
    $('.delete-document-form').on('submit', function(e) {
        e.preventDefault();
        const form = this;

        Swal.fire({
            title: 'Delete Document?'
            ,html: 'Delete this document from your list? Receivers will keep their copies.'
            ,icon: 'warning'
            ,showCancelButton: true
            ,confirmButtonColor: '#d33'
            ,cancelButtonColor: '#6c757d'
            ,confirmButtonText: 'Yes, delete'
            ,cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            Swal.fire({
                title: 'Deleting...'
                ,text: 'Please wait while we remove the document.'
                ,allowOutsideClick: false
                ,didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: $(form).attr('action')
                ,type: 'POST'
                ,dataType: 'json'
                ,data: $(form).serialize()
                ,success: function(response) {
                    Swal.fire({
                        icon: 'success'
                        ,title: 'Deleted'
                        ,text: response.message || 'Document removed for sender.'
                        ,confirmButtonColor: '#3085d6'
                    }).then(() => {
                        location.reload();
                    });
                }
                ,error: function(xhr) {
                    Swal.fire({
                        icon: 'error'
                        ,title: 'Error!'
                        ,text: xhr.responseJSON?.message || 'Failed to delete document.'
                        ,confirmButtonColor: '#d33'
                    });
                }
            });
        });
    });

});
</script>
@endsection
@endsection
