@extends('layouts.admin')

@section('content')
    <div class="row mb-3">

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5>Total Delivered</h5>
                    <h3>{{ number_format($totalOrders) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5>Total Product Qty</h5>
                    <h3>{{ number_format($totalQty) }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5>Total Amount</h5>
                    <h3>₹{{ number_format($totalAmount, 2) }}</h3>
                </div>
            </div>
        </div>

    </div>
    <div class="card mb-4">

        <div class="card-header bg-primary text-white">

            <h5>Product Wise Summary</h5>

        </div>

        <div class="table-responsive">

            <table class="table table-bordered">

                <thead>

                    <tr>

                        <th>#</th>

                        <th>Product</th>

                        <th>Orders</th>

                        <th>Qty</th>

                        <th>Amount</th>

                    </tr>

                </thead>

                <tbody>

                    @foreach ($productSummary as $key => $row)
                        <tr>

                            <td>{{ $key + 1 }}</td>

                            <td>{{ $row->product }}</td>

                            <td>{{ $row->orders }}</td>

                            <td>{{ $row->qty }}</td>

                            <td>₹{{ number_format($row->amount, 2) }}</td>

                        </tr>
                    @endforeach

                </tbody>

            </table>

        </div>

    </div>
    <div class="card">

        <div class="card-header bg-success text-white">

            <h5>Delivered Orders</h5>

        </div>

        <div class="table-responsive">

            <table class="table table-striped table-bordered">

                <thead>

                    <tr>

                        <th>Order ID</th>

                        <th>Barcode</th>

                        <th>Customer</th>

                        <th>Phone</th>

                        <th>City</th>

                        <th>Product</th>

                        <th>Qty</th>

                        <th>Amount</th>

                        <th>Source</th>

                        <th>Delivery Date</th>

                    </tr>

                </thead>

                <tbody>

                    @foreach ($orders as $order)
                        <tr>

                            <td>{{ $order->order_id }}</td>

                            <td>{{ $order->barcode }}</td>

                            <td>{{ $order->customer_name }}</td>

                            <td>{{ $order->customer_phone }}</td>

                            <td>{{ $order->city }}</td>

                            <td>{{ $order->product }}</td>

                            <td>{{ $order->quantity }}</td>

                            <td>₹{{ number_format($order->amount, 2) }}</td>

                            <td>{{ $order->order_source ?? 'Web' }}</td>

                            <td>{{ $order->delivery_date }}</td>

                        </tr>
                    @endforeach

                </tbody>

            </table>

        </div>

        {{ $orders->links() }}

    </div>
@endsection
