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
<!-- Navbar Brand Text -->
<div class="navbar-brand d-flex align-items-center">
    <span class="app-brand-text menu-text text-primary text-heading">Document Management System</span>
</div>
@endif

<div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
    <ul class="navbar-nav flex-row align-items-center ms-auto">

        {{-- ── NOTIFICATIONS ── --}}
        <li class="nav-item navbar-dropdown dropdown-notifications me-4">
            <a class="nav-link dropdown-toggle hide-arrow p-0"
               href="javascript:void(0);"
               data-bs-toggle="dropdown"
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

            <ul class="dropdown-menu dropdown-menu-end p-0"
                style="min-width:380px; max-width:380px; border-radius:0.75rem; overflow:hidden; box-shadow:0 8px 24px rgba(26,29,58,0.14);">

                {{-- Header --}}
                <li class="d-flex align-items-center justify-content-between px-4 pt-3 pb-2"
                    style="border-bottom:1px solid #f0f2f7;">
                    <h6 class="mb-0 fw-bold" style="font-size:0.95rem;">Notifications</h6>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge rounded-pill bg-label-primary px-2 py-1"
                              id="docCount"
                              style="font-size:0.72rem;">
                            0 New
                        </span>
                        <a href="{{ route('documents.incoming') }}"
                           class="text-muted"
                           title="View inbox"
                           style="font-size:1.1rem; line-height:1;">
                            <i class="bx bx-envelope"></i>
                        </a>
                    </div>
                </li>

                {{-- Scrollable list --}}
                <li id="notificationsList"
                    style="max-height:320px; overflow-y:auto;">
                    {{-- Default empty state --}}
                    <div class="text-center py-5 text-muted" id="emptyState">
                        <i class="bx bxs-bell-off" style="font-size:2.2rem; color:#c4c6d0;"></i>
                        <p class="mt-2 mb-0" style="font-size:0.85rem;">No new notifications</p>
                    </div>
                </li>

                {{-- Footer button --}}
                <li style="border-top:1px solid #f0f2f7;">
                    <a href="{{ route('documents.incoming') }}"
                       class="btn btn-primary w-100 fw-semibold"
                       style="border-radius:0; padding:10px; font-size:0.875rem;">
                        View all notifications
                    </a>
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
                <li>
                    <a class="dropdown-item" href="javascript:void(0);">
                        <i class="icon-base bx bx-cog icon-md me-3"></i><span>Settings</span>
                    </a>
                </li>
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
/* ── Notification item hover ── */
#notificationsList a.notif-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #f0f2f7;
    text-decoration: none;
    transition: background 0.12s;
    position: relative;
}
#notificationsList a.notif-item:last-child { border-bottom: none; }
#notificationsList a.notif-item:hover { background: #f5f6ff; }

/* unread dot */
#notificationsList a.notif-item.unread::after {
    content: '';
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #696cff;
}

/* avatar */
#notificationsList .notif-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #eef2ff;
    color: #696cff;
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* priority color variants */
#notificationsList .notif-avatar.urgent { background: #fff0f0; color: #e74c3c; }
#notificationsList .notif-avatar.high   { background: #fffbeb; color: #f59e0b; }
#notificationsList .notif-avatar.normal { background: #eef2ff; color: #696cff; }

/* text */
.notif-title  { font-size: 0.8375rem; font-weight: 600; color: #1a1d3a; margin-bottom: 2px; }
.notif-sub    { font-size: 0.775rem;  color: #8b90b8; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 240px; }
.notif-time   { font-size: 0.72rem;   color: #b0b4d8; }

/* scrollbar styling */
#notificationsList::-webkit-scrollbar { width: 4px; }
#notificationsList::-webkit-scrollbar-track { background: transparent; }
#notificationsList::-webkit-scrollbar-thumb { background: #e2e5f0; border-radius: 4px; }
#notificationsList::-webkit-scrollbar-thumb:hover { background: #696cff; }
</style>

<script>
// Helper: format timestamp as relative time
function timeAgo(dateStr) {
    const now  = new Date();
    const date = new Date(dateStr);
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60)               return 'Just now';
    if (diff < 3600)             return Math.floor(diff / 60) + 'hr ago';
    if (diff < 86400)            return Math.floor(diff / 3600) + 'hr ago';
    if (diff < 86400 * 2)        return 'Yesterday';
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

// Helper: priority → avatar css class
function priorityClass(priority) {
    if (priority === 'urgent') return 'urgent';
    if (priority === 'high')   return 'high';
    return 'normal';
}

// Helper: priority → icon
function priorityIcon(priority) {
    if (priority === 'urgent') return 'bxs-bell-ring';
    if (priority === 'high')   return 'bx-envelope';
    return 'bx-envelope';
}

function updateNotifications() {
    fetch('{{ route("documents.pending-documents") }}')
        .then(r => r.json())
        .then(data => {
            const badge  = document.getElementById('pendingBadge');
            const count  = document.getElementById('docCount');
            const list   = document.getElementById('notificationsList');

            if (!data.success) return;

            // Badge
            if (data.count > 0) {
                badge.textContent    = data.count > 99 ? '99+' : data.count;
                badge.style.display  = 'inline-block';
            } else {
                badge.style.display  = 'none';
            }

            // Count label
            count.textContent = data.count + ' New';

            // List
            if (data.documents && data.documents.length > 0) {
                let html = '';
                data.documents.forEach(doc => {
                    const initial  = doc.sender_name.charAt(0).toUpperCase();
                    const pClass   = priorityClass(doc.priority || 'normal');
                    const ago      = timeAgo(doc.sent_at_raw || doc.sent_at);

                    html += `
                        <a href="{{ route('documents.incoming') }}"
                           class="notif-item unread"
                           title="Open inbox">
                            <div class="notif-avatar ${pClass}">${initial}</div>
                            <div style="flex:1; min-width:0;">
                                <div class="notif-title">${doc.document_type}</div>
                                <div class="notif-sub">From ${doc.sender_name}</div>
                                <div class="notif-time">
                                    <i class="bx bx-time-five" style="font-size:0.7rem;vertical-align:middle;"></i>
                                    ${ago}
                                </div>
                            </div>
                        </a>`;
                });
                list.innerHTML = html;
            } else {
                list.innerHTML = `
                    <div class="text-center py-5 text-muted">
                        <i class="bx bxs-bell-off" style="font-size:2.2rem; color:#c4c6d0;"></i>
                        <p class="mt-2 mb-0" style="font-size:0.85rem;">No new notifications</p>
                    </div>`;
            }
        })
        .catch(err => console.error('Notification error:', err));
}

document.addEventListener('DOMContentLoaded', function () {
    updateNotifications();
    setInterval(updateNotifications, 30000);
});
</script>