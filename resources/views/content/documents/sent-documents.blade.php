@extends('layouts.contentNavbarLayout')

@section('title', 'Sent Documents')

@section('content')
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="mb-4">
        <h4 class="fw-bold mb-2"><i class="bx bx-envelope me-2"></i>Mail</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb-style1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard-analytics') }}">Home</a>
                </li>
                <li class="breadcrumb-item inactive">Mail</li>
                <li class="breadcrumb-item active">Sent Documents</li>
            </ol>
        </nav>
    </div>

    <div class="card overflow-hidden" style="min-height: 75vh;">
        <div class="row g-0 h-100">

            {{-- LEFT SIDEBAR --}}
            <div class="col-12 col-md-3 col-lg-2 border-end" style="background: #fff;">
                <div class="p-3">
                    <a href="{{ route('documents.send') }}" class="btn btn-primary w-100 mb-3 fw-semibold">
                        <i class="bx bx-plus me-1"></i> Send Document
                    </a>

                    {{-- Search bar --}}
                    <form class="mb-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text border-end-0 bg-transparent"><i class="bx bx-search text-muted"></i></span>
                            <input class="form-control border-start-0 ps-0" type="search" placeholder="Search documents">
                        </div>
                    </form>

                    <ul class="nav flex-column mail-nav gap-1">
                        <li class="nav-item">
                            <a href="{{ route('documents.incoming') }}" class="nav-link d-flex align-items-center gap-2 px-3 py-2 rounded text-body">
                                <i class="bx bxs-inbox fs-5"></i>
                                <span class="flex-grow-1">Inbox</span>
                                @if(($inboxCount ?? 0) > 0)
                                    <span class="badge rounded-pill bg-primary">{{ $inboxCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('documents.sent') }}" class="nav-link d-flex align-items-center gap-2 px-3 py-2 rounded active">
                                <i class="bx bx-send fs-5"></i>
                                <span>Sent</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('documents.received') }}" class="nav-link d-flex align-items-center gap-2 px-3 py-2 rounded text-body">
                                <i class="bx bxs-download fs-5"></i>
                                <span>Received</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- MAIN CONTENT --}}
            <div class="col-12 col-md-9 col-lg-10 d-flex flex-column">

                {{-- Toolbar --}}
                <div class="d-flex align-items-center justify-content-end px-4 py-2 border-bottom" style="min-height: 52px;">
                    <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.85rem;">
                        {{ $documents->firstItem() ?? 0 }}&ndash;{{ $documents->lastItem() ?? 0 }} of {{ $documents->total() ?? 0 }}
                        <button class="btn btn-icon btn-sm btn-outline-secondary border-0" {{ $documents->onFirstPage() ? 'disabled' : '' }}>
                            <i class="bx bx-chevron-left"></i>
                        </button>
                        <button class="btn btn-icon btn-sm btn-outline-secondary border-0" {{ !$documents->hasMorePages() ? 'disabled' : '' }}>
                            <i class="bx bx-chevron-right"></i>
                        </button>
                    </div>
                </div>

                {{-- Column Headers --}}
                <div class="mail-header d-flex align-items-center gap-3 px-4 py-2 border-bottom">
                    <div class="col-header" style="width: 200px;">Sent To</div>
                    <div class="col-header flex-grow-1">Document Type &mdash; Purpose</div>
                    <div class="col-header d-none d-xl-block" style="min-width: 150px;">Tracking Code</div>
                    <div class="col-header d-none d-lg-block" style="min-width: 80px;">Priority</div>
                    <div class="col-header d-none d-lg-block" style="min-width: 80px;">Status</div>
                    <div class="col-header d-none d-lg-block" style="min-width: 80px;">Date</div>
                    <div class="col-header text-end d-none d-lg-block   " style="min-width: 90px;">Action</div>
                </div>

                {{-- Mail list --}}
                <div class="mail-list flex-grow-1">
                    @forelse($documents as $document)
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

                        $groupName = $route?->group ? ($route->group->position) : null;

                        $priorityValue = $route?->priority ?? 'normal';
                        $priorityClass = match($priorityValue) {
                            'urgent' => 'bg-danger',
                            'high'   => 'bg-warning',
                            'low'    => 'bg-secondary',
                            default  => 'bg-primary'
                        };

                        $statusValue = $document->status;
                        if ($recipients->isNotEmpty()) {
                            $actions = $recipients->pluck('action')
                                ->filter()
                                ->map(fn ($a) => strtolower(trim((string) $a)))
                                ->unique();
                            $hasPending = $recipients->contains(fn ($r) => is_null($r->action) || $r->action === 'pending');
                            $hasReceive = $actions->contains('receive') || $actions->contains('received') || $recipients->whereNotNull('receive_at')->isNotEmpty();
                            if ($hasPending)                        $statusValue = 'pending';
                            elseif ($hasReceive)                    $statusValue = 'receive';
                            elseif ($actions->contains('approved')) $statusValue = 'approved';
                            elseif ($actions->contains('rejected')) $statusValue = 'rejected';
                            else                                    $statusValue = 'pending';
                        }
                        $statusClass = match($statusValue) {
                            'pending'            => 'bg-warning',
                            'approved'           => 'bg-success',
                            'rejected'           => 'bg-danger',
                            'receive','received' => 'bg-info',
                            default              => 'bg-secondary'
                        };

                        $sentAt = $document?->created_at;

                        $singleRecipient = $recipients->count() === 1 ? $recipients->first() : null;
                        $recipientLabel = 'No recipients';
                        if ($groupName) {
                            $recipientLabel = $groupName;
                        } elseif ($recipients->count() > 1) {
                            $recipientLabel = $recipients->count() . ' Recipients';
                        } elseif ($singleRecipient) {
                            $emp = $singleRecipient->user->employee;
                            $recipientLabel = $emp
                                ? ($emp->firstname . ' ' . $emp->lastname)
                                : $singleRecipient->user->name;
                        }

                        $hasModal = $route?->group_id || $recipients->count() > 1;
                    @endphp

                    <div class="mail-item d-flex align-items-center gap-3 px-4 py-3 border-bottom"
                         style="transition: background .15s;">

                        {{-- Sent To --}}
                        <div class="flex-shrink-0" style="width: 200px; overflow: hidden;">
                            @if($hasModal)
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 w-75"
                                        style="font-size: 0.8rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                        data-bs-toggle="modal"
                                        data-bs-target="#recipientsModal-{{ $document->document_id }}"
                                        title="View Recipients">
                                    <i class="bx bx-group flex-shrink-0"></i>
                                    <span class="text-truncate">{{ $recipientLabel }}</span>
                                </button>
                            @else
                                <span class="text-body" style="font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;">
                                    {{ $recipientLabel }}
                                </span>
                            @endif
                        </div>

                        {{-- Subject / preview --}}
                        <div class="flex-grow-1 text-truncate" style="font-size: 0.875rem;">
                            <span class="fw-semibold text-body">
                                {{ $document->documentType?->type_name ?? 'Document' }} &mdash;
                            </span>
                            <span class="text-muted">{{ $document->purpose }}</span>
                        </div>

                        {{-- Tracking Code --}}
                        <div class="d-none d-xl-block" style="min-width: 150px;">
                            <span class="badge bg-label-primary" style="font-size: 0.7rem;">
                                {{ $document->tracking_code ?? 'N/A' }}
                            </span>
                        </div>

                        {{-- Priority --}}
                        <div class="d-none d-lg-block" style="min-width: 80px;">
                            <span class="badge {{ $priorityClass }}" style="font-size: 0.7rem;">
                                {{ ucfirst($priorityValue) }}
                            </span>
                        </div>

                        {{-- Status --}}
                        <div class="d-none d-lg-block" style="min-width: 80px;">
                            <span class="badge {{ $statusClass }}" style="font-size: 0.7rem;">
                                {{ ucfirst($statusValue) }}
                            </span>
                        </div>

                        {{-- Date --}}
                        <div class="text-muted text-end flex-shrink-0" style="font-size: 0.8rem; min-width: 80px;">
                            {{ $sentAt ? $sentAt->format('M d, Y') : 'N/A' }}
                        </div>

                        {{-- Actions (3-dot dropdown, always visible) --}}
                        <div class="dropdown flex-shrink-0 text-end " style="min-width: 90px;" onclick="event.stopPropagation()">
                            <button class="btn btn-icon btn-sm btn-outline-secondary" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @if($hasPendingRecipients)
                                <li>
                                    <button type="button"
                                            class="dropdown-item text-danger unsend-btn"
                                            data-document-id="{{ encryptId($document->document_id) }}"
                                            data-tracking-code="{{ $document->tracking_code }}">
                                        <i class="bx bx-x-circle me-1"></i> Unsend Pending
                                    </button>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                @endif
                                <li>
                                    <a href="{{ route('documents.download', encryptId($document->document_id)) }}"
                                       class="dropdown-item">
                                        <i class="bx bx-download me-1"></i> Download
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    @empty
                        <div class="text-center py-5 my-5">
                            <i class="bx bx-send" style="font-size: 64px; color: #ccc;"></i>
                            <p class="text-muted mt-3 mb-0">No sent documents found.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if($documents->hasPages())
                <div class="px-4 py-3 border-top d-flex justify-content-end">
                    {{ $documents->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Recipient Modals --}}
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
    <div class="modal fade" id="recipientsModal-{{ $document->document_id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Recipients &mdash; <span class="badge bg-label-primary">{{ $document->tracking_code }}</span>
                        @if($route?->group_id && $route->group)
                            <small class="text-muted ms-2 fs-6">({{ $route->group->position }} - {{ getCampusName($route->group->campus) }})</small>
                        @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <ul class="list-group list-group-flush">
                        @foreach($recipients as $recipient)
                            @php
                                $receiveStatus = $recipient->action ?: 'pending';
                                $receiveClass = match($receiveStatus) {
                                    'receive'  => 'bg-info',
                                    'approved' => 'bg-success',
                                    'rejected' => 'bg-danger',
                                    default    => 'bg-warning'
                                };
                                $emp = $recipient->user->employee;
                                $name = $emp ? ($emp->firstname . ' ' . $emp->lastname) : $recipient->user->name;
                            @endphp
                            <li class="list-group-item d-flex align-items-center justify-content-between px-4 py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bx bx-user-circle fs-4 text-muted"></i>
                                    <div>
                                        <div class="fw-semibold" style="font-size: 0.875rem;">{{ $name }}</div>
                                        <small class="text-muted"><i class="bx bx-envelope me-1"></i>{{ $recipient->user->email }}</small>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $receiveClass }}" style="font-size: 0.7rem;">{{ ucfirst($receiveStatus) }}</span>
                                    @if($receiveStatus === 'pending')
                                        <button type="button"
                                                class="btn btn-icon btn-sm btn-outline-danger unsend-recipient-btn"
                                                data-document-id="{{ encryptId($document->document_id) }}"
                                                data-recipient-id="{{ encryptId($recipient->recipient_id) }}"
                                                data-recipient-name="{{ $name }}"
                                                title="Unsend to this recipient">
                                            <i class="bx bx-user-x"></i>
                                        </button>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endif
