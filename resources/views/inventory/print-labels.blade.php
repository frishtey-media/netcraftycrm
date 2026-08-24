@extends('layouts.inventory')

@section('content')

    <style>
        .inventory-page {
            background: #f5f7fa;
            min-height: calc(100vh - 60px);
        }

        .page-header {
            background: #fff;
            border: 1px solid #e5e9ef;
            border-radius: 12px;
            padding: 18px 20px;
            margin-bottom: 15px;
        }

        .page-title {
            font-size: 21px;
            font-weight: 700;
            margin: 0;
            color: #172033;
        }

        .page-subtitle {
            color: #7b8794;
            font-size: 12px;
            margin-top: 3px;
        }

        /*
                                                |--------------------------------------------------------------------------
                                                | TOP SUMMARY
                                                |--------------------------------------------------------------------------
                                                */

        .summary-card {
            background: #fff;
            border: 1px solid #e5e9ef;
            border-radius: 10px;
            padding: 14px 16px;
            height: 100%;
        }

        .summary-title {
            font-size: 11px;
            font-weight: 700;
            color: #7b8794;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .summary-number {
            font-size: 24px;
            line-height: 1.1;
            font-weight: 800;
            color: #172033;
            margin-top: 5px;
        }

        .summary-sub {
            font-size: 11px;
            color: #8a94a6;
            margin-top: 4px;
        }

        .summary-card.india {
            border-left: 4px solid #0d6efd;
        }

        .summary-card.delivery {
            border-left: 4px solid #198754;
        }

        .summary-card.total {
            border-left: 4px solid #6f42c1;
        }

        /*
                                                |--------------------------------------------------------------------------
                                                | FILTER
                                                |--------------------------------------------------------------------------
                                                */

        .filter-card {
            background: #fff;
            border: 1px solid #e5e9ef;
            border-radius: 12px;
            margin-top: 15px;
            padding: 16px;
        }

        .filter-title {
            font-size: 15px;
            font-weight: 700;
            color: #172033;
        }

        .filter-subtitle {
            font-size: 11px;
            color: #8a94a6;
        }

        .form-label {
            font-size: 11px;
            font-weight: 700;
            color: #5d6878;
            margin-bottom: 5px;
        }

        .form-control,
        .form-select {
            height: 38px;
            border-radius: 7px;
            border-color: #dfe4ea;
            font-size: 12px;
        }

        /*
                                                |--------------------------------------------------------------------------
                                                | LABEL TYPE
                                                |--------------------------------------------------------------------------
                                                */

        .label-type-tabs {
            display: flex;
            gap: 7px;
            flex-wrap: wrap;
        }

        .label-type-tabs a {
            text-decoration: none;
            padding: 7px 14px;
            border: 1px solid #dfe4ea;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            color: #596579;
            background: #fff;
        }

        .label-type-tabs a:hover {
            border-color: #0d6efd;
            color: #0d6efd;
        }

        .label-type-tabs a.active {
            background: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        /*
                                                |--------------------------------------------------------------------------
                                                | FILTER RESULT
                                                |--------------------------------------------------------------------------
                                                */

        .result-bar {
            background: #172033;
            color: #fff;
            border-radius: 9px;
            padding: 11px 15px;
            margin-top: 15px;
        }

        .result-label {
            font-size: 10px;
            color: #aeb8c7;
            text-transform: uppercase;
        }

        .result-number {
            font-size: 18px;
            font-weight: 800;
        }

        /*
                                                |--------------------------------------------------------------------------
                                                | QUANTITY
                                                |--------------------------------------------------------------------------
                                                */

        .quantity-section {
            background: #fff;
            border: 1px solid #e5e9ef;
            border-radius: 12px;
            margin-top: 15px;
            padding: 15px;
        }

        .quantity-box {
            border: 1px solid #e1e6ed;
            border-radius: 8px;
            padding: 10px 13px;
            text-decoration: none;
            color: #172033;
            display: block;
            background: #fff;
            transition: .15s;
        }

        .quantity-box:hover,
        .quantity-box.active {
            border-color: #0d6efd;
            background: #f4f8ff;
        }

        .quantity-label {
            font-size: 10px;
            color: #8a94a6;
            text-transform: uppercase;
        }

        .quantity-value {
            font-size: 21px;
            font-weight: 800;
            color: #0d6efd;
        }

        .quantity-info {
            font-size: 11px;
            color: #667085;
        }

        /*
                                                |--------------------------------------------------------------------------
                                                | TABLE
                                                |--------------------------------------------------------------------------
                                                */

        .orders-card {
            background: #fff;
            border: 1px solid #e5e9ef;
            border-radius: 12px;
            margin-top: 15px;
            overflow: hidden;
        }

        .orders-header {
            padding: 13px 16px;
            border-bottom: 1px solid #edf0f3;
        }

        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background: #f8fafc;
            font-size: 10px;
            text-transform: uppercase;
            color: #697586;
            padding: 10px 12px;
            white-space: nowrap;
        }

        .table tbody td {
            font-size: 12px;
            padding: 10px 12px;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: #f8fbff;
        }

        .order-id {
            font-weight: 700;
            color: #172033;
        }

        .barcode {
            font-family: monospace;
            font-size: 11px;
            color: #667085;
        }

        .customer-name {
            font-weight: 600;
        }

        .customer-phone {
            color: #98a2b3;
            font-size: 10px;
        }

        .product-name {
            max-width: 260px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .qty-badge {
            background: #eef4ff;
            color: #0d6efd;
            padding: 4px 9px;
            border-radius: 5px;
            font-weight: 700;
        }

        .india-badge {
            background: #eaf2ff;
            color: #0d6efd;
            padding: 4px 7px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 700;
        }

        .delivery-badge {
            background: #e8f7ef;
            color: #198754;
            padding: 4px 7px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 700;
        }

        .pending-badge {
            background: #fff3cd;
            color: #8a6500;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 10px;
        }

        .printed-badge {
            background: #d1e7dd;
            color: #146c43;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 10px;
        }

        /*
                                                |--------------------------------------------------------------------------
                                                | PRINT BAR
                                                |--------------------------------------------------------------------------
                                                */

        .print-bar {
            background: #172033;
            color: #fff;
            border-radius: 10px;
            margin-top: 15px;
            padding: 13px 16px;
        }

        .print-small {
            color: #aeb8c7;
            font-size: 10px;
            text-transform: uppercase;
        }

        .print-value {
            font-size: 17px;
            font-weight: 800;
        }



        /* =========================================================
                   LABEL SUMMARY
                ========================================================= */

        .label-summary-wrapper {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.05);
        }


        /* HEADER */

        .summary-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 18px 22px;
            border-bottom: 1px solid #e8edf3;
            background: #ffffff;
        }

        .summary-heading h5 {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
            color: #172033;
        }

        .summary-heading h5 i {
            margin-right: 8px;
        }

        .summary-heading p {
            margin: 4px 0 0;
            font-size: 12px;
            color: #718096;
        }

        .summary-date {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            background: #f8fafc;
            padding: 7px 12px;
            border-radius: 7px;
        }


        /* TABLE */

        .label-summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .label-summary-table thead {
            background: #f8fafc;
        }

        .label-summary-table th {
            padding: 13px 20px;
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .3px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }

        .label-summary-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #edf1f5;
            vertical-align: middle;
        }

        .label-summary-table tbody tr:hover {
            background: #fafcff;
        }

        .label-summary-table tbody tr:last-child td {
            border-bottom: none;
        }


        /* LABEL TYPE */

        .label-type {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .type-icon {
            width: 38px;
            height: 38px;
            border-radius: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
        }

        .india-icon {
            background: #eff6ff;
            color: #1677ff;
        }

        .delivery-icon {
            background: #ecfdf5;
            color: #159447;
        }

        .overall-icon {
            background: #f3e8ff;
            color: #7c3aed;
        }

        .label-type strong {
            display: block;
            font-size: 14px;
            color: #172033;
        }

        .label-type small {
            display: block;
            margin-top: 3px;
            font-size: 11px;
            color: #94a3b8;
        }


        /* TOTAL */

        .total-number {
            font-size: 18px;
            font-weight: 750;
            color: #172033;
        }


        /* STATUS BADGES */

        .count-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 700;
        }

        .printed-badge {
            color: #15803d;
            background: #dcfce7;
        }

        .pending-badge {
            color: #b45309;
            background: #fef3c7;
        }


        /* PROGRESS */

        .progress-cell {
            min-width: 180px;
        }

        .progress-info {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 5px;
        }

        .progress-info span {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
        }

        .progress {
            height: 7px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            border-radius: 10px;
        }

        .printed-progress {
            background: #16a34a;
        }

        .delivery-progress {
            background: #16a34a;
        }

        .overall-progress {
            background: #7c3aed;
        }


        /* OVERALL ROW */

        .overall-row {
            background: #faf8ff;
        }

        .overall-row td {
            border-top: 1px solid #e9ddff;
        }

        .overall-number {
            font-size: 20px;
        }


        /* MOBILE */

        @media (max-width: 768px) {

            .summary-heading {
                align-items: flex-start;
                gap: 10px;
            }

            .label-summary-table {
                min-width: 700px;
            }

        }
    </style>


    <div class="inventory-page">

        <div class="container-fluid py-3">





            {{-- =========================================================
         TOP COUNTING
    ========================================================== --}}

            {{-- DAILY LABEL SUMMARY --}}
            <div class="label-summary-wrapper mb-4">

                <div class="summary-heading">
                    <div>
                        <h5>
                            <i class="bi bi-printer"></i>
                            Label Printing Summary
                        </h5>
                        <p>Today's label printing status</p>
                    </div>

                    <div class="summary-date">
                        {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                    </div>
                </div>

                <div class="table-responsive">

                    <table class="label-summary-table">

                        <thead>
                            <tr>
                                <th>Label Type</th>
                                <th>Total Orders</th>
                                <th>Printed</th>
                                <th>Pending</th>
                                <th>Progress</th>
                            </tr>
                        </thead>

                        <tbody>

                            {{-- INDIA POST --}}
                            <tr>

                                <td>
                                    <div class="label-type">

                                        <span class="type-icon india-icon">
                                            <i class="bi bi-box-seam"></i>
                                        </span>

                                        <div>
                                            <strong>India Post</strong>
                                            <small>
                                                {{ $indiaPostArticles ?? 0 }} Articles
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="total-number">
                                        {{ $indiaPostTotal ?? 0 }}
                                    </span>
                                </td>

                                <td>
                                    <span class="count-badge printed-badge">
                                        <i class="bi bi-check-circle-fill"></i>
                                        {{ $indiaPostPrinted ?? 0 }}
                                    </span>
                                </td>

                                <td>
                                    <span class="count-badge pending-badge">
                                        <i class="bi bi-clock-fill"></i>
                                        {{ $indiaPostPending ?? 0 }}
                                    </span>
                                </td>

                                <td class="progress-cell">

                                    @php
                                        $indiaTotal = $indiaPostTotal ?? 0;

                                        $indiaProgress =
                                            $indiaTotal > 0 ? round(($indiaPostPrinted / $indiaTotal) * 100) : 0;
                                    @endphp

                                    <div class="progress-info">
                                        <span>{{ $indiaProgress }}%</span>
                                    </div>

                                    <div class="progress">
                                        <div class="progress-bar printed-progress" style="width: {{ $indiaProgress }}%">
                                        </div>
                                    </div>

                                </td>

                            </tr>


                            {{-- DELIVERY / COURIER --}}
                            <tr>

                                <td>
                                    <div class="label-type">

                                        <span class="type-icon delivery-icon">
                                            <i class="bi bi-truck"></i>
                                        </span>

                                        <div>
                                            <strong>Delivery / Courier</strong>
                                            <small>
                                                {{ $deliveryArticles ?? 0 }} Articles
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="total-number">
                                        {{ $deliveryTotal ?? 0 }}
                                    </span>
                                </td>

                                <td>
                                    <span class="count-badge printed-badge">
                                        <i class="bi bi-check-circle-fill"></i>
                                        {{ $deliveryPrinted ?? 0 }}
                                    </span>
                                </td>

                                <td>
                                    <span class="count-badge pending-badge">
                                        <i class="bi bi-clock-fill"></i>
                                        {{ $deliveryPending ?? 0 }}
                                    </span>
                                </td>

                                <td class="progress-cell">

                                    @php
                                        $deliveryTotalCount = $deliveryTotal ?? 0;

                                        $deliveryProgress =
                                            $deliveryTotalCount > 0
                                                ? round(($deliveryPrinted / $deliveryTotalCount) * 100)
                                                : 0;
                                    @endphp

                                    <div class="progress-info">
                                        <span>{{ $deliveryProgress }}%</span>
                                    </div>

                                    <div class="progress">
                                        <div class="progress-bar delivery-progress"
                                            style="width: {{ $deliveryProgress }}%"></div>
                                    </div>

                                </td>

                            </tr>


                            {{-- OVERALL --}}
                            <tr class="overall-row">

                                <td>
                                    <div class="label-type">

                                        <span class="type-icon overall-icon">
                                            <i class="bi bi-bar-chart-line-fill"></i>
                                        </span>

                                        <div>
                                            <strong>Overall Total</strong>
                                            <small>
                                                {{ $overallArticles ?? 0 }} Articles
                                            </small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="total-number overall-number">
                                        {{ $overallTotal ?? 0 }}
                                    </span>
                                </td>

                                <td>
                                    <span class="count-badge printed-badge">
                                        <i class="bi bi-check-circle-fill"></i>
                                        {{ $overallPrinted ?? 0 }}
                                    </span>
                                </td>

                                <td>
                                    <span class="count-badge pending-badge">
                                        <i class="bi bi-clock-fill"></i>
                                        {{ $overallPending ?? 0 }}
                                    </span>
                                </td>

                                <td class="progress-cell">

                                    @php
                                        $overallTotalCount = $overallTotal ?? 0;

                                        $overallProgress =
                                            $overallTotalCount > 0
                                                ? round(($overallPrinted / $overallTotalCount) * 100)
                                                : 0;
                                    @endphp

                                    <div class="progress-info">
                                        <span>{{ $overallProgress }}%</span>
                                    </div>

                                    <div class="progress">
                                        <div class="progress-bar overall-progress" style="width: {{ $overallProgress }}%">
                                        </div>
                                    </div>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>


            {{-- =========================================================
         FILTER CARD
    ========================================================== --}}

            <div class="filter-card">

                <div class="d-flex justify-content-between
                    align-items-center mb-3">

                    <div>

                        <div class="filter-title">

                            <i class="bi bi-funnel me-1"></i>

                            Filter Orders

                        </div>

                        <div class="filter-subtitle">

                            Client → Product → Quantity

                        </div>

                    </div>

                </div>


                {{-- LABEL TYPE --}}

                <div class="mb-3">

                    <label class="form-label">
                        Label Type
                    </label>

                    <div class="label-type-tabs">

                        <a href="{{ route('inventory.printLabels', array_merge(request()->query(), ['label_type' => 'all'])) }}"
                            class="{{ $labelType === 'all' ? 'active' : '' }}">
                            All
                        </a>


                        <a href="{{ route('inventory.printLabels', array_merge(request()->query(), ['label_type' => 'india_post'])) }}"
                            class="{{ $labelType === 'india_post' ? 'active' : '' }}">
                            <i class="bi bi-mailbox me-1"></i>
                            India Post
                        </a>


                        <a href="{{ route('inventory.printLabels', array_merge(request()->query(), ['label_type' => 'delivery'])) }}"
                            class="{{ $labelType === 'delivery' ? 'active' : '' }}">
                            <i class="bi bi-truck me-1"></i>
                            Delivery
                        </a>

                    </div>

                </div>


                <form method="GET" action="{{ route('inventory.printLabels') }}">

                    <input type="hidden" name="label_type" value="{{ $labelType }}">


                    <div class="row g-3">

                        {{-- DATE --}}

                        <div class="col-xl-2 col-lg-3 col-md-4">

                            <label class="form-label">
                                Date
                            </label>

                            <input type="date" name="date" value="{{ $date }}" class="form-control">

                        </div>


                        {{-- CLIENT --}}

                        @if (!$isClient)
                            <div class="col-xl-2 col-lg-3 col-md-4">

                                <label class="form-label">
                                    Client
                                </label>

                                <select name="client_id" class="form-select" onchange="this.form.submit()">

                                    <option value="all">
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
                        @endif


                        {{-- PRODUCT --}}

                        <div class="col-xl-3 col-lg-4 col-md-6">

                            <label class="form-label">
                                Product
                            </label>

                            <select name="product" class="form-select">

                                <option value="all">
                                    All Products
                                </option>

                                @foreach ($products as $product)
                                    <option value="{{ $product }}"
                                        {{ request('product') == $product ? 'selected' : '' }}>

                                        {{ $product }}

                                    </option>
                                @endforeach

                            </select>

                        </div>


                        {{-- QUANTITY --}}

                        <div class="col-xl-2 col-lg-3 col-md-4">

                            <label class="form-label">
                                Quantity
                            </label>

                            <select name="quantity" class="form-select">

                                <option value="all">
                                    All Quantity
                                </option>

                                @foreach ($quantities as $quantity)
                                    <option value="{{ $quantity }}"
                                        {{ request('quantity') == $quantity ? 'selected' : '' }}>

                                        Qty {{ $quantity }}

                                    </option>
                                @endforeach

                            </select>

                        </div>


                        {{-- STATUS --}}

                        <div class="col-xl-2 col-lg-3 col-md-4">

                            <label class="form-label">
                                Status
                            </label>

                            <select name="status" class="form-select">

                                <option value="all">
                                    All Status
                                </option>

                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>
                                    Pending
                                </option>

                                <option value="printed" {{ request('status') == 'printed' ? 'selected' : '' }}>
                                    Printed
                                </option>

                            </select>

                        </div>


                        {{-- SEARCH --}}

                        <div class="col-xl-3 col-lg-4 col-md-6">

                            <label class="form-label">
                                Search
                            </label>

                            <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                                placeholder="Order / barcode / customer...">

                        </div>


                        {{-- APPLY --}}

                        <div class="col-xl-1 col-lg-2 col-md-4
                            d-flex align-items-end">

                            <button class="btn btn-primary w-100" type="submit">

                                <i class="bi bi-search"></i>

                            </button>

                        </div>

                    </div>


                    {{-- SORTING --}}

                    <div class="row g-3 mt-1">

                        <div class="col-md-3">

                            <label class="form-label">
                                Sort By
                            </label>

                            <select name="sort" class="form-select">

                                <option value="created_at">
                                    Date
                                </option>

                                <option value="product" {{ request('sort') === 'product' ? 'selected' : '' }}>
                                    Product
                                </option>

                                <option value="quantity" {{ request('sort') === 'quantity' ? 'selected' : '' }}>
                                    Quantity
                                </option>

                                <option value="pincode" {{ request('sort') === 'pincode' ? 'selected' : '' }}>
                                    Pincode
                                </option>

                                <option value="order_id" {{ request('sort') === 'order_id' ? 'selected' : '' }}>
                                    Order ID
                                </option>

                            </select>

                        </div>


                        <div class="col-md-3">

                            <label class="form-label">
                                Direction
                            </label>

                            <select name="direction" class="form-select">

                                <option value="desc" {{ request('direction', 'desc') === 'desc' ? 'selected' : '' }}>
                                    High / Z → A
                                </option>

                                <option value="asc" {{ request('direction') === 'asc' ? 'selected' : '' }}>
                                    Low / A → Z
                                </option>

                            </select>

                        </div>


                        <div class="col-md-2 d-flex align-items-end">

                            <a href="{{ route('inventory.printLabels', ['date' => $date]) }}"
                                class="btn btn-outline-secondary w-100">

                                Reset

                            </a>

                        </div>

                    </div>

                </form>

            </div>


            {{-- =========================================================
         FILTERED RESULT
    ========================================================== --}}

            <div class="result-bar">

                <div class="row align-items-center">

                    <div class="col-md-4">

                        <div class="result-label">
                            Filtered Orders
                        </div>

                        <div class="result-number">
                            {{ $filteredOrders }}
                        </div>

                    </div>


                    <div class="col-md-4">

                        <div class="result-label">
                            Filtered Articles
                        </div>

                        <div class="result-number">
                            {{ $filteredArticles }}
                        </div>

                    </div>


                    <div class="col-md-4 text-md-end">

                        @if ($labelType === 'india_post')
                            <span class="india-badge">
                                INDIA POST
                            </span>
                        @elseif($labelType === 'delivery')
                            <span class="delivery-badge">
                                DELIVERY
                            </span>
                        @else
                            <span>
                                ALL LABEL TYPES
                            </span>
                        @endif

                    </div>

                </div>

            </div>


            {{-- =========================================================
         QUANTITY SUMMARY
    ========================================================== --}}

            <div class="quantity-section">

                <div class="filter-title mb-2">

                    Packing by Quantity

                </div>

                <div class="row g-2">

                    @foreach ($quantitySummary as $summary)
                        <div class="col-xl-2 col-lg-3 col-md-4 col-6">

                            <a href="{{ route(
                                'inventory.printLabels',
                                array_merge(request()->query(), [
                                    'quantity' => $summary->quantity,
                                ]),
                            ) }}"
                                class="quantity-box
                        {{ request('quantity') == $summary->quantity ? 'active' : '' }}">

                                <div class="quantity-label">
                                    Qty
                                </div>

                                <div class="quantity-value">
                                    {{ $summary->quantity }}
                                </div>

                                <div class="quantity-info">

                                    {{ $summary->orders_count }}
                                    Orders ·
                                    {{ $summary->total_articles }}
                                    Pieces

                                </div>

                            </a>

                        </div>
                    @endforeach

                </div>

            </div>


            {{-- =========================================================
         ORDERS TABLE
    ========================================================== --}}

            <form method="POST" action="{{ route('inventory.printLabels.generate') }}" id="labelForm">

                @csrf


                <div class="orders-card">

                    <div class="orders-header">

                        <div class="d-flex justify-content-between
                            align-items-center">

                            <div>

                                <strong>
                                    Orders
                                </strong>

                                <span class="text-muted small ms-2">
                                    {{ $orders->total() }} records
                                </span>

                            </div>


                            <div class="small">

                                Selected:
                                <strong id="selectedCount">
                                    0
                                </strong>

                            </div>

                        </div>

                    </div>


                    <div class="table-responsive">

                        <table class="table table-hover">

                            <thead>

                                <tr>

                                    <th width="40">

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

                                        <td>

                                            <input type="checkbox" name="order_ids[]" value="{{ $order->id }}"
                                                class="form-check-input order-checkbox"
                                                data-qty="{{ $order->quantity ?? 0 }}">

                                        </td>


                                        {{-- TYPE --}}

                                        <td>

                                            @if (!empty($order->barcode))
                                                <span class="india-badge">

                                                    India Post

                                                </span>
                                            @else
                                                <span class="delivery-badge">

                                                    Delivery

                                                </span>
                                            @endif

                                        </td>


                                        {{-- ORDER --}}

                                        <td>

                                            <div class="order-id">
                                                {{ $order->order_id }}
                                            </div>

                                        </td>


                                        {{-- BARCODE --}}

                                        <td>

                                            @if (!empty($order->barcode))
                                                <div class="barcode">
                                                    {{ $order->barcode }}
                                                </div>
                                            @else
                                                <span class="text-muted">
                                                    Delivery
                                                </span>
                                            @endif

                                        </td>


                                        {{-- CUSTOMER --}}

                                        <td>

                                            <div class="customer-name">

                                                {{ $order->customer_name }}

                                            </div>

                                            @if ($order->customer_phone)
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

                                            {{ ltrim($order->pincode, "'") }}

                                        </td>


                                        {{-- STATUS --}}

                                        <td>

                                            @if ($order->label_status === 'printed')
                                                <span class="printed-badge">

                                                    Printed

                                                </span>
                                            @else
                                                <span class="pending-badge">

                                                    Pending

                                                </span>
                                            @endif

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="9" class="text-center py-5 text-muted">

                                            <i class="bi bi-inbox" style="font-size:30px;"></i>

                                            <div class="mt-2">

                                                No orders found
                                                for selected filters.

                                            </div>

                                        </td>

                                    </tr>
                                @endforelse

                            </tbody>

                        </table>

                    </div>


                    @if ($orders->hasPages())
                        <div class="p-3">

                            {{ $orders->links() }}

                        </div>
                    @endif

                </div>


                {{-- =====================================================
             PRINT BAR
        ====================================================== --}}

                <div class="print-bar">

                    <div class="row g-3 align-items-end">

                        <div class="col-lg-3">

                            <div class="print-small">
                                Selected Orders
                            </div>

                            <div class="print-value">

                                <span id="selectedOrders">
                                    0
                                </span>

                            </div>

                        </div>


                        <div class="col-lg-3">

                            <div class="print-small">
                                Selected Pieces
                            </div>

                            <div class="print-value">

                                <span id="selectedPieces">
                                    0
                                </span>

                            </div>

                        </div>


                        <div class="col-lg-3">

                            <label class="form-label text-white">
                                Label Sender
                            </label>

                            <select name="sender_id" id="sender_id" class="form-select">

                                <option value="">
                                    Select Sender
                                </option>

                                @foreach ($senders as $sender)
                                    <option value="{{ $sender->id }}">

                                        {{ $sender->customer_name }}

                                    </option>
                                @endforeach

                            </select>

                        </div>


                        <div class="col-lg-3">

                            <button type="submit" class="btn btn-primary w-100" id="printButton">

                                <i class="bi bi-printer me-1"></i>

                                Print Selected

                            </button>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>


    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function() {

                const selectAll =
                    document.getElementById('selectAll');

                const checkboxes =
                    document.querySelectorAll(
                        '.order-checkbox'
                    );

                const selectedCount =
                    document.getElementById(
                        'selectedCount'
                    );

                const selectedOrders =
                    document.getElementById(
                        'selectedOrders'
                    );

                const selectedPieces =
                    document.getElementById(
                        'selectedPieces'
                    );

                const form =
                    document.getElementById(
                        'labelForm'
                    );


                function updateSelection() {
                    let orders = 0;
                    let pieces = 0;


                    checkboxes.forEach(
                        function(checkbox) {

                            if (checkbox.checked) {

                                orders++;

                                pieces += parseInt(
                                    checkbox.dataset.qty || 0
                                );

                            }

                        }
                    );


                    selectedCount.textContent =
                        orders;

                    selectedOrders.textContent =
                        orders;

                    selectedPieces.textContent =
                        pieces;


                    if (selectAll) {

                        selectAll.checked =
                            orders > 0 &&
                            orders === checkboxes.length;

                    }
                }


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

                            updateSelection();

                        }
                    );
                }


                checkboxes.forEach(
                    function(checkbox) {

                        checkbox.addEventListener(
                            'change',
                            updateSelection
                        );

                    }
                );


                form.addEventListener(
                    'submit',
                    function(event) {

                        let selected = 0;

                        checkboxes.forEach(
                            function(checkbox) {

                                if (checkbox.checked) {
                                    selected++;
                                }

                            }
                        );


                        if (selected === 0) {

                            event.preventDefault();

                            alert(
                                'Please select at least one order.'
                            );

                        }

                    }
                );


                updateSelection();

            }
        );
    </script>

@endsection
