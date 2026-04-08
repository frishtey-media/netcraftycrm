@extends('layouts.inventory')

@section('title', 'Edit Product')
@section('page-title', 'Edit Product')

@section('content')

    <div class="card shadow-sm">
        <div class="card-body">

            <form action="{{ route('products.update', $product->id) }}" method="POST">
                @csrf
                @method('PUT')

                @include('inventory.products.form')

                <button type="submit" class="btn btn-primary">
                    Update Product
                </button>

                <a href="{{ route('products.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
            </form>

        </div>
    </div>

@endsection
