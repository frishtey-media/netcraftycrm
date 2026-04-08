@extends('layouts.inventory')

@section('title', 'Add Product')
@section('page-title', 'Add Product')

@section('content')

    <div class="card shadow-sm">
        <div class="card-body">

            <form action="{{ route('products.store') }}" method="POST">
                @csrf

                @include('inventory.products.form')

                <button type="submit" class="btn btn-success">
                    Save Product
                </button>

                <a href="{{ route('products.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
            </form>

        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const priceInput = document.getElementById("price");
            const quantityInput = document.getElementById("quantity");
            const totalInput = document.getElementById("total_price");

            function calculateTotal() {
                let price = parseFloat(priceInput.value) || 0;
                let quantity = parseFloat(quantityInput.value) || 0;
                totalInput.value = (price * quantity).toFixed(2);
            }

            priceInput.addEventListener("input", calculateTotal);
            quantityInput.addEventListener("input", calculateTotal);

        });
    </script>

@endsection
