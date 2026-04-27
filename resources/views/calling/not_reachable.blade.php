@extends('layouts.calling')

@section('title', 'Not Reachable Orders')

@section('content')

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
                            <a href="tel:{{ $order->customer_phone }}" class="text-primary">
                                {{ $order->customer_phone }}
                            </a>
                        </td>

                        <td>{{ $order->city }}</td>
                        <td>{{ $order->state }}</td>
                        <td>{{ $order->pincode }}</td>
                        <td>{{ $order->shipping_address }}</td>

                        <td>
                            @if ($order->status == 'verified')
                                <span class="badge bg-success">Verified</span>
                            @elseif($order->status == 'pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                            @else
                                <span class="badge bg-danger">Not Reachable</span>
                            @endif
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

                    <!-- TOP -->
                    <div class="d-flex justify-content-between">
                        <strong>#{{ $order->order_id }}</strong>

                        @if ($order->status == 'pending')
                            <span class="badge bg-warning">Pending</span>
                        @elseif($order->status == 'verified')
                            <span class="badge bg-success">Verified</span>
                        @else
                            <span class="badge bg-danger">Not Reachable</span>
                        @endif
                    </div>

                    <!-- CUSTOMER -->
                    <div class="fw-semibold mt-1">{{ $order->customer_name }}</div>

                    <!-- DETAILS -->
                    <div class="mt-2 small">

                        <div><strong>Product:</strong> {{ $order->product_name }}</div>

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

                    <!-- ACTION -->
                    <div class="mt-3 d-flex gap-2">
                        <a href="tel:{{ $order->customer_phone }}" class="btn btn-success btn-sm w-50">
                            📞 Call
                        </a>

                        <button class="btn btn-primary btn-sm w-50" data-bs-toggle="modal"
                            data-bs-target="#editModal{{ $order->id }}">
                            ✏ Edit
                        </button>
                    </div>

                </div>
            </div>
        @endforeach

    </div>


    <!-- ================= MODALS ================= -->
    @foreach ($orders as $order)
        <div class="modal fade" id="editModal{{ $order->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content p-3">

                    <h5>Edit Order</h5>

                    <form method="POST" action="/calling/update/{{ $order->id }}">
                        @csrf

                        <label>Customer Name</label>
                        <input name="customer_name" value="{{ $order->customer_name }}" class="form-control mb-2">

                        <label>Phone</label>
                        <input name="customer_phone" value="{{ $order->customer_phone }}" class="form-control mb-2">

                        <label>City</label>
                        <input name="city" value="{{ $order->city }}" class="form-control mb-2">

                        <label>State</label>
                        <input name="state" value="{{ $order->state }}" class="form-control mb-2">

                        <label>Pincode</label>
                        <input name="pincode" value="{{ $order->pincode }}" class="form-control mb-2">

                        <label>Address</label>
                        <textarea name="shipping_address" class="form-control mb-2">{{ $order->shipping_address }}</textarea>

                        <button class="btn btn-success w-100">Update</button>

                    </form>

                </div>
            </div>
        </div>
    @endforeach

@endsection


@push('scripts')
    <!-- jQuery + DataTable -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#ordersTable').DataTable({
                pageLength: 10,
                responsive: true
            });
        });
    </script>
@endpush


<style>
    .card {
        border-radius: 12px;
    }

    .badge {
        font-size: 11px;
        padding: 5px 10px;
    }
</style>
