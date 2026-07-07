@extends('layouts.admin')

@section('title', $title)

@section('content')

    <div class="container-fluid">

        <!-- Header -->

        <div class="card shadow-sm mb-4">

            <div class="card-header bg-primary text-white d-flex justify-content-between">

                <h4 class="mb-0">
                    {{ $title }}
                </h4>

                <a href="{{ route('reports.export', $type) }}" class="btn btn-success">

                    <i class="fas fa-file-excel"></i>

                    Export Excel

                </a>

            </div>

            <div class="card-body">

                <h5>

                    Total Orders :

                    <span class="badge bg-primary">

                        {{ $totalOrders }}

                    </span>

                </h5>

            </div>

        </div>



        <!-- Client Summary -->

        <div class="card shadow-sm mb-4">

            <div class="card-header bg-success text-white">

                <h5 class="mb-0">

                    Client Wise Summary

                </h5>

            </div>

            <div class="table-responsive">

                <table class="table table-bordered table-hover mb-0">

                    <thead class="table-dark">

                        <tr>

                            <th width="80">#</th>

                            <th>Client Name</th>

                            <th width="150">Orders</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($clientSummary as $key=>$client)
                            <tr>

                                <td>

                                    {{ $key + 1 }}

                                </td>

                                <td>

                                    {{ $client->client_name }}

                                </td>

                                <td>

                                    <span class="badge bg-success">

                                        {{ $client->total }}

                                    </span>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="3" class="text-center">

                                    No Record Found

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>




        <!-- Orders -->

        <div class="card shadow-sm">

            <div class="card-header bg-dark text-white">

                <h5 class="mb-0">

                    Orders List

                </h5>

            </div>

            <div class="table-responsive">

                <table class="table table-bordered table-striped table-hover mb-0">

                    <thead class="table-dark">

                        <tr>

                            <th>#</th>

                            <th>Order ID</th>

                            <th>Barcode</th>

                            <th>Client</th>

                            <th>Customer</th>

                            <th>Phone</th>

                            <th>Product</th>
                            <th>created_at</th>


                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($orders as $key=>$order)
                            <tr>

                                <td>

                                    {{ $key + 1 }}

                                </td>

                                <td>

                                    {{ $order->order_id }}

                                </td>

                                <td>

                                    {{ $order->barcode }}

                                </td>

                                <td>

                                    {{ optional($order->client)->client_name }}

                                </td>

                                <td>

                                    {{ $order->customer_name }}

                                </td>

                                <td>

                                    {{ $order->customer_phone }}

                                </td>

                                <td>

                                    {{ $order->product }}

                                </td>
                                <td>

                                    {{ $order->created_at }}

                                </td>

                                <td>

                                    @if ($order->delivery_status == 'Delivered')
                                        <span class="badge bg-success">

                                            Delivered

                                        </span>
                                    @elseif($order->delivery_status == 'RTO')
                                        <span class="badge bg-danger">

                                            RTO

                                        </span>
                                    @elseif($order->delivery_status == 'In Transit')
                                        <span class="badge bg-warning text-dark">

                                            In Transit

                                        </span>
                                    @else
                                        <span class="badge bg-secondary">

                                            No Status

                                        </span>
                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="8" class="text-center">

                                    No Orders Found

                                </td>

                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

@endsection
