@extends('layouts.inventory')

@section('title', 'Create Purchase')
@section('page-title', 'Create Purchase')

@section('content')

    <form method="POST" action="{{ route('sales.store') }}">
        @csrf

        <div class="row mb-3">

            <div class="col-md-4">
                <label>Warehouse</label>
                <select name="warehouse_id" class="form-control" required>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label>Date</label>
                <input type="date" name="sale_date" class="form-control" required>
            </div>
        </div>

        <hr>

        <div id="items">
            <div class="row mb-2">
                <div class="col-md-4">
                    <select name="items[0][product_id]" class="form-control">
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->name }} (Stock: {{ $product->low_stock_alert }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <input type="number" name="items[0][quantity]" placeholder="Qty" class="form-control">
                </div>

            </div>
        </div>

        <button type="submit" class="btn btn-danger mt-3">
            Save Sale
        </button>
    </form>


@endsection
