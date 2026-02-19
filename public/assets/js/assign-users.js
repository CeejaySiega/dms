$(document).ready(function () {
  const groupId = $('#assign-users-group-id').val();
  const csrfToken = $('meta[name="csrf-token"]').attr('content');
  let originalHTML = '';

  // Store original HTML on page load
  originalHTML = $('#userList').html();

  // Search functionality - Real-time search
  $('#userSearch').on('input', function () {
    filterUsers();
  });

  // Campus filter functionality
  $('#campusFilter').on('change', function () {
    filterUsers();
  });

  function filterUsers() {
    const searchTerm = $('#userSearch').val().toLowerCase().trim();
    const campusFilter = $('#campusFilter').val().trim();
    const itemsToKeep = [];
    const tempDiv = $('<div>').html(originalHTML);
    tempDiv.find('.user-item').each(function () {
      const searchData = $(this).attr('data-search') || '';
      const campusId = $(this).attr('data-campus-id') || '';
      const matchesSearch = searchTerm.length === 0 || searchData.includes(searchTerm);
      const matchesCampus = campusFilter.length === 0 || campusId === campusFilter;
      if (matchesSearch && matchesCampus) {
        itemsToKeep.push($(this));
      }
    });
    $('#userList').html('');
    if (itemsToKeep.length === 0) {
      $('#userList').html(
        '<div id="noResultsMessage" class="text-center text-muted py-4">No users found matching your filters.</div>'
      );
    } else {
      itemsToKeep.forEach(function (item) {
        $('#userList').append(item);
      });
    }
  }

  // Assign selected users
  $('#assignBtn').on('click', function () {
    const selectedUsers = $('.user-checkbox:checked')
      .map(function () {
        return $(this).val();
      })
      .get();
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
        refreshAssignedUsersList();
        refreshUserList();
        $('.user-checkbox:checked').prop('checked', false);
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
    }).then(result => {
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
          refreshAssignedUsersList();
          refreshUserList();
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
        },
        complete: function () {
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
        $('#memberCount').text(response.members.length);
        updateMemberCount();
      }
    });
  }

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

  function refreshUserList() {
    $.ajax({
      url: `/groups/assign/${groupId}/users`,
      method: 'GET',
      dataType: 'json',
      success: function (response) {
        if (response && response.html !== undefined) {
          $('#userList').html(response.html);
        } else if (typeof response === 'string') {
          $('#userList').html(response);
        }
        originalHTML = $('#userList').html();
        filterUsers();
      },
      error: function (xhr) {
        let msg = 'Failed to refresh the user list.';
        if (xhr.responseText) msg += '\n' + xhr.responseText;
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: msg,
          confirmButtonColor: '#d33'
        });
      }
    });
  }

  function refreshAssignedUsersList() {
    $.ajax({
      url: `/groups/assign/${groupId}/members`,
      method: 'GET',
      dataType: 'json',
      success: function (response) {
        if (response && response.html !== undefined) {
          $('#membersList').parent().html(response.html);
        } else if (typeof response === 'string') {
          $('#membersList').parent().html(response);
        }
        updateMemberCount();
      },
      error: function (xhr) {
        let msg = 'Failed to refresh the assigned users list.';
        if (xhr.responseText) msg += '\n' + xhr.responseText;
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: msg,
          confirmButtonColor: '#d33'
        });
      }
    });
  }

  function showAlert(message, type) {
    const iconMap = {
      warning: 'warning',
      success: 'success',
      danger: 'error',
      info: 'info'
    };
    Swal.fire({
      icon: iconMap[type] || 'info',
      title: type === 'success' ? 'Success!' : type === 'danger' ? 'Error!' : 'Warning',
      text: message,
      confirmButtonColor: '#3085d6'
    });
  }
});
