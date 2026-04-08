@extends('layouts.inventory')

@section('title', 'Sales')
@section('page-title', 'Sales List')

@section('content')

    <div class="card shadow-sm">
        <div class="card-body">

            <div class="d-flex justify-content-between mb-3">
                <h5>All Sales</h5>
                <a href="{{ route('sales.create') }}" class="btn btn-primary">
                    + New Sale
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Invoice No.</th>
                            <th>Warehouse</th>
                            <th>Products</th>
                            <th>Total Qty</th>

                            <th>Unit Price</th>
                            <th>Total Price</th>
                            <th>Date</th>
                            <th>Invoice</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $key => $sale)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $sale->invoice_no }}</td>
                                <td>{{ $sale->warehouse->name ?? '-' }}</td>
                                <td>
                                    @foreach ($sale->items as $item)
                                        {{ $item->product->name ?? '-' }} <br>
                                    @endforeach
                                </td>
                                <td>
                                    {{ $sale->items_sum_quantity ?? 0 }}
                                </td>

                                <td>
                                    ₹ {{ number_format($sale->items->sum('price'), 2) }}
                                </td>
                                <td>
                                    ₹ {{ number_format($sale->items->sum('subtotal'), 2) }}
                                </td>
                                <td>{{ $sale->sale_date }}</td>
                                <td>
                                    <a href="{{ route('sales.invoice', $sale->id) }}" class="btn btn-sm btn-warning">
                                        Print Invoice
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">No Sales Found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

@endsection
