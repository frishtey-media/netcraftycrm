@extends('layouts.admin')

@section('content')
    <div class="container">
        <h4>Shopify Orders</h4>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Client</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Weight</th>
                    <th>Barcode</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td>{{ $order->order_id }}</td>
                        <td>{{ $order->client->client_name }}</td>
                        <td>{{ $order->product_name }}</td>
                        <td>{{ $order->quantity }}</td>
                        <td>{{ $order->weight }}</td>
                        <td>{{ $order->barcode ?? 'Not Assigned' }}</td>
                        <td>
                            @if (!$order->barcode)
                                <form method="POST" action="{{ route('orders.assign-barcode', $order->id) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-success">
                                        Assign Barcode
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
