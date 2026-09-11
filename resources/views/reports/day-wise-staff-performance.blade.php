@extends('layouts.admin')

@section('content')

    <style>
        .dsp-page {
            padding-bottom: 40px;
        }

        .dsp-page * {
            box-sizing: border-box;
        }

        .dsp-hero {
            position: relative;
            overflow: hidden;
            border-radius: 18px;
            padding: 28px 30px;
            margin-bottom: 22px;
            background: linear-gradient(135deg, #111827 0%, #1e3a8a 55%, #2563eb 100%);
            color: #fff;
            box-shadow: 0 12px 35px rgba(30, 58, 138, .20);
        }

        .dsp-hero:before {
            content: "";
            position: absolute;
            width: 230px;
            height: 230px;
            border-radius: 50%;
            right: -70px;
            top: -100px;
            background: rgba(255, 255, 255, .08);
        }

        .dsp-hero:after {
            content: "";
            position: absolute;
            width: 160px;
            height: 160px;
            border-radius: 50%;
            right: 120px;
            bottom: -110px;
            background: rgba(255, 255, 255, .05);
        }

        .dsp-hero-content {
            position: relative;
            z-index: 2;
        }

        .dsp-hero-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            background: rgba(255, 255, 255, .14);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-right: 14px;
            vertical-align: middle;
        }

        .dsp-hero h2 {
            display: inline-block;
            margin: 0;
            font-size: 25px;
            font-weight: 800;
            vertical-align: middle;
        }

        .dsp-hero p {

            color: rgba(255, 255, 255, .78);
            font-size: 13px;
        }



        .dsp-filter-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 7px 25px rgba(15, 23, 42, .06);
        }

        .dsp-section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 17px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 20px;
        }

        .dsp-section-title-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #2563eb;
        }

        .dsp-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 7px;
        }

        .dsp-required {
            color: #ef4444;
        }

        .dsp-input,
        .dsp-select {
            width: 100%;
            height: 45px;
            border: 1px solid #dbe1ea;
            border-radius: 10px;
            background: #fff;
            color: #1f2937;
            padding: 0 13px;
            font-size: 13px;
            outline: none;
            transition: all .2s ease;
        }

        .dsp-input:focus,
        .dsp-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, .10);
        }

        .dsp-staff-select {
            height: 230px !important;
            padding: 7px !important;
            cursor: pointer;
        }

        .dsp-staff-select option {
            padding: 8px 10px;
            border-radius: 7px;
        }

        .dsp-help {
            display: block;
            margin-top: 6px;
            color: #9ca3af;
            font-size: 11px;
        }

        .dsp-help-danger {
            color: #ef4444;
        }


        /* =========================================================
                                                                                                                                           BUTTONS
                                                                                                                                        ========================================================= */

        .dsp-btn {
            height: 45px;
            border-radius: 10px;
            border: 0;
            font-size: 13px;
            font-weight: 700;
            transition: all .2s ease;
        }

        .dsp-btn-primary {
            color: #fff;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            box-shadow: 0 5px 14px rgba(37, 99, 235, .22);
        }

        .dsp-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(37, 99, 235, .28);
        }

        .dsp-btn-primary:disabled {
            opacity: .55;
            transform: none;
            cursor: not-allowed;
        }

        .dsp-btn-reset {
            background: #fff;
            color: #374151;
            border: 1px solid #dbe1ea;
        }

        .dsp-btn-reset:hover {
            background: #f9fafb;
        }


        /* =========================================================
                                                                                                                                           SELECTED FILTERS
                                                                                                                                        ========================================================= */

        .dsp-selected-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 22px;
        }

        .dsp-filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 12px;
            border-radius: 30px;
            background: #fff;
            border: 1px solid #e5e7eb;
            color: #374151;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 3px 10px rgba(15, 23, 42, .04);
        }

        .dsp-filter-chip i {
            color: #2563eb;
        }


        /* =========================================================
                                                                                                                                           SUMMARY
                                                                                                                                        ========================================================= */

        .dsp-summary-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 13px;
        }

        .dsp-summary-heading h5 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: #111827;
        }

        .dsp-summary-date {
            color: #6b7280;
            font-size: 12px;
            font-weight: 500;
        }

        .dsp-summary-card {
            position: relative;
            overflow: hidden;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            min-height: 135px;
            padding: 19px;
            box-shadow: 0 7px 22px rgba(15, 23, 42, .05);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .dsp-summary-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(15, 23, 42, .09);
        }

        .dsp-summary-card:after {
            content: "";
            position: absolute;
            width: 90px;
            height: 90px;
            border-radius: 50%;
            right: -35px;
            bottom: -40px;
            background: rgba(37, 99, 235, .05);
        }

        .dsp-summary-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dsp-summary-label {
            color: #6b7280;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .6px;
        }

        .dsp-summary-icon {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
        }

        .dsp-icon-leads {
            background: #eef2ff;
            color: #4f46e5;
        }

        .dsp-icon-confirm {
            background: #ecfdf5;
            color: #059669;
        }

        .dsp-icon-delivery {
            background: #eff6ff;
            color: #2563eb;
        }

        .dsp-icon-rto {
            background: #fff1f2;
            color: #e11d48;
        }

        .dsp-summary-value {
            position: relative;
            z-index: 2;
            margin-top: 10px;
            color: #111827;
            font-size: 29px;
            font-weight: 800;
            line-height: 1;
        }

        .dsp-summary-value.confirm {
            color: #059669;
        }

        .dsp-summary-value.delivery {
            color: #2563eb;
        }

        .dsp-summary-value.rto {
            color: #e11d48;
        }

        .dsp-summary-rate {
            position: relative;
            z-index: 2;
            margin-top: 9px;
            font-size: 11px;
            color: #6b7280;
            font-weight: 600;
        }

        .dsp-progress {
            position: relative;
            z-index: 2;
            height: 6px;
            background: #eef0f3;
            border-radius: 20px;
            overflow: hidden;
            margin-top: 7px;
        }

        .dsp-progress-bar {
            height: 100%;
            border-radius: 20px;
        }

        .dsp-progress-green {
            background: linear-gradient(90deg, #059669, #34d399);
        }

        .dsp-progress-blue {
            background: linear-gradient(90deg, #2563eb, #60a5fa);
        }

        .dsp-progress-red {
            background: linear-gradient(90deg, #e11d48, #fb7185);
        }


        /* =========================================================
                                                                                                                                           REPORT CARD
                                                                                                                                        ========================================================= */

        .dsp-report-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 7px 25px rgba(15, 23, 42, .06);
            margin-top: 24px;
        }

        .dsp-report-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .dsp-report-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #111827;
            font-size: 17px;
            font-weight: 800;
        }

        .dsp-report-title-icon {
            width: 35px;
            height: 35px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            color: #2563eb;
        }

        .dsp-staff-count {
            padding: 6px 11px;
            background: #f3f4f6;
            color: #4b5563;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }


        /* =========================================================
                                                                                                                                           STAFF CARD
                                                                                                                                        ========================================================= */

        .dsp-staff-card {
            border: 1px solid #e5e7eb;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 18px;
            background: #fff;
            transition: box-shadow .2s ease;
        }

        .dsp-staff-card:hover {
            box-shadow: 0 8px 25px rgba(15, 23, 42, .07);
        }

        .dsp-staff-head {
            padding: 16px 18px;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-bottom: 1px solid #e5e7eb;
        }

        .dsp-staff-head-main {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .dsp-staff-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .dsp-staff-avatar {
            width: 43px;
            height: 43px;
            min-width: 43px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 16px;
            box-shadow: 0 5px 12px rgba(37, 99, 235, .20);
        }

        .dsp-staff-name {
            color: #111827;
            font-size: 15px;
            font-weight: 800;
        }

        .dsp-staff-subtitle {
            margin-top: 3px;
            color: #9ca3af;
            font-size: 10px;
        }

        .dsp-staff-stats {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 7px;
        }

        .dsp-stat-pill {
            padding: 7px 10px;
            border-radius: 8px;
            background: #fff;
            border: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 10px;
            font-weight: 600;
        }

        .dsp-stat-pill strong {
            color: #111827;
            font-size: 11px;
        }

        .dsp-stat-pill.confirm strong {
            color: #059669;
        }

        .dsp-stat-pill.delivery strong {
            color: #2563eb;
        }

        .dsp-stat-pill.rto strong {
            color: #e11d48;
        }


        /* =========================================================
                                                                                                                                           TABLE
                                                                                                                                        ========================================================= */

        .dsp-table-wrap {
            overflow-x: auto;
        }

        .dsp-table {
            width: 100%;
            margin: 0;
            border-collapse: separate;
            border-spacing: 0;
            white-space: nowrap;
        }

        .dsp-table thead th {
            position: sticky;
            top: 0;
            z-index: 3;
            background: #fafafa;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 14px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .35px;
        }

        .dsp-table tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f3f5;
            color: #374151;
            font-size: 12px;
            vertical-align: middle;
        }

        .dsp-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .dsp-table tbody tr:hover td {
            background: #fafcff;
        }

        .dsp-date {
            color: #111827;
            font-weight: 700;
        }

        .dsp-date-day {
            display: block;
            color: #9ca3af;
            font-size: 9px;
            font-weight: 500;
            margin-top: 2px;
        }

        .dsp-number {
            display: inline-flex;
            min-width: 42px;
            justify-content: center;
            align-items: center;
            padding: 5px 9px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 11px;
        }

        .dsp-number-leads {
            color: #4338ca;
            background: #eef2ff;
        }

        .dsp-number-confirm {
            color: #047857;
            background: #ecfdf5;
        }

        .dsp-number-delivery {
            color: #1d4ed8;
            background: #eff6ff;
        }

        .dsp-number-rto {
            color: #be123c;
            background: #fff1f2;
        }

        .dsp-percent {
            min-width: 90px;
        }

        .dsp-percent-value {
            display: block;
            color: #374151;
            font-weight: 800;
            font-size: 11px;
        }

        .dsp-mini-progress {
            width: 90px;
            height: 5px;
            border-radius: 20px;
            overflow: hidden;
            background: #edf0f3;
            margin-top: 5px;
        }

        .dsp-mini-bar {
            height: 100%;
            border-radius: 20px;
        }

        .dsp-total-row td {
            background: #fafafa !important;
            border-top: 1px solid #e5e7eb;
            border-bottom: 0 !important;
            color: #111827 !important;
            font-weight: 800 !important;
        }

        .dsp-total-label {
            color: #111827;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .3px;
        }


        /* =========================================================
                                                                                                                                           EMPTY STATES
                                                                                                                                        ========================================================= */

        .dsp-empty {
            padding: 55px 20px;
            text-align: center;
        }

        .dsp-empty-icon {
            width: 62px;
            height: 62px;
            margin: 0 auto 15px;
            border-radius: 18px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
        }

        .dsp-empty-title {
            color: #111827;
            font-size: 15px;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .dsp-empty-text {
            color: #9ca3af;
            font-size: 12px;
        }


        /* =========================================================
                                                                                                                                           RESPONSIVE
                                                                                                                                        ========================================================= */

        @media (max-width: 991px) {

            .dsp-staff-head-main {
                align-items: flex-start;
                flex-direction: column;
            }

            .dsp-staff-stats {
                justify-content: flex-start;
            }

        }


        @media (max-width: 767px) {

            .dsp-page {
                padding: 0 5px 30px;
            }

            .dsp-hero {
                padding: 15px 23px;
                border-radius: 14px;
            }

            .dsp-hero h2 {
                font-size: 20px;
            }

            .dsp-hero p {
                margin-left: 0;
            }

            .dsp-filter-card,
            .dsp-report-card {
                padding: 17px;
                border-radius: 14px;
            }

            .dsp-summary-card {
                min-height: 120px;
            }

            .dsp-staff-head {
                padding: 14px;
            }

            .dsp-staff-stats {
                width: 100%;
            }

            .dsp-stat-pill {
                flex: 1;
            }

        }
    </style>


    <div class="container-fluid dsp-page">


        {{-- =========================================================
         HERO HEADER
    ========================================================== --}}

        <div class="dsp-hero">

            <div class="dsp-hero-content">

                <div>
                    <h2>
                        Day-Wise Staff Performance
                    </h2>

                </div>

                <p>
                    Track staff-wise leads, confirmations, deliveries and RTO performance.
                </p>

            </div>

        </div>

        {{-- =========================================================
         FILTER
    ========================================================== --}}

        <div class="dsp-filter-card">

            <div class="dsp-section-title">



                Report Filters

            </div>


            <form method="GET" action="{{ route('admin.day-wise-staff-performance') }}" id="performanceFilterForm">

                <div class="row">

                    {{-- CLIENT --}}

                    <div class="col-lg-3 col-md-6 mb-3">

                        <label class="dsp-label">

                            <i class="fas fa-building mr-1"></i>

                            Client

                            <span class="dsp-required">*</span>

                        </label>


                        @if (auth()->user()->isClient ?? false)
                            <select class="dsp-select" disabled>

                                @foreach ($clients as $client)
                                    <option selected>
                                        {{ $client->client_name }}
                                    </option>
                                @endforeach

                            </select>


                            {{-- IMPORTANT:
             Disabled fields are not submitted.
             Send client_id through hidden input.
        --}}

                            <input type="hidden" name="client_id" value="{{ $clientId }}">
                        @else
                            <select name="client_id" id="client_id" class="dsp-select" required
                                onchange="loadStaffForClient()">

                                <option value="">
                                    Select Client
                                </option>

                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}"
                                        {{ (int) $clientId === (int) $client->id ? 'selected' : '' }}>
                                        {{ $client->client_name }}
                                    </option>
                                @endforeach

                            </select>
                        @endif

                    </div>


                    {{-- FROM DATE --}}

                    <div class="col-lg-2 col-md-6 mb-3">

                        <label class="dsp-label">

                            <i class="far fa-calendar-alt mr-1"></i>

                            From Date

                        </label>

                        <input type="date" name="date_from" class="dsp-input" value="{{ $dateFrom }}">

                    </div>


                    {{-- TO DATE --}}

                    <div class="col-lg-2 col-md-6 mb-3">

                        <label class="dsp-label">

                            <i class="far fa-calendar-alt mr-1"></i>

                            To Date

                        </label>

                        <input type="date" name="date_to" class="dsp-input" value="{{ $dateTo }}">

                    </div>


                    {{-- STAFF --}}

                    <div class="col-lg-3 col-md-6 mb-3">

                        <label class="dsp-label">

                            <i class="fas fa-users mr-1"></i>

                            Staff

                            <span class="dsp-required">*</span>

                        </label>

                        <select name="staff_id[]" id="staff_id" class="dsp-select dsp-staff-select" multiple size="10"
                            {{ !$clientId || $staffs->isEmpty() ? 'disabled' : '' }}>

                            @if (!$clientId)
                                <option disabled>
                                    Select Client first
                                </option>
                            @elseif($staffs->isEmpty())
                                <option disabled>
                                    No staff found for this client
                                </option>
                            @else
                                @foreach ($staffs as $staff)
                                    <option value="{{ $staff->id }}"
                                        {{ in_array((int) $staff->id, $selectedStaffIds ?? [], true) ? 'selected' : '' }}>
                                        {{ $staff->name }}
                                    </option>
                                @endforeach
                            @endif

                        </select>


                        @if ($clientId)
                            <small class="dsp-help">

                                <i class="fas fa-info-circle mr-1"></i>

                                Ctrl + Click to select multiple staff.

                            </small>
                        @else
                            <small class="dsp-help dsp-help-danger">

                                Select client first.

                            </small>
                        @endif

                    </div>


                    {{-- BUTTONS --}}

                    <div class="col-lg-2 col-md-6 mb-3">

                        <label class="dsp-label">
                            &nbsp;
                        </label>


                        <button type="submit" class="btn dsp-btn dsp-btn-primary btn-block mb-2"
                            {{ !$clientId || $staffs->isEmpty() ? 'disabled' : '' }}>

                            <i class="fas fa-search mr-1"></i>

                            Apply Filter

                        </button>


                        <a href="{{ route('admin.day-wise-staff-performance') }}"
                            class="btn dsp-btn dsp-btn-reset btn-block">

                            <i class="fas fa-redo mr-1"></i>

                            Reset

                        </a>

                    </div>

                </div>

            </form>

        </div>


        {{-- =========================================================
         SELECTED FILTER CHIPS
    ========================================================== --}}

        @if ($clientId)
            @php

                $selectedClient = $clients->firstWhere('id', $clientId);

            @endphp


            <div class="dsp-selected-filters">

                <div class="dsp-filter-chip">

                    <i class="fas fa-building"></i>

                    <span>
                        {{ $selectedClient->client_name ?? 'Client' }}
                    </span>

                </div>


                <div class="dsp-filter-chip">

                    <i class="far fa-calendar-alt"></i>

                    <span>
                        {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}
                        -
                        {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}
                    </span>

                </div>


                @if (!empty($selectedStaffIds))
                    <div class="dsp-filter-chip">

                        <i class="fas fa-users"></i>

                        <span>
                            {{ count($selectedStaffIds) }} Staff Selected
                        </span>

                    </div>
                @endif

            </div>
        @endif


        {{-- =========================================================
         SUMMARY
    ========================================================== --}}

        @if ($clientId && !empty($selectedStaffIds))
            <div class="dsp-summary-heading">

                <div>

                    <h5>
                        Performance Summary
                    </h5>

                    <span class="dsp-summary-date">

                        {{ \Carbon\Carbon::parse($dateFrom)->format('d M Y') }}

                        &nbsp;—&nbsp;

                        {{ \Carbon\Carbon::parse($dateTo)->format('d M Y') }}

                    </span>

                </div>

            </div>


            <div class="row">


                {{-- TOTAL LEADS --}}

                <div class="col-xl-3 col-md-6 mb-3">

                    <div class="dsp-summary-card">

                        <div class="dsp-summary-top">

                            <div class="dsp-summary-label">
                                Total Leads
                            </div>

                            <div class="dsp-summary-icon dsp-icon-leads">

                                <i class="fas fa-users"></i>

                            </div>

                        </div>

                        <div class="dsp-summary-value">

                            {{ number_format($summary['total_leads']) }}

                        </div>

                        <div class="dsp-summary-rate">
                            Selected staff total
                        </div>

                    </div>

                </div>


                {{-- CONFIRM --}}

                <div class="col-xl-3 col-md-6 mb-3">

                    <div class="dsp-summary-card">

                        <div class="dsp-summary-top">

                            <div class="dsp-summary-label">
                                Confirm
                            </div>

                            <div class="dsp-summary-icon dsp-icon-confirm">

                                <i class="fas fa-check-circle"></i>

                            </div>

                        </div>

                        <div class="dsp-summary-value confirm">

                            {{ number_format($summary['confirm']) }}

                        </div>

                        <div class="dsp-summary-rate">

                            {{ number_format($summary['confirm_percent'], 2) }}%

                        </div>

                        <div class="dsp-progress">

                            <div class="dsp-progress-bar dsp-progress-green"
                                style="width: {{ min(100, max(0, $summary['confirm_percent'])) }}%;"></div>

                        </div>

                    </div>

                </div>


                {{-- DELIVERY --}}

                <div class="col-xl-3 col-md-6 mb-3">

                    <div class="dsp-summary-card">

                        <div class="dsp-summary-top">

                            <div class="dsp-summary-label">
                                Delivery
                            </div>

                            <div class="dsp-summary-icon dsp-icon-delivery">

                                <i class="fas fa-truck"></i>

                            </div>

                        </div>

                        <div class="dsp-summary-value delivery">

                            {{ number_format($summary['delivery']) }}

                        </div>

                        <div class="dsp-summary-rate">

                            {{ number_format($summary['delivery_percent'], 2) }}%

                        </div>

                        <div class="dsp-progress">

                            <div class="dsp-progress-bar dsp-progress-blue"
                                style="width: {{ min(100, max(0, $summary['delivery_percent'])) }}%;"></div>

                        </div>

                    </div>

                </div>


                {{-- RTO --}}

                <div class="col-xl-3 col-md-6 mb-3">

                    <div class="dsp-summary-card">

                        <div class="dsp-summary-top">

                            <div class="dsp-summary-label">
                                RTO
                            </div>

                            <div class="dsp-summary-icon dsp-icon-rto">

                                <i class="fas fa-undo-alt"></i>

                            </div>

                        </div>

                        <div class="dsp-summary-value rto">

                            {{ number_format($summary['rto']) }}

                        </div>

                        <div class="dsp-summary-rate">

                            {{ number_format($summary['rto_percent'], 2) }}%

                        </div>

                        <div class="dsp-progress">

                            <div class="dsp-progress-bar dsp-progress-red"
                                style="width: {{ min(100, max(0, $summary['rto_percent'])) }}%;"></div>

                        </div>

                    </div>

                </div>

            </div>
        @endif


        {{-- =========================================================
         REPORT
    ========================================================== --}}

        @if ($clientId && !empty($selectedStaffIds))
            <div class="dsp-report-card">


                <div class="dsp-report-heading">

                    <div class="dsp-report-title">

                        <span class="dsp-report-title-icon">

                            <i class="fas fa-chart-bar"></i>

                        </span>

                        Staff Performance

                    </div>


                    <span class="dsp-staff-count">

                        {{ $staffWiseReport->count() }}

                        Staff

                    </span>

                </div>


                {{-- =====================================================
                 STAFF SECTIONS
            ====================================================== --}}

                @forelse($staffWiseReport as $staffReport)
                    @php

                        $staffName = trim($staffReport['staff_name'] ?? '');

                        $staffInitial = strtoupper(substr($staffName, 0, 1));

                    @endphp


                    <div class="dsp-staff-card">


                        {{-- =================================================
                         STAFF HEADER
                    ================================================== --}}

                        <div class="dsp-staff-head">

                            <div class="dsp-staff-head-main">


                                <div class="dsp-staff-info">

                                    <div class="dsp-staff-avatar">

                                        {{ $staffInitial }}

                                    </div>


                                    <div>

                                        <div class="dsp-staff-name">

                                            {{ $staffName }}

                                        </div>

                                        <div class="dsp-staff-subtitle">

                                            Staff-wise performance

                                        </div>

                                    </div>

                                </div>


                                <div class="dsp-staff-stats">


                                    <div class="dsp-stat-pill">

                                        Leads:

                                        <strong>
                                            {{ number_format($staffReport['total_leads']) }}
                                        </strong>

                                    </div>


                                    <div class="dsp-stat-pill confirm">

                                        Confirm:

                                        <strong>
                                            {{ number_format($staffReport['confirm']) }}
                                        </strong>

                                        · {{ number_format($staffReport['confirm_percent'], 2) }}%

                                    </div>


                                    <div class="dsp-stat-pill delivery">

                                        Delivery:

                                        <strong>
                                            {{ number_format($staffReport['delivery']) }}
                                        </strong>

                                        · {{ number_format($staffReport['delivery_percent'], 2) }}%

                                    </div>


                                    <div class="dsp-stat-pill rto">

                                        RTO:

                                        <strong>
                                            {{ number_format($staffReport['rto']) }}
                                        </strong>

                                        · {{ number_format($staffReport['rto_percent'], 2) }}%

                                    </div>


                                </div>

                            </div>

                        </div>


                        {{-- =================================================
                         TABLE
                    ================================================== --}}

                        <div class="dsp-table-wrap">

                            <table class="dsp-table">

                                <thead>

                                    <tr>

                                        <th>
                                            Date
                                        </th>

                                        <th class="text-center">
                                            Total Leads
                                        </th>

                                        <th class="text-center">
                                            Confirm
                                        </th>

                                        <th class="text-center">
                                            Confirm %
                                        </th>

                                        <th class="text-center">
                                            Delivery
                                        </th>

                                        <th class="text-center">
                                            Delivery %
                                        </th>

                                        <th class="text-center">
                                            RTO
                                        </th>

                                        <th class="text-center">
                                            RTO %
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>


                                    @foreach ($staffReport['rows'] as $row)
                                        @php

                                            $dayName = \Carbon\Carbon::parse($row['date'])->format('l');

                                        @endphp


                                        <tr>


                                            {{-- DATE --}}

                                            <td>

                                                <span class="dsp-date">

                                                    {{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}

                                                </span>

                                                <span class="dsp-date-day">

                                                    {{ $dayName }}

                                                </span>

                                            </td>


                                            {{-- TOTAL LEADS --}}

                                            <td class="text-center">

                                                <span class="dsp-number dsp-number-leads">

                                                    {{ number_format($row['total_leads']) }}

                                                </span>

                                            </td>


                                            {{-- CONFIRM --}}

                                            <td class="text-center">

                                                <span class="dsp-number dsp-number-confirm">

                                                    {{ number_format($row['confirm']) }}

                                                </span>

                                            </td>


                                            {{-- CONFIRM % --}}

                                            <td class="text-center">

                                                <div class="dsp-percent">

                                                    <span class="dsp-percent-value">

                                                        {{ number_format($row['confirm_percent'], 2) }}%

                                                    </span>

                                                    <div class="dsp-mini-progress">

                                                        <div class="dsp-mini-bar dsp-progress-green"
                                                            style="width: {{ min(100, max(0, $row['confirm_percent'])) }}%;">
                                                        </div>

                                                    </div>

                                                </div>

                                            </td>


                                            {{-- DELIVERY --}}

                                            <td class="text-center">

                                                <span class="dsp-number dsp-number-delivery">

                                                    {{ number_format($row['delivery']) }}

                                                </span>

                                            </td>


                                            {{-- DELIVERY % --}}

                                            <td class="text-center">

                                                <div class="dsp-percent">

                                                    <span class="dsp-percent-value">

                                                        {{ number_format($row['delivery_percent'], 2) }}%

                                                    </span>

                                                    <div class="dsp-mini-progress">

                                                        <div class="dsp-mini-bar dsp-progress-blue"
                                                            style="width: {{ min(100, max(0, $row['delivery_percent'])) }}%;">
                                                        </div>

                                                    </div>

                                                </div>

                                            </td>


                                            {{-- RTO --}}

                                            <td class="text-center">

                                                <span class="dsp-number dsp-number-rto">

                                                    {{ number_format($row['rto']) }}

                                                </span>

                                            </td>


                                            {{-- RTO % --}}

                                            <td class="text-center">

                                                <div class="dsp-percent">

                                                    <span class="dsp-percent-value">

                                                        {{ number_format($row['rto_percent'], 2) }}%

                                                    </span>

                                                    <div class="dsp-mini-progress">

                                                        <div class="dsp-mini-bar dsp-progress-red"
                                                            style="width: {{ min(100, max(0, $row['rto_percent'])) }}%;">
                                                        </div>

                                                    </div>

                                                </div>

                                            </td>

                                        </tr>
                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    </div>

                @empty

                    <div class="dsp-empty">

                        <div class="dsp-empty-icon">

                            <i class="fas fa-chart-line"></i>

                        </div>

                        <div class="dsp-empty-title">

                            No Performance Data

                        </div>

                        <div class="dsp-empty-text">

                            No records found for the selected staff and date range.

                        </div>

                    </div>
                @endforelse


            </div>
        @elseif($clientId)
            {{-- =========================================================
             CLIENT SELECTED
             STAFF NOT SELECTED
        ========================================================== --}}

            <div class="dsp-report-card">

                <div class="dsp-empty">

                    <div class="dsp-empty-icon">

                        <i class="fas fa-users"></i>

                    </div>

                    <div class="dsp-empty-title">

                        Select Staff

                    </div>

                    <div class="dsp-empty-text">

                        Select one or more staff members above and click
                        <strong>Apply Filter</strong>.

                    </div>

                </div>

            </div>
        @else
            {{-- =========================================================
             CLIENT NOT SELECTED
        ========================================================== --}}

            <div class="dsp-report-card">

                <div class="dsp-empty">

                    <div class="dsp-empty-icon">

                        <i class="fas fa-building"></i>

                    </div>

                    <div class="dsp-empty-title">

                        Select Client

                    </div>

                    <div class="dsp-empty-text">

                        Select a client first. Staff belonging to that client
                        will then appear in the Staff selector.

                    </div>

                </div>

            </div>
        @endif


    </div>


    {{-- =============================================================
     CLIENT CHANGE
============================================================= --}}

    <script>
        function loadStaffForClient() {

            const clientId =
                document.getElementById('client_id').value;

            const staffSelect =
                document.getElementById('staff_id');


            if (!clientId) {

                staffSelect.innerHTML =
                    '<option disabled>Select Client first</option>';

                staffSelect.disabled = true;

                return;
            }


            /*
             * Reload page with selected client.
             *
             * Controller will load only staff belonging
             * to this client.
             */

            const form =
                document.getElementById(
                    'performanceFilterForm'
                );


            /*
             * Remove existing staff values before
             * submitting because client has changed.
             */

            staffSelect.disabled = true;


            form.submit();

        }
    </script>

@endsection
