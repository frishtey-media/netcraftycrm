@extends('layouts.admin')

@section('content')
    <style>
        body {
            background: #f4f6f9;
        }

        .report-header {
            background: #0d6efd;
            color: #fff;
            padding: 15px 20px;
            border-radius: 6px;
        }

        .report-header h4 {
            margin: 0;
            font-weight: 700;
        }

        .filter-card {
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        .stat-card {
            border: 0;
            border-radius: 7px;
            min-height: 110px;
            color: #fff;
        }

        .stat-card .title {
            font-size: 13px;
            font-weight: 600;
        }

        .stat-card .count {
            font-size: 30px;
            font-weight: 700;
            line-height: 1.2;
        }

        .stat-card .sub {
            font-size: 11px;
        }

        .bg-blue {
            background: #0d6efd;
        }

        .bg-green {
            background: #198754;
        }

        .bg-yellow {
            background: #ffc107;
            color: #111;
        }

        .bg-red {
            background: #dc3545;
        }

        .bg-dark-custom {
            background: #212529;
        }

        .bg-gray {
            background: #6c757d;
        }

        .bg-cyan {
            background: #0dcaf0;
            color: #111;
        }

        .metric-box {
            border: 1px solid #ddd;
            background: #fff;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
        }

        .metric-box .value {
            font-size: 22px;
            font-weight: 700;
        }

        .metric-box .label {
            font-size: 10px;
            color: #777;
        }

        .progress {
            height: 22px;
            border-radius: 20px;
        }

        .best-card {
            border: 2px solid #ffc107;
            border-radius: 8px;
            background: #fff;
        }

        .best-name {
            font-size: 25px;
            font-weight: 700;
        }

        .best-score {
            font-size: 32px;
            font-weight: 700;
        }

        .table th {
            white-space: nowrap;
            font-size: 11px;
        }

        .table td {
            white-space: nowrap;
            font-size: 11px;
            vertical-align: middle;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 8px;
        }
    </style>


    <div class="container-fluid py-2">


        {{-- ============================================================ --}}
        {{-- HEADER --}}
        {{-- ============================================================ --}}

        <div class="report-header mb-3 d-flex justify-content-between align-items-center">

            <h4>
                Staff Performance Report
            </h4>

            <strong>

                {{ \Carbon\Carbon::parse($dateFrom)->format('d-m-Y') }}

                to

                {{ \Carbon\Carbon::parse($dateTo)->format('d-m-Y') }}

            </strong>

        </div>



        {{-- ============================================================ --}}
        {{-- FILTER --}}
        {{-- ============================================================ --}}

        <div class="card filter-card mb-3">

            <div class="card-body">

                <form method="GET" action="{{ route('staff.performance.report') }}">

                    <div class="row g-2">


                        {{-- CLIENT --}}

                        <div class="col-lg-2 col-md-4">

                            <label class="fw-bold">
                                Client
                            </label>

                            <select name="client_id" id="client_id" class="form-select">

                                <option value="">
                                    All Clients
                                </option>

                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}"
                                        {{ request('client_id') == $client->id ? 'selected' : '' }}>

                                        {{ $client->client_name }}

                                    </option>
                                @endforeach

                            </select>

                        </div>



                        {{-- DATE FROM --}}

                        <div class="col-lg-2 col-md-4">

                            <label class="fw-bold">
                                Date From
                            </label>

                            <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">

                        </div>



                        {{-- DATE TO --}}

                        <div class="col-lg-2 col-md-4">

                            <label class="fw-bold">
                                Date To
                            </label>

                            <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">

                        </div>



                        {{-- STAFF --}}

                        <div class="col-lg-2 col-md-4">

                            <label class="fw-bold">
                                Staff
                            </label>

                            <select name="staff_id" id="staff_id" class="form-select">

                                <option value="">
                                    All Staff
                                </option>

                                @foreach ($staffs as $staff)
                                    <option value="{{ $staff->id }}"
                                        {{ request('staff_id') == $staff->id ? 'selected' : '' }}>

                                        {{ $staff->name }}

                                    </option>
                                @endforeach

                            </select>

                        </div>



                        {{-- SOURCE --}}

                        <div class="col-lg-2 col-md-4">

                            <label class="fw-bold">
                                Source
                            </label>

                            <select name="order_source" class="form-select">

                                <option value="">
                                    All Sources
                                </option>

                                @foreach ($sources as $source)
                                    <option value="{{ $source }}"
                                        {{ request('order_source') == $source ? 'selected' : '' }}>

                                        {{ ucwords(str_replace('-', ' ', $source)) }}

                                    </option>
                                @endforeach

                            </select>

                        </div>



                        {{-- CALL STATUS --}}

                        <div class="col-lg-2 col-md-4">

                            <label class="fw-bold">
                                Call Status
                            </label>

                            <select name="call_status" class="form-select">

                                <option value="">
                                    All Status
                                </option>

                                @foreach ($callStatuses as $status)
                                    <option value="{{ $status }}"
                                        {{ request('call_status') == $status ? 'selected' : '' }}>

                                        {{ ucwords($status) }}

                                    </option>
                                @endforeach

                            </select>

                        </div>



                        {{-- DELIVERY STATUS --}}

                        <div class="col-lg-3 col-md-4">

                            <label class="fw-bold">
                                Delivery Status
                            </label>

                            <select name="delivery_status" class="form-select">

                                <option value="">
                                    All Delivery Status
                                </option>

                                @foreach ($deliveryStatuses as $status)
                                    <option value="{{ $status }}"
                                        {{ request('delivery_status') == $status ? 'selected' : '' }}>

                                        {{ $status }}

                                    </option>
                                @endforeach

                            </select>

                        </div>



                        {{-- BUTTONS --}}

                        <div class="col-lg-5 col-md-8 d-flex align-items-end gap-2">

                            <button type="submit" class="btn btn-primary">

                                Filter

                            </button>


                            <a href="{{ route('staff.performance.report') }}" class="btn btn-secondary">

                                Reset

                            </a>


                            <a href="{{ route('staff.performance.export', request()->query()) }}" class="btn btn-success">

                                Excel Export

                            </a>

                        </div>


                    </div>

                </form>

            </div>

        </div>



        {{-- ============================================================ --}}
        {{-- CALLING PERFORMANCE --}}
        {{-- ============================================================ --}}

        <div class="section-title">
            Calling Performance
        </div>


        <div class="row g-2 mb-3">


            {{-- TOTAL --}}

            <div class="col-lg-2 col-md-4">

                <div class="card stat-card bg-blue">

                    <div class="card-body text-center">

                        <div class="title">
                            Total Leads
                        </div>

                        <div class="count">
                            {{ $overall['total'] }}
                        </div>

                    </div>

                </div>

            </div>



            {{-- PENDING --}}

            <div class="col-lg-2 col-md-4">

                <div class="card stat-card bg-yellow">

                    <div class="card-body text-center">

                        <div class="title">
                            Pending
                        </div>

                        <div class="count">
                            {{ $overall['pending'] }}
                        </div>

                    </div>

                </div>

            </div>



            {{-- VERIFIED --}}

            <div class="col-lg-2 col-md-4">

                <div class="card stat-card bg-green">

                    <div class="card-body text-center">

                        <div class="title">
                            Verified
                        </div>

                        <div class="count">
                            {{ $overall['verified'] }}
                        </div>

                        <div class="sub">
                            {{ $confirmationRate }}% Confirmation
                        </div>

                    </div>

                </div>

            </div>



            {{-- CANCEL --}}

            <div class="col-lg-2 col-md-4">

                <div class="card stat-card bg-red">

                    <div class="card-body text-center">

                        <div class="title">
                            Cancel
                        </div>

                        <div class="count">
                            {{ $overall['cancel'] }}
                        </div>

                    </div>

                </div>

            </div>



            {{-- NOT REACHABLE --}}

            <div class="col-lg-2 col-md-4">

                <div class="card stat-card bg-dark-custom">

                    <div class="card-body text-center">

                        <div class="title">
                            Not Reachable
                        </div>

                        <div class="count">
                            {{ $overall['not_reachable'] }}
                        </div>

                    </div>

                </div>

            </div>



            {{-- SAME ORDER --}}

            <div class="col-lg-2 col-md-4">

                <div class="card stat-card bg-gray">

                    <div class="card-body text-center">

                        <div class="title">
                            Same Order
                        </div>

                        <div class="count">
                            {{ $overall['same_order'] }}
                        </div>

                    </div>

                </div>

            </div>


        </div>



        {{-- ============================================================ --}}
        {{-- SOURCE PERFORMANCE --}}
        {{-- ============================================================ --}}

        <div class="section-title">
            Order Source Performance
        </div>


        <div class="row g-2 mb-3">


            <div class="col">

                <div class="card stat-card bg-green">

                    <div class="card-body text-center">

                        <div class="title">
                            WhatsApp
                        </div>

                        <div class="count">
                            {{ $sourceStats['whatsapp'] }}
                        </div>

                    </div>

                </div>

            </div>


            <div class="col">

                <div class="card stat-card bg-blue">

                    <div class="card-body text-center">

                        <div class="title">
                            Web
                        </div>

                        <div class="count">
                            {{ $sourceStats['web'] }}
                        </div>

                    </div>

                </div>

            </div>


            <div class="col">

                <div class="card stat-card bg-red">

                    <div class="card-body text-center">

                        <div class="title">
                            RTO
                        </div>

                        <div class="count">
                            {{ $sourceStats['rto'] }}
                        </div>

                    </div>

                </div>

            </div>


            <div class="col">

                <div class="card stat-card bg-cyan">

                    <div class="card-body text-center">

                        <div class="title">
                            Re-delivered
                        </div>

                        <div class="count">
                            {{ $sourceStats['deliveredreorder'] }}
                        </div>

                    </div>

                </div>

            </div>


            <div class="col">

                <div class="card stat-card bg-gray">

                    <div class="card-body text-center">

                        <div class="title">
                            Abandoned
                        </div>

                        <div class="count">
                            {{ $sourceStats['shopify_abandoned_checkout'] }}
                        </div>

                    </div>

                </div>

            </div>


        </div>



        {{-- ============================================================ --}}
        {{-- LEAD CONFIRMATION --}}
        {{-- ============================================================ --}}

        <div class="card mb-3">

            <div class="card-body">

                <div class="d-flex justify-content-between">

                    <strong>
                        Lead Confirmation
                    </strong>

                    <strong>
                        {{ $confirmationRate }}%
                    </strong>

                </div>


                <div class="progress mt-2">

                    <div class="progress-bar bg-success" style="width:{{ min($confirmationRate, 100) }}%">

                        {{ $overall['verified'] }}
                        /
                        {{ $overall['total'] }}

                    </div>

                </div>


                <div class="row g-2 mt-2">


                    <div class="col-md-4">

                        <div class="metric-box">

                            <div class="value">
                                {{ $reachabilityRate }}%
                            </div>

                            <div class="label">
                                Reachability Rate
                            </div>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="metric-box">

                            <div class="value text-danger">
                                {{ $overall['cancel'] }}
                            </div>

                            <div class="label">
                                Cancelled Leads
                            </div>

                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="metric-box">

                            <div class="value">
                                {{ $overall['not_reachable'] }}
                            </div>

                            <div class="label">
                                Not Reachable
                            </div>

                        </div>

                    </div>


                </div>

            </div>

        </div>



        {{-- ============================================================ --}}
        {{-- DELIVERY PERFORMANCE --}}
        {{-- ============================================================ --}}

        <div class="section-title">
            Delivery Performance
        </div>

        <div class="row g-2 mb-3">

            {{-- Delivered --}}
            <div class="col-lg-2 col-md-4">
                <div class="card stat-card bg-green">
                    <div class="card-body text-center">
                        <div class="title">Delivered</div>
                        <div class="count">
                            {{ $delivery['delivered'] ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>


            {{-- RTO Intransit --}}
            <div class="col-lg-2 col-md-4">
                <div class="card stat-card bg-red">
                    <div class="card-body text-center">
                        <div class="title">RTO-intrasit</div>
                        <div class="count">
                            {{ $delivery['rto_intransit'] ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>


            {{-- RTO Received --}}
            <div class="col-lg-2 col-md-4">
                <div class="card stat-card bg-red">
                    <div class="card-body text-center">
                        <div class="title">RTO Received</div>
                        <div class="count">
                            {{ $delivery['rto_received'] ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>


            {{-- Customer Intransit --}}
            <div class="col-lg-2 col-md-4">
                <div class="card stat-card bg-blue">
                    <div class="card-body text-center">
                        <div class="title">Customer - Intrasit</div>
                        <div class="count">
                            {{ $delivery['customer_intransit'] ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>


            {{-- Out For Delivery --}}
            <div class="col-lg-2 col-md-4">
                <div class="card stat-card bg-yellow">
                    <div class="card-body text-center">
                        <div class="title">Out for Delivery</div>
                        <div class="count">
                            {{ $delivery['ofd'] ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>


            {{-- On Hold --}}
            <div class="col-lg-2 col-md-4">
                <div class="card stat-card bg-dark-custom">
                    <div class="card-body text-center">
                        <div class="title">On Hold</div>
                        <div class="count">
                            {{ $delivery['on_hold'] ?? 0 }}
                        </div>
                    </div>
                </div>
            </div>

        </div>



        {{-- ============================================================ --}}
        {{-- DELIVERY RATIO --}}
        {{-- ============================================================ --}}

        <div class="card mb-3">
            <div class="card-body">

                <div class="d-flex justify-content-between">
                    <strong>Verified → Delivered</strong>

                    <strong>
                        {{ $deliveryRate ?? 0 }}%
                    </strong>
                </div>

                <div class="progress mt-2">

                    <div class="progress-bar bg-success" style="width:{{ min($deliveryRate ?? 0, 100) }}%">
                        {{ $delivery['delivered'] ?? 0 }}
                        /
                        {{ $overall['verified'] ?? 0 }}
                    </div>

                </div>


                <div class="row g-2 mt-2">

                    <div class="col-md-4">
                        <div class="metric-box">

                            <div class="value text-danger">
                                {{ $rtoRate ?? 0 }}%
                            </div>

                            <div class="label">
                                RTO Rate
                            </div>

                        </div>
                    </div>


                    <div class="col-md-4">
                        <div class="metric-box">

                            <div class="value">
                                {{ $delivery['customer_intransit'] ?? 0 }}
                            </div>

                            <div class="label">
                                Customer - Intrasit
                            </div>

                        </div>
                    </div>


                    <div class="col-md-4">
                        <div class="metric-box">

                            <div class="value">
                                {{ $delivery['no_status'] ?? 0 }}
                            </div>

                            <div class="label">
                                No Status
                            </div>

                        </div>
                    </div>

                </div>

            </div>
        </div>



        {{-- ============================================================ --}}
        {{-- BEST PERFORMER --}}
        {{-- ============================================================ --}}

        @if ($bestStaff)
            <div class="card best-card mb-3">

                <div class="card-body">

                    <div class="row align-items-center">


                        <div class="col-lg-3 text-center">

                            <div class="text-muted">
                                🏆 BEST PERFORMER
                            </div>

                            <div class="best-name">
                                {{ $bestStaff['staff_name'] }}
                            </div>

                            <div>
                                {{ $bestStaff['client_name'] }}
                            </div>

                            <span class="badge bg-success">
                                {{ $bestStaff['rating'] }}
                            </span>

                        </div>


                        <div class="col-lg-2 text-center">

                            <div class="text-muted">
                                Overall Score
                            </div>

                            <div class="best-score text-success">
                                {{ $bestStaff['score'] }}%
                            </div>

                        </div>


                        <div class="col-lg-2">

                            <div class="metric-box">

                                <div class="value">
                                    {{ $bestStaff['confirmation_rate'] }}%
                                </div>

                                <div class="label">
                                    Confirmation
                                </div>

                            </div>

                        </div>


                        <div class="col-lg-2">

                            <div class="metric-box">

                                <div class="value">
                                    {{ $bestStaff['delivery_rate'] }}%
                                </div>

                                <div class="label">
                                    Delivery
                                </div>

                            </div>

                        </div>


                        <div class="col-lg-2">

                            <div class="metric-box">

                                <div class="value text-danger">
                                    {{ $bestStaff['rto_rate'] }}%
                                </div>

                                <div class="label">
                                    RTO
                                </div>

                            </div>

                        </div>


                    </div>

                </div>

            </div>
        @endif



        {{-- ============================================================ --}}
        {{-- STAFF RANKING --}}
        {{-- ============================================================ --}}

        <div class="card mb-3">

            <div class="card-header">

                <strong>
                    Client-wise Staff Performance Ranking
                </strong>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover mb-0">

                        <thead class="table-dark">

                            <tr>

                                <th>Rank</th>

                                <th>Client</th>

                                <th>Staff</th>

                                <th>Leads</th>

                                <th>WhatsApp</th>

                                <th>Web</th>

                                <th>RTO Source</th>

                                <th>Re-delivered</th>

                                <th>Abandoned</th>

                                <th>Pending</th>

                                <th>Verified</th>

                                <th>Confirm %</th>

                                <th>Cancel</th>

                                <th>Cancel %</th>

                                <th>Not Reachable</th>

                                <th>Reach %</th>

                                <th>Delivered</th>

                                <th>Delivery %</th>

                                <th>RTO In</th>

                                <th>RTO Received</th>

                                <th>RTO %</th>

                                <th>Customer In</th>

                                <th>OFD</th>

                                <th>Hold</th>

                                <th>No Status</th>

                                <th>Score</th>

                                <th>Rating</th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($staffReport as $index => $staff)
                                <tr>


                                    <td>

                                        @if ($index === 0)
                                            🏆
                                        @endif

                                        <strong>
                                            #{{ $index + 1 }}
                                        </strong>

                                    </td>


                                    <td>

                                        <strong>
                                            {{ $staff['client_name'] }}
                                        </strong>

                                    </td>


                                    <td>

                                        <strong>
                                            {{ $staff['staff_name'] }}
                                        </strong>

                                    </td>


                                    <td>
                                        {{ $staff['total'] }}
                                    </td>


                                    <td>
                                        {{ $staff['whatsapp'] }}
                                    </td>


                                    <td>
                                        {{ $staff['web'] }}
                                    </td>


                                    <td>
                                        {{ $staff['rto_source'] }}
                                    </td>


                                    <td>
                                        {{ $staff['deliveredreorder'] }}
                                    </td>


                                    <td>
                                        {{ $staff['shopify_abandoned_checkout'] }}
                                    </td>


                                    <td>
                                        {{ $staff['pending'] }}
                                    </td>


                                    <td class="text-success fw-bold">
                                        {{ $staff['verified'] }}
                                    </td>


                                    <td>

                                        {{ $staff['confirmation_rate'] }}%

                                        <div class="progress" style="height:5px">

                                            <div class="progress-bar bg-success"
                                                style="width:{{ min($staff['confirmation_rate'], 100) }}%"></div>

                                        </div>

                                    </td>


                                    <td class="text-danger">
                                        {{ $staff['cancel'] }}
                                    </td>


                                    <td>
                                        {{ $staff['cancel_rate'] }}%
                                    </td>


                                    <td>
                                        {{ $staff['not_reachable'] }}
                                    </td>


                                    <td>
                                        {{ $staff['reachability_rate'] }}%
                                    </td>


                                    <td class="text-success fw-bold">
                                        {{ $staff['delivered'] }}
                                    </td>


                                    <td>

                                        {{ $staff['delivery_rate'] }}%

                                        <div class="progress" style="height:5px">

                                            <div class="progress-bar bg-primary"
                                                style="width:{{ min($staff['delivery_rate'], 100) }}%"></div>

                                        </div>

                                    </td>


                                    <td>
                                        {{ $staff['rto_intransit'] ?? 0 }}
                                    </td>


                                    <td>
                                        {{ $staff['rto_received'] ?? 0 }}
                                    </td>


                                    <td class="text-danger">
                                        {{ $staff['rto_rate'] ?? 0 }}%
                                    </td>


                                    <td>
                                        {{ $staff['customer_intransit'] ?? 0 }}
                                    </td>


                                    <td>
                                        {{ $staff['ofd'] }}
                                    </td>


                                    <td>
                                        {{ $staff['on_hold'] }}
                                    </td>


                                    <td>
                                        {{ $staff['no_status'] }}
                                    </td>


                                    <td>

                                        <span
                                            class="badge

@if ($staff['score'] >= 80) bg-success

@elseif($staff['score'] >= 70)
bg-primary

@elseif($staff['score'] >= 60)
bg-warning text-dark

@else
bg-danger @endif

">

                                            {{ $staff['score'] }}%

                                        </span>

                                    </td>


                                    <td>

                                        @if ($staff['rating'] === 'Excellent')
                                            <span class="badge bg-success">
                                                Excellent
                                            </span>
                                        @elseif($staff['rating'] === 'Very Good')
                                            <span class="badge bg-primary">
                                                Very Good
                                            </span>
                                        @elseif($staff['rating'] === 'Good')
                                            <span class="badge bg-info text-dark">
                                                Good
                                            </span>
                                        @elseif($staff['rating'] === 'Average')
                                            <span class="badge bg-warning text-dark">
                                                Average
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                Needs Improvement
                                            </span>
                                        @endif

                                    </td>


                                </tr>

                            @empty

                                <tr>

                                    <td colspan="27" class="text-center py-4">

                                        No performance data found.

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>





    </div>
@endsection
