@extends('layouts.contentNavbarLayout')

@section('title', 'Received Documents')

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
                <li class="breadcrumb-item active">Received Documents</li>
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
                            <a href="{{ route('documents.sent') }}" class="nav-link d-flex align-items-center gap-2 px-3 py-2 rounded text-body">
                                <i class="bx bx-send fs-5"></i>
                                <span>Sent</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('documents.received') }}" class="nav-link d-flex align-items-center gap-2 px-3 py-2 rounded active">
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
                        {{ $received->firstItem() ?? 0 }}–{{ $received->lastItem() ?? 0 }} of {{ $received->total() ?? 0 }}
                        <button class="btn btn-icon btn-sm btn-outline-secondary border-0" {{ $received->onFirstPage() ? 'disabled' : '' }}>
                            <i class="bx bx-chevron-left"></i>
                        </button>
                        <button class="btn btn-icon btn-sm btn-outline-secondary border-0" {{ !$received->hasMorePages() ? 'disabled' : '' }}>
                            <i class="bx bx-chevron-right"></i>
                        </button>
                    </div>
                </div>

                {{-- Column Headers --}}
                <div class="mail-header d-flex align-items-center gap-3 px-4 py-2 border-bottom">
                    <div class="col-header" style="width: 200px;">Sender</div>
                    <div class="col-header flex-grow-1">Document Type — Purpose</div>
                    <div class="col-header d-none d-xl-block" style="min-width: 150px;">Tracking Code</div>
                    <div class="col-header d-none d-lg-block" style="min-width: 80px;">Status</div>
                    <div class="col-header d-none d-lg-block" style="min-width: 110px;">Received Date</div>
                    <div class="col-header text-end" style="min-width: 80px;">Action</div>
                </div>

                {{-- Mail list --}}
                <div class="mail-list flex-grow-1">
                    @forelse($received as $receivedDocument)
                        @php
                            $document = $receivedDocument->document;
                        @endphp
                        @if($document)
                        @php
                            $sender = optional($document->user)->employee;
                            $senderName = $sender
                                ? ($sender->firstname . ' ' . $sender->lastname)
                                : (optional($document->user)->name ?? 'N/A');
                        @endphp

                        <div class="mail-item d-flex align-items-center gap-3 px-4 py-3 border-bottom"
                             style="transition: background .15s;">

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
                            <div class="d-none d-xl-block" style="min-width: 150px;">
                                <span class="badge bg-label-primary" style="font-size: 0.7rem;">
                                    {{ $document->tracking_code }}
                                </span>
                            </div>

                            {{-- Status --}}
                            <div class="d-none d-lg-block" style="min-width: 80px;">
                                <span class="badge bg-info" style="font-size: 0.7rem;">Received</span>
                            </div>

                            {{-- Received Date --}}
                            <div class="text-muted d-none d-lg-block flex-shrink-0" style="font-size: 0.8rem; min-width: 110px;">
                                {{ optional($receivedDocument->receive_at)->format('m/d/y') ?? 'N/A' }}
                            </div>

                            {{-- Actions (3-dot dropdown, always visible) --}}
                            <div class="dropdown flex-shrink-0 text-end" style="min-width: 80px;" onclick="event.stopPropagation()">
                                <button class="btn btn-icon btn-sm btn-outline-secondary" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @if($document->status !== 'archived')
                                    <li>
                                        <form action="{{ route('documents.archive-receiver', encryptId($document->document_id)) }}"
                                              method="POST"
                                              class="archive-form">
                                            @csrf
                                            <button type="submit" class="dropdown-item">
                                                <i class="bx bx-archive me-1"></i> Archive
                                            </button>
                                        </form>
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
                        @endif
                    @empty
                        <div class="text-center py-5 my-5">
                            <i class="bx bx-receipt" style="font-size: 64px; color: #ccc;"></i>
                            <p class="text-muted mt-3 mb-0">No received documents found.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Pagination --}}
                @if($received->hasPages())
                <div class="px-4 py-3 border-top d-flex justify-content-end">
                    {{ $received->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

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
    $('.archive-form').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'Archive Document?',
            text: 'Are you sure you want to archive this document?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#696cff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, archive it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
    });
});
</script>
@endsection
@endsection