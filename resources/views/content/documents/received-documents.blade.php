@extends('layouts.contentNavbarLayout')

@section('title', 'Received Documents')

@section('content')

@include('content.documents.styles.received-documents-style')

<div class="container-xxl flex-grow-1 container-p-y">

    {{-- ── Page header ── --}}
    <div class="mb-4 d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="page-title-icon">
                <i class="bx bxs-download text-primary fs-4"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-1">Received</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0" style="font-size: 0.8rem;">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard-analytics') }}">Home</a></li>
                        <li class="breadcrumb-item inactive">Mail</li>
                        <li class="breadcrumb-item active">Received Documents</li>
                    </ol>
                </nav>
            </div>
        </div>
        <a href="{{ route('documents.send') }}" class="btn btn-primary fw-semibold align-self-start d-flex align-items-center gap-1"
           style="box-shadow: 0 2px 8px rgba(105,108,255,0.25);">
            <i class="bx bx-plus"></i> Send Document
        </a>
    </div>

    {{-- ── Hint bar ── --}}
    <div class="hint-bar">
        <i class="bx bx-info-circle"></i>
        Click a row to view document details and actions.
    </div>

    {{-- ── Mail card — full width ── --}}
    <div class="card mail-card">

        {{-- Toolbar ── --}}
        <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom flex-shrink-0">
            <div class="mail-search-wrap">
                <i class="bx bx-search text-muted" style="font-size: 0.95rem; flex-shrink:0;"></i>
                <input type="text" placeholder="Search documents…" />
            </div>
            <div class="ms-auto d-flex align-items-center gap-1 text-muted" style="font-size: 0.85rem; white-space: nowrap;">
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

        {{-- Column Headers ── --}}
        <div class="mail-header d-flex align-items-center gap-3 px-4 py-2 border-bottom flex-shrink-0">
            <div class="col-header" style="width: 200px;">Sender</div>
            <div class="col-header flex-grow-1">Document Type</div>
            <div class="col-header d-none d-xl-block" style="min-width: 160px;">Tracking Code</div>
            <div class="col-header d-none d-lg-block" style="min-width: 70px;">Status</div>
            <div class="col-header d-none d-lg-block" style="min-width: 80px; text-align: center;">Date</div>
        </div>

        {{-- Mail list ── --}}
        <div class="mail-list">
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
                     style="cursor: pointer;"
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
                            {{ $document->documentType->type_name ?? 'Document' }}
                        </span>
                    </div>

                    {{-- Tracking Code --}}
                    <div class="d-none d-xl-block" style="min-width: 160px;">
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

        {{-- ── Pagination footer ── --}}
        <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between flex-shrink-0 mt-auto"
             style="background: #fafbff;">
            <span class="text-muted" style="font-size: 0.8125rem;">
                Showing {{ $received->firstItem() ?? 0 }} to {{ $received->lastItem() ?? 0 }}
                of {{ $received->total() ?? 0 }} results
            </span>

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
                    <a class="page-link" href="{{ !$received->onFirstPage() ? $query->previousPageUrl() : '#' }}">‹</a>
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
                    <a class="page-link" href="{{ $received->hasMorePages() ? $query->nextPageUrl() : '#' }}">›</a>
                </li>
            </ul>
        </div>

    </div>{{-- /mail-card --}}
</div>

{{-- ── MODALS (unchanged) ── --}}
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
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('documents.download', encryptId($document->document_id)) }}"
                           class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                            <i class="bx bx-download me-1"></i> Download
                        </a>
                        <a href="{{ route('documents.forward.form', ['documentId' => encryptId($document->document_id), 'base_route' => encryptId($receivedDocument->route_id), 'source' => 'received']) }}"
                           class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                            <i class="bx bx-share-alt me-1"></i> Forward
                        </a>
                    </div>
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
@include('content.documents.scripts.received-documents-script')
@endsection