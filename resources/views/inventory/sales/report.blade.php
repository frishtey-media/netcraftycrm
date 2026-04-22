@extends('layouts.inventory')

@section('title', 'Sales Report')
@section('page-title', 'Sales Report')

@section('content')

    <div class="card shadow p-4">

        <h4 class="mb-4">Sales Report</h4>

        {{-- FILTER FORM --}}
        <form method="GET" action="{{ route('sales.report') }}" class="row mb-3">

            <div class="col-md-3">
                <input type="date" name="from" value="{{ request('from') }}" class="form-control">
            </div>

            <div class="col-md-3">
                <input type="date" name="to" value="{{ request('to') }}" class="form-control">
            </div>

            {{-- ✅ Product Filter --}}
            <div class="col-md-3">
                <select name="product_id" class="form-control">
                    <option value="">All Products</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    Filter
                </button>

                <a href="{{ route('sales.report.export', request()->all()) }}" class="btn btn-success">
                    Export Excel
                </a>
            </div>

        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-striped">

                <thead class="table-dark">
                    <tr>
                        <th>#</th>
                        <th>Warehouse</th>
                        <th>Client Name</th>
                        <th>Product Name</th>
                        <th>Unit Price</th>
                        <th>Quantity</th>
                        <th>Total Sale Value</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>

                    @php
                        $grandTotalQty = 0;
                        $grandTotalValue = 0;
                        $sr = 1;
                    @endphp

                    @forelse($sales as $productId => $items)

                        @php
                            $product = $items->first()->product;
                            $totalQty = 0;
                            $totalValue = 0;
                        @endphp

                        {{-- Product Header --}}
                        <tr class="table-primary">
                            <td colspan="8">
                                <strong>Product: {{ $product->name }}</strong>
                            </td>
                        </tr>

                        @foreach ($items as $item)
                            @php
                                $qty = $item->quantity;
                                $value = $item->subtotal;

                                $totalQty += $qty;
                                $totalValue += $value;
                            @endphp

                            <tr>
                                <td>{{ $sr++ }}</td>
                                <td>{{ $product->warehouse->name ?? '' }}</td>
                                <td>{{ $product->client->name ?? '' }}</td>
                                <td>{{ $product->name }}</td>
                                <td>₹ {{ number_format($item->price, 2) }}</td>
                                <td>{{ $qty }}</td>
                                <td>₹ {{ number_format($value, 2) }}</td>
                                <td>{{ $item->created_at->format('d-m-Y') }}</td>
                            </tr>
                        @endforeach

                        {{-- Product Total --}}
                        <tr class="table-warning">
                            <td colspan="5" class="text-end">
                                <strong>Product Total</strong>
                            </td>
                            <td><strong>{{ $totalQty }}</strong></td>
                            <td><strong>₹ {{ number_format($totalValue, 2) }}</strong></td>
                            <td></td>
                        </tr>

                        @php
                            $grandTotalQty += $totalQty;
                            $grandTotalValue += $totalValue;
                        @endphp

                    @empty
                        <tr>
                            <td colspan="8" class="text-center">No Sales Found</td>
                        </tr>
                    @endforelse

                </tbody>

                @if ($grandTotalQty > 0)
                    <tfoot>
                        <tr class="table-success">
                            <th colspan="5" class="text-end">Grand Total</th>
                            <th>{{ $grandTotalQty }}</th>
                            <th>₹ {{ number_format($grandTotalValue, 2) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                @endif

            </table>
        </div>

    </div>

@endsection
