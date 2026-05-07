<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const userSearch = document.getElementById('userSearch');
    const userId = document.getElementById('userId');
    const userList = document.getElementById('userList');
    const addRecipientBtn = document.getElementById('addRecipientBtn');
    const recipientList = document.getElementById('recipientList');
    const recipientInputs = document.getElementById('recipientInputs');
    const forwardForm = document.getElementById('forwardDocumentForm');
    const priority = document.getElementById('priority');
    const dueDateWrap = document.getElementById('dueDateWrap');
    const dueDate = document.getElementById('dueDate');
    const selectedRecipients = [];

    function syncDueDateVisibility() {
        const urgent = priority && priority.value === 'urgent';
        dueDateWrap.classList.toggle('d-none', !urgent);
        dueDate.required = urgent;

        if (!urgent) {
            dueDate.value = '';
        }
    }

    if (priority && dueDateWrap && dueDate) {
        priority.addEventListener('change', syncDueDateVisibility);
        syncDueDateVisibility();
    }

    function renderRecipients() {
        recipientList.innerHTML = '';
        recipientInputs.innerHTML = '';

        selectedRecipients.forEach(function (recipient) {
            const chip = document.createElement('span');
            chip.className = 'badge bg-label-primary me-1 mb-1';
            chip.innerHTML = recipient.label +
                ' <button type="button" class="btn btn-sm btn-link text-danger ms-1 p-0 remove-recipient" data-id="' + recipient.id + '">x</button>';
            recipientList.appendChild(chip);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'user_ids[]';
            input.value = recipient.id;
            recipientInputs.appendChild(input);
        });
    }

    userSearch.addEventListener('input', function () {
        const inputValue = userSearch.value;
        const options = userList.querySelectorAll('option');
        let matchedId = '';

        options.forEach(function (option) {
            if (option.value === inputValue) {
                matchedId = option.dataset.id || '';
            }
        });

        userId.value = matchedId;
    });

    addRecipientBtn.addEventListener('click', function () {
        const id = userId.value;
        const label = userSearch.value.trim();

        if (!id) {
            Swal.fire({ icon: 'warning', title: 'Invalid recipient', text: 'Select a valid user from the list.' });
            return;
        }

        if (selectedRecipients.some(function (r) { return r.id === id; })) {
            Swal.fire({ icon: 'info', title: 'Already selected', text: 'This recipient is already added.' });
            return;
        }

        if (selectedRecipients.length >= 5) {
            Swal.fire({ icon: 'warning', title: 'Limit reached', text: 'You can forward to up to 5 recipients only.' });
            return;
        }

        selectedRecipients.push({ id: id, label: label });
        renderRecipients();
        userSearch.value = '';
        userId.value = '';
    });

    recipientList.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-recipient');
        if (!button) return;

        const id = button.getAttribute('data-id');
        const index = selectedRecipients.findIndex(function (r) { return r.id === id; });
        if (index >= 0) {
            selectedRecipients.splice(index, 1);
            renderRecipients();
        }
    });

    forwardForm.addEventListener('submit', function (event) {
        if (!selectedRecipients.length) {
            event.preventDefault();
            Swal.fire({ icon: 'warning', title: 'No recipients', text: 'Please add at least one recipient.' });
            return;
        }

        if (!forwardForm.checkValidity()) {
            return;
        }

        event.preventDefault();

        if (forwardForm.dataset.submitting === '1') {
            return;
        }

        forwardForm.dataset.submitting = '1';
        Swal.fire({
            title: 'Forwarding Document...',
            html: '<p class="text-muted mb-0">Please wait while the document is being forwarded.</p>',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function () {
                Swal.showLoading();
            }
        });

        setTimeout(function () {
            forwardForm.submit();
        }, 50);
    });
});
</script>
