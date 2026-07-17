@extends('layouts.admin')

@section('content')
    <style>
        #barcodeTable {
            padding-top: 25px;
        }

        .report-card {
            border-radius: 15px;
            transition: all .3s;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .08);
        }

        .report-card:hover {
            transform: translateY(-3px);
        }

        .report-card .count {
            font-size: 34px;
            font-weight: 700;
        }

        .report-card .title {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .dashboard-card {
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
        }

        .dashboard-card .card-header {
            padding: 8px 15px;
            font-size: 16px;
            font-weight: 600;
        }

        .dashboard-card .card-body {
            padding: 12px 15px;
        }

        .dashboard-card p {
            margin-bottom: 6px;
            font-size: 14px;
        }

        .dashboard-card b {
            font-size: 16px;
        }

        .row.g-4 {
            --bs-gutter-y: 12px;
        }

        .card {
            border-radius: 12px;
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

        .btn {
            height: 42px;
            border-radius: 8px;
            font-weight: 500;
        }

        .card-body {
            padding: 20px;
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

        .btn-light {
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .stat-card {
            border-radius: 12px;
            transition: .2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .icon {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .bg-purple {
            background: #f2e8ff;
            color: #8e44ad;
        }

        .summary-head {
            background: #082b68;
            color: #fff;
        }

        .summary-head th {

            background: #0d2d68 !important;

            color: #fff;

            font-size: 13px;

            font-weight: 600;

        }

        .table td,
        .table th {

            padding: .55rem;

            font-size: 13px;

            vertical-align: middle;

        }

        .table tbody th {
            background: #fafafa;
        }

        .container-fluid {

            max-width: 1900px;

            margin: auto;

        }

        .card {

            border: none;

            border-radius: 10px;

            box-shadow: 0 1px 6px rgba(0, 0, 0, .08);

        }

        .summary-head th {
            background: #082b68 !important;
            color: #fff !important;
            border-color: #082b68 !important;
            font-weight: 600;
        }

        .dashboard-card {
            border-radius: 12px;
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .08);
            height: 100%;
        }

        .chart-card {
            height: 320px;
        }

        .chart-card .card-body {
            height: 100%;
        }

        .staff-card {

            height: 320px;

            overflow: auto;

        }

        .chart-card canvas {
            width: 100% !important;
            height: 240px !important;
        }

        .chart-card {

            height: 320px;

            border-radius: 12px;

        }

        .chart-card canvas {

            height: 250px !important;

        }

        .card {

            border-radius: 12px;

        }

        .card h6 {

            font-weight: 600;

            margin-bottom: 15px;

        }

        .progress {

            height: 8px;

            background: #eee;

        }

        .progress-bar {

            border-radius: 10px;

        }

        .table td {

            vertical-align: middle;

        }

        .badge {

            padding: 7px 10px;

            font-size: 12px;

            border-radius: 20px;

        }

        .card-header {

            font-weight: 600;

        }

        .card-body small {

            font-size: 12px;

            color: #999;

        }

        .card-body h5 {

            margin: 0;

            font-weight: 700;

        }

        .card-body h6 {

            margin: 0;

            font-weight: 600;

        }

        .client-card {

            height: 320px;

            overflow: auto;

        }

        .insight-card {

            height: 320px;

        }

        .stat-card {

            height: 74px;

            border-radius: 12px;

        }

        .stat-card .card-body {

            padding: 12px;

        }

        .icon {

            width: 44px;

            height: 44px;

            font-size: 18px;

        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">

            <form method="GET" action="{{ route('orders.list') }}">

                <div class="row g-3 align-items-end">

                    <!-- Client -->
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label fw-semibold small">Client</label>

                        @if (auth()->user()->role == 'client')
                            <input type="hidden" name="client_id" value="{{ $clients->first()->id }}">

                            <input type="text" class="form-control" value="{{ $clients->first()->client_name }}"
                                readonly>
                        @else
                            <select name="client_id" id="client_id" class="form-select">
                                <option value="">Select Client</option>

                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}"
                                        {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                        {{ $client->client_name }}
                                    </option>
                                @endforeach

                            </select>
                        @endif
                    </div>
                    <div class="col-md-3">
                        <label>Product</label>

                        <select name="product" id="product" class="form-control">

                            <option value="">All Products</option>

                        </select>
                    </div>
                    <!-- Staff -->
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label fw-semibold small">Staff</label>

                        <select name="staff_id" class="form-select">
                            <option value="">All Staff</option>

                            @foreach ($staffs as $staff)
                                <option value="{{ $staff->id }}"
                                    {{ request('staff_id') == $staff->id ? 'selected' : '' }}>
                                    {{ $staff->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label fw-semibold small">Status</label>

                        <select name="delivery_status" class="form-select">
                            <option value="">All Status</option>
                            <option value="Delivered" {{ request('delivery_status') == 'Delivered' ? 'selected' : '' }}>
                                Delivered
                            </option>
                            <option value="RTO" {{ request('delivery_status') == 'RTO' ? 'selected' : '' }}>RTO</option>
                            <option value="In Transit" {{ request('delivery_status') == 'In Transit' ? 'selected' : '' }}>
                                In
                                Transit</option>
                            <option value="Out For Delivery"
                                {{ request('delivery_status') == 'Out For Delivery' ? 'selected' : '' }}>Out For Delivery
                            </option>
                            <option value="null" {{ request('delivery_status') == 'null' ? 'selected' : '' }}>No Status
                            </option>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label fw-semibold small">Date From</label>

                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                    </div>

                    <!-- Date To -->
                    <div class="col-lg-2 col-md-6">
                        <label class="form-label fw-semibold small">Date To</label>

                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                    </div>

                    <!-- Search -->
                    <div class="col-lg-3 col-md-8">
                        <label class="form-label fw-semibold small">Search</label>


                        <label class="form-label fw-semibold">
                            Bulk Search
                        </label>

                        <textarea name="search" class="form-control" rows="4" placeholder="Paste Order ID / Barcode / Phone">
{{ request('search') }}</textarea>

                        <small class="text-muted">
                            One value per line or comma separated.
                        </small>

                    </div>

                    <!-- Records -->
                    <div class="col-lg-1 col-md-4">
                        <label class="form-label fw-semibold small">Records</label>

                        <select name="per_page" class="form-select" onchange="this.form.submit()">

                            <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page', 100) == 100 ? 'selected' : '' }}>100</option>
                            <option value="500" {{ request('per_page') == 500 ? 'selected' : '' }}>500</option>
                            <option value="1000" {{ request('per_page') == 1000 ? 'selected' : '' }}>1000</option>
                            <option value="5000" {{ request('per_page') == 5000 ? 'selected' : '' }}>5000</option>
                            <option value="10000" {{ request('per_page') == 10000 ? 'selected' : '' }}>10000</option>
                            <option value="15000" {{ request('per_page') == 15000 ? 'selected' : '' }}>15000</option>

                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="col-lg-2 col-md-12 d-flex gap-2">

                        <button type="submit" class="btn btn-primary w-100">
                            Filter
                        </button>

                        <a href="{{ route('orders.list') }}" class="btn btn-outline-secondary w-100">
                            Reset
                        </a>
                        <button type="button" id="compareBtn" class="btn btn-warning">

                            Compare

                        </button>
                    </div>

                </div>
                <div class="card border-warning shadow-sm mb-4" id="compareSection" style="display:none;">

                    <div class="card-header bg-warning">

                        <h5 class="mb-0">

                            <i class="fas fa-chart-line"></i>

                            Compare Current Report

                        </h5>

                    </div>

                    <div class="card-body">

                        <div class="alert alert-info mb-4">

                            <strong>

                                Current Filters

                            </strong>

                            will remain same.

                            Only Date Range will change.

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

    <div class="card mb-3" style="padding: 15px;">

        <div class="table-responsive">
            @if (!empty($searchTerms))
                <div class="alert alert-info mb-3">

                    <strong>Total Search Items:</strong>
                    {{ count($searchTerms) }}

                    &nbsp;&nbsp;|&nbsp;&nbsp;

                    <strong>Matched:</strong>
                    {{ count($searchTerms) - count($notFound) }}

                    &nbsp;&nbsp;|&nbsp;&nbsp;

                    <strong>Not Found:</strong>
                    {{ count($notFound) }}

                </div>
            @endif
            @if (!empty($notFound))
                <div class="alert alert-danger">

                    <strong>Records Not Found ({{ count($notFound) }})</strong>

                    <textarea class="form-control mt-2" rows="6" readonly>{{ implode("\n", $notFound) }}</textarea>

                </div>
            @endif
            @if (request()->anyFilled(['client_id', 'staff_id', 'delivery_status', 'date_from', 'date_to', 'search']))
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body py-3">

                        <h6 class="fw-bold mb-3">
                            Applied Filters
                        </h6>

                        <div class="d-flex flex-wrap gap-2 align-items-center">

                            @if (request('client_id'))
                                <span class="filter-badge bg-client">
                                    <strong>Client :</strong>
                                    {{ optional($clients->where('id', request('client_id'))->first())->client_name }}
                                </span>
                            @endif

                            @if (request('staff_id'))
                                <span class="filter-badge bg-staff">
                                    <strong>Staff :</strong>
                                    {{ optional($staffs->where('id', request('staff_id'))->first())->name }}
                                </span>
                            @endif

                            @if (request('date_from') || request('date_to'))
                                <span class="filter-badge bg-date">
                                    <strong>Date :</strong>
                                    {{ request('date_from') ? \Carbon\Carbon::parse(request('date_from'))->format('d-m-Y') : '' }}
                                    @if (request('date_from') && request('date_to'))
                                        to
                                    @endif
                                    {{ request('date_to') ? \Carbon\Carbon::parse(request('date_to'))->format('d-m-Y') : '' }}
                                </span>
                            @endif

                            @if (request('delivery_status'))
                                <span class="filter-badge bg-status">
                                    <strong>Status :</strong>
                                    {{ request('delivery_status') }}
                                </span>
                            @endif

                            @if (request('search'))
                                <span class="filter-badge bg-search">
                                    <strong>Search :</strong>
                                    {{ request('search') }}
                                </span>
                            @endif

                            <a href="{{ route('orders.list') }}" class="btn btn-light btn-sm px-3">
                                <i class="fas fa-times me-1"></i> Clear All
                            </a>

                        </div>

                    </div>
                </div>

            @endif
            @if (auth()->user()->role == 'super_admin')
                <div class="row g-3 mb-4">

                    <div class="col-lg col-md-3">
                        <div class="card border-0 shadow-sm stat-card">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon bg-primary-subtle text-primary">
                                    <i class="fas fa-shopping-bag"></i>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted">Total Orders</small>
                                    <h3>{{ number_format($totalOrders) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg col-md-3">
                        <div class="card border-0 shadow-sm stat-card">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon bg-success-subtle text-success">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted">Delivered</small>
                                    <h3>{{ number_format($totalDelivered) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="col-lg col-md-3">
                        <div class="card border-0 shadow-sm stat-card">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon bg-danger-subtle text-danger">
                                    <i class="fas fa-reply"></i>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted">RTO</small>
                                    <h3>{{ number_format($totalRto) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg col-md-3">
                        <div class="card border-0 shadow-sm stat-card">
                            <div class="card-body d-flex align-items-center">

                                <div class="icon bg-danger-subtle text-danger">
                                    <i class="fas fa-undo"></i>
                                </div>

                                <div class="ms-3">
                                    <small class="text-muted">RTO Received</small>

                                    <h4 class="text-danger mb-0">
                                        {{ number_format($totalRtoReceived) }}
                                    </h4>


                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-lg col-md-3">
                        <div class="card border-0 shadow-sm stat-card">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon bg-warning-subtle text-warning">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted">In Transit</small>
                                    <h3>{{ number_format($totalTransit) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg col-md-3">
                        <div class="card border-0 shadow-sm stat-card">
                            <div class="card-body d-flex align-items-center">
                                <div class="icon bg-purple">
                                    <i class="fas fa-question-circle"></i>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted">Our No Status</small>
                                    <h3>{{ number_format($totalNoStatus) }}</h3>
                                    <!--  <small class="text-muted">Our Side Pending</small>                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <h6>{{ number_format($ourSidePending) }}</h6>-->



                                    <small class="text-muted">Transit > 7 Days</small>
                                    <h6 class="text-warning">{{ number_format($transit7Days) }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- <div class="col-lg col-md-3">
                                                                                                                        <div class="card border-0 shadow-sm stat-card">
                                                                                                                            <div class="card-body d-flex align-items-center">

                                                                                                                                <div class="icon bg-info-subtle text-info">
                                                                                                                                    <i class="fas fa-money-bill-wave"></i>
                                                                                                                                </div>

                                                                                                                                <div class="ms-3">
                                                                                                                                    <small class="text-muted">Payment Received</small>

                                                                                                                                    <h4 class="text-success mb-0">
                                                                                                                                        ₹{{ number_format($paymentReceivedAmount) }}
                                                                                                                                    </h4>

                                                                                                                                    <small class="text-muted">
                                                                                                                                        {{ number_format($paymentReceivedOrders) }} Orders
                                                                                                                                    </small>

                                                                                                                                </div>

                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                    </div>

                                                                                                                    <div class="col-lg col-md-3">
                                                                                                                        <div class="card border-0 shadow-sm stat-card">
                                                                                                                            <div class="card-body d-flex align-items-center">

                                                                                                                                <div class="icon bg-warning-subtle text-warning">
                                                                                                                                    <i class="fas fa-wallet"></i>
                                                                                                                                </div>

                                                                                                                                <div class="ms-3">

                                                                                                                                    <small class="text-muted">Pending Payment</small>

                                                                                                                                    <h4 class="text-warning mb-0">
                                                                                                                                        ₹{{ number_format($paymentPendingAmount) }}
                                                                                                                                    </h4>

                                                                                                                                    <small class="text-muted">
                                                                                                                                        {{ number_format($paymentPendingOrders) }} Orders
                                                                                                                                    </small>

                                                                                                                                </div>

                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                    </div>-->

                </div>

                <div class="card border-0 shadow-sm">

                    <div class="card-header bg-white border-bottom">
                        <strong>Web vs WhatsApp Summary</strong>
                    </div>

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle text-center mb-0">

                            <thead class="summary-head">

                                <tr>
                                    <th>Category</th>
                                    <th>Orders</th>
                                    <th>Delivered</th>
                                    <th>RTO</th>
                                    <th>RTO Received</th>
                                    <th>In Transit</th>
                                    <th>No Status</th>
                                    <!--<th>Payment Received</th>
                                                                                                        <th>Pending Payment</th>-->
                                </tr>

                            </thead>

                            <tbody>

                                <tr>
                                    <th class="text-primary">Total</th>

                                    <td><strong>{{ number_format($totalOrders) }}</strong></td>

                                    <td>
                                        <strong>{{ number_format($totalDelivered) }}</strong>
                                        <small class="text-success">{{ $totalDeliveredPercent }}%</small>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($totalRto) }}</strong>
                                        <small class="text-success">{{ $totalRtoPercent }}%</small>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($totalRtoReceived) }}</strong>
                                        <small class="text-success">{{ $totalRtoReceivedPercent }}%</small>
                                    </td>


                                    <td>
                                        <strong>{{ number_format($totalTransit) }}</strong>
                                        <small class="text-success">{{ $totalTransitPercent }}%</small>
                                    </td>
                                    <td>
                                        <strong>{{ number_format($totalNoStatus) }}</strong>
                                        <small class="text-success">{{ $totalNoStatusPercent }}%</small>
                                    </td>

                                    <!--  <td>
                                                                                                                                <strong class="text-success">
                                                                                                                                    ₹{{ number_format($paymentReceivedAmount) }}
                                                                                                                                </strong><br>
                                                                                                                                <small>
                                                                                                                                    {{ $paymentReceivedOrders }} Orders ({{ $paymentReceivedPercent }}%)
                                                                                                                                </small>
                                                                                                                            </td>

                                                                                                                            <td>
                                                                                                                                <strong class="text-danger">
                                                                                                                                    ₹{{ number_format($paymentPendingAmount) }}
                                                                                                                                </strong><br>
                                                                                                                                <small>
                                                                                                                                    {{ $paymentPendingOrders }} Orders ({{ $paymentPendingPercent }}%)
                                                                                                                                </small>
                                                                                                                            </td>-->

                                </tr>

                                <tr>
                                    <th class="text-primary">
                                        🌐 Web
                                    </th>

                                    <td>{{ $webOrders }}</td>
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
                                        <small class="text-success">
                                            {{ $webRtoPercent }}%
                                        </small>
                                    </td>
                                    <td>
                                        {{ number_format($webRtoReceived) }}
                                        <br>
                                        <small class="text-success">
                                            {{ $webRtoReceivedPercent }}%
                                        </small>
                                    </td>
                                    <td>
                                        {{ number_format($webTransit) }}
                                        <br>
                                        <small class="text-success">
                                            {{ $webTransitPercent }}%
                                        </small>
                                    </td>
                                    <td>
                                        {{ number_format($webNoStatus) }}
                                        <br>
                                        <small class="text-success">
                                            {{ $webNoStatusPercent }}%
                                        </small>
                                    </td>



                                    <!--<td class="text-success">
                                                                                                                            ₹{{ number_format($webPaymentReceivedAmount ?? 0) }}
                                                                                                                        </td>

                                                                                                                        <td class="text-danger">
                                                                                                                            ₹{{ number_format($webPaymentPendingAmount ?? 0) }}
                                                                                                                        </td>-->

                                </tr>

                                <tr>
                                    <th class="text-success">
                                        <i class="fab fa-whatsapp"></i> WhatsApp
                                    </th>

                                    <td>{{ $whatsappOrders }}</td>
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
                                        <small class="text-success">
                                            {{ $waRtoPercent }}%
                                        </small>
                                    </td>

                                    <td>
                                        {{ number_format($whatsappRtoReceived) }}
                                        <br>
                                        <small class="text-success">
                                            {{ $waRtoReceivedPercent }}%
                                        </small>
                                    </td>
                                    <td>
                                        {{ number_format($whatsappTransit) }}
                                        <br>
                                        <small class="text-success">
                                            {{ $waTransitPercent }}%
                                        </small>
                                    </td>
                                    <td>
                                        {{ number_format($whatsappNoStatus) }}
                                        <br>
                                        <small class="text-success">
                                            {{ $waNoStatusPercent }}%
                                        </small>
                                    </td>

                                    <!-- <td class="text-success">
                                                                                                                        ₹{{ number_format($whatsappPaymentReceivedAmount ?? 0) }}
                                                                                                                    </td>

                                                                                                                    <td class="text-danger">
                                                                                                                        ₹{{ number_format($whatsappPaymentPendingAmount ?? 0) }}
                                                                                                                    </td>-->

                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

                @if ($compareData)
                    <hr class="my-5">

                    <div class="text-center mb-3">
                        <h3 class="text-warning">
                            Compare Report
                        </h3>

                        <small>

                            {{ request('compare_from') }}

                            →

                            {{ request('compare_to') }}

                        </small>
                    </div>

                    <div class="card shadow-sm border-warning">

                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                Web vs WhatsApp Summary (Compare)
                            </h5>
                        </div>

                        <div class="card-body p-0">

                            <table class="table table-bordered table-hover mb-0">

                                <thead class="table-dark text-center">

                                    <tr>

                                        <th>Category</th>

                                        <th>Orders</th>

                                        <th>Delivered</th>

                                        <th>RTO</th>

                                        <th>Transit</th>

                                        <th>No Status</th>

                                        <th>Payment Received</th>

                                        <th>Pending Payment</th>

                                    </tr>

                                </thead>

                                <tbody class="text-center">

                                    <tr>

                                        <th>Total</th>

                                        <td>{{ $compareData['totalOrders'] }}</td>

                                        <td>{{ $compareData['totalDelivered'] }}</td>

                                        <td>{{ $compareData['totalRto'] }}</td>

                                        <td>{{ $compareData['totalTransit'] }}</td>

                                        <td>{{ $compareData['totalNoStatus'] }}</td>

                                        <td>
                                            <strong class="text-success">
                                                ₹{{ number_format($compareData['paymentReceivedAmount'], 2) }}
                                            </strong>
                                            <br>
                                            <small>
                                                {{ $compareData['paymentReceivedOrders'] }}
                                                Orders
                                                ({{ $compareData['totalOrders'] > 0
                                                    ? number_format(($compareData['paymentReceivedOrders'] / $compareData['totalOrders']) * 100, 2)
                                                    : 0 }}%)
                                            </small>
                                        </td>

                                        <td>
                                            <strong class="text-danger">
                                                ₹{{ number_format($compareData['paymentPendingAmount'], 2) }}
                                            </strong>
                                            <br>
                                            <small>
                                                {{ $compareData['paymentPendingOrders'] }}
                                                Orders
                                                ({{ $compareData['totalDelivered'] > 0
                                                    ? number_format(($compareData['paymentPendingOrders'] / $compareData['totalDelivered']) * 100, 2)
                                                    : 0 }}%)
                                            </small>
                                        </td>
                                    </tr>

                                    <tr>

                                        <th>Web</th>

                                        <td>{{ $compareData['webOrders'] }}</td>

                                        <td>
                                            {{ $compareData['webDelivered'] }}
                                            <br>
                                            <small class="text-success">
                                                {{ $compareData['webOrders'] > 0
                                                    ? number_format(($compareData['webDelivered'] / $compareData['webOrders']) * 100, 2)
                                                    : 0 }}%
                                            </small>
                                        </td>

                                        <td>
                                            {{ $compareData['webRto'] }}
                                            <br>
                                            <small class="text-success">
                                                {{ $compareData['webOrders'] > 0
                                                    ? number_format(($compareData['webRto'] / $compareData['webOrders']) * 100, 2)
                                                    : 0 }}%
                                            </small>
                                        </td>
                                        <td>
                                            {{ $compareData['webTransit'] }}
                                            <br>
                                            <small class="text-success">
                                                {{ $compareData['webOrders'] > 0
                                                    ? number_format(($compareData['webTransit'] / $compareData['webOrders']) * 100, 2)
                                                    : 0 }}%
                                            </small>
                                        </td>

                                        <td>
                                            {{ $compareData['webNoStatus'] }}
                                            <br>
                                            <small class="text-success">
                                                {{ $compareData['webOrders'] > 0
                                                    ? number_format(($compareData['webNoStatus'] / $compareData['webOrders']) * 100, 2)
                                                    : 0 }}%
                                            </small>
                                        </td>

                                        <td>-</td>

                                        <td>-</td>

                                    </tr>

                                    <tr>

                                        <th>WhatsApp</th>

                                        <td>{{ $compareData['whatsappOrders'] }}</td>

                                        <td>
                                            {{ $compareData['whatsappDelivered'] }}
                                            <br>
                                            <small class="text-success">
                                                {{ $compareData['whatsappOrders'] > 0
                                                    ? number_format(($compareData['whatsappDelivered'] / $compareData['whatsappOrders']) * 100, 2)
                                                    : 0 }}%
                                            </small>
                                        </td>

                                        <td>
                                            {{ $compareData['whatsappRto'] }}
                                            <br>
                                            <small class="text-success">
                                                {{ $compareData['whatsappOrders'] > 0
                                                    ? number_format(($compareData['whatsappRto'] / $compareData['whatsappOrders']) * 100, 2)
                                                    : 0 }}%
                                            </small>
                                        </td>

                                        <td>
                                            {{ $compareData['whatsappTransit'] }}
                                            <br>
                                            <small class="text-success">
                                                {{ $compareData['whatsappOrders'] > 0
                                                    ? number_format(($compareData['whatsappTransit'] / $compareData['whatsappOrders']) * 100, 2)
                                                    : 0 }}%
                                            </small>
                                        </td>

                                        <td>
                                            {{ $compareData['whatsappNoStatus'] }}
                                            <br>
                                            <small class="text-success">
                                                {{ $compareData['whatsappOrders'] > 0
                                                    ? number_format(($compareData['whatsappNoStatus'] / $compareData['whatsappOrders']) * 100, 2)
                                                    : 0 }}%
                                            </small>
                                        </td>

                                        <td>-</td>

                                        <td>-</td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>
                @endif
                <!--<div class="row g-3 mb-3">

                                                                                                                                                                                                                                                    <div class="col-xl-3 col-lg-6">
                                                                                                                                                                                                                                                        <div class="card shadow-sm chart-card">
                                                                                                                                                                                                                                                            <div class="card-body">
                                                                                                                                                                                                                                                                <h6 class="fw-bold">Orders Trend</h6>
                                                                                                                                                                                                                                                                <canvas id="ordersChart"></canvas>
                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                    </div>

                                                                                                                                                                                                                                                    <div class="col-xl-3 col-lg-6">
                                                                                                                                                                                                                                                        <div class="card shadow-sm chart-card">
                                                                                                                                                                                                                                                            <div class="card-body">
                                                                                                                                                                                                                                                                <h6 class="fw-bold">Delivery Trend</h6>
                                                                                                                                                                                                                                                                <canvas id="deliveryChart"></canvas>
                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                    </div>

                                                                                                                                                                                                                                                    <div class="col-xl-3 col-lg-6">
                                                                                                                                                                                                                                                        <div class="card shadow-sm chart-card">
                                                                                                                                                                                                                                                            <div class="card-body">
                                                                                                                                                                                                                                                                <h6 class="fw-bold">Payment Trend</h6>
                                                                                                                                                                                                                                                                <canvas id="paymentChart"></canvas>
                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                    </div>

                                                                                                                                                                                                                                                    <div class="col-xl-3 col-lg-6">
                                                                                                                                                                                                                                                        <div class="card shadow-sm chart-card">
                                                                                                                                                                                                                                                            <div class="card-body">
                                                                                                                                                                                                                                                                <h6 class="fw-bold">Source Distribution</h6>
                                                                                                                                                                                                                                                                <canvas id="sourceChart"></canvas>
                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                    </div>

                                                                                                                                                                                                                                                </div>

                                                                                                                                                                                                                                                <div class="row g-3">

                                                                                                                                                                                                                                                    <div class="col-xl-4">

                                                                                                                                                                                                                                                        <div class="card shadow-sm staff-card">

                                                                                                                                                                                                                                                            <div class="card-header">

                                                                                                                                                                                                                                                                <b>Staff Performance (Orders)</b>

                                                                                                                                                                                                                                                            </div>

                                                                                                                                                                                                                                                            <div class="table-responsive">

                                                                                                                                                                                                                                                                <table class="table table-sm align-middle">

                                                                                                                                                                                                                                                                    <thead>

                                                                                                                                                                                                                                                                        <tr>

                                                                                                                                                                                                                                                                            <th>#</th>

                                                                                                                                                                                                                                                                            <th>Staff</th>

                                                                                                                                                                                                                                                                            <th>Total</th>

                                                                                                                                                                                                                                                                            <th>Delivered</th>

                                                                                                                                                                                                                                                                            <th>Success</th>

                                                                                                                                                                                                                                                                        </tr>

                                                                                                                                                                                                                                                                    </thead>

                                                                                                                                                                                                                                                                    <tbody>

                                                                                                                                                                                                                                                                        @foreach ($staffPerformance as $index => $staff)
    <tr>

                                                                                                                                                                                                                                                                                <td>{{ $index + 1 }}</td>

                                                                                                                                                                                                                                                                                <td>{{ $staff->name }}</td>

                                                                                                                                                                                                                                                                                <td>{{ $staff->total_orders }}</td>

                                                                                                                                                                                                                                                                                <td>{{ $staff->delivered }}</td>

                                                                                                                                                                                                                                                                                <td width="170">

                                                                                                                                                                                                                                                                                    <div class="progress" style="height:8px;">

                                                                                                                                                                                                                                                                                        <div class="progress-bar bg-success"
                                                                                                                                                                                                                                                                                            style="width:{{ $staff->success }}%">

                                                                                                                                                                                                                                                                                        </div>

                                                                                                                                                                                                                                                                                    </div>

                                                                                                                                                                                                                                                                                    <small>

                                                                                                                                                                                                                                                                                        {{ $staff->success }}%

                                                                                                                                                                                                                                                                                    </small>

                                                                                                                                                                                                                                                                                </td>

                                                                                                                                                                                                                                                                            </tr>
    @endforeach

                                                                                                                                                                                                                                                                    </tbody>

                                                                                                                                                                                                                                                                </table>

                                                                                                                                                                                                                                                            </div>

                                                                                                                                                                                                                                                        </div>

                                                                                                                                                                                                                                                    </div>

                                                                                                                                                                                                                                                    <div class="col-xl-4">

                                                                                                                                                                                                                                                        <div class="card shadow-sm client-card">

                                                                                                                                                                                                                                                            <div class="card-header bg-white">

                                                                                                                                                                                                                                                                <strong>🏆 Top Clients</strong>

                                                                                                                                                                                                                                                            </div>

                                                                                                                                                                                                                                                            <div class="table-responsive">

                                                                                                                                                                                                                                                                <table class="table table-hover align-middle mb-0">

                                                                                                                                                                                                                                                                    <thead class="table-light">

                                                                                                                                                                                                                                                                        <tr>

                                                                                                                                                                                                                                                                            <th>#</th>

                                                                                                                                                                                                                                                                            <th>Client</th>

                                                                                                                                                                                                                                                                            <th>Web</th>

                                                                                                                                                                                                                                                                            <th>WhatsApp</th>

                                                                                                                                                                                                                                                                            <th>Total</th>

                                                                                                                                                                                                                                                                        </tr>

                                                                                                                                                                                                                                                                    </thead>

                                                                                                                                                                                                                                                                    <tbody>

                                                                                                                                                                                                                                                                        @forelse($topClients as $index=>$client)
    <tr>

                                                                                                                                                                                                                                                                                <td>{{ $index + 1 }}</td>

                                                                                                                                                                                                                                                                                <td>

                                                                                                                                                                                                                                                                                    <strong>

                                                                                                                                                                                                                                                                                        {{ $client->client_name }}

                                                                                                                                                                                                                                                                                    </strong>

                                                                                                                                                                                                                                                                                </td>

                                                                                                                                                                                                                                                                                <td>

                                                                                                                                                                                                                                                                                    <span class="badge bg-primary">

                                                                                                                                                                                                                                                                                        {{ $client->web_orders }}

                                                                                                                                                                                                                                                                                    </span>

                                                                                                                                                                                                                                                                                </td>

                                                                                                                                                                                                                                                                                <td>

                                                                                                                                                                                                                                                                                    <span class="badge bg-success">

                                                                                                                                                                                                                                                                                        {{ $client->whatsapp_orders }}

                                                                                                                                                                                                                                                                                    </span>

                                                                                                                                                                                                                                                                                </td>

                                                                                                                                                                                                                                                                                <td>

                                                                                                                                                                                                                                                                                    <strong>

                                                                                                                                                                                                                                                                                        {{ $client->total_orders }}

                                                                                                                                                                                                                                                                                    </strong>

                                                                                                                                                                                                                                                                                </td>

                                                                                                                                                                                                                                                                            </tr>

                                        @empty

                                                                                                                                                                                                                                                                            <tr>

                                                                                                                                                                                                                                                                                <td colspan="5" class="text-center">

                                                                                                                                                                                                                                                                                    No Record Found

                                                                                                                                                                                                                                                                                </td>

                                                                                                                                                                                                                                                                            </tr>
    @endforelse

                                                                                                                                                                                                                                                                    </tbody>

                                                                                                                                                                                                                                                                </table>

                                                                                                                                                                                                                                                            </div>

                                                                                                                                                                                                                                                        </div>

                                                                                                                                                                                                                                                    </div>

                                                                                                                                                                                                                                                    <div class="col-xl-2">
                                                                                                                                                                                                                                                        <div class="card shadow-sm chart-card">
                                                                                                                                                                                                                                                            <div class="card-body">
                                                                                                                                                                                                                                                                <h6 class="fw-bold">Order Status</h6>
                                                                                                                                                                                                                                                                <canvas id="statusChart"></canvas>
                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                    </div>

                                                                                                                                                                                                                                                    <div class="col-xl-2">

                                                                                                                                                                                                                                                        <div class="card shadow-sm">

                                                                                                                                                                                                                                                            <div class="card-header bg-white">

                                                                                                                                                                                                                                                                <strong>💡 Quick Insights</strong>

                                                                                                                                                                                                                                                            </div>

                                                                                                                                                                                                                                                            <div class="card-body">

                                                                                                                                                                                                                                                                <div class="mb-3">

                                                                                                                                                                                                                                                                    <small class="text-muted">

                                                                                                                                                                                                                                                                        Delivery Rate

                                                                                                                                                                                                                                                                    </small>

                                                                                                                                                                                                                                                                    <h5 class="text-success">

                                                                                                                                                                                                                                                                        {{ $deliveryRate }}%

                                                                                                                                                                                                                                                                    </h5>

                                                                                                                                                                                                                                                                </div>

                                                                                                                                                                                                                                                                <hr>

                                                                                                                                                                                                                                                                <div class="mb-3">

                                                                                                                                                                                                                                                                    <small class="text-muted">

                                                                                                                                                                                                                                                                        RTO Rate

                                                                                                                                                                                                                                                                    </small>

                                                                                                                                                                                                                                                                    <h5 class="text-danger">

                                                                                                                                                                                                                                                                        {{ $rtoRate }}%

                                                                                                                                                                                                                                                                    </h5>

                                                                                                                                                                                                                                                                </div>

                                                                                                                                                                                                                                                                <hr>

                                                                                                                                                                                                                                                                <div class="mb-3">

                                                                                                                                                                                                                                                                    <small class="text-muted">

                                                                                                                                                                                                                                                                        Avg Orders / Day

                                                                                                                                                                                                                                                                    </small>

                                                                                                                                                                                                                                                                    <h5>

                                                                                                                                                                                                                                                                        {{ $averageOrders }}

                                                                                                                                                                                                                                                                    </h5>

                                                                                                                                                                                                                                                                </div>

                                                                                                                                                                                                                                                                <hr>

                                                                                                                                                                                                                                                                <div class="mb-3">

                                                                                                                                                                                                                                                                    <small class="text-muted">

                                                                                                                                                                                                                                                                        Best Day

                                                                                                                                                                                                                                                                    </small>

                                                                                                                                                                                                                                                                    <h6>

                                                                                                                                                                                                                                                                        @if ($bestDay)
    {{ \Carbon\Carbon::parse($bestDay->day)->format('d M') }}
    @endif

                                                                                                                                                                                                                                                                    </h6>

                                                                                                                                                                                                                                                                </div>

                                                                                                                                                                                                                                                                <hr>

                                                                                                                                                                                                                                                                <div class="mb-3">

                                                                                                                                                                                                                                                                    <small class="text-muted">

                                                                                                                                                                                                                                                                        Highest Payment

                                                                                                                                                                                                                                                                    </small>

                                                                                                                                                                                                                                                                    <h6 class="text-primary">

                                                                                                                                                                                                                                                                        @if ($highestPayment)
    ₹{{ number_format($highestPayment->amount, 2) }}
    @endif

                                                                                                                                                                                                                                                                    </h6>

                                                                                                                                                                                                                                                                </div>

                                                                                                                                                                                                                                                                <hr>

                                                                                                                                                                                                                                                                <div>

                                                                                                                                                                                                                                                                    <small class="text-muted">

                                                                                                                                                                                                                                                                        Best Staff

                                                                                                                                                                                                                                                                    </small>

                                                                                                                                                                                                                                                                    <h6>

                                                                                                                                                                                                                                                                        @if ($bestStaff)
    {{ $bestStaff->name }}
                                                                                                                                                                                                                                                                            <br>
                                                                                                                                                                                                                                                                            <small class="text-success">
                                                                                                                                                                                                                                                                                {{ $bestStaff->delivered }} Deliveries
                                                                                                                                                                                                                                                                            </small>
@else
    N/A
    @endif

                                                                                                                                                                                                                                                                    </h6>

                                                                                                                                                                                                                                                                </div>

                                                                                                                                                                                                                                                            </div>

                                                                                                                                                                                                                                                        </div>

                                                                                                                                                                                                                                                    </div>

                                                                                                                                                                                                                                                </div>-->
            @endif
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
                    Download Money order Pdf
                </button>
                <button class="btn btn-success" id="downloadexcelsss">
                    Download Excel
                </button>
                <button class="btn btn-primary d-none" id="updateStatusBtn">
                    <i class="fa fa-edit"></i>
                    Update Status
                </button>
            </div>
            <table id="barcodeTable" class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>#</th>
                        <th>Order ID</th>

                        <th>Delivery Status</th>
                        <th>Payment Status</th>
                        <th>RTO Receive Status</th>
                        <th>Barcode</th>
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
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td>
                                <input type="checkbox" class="order-checkbox" value="{{ $order->id }}">
                            </td>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="Assigned To: {{ $order->callingOrder->staff->name ?? 'Not Assigned' }}">

                                    {{ $order->order_id }}
                                </a>
                            </td>
                            <td>
                                @if (strtolower($order->delivery_status) == 'delivered')
                                    <span class="badge bg-success fs-6">
                                        {{ $order->delivery_status }}
                                    </span>
                                @elseif(strtolower($order->delivery_status) == 'in transit' || strtolower($order->delivery_status) == 'out for delivery')
                                    <span class="badge bg-primary fs-6">
                                        {{ $order->delivery_status }}
                                    </span>
                                @else
                                    <span class="badge bg-danger fs-6">
                                        {{ $order->delivery_status }}
                                    </span>
                                @endif
                            </td>
                            <td class="{{ $order->recivedpaysts == 1 ? 'bg-success text-white fw-bold' : '' }}">
                                {{ $order->recivedpaysts == 1 ? 'Payment Received' : '' }}
                            </td>

                            <td class="{{ $order->rtorecivedsts == 1 ? 'bg-warning text-dark fw-bold' : '' }}">
                                {{ $order->rtorecivedsts == 1 ? 'RTO Received' : '' }}
                            </td>
                            <td>{{ $order->barcode }}</td>
                            <td>{{ $order->customer_name }}</td>
                            <td>{{ $order->customer_phone }}</td>
                            <td>{{ $order->shipping_address }}</td>
                            <td>{{ $order->payment_mode }}</td>
                            <td>{{ $order->amount }}</td>
                            <td>{{ $order->product }}</td>
                            <td>{{ $order->quantity }}</td>
                            <td>{{ $order->weight }}</td>
                            <td>{{ $order->date }}</td>
                            <td>{{ $order->delivery_remark }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
        </div>
    </div>
    {{-- SCRIPTS --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>



    <script>
        $('#client_id').on('change', function() {

            let clientId = $(this).val();

            $('#product').html('<option>Loading...</option>');

            if (clientId == '') {
                $('#product').html('<option value="">All Products</option>');
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
                            '<option value="' + product + '">' + product + '</option>';

                    });

                    $('#product').html(options);

                }

            });

        });


        document.addEventListener("DOMContentLoaded", function() {
            var tooltipTriggerList = [].slice.call(
                document.querySelectorAll('[data-bs-toggle="tooltip"]')
            );

            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
    <script>
        $(document).ready(function() {

            // Select All
            $('#selectAll').on('change', function() {
                $('.order-checkbox').prop('checked', $(this).prop('checked'));
                toggleButtons();
            });

            // Individual checkbox
            $(document).on('change', '.order-checkbox', function() {
                toggleButtons();
            });

            function toggleButtons() {
                let checkedCount = $('.order-checkbox:checked').length;
                if (checkedCount > 0) {
                    $('#bulkActions').removeClass('d-none');
                } else {
                    $('#bulkActions').addClass('d-none');
                }
            }

            function getSelectedOrders() {
                let ids = [];
                $('.order-checkbox:checked').each(function() {
                    ids.push($(this).val());
                });
                return ids;
            }

            // Invoice PDF
            $('#downloadInvoice').click(function() {
                let ids = getSelectedOrders();
                window.location.href = "{{ route('orders.invoice.pdf') }}?ids=" + ids.join(',');
            });

            // Excel
            $('#downloadExcel').click(function() {
                let ids = getSelectedOrders();
                window.location.href = "{{ route('orders.postoffice.excel') }}?ids=" + ids.join(',');
            });

            $('#downloadmoney').click(function() {
                let ids = getSelectedOrders();

                if (ids.length === 0) {
                    alert('Please select orders');
                    return;
                }

                let form = $('<form>', {
                    method: 'POST',
                    action: "{{ route('orders.Moneyorder.pdf') }}"
                });

                form.append('@csrf');
                form.append(`<input type="hidden" name="ids" value="${ids.join(',')}">`);

                $('body').append(form);
                form.submit();
            });




            $('#downloadexcelsss').click(function() {

                let ids = getSelectedOrders();

                if (ids.length === 0) {
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


        });
    </script>

    <div class="modal fade" id="senderModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="labelDownloadForm" method="POST" action="{{ route('labels.selected.pdf') }}">
                @csrf

                <input type="hidden" name="ids" id="selected_ids">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5>Select Sender</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <select name="sender_id" id="modal_sender_id" class="form-control" required>
                            <option value="">-- Select Sender --</option>
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
                                Use Old Barcode (Assign existing barcode)
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
    <div class="modal fade" id="statusModal">

        <div class="modal-dialog">

            <form method="POST" action="{{ route('orders.manual.delivery') }}">

                @csrf

                <input type="hidden" name="order_id" id="status_order_id">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5>Manual Delivery</h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
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

                        <label>Delivery Date</label>

                        <input type="date" class="form-control" name="delivery_date" value="{{ date('Y-m-d') }}">

                        <br>

                        <label>Remark</label>

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
    <script>
        $('#compareBtn').click(function() {

            $('#compareSection').slideToggle();

        });

        function toggleButtons() {

            let count = $('.order-checkbox:checked').length;

            if (count == 1) {

                $('#updateStatusBtn').removeClass('d-none');

            } else {

                $('#updateStatusBtn').addClass('d-none');

            }

        }

        $(document).on(
            'change',
            '.order-checkbox,#selectAll',
            toggleButtons
        );


        $(document).on(
            'click',
            '#updateStatusBtn',
            function() {

                let id = $('.order-checkbox:checked')
                    .first()
                    .val();

                $('#status_order_id').val(id);

                let modal = new bootstrap.Modal(
                    document.getElementById('statusModal')
                );

                modal.show();

            }
        );

        // get selected orders
        function getSelectedOrders() {
            let ids = [];
            $('.order-checkbox:checked').each(function() {
                ids.push($(this).val());
            });
            return ids;
        }

        // open sender modal

        $('#downloadLabel').on('click', function() {
            let ids = getSelectedOrders();

            if (ids.length === 0) {
                alert('Please select at least one order');
                return;
            }

            $('#selected_ids').val(ids.join(','));
            $('#senderModal').modal('show');
        });

        // select all
        $('#selectAll').on('change', function() {
            $('.order-checkbox').prop('checked', this.checked);
        });

        // confirm download
        $('#confirmDownloadLabel').on('click', function() {
            let senderId = $('#modal_sender_id').val();

            if (!senderId) {
                alert('Please select sender');
                return;
            }

            // submit POST form
            $('#labelDownloadForm').submit();

            $('#senderModal').modal('hide');
        });

        new Chart(document.getElementById('ordersChart'), {

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

        });


        new Chart(document.getElementById('deliveryChart'), {

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
                        label: 'RTO',
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

        });

        new Chart(document.getElementById('paymentChart'), {

            type: 'line',

            data: {

                labels: @json($labels),

                datasets: [

                    {
                        label: 'Received',
                        data: @json($paymentChart),
                        borderColor: '#2563eb',
                        backgroundColor: 'transparent',
                        tension: .4
                    }

                ]

            },

            options: {
                responsive: true,
                maintainAspectRatio: false
            }

        });


        new Chart(document.getElementById('sourceChart'), {

            type: 'doughnut',

            data: {

                labels: [
                    'Web',
                    'WhatsApp'
                ],

                datasets: [{

                    data: @json($sourceChart),

                    backgroundColor: [
                        '#2563eb',
                        '#22c55e'
                    ]

                }]

            },

            options: {
                responsive: true,
                maintainAspectRatio: false
            }

        });



        new Chart(document.getElementById('statusChart'), {

            type: 'doughnut',

            data: {

                labels: [
                    'Delivered',
                    'RTO',
                    'Transit',
                    'No Status'
                ],

                datasets: [{

                    data: @json($statusChart),

                    backgroundColor: [

                        '#16a34a',
                        '#ef4444',
                        '#f59e0b',
                        '#7c3aed'

                    ]

                }]

            },

            options: {
                responsive: true,
                maintainAspectRatio: false
            }

        });
    </script>

@endsection
