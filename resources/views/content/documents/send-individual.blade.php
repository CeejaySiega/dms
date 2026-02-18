@extends('layouts.contentNavbarLayout')

@section('title', 'Send to Individual')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header with Breadcrumb -->
    <div class="mb-4">
        <h4 class="fw-bold mb-2">Document Management System</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb-style1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard-analytics') }}">Home</a>
                </li>
                <li class="breadcrumb-item active">Review Document</li>
                <li class="breadcrumb-item active">Send to Individual</li>
            </ol>
        </nav>
    </div>

    {{-- <div class="row">
        <div class="col-md-12">
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bx bx-user me-2"></i>Send to Individual</h5>
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

                <!-- Right Column: Recipient Selection -->
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0 text-white"><i class="bx bx-user-plus me-2"></i>Select Recipient</h6>
                        </div>
                        <div class="card-body">
                            <form id="sendIndividualForm">
                                @csrf

                                <!-- User Selection -->
                                <div id="userSelectionDiv" class="mb-3">
                                    <label class="form-label fw-semibold">Select User <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="userSearch" placeholder="Search by name or email" list="userList" autocomplete="off">
                                    <input type="hidden" id="userId" name="user_id" required>
                                    <datalist id="userList">
                                        @foreach($users as $user)
                                            @if($user->employee)
                                                @php $display = $user->employee->firstname . ' ' . $user->employee->lastname . ' (' . $user->email . ')'; @endphp
                                            @else
                                                @php $display = $user->name . ' (' . $user->email . ')'; @endphp
                                            @endif
                                            <option value="{{ $display }}" data-id="{{ encryptId($user->user_id) }}"></option>
                                        @endforeach
                                    </datalist>
                                    <div class="d-flex align-items-center gap-2 mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="addRecipientBtn">
                                            <i class="bx bx-user-plus me-1"></i> Add Recipient
                                        </button>
                                        <small class="text-muted">Max 5 recipients</small>
                                    </div>
                                    <div id="recipientList" class="mt-3"></div>
                                    <div id="recipientInputs"></div>
                                    <small class="text-muted d-block mt-2">
                                        <i class="bx bx-info-circle"></i> Select the user you want to send to
                                    </small>
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

@section('page-script')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    const $userSearch = $('#userSearch');
    const $userId = $('#userId');
    const $recipientList = $('#recipientList');
    const $recipientInputs = $('#recipientInputs');
    const $addRecipientBtn = $('#addRecipientBtn');
    const recipients = [];

    $userSearch.on('input', function() {
        const inputVal = $(this).val();
        const match = $('#userList option').filter(function() {
            return $(this).val() === inputVal;
        }).first();

        if (match.length) {
            $userId.val(match.data('id'));
        } else {
            $userId.val('');
        }
    });

    const renderRecipients = () => {
        $recipientList.empty();
        $recipientInputs.empty();

        recipients.forEach((recipient) => {
            const badge = $(
                `<span class="badge bg-label-primary me-1 mb-1">
                    ${recipient.label}
                    <button type="button" class="btn btn-sm btn-link text-danger ms-1 p-0 remove-recipient" data-id="${recipient.id}">
                        <i class="bx bx-x"></i>
                    </button>
                </span>`
            );
            $recipientList.append(badge);
            $recipientInputs.append(`<input type="hidden" name="user_ids[]" value="${recipient.id}">`);
        });
    };

    $addRecipientBtn.on('click', function() {
        const id = $userId.val();
        const label = $userSearch.val().trim();

        if (!id) {
            Swal.fire({
                icon: 'warning',
                title: 'Invalid Recipient',
                text: 'Please select a valid user from the list.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        if (recipients.find((r) => r.id === id)) {
            Swal.fire({
                icon: 'info',
                title: 'Already Added',
                text: 'This recipient is already selected.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        if (recipients.length >= 5) {
            Swal.fire({
                icon: 'warning',
                title: 'Limit Reached',
                text: 'You can select up to 5 recipients only.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        recipients.push({ id, label });
        renderRecipients();

        $userSearch.val('');
        $userId.val('');
    });

    $recipientList.on('click', '.remove-recipient', function() {
        const id = $(this).data('id').toString();
        const index = recipients.findIndex((r) => r.id.toString() === id);
        if (index !== -1) {
            recipients.splice(index, 1);
            renderRecipients();
        }
    });

    // Handle form submission
    $('#sendIndividualForm').on('submit', function(e) {
        e.preventDefault();

        const userIds = recipients.map((r) => r.id);
        const priority = $('#priority').val();
        const notes = $('#notes').val();
        const dueDate = $('#dueDate').val();

        // Validate selection
        if (!userIds.length) {
            Swal.fire({
                icon: 'warning',
                title: 'Missing Selection',
                text: 'Please select at least one recipient.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        // Get selected user name
        const selectedUserOption = recipients.map((r) => r.label).join(', ');

        // Get document details from the page
        const trackingCode = '{{ session("document_data.tracking_code") }}';
        const documentType = '{{ session("document_data.documenttype_id") }}';
        const fileName = '{{ session("document_data.file_name") }}';
        const fileSize = '{{ number_format(session("document_data.file_size") / 1024, 2) }}';
        const purpose = @json(session('document_data.purpose') ?? '');
        const purposeOthers = '{{ session("document_data.purpose_others") }}';

        // Build confirmation HTML
        let confirmationHTML = `
            <div class="text-start">
                <div class="mb-3">
                    <h6 class="text-primary fw-bold mb-2"><i class="bx bx-file"></i> Document Information</h6>
                    <div class="ps-3 border-start border-primary">
                        <p class="mb-2"><strong>Tracking Code:</strong> <br/><span class="badge bg-light text-dark">${trackingCode}</span></p>
                        <p class="mb-2"><strong>Document Type:</strong> <br/>${documentType}</p>
                        <p class="mb-2"><strong>File Name:</strong> <br/>${fileName}</p>
                        <p class="mb-2"><strong>File Size:</strong> <br/>${fileSize} KB</p>
                        <p class="mb-0"><strong>Purpose:</strong> <br/>`;
        
        if (purpose) {
            confirmationHTML += `<span class="badge bg-primary me-1">${purpose}</span>`;
        }
        
        if (purposeOthers) {
            confirmationHTML += `<span class="badge bg-info">${purposeOthers}</span>`;
        }
        
        confirmationHTML += `
                        </p>
                    </div>
                </div>

                <div class="mb-3">
                    <h6 class="text-success fw-bold mb-2"><i class="bx bx-send"></i> Send Details</h6>
                    <div class="ps-3 border-start border-success">
                        <p class="mb-2"><strong>Recipient:</strong> <br/>${selectedUserOption}</p>
                        <p class="mb-2"><strong>Priority:</strong> <br/>
                            <span class="badge ${getPriorityBadgeClass(priority)}">${priority.charAt(0).toUpperCase() + priority.slice(1)}</span>
                        </p>
                        ${notes ? `<p class="mb-0"><strong>Notes:</strong> <br/><em>${notes}</em></p>` : ''}
                    </div>
                </div>
            </div>
        `;

        // Show confirmation dialog
        Swal.fire({
            title: 'Confirm Document Send',
            html: confirmationHTML,
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="bx bx-check"></i> Send Now',
            cancelButtonText: '<i class="bx bx-x"></i> Cancel',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Proceed with sending
                const formData = {
                    user_ids: userIds,
                    notes: notes,
                    priority: priority,
                    due_date: dueDate,
                    _token: '{{ csrf_token() }}'
                };

                $.ajax({
                    url: '{{ route("documents.store") }}',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Document sent successfully!',
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

    // Helper function to get priority badge color
    function getPriorityBadgeClass(priority) {
        const classes = {
            'low': 'bg-success',
            'normal': 'bg-info',
            'high': 'bg-warning text-dark',
            'urgent': 'bg-danger'
        };
        return classes[priority] || 'bg-secondary';
    }
});
</script>
@endsection

<style>
    .form-label.fw-semibold {
        font-weight: 600;
        color: #495057;
    }

    .form-control-plaintext {
        padding-top: 0.375rem;
        padding-bottom: 0.375rem;
        margin-bottom: 0;
        font-size: inherit;
        line-height: 1.5;
        color: #212529;
    }

    .btn-group {
        gap: 0.5rem;
    }

    .btn-group .btn {
        border-radius: 0.375rem;
    }

    .btn-group .btn:not(:last-child) {
        margin-right: 0;
    }
</style>
@endsection
