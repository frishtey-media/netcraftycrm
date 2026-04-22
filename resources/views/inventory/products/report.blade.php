@extends('layouts.inventory')

@section('title', 'Product Report')
@section('page-title', 'Product Report')

@section('content')

    <div class="card shadow p-4">

        <h4 class="mb-4">Product Stock Report</h4>
        <form method="GET" action="{{ route('products.report') }}" class="row mb-3">

            <div class="col-md-3">
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
            </div>

            <div class="col-md-3">
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
            </div>


            <div class="col-md-3">
                <select name="product_id" class="form-control">
                    <option value="">All Products</option>
                    @foreach ($allProducts as $product)
                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }}
                        </option>
                    @endforeach
                </select>
            </div>


            <!--  <div class="col-md-3">
                    <input type="text" name="product_name" value="{{ request('product_name') }}" placeholder="Search Product"
                        class="form-control">
                </div>-->

            <div class="col-md-3 mt-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Filter</button>

                <a href="{{ route('products.report.export', request()->all()) }}" class="btn btn-success">
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
                        <th>Total Stock Value</th>
                        <th>Date</th>
                        <th>Low Stock Alert</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $grandTotalQty = 0;
                        $grandTotalValue = 0;
                        $sr = 1;
                    @endphp

                    @forelse($products as $productId => $items)

                        @php
                            $product = $items->first()->product;
                            $totalQty = 0;
                            $totalValue = 0;
                        @endphp


                        <tr class="table-primary">
                            <td colspan="9">
                                <strong>Product: {{ $product->name }}</strong>
                            </td>
                        </tr>

                        @foreach ($items as $item)
                            @php
                                $positiveTypes = ['in', 'created', 'updated', 'rto_restored'];

                                $qty = in_array($item->type, $positiveTypes) ? $item->quantity : -$item->quantity;

                                $value = $qty * $item->price;

                                $totalQty += $qty;
                                $totalValue += $value;
                            @endphp
                            <tr>
                                <td>{{ $sr++ }}</td>
                                <td>{{ $product->warehouse->name ?? '' }}</td>
                                <td>{{ $product->category->name ?? '' }}</td>
                                <td>{{ $product->name }}</td>
                                <td>₹ {{ number_format($item->price, 2) }}</td>
                                <td>{{ $qty }}</td>
                                <td>₹ {{ number_format($value, 2) }}</td>
                                <td>{{ \Carbon\Carbon::parse($item->movement_date)->format('d-m-Y') }}</td>
                                <td>
                                    @if ($item->type == 'created')
                                        <span class="badge bg-success">Stock Created</span>
                                    @elseif ($item->type == 'updated')
                                        <span class="badge bg-danger">Stock Updated</span>
                                    @elseif ($item->type == 'rto_restored')
                                        <span class="badge bg-primary">RTO Re-Stock</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        <tr class="table-warning">
                            <td colspan="5" class="text-end"><strong>Product Total</strong></td>
                            <td><strong>{{ $totalQty }}</strong></td>
                            <td><strong>₹ {{ number_format($totalValue, 2) }}</strong></td>
                            <td></td>
                            <td></td>
                        </tr>

                        @php
                            $grandTotalQty += $totalQty;
                            $grandTotalValue += $totalValue;
                        @endphp

                    @empty
                        <tr>
                            <td colspan="9" class="text-center">No Data Found</td>
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
                            <th></th>
                        </tr>
                    </tfoot>
                @endif

            </table>
        </div>

    </div>

@endsection
