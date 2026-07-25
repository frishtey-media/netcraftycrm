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

                                <option value="">All Sources</option>

                                <option value="web" {{ request('order_source') == 'web' ? 'selected' : '' }}>
                                    Web
                                </option>

                                <option value="whatsapp" {{ request('order_source') == 'whatsapp' ? 'selected' : '' }}>
                                    WhatsApp
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

                    {{-- STAFF --}}
                    <div class="col">
                        <div class="card h-100 border-primary">
                            <div class="card-body text-center">

                                <h6 class="fw-bold">Staff</h6>

                                <h3 class="mb-0">
                                    {{ $staff->name }}
                                </h3>

                            </div>
                        </div>
                    </div>


                    {{-- TOTAL --}}
                    <div class="col">
                        <div class="card h-100 bg-primary text-white">
                            <div class="card-body text-center">

                                <h6 class="fw-bold">Total Orders</h6>

                                <h2>{{ $totalOrders }}</h2>

                                <div class="small">
                                    Web:
                                    <strong>{{ $webOrders }}</strong>

                                    <span class="mx-2">|</span>

                                    WhatsApp:
                                    <strong>{{ $whatsappOrders }}</strong>
                                </div>

                            </div>
                        </div>
                    </div>


                    {{-- PENDING --}}
                    <div class="col">
                        <div class="card h-100 bg-warning">
                            <div class="card-body text-center">

                                <h6 class="fw-bold">Pending</h6>

                                <h2>{{ $pendingOrders }}</h2>

                                <div class="small">
                                    Web:
                                    <strong>{{ $pendingWeb }}</strong>

                                    <span class="mx-2">|</span>

                                    WhatsApp:
                                    <strong>{{ $pendingWhatsapp }}</strong>
                                </div>

                            </div>
                        </div>
                    </div>


                    {{-- VERIFIED --}}
                    <div class="col">
                        <div class="card h-100 bg-success text-white">
                            <div class="card-body text-center">

                                <h6 class="fw-bold">Verified</h6>

                                <h2>{{ $verifiedOrders }}</h2>

                                <div class="small">
                                    Web:
                                    <strong>{{ $verifiedWeb }}</strong>

                                    <span class="mx-2">|</span>

                                    WhatsApp:
                                    <strong>{{ $verifiedWhatsapp }}</strong>
                                </div>

                            </div>
                        </div>
                    </div>


                    {{-- CANCEL --}}
                    <div class="col">
                        <div class="card h-100 bg-danger text-white">
                            <div class="card-body text-center">

                                <h6 class="fw-bold">Cancel</h6>

                                <h2>{{ $cancelOrders }}</h2>

                                <div class="small">
                                    Web:
                                    <strong>{{ $cancelWeb }}</strong>

                                    <span class="mx-2">|</span>

                                    WhatsApp:
                                    <strong>{{ $cancelWhatsapp }}</strong>
                                </div>

                            </div>
                        </div>
                    </div>


                    {{-- NOT REACHABLE --}}
                    <div class="col">
                        <div class="card h-100 bg-dark text-white">
                            <div class="card-body text-center">

                                <h6 class="fw-bold">
                                    Not Reachable
                                </h6>

                                <h2>{{ $notReachableOrders }}</h2>

                                <div class="small">
                                    Web:
                                    <strong>{{ $notReachableWeb }}</strong>

                                    <span class="mx-2">|</span>

                                    WhatsApp:
                                    <strong>{{ $notReachableWhatsapp }}</strong>
                                </div>

                            </div>
                        </div>
                    </div>


                    {{-- SAME ORDER --}}
                    <div class="col">
                        <div class="card h-100 bg-secondary text-white">
                            <div class="card-body text-center">

                                <h6 class="fw-bold">
                                    Same Order
                                </h6>

                                <h2>{{ $sameOrderOrders }}</h2>

                                <div class="small">
                                    Web:
                                    <strong>{{ $sameOrderWeb }}</strong>

                                    <span class="mx-2">|</span>

                                    WhatsApp:
                                    <strong>{{ $sameOrderWhatsapp }}</strong>
                                </div>

                            </div>
                        </div>
                    </div>


                    {{-- OTHER --}}
                    <div class="col">
                        <div class="card h-100 border-info">
                            <div class="card-body text-center">

                                <h6 class="fw-bold">
                                    Other
                                </h6>

                                <h2>{{ $otherOrders }}</h2>

                                <div class="small">
                                    Web:
                                    <strong>{{ $otherWeb }}</strong>

                                    <span class="mx-2">|</span>

                                    WhatsApp:
                                    <strong>{{ $otherWhatsapp }}</strong>
                                </div>

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

                                        @if (strtolower(trim($order->order_source ?? '')) === 'whatsapp')
                                            <span class="badge bg-success">
                                                WhatsApp
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


            </div>

        </div>

    </div>
@endsection
