@extends('layouts.admin')

@section('content')

    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h3>
                Pending Payment Report
            </h3>

            <a href="{{ route('payments.pending.export', request()->all()) }}" class="btn btn-success">

                <i class="fa fa-file-excel"></i>

                Export Excel

            </a>

        </div>

        <form method="GET">

            <div class="card shadow mb-4">

                <div class="card-body">

                    <div class="row">

                        {{-- Client --}}

                        @if (auth()->user()->role != 'client')
                            <div class="col-lg-2">

                                <label>Client</label>

                                <select name="client_id" class="form-control">

                                    <option value="">All</option>

                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}"
                                            {{ request('client_id') == $client->id ? 'selected' : '' }}>

                                            {{ $client->client_name }}

                                        </option>
                                    @endforeach

                                </select>

                            </div>
                        @endif


                        {{-- Product --}}

                        <div class="col-lg-2">

                            <label>Product</label>

                            <select name="product" class="form-control">

                                <option value="">All</option>

                                @foreach ($products as $product)
                                    <option value="{{ $product }}"
                                        {{ request('product') == $product ? 'selected' : '' }}>

                                        {{ $product }}

                                    </option>
                                @endforeach

                            </select>

                        </div>


                        {{-- Source --}}

                        <div class="col-lg-2">

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


                        {{-- From --}}

                        <div class="col-lg-2">

                            <label>From</label>

                            <input type="date" name="from" class="form-control" value="{{ request('from') }}">

                        </div>


                        {{-- To --}}

                        <div class="col-lg-2">

                            <label>To</label>

                            <input type="date" name="to" class="form-control" value="{{ request('to') }}">

                        </div>


                        {{-- Search --}}

                        <div class="col-lg-2">

                            <label>Search</label>

                            <input type="text" name="search" class="form-control" value="{{ request('search') }}"
                                placeholder="Order / Barcode / Phone">

                        </div>

                    </div>

                    <div class="mt-3">

                        <button class="btn btn-primary">

                            Filter

                        </button>

                        <a href="{{ route('payments.pending') }}" class="btn btn-secondary">

                            Reset

                        </a>

                    </div>

                </div>

            </div>

        </form>



        {{-- Dashboard --}}

        <div class="row mb-4">

            <div class="col-lg-6">

                <div class="card shadow">

                    <div class="card-body">

                        <h5>

                            Pending Articles

                        </h5>

                        <h2>

                            {{ number_format($totalOrders) }}

                        </h2>

                    </div>

                </div>

            </div>

            <div class="col-lg-6">

                <div class="card shadow">

                    <div class="card-body">

                        <h5>

                            Pending Amount

                        </h5>

                        <h2 class="text-success">

                            ₹{{ number_format($totalAmount, 2) }}

                        </h2>

                    </div>

                </div>

            </div>

        </div>
        <div class="card shadow">

            <div class="card-header d-flex justify-content-between">

                <h5 class="mb-0">
                    Pending Payment Orders
                </h5>

                <span>
                    Total Records :
                    <strong>{{ $orders->total() }}</strong>
                </span>

            </div>

            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover mb-0">

                        <thead class="table-dark">

                            <tr>

                                <th>#</th>

                                <th>Order ID</th>

                                <th>Barcode</th>

                                <th>Client</th>

                                <th>Customer</th>

                                <th>Phone</th>

                                <th>Product</th>

                                <th>Qty</th>

                                <th>Amount</th>

                                <th>Delivery Date</th>

                                <th>Status</th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($orders as $row)
                                <tr>

                                    <td>
                                        {{ $loop->iteration + ($orders->currentPage() - 1) * $orders->perPage() }}
                                    </td>

                                    <td>{{ $row->order_id }}</td>

                                    <td>{{ $row->barcode }}</td>

                                    <td>{{ $row->client_name }}</td>

                                    <td>{{ $row->customer_name }}</td>

                                    <td>{{ $row->customer_phone }}</td>

                                    <td>{{ $row->product }}</td>

                                    <td>{{ $row->quantity }}</td>

                                    <td>
                                        ₹{{ number_format($row->amount, 2) }}
                                    </td>

                                    <td>
                                        {{ \Carbon\Carbon::parse($row->delivery_date)->format('d-m-Y') }}
                                    </td>

                                    <td>

                                        <span class="badge bg-success">

                                            {{ $row->delivery_status }}

                                        </span>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="11" class="text-center">

                                        No Pending Payment Found

                                    </td>

                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

            <div class="card-footer">

                {{ $orders->appends(request()->query())->links() }}

            </div>

        </div>
    @endsection
