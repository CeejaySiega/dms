@if($members->isEmpty())
    <p class="text-muted mb-0" id="emptyMessage">No users assigned to this group yet.</p>
@endif
<ul class="list-group" id="membersList">
    @foreach($members as $member)
        <li class="list-group-item d-flex align-items-center justify-content-between" data-member-id="{{ encryptId($member->user_id) }}">
            <div>
                @if($member->user->employee)
                    <div class="fw-semibold">{{ $member->user->employee->firstname }} {{ $member->user->employee->lastname }}</div>
                @else
                    <div class="fw-semibold">
                        {{ $member->user->name ?? $member->user->email ?? ('User #' . $member->user_id) }}
                    </div>
                @endif
                <div class="text-muted small">{{ $member->user->email ?? '' }}</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger remove-member-btn" data-user-id="{{ encryptId($member->user_id) }}">
                <i class="bx bx-x"></i> Remove
            </button>
        </li>
    @endforeach
</ul>
