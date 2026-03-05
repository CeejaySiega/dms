@extends('layouts.contentNavbarLayout')

@section('title', 'Received Documents')

@section('content')

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
@media (max-width: 768px) {
    .col-header {
        font-size: 0.6rem;
    }
}
.mail-header { background: #f5f6f8; }
.mail-item:hover { background: rgba(67, 89, 113, 0.04); }

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
    .dt-pagination {
        justify-content: center;
        width: 100%;
    }
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
    .dt-pagination .page-item .page-link {
        padding: 0.25rem 0.5rem;
        font-size: 0.7rem;
        min-width: 26px;
    }
}
.dt-pagination .page-item .page-link:hover {
    background: #f0f1ff;
    color: #696cff;
}
.dt-pagination .page-item.active .page-link {
    background: #696cff;
    color: #fff;
    border-color: #696cff;
}
.dt-pagination .page-item.disabled .page-link {
    color: #c4c6d0;
    pointer-events: none;
}

/* SweetAlert2 always above Bootstrap modal */
.swal2-container { z-index: 99999 !important; }

/* Responsive sidebar */
@media (max-width: 768px) {
    .col-md-3 { position: sticky; top: 0; z-index: 100; }
    .mail-item { font-size: 0.8rem; }
}
@media (max-width: 576px) {
    .row.g-0 { flex-wrap: wrap; }
    .col-12 { max-width: 100%; }
}

/* Zoom-safe container sizing with aspect ratio */
.card[style*="min-height"] {
    min-height: clamp(600px, 75vh, 900px) !important;
    aspect-ratio: auto / 1.2;
}

.col-12.col-md-9.col-lg-10[style*="height"] {
    height: clamp(600px, 75vh, 900px) !important;
}

.mail-list {
    flex-grow: 1;
    overflow-y: auto;
    min-height: 0;
}

