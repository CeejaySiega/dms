@php
    use App\Helpers\Globalpreferrence;
@endphp

@foreach($users as $user)
    @php
        $isMember = in_array($user->user_id, $memberUserIds, true);
        $displayName = $user->name ?: $user->email;
        $employeeName = $user->employee ? strtolower($user->employee->firstname . ' ' . $user->employee->lastname) : '';
        $campusCode = $user->employee ? strtolower($user->employee->campus) : '';
        $searchText = strtolower(($user->name ?? '') . ' ' . ($user->email ?? '') . ' ' . $employeeName . ' ' . $campusCode);
    @endphp
    <label class="list-group-item d-flex align-items-start gap-2 user-item" data-search="{{ $searchText }}" data-campus="{{ $user->employee ? $user->employee->campus : '' }}" data-campus-id="{{ $user->employee ? getCampusId($user->employee->campus) : '' }}" data-user-id="{{ encryptId($user->user_id) }}">
        <input class="form-check-input mt-1 user-checkbox" type="checkbox" value="{{ encryptId($user->user_id) }}" {{ $isMember ? 'disabled' : '' }}>
        <span>
            @if($user->employee)
                <div class="fw-semibold">{{ $user->employee->firstname }} {{ $user->employee->lastname }}</div>
            @else
                <div class="fw-semibold">{{ $displayName }}</div>
            @endif
            <div class="text-muted small">{{ $user->email }}</div>
            @if($user->employee)
                <div class="text-muted small">{{ getCampusName($user->employee->campus) ?? $user->employee->campus }}</div>
            @endif
            @if($user->group_memberships > 0 && !$isMember)
                <div class="text-muted small">
                    <i class="bx bx-info-circle"></i> In {{ $user->group_memberships }} group(s)
                </div>
            @endif
        </span>
        @if($isMember)
            <span class="badge bg-label-success ms-auto">Assigned</span>
        @elseif($user->group_memberships > 0)
            <span class="badge bg-label-warning ms-auto">In other groups</span>
        @endif
    </label>
@endforeach
