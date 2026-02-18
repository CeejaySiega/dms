
@extends('layouts/contentNavbarLayout')

@section('title', 'Users List')
@section('content')
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
        <div>
            <button class="btn btn-sm btn-info me-2" data-bs-toggle="modal" data-bs-target="#addTestUserModal">
                <i class="bx bx-plus me-1"></i> Add User
            </button>
        </div>
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
                                @if(is_null($user->google_id))
                                    <hr class="dropdown-divider">
                                    <a class="dropdown-item edit-user" href="javascript:void(0);"
                                       data-user-id="{{ encryptId($user->user_id) }}"
                                       data-email="{{ $user->email }}"
                                       data-firstname="{{ $user->employee->firstname ?? '' }}"
                                       data-lastname="{{ $user->employee->lastname ?? '' }}"
                                       data-campus="{{ $user->employee->campus ?? '' }}"
                                       data-department-id="{{ $user->employee->department_id ?? '' }}"
>
                                        <i class="icon-base bx bx-edit me-2"></i> Edit
                                    </a>
                                    <a class="dropdown-item delete-user" href="javascript:void(0);" data-user-id="{{ encryptId($user->user_id) }}">
                                        <i class="icon-base bx bx-trash me-2 text-danger"></i> Delete
                                    </a>
                                @endif
                            </div>
                        </div>
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

<!-- Add Test User Modal -->
<div class="modal fade" id="addTestUserModal" tabindex="-1" aria-labelledby="addTestUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTestUserLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addTestUserForm">
                <div class="modal-body">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="firstName" name="firstname" placeholder="John" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="lastName" name="lastname" placeholder="Doe" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="user@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter password (min 6 characters)" required minlength="6">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Campus <span class="text-danger">*</span></label>
                            <select class="form-select" id="campus" name="campus" required>
                                <option value="">-- Select Campus --</option>
                                @foreach(getCampuses() as $code => $campus)
                                    <option value="{{ $code }}">{{ $campus['Campus'] }} ({{ $code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department <span class="text-danger">*</span></label>
                            <select class="form-select" id="departmentId" name="department_id" required>
                                <option value="">-- Select Department --</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->department_id }}">{{ $department->department_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">-- Select Role --</option>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                            <option value="superadmin">Super Admin</option>
                        </select>
                    </div>

                    <div class="alert alert-info" role="alert">
                        <i class="bx bx-info-circle me-2"></i>
                        <strong>Note:</strong> User information will be saved in both User and Employee models.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i> Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserLabel">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editUserForm">
                <input type="hidden" id="editUserId">
                <div class="modal-body">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editFirstName" name="first_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editLastName" name="last_name" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="editEmail" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password (leave blank to keep) </label>
                        <input type="password" class="form-control" id="editPassword" name="password" minlength="6">
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Campus <span class="text-danger">*</span></label>
                            <select class="form-select" id="editCampus" name="campus" required>
                                <option value="">-- Select Campus --</option>
                                @foreach(getCampuses() as $code => $campus)
                                    <option value="{{ $code }}">{{ $campus['Campus'] }} ({{ $code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Department <span class="text-danger">*</span></label>
                            <select class="form-select" id="editDepartmentId" name="department_id" required>
                                <option value="">-- Select Department --</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->department_id }}">{{ $department->department_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="editRole" name="role" required>
                            <option value="">-- Select Role --</option>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                            <option value="superadmin">Super Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('page-script')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
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

    $('#addTestUserForm').on('submit', function(e) {
        e.preventDefault();

        const formData = {
            first_name: $('#firstName').val(),
            last_name: $('#lastName').val(),
            email: $('#email').val(),
            password: $('#password').val(),
            campus: $('#campus').val(),
            department_id: $('#departmentId').val(),
            role: $('#role').val(),
            _token: '{{ csrf_token() }}'
        };

        $.ajax({
            url: '{{ route("users.create-test-user") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addTestUserModal'));
                if (modal) {
                    modal.hide();
                }
                
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'User created successfully!',
                    confirmButtonColor: '#3085d6'
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                const errorMessage = xhr.responseJSON?.message || 'Error creating user';
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage,
                    confirmButtonColor: '#d33'
                });
            }
        });
    });

    $('.edit-user').on('click', function() {
        $('#editUserId').val($(this).data('user-id'));
        $('#editFirstName').val($(this).data('firstname'));
        $('#editLastName').val($(this).data('lastname'));
        $('#editEmail').val($(this).data('email'));
        $('#editCampus').val($(this).data('campus'));
        $('#editDepartmentId').val($(this).data('department-id'));
        $('#editRole').val($(this).data('role'));
        $('#editPassword').val('');

        const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
        editModal.show();
    });

    $('#editUserForm').on('submit', function(e) {
        e.preventDefault();

        const userId = $('#editUserId').val();
        const formData = {
            first_name: $('#editFirstName').val(),
            last_name: $('#editLastName').val(),
            email: $('#editEmail').val(),
            password: $('#editPassword').val(),
            campus: $('#editCampus').val(),
            department_id: $('#editDepartmentId').val(),
            role: $('#editRole').val(),
            _token: '{{ csrf_token() }}'
        };

        $.ajax({
            url: '/users/' + userId,
            method: 'PUT',
            data: formData,
            success: function(response) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
                if (modal) {
                    modal.hide();
                }
                
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'User updated successfully!',
                    confirmButtonColor: '#3085d6'
                }).then(() => {
                    location.reload();
                });
            },
            error: function(xhr) {
                const errorMessage = xhr.responseJSON?.message || 'Error updating user';
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage,
                    confirmButtonColor: '#d33'
                });
            }
        });
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
