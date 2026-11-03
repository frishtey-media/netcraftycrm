@extends('layouts.admin')

@section('content')
    <div class="container-fluid">

        {{-- =========================================================
        MAIN CARD
    ========================================================== --}}
        <div class="card shadow-sm">


            {{-- =====================================================
            HEADER
        ====================================================== --}}
            <div
                class="card-header bg-primary text-white
                    d-flex justify-content-between align-items-center">

                <h4 class="mb-0">
                    Orders Report
                </h4>

                <div class="d-flex align-items-center gap-3">

                    <span>
                        <strong>{{ $from->format('d-m-Y') }}</strong>
                        to
                        <strong>{{ $to->format('d-m-Y') }}</strong>
                    </span>

                    <button type="button" class="btn btn-light fw-bold" id="openAllStaffRecordings"
                        data-staff-id="{{ $staff->id }}" data-from="{{ $from->format('Y-m-d') }}"
                        data-to="{{ $to->format('Y-m-d') }}">

                        <i class="fas fa-headphones-alt me-1"></i>
                        All Recordings
                    </button>

                </div>

            </div>



            {{-- =====================================================
            FILTERS
        ====================================================== --}}
            <div class="card-body border-bottom">

                <form method="GET" action="{{ route('performance.orders') }}" id="filterForm">

                    <input type="hidden" name="staff_id" value="{{ $staff->id }}">

                    <div class="row g-3 align-items-end">

                        {{-- DATE FROM --}}
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Date From</label>

                            <input type="date" name="from" class="form-control"
                                value="{{ request('from', $from->format('Y-m-d')) }}">
                        </div>


                        {{-- DATE TO --}}
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Date To</label>

                            <input type="date" name="to" class="form-control"
                                value="{{ request('to', $to->format('Y-m-d')) }}">
                        </div>


                        {{-- ORDER SOURCE --}}
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Order Source</label>

                            <select name="order_source" class="form-select">

                                <option value="">
                                    All Sources
                                </option>

                                <option value="web" {{ request('order_source') == 'web' ? 'selected' : '' }}>
                                    Web
                                </option>

                                <option value="whatsapp" {{ request('order_source') == 'whatsapp' ? 'selected' : '' }}>
                                    WhatsApp
                                </option>

                                <option value="RTO" {{ request('order_source') == 'RTO' ? 'selected' : '' }}>
                                    RTO
                                </option>
                                <option value="deliveredreorder"
                                    {{ request('order_source') == 'deliveredreorder' ? 'selected' : '' }}>
                                    Deliver Re-Order
                                </option>

                                <option value="shopify_abandoned_checkout"
                                    {{ request('order_source') == 'shopify_abandoned_checkout' ? 'selected' : '' }}>
                                    Abandoned Checkout
                                </option>
                            </select>
                        </div>





                        {{-- CALL STATUS --}}
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Call Status</label>

                            <select name="status" class="form-select">

                                <option value="">All Status</option>

                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>
                                    Pending
                                </option>

                                <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>
                                    Verified
                                </option>

                                <option value="cancel" {{ request('status') == 'cancel' ? 'selected' : '' }}>
                                    Cancel
                                </option>

                                <option value="not_reachable" {{ request('status') == 'not_reachable' ? 'selected' : '' }}>
                                    Not Reachable
                                </option>

                                <option value="same_order" {{ request('status') == 'same_order' ? 'selected' : '' }}>
                                    Same Order
                                </option>

                                <option value="other" {{ request('status') == 'other' ? 'selected' : '' }}>
                                    Other
                                </option>

                            </select>
                        </div>


                        {{-- FILTER BUTTON --}}
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">
                                Filter
                            </button>
                        </div>
                        <div class="col-md-1">

                            <a href="{{ route('performance.orders', [
                                'staff_id' => $staff->id,
                            ]) }}"
                                class="btn btn-secondary w-100">

                                Reset

                            </a>

                        </div>
                        <div class="col-md-2">

                            <button type="submit" formaction="{{ route('performance.orders.export') }}"
                                class="btn btn-success w-100">

                                Excel Export

                            </button>

                        </div>
                    </div>



                </form>

            </div>



            {{-- =====================================================
            SUMMARY
        ====================================================== --}}
            <div class="card-body">


                <div class="row g-3">

                    {{-- =========================================================
         STAFF
    ========================================================== --}}
                    <div class="col">
                        <div class="card h-100 border-primary">
                            <div class="card-body text-center">

                                <h6 class="fw-bold">
                                    Staff
                                </h6>

                                <h3 class="mb-0">
                                    {{ $staff->name }}
                                </h3>

                            </div>
                        </div>
                    </div>


                    {{-- =========================================================
         TOTAL ORDERS
    ========================================================== --}}
                    <div class="col">
                        <div class="card h-100 bg-primary text-white">
                            <div class="card-body text-center">

                                <h6 class="fw-bold">
                                    Total Orders
                                </h6>

                                <h2>
                                    {{ $totalOrders }}
                                </h2>

                                <div class="small">

                                    Web:
                                    <strong>
                                        {{ $webOrders }}
                                    </strong>

                                    <span class="mx-2">|</span>

                                    WhatsApp:
                                    <strong>
                                        {{ $whatsappOrders }}
                                    </strong>

                                    <span class="mx-2">|</span>

                                    RTO:
                                    <strong>
                                        {{ $rtoOrders }}
                                    </strong>

                                </div>
                                <small class="d-block">
                                    Deliver Re-Order: {{ $deliveredReorderOrders }}
                                    &nbsp; | &nbsp;
                                    Abandoned: {{ $abandonedOrders }}
                                </small>
                            </div>
                        </div>
                    </div>


                    {{-- =========================================================
         PENDING
    ========================================================== --}}
                    <div class="col">
                        <div class="card h-100 bg-warning">
                            <div class="card-body text-center">

                                <h6 class="fw-bold">
                                    Pending
                                </h6>

                                <h2>
                                    {{ $pendingOrders }}
                                </h2>

                                <div class="small">

                                    Web:
                                    <strong>
                                        {{ $pendingWeb }}
                                    </strong>

                                    <span class="mx-2">|</span>

                                    WhatsApp:
                                    <strong>
                                        {{ $pendingWhatsapp }}
                                    </strong>

                                    <span class="mx-2">|</span>

                                    RTO:
                                    <strong>
                                        {{ $rtoPending }}
                                    </strong>

                                </div>
                                <div class="small mt-1">
                                    Deliver Re-Order:
                                    <strong>{{ $pendingDeliveredReorder }}</strong>
                                    <span class="mx-2">|</span>
                                    Abandoned:
                                    <strong>{{ $pendingAbandoned }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>


                    {{-- =========================================================
         VERIFIED
    ========================================================== --}}
                    <div class="col">
                        <div class="card h-100 bg-success text-white">
                            <div class="card-body text-center">

                                <h6 class="fw-bold">
                                    Verified
                                </h6>

                                <h2>
                                    {{ $verifiedOrders }}
                                </h2>

                                <div class="small">

                                    Web:
                                    <strong>
                                        {{ $verifiedWeb }}
                                    </strong>

                                    <span class="mx-2">|</span>

                                    WhatsApp:
                                    <strong>
                                        {{ $verifiedWhatsapp }}
                                    </strong>

                                    <span class="mx-2">|</span>

                                    RTO:
                                    <strong>
                                        {{ $rtoVerified }}
                                    </strong>

                                </div>
                                <div class="small mt-1">
                                    Deliver Re-Order:
                                    <strong>{{ $verifiedDeliveredReorder }}</strong>
                                    <span class="mx-2">|</span>
                                    Abandoned:
                                    <strong>{{ $verifiedAbandoned }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>


                    {{-- =========================================================
         CANCEL
    ========================================================== --}}
                    <div class="col">
                        <div class="card h-100 bg-danger text-white">
                            <div class="card-body text-center">

                                <h6 class="fw-bold">
                                    Cancel
                                </h6>

                                <h2>
                                    {{ $cancelOrders }}
                                </h2>

                                <div class="small">

                                    Web:
                                    <strong>
                                        {{ $cancelWeb }}
                                    </strong>

                                    <span class="mx-2">|</span>

                                    WhatsApp:
                                    <strong>
                                        {{ $cancelWhatsapp }}
                                    </strong>

                                    <span class="mx-2">|</span>

                                    RTO:
                                    <strong>
                                        {{ $rtoCancel }}
                                    </strong>

                                </div>
                                <div class="small mt-1">
                                    Deliver Re-Order:
                                    <strong>{{ $cancelDeliveredReorder }}</strong>
                                    <span class="mx-2">|</span>
                                    Abandoned:
                                    <strong>{{ $cancelAbandoned }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>


                    {{-- =========================================================
         NOT REACHABLE
    ========================================================== --}}
                    <div class="col">
                        <div class="card h-100 bg-dark text-white">
                            <div class="card-body text-center">

                                <h6 class="fw-bold">
                                    Not Reachable
                                </h6>

                                <h2>
                                    {{ $notReachableOrders }}
                                </h2>

                                <div class="small">

                                    Web:
                                    <strong>
                                        {{ $notReachableWeb }}
                                    </strong>

                                    <span class="mx-2">|</span>

                                    WhatsApp:
                                    <strong>
                                        {{ $notReachableWhatsapp }}
                                    </strong>

                                    <span class="mx-2">|</span>

                                    RTO:
                                    <strong>
                                        {{ $rtoNotReachable }}
                                    </strong>

                                </div>
                                <div class="small mt-1">
                                    Deliver Re-Order:
                                    <strong>{{ $notReachableDeliveredReorder }}</strong>
                                    <span class="mx-2">|</span>
                                    Abandoned:
                                    <strong>{{ $notReachableAbandoned }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>


                    {{-- =========================================================
         SAME ORDER
    ========================================================== --}}
                    <div class="col">
                        <div class="card h-100 bg-secondary text-white">
                            <div class="card-body text-center">

                                <h6 class="fw-bold">
                                    Same Order
                                </h6>

                                <h2>
                                    {{ $sameOrderOrders }}
                                </h2>

                                <div class="small">

                                    Web:
                                    <strong>
                                        {{ $sameOrderWeb }}
                                    </strong>

                                    <span class="mx-2">|</span>

                                    WhatsApp:
                                    <strong>
                                        {{ $sameOrderWhatsapp }}
                                    </strong>

                                    <span class="mx-2">|</span>

                                    RTO:
                                    <strong>
                                        {{ $rtoSameOrder }}
                                    </strong>

                                </div>
                                <div class="small mt-1">
                                    Deliver Re-Order:
                                    <strong>{{ $sameOrderDeliveredReorder }}</strong>
                                    <span class="mx-2">|</span>
                                    Abandoned:
                                    <strong>{{ $sameOrderAbandoned }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>


                    {{-- =========================================================
         OTHER
    ========================================================== --}}
                    <div class="col">
                        <div class="card h-100 border-info">
                            <div class="card-body text-center">

                                <h6 class="fw-bold">
                                    Other
                                </h6>

                                <h2>
                                    {{ $otherOrders }}
                                </h2>

                                <div class="small">

                                    Web:
                                    <strong>
                                        {{ $otherWeb }}
                                    </strong>

                                    <span class="mx-2">|</span>

                                    WhatsApp:
                                    <strong>
                                        {{ $otherWhatsapp }}
                                    </strong>

                                    <span class="mx-2">|</span>

                                    RTO:
                                    <strong>
                                        {{ $rtoOther }}
                                    </strong>

                                </div>
                                <div class="small mt-1">
                                    Deliver Re-Order:
                                    <strong>{{ $otherDeliveredReorder }}</strong>
                                    <span class="mx-2">|</span>
                                    Abandoned:
                                    <strong>{{ $otherAbandoned }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>




                <div class="card-body">

                    {{-- =================================================
                TABLE
            ================================================== --}}
                    <div class="table-responsive">

                        <table class="table table-bordered
                              table-striped align-middle">

                            <thead class="table-dark">

                                <tr>

                                    <th>#</th>

                                    <th>Order ID</th>

                                    <th>Client</th>

                                    <th>Customer</th>

                                    <th>Phone</th>

                                    <th>Product</th>

                                    <th>Qty</th>

                                    <th>Amount</th>

                                    <th>Source</th>

                                    <th>Payment</th>

                                    <th>Status</th>
                                    <th>Shipping Address</th>
                                    <!--  <th>Call Start</th>
                                                                                                                                        <th>Call End</th>
                                                                                                                                        <th>Call Duration</th>
                                                                                                                                        <th>Call Recording</th>-->
                                    <th>Remarks</th>

                                    <th>Updated Date</th>
                                    <th>Edit</th>


                                </tr>

                            </thead>


                            <tbody>

                                @forelse($orders as $order)
                                    <tr>


                                        {{-- SERIAL --}}
                                        <td>

                                            {{ $orders->firstItem() + $loop->index }}

                                        </td>


                                        {{-- ORDER --}}
                                        <td>

                                            <strong>
                                                {{ $order->order_id }}
                                            </strong>

                                        </td>


                                        {{-- CLIENT --}}
                                        <td>

                                            {{ $order->client->client_name ?? 'N/A' }}

                                        </td>


                                        {{-- CUSTOMER --}}
                                        <td>

                                            {{ $order->customer_name ?: '-' }}

                                        </td>


                                        {{-- PHONE --}}
                                        <td>
                                            @if ($order->customer_phone)
                                                <button type="button"
                                                    class="btn btn-link p-0 fw-semibold recordingHistoryBtn"
                                                    data-phone="{{ $order->customer_phone }}"
                                                    data-staff-id="{{ $staff->id }}"
                                                    data-from="{{ request('from', $from->format('Y-m-d')) }}"
                                                    data-to="{{ request('to', $to->format('Y-m-d')) }}">
                                                    {{ $order->customer_phone }}
                                                    <i class="fas fa-headphones-alt ms-1"></i>
                                                </button>
                                            @else
                                                -
                                            @endif
                                        </td>


                                        {{-- PRODUCT --}}
                                        <td>

                                            {{ $order->product_name ?: '-' }}

                                        </td>


                                        {{-- QTY --}}
                                        <td>

                                            {{ $order->quantity ?? 0 }}

                                        </td>


                                        {{-- AMOUNT --}}
                                        <td>

                                            ₹{{ number_format((float) $order->amount, 2) }}

                                        </td>


                                        {{-- SOURCE --}}
                                        <td>
                                            @php
                                                $source = strtolower(trim($order->order_source ?? ''));
                                            @endphp

                                            @if ($source === 'rto')
                                                <span class="badge bg-danger">
                                                    RTO
                                                </span>
                                            @elseif ($source === 'whatsapp')
                                                <span class="badge bg-success">
                                                    WhatsApp
                                                </span>
                                            @elseif ($source === 'deliveredreorder')
                                                <span class="badge bg-warning text-dark">
                                                    Deliver Re-Order
                                                </span>
                                            @elseif ($source === 'shopify_abandoned_checkout')
                                                <span class="badge bg-info text-dark">
                                                    Abandoned Checkout
                                                </span>
                                            @else
                                                <span class="badge bg-primary">
                                                    Web
                                                </span>
                                            @endif

                                        </td>


                                        {{-- PAYMENT --}}
                                        <td>

                                            @php

                                                $payment = strtolower(trim($order->payment_mode ?? ''));

                                            @endphp


                                            @if (in_array($payment, ['prepaid', 'paid']))
                                                <span class="badge bg-success">

                                                    Prepaid

                                                </span>
                                            @elseif(in_array($payment, ['cod', 'vpp']))
                                                <span class="badge bg-warning text-dark">

                                                    COD

                                                </span>
                                            @else
                                                <span class="badge bg-secondary">

                                                    {{ $order->payment_mode ?: '-' }}

                                                </span>
                                            @endif

                                        </td>


                                        {{-- STATUS --}}
                                        <td>

                                            @if ($order->status === 'verified')
                                                <span class="badge bg-success">
                                                    Verified
                                                </span>
                                            @elseif($order->status === 'pending')
                                                <span class="badge bg-warning text-dark">
                                                    Pending
                                                </span>
                                            @elseif($order->status === 'cancel')
                                                <span class="badge bg-danger">
                                                    Cancel
                                                </span>
                                            @elseif($order->status === 'same_order')
                                                <span class="badge bg-secondary">
                                                    Same Order
                                                </span>
                                            @elseif($order->status === 'not_reachable')
                                                <span class="badge bg-dark">
                                                    Not Reachable
                                                </span>
                                            @else
                                                <span class="badge bg-info text-dark">

                                                    Other

                                                </span>
                                            @endif

                                        </td>

                                        <td>

                                            {{ $order->shipping_address ?: '-' }}

                                        </td>
                                        {{-- CALL START --}}
                                        <!--  <td>
                                                                                                                                                    @if ($order->call_started_at)
    {{ \Carbon\Carbon::parse($order->call_started_at)->format('d-m-Y h:i:s A') }}
@else
    -
    @endif
                                                                                                                                                </td>

                                                                                                                                                {{-- CALL END --}}
                                                                                                                                                <td>
                                                                                                                                                    @if ($order->call_ended_at)
    {{ \Carbon\Carbon::parse($order->call_ended_at)->format('d-m-Y h:i:s A') }}
@else
    -
    @endif
                                                                                                                                                </td>

                                                                                                                                                {{-- CALL DURATION --}}
                                                                                                                                                <td>
                                                                                                                                                    @if ($order->call_duration_seconds !== null)
    @php
        $seconds = (int) $order->call_duration_seconds;
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remainingSeconds = $seconds % 60;
    @endphp

                                                                                                                                                        {{ sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds) }}
@else
    -
    @endif
                                                                                                                                                </td>

                                                                                                                                                {{-- RECORDING --}}
                                                                                                                                                {{-- CALL RECORDING - INLINE PLAYER --}}
                                                                                                                                                <td style="min-width: 260px;">
                                                                                                                                                    @if ($order->call_recording_id)
    <audio controls preload="none" style="width: 250px; max-width: 100%;">
                                                                                                                                                            <source
                                                                                                                                                                src="{{ route('admin.call-recordings.play', $order->call_recording_id) }}"
                                                                                                                                                                type="audio/mpeg">
                                                                                                                                                            Your browser does not support audio playback.
                                                                                                                                                        </audio>
@else
    <span class="text-muted">Recording Not Available</span>
    @endif
                                                                                                                                                </td>-->
                                        {{-- REMARKS --}}
                                        <td>

                                            {{ $order->remarks ?: '-' }}

                                        </td>


                                        {{-- DATE --}}
                                        <td>

                                            @if ($order->updated_at)
                                                {{ $order->updated_at->format('d-m-Y h:i A') }}
                                            @else
                                                -
                                            @endif

                                        </td>

                                        <td>

                                            <button type="button" class="btn btn-primary btn-sm editOrderBtn"
                                                data-id="{{ $order->id }}" data-order-id="{{ $order->order_id }}">
                                                Edit
                                            </button>

                                        </td>
                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="13" class="text-center text-danger py-4">

                                            No Orders Found

                                        </td>

                                    </tr>
                                @endforelse

                            </tbody>

                        </table>

                    </div>



                    {{-- =================================================
                PAGINATION
            ================================================== --}}
                    <div class="mt-3">

                        {{ $orders->links() }}

                    </div>

                    <div class="modal fade" id="allStaffRecordingsModal" tabindex="-1" aria-hidden="true">

                        <div class="modal-dialog modal-xl modal-dialog-scrollable">

                            <div class="modal-content">

                                <div class="modal-header bg-primary text-white">

                                    <div>
                                        <h5 class="modal-title">
                                            All Staff Recordings
                                        </h5>

                                        <small id="allRecordingStaffInfo"></small>
                                    </div>

                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal">
                                    </button>

                                </div>

                                <div class="modal-body">

                                    <div class="d-flex justify-content-between mb-3">
                                        <strong id="allRecordingDateRange"></strong>

                                        <span class="badge bg-primary" id="allRecordingCount">
                                            0 Recordings
                                        </span>
                                    </div>

                                    <div id="allRecordingLoading" class="text-center py-5 d-none">

                                        <div class="spinner-border text-primary"></div>
                                        <p class="mt-2">Loading all recordings...</p>

                                    </div>

                                    <div id="allRecordingError" class="alert alert-danger d-none"></div>

                                    <div id="allRecordingEmpty" class="alert alert-warning d-none">
                                        No recordings found for this staff and date range.
                                    </div>

                                    <div id="allRecordingList"></div>

                                    <div class="d-flex justify-content-between align-items-center mt-3">

                                        <button class="btn btn-outline-primary" id="allRecordingPrevious" disabled>
                                            Previous
                                        </button>

                                        <span id="allRecordingPageInfo"></span>

                                        <button class="btn btn-outline-primary" id="allRecordingNext" disabled>
                                            Next
                                        </button>

                                    </div>

                                </div>

                                <div class="modal-footer">

                                    <button class="btn btn-secondary" data-bs-dismiss="modal">
                                        Close
                                    </button>

                                </div>

                            </div>

                        </div>

                    </div>

                    <div class="modal fade" id="recordingHistoryModal" tabindex="-1"
                        aria-labelledby="recordingHistoryModalLabel" aria-hidden="true">

                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">

                                <div class="modal-header bg-primary text-white">
                                    <div>
                                        <h5 class="modal-title" id="recordingHistoryModalLabel">
                                            Call Recording History
                                        </h5>
                                        <small id="recordingCustomerPhone"></small>
                                    </div>

                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal">
                                    </button>
                                </div>

                                <div class="modal-body">

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <strong id="recordingStaffName">Staff</strong>

                                        <span class="badge bg-primary" id="recordingTotal">
                                            0 Recordings
                                        </span>
                                    </div>

                                    <div id="recordingHistoryLoading" class="text-center py-5 d-none">
                                        <div class="spinner-border text-primary"></div>
                                        <p class="mt-2">Loading recordings...</p>
                                    </div>

                                    <div id="recordingHistoryError" class="alert alert-danger d-none"></div>

                                    <div id="recordingHistoryEmpty" class="alert alert-warning d-none">
                                        No recordings found for this customer and date range.
                                    </div>

                                    <div id="recordingHistoryContent"></div>

                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        Close
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- Edit Calling Order Modal -->
                    <div class="modal fade" id="editOrderModal" tabindex="-1" aria-labelledby="editOrderModalLabel"
                        aria-hidden="true">

                        <div class="modal-dialog modal-xl">
                            <div class="modal-content">

                                <div class="modal-header">
                                    <h5 class="modal-title" id="editOrderModalLabel">
                                        Edit Order
                                    </h5>

                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                                    </button>
                                </div>

                                <form id="editOrderForm">

                                    @csrf

                                    <input type="hidden" id="edit_order_id" name="id">

                                    <div class="modal-body">

                                        <div class="row">

                                            <!-- Product -->
                                            <div class="col-md-8 mb-3">
                                                <label class="form-label">Product Name</label>

                                                <input type="text" class="form-control" id="edit_product_name"
                                                    name="product_name">
                                            </div>

                                            <!-- Quantity -->
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Quantity</label>

                                                <input type="number" class="form-control" id="edit_quantity"
                                                    name="quantity" min="1">
                                            </div>


                                            <!-- Customer Name -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Customer Name</label>

                                                <input type="text" class="form-control" id="edit_customer_name"
                                                    name="customer_name">
                                            </div>


                                            <!-- Father Name -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Father Name</label>

                                                <input type="text" class="form-control" id="edit_father_name"
                                                    name="father_name">
                                            </div>


                                            <!-- Phone -->
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Customer Phone</label>

                                                <input type="text" class="form-control" id="edit_customer_phone"
                                                    name="customer_phone">
                                            </div>


                                            <!-- Age -->
                                            <div class="col-md-2 mb-3">
                                                <label class="form-label">Age</label>

                                                <input type="number" class="form-control" id="edit_age"
                                                    name="age">
                                            </div>


                                            <!-- City -->
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">City</label>

                                                <input type="text" class="form-control" id="edit_city"
                                                    name="city">
                                            </div>


                                            <!-- State -->
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">State</label>

                                                <input type="text" class="form-control" id="edit_state"
                                                    name="state">
                                            </div>


                                            <!-- Pincode -->
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Pincode</label>

                                                <input type="text" class="form-control" id="edit_pincode"
                                                    name="pincode">
                                            </div>


                                            <!-- Payment Mode -->
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Payment Mode</label>

                                                <select class="form-control" id="edit_payment_mode" name="payment_mode">

                                                    <option value="">Select</option>
                                                    <option value="cod">COD</option>
                                                    <option value="prepaid">Prepaid</option>
                                                    <option value="abandoned_checkout">
                                                        Abandoned Checkout
                                                    </option>

                                                </select>
                                            </div>


                                            <!-- Amount -->
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Amount</label>

                                                <input type="number" step="0.01" class="form-control"
                                                    id="edit_amount" name="amount">
                                            </div>


                                            <!-- Status -->
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Status</label>

                                                <select class="form-control" id="edit_status" name="status">

                                                    <option value="pending">Pending</option>
                                                    <option value="verified">Verified</option>
                                                    <option value="cancel">Cancel</option>
                                                    <option value="not reachable">Not Reachable</option>
                                                    <option value="same order">Same Order</option>
                                                    <option value="other">Other</option>

                                                </select>
                                            </div>


                                            <!-- Shipping Address -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Shipping Address</label>

                                                <textarea class="form-control" id="edit_shipping_address" name="shipping_address" rows="3"></textarea>
                                            </div>


                                            <!-- Remarks -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Remarks</label>

                                                <textarea class="form-control" id="edit_remarks" name="remarks" rows="3"></textarea>
                                            </div>

                                        </div>

                                        <div id="editOrderError" class="alert alert-danger d-none"></div>

                                    </div>


                                    <div class="modal-footer">

                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                            Close
                                        </button>

                                        <button type="submit" class="btn btn-success" id="updateOrderBtn">
                                            Update Order
                                        </button>

                                    </div>

                                </form>

                            </div>
                        </div>
                    </div>

                </div>

            </div>

        </div>

        <script>
            $(function() {

                let allRecordingStaffId = null;
                let allRecordingFrom = null;
                let allRecordingTo = null;
                let allRecordingPage = 1;
                let allRecordingLastPage = 1;

                const allRecordingsUrl = @json(route('admin.staff-recordings.all'));

                $('#openAllStaffRecordings').on('click', function() {

                    allRecordingStaffId = $(this).data('staff-id');
                    allRecordingFrom = $(this).data('from');
                    allRecordingTo = $(this).data('to');
                    allRecordingPage = 1;

                    $('#allRecordingStaffInfo').text(
                        'Staff: ' + @json($staff->name)
                    );

                    $('#allRecordingDateRange').text(
                        allRecordingFrom + ' to ' + allRecordingTo
                    );

                    bootstrap.Modal.getOrCreateInstance(
                        document.getElementById('allStaffRecordingsModal')
                    ).show();

                    loadAllStaffRecordings(1);
                });


                function loadAllStaffRecordings(page) {

                    allRecordingPage = page;

                    $('#allRecordingLoading').removeClass('d-none');
                    $('#allRecordingList').empty();
                    $('#allRecordingError').addClass('d-none').empty();
                    $('#allRecordingEmpty').addClass('d-none');

                    $('#allRecordingPrevious, #allRecordingNext')
                        .prop('disabled', true);

                    $.ajax({

                        url: allRecordingsUrl,
                        method: 'GET',

                        data: {
                            staff_id: allRecordingStaffId,
                            from: allRecordingFrom,
                            to: allRecordingTo,
                            page: page
                        },

                        success: function(response) {

                            $('#allRecordingLoading').addClass('d-none');

                            const recordings = response.recordings || [];

                            $('#allRecordingCount').text(
                                response.total + ' Recordings'
                            );

                            allRecordingLastPage = response.last_page || 1;

                            $('#allRecordingPageInfo').text(
                                'Page ' + response.current_page +
                                ' of ' + response.last_page
                            );

                            $('#allRecordingPrevious').prop(
                                'disabled',
                                response.current_page <= 1
                            );

                            $('#allRecordingNext').prop(
                                'disabled',
                                response.current_page >= response.last_page
                            );

                            if (!recordings.length) {
                                $('#allRecordingEmpty').removeClass('d-none');
                                return;
                            }

                            recordings.forEach(function(item, index) {

                                const callDate = item.call_started_at ?
                                    new Date(
                                        String(item.call_started_at).replace(' ', 'T')
                                    ).toLocaleString('en-IN') :
                                    '-';

                                const duration = formatAllRecordingDuration(
                                    item.duration_seconds
                                );

                                const uploaded =
                                    String(item.upload_status).toLowerCase() === 'uploaded';

                                const audio = item.recording_url ?
                                    `
        <audio controls preload="metadata"
            style="width:100%;max-width:550px;">
            <source
                src="${escapeAllRecordingHtml(item.recording_url)}"
                type="${escapeAllRecordingHtml(item.recording_mime || 'audio/mpeg')}">
            Your browser does not support audio playback.
        </audio>
    ` :
                                    `<span class="badge bg-warning text-dark">
        ${escapeAllRecordingHtml(item.upload_status || 'Not uploaded')}
       </span>`;

                                const html = `
                        <div class="card mb-3 shadow-sm">
                            <div class="card-body">

                                <div class="row g-3">

                                    <div class="col-md-1">
                                        <strong>#${(page - 1) * 100 + index + 1}</strong>
                                    </div>

                                    <div class="col-md-3">
                                        <strong>Customer Phone</strong>
                                        <div>
                                            ${escapeAllRecordingHtml(item.customer_phone || '-')}
                                        </div>
                                    </div>

                                    <div class="col-md-6">

                                        <div>
                                            ${audio}
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <strong>Date Time</strong>
                                        <div>
                                            ${escapeAllRecordingHtml(callDate)}
                                        </div>
                                    </div>





                                </div>

                                <hr>



                                ${item.upload_error ? `
                                                                                                                                                                                                                                                            <div class="alert alert-danger mt-3 mb-0">
                                                                                                                                                                                                                                                                ${escapeAllRecordingHtml(item.upload_error)}
                                                                                                                                                                                                                                                            </div>
                                                                                                                                                                                                                                                        ` : ''}

                            </div>
                        </div>
                    `;

                                $('#allRecordingList').append(html);
                            });
                        },

                        error: function(xhr) {

                            $('#allRecordingLoading').addClass('d-none');

                            $('#allRecordingError')
                                .removeClass('d-none')
                                .text(
                                    xhr.responseJSON?.message ||
                                    'Unable to load staff recordings.'
                                );
                        }

                    });
                }


                $('#allRecordingPrevious').on('click', function() {

                    if (allRecordingPage > 1) {
                        loadAllStaffRecordings(allRecordingPage - 1);
                    }

                });


                $('#allRecordingNext').on('click', function() {

                    if (allRecordingPage < allRecordingLastPage) {
                        loadAllStaffRecordings(allRecordingPage + 1);
                    }

                });


                function formatAllRecordingDuration(seconds) {

                    if (seconds === null || seconds === undefined) {
                        return '-';
                    }

                    seconds = parseInt(seconds, 10) || 0;

                    const hours = Math.floor(seconds / 3600);
                    const minutes = Math.floor((seconds % 3600) / 60);
                    const remaining = seconds % 60;

                    return [
                        String(hours).padStart(2, '0'),
                        String(minutes).padStart(2, '0'),
                        String(remaining).padStart(2, '0')
                    ].join(':');
                }


                function escapeAllRecordingHtml(value) {

                    return String(value ?? '').replace(/[&<>"']/g, function(char) {

                        return {
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;',
                            '"': '&quot;',
                            "'": '&#039;'
                        } [char];

                    });
                }

            });
        </script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {

                document.addEventListener('click', async function(e) {

                    const button = e.target.closest('.recordingHistoryBtn');
                    if (!button) return;

                    e.preventDefault();

                    const phone = button.getAttribute('data-phone');
                    const staffId = button.getAttribute('data-staff-id');
                    const from = button.getAttribute('data-from');
                    const to = button.getAttribute('data-to');

                    console.log('Recording History Click:', {
                        phone,
                        staffId,
                        from,
                        to
                    });

                    const modalElement = document.getElementById('recordingHistoryModal');

                    if (!modalElement || !window.bootstrap) {
                        console.error('Modal or Bootstrap JS missing');
                        alert('Recording modal initialization failed.');
                        return;
                    }

                    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

                    const content = document.getElementById('recordingHistoryContent');
                    const loading = document.getElementById('recordingHistoryLoading');
                    const errorBox = document.getElementById('recordingHistoryError');
                    const emptyBox = document.getElementById('recordingHistoryEmpty');

                    document.getElementById('recordingCustomerPhone').textContent =
                        'Phone: ' + phone;

                    document.getElementById('recordingStaffName').textContent =
                        'Staff: ' + @json($staff->name);

                    document.getElementById('recordingTotal').textContent = 'Loading...';

                    content.innerHTML = '';
                    errorBox.classList.add('d-none');
                    emptyBox.classList.add('d-none');
                    loading.classList.remove('d-none');

                    modal.show();

                    try {

                        const url = new URL(
                            @json(route('admin.staff-recordings.history')),
                            window.location.origin
                        );

                        url.searchParams.set('staff_id', staffId);
                        url.searchParams.set('customer_phone', phone);
                        url.searchParams.set('from', from);
                        url.searchParams.set('to', to);

                        const response = await fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'API request failed');
                        }

                        loading.classList.add('d-none');

                        const recordings = data.recordings || [];

                        document.getElementById('recordingTotal').textContent =
                            recordings.length + ' Recordings';

                        if (!recordings.length) {
                            emptyBox.classList.remove('d-none');
                            return;
                        }

                        recordings.forEach(function(item, index) {

                            const card = document.createElement('div');
                            card.className = 'card mb-3 shadow-sm';

                            const body = document.createElement('div');
                            body.className = 'card-body';

                            const heading = document.createElement('h6');
                            heading.textContent = 'Call #' + (index + 1);

                            const date = document.createElement('p');
                            date.textContent = 'Call Date: ' +
                                (item.call_started_at || item.created_at || '-');

                            const duration = document.createElement('p');
                            duration.textContent = 'Duration: ' +
                                (item.duration_seconds ?? '-') + ' seconds';

                            const status = document.createElement('p');
                            status.textContent = 'Status: ' +
                                (item.upload_status || '-');

                            body.append(heading, date, duration, status);

                            if (item.recording_url) {

                                const audio = document.createElement('audio');
                                audio.controls = true;
                                audio.preload = 'metadata';
                                audio.style.width = '100%';

                                const source = document.createElement('source');
                                source.src = item.recording_url;
                                source.type = item.recording_mime || 'audio/mpeg';

                                audio.appendChild(source);
                                body.appendChild(audio);

                            } else {

                                const unavailable = document.createElement('p');
                                unavailable.className = 'text-warning';
                                unavailable.textContent =
                                    item.upload_error || 'Recording not available';

                                body.appendChild(unavailable);
                            }

                            card.appendChild(body);
                            content.appendChild(card);
                        });

                    } catch (error) {

                        console.error('Recording History API Error:', error);

                        loading.classList.add('d-none');
                        errorBox.textContent = error.message;
                        errorBox.classList.remove('d-none');
                    }
                });

            });


            $(document).ready(function() {

                /*
                |--------------------------------------------------------------------------
                | OPEN EDIT MODAL
                |--------------------------------------------------------------------------
                */

                $(document).on('click', '.editOrderBtn', function() {

                    let orderId = $(this).data('id');

                    $('#editOrderError')
                        .addClass('d-none')
                        .html('');

                    $.ajax({

                        url: "{{ url('/calling-orders') }}/" + orderId + "/edit",

                        type: "GET",

                        success: function(response) {

                            /*
                            |--------------------------------------------------------------------------
                            | Fill Form
                            |--------------------------------------------------------------------------
                            */

                            $('#edit_order_id').val(response.id);

                            $('#edit_product_name').val(response.product_name ?? '');
                            $('#edit_quantity').val(response.quantity ?? '');

                            $('#edit_customer_name').val(response.customer_name ?? '');
                            $('#edit_father_name').val(response.father_name ?? '');

                            $('#edit_customer_phone').val(
                                response.customer_phone ?? ''
                            );

                            $('#edit_age').val(response.age ?? '');

                            $('#edit_shipping_address').val(
                                response.shipping_address ?? ''
                            );

                            $('#edit_city').val(response.city ?? '');
                            $('#edit_state').val(response.state ?? '');
                            $('#edit_pincode').val(response.pincode ?? '');

                            $('#edit_payment_mode').val(
                                response.payment_mode ?? ''
                            );

                            $('#edit_amount').val(response.amount ?? '');

                            $('#edit_status').val(
                                response.status ?? 'pending'
                            );

                            $('#edit_remarks').val(
                                response.remarks ?? ''
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | Show Modal
                            |--------------------------------------------------------------------------
                            */

                            let modalElement =
                                document.getElementById('editOrderModal');

                            let modal =
                                bootstrap.Modal.getOrCreateInstance(
                                    modalElement
                                );

                            modal.show();

                        },

                        error: function(xhr) {

                            let message =
                                xhr.responseJSON?.message ||
                                'Unable to load order.';

                            alert(message);
                        }

                    });

                });


                /*
                |--------------------------------------------------------------------------
                | UPDATE ORDER
                |--------------------------------------------------------------------------
                */

                $('#editOrderForm').on('submit', function(e) {

                    e.preventDefault();

                    let form = $(this);

                    let orderId =
                        $('#edit_order_id').val();

                    if (!orderId) {

                        $('#editOrderError')
                            .removeClass('d-none')
                            .html('Order ID is missing.');

                        return;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Disable Button
                    |--------------------------------------------------------------------------
                    */

                    $('#updateOrderBtn')
                        .prop('disabled', true)
                        .text('Updating...');


                    /*
                    |--------------------------------------------------------------------------
                    | AJAX UPDATE
                    |--------------------------------------------------------------------------
                    */

                    $.ajax({

                        url: "{{ url('/calling-orders') }}/" +
                            orderId,

                        type: "POST",

                        data: form.serialize() +
                            '&_method=PUT',

                        success: function(response) {

                            /*
                            |--------------------------------------------------------------------------
                            | Enable Button
                            |--------------------------------------------------------------------------
                            */

                            $('#updateOrderBtn')
                                .prop('disabled', false)
                                .text('Update Order');


                            /*
                            |--------------------------------------------------------------------------
                            | Close Modal
                            |--------------------------------------------------------------------------
                            */

                            let modalElement =
                                document.getElementById(
                                    'editOrderModal'
                                );

                            let modal =
                                bootstrap.Modal.getInstance(
                                    modalElement
                                );

                            if (modal) {
                                modal.hide();
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Success
                            |--------------------------------------------------------------------------
                            */

                            alert(
                                response.message ||
                                'Order updated successfully.'
                            );


                            /*
                            |--------------------------------------------------------------------------
                            | Reload
                            |--------------------------------------------------------------------------
                            */

                            location.reload();

                        },

                        error: function(xhr) {

                            $('#updateOrderBtn')
                                .prop('disabled', false)
                                .text('Update Order');


                            /*
                            |--------------------------------------------------------------------------
                            | Validation Errors
                            |--------------------------------------------------------------------------
                            */

                            let message =
                                xhr.responseJSON?.message ||
                                'Unable to update order.';


                            if (
                                xhr.responseJSON?.errors
                            ) {

                                let errors =
                                    xhr.responseJSON.errors;

                                message = '';

                                $.each(
                                    errors,
                                    function(field, messages) {

                                        message +=
                                            messages.join('<br>') +
                                            '<br>';

                                    }
                                );
                            }


                            $('#editOrderError')
                                .removeClass('d-none')
                                .html(message);

                        }

                    });

                });

            });


            document.addEventListener('DOMContentLoaded', function() {

                const existingOrderIds = @json($existingOrderIds);

                document.querySelectorAll('.editOrderBtn').forEach(function(button) {

                    const orderId = String(
                        button.getAttribute('data-order-id') || ''
                    );



                    if (existingOrderIds.includes(orderId)) {

                        button.disabled = true;

                        button.classList.remove('btn-primary');
                        button.classList.add('btn-secondary');

                        button.title =
                            'Edit disabled - Order already exists in orders table';

                    }

                });

            });
        </script>
    @endsection