@endforeach

<style>
.mail-nav .nav-link.active {
    background: rgba(105, 108, 255, 0.16);
    color: #696cff !important;
    font-weight: 600;
}
.mail-nav .nav-link:hover:not(.active) {
    background: rgba(67, 89, 113, 0.06);
}
.col-header {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05rem;
    color: #a1acb8;
}
.mail-header {
    background: #f8f8f8;
}
.mail-item:hover {
    background: rgba(67, 89, 113, 0.04);
}

</style>

@section('page-script')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    });

    $('.unsend-btn').on('click', function () {
        const documentId = $(this).data('document-id');
        const trackingCode = $(this).data('tracking-code');
        Swal.fire({
            title: 'Unsend Document?',
            html: `Remove all <strong>pending</strong> recipients from document <strong>${trackingCode}</strong>?<br><small class="text-muted">Recipients who already received/approved/rejected will remain.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, remove pending',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Removing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                $.ajax({
                    url: '/documents/' + documentId,
                    type: 'DELETE',
                    dataType: 'json',
                    success: function (response) {
                        Swal.fire({ icon: 'success', title: 'Unsent!', text: response.message || 'Pending recipients removed.', confirmButtonColor: '#696cff' })
                            .then(() => location.reload());
                    },
                    error: function (xhr) {
                        Swal.fire({ icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Failed to unsend document.', confirmButtonColor: '#d33' });
                    }
                });
            }
        });
    });

    $('.unsend-recipient-btn').on('click', function () {
        const documentId = $(this).data('document-id');
        const recipientId = $(this).data('recipient-id');
        const recipientName = $(this).data('recipient-name');
        Swal.fire({
            title: 'Unsend to Recipient?',
            html: `Remove <strong>${recipientName}</strong> from this document?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, unsend',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Removing...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                $.ajax({
                    url: `/documents/${documentId}/recipients/${recipientId}`,
                    type: 'DELETE',
                    dataType: 'json',
                    success: function (response) {
                        Swal.fire({ icon: 'success', title: 'Removed!', text: response.message || 'Recipient removed successfully.', confirmButtonColor: '#696cff' })
                            .then(() => location.reload());
                    },
                    error: function (xhr) {
                        Swal.fire({ icon: 'error', title: 'Error!', text: xhr.responseJSON?.message || 'Failed to remove recipient.', confirmButtonColor: '#d33' });
                    }
                });
            }
        });
    });
});
</script>
@endsection
@endsection