@extends('layouts.inventory')

@section('title', 'Products')
@section('page-title', 'Products')

@section('content')

    <div class="card shadow-sm">
        <div class="card-body">

            {{-- Top Header --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Product List</h5>

                <a href="{{ route('products.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Product
                </a>
            </div>

            {{-- Search Filter --}}
            <form method="GET" action="{{ route('products.index') }}" class="row mb-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or SKU..."
                        value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100">
                        Filter
                    </button>
                </div>
            </form>

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Warehouse</th>
                            <th>Product Name</th>
                            <!--  <th>Price</th>-->
                            <th>Stock</th>
                            <th>Date</th>
                            <th width="150">Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold">
                                    {{ $product->category->name ?? 'N/A' }}
                                </td>

                                <td class="fw-semibold">
                                    {{ $product->warehouse->name ?? 'N/A' }}
                                </td>

                                <td class="fw-semibold">
                                    {{ $product->name }}
                                </td>

                                <!-- <td class="text-success fw-bold">
                                                ₹{{ number_format($product->price, 2) }}
                                            </td>-->
                                <td>
                                    @if ($product->low_stock_alert <= 5)
                                        <span class="badge bg-danger">
                                            {{ $product->low_stock_alert }}
                                        </span>
                                    @else
                                        <span class="badge bg-success">
                                            {{ $product->low_stock_alert }}
                                        </span>
                                    @endif
                                </td>
                                <td class="fw-semibold">
                                    {{ $product->created_at }}
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="javascript:void(0);" class="btn btn-sm btn-danger updateStockBtn"
                                            data-id="{{ $product->id }}" data-name="{{ $product->name }}"
                                            data-stock="{{ $product->low_stock_alert }}"
                                            data-price="{{ $product->price }}">
                                            Update Stock
                                        </a>

                                        <!-- <a href="{{ route('products.edit', $product->id) }}"
                                                            class="btn btn-sm btn-warning">
                                                            Edit
                                                        </a>-->

                                        <!--<form action="{{ route('products.destroy', $product->id) }}" method="POST">
                                                </form>-->
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    No products found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-3">
                {{ $products->withQueryString()->links() }}
            </div>

        </div>
    </div>
    <div class="modal fade" id="updateStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Add Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <form action="{{ route('products.updateStock') }}" method="POST">
                    @csrf

                    <div class="modal-body">

                        <input type="hidden" name="product_id" id="modal_product_id">

                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" id="modal_product_name" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Current Stock</label>
                            <input type="number" id="modal_current_stock" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Per Stock Price</label>
                            <input type="text" id="modal_price" class="form-control" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Add Stock Quantity</label>
                            <input type="number" name="low_stock_alert" class="form-control" required>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">
                            Add Stock
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
    <script>
        function calculateTotal() {
            let price = parseFloat(document.getElementById('price').value) || 0;
            let quantity = parseFloat(document.getElementById('quantity').value) || 0;
            document.getElementById('total_price').value = (price * quantity).toFixed(2);
        }

        document.getElementById('price').addEventListener('input', calculateTotal);
        document.getElementById('quantity').addEventListener('input', calculateTotal);
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const buttons = document.querySelectorAll(".updateStockBtn");
            const modal = new bootstrap.Modal(document.getElementById("updateStockModal"));

            buttons.forEach(button => {
                button.addEventListener("click", function() {

                    document.getElementById("modal_product_id").value = this.dataset.id;
                    document.getElementById("modal_product_name").value = this.dataset.name;
                    document.getElementById("modal_current_stock").value = this.dataset.stock;
                    document.getElementById("modal_price").value = this.dataset.price;

                    modal.show();
                });
            });

        });
    </script>


@endsection
