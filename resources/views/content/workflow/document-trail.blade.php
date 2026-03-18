@extends('layouts.contentNavbarLayout')

@section('title', 'Workflow - Document Trail')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h4 class="mb-1">Document Trail</h4>
                        <p class="text-muted mb-0">Track document movement and action history across recipients.</p>
                    </div>
                    <span class="badge bg-label-primary">Workflow</span>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="trailTable">
                            <thead>
                                <tr>
                                    <th>Tracking Code</th>
                                    <th>Document</th>
                                    <th>Type</th>
                                    <th>Sent By</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Date Sent</th>
                                    <th class="text-center">Trail</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($documents ?? [] as $doc)
                                <tr>
                                    <td>
                                        <code class="text-primary" style="font-size:.8rem;">
                                            {{ $doc->tracking_code }}
                                        </code>
                                    </td>
                                    <td>
                                        <div style="font-size:.875rem;font-weight:500;max-width:180px;
                                                    white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                            {{ $doc->purpose }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-info" style="font-size:.72rem;">
                                            {{ optional($doc->documentType)->type_name ?? '—' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-initial rounded-circle bg-label-primary
                                                        d-flex align-items-center justify-content-center fw-semibold"
                                                 style="width:30px;height:30px;font-size:.72rem;flex-shrink:0;">
                                                {{ strtoupper(substr(optional($doc->user->employee)->firstname ?? 'U', 0, 1)) }}{{ strtoupper(substr(optional($doc->user->employee)->lastname ?? '', 0, 1)) }}
                                            </div>
                                            <div style="font-size:.8rem;">
                                                {{ optional($doc->user->employee)->firstname }}
                                                {{ optional($doc->user->employee)->lastname }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $pColor = match(strtolower($doc->priority ?? 'normal')) {
                                                'urgent' => 'danger',
                                                'high'   => 'warning',
                                                default  => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge bg-label-{{ $pColor }}" style="font-size:.72rem;">
                                            {{ ucfirst($doc->priority ?? 'Normal') }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $sColor = match(strtolower($doc->status ?? '')) {
                                                'pending'  => 'warning',
                                                'received' => 'success',
                                                'archived' => 'secondary',
                                                default    => 'info',
                                            };
                                        @endphp
                                        <span class="badge bg-label-{{ $sColor }}" style="font-size:.72rem;">
                                            {{ ucfirst($doc->status ?? '—') }}
                                        </span>
                                    </td>
                                    <td style="font-size:.8rem;color:var(--bs-secondary-color);">
                                        {{ \Carbon\Carbon::parse($doc->created_at)->format('M j, Y') }}<br>
                                        <small>{{ \Carbon\Carbon::parse($doc->created_at)->format('h:i A') }}</small>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-icon btn-outline-primary view-trail-btn"
                                                style="width:32px;height:32px;border-radius:8px;"
                                                data-document-id="{{ $doc->document_id }}"
                                                data-tracking="{{ $doc->tracking_code }}"
                                                data-purpose="{{ $doc->purpose }}"
                                                title="View trail">
                                            <i class="bx bx-transfer-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bx bx-transfer" style="font-size:2rem;display:block;
                                           margin-bottom:8px;color:#c4c6d0;"></i>
                                        No documents found.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════
     TRAIL MODAL
══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="trailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:14px;overflow:hidden;">

            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-semibold mb-0" id="trailModalTitle">Document Trail</h5>
                    <small class="text-muted" id="trailModalCode"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body pt-3" id="trailModalBody">

                {{-- Loading --}}
                <div id="trailLoading" class="text-center py-5">
                    <div class="spinner-border text-primary" style="width:2rem;height:2rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="text-muted mt-2" style="font-size:.875rem;">Loading trail...</div>
                </div>

                {{-- Error --}}
                <div id="trailError" class="d-none">
                    <div class="alert alert-danger">
                        <i class="bx bx-error me-2"></i>
                        Failed to load trail. Please try again.
                    </div>
                </div>

                {{-- Content --}}
                <div id="trailContent" class="d-none">

                    {{-- Stats --}}
                    <div class="row g-2 mb-3" id="trailStats"></div>

                    {{-- Progress --}}
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Progress</small>
                            <small class="text-muted" id="trailProgressPct"></small>
                        </div>
                        <div class="progress" style="height:5px;border-radius:999px;">
                            <div class="progress-bar bg-success" id="trailProgressBar"
                                 style="border-radius:999px;transition:width .4s;"></div>
                        </div>
                    </div>

                    {{-- Currently with --}}
                    <div id="trailCurrentBanner" class="d-none rounded-3 p-3 mb-4 align-items-center gap-3"
                         style="background:#EFF6FF;border:1px solid #BFDBFE;">
                        <div style="width:10px;height:10px;border-radius:50%;background:#3B82F6;
                                    flex-shrink:0;animation:pulseBlue 2s infinite;"></div>
                        <div class="flex-grow-1">
                            <div style="font-size:.75rem;color:#1e40af;">Currently with</div>
                            <div class="fw-semibold" id="trailCurrentName" style="color:#1e3a8a;"></div>
                            <div id="trailCurrentDept" style="font-size:.75rem;color:#3B82F6;"></div>
                        </div>
                        <div class="text-end">
                            <div style="font-size:.72rem;color:#3B82F6;">Since</div>
                            <div id="trailCurrentSince" style="font-size:.8rem;font-weight:500;color:#1e40af;"></div>
                        </div>
                    </div>

                    {{-- Visual trail --}}
                    <div class="text-muted mb-2"
                         style="font-size:.7rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;">
                        Routing path
                    </div>
                    <div class="overflow-auto pb-2 mb-4" style="white-space:nowrap;">
                        <div id="nodeTrail" class="d-inline-flex align-items-flex-start gap-0"></div>
                    </div>

                    {{-- Detail log --}}
                    <div class="text-muted mb-2"
                         style="font-size:.7rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;">
                        Detailed trail log
                    </div>
                    <div id="detailLog"></div>

                    {{-- Legend --}}
                    <div class="d-flex gap-3 flex-wrap mt-3 pt-3"
                         style="border-top:0.5px solid var(--bs-border-color);">
                        @foreach([
                            ['#7F77DD','Sender'],
                            ['#1D9E75','Received'],
                            ['#BA7517','Forwarded'],
                            ['#3B82F6','Currently with'],
                            ['#d1d5db','Pending'],
                        ] as $l)
                        <div class="d-flex align-items-center gap-1">
                            <div style="width:8px;height:8px;border-radius:50%;background:{{ $l[0] }};"></div>
                            <span class="text-muted" style="font-size:.75rem;">{{ $l[1] }}</span>
                        </div>
                        @endforeach
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes pulseBlue {
    0%,100% { box-shadow:0 0 0 3px rgba(59,130,246,.2); }
    50%     { box-shadow:0 0 0 6px rgba(59,130,246,.05); }
}
@keyframes ring {
    0%  { transform:scale(1);opacity:.6; }
    100%{ transform:scale(1.35);opacity:0; }
}
.trail-node-wrap  { display:inline-flex;flex-direction:column;align-items:center;flex-shrink:0;width:88px;vertical-align:top; }
.trail-conn       { display:inline-flex;align-items:center;margin-top:22px;position:relative;flex-shrink:0; }
.trail-conn-lbl   { position:absolute;top:-16px;left:50%;transform:translateX(-50%);font-size:9px;white-space:nowrap;padding:1px 5px;border-radius:999px;font-weight:500; }
.trail-detail-row { display:flex;gap:12px;position:relative;padding-bottom:14px; }
.trail-detail-row:not(:last-child)::before {
    content:'';position:absolute;left:10px;top:22px;bottom:0;
    width:1.5px;background:var(--bs-border-color);z-index:0;
}
.trail-detail-dot { width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:2px solid;flex-shrink:0;margin-top:2px;z-index:1;background:#fff; }
.trail-remark     { margin-top:6px;padding:5px 10px;background:var(--bs-gray-100);border-left:2px solid #BA7517;border-radius:0 6px 6px 0;font-size:.78rem;color:var(--bs-secondary-color);font-style:italic; }
.view-trail-btn:hover { background:#696cff!important;border-color:#696cff!important;color:#fff!important; }
</style>

<script>
const C = {
    sent:      {bg:'#EEEDFE',bd:'#7F77DD',tx:'#3C3489',dt:'#7F77DD'},
    received:  {bg:'#E1F5EE',bd:'#1D9E75',tx:'#085041',dt:'#1D9E75'},
    forwarded: {bg:'#FAEEDA',bd:'#BA7517',tx:'#633806',dt:'#BA7517'},
    active:    {bg:'#EFF6FF',bd:'#3B82F6',tx:'#1e40af',dt:'#3B82F6'},
    pending:   {bg:'#f3f4f6',bd:'#d1d5db',tx:'#9ca3af',dt:'#d1d5db'},
};

const ini  = n => { if(!n)return'?'; const p=n.trim().split(' '); return (p[0][0]+(p[1]?p[1][0]:'')).toUpperCase(); };
const fmtD = ts => { if(!ts)return'—'; const d=new Date(ts); return d.toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})+' · '+d.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit'}); };
const ago  = ts => { if(!ts)return'—'; const s=Math.floor((Date.now()-new Date(ts))/1000); if(s<60)return'Just now'; if(s<3600)return Math.floor(s/60)+'m ago'; if(s<86400)return Math.floor(s/3600)+'h ago'; if(s<172800)return'Yesterday'; return Math.floor(s/86400)+'d ago'; };
const tlbl = t => ({sent:'Sender',received:'Received',forwarded:'Forwarded',active:'Waiting',pending:'Pending'}[t]||t);

function buildNodes(trail) {
    const ct = document.getElementById('nodeTrail');
    ct.innerHTML = '';
    trail.forEach((s, i) => {
        const c = C[s.type]||C.pending;
        const w = document.createElement('div');
        w.className = 'trail-node-wrap';
        const cw = document.createElement('div');
        cw.style.position = 'relative';
        if (s.type==='active') {
            const r = document.createElement('div');
            r.style.cssText = 'position:absolute;inset:-6px;border-radius:50%;border:2px solid #3B82F6;animation:ring 1.8s infinite;opacity:0;';
            cw.appendChild(r);
        }
        const ci = document.createElement('div');
        ci.style.cssText = `width:44px;height:44px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:500;border:2px solid;position:relative;background:${c.bg};border-color:${c.bd};color:${c.tx};`;
        ci.textContent = ini(s.actor_name);
        const bk = document.createElement('div');
        bk.style.cssText = `position:absolute;width:14px;height:14px;border-radius:50%;top:-2px;right:-2px;border:2px solid #fff;background:${c.dt};display:flex;align-items:center;justify-content:center;`;
        const icons = {
            sent:     `<svg width="7" height="7" viewBox="0 0 8 8" fill="none"><path d="M1 4l2 2 4-4" stroke="#fff" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
            received: `<svg width="7" height="7" viewBox="0 0 8 8" fill="none"><path d="M1 4l2 2 4-4" stroke="#fff" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
            forwarded:`<svg width="7" height="7" viewBox="0 0 8 8" fill="none"><path d="M1 4h6M4.5 2L7 4l-2.5 2" stroke="#fff" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
            active:   `<div style="width:5px;height:5px;background:#fff;border-radius:50%;"></div>`,
        };
        bk.innerHTML = icons[s.type]||'';
        ci.appendChild(bk); cw.appendChild(ci); w.appendChild(cw);
        const time = s.action_at ? new Date(s.action_at).toLocaleDateString('en-US',{month:'short',day:'numeric'})+' · '+new Date(s.action_at).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:false}) : '—';
        const lb = document.createElement('div');
        lb.style.cssText = 'text-align:center;margin-top:8px;width:80px;';
        lb.innerHTML = `<div style="font-size:11px;font-weight:500;line-height:1.3;word-break:break-word;">${s.actor_name.split(' ')[0]}</div><div style="font-size:10px;color:var(--bs-secondary-color);margin-top:1px;">${(s.department||'').substring(0,13)}</div><div style="font-size:10px;color:var(--bs-secondary-color);">${time}</div>`;
        w.appendChild(lb); ct.appendChild(w);
        if (i < trail.length - 1) {
            const cn = document.createElement('div');
            cn.className = 'trail-conn';
            const isFwd  = s.type==='forwarded';
            const isPend = s.type==='pending'||s.type==='active';
            const lc     = isFwd?'#BA7517':isPend?'#d1d5db':'#1D9E75';
            if (isFwd||s.type==='sent') {
                const lb2 = document.createElement('span');
                lb2.className = 'trail-conn-lbl';
                lb2.style.cssText = isFwd?'background:#FAEEDA;color:#633806;':'background:#E1F5EE;color:#085041;';
                lb2.textContent = isFwd?'fwd':'sent';
                cn.appendChild(lb2);
            }
            cn.innerHTML += `<div style="height:2px;width:16px;background:${lc};"></div><div style="height:2px;width:16px;background:${lc};"></div><div style="width:0;height:0;border-top:5px solid transparent;border-bottom:5px solid transparent;border-left:7px solid ${lc};"></div>`;
            ct.appendChild(cn);
        }
    });
}

function buildLog(trail) {
    const lg = document.getElementById('detailLog');
    lg.innerHTML = '';
    trail.forEach(s => {
        const c = C[s.type]||C.pending;
        const row = document.createElement('div');
        row.className = 'trail-detail-row';
        const dot = document.createElement('div');
        dot.className = 'trail-detail-dot';
        dot.style.cssText = `background:${c.bg};border-color:${c.bd};`;
        const di = {
            sent:     `<svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M1 4.5l2.5 2.5L8 2" stroke="${c.dt}" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
            received: `<svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M1 4.5l2.5 2.5L8 2" stroke="${c.dt}" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
            forwarded:`<svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M1 4.5h7M5 2l2.5 2.5L5 7" stroke="${c.dt}" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
            active:   `<div style="width:6px;height:6px;background:${c.dt};border-radius:50%;"></div>`,
        };
        dot.innerHTML = di[s.type]||'';
        const actMap = { sent:'sent the document', received:'received the document', forwarded:`forwarded to <strong>${s.forwarded_to||'—'}</strong>`, active:'currently holding', pending:'pending' };
        const con = document.createElement('div');
        con.style.cssText = 'flex:1;min-width:0;';
        con.innerHTML = `
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                <div>
                    <span style="font-size:.875rem;font-weight:500;">${s.actor_name}</span>
                    <span style="font-size:.8rem;color:var(--bs-secondary-color);margin-left:4px;">${actMap[s.type]||s.type}</span>
                    <div style="font-size:.75rem;color:var(--bs-secondary-color);">${s.department} · ${s.campus}</div>
                </div>
                <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                    <span class="badge rounded-pill" style="font-size:.7rem;background:${c.bg};color:${c.tx};">${tlbl(s.type)}</span>
                    <small style="color:var(--bs-secondary-color);">${fmtD(s.action_at)}</small>
                </div>
            </div>
            ${s.remarks ? `<div class="trail-remark">"${s.remarks}"</div>` : ''}`;
        row.appendChild(dot); row.appendChild(con); lg.appendChild(row);
    });
}

function renderTrail(data) {
    const trail = data.trail||[];
    // Stats
    const statsHtml = [
        {l:'Hops',ic:'bx-transfer-alt',cl:'primary',v:trail.length},
        {l:'Received',ic:'bx-check-circle',cl:'success',v:trail.filter(s=>s.type==='received').length},
        {l:'Forwarded',ic:'bx-share',cl:'warning',v:trail.filter(s=>s.type==='forwarded').length},
        {l:'Pending',ic:'bx-time-five',cl:'info',v:trail.filter(s=>s.type==='active'||s.type==='pending').length},
    ].map(s=>`<div class="col-6 col-sm-3"><div class="d-flex align-items-center gap-2 p-2 rounded" style="background:var(--bs-gray-100);"><i class="bx ${s.ic} text-${s.cl}" style="font-size:1.1rem;"></i><div><div class="fw-semibold" style="font-size:1rem;line-height:1;">${s.v}</div><div class="text-muted" style="font-size:.7rem;">${s.l}</div></div></div></div>`).join('');
    document.getElementById('trailStats').innerHTML = statsHtml;
    // Progress
    const done = trail.filter(s=>['sent','received','forwarded'].includes(s.type)).length;
    const pct  = trail.length > 0 ? Math.round((done/trail.length)*100) : 0;
    document.getElementById('trailProgressBar').style.width = pct+'%';
    document.getElementById('trailProgressPct').textContent = pct+'%';
    // Currently with
    const active = trail.filter(s=>s.type==='active').pop();
    if (active) {
        const b = document.getElementById('trailCurrentBanner');
        b.classList.remove('d-none'); b.classList.add('d-flex');
        document.getElementById('trailCurrentName').textContent  = active.actor_name;
        document.getElementById('trailCurrentDept').textContent  = active.department+' · '+active.campus;
        document.getElementById('trailCurrentSince').textContent = ago(active.action_at);
    }
    buildNodes(trail);
    buildLog(trail);
    document.getElementById('trailLoading').classList.add('d-none');
    document.getElementById('trailContent').classList.remove('d-none');
}

function loadTrail(id) {
    ['trailLoading','trailError','trailContent'].forEach(x=>{
        document.getElementById(x).classList.add('d-none');
    });
    document.getElementById('trailLoading').classList.remove('d-none');
    document.getElementById('trailCurrentBanner').classList.add('d-none');
    document.getElementById('trailCurrentBanner').classList.remove('d-flex');

    fetch(`/documents/${id}/trail/data`, {
        headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json',
                 'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''}
    })
    .then(r=>r.ok?r.json():Promise.reject(r.status))
    .then(renderTrail)
    .catch(()=>{
        document.getElementById('trailLoading').classList.add('d-none');
        document.getElementById('trailError').classList.remove('d-none');
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const modal = new bootstrap.Modal(document.getElementById('trailModal'));
    document.querySelectorAll('.view-trail-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('trailModalTitle').textContent = this.dataset.purpose || 'Document Trail';
            document.getElementById('trailModalCode').textContent  = this.dataset.tracking || '';
            modal.show();
            loadTrail(this.dataset.documentId);
        });
    });
});
</script>
@endsection