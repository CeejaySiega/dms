<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Stop dropdown clicks from opening the row modal
    document.querySelectorAll('.mail-item .dropdown').forEach(function (dropdown) {
        dropdown.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    });

    // Archive confirmation
    document.querySelectorAll('.archive-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const submittedForm = this;
            Swal.fire({
                title: 'Archive Document?',
                text: 'Are you sure you want to archive this document?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#696cff',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, archive it',
                cancelButtonText: 'Cancel',
                customClass: { container: 'swal-over-modal' },
                didOpen: function () {
                    const el = document.querySelector('.swal-over-modal');
                    if (el) el.style.zIndex = 99999;
                }
            }).then(function (result) {
                if (result.isConfirmed) submittedForm.submit();
            });
        });
    });

    function formatTrailDate(ts) {
        if (!ts) return 'N/A';
        const d = new Date(ts);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
            + ' ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    }

    function trailText(step) {
        if (step.type === 'sent') return 'Sent document';
        if (step.type === 'received') return step.remarks ? ('Received (' + step.remarks + ')') : 'Received document';
        if (step.type === 'forwarded') return 'Forwarded to ' + (step.forwarded_to || 'N/A');
        if (step.type === 'active') return 'Currently handling document';
        return 'Pending';
    }

    function trailBadgeClass(type) {
        if (type === 'sent') return 'bg-label-primary';
        if (type === 'received') return 'bg-label-success';
        if (type === 'forwarded') return 'bg-label-warning';
        if (type === 'active') return 'bg-label-info';
        return 'bg-label-secondary';
    }

    function renderTrail(section, trail) {
        const list = section.querySelector('.received-trail-list');
        list.innerHTML = '';

        if (!Array.isArray(trail) || trail.length === 0) {
            list.innerHTML = '<li class="list-group-item text-muted" style="font-size:0.82rem;">No trail entries found.</li>';
        } else {
            trail.forEach(function (step) {
                const li = document.createElement('li');
                li.className = 'list-group-item px-0';
                li.innerHTML = `
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="fw-semibold" style="font-size:0.86rem;">${step.actor_name || 'Unknown User'}</div>
                            <div class="text-muted" style="font-size:0.8rem;">${trailText(step)}</div>
                            <div class="text-muted" style="font-size:0.74rem;">${step.department || 'N/A'} · ${step.campus || 'N/A'}</div>
                        </div>
                        <div class="text-end">
                            <span class="badge ${trailBadgeClass(step.type)}" style="font-size:0.68rem;">${(step.type || 'pending').toUpperCase()}</span>
                            <div class="text-muted mt-1" style="font-size:0.72rem;">${formatTrailDate(step.action_at)}</div>
                        </div>
                    </div>`;
                list.appendChild(li);
            });
        }

        section.querySelector('.received-trail-loading').classList.add('d-none');
        section.querySelector('.received-trail-data').classList.remove('d-none');
    }

    const loadedReceivedTrails = new Set();

    function loadReceivedTrail(section) {
        const docId = section.dataset.documentId;
        if (!docId || loadedReceivedTrails.has(docId)) return;

        fetch(`/documents/${docId}/trail/data`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
        .then(function (r) {
            if (!r.ok) throw new Error('Trail fetch failed');
            return r.json();
        })
        .then(function (data) {
            renderTrail(section, data.trail || []);
            loadedReceivedTrails.add(docId);
        })
        .catch(function () {
            section.querySelector('.received-trail-loading').classList.add('d-none');
            section.querySelector('.received-trail-error').classList.remove('d-none');
        });
    }

    document.querySelectorAll('.received-trail-section').forEach(function (section) {
        const modalEl = section.closest('.modal');
        if (!modalEl) return;

        modalEl.addEventListener('show.bs.modal', function () {
            loadReceivedTrail(section);
        });
    });

});
</script>
