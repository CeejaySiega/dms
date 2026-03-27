@extends('layouts.contentNavbarLayout')

@section('title', 'Document Sent - Receipt')

@section('content')
@include('content.documents.styles.send-receipt-style')

    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Success Header -->
            <div class="card mb-4 border-success">
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <i class="bx bx-check-circle text-success" style="font-size: 80px;"></i>
                    </div>
                    <h3 class="text-success mb-2">Document Sent Successfully!</h3>
                    <p class="text-muted mb-0">Your document has been successfully sent to the recipient.</p>
                </div>
            </div>

            <!-- Receipt Details -->
            <div class="card">
                <div class="card-header bg-primary">
                    <h5 class="mb-0 text-white">
                        <i class="bx bx-receipt me-2"></i>Document Send Receipt
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Document Information -->
                    <div class="mb-4">
                        <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">
                            <i class="bx bx-file me-1"></i> Document Information
                        </h6>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="fw-semibold text-muted">Tracking Number:</label>
                            </div>
                            <div class="col-md-8">
                                <span class="badge bg-label-primary fs-6">{{ $document->tracking_code }}</span>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="fw-semibold text-muted">Document Type:</label>
                            </div>
                            <div class="col-md-8">
                                <span>{{ $document->documentType->type_name ?? 'N/A' }}</span>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="fw-semibold text-muted">Purpose:</label>
                            </div>
                            <div class="col-md-8">
                                <span>{{ $document->purpose }}</span>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="fw-semibold text-muted">File Name:</label>
                            </div>
                            <div class="col-md-8">
                                <i class="bx bx-file me-1"></i>{{ $document->file_name }}
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="fw-semibold text-muted">Date Sent:</label>
                            </div>
                            <div class="col-md-8">
                                <i class="bx bx-calendar me-1"></i>{{ $document->created_at->format('F d, Y h:i A') }}
                            </div>
                        </div>
                    </div>

                    <!-- Sender Information -->
                    <div class="mb-4">
                        <h6 class="text-primary fw-bold border-bottom pb-2 mb-3">
                            <i class="bx bx-user me-1"></i> Sent By
                        </h6>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="fw-semibold text-muted">Name:</label>
                            </div>
                            <div class="col-md-8">
                                @if($document->user->employee)
                                    {{ $document->user->employee->firstname }} {{ $document->user->employee->lastname }}
                                @else
                                    {{ $document->user->name }}
                                @endif
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="fw-semibold text-muted">Email:</label>
                            </div>
                            <div class="col-md-8">
                                {{ $document->user->email }}
                            </div>
                        </div>
                    </div>

                    <!-- Recipient Information -->
                    <div class="mb-4">
                        <h6 class="text-success fw-bold border-bottom pb-2 mb-3">
                            <i class="bx bx-user-check me-1"></i> Recipient Details
                        </h6>

                        @php
                            $receivedCount = $recipients->where('action', 'receive')->count();
                            $pendingCount = $recipients->whereNull('action')->count() + $recipients->where('action', 'pending')->count();
                            $rejectedCount = $recipients->where('action', 'rejected')->count();
                        @endphp
                        <div class="alert alert-info mb-3">
                            <strong>Summary:</strong>
                            {{ $receivedCount }} received,
                            {{ $pendingCount }} pending,
                            {{ $rejectedCount }} rejected
                        </div>
                        
                        @foreach($recipients as $recipient)
                        <div class="alert alert-light border mb-2">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="fw-semibold text-muted">Recipient:</label>
                                </div>
                                <div class="col-md-8">
                                    @if($recipient->user->employee)
                                        {{ $recipient->user->employee->firstname }} {{ $recipient->user->employee->lastname }}
                                    @else
                                        {{ $recipient->user->name }}
                                    @endif
                                    <span class="text-muted">({{ $recipient->user->email }})</span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-4">
                                    <label class="fw-semibold text-muted">Role:</label>
                                </div>
                                <div class="col-md-8">
                                    <span class="badge bg-info">{{ ucfirst($recipient->role) }}</span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-4">
                                    <label class="fw-semibold text-muted">Sent At:</label>
                                </div>
                                <div class="col-md-8">
                                    {{ \Carbon\Carbon::parse($recipient->sent_at)->format('F d, Y h:i A') }}
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-4">
                                    <label class="fw-semibold text-muted">Receive Status:</label>
                                </div>
                                <div class="col-md-8">
                                    @php
                                        $status = $recipient->action ?: 'pending';
                                        $statusClass = match($status) {
                                            'receive' => 'bg-success',
                                            'rejected' => 'bg-danger',
                                            default => 'bg-warning'
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ ucfirst($status) }}</span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-4">
                                    <label class="fw-semibold text-muted">Received At:</label>
                                </div>
                                <div class="col-md-8">
                                    @if($recipient->receive_at)
                                        {{ $recipient->receive_at->format('F d, Y h:i A') }}
                                    @else
                                        <span class="text-muted">Not yet received</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Routing Information -->
                    @if($route)
                    <div class="mb-4">
                        <h6 class="text-info fw-bold border-bottom pb-2 mb-3">
                            <i class="bx bx-git-branch me-1"></i> Routing Information
                        </h6>

                        @php
                            $receivedCount = $recipients->where('action', 'receive')->count();
                            $rejectedCount = $recipients->where('action', 'rejected')->count();
                            $totalCount = $recipients->count();
                        @endphp
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="fw-semibold text-muted">Status:</label>
                            </div>
                            <div class="col-md-8">
                                <span class="badge bg-warning">In Progress</span>
                                <span class="ms-2 text-muted">
                                    {{ $receivedCount }}/{{ $totalCount }} received,
                                    {{ $rejectedCount }} rejected
                                </span>
                            </div>
                        </div>
                    </div>
                    @endif

                    @php
                        // Use Storage::url to generate the correct public URL for the file
                        $fileUrl = urlencode(Storage::url($document->file_path));
                    @endphp
                    <div class="d-flex justify-content-center gap-2 mt-4 pt-4 border-top">
                       <a href="https://docs.google.com/gview?url={{ urlencode($fileUrl) }}&embedded=true" target="_blank" class="btn btn-primary">
                            <i class="bx bx-show me-1"></i> View in Google Docs
                        </a>
                        <a href="{{ route('documents.sent') }}" class="btn btn-success">
                            <i class="bx bx-list-ul me-1"></i> View Sent Documents
                        </a>
                        <a href="{{ route('documents.send') }}" class="btn btn-outline-primary">
                            <i class="bx bx-plus me-1"></i> Send Another Document
                        </a>
                    </div>

                    <!-- Print Option -->
                
                </div>
            </div>
        </div>
        </div>
    </div>
@endsection
