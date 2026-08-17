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

                <div>

                    <strong>
                        {{ $from->format('d-m-Y') }}
                    </strong>

                    to

                    <strong>
                        {{ $to->format('d-m-Y') }}
                    </strong>

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

                                            {{ $order->customer_phone ?: '-' }}

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
        </script>
        <script>
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
