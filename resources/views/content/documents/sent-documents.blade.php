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
                <div class="d-flex align-items-center justify-content-between px-4 py-2 border-bottom" style="min-height: 52px;">

                    {{-- ✅ Hint note on the left --}}
                    <div class="d-flex align-items-center gap-1 px-3 py-1 rounded" style="font-size: 0.78rem; background: #eafbe7; color: #6fd44c; font-weight: 600;">
                        <i class="bx bx-info-circle" style="font-size: 0.95rem; color: #6fd44c;"></i>
                        <span style="color: #6fd44c;">Click a row to view document details and actions.</span>
                    </div>

                    {{-- Pagination controls on the right --}}
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

                {{-- Column Headers — Action column removed --}}
                <div class="mail-header d-flex align-items-center gap-3 px-4 py-2 border-bottom">
                    <div class="col-header" style="width: 200px;">Sent To</div>
                    <div class="col-header flex-grow-1">Document Type &mdash; Purpose</div>
                    <div class="col-header d-none d-xl-block" style="min-width: 150px;">Tracking Code</div>
                    <div class="col-header d-none d-lg-block" style="min-width: 80px;">Priority</div>
                    <div class="col-header d-none d-lg-block" style="min-width: 80px;">Status</div>
                    <div class="col-header d-none d-lg-block" style="min-width: 80px;">Date</div>
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

                        $pendingRecipients = $recipients->filter(function ($recipient) {
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
                    @endphp

                    {{-- Clickable Row — Action column removed --}}
                    <div class="mail-item d-flex align-items-center gap-3 px-4 py-3 border-bottom sent-document-row"
                        style="transition: background .15s; cursor: pointer;"
                        data-bs-toggle="modal"
                        data-bs-target="#sentDocumentModal-{{ $document->document_id }}">

                        {{-- Sent To --}}
                        <div class="flex-shrink-0" style="width: 200px; overflow: hidden;">
                            <span class="text-body" style="font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;">
                                {{ $recipientLabel }}
                            </span>
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

{{-- ============================================================
     COMBINED MODALS — Details + Recipients + Unsend
     ============================================================ --}}
@foreach($documents as $document)
    @php
        $route = \App\Models\DocumentRoute::with('group')
            ->where('document_id', $document->document_id)
            ->first();

        $recipients = $route
            ? \App\Models\Recipient::with('user.employee')
                ->where('route_id', $route->route_id)
                ->get()
            : collect();

        $pendingRecipients = $recipients->filter(fn($r) =>
            is_null($r->action) || $r->action === 'pending'
        );

        $priorityVal   = $route?->priority ?? 'normal';
        $priorityBadge = match($priorityVal) {
            'urgent' => 'bg-danger',
            'high'   => 'bg-warning',
            'low'    => 'bg-secondary',
            default  => 'bg-primary',
        };

        $statusVal = $document->status;
        if ($recipients->isNotEmpty()) {
            $actions    = $recipients->pluck('action')->filter()->map(fn($a) => strtolower(trim((string) $a)))->unique();
            $hasPending = $recipients->contains(fn($r) => is_null($r->action) || $r->action === 'pending');
            $hasReceive = $actions->contains('receive') || $actions->contains('received') || $recipients->whereNotNull('receive_at')->isNotEmpty();
            if ($hasPending)                        $statusVal = 'pending';
            elseif ($hasReceive)                    $statusVal = 'receive';
            elseif ($actions->contains('approved')) $statusVal = 'approved';
            elseif ($actions->contains('rejected')) $statusVal = 'rejected';
            else                                    $statusVal = 'pending';
        }
        $statusBadge = match($statusVal) {
            'pending'            => 'bg-warning',
            'approved'           => 'bg-success',
            'rejected'           => 'bg-danger',
            'receive','received' => 'bg-info',
            default              => 'bg-secondary',
        };
    @endphp

    <div class="modal fade" id="sentDocumentModal-{{ $document->document_id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">

                {{-- Header --}}
                <div class="modal-header border-bottom-0 pb-1">
                    <h5 class="modal-title d-flex flex-column gap-1">
                        <span class="d-flex align-items-center gap-2">
                            <i class="bx bx-file text-muted"></i>
                            <span class="fw-semibold">{{ $document->documentType?->type_name ?? 'Document' }}</span>
                            <span style="color: #e74c3c; font-weight: 600; font-size: 0.9rem;">
                                {{ $document->tracking_code ?? 'N/A' }}
                            </span>
                        </span>
                        @if(!empty($document->name))
                            <span class="fw-normal text-muted" style="font-size:0.95rem; margin-left:2.1em;">
                                {{ $document->name }}
                            </span>
                        @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-2">

                    {{-- ── Document Details Section ── --}}
                    <div class="mb-3">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bx bx-info-circle text-muted" style="font-size: 0.8rem;"></i>
                            <span class="text-uppercase fw-bold text-muted" style="font-size: 0.7rem; letter-spacing: 0.08em;">
                                Document Details
                            </span>
                        </div>

                        <div class="row g-3">
                            {{-- Row 1: Document Type + Tracking Code --}}
                            <div class="col-6">
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">File Name</div>
                                <div class="fw-semibold" style="font-size: 0.9rem;">
                                    {{ $document->file_name ?? 'N/A' }}
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">Tracking Code</div>
                                <div class="fw-semibold" style="color: #e74c3c; font-size: 0.9rem;">
                                    {{ $document->tracking_code ?? 'N/A' }}
                                </div>
                            </div>

                            {{-- Row 2: Purpose --}}
                            <div class="col-12">
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">Purpose</div>
                                <div style="font-size: 0.9rem;">{{ $document->purpose }}</div>
                            </div>

                            {{-- Row 3: Priority + Status + Sent At --}}
                            <div class="col-4">
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">Priority</div>
                                <span class="badge {{ $priorityBadge }}" style="font-size: 0.75rem;">
                                    {{ ucfirst($priorityVal) }}
                                </span>
                            </div>
                            <div class="col-4">
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">Status</div>
                                <span class="badge {{ $statusBadge }}" style="font-size: 0.75rem;">
                                    {{ ucfirst($statusVal) }}
                                </span>
                            </div>
                            <div class="col-4">
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">Sent At</div>
                                <div style="font-size: 0.85rem;">
                                    {{ $document->created_at?->format('M d, Y H:i') ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- ── Recipients Section ── --}}
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bx bx-group text-muted" style="font-size: 0.8rem;"></i>
                            <span class="text-uppercase fw-bold text-muted" style="font-size: 0.7rem; letter-spacing: 0.08em;">
                                Recipients
                            </span>
                            @if($route?->group_id && $route->group)
                                <span class="text-muted fw-semibold" style="font-size: 0.75rem;">
                                    &mdash; {{ strtoupper($route->group->position) }}
                                    @if(function_exists('getCampusName'))
                                        ({{ getCampusName($route->group->campus) }})
                                    @endif
                                </span>
                            @endif
                        </div>

                        @if($recipients->isEmpty())
                            <p class="text-muted small text-center py-3">No recipients found.</p>
                        @else
                            <div class="d-flex flex-column gap-2">
                                @foreach($recipients as $recipient)
                                    @php
                                        $emp       = $recipient->user->employee;
                                        $name      = $emp
                                            ? ($emp->firstname . ' ' . $emp->lastname)
                                            : $recipient->user->name;
                                        $action    = $recipient->action ?: 'pending';
                                        $isPending = is_null($recipient->action) || $recipient->action === 'pending';

                                        $rBadge = match(strtolower($action)) {
                                            'receive','received' => 'bg-info',
                                            'approved'           => 'bg-success',
                                            'rejected'           => 'bg-danger',
                                            default              => 'bg-warning',
                                        };
                                    @endphp
                                    <div class="d-flex align-items-center justify-content-between py-2 px-1"
                                         style="border-bottom: 1px solid #f0f0f0;">

                                        {{-- Avatar + Info --}}
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar avatar-sm flex-shrink-0">
                                                <span class="avatar-initial rounded-circle bg-label-secondary fw-bold"
                                                      style="width: 36px; height: 36px; font-size: 0.85rem;">
                                                    {{ strtoupper(substr($name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <div>
                                                <div class="fw-semibold" style="font-size: 0.875rem;">
                                                    {{ strtoupper($name) }}
                                                </div>
                                                <small class="text-muted">{{ $recipient->user->email }}</small>
                                            </div>
                                        </div>

                                        {{-- Status Badge + Unsend Button --}}
                                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                            <span class="badge {{ $rBadge }}" style="font-size: 0.75rem; min-width: 60px; text-align: center;">
                                                {{ ucfirst($action) }}
                                            </span>
                                            @if($isPending)
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                                                        style="font-size: 0.78rem; padding: 3px 10px;"
                                                        onclick="confirmUnsend(
                                                            '{{ route('documents.unsend-recipient', [encryptId($document->document_id), encryptId($recipient->recipient_id)]) }}',
                                                            '{{ addslashes($name) }}'
                                                        )"
                                                        title="Unsend to {{ $name }}">
                                                    <i class="bx bx-x" style="font-size: 0.9rem;"></i>
                                                    Unsend
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                </div>{{-- /modal-body --}}

                {{-- Footer --}}
                <div class="modal-footer border-top-0 justify-content-between pt-1">
                    <a href="{{ route('documents.download', encryptId($document->document_id)) }}"
                       class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                        <i class="bx bx-download me-1"></i> Download
                    </a>
                    <button type="button"
                            class="btn btn-secondary btn-sm"
                            data-bs-dismiss="modal">
                        Close
                    </button>
                </div>

            </div>
        </div>
    </div>
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
.sent-document-row {
    cursor: pointer;
}

/* ✅ Fix: SweetAlert2 above Bootstrap modal */
.swal2-container {
    z-index: 99999 !important;
}
</style>

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmUnsend(url, recipientName) {
    Swal.fire({
        title: 'Unsend to Recipient?',
        html: 'Remove <strong>' + recipientName + '</strong> from this document?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, unsend',
        cancelButtonText: 'Cancel',
        /* ✅ Fix: ensure Swal renders above the open Bootstrap modal */
        customClass: { container: 'swal-over-modal' },
        didOpen: function () {
            document.querySelector('.swal-over-modal').style.zIndex = 99999;
        }
    }).then(function (result) {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Removing...',
            allowOutsideClick: false,
            didOpen: function () { Swal.showLoading(); },
            customClass: { container: 'swal-over-modal' }
        });

        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Removed!',
                    text: data.message || 'Recipient removed successfully.',
                    confirmButtonColor: '#696cff',
                    customClass: { container: 'swal-over-modal' }
                }).then(function () { location.reload(); });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: data.message || 'Failed to remove recipient.',
                    confirmButtonColor: '#d33',
                    customClass: { container: 'swal-over-modal' }
                });
            }
        })
        .catch(function () {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An unexpected error occurred.',
                confirmButtonColor: '#d33',
                customClass: { container: 'swal-over-modal' }
            });
        });
    });
}
</script>
@endsection
@endsection