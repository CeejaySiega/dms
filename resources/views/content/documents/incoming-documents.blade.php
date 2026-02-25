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
                <div class="d-flex align-items-center justify-content-end px-4 py-2 border-bottom" style="min-height: 52px;">
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
                    <div class="col-header d-none d-lg-block" style="min-width: 80px;">Date</div>
                    <div class="col-header text-end" style="min-width: 80px;">Action</div>
                </div>

                {{-- Mail list --}}
                <div class="mail-list flex-grow-1">
                    @forelse($inbox as $recipient)
                        @php
                            $document = optional($recipient->route)->document;
                        @endphp
                        @if($document)
                        @php
                            $sender = optional($document->user)->employee;
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
                        @endphp

                        <div class="mail-item d-flex align-items-center gap-3 px-4 py-3 border-bottom {{ $isUnread ? 'mail-unread' : '' }}"
                             style="cursor: pointer; transition: background .15s;"
                             onclick="window.location='{{ route('documents.show', encryptId($document->document_id)) }}'">

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
                            <div class="text-muted flex-shrink-0" style="font-size: 0.8rem; min-width: 80px;">
                                {{ optional($recipient->sent_at)->format('M d, Y') ?? '' }}
                            </div>

                            {{-- Actions (shown on hover) --}}<div class="dropdown flex-shrink-0 text-end " style="min-width: 90px;" onclick="event.stopPropagation()">
                            <button class="btn btn-icon btn-sm btn-outline-secondary" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bx bx-dots-vertical-rounded"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <form action="{{ route('documents.receive', encryptId($document->document_id)) }}" method="POST" class="receive-form">
                                    @csrf
                                    <button type="submit"
                                            class="dropdown-item d-flex align-items-center gap-2"

                                            @disabled($isFinal)>
                                        <i class="bx bx-envelope-open"></i> Receive Document
                                    </button>
                                </form>
                                <a href="{{ route('documents.show', encryptId($document->document_id)) }}"
                                   class="dropdown-item d-flex align-items-center gap-2"
                                    title="">
                                    <i class="bx bx-show"></i> View Document
                                </a>
                            </ul>
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

<style>
/* Sidebar nav active & hover */
.mail-nav .nav-link.active {
    background: rgba(105, 108, 255, 0.16);
    color: #696cff !important;
    font-weight: 600;
}
.mail-nav .nav-link:hover:not(.active) {
    background: rgba(67, 89, 113, 0.06);
}

/* Column header style */
.col-header {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05rem;
    color: #a1acb8;
}

/* Column header background */
.mail-header {
    background: #f8f8f8;
}

/* Mail item hover */
.mail-item:hover {
    background: rgba(67, 89, 113, 0.04);
}
.mail-unread {
    background: rgba(105, 108, 255, 0.04);
}

/* Actions hidden by default, show on hover */
.mail-actions {
    opacity: 0;
    transition: opacity .15s;
}
.mail-item:hover .mail-actions {
    opacity: 1;
}

@media (max-width: 768px) {
    .mail-actions { opacity: 1; }
}
</style>

@section('page-script')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {
    $('.receive-form').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        Swal.fire({
            title: 'Receive Document?',
            text: 'Mark this document as received?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#696cff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, receive it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
    });
});
</script>
@endsection
@endsection