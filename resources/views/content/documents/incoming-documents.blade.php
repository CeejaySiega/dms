@extends('layouts.contentNavbarLayout')

@section('title', 'Mail - Inbox')

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
                <li class="breadcrumb-item active">Inbox</li>
            </ol>
        </nav>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
            <i class="bx bx-check-circle me-1"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="bx bx-error-circle me-1"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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
                            <a href="{{ route('documents.incoming') }}" class="nav-link d-flex align-items-center gap-2 px-3 py-2 rounded active">
                                <i class="bx bxs-inbox fs-5"></i>
                                <span class="flex-grow-1">Inbox</span>
                                @if(($inboxCount ?? 0) > 0)
                                    <span class="badge rounded-pill bg-primary">{{ $inboxCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('documents.sent') }}" class="nav-link d-flex align-items-center gap-2 px-3 py-2 rounded text-body">
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
                        {{ $inbox->firstItem() ?? 0 }}–{{ $inbox->lastItem() ?? 0 }} of {{ $inbox->total() ?? 0 }}
                        <button class="btn btn-icon btn-sm btn-outline-secondary border-0" {{ $inbox->onFirstPage() ? 'disabled' : '' }}>
                            <i class="bx bx-chevron-left"></i>
                        </button>
                        <button class="btn btn-icon btn-sm btn-outline-secondary border-0" {{ !$inbox->hasMorePages() ? 'disabled' : '' }}>
                            <i class="bx bx-chevron-right"></i>
                        </button>
                    </div>
                </div>

                {{-- Column Headers --}}
                <div class="mail-header d-flex align-items-center gap-3 px-4 py-2 border-bottom">
                    <div class="col-header" style="width: 200px;">Sender</div>
                    <div class="col-header flex-grow-1">Document Type — Purpose</div>
                    <div class="col-header d-none d-xl-block" style="min-width: 140px;">Tracking Code</div>
                    <div class="col-header d-none d-lg-block" style="min-width: 80px;">Priority</div>
                    <div class="col-header d-none d-lg-block" style="min-width: 80px;">Status</div>
                    <div class="col-header d-none d-lg-block" style="min-width: 80px; text-align: center;">Date</div>
                </div>

                {{-- Mail list --}}
                <div class="mail-list flex-grow-1">
                    @forelse($inbox as $recipient)
                        @php
                            $document = optional($recipient->route)->document;
                        @endphp
                        @if($document)
                        @php
                            $sender     = optional($document->user)->employee;
                            $senderName = $sender
                                ? ($sender->firstname . ' ' . $sender->lastname)
                                : (optional($document->user)->name ?? 'N/A');

                            $priorityValue = optional($recipient->route)->priority ?? 'normal';
                            $priorityClass = match($priorityValue) {
                                'urgent' => 'bg-danger',
                                'high'   => 'bg-warning',
                                'low'    => 'bg-secondary',
                                default  => 'bg-primary'
                            };

                            $statusValue = $recipient->action ?: 'pending';
                            $statusValue = $statusValue === 'received' ? 'receive' : $statusValue;
                            $statusClass = match($statusValue) {
                                'pending'  => 'bg-warning',
                                'approved' => 'bg-success',
                                'receive'  => 'bg-info',
                                'rejected' => 'bg-danger',
                                default    => 'bg-secondary'
                            };

                            $isUnread = $statusValue === 'pending';
                            $action   = $recipient->action;
                            $isFinal  = in_array($action, ['receive', 'approved', 'rejected']);

                            $modalId = 'inboxDocModal-' . $recipient->recipient_id;
                        @endphp

                        {{-- Clickable Row --}}
                        <div class="mail-item d-flex align-items-center gap-3 px-4 py-2 border-bottom {{ $isUnread ? 'mail-unread' : '' }}"
                             style="cursor: pointer; transition: background .15s;"
                             data-bs-toggle="modal"
                             data-bs-target="#{{ $modalId }}">

                            {{-- Sender name --}}
                            <div class="flex-shrink-0" style="width: 200px; overflow: hidden;">
                                <span class="{{ $isUnread ? 'fw-semibold text-body' : 'text-body' }}"
                                      style="font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;">
                                    {{ $senderName }}
                                </span>
                            </div>

                            {{-- Subject / preview --}}
                            <div class="flex-grow-1 text-truncate" style="font-size: 0.875rem;">
                                <span class="{{ $isUnread ? 'fw-semibold text-body' : 'text-muted' }}">
                                    {{ $document->documentType->type_name ?? 'Document' }} —
                                </span>
                                <span class="text-muted">{{ $document->purpose }}</span>
                            </div>

                            {{-- Tracking code --}}
                            <div class="d-none d-xl-block" style="min-width: 140px;">
                                <span class="badge bg-label-primary" style="font-size: 0.7rem;">
                                    {{ $document->tracking_code }}
                                </span>
                            </div>

                            {{-- Priority badge --}}
                            <div class="d-none d-lg-block" style="min-width: 80px;">
                                <span class="badge {{ $priorityClass }}" style="font-size: 0.7rem;">
                                    {{ ucfirst($priorityValue) }}
                                </span>
                            </div>

                            {{-- Status badge --}}
                            <div class="d-none d-lg-block" style="min-width: 80px;">
                                <span class="badge {{ $statusClass }}" style="font-size: 0.7rem;">
                                    {{ ucfirst($statusValue) }}
                                </span>
                            </div>

                            {{-- Date --}}
                            <div class="text-muted d-none d-lg-flex flex-shrink-0" style="font-size: 0.8rem; min-width: 80px; justify-content: center;">
                                {{ optional($recipient->sent_at)->format('M d, Y') ?? '' }}
                            </div>

                            {{-- Action Dropdown — stopPropagation so it doesn't open the modal
                            <div class="dropdown flex-shrink-0 text-end" style="min-width: 80px;" onclick="event.stopPropagation()">
                                <button class="btn btn-icon btn-sm btn-outline-secondary" type="button"
                                        data-bs-toggle="dropdown"
                                        data-bs-strategy="fixed"
                                        aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <form action="{{ route('documents.receive', encryptId($document->document_id)) }}"
                                              method="POST" class="receive-form">
                                            @csrf
                                            <button type="submit"
                                                    class="dropdown-item d-flex align-items-center gap-2"
                                                    @disabled($isFinal)>
                                                <i class="bx bx-envelope-open"></i> Receive Document
                                            </button>
                                        </form>
                                    </li>
                                    <li>
                                        <a href="{{ route('documents.show', encryptId($document->document_id)) }}"
                                           class="dropdown-item d-flex align-items-center gap-2">
                                            <i class="bx bx-show"></i> View Document
                                        </a>
                                    </li>
                                </ul>
                            </div> --}}

                        </div>
                        @endif
                    @empty
                        <div class="text-center py-5 my-5">
                            <i class="bx bxs-inbox" style="font-size: 64px; color: #ccc;"></i>
                            <p class="text-muted mt-3 mb-0">No incoming documents found.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if($inbox->hasPages())
                <div class="px-4 py-3 border-top d-flex justify-content-end">
                    {{ $inbox->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     INBOX MODALS — Document Details per Row
     ============================================================ --}}
