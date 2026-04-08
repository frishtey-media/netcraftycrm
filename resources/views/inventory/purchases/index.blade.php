@extends('layouts.inventory')

@section('title', 'Purchases')
@section('page-title', 'Purchase List')

@section('content')

    <div class="card shadow-sm">
        <div class="card-body">

            <div class="d-flex justify-content-between mb-3">
                <h5>All Purchases</h5>
                <a href="{{ route('purchases.create') }}" class="btn btn-primary">
                    + New Purchase
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Invoice</th>
                            <th>Supplier</th>
                            <th>Warehouse</th>
                            <th>Date</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $key => $purchase)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $purchase->invoice_no }}</td>
                                <td>{{ $purchase->supplier->name ?? '-' }}</td>
                                <td>{{ $purchase->warehouse->name ?? '-' }}</td>
                                <td>{{ $purchase->purchase_date }}</td>
                                <td>₹ {{ number_format($purchase->total_amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">No Purchases Found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

@endsection