</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="mb-4">
        <h4 class="fw-bold mb-2"><i class="bx bx-envelope me-2"></i>Mail</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb-style1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard-analytics') }}">Home</a></li>
                <li class="breadcrumb-item inactive">Mail</li>
                <li class="breadcrumb-item active">Received Documents</li>
            </ol>
        </nav>
    </div>

    <div class="card overflow-hidden" style="min-height: 75vh;">
        <div class="row g-0 h-100">

            {{-- ── LEFT SIDEBAR ── --}}
            <div class="col-12 col-md-3 col-lg-2 border-end" style="background: #fff;">
                <div class="p-3">
                    <a href="{{ route('documents.send') }}" class="btn btn-primary w-100 mb-3 fw-semibold">
                        <i class="bx bx-plus me-1"></i> Send Document
                    </a>

                    <form class="mb-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text border-end-0 bg-transparent">
                                <i class="bx bx-search text-muted"></i>
                            </span>
                            <input class="form-control border-start-0 ps-0"
                                   type="search"
                                   placeholder="Search documents">
                        </div>
                    </form>

                    <ul class="nav flex-column mail-nav gap-1">
                        <li class="nav-item">
                            <a href="{{ route('documents.incoming') }}"
                               class="nav-link d-flex align-items-center gap-2 px-3 py-2 rounded text-body">
                                <i class="bx bxs-inbox fs-5"></i>
                                <span class="flex-grow-1">Inbox</span>
                                @if(($inboxCount ?? 0) > 0)
                                    <span class="badge rounded-pill bg-primary">{{ $inboxCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('documents.sent') }}"
                               class="nav-link d-flex align-items-center gap-2 px-3 py-2 rounded text-body">
                                <i class="bx bx-send fs-5"></i>
                                <span>Sent</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('documents.received') }}"
                               class="nav-link d-flex align-items-center gap-2 px-3 py-2 rounded active">
                                <i class="bx bxs-download fs-5"></i>
                                <span>Received</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- ── MAIN CONTENT ── --}}
            <div class="col-12 col-md-9 col-lg-10 d-flex flex-column" style="min-height: 0; height: 75vh;">

                {{-- Toolbar --}}
                <div class="d-flex align-items-center justify-content-between px-4 py-2 border-bottom flex-shrink-0"
                     style="min-height: 52px;">

                    {{-- Hint --}}
                    <div class="d-flex align-items-center gap-1 px-3 py-1 rounded"
                         style="font-size: 0.78rem; background: #eafbe7; color: #6fd44c; font-weight: 600;">
                        <i class="bx bx-info-circle" style="font-size: 0.95rem; color: #6fd44c;"></i>
                        <span style="color: #6fd44c;">Click a row to view document details and actions.</span>
                    </div>

                    {{-- Entry count + chevron nav --}}
                    <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.85rem;">
                        {{ $received->firstItem() ?? 0 }}&ndash;{{ $received->lastItem() ?? 0 }}
                        of {{ $received->total() ?? 0 }}
                        <a href="{{ !$received->onFirstPage() ? $received->previousPageUrl() : '#' }}"
                           class="btn btn-icon btn-sm btn-outline-secondary border-0 {{ $received->onFirstPage() ? 'disabled' : '' }}">
                            <i class="bx bx-chevron-left"></i>
                        </a>
                        <a href="{{ $received->hasMorePages() ? $received->nextPageUrl() : '#' }}"
                           class="btn btn-icon btn-sm btn-outline-secondary border-0 {{ !$received->hasMorePages() ? 'disabled' : '' }}">
                            <i class="bx bx-chevron-right"></i>
                        </a>
                    </div>
                </div>

                {{-- Column Headers --}}
                <div class="mail-header d-flex align-items-center gap-3 px-4 py-2 border-bottom flex-shrink-0">
                    <div class="col-header" style="width: 200px;">Sender</div>
                    <div class="col-header flex-grow-1">Document Type — Purpose</div>
                    <div class="col-header d-none d-xl-block" style="min-width: 140px;">Tracking Code</div>
                    <div class="col-header d-none d-lg-block" style="min-width: 70px;">Status</div>
                    <div class="col-header d-none d-lg-block" style="min-width: 80px; text-align: center;">Date</div>
                </div>

                {{-- Mail list --}}
                <div class="mail-list flex-grow-1" style="overflow-y: auto; min-height: 0;">
                    @forelse($received as $receivedDocument)
                        @php
                            $document = $receivedDocument->document;
                        @endphp
                        @if($document)
                        @php
                            $sender     = optional($document->user)->employee;
                            $senderName = $sender
                                ? ($sender->firstname . ' ' . $sender->lastname)
                                : (optional($document->user)->name ?? 'N/A');
                            $modalId = 'receivedDocModal-' . $receivedDocument->recipient_id;
                        @endphp

                        <div class="mail-item d-flex align-items-center gap-3 px-4 py-2 border-bottom"
                             style="transition: background .15s; cursor: pointer;"
                             data-bs-toggle="modal"
                             data-bs-target="#{{ $modalId }}">

                            {{-- Sender --}}
                            <div class="flex-shrink-0" style="width: 200px; overflow: hidden;">
                                <span class="text-body"
                                      style="font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;">
                                    {{ $senderName }}
                                </span>
                            </div>

                            {{-- Subject / preview --}}
                            <div class="flex-grow-1 text-truncate" style="font-size: 0.875rem;">
                                <span class="fw-semibold text-body">
                                    {{ $document->documentType->type_name ?? 'Document' }} —
                                </span>
                                <span class="text-muted">{{ $document->purpose }}</span>
                            </div>

                            {{-- Tracking Code --}}
                            <div class="d-none d-xl-block" style="min-width: 140px;">
                                <span class="badge bg-label-primary" style="font-size: 0.7rem;">
                                    {{ $document->tracking_code }}
                                </span>
                            </div>

                            {{-- Status --}}
                            <div class="d-none d-lg-block" style="min-width: 70px;">
                                <span class="badge bg-info" style="font-size: 0.7rem;">Received</span>
                            </div>

                            {{-- Received Date --}}
                            <div class="text-muted d-none d-lg-flex flex-shrink-0"
                                 style="font-size: 0.8rem; min-width: 80px; justify-content: center;">
                                {{ optional($receivedDocument->receive_at)->format('M d, Y') ?? 'N/A' }}
                            </div>
                        </div>
                        @endif
                    @empty
                        <div class="text-center py-5 my-5">
                            <i class="bx bx-receipt" style="font-size: 64px; color: #ccc;"></i>
                            <p class="text-muted mt-3 mb-0">No received documents found.</p>
                        </div>
                    @endforelse
                </div>

                {{-- ── Pagination footer — always visible at bottom ── --}}
                <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between flex-shrink-0">

                    {{-- Info --}}
                    <span class="text-muted" style="font-size: 0.8125rem;">
                        Showing {{ $received->firstItem() ?? 0 }} to {{ $received->lastItem() ?? 0 }}
                        of {{ $received->total() ?? 0 }} results
                    </span>

                    {{-- Numbered pagination --}}
                    @php
                        $current = $received->currentPage();
                        $last    = $received->lastPage();
                        $window  = 2;
                        $start   = max(1, $current - $window);
                        $end     = min($last, $current + $window);
                        $query   = $received->appends(request()->query());
                    @endphp
                    <ul class="dt-pagination">
                        <li class="page-item {{ $received->onFirstPage() ? 'disabled' : '' }}">
                            <a class="page-link"
                               href="{{ !$received->onFirstPage() ? $query->previousPageUrl() : '#' }}">‹</a>
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

                        <li class="page-item {{ !$received->hasMorePages() ? 'disabled' : '' }}">
                            <a class="page-link"
                               href="{{ $received->hasMorePages() ? $query->nextPageUrl() : '#' }}">›</a>
                        </li>
                    </ul>
                </div>

            </div>{{-- /main content --}}
        </div>
    </div>
