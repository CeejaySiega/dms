
@extends('layouts/contentNavbarLayout')

@section('title', 'Users List') 
@section('content')
@php
    $currentRole = strtolower((string) optional(auth()->user()->employee)->role);
    $canManageUsers = $currentRole === 'superadmin';
    $canRegisterAccounts = in_array($currentRole, ['superadmin', 'admin']);
@endphp
<!-- Page Header with Breadcrumb -->
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="mb-4">
        <h4 class="fw-bold mb-2"><i class="bx bx-user me-2"></i>Users Management</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb-style1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard-analytics') }}">Home</a>
                </li>
                <li class="breadcrumb-item active">Users Management</li>
            </ol>
        </nav>
    </div>
</div>
<!-- Users List Table -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between py-3">
        <div class="d-flex align-items-center gap-2">
            <h5 class="m-0">Users List and Permissions</h5>
        </div>
        @if($canManageUsers || $canRegisterAccounts)
            <div>
                <button class="btn btn-sm btn-info me-2" data-bs-toggle="modal" data-bs-target="#registerAccountModal">
                    <i class="bx bx-plus me-1"></i> Register Account
                </button>
            </div>
        @endif
    </div>
    
    <div class="card-body border-bottom">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="icon-base bx bx-search"></i></span>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search by name or email...">
                </div>
            </div>
            <div class="col-md-4">
              <select class="form-select" id="campusFilter">
                     <option value="">All Campus</option>
                 @foreach(getCampusNames() as $campus)
                    <option value="{{ $campus }}">{{ $campus }}</option>
                 @endforeach
              </select>
            </div>
            <div class="col-md-4">
                <select class="form-select" id="departmentFilter">
                    <option value="">All Departments</option>
                    <option value="UISA">UISA</option>
                    <option value="HR">HR</option>
                    <option value="Finance">Finance</option>
                    <option value="IT">IT</option>
                </select>
            </div>
        </div>
    </div>
    
    <div class="table-responsive text-nowrap">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>User ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Campus</th>
                    <th>Department</th>
                    <th>Role</th>
                    <th class="text-center">Actions</th>
                </tr>
            </thead>
            <tbody class="table-border-bottom-0">
                @forelse($users as $user)
                <tr>
                    <td><span class="fw-semibold">#{{ $user->user_id }}</span></td>
                    <td>
                        @if($user->employee)
                            <div class="d-flex align-items-center">
                                <div class="avatar avatar-sm me-3">
                                    <span class="avatar-initial rounded-circle bg-label-primary">{{ strtoupper(substr($user->employee->firstname, 0, 1)) }}</span>
                                </div>
                                <div>
                                    <span class="fw-semibold">{{ $user->employee->firstname }} {{ $user->employee->lastname }}</span>
                                </div>
                            </div>
                        @else
                            <span>{{ $user->name }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="text-truncate">{{ $user->email }}</span>
                    </td>
                    <td>
                        @if($user->employee)
                            <span class="badge bg-label-{{ getCampusColor($user->employee->campus) }}">
                                {{ getCampusName($user->employee->campus) }}
                            </span>
                        @else
                            <span class="badge bg-label-secondary">—</span>
                        @endif
                    </td>
                    <td>
                        @if($user->employee && $user->employee->department)
                            <span class="badge bg-label-info">{{ $user->employee->department->department_name }}</span>
                        @else
                            <span class="badge bg-label-secondary">N/A</span>
                        @endif
                    </td>
                    <td>
                        @if($user->employee && $user->employee->role)
                            @php
                                $roleColors = [
                                    'admin' => 'danger',
                                    'superadmin' => 'warning',
                                    'user' => 'secondary'
                                ];
                                $roleColor = $roleColors[strtolower($user->employee->role)] ?? 'secondary';
                            @endphp
                            <span class="badge bg-label-{{ $roleColor }}">{{ ucfirst($user->employee->role) }}</span>
                        @else
                            <span class="badge bg-label-secondary">No Role</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($canManageUsers)
                            <div class="dropdown">
                                <button type="button" class="btn btn-sm btn-icon btn-text-secondary rounded-pill" data-bs-toggle="dropdown">
                                    <i class="icon-base bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu">
                                    <h6 class="dropdown-header">Change Permissions</h6>
                                    <a class="dropdown-item" href="javascript:void(0);" onclick="changeRole('{{ encryptId($user->user_id) }}', 'admin')">
                                        <i class="icon-base bx bx-shield me-2 text-danger"></i> Admin
                                    </a>
                                    <a class="dropdown-item" href="javascript:void(0);" onclick="changeRole('{{ encryptId($user->user_id) }}', 'superadmin')">
                                        <i class="icon-base bx bx-crown me-2 text-warning"></i> Super Admin
                                    </a>
                                    <a class="dropdown-item" href="javascript:void(0);" onclick="changeRole('{{ encryptId($user->user_id) }}', 'user')">
                                        <i class="icon-base bx bx-user me-2 text-secondary"></i> User
                                    </a>
                                    <hr class="dropdown-divider">
                                    @if($user->user_id === auth()->user()->user_id)
                                        {{-- <span class="dropdown-item text-muted small">
                                            <i class="icon-base bx bx-trash me-2 text-secondary"></i> Delete (Cannot)
                                        </span> --}}
                                    @else
                                        <a class="dropdown-item delete-user" href="javascript:void(0);" data-user-id="{{ encryptId($user->user_id) }}">
                                            <i class="icon-base bx bx-trash me-2 text-danger"></i> Delete
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @else
                            <span class="text-muted small">View only</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <div class="mb-3">
                            <i class="icon-base bx bx-user-x icon-lg text-muted mb-3" style="font-size: 3rem;"></i>
                        </div>
                        <h6 class="text-muted mb-1">No users found</h6>
                        <p class="text-muted small">Try adjusting your search filters</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@if($canManageUsers || $canRegisterAccounts)
<!-- Register Account Modal -->
<div class="modal fade" id="registerAccountModal" tabindex="-1" aria-labelledby="registerAccountLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="registerAccountLabel">Register Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="registerAccountForm">
                <div class="modal-body">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">HRMIS Account (Email) <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="hrmisAccount" name="hrmis_account" placeholder="employee@southernleytestateu.edu.ph" required>
                        <small class="text-muted">Use a valid HRMIS email account to register.</small>
                    </div>

                    {{-- <div class="alert alert-info" role="alert">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Note:</strong> Credentials are pulled from HRMIS and role defaults to User.
                    </div> --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i> Register Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endif

@section('page-script')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    const canManageUsers = @json($canManageUsers);
    const canRegisterAccounts = @json($canRegisterAccounts);

    function filterTable() {
        var searchValue = $('#searchInput').val().toLowerCase();
        var campusValue = $('#campusFilter').val().toLowerCase();
        var departmentValue = $('#departmentFilter').val().toLowerCase();

        var visibleRows = 0;    

        $('tbody tr').each(function() {
            if ($(this).find('td[colspan="7"]').length) {
                return true;
            }
            var name = $(this).find('td:eq(1)').text().toLowerCase();
            var email = $(this).find('td:eq(2)').text().toLowerCase();
            var campus = $(this).find('td:eq(3)').text().toLowerCase();
            var department = $(this).find('td:eq(4)').text().toLowerCase();

            var matchesSearch = name.includes(searchValue) || email.includes(searchValue);
            var matchesCampus = !campusValue || campus.includes(campusValue);
            var matchesDepartment = !departmentValue || department.includes(departmentValue);

            if (matchesSearch && matchesCampus && matchesDepartment) {
                $(this).show();
                visibleRows++;
            } else {
                $(this).hide();
            }
        });

        var emptyRow = $('tbody tr').filter(function() {
            return $(this).find('td[colspan="7"]').length;
        });
        if (emptyRow.length) {
            emptyRow.toggle(visibleRows === 0);
        }
    }

    $('#searchInput').on('keyup', filterTable);
    $('#campusFilter, #departmentFilter').on('change', filterTable);

    $('#registerAccountForm').on('submit', function(e) {
        e.preventDefault();

        if (!canRegisterAccounts) {
            Swal.fire({
                icon: 'error',
                title: 'Access Denied',
                text: 'You do not have permission to register accounts.',
                confirmButtonColor: '#d33'
            });
            return;
        }

        const formData = {
            hrmis_account: $('#hrmisAccount').val(),
            _token: '{{ csrf_token() }}'
        };

        $.ajax({
            url: '{{ route("users.register-account") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('registerAccountModal'));
                if (modal) {
                    modal.hide();
                }
                
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message || 'Account registered successfully!',
                    confirmButtonColor: '#3085d6'
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                const errorMessage = xhr.responseJSON?.message || 'Error registering account';
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage,
                    confirmButtonColor: '#d33'
                });
            }
        });
    });

    $('#registerAccountModal').on('hidden.bs.modal', function() {
        $('#registerAccountForm')[0].reset();
    });

    $('.delete-user').on('click', function() {
        const userId = $(this).data('user-id');
        
        Swal.fire({
            title: 'Delete User?',
            text: 'This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            $.ajax({
                url: '/users/' + userId,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'User deleted successfully!',
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    const errorMessage = xhr.responseJSON?.message || 'Error deleting user';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: errorMessage,
                        confirmButtonColor: '#d33'
                    });
                }
            });
        });
    });
});

function changeRole(userId, role) {
    if (!@json($canManageUsers)) {
        return;
    }

    Swal.fire({
        title: 'Change Role?',
        text: 'Change this user\'s role to ' + role.toUpperCase() + '?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, change it!'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        $.ajax({
            url: '/users/' + userId + '/change-role',
            method: 'POST',
            data: {
                role: role,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Role changed successfully!',
                    confirmButtonColor: '#3085d6'
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                const errorMessage = xhr.responseJSON?.message || 'Unknown error';
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Error changing role: ' + errorMessage,
                    confirmButtonColor: '#d33'
                });
            }
        });
    });
}
</script>
@endsection
