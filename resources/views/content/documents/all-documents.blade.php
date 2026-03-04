@extends('layouts.contentNavbarLayout')

@section('title', 'All Documents')

@section('content')

<style>
    /* ── Top controls ── */
    .dt-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .dt-left-controls {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .dt-length-label {
        font-size: 0.875rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .dt-length-select {
        display: inline-block;
        padding: 0.25rem 1.75rem 0.25rem 0.6rem;
        font-size: 0.875rem;
        border: 1px solid #d9dee3;
        border-radius: 0.375rem;
        background-color: #fff;
        appearance: auto;
        cursor: pointer;
        color: #4a5568;
    }
    .dt-length-select:focus {
        outline: none;
        border-color: #696cff;
        box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.15);
    }
    .dt-filter-select {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border: 1px solid #d9dee3;
        border-radius: 0.375rem;
        background-color: #fff;
        cursor: pointer;
        color: #4a5568;
        min-width: 140px;
    }
    .dt-filter-select:focus {
        outline: none;
        border-color: #696cff;
        box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.15);
    }
    .dt-search-wrap {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .dt-search-label {
        font-size: 0.875rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .dt-search-input {
        border: 1px solid #d9dee3;
        border-radius: 0.375rem;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        min-width: 220px;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .dt-search-input:focus {
        outline: none;
        border-color: #696cff;
        box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.15);
    }

    /* ── Table ── */
    .dt-table thead th {
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6c757d;
        border-top: none;
        border-bottom: 1px solid #e9ecef !important;
        padding: 0.85rem 1rem;
        white-space: nowrap;
        background: #fff;
    }
    .dt-table tbody td {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f1f3;
        color: #4a5568;
    }
    .dt-table tbody tr:last-child td { border-bottom: none; }
    .dt-table tbody tr:hover { background-color: #f8f8ff; }

    /* ── Bottom controls ── */
    .dt-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1.25rem;
        flex-wrap: wrap;
        gap: 0.75rem;
    }
    .dt-info {
        font-size: 0.8125rem;
        color: #6c757d;
    }

    /* ── Pagination ── */
    .dt-pagination {
        display: flex;
        align-items: center;
        gap: 3px;
        list-style: none;
        margin: 0;
        padding: 0;
    }
    .dt-pagination .page-item .page-link {
        border: 1px solid transparent;
        border-radius: 0.375rem !important;
        padding: 0.3rem 0.65rem;
        font-size: 0.875rem;
        color: #6c757d;
        background: transparent;
        min-width: 34px;
        text-align: center;
        line-height: 1.5;
        transition: background 0.15s, color 0.15s;
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

    /* ── Empty state ── */
    .dt-empty { padding: 3rem 1rem; text-align: center; }
</style>

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
                                   name="search"
                                   class="dt-search-input"
                                   value="{{ request('search') }}"
                                   placeholder="Tracking code or file name…"
                                   autocomplete="off">
                        </label>
                        <a href="{{ route('documents.send') }}" class="btn btn-primary btn-sm ms-1" title="Send New Document">
                            <i class="bx bx-plus"></i>
                        </a>
                    </div>

                </div>
            </form>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover dt-table w-100">
                    <thead>
                        <tr>
                            <th>Tracking Code</th>
                            <th>Sent To</th>
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
                                'high'   => 'bg-warning',
                                'low'    => 'bg-secondary',
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

                                $statusValue = $hasPending ? 'pending' : ($hasReceive ? 'receive' : 'pending');
                            }
                            $statusClass = match($statusValue) {
                                'pending'            => 'bg-warning',
                                'receive','received' => 'bg-info',
                                'archived'           => 'bg-secondary',
                                'restored'           => 'bg-success',
                                default              => 'bg-secondary',
                            };
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
                            </td>
                            <td>
                                <small class="text-muted">{{ $document->created_at->format('M d, Y h:i A') }}</small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    @if($statusValue !== 'pending')
                                        <form action="{{ route('documents.delete-document', encryptId($document->document_id)) }}"
                                              method="POST"
                                              class="d-inline delete-document-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                    title="Delete">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </form>
                                        @if($document->status !== 'archived')
                                            <form action="{{ route('documents.archive', encryptId($document->document_id)) }}"
                                                  method="POST"
                                                  class="d-inline archive-form">
                                                @csrf
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-secondary"
                                                        title="Archive">
                                                    <i class="bx bx-archive"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                    <a href="{{ route('documents.download', encryptId($document->document_id)) }}"
                                       class="btn btn-sm btn-outline-success"
                                       title="Download">
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
                                        <span class="badge bg-info">{{ ucfirst($recipient->action ?? 'pending') }}</span>
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

@section('page-script')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    });

    // Delete confirmation
    $('.delete-document-form').on('submit', function (e) {
        e.preventDefault();
        const form = this;

        Swal.fire({
            title: 'Delete Document?',
            html: 'Delete this document from your list? Receivers will keep their copies.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Deleting…',
                text: 'Please wait.',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: $(form).attr('action'),
                type: 'POST',
                dataType: 'json',
                data: $(form).serialize(),
                success: function (response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted',
                        text: response.message || 'Document removed.',
                        confirmButtonColor: '#3085d6'
                    }).then(() => location.reload());
                },
                error: function (xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: xhr.responseJSON?.message || 'Failed to delete.',
                        confirmButtonColor: '#d33'
                    });
                }
            });
        });
    });

    // Archive confirmation
    $('.archive-form').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        const code = $(this).closest('tr').find('.badge.bg-label-primary').text().trim();

        Swal.fire({
            title: 'Archive Document?',
            text: `Move "${code}" to archive?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#696cff',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, archive it',
            cancelButtonText: 'Cancel'
        }).then(result => {
            if (result.isConfirmed) form.submit();
        });
    });
});
</script>
@endsection