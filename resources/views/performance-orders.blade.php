@extends('layouts.admin')

@section('content')
    <div class="container-fluid">


        <div class="card shadow-sm">

            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">

                <h4 class="mb-0">Orders Report</h4>

                <div>
                    <strong>
                        {{ \Carbon\Carbon::parse(request('from'))->format('d-m-Y') }}
                    </strong>
                    to
                    <strong>
                        {{ \Carbon\Carbon::parse(request('to'))->format('d-m-Y') }}
                    </strong>
                </div>

            </div>

            <div class="card-body">

                <div class="row mb-4">

                    <div class="col-md-3">
                        <div class="card border-primary h-100">
                            <div class="card-body text-center">
                                <h6>Staff</h6>
                                <h4>{{ $staff->name }}</h4>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body text-center">
                                <h6>Total Orders</h6>
                                <h3>{{ $totalOrders }}</h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body text-center">
                                <h6>Verified</h6>
                                <h3>{{ $verifiedOrders }}</h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="card bg-danger text-white h-100">
                            <div class="card-body text-center">
                                <h6>Cancel</h6>
                                <h3>{{ $cancelOrders }}</h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="card bg-dark text-white h-100">
                            <div class="card-body text-center">
                                <h6>Not Reachable</h6>
                                <h3>{{ $notReachableOrders }}</h3>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-1">
                        <div class="card bg-secondary text-white h-100">
                            <div class="card-body text-center">
                                <h6>Same</h6>
                                <h3>{{ $sameOrderOrders }}</h3>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="table-responsive">

                    <table class="table table-bordered table-striped align-middle">

                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Order ID</th>
                                <th>Client</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Product</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Updated Date</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($orders as $order)
                                <tr>

                                    <td>{{ $loop->iteration }}</td>

                                    <td>
                                        <strong>{{ $order->order_id }}</strong>
                                    </td>

                                    <td>
                                        {{ $order->client->client_name ?? 'N/A' }}
                                    </td>

                                    <td>{{ $order->customer_name }}</td>

                                    <td>{{ $order->customer_phone }}</td>

                                    <td>{{ $order->product_name }}</td>

                                    <td>
                                        ₹{{ number_format($order->amount, 2) }}
                                    </td>

                                    <td>

                                        @if ($order->status == 'verified')
                                            <span class="badge bg-success">Verified</span>
                                        @elseif($order->status == 'pending')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @elseif($order->status == 'cancel')
                                            <span class="badge bg-danger">Cancel</span>
                                        @elseif($order->status == 'same_order')
                                            <span class="badge bg-secondary">Same Order</span>
                                        @elseif($order->status == 'not_reachable')
                                            <span class="badge bg-dark">Not Reachable</span>
                                        @else
                                            <span class="badge bg-info">
                                                {{ ucfirst($order->status) }}
                                            </span>
                                        @endif

                                    </td>

                                    <td>
                                        {{ $order->updated_at->format('d-m-Y h:i A') }}
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="9" class="text-center text-danger">
                                        No Orders Found
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

                <div class="mt-3">
                    {{ $orders->appends(request()->query())->links() }}
                </div>

            </div>

        </div>


    </div>
@endsection
