@extends('layouts.admin')

@section('content')
    <h4>Orders List</h4>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

    <form method="GET" action="{{ route('orders.list') }}" class="card mb-3">
        <div class="card-body">
            <div class="row g-2">

                <div class="col-md-2">
                    <label>Date From</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>

                <div class="col-md-2">
                    <label>Date To</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary me-2">Filter</button>
                    <a href="{{ route('orders.list') }}" class="btn btn-secondary">Reset</a>
                </div>

            </div>
        </div>
    </form>

    <table id="barcodeTable" class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Order ID</th>
                <th>Barcode</th>
                <th>Customer Name</th>
                <th>Customer Phone</th>
                <th>Shipping Address</th>
                <th>Payment Mode</th>
                <th>Amount</th>
                <th>Product</th>
                <th>Quantity</th>
                <th>Weight</th>
                <th>Date</th>
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
                    <td>{{ $order->shipping_address }}</td>
                    <td>{{ $order->payment_mode }}</td>
                    <td>{{ $order->amount }}</td>
                    <td>{{ $order->product }}</td>
                    <td>{{ $order->quantity }}</td>
                    <td>{{ $order->weight }}</td>
                    <td>{{ $order->date }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- SCRIPTS --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#barcodeTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                language: {
                    search: "Search Orders:"
                }
            });
        });
    </script>
@endsection
