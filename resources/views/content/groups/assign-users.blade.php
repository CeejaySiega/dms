@extends('layouts/contentNavbarLayout')

@section('title', 'Assign Users to Group')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Page Header with Breadcrumb -->
    <div class="mb-4">
        <h4 class="fw-bold mb-2"><i class="bx bx-user-check me-2"></i>Assign Users to Group</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb-style1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard-analytics') }}">Home</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('groups.index') }}">Groups</a>
                </li>
                <li class="breadcrumb-item active">Assign Users</li>
            </ol>
        </nav>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <h6 class="mb-1"><span class="fw-bold">Group Name:</span>&nbsp;{{ $group->position }}</h6>
                    </div>
                </div>
                <a href="{{ route('groups.index') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Groups
                </a>
            </div>
        </div>
    </div>

    <div id="alertContainer"></div>

    @php
        $memberUserIds = $members->pluck('user_id')->all();
    @endphp

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Available Users</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 d-flex gap-2" style="align-items: flex-end;">
                        <input type="text" class="form-control" id="userSearch" placeholder="Search by name, email, or campus">
                        <select class="form-select" id="campusFilter" style="flex: 0 0 250px;">
                            <option value="">-- All Campuses --</option>
                            @php
                                $campuses = \App\Helpers\Globalpreferrence::Campuses();
                            @endphp
                            @foreach($campuses as $abbreviation => $campus)
                            <option value="{{ $campus['ID'] }}">{{ $campus['Campus'] }} ({{ $abbreviation }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="list-group" id="userList" style="max-height: 420px; overflow-y: auto;">


                        @foreach($users as $user)
                            @php
                                $isMember = in_array($user->user_id, $memberUserIds, true);
                                $displayName = $user->name ?: $user->email;
                                $employeeName = $user->employee ? strtolower($user->employee->firstname . ' ' . $user->employee->lastname) : '';
                                $campusCode = $user->employee ? strtolower($user->employee->campus) : '';
                                $searchText = strtolower(($user->name ?? '') . ' ' . ($user->email ?? '') . ' ' . $employeeName . ' ' . $campusCode);
                            @endphp
                            <label class="list-group-item d-flex align-items-start gap-2 user-item" data-search="{{ $searchText }}" data-campus="{{ $user->employee ? $user->employee->campus : '' }}" data-campus-id="{{ $user->employee ? getCampusId($user->employee->campus) : '' }}" data-user-id="{{ encryptId($user->user_id) }}">
                                <input class="form-check-input mt-1 user-checkbox" type="checkbox" value="{{ encryptId($user->user_id) }}" {{ $isMember ? 'disabled' : '' }}>
                                <span>
                                    @if($user->employee)
                                        <div class="fw-semibold">{{ $user->employee->firstname }} {{ $user->employee->lastname }}</div>
                                    @else
                                        <div class="fw-semibold">{{ $displayName }}</div>
                                    @endif
                                    <div class="text-muted small">{{ $user->email }}</div>
                                    @if($user->employee)
                                        <div class="text-muted small">{{ getCampusName($user->employee->campus) ?? $user->employee->campus }}</div>
                                    @endif
                                    @if($user->group_memberships > 0 && !$isMember)
                                        <div class="text-muted small">
                                            <i class="bx bx-info-circle"></i> In {{ $user->group_memberships }} group(s)
                                        </div>
                                    @endif
                                </span>
                                @if($isMember)
                                    <span class="badge bg-label-success ms-auto">Assigned</span>
                                @elseif($user->group_memberships > 0)
                                    <span class="badge bg-label-warning ms-auto">In other groups</span>
                                @endif
                            </label>
                        @endforeach
                    </div>

                    <button type="button" class="btn btn-primary mt-3" id="assignBtn">
                        <i class="bx bx-plus me-1"></i> Assign Selected
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">Assigned Users (<span id="memberCount">{{ $members->count() }}</span>)</h5>
                </div>
                <div class="card-body">
                    @if($members->isEmpty())
                        <p class="text-muted mb-0" id="emptyMessage">No users assigned to this group yet.</p>
                    @endif
                    <ul class="list-group" id="membersList">
                        @foreach($members as $member)
                            <li class="list-group-item d-flex align-items-center justify-content-between" data-member-id="{{ encryptId($member->user_id) }}">
                                <div>
                                    @if($member->user->employee)
                                        <div class="fw-semibold">{{ $member->user->employee->firstname }} {{ $member->user->employee->lastname }}</div>
                                    @else
                                        <div class="fw-semibold">
                                            {{ $member->user->name ?? $member->user->email ?? ('User #' . $member->user_id) }}
                                        </div>
                                    @endif
                                    <div class="text-muted small">{{ $member->user->email ?? '' }}</div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-member-btn" data-user-id="{{ encryptId($member->user_id) }}">
                                    <i class="bx bx-x"></i> Remove
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

</script>
<!-- CSRF Token for AJAX -->
<meta name="csrf-token" content="{{ csrf_token() }}">
<!-- Hidden input for groupId -->
<input type="hidden" id="assign-users-group-id" value="{{ encryptId($group->group_id) }}">
<!-- External JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/assets/js/assign-users.js"></script>
</script>
@endsection
