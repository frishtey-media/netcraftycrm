@extends('layouts.admin')

@section('content')

    <div class="container-fluid py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h3 class="mb-1">
                    Auto Order Assignment Scheduler
                </h3>

                <p class="text-muted mb-0">
                    Automatically assign pending orders to staff.
                </p>
            </div>

        </div>


        {{-- Success / Error --}}

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif


        {{-- ========================================================= --}}
        {{-- CREATE / EDIT --}}
        {{-- ========================================================= --}}

        <div class="card shadow-sm mb-4">

            <div class="card-header bg-primary text-white">

                <h5 class="mb-0">
                    Create Assignment Scheduler
                </h5>

            </div>


            <div class="card-body">

                <form method="POST" action="{{ route('assignment.scheduler.save') }}">

                    @csrf


                    <input type="hidden" name="scheduler_id" id="scheduler_id">


                    {{-- CLIENT --}}

                    <div class="mb-4">

                        <label class="form-label fw-bold">
                            Client
                        </label>

                        <select name="client_id" class="form-select" required>

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


                    {{-- ORDER TYPES --}}

                    <div class="mb-4">

                        <label class="form-label fw-bold">
                            Order Types
                        </label>

                        <div class="row">

                            <div class="col-md-3">

                                <div class="form-check">

                                    <input class="form-check-input" type="checkbox" name="order_types[]" value="shopify"
                                        id="typeShopify">

                                    <label class="form-check-label" for="typeShopify">
                                        Shopify Orders
                                    </label>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-check">

                                    <input class="form-check-input" type="checkbox" name="order_types[]"
                                        value="abandoned_checkout" id="typeAbandoned">

                                    <label class="form-check-label" for="typeAbandoned">
                                        Abandoned Checkout
                                    </label>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-check">

                                    <input class="form-check-input" type="checkbox" name="order_types[]"
                                        value="deliveredreorder" id="typeReorder">

                                    <label class="form-check-label" for="typeReorder">
                                        Delivered Re-Order
                                    </label>

                                </div>

                            </div>


                            <div class="col-md-3">

                                <div class="form-check">

                                    <input class="form-check-input" type="checkbox" name="order_types[]" value="rto"
                                        id="typeRto">

                                    <label class="form-check-label" for="typeRto">
                                        RTO
                                    </label>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- TIME --}}

                    <div class="row mb-4">

                        <div class="col-md-6">

                            <label class="form-label fw-bold">
                                Start Time
                            </label>

                            <input type="time" name="start_time" value="09:00" class="form-control" required>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label fw-bold">
                                End Time
                            </label>

                            <input type="time" name="end_time" value="17:00" class="form-control" required>

                        </div>

                    </div>


                    {{-- DAYS --}}

                    <div class="mb-4">

                        <label class="form-label fw-bold">
                            Working Days
                        </label>

                        <div class="row">

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


                            @foreach ($days as $value => $label)
                                <div class="col-md-3 mb-2">

                                    <div class="form-check">

                                        <input class="form-check-input" type="checkbox" name="days[]"
                                            value="{{ $value }}" id="day_{{ $value }}"
                                            {{ $value !== 'sunday' ? 'checked' : '' }}>

                                        <label class="form-check-label" for="day_{{ $value }}">
                                            {{ $label }}
                                        </label>

                                    </div>

                                </div>
                            @endforeach

                        </div>

                    </div>


                    {{-- STAFF --}}

                    {{-- STAFF DISTRIBUTION --}}
                    <div class="mb-4" id="staffDistributionSection">

                        <label class="form-label fw-bold">
                            Staff Distribution
                        </label>

                        {{-- NORMAL ORDER TYPES --}}
                        <div id="normalStaffDistribution">

                            <div id="staffContainer">

                                @foreach ($staff as $member)
                                    <div class="row align-items-center mb-2">

                                        <div class="col-md-6">

                                            <div class="form-check">

                                                <input class="form-check-input staff-check" type="checkbox"
                                                    name="staff_ids[]" value="{{ $member->id }}"
                                                    id="staff_{{ $member->id }}">

                                                <label class="form-check-label" for="staff_{{ $member->id }}">
                                                    {{ $member->name }}
                                                </label>

                                            </div>

                                        </div>

                                        <div class="col-md-6">

                                            <div class="input-group">

                                                <input type="number" class="form-control staff-percentage"
                                                    name="staff_percentages[{{ $member->id }}]"
                                                    data-staff-id="{{ $member->id }}" min="0" max="100"
                                                    step="0.01" value="0">

                                                <span class="input-group-text">
                                                    %
                                                </span>

                                            </div>

                                        </div>

                                    </div>
                                @endforeach

                            </div>


                            <div class="mt-3">

                                <strong>
                                    Total Percentage:
                                </strong>

                                <span id="totalPercentage" class="badge bg-danger">
                                    0%
                                </span>

                            </div>

                        </div>


                        {{-- RTO SPECIAL MODE --}}
                        <div id="rtoAssignmentInfo" class="alert alert-info mb-0" style="display:none;">

                            <div class="d-flex align-items-start">

                                <div class="me-3 fs-4">
                                    🔄
                                </div>

                                <div>

                                    <strong>
                                        RTO Auto Assignment
                                    </strong>

                                    <div class="mt-1">
                                        RTO orders will automatically be assigned
                                        to the staff member who handled the original
                                        order.
                                    </div>

                                    <div class="mt-2">
                                        If the original staff member is inactive,
                                        the order will automatically go to the
                                        <strong>next active staff member</strong>.
                                    </div>

                                    <div class="mt-2 small text-muted">
                                        Staff percentage distribution is not required
                                        for RTO.
                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- ON / OFF --}}

                    <div class="mb-4">

                        <div class="form-check form-switch">

                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                id="schedulerActive" checked>

                            <label class="form-check-label fw-bold" for="schedulerActive">
                                Enable Auto Assignment
                            </label>

                        </div>

                    </div>


                    <button type="submit" class="btn btn-primary px-4">
                        Save Scheduler
                    </button>

                </form>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- EXISTING SCHEDULERS --}}
        {{-- ========================================================= --}}

        <div class="card shadow-sm">

            <div class="card-header">

                <h5 class="mb-0">
                    Existing Schedulers
                </h5>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover mb-0">

                        <thead class="table-dark">

                            <tr>

                                <th>Client</th>

                                <th>Order Types</th>

                                <th>Time</th>

                                <th>Staff</th>

                                <th>Status</th>

                                <th width="180">
                                    Action
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($schedulers as $scheduler)
                                <tr>

                                    <td>
                                        {{ $scheduler->client->client_name ?? '-' }}
                                    </td>


                                    <td>

                                        @foreach ($scheduler->order_types ?? [] as $type)
                                            @if ($type === 'shopify')
                                                <span class="badge bg-primary">
                                                    Shopify
                                                </span>
                                            @elseif($type === 'abandoned_checkout')
                                                <span class="badge bg-warning text-dark">
                                                    Abandoned
                                                </span>
                                            @elseif($type === 'deliveredreorder')
                                                <span class="badge bg-success">
                                                    Re-Order
                                                </span>
                                            @elseif($type === 'rto')
                                                <span class="badge bg-danger">
                                                    RTO
                                                </span>
                                            @endif
                                        @endforeach

                                    </td>


                                    <td>

                                        {{ \Carbon\Carbon::parse($scheduler->start_time)->format('h:i A') }}

                                        -

                                        {{ \Carbon\Carbon::parse($scheduler->end_time)->format('h:i A') }}

                                    </td>


                                    <td>

                                        @foreach ($scheduler->staff_assignments ?? [] as $assignment)
                                            @php
                                                $staffMember = $staff->firstWhere('id', $assignment['staff_id']);
                                            @endphp

                                            @if ($staffMember)
                                                <div>

                                                    {{ $staffMember->name }}

                                                    -

                                                    {{ $assignment['percentage'] }}%

                                                </div>
                                            @endif
                                        @endforeach

                                    </td>


                                    <td>

                                        @if ($scheduler->is_active)
                                            <span class="badge bg-success">
                                                ON
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                OFF
                                            </span>
                                        @endif

                                    </td>


                                    <td>

                                        <form method="POST"
                                            action="{{ route('assignment.scheduler.toggle', $scheduler->id) }}"
                                            class="d-inline">

                                            @csrf

                                            <button type="submit"
                                                class="btn btn-sm
                                            {{ $scheduler->is_active ? 'btn-danger' : 'btn-success' }}">

                                                {{ $scheduler->is_active ? 'Turn OFF' : 'Turn ON' }}

                                            </button>

                                        </form>


                                        <form method="POST"
                                            action="{{ route('assignment.scheduler.delete', $scheduler->id) }}"
                                            class="d-inline"
                                            onsubmit="
                                            return confirm(
                                                'Delete this scheduler?'
                                            );
                                        ">

                                            @csrf

                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Delete
                                            </button>

                                        </form>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="6" class="text-center py-4">
                                        No Scheduler Found
                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const rtoCheckbox =
                document.getElementById('typeRto');

            const normalDistribution =
                document.getElementById('normalStaffDistribution');

            const rtoInfo =
                document.getElementById('rtoAssignmentInfo');

            const checks =
                document.querySelectorAll('.staff-check');

            const percentages =
                document.querySelectorAll('.staff-percentage');

            const totalElement =
                document.getElementById('totalPercentage');


            /*
            |--------------------------------------------------------------------------
            | RTO MODE
            |--------------------------------------------------------------------------
            */

            function updateOrderTypeUI() {

                const isRto =
                    rtoCheckbox &&
                    rtoCheckbox.checked;


                if (isRto) {

                    /*
                    |--------------------------------------------------------------------------
                    | RTO
                    |--------------------------------------------------------------------------
                    */

                    normalDistribution.style.display = 'none';

                    rtoInfo.style.display = 'block';


                    /*
                    |--------------------------------------------------------------------------
                    | Disable percentage inputs
                    |--------------------------------------------------------------------------
                    */

                    percentages.forEach(function(input) {

                        input.disabled = true;

                        input.value = 0;

                    });


                    /*
                    |--------------------------------------------------------------------------
                    | Uncheck normal staff
                    |--------------------------------------------------------------------------
                    */

                    checks.forEach(function(checkbox) {

                        checkbox.checked = false;

                        checkbox.disabled = true;

                    });


                    totalElement.innerText = 'N/A';

                    totalElement.className =
                        'badge bg-info';


                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | NORMAL MODE
                    |--------------------------------------------------------------------------
                    */

                    normalDistribution.style.display = 'block';

                    rtoInfo.style.display = 'none';


                    percentages.forEach(function(input) {

                        input.disabled = false;

                    });


                    checks.forEach(function(checkbox) {

                        checkbox.disabled = false;

                    });


                    calculateTotal();

                }

            }


            /*
            |--------------------------------------------------------------------------
            | Calculate Percentage
            |--------------------------------------------------------------------------
            */

            function calculateTotal() {

                let total = 0;


                percentages.forEach(function(input) {

                    const checkbox =
                        document.getElementById(
                            'staff_' +
                            input.dataset.staffId
                        );


                    if (
                        checkbox &&
                        checkbox.checked &&
                        !input.disabled
                    ) {

                        total +=
                            parseFloat(input.value) || 0;

                    }

                });


                totalElement.innerText =
                    total.toFixed(2) + '%';


                if (
                    Math.abs(total - 100) < 0.01
                ) {

                    totalElement.className =
                        'badge bg-success';

                } else {

                    totalElement.className =
                        'badge bg-danger';

                }

            }


            /*
            |--------------------------------------------------------------------------
            | Events
            |--------------------------------------------------------------------------
            */

            if (rtoCheckbox) {

                rtoCheckbox.addEventListener(
                    'change',
                    updateOrderTypeUI
                );

            }


            checks.forEach(function(check) {

                check.addEventListener(
                    'change',
                    calculateTotal
                );

            });


            percentages.forEach(function(input) {

                input.addEventListener(
                    'input',
                    calculateTotal
                );

            });


            /*
            |--------------------------------------------------------------------------
            | Initial State
            |--------------------------------------------------------------------------
            */

            updateOrderTypeUI();

        });
    </script>

@endsection
