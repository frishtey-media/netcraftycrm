@extends('layouts.admin')

@section('content')

    <style>
        #barcodeTable {
            padding-top: 15px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, .08);
        }

        .form-label {
            font-size: 13px;
            margin-bottom: 5px;
        }

        .form-control,
        .form-select {
            height: 42px;
            border-radius: 8px;
            font-size: 14px;
        }

        textarea.form-control {
            height: auto;
        }

        .btn {
            min-height: 42px;
            border-radius: 8px;
            font-weight: 500;
        }

        .filter-badge {
            display: inline-flex;
            align-items: center;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
        }

        .bg-client {
            background: #dff6ef;
            color: #168a65;
        }

        .bg-staff {
            background: #e8efff;
            color: #3366cc;
        }

        .bg-date {
            background: #f0e8ff;
            color: #7b3fe4;
        }

        .bg-status {
            background: #fff2df;
            color: #d97706;
        }

        .bg-search {
            background: #ececec;
            color: #555;
        }

        .stat-card {
            min-height: 90px;
            border-radius: 12px;
            transition: .2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-card .card-body {
            padding: 14px;
        }

        .icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .bg-purple {
            background: #f2e8ff;
            color: #8e44ad;
        }

        .summary-head th {
            background: #082b68 !important;
            color: #fff !important;
            border-color: #082b68 !important;
            font-size: 13px;
            font-weight: 600;
        }

        .table td,
        .table th {
            padding: .55rem;
            font-size: 13px;
            vertical-align: middle;
        }

        .badge {
            padding: 7px 10px;
            font-size: 12px;
            border-radius: 20px;
        }

        .chart-card {
            height: 320px;
        }

        .chart-card canvas {
            width: 100% !important;
            height: 250px !important;
        }

        .staff-card,
        .client-card {
            height: 320px;
            overflow: auto;
        }

        .insight-card {
            height: 320px;
        }

        .status-delivered {
            background: #198754 !important;
            color: #fff !important;
        }

        .status-rto {
            background: #dc3545 !important;
            color: #fff !important;
        }

        .status-transit {
            background: #0d6efd !important;
            color: #fff !important;
        }

        .status-hold {
            background: #ffc107 !important;
            color: #212529 !important;
        }

        .status-other {
            background: #6c757d !important;
            color: #fff !important;
        }

        .status-empty {
            background: #f8f9fa !important;
            color: #212529 !important;
            border: 1px solid #dee2e6;
        }
    </style>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    <div class="container-fluid">

        {{-- =========================================================
         FILTERS
    ========================================================== --}}

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-body">

                <form method="GET" action="{{ route('orders.list') }}">

                    <div class="row g-3 align-items-end">

                        {{-- CLIENT --}}
                        <div class="col-lg-2 col-md-6">

                            <label class="form-label fw-semibold">
                                Client
                            </label>

                            @if (auth()->user()->role == 'client')
                                <input type="hidden" name="client_id" value="{{ optional($clients->first())->id }}">

                                <input type="text" class="form-control"
                                    value="{{ optional($clients->first())->client_name }}" readonly>
                            @else
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
                            @endif

                        </div>


                        {{-- PRODUCT --}}
                        <div class="col-lg-2 col-md-6">

                            <label class="form-label fw-semibold">
                                Product
                            </label>

                            <select name="product" id="product" class="form-select">

                                <option value="">
                                    All Products
                                </option>

                                @if (request('product'))
                                    <option value="{{ request('product') }}" selected>

                                        {{ request('product') }}

                                    </option>
                                @endif

                            </select>

                        </div>


                        {{-- STAFF --}}
                        <div class="col-lg-2 col-md-6">

                            <label class="form-label fw-semibold">
                                Staff
                            </label>

                            <select name="staff_id" class="form-select">

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


                        {{-- PAYMENT --}}
                        <div class="col-lg-2 col-md-6">

                            <label class="form-label fw-semibold">
                                Payment Type
                            </label>

                            <select name="payment_mode" class="form-select">

                                <option value="">
                                    All Payment
                                </option>

                                <option value="VPP" {{ request('payment_mode') == 'VPP' ? 'selected' : '' }}>
                                    VPP
                                </option>

                                <option value="COD" {{ request('payment_mode') == 'COD' ? 'selected' : '' }}>
                                    COD
                                </option>

                                <option value="prepaid"
                                    {{ strtolower(request('payment_mode', '')) == 'prepaid' ? 'selected' : '' }}>
                                    Prepaid
                                </option>

                            </select>

                        </div>


                        {{-- DELIVERY STATUS --}}
                        <div class="col-lg-3 col-md-6">

                            <label class="form-label fw-semibold">
                                Delivery Status
                            </label>

                            <select name="delivery_status" class="form-select">

                                <option value="">
                                    All Status
                                </option>

                                <option value="Delivered"
                                    {{ request('delivery_status') === 'Delivered' ? 'selected' : '' }}>
                                    Delivered
                                </option>

                                <option value="RTO-intrasit"
                                    {{ request('delivery_status') === 'RTO-intrasit' ? 'selected' : '' }}>
                                    RTO Intrasit
                                </option>

                                <option value="RTO Received"
                                    {{ request('delivery_status') === 'RTO Received' ? 'selected' : '' }}>
                                    RTO Received
                                </option>

                                <option value="Customer - Intrasit"
                                    {{ request('delivery_status') === 'Customer - Intrasit' ? 'selected' : '' }}>
                                    Customer Intrasit
                                </option>

                                <option value="Out for Delivery"
                                    {{ request('delivery_status') === 'Out for Delivery' ? 'selected' : '' }}>
                                    Out for Delivery
                                </option>

                                <option value="On Hold" {{ request('delivery_status') === 'On Hold' ? 'selected' : '' }}>
                                    On Hold
                                </option>

                                <option value="null" {{ request('delivery_status') === 'null' ? 'selected' : '' }}>
                                    No Status
                                </option>

                            </select>

                        </div>


                        {{-- DATE FROM --}}
                        <div class="col-lg-2 col-md-6">

                            <label class="form-label fw-semibold">
                                Date From
                            </label>

                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">

                        </div>


                        {{-- DATE TO --}}
                        <div class="col-lg-2 col-md-6">

                            <label class="form-label fw-semibold">
                                Date To
                            </label>

                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">

                        </div>


                        {{-- SEARCH --}}
                        <div class="col-lg-3 col-md-8">

                            <label class="form-label fw-semibold">
                                Bulk Search
                            </label>

                            <textarea name="search" class="form-control" rows="3" placeholder="Paste Order ID / Barcode / Phone">{{ request('search') }}</textarea>

                            <small class="text-muted">
                                One value per line or comma separated.
                            </small>

                        </div>


                        {{-- RECORDS --}}
                        <div class="col-lg-1 col-md-4">

                            <label class="form-label fw-semibold">
                                Records
                            </label>

                            <select name="per_page" class="form-select" onchange="this.form.submit()">

                                @foreach ([10, 25, 50, 100, 500, 1000, 5000, 10000, 15000] as $size)
                                    <option value="{{ $size }}"
                                        {{ request('per_page', 100) == $size ? 'selected' : '' }}>

                                        {{ $size }}

                                    </option>
                                @endforeach

                            </select>

                        </div>


                        {{-- BUTTONS --}}
                        <div class="col-lg-3 col-md-12 d-flex gap-2">

                            <button type="submit" class="btn btn-primary flex-fill">

                                <i class="fas fa-filter"></i>
                                Filter

                            </button>

                            <a href="{{ route('orders.list') }}" class="btn btn-outline-secondary flex-fill">

                                Reset

                            </a>

                            <button type="button" id="compareBtn" class="btn btn-warning">

                                Compare

                            </button>

                        </div>

                    </div>


                    {{-- COMPARE --}}
                    <div class="card border-warning shadow-sm mt-4" id="compareSection" style="display:none;">

                        <div class="card-header bg-warning">

                            <strong>
                                <i class="fas fa-chart-line"></i>
                                Compare Current Report
                            </strong>

                        </div>

                        <div class="card-body">

                            <div class="alert alert-info">

                                Current filters will remain same.
                                Only date range will change.

                            </div>

                            <div class="row">

                                <div class="col-md-3">

                                    <label>Compare From</label>

                                    <input type="date" name="compare_from" class="form-control">

                                </div>

                                <div class="col-md-3">

                                    <label>Compare To</label>

                                    <input type="date" name="compare_to" class="form-control">

                                </div>

                                <div class="col-md-2 d-grid">

                                    <label>&nbsp;</label>

                                    <button name="compare" value="1" class="btn btn-success">

                                        Compare

                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>

                </form>

            </div>

        </div>


        {{-- =========================================================
         SEARCH RESULT
    ========================================================== --}}

        @if (!empty($searchTerms))
            <div class="alert alert-info">

                <strong>Total Search Items:</strong>
                {{ count($searchTerms) }}

                &nbsp; | &nbsp;

                <strong>Matched:</strong>
                {{ count($searchTerms) - count($notFound) }}

                &nbsp; | &nbsp;

                <strong>Not Found:</strong>
                {{ count($notFound) }}

            </div>
        @endif


        @if (!empty($notFound))
            <div class="alert alert-danger">

                <strong>
                    Records Not Found ({{ count($notFound) }})
                </strong>

                <textarea class="form-control mt-2" rows="5" readonly>{{ implode("\n", $notFound) }}</textarea>

            </div>
        @endif


        {{-- =========================================================
         APPLIED FILTERS
    ========================================================== --}}

        @if (request()->anyFilled(['client_id', 'staff_id', 'delivery_status', 'date_from', 'date_to', 'search']))

            <div class="card shadow-sm border-0 mb-3">

                <div class="card-body py-3">

                    <h6 class="fw-bold mb-3">
                        Applied Filters
                    </h6>

                    <div class="d-flex flex-wrap gap-2">

                        @if (request('client_id'))
                            <span class="filter-badge bg-client">

                                <strong>Client:</strong>&nbsp;

                                {{ optional($clients->where('id', request('client_id'))->first())->client_name }}

                            </span>
                        @endif


                        @if (request('staff_id'))
                            <span class="filter-badge bg-staff">

                                <strong>Staff:</strong>&nbsp;

                                {{ optional($staffs->where('id', request('staff_id'))->first())->name }}

                            </span>
                        @endif


                        @if (request('date_from') || request('date_to'))
                            <span class="filter-badge bg-date">

                                <strong>Date:</strong>&nbsp;

                                {{ request('date_from') ? \Carbon\Carbon::parse(request('date_from'))->format('d-m-Y') : '' }}

                                @if (request('date_from') && request('date_to'))
                                    to
                                @endif

                                {{ request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('d-m-Y') : '' }}

                            </span>
                        @endif


                        @if (request('delivery_status'))
                            <span class="filter-badge bg-status">

                                <strong>Status:</strong>&nbsp;

                                {{ request('delivery_status') === 'null' ? 'No Status' : request('delivery_status') }}

                            </span>
                        @endif


                        @if (request('search'))
                            <span class="filter-badge bg-search">

                                <strong>Search:</strong>&nbsp;

                                {{ request('search') }}

                            </span>
                        @endif


                        <a href="{{ route('orders.list') }}" class="btn btn-light btn-sm">

                            <i class="fas fa-times"></i>
                            Clear All

                        </a>

                    </div>

                </div>

            </div>

        @endif


        {{-- =========================================================
         DASHBOARD
    ========================================================== --}}

        @if (auth()->user()->role == 'super_admin')
            <div class="row g-3 mb-4">


                {{-- TOTAL --}}
                <div class="col-lg col-md-4 col-sm-6">

                    <div class="card stat-card h-100">

                        <div class="card-body d-flex align-items-center">

                            <div class="icon bg-primary-subtle text-primary">

                                <i class="fas fa-shopping-bag"></i>

                            </div>

                            <div class="ms-3">

                                <small class="text-muted">
                                    Total Orders
                                </small>

                                <h3 class="mb-0">
                                    {{ number_format($totalOrders) }}
                                </h3>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- DELIVERED --}}
                <div class="col-lg col-md-4 col-sm-6">

                    <div class="card stat-card h-100">

                        <div class="card-body d-flex align-items-center">

                            <div class="icon bg-success-subtle text-success">

                                <i class="fas fa-check-circle"></i>

                            </div>

                            <div class="ms-3">

                                <small class="text-muted">
                                    Delivered
                                </small>

                                <h3 class="mb-0 text-success">
                                    {{ number_format($totalDelivered) }}
                                </h3>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- RTO INTRANSIT --}}
                <div class="col-lg col-md-4 col-sm-6">

                    <div class="card stat-card h-100">

                        <div class="card-body d-flex align-items-center">

                            <div class="icon bg-danger-subtle text-danger">

                                <i class="fas fa-reply"></i>

                            </div>

                            <div class="ms-3">

                                <small class="text-muted">
                                    RTO Intrasit
                                </small>

                                <h3 class="mb-0 text-danger">
                                    {{ number_format($totalRto) }}
                                </h3>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- RTO RECEIVED --}}
                <div class="col-lg col-md-4 col-sm-6">

                    <div class="card stat-card h-100">

                        <div class="card-body d-flex align-items-center">

                            <div class="icon bg-danger-subtle text-danger">

                                <i class="fas fa-undo"></i>

                            </div>

                            <div class="ms-3">

                                <small class="text-muted">
                                    RTO Received
                                </small>

                                <h3 class="mb-0 text-danger">
                                    {{ number_format($totalRtoReceived) }}
                                </h3>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- CUSTOMER INTRANSIT --}}
                <div class="col-lg col-md-4 col-sm-6">

                    <div class="card stat-card h-100">

                        <div class="card-body d-flex align-items-center">

                            <div class="icon bg-primary-subtle text-primary">

                                <i class="fas fa-truck"></i>

                            </div>

                            <div class="ms-3">

                                <small class="text-muted">
                                    Customer Intrasit
                                </small>

                                <h3 class="mb-0 text-primary">
                                    {{ number_format($totalTransit) }}
                                </h3>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="col-lg col-md-4 col-sm-6">

                    <div class="card stat-card h-100">

                        <div class="card-body d-flex align-items-center">

                            <div class="icon bg-danger-subtle text-danger">

                                <i class="fas fa-truck"></i>

                            </div>

                            <div class="ms-3">

                                <small class="text-muted">
                                    Hold
                                </small>

                                <h3 class="mb-0 text-danger">
                                    {{ number_format($hold) }}
                                </h3>

                            </div>

                        </div>

                    </div>

                </div>
                <div class="col-lg col-md-4 col-sm-6">

                    <div class="card stat-card h-100">

                        <div class="card-body d-flex align-items-center">

                            <div class="icon bg-danger-subtle text-danger">

                                <i class="fas fa-truck"></i>

                            </div>

                            <div class="ms-3">

                                <small class="text-muted">
                                    OFD
                                </small>

                                <h3 class="mb-0 text-danger">
                                    {{ number_format($ofd) }}
                                </h3>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- NO STATUS --}}
                <div class="col-lg col-md-4 col-sm-6">

                    <div class="card stat-card h-100">

                        <div class="card-body d-flex align-items-center">

                            <div class="icon bg-purple">

                                <i class="fas fa-question-circle"></i>

                            </div>

                            <div class="ms-3">

                                <small class="text-muted">
                                    No Status
                                </small>

                                <h3 class="mb-0">
                                    {{ number_format($totalNoStatus) }}
                                </h3>

                                <small class="text-muted">
                                    Customer Transit &gt; 7 Days
                                </small>

                                <h6 class="text-warning mb-0">
                                    {{ number_format($transit7Days) }}
                                </h6>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- =====================================================
             WEB / WHATSAPP SUMMARY
        ====================================================== --}}

            <div class="card shadow-sm mb-4">

                <div class="card-header bg-white">

                    <strong>
                        Web vs WhatsApp Summary
                    </strong>

                </div>

                <div class="table-responsive">

                    <table class="table table-bordered table-hover text-center mb-0">

                        <thead class="summary-head">

                            <tr>

                                <th>Category</th>
                                <th>Orders</th>
                                <th>Delivered</th>
                                <th>RTO Intrasit</th>
                                <th>RTO Received</th>
                                <th>Customer Intrasit</th>
                                <th>No Status</th>

                            </tr>

                        </thead>

                        <tbody>

                            {{-- TOTAL --}}
                            <tr>

                                <th class="text-primary">
                                    Total
                                </th>

                                <td>
                                    <strong>
                                        {{ number_format($totalOrders) }}
                                    </strong>
                                </td>

                                <td>

                                    <strong class="text-success">
                                        {{ number_format($totalDelivered) }}
                                    </strong>

                                    <br>

                                    <small class="text-success">
                                        {{ $totalDeliveredPercent }}%
                                    </small>

                                </td>

                                <td>

                                    <strong class="text-danger">
                                        {{ number_format($totalRto) }}
                                    </strong>

                                    <br>

                                    <small class="text-danger">
                                        {{ $totalRtoPercent }}%
                                    </small>

                                </td>

                                <td>

                                    <strong class="text-danger">
                                        {{ number_format($totalRtoReceived) }}
                                    </strong>

                                    <br>

                                    <small class="text-danger">
                                        {{ $totalRtoReceivedPercent }}%
                                    </small>

                                </td>

                                <td>

                                    <strong class="text-primary">
                                        {{ number_format($totalTransit) }}
                                    </strong>

                                    <br>

                                    <small class="text-primary">
                                        {{ $totalTransitPercent }}%
                                    </small>

                                </td>

                                <td>

                                    <strong>
                                        {{ number_format($totalNoStatus) }}
                                    </strong>

                                    <br>

                                    <small>
                                        {{ $totalNoStatusPercent }}%
                                    </small>

                                </td>

                            </tr>


                            {{-- WEB --}}
                            <tr>

                                <th class="text-primary">
                                    🌐 Web
                                </th>

                                <td>
                                    {{ number_format($webOrders) }}
                                </td>

                                <td>

                                    {{ number_format($webDelivered) }}

                                    <br>

                                    <small class="text-success">
                                        {{ $webDeliveredPercent }}%
                                    </small>

                                </td>

                                <td>

                                    {{ number_format($webRto) }}

                                    <br>

                                    <small class="text-danger">
                                        {{ $webRtoPercent }}%
                                    </small>

                                </td>

                                <td>

                                    {{ number_format($webRtoReceived) }}

                                    <br>

                                    <small class="text-danger">
                                        {{ $webRtoReceivedPercent }}%
                                    </small>

                                </td>

                                <td>

                                    {{ number_format($webTransit) }}

                                    <br>

                                    <small class="text-primary">
                                        {{ $webTransitPercent }}%
                                    </small>

                                </td>

                                <td>

                                    {{ number_format($webNoStatus) }}

                                    <br>

                                    <small>
                                        {{ $webNoStatusPercent }}%
                                    </small>

                                </td>

                            </tr>


                            {{-- WHATSAPP --}}
                            <tr>

                                <th class="text-success">

                                    <i class="fab fa-whatsapp"></i>
                                    WhatsApp

                                </th>

                                <td>
                                    {{ number_format($whatsappOrders) }}
                                </td>

                                <td>

                                    {{ number_format($whatsappDelivered) }}

                                    <br>

                                    <small class="text-success">
                                        {{ $waDeliveredPercent }}%
                                    </small>

                                </td>

                                <td>

                                    {{ number_format($whatsappRto) }}

                                    <br>

                                    <small class="text-danger">
                                        {{ $waRtoPercent }}%
                                    </small>

                                </td>

                                <td>

                                    {{ number_format($whatsappRtoReceived) }}

                                    <br>

                                    <small class="text-danger">
                                        {{ $waRtoReceivedPercent }}%
                                    </small>

                                </td>

                                <td>

                                    {{ number_format($whatsappTransit) }}

                                    <br>

                                    <small class="text-primary">
                                        {{ $waTransitPercent }}%
                                    </small>

                                </td>

                                <td>

                                    {{ number_format($whatsappNoStatus) }}

                                    <br>

                                    <small>
                                        {{ $waNoStatusPercent }}%
                                    </small>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- =====================================================
             CHARTS
        ====================================================== --}}

            <div class="row g-3 mb-4">

                <div class="col-xl-4 col-lg-6">

                    <div class="card chart-card">

                        <div class="card-body">

                            <h6 class="fw-bold">
                                Orders Trend
                            </h6>

                            <canvas id="ordersChart"></canvas>

                        </div>

                    </div>

                </div>


                <div class="col-xl-4 col-lg-6">

                    <div class="card chart-card">

                        <div class="card-body">

                            <h6 class="fw-bold">
                                Delivery / RTO Trend
                            </h6>

                            <canvas id="deliveryChart"></canvas>

                        </div>

                    </div>

                </div>


                <div class="col-xl-4 col-lg-6">

                    <div class="card chart-card">

                        <div class="card-body">

                            <h6 class="fw-bold">
                                Order Status
                            </h6>

                            <canvas id="statusChart"></canvas>

                        </div>

                    </div>

                </div>

            </div>
        @endif


        {{-- =========================================================
         BULK ACTIONS
    ========================================================== --}}

        <div id="bulkActions" class="mb-3 d-none">

            <button class="btn btn-danger" id="downloadInvoice">

                Download Invoice PDF

            </button>

            <button class="btn btn-success" id="downloadLabel">

                Re-Download Label PDF

            </button>

            <button class="btn btn-primary" id="downloadExcel">

                Download Post Office Excel

            </button>

            <button class="btn btn-danger" id="downloadmoney">

                Download Money Order PDF

            </button>

            <button class="btn btn-success" id="downloadexcelsss">

                Download Excel

            </button>

            <button class="btn btn-primary d-none" id="updateStatusBtn">

                <i class="fa fa-edit"></i>
                Update Status

            </button>

        </div>


        {{-- =========================================================
         ORDERS TABLE
    ========================================================== --}}

        <div class="card">

            <div class="card-body">

                <div class="table-responsive">

                    <table id="barcodeTable" class="table table-bordered table-striped">

                        <thead class="table-dark">

                            <tr>

                                <th>
                                    <input type="checkbox" id="selectAll">
                                </th>

                                <th>#</th>

                                <th>Order ID</th>

                                <th>Delivery Status</th>

                                <th>Payment Status</th>

                                <th>Barcode / AWB</th>

                                <th>Customer Name</th>

                                <th>Customer Phone</th>

                                <th>Shipping Address</th>

                                <th>Payment Mode</th>

                                <th>Amount</th>

                                <th>Product</th>

                                <th>Quantity</th>

                                <th>Weight</th>

                                <th>Date</th>

                                <th>Delivery Remarks</th>

                                <th>Label</th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($orders as $order)
                                @php

                                    $status = trim($order->delivery_status ?? '');

                                    $statusLower = strtolower($status);

                                @endphp

                                <tr>

                                    {{-- CHECKBOX --}}
                                    <td>

                                        <input type="checkbox" class="order-checkbox" value="{{ $order->id }}">

                                    </td>


                                    {{-- NUMBER --}}
                                    <td>
                                        {{ $orders->firstItem() + $loop->index }}
                                    </td>


                                    {{-- ORDER --}}
                                    <td>

                                        <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="Assigned To: {{ $order->callingOrder->staff->name ?? 'Not Assigned' }}">

                                            {{ $order->order_id }}

                                        </a>

                                    </td>


                                    {{-- DELIVERY STATUS --}}
                                    <td>

                                        @if ($statusLower === 'delivered')
                                            <span class="badge status-delivered">
                                                Delivered
                                            </span>
                                        @elseif($statusLower === 'rto-intrasit')
                                            <span class="badge status-rto">
                                                RTO Intrasit
                                            </span>
                                        @elseif($statusLower === 'rto received')
                                            <span class="badge status-rto">
                                                RTO Received
                                            </span>
                                        @elseif($statusLower === 'customer - intrasit')
                                            <span class="badge status-transit">
                                                Customer Intrasit
                                            </span>
                                        @elseif($statusLower === 'out for delivery')
                                            <span class="badge status-transit">
                                                Out for Delivery
                                            </span>
                                        @elseif($statusLower === 'on hold')
                                            <span class="badge status-hold">
                                                On Hold
                                            </span>
                                        @elseif($status !== '')
                                            <span class="badge status-other">
                                                {{ $status }}
                                            </span>
                                        @else
                                            <span class="badge status-empty">
                                                No Status
                                            </span>
                                        @endif

                                    </td>


                                    {{-- PAYMENT STATUS --}}
                                    <td>

                                        @if ($order->recivedpaysts == 1)
                                            <span class="badge bg-success">
                                                Payment Received
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                Pending
                                            </span>
                                        @endif

                                    </td>


                                    {{-- BARCODE / AWB --}}
                                    <td>

                                        @if ($order->delhiveryShipment?->awb)
                                            <div>
                                                <strong>AWB:</strong>
                                                {{ $order->delhiveryShipment->awb }}
                                            </div>
                                        @endif


                                        @if ($order->barcode)
                                            <div>
                                                <strong>Barcode:</strong>
                                                {{ $order->barcode }}
                                            </div>
                                        @endif

                                    </td>


                                    {{-- CUSTOMER --}}
                                    <td>
                                        {{ $order->customer_name }}
                                    </td>


                                    {{-- PHONE --}}
                                    <td>
                                        {{ $order->customer_phone }}
                                    </td>


                                    {{-- ADDRESS --}}
                                    <td>
                                        {{ $order->shipping_address }}
                                    </td>


                                    {{-- PAYMENT MODE --}}
                                    <td>
                                        {{ $order->payment_mode }}
                                    </td>


                                    {{-- AMOUNT --}}
                                    <td>
                                        {{ $order->amount }}
                                    </td>


                                    {{-- PRODUCT --}}
                                    <td>
                                        {{ $order->product }}
                                    </td>


                                    {{-- QUANTITY --}}
                                    <td>
                                        {{ $order->quantity }}
                                    </td>


                                    {{-- WEIGHT --}}
                                    <td>
                                        {{ $order->weight }}
                                    </td>


                                    {{-- DATE --}}
                                    <td>
                                        {{ $order->date }}
                                    </td>


                                    {{-- REMARK --}}
                                    <td>
                                        {{ $order->delivery_remark }}
                                    </td>


                                    {{-- LABEL --}}
                                    <td>

                                        @if ($order->delhiveryShipment)
                                            @if ($order->delhiveryShipment->label_path && $order->delhiveryShipment->label_url)
                                                <a href="{{ route('delhivery.label.download', $order->delhiveryShipment->id) }}"
                                                    class="btn btn-sm btn-primary" target="_blank">

                                                    <i class="fas fa-print"></i>
                                                    Print Label

                                                </a>
                                            @elseif($order->delhiveryShipment->status === 'label_generated')
                                                <span class="badge bg-warning text-dark">
                                                    Label Ready
                                                </span>
                                            @elseif($order->delhiveryShipment->status === 'label_failed')
                                                <span class="badge bg-danger"
                                                    title="{{ $order->delhiveryShipment->error_message }}">

                                                    Label Failed

                                                </span>
                                            @else
                                                <span class="badge bg-secondary">
                                                    Label Pending
                                                </span>
                                            @endif
                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="17" class="text-center py-4">

                                        <strong>
                                            No Orders Found
                                        </strong>

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>


                {{-- PAGINATION --}}
                @if ($orders->total() > 0)
                    <div class="d-flex justify-content-between align-items-center mt-3">

                        <div>

                            Showing
                            <strong>{{ $orders->firstItem() }}</strong>
                            to
                            <strong>{{ $orders->lastItem() }}</strong>
                            of
                            <strong>{{ $orders->total() }}</strong>
                            records

                        </div>

                        <div>

                            {{ $orders->links() }}

                        </div>

                    </div>
                @endif

            </div>

        </div>

    </div>


    {{-- =============================================================
     SENDER MODAL
============================================================= --}}

    <div class="modal fade" id="senderModal" tabindex="-1">

        <div class="modal-dialog">

            <form id="labelDownloadForm" method="POST" action="{{ route('labels.selected.pdf') }}">

                @csrf

                <input type="hidden" name="ids" id="selected_ids">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5>
                            Select Sender
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal">
                        </button>

                    </div>

                    <div class="modal-body">

                        <select name="sender_id" id="modal_sender_id" class="form-control" required>

                            <option value="">
                                -- Select Sender --
                            </option>

                            @foreach ($senders as $sender)
                                <option value="{{ $sender->id }}">
                                    {{ $sender->customer_name }}
                                </option>
                            @endforeach

                        </select>


                        <div class="form-check mt-3">

                            <input class="form-check-input" type="checkbox" name="use_old_barcode" id="use_old_barcode"
                                value="1">

                            <label class="form-check-label" for="use_old_barcode">

                                Use Old Barcode
                                (Assign existing barcode)

                            </label>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="button" id="confirmDownloadLabel" class="btn btn-success">

                            Download Label PDF

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    {{-- =============================================================
     MANUAL DELIVERY MODAL
============================================================= --}}

    <div class="modal fade" id="statusModal" tabindex="-1">

        <div class="modal-dialog">

            <form method="POST" action="{{ route('orders.manual.delivery') }}">

                @csrf

                <input type="hidden" name="order_id" id="status_order_id">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5>
                            Manual Delivery
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal">
                        </button>

                    </div>

                    <div class="modal-body">

                        <label>Status</label>

                        <select class="form-control" name="delivery_status">

                            <option value="Delivered">
                                Delivered By Hand
                            </option>

                        </select>

                        <br>

                        <label>
                            Delivery Date
                        </label>

                        <input type="date" class="form-control" name="delivery_date" value="{{ date('Y-m-d') }}">

                        <br>

                        <label>
                            Remark
                        </label>

                        <textarea class="form-control" name="remark">Delivered By Hand</textarea>

                    </div>

                    <div class="modal-footer">

                        <button type="submit" class="btn btn-success">

                            Save

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>


    {{-- =============================================================
     JAVASCRIPT
============================================================= --}}

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        $(document).ready(function() {


            /* =========================================================
               PRODUCT LOAD
            ========================================================== */

            $('#client_id').on('change', function() {

                let clientId = $(this).val();

                $('#product').html(
                    '<option value="">Loading...</option>'
                );

                if (!clientId) {

                    $('#product').html(
                        '<option value="">All Products</option>'
                    );

                    return;
                }

                $.ajax({

                    url: '/get-products/' + clientId,

                    type: 'GET',

                    success: function(products) {

                        let options =
                            '<option value="">All Products</option>';

                        $.each(products, function(index, product) {

                            options +=
                                '<option value="' +
                                product +
                                '">' +
                                product +
                                '</option>';

                        });

                        $('#product').html(options);

                    },

                    error: function() {

                        $('#product').html(
                            '<option value="">All Products</option>'
                        );

                    }

                });

            });


            /* =========================================================
               TOOLTIP
            ========================================================== */

            document
                .querySelectorAll('[data-bs-toggle="tooltip"]')
                .forEach(function(el) {

                    new bootstrap.Tooltip(el);

                });


            /* =========================================================
               COMPARE
            ========================================================== */

            $('#compareBtn').on('click', function() {

                $('#compareSection').slideToggle();

            });


            /* =========================================================
               SELECT ALL
            ========================================================== */

            $('#selectAll').on('change', function() {

                $('.order-checkbox').prop(
                    'checked',
                    this.checked
                );

                toggleButtons();

            });


            /* =========================================================
               INDIVIDUAL CHECKBOX
            ========================================================== */

            $(document).on(
                'change',
                '.order-checkbox',
                function() {

                    toggleButtons();

                }
            );


            /* =========================================================
               BUTTON VISIBILITY
            ========================================================== */

            function toggleButtons() {

                let count =
                    $('.order-checkbox:checked').length;

                if (count > 0) {

                    $('#bulkActions')
                        .removeClass('d-none');

                } else {

                    $('#bulkActions')
                        .addClass('d-none');

                }


                if (count === 1) {

                    $('#updateStatusBtn')
                        .removeClass('d-none');

                } else {

                    $('#updateStatusBtn')
                        .addClass('d-none');

                }

            }


            /* =========================================================
               GET SELECTED ORDERS
            ========================================================== */

            function getSelectedOrders() {

                let ids = [];

                $('.order-checkbox:checked')
                    .each(function() {

                        ids.push($(this).val());

                    });

                return ids;

            }


            /* =========================================================
               INVOICE
            ========================================================== */

            $('#downloadInvoice').on('click', function() {

                let ids = getSelectedOrders();

                if (!ids.length) {

                    alert('Please select orders');

                    return;

                }

                window.location.href =
                    "{{ route('orders.invoice.pdf') }}" +
                    "?ids=" +
                    ids.join(',');

            });


            /* =========================================================
               POST OFFICE EXCEL
            ========================================================== */

            $('#downloadExcel').on('click', function() {

                let ids = getSelectedOrders();

                if (!ids.length) {

                    alert('Please select orders');

                    return;

                }

                window.location.href =
                    "{{ route('orders.postoffice.excel') }}" +
                    "?ids=" +
                    ids.join(',');

            });


            /* =========================================================
               MONEY ORDER PDF
            ========================================================== */

            $('#downloadmoney').on('click', function() {

                let ids = getSelectedOrders();

                if (!ids.length) {

                    alert('Please select orders');

                    return;

                }

                let form = $('<form>', {

                    method: 'POST',

                    action: "{{ route('orders.Moneyorder.pdf') }}"

                });

                form.append('@csrf');

                form.append(
                    $('<input>', {

                        type: 'hidden',

                        name: 'ids',

                        value: ids.join(',')

                    })
                );

                $('body').append(form);

                form.submit();

            });


            /* =========================================================
               EXPORT EXCEL
            ========================================================== */

            $('#downloadexcelsss').on('click', function() {

                let ids = getSelectedOrders();

                if (!ids.length) {

                    alert('Please select orders');

                    return;

                }

                let form = $('<form>', {

                    method: 'POST',

                    action: "{{ route('orders.export.selected') }}"

                });

                form.append('@csrf');

                form.append(
                    $('<input>', {

                        type: 'hidden',

                        name: 'ids',

                        value: ids.join(',')

                    })
                );

                $('body').append(form);

                form.submit();

            });


            /* =========================================================
               MANUAL STATUS
            ========================================================== */

            $('#updateStatusBtn').on('click', function() {

                let id =
                    $('.order-checkbox:checked')
                    .first()
                    .val();

                if (!id) {

                    alert('Please select one order');

                    return;

                }

                $('#status_order_id').val(id);

                let modal =
                    new bootstrap.Modal(
                        document.getElementById('statusModal')
                    );

                modal.show();

            });


            /* =========================================================
               DOWNLOAD LABEL
            ========================================================== */

            $('#downloadLabel').on('click', function() {

                let ids = getSelectedOrders();

                if (!ids.length) {

                    alert('Please select at least one order');

                    return;

                }

                $('#selected_ids')
                    .val(ids.join(','));

                let modal =
                    new bootstrap.Modal(
                        document.getElementById('senderModal')
                    );

                modal.show();

            });


            /* =========================================================
               CONFIRM LABEL
            ========================================================== */

            $('#confirmDownloadLabel').on('click', function() {

                let senderId =
                    $('#modal_sender_id').val();

                if (!senderId) {

                    alert('Please select sender');

                    return;

                }

                $('#labelDownloadForm').submit();

            });


            /* =========================================================
               ORDERS CHART
            ========================================================== */

            @if (auth()->user()->role == 'super_admin')

                const ordersChartEl =
                    document.getElementById('ordersChart');

                if (ordersChartEl) {

                    new Chart(
                        ordersChartEl, {

                            type: 'line',

                            data: {

                                labels: @json($labels),

                                datasets: [

                                    {

                                        label: 'Total',

                                        data: @json($totalOrdersChart),

                                        borderColor: '#2563eb',

                                        backgroundColor: 'transparent',

                                        tension: .4

                                    },

                                    {

                                        label: 'Web',

                                        data: @json($webOrdersChart),

                                        borderColor: '#16a34a',

                                        backgroundColor: 'transparent',

                                        tension: .4

                                    },

                                    {

                                        label: 'WhatsApp',

                                        data: @json($waOrdersChart),

                                        borderColor: '#7c3aed',

                                        backgroundColor: 'transparent',

                                        tension: .4

                                    }

                                ]

                            },

                            options: {

                                responsive: true,

                                maintainAspectRatio: false

                            }

                        }
                    );

                }


                /* =========================================================
                   DELIVERY / RTO CHART
                ========================================================== */

                const deliveryChartEl =
                    document.getElementById('deliveryChart');

                if (deliveryChartEl) {

                    new Chart(
                        deliveryChartEl, {

                            type: 'line',

                            data: {

                                labels: @json($labels),

                                datasets: [

                                    {

                                        label: 'Delivered',

                                        data: @json($deliveryChart),

                                        borderColor: '#16a34a',

                                        backgroundColor: 'transparent',

                                        tension: .4

                                    },

                                    {

                                        label: 'RTO Intrasit',

                                        data: @json($rtoChart),

                                        borderColor: '#dc2626',

                                        backgroundColor: 'transparent',

                                        tension: .4

                                    }

                                ]

                            },

                            options: {

                                responsive: true,

                                maintainAspectRatio: false

                            }

                        }
                    );

                }


                /* =========================================================
                   STATUS CHART
                ========================================================== */

                const statusChartEl =
                    document.getElementById('statusChart');

                if (statusChartEl) {

                    new Chart(
                        statusChartEl, {

                            type: 'doughnut',

                            data: {

                                labels: [

                                    'Delivered',

                                    'RTO Intrasit',

                                    'RTO Received',

                                    'Customer Intrasit',

                                    'No Status'

                                ],

                                datasets: [

                                    {

                                        data: @json($statusChart),

                                        backgroundColor: [

                                            '#16a34a',

                                            '#dc2626',

                                            '#b91c1c',

                                            '#2563eb',

                                            '#7c3aed'

                                        ]

                                    }

                                ]

                            },

                            options: {

                                responsive: true,

                                maintainAspectRatio: false

                            }

                        }
                    );

                }
            @endif

        });
    </script>

@endsection
