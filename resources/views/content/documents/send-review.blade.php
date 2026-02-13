@extends('layouts.contentNavbarLayout')

@section('title', 'Review and Send Document')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-md-12">
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Review and Send Document</h5>
                </div>
            </div>

            <div class="row">
                <!-- Left Column: Document Review -->
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

                            <div class="mt-4">
                                <a href="{{ route('documents.send') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-edit me-1"></i> Edit Document
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Send Options -->
                <div class="col-md-5">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0 text-white"><i class="bx bx-send me-2"></i>Choose Send Option</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-4">Select how you want to send this document:</p>

                            <!-- Individual Send Option -->
                            <div class="card mb-3 border shadow-sm" style="cursor: pointer;" onclick="selectSendOption('individual')">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar">
                                                <span class="avatar-initial rounded bg-label-primary">
                                                    <i class="bx bx-user bx-md"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">Individual Send</h6>
                                            <small class="text-muted">Send to specific individuals or departments</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Group Send Option -->
                            <div class="card mb-3 border shadow-sm" style="cursor: pointer;" onclick="selectSendOption('group')">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <div class="avatar">
                                                <span class="avatar-initial rounded bg-label-success">
                                                    <i class="bx bx-group bx-md"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-1">Group Send</h6>
                                            <small class="text-muted">Send to predefined groups or multiple recipients</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden forms for submission -->
                            <form action="{{ route('documents.send-individual') }}" method="GET" id="individualSendForm">
                            </form>

                            <form action="{{ route('documents.send-group') }}" method="GET" id="groupSendForm">
                            </form>

                            <div class="mt-4 text-center">
                                <a href="{{ route('documents.send') }}" class="btn btn-outline-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function selectSendOption(option) {
        if (option === 'individual') {
            document.getElementById('individualSendForm').submit();
        } else if (option === 'group') {
            document.getElementById('groupSendForm').submit();
        }
    }
</script>

<style>
    .card:hover {
        transform: translateY(-2px);
        transition: all 0.3s ease;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15) !important;
    }

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
</style>
@endsection
