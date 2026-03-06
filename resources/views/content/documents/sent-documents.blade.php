@extends('layouts.contentNavbarLayout')

@section('title', 'Sent Documents')

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
.sent-document-row { cursor: pointer; }

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

/* ── Page title icon ── */
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
                <i class="bx bx-send text-primary fs-4"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-1">Sent</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1 mb-0" style="font-size: 0.8rem;">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard-analytics') }}">Home</a></li>
                        <li class="breadcrumb-item inactive">Mail</li>
                        <li class="breadcrumb-item active">Sent Documents</li>
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
                {{ $documents->firstItem() ?? 0 }}&ndash;{{ $documents->lastItem() ?? 0 }}
                of {{ $documents->total() ?? 0 }}
                <a href="{{ !$documents->onFirstPage() ? $documents->previousPageUrl() : '#' }}"
                   class="btn btn-icon btn-sm btn-outline-secondary border-0 {{ $documents->onFirstPage() ? 'disabled' : '' }}">
                    <i class="bx bx-chevron-left"></i>
                </a>
                <a href="{{ $documents->hasMorePages() ? $documents->nextPageUrl() : '#' }}"
                   class="btn btn-icon btn-sm btn-outline-secondary border-0 {{ !$documents->hasMorePages() ? 'disabled' : '' }}">
                    <i class="bx bx-chevron-right"></i>
                </a>
            </div>
        </div>

        {{-- Column Headers ── --}}
        <div class="mail-header d-flex align-items-center gap-3 px-4 py-2 border-bottom flex-shrink-0">
            <div class="col-header" style="width: 200px;">Recipient</div>
            <div class="col-header flex-grow-1">Document Type — Purpose</div>
            <div class="col-header d-none d-xl-block" style="min-width: 160px;">Tracking Code</div>
            <div class="col-header d-none d-lg-block" style="min-width: 80px;">Priority</div>
            <div class="col-header d-none d-lg-block" style="min-width: 80px;">Status</div>
            <div class="col-header d-none d-lg-block" style="min-width: 90px; text-align: center;">Sent Date</div>
        </div>

        {{-- Mail list ── --}}
        <div class="mail-list">
            @forelse($documents as $document)
            @php
                $route = \App\Models\DocumentRoute::with('group')
                    ->where('document_id', $document->document_id)
                    ->first();
                $recipients = $route
                    ? \App\Models\Recipient::with('user.employee')
                        ->where('route_id', $route->route_id)
                        ->get()
                    : collect();

                $groupName     = $route?->group ? $route->group->position : null;
                $priorityValue = $route?->priority ?? 'normal';
                $priorityClass = match($priorityValue) {
                    'urgent' => 'bg-danger',
                    'high'   => 'bg-warning',
                    'low'    => 'bg-secondary',
                    default  => 'bg-primary',
                };

                $statusValue = $document->status;
                if ($recipients->isNotEmpty()) {
                    $actions    = $recipients->pluck('action')->filter()->map(fn($a) => strtolower(trim((string)$a)))->unique();
                    $hasPending = $recipients->contains(fn($r) => is_null($r->action) || $r->action === 'pending');
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
                    default              => 'bg-secondary',
                };

                $singleRecipient = $recipients->count() === 1 ? $recipients->first() : null;
                $recipientLabel  = 'No recipients';
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

            <div class="mail-item d-flex align-items-center gap-3 px-4 py-2 border-bottom sent-document-row"
                 data-bs-toggle="modal"
                 data-bs-target="#sentDocumentModal-{{ $document->document_id }}">

                {{-- Recipient --}}
                <div class="flex-shrink-0" style="width: 200px; overflow: hidden;">
                    <span class="text-body"
                          style="font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;">
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
                <div class="d-none d-xl-block" style="min-width: 160px;">
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
                <div class="text-muted d-none d-lg-flex flex-shrink-0"
                     style="font-size: 0.8rem; min-width: 90px; justify-content: center;">
                    {{ $document->created_at?->format('M d, Y') ?? 'N/A' }}
                </div>
            </div>

            @empty
                <div class="text-center py-5 my-5">
                    <i class="bx bx-send" style="font-size: 64px; color: #ccc;"></i>
                    <p class="text-muted mt-3 mb-0">No sent documents found.</p>
                </div>
            @endforelse
        </div>

        {{-- ── Pagination footer ── --}}
        <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between flex-shrink-0 mt-auto"
             style="background: #fafbff;">
            <span class="text-muted" style="font-size: 0.8125rem;">
                Showing {{ $documents->firstItem() }} to {{ $documents->lastItem() }}
                of {{ $documents->total() }} results
            </span>

            @php
                $current = $documents->currentPage();
                $last    = $documents->lastPage();
                $window  = 2;
                $start   = max(1, $current - $window);
                $end     = min($last, $current + $window);
                $query   = $documents->appends(request()->query());
            @endphp
            <ul class="dt-pagination">
                <li class="page-item {{ $documents->onFirstPage() ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ !$documents->onFirstPage() ? $query->previousPageUrl() : '#' }}">‹</a>
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
                <li class="page-item {{ !$documents->hasMorePages() ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $documents->hasMorePages() ? $query->nextPageUrl() : '#' }}">›</a>
                </li>
            </ul>
        </div>

    </div>{{-- /mail-card --}}
