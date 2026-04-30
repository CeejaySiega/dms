@extends('layouts.contentNavbarLayout')

@section('title', 'Document Trail')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="mb-4">
                <h4 class="fw-bold mb-2"><i class="bx bx-transfer-alt me-2"></i>Document Trail</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard-analytics') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item active">Document Trail</li>
                    </ol>
                </nav>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div></div>
                </div>

                <div class="card-body">
                    <form method="GET" action="{{ route('trail.document-trail') }}" class="row g-2 mb-3 align-items-center">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Search tracking code, file, purpose...">
                        </div>
                        <div class="col-md-3">
                            <select name="send_mode" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All Modes</option>
                                <option value="individual" {{ request('send_mode') === 'individual' ? 'selected' : '' }}>Individual</option>
                                <option value="group" {{ request('send_mode') === 'group' ? 'selected' : '' }}>Group</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                                @foreach([10, 15, 25, 50] as $len)
                                    <option value="{{ $len }}" {{ (int) request('per_page', 15) === $len ? 'selected' : '' }}>{{ $len }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-outline-primary">Search</button>
                            @if(request('search') || request('send_mode') || request('per_page'))
                                <a href="{{ route('trail.document-trail') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                            @endif
                        </div>
                    </form>

                    <div class="d-flex align-items-center gap-4 flex-wrap mb-3" style="font-size:.85rem;">
                        <div class="d-flex align-items-center gap-2">
                            <span style="width:12px;height:12px;border-radius:50%;display:inline-block;background:#696cff;"></span>
                            <span class="text-muted">Group Send</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span style="width:12px;height:12px;border-radius:50%;display:inline-block;background:#8592a3;"></span>
                            <span class="text-muted">Individual Send</span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="trailTable">
                            <thead>
                                <tr>
                                    <th>Tracking Code</th>
                                    <th>Document</th>
                                    <th>Type</th>
                                    <th>Sent By</th>
                                    <th>Sent To</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Date Sent</th>
                                    <th class="text-center">Trail</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($documents ?? [] as $doc)
                                @php
                                    $routes = collect($doc->routes ?? []);
                                    $isGroupSend = $routes->contains(fn ($route) => !is_null($route->group_id));
                                    $routeIds = $routes->pluck('route_id')->filter()->values();

                                    if ($isGroupSend) {
                                        $groupIds = $routes->pluck('group_id')->filter()->unique()->values();
                                        $sentToText = $groupIds->isNotEmpty()
                                            ? \App\Models\Group::query()
                                                ->whereIn('group_id', $groupIds)
                                                ->pluck('position')
                                                ->filter()
                                                ->unique()
                                                ->join(', ')
                                            : 'Group';
                                    } else {
                                        $recipientNames = $routeIds->isNotEmpty()
                                            ? \App\Models\Recipient::query()
                                                ->with('user.employee')
                                                ->whereIn('route_id', $routeIds)
                                                ->whereNull('deleted_at')
                                                ->get()
                                                ->map(function ($recipient) {
                                                    $employee = optional($recipient->user)->employee;
                                                    if ($employee) {
                                                        return trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? ''));
                                                    }
                                                    return optional($recipient->user)->name;
                                                })
                                                ->filter()
                                                ->unique()
                                                ->values()
                                            : collect();

                                        if ($recipientNames->isEmpty()) {
                                            $recipientNames = $routes
                                                ->map(function ($route) {
                                                    $receiver = optional($route)->receiverUser;
                                                    $employee = optional($receiver)->employee;
                                                    if ($employee) {
                                                        return trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? ''));
                                                    }
                                                    return optional($receiver)->name;
                                                })
                                                ->filter()
                                                ->unique()
                                                ->values();
                                        }

                                        $sentToText = $recipientNames->isNotEmpty()
                                            ? $recipientNames->join(', ')
                                            : 'Individual';
                                    }

                                    if ($sentToText === '') {
                                        $sentToText = $isGroupSend ? 'Group' : 'Individual';
                                    }
                                @endphp
                                <tr data-send-mode="{{ $isGroupSend ? 'group' : 'individual' }}">
                                    <td>
                                        <code class="text-primary" style="font-size:.8rem;">{{ $doc->tracking_code }}</code>
                                    </td>
                                    <td>
                                        <div style="font-size:.875rem;font-weight:500;max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
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
                                            <div class="avatar-initial rounded-circle bg-label-primary d-flex align-items-center justify-content-center fw-semibold"
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
                                        @if($isGroupSend)
                                            <span class="badge bg-label-primary" style="font-size:.72rem;max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $sentToText }}">{{ $sentToText }}</span>
                                        @else
                                            <span class="badge bg-label-secondary" style="font-size:.72rem;max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $sentToText }}">{{ $sentToText }}</span>
                                        @endif
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
                                                'pending'   => 'warning',
                                                'forwarded' => 'primary',
                                                'received'  => 'success',
                                                'archived'  => 'secondary',
                                                default     => 'info',
                                            };
                                        @endphp
                                        <span class="badge bg-label-{{ $sColor }}" style="font-size:.72rem;">
                                            {{ $displayStatus !== '' ? ucfirst($displayStatus) : '—' }}
                                        </span>
                                    </td>
                                    <td style="font-size:.8rem;color:var(--bs-secondary-color);">
                                        {{ \Carbon\Carbon::parse($doc->created_at)->format('M j, Y') }} · {{ \Carbon\Carbon::parse($doc->created_at)->format('h:i A') }}
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
                                    <td colspan="9" class="text-center text-muted py-5">
                                        <i class="bx bx-transfer" style="font-size:2rem;display:block;margin-bottom:8px;color:#c4c6d0;"></i>
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

                    {{-- Send mode banner --}}
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
                    <div class="mb-4">
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

                    {{-- Trail log label --}}
                    <div class="text-muted mb-3"
                         style="font-size:.7rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;">
                        Trail log
                    </div>

                    {{-- Detail log (group members render inline here) --}}
                    <div id="detailLog"></div>

                    {{-- Legend --}}
                    <div class="d-flex gap-3 flex-wrap mt-3 pt-3"
                         style="border-top:0.5px solid var(--bs-border-color);">
                        @foreach([
                            ['#7F77DD','Sender'],
                            ['#1D9E75','Received'],
                            ['#BA7517','Forwarded'],
                            ['#696cff','Group'],
                            ['#3B82F6','Currently with / Pending'],
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
    50%      { box-shadow:0 0 0 6px rgba(59,130,246,.05); }
}
@keyframes pingPending {
    0%   { transform:scale(1); opacity:.55; }
    100% { transform:scale(1.9); opacity:0; }
}

/* ── Trail log timeline ─────────────────────────── */
.trail-detail-row {
    display:flex;
    gap:12px;
    position:relative;
    padding-bottom:14px;
}
.trail-detail-row:not(:last-child)::before {
    content:'';
    position:absolute;
    left:10px;
    top:24px;
    bottom:0;
    width:1.5px;
    background:var(--bs-border-color);
    z-index:0;
}
/* Group node row — extend the line through the nested card */
.trail-detail-row.is-group:not(:last-child)::before {
    top:24px;
}
.trail-detail-dot {
    width:22px;
    height:22px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    border:2px solid;
    flex-shrink:0;
    margin-top:2px;
    z-index:1;
    background:#fff;
}
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
.trail-remark {
    margin-top:6px;
    padding:5px 10px;
    background:var(--bs-gray-100);
    border-left:2px solid #BA7517;
    border-radius:0 6px 6px 0;
    font-size:.78rem;
    color:var(--bs-secondary-color);
    font-style:italic;
}

/* ── Group members nested card ──────────────────── */
.group-members-card {
    margin-top:10px;
    border:1px solid #E2E8F0;
    border-radius:10px;
    overflow:hidden;
    background:#fff;
}
.group-members-card-header {
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:8px 12px;
    background:#F8FAFC;
    border-bottom:1px solid #E2E8F0;
    cursor:pointer;
    user-select:none;
}
.group-members-card-header:hover { background:#EEF2F6; }
.group-members-card-body { padding:0; }
.group-member-row {
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:9px 12px;
    gap:8px;
}
.group-member-row:not(:last-child) { border-bottom:1px solid #F1F5F9; }
.group-member-avatar {
    width:28px;
    height:28px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:10px;
    font-weight:600;
    flex-shrink:0;
}
.group-members-chevron {
    transition:transform .25s;
    font-size:14px;
    color:#94a3b8;
}
.group-members-chevron.open { transform:rotate(180deg); }

/* ── Table fixes ─────────────────────────────────── */
#trailTable th {
    text-align:left !important;
    white-space:nowrap !important;
    overflow:hidden !important;
    text-overflow:ellipsis !important;
    vertical-align:middle !important;
}
#trailTable th.text-center { text-align:center !important; }
#trailTable td {
    text-align:left !important;
    padding-left:10px !important;
    white-space:nowrap !important;
    overflow:hidden !important;
    text-overflow:ellipsis !important;
    vertical-align:middle !important;
}
#trailTable td code,
#trailTable td .fw-semibold,
#trailTable td .badge,
#trailTable td small { display:inline-block; white-space:nowrap; vertical-align:middle; }
#trailTable td .d-flex.align-items-center { justify-content:flex-start; }
#trailTable td.text-center { text-align:center !important; padding-left:0 !important; }
.view-trail-btn:hover { background:#696cff!important;border-color:#696cff!important;color:#fff!important; }
</style>

<script>
const C = {
    sent:      { bg:'#EEEDFE', bd:'#7F77DD', tx:'#3C3489', dt:'#7F77DD' },
    received:  { bg:'#E1F5EE', bd:'#1D9E75', tx:'#085041', dt:'#1D9E75' },
    forwarded: { bg:'#FAEEDA', bd:'#BA7517', tx:'#633806', dt:'#BA7517' },
    group:     { bg:'#EDEDFF', bd:'#696cff', tx:'#3730a3', dt:'#696cff' },
    active:    { bg:'#EFF6FF', bd:'#3B82F6', tx:'#1e40af', dt:'#3B82F6' },
    pending:   { bg:'#EFF6FF', bd:'#3B82F6', tx:'#1e40af', dt:'#3B82F6' },
};

const ini  = n => { if (!n) return '?'; const p = n.trim().split(' '); return (p[0][0] + (p[1] ? p[1][0] : '')).toUpperCase(); };
const fmtD = ts => { if (!ts) return '—'; const d = new Date(ts); return d.toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'}) + ' · ' + d.toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit'}); };
const ago  = ts => { if (!ts) return '—'; const s = Math.floor((Date.now() - new Date(ts)) / 1000); if (s < 60) return 'Just now'; if (s < 3600) return Math.floor(s/60) + 'm ago'; if (s < 86400) return Math.floor(s/3600) + 'h ago'; if (s < 172800) return 'Yesterday'; return Math.floor(s/86400) + 'd ago'; };
const tlbl = t => ({ sent:'Sender', received:'Received', forwarded:'Forwarded', group:'Group', active:'Currently holding', pending:'Currently holding' }[t] || t);

