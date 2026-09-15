@extends('layouts.admin')

@section('content')
    <style>
        /* =========================================
                                                                                                                                   PERFORMANCE FILTER
                                                                                                                                ========================================= */

        .performance-filter {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #ffffff;
        }

        .performance-filter .card-body {
            background: #fff;
            border-radius: 14px;
        }


        /* LABEL */

        .filter-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 7px;
        }


        /* INPUT + SELECT */

        .filter-control {
            height: 48px;
            border-radius: 9px;
            border: 1px solid #d9dee7;
            font-size: 14px;
            color: #374151;
            box-shadow: none !important;
        }

        .filter-control:focus {
            border-color: #3b82f6;
        }


        /* STAFF MULTI SELECT */

        .staff-multi-select {
            width: 100%;
            min-height: 130px;
            height: 130px;

            border-radius: 9px;
            border: 1px solid #d9dee7;

            padding: 6px;

            font-size: 14px;

            background: #fff;

            box-shadow: none !important;
        }


        /* STAFF OPTIONS */

        .staff-multi-select option {
            padding: 8px 10px;
            border-radius: 6px;
            margin-bottom: 2px;
        }


        /* HELP TEXT */

        .staff-help {
            margin-top: 6px;

            font-size: 11px;

            color: #8a94a6;
        }

        .staff-help i {
            margin-right: 3px;
        }


        /* BUTTON AREA */

        .filter-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }


        /* BUTTON */

        .filter-btn {
            height: 48px;

            border-radius: 9px;

            font-size: 14px;
            font-weight: 500;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 0 16px;

            white-space: nowrap;
        }


        /* HOVER */

        .filter-btn {
            transition: all .2s ease;
        }

        .filter-btn:hover {
            transform: translateY(-1px);
        }


        /* DESKTOP */

        @media (min-width: 1200px) {

            .filter-actions {
                justify-content: flex-end;
            }

            .filter-btn {
                flex: 1;
            }

        }


        /* TABLET */

        @media (max-width: 1199px) {

            .filter-actions {
                width: 100%;
            }

            .filter-btn {
                flex: 1;
            }

        }


        /* MOBILE */

        @media (max-width: 576px) {

            .performance-filter .card-body {
                padding: 15px !important;
            }

            .filter-control {
                height: 46px;
            }

            .staff-multi-select {
                height: 140px;
            }

            .filter-actions {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }

            .filter-actions .filter-btn:last-child {
                grid-column: 1 / -1;
            }

        }


        .staff-dropdown {
            position: relative;
            width: 100%;
        }

        .staff-dropdown-btn {
            width: 100%;
            height: 48px;

            background: #fff;
            border: 1px solid #d9dee7;
            border-radius: 9px;

            padding: 0 14px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            font-size: 14px;
            color: #374151;

            cursor: pointer;
        }

        .staff-dropdown-btn:hover {
            border-color: #3b82f6;
        }

        .staff-dropdown-btn i {
            font-size: 12px;
            color: #6b7280;
        }


        /* DROPDOWN */

        .staff-dropdown-menu {
            display: none;

            position: absolute;

            top: calc(100% + 6px);
            left: 0;
            right: 0;

            background: #fff;

            border: 1px solid #e1e5eb;
            border-radius: 10px;

            box-shadow: 0 10px 30px rgba(0, 0, 0, .12);

            z-index: 9999;

            overflow: hidden;
        }

        .staff-dropdown.open .staff-dropdown-menu {
            display: block;
        }


        /* SEARCH */

        .staff-search {
            padding: 10px;

            border-bottom: 1px solid #eee;

            position: relative;
        }

        .staff-search i {
            position: absolute;

            left: 20px;
            top: 20px;

            color: #9ca3af;
        }

        .staff-search input {
            width: 100%;

            height: 38px;

            border: 1px solid #ddd;
            border-radius: 7px;

            padding: 0 10px 0 34px;

            outline: none;

            font-size: 13px;
        }

        .staff-search input:focus {
            border-color: #3b82f6;
        }


        /* STAFF LIST */

        .staff-list {
            max-height: 230px;
            overflow-y: auto;

            padding: 6px;
        }


        /* OPTION */

        .staff-option {
            display: flex;

            align-items: center;

            gap: 10px;

            padding: 9px 10px;

            border-radius: 7px;

            cursor: pointer;

            font-size: 14px;

            margin: 1px 0;
        }

        .staff-option:hover {
            background: #f3f6fa;
        }

        .staff-option input {
            width: 16px;
            height: 16px;

            cursor: pointer;
        }


        .staff-option:has(input:checked) {
            background: #eef5ff;
            color: #1769e0;
            font-weight: 500;
        }

        .staff-dropdown-footer {
            border-top: 1px solid #eee;

            padding: 8px 10px;

            display: flex;

            align-items: center;
            justify-content: space-between;

            background: #fafafa;

            font-size: 12px;

            color: #6b7280;
        }

        .staff-dropdown-footer button {
            padding: 0;
            text-decoration: none;
        }
    </style>
    <div class="container-fluid">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold">📊 Staff Performance Dashboard</h4>

            <div class="d-flex gap-2">
                <a href="?from={{ date('Y-m-d') }}&to={{ date('Y-m-d') }}" class="btn btn-sm btn-primary">Today</a>

                <a href="?from={{ date('Y-m-d', strtotime('-1 day')) }}&to={{ date('Y-m-d', strtotime('-1 day')) }}"
                    class="btn btn-sm btn-outline-secondary">Yesterday</a>

                <a href="?from={{ date('Y-m-d', strtotime('-7 days')) }}&to={{ date('Y-m-d') }}"
                    class="btn btn-sm btn-dark">Last 7 Days</a>
            </div>
        </div>

        <!-- FILTER -->
        {{-- FILTER --}}
        <div class="card shadow-sm mb-4 performance-filter">

            <div class="card-body p-4">

                <form method="GET" action="{{ url()->current() }}" class="row g-3 align-items-end">

                    {{-- FROM --}}
                    <div class="col-xl-2 col-lg-3 col-md-3 col-6">

                        <label class="filter-label">
                            From
                        </label>

                        <input type="date" name="from" value="{{ \Carbon\Carbon::parse($from)->format('Y-m-d') }}"
                            class="form-control filter-control">

                    </div>


                    {{-- TO --}}
                    <div class="col-xl-2 col-lg-3 col-md-3 col-6">

                        <label class="filter-label">
                            To
                        </label>

                        <input type="date" name="to" value="{{ \Carbon\Carbon::parse($to)->format('Y-m-d') }}"
                            class="form-control filter-control">

                    </div>


                    {{-- CLIENT --}}
                    <div class="col-xl-2 col-lg-3 col-md-3 col-12">

                        <label class="filter-label">
                            Client
                        </label>

                        <select name="client_id" class="form-select filter-control" {{ $isClientUser ? 'disabled' : '' }}>

                            @if (!$isClientUser)
                                <option value="">
                                    All Clients
                                </option>
                            @endif

                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}"
                                    {{ (string) $clientId === (string) $client->id ? 'selected' : '' }}>

                                    {{ $client->client_name }}

                                </option>
                            @endforeach

                        </select>

                        @if ($isClientUser)
                            <input type="hidden" name="client_id" value="{{ $clientId }}">
                        @endif

                    </div>


                    {{-- STAFF --}}
                    {{-- STAFF --}}
                    <div class="col-xl-3 col-lg-3 col-md-4 col-12">

                        <label class="filter-label">
                            Staff <span class="text-danger">*</span>
                        </label>

                        <div class="staff-dropdown" id="staffDropdown">

                            {{-- BUTTON --}}
                            <button type="button" class="staff-dropdown-btn" id="staffDropdownBtn">

                                <span id="staffSelectedText">
                                    👥 Select Staff
                                </span>

                                <i class="fas fa-chevron-down"></i>

                            </button>


                            {{-- MENU --}}
                            <div class="staff-dropdown-menu">

                                {{-- SEARCH --}}
                                <div class="staff-search">

                                    <i class="fas fa-search"></i>

                                    <input type="text" id="staffSearch" placeholder="Search staff..." autocomplete="off">

                                </div>


                                {{-- STAFF --}}
                                <div class="staff-list">

                                    @foreach ($allStaff as $staff)
                                        <label class="staff-option">

                                            <input type="checkbox" name="staff_ids[]" value="{{ $staff->id }}"
                                                class="staff-checkbox-filter"
                                                {{ in_array((int) $staff->id, array_map('intval', $staffIds ?? [])) ? 'checked' : '' }}>

                                            <span>
                                                {{ $staff->name }}
                                            </span>

                                        </label>
                                    @endforeach

                                </div>


                                {{-- FOOTER --}}
                                <div class="staff-dropdown-footer">

                                    <span id="staffCount">
                                        0 staff selected
                                    </span>

                                    <button type="button" id="clearStaff" class="btn btn-sm btn-link">
                                        Clear
                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>

                    {{-- ACTION BUTTONS --}}
                    <div class="col-xl-3 col-lg-12 col-md-12 col-12">

                        <div class="filter-actions">

                            {{-- APPLY --}}
                            <button type="submit" class="btn btn-primary filter-btn">

                                <i class="fas fa-filter me-1"></i>
                                Apply

                            </button>


                            {{-- RESET --}}
                            <a href="{{ url()->current() }}" class="btn btn-secondary filter-btn">

                                <i class="fas fa-undo me-1"></i>
                                Reset

                            </a>


                            {{-- COMPARE --}}
                            <a href="{{ route('admin.day-wise-staff-performance', request()->query()) }}"
                                class="btn btn-success filter-btn">

                                <i class="fas fa-chart-bar me-1"></i>
                                Compare

                            </a>

                        </div>

                    </div>

                </form>

            </div>

        </div>



        <div class="row mb-4">

            <div class="row g-3">

                {{-- TOTAL ORDERS --}}
                <div class="col-md-2 col-6 mb-2">
                    <div class="card bg-primary text-white p-3 text-center h-100">
                        <h6>Total Orders</h6>
                        <h3>{{ $totalOrders }}</h3>

                        <small>
                            Web: {{ $totalWeb }}
                            &nbsp; | &nbsp;
                            WhatsApp: {{ $totalWhatsapp }}
                        </small>

                        <small class="d-block">
                            RTO: {{ $totalRtoAll }}
                            &nbsp; | &nbsp;
                            Deliver Re-Order: {{ $totalDeliveredReorder }}
                        </small>

                        <small class="d-block">
                            Abandoned: {{ $totalAbandoned }}
                        </small>
                    </div>
                </div>


                {{-- PENDING --}}
                <div class="col-md-2 col-6 mb-2">
                    <div class="card bg-warning text-dark p-3 text-center h-100">
                        <h6>Pending</h6>
                        <h3>{{ $totalPending }}</h3>

                        <small>
                            Web: {{ $pendingWeb }}
                            &nbsp; | &nbsp;
                            WhatsApp: {{ $pendingWhatsapp }}
                        </small>

                        <small class="d-block">
                            RTO: {{ $pendingRto }}
                            &nbsp; | &nbsp;
                            Deliver Re-Order: {{ $pendingDeliveredReorder }}
                        </small>
                        <small class="d-block">
                            Abandoned: {{ $pendingAbandoned }}
                        </small>
                    </div>
                </div>


                {{-- VERIFIED --}}
                <div class="col-md-2 col-6 mb-2">
                    <div class="card bg-success text-white p-3 text-center h-100">
                        <h6>Verified</h6>
                        <h3>{{ $totalVerified }}</h3>

                        <small>
                            Web: {{ $verifiedWeb }}
                            &nbsp; | &nbsp;
                            WhatsApp: {{ $verifiedWhatsapp }}
                        </small>

                        <small class="d-block">
                            RTO: {{ $verifiedRto }}
                            &nbsp; | &nbsp;
                            Deliver Re-Order: {{ $verifiedDeliveredReorder }}
                        </small>
                        <small class="d-block">
                            Abandoned: {{ $verifiedAbandoned }}
                        </small>
                    </div>
                </div>


                {{-- CANCEL --}}
                <div class="col-md-2 col-6 mb-2">
                    <div class="card bg-danger text-white p-3 text-center h-100">
                        <h6>Cancel</h6>
                        <h3>{{ $totalCancel }}</h3>

                        <small>
                            Web: {{ $cancelWeb }}
                            &nbsp; | &nbsp;
                            WhatsApp: {{ $cancelWhatsapp }}
                        </small>

                        <small class="d-block">
                            RTO: {{ $cancelRto }}
                            &nbsp; | &nbsp;
                            Deliver Re-Order: {{ $cancelDeliveredReorder }}
                        </small>
                        <small class="d-block">
                            Abandoned: {{ $cancelAbandoned }}
                        </small>
                    </div>
                </div>


                {{-- NOT REACHABLE --}}
                <div class="col-md-2 col-6 mb-2">
                    <div class="card bg-dark text-white p-3 text-center h-100">
                        <h6>Not Reachable</h6>
                        <h3>{{ $totalNotReachable }}</h3>

                        <small>
                            Web: {{ $notReachableWeb }}
                            &nbsp; | &nbsp;
                            WhatsApp: {{ $notReachableWhatsapp }}
                        </small>

                        <small class="d-block">
                            RTO: {{ $notReachableRto }}
                            &nbsp; | &nbsp;
                            Deliver Re-Order: {{ $notReachableDeliveredReorder }}
                        </small>
                        <small class="d-block">
                            Abandoned: {{ $notReachableAbandoned }}
                        </small>
                    </div>
                </div>


                {{-- SAME ORDER --}}
                <div class="col-md-2 col-6 mb-2">
                    <div class="card bg-secondary text-white p-3 text-center h-100">
                        <h6>Same Order</h6>
                        <h3>{{ $totalSameOrder }}</h3>

                        <small>
                            Web: {{ $sameOrderWeb }}
                            &nbsp; | &nbsp;
                            WhatsApp: {{ $sameOrderWhatsapp }}
                        </small>

                        <small class="d-block">
                            RTO: {{ $sameOrderRto }}
                            &nbsp; | &nbsp;
                            Deliver Re-Order: {{ $sameOrderDeliveredReorder }}
                        </small>
                        <small class="d-block">
                            Abandoned: {{ $sameOrderAbandoned }}
                        </small>
                    </div>
                </div>


                {{-- OTHER --}}
                <div class="col-md-2 col-6 mb-2">
                    <div class="card bg-white border-primary p-3 text-center h-100">
                        <h6>Other</h6>
                        <h3>{{ $totalOther }}</h3>

                        <small>
                            Web: {{ $otherWeb }}
                            &nbsp; | &nbsp;
                            WhatsApp: {{ $otherWhatsapp }}
                        </small>

                        <small class="d-block">
                            RTO: {{ $otherRto }}
                            &nbsp; | &nbsp;
                            Deliver Re-Order: {{ $otherDeliveredReorder }}
                        </small>
                        <small class="d-block">
                            Abandoned: {{ $otherAbandoned }}
                        </small>
                    </div>
                </div>

            </div>
            <!--  <div class="col-md-2 col-6 mb-2">
                                                                                                                                                                                                                                                                                                                                                                                                    <div class="card bg-dark text-white p-3">
                                                                                                                                                                                                                                                                                                                                                                                                        <h6>WA Leads</h6>
                                                                                                                                                                                                                                                                                                                                                                                                        <h3>{{ $totalWA }}</h3>
                                                                                                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                                                                                                </div>

                                                                                                                                                                                                                                                                                                                                                                                                <div class="col-md-2 col-6 mb-2">
                                                                                                                                                                                                                                                                                                                                                                                                    <div class="card bg-info text-white p-3">
                                                                                                                                                                                                                                                                                                                                                                                                        <h6>WA Verified</h6>
                                                                                                                                                                                                                                                                                                                                                                                                        <h3>{{ $verifiedWA }}</h3>
                                                                                                                                                                                                                                                                                                                                                                                                    </div>
                                                                                                                                                                                                                                                                                                                                                                                                </div>-->

        </div>

        <!-- TABLE -->
        <div class="card shadow-sm">
            <div class="card-header fw-bold">Staff Report</div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th width="50">

                                <input type="checkbox" id="checkAll">

                            </th>

                            <th>Staff</th>
                            <th>Clients</th>
                            <th>Total</th>
                            <th>Web Verified</th>
                            <th>WA Verified</th>
                            <th>RTO Verified</th>
                            <th>Re-Order Verified</th>

                            <th>Abandoned Verified</th>
                            <th>Pending</th>
                            <!-- <th>RTO</th>-->
                            <th>Not Reachable</th>
                            <th>Cancel</th>
                            <th>Same Order</th>
                            <th>Other</th>
                            <!--  <th>WA Total</th>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              <th>WA Verified</th>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               <th>WA Pending</th>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                               <th>Combined %</th>-->
                            <th>Order %</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($staffs as $staff)
                            @php
                                $success =
                                    $staff->total_orders > 0
                                        ? round(
                                            (($staff->web_verified_orders + $staff->whatsapp_verified_orders) /
                                                max($staff->total_orders, 1)) *
                                                100,
                                            1,
                                        )
                                        : 0;

                                $combinedTotal = $staff->total_orders + $staff->wa_total;
                                $combinedVerified =
                                    $staff->web_verified_orders +
                                    $staff->whatsapp_verified_orders +
                                    ($staff->wa_verified ?? 0);

                                $combinedRate =
                                    $combinedTotal > 0 ? round(($combinedVerified / $combinedTotal) * 100, 1) : 0;
                            @endphp

                            <td>
                                <input type="checkbox" class="staff-checkbox" value="{{ $staff->id }}">
                            </td>

                            <td>

                                {{ $staff->name }}

                            </td>
                            <td>
                                @if (isset($clientWise[$staff->id]))
                                    @foreach ($clientWise[$staff->id] as $c)
                                        <div class="badge bg-light text-dark mb-1">
                                            {{ $c['client'] }} ({{ $c['total'] }})
                                        </div>
                                    @endforeach
                                @endif
                            </td>

                            <td>
                                <a href="{{ route('performance.orders', [
                                    'staff_id' => $staff->id,
                                    'from' => request('from'),
                                    'to' => request('to'),
                                ]) }}"
                                    class="badge bg-secondary text-decoration-none">
                                    {{ $staff->total_orders }}
                                </a>
                            </td>

                            <!-- WEB VERIFIED -->
                            <td>

                                <a href="{{ route('admin.staff.verified', [
                                    'staff_id' => $staff->id,
                                    'type' => 'web',
                                    'from' => request('from'),
                                    'to' => request('to'),
                                ]) }}"
                                    class="badge bg-success text-decoration-none">

                                    {{ $staff->web_verified_orders }}

                                </a>

                            </td>

                            <!-- WHATSAPP VERIFIED -->
                            <td>

                                <a href="{{ route('admin.staff.verified', [
                                    'staff_id' => $staff->id,
                                    'type' => 'whatsapp',
                                    'from' => request('from'),
                                    'to' => request('to'),
                                ]) }}"
                                    class="badge bg-primary text-decoration-none">

                                    {{ $staff->whatsapp_verified_orders }}

                                </a>

                            </td>
                            <td>
                                <span class="badge bg-success">
                                    {{ $staff->rto_verified_orders }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-success">
                                    {{ $staff->delivered_reorder_orders }}
                                </span>
                            </td>

                            <td>
                                <span class="badge bg-success">
                                    {{ $staff->abandoned_verified ?? 0 }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-warning text-dark" style="cursor:pointer"
                                    onclick="openShiftModal({{ $staff->id }})">
                                    {{ $staff->pending_orders }}
                                </span>
                            </td>
                            <!-- <td>
                                                                                                                                                                                                                                                                                                                                                                                                                <span class="badge bg-danger">
                                                                                                                                                                                                                                                                                                                                                                                                                    {{ $staff->rto_orders }}
                                                                                                                                                                                                                                                                                                                                                                                                                </span>
                                                                                                                                                                                                                                                                                                                                                                                                            </td>-->
                            <td><span class="badge bg-danger">{{ $staff->not_reachable_orders }}</span></td>
                            <td><span class="badge bg-danger">{{ $staff->cancel }}</span></td>
                            <td><span class="badge bg-danger">{{ $staff->same_order }}</span></td>
                            <td><span class="badge bg-danger">{{ $staff->other }}</span></td>
                            <!--<td><span class="badge bg-dark">{{ $staff->wa_total ?? 0 }}</span></td>

                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        <td><span class="badge bg-success">{{ $staff->wa_verified ?? 0 }}</span></td>

                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <td><span class="badge bg-warning text-dark">{{ $staff->wa_pending ?? 0 }}</span></td>

                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <td><strong>{{ $combinedRate }}%</strong></td>-->

                            <td><small>{{ $success }}%</small></td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>
            </div>
        </div>
        @if (auth()->user()->role == 'super_admin')
            <div class="modal fade" id="shiftModal" tabindex="-1" aria-hidden="true">

                <div class="modal-dialog">

                    <div class="modal-content p-3">

                        <h4 class="mb-4">
                            Shift Pending Orders
                        </h4>

                        <form method="POST" action="{{ route('shift.orders') }}">

                            @csrf

                            {{-- FROM STAFF --}}
                            <input type="hidden" name="from_staff" id="from_staff">

                            {{-- CURRENT FILTER FROM --}}
                            <input type="hidden" name="filter_from" id="filter_from"
                                value="{{ \Carbon\Carbon::parse($from)->format('Y-m-d') }}">

                            {{-- CURRENT FILTER TO --}}
                            <input type="hidden" name="filter_to" id="filter_to"
                                value="{{ \Carbon\Carbon::parse($to)->format('Y-m-d') }}">


                            {{-- SHIFT TO STAFF --}}
                            <div class="mb-3">

                                <label class="form-label">
                                    Shift To Staff
                                </label>

                                <select name="to_staff" id="to_staff" class="form-control" required>

                                    <option value="">
                                        Select Staff
                                    </option>

                                    @foreach ($allStaff as $s)
                                        <option value="{{ $s->id }}">
                                            {{ $s->name }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>


                            {{-- FROM ORDER SOURCE --}}
                            <div class="mb-3">

                                <label class="form-label">
                                    From Order Source
                                </label>

                                <select name="order_source" id="order_source" class="form-control" required>

                                    <option value="">
                                        Select Staff First
                                    </option>

                                </select>

                            </div>


                            {{-- SHIFT TYPE --}}
                            <div class="mb-3">

                                <label class="form-label d-block">
                                    Shift Type
                                </label>


                                {{-- AS IT IS --}}
                                <div class="form-check mb-3">

                                    <input class="form-check-input" type="radio" name="shift_type" value="same"
                                        id="shift_same" checked>

                                    <label class="form-check-label" for="shift_same">

                                        <strong>
                                            As It Is
                                        </strong>

                                        <small class="text-muted d-block">
                                            Keep original date, source & order ID
                                        </small>

                                    </label>

                                </div>


                                {{-- AS FRESH LEAD --}}
                                <div class="form-check">

                                    <input class="form-check-input" type="radio" name="shift_type" value="fresh"
                                        id="shift_fresh">

                                    <label class="form-check-label" for="shift_fresh">

                                        <strong>
                                            As Fresh Lead
                                        </strong>

                                        <small class="text-muted d-block">
                                            Show as today's fresh lead
                                        </small>

                                    </label>

                                </div>

                            </div>


                            {{-- NEW ORDER SOURCE --}}
                            <div id="fresh_source_box" class="mb-3" style="display:none;">

                                <label class="form-label">
                                    New Order Source
                                </label>

                                <select name="new_order_source" id="new_order_source" class="form-control">

                                    <option value="">
                                        Select New Source
                                    </option>

                                    <option value="whatsapp">
                                        WhatsApp
                                    </option>

                                    <option value="__NULL__">
                                        Web
                                    </option>

                                    <option value="shopify_abandoned_checkout">
                                        Abandoned Checkout
                                    </option>

                                    <option value="RTO">
                                        RTO
                                    </option>

                                    <option value="deliveredreorder">
                                        Delivered Re-Order
                                    </option>

                                </select>

                            </div>


                            {{-- REMARK --}}
                            <div class="mb-3">

                                <label class="form-label">
                                    Remark
                                </label>

                                <textarea name="remark" class="form-control" rows="3" required placeholder="Enter reason for shifting..."></textarea>

                            </div>


                            {{-- SUBMIT --}}
                            <button type="submit" class="btn btn-primary w-100" id="shiftSubmitBtn">

                                Shift Orders

                            </button>

                        </form>

                    </div>

                </div>

            </div>
        @endif
        <script>
            function openShiftModal(staffId) {
                console.log('==============================');
                console.log('SHIFT MODAL OPEN');
                console.log('Staff ID:', staffId);


                /*
                |--------------------------------------------------------------------------
                | GET CURRENT FILTER DATE
                |--------------------------------------------------------------------------
                */

                let filterFrom =
                    $('#filter_from').val();

                let filterTo =
                    $('#filter_to').val();


                console.log('Filter From:', filterFrom);
                console.log('Filter To:', filterTo);


                /*
                |--------------------------------------------------------------------------
                | FROM STAFF
                |--------------------------------------------------------------------------
                */

                $('#from_staff').val(staffId);


                /*
                |--------------------------------------------------------------------------
                | RESET SOURCE
                |--------------------------------------------------------------------------
                */

                $('#order_source').html(
                    '<option value="">Loading...</option>'
                );


                /*
                |--------------------------------------------------------------------------
                | RESET SHIFT TYPE
                |--------------------------------------------------------------------------
                */

                $('#shift_same')
                    .prop('checked', true);

                $('#shift_fresh')
                    .prop('checked', false);


                $('#fresh_source_box')
                    .hide();


                $('#new_order_source')
                    .prop('required', false)
                    .val('');


                /*
                |--------------------------------------------------------------------------
                | RESET REMARK
                |--------------------------------------------------------------------------
                */

                $('textarea[name="remark"]').val('');


                /*
                |--------------------------------------------------------------------------
                | BUTTON
                |--------------------------------------------------------------------------
                */

                $('#shiftSubmitBtn')
                    .text('Loading...');


                /*
                |--------------------------------------------------------------------------
                | OPEN MODAL
                |--------------------------------------------------------------------------
                */

                $('#shiftModal').modal('show');


                /*
                |--------------------------------------------------------------------------
                | AJAX
                |--------------------------------------------------------------------------
                */

                $.ajax({

                    url: "{{ route('shift.order.sources', ['staff' => '__STAFF__']) }}"
                        .replace('__STAFF__', staffId),

                    type: 'GET',

                    data: {

                        from: filterFrom,

                        to: filterTo

                    },

                    dataType: 'json',


                    success: function(response) {
                        console.log(
                            'SHIFT SOURCE RESPONSE:',
                            response
                        );


                        if (
                            !response.success
                        ) {

                            $('#order_source').html(
                                '<option value="">No sources found</option>'
                            );

                            $('#shiftSubmitBtn')
                                .text('Shift Orders');

                            return;
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | CLEAR
                        |--------------------------------------------------------------------------
                        */

                        $('#order_source').empty();


                        /*
                        |--------------------------------------------------------------------------
                        | ALL SOURCES
                        |--------------------------------------------------------------------------
                        */

                        $('#order_source').append(

                            $('<option>', {

                                value: 'all',

                                text: 'All Sources (' +
                                    response.all +
                                    ')'

                            })

                        );


                        /*
                        |--------------------------------------------------------------------------
                        | SOURCE OPTIONS
                        |--------------------------------------------------------------------------
                        */

                        $.each(
                            response.sources,
                            function(index, item) {

                                $('#order_source').append(

                                    $('<option>', {

                                        value: item.order_source,

                                        text: item.name +
                                            ' (' +
                                            item.total +
                                            ')'

                                    })

                                );

                            }
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | DEFAULT ALL
                        |--------------------------------------------------------------------------
                        */

                        $('#order_source')
                            .val('all');


                        updateShiftButton();

                    },


                    error: function(xhr) {
                        console.error(
                            'SHIFT SOURCE AJAX ERROR'
                        );

                        console.error(
                            'HTTP STATUS:',
                            xhr.status
                        );

                        console.error(
                            'RESPONSE:',
                            xhr.responseText
                        );


                        $('#order_source').html(

                            '<option value="">' +
                            'Unable to load sources' +
                            '</option>'

                        );


                        $('#shiftSubmitBtn')
                            .text('Shift Orders');
                    }

                });

            }


            /*
            |--------------------------------------------------------------------------
            | FRESH LEAD SHOW/HIDE
            |--------------------------------------------------------------------------
            */


            $(document).on(
                'change',
                'input[name="shift_type"]',
                function() {

                    if (
                        $(this).val() === 'fresh'
                    ) {

                        $('#fresh_source_box')
                            .slideDown();

                        $('#new_order_source')
                            .prop('required', true);

                    } else {

                        $('#fresh_source_box')
                            .slideUp();

                        $('#new_order_source')
                            .prop('required', false)
                            .val('');

                    }

                    updateShiftButton();

                }
            );


            /*
            |--------------------------------------------------------------------------
            | SOURCE CHANGE
            |--------------------------------------------------------------------------
            */

            $(document).on(
                'change',
                '#order_source',
                function() {
                    updateShiftButton();
                }
            );


            /*
            |--------------------------------------------------------------------------
            | NEW SOURCE CHANGE
            |--------------------------------------------------------------------------
            */

            $(document).on(
                'change',
                '#new_order_source',
                function() {
                    updateShiftButton();
                }
            );


            /*
            |--------------------------------------------------------------------------
            | BUTTON COUNT
            |--------------------------------------------------------------------------
            */

            function updateShiftButton() {
                let selectedText =
                    $('#order_source option:selected')
                    .text();


                let match =
                    selectedText.match(/\((\d+)\)/);


                if (match) {

                    $('#shiftSubmitBtn').text(
                        'Shift ' +
                        match[1] +
                        ' Orders'
                    );

                } else {

                    $('#shiftSubmitBtn').text(
                        'Shift Orders'
                    );
                }
            }

            $(document).ready(function() {

                const dropdown = $('#staffDropdown');
                const button = $('#staffDropdownBtn');

                const selectedText = $('#staffSelectedText');
                const countText = $('#staffCount');


                // OPEN DROPDOWN
                button.on('click', function(e) {

                    e.preventDefault();
                    e.stopPropagation();

                    dropdown.toggleClass('open');

                });


                // DON'T CLOSE INSIDE
                $('.staff-dropdown-menu').on('click', function(e) {

                    e.stopPropagation();

                });


                // CLOSE OUTSIDE
                $(document).on('click', function() {

                    dropdown.removeClass('open');

                });


                // STAFF CHANGE
                $(document).on(
                    'change',
                    '.staff-checkbox-filter',
                    function() {

                        updateStaffText();

                    }
                );


                // UPDATE SELECTED COUNT
                function updateStaffText() {

                    let selected = $('.staff-checkbox-filter:checked');

                    let count = selected.length;

                    if (count === 0) {

                        selectedText.html('👥 Select Staff');

                        countText.text('0 staff selected');

                    } else {

                        selectedText.html(
                            '👥 ' + count + ' Staff Selected'
                        );

                        countText.text(
                            count + ' staff selected'
                        );

                    }

                }


                // SEARCH
                $('#staffSearch').on('keyup', function() {

                    let search = $(this).val().toLowerCase();

                    $('.staff-option').each(function() {

                        let name = $(this)
                            .find('span')
                            .text()
                            .toLowerCase();

                        $(this).toggle(
                            name.includes(search)
                        );

                    });

                });


                // CLEAR
                $('#clearStaff').on('click', function(e) {

                    e.preventDefault();

                    $('.staff-checkbox-filter')
                        .prop('checked', false);

                    updateStaffText();

                });


                // INITIAL LOAD
                updateStaffText();

            });
        </script>
        <script>
            $('#checkAll').on('change', function() {

                $('.staff-checkbox').prop('checked', this.checked);

            });

            $(document).on('change', '.staff-checkbox', function() {

                $('#checkAll').prop(

                    'checked',

                    $('.staff-checkbox').length == $('.staff-checkbox:checked').length

                );

            });







            $('#exportSelected').click(function() {

                let ids = [];

                $('.staff-checkbox:checked').each(function() {
                    ids.push($(this).val());
                });

                if (ids.length == 0) {
                    alert('Please select staff');
                    return;
                }

                let url = "{{ route('performance.export.selected') }}?";

                ids.forEach(function(id) {
                    url += "staff_ids[]=" + id + "&";
                });

                url += "from={{ request('from') }}";
                url += "&to={{ request('to') }}";
                url += "&client_id={{ request('client_id') }}";

                window.location.href = url;
            });
        </script>
    </div>
@endsection