</div>

{{-- ── MODALS (unchanged) ── --}}
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
        $pendingRecipients = $recipients->filter(fn($r) => is_null($r->action) || $r->action === 'pending');

        $priorityVal   = $route?->priority ?? 'normal';
        $priorityBadge = match($priorityVal) {
            'urgent' => 'bg-danger',
            'high'   => 'bg-warning',
            'low'    => 'bg-secondary',
            default  => 'bg-primary',
        };

        $statusVal = $document->status;
        if ($recipients->isNotEmpty()) {
            $actions    = $recipients->pluck('action')->filter()->map(fn($a) => strtolower(trim((string)$a)))->unique();
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

                <div class="modal-header border-bottom-0 pb-1">
                    <h5 class="modal-title d-flex flex-column gap-1">
                        <span class="d-flex align-items-center gap-2">
                            <i class="bx bx-file text-muted"></i>
                            <span class="fw-semibold">{{ $document->documentType?->type_name ?? 'Document' }}</span>
                            <span style="color: #e74c3c; font-weight: 600; font-size: 0.9rem;">
                                {{ $document->tracking_code ?? 'N/A' }}
                            </span>
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
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">File Name</div>
                                <div class="fw-semibold" style="font-size: 0.9rem;">{{ $document->file_name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">Tracking Code</div>
                                <div class="fw-semibold" style="color: #e74c3c; font-size: 0.9rem;">{{ $document->tracking_code ?? 'N/A' }}</div>
                            </div>
                            <div class="col-12">
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">Purpose</div>
                                <div style="font-size: 0.9rem;">{{ $document->purpose }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">Priority</div>
                                <span class="badge {{ $priorityBadge }}" style="font-size: 0.75rem;">{{ ucfirst($priorityVal) }}</span>
                            </div>
                            <div class="col-4">
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">Status</div>
                                <span class="badge {{ $statusBadge }}" style="font-size: 0.75rem;">{{ ucfirst($statusVal) }}</span>
                            </div>
                            <div class="col-4">
                                <div class="text-muted mb-1" style="font-size: 0.78rem;">Sent At</div>
                                <div style="font-size: 0.85rem;">{{ $document->created_at?->format('M d, Y H:i') ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <i class="bx bx-group text-muted" style="font-size: 0.8rem;"></i>
                            <span class="text-uppercase fw-bold text-muted"
                                  style="font-size: 0.7rem; letter-spacing: 0.08em;">Recipients</span>
                            @if($route?->group_id && $route->group)
                                <span class="text-muted fw-semibold" style="font-size: 0.75rem;">
                                    &mdash; {{ strtoupper($route->group->position) }}
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
                                        $rBadge    = match(strtolower($action)) {
                                            'receive','received' => 'bg-info',
                                            'approved'           => 'bg-success',
                                            'rejected'           => 'bg-danger',
                                            default              => 'bg-warning',
                                        };
                                    @endphp
                                    <div class="d-flex align-items-center justify-content-between py-2 px-1"
                                         style="border-bottom: 1px solid #f0f0f0;">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar avatar-sm flex-shrink-0">
                                                <span class="avatar-initial rounded-circle bg-label-secondary fw-bold"
                                                      style="width: 36px; height: 36px; font-size: 0.85rem;">
                                                    {{ strtoupper(substr($name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <div>
                                                <div class="fw-semibold" style="font-size: 0.875rem;">{{ strtoupper($name) }}</div>
                                                <small class="text-muted">{{ $recipient->user->email }}</small>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                            <span class="badge {{ $rBadge }}"
                                                  style="font-size: 0.75rem; min-width: 60px; text-align: center;">
                                                {{ ucfirst($action) }}
                                            </span>
                                            @if($isPending)
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                                                        style="font-size: 0.78rem; padding: 3px 10px;"
                                                        onclick="confirmUnsend(
                                                            '{{ route('documents.unsend-recipient', [encryptId($document->document_id), encryptId($recipient->recipient_id)]) }}',
                                                            '{{ addslashes($name) }}'
                                                        )">
                                                    <i class="bx bx-x" style="font-size: 0.9rem;"></i> Unsend
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div class="modal-footer border-top-0 justify-content-between pt-1">
                    <a href="{{ route('documents.download', encryptId($document->document_id)) }}"
                       class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                        <i class="bx bx-download me-1"></i> Download
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div>
@endforeach

@endsection

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
        customClass: { container: 'swal-over-modal' },
        didOpen: function () {
            const el = document.querySelector('.swal-over-modal');
            if (el) el.style.zIndex = 99999;
        }
    }).then(function (result) {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Removing…',
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
        .then(r => r.json())
        .then(function (data) {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Removed!',
                    text: data.message || 'Recipient removed successfully.',
                    confirmButtonColor: '#696cff',
                    customClass: { container: 'swal-over-modal' }
                }).then(() => location.reload());
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