/* ── Collect unique group members from trail ──── */
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
                user_id:    s.user_id,
                actor_name: s.actor_name || 'Unknown User',
                department: s.department || 'N/A',
                campus:     s.campus || 'N/A',
                type:       s.type,
                action_at:  s.action_at,
            });
        }
    });
    return Array.from(map.values());
}

/* ── Inline group members card ──────────────────── */
function buildGroupMembersCard(members, groupName) {
    const count = members.length;

    const rows = members.map(m => {
        const c = C[m.type] || C.pending;
        const initials = ini(m.actor_name);
        return `
            <div class="group-member-row">
                <div class="d-flex align-items-center gap-2">
                    <div class="group-member-avatar" style="background:${c.bg};color:${c.tx};border:1.5px solid ${c.bd};">
                        ${initials}
                    </div>
                    <div>
                        <div style="font-size:.83rem;font-weight:500;line-height:1.3;">${m.actor_name}</div>
                        <div style="font-size:.72rem;color:var(--bs-secondary-color);">${m.department} · ${m.campus}</div>
                    </div>
                </div>
                <span class="badge rounded-pill" style="font-size:.68rem;background:${c.bg};color:${c.tx};border:1px solid ${c.bd};">
                    ${tlbl(m.type)}
                </span>
            </div>`;
    }).join('');

    const empty = `<div class="px-3 py-3 text-muted" style="font-size:.82rem;">No members found.</div>`;
    const cardId = 'gmc-' + Math.random().toString(36).slice(2);

    return `
        <div class="group-members-card">
            <div class="group-members-card-header" onclick="toggleGroupCard('${cardId}')">
                <div class="d-flex align-items-center gap-2">
                    <i class="bx bx-group" style="font-size:14px;color:#696cff;"></i>
                    <span style="font-size:.78rem;font-weight:600;color:#3730a3;">${groupName}</span>
                    <span class="badge rounded-pill" style="font-size:.65rem;background:#EDEDFF;color:#3730a3;border:1px solid #696cff;">${count} member${count === 1 ? '' : 's'}</span>
                </div>
                <i class="bx bx-chevron-down group-members-chevron open" id="chev-${cardId}"></i>
            </div>
            <div class="group-members-card-body" id="${cardId}">
                ${count > 0 ? rows : empty}
            </div>
        </div>`;
}

