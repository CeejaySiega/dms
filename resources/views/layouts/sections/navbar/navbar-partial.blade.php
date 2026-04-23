@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
@endphp

<!--  Brand demo (display only for navbar-full and hide on below xl) -->
@if(isset($navbarFull))
<div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-4">
    <a href="{{url('/')}}" class="app-brand-link gap-2">
        <span class="app-brand-logo demo">@include('_partials.macros')</span>
        <span class="app-brand-text demo menu-text fw-bold text-heading">{{config('variables.templateName')}}</span>
    </a>
</div>
@else
@if(!isset($navbarHideToggle))
<div class="layout-menu-toggle navbar-nav align-items-center d-flex d-lg-none me-4">
    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
        <i class="icon-base bx bx-menu icon-md"></i>
    </a>
</div>
@endif
<div class="navbar-brand d-flex align-items-center">
    <span class="app-brand-text menu-text text-primary text-heading">Document Management System</span>
</div>
@endif

<div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
    <ul class="navbar-nav flex-row align-items-center ms-auto">

        {{-- ── SEARCH ── --}}
        <li class="nav-item me-4">
            <a class="nav-link p-0" href="javascript:void(0);" id="navbarSearchToggle"
               title="Search documents" style="position:relative;">
                <i class="icon-base bx bx-search icon-md"></i>
            </a>
        </li>

        {{-- ── SEARCH MODAL OVERLAY ── --}}
        <div id="searchOverlay">
            <div id="searchBox">
                <div id="searchInputWrap">
                    <i class="bx bx-search" id="searchBoxIcon"></i>
                    <input type="text"
                           id="navbarSearchInput"
                           placeholder="Search documents…"
                           autocomplete="off"
                           aria-label="Search documents" />
                    <button id="searchCloseBtn" title="Close">&times;</button>
                </div>
                <div id="navbarSearchResults"></div>
            </div>
        </div>

        {{-- ── NOTIFICATIONS ── --}}
        <li class="nav-item navbar-dropdown dropdown-notifications me-4">
            <a class="nav-link dropdown-toggle hide-arrow p-0"
               href="javascript:void(0);"
               data-bs-toggle="dropdown"
                    data-bs-display="static"
               data-bs-auto-close="outside"
               aria-expanded="false"
               style="position: relative;">
                <i class="icon-base bx bx-bell icon-md"></i>
                <span class="badge bg-danger rounded-pill badge-notifications"
                      id="pendingBadge"
                      style="display:none; position:absolute; top:-8px; right:-8px; font-size:0.65rem; padding:0.25rem 0.45rem; min-width:18px; text-align:center;">
                    0
                </span>
            </a>

            <ul class="dropdown-menu dropdown-menu-end p-0 notif-dropdown-menu"
                style="min-width:400px; max-width:400px; border-radius:0.85rem; overflow:hidden; box-shadow:0 8px 32px rgba(26,29,58,0.16);">

                {{-- Header --}}
                <li class="notif-header d-flex align-items-center justify-content-between px-4 pt-3 pb-2">
                    <div class="d-flex align-items-center gap-2">
                        <h6 class="mb-0 fw-bold" style="font-size:0.95rem;">Notifications</h6>
                        <span class="badge rounded-pill bg-label-primary px-2 py-1"
                              id="docCount"
                              style="font-size:0.72rem;">
                            0 New
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        {{-- Mark all read --}}
                        <button class="notif-action-btn" id="markAllReadBtn" title="Mark all as read" onclick="markAllRead()">
                            <i class="bx bx-check-double"></i>
                        </button>
                        {{-- Go to inbox --}}
                        <a href="{{ route('documents.incoming') }}" class="notif-action-btn" title="Open inbox">
                            <i class="bx bx-envelope"></i>
                        </a>
                    </div>
                </li>

                {{-- Scrollable list --}}
                <li id="notificationsList" style="max-height:360px; overflow-y:auto;">
                    <div class="text-center py-5 text-muted" id="emptyState">
                        <i class="bx bxs-bell-off" style="font-size:2.2rem; color:#c4c6d0;"></i>
                        <p class="mt-2 mb-0" style="font-size:0.85rem;">No new notifications</p>
                    </div>
                </li>

            </ul>
        </li>

        {{-- ── USER DROPDOWN ── --}}
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
            @php
                $employee  = Auth::user()->employee;
                $userName  = $employee
                    ? $employee->firstname . ' ' . $employee->lastname
                    : Auth::user()->name ?? Auth::user()->email;
                $initials  = strtoupper(
                    substr($userName, 0, 1) .
                    (strpos($userName, ' ') !== false
                        ? substr($userName, strpos($userName, ' ') + 1, 1)
                        : '')
                );
            @endphp
            <a class="nav-link dropdown-toggle hide-arrow p-0"
               href="javascript:void(0);"
               data-bs-toggle="dropdown">
                <div class="avatar avatar-online">
                    <span class="avatar-initial rounded-circle bg-label-primary fw-bold d-flex align-items-center justify-content-center"
                          style="width:40px; height:40px; cursor:pointer;">
                        {{ $initials }}
                    </span>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="javascript:void(0);">
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-3">
                                <div class="avatar avatar-online">
                                    <span class="avatar-initial rounded-circle bg-label-primary fw-bold d-flex align-items-center justify-content-center"
                                          style="width:40px; height:40px;">
                                        {{ $initials }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                @php
                                    $employee     = Auth::user()->employee;
                                    $employeeName = $employee
                                        ? $employee->firstname . ' ' . $employee->lastname
                                        : Auth::user()->name ?? Auth::user()->email;
                                @endphp
                                <h6 class="mb-0">{{ $employeeName }}</h6>
                                <small class="text-muted">{{ Auth::user()->email }}</small>
                            </div>
                        </div>
                    </a>
                </li>
                <li><div class="dropdown-divider my-1"></div></li>
                <li>
                    <a class="dropdown-item" href="{{ route('profile.view') }}">
                        <i class="icon-base bx bx-user icon-md me-3"></i><span>My Profile</span>
                    </a>
                </li>
                {{-- <li>
                    <a class="dropdown-item" href="javascript:void(0);">
                        <i class="icon-base bx bx-cog icon-md me-3"></i><span>Settings</span>
                    </a>
                </li> --}}
                <li><div class="dropdown-divider my-1"></div></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="icon-base bx bx-power-off icon-md me-3"></i><span>Log Out</span>
                        </button>
                    </form>
                </li>
            </ul>
        </li>

    </ul>
</div>

<style>
/* ── Search overlay ── */
#searchOverlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(26,29,58,0.45);
    backdrop-filter: blur(3px);
    z-index: 99998;
    align-items: flex-start;
    justify-content: center;
    padding-top: 80px;
}
#searchOverlay.show { display: flex; }
#searchBox {
    width: 100%;
    max-width: 560px;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 16px 48px rgba(26,29,58,0.18);
    overflow: hidden;
    animation: searchSlideIn .18s ease;
}
@keyframes searchSlideIn {
    from { opacity: 0; transform: translateY(-12px); }
    to   { opacity: 1; transform: translateY(0); }
}
#searchInputWrap {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 18px;
    border-bottom: 1px solid #f0f2f7;
}
#searchBoxIcon {
    font-size: 1.15rem;
    color: #696cff;
    flex-shrink: 0;
}
#navbarSearchInput {
    flex: 1;
    border: none;
    outline: none;
    font-size: 0.9375rem;
    color: #1a1d3a;
    background: transparent;
}
#navbarSearchInput::placeholder { color: #b0b4d8; }
#searchCloseBtn {
    background: none;
    border: none;
    font-size: 1.4rem;
    color: #b0b4d8;
    cursor: pointer;
    line-height: 1;
    padding: 0 2px;
    transition: color .15s;
}
#searchCloseBtn:hover { color: #696cff; }

