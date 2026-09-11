@extends('layouts.admin')

@section('content')
    <style>
        .staff-detail {
            padding: 12px;
            background: #f4f6f8;
            min-height: 100vh;
        }

        .detail-header,
        .filter-card,
        .stat-card,
        .graph-card {
            background: #fff;
            border: 1px solid #d8dde3;
            border-radius: 7px;
        }

        .detail-header {
            padding: 12px 15px;
            margin-bottom: 10px;
        }

        .staff-name {
            font-size: 22px;
            font-weight: 700;
            color: #20252b;
        }

        .staff-client {
            font-size: 12px;
            color: #666;
            margin-top: 2px;
        }

        .staff-month {
            color: #777;
            font-size: 11px;
            margin-top: 2px;
        }

        .filter-card {
            padding: 10px;
            margin-bottom: 10px;
        }

        .filter-card label {
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .filter-card .form-control {
            height: 35px;
            font-size: 12px;
        }

        .stat-card {
            padding: 12px;
            height: 100%;
        }

        .stat-title {
            font-size: 10px;
            color: #777;
            font-weight: 700;
            text-transform: uppercase;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            line-height: 27px;
            margin-top: 3px;
        }

        .stat-sub {
            font-size: 10px;
            color: #666;
            margin-top: 3px;
        }

        .graph-card {
            padding: 12px;
            margin-top: 10px;
        }

        .graph-title {
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .graph-box {
            height: 280px;
            position: relative;
        }

        .points-table th {
            background: #212529;
            color: #fff;
            font-size: 10px;
            white-space: nowrap;
            vertical-align: middle;
        }

        .points-table td {
            font-size: 10px;
            white-space: nowrap;
            vertical-align: middle;
        }

        .positive {
            color: #198754;
            font-weight: 700;
        }

        .negative {
            color: #dc3545;
            font-weight: 700;
        }

        .neutral {
            color: #6c757d;
            font-weight: 700;
        }

        .points-breakdown {
            font-size: 9px;
            color: #666;
            line-height: 14px;
            white-space: nowrap;
        }

        @media (max-width: 767px) {
            .staff-detail {
                padding: 7px;
            }

            .staff-name {
                font-size: 18px;
            }

            .graph-box {
                height: 230px;
            }

            .stat-value {
                font-size: 20px;
            }
        }
    </style>

    <div class="container-fluid staff-detail">

        {{-- =====================================================
         HEADER
    ====================================================== --}}
        <div class="detail-header">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <div class="staff-name">
                        {{ $staff->name ?? 'Staff' }}
                    </div>

                    @if (!empty($clientName))
                        <div class="staff-client">
                            Client: {{ $clientName }}
                        </div>
                    @endif

                    <div class="staff-month">
                        Points Detail:
                        {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}
                        -
                        {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                    </div>

                </div>

                <div>

                    <a href="{{ route('staff.performance.report', request()->query()) }}" class="btn btn-outline-dark btn-sm">
                        ← Back
                    </a>

                </div>

            </div>

        </div>


        {{-- =====================================================
         DATE FILTER
    ====================================================== --}}
        <div class="filter-card">

            <form method="GET" action="{{ route('staff.performance.detail', ['staffId' => $staff->id]) }}">

                @if (request()->filled('client_id'))
                    <input type="hidden" name="client_id" value="{{ request('client_id') }}">
                @endif

                <div class="row g-2 align-items-end">

                    <div class="col-md-4">

                        <label>Date From</label>

                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">

                    </div>

                    <div class="col-md-4">

                        <label>Date To</label>

                        <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">

                    </div>

                    <div class="col-md-4">

                        <button type="submit" class="btn btn-primary w-100">
                            Apply
                        </button>

                    </div>

                </div>

            </form>

        </div>


        {{-- =====================================================
         SUMMARY
    ====================================================== --}}
        <div class="row g-2">

            {{-- TOTAL POINTS --}}
            <div class="col-xl-2 col-lg-4 col-md-4 col-6">

                <div class="stat-card">

                    <div class="stat-title">
                        Total Points
                    </div>

                    <div class="stat-value text-primary">
                        {{ $totalPoints ?? 0 }}
                    </div>

                </div>

            </div>


            {{-- COD --}}
            <div class="col-xl-2 col-lg-4 col-md-4 col-6">

                <div class="stat-card">

                    <div class="stat-title">
                        COD
                    </div>

                    <div class="stat-value">
                        {{ $totalCod ?? 0 }}
                    </div>

                    <div class="stat-sub positive">
                        +{{ $totalCodPoints ?? ($totalCod ?? 0) * 2 }} Points
                    </div>

                </div>

            </div>


            {{-- VPP --}}
            <div class="col-xl-2 col-lg-4 col-md-4 col-6">

                <div class="stat-card">

                    <div class="stat-title">
                        VPP
                    </div>

                    <div class="stat-value">
                        {{ $totalVpp ?? 0 }}
                    </div>

                    <div class="stat-sub positive">
                        +{{ $totalVppPoints ?? ($totalVpp ?? 0) * 2 }} Points
                    </div>

                </div>

            </div>


            {{-- PREPAID --}}
            <div class="col-xl-2 col-lg-4 col-md-4 col-6">

                <div class="stat-card">

                    <div class="stat-title">
                        Prepaid
                    </div>

                    <div class="stat-value">
                        {{ $totalPrepaid ?? 0 }}
                    </div>

                    <div class="stat-sub positive">
                        +{{ $totalPrepaidPoints ?? ($totalPrepaid ?? 0) * 4 }} Points
                    </div>

                </div>

            </div>


            {{-- DELIVERED --}}
            <div class="col-xl-2 col-lg-4 col-md-4 col-6">

                <div class="stat-card">

                    <div class="stat-title">
                        Delivered
                    </div>

                    <div class="stat-value text-success">
                        {{ $totalDelivered ?? 0 }}
                    </div>

                    <div class="stat-sub positive">
                        +{{ $totalDeliveredPoints ?? ($totalDelivered ?? 0) * 4 }} Points
                    </div>

                </div>

            </div>


            {{-- RTO --}}
            <div class="col-xl-2 col-lg-4 col-md-4 col-6">

                <div class="stat-card">

                    <div class="stat-title">
                        RTO-Intrasit
                    </div>

                    <div class="stat-value text-danger">
                        {{ $totalRto ?? 0 }}
                    </div>

                    <div class="stat-sub negative">
                        {{ $totalRtoPoints ?? ($totalRto ?? 0) * -1 }} Points
                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
         POINT FORMULA
    ====================================================== --}}
        <div class="graph-card">

            <div class="graph-title">
                Point Rules
            </div>

            <div class="points-breakdown">

                COD +2
                &nbsp; | &nbsp;

                VPP +2
                &nbsp; | &nbsp;

                Prepaid +4
                &nbsp; | &nbsp;

                Delivered +4
                &nbsp; | &nbsp;

                RTO-Intrasit -1

            </div>

        </div>


        {{-- =====================================================
         DAILY POINT GRAPH
    ====================================================== --}}
        <div class="graph-card">

            <div class="graph-title">
                Daily Points — Up / Down
            </div>

            <div class="graph-box">
                <canvas id="pointsChart"></canvas>
            </div>

        </div>


        {{-- =====================================================
         CUMULATIVE GRAPH
    ====================================================== --}}
        <div class="graph-card">

            <div class="graph-title">
                Cumulative Points
            </div>

            <div class="graph-box">
                <canvas id="cumulativeChart"></canvas>
            </div>

        </div>


        {{-- =====================================================
         DAY-WISE TABLE
    ====================================================== --}}
        <div class="graph-card">

            <div class="d-flex justify-content-between align-items-center mb-2">

                <div class="graph-title mb-0">
                    Day-wise Points
                </div>

                <div class="small text-muted">
                    {{ count($dailyPoints ?? []) }} Days
                </div>

            </div>

            <div class="table-responsive">

                <table class="table table-bordered table-sm points-table mb-0">

                    <thead>

                        <tr>

                            <th>Date</th>
                            <th>Day</th>

                            <th>COD</th>
                            <th>VPP</th>
                            <th>Prepaid</th>
                            <th>Delivered</th>
                            <th>RTO</th>

                            <th>Net Points</th>
                            <th>Cumulative</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($dailyPoints ?? [] as $day)
                            <tr>

                                <td>
                                    {{ $day['display_date'] ?? $day['date'] }}
                                </td>

                                <td>
                                    {{ $day['day'] ?? '-' }}
                                </td>


                                {{-- COD --}}
                                <td>

                                    {{ $day['cod'] ?? 0 }}

                                    @if (($day['cod_points'] ?? 0) > 0)
                                        <small class="positive d-block">
                                            +{{ $day['cod_points'] }}
                                        </small>
                                    @endif

                                </td>


                                {{-- VPP --}}
                                <td>

                                    {{ $day['vpp'] ?? 0 }}

                                    @if (($day['vpp_points'] ?? 0) > 0)
                                        <small class="positive d-block">
                                            +{{ $day['vpp_points'] }}
                                        </small>
                                    @endif

                                </td>


                                {{-- PREPAID --}}
                                <td>

                                    {{ $day['prepaid'] ?? 0 }}

                                    @if (($day['prepaid_points'] ?? 0) > 0)
                                        <small class="positive d-block">
                                            +{{ $day['prepaid_points'] }}
                                        </small>
                                    @endif

                                </td>


                                {{-- DELIVERED --}}
                                <td>

                                    {{ $day['delivered'] ?? 0 }}

                                    @if (($day['delivered_points'] ?? 0) > 0)
                                        <small class="positive d-block">
                                            +{{ $day['delivered_points'] }}
                                        </small>
                                    @endif

                                </td>


                                {{-- RTO --}}
                                <td>

                                    {{ $day['rto'] ?? 0 }}

                                    @if (($day['rto_points'] ?? 0) < 0)
                                        <small class="negative d-block">
                                            {{ $day['rto_points'] }}
                                        </small>
                                    @endif

                                </td>


                                {{-- NET --}}
                                <td>

                                    @if (($day['points'] ?? 0) > 0)
                                        <span class="positive">
                                            +{{ $day['points'] }}
                                        </span>
                                    @elseif(($day['points'] ?? 0) < 0)
                                        <span class="negative">
                                            {{ $day['points'] }}
                                        </span>
                                    @else
                                        <span class="neutral">
                                            0
                                        </span>
                                    @endif

                                </td>


                                {{-- CUMULATIVE --}}
                                <td>

                                    <strong>
                                        {{ $day['cumulative_points'] ?? 0 }}
                                    </strong>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="9" class="text-center text-muted py-4">

                                    No points data found.

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    {{-- =====================================================
     CHART JS
====================================================== --}}

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const labels =
            @json($chartLabels ?? []);

        const points =
            @json($chartPoints ?? []);

        const cumulative =
            @json($chartCumulative ?? []);


        /*
        |--------------------------------------------------------------------------
        | DAILY POINTS
        |--------------------------------------------------------------------------
        */

        const pointsCanvas =
            document.getElementById('pointsChart');

        if (pointsCanvas) {

            new Chart(
                pointsCanvas, {

                    type: 'bar',

                    data: {

                        labels: labels,

                        datasets: [{
                            label: 'Daily Points',

                            data: points,

                            borderWidth: 1
                        }]

                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        scales: {

                            y: {

                                beginAtZero: true

                            }

                        },

                        plugins: {

                            tooltip: {

                                callbacks: {

                                    label: function(context) {

                                        const value =
                                            Number(context.raw || 0);

                                        return (
                                            value >= 0 ?
                                            '+' + value :
                                            value
                                        ) + ' Points';

                                    }

                                }

                            }

                        }

                    }

                }
            );

        }


        /*
        |--------------------------------------------------------------------------
        | CUMULATIVE
        |--------------------------------------------------------------------------
        */

        const cumulativeCanvas =
            document.getElementById('cumulativeChart');

        if (cumulativeCanvas) {

            new Chart(
                cumulativeCanvas, {

                    type: 'line',

                    data: {

                        labels: labels,

                        datasets: [{
                            label: 'Cumulative Points',

                            data: cumulative,

                            tension: 0.3,

                            borderWidth: 3,

                            fill: false,

                            pointRadius: 3
                        }]

                    },

                    options: {

                        responsive: true,

                        maintainAspectRatio: false,

                        scales: {

                            y: {

                                beginAtZero: true

                            }

                        }

                    }

                }
            );

        }
    </script>
@endsection
