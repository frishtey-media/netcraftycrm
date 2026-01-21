<table class="table table-bordered">
    <thead class="table-dark">
        <tr>
            <th>Order ID</th>
            <th>Date</th>
            <th>Barcode</th>
            <th>Payment Mode</th>
            <th>Amount</th>
            <th>Customer Name</th>
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
        @foreach ($orders as $order)
            <tr>
                <td>RR{{ $order->order_id }}</td>
                <td>{{ \Carbon\Carbon::parse($order->order_date)->format('d-m-Y') }}</td>
                <td>{{ $order->barcode ?? 'PENDING' }}</td>
                <td>{{ $order->payment_mode }}</td>
                <td>{{ $order->amount }}</td>
                <td>{{ $order->customer_name }}</td>
                <td>{{ $order->customer_phone }}</td>
                <td>{{ $order->shipping_address }}</td>
                <td>{{ $order->city }}</td>
                <td>{{ $order->state }}</td>
                <td>{{ $order->pincode }}</td>
                <td>{{ $order->product_name }}</td>
                <td>{{ $order->quantity }}</td>
                <td>{{ $order->total_weight }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
