@extends('layouts/contentNavbarLayout')

<title>Document Management System</title>

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

@php
    use Illuminate\Support\Facades\Auth;
    use App\Models\Recipient;
    use App\Models\ReceivedDocument;
    use App\Models\Document;

    $uid       = Auth::id();
    $month     = now();
    $lastMonth = now()->subMonth();

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

    $archivedNow = Document::where('user_id', $uid)->where('status', 'archived')
        ->whereMonth('created_at', $month->month)->whereYear('created_at', $month->year)->count();

    $archivedLast = Document::where('user_id', $uid)->where('status', 'archived')
        ->whereMonth('created_at', $lastMonth->month)->whereYear('created_at', $lastMonth->year)->count();

    $delta = fn($cur, $prev) => $prev == 0 ? ($cur > 0 ? 100.0 : 0.0) : round((($cur - $prev) / $prev) * 100, 1);

    date_default_timezone_set('Asia/Manila');
    $hour     = (int) date('H');
    $greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');
    $userName = Auth::user()->employee->firstname;

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
            'count'  => $archivedNow,
            'label'  => 'Archived documents',
            'sub'    => 'Stored for reference',
            'delta'  => $delta($archivedNow, $archivedLast),
            'route'  => route('documents.archived'),
            'icon'   => 'bx-archive',
            'color'  => '#06b6d4',
            'bg'     => '#cffafe',
            'border' => '#a5f3fc',
        ],
    ];
@endphp

<div class="container-xxl flex-grow-1 container-p-y">

<div class="row">
    <div class="col-12 mb-3 order-0">
        <div class="card">
            <div class="d-flex align-items-start row">
                <div class="col-sm-8">
                    <div class="card-body">
                        <h5 class="card-title text-primary mb-3">{{ $greeting }}, {{ $userName }}! 👋</h5>
                        <p class="mb-6">Welcome to Document Management System where you have
                            <strong>{{ $sentNow }} documents</strong> processed this month.
                            <br /><br /><strong>Keep up the excellent workflow management!</strong>
                        </p>
                        {{-- <a href="{{ route('documents.sent') }}" class="btn btn-sm btn-outline-primary">View Documents</a> --}}
                    </div>
                </div>
                <div class="col-sm-4 text-center text-sm-left">
                    <div class="card-body pb-0 px-0 px-md-6">
                        <img src="{{ asset('assets/img/illustrations/man-with-laptop.png') }}" height="175" alt="Document Management" />
                    </div>
                </div>
            </div>
        </div>
    </div>
{{-- ══════════════════════════════════════════
     4 STAT CARDS  (matches screenshot layout)
     ══════════════════════════════════════════ --}}
