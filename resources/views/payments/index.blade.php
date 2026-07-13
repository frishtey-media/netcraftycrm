@extends('layouts.admin')

@section('title', 'Payment Report')

@section('content')

    <div class="container-fluid">
        <div class="row mb-4">
            <!--  <div class="col-lg-2">
                                                                                    <label class="form-label">Order Source</label>

                                                                                    <select name="order_source" class="form-control">

                                                                                        <option value="">All Orders</option>

                                                                                        <option value="web" {{ request('order_source') == 'web' ? 'selected' : '' }}>
                                                                                            Web
                                                                                        </option>

                                                                                        <option value="whatsapp" {{ request('order_source') == 'whatsapp' ? 'selected' : '' }}>
                                                                                            WhatsApp
                                                                                        </option>

                                                                                    </select>
                                                                                </div>-->
            <div class="col-lg-3">

                <div class="card shadow">

                    <div class="card-body">

                        <h5>Total Payment</h5>

                        <h2 class="text-success" style="font-size: 18px;">

                            ₹{{ number_format($totalAmount, 2) }}

                        </h2>

                    </div>

                </div>

            </div>

            <div class="col-lg-3">

                <div class="card shadow">

                    <div class="card-body">

                        <h5>Total Articles</h5>

                        <h2 style="font-size: 18px;">

                            {{ $totalArticles }}

                        </h2>

                    </div>

                </div>

            </div>
            <div class="col-lg-3">

                <div class="card shadow">

                    <div class="card-body">

                        <h5>Web Orders</h5>

                        <h2 class="text-primary" style="font-size: 18px;">

                            {{ $webArticles }} ( <small>

                                ₹{{ number_format($webAmount, 2) }}

                            </small>)

                        </h2>



                    </div>

                </div>

            </div>

            <div class="col-lg-3">

                <div class="card shadow">

                    <div class="card-body">

                        <h5>WhatsApp Orders</h5>

                        <h2 class="text-success" style="font-size: 18px;">

                            {{ $whatsappArticles }} ( <small>

                                ₹{{ number_format($whatsappAmount, 2) }}

                            </small>)

                        </h2>



                    </div>

                </div>

            </div>
        </div>
        <div class="row mb-4">
            <div class="col-lg-3">

                <div class="card shadow">

                    <div class="card-body">

                        <h5>Matched Orders</h5>

                        <h2 class="text-success" style="font-size: 18px;">

                            {{ $matchedArticles }}

                        </h2>

                    </div>

                </div>

            </div>

            <div class="col-lg-3">

                <div class="card shadow">

                    <div class="card-body">

                        <h5>Barcode Not Found</h5>

                        <h2 class="text-danger" style="font-size: 18px;">

                            {{ $unMatchedArticles }}

                        </h2>

                    </div>

                </div>

            </div>
            <div class="col-lg-3">

                <a href="{{ route('payments.pending', request()->all()) }}" style="text-decoration:none;">

                    <div class="card shadow border-danger">

                        <div class="card-body">

                            <h5>Pending Payment</h5>

                            <h2 class="text-danger" style="font-size: 18px;">

                                {{ number_format($pendingPaymentOrders) }} ( <small>

                                    ₹{{ number_format($pendingPaymentAmount, 2) }}

                                </small>)

                            </h2>



                        </div>

                    </div>

                </a>
            </div>
        </div>
        <div class="card shadow mb-4">

            <div class="card-body">

                <form method="GET">

                    <div class="row">

                        <div class="col-lg-3">

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



                        <div class="col-lg-3">

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



                        <div class="col-lg-2">

                            <label>Bill Date From</label>

                            <input type="date" class="form-control" name="from" value="{{ request('from') }}">

                        </div>



                        <div class="col-lg-2">

                            <label>Bill Date To</label>

                            <input type="date" class="form-control" name="to" value="{{ request('to') }}">

                        </div>

                        <div class="col-lg-2">

                            <label>Search</label>

                            <input class="form-control" name="search" value="{{ request('search') }}"
                                placeholder="Barcode / Name">

                        </div>

                    </div>

                    <div class="row mt-3">

                        <div class="col-lg-12">

                            <button class="btn btn-primary">

                                Filter

                            </button>

                            <a href="{{ route('payments.index') }}" class="btn btn-secondary">

                                Reset

                            </a>

                            <a href="{{ route('payments.export', request()->query()) }}" class="btn btn-success float-end">

                                Export Excel

                            </a>

                        </div>

                    </div>

                </form>

            </div>

        </div>
        <div class="card shadow mb-4">

            <div class="card-header">

                <h5>

                    Client Wise Summary

                </h5>

            </div>

            <div class="table-responsive">

                <table class="table table-bordered">

                    <thead>

                        <tr>

                            <th>Client</th>

                            <th>Articles</th>

                            <th>Total Amount</th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($clientSummary as $client)
                            <tr>

                                <td>

                                    {{ $client->client_name }}

                                </td>

                                <td>

                                    {{ $client->articles }}

                                </td>

                                <td>

                                    ₹{{ number_format($client->amount, 2) }}

                                </td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>
        <div class="card shadow mb-4">

            <div class="card-header bg-success text-white">

                <h5 class="mb-0">

                    Payment Details (Matched Orders)

                </h5>

            </div>

            <div class="table-responsive">

                <table class="table table-bordered table-striped table-hover">

                    <thead class="table-dark">

                        <tr>

                            <th>#</th>

                            <th>Article</th>

                            <th>Order ID</th>

                            <th>Client</th>

                            <th>Customer</th>

                            <th>Phone</th>

                            <th width="250">Address</th>

                            <th>Product</th>

                            <th>Qty</th>

                            <th>Weight</th>

                            <th>COD</th>

                            <th>Commission</th>

                            <th>Bill Date</th>

                            <th>Delivered Date</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($payments as $key=>$payment)
                            <tr>

                                <td>

                                    {{ $payments->firstItem() + $key }}

                                </td>

                                <td>

                                    {{ $payment->article_number }}

                                </td>

                                <td>

                                    {{ $payment->order_id }}

                                </td>

                                <td>

                                    {{ $payment->client_name }}

                                </td>

                                <td>

                                    {{ $payment->customer_name }}

                                </td>

                                <td>

                                    {{ $payment->customer_phone }}

                                </td>

                                <td>

                                    {{ $payment->shipping_address }}

                                    <br>

                                    {{ $payment->city }}

                                    {{ $payment->state }}

                                    {{ $payment->pincode }}

                                </td>

                                <td>

                                    {{ $payment->product }}

                                </td>

                                <td>

                                    {{ $payment->quantity }}

                                </td>

                                <td>

                                    {{ $payment->weight }}

                                </td>

                                <td>

                                    ₹{{ number_format($payment->cod_value, 2) }}

                                </td>

                                <td>

                                    ₹{{ number_format($payment->cod_commission, 2) }}

                                </td>

                                <td>

                                    {{ $payment->bill_date }}

                                </td>

                                <td>

                                    {{ $payment->delivered_date }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="14" class="text-center">

                                    No Payment Found

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>
        <div class="mt-3">

            {{ $payments->withQueryString()->links() }}

        </div>
        <div class="card shadow">

            <div class="card-header bg-danger text-white">

                <h5 class="mb-0">

                    Barcode Not Found in Orders

                    ({{ $unMatchedArticles }})

                </h5>

            </div>

            <div class="table-responsive">

                <table class="table table-bordered table-hover">

                    <thead class="table-dark">

                        <tr>

                            <th>#</th>

                            <th>Article Number</th>

                            <th>Invoice</th>

                            <th>Customer</th>

                            <th>COD</th>

                            <th>Bill Date</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($unmatched as $key=>$row)
                            <tr>

                                <td>

                                    {{ $key + 1 }}

                                </td>

                                <td>

                                    {{ $row->article_number }}

                                </td>

                                <td>

                                    {{ $row->cod_invoice_number }}

                                </td>

                                <td>

                                    {{ $row->customer_name }}

                                </td>

                                <td>

                                    ₹{{ number_format($row->cod_value, 2) }}

                                </td>

                                <td>

                                    {{ $row->bill_date }}

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="6" class="text-center">

                                    All Payments Matched Successfully

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>
    @endsection
