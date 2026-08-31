@extends('layouts.admin')

@section('content')
    <style>
        /* =========================================================
                                                                                                                                           STAFF PERFORMANCE REPORT
                                                                                                                                           Bootstrap Grid Based Layout
                                                                                                                                        ========================================================= */

        .staff-report {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            padding: 10px;
            background: #f4f6f8;
            overflow-x: hidden;
        }

        .staff-report *,
        .staff-report *::before,
        .staff-report *::after {
            box-sizing: border-box;
        }


        /* =========================================================
                                                                                                                                           BOOTSTRAP ROW FIX
                                                                                                                                        ========================================================= */

        .staff-report .row {
            --bs-gutter-x: 10px;
            --bs-gutter-y: 8px;
        }

        .staff-report [class*="col-"] {
            min-width: 0;
        }


        /* =========================================================
                                                                                                                                           PAGE TITLE
                                                                                                                                        ========================================================= */

        .report-title {
            font-size: 20px;
            font-weight: 700;
            color: #20252b;
            margin: 0 0 8px;
        }


        /* =========================================================
                                                                                                                                           FILTER
                                                                                                                                        ========================================================= */

        .report-filter {
            background: #fff;
            border: 1px solid #d8dde3;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 10px;
        }

        .report-filter label {
            display: block;
            margin-bottom: 3px;

            font-size: 11px;
            line-height: 14px;
            font-weight: 700;
            color: #20252b;
        }

        .report-filter .form-control,
        .report-filter .form-select {
            width: 100%;
            height: 36px;
            min-height: 36px;

            padding: 5px 9px;

            font-size: 12px;

            border: 1px solid #d5dbe1;
            border-radius: 5px;

            background: #fff;
        }

        .report-filter .form-control:focus,
        .report-filter .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 .12rem rgba(13, 110, 253, .12);
        }


        /* FILTER BUTTONS */

        .filter-buttons {
            display: flex;
            align-items: flex-end;
            gap: 5px;

            height: 100%;
            padding-top: 17px;
        }

        .filter-buttons .btn {
            height: 36px;
            padding: 5px 13px;

            font-size: 12px;
            line-height: 22px;

            white-space: nowrap;
        }


        /* =========================================================
                                                                                                                                           SECTION TITLE
                                                                                                                                        ========================================================= */

        .report-section-title {
            margin: 8px 0 5px;

            font-size: 14px;
            line-height: 18px;

            font-weight: 700;

            color: #20252b;
        }


        /* =========================================================
                                                                                                                                           STAT CARD
                                                                                                                                        ========================================================= */

        .report-stat {
            width: 100%;
            height: 70px;
            min-height: 70px;

            border: 0;
            border-radius: 6px;

            margin: 0;

            overflow: hidden;

            box-shadow: 0 1px 3px rgba(0, 0, 0, .10);
        }

        .report-stat-body {
            width: 100%;
            height: 100%;

            padding: 8px 10px;

            display: flex;
            flex-direction: column;
            justify-content: center;

            overflow: hidden;
        }

        .report-stat-title {
            font-size: 10px;
            line-height: 13px;

            font-weight: 700;

            text-transform: uppercase;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .report-stat-value {
            margin-top: 1px;

            font-size: 21px;
            line-height: 23px;

            font-weight: 700;
        }

        .report-stat-sub {
            margin-top: 1px;

            font-size: 9px;
            line-height: 11px;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }


        /* =========================================================
                                                                                                                                           COLORS
                                                                                                                                        ========================================================= */

        .stat-blue {
            background: #0d6efd;
            color: #fff;
        }

        .stat-green {
            background: #198754;
            color: #fff;
        }

        .stat-red {
            background: #dc3545;
            color: #fff;
        }

        .stat-yellow {
            background: #ffc107;
            color: #111;
        }

        .stat-dark {
            background: #343a40;
            color: #fff;
        }

        .stat-gray {
            background: #6c757d;
            color: #fff;
        }

        .stat-cyan {
            background: #0dcaf0;
            color: #111;
        }


        /* =========================================================
                                                                                                                                           PROGRESS SECTION
                                                                                                                                        ========================================================= */

        .report-panel {
            width: 100%;
            max-width: 100%;
            min-width: 0;

            background: #fff;

            border: 1px solid #d8dde3;
            border-radius: 6px;

            padding: 9px 10px;

            margin-bottom: 9px;

            overflow: hidden;
        }

        .report-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;

            margin-bottom: 5px;
        }

        .report-panel-title {
            font-size: 14px;
            line-height: 18px;

            font-weight: 700;
        }

        .report-panel-percent {
            font-size: 11px;
            font-weight: 700;
        }


        /* =========================================================
                                                                                                                                           PROGRESS BAR
                                                                                                                                        ========================================================= */

        .report-progress {
            width: 100%;
            height: 11px;

            background: #e9ecef;

            border-radius: 8px;

            overflow: hidden;

            margin-bottom: 8px;
        }

        .report-progress .progress-bar {
            height: 100%;

            border-radius: 8px;
        }


        /* =========================================================
                                                                                                                                           SMALL METRIC BOX
                                                                                                                                        ========================================================= */

        .report-metric {
            width: 100%;
            height: 56px;

            border: 1px solid #d8dde3;

            border-radius: 5px;

            background: #fff;

            display: flex;
            flex-direction: column;

            align-items: center;
            justify-content: center;

            overflow: hidden;
        }

        .report-metric-value {
            font-size: 18px;
            line-height: 20px;

            font-weight: 700;

            white-space: nowrap;
        }

        .report-metric-label {
            margin-top: 1px;

            font-size: 9px;
            line-height: 11px;

            color: #666;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;

            max-width: 100%;
        }


        /* =========================================================
                                                                                                                                           BEST PERFORMER
                                                                                                                                        ========================================================= */

        .best-performer {
            width: 100%;
            max-width: 100%;

            background: #fff;

            border: 1px solid #ffc107;
            border-left: 4px solid #ffc107;

            border-radius: 6px;

            padding: 8px 10px;

            margin-bottom: 9px;

            overflow: hidden;
        }

        .best-label {
            font-size: 9px;
            line-height: 11px;

            color: #777;

            text-transform: uppercase;
        }

        .best-name {
            font-size: 18px;
            line-height: 21px;

            font-weight: 700;
        }

        .best-client {
            font-size: 10px;
            line-height: 13px;

            color: #666;
        }

        .best-score-label {
            font-size: 9px;
            line-height: 11px;

            color: #666;

            text-transform: uppercase;
        }

        .best-score {
            font-size: 23px;
            line-height: 25px;

            font-weight: 700;

            color: #198754;
        }


        /* =========================================================
                                                                                                                                           TABLE CONTAINER
                                                                                                                                        ========================================================= */

        .report-table-card {
            width: 100%;
            max-width: 100%;
            min-width: 0;

            background: #fff;

            border: 1px solid #d8dde3;

            border-radius: 6px;

            margin-bottom: 10px;

            overflow: hidden;
        }


        /* =========================================================
                                                                                                                                           TABLE HEADER
                                                                                                                                        ========================================================= */

        .report-table-header {
            height: 35px;

            padding: 8px 10px;

            font-size: 12px;
            line-height: 15px;

            font-weight: 700;

            border-bottom: 1px solid #d8dde3;

            background: #fff;
        }


        /* =========================================================
                                                                                                                                           TABLE SCROLL
                                                                                                                                        ========================================================= */

        .report-table-scroll {
            width: 100%;
            max-width: 100%;
            min-width: 0;

            overflow-x: auto;
            overflow-y: hidden;

            -webkit-overflow-scrolling: touch;

            scrollbar-width: auto;
        }

        .report-table-scroll::-webkit-scrollbar {
            height: 10px;
        }

        .report-table-scroll::-webkit-scrollbar-track {
            background: #e9ecef;
        }

        .report-table-scroll::-webkit-scrollbar-thumb {
            background: #6c757d;
            border-radius: 6px;
        }

        .report-table-scroll::-webkit-scrollbar-thumb:hover {
            background: #495057;
        }


        /* =========================================================
                                                                                                                                           STAFF RANKING TABLE
                                                                                                                                        ========================================================= */

        .staff-ranking-table {
            width: max-content;

            min-width: 1550px;

            max-width: none;

            margin: 0;

            border-collapse: collapse;
        }

        .staff-ranking-table th {
            background: #212529;

            color: #fff;

            font-size: 10px;
            line-height: 13px;

            font-weight: 700;

            padding: 6px 7px;

            white-space: nowrap;

            border: 1px solid #454d55;

            vertical-align: middle;
        }

        .staff-ranking-table td {
            font-size: 10px;
            line-height: 13px;

            padding: 6px 7px;

            white-space: nowrap;

            border: 1px solid #dee2e6;

            vertical-align: middle;
        }


        /* =========================================================
                                                                                                                                           ORDER DETAIL TABLE
                                                                                                                                        ========================================================= */

        .order-detail-table {
            width: max-content;

            min-width: 1200px;

            max-width: none;

            margin: 0;

            border-collapse: collapse;
        }

        .order-detail-table th {
            background: #f1f3f5;

            color: #111;

            font-size: 10px;
            line-height: 13px;

            font-weight: 700;

            padding: 7px 8px;

            white-space: nowrap;

            border: 1px solid #d8dde3;
        }

        .order-detail-table td {
            font-size: 10px;
            line-height: 13px;

            padding: 7px 8px;

            white-space: nowrap;

            border: 1px solid #d8dde3;

            vertical-align: middle;
        }


        /* =========================================================
                                                                                                                                           TABLE PROGRESS
                                                                                                                                        ========================================================= */

        .table-progress {
            width: 65px;
            height: 5px;

            background: #e9ecef;

            border-radius: 5px;

            overflow: hidden;

            margin-top: 2px;
        }

        .table-progress>div {
            height: 100%;

            background: #198754;
        }


        /* =========================================================
                                                                                                                                           BADGES
                                                                                                                                        ========================================================= */

        .staff-report .badge {
            font-size: 9px;

            line-height: 11px;

            padding: 3px 6px;

            white-space: nowrap;
        }


        /* =========================================================
                                                                                                                                           MOBILE / TABLET
                                                                                                                                        ========================================================= */

        @media (max-width: 1199px) {

            .report-stat {
                height: 68px;
            }

        }


        @media (max-width: 991px) {

            .staff-report {
                padding: 8px;
            }

            .report-stat {
                height: 66px;
            }

            .report-stat-value {
                font-size: 20px;
            }

            .filter-buttons {
                padding-top: 4px;
            }

        }


        @media (max-width: 767px) {

            .staff-report {
                padding: 6px;
            }

            .report-title {
                font-size: 17px;
            }

            .report-stat {
                height: 64px;
                min-height: 64px;
            }

            .report-stat-body {
                padding: 7px 9px;
            }

            .report-stat-value {
                font-size: 19px;
                line-height: 21px;
            }

            .report-stat-title {
                font-size: 9px;
            }

            .report-stat-sub {
                font-size: 8px;
            }

            .report-table-scroll {
                overflow-x: auto !important;
            }

        }


        @media (max-width: 575px) {

            .filter-buttons {
                flex-wrap: wrap;
            }

            .filter-buttons .btn {
                flex: 0 0 auto;
            }

        }


        /* =========================================================
                                                                                                                                           PAGE LEVEL OVERFLOW PROTECTION
                                                                                                                                        ========================================================= */

        .staff-report,
        .staff-report>*,
        .staff-report .row,
        .staff-report .report-panel,
        .staff-report .best-performer,
        .staff-report .report-table-card {
            min-width: 0 !important;
            max-width: 100% !important;
        }
    </style>


    <div class="staff-report">


        {{-- =====================================================
         PAGE TITLE
    ====================================================== --}}

        <div class="report-title">
            Staff Performance Report
        </div>


        {{-- =====================================================
         FILTERS
    ====================================================== --}}

        <div class="report-filter">

            <form method="GET" action="{{ route('staff.performance.report') }}">

                <div class="row g-2">


                    {{-- CLIENT --}}

                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">

                        <label>Client</label>

                        <select name="client_id" class="form-select">

                            <option value="">
                                All Clients
                            </option>

                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}"
                                    {{ (string) request('client_id') === (string) $client->id ? 'selected' : '' }}>
                                    {{ $client->client_name ?? ($client->name ?? 'Client #' . $client->id) }}
                                </option>
                            @endforeach

                        </select>

                    </div>


                    {{-- DATE FROM --}}

                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">

                        <label>Date From</label>

                        <input type="date" name="date_from" value="{{ request('date_from', $dateFrom ?? '') }}"
                            class="form-control">

                    </div>


                    {{-- DATE TO --}}

                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">

                        <label>Date To</label>

                        <input type="date" name="date_to" value="{{ request('date_to', $dateTo ?? '') }}"
                            class="form-control">

                    </div>


                    {{-- STAFF --}}

                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">

                        <label>Staff</label>

                        <select name="staff_id" class="form-select">

                            <option value="">
                                All Staff
                            </option>

                            @foreach ($staffs as $staff)
                                <option value="{{ $staff->id }}"
                                    {{ (string) request('staff_id') === (string) $staff->id ? 'selected' : '' }}>
                                    {{ $staff->name }}
                                </option>
                            @endforeach

                        </select>

                    </div>


                    {{-- SOURCE --}}

                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">

                        <label>Source</label>

                        <select name="order_source" class="form-select">

                            <option value="">
                                All Sources
                            </option>

                            <option value="web" {{ request('order_source') === 'web' ? 'selected' : '' }}>
                                Web
                            </option>

                            <option value="whatsapp" {{ request('order_source') === 'whatsapp' ? 'selected' : '' }}>
                                WhatsApp
                            </option>

                            <option value="rto" {{ request('order_source') === 'rto' ? 'selected' : '' }}>
                                RTO
                            </option>

                            <option value="re-delivered"
                                {{ request('order_source') === 're-delivered' ? 'selected' : '' }}>
                                Re-delivered
                            </option>

                            <option value="abanded" {{ request('order_source') === 'abanded' ? 'selected' : '' }}>
                                Abandoned
                            </option>

                        </select>

                    </div>


                    {{-- CALL STATUS --}}

                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6">

                        <label>Call Status</label>

                        <select name="call_status" class="form-select">

                            <option value="">
                                All Status
                            </option>

                            @foreach ($callStatuses as $status)
                                <option value="{{ $status }}"
                                    {{ request('call_status') === $status ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $status)) }}
                                </option>
                            @endforeach

                        </select>

                    </div>


                    {{-- DELIVERY STATUS --}}

                    <div class="col-xl-3 col-lg-4 col-md-5 col-sm-6">

                        <label>Delivery Status</label>

                        <select name="delivery_status" class="form-select">

                            <option value="">
                                All Delivery Status
                            </option>

                            @foreach ($deliveryStatuses as $status)
                                <option value="{{ $status }}"
                                    {{ request('delivery_status') === $status ? 'selected' : '' }}>
                                    {{ $status }}
                                </option>
                            @endforeach

                        </select>

                    </div>


                    {{-- BUTTONS --}}

                    <div class="col-xl-5 col-lg-5 col-md-7 col-sm-6">

                        <div class="filter-buttons">

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

                </div>

            </form>

        </div>


        {{-- =====================================================
         CALLING PERFORMANCE
    ====================================================== --}}

        <div class="report-section-title">
            Calling Performance
        </div>


        <div class="row g-2">


            {{-- TOTAL --}}

            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">

                <div class="report-stat stat-blue">

                    <div class="report-stat-body">

                        <div class="report-stat-title">
                            Total Leads
                        </div>

                        <div class="report-stat-value">
                            {{ $overall['total'] ?? 0 }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- PENDING --}}

            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">

                <div class="report-stat stat-yellow">

                    <div class="report-stat-body">

                        <div class="report-stat-title">
                            Pending
                        </div>

                        <div class="report-stat-value">
                            {{ $overall['pending'] ?? 0 }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- VERIFIED --}}

            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">

                <div class="report-stat stat-green">

                    <div class="report-stat-body">

                        <div class="report-stat-title">
                            Verified
                        </div>

                        <div class="report-stat-value">
                            {{ $overall['verified'] ?? 0 }}
                        </div>

                        <div class="report-stat-sub">
                            {{ $confirmationRate ?? 0 }}% Confirm
                        </div>

                    </div>

                </div>

            </div>


            {{-- CANCEL --}}

            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">

                <div class="report-stat stat-red">

                    <div class="report-stat-body">

                        <div class="report-stat-title">
                            Cancel
                        </div>

                        <div class="report-stat-value">
                            {{ $overall['cancel'] ?? 0 }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- NOT REACHABLE --}}

            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">

                <div class="report-stat stat-dark">

                    <div class="report-stat-body">

                        <div class="report-stat-title">
                            Not Reachable
                        </div>

                        <div class="report-stat-value">
                            {{ $overall['not_reachable'] ?? 0 }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- SAME ORDER --}}

            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">

                <div class="report-stat stat-gray">

                    <div class="report-stat-body">

                        <div class="report-stat-title">
                            Same Order
                        </div>

                        <div class="report-stat-value">
                            {{ $overall['same_order'] ?? 0 }}
                        </div>

                    </div>

                </div>

            </div>
            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">

                <div class="report-stat stat-gray">

                    <div class="report-stat-body">

                        <div class="report-stat-title">
                            Other
                        </div>

                        <div class="report-stat-value">
                            {{ $overall['other'] ?? 0 }}
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
         ORDER SOURCE PERFORMANCE
    ====================================================== --}}

        <div class="report-section-title">
            Order Source Performance
        </div>


        <div class="row g-2">


            {{-- WEB --}}

            <div class="col-xl col-lg-4 col-md-4 col-sm-6">

                <div class="report-stat stat-blue">

                    <div class="report-stat-body">

                        <div class="report-stat-title">
                            Web
                        </div>

                        <div class="report-stat-value">
                            {{ $sourceStats['web'] ?? 0 }}
                        </div>

                        <div class="report-stat-sub">
                            NULL / Empty Source
                        </div>

                    </div>

                </div>

            </div>


            {{-- WHATSAPP --}}

            <div class="col-xl col-lg-4 col-md-4 col-sm-6">

                <div class="report-stat stat-green">

                    <div class="report-stat-body">

                        <div class="report-stat-title">
                            WhatsApp
                        </div>

                        <div class="report-stat-value">
                            {{ $sourceStats['whatsapp'] ?? 0 }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- RTO --}}

            <div class="col-xl col-lg-4 col-md-4 col-sm-6">

                <div class="report-stat stat-red">

                    <div class="report-stat-body">

                        <div class="report-stat-title">
                            RTO
                        </div>

                        <div class="report-stat-value">
                            {{ $sourceStats['rto'] ?? 0 }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- RE DELIVERED --}}

            <div class="col-xl col-lg-4 col-md-4 col-sm-6">

                <div class="report-stat stat-cyan">

                    <div class="report-stat-body">

                        <div class="report-stat-title">
                            Re-delivered
                        </div>

                        <div class="report-stat-value">
                            {{ $sourceStats['deliveredreorder'] ?? ($sourceStats['deliveredreorder'] ?? 0) }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- ABANDONED --}}

            <div class="col-xl col-lg-4 col-md-4 col-sm-6">

                <div class="report-stat stat-gray">

                    <div class="report-stat-body">

                        <div class="report-stat-title">
                            Abandoned
                        </div>

                        <div class="report-stat-value">
                            {{ $sourceStats['shopify_abandoned_checkout'] ?? ($sourceStats['shopify_abandoned_checkout'] ?? 0) }}
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
         LEAD CONFIRMATION
    ====================================================== --}}

        <div class="report-section-title">
            Lead Confirmation & Reachability
        </div>


        <div class="report-panel">

            <div class="report-panel-header">

                <div class="report-panel-title">
                    Lead Confirmation
                </div>

                <div class="report-panel-percent">
                    {{ $confirmationRate ?? 0 }}%
                </div>

            </div>


            <div class="report-progress">

                <div class="progress-bar bg-success" style="width: {{ min((float) ($confirmationRate ?? 0), 100) }}%;">
                </div>

            </div>


            <div class="row g-2">


                <div class="col-xl-2 col-lg-3 col-md-4 col-4">

                    <div class="report-metric">

                        <div class="report-metric-value text-success">
                            {{ $confirmationRate ?? 0 }}%
                        </div>

                        <div class="report-metric-label">
                            Confirmation
                        </div>

                    </div>

                </div>


                <div class="col-xl-2 col-lg-3 col-md-4 col-4">

                    <div class="report-metric">

                        <div class="report-metric-value">
                            {{ $reachabilityRate ?? 0 }}%
                        </div>

                        <div class="report-metric-label">
                            Reachability
                        </div>

                    </div>

                </div>


                <div class="col-xl-2 col-lg-3 col-md-4 col-4">

                    <div class="report-metric">

                        <div class="report-metric-value text-danger">
                            {{ $overall['cancel'] ?? 0 }}
                        </div>

                        <div class="report-metric-label">
                            Cancelled
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
         DELIVERY PERFORMANCE
    ====================================================== --}}

        <div class="report-section-title">
            Delivery Performance
        </div>


        <div class="row g-2">


            {{-- DELIVERED --}}

            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">

                <div class="report-stat stat-green">

                    <div class="report-stat-body">

                        <div class="report-stat-title">
                            Delivered
                        </div>

                        <div class="report-stat-value">
                            {{ $delivery['delivered'] ?? 0 }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- RTO INTRANSIT --}}

            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">

                <div class="report-stat stat-red">

                    <div class="report-stat-body">

                        <div class="report-stat-title">
                            RTO-Intrasit
                        </div>

                        <div class="report-stat-value">
                            {{ $delivery['rto_intransit'] ?? 0 }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- RTO RECEIVED --}}

            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">

                <div class="report-stat stat-red">

                    <div class="report-stat-body">

                        <div class="report-stat-title">
                            RTO Received
                        </div>

                        <div class="report-stat-value">
                            {{ $delivery['rto_received'] ?? 0 }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- CUSTOMER INTRANSIT --}}

            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">

                <div class="report-stat stat-blue">

                    <div class="report-stat-body">

                        <div class="report-stat-title">
                            Customer - Intrasit
                        </div>

                        <div class="report-stat-value">
                            {{ $delivery['customer_intransit'] ?? 0 }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- OFD --}}

            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">

                <div class="report-stat stat-yellow">

                    <div class="report-stat-body">

                        <div class="report-stat-title">
                            Out for Delivery
                        </div>

                        <div class="report-stat-value">
                            {{ $delivery['ofd'] ?? 0 }}
                        </div>

                    </div>

                </div>

            </div>


            {{-- HOLD --}}

            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6">

                <div class="report-stat stat-dark">

                    <div class="report-stat-body">

                        <div class="report-stat-title">
                            On Hold
                        </div>

                        <div class="report-stat-value">
                            {{ $delivery['on_hold'] ?? 0 }}
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
         VERIFIED -> DELIVERED
    ====================================================== --}}

        <div class="report-panel">

            <div class="report-panel-header">

                <div class="report-panel-title">
                    Verified → Delivered
                </div>

                <div class="report-panel-percent">
                    {{ $deliveryRate ?? 0 }}%
                </div>

            </div>


            <div class="report-progress">

                <div class="progress-bar bg-success" style="width: {{ min((float) ($deliveryRate ?? 0), 100) }}%;"></div>

            </div>


            <div class="row g-2">


                <div class="col-xl-2 col-lg-3 col-md-4 col-4">

                    <div class="report-metric">

                        <div class="report-metric-value text-danger">
                            {{ $rtoRate ?? 0 }}%
                        </div>

                        <div class="report-metric-label">
                            RTO Rate
                        </div>

                    </div>

                </div>


                <div class="col-xl-2 col-lg-3 col-md-4 col-4">

                    <div class="report-metric">

                        <div class="report-metric-value">
                            {{ $delivery['customer_intransit'] ?? 0 }}
                        </div>

                        <div class="report-metric-label">
                            Customer Intrasit
                        </div>

                    </div>

                </div>


                <div class="col-xl-2 col-lg-3 col-md-4 col-4">

                    <div class="report-metric">

                        <div class="report-metric-value">
                            {{ $delivery['no_status'] ?? 0 }}
                        </div>

                        <div class="report-metric-label">
                            No Status
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- =====================================================
         BEST PERFORMER
    ====================================================== --}}

        @if (!empty($bestStaff))
            <div class="best-performer">

                <div class="row g-2 align-items-center">


                    {{-- STAFF --}}

                    <div class="col-xl-3 col-lg-3 col-md-4 col-12">

                        <div class="best-label">
                            Best Performer
                        </div>

                        <div class="best-name">
                            {{ $bestStaff['staff_name'] ?? '-' }}
                        </div>

                        <div class="best-client">
                            {{ $bestStaff['client_name'] ?? '-' }}
                        </div>

                        <span class="badge bg-success">
                            {{ $bestStaff['rating'] ?? 'Good' }}
                        </span>

                    </div>


                    {{-- SCORE --}}

                    <div class="col-xl-2 col-lg-2 col-md-2 col-6">

                        <div class="best-score-label">
                            Overall Score
                        </div>

                        <div class="best-score">
                            {{ $bestStaff['score'] ?? 0 }}%
                        </div>

                    </div>


                    {{-- CONFIRMATION --}}

                    <div class="col-xl-2 col-lg-2 col-md-2 col-6">

                        <div class="report-metric">

                            <div class="report-metric-value">
                                {{ $bestStaff['confirmation_rate'] ?? 0 }}%
                            </div>

                            <div class="report-metric-label">
                                Confirmation
                            </div>

                        </div>

                    </div>


                    {{-- DELIVERY --}}

                    <div class="col-xl-2 col-lg-2 col-md-2 col-6">

                        <div class="report-metric">

                            <div class="report-metric-value">
                                {{ $bestStaff['delivery_rate'] ?? 0 }}%
                            </div>

                            <div class="report-metric-label">
                                Delivery
                            </div>

                        </div>

                    </div>


                    {{-- RTO --}}

                    <div class="col-xl-2 col-lg-2 col-md-2 col-6">

                        <div class="report-metric">

                            <div class="report-metric-value text-danger">
                                {{ $bestStaff['rto_rate'] ?? 0 }}%
                            </div>

                            <div class="report-metric-label">
                                RTO
                            </div>

                        </div>

                    </div>

                </div>

            </div>
        @endif


        {{-- =====================================================
         STAFF PERFORMANCE RANKING
    ====================================================== --}}

        <div class="report-section-title">
            Client-wise Staff Performance
        </div>


        <div class="report-table-card">


            <div class="report-table-header">

                <div class="row align-items-center">

                    <div class="col-8">
                        Staff Performance Ranking
                    </div>

                    <div class="col-4 text-end">
                        {{ count($staffReport ?? []) }} Staff
                    </div>

                </div>

            </div>


            {{-- ONLY TABLE SCROLLS --}}

            <div class="report-table-scroll">

                <table class="table table-bordered staff-ranking-table">

                    <thead>

                        <tr>

                            <th>#</th>
                            <th>Client</th>
                            <th>Staff</th>
                            <th>Leads</th>
                            <th>Web</th>
                            <th>WhatsApp</th>
                            <th>RTO Source</th>
                            <th>Re-delivered</th>
                            <th>Abandoned</th>
                            <th>Pending</th>
                            <th>Verified</th>
                            <th>Confirm %</th>
                            <th>Cancel</th>
                            <th>Cancel %</th>
                            <th>Not Reachable</th>
                            <th>other</th>
                            <th>Reach %</th>
                            <th>Delivered</th>
                            <th>Delivery %</th>
                            <th>RTO Intrasit</th>
                            <th>RTO Received</th>
                            <th>RTO %</th>
                            <th>Customer Intrasit</th>
                            <th>OFD</th>
                            <th>Hold</th>
                            <th>No Status</th>
                            <th>Score</th>
                            <th>Rating</th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($staffReport ?? [] as $index => $staff)
                            <tr>

                                <td>
                                    @if ($index == 0)
                                        🏆
                                    @endif

                                    #{{ $index + 1 }}
                                </td>


                                <td>
                                    <strong>
                                        {{ $staff['client_name'] ?? '-' }}
                                    </strong>
                                </td>


                                <td>
                                    <strong>
                                        {{ $staff['staff_name'] ?? '-' }}
                                    </strong>
                                </td>


                                <td>
                                    {{ $staff['total'] ?? 0 }}
                                </td>


                                <td>
                                    {{ $staff['web'] ?? 0 }}
                                </td>


                                <td>
                                    {{ $staff['whatsapp'] ?? 0 }}
                                </td>


                                <td>
                                    {{ $staff['rto_source'] ?? 0 }}
                                </td>


                                <td>
                                    {{ $staff['deliveredreorder'] ?? 0 }}
                                </td>


                                <td>
                                    {{ $staff['shopify_abandoned_checkout'] ?? 0 }}
                                </td>


                                <td>
                                    {{ $staff['pending'] ?? 0 }}
                                </td>


                                <td class="text-success">
                                    {{ $staff['verified'] ?? 0 }}
                                </td>


                                <td>

                                    {{ $staff['confirmation_rate'] ?? 0 }}%

                                    <div class="table-progress">

                                        <div style="width: {{ min((float) ($staff['confirmation_rate'] ?? 0), 100) }}%;">
                                        </div>

                                    </div>

                                </td>


                                <td class="text-danger">
                                    {{ $staff['cancel'] ?? 0 }}
                                </td>


                                <td>
                                    {{ $staff['cancel_rate'] ?? 0 }}%
                                </td>


                                <td>
                                    {{ $staff['not_reachable'] ?? 0 }}
                                </td>
                                <td>
                                    {{ $staff['other'] ?? 0 }}
                                </td>

                                <td>
                                    {{ $staff['reachability_rate'] ?? 0 }}%
                                </td>


                                <td class="text-success">
                                    {{ $staff['delivered'] ?? 0 }}
                                </td>


                                <td>

                                    {{ $staff['delivery_rate'] ?? 0 }}%

                                    <div class="table-progress">

                                        <div style="width: {{ min((float) ($staff['delivery_rate'] ?? 0), 100) }}%;">
                                        </div>

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
                                    {{ $staff['ofd'] ?? 0 }}
                                </td>


                                <td>
                                    {{ $staff['on_hold'] ?? 0 }}
                                </td>


                                <td>
                                    {{ $staff['no_status'] ?? 0 }}
                                </td>


                                <td>

                                    <span class="badge bg-warning text-dark">
                                        {{ $staff['score'] ?? 0 }}%
                                    </span>

                                </td>


                                <td>

                                    <span class="badge bg-info text-dark">
                                        {{ $staff['rating'] ?? '-' }}
                                    </span>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="27" class="text-center py-3">
                                    No staff performance data found.
                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>
@endsection
