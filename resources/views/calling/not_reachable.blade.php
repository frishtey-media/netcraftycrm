@extends('layouts.calling')

@section('title', 'Not Reachable Orders')

@section('content')

    <style>
        .client-scroll {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 5px;
        }

        .client-chip {
            padding: 6px 14px;
            border-radius: 20px;
            background: #f1f1f1;
            text-decoration: none;
            color: #333;
            white-space: nowrap;
            font-size: 13px;
            transition: 0.3s;
        }

        .client-chip.active {
            background: #dc3545;
            color: #fff;
        }

        .client-chip:hover {
            background: #dc3545;
            color: #fff;
        }
    </style>

    <!-- 🔥 CLIENT FILTER -->
    <div class="client-scroll mb-3">

        <a href="{{ route('calling.not.reachable') }}" class="client-chip {{ request('client_id') ? '' : 'active' }}">
            All
        </a>

        @foreach ($clients as $row)
            <a href="{{ route('calling.not.reachable', ['client_id' => $row->client_id]) }}"
                class="client-chip {{ request('client_id') == $row->client_id ? 'active' : '' }}">
                {{ $row->client->client_name ?? 'Client' }} ({{ $row->total }})
            </a>
        @endforeach

    </div>

    <!-- ================= DESKTOP ================= -->
    <div class="table-responsive d-none d-md-block">

        <table id="ordersTable" class="table table-hover align-middle">

            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Product</th>
                    <th>Phone</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Pincode</th>
                    <th>Address</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td>#{{ $order->order_id }}</td>
                        <td>{{ $order->customer_name }}</td>
                        <td>{{ $order->product_name }}</td>

                        <td>
                            <a href="tel:{{ $order->customer_phone }}">
                                {{ $order->customer_phone }}
                            </a>
                        </td>

                        <td>{{ $order->city }}</td>
                        <td>{{ $order->state }}</td>
                        <td>{{ $order->pincode }}</td>
                        <td>{{ $order->shipping_address }}</td>

                        <td>
                            <span class="badge bg-danger">Not Reachable</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>

        </table>
    </div>

    <!-- ================= MOBILE ================= -->
    <div class="d-block d-md-none">

        @foreach ($orders as $order)
            <div class="card mb-3 shadow-sm border-0 rounded-3">

                <div class="card-body">

                    <div class="d-flex justify-content-between">
                        <strong>#{{ $order->order_id }}</strong>
                        <span class="badge bg-danger">Not Reachable</span>
                    </div>

                    <div class="fw-semibold mt-1">{{ $order->customer_name }}</div>

                    <div class="mt-2 small">

                        <div><strong>Product:</strong> {{ $order->product_name }}</div>
                        <div class="label">Payment Mode</div>
                        <div class="value mb-2">
                            {{ $order->payment_mode }}
                        </div>
                        <div>
                            <strong>Mobile:</strong>
                            <a href="tel:{{ $order->customer_phone }}">
                                {{ $order->customer_phone }}
                            </a>
                        </div>

                        <div><strong>City:</strong> {{ $order->city }}</div>
                        <div><strong>State:</strong> {{ $order->state }}</div>
                        <div><strong>Pincode:</strong> {{ $order->pincode }}</div>

                        <div>
                            <strong>Address:</strong>
                            {{ $order->shipping_address }}
                        </div>

                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <a href="tel:{{ $order->customer_phone }}" class="btn btn-success btn-sm w-50">
                            📞 Call
                        </a>

                        <button class="btn btn-primary btn-sm w-50" data-bs-toggle="modal"
                            data-bs-target="#editModal{{ $order->id }}">
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
                            <input type="text" name="father_name" value="{{ $order->father_name }}" class="form-control"
                                placeholder="Enter Father Name">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Quantity</label>
                            <input type="text" name="quantity" value="{{ $order->quantity }}" class="form-control"
                                placeholder="Enter quantity">
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

            if (!$.fn.DataTable.isDataTable('#ordersTable')) {
                $('#ordersTable').DataTable({
                    pageLength: 10,
                    responsive: true,
                    destroy: true
                });
            }

        });
    </script>
@endpush
