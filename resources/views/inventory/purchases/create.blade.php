@extends('layouts.inventory')

@section('title', 'Create Purchase')
@section('page-title', 'Create Purchase')

@section('content')
    {{-- Success Message --}}
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    {{-- Validation Errors --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Whoops! Something went wrong.</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('purchases.store') }}">
        @csrf

        <div class="row mb-3">
            <div class="col-md-4">
                <label>Supplier</label>
                <select name="supplier_id" class="form-control" required>
                    @foreach ($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label>Warehouse</label>
                <select name="warehouse_id" class="form-control" required>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label>Date</label>
                <input type="date" name="purchase_date" class="form-control" required>
            </div>
        </div>

        <hr>

        <h5>Add Products</h5>

        <div id="items">
            <div class="row mb-2">
                <div class="col-md-4">
                    <select name="products[0][product_id]" class="form-control">
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <input type="number" name="products[0][quantity]" placeholder="Qty" class="form-control">
                </div>

                <div class="col-md-3">
                    <input type="number" step="0.01" name="products[0][price]" placeholder="Price" class="form-control">
                </div>
            </div>
        </div>


        <button type="submit" class="btn btn-success mt-3">
            Save Purchase
        </button>
    </form>

@endsection
