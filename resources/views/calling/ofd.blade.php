@extends('layouts.calling')

@section('title', 'Out for Delivery')

@section('content')

    <style>
        .order-card {
            border-radius: 12px;
            border: 1px solid #e9ecef;
            background: #fff;
            transition: .2s;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 18px rgba(0, 0, 0, .06);
        }

        .customer-name {
            font-size: 16px;
            font-weight: 600;
            color: #222;
        }

        .label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            margin-top: 6px;
        }

        .value {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }

        .phone {
            color: #0d6efd;
            font-weight: 600;
            text-decoration: none;
        }

        .status-badge {
            font-size: 11px;
            padding: 6px 10px;
            border-radius: 6px;
        }

        .client-scroll {
            display: flex;
            overflow-x: auto;
            gap: 8px;
            padding-bottom: 8px;
        }

        .client-scroll::-webkit-scrollbar {
            display: none;
        }

        .client-chip {
            white-space: nowrap;
            padding: 6px 14px;
            border-radius: 20px;
            background: #f1f1f1;
            color: #333;
            font-size: 13px;
            text-decoration: none;
            border: 1px solid #ddd;
        }

        .client-chip.active {
            background: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        .call-btn {
            min-width: 110px;
        }

        .called-btn {
            min-width: 90px;
        }

        .order-number {
            font-weight: 600;
            color: #333;
        }
    </style>


    {{-- ================= HEADER ================= --}}
    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>
            <h4 class="mb-1">Out for Delivery</h4>

            <small class="text-muted">
                Customers whose orders are currently out for delivery
            </small>
        </div>

        <span class="badge bg-primary status-badge">
            {{ $orders->count() }} Orders
        </span>

    </div>


    {{-- ================= CLIENT FILTER ================= --}}
    @if (isset($clients) && $clients->count())

        <div class="client-scroll mb-3">

            <a href="{{ route('calling.ofd') }}" class="client-chip {{ request('client_id') ? '' : 'active' }}">
                All
            </a>

            @foreach ($clients as $row)
                <a href="{{ route('calling.ofd', ['client_id' => $row->client_id]) }}"
                    class="client-chip {{ request('client_id') == $row->client_id ? 'active' : '' }}">

                    {{ $row->client->client_name ?? 'Client' }}
                    ({{ $row->total }})
                </a>
            @endforeach

        </div>

    @endif


    {{-- ================================================= --}}
    {{-- DESKTOP --}}
    {{-- ================================================= --}}

    <div class="table-responsive d-none d-md-block">

        <table id="ordersTable" class="table table-hover align-middle">

            <thead class="table-dark">

                <tr>
                    <th>#</th>
                    <th>Barcode</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Order Date</th>
                    <th>Delivery Remarks</th>
                    <th>Status</th>
                    <th>Call Status</th>
                    <th>Call</th>
                </tr>

            </thead>

            <tbody>

                @forelse($orders as $order)
                    <tr>

                        {{-- ORDER ID --}}
                        <td>
                            <span class="order-number">
                                #{{ $order->order_id }}
                            </span>
                        </td>

                        <td>
                            <span class="order-barcode">
                                {{ $order->barcode }}
                            </span>
                        </td>

                        {{-- CUSTOMER --}}

                        <td>

                            <div class="customer-name">
                                {{ $order->customer_name }}
                            </div>

                            <small class="text-muted">
                                {{ $order->city }}, {{ $order->state }}
                            </small>

                        </td>


                        {{-- PRODUCT --}}
                        <td>

                            {{ $order->product_name }}

                            @if (!empty($order->quantity))
                                <br>
                                <small class="text-muted">
                                    Qty: {{ $order->quantity }}
                                </small>
                            @endif

                        </td>


                        {{-- PHONE --}}
                        <td>

                            <a href="tel:{{ $order->customer_phone }}" class="phone">

                                📞 {{ $order->customer_phone }}

                            </a>

                        </td>


                        {{-- ADDRESS --}}
                        <td>

                            {{ $order->shipping_address }}

                            <br>

                            <strong>
                                {{ $order->city }},
                                {{ $order->state }}
                                - {{ $order->pincode }}
                            </strong>

                        </td>


                        {{-- ORDER DATE --}}
                        <td>
                            {{ $order->order_date }}
                        </td>
                        <td>
                            {{ $order->delivery_remark }}
                        </td>


                        {{-- STATUS --}}
                        <td>

                            <span class="badge bg-primary status-badge">
                                Out for Delivery
                            </span>

                        </td>

                        <td>
                            @if ($order->called_at)
                                <span class="badge bg-success">
                                    ✓ Called
                                </span>

                                <br>

                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($order->called_at)->format('d-m-Y h:i A') }}
                                </small>
                            @else
                                <span class="badge bg-secondary">
                                    Not Called
                                </span>
                            @endif
                        </td>
                        {{-- CALL --}}
                        <td>

                            <a href="tel:{{ $order->customer_phone }}" class="btn btn-success btn-sm call-btn track-call"
                                data-callingorder-id="{{ $order->id }}" data-order-id="{{ $order->order_id }}"
                                data-phone="{{ $order->customer_phone }}">

                                📞 Call Customer
                            </a>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="8" class="text-center py-5">

                            <div class="text-muted">

                                <div style="font-size:35px;">
                                    📭
                                </div>

                                <strong>
                                    No Out for Delivery Orders
                                </strong>

                                <br>

                                <small>
                                    No assigned OFD customers are available for calling.
                                </small>

                            </div>

                        </td>

                    </tr>
                @endforelse

            </tbody>

        </table>

    </div>


    {{-- ================================================= --}}
    {{-- MOBILE --}}
    {{-- ================================================= --}}


    <div class="d-block d-md-none">

        @forelse($orders as $order)
            <div class="card order-card mb-3">

                <div class="card-body">

                    {{-- TOP --}}
                    <div class="d-flex justify-content-between align-items-center mb-2">

                        <div class="order-number">
                            #{{ $order->order_id }}
                        </div>

                        <span class="badge bg-primary status-badge">
                            OFD
                        </span>

                    </div>


                    {{-- CUSTOMER --}}
                    <div class="customer-name">
                        {{ $order->customer_name }}
                    </div>

                    <div class="text-muted small mb-3">
                        {{ $order->city }}, {{ $order->state }}
                    </div>


                    {{-- BARCODE --}}
                    @if (!empty($order->barcode))
                        <div class="label">
                            Barcode
                        </div>

                        <div class="value mb-2">
                            {{ $order->barcode }}
                        </div>
                    @endif


                    {{-- PRODUCT --}}
                    <div class="label">
                        Product
                    </div>

                    <div class="value mb-2">

                        {{ $order->product_name }}

                        @if (!empty($order->quantity))
                            <span class="text-muted">
                                (Qty: {{ $order->quantity }})
                            </span>
                        @endif

                    </div>


                    {{-- PHONE --}}
                    <div class="label">
                        Mobile
                    </div>

                    <div class="value mb-2">

                        <a href="" class="phone">

                            📞 {{ $order->customer_phone }}

                        </a>

                    </div>


                    {{-- PAYMENT --}}
                    @if (!empty($order->payment_mode))
                        <div class="label">
                            Payment Mode
                        </div>

                        <div class="value mb-2">
                            {{ $order->payment_mode }}
                        </div>
                    @endif


                    {{-- AMOUNT --}}
                    @if (!empty($order->amount))
                        <div class="label">
                            Amount
                        </div>

                        <div class="value mb-2">
                            ₹{{ number_format($order->amount, 2) }}
                        </div>
                    @endif


                    {{-- ADDRESS --}}
                    <div class="label">
                        Address
                    </div>

                    <div class="value mb-2">

                        {{ $order->shipping_address }},
                        {{ $order->city }},
                        {{ $order->state }}
                        -
                        <strong>{{ $order->pincode }}</strong>

                    </div>


                    {{-- ORDER DATE --}}
                    <div class="label">
                        Order Date
                    </div>

                    <div class="value mb-2">
                        {{ $order->order_date }}
                    </div>


                    {{-- DELIVERY REMARK --}}
                    <div class="label">
                        Delivery Remarks
                    </div>

                    <div class="value mb-2">
                        {{ $order->delivery_remark ?: '—' }}
                    </div>


                    {{-- ===================================== --}}
                    {{-- CALL STATUS --}}
                    {{-- ===================================== --}}

                    <div class="mt-3 p-2 rounded" style="background:#f8f9fa; border:1px solid #eee;">

                        <div class="d-flex justify-content-between align-items-center">

                            {{-- STATUS --}}
                            <div>

                                <div class="label mt-0">
                                    Call Status
                                </div>

                                @if ($order->called_at)
                                    <span class="badge bg-success">
                                        ✓ Called
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        Not Called
                                    </span>
                                @endif

                            </div>


                            {{-- LAST CALL --}}
                            @if ($order->called_at)
                                <div class="text-end">

                                    <div class="label mt-0">
                                        Last Call
                                    </div>

                                    <small class="text-muted">

                                        {{ \Carbon\Carbon::parse($order->called_at)->format('d-m-Y') }}

                                        <br>

                                        <strong>
                                            {{ \Carbon\Carbon::parse($order->called_at)->format('h:i A') }}
                                        </strong>

                                    </small>

                                </div>
                            @endif

                        </div>

                    </div>


                    {{-- ===================================== --}}
                    {{-- CALL BUTTON --}}
                    {{-- ===================================== --}}

                    <div class="mt-3">

                        <a href="tel:{{ $order->customer_phone }}"
                            class="btn w-100 track-call
                       {{ $order->called_at ? 'btn-secondary' : 'btn-success' }}"
                            data-callingorder-id="{{ $order->id }}" data-order-id="{{ $order->order_id }}"
                            data-phone="{{ $order->customer_phone }}">

                            @if ($order->called_at)
                                ✓ Called — Call Again
                            @else
                                📞 Call Customer
                            @endif

                        </a>

                    </div>

                </div>

            </div>

        @empty

            <div class="text-center py-5 text-muted">

                <div style="font-size:40px;">
                    📭
                </div>

                <strong>
                    No Out for Delivery Orders
                </strong>

                <br>

                <small>
                    No assigned OFD customers are available for calling.
                </small>

            </div>
        @endforelse

    </div>


@endsection


@push('scripts')
    <script>
        $(document).on('click', '.track-call', function(e) {

            let button = $(this);

            let callingOrderId = button.data('callingorder-id');
            let orderId = button.data('order-id');
            let phone = button.data('phone');

            // Open phone dialer
            window.location.href = 'tel:' + phone;

            // Track call
            $.ajax({
                url: "{{ route('calling.trackCall') }}",
                type: "POST",

                data: {
                    _token: "{{ csrf_token() }}",
                    callingorder_id: callingOrderId,
                    order_id: orderId,
                    customer_phone: phone,
                    call_status: 'called'
                },

                success: function(response) {

                    if (response.success) {

                        button.removeClass('btn-success')
                            .addClass('btn-secondary');

                        button.html('✓ Called');

                        // Optional
                        console.log('Call tracked:', response.called_at);
                    }
                },

                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });

        });
        $(document).ready(function() {

            let table = $('#ordersTable');

            if (table.length) {

                table.DataTable({

                    pageLength: 10,

                    searching: true,

                    ordering: true,

                    responsive: true,

                    lengthMenu: [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
                    ],

                    language: {
                        emptyTable: "No Out for Delivery orders found"
                    }

                });

            }

        });
    </script>
@endpush
