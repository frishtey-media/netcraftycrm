@extends('layouts.admin')

@section('content')

    <div class="container-fluid py-4">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

            <div>
                <h3 class="fw-bold mb-1">
                    <i class="fas fa-edit text-primary me-2"></i>
                    Edit Order
                </h3>

                <div class="text-muted">
                    Order #{{ $order->order_id }}
                </div>
            </div>

            <div class="d-flex gap-2">

                <a href="{{ route('orders.show', $order->id) }}" class="btn btn-outline-primary">

                    <i class="fas fa-eye me-1"></i>
                    View Order
                </a>

                <a href="{{ route('orders.list') }}" class="btn btn-outline-secondary">

                    <i class="fas fa-arrow-left me-1"></i>
                    Back
                </a>

            </div>

        </div>


        {{-- SUCCESS --}}
        @if (session('success'))
            <div class="alert alert-success border-0 shadow-sm">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
            </div>
        @endif


        {{-- VALIDATION ERRORS --}}
        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">

                <div class="fw-bold mb-2">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Please correct the following:
                </div>

                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

            </div>
        @endif


        <form method="POST" action="{{ route('orders.update', $order->id) }}" id="orderEditForm">

            @csrf
            @method('PUT')


            <div class="row g-4">

                {{-- =====================================================
                LEFT SIDE
            ====================================================== --}}
                <div class="col-xl-8">


                    {{-- CUSTOMER INFORMATION --}}
                    <div class="card edit-card mb-4">

                        <div class="card-header">

                            <h5 class="mb-0">
                                <i class="fas fa-user text-primary me-2"></i>
                                Customer Information
                            </h5>

                        </div>

                        <div class="card-body">

                            <div class="row g-3">

                                <div class="col-md-6">

                                    <label class="form-label">
                                        Customer Name
                                    </label>

                                    <input type="text" name="customer_name" class="form-control"
                                        value="{{ old('customer_name', $order->customer_name) }}"
                                        placeholder="Customer name">

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        Father / Company Name
                                    </label>

                                    <input type="text" name="father_name" class="form-control"
                                        value="{{ old('father_name', $order->father_name) }}"
                                        placeholder="Father / Company">

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        Phone Number
                                    </label>

                                    <input type="text" name="customer_phone" class="form-control"
                                        value="{{ old('customer_phone', $order->customer_phone) }}"
                                        placeholder="Phone number">

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        Pincode
                                    </label>

                                    <input type="text" name="pincode" class="form-control"
                                        value="{{ old('pincode', $order->pincode) }}" placeholder="Pincode">

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        City
                                    </label>

                                    <input type="text" name="city" class="form-control"
                                        value="{{ old('city', $order->city) }}" placeholder="City">

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        State
                                    </label>

                                    <input type="text" name="state" class="form-control"
                                        value="{{ old('state', $order->state) }}" placeholder="State">

                                </div>


                                <div class="col-12">

                                    <label class="form-label">
                                        Shipping Address
                                    </label>

                                    <textarea name="shipping_address" class="form-control" rows="3" placeholder="Complete shipping address">{{ old('shipping_address', $order->shipping_address) }}</textarea>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- PRODUCT INFORMATION --}}
                    <div class="card edit-card mb-4">

                        <div class="card-header">

                            <h5 class="mb-0">
                                <i class="fas fa-box text-success me-2"></i>
                                Product & Payment
                            </h5>

                        </div>

                        <div class="card-body">

                            <div class="row g-3">

                                <div class="col-md-8">

                                    <label class="form-label">
                                        Product
                                    </label>

                                    <input type="text" name="product" class="form-control"
                                        value="{{ old('product', $order->product) }}" placeholder="Product name">

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Quantity
                                    </label>

                                    <input type="number" name="quantity" min="1" class="form-control"
                                        value="{{ old('quantity', $order->quantity ?? 1) }}">

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Weight
                                    </label>

                                    <input type="text" name="weight" class="form-control"
                                        value="{{ old('weight', $order->weight) }}" placeholder="Weight">

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Payment Mode
                                    </label>

                                    @php
                                        $paymentMode = old('payment_mode', $order->payment_mode);
                                    @endphp

                                    <select name="payment_mode" class="form-select">

                                        <option value="">
                                            Select Payment
                                        </option>

                                        <option value="COD"
                                            {{ strtoupper($paymentMode ?? '') === 'COD' ? 'selected' : '' }}>
                                            COD
                                        </option>

                                        <option value="Prepaid"
                                            {{ strtolower($paymentMode ?? '') === 'prepaid' ? 'selected' : '' }}>
                                            Prepaid
                                        </option>

                                    </select>

                                </div>


                                <div class="col-md-4">

                                    <label class="form-label">
                                        Amount
                                    </label>

                                    <div class="input-group">

                                        <span class="input-group-text">
                                            ₹
                                        </span>

                                        <input type="number" step="0.01" min="0" name="amount"
                                            class="form-control" value="{{ old('amount', $order->amount) }}">

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- DELIVERY INFORMATION --}}
                    <div class="card edit-card">

                        <div class="card-header">

                            <h5 class="mb-0">
                                <i class="fas fa-truck text-info me-2"></i>
                                Delivery Information
                            </h5>

                        </div>

                        <div class="card-body">

                            <div class="row g-3">

                                <div class="col-md-6">

                                    <label class="form-label">
                                        Barcode
                                    </label>

                                    <input type="text" name="barcode" class="form-control"
                                        value="{{ old('barcode', $order->barcode) }}" placeholder="Barcode">

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        Delivery Status
                                    </label>

                                    @php
                                        $currentStatus = old('delivery_status', $order->delivery_status);
                                    @endphp

                                    <select name="delivery_status" id="delivery_status" class="form-select">

                                        <option value="">
                                            No Status
                                        </option>

                                        @foreach (['In Transit', 'Out For Delivery', 'Delivered', 'RTO'] as $status)
                                            <option value="{{ $status }}"
                                                {{ $currentStatus === $status ? 'selected' : '' }}>

                                                {{ $status }}

                                            </option>
                                        @endforeach

                                    </select>

                                </div>


                                <div class="col-md-6">

                                    <label class="form-label">
                                        Delivery Date
                                    </label>

                                    <input type="date" name="delivery_date" class="form-control"
                                        value="{{ old(
                                            'delivery_date',
                                            $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('Y-m-d') : '',
                                        ) }}">

                                </div>


                                <div class="col-md-6" id="rtoReceivedBox">

                                    <label class="form-label">
                                        RTO Received
                                    </label>

                                    <select name="rtorecivedsts" class="form-select">

                                        <option value="0"
                                            {{ (int) old('rtorecivedsts', $order->rtorecivedsts) === 0 ? 'selected' : '' }}>

                                            Pending

                                        </option>

                                        <option value="1"
                                            {{ (int) old('rtorecivedsts', $order->rtorecivedsts) === 1 ? 'selected' : '' }}>

                                            Received

                                        </option>

                                    </select>

                                </div>


                                <div class="col-12">

                                    <label class="form-label">
                                        Delivery Remark
                                    </label>

                                    <textarea name="delivery_remark" rows="3" class="form-control" placeholder="Delivery remarks">{{ old('delivery_remark', $order->delivery_remark) }}</textarea>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                {{-- =====================================================
                RIGHT SIDE
            ====================================================== --}}
                <div class="col-xl-4">


                    {{-- ORDER INFO --}}
                    <div class="card edit-card mb-4">

                        <div class="card-header">

                            <h5 class="mb-0">
                                <i class="fas fa-receipt text-primary me-2"></i>
                                Order Information
                            </h5>

                        </div>

                        <div class="card-body">

                            <div class="info-row">

                                <span>Order ID</span>

                                <strong>
                                    {{ $order->order_id }}
                                </strong>

                            </div>

                            <div class="info-row">

                                <span>Client</span>

                                <strong>
                                    {{ optional($order->client)->client_name ?? '-' }}
                                </strong>

                            </div>

                            <div class="info-row">

                                <span>Created</span>

                                <strong>
                                    {{ $order->created_at ? $order->created_at->format('d M Y') : '-' }}
                                </strong>

                            </div>

                            <div class="info-row">

                                <span>Current Status</span>

                                <strong>
                                    {{ $order->delivery_status ?: 'No Status' }}
                                </strong>

                            </div>

                        </div>

                    </div>


                    {{-- SAVE CARD --}}
                    <div class="card edit-card sticky-save">

                        <div class="card-body">

                            <h5 class="fw-bold mb-2">
                                Save Changes
                            </h5>

                            <p class="text-muted small">
                                Review the order information before updating.
                            </p>

                            <button type="submit" class="btn btn-primary w-100 mb-2">

                                <i class="fas fa-save me-2"></i>
                                Update Order

                            </button>

                            <a href="{{ route('orders.show', $order->id) }}" class="btn btn-light border w-100">

                                Cancel

                            </a>

                        </div>

                    </div>

                </div>

            </div>

        </form>

    </div>


    <style>
        .edit-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, .07);
            overflow: hidden;
        }

        .edit-card .card-header {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 18px 22px;
        }

        .edit-card .card-body {
            padding: 22px;
        }

        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: #475569;
        }

        .form-control,
        .form-select,
        .input-group-text {
            min-height: 44px;
            border-color: #e2e8f0;
            border-radius: 10px;
        }

        textarea.form-control {
            min-height: auto;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 .2rem rgba(59, 130, 246, .12);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            padding: 13px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-row:last-child {
            border-bottom: 0;
        }

        .info-row span {
            color: #64748b;
            font-size: 13px;
        }

        .info-row strong {
            color: #0f172a;
            text-align: right;
            font-size: 13px;
        }

        .sticky-save {
            position: sticky;
            top: 20px;
        }
    </style>


    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const deliveryStatus =
                document.getElementById('delivery_status');

            const rtoBox =
                document.getElementById('rtoReceivedBox');


            function handleRtoField() {
                if (!deliveryStatus || !rtoBox) {
                    return;
                }

                if (deliveryStatus.value === 'RTO') {

                    rtoBox.style.display = '';

                } else {

                    rtoBox.style.display = 'none';

                    const select =
                        rtoBox.querySelector(
                            'select[name="rtorecivedsts"]'
                        );

                    if (select) {
                        select.value = '0';
                    }
                }
            }


            deliveryStatus.addEventListener(
                'change',
                handleRtoField
            );

            handleRtoField();

        });
    </script>

@endsection