@foreach($inbox as $recipient)
    @php
        $document = optional($recipient->route)->document;
        if (!$document) continue;

        $sender     = optional($document->user)->employee;
        $senderName = $sender
            ? ($sender->firstname . ' ' . $sender->lastname)
            : (optional($document->user)->name ?? 'N/A');
        $senderEmail = optional($document->user)->email ?? 'N/A';

        $priorityVal   = optional($recipient->route)->priority ?? 'normal';
        $priorityBadge = match($priorityVal) {
            'urgent' => 'bg-danger',
            'high'   => 'bg-warning',
            'low'    => 'bg-secondary',
            default  => 'bg-primary',
        };

        $statusVal = $recipient->action ?: 'pending';
        $statusVal = $statusVal === 'received' ? 'receive' : $statusVal;
        $statusBadge = match($statusVal) {
            'pending'  => 'bg-warning',
            'approved' => 'bg-success',
            'receive'  => 'bg-info',
            'rejected' => 'bg-danger',
            default    => 'bg-secondary',
        };

        $isFinal = in_array($recipient->action, ['receive', 'approved', 'rejected']);
        $modalId = 'inboxDocModal-' . $recipient->recipient_id;
    @endphp

    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">

                {{-- Header --}}
                <div class="modal-header border-bottom-0 pb-1">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="bx bx-file text-muted"></i>
                        <span class="fw-semibold">Incoming Document</span>
                        <span style="color: #e74c3c; font-weight: 600; font-size: 0.9rem;">
                            {{ $document->tracking_code ?? 'N/A' }}
                        </span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body pt-2">

                    {{-- ── Document Details ── --}}
                    <div class="mb-3">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bx bx-info-circle text-muted" style="font-size: 0.8rem;"></i>
                            <span class="text-uppercase fw-bold text-muted" style="font-size: 0.7rem; letter-spacing: 0.08em;">
                                Document Details
                            </span>
                        </div>

                        <div class="row g-3">
                            {{-- Document Type + Tracking Code --}}
                            <div class="col-6">
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">Document Type</div>
                                <div class="fw-semibold" style="font-size: 0.9rem;">
                                    {{ $document->documentType?->type_name ?? 'Document' }}
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">Tracking Code</div>
                                <div class="fw-semibold" style="color: #e74c3c; font-size: 0.9rem;">
                                    {{ $document->tracking_code ?? 'N/A' }}
                                </div>
                            </div>

                            {{-- Purpose --}}
                            <div class="col-12">
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">Purpose</div>
                                <div style="font-size: 0.9rem;">{{ $document->purpose }}</div>
                            </div>

                            {{-- Priority + Status + Date --}}
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
                                    {{ optional($recipient->sent_at)->format('M d, Y H:i') ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    {{-- ── Sender Details ── --}}
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bx bx-user text-muted" style="font-size: 0.8rem;"></i>
                            <span class="text-uppercase fw-bold text-muted" style="font-size: 0.7rem; letter-spacing: 0.08em;">
                                Sender
                            </span>
                        </div>

                        <div class="d-flex align-items-center gap-3 py-2 px-1">
                            <div class="avatar avatar-sm flex-shrink-0">
                                <span class="avatar-initial rounded-circle bg-label-primary fw-bold"
                                      style="width: 36px; height: 36px; font-size: 0.85rem;">
                                    {{ strtoupper(substr($senderName, 0, 1)) }}
                                </span>
                            </div>
                            <div>
                                <div class="fw-semibold" style="font-size: 0.875rem;">
                                    {{ strtoupper($senderName) }}
                                </div>
                                <small class="text-muted">{{ $senderEmail }}</small>
                            </div>
                        </div>
                    </div>

                </div>{{-- /modal-body --}}

                {{-- Footer --}}
                <div class="modal-footer border-top-0 justify-content-between pt-1">

                    {{-- Left: View full document --}}
                    <a href="{{ route('documents.show', encryptId($document->document_id)) }}"
                       class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                        <i class="bx bx-show me-1"></i> View Document
                    </a>

                    {{-- Right: Receive + Close --}}
                    <div class="d-flex gap-2">
                        @if(!$isFinal)
                            <form action="{{ route('documents.receive', encryptId($document->document_id)) }}"
                                  method="POST" class="receive-form">
                                @csrf
                                <button type="submit"
                                        class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                                    <i class="bx bx-envelope-open me-1"></i> Receive
                                </button>
                            </form>
                        @endif
                        <button type="button"
                                class="btn btn-secondary btn-sm"
                                data-bs-dismiss="modal">
                            Close
                        </button>
                    </div>
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
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1rem;
    color: #6c757d;
}
.mail-header {
    background: #f5f6f8;
}
.mail-item:hover {
    background: rgba(67, 89, 113, 0.04);
}
.mail-unread {
    background: rgba(105, 108, 255, 0.04);
}

/* Fix: SweetAlert2 always above Bootstrap modal */
.swal2-container {
    z-index: 99999 !important;
}
</style>

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Stop dropdown clicks from opening the row modal
    document.querySelectorAll('.mail-item .dropdown').forEach(function (dropdown) {
        dropdown.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    });

    // Intercept all receive forms (both dropdown + modal) with SweetAlert confirm
    document.querySelectorAll('.receive-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const submittedForm = this;
            Swal.fire({
                title: 'Receive Document?',
                text: 'Mark this document as received?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#696cff',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, receive it',
                cancelButtonText: 'Cancel',
                customClass: { container: 'swal-over-modal' },
                didOpen: function () {
                    document.querySelector('.swal-over-modal').style.zIndex = 99999;
                }
            }).then(function (result) {
                if (result.isConfirmed) submittedForm.submit();
            });
        });
    });

});
</script>
@endsection
@endsection