@extends('layouts.contentNavbarLayout')

@section('title', 'Send Document')

@section('content')
<style>
    @media (max-width: 768px) {
        .form-label {
            font-size: 0.875rem;
        }
        .form-control, .form-select {
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
        }
        .btn-group {
            flex-wrap: wrap;
            gap: 0.25rem;
        }
        .card {
            margin-bottom: 1rem;
        }
    }
    @media (max-width: 576px) {
        h4 {
            font-size: 1.25rem;
        }
        .form-check {
            margin-bottom: 0.5rem;
        }
        .btn {
            font-size: 0.85rem;
            padding: 0.4rem 0.75rem;
        }
    }
</style>
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header with Breadcrumb -->
    <div class="mb-4">
        <h4 class="fw-bold mb-2"><i class="bx bx-send me-2"></i>Send Document</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb-style1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard-analytics') }}">Home</a>
                </li>
                <li class="breadcrumb-item active">Send Document</li>
            </ol>
        </nav>
    </div>

    {{-- <div class="row">
        <div class="col-md-12">
           
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Send Document</h5>
                </div>
            </div> --}}

        
            <div class="card">
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6 class="mb-2">Please fix the following errors:</h6>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @php
                    
                        $campuses = getCampuses();
                        
                        
                        $userCampusId = auth()->user()->employee->campus;
                        
                        
                        $campusData = null;
                        $campusAbbr = null;
                        
                        if ($userCampusId) {
                            foreach ($campuses as $abbr => $campus) {
                                if ($campus['ID'] == $userCampusId) {
                                    $campusData = $campus;
                                    $campusAbbr = $abbr;
                                    break;
                                }
                            }
                        }
                        
                        if (!$campusData) {
                            foreach ($campuses as $abbr => $campus) {
                                $campusData = $campus;
                                $campusAbbr = $abbr;
                                break;
                            }
                        }
                        
                        $campusId = $campusData['ID'];
                        $allCampusesJson = json_encode($campuses);
                    @endphp

                    <form action="{{ route('documents.review') }}" method="POST" enctype="multipart/form-data" id="sendDocumentForm" data-campus-id="{{ $campusId }}" data-campus-abbr="{{ $campusAbbr }}" data-all-campuses="{{ htmlspecialchars($allCampusesJson) }}">
                        @csrf

                        <!-- Tracking Number -->
                        <div class="mb-3">
                            <label class="form-label" for="tracking_code">Tracking Number<span class="text-danger">*</span></label>
                            <input
                                type="text"
                                class="form-control @error('tracking_code') is-invalid @enderror"
                                id="tracking_code"
                                name="tracking_code"
                                value="{{ old('tracking_code', $trackingCode ?? '') }}"
                                readonly
                                required
                            />
                            {{-- <small class="form-text text-muted">
                                Please make sure to attach the correct tracking number to the actual document.
                            </small> --}}
                            @error('tracking_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Document Type -->
                        <div class="mb-3">
                            <label class="form-label" for="documenttype_id">Type<span class="text-danger">*</span></label>
                            <select class="form-select @error('documenttype_id') is-invalid @enderror" id="documenttype_id" name="documenttype_id" required>
                                <option value="">Select document type</option>
                                <option value="Memorandum" {{ old('documenttype_id') == 'Memorandum' ? 'selected' : '' }}>Memorandum</option>
                                <option value="Request Letter" {{ old('documenttype_id') == 'Request Letter' ? 'selected' : '' }}>Request Letter</option>
                                <option value="Office Order" {{ old('documenttype_id') == 'Office Order' ? 'selected' : '' }}>Office Order</option>
                                <option value="Endorsement" {{ old('documenttype_id') == 'Endorsement' ? 'selected' : '' }}>Endorsement</option>
                                <option value="Circular" {{ old('documenttype_id') == 'Circular' ? 'selected' : '' }}>Circular</option>
                                <option value="Report" {{ old('documenttype_id') == 'Report' ? 'selected' : '' }}>Report</option>
                                <option value="Communication Letter" {{ old('documenttype_id') == 'Communication Letter' ? 'selected' : '' }}>Communication Letter</option>
                                <option value="Travel Order" {{ old('documenttype_id') == 'Travel Order' ? 'selected' : '' }}>Travel Order</option>
                                <option value="Purchase Request" {{ old('documenttype_id') == 'Purchase Request' ? 'selected' : '' }}>Purchase Request</option>
                            </select>
                            @error('documenttype_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- For (Purpose) -->
                        <div class="mb-3">
                            <label class="form-label">For<span class="text-danger">*</span></label>
                            <div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input for-radio" id="for1" name="purpose" value="appropriate action" {{ old('purpose') == 'appropriate action' ? 'checked' : '' }} required>
                                    <label class="form-check-label" for="for1">appropriate action</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input for-radio" id="for2" name="purpose" value="coding/deposit/preparation of receipt" {{ old('purpose') == 'coding/deposit/preparation of receipt' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="for2">coding/deposit/preparation of receipt</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input for-radio" id="for3" name="purpose" value="comment/reaction/response" {{ old('purpose') == 'comment/reaction/response' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="for3">comment/reaction/response</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input for-radio" id="for4" name="purpose" value="compliance/implementation" {{ old('purpose') == 'compliance/implementation' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="for4">compliance/implementation</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input for-radio" id="for5" name="purpose" value="dissemination of information" {{ old('purpose') == 'dissemination of information' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="for5">dissemination of information</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input for-radio" id="for6" name="purpose" value="draft of reply" {{ old('purpose') == 'draft of reply' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="for6">draft of reply</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input for-radio" id="for7" name="purpose" value="endorsement/recommendation" {{ old('purpose') == 'endorsement/recommendation' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="for7">endorsement/recommendation</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input for-radio" id="for8" name="purpose" value="others" {{ old('purpose') == 'others' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="for8">others</label>
                                </div>
                            </div>
                            
                            <!-- Others Textbox (hidden by default) -->
                            <div id="othersTextboxContainer" style="display: none; margin-top: 10px;">
                                <input
                                    type="text"
                                    class="form-control @error('purpose_others') is-invalid @enderror"
                                    id="purpose_others"
                                    name="purpose_others"
                                    placeholder="Please specify other purpose"
                                    value="{{ old('purpose_others') }}"
                                />
                                @error('purpose_others')
                                    <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            @error('purpose')
                                <div class="invalid-feedback" style="display:block;">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- File Upload -->
                        <div class="mb-3">
                            <label class="form-label" for="file">Attach File <span class="text-danger">*</span></label>
                            <input
                                type="file"
                                class="form-control @error('file') is-invalid @enderror"
                                id="file"
                                name="file"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.png,.jpg,.jpeg"
                                required
                            />
                            <small class="form-text text-muted">
                                Accepted formats: PDF, DOC, DOCX, XLS, XLSX, TXT, PNG, JPG, JPEG (Max: 10MB)
                            </small>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Buttons -->
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary me-2" id="submitBtn">
                                <i class="bx bx-send me-1"></i> Send To
                            </button>
                            <a href="{{ route('dashboard-analytics') }}" class="btn btn-secondary">
                                <i class="bx bx-arrow-back me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('sendDocumentForm');
        const submitBtn = document.getElementById('submitBtn');
        const trackingCodeInput = document.getElementById('tracking_code');
        const othersRadio = document.getElementById('for8');
        const othersTextboxContainer = document.getElementById('othersTextboxContainer');
        const purposeOthersInput = document.getElementById('purpose_others');

        // Show/hide others textbox based on radio selection
        const purposeRadios = document.querySelectorAll('.for-radio');
        const toggleOthers = () => {
            if (othersRadio && othersRadio.checked) {
                othersTextboxContainer.style.display = 'block';
                purposeOthersInput.focus();
            } else {
                othersTextboxContainer.style.display = 'none';
                purposeOthersInput.value = '';
            }
        };

        purposeRadios.forEach((radio) => {
            radio.addEventListener('change', toggleOthers);
        });

        toggleOthers();

        if (trackingCodeInput && !trackingCodeInput.value) {
            trackingCodeInput.value = @json($trackingCode ?? '');
        }

        // Form validation and submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!form.checkValidity()) {
                e.stopPropagation();
                form.classList.add('was-validated');
                return;
            }

            Swal.fire({
                title: 'Continue to Review?'
                ,text: 'Proceed to review and send this document?'
                ,icon: 'question'
                ,showCancelButton: true
                ,confirmButtonColor: '#3085d6'
                ,cancelButtonColor: '#6c757d'
                ,confirmButtonText: 'Yes, continue'
                ,cancelButtonText: 'Cancel'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
                form.submit();
            });
        });

        // File input preview
        const fileInput = document.getElementById('file');
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const maxSize = 10 * 1024 * 1024; // 10MB
                    if (file.size > maxSize) {
                        Swal.fire({
                            icon: 'warning'
                            ,title: 'File Too Large'
                            ,text: 'File size exceeds the 10MB limit.'
                            ,confirmButtonColor: '#3085d6'
                        });
                        fileInput.value = '';
                    }
                }
            });
        }
    });
</script>

<style>
    .form-label {
        font-weight: 500;
        color: #495057;
    }

    .text-danger {
        color: #dc3545 !important;
    }

    .card {
        border: 1px solid #e0e0e0;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #e0e0e0;
    }
</style>
@endsection
