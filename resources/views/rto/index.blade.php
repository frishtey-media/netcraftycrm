@extends('layouts.admin')

@section('content')
    <style>
        #barcodeTable {

            padding-top: 25px;

        }
    </style>
    <div class="card mb-3">
        <h4 style="text-align: center;padding: 20px;">RTO Report</h4>
        <hr style="margin: 0;">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

        <form method="POST" action="{{ route('rto.search') }}" class="card mb-3" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <div class="row g-2">

                    <div class="col-md-3">
                        <label>Upload RTO Barcodes (Excel)</label>
                        <input type="file" name="rtobarcodes" class="form-control" required>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary">Search</button>
                    </div>

                </div>
            </div>
        </form>
    </div>
    @if (count($orders) > 0)
        <div style="text-align: right;"> <a href="{{ route('rto.export') }}" class="btn btn-success mb-3">
                Export RTO Orders
            </a></div>
    @endif

    <div class="card mb-3" style="    padding: 15px;">
        <form method="GET" action="{{ route('rto.index') }}">
            <div class="row">

                <div class="col-md-3">
                    <label>From Date</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>

                <div class="col-md-3">
                    <label>To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary">
                        Filter
                    </button>
                </div>

            </div>
        </form>
        @if (isset($staffCounts) && $staffCounts->count())
            <div class="card mb-3">
                <div class="card-header">
                    <strong>Staff Wise RTO Count</strong>
                </div>

                <div class="card mb-3">
                    <div class="card-header">
                        <strong>Date Wise Staff RTO</strong>
                    </div>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Staff Name</th>
                                <th>Total RTO</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($dateWiseCounts as $row)
                                <tr>
                                    <td>{{ $row->rto_date }}</td>
                                    <td>{{ $row->staff_name }}</td>
                                    <td>{{ $row->total_rto }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
        <table id="barcodeTable" class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Order ID</th>
                    <th>Staff Name</th>
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
                        <td>
                            {{ optional(optional($order->callingOrder)->staff)->name ?? 'Not Assigned' }}
                        </td>
                        <td>{{ $order->barcode }}</td>
                        <td>{{ $order->customer_name }}</td>
                        <td>{{ $order->customer_phone }}</td>
                        <td>{{ $order->shipping_address }}</td>
                        <td>{{ $order->payment_mode }}</td>
                        <td>{{ $order->amount }}</td>
                        <td>{{ $order->product }}</td>
                        <td>{{ $order->quantity }}</td>
                        <td>{{ $order->weight }}</td>
                        <td>{{ $order->order_date }}</td>

                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
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
                lengthMenu: [10, 25, 50, 100, 500, 1000, 2000, 5000],
                language: {
                    search: "Search Orders:"
                }
            });
        });
    </script>
@endsection
