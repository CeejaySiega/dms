@extends('layouts.contentNavbarLayout')

@section('title', 'Restored Documents')

@section('content')

@include('content.documents.styles.restored-documents-style')

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
                                <input type="hidden" name="document_type" value="{{ request('document_type') }}">
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
                            <input type="hidden" name="document_type" value="{{ request('document_type') }}">
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
                                @php
                                    $doc = $document->document;
                                    $isSender = $doc && $doc->user_id === auth()->user()->user_id;
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge bg-label-primary">{{ $doc->tracking_code ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <i class="bx bx-file me-1 text-info"></i>
                                        {{ $doc->documentType->type_name ?? 'N/A' }}
                                    </td>
                                    <td>
                                        <span class="text-truncate d-inline-block"
                                              style="max-width:250px"
                                              title="{{ $doc->purpose ?? '' }}">
                                            {{ Str::limit($doc->purpose ?? '', 50) }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <i class="bx bx-file me-1"></i>{{ $document->file_name ?? 'N/A' }}
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            @if($document->archive_at)
                                                {{ $document->archive_at->format('M d, Y h:i A') }}
                                            @else
                                                <i>Not set</i>
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-success">
                                            <i class="bx bx-check-circle me-1"></i>Restored
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            @if($doc)
                                            <a href="{{ route('documents.download', encryptId($document->document_id)) }}"
                                                              class="btn btn-outline-success dt-action-btn"
                                               title="Download">
                                                <i class="bx bx-download"></i>
                                            </a>
                                            @if($isSender)
                                            <form action="{{ route('documents.archive', encryptId($document->document_id)) }}"
                                                  method="POST"
                                                  class="d-inline archive-form"
                                                  data-tracking="{{ $doc->tracking_code ?? 'Document' }}">
                                                @csrf
                                                <button type="submit"
                                                        class="btn btn-outline-secondary dt-action-btn"
                                                        title="Archive Again">
                                                    <i class="bx bx-archive"></i>
                                                </button>
                                            </form>
                                            @else
                                            <form action="{{ route('documents.archive-receiver', encryptId($document->document_id)) }}"
                                                  method="POST"
                                                  class="d-inline archive-form"
                                                  data-tracking="{{ $doc->tracking_code ?? 'Document' }}">
                                                @csrf
                                                <button type="submit"
                                                        class="btn btn-outline-secondary dt-action-btn"
                                                        title="Archive Again">
                                                    <i class="bx bx-archive"></i>
                                                </button>
                                            </form>
                                            @endif
                                            @endif
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

@include('content.documents.scripts.restored-documents-script')