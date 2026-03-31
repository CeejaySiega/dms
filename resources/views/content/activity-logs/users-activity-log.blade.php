@extends('layouts/contentNavbarLayout')

@section('title','All Activity Logs')

@section('vendor-style')
@vite('resources/assets/vendor/libs/apex-charts/apex-charts.scss')
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/apex-charts/apexcharts.js')
@endsection

@section('page-script')
@vite('resources/assets/js/dashboards-analytics.js')
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="mb-4">
                    <h4 class="fw-bold mb-2"><i class="bx bx-archive me-2"></i>All User Activity Logs</h4>
                    <nav aria-label="breadcrumb">
                         <ol class="breadcrumb breadcrumb-style1">
                         <li class="breadcrumb-item">
                            <a href="{{ route('dashboard-analytics') }}">Home</a>
                        </li>
                        <li class="breadcrumb-item active">Activity Logs</li>
                        </ol>
                    </nav>
                </div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <form action="{{ route('user.activity-logs.delete') }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">ClearAll Logs</button>
                    </form>

            </div>
            <div class="table-responsive text-nowrap">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>User Agent</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                        @forelse($logs as $log)
                            <tr>
                                <td>
                                    @php
                                        $employee = null;
                                        if ($log->user && $log->user->employee) {
                                            $employee = $log->user->employee;
                                        } elseif ($log->user_id) {
                                            $employee = \App\Models\Employee::where('user_id', $log->user_id)->first();
                                        }
                                    @endphp
                                    <span class="fw-semibold">
                                        {{ $employee ? ($employee->firstname . ' ' . $employee->lastname) : 'N/A' }}
                                    </span>
                                </td>
                                <td><span class="badge bg-label-primary me-1">{{ $log->action }}</span></td>
                                <td>{{ $log->description }}</td>
                                <td>{{ $log->ip_address }}</td>
                                <td><span class="text-truncate d-inline-block" style="max-width: 200px;">{{ Str::limit($log->user_agent, 40) }}</span></td>
                                <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No activity logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
