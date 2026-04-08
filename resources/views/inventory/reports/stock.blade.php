@extends('layouts.inventory')

@section('title', 'Stock Report')
@section('page-title', 'Stock Report')

@section('content')

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Product</th>
                <th>Warehouse</th>
                <th>Stock</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($stocks as $stock)
                <tr>
                    <td>{{ $stock->product->name }}</td>
                    <td>{{ $stock->warehouse->name }}</td>
                    <td>
                        @if ($stock->stock <= $stock->product->low_stock_alert)
                            <span class="badge bg-danger">
                                {{ $stock->stock }}
                            </span>
                        @else
                            {{ $stock->stock }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

@endsection
