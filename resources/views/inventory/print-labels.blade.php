@extends('layouts.inventory')

@section('content')

    <style>
        .label-page {
            font-size: 13px;
        }

        .summary-card,
        .filter-card,
        .packing-card,
        .orders-card {
            border: 1px solid #e1e6ef;
            border-radius: 10px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .03);
        }

        .page-title {
            font-size: 18px;
            font-weight: 600;
            color: #172033;
        }

        .page-subtitle {
            font-size: 11px;
            color: #7c8798;
        }

        .summary-table {
            margin-bottom: 0;
        }

        .summary-table th {
            font-size: 10px;
            text-transform: uppercase;
            color: #637083;
            font-weight: 600;
            background: #f8fafc;
            white-space: nowrap;
        }

        .summary-table td {
            vertical-align: middle;
        }

        .summary-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
            background: #f1f5ff;
        }

        .summary-name {
            font-weight: 600;
            color: #1e293b;
        }

        .summary-small {
            font-size: 10px;
            color: #8a94a6;
        }

        .progress {
            height: 5px;
            border-radius: 10px;
            background: #e9edf3;
        }

        .progress-bar {
            border-radius: 10px;
        }

        .filter-title {
            font-size: 14px;
            font-weight: 600;
            color: #172033;
        }

        .filter-subtitle {
            font-size: 10px;
            color: #8a94a6;
        }

        .form-label {
            font-size: 11px;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 5px;
        }

        .form-control,
        .form-select {
            font-size: 12px;
            min-height: 36px;
        }

        .label-type-btn {
            font-size: 12px;
            border-radius: 6px;
            padding: 7px 14px;
        }

        .stats-bar {
            background: #172033;
            color: #fff;
            border-radius: 8px;
        }

        .stats-label {
            font-size: 9px;
            color: #aab4c5;
            text-transform: uppercase;
        }

        .stats-value {
            font-size: 16px;
            font-weight: 700;
        }

        .packing-item {
            border: 1px solid #dce3ed;
            border-radius: 7px;
            padding: 10px 12px;
            min-width: 180px;
            background: #fff;
        }

        .packing-qty {
            font-size: 11px;
            color: #8a94a6;
            text-transform: uppercase;
        }

        .packing-number {
            font-size: 17px;
            font-weight: 700;
            color: #2563eb;
        }

        .packing-info {
            font-size: 10px;
            color: #7c8798;
        }

        .orders-header {
            padding: 13px 15px;
            border-bottom: 1px solid #e8edf4;
        }

        .orders-title {
            font-size: 15px;
            font-weight: 600;
        }

        .orders-count {
            color: #7c8798;
            font-size: 12px;
        }

        .table-orders {
            margin-bottom: 0;
        }

        .table-orders th {
            font-size: 9px;
            color: #697586;
            text-transform: uppercase;
            white-space: nowrap;
            background: #f8fafc;
            padding: 10px 8px;
        }

        .table-orders td {
            font-size: 11px;
            vertical-align: middle;
            padding: 9px 8px;
        }

        .order-id {
            font-weight: 600;
            color: #172033;
        }

        .barcode {
            font-size: 10px;
            color: #64748b;
            white-space: nowrap;
        }

        .customer-name {
            font-weight: 600;
            color: #172033;
        }

        .customer-phone {
            font-size: 10px;
            color: #7c8798;
        }

        .product-name {
            max-width: 260px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .qty-badge {
            background: #eff6ff;
            color: #2563eb;
            border-radius: 5px;
            padding: 4px 8px;
            font-weight: 600;
        }

        .status-printed {
            background: #dcfce7;
            color: #15803d;
            border-radius: 12px;
            padding: 4px 9px;
            font-size: 10px;
        }

        .status-pending {
            background: #fef3c7;
            color: #b45309;
            border-radius: 12px;
            padding: 4px 9px;
            font-size: 10px;
        }

        .type-badge {
            font-size: 9px;
            border-radius: 4px;
            padding: 4px 7px;
            white-space: nowrap;
        }

        .type-india {
            background: #eff6ff;
            color: #2563eb;
        }

        .type-delivery {
            background: #ecfdf5;
            color: #059669;
        }

        .pagination {
            margin-bottom: 0;
        }

        .pagination .page-link {
            font-size: 11px;
            padding: 5px 9px;
        }

        .selected-info {
            font-size: 11px;
            color: #64748b;
        }

        .sticky-action {
            position: sticky;
            bottom: 10px;
            z-index: 20;
            margin-top: 12px;
        }

        @media(max-width: 767px) {

            .summary-table {
                min-width: 850px;
            }

            .table-orders {
                min-width: 950px;
            }

            .filter-row>div {
                margin-bottom: 10px;
            }

            .stats-bar .col {
                margin-bottom: 10px;
            }

            .packing-item {
                min-width: 150px;
            }
        }
    </style>


    <div class="container-fluid label-page py-2">

        {{-- ========================================================= --}}
        {{-- FLASH SUCCESS --}}
        {{-- ========================================================= --}}

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}

                <button type="button" class="btn-close" data-bs-dismiss="alert">
                </button>
            </div>
        @endif


        {{-- ========================================================= --}}
        {{-- ERROR REPORT --}}
        {{-- ========================================================= --}}

        @if (session('label_error_report'))
            <div class="alert alert-danger">

                <strong>
                    Label generation failed
                </strong>

                <div class="mt-2">

                    @foreach (session('label_error_report') as $error)
                        <div class="mb-1">

                            <strong>
                                {{ $error['step'] ?? 'Error' }}
                            </strong>

                            :

                            {{ $error['message'] ?? 'Unknown error' }}

                            @if (!empty($error['order_id']))
                                <small>
                                    (Order: {{ $error['order_id'] }})
                                </small>
                            @endif

                        </div>
                    @endforeach

                </div>

            </div>
        @endif


        {{-- ========================================================= --}}
        {{-- PAGE HEADER --}}
        {{-- ========================================================= --}}

        <div class="d-flex justify-content-between align-items-center mb-2">

            <div>

                <div class="page-title">
                    <i class="bi bi-printer me-1"></i>
                    Label Printing
                </div>

                <div class="page-subtitle">
                    Today's label printing status
                </div>

            </div>

            <div class="badge bg-light text-dark border">
                {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- SUMMARY --}}
        {{-- ========================================================= --}}

        <div class="summary-card mb-3">

            <div class="table-responsive">

                <table class="table summary-table">

                    <thead>

                        <tr>

                            <th style="width:30%;">
                                Label Type
                            </th>

                            <th>
                                Total Orders
                            </th>

                            <th>
                                Printed
                            </th>

                            <th>
                                Pending
                            </th>

                            <th style="width:25%;">
                                Progress
                            </th>

                        </tr>

                    </thead>

                    <tbody>


                        {{-- INDIA POST --}}

                        @php
                            $indiaProgress =
                                $indiaPostTotal > 0 ? round(($indiaPostPrinted / $indiaPostTotal) * 100) : 0;
                        @endphp

                        <tr>

                            <td>

                                <span class="summary-icon">
                                    <i class="bi bi-box text-primary"></i>
                                </span>

                                <span class="summary-name">
                                    India Post
                                </span>

                                <div class="summary-small ms-5">
                                    {{ number_format($indiaPostArticles) }} Articles
                                </div>

                            </td>

                            <td>
                                <strong>
                                    {{ number_format($indiaPostTotal) }}
                                </strong>
                            </td>

                            <td>

                                <span class="status-printed">

                                    <i class="bi bi-check-circle-fill"></i>

                                    {{ number_format($indiaPostPrinted) }}

                                </span>

                            </td>

                            <td>

                                <span class="status-pending">

                                    <i class="bi bi-clock-fill"></i>

                                    {{ number_format($indiaPostPending) }}

                                </span>

                            </td>

                            <td>

                                <div class="d-flex justify-content-between mb-1">

                                    <small>
                                        {{ $indiaProgress }}%
                                    </small>

                                </div>

                                <div class="progress">

                                    <div class="progress-bar bg-success" style="width:{{ $indiaProgress }}%">
                                    </div>

                                </div>

                            </td>

                        </tr>


                        {{-- DELIVERY --}}

                        @php
                            $deliveryProgress =
                                $deliveryTotal > 0 ? round(($deliveryPrinted / $deliveryTotal) * 100) : 0;
                        @endphp

                        <tr>

                            <td>

                                <span class="summary-icon" style="background:#ecfdf5;">

                                    <i class="bi bi-truck text-success"></i>

                                </span>

                                <span class="summary-name">
                                    Delivery / Courier
                                </span>

                                <div class="summary-small ms-5">
                                    {{ number_format($deliveryArticles) }} Articles
                                </div>

                            </td>

                            <td>
                                <strong>
                                    {{ number_format($deliveryTotal) }}
                                </strong>
                            </td>

                            <td>

                                <span class="status-printed">

                                    <i class="bi bi-check-circle-fill"></i>

                                    {{ number_format($deliveryPrinted) }}

                                </span>

                            </td>

                            <td>

                                <span class="status-pending">

                                    <i class="bi bi-clock-fill"></i>

                                    {{ number_format($deliveryPending) }}

                                </span>

                            </td>

                            <td>

                                <div class="d-flex justify-content-between mb-1">

                                    <small>
                                        {{ $deliveryProgress }}%
                                    </small>

                                </div>

                                <div class="progress">

                                    <div class="progress-bar bg-success" style="width:{{ $deliveryProgress }}%">
                                    </div>

                                </div>

                            </td>

                        </tr>


                        {{-- OVERALL --}}

                        @php
                            $overallProgress = $overallTotal > 0 ? round(($overallPrinted / $overallTotal) * 100) : 0;
                        @endphp

                        <tr style="background:#faf8ff;">

                            <td>

                                <span class="summary-icon" style="background:#f3e8ff;">

                                    <i class="bi bi-bar-chart-fill text-purple"></i>

                                </span>

                                <span class="summary-name">
                                    Overall Total
                                </span>

                                <div class="summary-small ms-5">
                                    {{ number_format($overallArticles) }} Articles
                                </div>

                            </td>

                            <td>
                                <strong>
                                    {{ number_format($overallTotal) }}
                                </strong>
                            </td>

                            <td>

                                <span class="status-printed">

                                    <i class="bi bi-check-circle-fill"></i>

                                    {{ number_format($overallPrinted) }}

                                </span>

                            </td>

                            <td>

                                <span class="status-pending">

                                    <i class="bi bi-clock-fill"></i>

                                    {{ number_format($overallPending) }}

                                </span>

                            </td>

                            <td>

                                <div class="d-flex justify-content-between mb-1">

                                    <small>
                                        {{ $overallProgress }}%
                                    </small>

                                </div>

                                <div class="progress">

                                    <div class="progress-bar bg-primary" style="width:{{ $overallProgress }}%">
                                    </div>

                                </div>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- FILTERS --}}
        {{-- ========================================================= --}}

        <div class="filter-card p-3 mb-3">

            <div class="filter-title">

                <i class="bi bi-funnel"></i>
                Filter Orders

            </div>

            <div class="filter-subtitle mb-3">
                Client → Product → Quantity
            </div>


            <form method="GET" action="{{ route('inventory.printLabels') }}" id="filterForm">


                {{-- LABEL TYPE --}}

                <div class="mb-3">

                    <label class="form-label">
                        Label Type
                    </label>

                    <div class="d-flex gap-2 flex-wrap">

                        <a href="{{ route('inventory.printLabels', array_merge(request()->except('page'), ['label_type' => 'all'])) }}"
                            class="btn label-type-btn
                       {{ $labelType === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">

                            All

                        </a>

                        <a href="{{ route('inventory.printLabels', array_merge(request()->except('page'), ['label_type' => 'india_post'])) }}"
                            class="btn label-type-btn
                       {{ $labelType === 'india_post' ? 'btn-primary' : 'btn-outline-secondary' }}">

                            <i class="bi bi-box"></i>
                            India Post

                        </a>

                        <a href="{{ route('inventory.printLabels', array_merge(request()->except('page'), ['label_type' => 'delivery'])) }}"
                            class="btn label-type-btn
                       {{ $labelType === 'delivery' ? 'btn-primary' : 'btn-outline-secondary' }}">

                            <i class="bi bi-truck"></i>
                            Delivery

                        </a>

                    </div>

                </div>


                {{-- FIRST ROW --}}

                <div class="row filter-row">

                    {{-- DATE --}}

                    <div class="col-md-2">

                        <label class="form-label">
                            Date
                        </label>

                        <input type="date" name="date" class="form-control" value="{{ $date }}">

                    </div>


                    {{-- CLIENT --}}

                    <div class="col-md-3">

                        <label class="form-label">
                            Client
                        </label>

                        <select name="client" class="form-select" {{ $isClient ? 'disabled' : '' }}>

                            @if (!$isClient)
                                <option value="all">
                                    All Clients
                                </option>
                            @endif

                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}"
                                    {{ (string) $clientId === (string) $client->id ? 'selected' : '' }}>
                                    {{ $client->client_name ?? ($client->name ?? 'Client #' . $client->id) }}
                                </option>
                            @endforeach

                        </select>

                        @if ($isClient)
                            <input type="hidden" name="client" value="{{ $user->client_id ?? $clientId }}">
                        @endif

                    </div>


                    {{-- PRODUCT --}}

                    <div class="col-md-3">

                        <label class="form-label">
                            Product
                        </label>

                        <select name="product" class="form-select">

                            <option value="all">
                                All Products
                            </option>

                            @foreach ($products as $item)
                                <option value="{{ $item }}"
                                    {{ (string) $product === (string) $item ? 'selected' : '' }}>
                                    {{ $item }}
                                </option>
                            @endforeach

                        </select>

                    </div>


                    {{-- QUANTITY --}}

                    <div class="col-md-2">

                        <label class="form-label">
                            Quantity
                        </label>

                        <select name="quantity" class="form-select">

                            <option value="all">
                                All Quantity
                            </option>

                            @foreach ($quantities as $qty)
                                <option value="{{ $qty }}"
                                    {{ (string) $quantity === (string) $qty ? 'selected' : '' }}>
                                    {{ $qty }}
                                </option>
                            @endforeach

                        </select>

                    </div>


                    {{-- STATUS --}}

                    <div class="col-md-2">

                        <label class="form-label">
                            Status
                        </label>

                        <select name="status" class="form-select">

                            <option value="all" {{ $status === 'all' ? 'selected' : '' }}>
                                All Status
                            </option>

                            <option value="printed" {{ $status === 'printed' ? 'selected' : '' }}>
                                Printed
                            </option>

                            <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>
                                Pending
                            </option>

                        </select>

                    </div>

                </div>


                {{-- SECOND ROW --}}

                <div class="row filter-row mt-2">

                    {{-- SEARCH --}}

                    <div class="col-md-5">

                        <label class="form-label">
                            Search
                        </label>

                        <input type="text" name="search" class="form-control" value="{{ $search }}"
                            placeholder="Order / barcode / customer / phone / product / pincode">

                    </div>


                    {{-- SORT --}}

                    <div class="col-md-3">

                        <label class="form-label">
                            Sort By
                        </label>

                        <select name="sort" class="form-select">

                            <option value="created_at" {{ $sort === 'created_at' ? 'selected' : '' }}>
                                Date
                            </option>

                            <option value="order_id" {{ $sort === 'order_id' ? 'selected' : '' }}>
                                Order ID
                            </option>

                            <option value="customer_name" {{ $sort === 'customer_name' ? 'selected' : '' }}>
                                Customer
                            </option>

                            <option value="product" {{ $sort === 'product' ? 'selected' : '' }}>
                                Product
                            </option>

                            <option value="quantity" {{ $sort === 'quantity' ? 'selected' : '' }}>
                                Quantity
                            </option>

                            <option value="pincode" {{ $sort === 'pincode' ? 'selected' : '' }}>
                                Pincode
                            </option>

                        </select>

                    </div>


                    {{-- DIRECTION --}}

                    <div class="col-md-2">

                        <label class="form-label">
                            Direction
                        </label>

                        <select name="direction" class="form-select">

                            <option value="desc" {{ $direction === 'desc' ? 'selected' : '' }}>
                                High / Z → A
                            </option>

                            <option value="asc" {{ $direction === 'asc' ? 'selected' : '' }}>
                                Low / A → Z
                            </option>

                        </select>

                    </div>


                    {{-- ACTIONS --}}

                    <div class="col-md-2 d-flex align-items-end gap-2">

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i>
                            Search
                        </button>

                        <a href="{{ route('inventory.printLabels') }}" class="btn btn-outline-secondary" title="Reset">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </a>

                    </div>

                </div>


                {{-- KEEP LABEL TYPE IF USING FORM --}}

                <input type="hidden" name="label_type" value="{{ $labelType }}">

                {{-- KEEP PAGE SIZE --}}

                <input type="hidden" name="per_page" value="{{ $perPage }}" id="filterPerPage">

            </form>

        </div>


        {{-- ========================================================= --}}
        {{-- FILTERED STATS --}}
        {{-- ========================================================= --}}

        <div class="stats-bar p-3 mb-3">

            <div class="row align-items-center">

                <div class="col-md-6">

                    <div class="stats-label">
                        Filtered Orders
                    </div>

                    <div class="stats-value">
                        {{ number_format($filteredOrders) }}
                    </div>

                </div>

                <div class="col-md-6">

                    <div class="stats-label">
                        Filtered Articles
                    </div>

                    <div class="stats-value">
                        {{ number_format($filteredArticles) }}
                    </div>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- PACKING BY QUANTITY --}}
        {{-- ========================================================= --}}

        @if (isset($quantitySummary) && $quantitySummary->count())
            <div class="packing-card p-3 mb-3">

                <div class="fw-semibold mb-2">
                    Packing by Quantity
                </div>

                <div class="d-flex gap-2 flex-wrap">

                    @foreach ($quantitySummary as $summary)
                        <div class="packing-item">

                            <div class="packing-qty">
                                Qty
                            </div>

                            <div class="packing-number">
                                {{ $summary->quantity }}
                            </div>

                            <div class="packing-info">

                                {{ number_format($summary->orders_count) }}
                                Orders

                                ·

                                {{ number_format($summary->total_articles) }}
                                Pieces

                            </div>

                        </div>
                    @endforeach

                </div>

            </div>
        @endif


        {{-- ========================================================= --}}
        {{-- ORDERS --}}
        {{-- ========================================================= --}}

        <div class="orders-card">

            <div class="orders-header">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                    <div>

                        <span class="orders-title">
                            Orders
                        </span>

                        <span class="orders-count">
                            {{ number_format($orders->total()) }} records
                        </span>

                    </div>


                    <div class="d-flex align-items-center gap-3">

                        <span class="selected-info">

                            Selected:
                            <strong id="selectedCount">
                                0
                            </strong>

                        </span>


                        {{-- PER PAGE --}}

                        <div class="d-flex align-items-center gap-1">

                            <label class="small text-muted mb-0">
                                Show
                            </label>

                            <select class="form-select form-select-sm" style="width:90px;" id="perPageSelect"
                                onchange="changePerPage(this.value)">

                                @foreach ([50, 100, 250, 500, 1000] as $size)
                                    <option value="{{ $size }}" {{ (int) $perPage === $size ? 'selected' : '' }}>
                                        {{ $size }}
                                    </option>
                                @endforeach

                            </select>

                        </div>

                    </div>

                </div>

            </div>


            {{-- ===================================================== --}}
            {{-- GENERATE LABEL FORM --}}
            {{-- ===================================================== --}}

            <form method="POST" action="{{ route('inventory.printLabels.generate') }}" id="generateLabelsForm">

                @csrf


                <div class="table-responsive">

                    <table class="table table-hover table-orders">

                        <thead>

                            <tr>

                                <th width="35">

                                    <input type="checkbox" id="selectAll" class="form-check-input">

                                </th>

                                <th>
                                    Type
                                </th>

                                <th>
                                    Order ID
                                </th>

                                <th>
                                    Barcode / Tracking
                                </th>

                                <th>
                                    Customer
                                </th>

                                <th>
                                    Product
                                </th>

                                <th>
                                    Qty
                                </th>

                                <th>
                                    Pincode
                                </th>

                                <th>
                                    Status
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($orders as $order)
                                <tr>

                                    {{-- CHECKBOX --}}

                                    <td>

                                        <input type="checkbox" name="order_ids[]" value="{{ $order->id }}"
                                            class="form-check-input order-checkbox">

                                    </td>


                                    {{-- TYPE --}}

                                    <td>

                                        @if (!empty($order->barcode))
                                            <span class="type-badge type-india">
                                                India Post
                                            </span>
                                        @else
                                            <span class="type-badge type-delivery">
                                                Delivery
                                            </span>
                                        @endif

                                    </td>


                                    {{-- ORDER ID --}}

                                    <td>

                                        <span class="order-id">
                                            {{ $order->order_id }}
                                        </span>

                                    </td>


                                    {{-- BARCODE --}}

                                    <td>

                                        @if (!empty($order->barcode))
                                            <span class="barcode">
                                                {{ $order->barcode }}
                                            </span>
                                        @else
                                            <span class="text-muted">
                                                —
                                            </span>
                                        @endif

                                    </td>


                                    {{-- CUSTOMER --}}

                                    <td>

                                        <div class="customer-name">
                                            {{ $order->customer_name }}
                                        </div>

                                        @if (!empty($order->customer_phone))
                                            <div class="customer-phone">
                                                {{ $order->customer_phone }}
                                            </div>
                                        @endif

                                    </td>


                                    {{-- PRODUCT --}}

                                    <td>

                                        <div class="product-name" title="{{ $order->product }}">
                                            {{ $order->product }}
                                        </div>

                                    </td>


                                    {{-- QTY --}}

                                    <td>

                                        <span class="qty-badge">
                                            {{ $order->quantity }}
                                        </span>

                                    </td>


                                    {{-- PINCODE --}}

                                    <td>
                                        {{ $order->pincode }}
                                    </td>


                                    {{-- STATUS --}}

                                    <td>

                                        @if ($order->label_status === 'printed')
                                            <span class="status-printed">

                                                <i class="bi bi-check-circle-fill"></i>
                                                Printed

                                            </span>
                                        @else
                                            <span class="status-pending">

                                                <i class="bi bi-clock-fill"></i>
                                                Pending

                                            </span>
                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="9" class="text-center py-5">

                                        <div class="text-muted">

                                            <i class="bi bi-inbox" style="font-size:35px;"></i>

                                            <div class="fw-semibold mt-2">
                                                No orders found
                                            </div>

                                            <small>
                                                Try changing your filters.
                                            </small>

                                        </div>

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>


                {{-- ================================================= --}}
                {{-- GENERATE ACTION --}}
                {{-- ================================================= --}}

                @if ($orders->count())
                    <div class="sticky-action">

                        <div class="card border shadow-sm">

                            <div class="card-body py-2">

                                <div class="row align-items-end">

                                    {{-- SENDER --}}

                                    <div class="col-md-5">

                                        <label class="form-label mb-1">
                                            Label Sender
                                        </label>

                                        <select name="sender_id" id="sender_id" class="form-select" required>

                                            <option value="">
                                                Select Sender
                                            </option>

                                            @foreach ($senders as $sender)
                                                <option value="{{ $sender->id }}">

                                                    {{ $sender->customer_name }}

                                                    @if (!empty($sender->mobile))
                                                        - {{ $sender->mobile }}
                                                    @endif

                                                </option>
                                            @endforeach

                                        </select>

                                    </div>


                                    {{-- SELECTED --}}

                                    <div class="col-md-3">

                                        <div class="small text-muted">
                                            Selected Orders
                                        </div>

                                        <div class="fw-bold">
                                            <span id="selectedCountBottom">
                                                0
                                            </span>
                                        </div>

                                    </div>


                                    {{-- BUTTON --}}

                                    <div class="col-md-4">

                                        <button type="submit" class="btn btn-primary w-100" id="generateBtn" disabled>

                                            <i class="bi bi-printer"></i>

                                            Generate Labels

                                        </button>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>
                @endif

            </form>


            {{-- ================================================= --}}
            {{-- PAGINATION --}}
            {{-- ================================================= --}}

            <div class="px-3 py-3">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                    <div class="small text-muted">

                        @if ($orders->total() > 0)
                            Showing
                            <strong>{{ $orders->firstItem() }}</strong>
                            -
                            <strong>{{ $orders->lastItem() }}</strong>

                            of

                            <strong>{{ number_format($orders->total()) }}</strong>

                            orders
                        @else
                            Showing 0 orders
                        @endif

                    </div>


                    <div>

                        {{ $orders->links() }}

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ============================================================= --}}
    {{-- JAVASCRIPT --}}
    {{-- ============================================================= --}}

    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function() {

                const selectAll =
                    document.getElementById('selectAll');

                const checkboxes =
                    document.querySelectorAll('.order-checkbox');

                const selectedCount =
                    document.getElementById('selectedCount');

                const selectedCountBottom =
                    document.getElementById('selectedCountBottom');

                const generateBtn =
                    document.getElementById('generateBtn');


                /*
                |--------------------------------------------------------------------------
                | UPDATE SELECTED COUNT
                |--------------------------------------------------------------------------
                */

                function updateSelectedCount() {
                    const checked =
                        document.querySelectorAll(
                            '.order-checkbox:checked'
                        ).length;


                    if (selectedCount) {

                        selectedCount.innerText =
                            checked;
                    }


                    if (selectedCountBottom) {

                        selectedCountBottom.innerText =
                            checked;
                    }


                    if (generateBtn) {

                        generateBtn.disabled =
                            checked === 0;
                    }


                    if (selectAll) {

                        if (
                            checkboxes.length > 0 &&
                            checked === checkboxes.length
                        ) {

                            selectAll.checked = true;

                        } else {

                            selectAll.checked = false;
                        }
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | SELECT ALL
                |--------------------------------------------------------------------------
                */

                if (selectAll) {

                    selectAll.addEventListener(
                        'change',
                        function() {

                            checkboxes.forEach(
                                function(checkbox) {

                                    checkbox.checked =
                                        selectAll.checked;

                                }
                            );

                            updateSelectedCount();

                        }
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | INDIVIDUAL CHECKBOX
                |--------------------------------------------------------------------------
                */

                checkboxes.forEach(
                    function(checkbox) {

                        checkbox.addEventListener(
                            'change',
                            updateSelectedCount
                        );

                    }
                );


                /*
                |--------------------------------------------------------------------------
                | GENERATE VALIDATION
                |--------------------------------------------------------------------------
                */

                const form =
                    document.getElementById(
                        'generateLabelsForm'
                    );


                if (form) {

                    form.addEventListener(
                        'submit',
                        function(event) {

                            const selected =
                                document.querySelectorAll(
                                    '.order-checkbox:checked'
                                ).length;


                            const sender =
                                document.getElementById(
                                    'sender_id'
                                );


                            if (selected === 0) {

                                event.preventDefault();

                                alert(
                                    'Please select at least one order.'
                                );

                                return;
                            }


                            if (
                                sender &&
                                !sender.value
                            ) {

                                event.preventDefault();

                                alert(
                                    'Please select a label sender.'
                                );

                                sender.focus();

                                return;
                            }


                            const confirmed =
                                confirm(
                                    'Generate labels for ' +
                                    selected +
                                    ' selected order(s)?'
                                );


                            if (!confirmed) {

                                event.preventDefault();
                            }

                        }
                    );
                }


                updateSelectedCount();

            }
        );

        function changePerPage(value) {
            const url =
                new URL(
                    window.location.href
                );
            url.searchParams.set(
                'per_page',
                value
            );
            url.searchParams.delete(
                'page'
            );
            window.location.href =
                url.toString();
        }
    </script>

@endsection
