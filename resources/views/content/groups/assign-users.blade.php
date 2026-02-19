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

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {
    const groupId = '{{ encryptId($group->group_id) }}';
    const csrfToken = '{{ csrf_token() }}';
    let originalHTML = ''; // Store original list HTML

    // Store original HTML on page load
    originalHTML = $('#userList').html();

    // Search functionality - Real-time search
    $('#userSearch').on('input', function() {
        filterUsers();
    });

    // Campus filter functionality
    $('#campusFilter').on('change', function() {
        filterUsers();
    });

    function filterUsers() {
        const searchTerm = $('#userSearch').val().toLowerCase().trim();
        const campusFilter = $('#campusFilter').val().trim();
        
        // Filter and show matching items
        const itemsToKeep = [];
        const tempDiv = $('<div>').html(originalHTML);
        
        tempDiv.find('.user-item').each(function () {
            const searchData = $(this).attr('data-search') || '';
            const campusId = $(this).attr('data-campus-id') || '';
            
            // Check if item matches search term (if any)
            const matchesSearch = searchTerm.length === 0 || searchData.includes(searchTerm);
            
            // Check if item matches campus filter (if any)
            const matchesCampus = campusFilter.length === 0 || campusId === campusFilter;
            
            if (matchesSearch && matchesCampus) {
                itemsToKeep.push($(this));
            }
        });
        
        // Remove all items
        $('#userList').html('');
        
        if (itemsToKeep.length === 0) {
            // Show no results message
            $('#userList').html('<div id="noResultsMessage" class="text-center text-muted py-4">No users found matching your filters.</div>');
        } else {
            itemsToKeep.forEach(function(item) {
                $('#userList').append(item);
            });
        }
    }

    // Assign selected users
    $('#assignBtn').on('click', function () {
        const selectedUsers = $('.user-checkbox:checked').map(function () {
            return $(this).val();
        }).get();

        if (selectedUsers.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Warning',
                text: 'Please select at least one user to assign.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        const btn = $(this);
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Assigning...');

        $.ajax({
            url: `/groups/assign/${groupId}`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            },
            data: JSON.stringify({
                user_ids: selectedUsers
            }),
            success: function (response) {
                // Get user data and add to members list
                selectedUsers.forEach(userId => {
                    const userItem = $(`.user-item[data-user-id="${userId}"]`);
                    const userName = userItem.find('.fw-semibold').text();
                    const userEmail = userItem.find('.text-muted.small').first().text();
                    
                    // Check if already in list
                    if ($(`#membersList li[data-member-id="${userId}"]`).length === 0) {
                        const memberHtml = `
                            <li class="list-group-item d-flex align-items-center justify-content-between" data-member-id="${userId}">
                                <div>
                                    <div class="fw-semibold">${userName}</div>
                                    <div class="text-muted small">${userEmail}</div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-member-btn" data-user-id="${userId}">
                                    <i class="bx bx-x"></i> Remove
                                </button>
                            </li>
                        `;
                        if ($('#membersList').find('#emptyMessage').length > 0) {
                            $('#membersList').html(memberHtml);
                        } else {
                            $('#membersList').append(memberHtml);
                        }
                    }
                    
                    // Disable checkbox and add badge
                    userItem.find('.user-checkbox').prop('disabled', true);
                    if (userItem.find('.badge').length === 0) {
                        userItem.find('span').eq(1).append('<span class="badge bg-label-success ms-auto">Assigned</span>');
                    }
                });
                
                // Remove empty message
                $('#emptyMessage').remove();
                
                // Update member count
                updateMemberCount();
                
                // Reset checkboxes
                $('.user-checkbox:checked').prop('checked', false);
                
                // Show success alert
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message,
                    confirmButtonColor: '#3085d6'
                });
            },
            error: function (xhr) {
                const message = xhr.responseJSON?.message || 'An error occurred while assigning users.';
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: message,
                    confirmButtonColor: '#d33'
                });
            },
            complete: function () {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Remove member
    $(document).on('click', '.remove-member-btn', function () {
        const userId = $(this).data('user-id');
        const btn = $(this);

        Swal.fire({
            title: 'Remove User?',
            text: 'This user will be removed from the group.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, remove it!'
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            const originalHtml = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

            $.ajax({
                url: `/groups/assign/${groupId}`,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json'
                },
                data: JSON.stringify({
                    user_id: userId
                }),
                success: function (response) {
                    // Remove from list with animation
                    $(`#membersList li[data-member-id="${userId}"]`).fadeOut(300, function () {
                        $(this).remove();
                        updateMemberCount();
                    });

                    // Re-enable checkbox and remove badge
                    $(`.user-item[data-user-id="${userId}"] .user-checkbox`).prop('disabled', false);
                    $(`.user-item[data-user-id="${userId}"] .badge`).remove();

                    // Show success alert
                    Swal.fire({
                        icon: 'success',
                        title: 'Removed!',
                        text: 'User removed successfully!',
                        confirmButtonColor: '#3085d6'
                    });
                },
                error: function (xhr) {
                    const message = xhr.responseJSON?.message || 'An error occurred while removing the user.';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: message,
                        confirmButtonColor: '#d33'
                    });
                    btn.prop('disabled', false).html(originalHtml);
                }
            });
        });
    });
    function loadMembers() {
        $.ajax({
            url: `/groups/assign/${groupId}/members`,
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function (response) {
                // Update member count
                $('#memberCount').text(response.members.length);
                updateMemberCount();
            }
        });
    }

    // Update member count display
    function updateMemberCount() {
        const count = $('#membersList li').length;
        $('#memberCount').text(count);
        
        if (count === 0) {
            if ($('#emptyMessage').length === 0) {
                $('#membersList').html('<p class="text-muted mb-0" id="emptyMessage">No users assigned to this group yet.</p>');
            }
        } else {
            $('#emptyMessage').remove();
        }
    }

    // Show alert message using SweetAlert2
    function showAlert(message, type) {
        const iconMap = {
            'warning': 'warning',
            'success': 'success',
            'danger': 'error',
            'info': 'info'
        };
        
        Swal.fire({
            icon: iconMap[type] || 'info',
            title: type === 'success' ? 'Success!' : type === 'danger' ? 'Error!' : 'Warning',
            text: message,
            confirmButtonColor: '#3085d6'
        });
    }
});
</script>
@endsection