</div>

{{-- ── MODALS ── --}}
@foreach($received as $receivedDocument)
    @php
        $document = $receivedDocument->document;
        if (!$document) continue;

        $sender      = optional($document->user)->employee;
        $senderName  = $sender
            ? ($sender->firstname . ' ' . $sender->lastname)
            : (optional($document->user)->name ?? 'N/A');
        $senderEmail = optional($document->user)->email ?? 'N/A';
        $modalId     = 'receivedDocModal-' . $receivedDocument->recipient_id;
    @endphp

    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">

                <div class="modal-header border-bottom-0 pb-1">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="bx bx-file text-muted"></i>
                        <span class="fw-semibold">Received Document</span>
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
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">Status</div>
                                <span class="badge bg-info" style="font-size: 0.75rem;">Received</span>
                            </div>
                            <div class="col-4">
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">Received Date</div>
                                <div style="font-size: 0.85rem;">
                                    {{ optional($receivedDocument->receive_at)->format('M d, Y H:i') ?? 'N/A' }}
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">Archive Status</div>
                                @if($document->status === 'archived')
                                    <span class="badge bg-secondary" style="font-size: 0.75rem;">Archived</span>
                                @else
                                    <span class="badge bg-label-secondary" style="font-size: 0.75rem;">Active</span>
                                @endif
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
                    <a href="{{ route('documents.download', encryptId($document->document_id)) }}"
                       class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                        <i class="bx bx-download me-1"></i> Download
                    </a>
                    <div class="d-flex gap-2">
                        @if($document->status !== 'archived')
                            <form action="{{ route('documents.archive-receiver', encryptId($document->document_id)) }}"
                                  method="POST"
                                  class="archive-form">
                                @csrf
                                <button type="submit"
                                        class="btn btn-outline-warning btn-sm d-flex align-items-center gap-1">
                                    <i class="bx bx-archive me-1"></i> Archive
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

    // Stop dropdown clicks from opening the row modal
    document.querySelectorAll('.mail-item .dropdown').forEach(function (dropdown) {
        dropdown.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    });

    // Archive confirmation
    document.querySelectorAll('.archive-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const submittedForm = this;
            Swal.fire({
                title: 'Archive Document?',
                text: 'Are you sure you want to archive this document?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#696cff',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, archive it',
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