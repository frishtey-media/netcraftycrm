@extends('layouts.calling')

@section('title', 'Orders')

@section('content')
    <style>
        .order-card {
            border-radius: 12px;
            border: 1px solid #eee;
            background: #fff;
        }

        .customer-name {
            font-size: 15px;
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

        .badge {
            font-size: 11px;
            padding: 5px 10px;
            border-radius: 6px;
        }


        .client-scroll {
            display: flex;
            overflow-x: auto;
            gap: 8px;
            padding-bottom: 5px;
        }

        /* SCROLLBAR HIDE */
        .client-scroll::-webkit-scrollbar {
            display: none;
        }

        /* CHIP STYLE */
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

        /* ACTIVE */
        .client-chip.active {
            background: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }
    </style>
    <!-- ================= DESKTOP TABLE ================= -->
    <div class="table-responsive d-none d-md-block">
        <div class="client-scroll mb-3">

            <a href="{{ route('calling.orders') }}" class="client-chip {{ request('client_id') ? '' : 'active' }}">
                All
            </a>

            @foreach ($clients as $row)
                <a href="{{ route('calling.orders', ['client_id' => $row->client_id]) }}"
                    class="client-chip {{ request('client_id') == $row->client_id ? 'active' : '' }}">

                    {{ $row->client->client_name ?? 'Client' }}
                    ({{ $row->total }})
                </a>
            @endforeach

        </div>
        <table id="ordersTable" class="table table-hover align-middle">

            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Order Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td>#{{ $order->order_id }}</td>

                        <td>
                            <strong>{{ $order->customer_name }}</strong><br>
                            <small class="text-muted">{{ $order->city }}, {{ $order->state }}</small>
                        </td>

                        <td>{{ $order->product_name }}</td>

                        <td>
                            <a href="tel:{{ $order->customer_phone }}" class="text-primary">
                                {{ $order->customer_phone }}
                            </a>
                        </td>

                        <td>
                            {{ $order->shipping_address }}<br>
                            <strong>{{ $order->pincode }}</strong>
                        </td>
                        <td>
                            {{ $order->order_date }}
                        </td>
                        <td>
                            @if ($order->status == 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @elseif($order->status == 'verified')
                                <span class="badge bg-success">Verified</span>
                            @else
                                <span class="badge bg-danger">Not Reachable</span>
                            @endif

                        </td>

                        <td>
                            <!-- STATUS UPDATE -->
                            <form method="POST" action="/calling/statusupdate/{{ $order->id }}">
                                @csrf
                                <div class="d-flex gap-1 mb-1">
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="verified">Confirm</option>
                                        <option value="same_order">Same Order</option>
                                        <option value="not_reachable">Not Reachable</option>
                                        <option value="cancel">Cancel</option>

                                    </select>
                                    <button class="btn btn-success btn-sm">✔</button>
                                </div>
                            </form>

                            <!-- EDIT -->
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#edit{{ $order->id }}">
                                ✏ Edit
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>

        </table>
    </div>


    <!-- ================= MOBILE VIEW ================= -->
    <div class="d-block d-md-none">
        <div class="client-scroll mb-3">

            <a href="{{ route('calling.orders') }}" class="client-chip {{ request('client_id') ? '' : 'active' }}">
                All
            </a>

            @foreach ($clients as $row)
                <a href="{{ route('calling.orders', ['client_id' => $row->client_id]) }}"
                    class="client-chip {{ request('client_id') == $row->client_id ? 'active' : '' }}">

                    {{ $row->client->client_name ?? 'Client' }}
                    ({{ $row->total }})
                </a>
            @endforeach

        </div>
        @foreach ($orders as $order)
            <div class="card order-card mb-3">

                <div class="card-body">

                    <!-- TOP -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold">#{{ $order->order_id }}</div>
                        {{ $order->order_date }}
                        @if ($order->status == 'pending')
                            <span class="badge bg-warning">Pending</span>
                        @elseif($order->status == 'verified')
                            <span class="badge bg-success">Verified</span>
                        @else
                            <span class="badge bg-danger">Not Reachable</span>
                        @endif
                    </div>

                    <!-- CUSTOMER -->
                    <div class="fw-semibold customer-name">
                        {{ $order->customer_name }}
                    </div>

                    <div class="text-muted small mb-2">
                        {{ $order->city }}, {{ $order->state }}
                    </div>

                    <!-- PRODUCT -->
                    <div class="label">Product</div>
                    <div class="value mb-2">
                        {{ $order->product_name }}
                    </div>
                    <div class="label">Payment Mode</div>
                    <div class="value mb-2">
                        {{ $order->payment_mode }}
                    </div>

                    <!-- PHONE -->
                    <div class="label">Mobile</div>
                    <div class="value mb-2">
                        <a href="tel:{{ $order->customer_phone }}" class="phone">
                            {{ $order->customer_phone }}
                        </a>
                    </div>

                    <!-- ADDRESS -->
                    <div class="label">Address</div>
                    <div class="value">
                        {{ $order->shipping_address }},
                        {{ $order->city }},
                        {{ $order->state }} - <strong>{{ $order->pincode }}</strong>
                    </div>

                    <!-- BUTTONS -->
                    <div class="d-flex gap-2 mt-3">

                        <a href="tel:{{ $order->customer_phone }}" class="btn btn-success btn-sm w-50">
                            📞 Call
                        </a>

                        <button class="btn btn-primary btn-sm w-50" data-bs-toggle="modal"
                            data-bs-target="#edit{{ $order->id }}">
                            ✏ Edit
                        </button>

                    </div>

                    <!-- STATUS UPDATE -->
                    <form method="POST" action="/calling/statusupdate/{{ $order->id }}">
                        @csrf

                        <div class="d-flex gap-2 mt-3">
                            <select name="status" class="form-select form-select-sm">
                                <option value="verified">Confirm</option>
                                <option value="same_order">Same Order</option>
                                <option value="not_reachable">Not Reachable</option>
                                <option value="cancel">Cancel</option>
                            </select>

                            <button class="btn btn-success btn-sm">✔</button>
                        </div>
                    </form>

                </div>
            </div>
        @endforeach

    </div>


    <!-- ================= MODALS ================= -->
    @foreach ($orders as $order)
        <div class="modal fade" id="edit{{ $order->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content p-3">

                    <form method="POST" action="/calling/update/{{ $order->id }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label fw-bold">Customer Name</label>
                            <input type="text" name="customer_name" value="{{ $order->customer_name }}"
                                class="form-control" placeholder="Enter customer name">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Customer Phone</label>
                            <input type="text" name="customer_phone" value="{{ $order->customer_phone }}"
                                class="form-control" placeholder="Enter phone number">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Product Name</label>
                            <input type="text" name="product_name" value="{{ $order->product_name }}"
                                class="form-control" placeholder="Enter Father Name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Customer Father Name</label>
                            <input type="text" name="father_name" value="{{ $order->father_name }}"
                                class="form-control" placeholder="Enter Father Name">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Quantity</label>
                            <input type="text" name="quantity" value="{{ $order->quantity }}" class="form-control"
                                placeholder="Enter quantity">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Price</label>
                            <input type="text" name="amount" value="{{ $order->amount }}" class="form-control"
                                placeholder="Enter amount">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Age</label>
                            <input type="number" name="age" value="{{ $order->age }}" class="form-control"
                                placeholder="Enter age">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">City</label>
                            <input type="text" name="city" value="{{ $order->city }}" class="form-control"
                                placeholder="Enter city">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">State</label>
                            <input type="text" name="state" value="{{ $order->state }}" class="form-control"
                                placeholder="Enter state">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Pincode</label>
                            <input type="text" name="pincode" value="{{ $order->pincode }}" class="form-control"
                                placeholder="Enter pincode">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Shipping Address</label>
                            <textarea name="shipping_address" class="form-control" rows="4" placeholder="Enter shipping address">{{ $order->shipping_address }}</textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100">
                            Update Order
                        </button>
                    </form>

                </div>
            </div>
        </div>
    @endforeach

@endsection


@push('scripts')
    <script>
        $(document).ready(function() {

            let table = $('#ordersTable');

            if (table.length) {

                if ($.fn.DataTable.isDataTable(table)) {
                    table.DataTable().destroy();
                }

                table.DataTable({
                    pageLength: 10,
                    searching: true,
                    ordering: true,
                    responsive: true
                });
            }

        });
    </script>
@endpush


<style>
    .card {
        transition: 0.3s;
    }

    .card:hover {
        transform: translateY(-3px);
    }
</style>
