<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
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

function buildTrailList(container, trail, unsendMap) {
    container.innerHTML = '';
    unsendMap = unsendMap || {};

    if (!Array.isArray(trail) || trail.length === 0) {
        container.innerHTML = '<li class="list-group-item text-muted" style="font-size:0.82rem;">No trail entries found.</li>';
        return;
    }

    trail.forEach(function (step) {
        let unsendBtn = '';
        const unsendData = unsendMap[step.user_id];
        if ((step.type === 'active' || step.type === 'pending') && unsendData) {
            unsendBtn = `
                <div class="mt-1">
                    <button type="button"
                            class="btn btn-sm btn-outline-danger"
                            style="font-size:.72rem;padding:2px 9px;"
                            onclick="confirmUnsend('${unsendData.url}','${unsendData.name.replace(/'/g,"\\'")}')">
                        <i class="bx bx-x" style="font-size:.85rem;"></i> Unsend
                    </button>
                </div>`;
        }

        const li = document.createElement('li');
        li.className = 'list-group-item px-0';
        li.innerHTML = `
            <div>
                <div>
                    <div class="fw-semibold" style="font-size:0.86rem;">${step.actor_name || 'Unknown User'}</div>
                    <div class="text-muted" style="font-size:0.8rem;">${trailText(step)}</div>
                    <div class="text-muted" style="font-size:0.74rem;">${step.department || 'N/A'} · ${step.campus || 'N/A'}</div>
                </div>
                <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                    <span class="badge ${trailBadgeClass(step.type)}" style="font-size:0.68rem;">${(step.type || 'pending').toUpperCase()}</span>
                    <span class="text-muted" style="font-size:0.72rem;">${formatTrailDate(step.action_at)}</span>
                    ${unsendBtn}
                </div>
            </div>`;
        container.appendChild(li);
    });
}

function renderTrail(pane, data, unsendMap) {
    buildTrailList(pane.querySelector('.sent-trail-list'), data.trail || [], unsendMap);
    pane.querySelector('.trail-loading').classList.add('d-none');
    pane.querySelector('.trail-data').classList.remove('d-none');
}

// ── Fetch trail (once per document) ──────────────────────────────────────
const loadedTrails = new Set();

function loadTrail(documentId, unsendMap) {
    if (loadedTrails.has(documentId)) return;

    const pane = document.querySelector(`#trail-section-${documentId}`);
    if (!pane) return;

    fetch(`/documents/${documentId}/trail/data`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept':           'application/json',
            'X-CSRF-TOKEN':     document.querySelector('meta[name="csrf-token"]')?.content || '',
        }
    })
    .then(r => r.ok ? r.json() : Promise.reject(r.status))
    .then(data => {
        renderTrail(pane, data, unsendMap);
        loadedTrails.add(documentId);
    })
    .catch(() => {
        pane.querySelector('.trail-loading').classList.add('d-none');
        pane.querySelector('.trail-error').classList.remove('d-none');
    });
}

// ── Wire trail loading when modal opens ───────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.trail-section').forEach(section => {
        const docId = section.dataset.documentId;
        const unsendMap = JSON.parse(section.dataset.unsend || '{}');

        const modalEl = section.closest('.modal');
        if (!modalEl) return;

        modalEl.addEventListener('show.bs.modal', function () {
            loadTrail(docId, unsendMap);
        });
    });
});

// ── Unsend confirm ────────────────────────────────────────────────────────
function confirmUnsend(url, recipientName) {
    Swal.fire({
        title: 'Unsend to Recipient?',
        html: 'Remove <strong>' + recipientName + '</strong> from this document?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, unsend',
        cancelButtonText: 'Cancel',
        customClass: { container: 'swal-over-modal' },
        didOpen: function () {
            const el = document.querySelector('.swal-over-modal');
            if (el) el.style.zIndex = 99999;
        }
    }).then(function (result) {
        if (!result.isConfirmed) return;

        Swal.fire({
            title: 'Removing…', allowOutsideClick: false,
            didOpen: function () { Swal.showLoading(); },
            customClass: { container: 'swal-over-modal' }
        });

        fetch(url, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept':        'application/json',
                'Content-Type':  'application/json',
            }
        })
        .then(r => r.json())
        .then(function (data) {
            if (data.success) {
                Swal.fire({
                    icon: 'success', title: 'Removed!',
                    text: data.message || 'Recipient removed successfully.',
                    confirmButtonColor: '#696cff',
                    customClass: { container: 'swal-over-modal' }
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    icon: 'error', title: 'Error!',
                    text: data.message || 'Failed to remove recipient.',
                    confirmButtonColor: '#d33',
                    customClass: { container: 'swal-over-modal' }
                });
            }
        })
        .catch(function () {
            Swal.fire({
                icon: 'error', title: 'Error!',
                text: 'An unexpected error occurred.',
                confirmButtonColor: '#d33',
                customClass: { container: 'swal-over-modal' }
            });
        });
    });
}
</script>