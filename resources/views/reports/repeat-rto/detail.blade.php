@extends('layouts.admin')

@section('content')
    <div class="container-fluid">

        <h3>

            Customer History

        </h3>

        <hr>
        <div class="row">

            <div class="col-md-3">

                <div class="card">

                    <div class="card-body">

                        <h5>Total Orders</h5>

                        <h2>{{ $summary['total_orders'] }}</h2>

                    </div>

                </div>

            </div>

            <div class="col-md-3">

                <div class="card bg-success text-white">

                    <div class="card-body">

                        <h5>Delivered</h5>

                        <h2>{{ $summary['delivered'] }}</h2>

                    </div>

                </div>

            </div>

            <div class="col-md-3">

                <div class="card bg-danger text-white">

                    <div class="card-body">

                        <h5>RTO</h5>

                        <h2>{{ $summary['rto'] }}</h2>

                    </div>

                </div>

            </div>

            <div class="col-md-3">

                <div class="card bg-info text-white">

                    <div class="card-body">

                        <h5>Revenue</h5>

                        <h2>

                            ₹ {{ number_format($summary['total_amount']) }}

                        </h2>

                    </div>

                </div>

            </div>

        </div>

        <hr>

        <h4>

            Customer Timeline

        </h4>

        <table class="table table-bordered">

            <thead>

                <tr>

                    <th>Date</th>

                    <th>Order ID</th>

                    <th>Barcode</th>

                    <th>Product</th>

                    <th>Amount</th>

                    <th>Staff</th>

                    <th>Source</th>

                    <th>Status</th>

                </tr>

            </thead>

            <tbody>

                @foreach ($orders as $row)
                    <tr>

                        <td>{{ $row->created_at }}</td>

                        <td>{{ $row->order_id }}</td>

                        <td>{{ $row->barcode }}</td>

                        <td>{{ $row->product }}</td>

                        <td>{{ $row->amount }}</td>

                        <td>{{ $row->staff_name }}</td>

                        <td>{{ $row->order_source }}</td>

                        <td>

                            @if ($row->delivery_status == 'Delivered')
                                <span class="badge badge-success">

                                    Delivered

                                </span>
                            @else
                                <span class="badge badge-danger">

                                    {{ $row->delivery_status }}

                                </span>
                            @endif

                        </td>

                    </tr>
                @endforeach

            </tbody>

        </table>
        <div class="card mt-4">

            <div class="card-header">

                <h5>Product Summary</h5>

            </div>

            <div class="card-body">

                <table class="table table-bordered">

                    <thead>

                        <tr>

                            <th>Product</th>

                            <th>Total Orders</th>

                            <th>Delivered</th>

                            <th>RTO</th>

                            <th>Total Revenue</th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($productSummary as $row)
                            <tr>

                                <td>{{ $row->product }}</td>

                                <td>{{ $row->orders }}</td>

                                <td>

                                    <span class="badge bg-success">

                                        {{ $row->delivered }}

                                    </span>

                                </td>

                                <td>

                                    <span class="badge bg-danger">

                                        {{ $row->rto }}

                                    </span>

                                </td>

                                <td>

                                    ₹ {{ number_format($row->amount) }}

                                </td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>

        <div class="card mt-4">

            <div class="card-header">

                <h5>

                    Staff Performance

                </h5>

            </div>

            <div class="card-body">

                <table class="table table-bordered">

                    <thead>

                        <tr>

                            <th>Staff</th>

                            <th>Total Orders</th>

                            <th>Delivered</th>

                            <th>RTO</th>

                            <th>Success %</th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($staffSummary as $row)
                            <tr>

                                <td>{{ $row->name ?? 'Not Assigned' }}</td>

                                <td>{{ $row->total_orders }}</td>

                                <td>{{ $row->delivered }}</td>

                                <td>{{ $row->rto }}</td>

                                <td>

                                    {{ $row->total_orders > 0 ? round(($row->delivered / $row->total_orders) * 100, 2) : 0 }}
                                    %

                                </td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>

        <div class="card mt-4">

            <div class="card-header">

                <h5>

                    Web / WhatsApp Summary

                </h5>

            </div>

            <div class="card-body">

                <table class="table table-bordered">

                    <thead>

                        <tr>

                            <th>Source</th>

                            <th>Orders</th>

                            <th>Delivered</th>

                            <th>RTO</th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($sourceSummary as $row)
                            <tr>

                                <td>{{ $row->order_source }}</td>

                                <td>{{ $row->total }}</td>

                                <td>{{ $row->delivered }}</td>

                                <td>{{ $row->rto }}</td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>
        <div class="card mt-4">

            <div class="card-header">

                <h5>

                    Order Timeline

                </h5>

            </div>

            <div class="card-body">

                <ul class="timeline">

                    @foreach ($timeline as $row)
                        <li>

                            <b>{{ date('d M Y', strtotime($row['date'])) }}</b>

                            <br>

                            {{ $row['product'] }}

                            <br>

                            Barcode :

                            {{ $row['barcode'] }}

                            <br>

                            Staff :

                            {{ $row['staff'] }}

                            <br>

                            @if ($row['status'] == 'Delivered')
                                <span class="badge bg-success">

                                    Delivered

                                </span>
                            @else
                                <span class="badge bg-danger">

                                    {{ $row['status'] }}

                                </span>
                            @endif

                        </li>
                    @endforeach

                </ul>

            </div>

        </div>
        <div class="card mb-3">

            <div class="card-body">

                <h4>

                    {{ $customer->customer_name }}

                </h4>

                <p>

                    <strong>Phone :</strong>

                    {{ $customer->customer_phone }}

                </p>

                <p>

                    <strong>Address :</strong>

                    {{ $customer->shipping_address }}

                </p>

                <p>

                    <strong>City :</strong>

                    {{ $customer->city }}

                </p>

            </div>

        </div>
        <div class="card mt-4">

            <div class="card-header">

                <h5>Recovery Chain</h5>

            </div>

            <div class="card-body">

                <table class="table table-bordered">

                    <thead>

                        <tr>

                            <th>Attempt</th>

                            <th>Order</th>

                            <th>Barcode</th>

                            <th>Product</th>

                            <th>Staff</th>

                            <th>Source</th>

                            <th>Status</th>

                            <th>Amount</th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($recoveryChain as $row)
                            <tr>

                                <td>{{ $row['attempt'] }}</td>

                                <td>{{ $row['order_id'] }}</td>

                                <td>{{ $row['barcode'] }}</td>

                                <td>{{ $row['product'] }}</td>

                                <td>{{ $row['staff'] }}</td>

                                <td>{{ $row['source'] }}</td>

                                <td>

                                    @if ($row['status'] == 'Delivered')
                                        <span class="badge bg-success">

                                            Delivered

                                        </span>
                                    @else
                                        <span class="badge bg-danger">

                                            {{ $row['status'] }}

                                        </span>
                                    @endif

                                </td>

                                <td>

                                    ₹ {{ number_format($row['amount']) }}

                                </td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>
        <div class="row mb-4">

            <div class="col-md-4">

                <div class="card bg-primary text-white">

                    <div class="card-body">

                        <h5>Total Attempts</h5>

                        <h2>{{ $totalAttempts }}</h2>

                    </div>

                </div>

            </div>

            <div class="col-md-4">

                <div class="card bg-warning">

                    <div class="card-body">

                        <h5>Recovered On</h5>

                        <h2>

                            {{ $successfulAttempt ?? '-' }}

                        </h2>

                    </div>

                </div>

            </div>

            <div class="col-md-4">

                <div class="card bg-success text-white">

                    <div class="card-body">

                        <h5>Final Status</h5>

                        <h2>

                            {{ $finalStatus }}

                        </h2>

                    </div>

                </div>

            </div>

        </div>
        <div class="card mt-4">

            <div class="card-header">

                <h5>Staff Recovery Performance</h5>

            </div>

            <div class="card-body">

                <table class="table table-bordered">

                    <thead>

                        <tr>

                            <th>Staff</th>

                            <th>Orders</th>

                            <th>Delivered</th>

                            <th>RTO</th>

                            <th>Revenue</th>

                            <th>Success %</th>

                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($staffRecovery as $row)
                            <tr>

                                <td>{{ $row->name }}</td>

                                <td>{{ $row->total }}</td>

                                <td>{{ $row->delivered }}</td>

                                <td>{{ $row->rto }}</td>

                                <td>₹ {{ number_format($row->revenue) }}</td>

                                <td>

                                    {{ $row->total > 0 ? round(($row->delivered / $row->total) * 100, 2) : 0 }} %

                                </td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>
    @endsection
