@extends('layouts.contentNavbarLayout')

@section('title', 'My Profile')

@section('content')

<style>
    * { box-sizing: border-box; }

    /* ── COVER BANNER ── */
    .profile-cover {
        position: relative;
        height: 200px;
        border-radius: 0.75rem 0.75rem 0 0;
        overflow: hidden;
        background: #3ab5a4;
    }

    .profile-cover-segments {
        display: flex;
        height: 100%;
        width: 100%;
    }

    .cover-seg {
        flex: 1;
        clip-path: polygon(0 0, 100% 0, 85% 100%, 0 100%);
        margin-right: -2%;
    }

    .cover-seg:last-child { clip-path: none; margin-right: 0; }

    .cover-seg-1 { background: #3ab5a4; }
    .cover-seg-2 { background: #6ecfc3; }
    .cover-seg-3 { background: #f5d5c0; }
    .cover-seg-4 { background: #f5a8a8; }

    /* ── PROFILE HEADER CARD ── */
    .profile-header-card {
        background: #fff;
        border-radius: 0.75rem;
        border: 1px solid #e9ecef;
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .profile-identity {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        padding: 0 1.75rem 1.25rem 1.75rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .profile-avatar-wrap {
        position: relative;
        margin-top: -52px;
    }

    .profile-avatar {
        width: 104px;
        height: 104px;
        border-radius: 0.5rem;
        border: 4px solid #fff;
        background: linear-gradient(135deg, #696cff, #a78bfa);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.25rem;
        font-weight: 700;
        color: #fff;
        letter-spacing: 0.02em;
        box-shadow: 0 4px 16px rgba(105,108,255,0.25);
    }

    .profile-name-block {
        flex: 1;
        min-width: 200px;
    }

    .profile-name-block h1 {
        font-size: 1.4rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 0.4rem 0;
    }

    .profile-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        font-size: 0.82rem;
        color: #64748b;
    }

    .profile-meta span {
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .profile-meta svg {
        width: 14px;
        height: 14px;
        opacity: 0.7;
        flex-shrink: 0;
    }

    .btn-connected {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        background: #696cff;
        color: #fff;
        border: none;
        border-radius: 0.4rem;
        padding: 0.55rem 1.25rem;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.18s;
        text-decoration: none;
    }

    .btn-connected:hover { background: #5a5df0; color: #fff; }

    /* ── TABS ── */
    .profile-tabs {
        display: flex;
        border-top: 1px solid #f0f0f0;
        padding: 0 1.75rem;
    }

    .profile-tab {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.85rem 1.1rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: #64748b;
        cursor: pointer;
        border-bottom: 2px solid transparent;
        margin-bottom: -1px;
        transition: color 0.15s, border-color 0.15s;
        text-decoration: none;
    }

    .profile-tab:hover { color: #696cff; }
    .profile-tab.active { color: #696cff; border-bottom-color: #696cff; font-weight: 600; }
    .profile-tab svg { width: 16px; height: 16px; }

    /* ── INFO CARDS ── */
    .info-card {
        background: #fff;
        border-radius: 0.75rem;
        border: 1px solid #e9ecef;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .info-section-label {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 1rem;
    }

    .info-row {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.55rem 0;
        font-size: 0.875rem;
        border-bottom: 1px solid #f8f9fa;
    }

    .info-row:last-child { border-bottom: none; }

    .info-row svg {
        width: 16px;
        height: 16px;
        color: #94a3b8;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .info-row-label {
        color: #94a3b8;
        font-weight: 500;
        min-width: 80px;
    }

    .info-row-value {
        color: #1e293b;
        font-weight: 500;
    }

    .badge-active {
        display: inline-block;
        background: #dcfce7;
        color: #16a34a;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 0.15rem 0.55rem;
        border-radius: 99px;
    }

    /* ── TIMELINE CARD ── */
    .timeline-card {
        background: #fff;
        border-radius: 0.75rem;
        border: 1px solid #e9ecef;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .timeline-card-title {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 1.5rem;
    }

    .timeline-card-title svg { width: 18px; height: 18px; color: #696cff; }

    .timeline-list { position: relative; }

    .timeline-list::before {
        content: '';
        position: absolute;
        left: 7px;
        top: 10px;
        bottom: 10px;
        width: 1px;
        background: #e2e8f0;
    }

    .timeline-item {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        position: relative;
    }

    .timeline-item:last-child { margin-bottom: 0; }

    .timeline-dot {
        width: 15px;
        height: 15px;
        border-radius: 50%;
        border: 2px solid #fff;
        box-shadow: 0 0 0 2px currentColor;
        flex-shrink: 0;
        margin-top: 3px;
        position: relative;
        z-index: 1;
    }

    .dot-blue  { background: #3b82f6; color: #3b82f6; }
    .dot-green { background: #22c55e; color: #22c55e; }
    .dot-sky   { background: #06b6d4; color: #06b6d4; }

    .timeline-body { flex: 1; }

    .timeline-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .timeline-event-title {
        font-size: 0.9rem;
        font-weight: 600;
        color: #1e293b;
    }

    .timeline-ago {
        font-size: 0.75rem;
        color: #94a3b8;
        white-space: nowrap;
    }

    .timeline-desc {
        font-size: 0.82rem;
        color: #64748b;
        margin: 0.25rem 0 0;
    }

    /* ── LEAVE BALANCE ── */
    .leave-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }

    .leave-tile {
        background: #f8f9ff;
        border: 1px solid #e8e9ff;
        border-radius: 0.5rem;
        padding: 1rem;
        text-align: center;
    }

    .leave-tile-label {
        font-size: 0.72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #94a3b8;
        margin-bottom: 0.4rem;
    }

    .leave-tile-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #696cff;
    }

    /* ── ANNOUNCEMENTS ── */
    .announce-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 2rem;
        color: #94a3b8;
        font-size: 0.85rem;
        text-align: center;
    }

    .announce-empty svg { width: 40px; height: 40px; opacity: 0.35; }

    @media (max-width: 768px) {
        .profile-identity { padding: 0 1rem 1rem 1rem; }
        .profile-tabs { padding: 0 1rem; overflow-x: auto; }
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">

    @php
        $employee = Auth::user()->employee;
        $fullName = $employee
            ? $employee->firstname . ' ' . $employee->lastname
            : Auth::user()->name ?? 'User';
        $initials = strtoupper(substr($fullName, 0, 1) . (strpos($fullName, ' ') !== false ? substr($fullName, strpos($fullName, ' ') + 1, 1) : ''));
        $position = $employee->position ?? 'Employee';
        $department = $employee && $employee->department ? $employee->department->department_name : '—';
        
        // Get campus from Globalpreferrence service by ID
        $campusData = null;
        $campus = '—';
        if ($employee && $employee->campus) {
            $campuses = \App\Helpers\Globalpreferrence::Campuses();
            foreach ($campuses as $campusItem) {
                if ($campusItem['ID'] == $employee->campus) {
                    $campusData = $campusItem;
                    $campus = $campusItem['Campus'];
                    break;
                }
            }
        }
        
        $joinedDate = Auth::user()->created_at ? Auth::user()->created_at->format('F Y') : 'N/A';
    @endphp

    <!-- Profile Header Card -->
    <div class="profile-header-card">

        <!-- Cover Banner -->
        <div class="profile-cover">
            <div class="profile-cover-segments">
                <div class="cover-seg cover-seg-1"></div>
                <div class="cover-seg cover-seg-2"></div>
                <div class="cover-seg cover-seg-3"></div>
                <div class="cover-seg cover-seg-4"></div>
            </div>
        </div>

        <!-- Identity Row -->
        <div class="profile-identity">
            <div style="display:flex;align-items:flex-end;gap:1.25rem;flex-wrap:wrap;">
                <div class="profile-avatar-wrap">
                    <div class="profile-avatar">{{ $initials }}</div>
                </div>
                <div class="profile-name-block">
                    <h1>{{ $fullName }}</h1>
                    <div class="profile-meta">
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            {{ $position }}
                        </span>
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            {{ $campus }}
                        </span>
                        <span>
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Joined {{ $joinedDate }}
                        </span>
                    </div>
                </div>
            </div>
            <a href="#" class="btn-connected">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                Connected
            </a>
        </div>

        <!-- Tabs -->
        <div class="profile-tabs">
            <a href="profile/view-profile.blade.php" class="profile-tab active">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Profile
            </a>
            <a href="#" class="profile-tab">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Groups
            </a>
            <a href="#" class="profile-tab">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                Projects
            </a>
            <a href="#" class="profile-tab">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                Connections
            </a>
        </div>

    </div>

    <div class="row">

        <!-- LEFT: About + Contacts + Leave Balance -->
        <div class="col-12 col-lg-4">

            <div class="info-card">
                
                <div class="info-section-label">About</div>
                

                <div class="info-row">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span class="info-row-label">Full Name:</span>
                    <span class="info-row-value">{{ $fullName }}</span>
                </div>

                <div class="info-row">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span class="info-row-label">Status:</span>
                    <span class="info-row-value"><span class="badge-active">Active</span></span>
                </div>

                <div class="info-row">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span class="info-row-label">Position:</span>
                    <span class="info-row-value">{{ $position }}</span>
                </div>

                <div class="info-row">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span class="info-row-label">Department:</span>
                    <span class="info-row-value">{{ $department }}</span>
                </div>

                @if($employee && $employee->gender)
                <div class="info-row">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span class="info-row-label">Gender:</span>
                    <span class="info-row-value">{{ ucfirst($employee->gender) }}</span>
                </div>
                @endif

                @if($employee && $employee->date_of_birth)
                <div class="info-row">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span class="info-row-label">Birthday:</span>
                    <span class="info-row-value">{{ $employee->date_of_birth->format('M d, Y') }}</span>
                </div>
                @endif
            </div>

            <div class="info-card">
                <div class="info-section-label">Contacts</div>

                <div class="info-row">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span class="info-row-label">Email:</span>
                    <span class="info-row-value" style="word-break:break-all;font-size:0.82rem;">{{ Auth::user()->email }}</span>
                </div>

                @if($employee && $employee->employee_id)
                <div class="info-row">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
                    <span class="info-row-label">Emp. ID:</span>
                    <span class="info-row-value">{{ $employee->employee_id }}</span>
                </div>
                @endif

                <div class="info-row">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span class="info-row-label">Campus:</span>
                    <span class="info-row-value">
                        @if($campusData)
                            <span class="badge bg-label-{{ $campusData['Color'] }}">
                                {{ $campusData['Campus'] }}
                            </span>
                        @else
                            <span>—</span>
                        @endif
                    </span>
                </div>
            </div>

            {{-- <div class="info-card">
                <div class="info-section-label">Leave Balance</div>
                <div class="leave-grid">
                    <div class="leave-tile">
                        <div class="leave-tile-label">Vacation</div>
                        <div class="leave-tile-value">—</div>
                    </div>
                    <div class="leave-tile">
                        <div class="leave-tile-label">Sick</div>
                        <div class="leave-tile-value">—</div>
                    </div>
                    <div class="leave-tile">
                        <div class="leave-tile-label">Forced</div>
                        <div class="leave-tile-value">—</div>
                    </div>
                    <div class="leave-tile">
                        <div class="leave-tile-label">SPL</div>
                        <div class="leave-tile-value">—</div>
                    </div>
                </div>
            </div> --}}

        </div>

        <!-- RIGHT: Activity Timeline + Announcements -->
        <div class="col-12 col-lg-8">

            <div class="timeline-card">
                <div class="timeline-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Activity Timeline
                </div>

                <div class="timeline-list">
                    @php
                        $activityLogs = Auth::user()->activityLogs()->orderBy('created_at', 'desc')->limit(20)->get();
                        $dotColorMap = [
                            'login' => 'dot-blue',
                            'logout' => 'dot-sky',
                            'profile_update' => 'dot-green',
                            'document_view' => 'dot-blue',
                            'document_forward' => 'dot-green',
                            'document_receive' => 'dot-sky',
                            'archive' => 'dot-green',
                        ];
                    @endphp

                    @forelse($activityLogs as $log)
                        <div class="timeline-item">
                            <div class="timeline-dot {{ $dotColorMap[strtolower($log->action)] ?? 'dot-blue' }}"></div>
                            <div class="timeline-body">
                                <div class="timeline-header">
                                    <div class="timeline-event-title">{{ ucwords(str_replace('_', ' ', $log->action)) }}</div>
                                    <div class="timeline-ago">{{ $log->created_at ? $log->created_at->diffForHumans() : '' }}</div>
                                </div>
                                <p class="timeline-desc">{{ $log->description }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="timeline-item">
                            <div class="timeline-dot dot-blue"></div>
                            <div class="timeline-body">
                                <div class="timeline-header">
                                    <div class="timeline-event-title">Account Created</div>
                                    <div class="timeline-ago">{{ Auth::user()->created_at ? Auth::user()->created_at->diffForHumans() : '' }}</div>
                                </div>
                                <p class="timeline-desc">Your GEMS account was successfully created.</p>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="timeline-card">
                <div class="timeline-card-title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                    Announcements
                </div>
                <div class="announce-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                    <span>No announcements at this time.</span>
                </div>
            </div>

        </div>
    </div>

</div>

@endsection