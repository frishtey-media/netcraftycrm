@extends('layouts.admin')

@section('content')

    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h3>Delivered Orders Report</h3>

            <a href="{{ route('delivered.export', request()->all()) }}" class="btn btn-success">

                <i class="fa fa-file-excel"></i>

                Export Excel

            </a>

        </div>

        <form method="GET">

            <div class="card shadow mb-4">

                <div class="card-body">

                    <div class="row">

                        @if (auth()->user()->role != 'client')
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
                        @endif
                        <div class="col-md-2">
                            <label>Staff</label>

                            <select name="staff_id" class="form-control">

                                <option value="">All Staff</option>

                                @foreach ($staffs as $staff)
                                    <option value="{{ $staff->id }}"
                                        {{ request('staff_id') == $staff->id ? 'selected' : '' }}>

                                        {{ $staff->name }}

                                    </option>
                                @endforeach

                                <option value="other" {{ request('staff_id') == 'other' ? 'selected' : '' }}>

                                    Other

                                </option>

                            </select>
                        </div>

                        <div class="col-md-2">

                            <label>Product</label>

                            <select name="product" class="form-control">

                                <option value="">All Products</option>

                                @foreach ($products as $product)
                                    <option value="{{ $product }}"
                                        {{ request('product') == $product ? 'selected' : '' }}>

                                        {{ $product }}

                                    </option>
                                @endforeach

                            </select>

                        </div>


                        <div class="col-md-2">

                            <label>Order Source</label>

                            <select name="order_source" class="form-control">

                                <option value="">All</option>

                                <option value="web" {{ request('order_source') == 'web' ? 'selected' : '' }}>

                                    Web

                                </option>

                                <option value="whatsapp" {{ request('order_source') == 'whatsapp' ? 'selected' : '' }}>

                                    WhatsApp

                                </option>

                            </select>

                        </div>


                        <div class="col-md-2">

                            <label>From</label>

                            <input type="date" name="from" class="form-control" value="{{ request('from') }}">

                        </div>


                        <div class="col-md-2">

                            <label>To</label>

                            <input type="date" name="to" class="form-control" value="{{ request('to') }}">

                        </div>


                        <div class="col-md-2">

                            <label>Search</label>

                            <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                                placeholder="Order / Barcode">

                        </div>

                    </div>

                    <div class="mt-3">

                        <button class="btn btn-primary">

                            Filter

                        </button>

                        <a href="{{ route('delivered.index') }}" class="btn btn-secondary">

                            Reset

                        </a>

                    </div>

                </div>

            </div>

        </form>


        <div class="row mb-4">

            <div class="col-md-6">

                <div class="card shadow">

                    <div class="card-body">

                        <h5>Total Delivered Orders</h5>

                        <h2 class="text-success">

                            {{ number_format($totalOrders) }}

                        </h2>

                    </div>

                </div>

            </div>

            <div class="col-md-6">

                <div class="card shadow">

                    <div class="card-body">

                        <h5>Total Delivered Amount</h5>

                        <h2 class="text-primary">

                            ₹{{ number_format($totalAmount, 2) }}

                        </h2>

                    </div>

                </div>

            </div>

        </div>


        <div class="card shadow">

            <div class="card-header">

                <strong>

                    Staff Wise Delivered Summary

                </strong>

            </div>

            <div class="table-responsive">

                <table class="table table-bordered table-striped mb-0">

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Client</th>

                            <th>Staff</th>

                            <th>Total Delivered</th>

                            <th>Web Delivered</th>

                            <th>WhatsApp Delivered</th>

                            <th>Total Amount</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($staffSummary as $row)
                            <tr>

                                <td>{{ $loop->iteration }}</td>

                                <td>{{ $row->client_name }}</td>

                                <td>{{ $row->staff_name }}</td>

                                <td>{{ $row->total_delivered }}</td>

                                <td>{{ $row->web_delivered }}</td>

                                <td>{{ $row->whatsapp_delivered }}</td>

                                <td><a
                                        href="{{ route('reports.staff.delivery.detail', [
                                            'staff_id' => $row->staff_id ?? 'other',
                                            'client_id' => request('client_id'),
                                            'from' => request('from'),
                                            'to' => request('to'),
                                        ]) }}">
                                        ₹{{ number_format($row->total_amount, 2) }}
                                    </a></td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="7" class="text-center">

                                    No Record Found

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                    <tfoot>

                        <tr class="table-success">

                            <th colspan="3">

                                Grand Total

                            </th>

                            <th>{{ $grandDelivered }}</th>

                            <th>{{ $grandWeb }}</th>

                            <th>{{ $grandWhatsapp }}</th>

                            <th>₹{{ number_format($grandAmount, 2) }}</th>

                        </tr>

                    </tfoot>

                </table>

            </div>

        </div>
        <div class="card shadow mt-4">

            <div class="card-header">

                <strong>

                    Delivered Orders Details

                </strong>

            </div>

            <div class="table-responsive">

                <table class="table table-bordered table-striped">

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Delivery Date</th>

                            <th>Client</th>

                            <th>Staff</th>

                            <th>Order ID</th>

                            <th>Barcode</th>

                            <th>Customer</th>

                            <th>Phone</th>

                            <th>Product</th>

                            <th>Qty</th>

                            <th>Amount</th>

                            <th>Source</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($orders as $row)
                            <tr>

                                <td>

                                    {{ $loop->iteration + ($orders->currentPage() - 1) * $orders->perPage() }}

                                </td>

                                <td>

                                    {{ \Carbon\Carbon::parse($row->delivery_date)->format('d-m-Y') }}

                                </td>

                                <td>

                                    {{ $row->client_name }}

                                </td>

                                <td>

                                    {{ $row->staff_name ?? 'Other' }}

                                </td>

                                <td>

                                    {{ $row->order_id }}

                                </td>

                                <td>

                                    {{ $row->barcode }}

                                </td>

                                <td>

                                    {{ $row->customer_name }}

                                </td>

                                <td>

                                    {{ $row->customer_phone }}

                                </td>

                                <td>

                                    {{ $row->product }}

                                </td>

                                <td>

                                    {{ $row->quantity }}

                                </td>

                                <td>

                                    ₹{{ number_format($row->amount, 2) }}

                                </td>

                                <td>

                                    @if ($row->order_source == 'whatsapp')
                                        <span class="badge bg-success">

                                            WhatsApp

                                        </span>
                                    @else
                                        <span class="badge bg-primary">

                                            Web

                                        </span>
                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="12" class="text-center">

                                    No Delivered Orders Found

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>

            <div class="card-footer">

                {{ $orders->appends(request()->query())->links() }}

            </div>

        </div>
    </div>

@endsection
