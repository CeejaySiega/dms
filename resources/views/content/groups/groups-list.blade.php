@extends('layouts/contentNavbarLayout')

@section('title', 'Group Management')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    
    <div class="mb-4">
        <h4 class="fw-bold mb-2"><i class="bx bx-group me-2"></i>Group Management</h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb breadcrumb-style1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard-analytics') }}">Home</a>
                </li>
                <li class="breadcrumb-item active">Group Management</li>
            </ol>
        </nav>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-3">
                <div class="d-flex align-items-center gap-2" >
                    <div class ="bx bx-search me-2"></div>
                    <input type="text" id="positionSearch" class="form-control" placeholder="Search Group" style="max-width: 200px;">
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGroupModal">
                    <i class="bx bx-plus me-1"></i> Add New Group
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Group ID</th>
                        <th>Group Name</th>
                        <th class="text-center">Members</th>
                        <th>Created at</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $group)
                    <tr class="group-row">
                        <td><span class="fw-semibold">#{{ $group->group_id }}</span></td>
                        <td>{{ $group->position }}</td>
                        <td class="text-center">
                            <span class="badge bg-label-info">{{ $group->members_count ?? 0 }}</span>
                        </td>
                        <td>
                            <small>{{ $group->created_at->format('M d, Y') }}</small>
                        </td>
                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill" data-bs-toggle="dropdown">
                                    <i class="bx bx-dots-vertical-rounded"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="{{ route('groups.assign.show', encryptId($group->group_id)) }}">
                                        <i class="bx bx-user-plus me-2"></i> Assign Users
                                    </a>
                                    <a class="dropdown-item edit-group" href="#" data-group-id="{{ encryptId($group->group_id) }}" 
                                       data-position="{{ $group->position }}" 
                                       data-campus="{{ $group->campus }}">
                                        <i class="bx bx-edit me-2"></i> Edit
                                    </a>
                                    <a class="dropdown-item delete-group" href="#" data-group-id="{{ encryptId($group->group_id) }}">
                                        <i class="bx bx-trash me-2 text-danger"></i> Delete
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <p class="text-muted">No groups found. Create your first group!</p>
                        </td>
                    </tr>
                    @endforelse
                    <tr id="noSearchResultsRow" style="display: none;">
                        <td colspan="5" class="text-center py-5">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <i class="bx bx-search-alt-2 text-muted" style="font-size: 2rem;"></i>
                                <p class="mb-0 fw-semibold">No matching groups found</p>
                                <small class="text-muted">Try a different keyword.</small>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div class="modal fade" id="addGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addGroupForm">
                <div class="modal-body">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label">Group Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="position" required>
                    </div>
                    
                    {{-- <div class="mb-3">
                        <label class="form-label">Campus <span class="text-danger">*</span></label>
                        <select class="form-select" name="campus" required>
                            <option value="">-- Select Campus --</option>
                            @foreach(getCampuses() as $abbreviation => $campus)
                                <option value="{{ $abbreviation }}">{{ $campus['Campus'] }} ({{ $abbreviation }})</option>
                            @endforeach
                        </select>
                    </div> --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Group</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Group Modal -->
<div class="modal fade" id="editGroupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Group</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editGroupForm">
                <div class="modal-body">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-3">
                        <label class="form-label">Group Name<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="position" id="editGroupPosition" required>
                    </div>

                    {{-- <div class="mb-3">
                        <label class="form-label">Campus <span class="text-danger">*</span></label>
                        <select class="form-select" name="campus" id="editGroupCampus" required>
                            <option value="">-- Select Campus --</option>
                            @foreach(getCampuses() as $abbreviation => $campus)
                                <option value="{{ $abbreviation }}">{{ $campus['Campus'] }} ({{ $abbreviation }})</option>
                            @endforeach
                        </select>
                    </div> --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    let currentGroupId = null;

    // Search groups by group name (2nd table column)
    $('#positionSearch').on('input', function() {
        const positionValue = ($(this).val() || '').toLowerCase();
        let visibleRows = 0;

        $('table tbody tr.group-row').each(function() {
            const position = $(this).find('td:eq(1)').text().toLowerCase();
            const matchesPosition = !positionValue || position.includes(positionValue);

            if (matchesPosition) {
                $(this).show();
                visibleRows++;
            } else {
                $(this).hide();
            }
        });

        if (positionValue && visibleRows === 0) {
            $('#noSearchResultsRow').show();
        } else {
            $('#noSearchResultsRow').hide();
        }
    });

    // Add Group
    $('#addGroupForm').on('submit', function(e) {
        e.preventDefault();
        const formData = {
            position: $('input[name="position"]').val(),
            // campus: $('select[name="campus"]').val(),
             _token: '{{ csrf_token() }}'
        };

        $.ajax({
            url: '{{ route("groups.store") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('addGroupModal'));
                if (modal) {
                    modal.hide();
                }
                
                // Show success alert
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Group created successfully!',
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
                    text: errorMessage,
                    confirmButtonColor: '#d33'
                });
            }
        });
    });

    // Edit Group - Open Modal
    $('.edit-group').on('click', function(e) {
        e.preventDefault();
        currentGroupId = $(this).data('group-id');
        
        $('#editGroupPosition').val($(this).data('position'));
        // $('#editGroupCampus').val($(this).data('campus'));
        
        const editModal = new bootstrap.Modal(document.getElementById('editGroupModal'));
        editModal.show();
    });

    // Update Group
    $('#editGroupForm').on('submit', function(e) {
        e.preventDefault();
        const formData = {
            position: $('#editGroupPosition').val(),
            // campus: $('#editGroupCampus').val(),
            _token: '{{ csrf_token() }}'
        };

        $.ajax({
            url: `/groups/${currentGroupId}`,
            method: 'PUT',
            data: formData,
            success: function(response) {
                // Close the modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('editGroupModal'));
                if (modal) {
                    modal.hide();
                }
                
                // Show success alert
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Group updated successfully!',
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
                    text: errorMessage,
                    confirmButtonColor: '#d33'
                });
            }
        });
    });

    // Delete Group
    $('.delete-group').on('click', function(e) {
        e.preventDefault();
        const groupId = $(this).data('group-id');
        
        Swal.fire({
            title: 'Delete Group?',
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
                url: `/groups/${groupId}`,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Group deleted successfully!',
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
                        text: errorMessage,
                        confirmButtonColor: '#d33'
                    });
                }
            });
        });
    });
});
</script>

<style>
    .table-hover tbody tr:hover {
        background-color: #f5f5f5;
    }
</style>
@endsection
