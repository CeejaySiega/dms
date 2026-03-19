<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Mark as read when modal is closed
    document.querySelectorAll('.modal').forEach(function (modal) {
        modal.addEventListener('hidden.bs.modal', function (event) {
            const modalId     = event.target.id;
            const recipientId = modalId.replace('inboxDocModal-', '');
            const mailItem    = document.querySelector(`.mail-item[data-recipient-id="${recipientId}"]`);

            if (mailItem && mailItem.getAttribute('data-status') === 'pending') {
                fetch(`{{ url('/documents/mark-as-read') }}/${recipientId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mailItem.classList.remove('mail-unread');

                        mailItem.querySelectorAll('.badge').forEach(badge => {
                            if (badge.textContent.trim().toLowerCase() === 'pending') {
                                badge.className      = 'badge bg-secondary';
                                badge.style.fontSize = '0.7rem';
                                badge.textContent    = 'Read';
                            }
                        });

                        const senderSpan = mailItem.querySelector('.flex-shrink-0 span');
                        if (senderSpan) {
                            senderSpan.classList.remove('fw-semibold', 'text-body');
                            senderSpan.classList.add('text-body');
                        }

                        const docTypeSpan = mailItem.querySelector('.flex-grow-1 span:first-child');
                        if (docTypeSpan) {
                            docTypeSpan.classList.remove('fw-semibold', 'text-body');
                            docTypeSpan.classList.add('text-muted');
                        }

                        mailItem.setAttribute('data-status', 'read');
                    }
                })
                .catch(error => console.error('Error marking document as read:', error));
            }
        });
    });

    // Receive form confirmation
    document.querySelectorAll('.receive-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const submittedForm = this;
            Swal.fire({
                title: 'Receive Document?',
                text: 'Mark this document as received?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#696cff',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, receive it',
                cancelButtonText: 'Cancel',
                customClass: { container: 'swal-over-modal' },
                didOpen: function () {
                    const el = document.querySelector('.swal-over-modal');
                    if (el) el.style.zIndex = 99999;
                }
            }).then(function (result) {
                if (result.isConfirmed) {
                    // Show loading alert while the request is being processed
                    Swal.fire({
                        title: 'Receiving Document…',
                        text: 'Please wait while we process your request.',
                        icon: 'info',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        customClass: { container: 'swal-over-modal' },
                        didOpen: function () {
                            const el = document.querySelector('.swal-over-modal');
                            if (el) el.style.zIndex = 99999;
                            Swal.showLoading();
                        }
                    });
                    submittedForm.submit();
                }
            });
        });
    });

});
</script>