/* ── Search results ── */
#navbarSearchResults {
    max-height: 320px;
    overflow-y: auto;
}
.search-result-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 18px;
    border-bottom: 1px solid #f0f2f7;
    text-decoration: none;
    color: #1a1d3a;
    font-size: 0.8375rem;
    transition: background .12s;
}
.search-result-item:last-child { border-bottom: none; }
.search-result-item:hover { background: #f5f6ff; }
.search-result-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    background: #eef2ff;
    color: #696cff;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}
.search-result-title { font-weight: 600; font-size: 0.83rem; }
.search-result-sub   { font-size: 0.74rem; color: #8b90b8; }
.search-empty        { padding: 28px 20px; text-align: center; color: #b0b4d8; font-size: 0.83rem; }

/* ── Header & Footer ── */
.notif-header {
    border-bottom: 1px solid #f0f2f7;
    background: #fff;
}

/* Keep notifications dropdown pinned below the bell icon */
.dropdown-notifications {
    position: relative;
}

.dropdown-notifications .notif-dropdown-menu {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    left: auto;
    transform: none !important;
}

@media (max-width: 767.98px) {
    .navbar-brand .app-brand-text {
        font-size: 1rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 52vw;
        display: inline-block;
    }

    #searchOverlay {
        padding-top: 56px;
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    #searchBox {
        max-width: 100%;
        border-radius: 12px;
    }

    #searchInputWrap {
        padding: 12px 12px;
    }

    #navbarSearchResults {
        max-height: 58vh;
    }

    .dropdown-notifications .notif-dropdown-menu {
        width: min(92vw, 400px);
        min-width: min(92vw, 400px) !important;
        max-width: min(92vw, 400px) !important;
        right: -0.25rem;
        left: auto;
    }

    #notificationsList {
        max-height: 52vh !important;
    }

    .notif-sub {
        max-width: 56vw;
    }
}

