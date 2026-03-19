<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ── Colour map ────────────────────────────────────────────────────────────
const TC = {
    sent:      { dot: '#7F77DD', bg: '#F3F2FE', tx: '#3C3489', border: '#C9C6F0' },
    received:  { dot: '#1D9E75', bg: '#E8F8F2', tx: '#085041', border: '#9FE1CB' },
    forwarded: { dot: '#BA7517', bg: '#FDF3E3', tx: '#633806', border: '#FAC775' },
    active:    { dot: '#3B82F6', bg: '#EFF6FF', tx: '#1e40af', border: '#BFDBFE' },
    pending:   { dot: '#9ca3af', bg: '#f3f4f6', tx: '#6b7280', border: '#e5e7eb' },
};

// ── Helpers ───────────────────────────────────────────────────────────────
function fmtDate(ts) {
    if (!ts) return '—';
    const d = new Date(ts);
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
         + ' · '
         + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

function timeAgo(ts) {
    if (!ts) return '—';
    const s = Math.floor((Date.now() - new Date(ts)) / 1000);
    if (s < 60)     return 'Just now';
    if (s < 3600)   return Math.floor(s / 60) + 'm ago';
    if (s < 86400)  return Math.floor(s / 3600) + 'h ago';
    if (s < 172800) return 'Yesterday';
    return Math.floor(s / 86400) + 'd ago';
}

// ── Build trail log ───────────────────────────────────────────────────────
// unsendMap = { user_id: { url, name } } — only populated for pending recipients
// when the logged-in user is the original sender
function buildTrailLog(container, trail, unsendMap) {
    container.innerHTML = '';
    unsendMap = unsendMap || {};

    trail.forEach((step, i) => {
        const c      = TC[step.type] || TC.pending;
        const isLast = i === trail.length - 1;

        // Action text
        let actionHtml = '';
        switch (step.type) {
            case 'sent':      actionHtml = 'sent the document'; break;
            case 'received':  actionHtml = 'received the document'; break;
            case 'forwarded': actionHtml = `forwarded to <strong style="color:#1a1d3a;font-weight:600;">${step.forwarded_to || '—'}</strong>`; break;
            case 'active':    actionHtml = 'has the document now'; break;
            default:          actionHtml = 'pending';
        }

        // Badge
        let badgeLabel = { sent:'Sender', received:'Received', forwarded:'Forwarded', active:'Waiting', pending:'Pending' }[step.type] || step.type;
        if (step.type === 'active' && step.action_at) {
            badgeLabel = 'Waiting · ' + timeAgo(step.action_at);
        }

        // Dot animation for active
        const dotAnim = step.type === 'active' ? 'animation:pulseBlue 2s infinite;' : '';

        // ── Unsend button — shown only on 'active' rows where user_id is in unsendMap ──
        let unsendBtn = '';
        const unsendData = unsendMap[step.user_id];
        if ((step.type === 'active') && unsendData) {
            unsendBtn = `
                <button type="button"
                        class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1 ms-2"
                        style="font-size:.72rem;padding:2px 9px;white-space:nowrap;"
                        onclick="confirmUnsend('${unsendData.url}','${unsendData.name.replace(/'/g,"\\'")}')">
                    <i class="bx bx-x" style="font-size:.85rem;"></i> Unsend
                </button>`;
        }

        const row = document.createElement('div');
        row.className = 'tl-row';

        row.innerHTML = `
            <div class="tl-dot-col">
                <div class="tl-dot" style="background:${c.dot};${dotAnim}"></div>
                ${!isLast ? '<div class="tl-vline"></div>' : ''}
            </div>
            <div class="tl-content">
                <div class="tl-main">
                    <div class="tl-left">
                        <div>
                            <span class="tl-name">${step.actor_name}</span>
                            <span class="tl-action">${actionHtml}</span>
                        </div>
                        <div class="tl-dept">${step.department} · ${step.campus}</div>
                    </div>
                    <div class="tl-right">
                        <span class="tl-badge"
                              style="background:${c.bg};color:${c.tx};border-color:${c.border};">
                            ${badgeLabel}
                        </span>
                        <span class="tl-date">${fmtDate(step.action_at)}</span>
                        ${unsendBtn}
                    </div>
                </div>
                ${step.remarks ? `<div class="tl-remark">"${step.remarks}"</div>` : ''}
            </div>`;

        container.appendChild(row);
    });
}

// ── Render ────────────────────────────────────────────────────────────────
function renderTrail(pane, data, unsendMap) {
    buildTrailLog(pane.querySelector('.trail-log-body'), data.trail || [], unsendMap);
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