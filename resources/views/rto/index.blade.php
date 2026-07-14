@extends('layouts.admin')

@section('content')

    <div class="card mb-3">
        <h4 class="text-center p-3">RTO Received Report</h4>
        <hr>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="alert alert-success m-3">
                {!! session('success') !!}
            </div>
        @endif
        @if (isset($skippedBarcodes) && count($skippedBarcodes))
            <div class="card mt-3">
                <div class="card-header bg-warning">
                    Skipped Barcodes (Already Scanned)
                </div>

                <div class="card-body">
                    @foreach ($skippedBarcodes as $barcode)
                        <span class="badge bg-danger">
                            {{ $barcode }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
        @if (isset($notFoundBarcodes) && count($notFoundBarcodes))
            <div class="card mt-3">
                <div class="card-header bg-danger text-white">
                    Not Found Barcodes
                    ({{ count($notFoundBarcodes) }})
                </div>

                <div class="card-body">
                    @foreach ($notFoundBarcodes as $barcode)
                        <span class="badge bg-dark me-1 mb-1">
                            {{ $barcode }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
        {{-- Upload Excel --}}
        <form method="POST" action="{{ route('rto.search') }}" enctype="multipart/form-data">
            @csrf

            <div class="card-body">
                <div class="row">

                    <div class="col-md-4">
                        <label>Upload RTO Barcodes (Excel)</label>
                        <input type="file" name="rtobarcodes" class="form-control" required>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-success">
                            Submit
                        </button>
                    </div>

                </div>
            </div>
        </form>
    </div>

    {{-- Export Button --}}
    @if (isset($orders) && count($orders) > 0)
        <div class="text-end mb-3">
            <a href="{{ route('rto.export') }}" class="btn btn-success">
                Export RTO Orders
            </a>
        </div>
    @endif


    <div class="card mb-3">
        <div class="card-body">

            {{-- Date Filter --}}
            <form method="GET" action="{{ route('rto.index') }}">
                <div class="row mb-4">
                    <div class="col-md-2">
                        <label>Client</label>
                        <select name="client_id" class="form-control">
                            <option value="">All Clients</option>

                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}"
                                    {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                    {{ $client->client_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label>Staff</label>

                        <select name="staff_id" class="form-control">

                            <option value="">All Staff</option>

                            <option value="other" {{ request('staff_id') == 'other' ? 'selected' : '' }}>
                                Other
                            </option>

                            @foreach ($staffs as $staff)
                                <option value="{{ $staff->id }}"
                                    {{ request('staff_id') == $staff->id ? 'selected' : '' }}>
                                    {{ $staff->name }}
                                </option>
                            @endforeach

                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>From Date</label>
                        <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                    </div>

                    <div class="col-md-2">
                        <label>To Date</label>
                        <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-primary">
                            Filter
                        </button>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <a href="{{ route('rto.index') }}" class="btn btn-secondary">
                            Reset
                        </a>
                    </div>

                </div>
            </form>

            {{-- Staff Wise Summary --}}
            @if (isset($staffCounts) && $staffCounts->count())
                <div class="card mb-4">
                    <div class="card-header">
                        <strong>Staff Wise RTO Summary</strong>
                    </div>

                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Client</th>
                                <th>Staff</th>
                                <th>Total RTO</th>
                                <th>Web RTO</th>
                                <th>WhatsApp RTO</th>
                                <th>RTO Received</th>
                                <th>Pending</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($staffCounts as $row)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $row->client_name }}</td>
                                    <td>{{ $row->staff_name }}</td>
                                    <td>{{ $row->total_rto }}</td>

                                    <td>{{ $row->web_rto }}</td>

                                    <td>{{ $row->whatsapp_rto }}</td>

                                    <td>{{ $row->rto_received }}</td>

                                    <td>{{ $row->pending_rto }}</td>
                                </tr>
                            @endforeach

                            <tr class="table-success">
                                <th colspan="3">Grand Total</th>
                                <td>{{ $grandTotal }}</td>

                                <td>{{ $grandWeb }}</td>

                                <td>{{ $grandWhatsapp }}</td>

                                <td>{{ $grandReceived }}</td>

                                <td>{{ $grandPending }}</td>
                            </tr>
                        </tbody>

                    </table>
                </div>
            @endif


            {{-- Scan Result Orders --}}
            @if (isset($orders) && count($orders) > 0)
                <div class="card">
                    <div class="card-header">
                        <strong>Scanned Orders</strong>
                    </div>

                    <div class="table-responsive">

                        <table class="table table-bordered table-striped">

                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Order ID</th>
                                    <th>Barcode</th>
                                    <th>Customer Name</th>
                                    <th>Father Name</th>
                                    <th>Phone</th>
                                    <th>Product</th>
                                    <th>Amount</th>
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
                                        <td>{{ $order->father_name }}</td>
                                        <td>{{ $order->customer_phone }}</td>
                                        <td>{{ $order->product }}</td>
                                        <td>{{ $order->amount }}</td>
                                        <td>{{ $order->date }}</td>
                                    </tr>
                                @endforeach

                            </tbody>

                        </table>

                    </div>
                </div>
            @endif

        </div>
    </div>

@endsection
