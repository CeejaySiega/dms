@extends('layouts.contentNavbarLayout')

@section('title', 'Send to Group')

@section('content')
<style>
    @media (max-width: 768px) {
        .row {
            flex-direction: column;
        }
        .col-md-7, .col-md-5 {
            width: 100%;
            margin-bottom: 1rem;
        }
        .card {
            margin-bottom: 1rem;
        }
        .form-label {
            font-size: 0.875rem;
        }
        .form-control, .form-select {
            font-size: 0.875rem;
        }
    }
    @media (max-width: 576px) {
        h4, h5, h6 {
            font-size: 1.1rem;
        }
        .btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }
        .list-group-item {
            padding: 0.75rem 0.5rem;
            font-size: 0.85rem;
        }
    }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header with Breadcrumb -->
    <div class="mb-4">
        <h4 class="fw-bold mb-2">Document Management System</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb-style1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard-analytics') }}">Home</a>
                </li>
                <li class="breadcrumb-item active">Review Document</li>s
                <li class="breadcrumb-item active">Send to Group</li>
            </ol>
        </nav>
    </div>

    {{-- <div class="row">
        <div class="col-md-12">
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bx bx-group me-2"></i>Send to Group</h5>
                        <a href="{{ route('documents.show-review') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div> --}}

            <div class="row">
                <!-- Left Column: Document Preview -->
                <div class="col-md-7">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0"><i class="bx bx-file me-2"></i>Document Details</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Tracking Number:</label>
                                <p class="form-control-plaintext">{{ session('document_data.tracking_code') }}</p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Document Type:</label>
                                <p class="form-control-plaintext">{{ session('document_data.documenttype_id') }}</p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Purpose (For):</label>
                                <div class="form-control-plaintext">
                                    @if(session('document_data.purpose'))
                                        <span class="badge bg-primary me-1 mb-1">{{ session('document_data.purpose') }}</span>
                                    @endif
                                    @if(session('document_data.purpose_others'))
                                        <span class="badge bg-info me-1 mb-1">Others: {{ session('document_data.purpose_others') }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Attached File:</label>
                                <p class="form-control-plaintext">
                                    <i class="bx bx-file me-1"></i>{{ session('document_data.file_name') }}
                                    <span class="text-muted">({{ number_format(session('document_data.file_size') / 1024, 2) }} KB)</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Group Selection -->
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0 text-white"><i class="bx bx-group me-2"></i>Select Group</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-4">Select the group you want to send to:</p>
                            <form id="sendGroupForm">
                                @csrf

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Select Group <span class="text-danger">*</span></label>
                                    <select class="form-select" id="groupSelect" name="group_id" required>
                                        <option value="">-- Choose a Group --</option>
                                        @foreach($groups as $group)
                                            <option value="{{ encryptId($group->group_id) }}" data-members="{{ $group->members_count }}">
                                                {{ $group->position }} ({{ $group->campus }}) - {{ $group->members_count }} member(s)
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-2" id="groupInfo"></small>
                                </div>

                                <!-- Priority -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Priority <span class="text-danger">*</span></label>
                                    <select class="form-select" id="priority" name="priority" required>
                                        <option value="">-- Select Priority --</option>
                                        <option value="normal" selected>Normal</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3 d-none" id="dueDateWrap">
                                    <label class="form-label fw-semibold">Due Date</label>
                                    <input type="date" class="form-control" id="dueDate" name="due_date" min="{{ now()->toDateString() }}">
                                    <small class="text-muted">Due date is required for urgent documents.</small>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-grid gap-2 mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bx bx-send me-1"></i> Send Document
                                    </button>
                                    <a href="{{ route('documents.show-review') }}" class="btn btn-outline-secondary">
                                        <i class="bx bx-x me-1"></i> Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
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
    const $priority = $('#priority');
    const $dueDateWrap = $('#dueDateWrap');
    const $dueDate = $('#dueDate');

    const syncDueDateVisibility = () => {
        const urgent = $priority.val() === 'urgent';
        $dueDateWrap.toggleClass('d-none', !urgent);
        $dueDate.prop('required', urgent);
        if (!urgent) {
            $dueDate.val('');
        }
    };

    $priority.on('change', syncDueDateVisibility);
    syncDueDateVisibility();

    $('#groupSelect').on('change', function() {
        const members = $(this).find(':selected').data('members');
        if (members !== undefined) {
            $('#groupInfo').text(`Selected group has ${members} member(s).`);
        } else {
            $('#groupInfo').text('');
        }
    });

    $('#sendGroupForm').on('submit', function(e) {
        e.preventDefault();

        const groupId = $('#groupSelect').val();
        const priority = $('#priority').val();
        const dueDate = priority === 'urgent' ? $dueDate.val() : '';

        if (!groupId) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Selection',
                text: 'Please select a group to send the document to.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        if (priority === 'urgent' && !dueDate) {
            Swal.fire({
                icon: 'warning',
                title: 'Due Date Required',
                text: 'Please set a due date for urgent documents.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        Swal.fire({
            title: 'Confirm Document Send',
            text: 'Send this document to the selected group?',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Send Now',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading alert
                Swal.fire({
                    title: 'Sending Document...',
                    html: '<p class="text-muted">Please wait while we send your document to the group.</p>',
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: (toast) => {
                        Swal.showLoading();
                    }
                });

                const formData = {
                    group_id: groupId,
                    priority: priority,
                    due_date: dueDate,
                    _token: '{{ csrf_token() }}'
                };

                $.ajax({
                    url: '{{ route("documents.store-group") }}',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message || 'Document sent successfully!',
                            confirmButtonColor: '#3085d6',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = response.redirect_url;
                        });
                    },
                    error: function(xhr) {
                        const errorMessage = xhr.responseJSON?.message || 'An error occurred while sending the document.';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMessage,
                            confirmButtonColor: '#d33'
                        });
                    }
                });
            }
        });
    });
});
</script>
@endsection