function toggleGroupCard(id) {
    const body = document.getElementById(id);
    const chev = document.getElementById('chev-' + id);
    if (!body || !chev) return;
    const isOpen = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : '';
    chev.classList.toggle('open', !isOpen);
}

/* ── Build detail log ───────────────────────────── */
function buildLog(trail, meta = {}) {
    const lg = document.getElementById('detailLog');
    lg.innerHTML = '';

    const isGroup = !!meta.is_group_send;
    const groupName = Array.isArray(meta.group_names) && meta.group_names.length
        ? meta.group_names.join(', ')
        : 'Group';

    if (isGroup) {
        /* Group send: show sender → group node (with inline members) */
        const sender = trail.find(s => s.type === 'sent') || {};
        const members = groupMembersFromTrail(trail);
        const lastIndex = trail.length - 1;

        // ── Sender row ──
        const sC = C.sent;
        const sRow = document.createElement('div');
        sRow.className = 'trail-detail-row';
        sRow.innerHTML = `
            <div class="trail-detail-dot" style="background:${sC.bg};border-color:${sC.bd};">
                <svg width="9" height="9" viewBox="0 0 9 9" fill="none">
                    <path d="M1 4.5l2.5 2.5L8 2" stroke="${sC.dt}" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                    <div>
                        <span style="font-size:.875rem;font-weight:500;">${sender.actor_name || '—'}</span>
                        <span style="font-size:.8rem;color:var(--bs-secondary-color);margin-left:4px;">sent the document to <strong>${groupName}</strong> group</span>
                        <div style="font-size:.75rem;color:var(--bs-secondary-color);">${sender.department || ''} · ${sender.campus || ''}</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                        <span class="badge rounded-pill" style="font-size:.7rem;background:${sC.bg};color:${sC.tx};">Sender</span>
                        <small style="color:var(--bs-secondary-color);">${fmtD(sender.action_at)}</small>
                    </div>
                </div>
            </div>`;
        lg.appendChild(sRow);

        // ── Group node row (with inline members card) ──
        const gC = C.group;
        const gRow = document.createElement('div');
        gRow.className = 'trail-detail-row is-group';
        gRow.innerHTML = `
            <div class="trail-detail-dot" style="background:${gC.bg};border-color:${gC.bd};">
                <i class="bx bx-group" style="font-size:11px;color:${gC.dt};"></i>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                    <div>
                        <span style="font-size:.875rem;font-weight:500;">${groupName}</span>
                        <span style="font-size:.8rem;color:var(--bs-secondary-color);margin-left:4px;">document distributed to group</span>
                        <div style="font-size:.75rem;color:var(--bs-secondary-color);">${fmtD(sender.action_at)}</div>
                    </div>
                    <span class="badge rounded-pill" style="font-size:.7rem;background:${gC.bg};color:${gC.tx};border:1px solid ${gC.bd};">Group</span>
                </div>
                ${buildGroupMembersCard(members, groupName)}
            </div>`;
        lg.appendChild(gRow);

    } else {
        /* Individual send: render each trail step normally */
        const lastIndex = trail.length - 1;
        trail.forEach((s, i) => {
            const c = C[s.type] || C.pending;
            const row = document.createElement('div');
            row.className = 'trail-detail-row';

            const dot = document.createElement('div');
            dot.className = 'trail-detail-dot';
            dot.style.cssText = `background:${c.bg};border-color:${c.bd};`;

            const shouldPing = s.type === 'pending' && i === lastIndex;
            if (shouldPing) {
                dot.classList.add('trail-detail-dot-ping');
                dot.style.position = 'relative';
            }

            const icons = {
                sent:     `<svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M1 4.5l2.5 2.5L8 2" stroke="${c.dt}" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
                received: `<svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M1 4.5l2.5 2.5L8 2" stroke="${c.dt}" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
                forwarded:`<svg width="9" height="9" viewBox="0 0 9 9" fill="none"><path d="M1 4.5h7M5 2l2.5 2.5L5 7" stroke="${c.dt}" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
                active:   `<div style="width:6px;height:6px;background:${c.dt};border-radius:50%;"></div>`,
                pending:  `<div style="width:6px;height:6px;background:${c.dt};border-radius:50%;"></div>`,
            };
            dot.innerHTML = icons[s.type] || '';

            const actMap = {
                sent:     'sent the document',
                received: 'received the document',
                forwarded:`forwarded to <strong>${s.forwarded_to || '—'}</strong>`,
                active:   'currently holding',
                pending:  'currently holding',
            };

            const con = document.createElement('div');
            con.style.cssText = 'flex:1;min-width:0;';
            con.innerHTML = `
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                    <div>
                        <span style="font-size:.875rem;font-weight:500;">${s.actor_name}</span>
                        <span style="font-size:.8rem;color:var(--bs-secondary-color);margin-left:4px;">${actMap[s.type] || s.type}</span>
                        <div style="font-size:.75rem;color:var(--bs-secondary-color);">${s.department} · ${s.campus}</div>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                        <span class="badge rounded-pill" style="font-size:.7rem;background:${c.bg};color:${c.tx};">${tlbl(s.type)}</span>
                        <small style="color:var(--bs-secondary-color);">${fmtD(s.action_at)}</small>
                    </div>
                </div>
                ${s.remarks ? `<div class="trail-remark">"${s.remarks}"</div>` : ''}`;

            row.appendChild(dot);
            row.appendChild(con);
            lg.appendChild(row);
        });
    }
}

/* ── Render everything ──────────────────────────── */
function renderTrail(data) {
    const trail = data.trail || [];
    const meta  = data.meta  || {};

    // Send mode banner
    const isGroup = !!meta.is_group_send;
    const count   = Number(meta.recipient_count || 0);
    const sendModeBanner = document.getElementById('trailSendModeBanner');
    sendModeBanner.classList.remove('d-none');
    document.getElementById('trailSendModeText').textContent   = isGroup ? 'Group Send' : 'Individual Send';
    document.getElementById('trailGroupName').textContent      = isGroup ? (meta.group_names?.join(', ') || 'Target group') : 'Direct recipients';
    document.getElementById('trailRecipientCount').textContent = `${count} Recipient${count === 1 ? '' : 's'}`;

    // Stats
    document.getElementById('trailStats').innerHTML = [
        { l:'Hops',      ic:'bx-transfer-alt', cl:'primary', v: trail.length },
        { l:'Received',  ic:'bx-check-circle', cl:'success', v: trail.filter(s => s.type === 'received').length },
        { l:'Forwarded', ic:'bx-share',        cl:'warning', v: trail.filter(s => s.type === 'forwarded').length },
        { l:'Pending',   ic:'bx-time-five',    cl:'info',    v: trail.filter(s => s.type === 'active' || s.type === 'pending').length },
    ].map(s => `
        <div class="col-6 col-sm-3">
            <div class="d-flex align-items-center gap-2 p-2 rounded" style="background:var(--bs-gray-100);">
                <i class="bx ${s.ic} text-${s.cl}" style="font-size:1.1rem;"></i>
                <div>
                    <div class="fw-semibold" style="font-size:1rem;line-height:1;">${s.v}</div>
                    <div class="text-muted" style="font-size:.7rem;">${s.l}</div>
                </div>
            </div>
        </div>`).join('');

    // Progress
    const done = trail.filter(s => ['sent','received','forwarded'].includes(s.type)).length;
    const pct  = trail.length > 0 ? Math.round((done / trail.length) * 100) : 0;
    document.getElementById('trailProgressBar').style.width = pct + '%';
    document.getElementById('trailProgressPct').textContent  = pct + '%';

    // Currently with
    const active = trail.filter(s => s.type === 'active' || s.type === 'pending').pop();
    if (active) {
        const b = document.getElementById('trailCurrentBanner');
        b.classList.remove('d-none');
        b.classList.add('d-flex');
        document.getElementById('trailCurrentName').textContent  = active.actor_name;
        document.getElementById('trailCurrentDept').textContent  = active.department + ' · ' + active.campus;
        document.getElementById('trailCurrentSince').textContent = ago(active.action_at);
    }

    buildLog(trail, meta);

    document.getElementById('trailLoading').classList.add('d-none');
    document.getElementById('trailContent').classList.remove('d-none');
}

/* ── Load trail from API ───────────────────────── */
function loadTrail(id) {
    ['trailLoading','trailError','trailContent'].forEach(x => {
        document.getElementById(x).classList.add('d-none');
    });
    document.getElementById('trailSendModeBanner').classList.add('d-none');
    document.getElementById('trailLoading').classList.remove('d-none');

    const cb = document.getElementById('trailCurrentBanner');
    cb.classList.add('d-none');
    cb.classList.remove('d-flex');

    fetch(`/documents/${id}/trail/data`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
        }
    })
    .then(r => r.ok ? r.json() : Promise.reject(r.status))
    .then(renderTrail)
    .catch(() => {
        document.getElementById('trailLoading').classList.add('d-none');
        document.getElementById('trailError').classList.remove('d-none');
    });
}

/* ── Bootstrap modal wiring ─────────────────────── */
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