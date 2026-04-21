@extends('layouts/contentNavbarLayout')

<title>Document Management System</title>

@section('vendor-style')
@vite('resources/assets/vendor/libs/apex-charts/apex-charts.scss')
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/apex-charts/apexcharts.js')
@endsection

@section('content')

@php
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\DB;
    use App\Models\Recipient;
    use App\Models\ReceivedDocument;
    use App\Models\Document;
    use Carbon\Carbon;

    $uid       = Auth::id();
    $month     = now();
    $lastMonth = now()->subMonth();

    // ── Current & previous month counts ──────────────────────────────────────
    $pendingNow = Recipient::where('user_id', $uid)
        ->where(function ($q) { $q->whereNull('action')->orWhere('action', 'pending'); })
        ->whereMonth('sent_at', $month->month)->whereYear('sent_at', $month->year)->count();

    $pendingLast = Recipient::where('user_id', $uid)
        ->where(function ($q) { $q->whereNull('action')->orWhere('action', 'pending'); })
        ->whereMonth('sent_at', $lastMonth->month)->whereYear('sent_at', $lastMonth->year)->count();

    $receivedNow = ReceivedDocument::where('user_id', $uid)
        ->whereMonth('receive_at', $month->month)->whereYear('receive_at', $month->year)->count();

    $receivedLast = ReceivedDocument::where('user_id', $uid)
        ->whereMonth('receive_at', $lastMonth->month)->whereYear('receive_at', $lastMonth->year)->count();

    $sentNow = Document::where('user_id', $uid)
        ->whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count();

    $sentLast = Document::where('user_id', $uid)
        ->whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->count();

    $totalNow  = $pendingNow + $receivedNow + $sentNow;
    $totalLast = $pendingLast + $receivedLast + $sentLast;

    $delta = fn($cur, $prev) => $prev == 0 ? ($cur > 0 ? 100.0 : 0.0) : round((($cur - $prev) / $prev) * 100, 1);

    date_default_timezone_set('Asia/Manila');
    $hour     = (int) date('H');
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');
    $userName = Auth::user()->employee->firstname;

    // ── 12-month rolling data ─────────────────────────────────────────────────
    $monthlyLabels  = [];
    $monthlySent    = [];
    $monthlyReceived = [];
    $monthlyPending = [];

    for ($i = 11; $i >= 0; $i--) {
        $d = now()->subMonths($i);
        $monthlyLabels[] = $d->format('M Y');

        $monthlySent[] = Document::where('user_id', $uid)
            ->whereMonth('created_at', $d->month)
            ->whereYear('created_at', $d->year)
            ->count();

        $monthlyReceived[] = ReceivedDocument::where('user_id', $uid)
            ->whereMonth('receive_at', $d->month)
            ->whereYear('receive_at', $d->year)
            ->count();

        $monthlyPending[] = Recipient::where('user_id', $uid)
            ->where(function ($q) { $q->whereNull('action')->orWhere('action', 'pending'); })
            ->whereMonth('sent_at', $d->month)
            ->whereYear('sent_at', $d->year)
            ->count();
    }

    // ── 8-week rolling data ───────────────────────────────────────────────────
    $weeklyLabels    = [];
    $weeklyProcessed = [];
    $weeklyPending   = [];

    for ($i = 7; $i >= 0; $i--) {
        $start = now()->startOfWeek()->subWeeks($i);
        $end   = (clone $start)->endOfWeek();
        $weeklyLabels[] = 'Wk ' . $start->format('M d');

        $weeklyProcessed[] = Document::where('user_id', $uid)
            ->whereBetween('created_at', [$start, $end])
            ->count()
            + ReceivedDocument::where('user_id', $uid)
            ->whereBetween('receive_at', [$start, $end])
            ->count();

        $weeklyPending[] = Recipient::where('user_id', $uid)
            ->where(function ($q) { $q->whereNull('action')->orWhere('action', 'pending'); })
            ->whereBetween('sent_at', [$start, $end])
            ->count();
    }

    // ── Mini-stat cards ───────────────────────────────────────────────────────
    $miniStats = [
        [
            'count'  => $pendingNow,
            'label'  => 'Pending documents',
            'sub'    => 'Awaiting your action',
            'delta'  => $delta($pendingNow, $pendingLast),
            'route'  => route('documents.incoming'),
            'icon'   => 'bx-time-five',
            'color'  => '#f59e0b',
            'bg'     => '#fef3c7',
            'border' => '#fde68a',
        ],
        [
            'count'  => $receivedNow,
            'label'  => 'Received documents',
            'sub'    => 'Successfully received',
            'delta'  => $delta($receivedNow, $receivedLast),
            'route'  => route('documents.received'),
            'icon'   => 'bx-envelope-open',
            'color'  => '#22c55e',
            'bg'     => '#dcfce7',
            'border' => '#bbf7d0',
        ],
        [
            'count'  => $sentNow,
            'label'  => 'Sent documents',
            'sub'    => 'Dispatched this month',
            'delta'  => $delta($sentNow, $sentLast),
            'route'  => route('documents.sent'),
            'icon'   => 'bx-send',
            'color'  => '#6366f1',
            'bg'     => '#ede9fe',
            'border' => '#ddd6fe',
        ],
        [
            'count'  => $totalNow,
            'label'  => 'Total documents',
            'sub'    => 'Pending + Received + Sent',
            'delta'  => $delta($totalNow, $totalLast),
            'route'  => route('documents.sent'),
            'icon'   => 'bx-file',
            'color'  => '#0ea5e9',
            'bg'     => '#e0f2fe',
            'border' => '#bae6fd',
        ],
    ];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

