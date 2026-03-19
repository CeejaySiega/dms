@extends('layouts.contentNavbarLayout')

@section('title', 'Sent Documents')

@section('content')

@include('content.documents.styles.sent-documents-style')

<div class="container-xxl flex-grow-1 container-p-y">

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
        <a href="{{ route('documents.send') }}"
           class="btn btn-primary fw-semibold align-self-start d-flex align-items-center gap-1"
           style="box-shadow: 0 2px 8px rgba(105,108,255,0.25);">
            <i class="bx bx-plus"></i> Send Document
        </a>
    </div>

    <div class="hint-bar">
        <i class="bx bx-info-circle"></i>
        Click a row to view document details, actions and current status.
    </div>

    <div class="card mail-card">

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

        <div class="mail-header d-flex align-items-center gap-3 px-4 py-2 border-bottom flex-shrink-0">
            <div class="col-header" style="width: 200px;">Sent To</div>
            <div class="col-header flex-grow-1">Document Type - Purpose</div>
            <div class="col-header d-none d-xl-block" style="min-width: 160px;">Tracking Code</div>
            <div class="col-header d-none d-lg-block" style="min-width: 80px;">Priority</div>
            <div class="col-header d-none d-lg-block" style="min-width: 80px;">Status</div>
            <div class="col-header d-none d-lg-block" style="min-width: 90px; text-align: center;">Sent Date</div>
        </div>

        <div class="mail-list">
            @forelse($documents as $document)
            @php
                $route = \App\Models\DocumentRoute::with('group')
                    ->where('document_id', $document->document_id)
                    ->where('sender_id', Auth::id())
                    ->first();
                $recipients = $route
                    ? \App\Models\Recipient::with('user.employee')
                        ->where('route_id', $route->route_id)
                        ->get()
                    : collect();

                $groupName     = $route?->group ? $route->group->position : null;
                $priorityValue = $route?->priority ?? 'normal';
                $priorityClass = match($priorityValue) {
                    'urgent' => 'bg-danger', 'high' => 'bg-warning',
                    'low'    => 'bg-secondary', default => 'bg-primary',
                };

                $statusValue = $document->status;
                if ($recipients->isNotEmpty()) {
                    $actions     = $recipients->pluck('action')->filter()->map(fn($a) => strtolower(trim((string)$a)))->unique();
                    $hasPending  = $recipients->contains(fn($r) => is_null($r->action) || $r->action === 'pending');
                    $hasReceive  = $actions->contains('receive') || $actions->contains('received') || $recipients->whereNotNull('receive_at')->isNotEmpty();
                    $isForwarded = !is_null($route?->forward_at);
                    if ($hasPending && $isForwarded)        $statusValue = 'forwarded';
                    elseif ($hasPending)                    $statusValue = 'pending';
                    elseif ($hasReceive)                    $statusValue = 'receive';
                    elseif ($actions->contains('approved')) $statusValue = 'approved';
                    elseif ($actions->contains('rejected')) $statusValue = 'rejected';
                    else                                    $statusValue = 'pending';
                }
                $statusClass = match($statusValue) {
                    'pending'            => 'bg-warning', 'forwarded' => 'bg-primary',
                    'approved'           => 'bg-success', 'rejected'  => 'bg-danger',
                    'receive','received' => 'bg-info',    default     => 'bg-secondary',
                };

                $singleRecipient = $recipients->count() === 1 ? $recipients->first() : null;
                $recipientLabel  = 'No recipients';
                if ($groupName)                   $recipientLabel = $groupName;
                elseif ($recipients->count() > 1) $recipientLabel = $recipients->count() . ' Recipients';
                elseif ($singleRecipient) {
                    $emp = $singleRecipient->user->employee;
                    $recipientLabel = $emp
                        ? ($emp->firstname . ' ' . $emp->lastname)
                        : $singleRecipient->user->name;
                }
            @endphp

            <div class="mail-item d-flex align-items-center gap-3 px-4 py-2 border-bottom sent-document-row"
                 data-bs-toggle="modal"
                 data-bs-target="#sentDocumentModal-{{ $document->document_id }}">
                <div class="flex-shrink-0" style="width:200px;overflow:hidden;">
                    <span class="text-body"
                          style="font-size:.875rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;">
                        {{ $recipientLabel }}
                    </span>
                </div>
                <div class="flex-grow-1 text-truncate" style="font-size:.875rem;">
                    <span class="fw-semibold text-body">{{ $document->documentType?->type_name ?? 'Document' }}</span>
                    <span class="text-muted"> - {{ $document->purpose ?? 'N/A' }}</span>
                </div>
                <div class="d-none d-xl-block" style="min-width:160px;">
                    <span class="badge bg-label-primary" style="font-size:.7rem;">{{ $document->tracking_code ?? 'N/A' }}</span>
                </div>
                <div class="d-none d-lg-block" style="min-width:80px;">
                    <span class="badge {{ $priorityClass }}" style="font-size:.7rem;">{{ ucfirst($priorityValue) }}</span>
                </div>
                <div class="d-none d-lg-block" style="min-width:80px;">
                    <span class="badge {{ $statusClass }}" style="font-size:.7rem;">{{ ucfirst($statusValue) }}</span>
                </div>
                <div class="text-muted d-none d-lg-flex flex-shrink-0"
                     style="font-size:.8rem;min-width:90px;justify-content:center;">
                    {{ $document->created_at?->format('M d, Y') ?? 'N/A' }}
                </div>
            </div>

            @empty
            <div class="text-center py-5 my-5">
                <i class="bx bx-send" style="font-size:64px;color:#ccc;"></i>
                <p class="text-muted mt-3 mb-0">No sent documents found.</p>
            </div>
            @endforelse
        </div>

        <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between flex-shrink-0 mt-auto"
             style="background:#fafbff;">
            <span class="text-muted" style="font-size:.8125rem;">
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
                    @if($start > 2)<li class="page-item disabled"><span class="page-link">…</span></li>@endif
                @endif
                @for($p = $start; $p <= $end; $p++)
                    <li class="page-item {{ $p === $current ? 'active' : '' }}">
                        <a class="page-link" href="{{ $query->url($p) }}">{{ $p }}</a>
                    </li>
                @endfor
                @if($end < $last)
                    @if($end < $last - 1)<li class="page-item disabled"><span class="page-link">…</span></li>@endif
                    <li class="page-item"><a class="page-link" href="{{ $query->url($last) }}">{{ $last }}</a></li>
                @endif
                <li class="page-item {{ !$documents->hasMorePages() ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ $documents->hasMorePages() ? $query->nextPageUrl() : '#' }}">›</a>
                </li>
            </ul>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════
    MODALS — Combined Details + Trail view
    Unsend button appears inline on pending trail rows
