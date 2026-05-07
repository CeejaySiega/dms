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
                                
                                <option value="normal" selected>Normal</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>

                        <div class="mb-3 d-none" id="dueDateWrap">
                            <label class="form-label fw-semibold">Due Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="due_date" id="dueDate" min="{{ now()->toDateString() }}" value="{{ old('due_date') }}">
                            <small class="text-muted">Due date is required for urgent forwards.</small>
                        </div>

{{-- 
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea class="form-control" name="notes" rows="2" maxlength="500" placeholder="Optional forwarding notes"></textarea>
                        </div> --}}

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

@include('content.documents.scripts.forward-document-script')
