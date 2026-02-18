@extends('layouts.contentNavbarLayout')

@section('title', 'Archived Documents')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-md-12">
            <!-- Header -->   
               <div class="mb-4">
                    <h4 class="fw-bold mb-2"><i class="bx bx-archive me-2"></i>Archived Documents</h4>
                    <nav aria-label="breadcrumb">
                         <ol class="breadcrumb breadcrumb-style1">
                         <li class="breadcrumb-item">
                            <a href="{{ route('dashboard-analytics') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item active">Archived Documents </li>
                        </ol>
                    </nav>
                </div>
            <div class="card mb-4">
                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                    <div>
                        {{-- <h5 class="mb-1"><i class="bx bx-archive me-2"></i>Archive</h5> --}}
                        <p class="text-muted mb-0">Review archived files and restore them when needed.</p>
                    </div>
                    <div class="d-flex align-items-center gap-4 mt-3 mt-md-0">
                        <div class="text-center">
                            <div class="text-muted small">Total Archived</div>
                            <div class="h5 mb-0">{{ method_exists($documents, 'total') ? $documents->total() : $documents->count() }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-muted small">Showing</div>
                            <div class="h5 mb-0">{{ $documents->count() }}</div>
                        </div>
                        {{-- <a href="{{ route('documents.send') }}" class="btn btn-primary">
                            <i class="bx bx-plus me-1"></i> Send New Document
                        </a> --}}
                    </div>
                </div>
            </div>
{{-- 
            @if(session('success'))
                <div class="alert alert-success" role="alert">
                    <i class="bx bx-check-circle me-1"></i>{{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger" role="alert">
                    <i class="bx bx-error-circle me-1"></i>{{ session('error') }}
                </div>
            @endif --}}

            <!-- Archived List -->
            <div class="card">
                <div class="card-body">
                    @if($documents->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Tracking Code</th>
                                    <th>Document Type</th>
                                    <th>Purpose</th>
                                    <th>File Name</th>
                                    <th>Archived At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($documents as $archive)
                                <tr>
                                    <td>
                                        <span class="badge bg-primary">{{ $archive->document->tracking_code }}</span>
                                    </td>
                                    <td>{{ $archive->document->documentType->type_name ?? 'N/A' }}</td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $archive->document->purpose }}">
                                            {{ $archive->document->purpose }}
                                        </span>
                                    </td>
                                    <td>
                                        <i class="bx bx-file me-1"></i>{{ $archive->file_name ?? $archive->document->file_name }}
                                    </td>
                                    <td>
                                        <small>{{ $archive->archive_at->format('M d, Y h:i A') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <form action="{{ route('documents.restore', $archive->archive_id) }}"
                                                  method="POST"
                                                  class="d-inline restore-form">
                                                @csrf
                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-primary"
                                                    id="restore-document-{{ $archive->archive_id }}"
                                                    name="restore"
                                                    title="Restore"
                                                    aria-label="Restore document">
                                                    <i class="bx bx-undo"></i>
                                                </button>
                                            </form>
                                            <a href="{{ route('documents.download', encryptId($archive->document_id)) }}"
                                               class="btn btn-sm btn-outline-success"
                                               title="Download">
                                                <i class="bx bx-download"></i>
                                            </a>
                                            <form action="{{ route('documents.permanent-delete', $archive->archive_id) }}"
                                                  method="POST"
                                                  class="d-inline delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                    id="delete-document-{{ $archive->archive_id }}"
                                                    name="delete"
                                                    title="Permanently Delete"
                                                    aria-label="Permanently delete archive">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $documents->links() }}
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="bx bx-archive" style="font-size: 64px; color: #ccc;"></i>
                        <p class="text-muted mt-3">No archived documents found.</p>
                        <a href="{{ route('documents.send') }}" class="btn btn-primary mt-2">
                            <i class="bx bx-plus me-1"></i> Send Your First Document
                        </a>
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
$(document).ready(function() {
    $('.restore-form').on('submit', function(e) {
        e.preventDefault();
        const form = this;

        Swal.fire({
            title: 'Restore Document?',
            text: 'This will move the document back to your active list.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, restore it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    $('.delete-form').on('submit', function(e) {
        e.preventDefault();
        const form = this;

        Swal.fire({
            title: 'Permanently Delete?',
            text: 'This action cannot be undone. The archived record will be permanently deleted.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete permanently',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>
@endsection