@media (max-width: 420px) {
    .navbar-brand .app-brand-text {
        max-width: 45vw;
        font-size: 0.92rem;
    }

    .dropdown-notifications .notif-dropdown-menu {
        width: calc(100vw - 1rem);
        min-width: calc(100vw - 1rem) !important;
        max-width: calc(100vw - 1rem) !important;
        right: -0.5rem;
    }

    .notif-header {
        padding-left: 0.75rem !important;
        padding-right: 0.75rem !important;
    }
}
/* ── Header action icon buttons ── */
.notif-action-btn {
    width: 30px; height: 30px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    background: #f8f9fa;
    color: #6c757d;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    text-decoration: none;
    transition: background .15s, color .15s, border-color .15s;
    line-height: 1;
}
.notif-action-btn:hover {
    background: #696cff;
    color: #fff;
    border-color: #696cff;
}

/* ── Section label ── */
.notif-section-label {
    padding: 10px 16px 4px;
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: .09em;
    text-transform: uppercase;
    color: #b0b4d8;
    background: #fafbff;
    border-bottom: 1px solid #f0f2f7;
}

/* ── Notification item base ── */
#notificationsList a.notif-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #f0f2f7;
    border-left: 3px solid transparent;        /* ← placeholder for unread accent */
    text-decoration: none;
    transition: background .12s, border-left-color .12s;
    position: relative;
    background: #fff;
}
#notificationsList a.notif-item:last-child { border-bottom: none; }
#notificationsList a.notif-item:hover      { background: #f5f6ff; }

/* ── UNREAD — highlighted state ── */
#notificationsList a.notif-item.unread {
    background: #eef0ff;                       /* soft indigo tint */
    border-left-color: #696cff;               /* solid left accent bar */
}
#notificationsList a.notif-item.unread:hover { background: #e4e7ff; }

/* pulsing dot for unread */
#notificationsList a.notif-item.unread::after {
    content: '';
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #696cff;
    box-shadow: 0 0 0 3px rgba(105,108,255,.2);
    animation: notifPulse 2s infinite;
}
@keyframes notifPulse {
    0%, 100% { box-shadow: 0 0 0 3px rgba(105,108,255,.2); }
    50%       { box-shadow: 0 0 0 6px rgba(105,108,255,.05); }
}

