@extends('layouts.contentNavbarLayout')

@section('title', 'Forward Document')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="mb-4">
        <h4 class="fw-bold mb-2">Forward Document</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb-style1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard-analytics') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('documents.received') }}">Documents</a></li>
                <li class="breadcrumb-item active">Forward</li>
            </ol>
        </nav>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="bx bx-file me-2"></i>Document Details</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Tracking Code</small>
                            <span class="fw-semibold">{{ $document->tracking_code ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Document Type</small>
                            <span class="fw-semibold">{{ $document->documentType?->type_name ?? 'Document' }}</span>
                        </div>
                        <div class="col-md-12">
                            <small class="text-muted d-block">Purpose</small>
                            <span>{{ $document->purpose }}</span>
                        </div>
                        <div class="col-md-12">
                            <small class="text-muted d-block">File</small>
                            <span>{{ $document->file_name ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-12">
                            <small class="text-muted d-block">Forward Source</small>
                            <span class="badge bg-label-secondary">{{ strtoupper($source) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0 text-white"><i class="bx bx-share-alt me-2"></i>Forward To</h6>
                </div>
                <div class="card-body">
                    <form id="forwardDocumentForm" method="POST" action="{{ route('documents.forward.store', ['documentId' => encryptId($document->document_id)]) }}">
                        @csrf

                        @if($baseRouteId)
                            <input type="hidden" name="base_route_id" value="{{ $baseRouteId }}">
                        @endif

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select User <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="userSearch" placeholder="Search by name or email" list="userList" autocomplete="off">
                            <input type="hidden" id="userId">
                            <datalist id="userList">
                                @foreach($users as $user)
                                    @php
                                        $display = optional($user->employee)
                                            ? ($user->employee->firstname . ' ' . $user->employee->lastname . ' (' . $user->email . ')')
                                            : (($user->name ?? ('User #' . $user->user_id)) . ' (' . $user->email . ')');
                                    @endphp
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
                            @error('user_ids')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Priority <span class="text-danger">*</span></label>
                            <select class="form-select" name="priority" id="priority" required>
                                <option value="low">Low</option>
                                <option value="normal" selected>Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea class="form-control" name="notes" rows="2" maxlength="500" placeholder="Optional forwarding notes"></textarea>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-share-alt me-1"></i> Forward Document
                            </button>
                            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const userSearch = document.getElementById('userSearch');
    const userId = document.getElementById('userId');
    const userList = document.getElementById('userList');
    const addRecipientBtn = document.getElementById('addRecipientBtn');
    const recipientList = document.getElementById('recipientList');
    const recipientInputs = document.getElementById('recipientInputs');
    const forwardForm = document.getElementById('forwardDocumentForm');
    const selectedRecipients = [];

    function renderRecipients() {
        recipientList.innerHTML = '';
        recipientInputs.innerHTML = '';

        selectedRecipients.forEach(function (recipient) {
            const chip = document.createElement('span');
            chip.className = 'badge bg-label-primary me-1 mb-1';
            chip.innerHTML = recipient.label +
                ' <button type="button" class="btn btn-sm btn-link text-danger ms-1 p-0 remove-recipient" data-id="' + recipient.id + '">x</button>';
            recipientList.appendChild(chip);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'user_ids[]';
            input.value = recipient.id;
            recipientInputs.appendChild(input);
        });
    }

    userSearch.addEventListener('input', function () {
        const inputValue = userSearch.value;
        const options = userList.querySelectorAll('option');
        let matchedId = '';

        options.forEach(function (option) {
            if (option.value === inputValue) {
                matchedId = option.dataset.id || '';
            }
        });

        userId.value = matchedId;
    });

    addRecipientBtn.addEventListener('click', function () {
        const id = userId.value;
        const label = userSearch.value.trim();

        if (!id) {
            Swal.fire({ icon: 'warning', title: 'Invalid recipient', text: 'Select a valid user from the list.' });
            return;
        }

        if (selectedRecipients.some(function (r) { return r.id === id; })) {
            Swal.fire({ icon: 'info', title: 'Already selected', text: 'This recipient is already added.' });
            return;
        }

        if (selectedRecipients.length >= 5) {
            Swal.fire({ icon: 'warning', title: 'Limit reached', text: 'You can forward to up to 5 recipients only.' });
            return;
        }

        selectedRecipients.push({ id: id, label: label });
        renderRecipients();
        userSearch.value = '';
        userId.value = '';
    });

    recipientList.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-recipient');
        if (!button) return;

        const id = button.getAttribute('data-id');
        const index = selectedRecipients.findIndex(function (r) { return r.id === id; });
        if (index >= 0) {
            selectedRecipients.splice(index, 1);
            renderRecipients();
        }
    });

    forwardForm.addEventListener('submit', function (event) {
        if (!selectedRecipients.length) {
            event.preventDefault();
            Swal.fire({ icon: 'warning', title: 'No recipients', text: 'Please add at least one recipient.' });
        }
    });
});
</script>
@endsection
