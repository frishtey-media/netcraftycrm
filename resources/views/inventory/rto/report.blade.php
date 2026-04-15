@extends('layouts.inventory')

@section('title', 'RTO Report')
@section('page-title', 'RTO Report')

@section('content')

    <div class="card shadow p-4">

        <h4 class="mb-4">RTO Orders Report</h4>

        <form method="GET" action="{{ route('rto.report') }}" class="row mb-3">

            <div class="col-md-3">
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
            </div>

            <div class="col-md-3">
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
            </div>

            <div class="col-md-3">
                <input type="text" name="tracking_no" value="{{ request('tracking_no') }}"
                    placeholder="Search Tracking No" class="form-control">
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="{{ route('sales.report.export', request()->all()) }}" class="btn btn-success">
                    Export Excel
                </a>
            </div>

        </form>
        <!--  @if (\DB::table('rto_reports')->where('is_restocked', 0)->exists())
    <form method="POST" action="{{ route('rto.restock') }}">
                        @csrf
                        <button class="btn btn-warning">RTO Re-Stock</button>
                    </form>
@else
    <button class="btn btn-secondary" disabled>Already Restocked</button>
    @endif-->
        <div class="table-responsive">

            <table class="table table-bordered table-striped">

                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Order ID</th>
                        <th>Tracking No</th>
                        <th>Customer Name</th>
                        <th>Phone</th>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Amount</th>
                        <th>Order Date</th>
                    </tr>
                </thead>

                <tbody>

                    @php
                        $grandQty = 0;
                        $grandAmount = 0;
                        $sr = 1;
                    @endphp

                    @forelse($rtoReports as $product => $items)

                        @php
                            $productQty = 0;
                            $productAmount = 0;
                        @endphp

                        <tr class="table-primary">
                            <td colspan="9"><strong>Product: {{ $product }}</strong></td>
                        </tr>

                        @foreach ($items as $rto)
                            @php
                                $productQty += $rto->quantity;
                                $productAmount += $rto->amount;
                            @endphp

                            <tr>
                                <td>{{ $sr++ }}</td>
                                <td>{{ $rto->order_id }}</td>
                                <td>{{ $rto->tracking_no }}</td>
                                <td>{{ $rto->customer_name }}</td>
                                <td>{{ $rto->customer_phone }}</td>
                                <td>{{ $rto->product }}</td>
                                <td>{{ $rto->quantity }}</td>
                                <td>₹ {{ number_format($rto->amount, 2) }}</td>
                                <td>{{ \Carbon\Carbon::parse($rto->order_date)->format('d-m-Y') }}</td>
                            </tr>
                        @endforeach

                        <tr class="table-warning">
                            <td colspan="6" class="text-end"><strong>Product Total</strong></td>
                            <td><strong>{{ $productQty }}</strong></td>
                            <td><strong>₹ {{ number_format($productAmount, 2) }}</strong></td>
                            <td></td>
                        </tr>

                        @php
                            $grandQty += $productQty;
                            $grandAmount += $productAmount;
                        @endphp

                    @empty

                        <tr>
                            <td colspan="9" class="text-center">No Data Found</td>
                        </tr>

                    @endforelse

                </tbody>

                <tfoot>
                    <tr class="table-success">
                        <th colspan="6" class="text-end">Grand Total</th>
                        <th>{{ $grandQty }}</th>
                        <th>₹ {{ number_format($grandAmount, 2) }}</th>
                        <th></th>
                    </tr>
                </tfoot>

            </table>

        </div>

    </div>

@endsection
