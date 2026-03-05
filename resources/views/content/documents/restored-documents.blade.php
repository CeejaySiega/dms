@extends('layouts.contentNavbarLayout')

@section('title', 'Restored Documents')

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
    @media (max-width: 768px) {
        .dt-controls {
            flex-direction: column;
            align-items: stretch;
        }
    }
    .dt-length-label {
        font-size: 0.875rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    @media (max-width: 576px) {
        .dt-length-label {
            font-size: 0.75rem;
        }
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
    @media (max-width: 576px) {
        .dt-length-select {
            width: 100%;
            font-size: 0.75rem;
        }
    }
    .dt-length-select:focus {
        outline: none;
        border-color: #696cff;
        box-shadow: 0 0 0 0.2rem rgba(105, 108, 255, 0.15);
    }
    .dt-search-label {
        font-size: 0.875rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    @media (max-width: 768px) {
        .dt-search-label {
            flex-direction: column;
            align-items: flex-start;
        }
    }
    .dt-search-input {
        border: 1px solid #d9dee3;
        border-radius: 0.375rem;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        min-width: 400px;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    @media (max-width: 768px) {
        .dt-search-input {
            min-width: 100%;
            width: 100%;
        }
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
    @media (max-width: 576px) {
        .dt-table thead th {
            font-size: 0.65rem;
            padding: 0.5rem 0.5rem;
            white-space: normal;
        }
    }
    .dt-table tbody td {
        padding: 0.8rem 1rem;
        font-size: 0.875rem;
        vertical-align: middle;
        border-bottom: 1px solid #f0f1f3;
        color: #4a5568;
    }
    @media (max-width: 576px) {
        .dt-table tbody td {
            padding: 0.5rem 0.5rem;
            font-size: 0.75rem;
        }
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
    @media (max-width: 576px) {
        .dt-bottom {
            flex-direction: column;
            align-items: stretch;
        }
    }
    .dt-info {
        font-size: 0.8125rem;
        color: #6c757d;
    }
    @media (max-width: 576px) {
        .dt-info {
            font-size: 0.75rem;
            text-align: center;
        }
    }

    /* ── Pagination ── */
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
        font-size: 0.875rem;
        color: #6c757d;
        background: transparent;
        min-width: 34px;
        text-align: center;
        line-height: 1.5;
        transition: background 0.15s, color 0.15s;
    }
    @media (max-width: 576px) {
        .dt-pagination .page-item .page-link {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            min-width: 28px;
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

    /* ── Empty state ── */
    .dt-empty {
        padding: 3rem 1rem;
        text-align: center;
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-md-12">

            <!-- Header -->
            <div class="mb-4">
                <h4 class="fw-bold mb-2"><i class="bx bx-undo me-2"></i>Restored Documents</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard-analytics') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item active">Restored Documents</li>
                    </ol>
                </nav>
            </div>

            <!-- Alert Messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <!-- Card -->
            <div class="card">
                <div class="card-body">

                    <!-- Top Controls: Show entries + Search -->
                    <div class="dt-controls">
                        <label class="dt-length-label">
                            Show
                            <form method="GET" action="{{ route('documents.restored') }}" id="perPageForm">
                                <input type="hidden" name="search" value="{{ request('search') }}">
                                <select name="per_page"
                                        class="dt-length-select"
                                        onchange="document.getElementById('perPageForm').submit()">
                                    @foreach([10, 25, 50, 100] as $len)
                                        <option value="{{ $len }}"
                                            {{ request('per_page', 10) == $len ? 'selected' : '' }}>
                                            {{ $len }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                            entries
                        </label>

                        <form method="GET" action="{{ route('documents.restored') }}">
                            <input type="hidden" name="per_page" value="{{ request('per_page', 10) }}">
                            <label class="dt-search-label">
                                Search:
                                <input type="text"
                                       name="search"
                                       id="liveSearchInput"
                                       class="dt-search-input"
                                       value="{{ request('search') }}"
                                       placeholder="Tracking code, file name, purpose…"
                                       autocomplete="off">
                            </label>
                        </form>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-hover dt-table w-100">
                            <thead>
                                <tr>
                                    <th>Tracking Code</th>
                                    <th>Document Type</th>
                                    <th>Purpose</th>
                                    <th>File Name</th>
                                    <th>Archived At</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($restoredDocuments as $document)
                                <tr>
                                    <td>
                                        <span class="badge bg-label-primary">{{ $document->tracking_code }}</span>
                                    </td>
                                    <td>
                                        <i class="bx bx-file me-1 text-info"></i>
                                        {{ $document->documentType->type_name ?? 'N/A' }}
                                    </td>
                                    <td>
                                        <span class="text-truncate d-inline-block"
                                              style="max-width:250px"
                                              title="{{ $document->purpose }}">
                                            {{ Str::limit($document->purpose, 50) }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <i class="bx bx-file me-1"></i>{{ $document->file_name ?? 'N/A' }}
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            @if($document->deleted_at)
                                                {{ $document->deleted_at->format('M d, Y h:i A') }}
                                            @else
                                                <i>Not set</i>
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-success">
                                            <i class="bx bx-check-circle me-1"></i>Active
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('documents.show', encryptId($document->document_id)) }}"
                                               class="btn btn-outline-info"
                                               title="View Details">
                                                <i class="bx bx-eye"></i>
                                            </a>
                                            <a href="{{ route('documents.download', encryptId($document->document_id)) }}"
                                               class="btn btn-outline-success"
                                               title="Download">
                                                <i class="bx bx-download"></i>
                                            </a>
                                            <form action="{{ route('documents.archive', $document->document_id) }}"
                                                  method="POST"
                                                  class="d-inline archive-form">
                                                @csrf
                                                <button type="submit"
                                                        class="btn btn-outline-secondary"
                                                        title="Archive Again">
                                                    <i class="bx bx-archive"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="dt-empty">
                                        <i class="bx bx-undo" style="font-size:64px;color:#ccc;"></i>
                                        <p class="text-muted mt-3 mb-1">No restored documents found.</p>
                                        <p class="text-muted small mb-3">Restore documents from archive to see them here.</p>
                                        <a href="{{ route('documents.archived') }}" class="btn btn-primary btn-sm">
                                            <i class="bx bx-arrow-back me-1"></i> Go to Archived Documents
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Bottom Controls: Info + Pagination -->
                    @if($restoredDocuments->count() > 0)
                    <div class="dt-bottom">

                        <!-- Info -->
                        <div class="dt-info">
                            @php
                                $total = method_exists($restoredDocuments, 'total')     ? $restoredDocuments->total()     : $restoredDocuments->count();
                                $from  = method_exists($restoredDocuments, 'firstItem') ? $restoredDocuments->firstItem() : 1;
                                $to    = method_exists($restoredDocuments, 'lastItem')  ? $restoredDocuments->lastItem()  : $restoredDocuments->count();
                            @endphp
                            Showing {{ $from }} to {{ $to }} of {{ $total }} entries
                            @if(request('search'))
                                <span>(filtered)</span>
                            @endif
                        </div>

                        <!-- Pagination -->
                        @if(method_exists($restoredDocuments, 'lastPage'))
                        @php
                            $current = $restoredDocuments->currentPage();
                            $last    = $restoredDocuments->lastPage();
                            $window  = 2;
                            $start   = max(1, $current - $window);
                            $end     = min($last, $current + $window);
                            $query   = $restoredDocuments->appends(request()->query());
                        @endphp
                        <ul class="dt-pagination">
                            <!-- Prev -->
                            <li class="page-item {{ $restoredDocuments->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link"
                                   href="{{ !$restoredDocuments->onFirstPage() ? $query->previousPageUrl() : '#' }}">‹</a>
                            </li>

                            @if($start > 1)
                                <li class="page-item">
                                    <a class="page-link" href="{{ $query->url(1) }}">1</a>
                                </li>
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

                            <!-- Next -->
                            <li class="page-item {{ !$restoredDocuments->hasMorePages() ? 'disabled' : '' }}">
                                <a class="page-link"
                                   href="{{ $restoredDocuments->hasMorePages() ? $query->nextPageUrl() : '#' }}">›</a>
                            </li>
                        </ul>
                        @endif

                    </div>
                    @endif

                </div>
            </div>

        </div>
    </div>
</div>

@endsection

@section('page-script')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {

    // Archive confirmation
    $('.archive-form').on('submit', function (e) {
        e.preventDefault();
        const form = this;
        const code = $(this).closest('tr').find('.badge.bg-label-primary').text().trim();

        Swal.fire({
            title: 'Archive Document?',
            text: `Move "${code}" back to archive?`,
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

    // Auto-close alerts after 5s
    document.querySelectorAll('.alert').forEach(function (alert) {
        setTimeout(() => new bootstrap.Alert(alert).close(), 5000);
    });

    // Live search/filter functionality
    const searchInput = document.getElementById('liveSearchInput');
    const tableRows = document.querySelectorAll('.dt-table tbody tr');
    
    if (searchInput && tableRows.length > 0) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            let visibleCount = 0;
            
            tableRows.forEach(function(row) {
                // Get all text content from the row
                const trackingCode = row.querySelector('.badge.bg-label-primary')?.textContent.toLowerCase() || '';
                const docType = row.cells[1]?.textContent.toLowerCase() || '';
                const purpose = row.cells[2]?.textContent.toLowerCase() || '';
                const fileName = row.cells[3]?.textContent.toLowerCase() || '';
                const restoredAt = row.cells[4]?.textContent.toLowerCase() || '';
                
                // Check if search term matches any column
                const matchFound = trackingCode.includes(searchTerm) ||
                                 docType.includes(searchTerm) ||
                                 purpose.includes(searchTerm) ||
                                 fileName.includes(searchTerm) ||
                                 restoredAt.includes(searchTerm);
                
                // Show or hide row
                if (matchFound) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show "no results" message if no rows visible
            const noResultsRow = document.getElementById('noResultsRow');
            if (visibleCount === 0 && searchTerm !== '') {
                if (!noResultsRow) {
                    const tbody = document.querySelector('.dt-table tbody');
                    const newRow = document.createElement('tr');
                    newRow.id = 'noResultsRow';
                    newRow.innerHTML = '<td colspan="7" class="text-center text-muted py-4">No documents found matching your search.</td>';
                    tbody.appendChild(newRow);
                }
            } else if (noResultsRow) {
                noResultsRow.remove();
            }
        });
    }
});
</script>
@endsection