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
                    @php
                        $trailDocs = collect($documents?->items() ?? []);
                        $groupCount = $trailDocs->filter(function ($doc) {
                            return collect($doc->routes ?? [])->contains(fn ($route) => !is_null($route->group_id));
                        })->count();
                        $individualCount = $trailDocs->count() - $groupCount;
                    @endphp

                    <div class="d-flex align-items-center gap-2 flex-wrap mb-3" id="trailModeTabs">
                        <button type="button" class="btn btn-sm btn-primary trail-mode-tab active" data-mode="all">
                            All ({{ $trailDocs->count() }})
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary trail-mode-tab" data-mode="individual">
                            Individual ({{ $individualCount }})
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary trail-mode-tab" data-mode="group">
                            Group ({{ $groupCount }})
                        </button>
                    </div>

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
                                @php
                                    $isGroupSend = collect($doc->routes ?? [])->contains(fn ($route) => !is_null($route->group_id));
                                @endphp
                                <tr data-send-mode="{{ $isGroupSend ? 'group' : 'individual' }}">
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
                                            $routeIds = collect($doc->routes ?? [])->pluck('route_id')->filter()->values();
                                            $recipientStates = $routeIds->isNotEmpty()
                                                ? \App\Models\Recipient::query()
                                                    ->whereIn('route_id', $routeIds)
                                                    ->get(['action', 'receive_at'])
                                                : collect();

                                            $hasUnreceived = $recipientStates->isNotEmpty() && $recipientStates->contains(function ($r) {
                                                $action = strtolower(trim((string) $r->action));
                                                return is_null($r->receive_at) || $action === '' || $action === 'pending';
                                            });

                                            $hasForwarded = collect($doc->routes ?? [])->contains(fn ($route) => !is_null($route->forward_at))
                                                || $recipientStates->contains(function ($r) {
                                                    return strtolower(trim((string) $r->action)) === 'forwarded';
                                                });

                                            $rawStatus = strtolower((string) ($doc->status ?? ''));

                                            if ($hasForwarded || $rawStatus === 'forwarded') {
                                                $displayStatus = 'forwarded';
                                            } elseif ($hasUnreceived) {
                                                $displayStatus = 'pending';
                                            } else {
                                                $displayStatus = $rawStatus;
                                            }

                                            $sColor = match($displayStatus) {
                                                'pending'  => 'warning',
                                                'forwarded'=> 'primary',
                                                'received' => 'success',
                                                'archived' => 'secondary',
                                                default    => 'info',
                                            };
                                        @endphp
                                        <span class="badge bg-label-{{ $sColor }}" style="font-size:.72rem;">
                                            {{ $displayStatus !== '' ? ucfirst($displayStatus) : '—' }}
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
                                <tr id="trailFilterEmptyRow" class="d-none">
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bx bx-filter-alt" style="font-size:2rem;display:block;
                                           margin-bottom:8px;color:#c4c6d0;"></i>
                                        No documents match this tab.
                                    </td>
                                </tr>
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

                    {{-- Send mode --}}
                    <div id="trailSendModeBanner" class="d-none rounded-3 p-3 mb-3"
                         style="background:#F8FAFC;border:1px solid #E2E8F0;">
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <div>
                                <div class="text-muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;">Send mode</div>
                                <div id="trailSendModeText" class="fw-semibold" style="font-size:.88rem;"></div>
                                <div id="trailGroupName" class="text-muted" style="font-size:.78rem;"></div>
                            </div>
                            <span id="trailRecipientCount" class="badge bg-label-secondary" style="font-size:.72rem;"></span>
                        </div>
                    </div>

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
                    <div id="trailGroupMembersPanel" class="d-none mb-4"></div>

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
                            ['#3B82F6','Pending'],
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
@keyframes pingPending {
    0%   { transform:scale(1); opacity:.55; }
    100% { transform:scale(1.9); opacity:0; }
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
.trail-detail-dot-ping::after {
    content:'';
    position:absolute;
    width:22px;
    height:22px;
    border-radius:50%;
    background:rgba(59,130,246,.35);
    animation:pingPending 1.4s infinite;
    z-index:-1;
}
.trail-remark     { margin-top:6px;padding:5px 10px;background:var(--bs-gray-100);border-left:2px solid #BA7517;border-radius:0 6px 6px 0;font-size:.78rem;color:var(--bs-secondary-color);font-style:italic; }
.view-trail-btn:hover { background:#696cff!important;border-color:#696cff!important;color:#fff!important; }
</style>

<script>
const C = {
    sent:      {bg:'#EEEDFE',bd:'#7F77DD',tx:'#3C3489',dt:'#7F77DD'},
    received:  {bg:'#E1F5EE',bd:'#1D9E75',tx:'#085041',dt:'#1D9E75'},
    forwarded: {bg:'#FAEEDA',bd:'#BA7517',tx:'#633806',dt:'#BA7517'},
    active:    {bg:'#EFF6FF',bd:'#3B82F6',tx:'#1e40af',dt:'#3B82F6'},
    pending:   {bg:'#EFF6FF',bd:'#3B82F6',tx:'#1e40af',dt:'#3B82F6'},
};

const ini  = n => { if(!n)return'?'; const p=n.trim().split(' '); return (p[0][0]+(p[1]?p[1][0]:'')).toUpperCase(); };
const fmtD = ts => { if(!ts)return'—'; const d=new Date(ts); return d.toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'})+' · '+d.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit'}); };
const ago  = ts => { if(!ts)return'—'; const s=Math.floor((Date.now()-new Date(ts))/1000); if(s<60)return'Just now'; if(s<3600)return Math.floor(s/60)+'m ago'; if(s<86400)return Math.floor(s/3600)+'h ago'; if(s<172800)return'Yesterday'; return Math.floor(s/86400)+'d ago'; };
const tlbl = t => ({sent:'Sender',received:'Received',forwarded:'Forwarded',active:'Currently holding',pending:'Currently holding'}[t]||t);

function groupMembersFromTrail(trail) {
    const map = new Map();
    trail.forEach(s => {
        if (!['received', 'active', 'pending'].includes(s.type)) return;
        const key = (s.user_id ?? '') + '|' + (s.actor_name ?? '');
        const prev = map.get(key);
        const prevTime = prev?.action_at ? new Date(prev.action_at).getTime() : 0;
        const currTime = s.action_at ? new Date(s.action_at).getTime() : 0;
        if (!prev || currTime >= prevTime) {
            map.set(key, {
                user_id: s.user_id,
                actor_name: s.actor_name || 'Unknown User',
                department: s.department || 'N/A',
                campus: s.campus || 'N/A',
                type: s.type,
                action_at: s.action_at,
            });
        }
    });
    return Array.from(map.values());
}

function renderGroupMembersPanel(trail, meta) {
    const panel = document.getElementById('trailGroupMembersPanel');
    if (!panel) return;

    if (!meta?.is_group_send) {
        panel.classList.add('d-none');
        panel.innerHTML = '';
        return;
    }

    const members = groupMembersFromTrail(trail);
    panel.classList.add('d-none');
    panel.innerHTML = `
        <div class="rounded-3 p-3" style="background:#F8FAFC;border:1px solid #E2E8F0;">
            <div class="text-muted mb-2" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;">Group members</div>
            ${members.length === 0
                ? '<div class="text-muted" style="font-size:.82rem;">No members found.</div>'
                : members.map(m => `
                    <div class="d-flex align-items-center justify-content-between py-2" style="border-bottom:1px solid #eef2f7;">
                        <div>
                            <div style="font-size:.86rem;font-weight:600;">${m.actor_name}</div>
                            <div style="font-size:.75rem;color:var(--bs-secondary-color);">${m.department} · ${m.campus}</div>
                        </div>
                        <span class="badge rounded-pill" style="font-size:.68rem;background:${(C[m.type]||C.pending).bg};color:${(C[m.type]||C.pending).tx};">${tlbl(m.type)}</span>
                    </div>
                `).join('')}
        </div>`;
}

function toggleGroupMembersPanel() {
    const panel = document.getElementById('trailGroupMembersPanel');
    if (!panel) return;
    panel.classList.toggle('d-none');
}

function buildNodes(trail, meta = {}) {
    const ct = document.getElementById('nodeTrail');
    ct.innerHTML = '';

    if (meta.is_group_send) {
        const sender = trail.find(s => s.type === 'sent') || trail[0] || {};
        const members = groupMembersFromTrail(trail);
        const count = Number(meta.recipient_count || members.length || 0);
        const groupName = Array.isArray(meta.group_names) && meta.group_names.length
            ? meta.group_names.join(', ')
            : 'Group';

        const sNode = document.createElement('div');
        sNode.className = 'trail-node-wrap';
        sNode.innerHTML = `
            <div style="position:relative;">
                <div style="width:44px;height:44px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;border:2px solid #7F77DD;background:#EEEDFE;color:#3C3489;position:relative;">${ini(sender.actor_name || '')}
                    <div style="position:absolute;width:14px;height:14px;border-radius:50%;top:-2px;right:-2px;border:2px solid #fff;background:#7F77DD;display:flex;align-items:center;justify-content:center;">
                        <svg width="7" height="7" viewBox="0 0 8 8" fill="none"><path d="M1 4l2 2 4-4" stroke="#fff" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                </div>
            </div>
            <div style="text-align:center;margin-top:8px;width:92px;">
                <div style="font-size:11px;font-weight:600;line-height:1.3;word-break:break-word;">${(sender.actor_name || 'Sender').split(' ')[0]}</div>
                <div style="font-size:10px;color:var(--bs-secondary-color);margin-top:1px;">${sender.action_at ? new Date(sender.action_at).toLocaleDateString('en-US',{month:'short',day:'numeric'})+' · '+new Date(sender.action_at).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:false}) : '—'}</div>
            </div>`;
        ct.appendChild(sNode);

        const cn = document.createElement('div');
        cn.className = 'trail-conn';
        cn.innerHTML = `<div style="height:2px;width:16px;background:#84cc16;"></div><div style="height:2px;width:16px;background:#84cc16;"></div><div style="width:0;height:0;border-top:5px solid transparent;border-bottom:5px solid transparent;border-left:7px solid #84cc16;"></div>`;
        ct.appendChild(cn);

        const gNode = document.createElement('div');
        gNode.className = 'trail-node-wrap';
        gNode.style.width = '120px';
        gNode.innerHTML = `
            <div style="position:relative;">
                <div style="width:44px;height:44px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;border:2px solid #d1d5db;background:#f8fafc;color:#6b7280;position:relative;">
                    <i class="bx bx-group" style="font-size:18px;color:#4b5563;"></i>
                </div>
                <div style="position:absolute;top:-4px;right:-8px;min-width:18px;height:18px;padding:0 5px;border-radius:999px;background:#3B82F6;color:#fff;font-size:11px;display:flex;align-items:center;justify-content:center;">${count}</div>
            </div>
            <div style="text-align:center;margin-top:8px;width:112px;">
                <div style="font-size:11px;font-weight:600;line-height:1.3;word-break:break-word;">${groupName}</div>
                <div style="font-size:10px;color:var(--bs-secondary-color);margin-top:1px;">Group Send</div>
                <button type="button" onclick="toggleGroupMembersPanel()" class="btn btn-sm btn-outline-secondary" style="margin-top:6px;font-size:.66rem;padding:3px 8px;border-radius:8px;">View ${count} member${count===1?'':'s'}</button>
            </div>`;
        ct.appendChild(gNode);
        return;
    }

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
            const lc     = isFwd?'#BA7517':isPend?'#3B82F6':'#1D9E75';
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

function buildLog(trail, meta = {}) {
    const lg = document.getElementById('detailLog');
    lg.innerHTML = '';
    const lastIndex = trail.length - 1;
    trail.forEach((s, i) => {
        const c = C[s.type]||C.pending;
        const row = document.createElement('div');
        row.className = 'trail-detail-row';
        const dot = document.createElement('div');
        dot.className = 'trail-detail-dot';
        dot.style.cssText = `background:${c.bg};border-color:${c.bd};`;
        const shouldPing = !!meta.is_group_send && s.type === 'pending' && i === lastIndex;
        if (shouldPing) {
            dot.classList.add('trail-detail-dot-ping');
            dot.style.position = 'relative';
        }
        const di = {
            sent:     `<svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M1 4.5l2.5 2.5L8 2" stroke="${c.dt}" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
            received: `<svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M1 4.5l2.5 2.5L8 2" stroke="${c.dt}" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
            forwarded:`<svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M1 4.5h7M5 2l2.5 2.5L5 7" stroke="${c.dt}" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
            active:   `<div style="width:6px;height:6px;background:${c.dt};border-radius:50%;"></div>`,
            pending:  `<div style="width:6px;height:6px;background:${c.dt};border-radius:50%;"></div>`,
        };
        dot.innerHTML = di[s.type]||'';
        const actMap = { sent:'sent the document', received:'received the document', forwarded:`forwarded to <strong>${s.forwarded_to||'—'}</strong>`, active:'currently holding', pending:'currently holding' };
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
    const meta = data.meta||{};

    const sendModeBanner = document.getElementById('trailSendModeBanner');
    const sendModeText = document.getElementById('trailSendModeText');
    const groupName = document.getElementById('trailGroupName');
    const recipientCount = document.getElementById('trailRecipientCount');
    const isGroup = !!meta.is_group_send;
    const count = Number(meta.recipient_count || 0);

    sendModeBanner.classList.remove('d-none');
    sendModeText.textContent = isGroup ? 'Group Send' : 'Individual Send';
    groupName.textContent = isGroup ? (meta.group_names?.join(', ') || 'Target group') : 'Direct recipients';
    recipientCount.textContent = `${count} Recipient${count === 1 ? '' : 's'}`;

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
    const active = trail.filter(s=>s.type==='active'||s.type==='pending').pop();
    if (active) {
        const b = document.getElementById('trailCurrentBanner');
        b.classList.remove('d-none'); b.classList.add('d-flex');
        document.getElementById('trailCurrentName').textContent  = active.actor_name;
        document.getElementById('trailCurrentDept').textContent  = active.department+' · '+active.campus;
        document.getElementById('trailCurrentSince').textContent = ago(active.action_at);
    }
    buildNodes(trail, meta);
    renderGroupMembersPanel(trail, meta);
    buildLog(trail, meta);
    document.getElementById('trailLoading').classList.add('d-none');
    document.getElementById('trailContent').classList.remove('d-none');
}

function loadTrail(id) {
    ['trailLoading','trailError','trailContent'].forEach(x=>{
        document.getElementById(x).classList.add('d-none');
    });
    document.getElementById('trailSendModeBanner').classList.add('d-none');
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

    const modeTabs = Array.from(document.querySelectorAll('.trail-mode-tab'));
    const tableBody = document.querySelector('#trailTable tbody');
    const emptyRow = document.getElementById('trailFilterEmptyRow');
    const dataRows = tableBody ? Array.from(tableBody.querySelectorAll('tr[data-send-mode]')) : [];

    function applyModeFilter(mode) {
        let visible = 0;
        dataRows.forEach(row => {
            const matches = mode === 'all' || row.dataset.sendMode === mode;
            row.classList.toggle('d-none', !matches);
            if (matches) visible += 1;
        });

        if (emptyRow) {
            emptyRow.classList.toggle('d-none', visible > 0);
        }

        modeTabs.forEach(tab => {
            const active = tab.dataset.mode === mode;
            tab.classList.toggle('active', active);
            tab.classList.toggle('btn-primary', active);
            tab.classList.toggle('btn-outline-primary', !active);
        });
    }

    modeTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            applyModeFilter(this.dataset.mode || 'all');
        });
    });

    applyModeFilter('all');

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