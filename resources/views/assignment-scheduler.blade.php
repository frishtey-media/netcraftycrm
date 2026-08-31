@extends('layouts.admin')

@section('content')

    <style>
        /* =========================================================
                   PAGE
                ========================================================= */

        .scheduler-page {
            width: 100%;
            max-width: 100%;
        }

        .scheduler-card {
            border: 0;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
            margin-bottom: 20px;
        }

        .scheduler-card .card-header {
            background: #fff;
            border-bottom: 1px solid #eee;
            padding: 14px 18px;
        }

        .scheduler-card .card-body {
            padding: 18px;
        }


        /* =========================================================
                   FORM
                ========================================================= */

        .form-label {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .form-control,
        .form-select {
            font-size: 13px;
            min-height: 38px;
        }


        /* =========================================================
                   ASSIGNMENT MODE
                ========================================================= */

        .assignment-mode {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .mode-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 13px 15px;
            cursor: pointer;
            transition: .2s;
            background: #fff;
        }

        .mode-card:hover {
            border-color: #0d6efd;
            background: #f8fbff;
        }

        .mode-card.active {
            border-color: #0d6efd;
            background: #f5f9ff;
        }

        .mode-title {
            font-size: 14px;
            font-weight: 600;
        }

        .mode-description {
            font-size: 12px;
            color: #6c757d;
            margin-top: 3px;
        }


        /* =========================================================
                   SUGGESTION
                ========================================================= */

        #auto-suggestion-section {
            margin-top: 18px;
        }

        .suggestion-box {
            border: 1px solid #dbe5f1;
            border-radius: 9px;
            background: #f8fbff;
            padding: 15px;
        }

        .suggestion-title {
            font-size: 15px;
            font-weight: 700;
        }

        .suggestion-subtitle {
            font-size: 12px;
            color: #6c757d;
        }


        /* =========================================================
                   PERFORMANCE TABLE
                ========================================================= */

        .suggestion-table-wrapper {
            width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            border: 1px solid #dee2e6;
            border-radius: 7px;
            background: #fff;
        }

        .suggestion-table {
            min-width: 1250px;
            margin-bottom: 0;
            font-size: 12px;
        }

        .suggestion-table th {
            white-space: nowrap;
            font-size: 11px;
            font-weight: 700;
            background: #212529;
            color: #fff;
            padding: 8px 9px;
            vertical-align: middle;
        }

        .suggestion-table td {
            white-space: nowrap;
            padding: 8px 9px;
            vertical-align: middle;
        }

        .suggestion-table .top-five-row {
            background: #eef7ff;
        }

        .suggestion-table .score-cell {
            min-width: 110px;
        }

        .suggestion-table .progress {
            height: 5px;
            margin-top: 4px;
        }


        /* =========================================================
                   ASSIGNMENT %
                ========================================================= */

        .suggested-percentage {
            font-size: 12px;
            font-weight: 700;
            padding: 5px 8px;
        }


        /* =========================================================
                   MANUAL STAFF
                ========================================================= */

        .staff-box {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }

        .staff-box-header {
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            padding: 10px 13px;
            font-size: 13px;
            font-weight: 700;
        }



        .staff-row {
            padding: 9px 13px;
            border-bottom: 1px solid #f0f0f0;
        }

        .staff-row:last-child {
            border-bottom: 0;
        }

        .staff-name {
            font-size: 13px;
            font-weight: 500;
        }

        .staff-percentage {
            max-width: 120px;
            font-size: 13px;
        }


        /* =========================================================
                   TOTAL
                ========================================================= */

        .total-box {
            margin-top: 12px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
        }

        .total-label {
            font-size: 13px;
            font-weight: 600;
        }


        /* =========================================================
                   RTO
                ========================================================= */

        .rto-info {
            border-radius: 8px;
            font-size: 13px;
        }


        /* =========================================================
                   EXISTING SCHEDULER TABLE
                ========================================================= */

        .scheduler-table-wrapper {
            width: 100%;
            overflow-x: auto;
        }

        .scheduler-table {
            min-width: 900px;
            margin-bottom: 0;
            font-size: 12px;
        }

        .scheduler-table th {
            white-space: nowrap;
            font-size: 11px;
            padding: 8px 10px;
        }

        .scheduler-table td {
            padding: 8px 10px;
            vertical-align: middle;
        }


        /* =========================================================
                   BADGES
                ========================================================= */

        .small-badge {
            font-size: 10px;
            padding: 4px 6px;
        }


        /* =========================================================
                   MOBILE
                ========================================================= */

        @media(max-width: 768px) {

            .assignment-mode {
                grid-template-columns: 1fr;
            }

            .scheduler-card .card-body {
                padding: 13px;
            }

            .scheduler-card .card-header {
                padding: 12px 13px;
            }

        }
    </style>


    <div class="container-fluid scheduler-page py-3">


        {{-- =========================================================
         PAGE HEADER
    ========================================================= --}}

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h4 class="mb-1 fw-bold">
                    Assignment Scheduler
                </h4>

                <div class="text-muted small">
                    Configure automatic order assignment for staff.
                </div>

            </div>

        </div>


        {{-- =========================================================
         ALERTS
    ========================================================= --}}

        @if (session('success'))
            <div class="alert alert-success py-2 small">
                {{ session('success') }}
            </div>
        @endif


        @if (session('error'))
            <div class="alert alert-danger py-2 small">
                {{ session('error') }}
            </div>
        @endif


        @if ($errors->any())
            <div class="alert alert-danger small">

                <ul class="mb-0">

                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach

                </ul>

            </div>
        @endif


        {{-- =========================================================
         CREATE / EDIT SCHEDULER
    ========================================================= --}}

        <div class="card scheduler-card">

            <div class="card-header">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="fw-bold">
                            Create Assignment Scheduler
                        </div>

                        <div class="text-muted small">
                            Select client, order type, timing and staff assignment.
                        </div>

                    </div>

                </div>

            </div>


            <div class="card-body">

                <form id="scheduler-form" method="POST" action="{{ route('assignment.scheduler.save') }}">

                    @csrf


                    <input type="hidden" name="scheduler_id" id="scheduler_id">


                    {{-- =================================================
                     CLIENT
                ================================================== --}}

                    <div class="row g-3 mb-3">

                        <div class="col-lg-6 col-md-6">

                            <label class="form-label">
                                Client
                            </label>

                            <select name="client_id" id="client_id" class="form-select" required>

                                <option value="">
                                    Select Client
                                </option>

                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}">

                                        {{ $client->client_name }}

                                    </option>
                                @endforeach

                            </select>

                        </div>


                        <div class="col-lg-3 col-md-3">

                            <label class="form-label">
                                Start Time
                            </label>

                            <input type="time" name="start_time" class="form-control"
                                value="{{ old('start_time', '09:00') }}" required>

                        </div>


                        <div class="col-lg-3 col-md-3">

                            <label class="form-label">
                                End Time
                            </label>

                            <input type="time" name="end_time" class="form-control"
                                value="{{ old('end_time', '18:00') }}" required>

                        </div>

                    </div>


                    {{-- =================================================
                     ORDER TYPES
                ================================================== --}}

                    <div class="mb-4">

                        <label class="form-label">
                            Order Types
                        </label>


                        <div class="row g-2">

                            <div class="col-lg-3 col-md-6">

                                <div class="form-check border rounded p-2">

                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="order_types[]"
                                        value="shopify" id="typeShopify">

                                    <label class="form-check-label" for="typeShopify">

                                        Shopify

                                    </label>

                                </div>

                            </div>


                            <div class="col-lg-3 col-md-6">

                                <div class="form-check border rounded p-2">

                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="order_types[]"
                                        value="abandoned_checkout" id="typeAbandoned">

                                    <label class="form-check-label" for="typeAbandoned">

                                        Abandoned Checkout

                                    </label>

                                </div>

                            </div>


                            <div class="col-lg-3 col-md-6">

                                <div class="form-check border rounded p-2">

                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="order_types[]"
                                        value="deliveredreorder" id="typeReorder">

                                    <label class="form-check-label" for="typeReorder">

                                        Delivered Re-Order

                                    </label>

                                </div>

                            </div>


                            <div class="col-lg-3 col-md-6">

                                <div class="form-check border rounded p-2">

                                    <input class="form-check-input ms-0 me-2" type="checkbox" name="order_types[]"
                                        value="rto" id="typeRto">

                                    <label class="form-check-label" for="typeRto">

                                        RTO

                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                     DAYS
                ================================================== --}}

                    <div class="mb-4">

                        <label class="form-label">
                            Working Days
                        </label>


                        @php

                            $days = [
                                'monday' => 'Monday',

                                'tuesday' => 'Tuesday',

                                'wednesday' => 'Wednesday',

                                'thursday' => 'Thursday',

                                'friday' => 'Friday',

                                'saturday' => 'Saturday',

                                'sunday' => 'Sunday',
                            ];

                        @endphp


                        <div class="row g-2">

                            @foreach ($days as $value => $label)
                                <div class="col-lg-3 col-md-4 col-sm-6">

                                    <div class="form-check">

                                        <input class="form-check-input" type="checkbox" name="days[]"
                                            value="{{ $value }}" id="day_{{ $value }}"
                                            {{ $value !== 'sunday' ? 'checked' : '' }}>

                                        <label class="form-check-label small" for="day_{{ $value }}">

                                            {{ $label }}

                                        </label>

                                    </div>

                                </div>
                            @endforeach

                        </div>

                    </div>


                    {{-- =================================================
                     ASSIGNMENT MODE
                ================================================== --}}

                    <div class="mb-3">

                        <label class="form-label">
                            Staff Assignment
                        </label>


                        <div class="assignment-mode">


                            {{-- MANUAL --}}

                            <label class="mode-card" id="manualModeCard">

                                <div class="d-flex">

                                    <input type="radio" class="form-check-input me-2 mt-1" name="assignment_mode"
                                        value="manual" id="manual_assignment" checked>

                                    <div>

                                        <div class="mode-title">
                                            Manual Assignment
                                        </div>

                                        <div class="mode-description">
                                            Select staff and set percentages manually.
                                        </div>

                                    </div>

                                </div>

                            </label>


                            {{-- AUTO --}}

                            <label class="mode-card" id="autoModeCard">

                                <div class="d-flex">

                                    <input type="radio" class="form-check-input me-2 mt-1" name="assignment_mode"
                                        value="auto" id="auto_suggest">

                                    <div>

                                        <div class="mode-title">
                                            🤖 Auto Suggest Distribution
                                        </div>

                                        <div class="mode-description">
                                            Top 5 staff based on client-wise performance.
                                        </div>

                                    </div>

                                </div>

                            </label>

                        </div>

                    </div>


                    {{-- =================================================
                     AUTO SUGGESTION
                ================================================== --}}

                    <div id="auto-suggestion-section" style="display:none;">

                        <div class="suggestion-box">


                            {{-- HEADER --}}

                            <div class="d-flex justify-content-between align-items-start mb-3">

                                <div>

                                    <div class="suggestion-title">
                                        Recommended Staff Distribution
                                    </div>

                                    <div class="suggestion-subtitle">
                                        Client-wise performance — Last 30 Days
                                    </div>

                                </div>


                                <div class="text-end">

                                    <div class="small text-muted">
                                        Total Assignment
                                    </div>

                                    <strong id="suggestedTotalPercentage" class="text-success">
                                        0%
                                    </strong>

                                </div>

                            </div>


                            {{-- LOADING --}}

                            <div id="suggestion-loading" class="text-center py-4" style="display:none;">

                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>

                                <span class="small">
                                    Calculating staff performance...
                                </span>

                            </div>


                            {{-- MESSAGE --}}

                            <div id="suggestion-message"></div>


                            {{-- TABLE --}}

                            <div class="suggestion-table-wrapper">

                                <table class="table table-bordered table-hover suggestion-table">

                                    <thead>

                                        <tr>

                                            <th>
                                                #
                                            </th>

                                            <th>
                                                Staff
                                            </th>

                                            <th>
                                                Leads
                                            </th>

                                            <th>
                                                Verified
                                            </th>

                                            <th>
                                                Confirm %
                                            </th>

                                            <th>
                                                Reach %
                                            </th>

                                            <th>
                                                Delivered
                                            </th>

                                            <th>
                                                Delivery %
                                            </th>

                                            <th>
                                                RTO Intransit
                                            </th>

                                            <th>
                                                RTO Received
                                            </th>

                                            <th>
                                                RTO %
                                            </th>

                                            <th>
                                                Cancel
                                            </th>

                                            <th>
                                                Cancel %
                                            </th>

                                            <th>
                                                Score
                                            </th>

                                            <th>
                                                Suggested %
                                            </th>

                                        </tr>

                                    </thead>


                                    <tbody id="suggestion-table-body">

                                    </tbody>


                                    <tfoot>

                                        <tr class="fw-bold">

                                            <td colspan="14" class="text-end">

                                                Total Suggested Assignment

                                            </td>

                                            <td>

                                                <span id="suggestedFooterPercentage" class="badge bg-success">
                                                    0%
                                                </span>

                                            </td>

                                        </tr>

                                    </tfoot>

                                </table>

                            </div>


                            {{-- INFO --}}

                            <div class="alert alert-info py-2 mt-3 mb-3 small">

                                <strong>
                                    How Auto Assignment works:
                                </strong>

                                Top 5 staff are selected according to their
                                overall performance score. The 100% order
                                assignment is distributed among these Top 5
                                according to their performance.

                            </div>


                            {{-- ACTION --}}

                            <div class="d-flex justify-content-between align-items-center">

                                <div class="small">

                                    <strong>
                                        Best Staff:
                                    </strong>

                                    <span id="bestStaffName" class="text-primary fw-bold">
                                        -
                                    </span>

                                </div>


                                <button type="button" id="apply-suggestion" class="btn btn-success btn-sm">

                                    Apply Suggested Distribution

                                </button>

                            </div>

                        </div>

                    </div>


                    {{-- =================================================
                     MANUAL STAFF DISTRIBUTION
                ================================================== --}}

                    <div class="mt-4" id="manual-staff-section">

                        <div class="staff-box">


                            <div class="staff-box-header">

                                Staff Distribution

                                <span class="text-muted fw-normal">
                                    — Selected client staff
                                </span>

                            </div>


                            <div class="staff-list" id="staff-distribution">

                                @forelse($staff as $member)
                                    <div class="staff-row">

                                        <div class="row align-items-center g-2">

                                            <div class="col-md-7">

                                                <div class="form-check mb-0">

                                                    <input class="form-check-input" type="checkbox" name="staff_ids[]"
                                                        value="{{ $member->id }}" id="staff_{{ $member->id }}">

                                                    <label class="form-check-label staff-name"
                                                        for="staff_{{ $member->id }}">

                                                        {{ $member->name }}

                                                    </label>

                                                </div>

                                            </div>


                                            <div class="col-md-5">

                                                <div class="input-group input-group-sm">

                                                    <input type="number" class="form-control staff-percentage"
                                                        name="staff_percentages[{{ $member->id }}]"
                                                        data-staff-id="{{ $member->id }}" value="0"
                                                        min="0" max="100" step="0.01">

                                                    <span class="input-group-text">
                                                        %
                                                    </span>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                @empty

                                    <div class="text-center py-4 text-muted small">

                                        No active staff found.

                                    </div>
                                @endforelse

                            </div>

                        </div>


                        <div class="total-box">

                            <span class="total-label">
                                Total Percentage:
                            </span>

                            <span id="total-percentage" class="badge bg-danger">
                                0%
                            </span>

                        </div>

                    </div>


                    {{-- =================================================
                     RTO INFO
                ================================================== --}}

                    <div id="rto-info" class="alert alert-info rto-info mt-3" style="display:none;">

                        <div class="fw-bold mb-1">
                            🔄 RTO Auto Assignment
                        </div>

                        <div>
                            RTO orders will automatically be assigned
                            to the staff member who handled the original order.
                        </div>

                        <div class="small text-muted mt-1">
                            Staff percentage distribution is not required for RTO.
                        </div>

                    </div>


                    {{-- =================================================
                     ACTIVE
                ================================================== --}}

                    <div class="form-check form-switch mt-4">

                        <input class="form-check-input" type="checkbox" name="is_active" value="1"
                            id="schedulerActive" checked>

                        <label class="form-check-label fw-semibold" for="schedulerActive">
                            Enable Auto Assignment
                        </label>

                    </div>


                    {{-- =================================================
                     SAVE
                ================================================== --}}

                    <div class="mt-4">

                        <button type="submit" class="btn btn-primary px-4">

                            Save Scheduler

                        </button>

                        <button type="reset" class="btn btn-outline-secondary ms-2" id="resetScheduler">

                            Reset

                        </button>

                    </div>

                </form>

            </div>

        </div>


        {{-- =========================================================
         EXISTING SCHEDULERS
    ========================================================= --}}

        <div class="card scheduler-card">

            <div class="card-header">

                <div class="fw-bold">
                    Existing Schedulers
                </div>

            </div>


            <div class="card-body p-0">

                <div class="scheduler-table-wrapper">

                    <table class="table table-bordered table-hover scheduler-table">

                        <thead class="table-dark">

                            <tr>

                                <th>
                                    Client
                                </th>

                                <th>
                                    Order Types
                                </th>

                                <th>
                                    Time
                                </th>

                                <th>
                                    Days
                                </th>

                                <th>
                                    Staff Distribution
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($schedulers as $scheduler)
                                <tr>

                                    {{-- CLIENT --}}

                                    <td>

                                        <strong>
                                            {{ $scheduler->client->client_name ?? '-' }}
                                        </strong>

                                    </td>


                                    {{-- ORDER TYPES --}}

                                    <td>

                                        @foreach ($scheduler->order_types ?? [] as $type)
                                            @if ($type === 'shopify')
                                                <span class="badge bg-primary small-badge">
                                                    Shopify
                                                </span>
                                            @elseif($type === 'abandoned_checkout')
                                                <span class="badge bg-warning text-dark small-badge">
                                                    Abandoned
                                                </span>
                                            @elseif($type === 'deliveredreorder')
                                                <span class="badge bg-success small-badge">
                                                    Re-Order
                                                </span>
                                            @elseif($type === 'rto')
                                                <span class="badge bg-danger small-badge">
                                                    RTO
                                                </span>
                                            @endif
                                        @endforeach

                                    </td>


                                    {{-- TIME --}}

                                    <td>

                                        {{ \Carbon\Carbon::parse($scheduler->start_time)->format('h:i A') }}

                                        -

                                        {{ \Carbon\Carbon::parse($scheduler->end_time)->format('h:i A') }}

                                    </td>


                                    {{-- DAYS --}}

                                    <td>

                                        @foreach ($scheduler->days ?? [] as $day)
                                            <span class="badge bg-light text-dark border small-badge">

                                                {{ ucfirst(substr($day, 0, 3)) }}

                                            </span>
                                        @endforeach

                                    </td>


                                    {{-- STAFF --}}

                                    <td>

                                        @if (in_array('rto', $scheduler->order_types ?? [], true))
                                            <span class="badge bg-info text-dark">
                                                Original Staff
                                            </span>
                                        @else
                                            @forelse($scheduler->staff_assignments ?? []
                                                        as $assignment)
                                                @php

                                                    $staffMember = $staff->firstWhere('id', $assignment['staff_id']);

                                                @endphp

                                                @if ($staffMember)
                                                    <div class="mb-1">

                                                        <span>
                                                            {{ $staffMember->name }}
                                                        </span>

                                                        <span class="badge bg-primary small-badge">

                                                            {{ $assignment['percentage'] }}%

                                                        </span>

                                                    </div>
                                                @endif

                                            @empty

                                                <span class="text-muted">
                                                    No staff assigned
                                                </span>
                                            @endforelse
                                        @endif

                                    </td>


                                    {{-- STATUS --}}

                                    <td>

                                        @if ($scheduler->is_active)
                                            <span class="badge bg-success">
                                                Active
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                Inactive
                                            </span>
                                        @endif

                                    </td>


                                    {{-- ACTION --}}

                                    <td>

                                        <div class="d-flex gap-1">


                                            {{-- TOGGLE --}}

                                            <form method="POST"
                                                action="{{ route('assignment.scheduler.toggle', $scheduler->id) }}">

                                                @csrf

                                                <button type="submit"
                                                    class="btn btn-sm
                                                {{ $scheduler->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}">

                                                    {{ $scheduler->is_active ? 'OFF' : 'ON' }}

                                                </button>

                                            </form>


                                            {{-- DELETE --}}

                                            <form method="POST"
                                                action="{{ route('assignment.scheduler.delete', $scheduler->id) }}"
                                                onsubmit="return confirm('Delete this scheduler?');">

                                                @csrf

                                                @method('DELETE')

                                                <button type="submit" class="btn btn-sm btn-outline-danger">

                                                    Delete

                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="7" class="text-center text-muted py-4">

                                        No assignment scheduler found.

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>


    {{-- =============================================================
     JAVASCRIPT
============================================================= --}}

    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function() {


                /*
                |--------------------------------------------------------------------------
                | ELEMENTS
                |--------------------------------------------------------------------------
                */

                const clientSelect =
                    document.getElementById(
                        'client_id'
                    );


                const manualRadio =
                    document.getElementById(
                        'manual_assignment'
                    );


                const autoRadio =
                    document.getElementById(
                        'auto_suggest'
                    );


                const manualCard =
                    document.getElementById(
                        'manualModeCard'
                    );


                const autoCard =
                    document.getElementById(
                        'autoModeCard'
                    );


                const autoSection =
                    document.getElementById(
                        'auto-suggestion-section'
                    );


                const manualSection =
                    document.getElementById(
                        'manual-staff-section'
                    );


                const suggestionLoading =
                    document.getElementById(
                        'suggestion-loading'
                    );


                const suggestionMessage =
                    document.getElementById(
                        'suggestion-message'
                    );


                const suggestionTableBody =
                    document.getElementById(
                        'suggestion-table-body'
                    );


                const applySuggestionBtn =
                    document.getElementById(
                        'apply-suggestion'
                    );


                const totalPercentage =
                    document.getElementById(
                        'total-percentage'
                    );


                const suggestedTotalPercentage =
                    document.getElementById(
                        'suggestedTotalPercentage'
                    );


                const suggestedFooterPercentage =
                    document.getElementById(
                        'suggestedFooterPercentage'
                    );


                const bestStaffName =
                    document.getElementById(
                        'bestStaffName'
                    );


                const rtoCheckbox =
                    document.getElementById(
                        'typeRto'
                    );


                const rtoInfo =
                    document.getElementById(
                        'rto-info'
                    );


                let suggestedStaff = [];


                /*
                |--------------------------------------------------------------------------
                | URL
                |--------------------------------------------------------------------------
                */

                const suggestionUrl =
                    "{{ route('assignment.scheduler.suggestion') }}";



                /*
                |--------------------------------------------------------------------------
                | ESCAPE HTML
                |--------------------------------------------------------------------------
                */

                function escapeHtml(value) {

                    const div =
                        document.createElement('div');

                    div.textContent =
                        value ?? '';

                    return div.innerHTML;

                }



                /*
                |--------------------------------------------------------------------------
                | NUMBER
                |--------------------------------------------------------------------------
                */

                function num(value) {

                    const result =
                        parseFloat(value);

                    return isNaN(result) ?
                        0 :
                        result;

                }



                /*
                |--------------------------------------------------------------------------
                | FORMAT
                |--------------------------------------------------------------------------
                */

                function formatPercent(value) {

                    return num(value)
                        .toFixed(2)
                        .replace(
                            /\.00$/,
                            ''
                        );

                }



                /*
                |--------------------------------------------------------------------------
                | MODE UI
                |--------------------------------------------------------------------------
                */

                function updateModeUI() {


                    if (
                        manualRadio &&
                        manualRadio.checked
                    ) {

                        manualCard?.classList.add(
                            'active'
                        );

                        autoCard?.classList.remove(
                            'active'
                        );

                        autoSection.style.display =
                            'none';


                        /*
                        | Enable manual
                        */

                        manualSection
                            ?.querySelectorAll('input')
                            .forEach(
                                function(input) {

                                    input.disabled =
                                        false;

                                }
                            );

                    }


                    if (
                        autoRadio &&
                        autoRadio.checked
                    ) {

                        manualCard?.classList.remove(
                            'active'
                        );

                        autoCard?.classList.add(
                            'active'
                        );


                        /*
                        | RTO cannot use performance
                        */

                        if (
                            rtoCheckbox &&
                            rtoCheckbox.checked
                        ) {

                            autoRadio.checked =
                                false;

                            manualRadio.checked =
                                true;

                            updateModeUI();

                            return;

                        }


                        manualSection
                            ?.querySelectorAll('input')
                            .forEach(
                                function(input) {

                                    input.disabled =
                                        true;

                                }
                            );


                        autoSection.style.display =
                            'block';


                        loadSuggestion();

                    }

                }



                /*
                |--------------------------------------------------------------------------
                | LOAD SUGGESTION
                |--------------------------------------------------------------------------
                */

                async function loadSuggestion() {


                    const clientId =
                        clientSelect?.value;


                    if (!clientId) {

                        autoSection.style.display =
                            'block';


                        showMessage(
                            '<div class="alert alert-warning py-2 small">Please select a client first.</div>'
                        );


                        suggestionTableBody.innerHTML =
                            '';


                        return;

                    }


                    /*
                    |--------------------------------------------------------------------------
                    | LOADING
                    |--------------------------------------------------------------------------
                    */

                    suggestionLoading.style.display =
                        'block';


                    suggestionMessage.innerHTML =
                        '';


                    suggestionTableBody.innerHTML =
                        '';


                    suggestedStaff = [];


                    try {


                        const url =
                            suggestionUrl +
                            '?client_id=' +
                            encodeURIComponent(
                                clientId
                            );


                        const response =
                            await fetch(
                                url, {

                                    method: 'GET',

                                    headers: {

                                        'Accept': 'application/json',

                                        'X-Requested-With': 'XMLHttpRequest'

                                    }

                                }
                            );


                        const data =
                            await response.json();


                        if (
                            !response.ok ||
                            !data.success
                        ) {

                            throw new Error(
                                data.message ||
                                'Unable to calculate staff performance.'
                            );

                        }


                        suggestedStaff =
                            Array.isArray(
                                data.staff
                            ) ?
                            data.staff : [];


                        /*
                        |--------------------------------------------------------------------------
                        | NO STAFF
                        |--------------------------------------------------------------------------
                        */

                        if (
                            suggestedStaff.length === 0
                        ) {

                            showMessage(
                                '<div class="alert alert-warning py-2 small">No active staff found for this client.</div>'
                            );

                            return;

                        }


                        /*
                        |--------------------------------------------------------------------------
                        | RENDER
                        |--------------------------------------------------------------------------
                        */

                        renderSuggestion(
                            suggestedStaff
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | TOTAL
                        |--------------------------------------------------------------------------
                        */

                        const total =
                            data.total_suggested_percentage ??
                            0;


                        suggestedTotalPercentage.innerText =
                            formatPercent(total) +
                            '%';


                        suggestedFooterPercentage.innerText =
                            formatPercent(total) +
                            '%';


                        /*
                        |--------------------------------------------------------------------------
                        | BEST
                        |--------------------------------------------------------------------------
                        */

                        bestStaffName.innerText =
                            data.best_staff?.staff_name ??
                            '-';


                        /*
                        |--------------------------------------------------------------------------
                        | MESSAGE
                        |--------------------------------------------------------------------------
                        */

                        showMessage(

                            `
                    <div class="alert alert-success py-2 small mb-3">

                        <strong>
                            Auto suggestion calculated.
                        </strong>

                        Top
                        ${data.top_five_count ?? 0}
                        staff will receive the workload.

                    </div>
                    `

                        );

                    } catch (error) {


                        console.error(
                            'Staff Suggestion Error:',
                            error
                        );


                        showMessage(

                            `
                    <div class="alert alert-danger py-2 small">

                        <strong>
                            Error:
                        </strong>

                        ${escapeHtml(
                            error.message
                        )}

                    </div>
                    `

                        );

                    } finally {

                        suggestionLoading.style.display =
                            'none';

                    }

                }



                /*
                |--------------------------------------------------------------------------
                | RENDER SUGGESTION
                |--------------------------------------------------------------------------
                */

                function renderSuggestion(
                    staffList
                ) {


                    suggestionTableBody.innerHTML =
                        '';


                    staffList.forEach(
                        function(staff, index) {


                            const tr =
                                document.createElement(
                                    'tr'
                                );


                            if (
                                staff.top_five
                            ) {

                                tr.classList.add(
                                    'top-five-row'
                                );

                            }


                            const score =
                                num(
                                    staff.score
                                );


                            const assignment =
                                num(
                                    staff.suggested_percentage
                                );


                            tr.innerHTML = `

                        <td>
                            ${index + 1}
                        </td>


                        <td>

                            <strong>
                                ${escapeHtml(
                                    staff.staff_name
                                )}
                            </strong>

                            ${
                                staff.top_five
                                ?

                                `
                                            <span
                                                class="badge bg-success small-badge ms-1"
                                            >
                                                TOP 5
                                            </span>
                                            `

                                :

                                `
                                            <span
                                                class="badge bg-secondary small-badge ms-1"
                                            >
                                                0%
                                            </span>
                                            `
                            }

                        </td>


                        <td>
                            ${staff.leads ?? 0}
                        </td>


                        <td>
                            ${staff.verified ?? 0}
                        </td>


                        <td>
                            ${formatPercent(
                                staff.confirmation_rate
                            )}%
                        </td>


                        <td>
                            ${formatPercent(
                                staff.reachability_rate
                            )}%
                        </td>


                        <td>
                            ${staff.delivered ?? 0}
                        </td>


                        <td>
                            ${formatPercent(
                                staff.delivery_rate
                            )}%
                        </td>


                        <td>
                            ${staff.rto_intransit ?? 0}
                        </td>


                        <td>
                            ${staff.rto_received ?? 0}
                        </td>


                        <td class="text-danger">
                            ${formatPercent(
                                staff.rto_rate
                            )}%
                        </td>


                        <td>
                            ${staff.cancel ?? 0}
                        </td>


                        <td>
                            ${formatPercent(
                                staff.cancel_rate
                            )}%
                        </td>


                        <td class="score-cell">

                            <strong>
                                ${formatPercent(
                                    score
                                )}%
                            </strong>

                            <div class="progress">

                                <div
                                    class="progress-bar"
                                    style="
                                        width:
                                        ${Math.min(
                                            score,
                                            100
                                        )}%;
                                    "
                                ></div>

                            </div>

                        </td>


                        <td>

                            <span
                                class="
                                    badge
                                    suggested-percentage
                                    ${
                                        assignment > 0
                                        ? 'bg-primary'
                                        : 'bg-secondary'
                                    }
                                "
                            >

                                ${formatPercent(
                                    assignment
                                )}%

                            </span>

                        </td>

                    `;


                            suggestionTableBody.appendChild(
                                tr
                            );

                        }
                    );

                }



                /*
                |--------------------------------------------------------------------------
                | APPLY SUGGESTION
                |--------------------------------------------------------------------------
                */

                if (applySuggestionBtn) {

                    applySuggestionBtn.addEventListener(
                        'click',
                        function() {


                            if (
                                !suggestedStaff.length
                            ) {

                                alert(
                                    'Please generate staff suggestion first.'
                                );

                                return;

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | RESET
                            |--------------------------------------------------------------------------
                            */

                            staffContainerInputs(
                                'input[name="staff_ids[]"]'
                            ).forEach(
                                function(input) {

                                    input.checked =
                                        false;

                                }
                            );


                            staffContainerInputs(
                                'input[name^="staff_percentages"]'
                            ).forEach(
                                function(input) {

                                    input.value =
                                        0;

                                }
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | APPLY ONLY TOP 5
                            |--------------------------------------------------------------------------
                            */

                            suggestedStaff.forEach(
                                function(staff) {


                                    if (
                                        !staff.top_five ||
                                        num(
                                            staff.suggested_percentage
                                        ) <= 0
                                    ) {

                                        return;

                                    }


                                    const checkbox =
                                        document.querySelector(
                                            `#staff_${staff.staff_id}`
                                        );


                                    const percentage =
                                        document.querySelector(
                                            `input[name="staff_percentages[${staff.staff_id}]"]`
                                        );


                                    if (
                                        checkbox &&
                                        percentage
                                    ) {

                                        checkbox.checked =
                                            true;


                                        percentage.value =
                                            formatPercent(
                                                staff.suggested_percentage
                                            );

                                    }

                                }
                            );


                            calculateTotal();


                            /*
                            |--------------------------------------------------------------------------
                            | SWITCH TO MANUAL
                            |--------------------------------------------------------------------------
                            |
                            | Because final values are now stored
                            | in existing staff_ids/staff_percentages.
                            |
                            */

                            manualRadio.checked =
                                true;


                            updateModeUI();


                            /*
                            |--------------------------------------------------------------------------
                            | BUTTON
                            |--------------------------------------------------------------------------
                            */

                            applySuggestionBtn.innerHTML =
                                'Applied ✓';


                            applySuggestionBtn.classList.remove(
                                'btn-success'
                            );


                            applySuggestionBtn.classList.add(
                                'btn-primary'
                            );


                            setTimeout(
                                function() {

                                    applySuggestionBtn.innerHTML =
                                        'Apply Suggested Distribution';

                                    applySuggestionBtn.classList.remove(
                                        'btn-primary'
                                    );

                                    applySuggestionBtn.classList.add(
                                        'btn-success'
                                    );

                                },
                                2500
                            );

                        }
                    );

                }



                /*
                |--------------------------------------------------------------------------
                | STAFF INPUT HELPER
                |--------------------------------------------------------------------------
                */

                function staffContainerInputs(
                    selector
                ) {

                    if (!manualSection) {
                        return [];
                    }


                    return Array.from(
                        manualSection.querySelectorAll(
                            selector
                        )
                    );

                }



                /*
                |--------------------------------------------------------------------------
                | CALCULATE TOTAL
                |--------------------------------------------------------------------------
                */

                function calculateTotal() {


                    let total =
                        0;


                    staffContainerInputs(
                            'input[name^="staff_percentages"]'
                        )
                        .forEach(
                            function(input) {

                                total +=
                                    num(
                                        input.value
                                    );

                            }
                        );


                    total =
                        Math.round(
                            total * 100
                        ) / 100;


                    totalPercentage.innerText =
                        formatPercent(total) +
                        '%';


                    if (
                        Math.abs(
                            total - 100
                        ) < 0.01
                    ) {

                        totalPercentage.classList.remove(
                            'bg-danger'
                        );

                        totalPercentage.classList.add(
                            'bg-success'
                        );

                    } else {

                        totalPercentage.classList.remove(
                            'bg-success'
                        );

                        totalPercentage.classList.add(
                            'bg-danger'
                        );

                    }

                }



                /*
                |--------------------------------------------------------------------------
                | MESSAGE
                |--------------------------------------------------------------------------
                */

                function showMessage(
                    html
                ) {

                    if (
                        suggestionMessage
                    ) {

                        suggestionMessage.innerHTML =
                            html;

                    }

                }



                /*
                |--------------------------------------------------------------------------
                | MANUAL INPUT EVENTS
                |--------------------------------------------------------------------------
                */

                staffContainerInputs(
                        'input[name^="staff_percentages"]'
                    )
                    .forEach(
                        function(input) {

                            input.addEventListener(
                                'input',
                                calculateTotal
                            );

                        }
                    );


                staffContainerInputs(
                        'input[name="staff_ids[]"]'
                    )
                    .forEach(
                        function(checkbox) {

                            checkbox.addEventListener(
                                'change',
                                function() {

                                    const input =
                                        document.querySelector(
                                            `input[name="staff_percentages[${this.value}]"]`
                                        );


                                    if (
                                        input &&
                                        !this.checked
                                    ) {

                                        input.value =
                                            0;

                                    }


                                    calculateTotal();

                                }
                            );

                        }
                    );



                /*
                |--------------------------------------------------------------------------
                | RADIO
                |--------------------------------------------------------------------------
                */

                if (manualRadio) {

                    manualRadio.addEventListener(
                        'change',
                        function() {

                            if (
                                this.checked
                            ) {

                                updateModeUI();

                            }

                        }
                    );

                }


                if (autoRadio) {

                    autoRadio.addEventListener(
                        'change',
                        function() {

                            if (
                                this.checked
                            ) {

                                updateModeUI();

                            }

                        }
                    );

                }



                /*
                |--------------------------------------------------------------------------
                | CLIENT CHANGE
                |--------------------------------------------------------------------------
                */

                if (clientSelect) {

                    clientSelect.addEventListener(
                        'change',
                        function() {


                            /*
                            | Reset applied button
                            */

                            if (
                                applySuggestionBtn
                            ) {

                                applySuggestionBtn.innerHTML =
                                    'Apply Suggested Distribution';

                                applySuggestionBtn.classList.remove(
                                    'btn-primary'
                                );

                                applySuggestionBtn.classList.add(
                                    'btn-success'
                                );

                            }


                            /*
                            | Auto mode
                            */

                            if (
                                autoRadio &&
                                autoRadio.checked
                            ) {

                                loadSuggestion();

                            }

                        }
                    );

                }



                /*
                |--------------------------------------------------------------------------
                | RTO UI
                |--------------------------------------------------------------------------
                */

                function updateRtoUI() {


                    const isRto =
                        rtoCheckbox &&
                        rtoCheckbox.checked;


                    if (isRto) {


                        /*
                        | Hide normal staff
                        */

                        manualSection.style.display =
                            'none';


                        rtoInfo.style.display =
                            'block';


                        /*
                        | Disable staff
                        */

                        staffContainerInputs(
                                'input'
                            )
                            .forEach(
                                function(input) {

                                    input.disabled =
                                        true;

                                    if (
                                        input.type ===
                                        'number'
                                    ) {

                                        input.value =
                                            0;

                                    }

                                }
                            );


                        /*
                        | Force manual
                        */

                        if (manualRadio) {

                            manualRadio.checked =
                                true;

                        }


                        if (autoRadio) {

                            autoRadio.disabled =
                                true;

                        }


                        autoSection.style.display =
                            'none';


                        totalPercentage.innerText =
                            'N/A';


                        totalPercentage.className =
                            'badge bg-info';

                    } else {


                        manualSection.style.display =
                            'block';


                        rtoInfo.style.display =
                            'none';


                        staffContainerInputs(
                                'input'
                            )
                            .forEach(
                                function(input) {

                                    input.disabled =
                                        false;

                                }
                            );


                        if (autoRadio) {

                            autoRadio.disabled =
                                false;

                        }


                        calculateTotal();

                    }

                }



                if (rtoCheckbox) {

                    rtoCheckbox.addEventListener(
                        'change',
                        function() {

                            updateRtoUI();

                        }
                    );

                }



                /*
                |--------------------------------------------------------------------------
                | FORM VALIDATION
                |--------------------------------------------------------------------------
                */

                const form =
                    document.getElementById(
                        'scheduler-form'
                    );


                if (form) {

                    form.addEventListener(
                        'submit',
                        function(event) {


                            /*
                            |--------------------------------------------------------------------------
                            | RTO
                            |--------------------------------------------------------------------------
                            */

                            if (
                                rtoCheckbox &&
                                rtoCheckbox.checked
                            ) {

                                return;

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | SELECTED STAFF
                            |--------------------------------------------------------------------------
                            */

                            const selectedStaff =
                                staffContainerInputs(
                                    'input[name="staff_ids[]"]:checked'
                                );


                            if (
                                selectedStaff.length === 0
                            ) {

                                event.preventDefault();

                                alert(
                                    'Please select at least one staff.'
                                );

                                return;

                            }


                            /*
                            |--------------------------------------------------------------------------
                            | TOTAL
                            |--------------------------------------------------------------------------
                            */

                            let total =
                                0;


                            staffContainerInputs(
                                    'input[name^="staff_percentages"]'
                                )
                                .forEach(
                                    function(input) {

                                        total +=
                                            num(
                                                input.value
                                            );

                                    }
                                );


                            total =
                                Math.round(
                                    total * 100
                                ) / 100;


                            if (
                                Math.abs(
                                    total - 100
                                ) > 0.01
                            ) {

                                event.preventDefault();

                                alert(
                                    'Staff percentage must be exactly 100%. Current: ' +
                                    formatPercent(total) +
                                    '%'
                                );

                                return;

                            }

                        }
                    );

                }



                /*
                |--------------------------------------------------------------------------
                | INITIAL
                |--------------------------------------------------------------------------
                */

                updateRtoUI();

                calculateTotal();

                updateModeUI();

            }
        );
    </script>

@endsection
