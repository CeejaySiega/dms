@extends('layouts.contentNavbarLayout')

@section('title', 'Inbox')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-md-12">
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bx bxs-inbox me-2"></i>Inbox
                        @if(($inboxCount ?? 0) > 0)
                            <span class="badge bg-warning text-dark ms-2">{{ $inboxCount }}</span>
                        @endif
                    </h5>
                    <a href="{{ route('documents.send') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i> Send New Document
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-warning" role="alert">
                    <i class="bx bx-bell me-1"></i>{{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-warning" role="alert">
                    <i class="bx bx-bell me-1"></i>{{ session('error') }}
                </div>
            @endif

            <!-- Filter Tabs -->
            <div class="card mb-4">
                <div class="card-body">
                    <ul class="nav nav-pills mb-0" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a href="{{ route('documents.all') }}" class="nav-link">
                                <i class="bx bx-list-ul me-1"></i> All Documents
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="{{ route('documents.incoming') }}" class="nav-link active">
                                <i class="bx bxs-inbox me-1"></i> Inbox
                                @if(($inboxCount ?? 0) > 0)
                                    <span class="badge bg-warning text-dark ms-1">{{ $inboxCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="{{ route('documents.received') }}" class="nav-link">
                                <i class="bx bxs-download me-1"></i> Received
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="{{ route('documents.sent') }}" class="nav-link">
                                <i class="bx bx-send me-1"></i> Sent
                            </a>
                        </li>
                    </ul>
                    
                </div>
            </div>

            <!-- Inbox List -->
            <div class="card">
                <div class="card-body">
                    @if($inbox->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tracking Code</th>
                                    <th>Sender</th>
                                    <th>Document Type</th>
                                    <th>Purpose</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Sent Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inbox as $recipient)
                                    @php
                                        $document = optional($recipient->route)->document;
                                    @endphp
                                    @if($document)
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary">{{ $document->tracking_code }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $sender = optional($document->user)->employee;
                                            @endphp
                                            @if($sender)
                                                <i class="bx bx-user-circle me-1"></i>{{ $sender->firstname }} {{ $sender->lastname }}
                                            @else
                                                <i class="bx bx-user-circle me-1"></i>{{ optional($document->user)->name ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td>{{ $document->documentType->type_name ?? 'N/A' }}</td>
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $document->purpose }}">
                                                {{ $document->purpose }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $priorityValue = optional($recipient->route)->priority ?? 'normal';
                                                $priorityClass = match($priorityValue) {
                                                    'urgent' => 'bg-danger',
                                                    'high' => 'bg-warning',
                                                    'low' => 'bg-secondary',
                                                    default => 'bg-primary'
                                                };
                                            @endphp
                                            <span class="badge {{ $priorityClass }}">{{ ucfirst($priorityValue) }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $statusValue = $recipient->action ?: 'pending';
                                                $statusValue = $statusValue === 'received' ? 'receive' : $statusValue;
                                                $statusClass = match($statusValue) {
                                                    'pending' => 'bg-warning',
                                                    'approved' => 'bg-success',
                                                    'receive' => 'bg-info',
                                                    'rejected' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                };
                                            @endphp
                                            <span class="badge {{ $statusClass }}">{{ ucfirst($statusValue) }}</span>
                                        </td>
                                        <td>
                                            <small>{{ optional($recipient->sent_at)->format('M d, Y h:i A') }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                @php
                                                    $action = $recipient->action;
                                                    $isFinal = in_array($action, ['receive', 'approved', 'rejected']);
                                                @endphp
                                                <form action="{{ route('documents.receive', encryptId($document->document_id)) }}" method="POST" class="d-inline receive-form">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-primary"
                                                            title="Receive"
                                                            @disabled($isFinal)>
                                                        <i class="bx bx-receipt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-4">
                        {{ $inbox->links() }}
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="bx bxs-inbox" style="font-size: 64px; color: #ccc;"></i>
                        <p class="text-muted mt-3">No incoming documents found.</p>
                    </div>
                    @endif
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
    $('.receive-form').on('submit', function(e) {
        e.preventDefault();
        const form = this;

        Swal.fire({
            title: 'Receive Document?',
            text: 'Mark this document as received?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, receive it',
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
@endsection
