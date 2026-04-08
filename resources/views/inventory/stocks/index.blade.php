@extends('layouts.inventory')

@section('title', 'Inventory Stock')
@section('page-title', 'Inventory Environment')

@section('content')

    <div class="card shadow-sm">
        <div class="card-body">

            <h5 class="mb-3">Warehouse Wise Stock</h5>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Warehouse</th>
                            <th>Quantity</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>

                        @forelse($stocks as $key => $stock)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $stock->product->name ?? '-' }}</td>
                                <td>{{ $stock->warehouse->name ?? '-' }}</td>

                                <td>
                                    <strong>{{ $stock->quantity }}</strong>
                                </td>

                                <td>
                                    @if ($stock->quantity == 0)
                                        <span class="badge bg-danger">Out of Stock</span>
                                    @elseif($stock->quantity <= 5)
                                        <span class="badge bg-warning text-dark">Low Stock</span>
                                    @else
                                        <span class="badge bg-success">In Stock</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    No Stock Available
                                </td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>

        </div>
    </div>

@endsection
