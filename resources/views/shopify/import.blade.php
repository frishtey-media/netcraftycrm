@extends('layouts.admin')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    @push('scripts')
        <script>
            $(function() {
                if (!$.fn.DataTable.isDataTable('#ordersTable')) {
                    $('#ordersTable').DataTable({
                        language: {
                            emptyTable: "No orders found"
                        }
                    });
                }
            });
        </script>
    @endpush


    <div class="container">
        <h4>Import Shopify Orders</h4>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('import_warning'))
            <div class="alert alert-warning alert-dismissible fade show">
                {{ session('import_warning') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif



        <form style="margin: 35px 0px 35px 0px;" method="POST" action="{{ route('shopify.import') }}"
            enctype="multipart/form-data">
            @csrf

            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Select Client</label>
                    <select name="client_id" class="form-control" required>
                        <option value="">-- Select Client --</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->client_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Upload Shopify Excel</label>
                    <input type="file" name="file" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        Import Orders
                    </button>
                </div>
            </div>
        </form>



        <table id="ordersTable" class="table table-bordered table-striped">
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
                    <th>Total Weight (GM)</th>
                    <th>Order Date</th>
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
                        <td>{{ $order->shopify_product_name }}</td>
                        <td>{{ $order->quantity }}</td>
                        <td>{{ $order->total_weight }}</td>
                        <td>{{ \Carbon\Carbon::parse($order->order_date)->format('d-m-Y') }}</td>
                    </tr>
                @endforeach
            </tbody>

        </table>

    </div>
@endsection
