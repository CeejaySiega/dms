@extends('layouts.contentNavbarLayout')

@section('title', 'My Profile')

@section('content')

<style>
    .profile-header-banner {
        background: linear-gradient(135deg, #4a7ba7 0%, #6ba3d4 50%, #5a8fb8 100%);
        background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 300"><defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M 40 0 L 0 0 0 40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="1200" height="300" fill="url(%23grid)"/></svg>');
        color: white;
        padding: 2rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }

    .profile-header-banner::before {
        content: '';
        position: absolute;
        right: 0;
        top: 0;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
        transform: translate(100px, -50px);
    }

    .profile-header-content {
        position: relative;
        z-index: 1;
    }

    .profile-header-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        letter-spacing: 0.05em;
    }

    .profile-header-subtitle {
        font-size: 0.875rem;
        opacity: 0.95;
        margin-bottom: 1.5rem;
    }

    .profile-user-info {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .profile-user-avatar {
        width: 100px;
        height: 100px;
        background: rgba(255, 255, 255, 0.2);
        border: 3px solid white;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .profile-user-details h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 0 0.25rem 0;
        color: white;
    }

    .profile-user-details p {
        margin: 0;
        font-size: 0.875rem;
        opacity: 0.9;
    }

    .dashboard-section {
        background: white;
        padding: 1.5rem;
        border-radius: 0.5rem;
        border: 1px solid #e9ecef;
        margin-bottom: 2rem;
    }

    .dashboard-title {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6c757d;
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f8f9fa;
    }

    .leave-balance-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f8f9fa;
        font-size: 0.875rem;
    }

    .leave-balance-item:last-child {
        border-bottom: none;
    }

    .leave-label {
        color: #6c757d;
        font-weight: 500;
    }

    .leave-value {
        color: #ffa500;
        font-weight: 600;
    }

    .announcements-section {
        background: white;
        padding: 1.5rem;
        border-radius: 0.5rem;
        border: 1px solid #e9ecef;
    }

    .announcements-title {
        font-size: 0.875rem;
        font-weight: 700;
        color: #ff6b6b;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .announcements-title::before,
    .announcements-title::after {
        content: '📢';
    }

    .announcement-empty {
        color: #6c757d;
        padding: 2rem;
        text-align: center;
        font-size: 0.875rem;
    }

    .profile-section-title {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6c757d;
        margin: 1.5rem 0 1rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .profile-info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .profile-info-card {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 0.375rem;
        border-left: 3px solid #696cff;
    }

    .profile-info-label {
        font-size: 0.75rem;
        color: #6c757d;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 0.25rem;
    }

    .profile-info-value {
        font-size: 0.95rem;
        color: #1f2937;
        font-weight: 500;
    }

    @media (max-width: 768px) {
        .profile-user-info {
            flex-direction: column;
            text-align: center;
        }

        .profile-user-avatar {
            width: 80px;
            height: 80px;
            font-size: 1.75rem;
        }

        .profile-info-grid {
            grid-template-columns: 1fr;
        }

        .profile-header-banner {
            padding: 1.5rem;
        }
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- Profile Header Banner -->
    <div class="profile-header-banner">
        <div class="profile-header-content">
            <div class="profile-header-title">SOUTHERN LEYTE STATE UNIVERSITY</div>
            <div class="profile-header-subtitle">Government Employee Management System (GEMS)</div>
            
            @php
                $employee = Auth::user()->employee;
                $fullName = $employee 
                    ? $employee->firstname . ' ' . $employee->lastname 
                    : Auth::user()->name ?? 'User';
                $initials = strtoupper(substr($fullName, 0, 1) . (strpos($fullName, ' ') !== false ? substr($fullName, strpos($fullName, ' ') + 1, 1) : ''));
                $position = $employee->position ?? 'Employee';
            @endphp

            <div class="profile-user-info">
                <div class="profile-user-avatar">
                    {{ $initials }}
                </div>
                <div class="profile-user-details">
                    <h2>{{ $fullName }}</h2>
                    <p>{{ $position }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-8">

            <!-- Dashboard Section -->
            <div class="dashboard-section">
                <div class="dashboard-title">DASHBOARD</div>
                <p class="text-muted mb-0">Welcome to your profile dashboard. Here you can view your information and manage your account settings.</p>
            </div>

            <!-- Leave Balance Section -->
            <div class="dashboard-section">
                <div class="dashboard-title">Leave Balance</div>
                
                <div class="leave-balance-item">
                    <span class="leave-label">Vacation balance:</span>
                    <span class="leave-value">No data</span>
                </div>
                <div class="leave-balance-item">
                    <span class="leave-label">Sick balance:</span>
                    <span class="leave-value">No data</span>
                </div>
                <div class="leave-balance-item">
                    <span class="leave-label">Forced balance:</span>
                    <span class="leave-value">No data</span>
                </div>
                <div class="leave-balance-item">
                    <span class="leave-label">SPL balance:</span>
                    <span class="leave-value">No data</span>
                </div>
            </div>

            <!-- Announcements Section -->
            <div class="announcements-section">
                <div class="announcements-title">ANNOUNCEMENTS</div>
                <div class="announcement-empty">
                    <p>No announcements at this time.</p>
                </div>
            </div>

        </div>

        <div class="col-12 col-lg-4">
            
            <!-- Personal Information Card -->
            <div class="dashboard-section">
                <div class="dashboard-title">Personal Information</div>

                @if($employee && $employee->firstname)
                <div class="profile-info-card mb-2">
                    <div class="profile-info-label">First Name</div>
                    <div class="profile-info-value">{{ $employee->firstname }}</div>
                </div>
                @endif

                @if($employee && $employee->lastname)
                <div class="profile-info-card mb-2">
                    <div class="profile-info-label">Last Name</div>
                    <div class="profile-info-value">{{ $employee->lastname }}</div>
                </div>
                @endif

                @if($employee && $employee->gender)
                <div class="profile-info-card mb-2">
                    <div class="profile-info-label">Gender</div>
                    <div class="profile-info-value">{{ ucfirst($employee->gender) }}</div>
                </div>
                @endif

                @if($employee && $employee->date_of_birth)
                <div class="profile-info-card">
                    <div class="profile-info-label">Date of Birth</div>
                    <div class="profile-info-value">{{ $employee->date_of_birth->format('F d, Y') }}</div>
                </div>
                @endif
            </div>

            <!-- Account Information Card -->
            <div class="dashboard-section">
                <div class="dashboard-title">Account Information</div>

                <div class="profile-info-card mb-2">
                    <div class="profile-info-label">Email Address</div>
                    <div class="profile-info-value" style="word-break: break-all;">{{ Auth::user()->email }}</div>
                </div>

                @if($employee && $employee->employee_id)
                <div class="profile-info-card mb-2">
                    <div class="profile-info-label">Employee ID</div>
                    <div class="profile-info-value">{{ $employee->employee_id }}</div>
                </div>
                @endif

                @if($employee && $employee->department)
                <div class="profile-info-card mb-2">
                    <div class="profile-info-label">Department</div>
                    <div class="profile-info-value">{{ $employee->department->department_name }}</div>
                </div>
                @endif

                @if($employee && $employee->campus)
                <div class="profile-info-card">
                    <div class="profile-info-label">Campus/Location</div>
                    <div class="profile-info-value">{{ $employee->campus }}</div>
                </div>
                @endif
            </div>

        </div>
    </div>

</div>

@endsection