══════════════════════════════════════════════════════ --}}
@foreach($documents as $document)
    @php
        $route = \App\Models\DocumentRoute::with('group')
            ->where('document_id', $document->document_id)
            ->where('sender_id', Auth::id())
            ->first();
        $recipients = $route
            ? \App\Models\Recipient::with('user.employee')
                ->where('route_id', $route->route_id)
                ->get()
            : collect();

        $priorityVal   = $route?->priority ?? 'normal';
        $priorityBadge = match($priorityVal) {
            'urgent' => 'bg-danger', 'high' => 'bg-warning',
            'low'    => 'bg-secondary', default => 'bg-primary',
        };

        $statusVal = $document->status;
        if ($recipients->isNotEmpty()) {
            $actions     = $recipients->pluck('action')->filter()->map(fn($a) => strtolower(trim((string)$a)))->unique();
            $hasPending  = $recipients->contains(fn($r) => is_null($r->action) || $r->action === 'pending');
            $hasReceive  = $actions->contains('receive') || $actions->contains('received') || $recipients->whereNotNull('receive_at')->isNotEmpty();
            $isForwarded = !is_null($route?->forward_at);
            if ($hasPending && $isForwarded)        $statusVal = 'forwarded';
            elseif ($hasPending)                    $statusVal = 'pending';
            elseif ($hasReceive)                    $statusVal = 'receive';
            elseif ($actions->contains('approved')) $statusVal = 'approved';
            elseif ($actions->contains('rejected')) $statusVal = 'rejected';
            else                                    $statusVal = 'pending';
        }
        $statusBadge = match($statusVal) {
            'pending'            => 'bg-warning', 'forwarded' => 'bg-primary',
            'approved'           => 'bg-success', 'rejected'  => 'bg-danger',
            'receive','received' => 'bg-info',    default     => 'bg-secondary',
        };

        // Build unsend map: user_id => { url, name } for pending recipients only.
        $unsendMap = [];
        if (Auth::id() === $document->user_id) {
            foreach ($recipients as $r) {
                if (is_null($r->action) || $r->action === 'pending') {
                    $emp  = $r->user->employee;
                    $name = $emp
                        ? ($emp->firstname . ' ' . $emp->lastname)
                        : $r->user->name;
                    $unsendMap[$r->user_id] = [
                        'url'  => route('documents.unsend-recipient', [
                                    encryptId($document->document_id),
                                    encryptId($r->recipient_id),
                                  ]),
                        'name' => $name,
                    ];
                }
            }
        }
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
                            <span style="color:#e74c3c;font-weight:600;font-size:.9rem;">
                                {{ $document->tracking_code ?? 'N/A' }}
                            </span>
                        </span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                {{-- Body --}}
                <div class="modal-body pt-2">
                    <div class="mb-3 pb-2 border-bottom">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bx bx-info-circle text-primary"></i>
                            <h6 class="mb-0 fw-semibold">Details</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-muted mb-1" style="font-size:.78rem;">File Name</div>
                                <div class="fw-semibold" style="font-size:.9rem;">{{ $document->file_name ?? 'N/A' }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted mb-1" style="font-size:.78rem;">Tracking Code</div>
                                <div class="fw-semibold" style="color:#e74c3c;font-size:.9rem;">{{ $document->tracking_code ?? 'N/A' }}</div>
                            </div>
                            <div class="col-12">
                                <div class="text-muted mb-1" style="font-size:.78rem;">Purpose</div>
                                <div style="font-size:.9rem;">{{ $document->purpose ?? 'N/A' }}</div>
                            </div>
                            <div class="col-4">
                                <div class="text-muted mb-1" style="font-size:.78rem;">Priority</div>
                                <span class="badge {{ $priorityBadge }}" style="font-size:.75rem;">{{ ucfirst($priorityVal) }}</span>
                            </div>
                            <div class="col-4">
                                <div class="text-muted mb-1" style="font-size:.78rem;">Status</div>
                                <span class="badge {{ $statusBadge }}" style="font-size:.75rem;">{{ ucfirst($statusVal) }}</span>
                            </div>
                            <div class="col-4">
                                <div class="text-muted mb-1" style="font-size:.78rem;">Sent At</div>
                                <div style="font-size:.85rem;">{{ $document->created_at?->format('M d, Y H:i') ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="trail-section"
                         id="trail-section-{{ $document->document_id }}"
                         data-document-id="{{ $document->document_id }}"
                         data-unsend='@json($unsendMap)'>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bx bx-transfer-alt text-primary"></i>
                            <h6 class="mb-0 fw-semibold">Trail</h6>
                        </div>

                        {{-- Loading --}}
                        <div class="trail-loading text-center py-5">
                            <div class="spinner-border text-primary"
                                 style="width:1.6rem;height:1.6rem;" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="text-muted mt-2" style="font-size:.8rem;">Loading trail…</div>
                        </div>

                        {{-- Error --}}
                        <div class="trail-error d-none">
                            <div class="alert alert-danger py-2" style="font-size:.82rem;">
                                <i class="bx bx-error me-1"></i>
                                Failed to load trail. Please try again.
                            </div>
                        </div>

                        {{-- Trail content --}}
                        <div class="trail-data d-none">
                            <div class="trail-log-wrap">
                                <div class="trail-log-title">Detailed Trail Log</div>
                                <div class="trail-log-body"></div>
                                <div class="trail-legend">
                                    <div class="tleg"><div class="tleg-dot" style="background:#7F77DD;"></div> Sender</div>
                                    <div class="tleg"><div class="tleg-dot" style="background:#1D9E75;"></div> Received</div>
                                    <div class="tleg"><div class="tleg-dot" style="background:#BA7517;"></div> Forwarded</div>
                                    <div class="tleg"><div class="tleg-dot" style="background:#3B82F6;animation:pulseBlue 2s infinite;"></div> Currently with</div>
                                    <div class="tleg"><div class="tleg-dot" style="background:#9ca3af;"></div> Pending</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>{{-- /modal-body --}}

                {{-- Footer --}}
                <div class="modal-footer border-top-0 justify-content-between pt-1">
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('documents.download', encryptId($document->document_id)) }}"
                           class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                            <i class="bx bx-download me-1"></i> Download
                        </a>
                        <a href="{{ route('documents.forward.form', [
                                'documentId' => encryptId($document->document_id),
                                'base_route' => $route ? encryptId($route->route_id) : null,
                                'source'     => 'sent',
                           ]) }}"
                           class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                            <i class="bx bx-share-alt me-1"></i> Forward
                        </a>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>

            </div>
        </div>
    </div>
@endforeach

@endsection

@section('page-script')
@include('content.documents.scripts.sent-documents-script')
@endsection