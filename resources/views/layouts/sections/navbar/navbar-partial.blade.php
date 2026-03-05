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

<!-- ! Not required for layout-without-menu -->
{{-- @if(!isset($navbarHideToggle))
<div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 {{ isset($contentNavbar) ?' d-xl-none ' : '' }}">
    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
        <i class="icon-base bx bx-menu icon-md"></i>
    </a>
</div>
@endif --}}

<div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
    <!-- Search removed -->
    <ul class="navbar-nav flex-row align-items-center ms-auto">
        <!-- Place this tag where you want the button to render. -->
        <li class="nav-item navbar-dropdown dropdown-notifications me-4">
            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false" style="position: relative;">
                <i class="icon-base bx bx-bell icon-md"></i>
                <span class="badge bg-danger rounded-pill badge-notifications" id="pendingBadge" style="display: none; position: absolute; top: -8px; right: -8px; font-size: 0.65rem; padding: 0.35rem 0.5rem;">0</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" id="notificationsDropdown" style="min-width: 300px; max-height: 400px; overflow-y: auto;">
                <li class="dropdown-header pb-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="mb-0">Incoming Documents</h6>
                        <small class="text-muted" id="docCount">0</small>
                    </div>
                </li>
                <li>
                    <hr class="dropdown-divider my-0">
                </li>
                <li class="dropdown-notifications-list" id="notificationsList">
                    <div class="text-center py-3 text-muted">
                        <i class="bx bxs-inbox" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">0 incoming documents</p>
                    </div>
                </li>
                <li>
                    <hr class="dropdown-divider my-0">
                </li>
                <li class="text-center py-2">
                    <a href="{{ route('documents.incoming') }}" class="stretched-link text-primary fw-semibold">View All</a>
                </li>
            </ul>
        </li>

        <!-- User -->
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
            @php
                $employee = Auth::user()->employee;
                $userName = $employee 
                    ? $employee->firstname . ' ' . $employee->lastname 
                    : Auth::user()->name ?? Auth::user()->email;
                $initials = strtoupper(substr($userName, 0, 1) . (strpos($userName, ' ') !== false ? substr($userName, strpos($userName, ' ') + 1, 1) : ''));
            @endphp
            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                <div class="avatar avatar-online">
                    <span class="avatar-initial rounded-circle bg-label-primary fw-bold d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; cursor: pointer;">
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
                                    <span class="avatar-initial rounded-circle bg-label-primary fw-bold d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        {{ $initials }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                @php
                                    $employee = Auth::user()->employee;
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
                <li>
                    <div class="dropdown-divider my-1"></div>
                </li>
                <li>
                    <a class="dropdown-item" href="javascript:void(0);">
                        <i class="icon-base bx bx-user icon-md me-3"></i><span>My Profile</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="javascript:void(0);">
                        <i class="icon-base bx bx-cog icon-md me-3"></i><span>Settings</span>
                    </a>
                </li>
                <!-- <li>
                    <a class="dropdown-item" href="javascript:void(0);">
                        <span class="d-flex align-items-center align-middle">
                            <i class="flex-shrink-0 icon-base bx bx-credit-card icon-md me-3"></i><span class="flex-grow-1 align-middle">Billing Plan</span>
                            <span class="flex-shrink-0 badge rounded-pill bg-danger">4</span>
                        </span>
                    </a>
                </li> -->
                <li>
                    <div class="dropdown-divider my-1"></div>
                </li>
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
        <!--/ User -->
    </ul>
</div>

<script>
// Fetch pending documents and update notification dropdown
function updateNotifications() {
    fetch('{{ route("documents.pending-documents") }}')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('pendingBadge');
            const docCount = document.getElementById('docCount');
            const notificationsList = document.getElementById('notificationsList');
            
            if (data.success) {
                // Update badge
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
                
                // Update doc count
                docCount.textContent = data.count;
                
                // Update notifications list
                if (data.documents && data.documents.length > 0) {
                    let html = '';
                    data.documents.forEach(doc => {
                        html += `
                            <a href="{{ route('documents.incoming') }}" class="dropdown-item d-flex align-items-center py-2 px-3">
                                <div class="avatar flex-shrink-0 me-3">
                                    <span class="avatar-initial rounded-circle bg-label-primary fw-bold" style="width: 32px; height: 32px; font-size: 0.75rem; display: flex; align-items: center; justify-content: center;">
                                        ${doc.sender_name.charAt(0).toUpperCase()}
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0" style="font-size: 0.875rem;">${doc.document_type}</h6>
                                        <small class="text-muted">${doc.sent_at}</small>
                                    </div>
                                    <small class="text-muted d-block">${doc.sender_name}</small>
                                </div>
                            </a>
                        `;
                    });
                    notificationsList.innerHTML = html;
                } else {
                    notificationsList.innerHTML = `
                        <div class="text-center py-3 text-muted">
                            <i class="bx bxs-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2 mb-0">${data.count} incoming document${data.count !== 1 ? 's' : ''}</p>
                        </div>
                    `;
                }
            }
        })
        .catch(error => console.error('Error fetching notifications:', error));
}

// Load notifications on page load
document.addEventListener('DOMContentLoaded', function () {
    updateNotifications();
    
    // Refresh notifications every 30 seconds
    setInterval(updateNotifications, 30000);
});
</script>