{{-- ══ Greeting card ══════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4 align-items-stretch">
    <div class="col-12 order-0">
        <div class="card h-100">
            <div class="d-flex align-items-start row">
                <div class="col-sm-8">
                    <div class="card-body">
                        <h5 class="card-title text-primary mb-3">{{ $greeting }}, {{ $userName }}! 👋</h5>
                        <p class="mb-6">Welcome to Document Management System where you have
                            <strong>{{ $sentNow }} documents</strong> processed this month.
                            <br /><br /><strong>Keep up the excellent workflow management!</strong>
                        </p>
                    </div>
                </div>
                <div class="col-sm-4 text-center text-sm-start">
                    <div class="card-body pb-0 px-0 px-md-6">
                        <img src="{{ asset('assets/img/illustrations/man-with-laptop.png') }}" height="175" alt="Document Management" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══ Mini stat cards ════════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">
    @foreach($miniStats as $s)
    @php $up = $s['delta'] >= 0; @endphp
    <div class="col-12 col-sm-6 col-xl-3">
        <a href="{{ $s['route'] }}" class="text-decoration-none d-block h-100">
            <div class="dms-stat-card h-100">
                <div class="dms-stat-top">
                    <div class="dms-stat-icon"
                         style="background:{{ $s['bg'] }}; border:1px solid {{ $s['border'] }};">
                        <i class="bx {{ $s['icon'] }}" style="color:{{ $s['color'] }};"></i>
                    </div>
                    <span class="dms-stat-num">{{ number_format($s['count']) }}</span>
                </div>
                <div class="dms-stat-label">{{ $s['label'] }}</div>
                <div class="dms-stat-sub">{{ $s['sub'] }}</div>
                <div class="dms-stat-bar" style="background:{{ $s['color'] }};"></div>
            </div>
        </a>
    </div>
    @endforeach
</div>

{{-- ══ Monthly Report Chart ════════════════════════════════════════════════════ --}}
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="card-title mb-1">Monthly Document Report</h5>
                    <p class="card-subtitle mb-0">Documents processed over the last 12 months</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center gap-1">
                        <span class="dms-legend-dot" style="background:#6366f1;"></span>
                        <small class="text-muted">Sent</small>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="dms-legend-dot" style="background:#22c55e;"></span>
                        <small class="text-muted">Received</small>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="dms-legend-dot" style="background:#f59e0b;"></span>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
            </div>
            <div class="card-body pt-2">
                <div id="monthlyBarChart"></div>
            </div>
        </div>
    </div>
</div>

{{-- ══ Weekly Processing + Document Types + Recent Activity ══════════════════ --}}
<div class="row g-3 align-items-stretch mb-4">

    {{-- Weekly processing trend ------------------------------------------------ --}}
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="card-title mb-1">Weekly Processing Trend</h5>
                    <p class="card-subtitle mb-0">Documents processed &amp; pending — last 8 weeks</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center gap-1">
                        <span class="dms-legend-dot" style="background:#0ea5e9;"></span>
                        <small class="text-muted">Processed</small>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="dms-legend-dot" style="background:#f59e0b;"></span>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
            </div>
            <div class="card-body pt-2">
                <div id="weeklyLineChart"></div>
            </div>
        </div>
    </div>

    {{-- Month summary donut + stats -------------------------------------------- --}}
    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-1">This Month Summary</h5>
                <p class="card-subtitle mb-0">{{ now()->format('F Y') }}</p>
            </div>
            <div class="card-body d-flex flex-column align-items-center">
                <div id="summaryDonut"></div>

                <div class="w-100 mt-3">
                    @php
                        $summaryRows = [
                            ['label' => 'Sent',     'count' => $sentNow,     'color' => '#6366f1'],
                            ['label' => 'Received', 'count' => $receivedNow, 'color' => '#22c55e'],
                            ['label' => 'Pending',  'count' => $pendingNow,  'color' => '#f59e0b'],
                        ];
                    @endphp
                    @foreach($summaryRows as $row)
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <span class="dms-legend-dot" style="background:{{ $row['color'] }};"></span>
                            <span class="text-body-secondary" style="font-size:.85rem;">{{ $row['label'] }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <strong style="font-size:.95rem;">{{ number_format($row['count']) }}</strong>
                            @php $pct = $totalNow > 0 ? round(($row['count'] / $totalNow) * 100) : 0; @endphp
                            <span class="badge" style="background:{{ $row['color'] }}20; color:{{ $row['color'] }}; font-size:.72rem;">{{ $pct }}%</span>
                        </div>
                    </div>
                    @endforeach

                    <hr class="my-3" />
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="text-body-secondary" style="font-size:.85rem;">Total</span>
                        <strong style="font-size:1rem;">{{ number_format($totalNow) }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══ Bottom row: Document Types + Recent Activity ═══════════════════════════ --}}
<div class="row g-3 align-items-stretch">
    <div class="col-md-6 col-lg-4 col-xl-4 order-0 mb-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <div class="card-title mb-0">
                    <h5 class="mb-1 me-2">Document Types</h5>
                    <p class="card-subtitle">{{ $sentNow }} Total Documents</p>
                </div>
            </div>
            <div class="card-body">
                <ul class="p-0 m-0">
                    <li class="d-flex align-items-center mb-5">
                        <div class="avatar flex-shrink-0 me-3"><span class="avatar-initial rounded bg-label-primary"><i class="icon-base bx bx-file-blank"></i></span></div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2"><h6 class="mb-0">Reports</h6><small>Monthly, Quarterly</small></div>
                            <div class="user-progress"><h6 class="mb-0">0</h6></div>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-5">
                        <div class="avatar flex-shrink-0 me-3"><span class="avatar-initial rounded bg-label-success"><i class="icon-base bx bx-list-check"></i></span></div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2"><h6 class="mb-0">Requests</h6><small>Leave, Approval</small></div>
                            <div class="user-progress"><h6 class="mb-0">0</h6></div>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-5">
                        <div class="avatar flex-shrink-0 me-3"><span class="avatar-initial rounded bg-label-info"><i class="icon-base bx bx-envelope"></i></span></div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2"><h6 class="mb-0">Memos</h6><small>Notices, Circulars</small></div>
                            <div class="user-progress"><h6 class="mb-0">0</h6></div>
                        </div>
                    </li>
                    <li class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3"><span class="avatar-initial rounded bg-label-secondary"><i class="icon-base bx bx-note"></i></span></div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2"><h6 class="mb-0">Certificates</h6><small>Credentials, Awards</small></div>
                            <div class="user-progress"><h6 class="mb-0">0</h6></div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-8 order-1 mb-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title m-0 me-2">Recent Activity</h5>
            </div>
            <div class="card-body pt-4">
                <ul class="p-0 m-0">
                    <li class="d-flex align-items-center mb-6">
                        <div class="avatar flex-shrink-0 me-3"><span class="avatar-initial rounded-circle bg-label-success"><i class="icon-base bx bx-check"></i></span></div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2"><small class="d-block">Approved</small><h6 class="fw-normal mb-0">Leave Request</h6></div>
                            <div class="user-progress d-flex align-items-center gap-2"><h6 class="fw-normal mb-0">Today</h6><span class="text-body-secondary">10:30 AM</span></div>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-6">
                        <div class="avatar flex-shrink-0 me-3"><span class="avatar-initial rounded-circle bg-label-info"><i class="icon-base bx bx-transfer"></i></span></div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2"><small class="d-block">Routed</small><h6 class="fw-normal mb-0">Monthly Report</h6></div>
                            <div class="user-progress d-flex align-items-center gap-2"><h6 class="fw-normal mb-0">Yesterday</h6><span class="text-body-secondary">2:15 PM</span></div>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-6">
                        <div class="avatar flex-shrink-0 me-3"><span class="avatar-initial rounded-circle bg-label-warning"><i class="icon-base bx bx-time"></i></span></div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2"><small class="d-block">Pending</small><h6 class="fw-normal mb-0">Approval Needed</h6></div>
                            <div class="user-progress d-flex align-items-center gap-2"><h6 class="fw-normal mb-0">2 days</h6><span class="text-body-secondary">Ago</span></div>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-6">
                        <div class="avatar flex-shrink-0 me-3"><span class="avatar-initial rounded-circle bg-label-success"><i class="icon-base bx bx-check"></i></span></div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2"><small class="d-block">Processed</small><h6 class="fw-normal mb-0">Certificate</h6></div>
                            <div class="user-progress d-flex align-items-center gap-2"><h6 class="fw-normal mb-0">3 days</h6><span class="text-body-secondary">Ago</span></div>
                        </div>
                    </li>
                    <li class="d-flex align-items-center mb-6">
                        <div class="avatar flex-shrink-0 me-3"><span class="avatar-initial rounded-circle bg-label-info"><i class="icon-base bx bx-transfer"></i></span></div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2"><small class="d-block">Forwarded</small><h6 class="fw-normal mb-0">Memo Circulation</h6></div>
                            <div class="user-progress d-flex align-items-center gap-2"><h6 class="fw-normal mb-0">5 days</h6><span class="text-body-secondary">Ago</span></div>
                        </div>
                    </li>
                    <li class="d-flex align-items-center">
                        <div class="avatar flex-shrink-0 me-3"><span class="avatar-initial rounded-circle bg-label-danger"><i class="icon-base bx bx-x"></i></span></div>
                        <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                            <div class="me-2"><small class="d-block">Rejected</small><h6 class="fw-normal mb-0">Incomplete Form</h6></div>
                            <div class="user-progress d-flex align-items-center gap-2"><h6 class="fw-normal mb-0">1 week</h6><span class="text-body-secondary">Ago</span></div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

</div>{{-- /container-xxl --}}

{{-- ══ Styles ════════════════════════════════════════════════════════════════ --}}
<style>
.card { box-shadow: none !important; }

/* ── stat cards ── */
.dms-stat-card {
    background: #ffffff;
    border: 1px solid #eef0f6;
    border-radius: 12px;
    padding: 20px 20px 0;
    position: relative;
    overflow: hidden;
    transition: transform .18s ease;
    cursor: pointer;
}
.dms-stat-card:hover { transform: translateY(-4px); }
.dms-stat-top { display: flex; align-items: center; gap: 14px; margin-bottom: 10px; }
.dms-stat-icon {
    width: 46px; height: 46px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.dms-stat-icon i { font-size: 1.45rem; }
.dms-stat-num { font-size: 2rem; font-weight: 700; color: #1a1d3a; line-height: 1; letter-spacing: -0.5px; }
.dms-stat-label { font-size: 0.875rem; color: #374151; font-weight: 500; margin-bottom: 2px; }
.dms-stat-sub { font-size: 0.76rem; color: #9ca3af; margin-bottom: 10px; }
.dms-stat-bar { height: 3px; margin: 0 -20px; opacity: 0.55; }

/* ── legend dot ── */
.dms-legend-dot {
    width: 10px; height: 10px; border-radius: 3px; display: inline-block; flex-shrink: 0;
}
</style>

{{-- ══ ApexCharts ════════════════════════════════════════════════════════════ --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Shared palette ────────────────────────────────────────────────────────
    var SENT_COLOR     = '#6366f1';
    var RECEIVED_COLOR = '#22c55e';
    var PENDING_COLOR  = '#f59e0b';
    var TOTAL_COLOR    = '#0ea5e9';

    // ── PHP → JS data ─────────────────────────────────────────────────────────
    var monthlyLabels   = @json($monthlyLabels);
    var monthlySent     = @json($monthlySent);
    var monthlyReceived = @json($monthlyReceived);
    var monthlyPending  = @json($monthlyPending);

    var weeklyLabels    = @json($weeklyLabels);
    var weeklyProcessed = @json($weeklyProcessed);
    var weeklyPending   = @json($weeklyPending);

    var sentNow     = {{ $sentNow }};
    var receivedNow = {{ $receivedNow }};
    var pendingNow  = {{ $pendingNow }};
    var totalNow    = {{ $totalNow }};

    // ── 1. Monthly grouped bar chart ─────────────────────────────────────────
    var monthlyBarOptions = {
        series: [
            { name: 'Sent',     data: monthlySent     },
            { name: 'Received', data: monthlyReceived  },
            { name: 'Pending',  data: monthlyPending   },
        ],
        chart: {
            type: 'bar',
            height: 320,
            stacked: false,
            toolbar: {
                show: true,
                tools: { download: true, selection: false, zoom: false, zoomin: false, zoomout: false, pan: false, reset: false },
            },
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                borderRadius: 5,
                borderRadiusApplication: 'end',
            },
        },
        dataLabels: { enabled: false },
        colors: [SENT_COLOR, RECEIVED_COLOR, PENDING_COLOR],
        xaxis: {
            categories: monthlyLabels,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: {
                style: { fontSize: '12px', colors: '#9ca3af' },
                rotate: -30,
            },
        },
        yaxis: {
            labels: {
                style: { fontSize: '12px', colors: '#9ca3af' },
                formatter: function (val) { return Math.round(val); },
            },
        },
        legend: { show: false },
        grid: {
            borderColor: '#f3f4f6',
            strokeDashArray: 4,
            yaxis: { lines: { show: true } },
            xaxis: { lines: { show: false } },
        },
        tooltip: {
            theme: 'light',
            y: { formatter: function (val) { return val + ' docs'; } },
        },
        fill: { opacity: 1 },
    };

    var monthlyBarChart = new ApexCharts(document.getElementById('monthlyBarChart'), monthlyBarOptions);
    monthlyBarChart.render();

    // ── 2. Weekly line + area chart ──────────────────────────────────────────
    var weeklyLineOptions = {
        series: [
            { name: 'Processed', data: weeklyProcessed },
            { name: 'Pending',   data: weeklyPending   },
        ],
        chart: {
            type: 'area',
            height: 300,
            toolbar: { show: false },
            sparkline: { enabled: false },
        },
        stroke: {
            curve: 'smooth',
            width: [2.5, 2],
            dashArray: [0, 4],
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.25,
                opacityTo: 0.02,
                stops: [0, 95, 100],
            },
        },
        colors: [TOTAL_COLOR, PENDING_COLOR],
        markers: { size: 4, strokeWidth: 0, hover: { size: 6 } },
        xaxis: {
            categories: weeklyLabels,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { fontSize: '12px', colors: '#9ca3af' } },
        },
        yaxis: {
            labels: {
                style: { fontSize: '12px', colors: '#9ca3af' },
                formatter: function (val) { return Math.round(val); },
            },
        },
        legend: { show: false },
        grid: {
            borderColor: '#f3f4f6',
            strokeDashArray: 4,
            yaxis: { lines: { show: true } },
            xaxis: { lines: { show: false } },
        },
        dataLabels: { enabled: false },
        tooltip: {
            theme: 'light',
            y: { formatter: function (val) { return val + ' docs'; } },
        },
    };

    var weeklyLineChart = new ApexCharts(document.getElementById('weeklyLineChart'), weeklyLineOptions);
    weeklyLineChart.render();

    // ── 3. Monthly summary donut ─────────────────────────────────────────────
    var donutOptions = {
        series: [sentNow, receivedNow, pendingNow],
        chart: {
            type: 'donut',
            height: 200,
        },
        labels: ['Sent', 'Received', 'Pending'],
        colors: [SENT_COLOR, RECEIVED_COLOR, PENDING_COLOR],
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total',
                            fontSize: '13px',
                            color: '#6b7280',
                            formatter: function () { return totalNow; },
                        },
                        value: {
                            fontSize: '20px',
                            fontWeight: 700,
                            color: '#1a1d3a',
                        },
                    },
                },
            },
        },
        dataLabels: { enabled: false },
        legend: { show: false },
        stroke: { width: 0 },
        tooltip: {
            y: { formatter: function (val) { return val + ' docs'; } },
        },
    };

    var summaryDonut = new ApexCharts(document.getElementById('summaryDonut'), donutOptions);
    summaryDonut.render();

});
</script>

@endsection