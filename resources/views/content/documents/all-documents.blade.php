@extends('layouts.contentNavbarLayout')

@section('title', 'All Documents')

@section('content')

@include('content.documents.styles.all-documents-style')

<div class="container-xxl flex-grow-1 container-p-y">

    <!-- Page Header -->
    <div class="mb-4">
        <h4 class="fw-bold mb-2"><i class="bx bx-file me-2"></i>My Documents</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb-style1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard-analytics') }}">Home</a>
                </li>
                <li class="breadcrumb-item active">My Documents</li>
            </ol>
        </nav>
    </div>

    <!-- Documents Card -->
    <div class="card">
        <div class="card-body">

            <!-- Top Controls -->
            <form method="GET" action="{{ route('documents.all') }}" id="filterForm">
                <input type="hidden" name="search" id="searchQuery" value="{{ request('search') }}">
                <div class="dt-controls">

                    <!-- Left: show entries + filters -->
                    <div class="dt-left-controls">
                        <label class="dt-length-label">
                            Show
                            <select name="per_page"
                                    class="dt-length-select"
                                    onchange="document.getElementById('filterForm').submit()">
                                @foreach([10, 25, 50, 100] as $len)
                                    <option value="{{ $len }}"
                                        {{ request('per_page', 10) == $len ? 'selected' : '' }}>
                                        {{ $len }}
                                    </option>
                                @endforeach
                            </select>
                            entries
                        </label>

                        <select name="document_type"
                                class="dt-filter-select"
                                onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Types</option>
                            @foreach(['Memorandum','Request Letter','Office Order','Endorsement','Circular','Report','Communication Letter','Travel Order','Purchase Request'] as $type)
                                <option value="{{ $type }}"
                                    {{ request('document_type') == $type ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>

                        <select name="status"
                                class="dt-filter-select"
                                onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Status</option>
                            <option value="pending"  {{ request('status') == 'pending'  ? 'selected' : '' }}>Pending</option>
                            <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
                        </select>

                        @if(request('search') || request('document_type') || request('status'))
                            <a href="{{ route('documents.all') }}" class="btn btn-sm btn-outline-secondary" title="Reset">
                                <i class="bx bx-reset"></i>
                            </a>
                        @endif
                    </div>

                    <!-- Right: search + new document -->
                    <div class="dt-search-wrap">
                        <label class="dt-search-label">
                            Search:
                            <input type="text"
                                   id="liveSearchInput"
                                   class="dt-search-input"
                                value="{{ request('search') }}"
                                placeholder="Tracking code, file name, purpose..."
                                   autocomplete="off">
                        </label>
                        <a href="{{ route('documents.send') }}" class="btn btn-primary btn-sm ms-1" title="Send New Document">
                            <i class="bx bx-plus"></i>
                        </a>
                    </div>

                </div>
            </form>

            <!-- Note about archive restriction -->
            <div class="alert alert-info alert-dismissible fade show mb-3" role="alert" style="font-size: 0.875rem;">
                <i class="bx bx-info-circle me-2"></i>
                <strong>Note:</strong> You cannot archive a document if there are pending recipients or if group members haven't received it yet.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover dt-table w-100">
                    <thead>
                        <tr>
                            <th>Tracking Code</th>
                            <th>Recipients</th>
                            <th>Document Type</th>
                            <th>Purpose</th>
                            <th>File Name</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Sent Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($documents as $document)
                        @php
                            $route = \App\Models\DocumentRoute::where('document_id', $document->document_id)->first();
                            $recipients = $route
                                ? \App\Models\Recipient::with('user.employee')->where('route_id', $route->route_id)->get()
                                : collect();
                            $isGroupSend = $recipients->count() > 1;

                            // Priority
                            $priorityValue = $route?->priority ?? 'normal';
                            $priorityClass = match($priorityValue) {
                                'urgent' => 'bg-danger',
                                default  => 'bg-primary',
                            };

                            // Status
                            $statusValue = $document->status;
                            if ($document->status === 'restored') {
                                $statusValue = 'restored';
                            } elseif ($recipients->isNotEmpty()) {
                                $actions = $recipients->pluck('action')
                                    ->filter()
                                    ->map(fn($a) => strtolower(trim((string) $a)))
                                    ->unique();

                                $hasPending = $recipients->contains(fn($r) => is_null($r->action) || $r->action === 'pending');
                                $hasReceive = $actions->contains('receive')
                                    || $actions->contains('received')
                                    || $recipients->whereNotNull('receive_at')->isNotEmpty();

                                // If document is marked as forwarded, always show forward status
                                if ($document->status === 'forward') {
                                    $statusValue = 'forward';
                                } elseif ($hasPending) {
                                    $statusValue = 'pending';
                                } elseif ($hasReceive) {
                                    $statusValue = 'receive';
                                } else {
                                    $statusValue = 'pending';
                                }
                            }
                            $statusClass = match($statusValue) {
                                'pending'            => 'bg-warning',
                                'forward'            => 'bg-primary',
                                'receive','received' => 'bg-info',
                                'archived'           => 'bg-secondary',
                                'restored'           => 'bg-success',
                                default              => 'bg-secondary',
                            };
                            $today = now()->startOfDay();
                            $dueState = null;
                            if ($document->due_date) {
                                $due = $document->due_date instanceof \Carbon\CarbonInterface
                                    ? $document->due_date->copy()->startOfDay()
                                    : \Carbon\Carbon::parse($document->due_date)->startOfDay();
                                if ($due->lt($today)) {
                                    $dueState = 'overdue';
                                } elseif ($due->equalTo($today)) {
                                    $dueState = 'today';
                                } else {
                                    $dueState = 'upcoming';
                                }
                            }
                        @endphp
                        <tr>
                            <td>
                                <span class="badge bg-label-primary">{{ $document->tracking_code }}</span>
                            </td>
                            <td>
                                @if($isGroupSend)
                                    <button type="button"
                                            class="btn btn-sm btn-outline-info"
                                            data-bs-toggle="modal"
                                            data-bs-target="#recipientsModal{{ $document->document_id }}">
                                        <i class="bx bx-group me-1"></i>Group ({{ $recipients->count() }})
                                    </button>
                                @elseif($recipients->count() > 0)
                                    @php $r = $recipients->first(); @endphp
                                    {{ $r->user->employee
                                        ? $r->user->employee->firstname . ' ' . $r->user->employee->lastname
                                        : $r->user->name }}
                                @else
                                    <span class="text-muted"><i>No recipients</i></span>
                                @endif
                            </td>
                            <td>{{ $document->documentType->type_name ?? 'N/A' }}</td>
                            <td>
                                <span class="text-truncate d-inline-block"
                                      style="max-width:200px"
                                      title="{{ $document->purpose }}">
                                    {{ $document->purpose }}
                                </span>
                            </td>
                            <td>
                                <i class="bx bx-file me-1"></i>{{ $document->file_name }}
                            </td>
                            <td>
                                <span class="badge {{ $priorityClass }}">{{ ucfirst($priorityValue) }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $statusClass }}">{{ ucfirst($statusValue) }}</span>
                                @if($dueState)
                                    <div class="mt-1">
                                        <span class="badge {{ $dueState === 'overdue' ? 'bg-danger' : ($dueState === 'today' ? 'bg-warning text-dark' : 'bg-label-secondary') }}" style="font-size:.65rem;">
                                            {{ $dueState === 'today' ? 'Due Today' : ($dueState === 'overdue' ? 'Overdue' : 'Due ' . optional($document->due_date)->format('M d')) }}
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $document->created_at->format('M d, Y h:i A') }}</small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2 justify-content-center">
                                    <!-- Archive button -->
                                    @if($document->status !== 'archived' && $document->status !== 'pending' && $document->allGroupMembersReceived())
                                        <form action="{{ route('documents.archive', encryptId($document->document_id)) }}"
                                              method="POST"
                                              class="d-inline-flex align-items-center m-0 archive-form">
                                            @csrf
                                            <button type="submit"
                                                    class="btn btn-link btn-icon btn-sm p-0 d-flex align-items-center justify-content-center"
                                                    title="Archive"
                                                    style="color: #6c757d; line-height: 1;">
                                                <i class="bx bx-archive"></i>
                                            </button>
                                        </form>
                                    @endif
                                    
                                    <!-- Download button -->
                                    <a href="{{ route('documents.download', encryptId($document->document_id)) }}"
                                       class="btn btn-link btn-icon btn-sm p-0 d-flex align-items-center justify-content-center"
                                       title="Download"
                                       style="color: #6c757d; line-height: 1;">
                                        <i class="bx bx-download"></i>
                                    </a>
                                </div>
                            </td>

                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="dt-empty">
                                <i class="bx bx-folder-open" style="font-size:64px;color:#ccc;"></i>
                                <p class="text-muted mt-3">No documents found.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Bottom Controls: Info + Pagination -->
            @if($documents->count() > 0)
            <div class="dt-bottom">
                <div class="dt-info">
                    @php
                        $total = method_exists($documents, 'total')     ? $documents->total()     : $documents->count();
                        $from  = method_exists($documents, 'firstItem') ? $documents->firstItem() : 1;
                        $to    = method_exists($documents, 'lastItem')  ? $documents->lastItem()  : $documents->count();
                    @endphp
                    Showing {{ $from }} to {{ $to }} of {{ $total }} entries
                    @if(request('search') || request('document_type') || request('status'))
                        <span class="text-muted">(filtered)</span>
                    @endif
                </div>

                @if(method_exists($documents, 'lastPage'))
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
                        <a class="page-link"
                           href="{{ !$documents->onFirstPage() ? $query->previousPageUrl() : '#' }}">‹</a>
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
                        <li class="page-item"><a class="page-link" href="{{ $query->url($last) }}">{{ $last }}</a></li>
                    @endif

                    <li class="page-item {{ !$documents->hasMorePages() ? 'disabled' : '' }}">
                        <a class="page-link"
                           href="{{ $documents->hasMorePages() ? $query->nextPageUrl() : '#' }}">›</a>
                    </li>
                </ul>
                @endif
            </div>
            @endif

        </div>
    </div>

    <!-- Recipient Modals -->
    @foreach($documents as $document)
        @php
            $routeM = \App\Models\DocumentRoute::where('document_id', $document->document_id)->first();
            $recipientsM = $routeM
                ? \App\Models\Recipient::with('user.employee')->where('route_id', $routeM->route_id)->get()
                : collect();
        @endphp
        @if($recipientsM->count() > 1)
        <div class="modal fade"
             id="recipientsModal{{ $document->document_id }}"
             tabindex="-1"
             aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bx bx-group me-2"></i>Recipients — {{ $document->tracking_code }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="list-group list-group-flush">
                            @foreach($recipientsM as $recipient)
                            <li class="list-group-item">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bx bx-user-circle fs-4 text-muted"></i>
                                    <div>
                                        <div class="fw-semibold">
                                            {{ $recipient->user->employee
                                                ? $recipient->user->employee->firstname . ' ' . $recipient->user->employee->lastname
                                                : $recipient->user->name }}
                                        </div>
                                        <span class="badge {{ ($recipient->action ?? 'pending') === 'pending' ? 'bg-warning' : 'bg-info' }}">{{ ucfirst($recipient->action ?? 'pending') }}</span>
                                        <small class="text-muted d-block">
                                            <i class="bx bx-envelope me-1"></i>{{ $recipient->user->email }}
                                        </small>
                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    @endforeach

</div>{{-- /container-xxl --}}

@endsection

@include('content.documents.scripts.all-documents-script')