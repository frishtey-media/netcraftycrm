@extends('layouts.calling')

@section('title', 'Verify Orders')

@section('content')

    <!-- ================= DESKTOP ================= -->
    <div class="table-responsive d-none d-md-block">

        <table id="ordersTable" class="table table-hover align-middle">

            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Phone</th>
                    <th>Location</th>
                    <th>Address</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td>#{{ $order->order_id }}</td>

                        <td>
                            <strong>{{ $order->customer_name }}</strong><br>
                            <small>{{ $order->city }}, {{ $order->state }}</small>
                        </td>

                        <td>{{ $order->product_name }}</td>

                        <td>
                            <a href="tel:{{ $order->customer_phone }}">
                                {{ $order->customer_phone }}
                            </a>
                        </td>

                        <td>
                            {{ $order->city }}<br>
                            <strong>{{ $order->pincode }}</strong>
                        </td>

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
            <div class="card order-card mb-3">

                <div class="card-body">

                    <!-- TOP -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
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
                    <div class="fw-semibold">{{ $order->customer_name }}</div>
                    <small class="text-muted">{{ $order->city }}, {{ $order->state }}</small>

                    <!-- DETAILS -->
                    <div class="mt-2">

                        <div class="row g-1">
                            <div class="col-4 label">Product</div>
                            <div class="col-8 value">{{ $order->product_name }}</div>

                            <div class="col-4 label">Mobile</div>
                            <div class="col-8 value">
                                <a href="tel:{{ $order->customer_phone }}">
                                    {{ $order->customer_phone }}
                                </a>
                            </div>

                            <div class="col-4 label">City</div>
                            <div class="col-8 value">{{ $order->city }}</div>

                            <div class="col-4 label">State</div>
                            <div class="col-8 value">{{ $order->state }}</div>

                            <div class="col-4 label">Pincode</div>
                            <div class="col-8 value">{{ $order->pincode }}</div>

                            <div class="col-4 label">Address</div>
                            <div class="col-8 value">{{ $order->shipping_address }}</div>
                        </div>

                    </div>

                </div>
            </div>
        @endforeach

    </div>

@endsection


@push('scripts')
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
    .order-card {
        border-radius: 12px;
        border: 1px solid #eee;
    }

    /* LABEL STYLE */
    .label {
        font-size: 11px;
        color: #999;
        font-weight: 600;
    }

    /* VALUE STYLE */
    .value {
        font-size: 14px;
        font-weight: 500;
    }

    /* MOBILE FONT */
    @media (max-width: 768px) {
        .card-body {
            font-size: 14px;
        }
    }
</style>
