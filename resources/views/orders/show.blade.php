@extends('layouts.admin')

@section('content')
    <div class="container-fluid py-4">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h3 class="fw-bold mb-1">
                    Order Details
                </h3>

                <div class="text-muted">
                    Order #{{ $order->order_id }}
                </div>
            </div>

            <div class="d-flex gap-2">

                <a href="{{ route('orders.list') }}" class="btn btn-outline-secondary">

                    <i class="fas fa-arrow-left me-1"></i>
                    Back

                </a>

                <a href="{{ route('orders.edit', $order->id) }}" class="btn btn-primary">

                    <i class="fas fa-edit me-1"></i>
                    Edit Order

                </a>

            </div>

        </div>


        <div class="row g-4">

            {{-- LEFT SIDE --}}
            <div class="col-xl-8">

                {{-- CUSTOMER --}}
                <div class="card order-card mb-4">

                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-user text-primary me-2"></i>
                            Customer Information
                        </h5>
                    </div>

                    <div class="card-body">

                        <div class="row g-4">

                            <div class="col-md-6">
                                <label>Customer Name</label>
                                <div class="detail-value">
                                    {{ $order->customer_name ?: '-' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label>Father / Company</label>
                                <div class="detail-value">
                                    {{ $order->father_name ?: '-' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label>Phone</label>

                                <div class="detail-value">

                                    @if ($order->customer_phone)
                                        <a href="tel:{{ $order->customer_phone }}">
                                            {{ $order->customer_phone }}
                                        </a>
                                    @else
                                        -
                                    @endif

                                </div>
                            </div>

                            <div class="col-md-6">
                                <label>Pincode</label>
                                <div class="detail-value">
                                    {{ $order->pincode ?: '-' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label>City</label>
                                <div class="detail-value">
                                    {{ $order->city ?: '-' }}
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label>State</label>
                                <div class="detail-value">
                                    {{ $order->state ?: '-' }}
                                </div>
                            </div>

                            <div class="col-12">
                                <label>Shipping Address</label>

                                <div class="detail-value">
                                    {{ $order->shipping_address ?: '-' }}
                                </div>
                            </div>

                        </div>

                    </div>

                </div>


                {{-- PRODUCT --}}
                <div class="card order-card">

                    <div class="card-header">

                        <h5 class="mb-0">
                            <i class="fas fa-box text-success me-2"></i>
                            Product Information
                        </h5>

                    </div>

                    <div class="card-body">

                        <div class="row g-4">

                            <div class="col-md-6">

                                <label>Product</label>

                                <div class="detail-value fw-bold">
                                    {{ $order->product ?: '-' }}
                                </div>

                            </div>

                            <div class="col-md-3">

                                <label>Quantity</label>

                                <div class="detail-value">
                                    {{ $order->quantity ?? 1 }}
                                </div>

                            </div>

                            <div class="col-md-3">

                                <label>Weight</label>

                                <div class="detail-value">
                                    {{ $order->weight ?: '-' }}
                                </div>

                            </div>

                            <div class="col-md-6">

                                <label>Amount</label>

                                <div class="detail-value fs-5 fw-bold text-success">

                                    ₹{{ number_format((float) $order->amount, 2) }}

                                </div>

                            </div>

                            <div class="col-md-6">

                                <label>Payment Mode</label>

                                <div class="detail-value">

                                    <span class="badge bg-warning text-dark">

                                        {{ $order->payment_mode ?: 'N/A' }}

                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- RIGHT SIDE --}}
            <div class="col-xl-4">

                <div class="card order-card mb-4">

                    <div class="card-header">

                        <h5 class="mb-0">
                            <i class="fas fa-truck text-info me-2"></i>
                            Delivery
                        </h5>

                    </div>

                    <div class="card-body">

                        <div class="mb-4">

                            <label>Delivery Status</label>

                            <div class="mt-2">

                                @if ($order->delivery_status === 'Delivered')
                                    <span class="badge bg-success fs-6">
                                        Delivered
                                    </span>
                                @elseif($order->delivery_status === 'RTO')
                                    <span class="badge bg-danger fs-6">
                                        RTO
                                    </span>
                                @elseif($order->delivery_status)
                                    <span class="badge bg-info fs-6">
                                        {{ $order->delivery_status }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary fs-6">
                                        No Status
                                    </span>
                                @endif

                            </div>

                        </div>


                        <div class="mb-4">

                            <label>Barcode</label>

                            <div class="detail-value">

                                <i class="fas fa-barcode me-1"></i>

                                {{ $order->barcode ?: '-' }}

                            </div>

                        </div>


                        <div class="mb-4">

                            <label>RTO Received</label>

                            <div class="mt-2">

                                @if ($order->delivery_status === 'RTO' && (int) $order->rtorecivedsts === 1)
                                    <span class="badge bg-success">
                                        Yes - Received
                                    </span>
                                @elseif($order->delivery_status === 'RTO')
                                    <span class="badge bg-warning text-dark">
                                        Pending
                                    </span>
                                @else
                                    <span class="text-muted">
                                        Not Applicable
                                    </span>
                                @endif

                            </div>

                        </div>


                        <div>

                            <label>Delivery Date</label>

                            <div class="detail-value">

                                {{ $order->delivery_date ?: '-' }}

                            </div>

                        </div>

                    </div>

                </div>


                {{-- STAFF --}}

                <div class="card order-card">

                    <div class="card-header">

                        <h5 class="mb-0">
                            <i class="fas fa-headset text-primary me-2"></i>
                            Calling Details
                        </h5>

                    </div>

                    <div class="card-body">

                        <label>Assigned Staff</label>

                        <div class="detail-value">

                            {{ optional(optional($order->callingOrder)->staff)->name ?? 'Unassigned' }}

                        </div>


                        <hr>


                        <label>Order Source</label>

                        <div class="detail-value">

                            @if (optional($order->callingOrder)->order_source)
                                <span class="badge bg-success">
                                    WhatsApp
                                </span>
                            @else
                                <span class="badge bg-primary">
                                    Web
                                </span>
                            @endif

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <style>
        .order-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, .07);
            overflow: hidden;
        }

        .order-card .card-header {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 18px 22px;
        }

        .order-card .card-body {
            padding: 22px;
        }

        .order-card label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 6px;
        }

        .detail-value {
            font-size: 14px;
            font-weight: 600;
            color: #334155;
        }
    </style>
@endsection
