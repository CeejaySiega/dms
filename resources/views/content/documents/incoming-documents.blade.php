@extends('layouts.contentNavbarLayout')

@section('title', 'Mail - Inbox')

@section('content')

<style>
/* ── Column headers ── */
.col-header {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1rem;
    color: #6c757d;
}
@media (max-width: 768px) {
    .col-header { font-size: 0.6rem; }
}

/* ── Mail rows ── */
.mail-header { background: #f8f9fc; }
.mail-item { transition: background .15s; }
.mail-item:hover { background: #f5f6ff; }
.mail-unread { background: rgba(105, 108, 255, 0.04); }

/* ── Scrollable list ── */
.mail-list {
    flex-grow: 1;
    overflow-y: auto;
    min-height: 0;
}

/* ── Search input in toolbar ── */
.mail-search-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f0f2f7;
    border: 1px solid #e2e5f0;
    border-radius: 7px;
    padding: 6px 12px;
    max-width: 280px;
    flex: 1;
}
.mail-search-wrap input {
    border: none;
    background: transparent;
    font-size: 0.8125rem;
    color: #1a1d3a;
    outline: none;
    width: 100%;
}
.mail-search-wrap input::placeholder { color: #8b90b8; }

/* ── Hint bar ── */
.hint-bar {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 600;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    color: #166534;
    margin-bottom: 16px;
}

/* ── DataTables-style pagination ── */
.dt-pagination {
    display: flex;
    align-items: center;
    gap: 3px;
    list-style: none;
    margin: 0;
    padding: 0;
    flex-wrap: wrap;
    justify-content: flex-end;
}
@media (max-width: 576px) {
    .dt-pagination { justify-content: center; width: 100%; }
}
.dt-pagination .page-item .page-link {
    border: 1px solid transparent;
    border-radius: 0.375rem !important;
    padding: 0.3rem 0.65rem;
    font-size: 0.8rem;
    color: #6c757d;
    background: transparent;
    min-width: 32px;
    text-align: center;
    line-height: 1.5;
    transition: background 0.15s, color 0.15s;
}
@media (max-width: 576px) {
    .dt-pagination .page-item .page-link { padding: 0.25rem 0.5rem; font-size: 0.7rem; min-width: 26px; }
}
.dt-pagination .page-item .page-link:hover { background: #f0f1ff; color: #696cff; }
.dt-pagination .page-item.active .page-link { background: #696cff; color: #fff; border-color: #696cff; }
.dt-pagination .page-item.disabled .page-link { color: #c4c6d0; pointer-events: none; }

/* SweetAlert2 always above Bootstrap modal */
.swal2-container { z-index: 99999 !important; }

/* ── Mail card ── */
.mail-card {
    min-height: clamp(600px, 75vh, 900px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(26,29,58,0.07), 0 4px 16px rgba(26,29,58,0.05);
    border: 1px solid #e2e5f0;
}

/* ── Page title styling ── */
.page-title-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(105, 108, 255, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
</style>

<div class="container-xxl flex-grow-1 container-p-y">

    {{-- ── Page header ── --}}
    <div class="mb-4 d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="page-title-icon">
                <i class="bx bxs-inbox text-primary fs-4"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-1">Inbox</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0" style="font-size: 0.8rem;">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard-analytics') }}">Home</a></li>
                        <li class="breadcrumb-item inactive">Mail</li>
                        <li class="breadcrumb-item active">Inbox</li>
                    </ol>
                </nav>
            </div>
        </div>
        <a href="{{ route('documents.send') }}" class="btn btn-primary fw-semibold align-self-start d-flex align-items-center gap-1"
           style="box-shadow: 0 2px 8px rgba(105,108,255,0.25);">
            <i class="bx bx-plus"></i> Send Document
        </a>
    </div>

    {{-- ── Flash messages ── --}}
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

    {{-- ── Hint bar (above card, like the redesign) ── --}}
    <div class="hint-bar">
        <i class="bx bx-info-circle"></i>
        Click a row to view document details and actions.
    </div>

    {{-- ── Mail card — full width ── --}}
    <div class="card mail-card">

        {{-- Toolbar: search (left) + count/chevrons (right) ── --}}
        <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom flex-shrink-0">
            <div class="mail-search-wrap">
                <i class="bx bx-search text-muted" style="font-size: 0.95rem; flex-shrink:0;"></i>
                <input type="text" placeholder="Search documents…" />
            </div>
            <div class="ms-auto d-flex align-items-center gap-1 text-muted" style="font-size: 0.85rem; white-space: nowrap;">
                {{ $inbox->firstItem() ?? 0 }}&ndash;{{ $inbox->lastItem() ?? 0 }}
                of {{ $inbox->total() ?? 0 }}
                <a href="{{ !$inbox->onFirstPage() ? $inbox->previousPageUrl() : '#' }}"
                   class="btn btn-icon btn-sm btn-outline-secondary border-0 {{ $inbox->onFirstPage() ? 'disabled' : '' }}">
                    <i class="bx bx-chevron-left"></i>
                </a>
                <a href="{{ $inbox->hasMorePages() ? $inbox->nextPageUrl() : '#' }}"
                   class="btn btn-icon btn-sm btn-outline-secondary border-0 {{ !$inbox->hasMorePages() ? 'disabled' : '' }}">
                    <i class="bx bx-chevron-right"></i>
                </a>
            </div>
        </div>

        {{-- Column Headers ── --}}
        <div class="mail-header d-flex align-items-center gap-3 px-4 py-2 border-bottom flex-shrink-0">
            <div class="col-header" style="width: 200px;">Sender</div>
            <div class="col-header flex-grow-1">Document Type — Purpose</div>
            <div class="col-header d-none d-xl-block" style="min-width: 160px;">Tracking Code</div>
            <div class="col-header d-none d-lg-block" style="min-width: 80px;">Priority</div>
            <div class="col-header d-none d-lg-block" style="min-width: 80px;">Status</div>
            <div class="col-header d-none d-lg-block" style="min-width: 80px; text-align: center;">Date</div>
        </div>

        {{-- Mail list ── --}}
        <div class="mail-list">
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
                        default  => 'bg-primary',
                    };

                    $statusValue = $recipient->action ?: 'pending';
                    $statusValue = $statusValue === 'received' ? 'receive' : $statusValue;
                    $statusClass = match($statusValue) {
                        'pending'  => 'bg-warning',
                        'read'     => 'bg-secondary',
                        'approved' => 'bg-success',
                        'receive'  => 'bg-info',
                        'rejected' => 'bg-danger',
                        default    => 'bg-secondary',
                    };

                    $isUnread = $statusValue === 'pending';
                    $isFinal  = in_array($recipient->action, ['receive', 'approved', 'rejected']);
                    $modalId  = 'inboxDocModal-' . $recipient->recipient_id;
                @endphp

                <div class="mail-item d-flex align-items-center gap-3 px-4 py-2 border-bottom {{ $isUnread ? 'mail-unread' : '' }}"
                     style="cursor: pointer;"
                     data-bs-toggle="modal"
                     data-bs-target="#{{ $modalId }}"
                     data-recipient-id="{{ $recipient->recipient_id }}"
                     data-status="{{ $statusValue }}">

                    {{-- Sender --}}
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
                    <div class="d-none d-xl-block" style="min-width: 160px;">
                        <span class="badge bg-label-primary" style="font-size: 0.7rem;">
                            {{ $document->tracking_code }}
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
                    <div class="text-muted d-none d-lg-flex flex-shrink-0"
                         style="font-size: 0.8rem; min-width: 80px; justify-content: center;">
                        {{ optional($recipient->sent_at)->format('M d, Y') ?? '' }}
                    </div>
                </div>
                @endif
            @empty
                <div class="text-center py-5 my-5">
                    <i class="bx bxs-inbox" style="font-size: 64px; color: #ccc;"></i>
                    <p class="text-muted mt-3 mb-0">No incoming documents found.</p>
                </div>
            @endforelse
        </div>

        {{-- ── Pagination footer ── --}}
        <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between flex-shrink-0 mt-auto"
             style="background: #fafbff;">
            <span class="text-muted" style="font-size: 0.8125rem;">
                Showing {{ $inbox->firstItem() ?? 0 }} to {{ $inbox->lastItem() ?? 0 }}
                of {{ $inbox->total() ?? 0 }} results
            </span>

            @php
                $current = $inbox->currentPage();
                $last    = $inbox->lastPage();
                $window  = 2;
                $start   = max(1, $current - $window);
                $end     = min($last, $current + $window);
                $query   = $inbox->appends(request()->query());
            @endphp
            <ul class="dt-pagination">
                <li class="page-item {{ $inbox->onFirstPage() ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ !$inbox->onFirstPage() ? $query->previousPageUrl() : '#' }}">‹</a>
                </li>
                @if($start > 1)
                    <li class="page-item"><a class="page-link" href="{{ $query->url(1) }}">1</a></li>
                    @if($start > 2)
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    @endif
                @endif
                @for($p = $start; $p <= $end; $p++)
                    <li class="page-item {{ $p === $current ? 'active' : '' }}">
                        <a class="page-link" href="{{ $query->url($p) }}">{{ $p }}</a>
                    </li>
                @endfor
                @if($end < $last)
                    @if($end < $last - 1)
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    @endif
                    <li class="page-item">
                        <a class="page-link" href="{{ $query->url($last) }}">{{ $last }}</a>
                    </li>
                @endif
                <li class="page-item {{ !$inbox->hasMorePages() ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $inbox->hasMorePages() ? $query->nextPageUrl() : '#' }}">›</a>
                </li>
            </ul>
        </div>

    </div>{{-- /mail-card --}}
</div>

{{-- ── MODALS (unchanged) ── --}}
@foreach($inbox as $recipient)
    @php
        $document = optional($recipient->route)->document;
        if (!$document) continue;

        $sender      = optional($document->user)->employee;
        $senderName  = $sender
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
            'read'     => 'bg-secondary',
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
                    <div class="mb-3">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bx bx-info-circle text-muted" style="font-size: 0.8rem;"></i>
                            <span class="text-uppercase fw-bold text-muted"
                                  style="font-size: 0.7rem; letter-spacing: 0.08em;">Document Details</span>
                        </div>
                        <div class="row g-3">
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
                            <div class="col-12">
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">Purpose</div>
                                <div style="font-size: 0.9rem;">{{ $document->purpose }}</div>
                            </div>
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

                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bx bx-user text-muted" style="font-size: 0.8rem;"></i>
                            <span class="text-uppercase fw-bold text-muted"
                                  style="font-size: 0.7rem; letter-spacing: 0.08em;">Sender</span>
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
                </div>

                <div class="modal-footer border-top-0 justify-content-between pt-1">
                    <a href="{{ route('documents.show', encryptId($document->document_id)) }}"
                       class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                        <i class="bx bx-show me-1"></i> View Document
                    </a>
                    <div class="d-flex gap-2">
                        @if(!$isFinal)
                            <form action="{{ route('documents.receive', encryptId($document->document_id)) }}"
                                  method="POST"
                                  class="receive-form">
                                @csrf
                                <button type="submit"
                                        class="btn btn-primary btn-sm d-flex align-items-center gap-1">
                                    <i class="bx bx-envelope-open me-1"></i> Receive
                                </button>
                            </form>
                        @endif
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                            Close
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endforeach

@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Mark as read when modal is closed
    document.querySelectorAll('.modal').forEach(function (modal) {
        modal.addEventListener('hidden.bs.modal', function (event) {
            const modalId     = event.target.id;
            const recipientId = modalId.replace('inboxDocModal-', '');
            const mailItem    = document.querySelector(`.mail-item[data-recipient-id="${recipientId}"]`);

            if (mailItem && mailItem.getAttribute('data-status') === 'pending') {
                fetch(`{{ url('/documents/mark-as-read') }}/${recipientId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mailItem.classList.remove('mail-unread');

                        mailItem.querySelectorAll('.badge').forEach(badge => {
                            if (badge.textContent.trim().toLowerCase() === 'pending') {
                                badge.className      = 'badge bg-secondary';
                                badge.style.fontSize = '0.7rem';
                                badge.textContent    = 'Read';
                            }
                        });

                        const senderSpan = mailItem.querySelector('.flex-shrink-0 span');
                        if (senderSpan) {
                            senderSpan.classList.remove('fw-semibold', 'text-body');
                            senderSpan.classList.add('text-body');
                        }

                        const docTypeSpan = mailItem.querySelector('.flex-grow-1 span:first-child');
                        if (docTypeSpan) {
                            docTypeSpan.classList.remove('fw-semibold', 'text-body');
                            docTypeSpan.classList.add('text-muted');
                        }

                        mailItem.setAttribute('data-status', 'read');
                    }
                })
                .catch(error => console.error('Error marking document as read:', error));
            }
        });
    });

    // Receive form confirmation
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
                    const el = document.querySelector('.swal-over-modal');
                    if (el) el.style.zIndex = 99999;
                }
            }).then(function (result) {
                if (result.isConfirmed) submittedForm.submit();
            });
        });
    });

});
</script>
@endsection