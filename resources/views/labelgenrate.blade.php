@extends('layouts.admin')

@section('content')

    <div class="p-4">

        <h4>Import Shopify Orders Excel</h4>


        <form method="POST" action="{{ url('/shopify-import') }}" enctype="multipart/form-data">
            @csrf

            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="file" name="file" class="form-control" required>
                </div>

                <div class="col-md-2">
                    <button class="btn btn-primary">Upload</button>
                </div>
            </div>
        </form>


        @if ($errors->any())
            <div class="alert alert-danger mt-3">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        @if (session('success'))
            <div class="alert alert-success mt-3">
                {{ session('success') }}
            </div>
        @endif


        @if (isset($orders) && count($orders) > 0)
            <hr class="my-4">

            <h4>Fill Barcode & Weight</h4>

            <form method="POST" action="{{ url('/barcode-save') }}">
                @csrf

                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Amount</th>
                            <th>Barcode</th>
                            <th>Weight (GM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $key => $order)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $order->order_id }}</td>
                                <td>{{ $order->customer_name }}</td>
                                <td>{{ $order->customer_phone }}</td>
                                <td>{{ $order->amount }}</td>

                                <td>
                                    <input type="text" name="barcode[{{ $order->id }}]" class="form-control" required>
                                </td>

                                <td>
                                    <input type="number" name="weight[{{ $order->id }}]" class="form-control" required>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <button class="btn btn-success">
                    Save & Generate Labels
                </button>
            </form>
        @endif

    </div>

    @if (isset($finalOrders) && $finalOrders->count())
        <hr class="my-4">
        <h4>Final Saved Courier Orders</h4>

        <table class="table table-bordered table-striped table-sm">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Order ID</th>
                    <th>Order Date</th>
                    <th>Barcode</th>
                    <th>Payment</th>
                    <th>Amount</th>

                    <th>Customer Name</th>
                    <th>Father / Company</th>
                    <th>Phone</th>

                    <th>Address</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Pincode</th>

                    <th>Product</th>
                    <th>Qty</th>
                    <th>Weight (GM)</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($finalOrders as $key => $order)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $order->order_id }}</td>
                        <td>{{ $order->order_date }}</td>
                        <td>{{ $order->barcode }}</td>
                        <td>{{ $order->payment_mode }}</td>
                        <td>{{ $order->amount }}</td>

                        <td>{{ $order->customer_name }}</td>
                        <td>{{ $order->customer_father_name }}</td>
                        <td>{{ $order->customer_phone }}</td>

                        <td>{{ $order->shipping_address }}</td>
                        <td>{{ $order->city }}</td>
                        <td>{{ $order->state }}</td>
                        <td>{{ $order->pincode }}</td>

                        <td>{{ $order->product }}</td>
                        <td>{{ $order->quantity }}</td>
                        <td>{{ $order->weight }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif



@endsection
