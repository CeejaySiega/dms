@extends('layouts.contentNavbarLayout')

@section('title', 'Received Documents')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-md-12">
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bx bx-receipt me-2"></i>Received Documents
                        @if(($inboxCount ?? 0) > 0)
                            <span class="badge bg-danger ms-2">{{ $inboxCount }}</span>
                        @endif
                    </h5>
                    <a href="{{ route('documents.send') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i> Send New Document
                    </a>
                </div>
            </div>

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
                            <a href="{{ route('documents.incoming') }}" class="nav-link">
                                <i class="bx bxs-inbox me-1"></i> Inbox
                                @if(($inboxCount ?? 0) > 0)
                                    <span class="badge bg-warning text-dark ms-1">{{ $inboxCount }}</span>
                                @endif
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="{{ route('documents.received') }}" class="nav-link active">
                                <i class="bx bxs-download me-1"></i> Received
                            </a>
                        </li>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a href="{{ route('documents.sent') }}" class="nav-link">
                                <i class="bx bx-send me-1"></i> Sent
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Received List -->
            <div class="card">
                <div class="card-body">
                    @if($received->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tracking Code</th>
                                    <th>Sender</th>
                                    <th>Document Type</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Sent Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($received as $receivedDocument)
                                    @php
                                        $document = $receivedDocument->document;
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
                                            <span class="badge bg-info">Received</span>
                                        </td>
                                        <td>
                                            <small>{{ optional($receivedDocument->receive_at)->format('M d, Y h:i A') }}</small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                @if($document->status !== 'archived')
                                               <form action="{{ route('documents.archive-receiver', encryptId($document->document_id)) }}"
                                                            method="POST"
                                                            class="d-inline">
                                                            @csrf
                                                         <button type="submit" class="btn btn-sm btn-outline-secondary" title="Archive">
                                                                <i class="bx bx-archive"></i>
                                                        </button>
                                                </form>
                                                  @endif
                                                <a href="{{ route('documents.download', encryptId($document->document_id)) }}"
                                                   class="btn btn-sm btn-outline-success"
                                                   title="Download">
                                                    <i class="bx bx-download"></i>
                                                </a>
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
                        {{ $received->links() }}
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="bx bx-receipt" style="font-size: 64px; color: #ccc;"></i>
                        <p class="text-muted mt-3">No received documents found.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