/* ── Avatar ── */
#notificationsList .notif-avatar {
    width: 38px; height: 38px;
    border-radius: 10px;                       /* rounded square */
    font-size: 13px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
/* colour variants */
#notificationsList .notif-avatar.urgent   { background: #fff0f0; color: #e74c3c; }
#notificationsList .notif-avatar.high     { background: #fffbeb; color: #f59e0b; }
#notificationsList .notif-avatar.normal   { background: #eef2ff; color: #696cff; }
#notificationsList .notif-avatar.received { background: #f0fdf4; color: #16a34a; }

/* ── Text ── */
.notif-title {
    font-size: 0.8375rem;
    font-weight: 600;
    color: #1a1d3a;
    margin-bottom: 2px;
    padding-right: 20px;            /* room for dot */
}
/* unread title gets a stronger colour */
#notificationsList a.notif-item.unread .notif-title { color: #3d3fcc; }

.notif-sub {
    font-size: 0.775rem;
    color: #8b90b8;
    margin-bottom: 3px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    max-width: 260px;
}
.notif-time { font-size: 0.72rem; color: #b0b4d8; }

/* ── "New" pill chip ── */
.notif-new-chip {
    display: inline-block;
    font-size: 0.63rem;
    background: #696cff;
    color: #fff;
    border-radius: 999px;
    padding: 1px 6px;
    font-weight: 700;
    vertical-align: middle;
    margin-left: 4px;
    letter-spacing: .02em;
}

/* ── "Received" chip ── */
.notif-received-chip {
    display: inline-block;
    font-size: 0.68rem;
    color: #22c55e;
    font-weight: 500;
    margin-left: 4px;
}

/* ── Scrollbar ── */
#notificationsList::-webkit-scrollbar { width: 4px; }
#notificationsList::-webkit-scrollbar-track { background: transparent; }
#notificationsList::-webkit-scrollbar-thumb { background: #e2e5f0; border-radius: 4px; }
#notificationsList::-webkit-scrollbar-thumb:hover { background: #696cff; }

/* ── Empty state ── */
.notif-empty {
    text-align: center;
    padding: 40px 20px;
    color: #b0b4d8;
}
.notif-empty i    { font-size: 2.4rem; color: #d0d3e8; display: block; margin-bottom: 8px; }
.notif-empty p    { font-size: 0.85rem; margin: 0; }
</style>

<script>
// ── Seen-notification helpers (localStorage) ──────────────────────────────
const SEEN_KEY = 'drs_seen_notifs_{{ Auth::id() }}';

function getSeenIds() {
    try { return JSON.parse(localStorage.getItem(SEEN_KEY) || '{}'); }
    catch(e) { return {}; }
}
function markSeen(type, id) {
    const seen = getSeenIds();
    seen[type + '_' + id] = true;
    try { localStorage.setItem(SEEN_KEY, JSON.stringify(seen)); } catch(e) {}
}
function isSeen(type, id) {
    return !!getSeenIds()[type + '_' + id];
}
function markAllVisible(incomingDocs, receivedDocs, forwardedDocs) {
    (incomingDocs || []).forEach(d => markSeen('in', d.recipient_id));
    (receivedDocs  || []).forEach(d => markSeen('rc', d.received_id));
    (forwardedDocs || []).forEach(d => markSeen('fw', d.route_id));
}
// ─────────────────────────────────────────────────────────────────────────

function timeAgo(dateStr) {
    const now  = new Date();
    const date = new Date(dateStr);
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60)        return 'Just now';
    if (diff < 3600)      return Math.floor(diff / 60) + 'm ago';
    if (diff < 86400)     return Math.floor(diff / 3600) + 'h ago';
    if (diff < 86400 * 2) return 'Yesterday';
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function priorityClass(priority) {
    if (priority === 'urgent') return 'urgent';
    if (priority === 'high')   return 'high';
    return 'normal';
}

let _lastIncoming = [];
let _lastReceived = [];
let _lastForwarded = [];

function renderNotifications(incomingDocs, receivedDocs, forwardedDocs) {
    const badge = document.getElementById('pendingBadge');
    const count = document.getElementById('docCount');
    const list  = document.getElementById('notificationsList');

    const unseenIncoming = (incomingDocs || []).filter(d => !isSeen('in', d.recipient_id));
    const unseenReceived = (receivedDocs  || []).filter(d => !isSeen('rc', d.received_id));
    const unseenForwarded = (forwardedDocs || []).filter(d => !isSeen('fw', d.route_id));
    const totalUnseen    = unseenIncoming.length + unseenReceived.length + unseenForwarded.length;

    // Bell badge
    if (totalUnseen > 0) {
        badge.textContent   = totalUnseen > 99 ? '99+' : totalUnseen;
        badge.style.display = 'inline-block';
    } else {
        badge.style.display = 'none';
    }

    // Header count chip
    count.textContent = totalUnseen > 0
        ? totalUnseen + ' New'
        : 'All read';
    count.style.background = totalUnseen > 0 ? '' : '#f0fdf4';
    count.style.color      = totalUnseen > 0 ? '' : '#16a34a';

    let html = '';

    // Build one combined newest-first timeline so the most recent appears on top.
    const timeline = [];

    (incomingDocs || []).forEach(doc => {
        timeline.push({
            type: 'in',
            id: doc.recipient_id,
            rawTime: doc.sent_at_raw || doc.sent_at,
            href: '{{ route("documents.incoming") }}',
            avatarClass: priorityClass(doc.priority || 'normal'),
            avatarInitial: (doc.sender_name || 'U').charAt(0).toUpperCase(),
            title: doc.document_type,
            chipHtml: '<span class="notif-received-chip"><i class="bx bx-envelope" style="vertical-align:middle;"></i> Incoming</span>',
            sub: `From ${doc.sender_name}`,
        });
    });

    (receivedDocs || []).forEach(doc => {
        timeline.push({
            type: 'rc',
            id: doc.received_id,
            rawTime: doc.receive_at_raw || doc.receive_at,
            href: '{{ route("documents.sent") }}',
            avatarClass: 'received',
            avatarInitial: (doc.receiver_name || 'U').charAt(0).toUpperCase(),
            title: doc.document_type,
            chipHtml: '<span class="notif-received-chip"><i class="bx bx-check-double" style="vertical-align:middle;"></i> Received</span>',
            sub: `By ${doc.receiver_name}`,
        });
    });

    (forwardedDocs || []).forEach(doc => {
        timeline.push({
            type: 'fw',
            id: doc.route_id,
            rawTime: doc.forward_at_raw || doc.forward_at,
            href: '{{ route("documents.sent") }}',
            avatarClass: 'normal',
            avatarInitial: (doc.forwarder_name || 'U').charAt(0).toUpperCase(),
            title: doc.document_type,
            chipHtml: '<span class="notif-received-chip"><i class="bx bx-share-alt" style="vertical-align:middle;"></i> Forwarded</span>',
            sub: `${doc.forwarder_name} → ${doc.receiver_name}`,
        });
    });

    timeline.sort((a, b) => new Date(b.rawTime).getTime() - new Date(a.rawTime).getTime());

    timeline.forEach(item => {
        const seen = isSeen(item.type, item.id);
        const ago = timeAgo(item.rawTime);

        html += `
        <a href="${item.href}"
           class="notif-item${seen ? '' : ' unread'}"
           data-notif-type="${item.type}"
           data-notif-id="${item.id}">
            <div class="notif-avatar ${item.avatarClass}">${item.avatarInitial}</div>
            <div style="flex:1; min-width:0;">
                <div class="notif-title">
                    ${item.title}
                    ${item.chipHtml}
                    ${!seen ? '<span class="notif-new-chip">New</span>' : ''}
                </div>
                <div class="notif-sub">
                    <i class="bx bx-user" style="font-size:0.72rem; vertical-align:middle;"></i>
                    ${item.sub}
                </div>
                <div class="notif-time">
                    <i class="bx bx-time-five" style="font-size:0.7rem; vertical-align:middle;"></i>
                    ${ago}
                </div>
            </div>
        </a>`;
    });

    // ── Empty state ──────────────────────────────────────────────────────
    if (html === '') {
        list.innerHTML = `
            <div class="notif-empty">
                <i class="bx bxs-bell-off"></i>
                <p>No new notifications</p>
            </div>`;
        return;
    }

    list.innerHTML = html;
}

function updateNotifications() {
    Promise.all([
        fetch('{{ route("documents.pending-documents") }}').then(r => r.json()),
        fetch('{{ route("documents.received-by-others") }}').then(r => r.json()),
        fetch('{{ route("documents.forwarded-by-others") }}').then(r => r.json()),
    ])
    .then(([incomingData, receivedData, forwardedData]) => {
        _lastIncoming = (incomingData.success && incomingData.documents) ? incomingData.documents : [];
        _lastReceived = (receivedData.success  && receivedData.documents) ? receivedData.documents : [];
        _lastForwarded = (forwardedData.success && forwardedData.documents) ? forwardedData.documents : [];
        renderNotifications(_lastIncoming, _lastReceived, _lastForwarded);
    })
    .catch(err => console.error('Notification error:', err));
}

// Mark all as read — called by the ✓✓ button
function markAllRead() {
    markAllVisible(_lastIncoming, _lastReceived, _lastForwarded);
    renderNotifications(_lastIncoming, _lastReceived, _lastForwarded);
}

document.addEventListener('DOMContentLoaded', function () {
    updateNotifications();
    setInterval(updateNotifications, 30000);

    // Mark individual notification as seen on click
    document.getElementById('notificationsList').addEventListener('click', function (e) {
        const item = e.target.closest('.notif-item');
        if (item) {
            const type = item.dataset.notifType;
            const id   = item.dataset.notifId;
            if (type && id) {
                markSeen(type, id);
                renderNotifications(_lastIncoming, _lastReceived, _lastForwarded);
            }
        }
    });

    // ── Navbar search modal ────────────────────────────────────────────────
    const overlay   = document.getElementById('searchOverlay');
    const input     = document.getElementById('navbarSearchInput');
    const results   = document.getElementById('navbarSearchResults');
    const closeBtn  = document.getElementById('searchCloseBtn');
    const toggleBtn = document.getElementById('navbarSearchToggle');
    let searchTimer = null;

    function openSearch() {
        overlay.classList.add('show');
        input.value = '';
        results.innerHTML = '';
        setTimeout(() => input.focus(), 60);
    }
    function closeSearch() {
        overlay.classList.remove('show');
        input.value = '';
        results.innerHTML = '';
    }

    toggleBtn.addEventListener('click', openSearch);
    closeBtn.addEventListener('click', closeSearch);

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeSearch();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSearch();
    });

    input.addEventListener('input', function () {
        clearTimeout(searchTimer);
        const q = this.value.trim();
        if (q.length < 2) { results.innerHTML = ''; return; }
        searchTimer = setTimeout(() => doSearch(q), 300);
    });

    function doSearch(q) {
        fetch(`{{ url('/documents/search') }}?q=${encodeURIComponent(q)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(data => renderResults(data, q))
        .catch(() => renderResults([], q));
    }

    function renderResults(items, q) {
        if (!items || items.length === 0) {
            results.innerHTML = `<div class="search-empty"><i class="bx bx-search-alt" style="font-size:1.6rem;display:block;margin-bottom:8px;"></i>No results for "<strong>${escHtml(q)}</strong>"</div>`;
        } else {
            results.innerHTML = items.map(item => `
                <a href="${item.url}" class="search-result-item">
                    <div class="search-result-icon"><i class="bx bx-barcode"></i></div>
                    <div>
                        <div class="search-result-title">${escHtml(item.title)}</div>
                        <div class="search-result-sub">${escHtml(item.sub || '')}</div>
                    </div>
                </a>`).join('');
        }
    }

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
});
</script>