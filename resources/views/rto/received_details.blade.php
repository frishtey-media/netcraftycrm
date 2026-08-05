@extends('layouts.admin')

@section('content')
    <div class="card">

        <div class="card-header">
            <h4 style="text-align:center;">{{ $heading }}</h4> <a
                href="{{ route('rto.details.export', request()->all()) }}" class="btn btn-success">
                Export Excel
            </a>
        </div>
        <div class="card-body">

            <table class="table table-bordered">

                <thead>

                    <tr>
                        <th>#</th>
                        <th>Order ID</th>
                        <th>Barcode</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Received Date</th>
                    </tr>

                </thead>

                <tbody>

                    @foreach ($orders as $order)
                        <tr>

                            <td>{{ $loop->iteration }}</td>

                            <td>{{ $order->order_id }}</td>

                            <td>{{ $order->barcode }}</td>

                            <td>{{ $order->customer_name }}</td>

                            <td>{{ $order->customer_phone }}</td>

                            <td>{{ $order->product }}</td>

                            <td>{{ $order->amount }}</td>

                            <td>{{ $order->rtoreciveddate }}</td>

                        </tr>
                    @endforeach

                </tbody>

            </table>

        </div>

    </div>
@endsection