<div class="row g-2 mb-4 w-100">
    @foreach($miniStats as $s)
    @php $up = $s['delta'] >= 0; @endphp
    <div class="col-sm-6 col-xl-3">
        <a href="{{ $s['route'] }}" class="text-decoration-none">
            <div class="dms-stat-card">

                {{-- Icon + Big Number (side by side, exactly like screenshot) --}}
                <div class="dms-stat-top">
                    <div class="dms-stat-icon"
                         style="background:{{ $s['bg'] }}; border:1px solid {{ $s['border'] }};">
                        <i class="bx {{ $s['icon'] }}" style="color:{{ $s['color'] }};"></i>
                    </div>
                    <span class="dms-stat-num">{{ number_format($s['count']) }}</span>
                </div>

                {{-- Label --}}
                <div class="dms-stat-label">{{ $s['label'] }}</div>

                {{-- Sub-label --}}
                <div class="dms-stat-sub">{{ $s['sub'] }}</div>

                {{-- % Change
                <div class="dms-stat-delta">
                    <span class="dms-delta-pct" style="color:{{ $up ? '#16a34a' : '#dc2626' }};">
                        {{-- arrow svg --}}
                        {{-- <svg width="11" height="11" viewBox="0 0 12 12" fill="none">
                            @if($up)
                            <path d="M6 9.5V2.5M6 2.5L2.5 6M6 2.5L9.5 6"
                                  stroke="#16a34a" stroke-width="1.8"
                                  stroke-linecap="round" stroke-linejoin="round"/>  
                            @else
                            <path d="M6 2.5V9.5M6 9.5L2.5 6M6 9.5L9.5 6"
                                  stroke="#dc2626" stroke-width="1.8"
                                  stroke-linecap="round" stroke-linejoin="round"/>
                            @endif
                        </svg>
                        {{ $up ? '+' : '' }}{{ $s['delta'] }}%
                    </span>
                    <span class="dms-delta-text">than last month</span>
                </div>  --}}

                {{-- Coloured bottom bar --}}
                <div class="dms-stat-bar" style="background:{{ $s['color'] }};"></div>
            </div>
        </a>
    </div>
    @endforeach
</div>

{{-- ══════════════════════════════
     GREETING CARD + small cards
     ══════════════════════════════ --}}

{{-- 
    <div class="col-xxl-4 col-lg-12 col-md-4 order-1">
        <div class="row">
            <div class="col-lg-6 col-md-12 col-6 mb-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img src="{{ asset('assets/img/icons/unicons/chart-success.png') }}" alt="chart success" class="rounded" />
                            </div>
                            <div class="dropdown">
                                <button class="btn p-0" type="button" id="cardOpt3" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt3">
                                    <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                    <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                </div>
                            </div>
                        </div>
                        <p class="mb-1">Completed</p>
                        <h4 class="card-title mb-3">{{ $receivedNow }}</h4>
                        <small class="text-{{ $delta($receivedNow,$receivedLast) >= 0 ? 'success':'danger' }} fw-medium">
                            <i class="icon-base bx bx-{{ $delta($receivedNow,$receivedLast) >= 0 ? 'up':'down' }}-arrow-alt"></i>
                            {{ $delta($receivedNow,$receivedLast) }}%
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 col-md-12 col-6 mb-6">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img src="{{ asset('assets/img/icons/unicons/wallet-info.png') }}" alt="wallet info" class="rounded" />
                            </div>
                            <div class="dropdown">
                                <button class="btn p-0" type="button" id="cardOpt6" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt6">
                                    <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                    <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                </div>
                            </div>
                        </div>
                        <p class="mb-1">Pending</p>
                        <h4 class="card-title mb-3">{{ $pendingNow }}</h4>
                        <small class="text-warning fw-medium">
                            <i class="icon-base bx bx-up-arrow-alt"></i>
                            {{ $delta($pendingNow,$pendingLast) }}%
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    {{-- <!-- Document Processing Overview -->
    <div class="col-12 col-xxl-8 order-2 order-md-3 order-xxl-2 mb-6 total-revenue">
        <div class="card">
            <div class="row row-bordered g-0">
                <div class="col-lg-8">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <div class="card-title mb-0">
                            <h5 class="m-0 me-2">Document Processing Overview</h5>
                        </div>
                        <div class="dropdown">
                            <button class="btn p-0" type="button" id="totalRevenue" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="icon-base bx bx-dots-vertical-rounded icon-lg text-body-secondary"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="totalRevenue">
                                <a class="dropdown-item" href="javascript:void(0);">Select All</a>
                                <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
                                <a class="dropdown-item" href="javascript:void(0);">Share</a>
                            </div>
                        </div>
                    </div>
                    <div id="totalRevenueChart" class="px-3"></div>
                </div>
                <div class="col-lg-4">
                    <div class="card-body px-xl-9 py-12 d-flex align-items-center flex-column">
                        <div class="text-center mb-6">
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-primary">
                                    <script>document.write(new Date().getFullYear());</script>
                                </button>
                                <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);">2025</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">2024</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);">2023</a></li>
                                </ul>
                            </div>
                        </div>
                        <div id="growthChart"></div>
                        <div class="text-center fw-medium my-6">
                            {{ $sentNow > 0 ? round(($receivedNow / $sentNow) * 100) : 0 }}% Processing Rate
                        </div>
                        <div class="d-flex gap-11 justify-content-between">
                            <div class="d-flex">
                                <div class="avatar me-2">
                                    <span class="avatar-initial rounded-2 bg-label-primary"><i class="icon-base bx bx-file-blank icon-lg text-primary"></i></span>
                                </div>
                                <div class="d-flex flex-column">
                                    <small>This Month</small>
                                    <h6 class="mb-0">{{ $sentNow }}</h6>
                                </div>
                            </div>
                            <div class="d-flex">
                                <div class="avatar me-2">
                                    <span class="avatar-initial rounded-2 bg-label-info"><i class="icon-base bx bx-check-circle icon-lg text-info"></i></span>
                                </div>
                                <div class="d-flex flex-column">
                                    <small>Last Month</small>
                                    <h6 class="mb-0">{{ $sentLast }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-8 col-lg-12 col-xxl-4 order-3 order-md-2 profile-report">
        <div class="row">
            <div class="col-6 mb-6 payments">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img src="{{ asset('assets/img/icons/unicons/paypal.png') }}" alt="incoming" class="rounded" />
                            </div>
                            <div class="dropdown">
                                <button class="btn p-0" type="button" id="cardOpt4" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="cardOpt4">
                                    <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                    <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                </div>
                            </div>
                        </div>
                        <p class="mb-1">Incoming Documents</p>
                        <h4 class="card-title mb-3">{{ $pendingNow }}</h4>
                        <small class="text-{{ $delta($pendingNow,$pendingLast) >= 0 ? 'success':'danger' }} fw-medium">
                            <i class="icon-base bx bx-{{ $delta($pendingNow,$pendingLast) >= 0 ? 'up':'down' }}-arrow-alt"></i>
                            {{ $delta($pendingNow,$pendingLast) }}%
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-6 mb-6 transactions">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="card-title d-flex align-items-start justify-content-between mb-4">
                            <div class="avatar flex-shrink-0">
                                <img src="{{ asset('assets/img/icons/unicons/cc-primary.png') }}" alt="Outgoing Documents" class="rounded" />
                            </div>
                            <div class="dropdown">
                                <button class="btn p-0" type="button" id="cardOpt1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="icon-base bx bx-dots-vertical-rounded text-body-secondary"></i>
                                </button>
                                <div class="dropdown-menu" aria-labelledby="cardOpt1">
                                    <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                    <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                </div>
                            </div>
                        </div>
                        <p class="mb-1">Outgoing Documents</p>
                        <h4 class="card-title mb-3">{{ $sentNow }}</h4>
                        <small class="text-{{ $delta($sentNow,$sentLast) >= 0 ? 'success':'danger' }} fw-medium">
                            <i class="icon-base bx bx-{{ $delta($sentNow,$sentLast) >= 0 ? 'up':'down' }}-arrow-alt"></i>
                            {{ $delta($sentNow,$sentLast) }}%
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-12 mb-6 profile-report">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-sm-row flex-column gap-10 flex-wrap">
                            <div class="d-flex flex-sm-column flex-row align-items-start justify-content-between">
                                <div class="card-title mb-6">
                                    <h5 class="text-nowrap mb-1">Routing Performance</h5>
                                    <span class="badge bg-label-warning">PENDING</span>
                                </div>
                                <div class="mt-sm-auto">
                                    <span class="text-success text-nowrap fw-medium">
                                        <i class="icon-base bx bx-up-arrow-alt"></i>
                                        {{ $sentNow > 0 ? round(($receivedNow / $sentNow) * 100) : 0 }}%
                                    </span>
                                    <h4 class="mb-0">On-Time Delivery</h4>
                                </div>
                            </div>
                            <div id="profileReportChart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> --}}

<div class="row d-flex align-items-center">
    <div class="col-md-6 col-lg-4 col-xl-4 order-0 mb-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
                <div class="card-title mb-0">
                    <h5 class="mb-1 me-2">Document Types</h5>
                    <p class="card-subtitle">{{ $sentNow }} Total Documents</p>
                </div>
                <div class="dropdown">
                    <button class="btn text-body-secondary p-0" type="button" id="orederStatistics" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-base bx bx-dots-vertical-rounded icon-lg"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="orederStatistics">
                        <a class="dropdown-item" href="javascript:void(0);">Select All</a>
                        <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
                        <a class="dropdown-item" href="javascript:void(0);">Share</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-6">
                    <div class="d-flex flex-column align-items-center gap-1">
                        <h3 class="mb-1">{{ $sentNow }}</h3>
                        <small>Total Documents</small>
                    </div>
                    <div id="orderStatisticsChart"></div>
                </div>
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

    <div class="col-md-6 col-lg-4 order-1 mb-6">
        <div class="card h-100">
            <div class="card-header nav-align-top">
                <ul class="nav nav-pills flex-wrap row-gap-2" role="tablist">
                    <li class="nav-item"><button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-tabs-line-card-income" aria-controls="navs-tabs-line-card-income" aria-selected="true">Active</button></li>
                    <li class="nav-item"><button type="button" class="nav-link" role="tab">Archived</button></li>
                    <li class="nav-item"><button type="button" class="nav-link" role="tab">Rejected</button></li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content p-0">
                    <div class="tab-pane fade show active" id="navs-tabs-line-card-income" role="tabpanel">
                        <div class="d-flex mb-6">
                            <div class="avatar flex-shrink-0 me-3"><img src="{{ asset('assets/img/icons/unicons/wallet.png') }}" alt="Status" /></div>
                            <div>
                                <p class="mb-0">Active Documents</p>
                                <div class="d-flex align-items-center">
                                    <h6 class="mb-0 me-1">{{ $sentNow }}</h6>
                                    <small class="text-success fw-medium"><i class="icon-base bx bx-chevron-up icon-lg"></i>{{ $delta($sentNow,$sentLast) }}%</small>
                                </div>
                            </div>
                        </div>
                        <div id="incomeChart"></div>
                        <div class="d-flex align-items-center justify-content-center mt-6 gap-3">
                            <div class="flex-shrink-0"><div id="expensesOfWeek"></div></div>
                            <div><h6 class="mb-0">This week processed</h6><small>+156 more than last week</small></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-4 order-2 mb-6">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="card-title m-0 me-2">Recent Activity</h5>
                <div class="dropdown">
                    <button class="btn text-body-secondary p-0" type="button" id="transactionID" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-base bx bx-dots-vertical-rounded icon-lg"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="transactionID">
                        <a class="dropdown-item" href="javascript:void(0);">Last 7 Days</a>
                        <a class="dropdown-item" href="javascript:void(0);">Last 30 Days</a>
                        <a class="dropdown-item" href="javascript:void(0);">Last 90 Days</a>
                    </div>
                </div>
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

{{-- ══ STAT CARD CSS ══ --}}
<style>
.dms-stat-card {
    background: #ffffff;
    border: 1px solid #eef0f6;
    border-radius: 12px;
    padding: 20px 20px 0;
    position: relative;
    overflow: hidden;
    transition: transform .18s ease, box-shadow .18s ease;
    box-shadow: 0 1px 4px rgba(0,0,0,.04);
    cursor: pointer;
}
.dms-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 28px rgba(0,0,0,0.09);
}

/* top: icon + number side-by-side, exactly matching screenshot */
.dms-stat-top {
    display: flex;
    align-items: center;
    gap: 14px;
    margin-bottom: 10px;
}
.dms-stat-icon {
    width: 46px; height: 46px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.dms-stat-icon i { font-size: 1.45rem; }

.dms-stat-num {
    font-size: 2rem;
    font-weight: 700;
    color: #1a1d3a;
    line-height: 1;
    letter-spacing: -0.5px;
}

.dms-stat-label {
    font-size: 0.875rem;
    color: #374151;
    font-weight: 500;
    margin-bottom: 2px;
}

.dms-stat-sub {
    font-size: 0.76rem;
    color: #9ca3af;
    margin-bottom: 10px;
}

.dms-stat-delta {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.79rem;
    padding-bottom: 14px;
}
.dms-delta-pct {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    font-weight: 700;
}
.dms-delta-text { color: #9ca3af; }

/* thin coloured line at the bottom — like the screenshot's left border accent */
.dms-stat-bar {
    height: 3px;
    margin: 0 -20px;
    opacity: 0.55;
}
</style>

</div>

@endsection