@extends('layouts.inventory')

@section('title', 'Products')
@section('page-title', 'Products')

@section('content')

    <div class="card shadow-sm">
        <div class="card-body">

            {{-- Top Header --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Product List</h5>

                <a href="{{ route('products.create') }}" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Add New Product
                </a>
            </div>



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
                            <th width="150">Update Stock</th>

                        </tr>
                    </thead>

                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold">
                                    {{ $product->client->client_name ?? 'N/A' }}
                                </td>

                                <td class="fw-semibold">
                                    {{ $product->warehouse->name ?? 'N/A' }}
                                </td>
                                <td class="fw-semibold">
                                    {{ $product->clientProduct->shopify_product_name ?? 'N/A' }}
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
                                    {{ $product->updated_at }}
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <!--  <a href="javascript:void(0);" class="btn btn-sm btn-success updateStockBtn"
                                                                                                                                                                                               </a>-->
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#updateStockModal" data-id="{{ $product->id }}"
                                            data-name="{{ $product->clientProduct->shopify_product_name ?? 'N/A' }}"
                                            data-stock="{{ $product->low_stock_alert }}"
                                            data-price="{{ $product->price }}">
                                            Add Stock
                                        </button>
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






    <div class="modal fade" id="updateStockModal" tabindex="-1" aria-hidden="true">

        <div class="modal-dialog">

            <div class="modal-content">

                <form action="{{ url('inventory/products/update-stock') }}" method="POST">

                    @csrf

                    <div class="modal-header">

                        <h5 class="modal-title">
                            Add Stock
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                    </div>


                    <div class="modal-body">

                        {{-- Product ID --}}
                        <input type="hidden" name="product_id" id="modal_product_id1">


                        {{-- Product Name --}}
                        <div class="mb-3">

                            <label class="form-label">
                                Product Name
                            </label>

                            <input type="text" id="modal_product_name" class="form-control" readonly>

                        </div>


                        {{-- Current Stock --}}
                        <div class="mb-3">

                            <label class="form-label">
                                Current Stock
                            </label>

                            <input type="number" id="modal_current_stock" class="form-control" readonly>

                        </div>


                        {{-- Price --}}
                        <div class="mb-3">

                            <label class="form-label">
                                Price
                            </label>

                            <input type="text" id="modal_price" class="form-control" readonly>

                        </div>


                        {{-- Add Stock --}}
                        <div class="mb-3">

                            <label class="form-label">
                                Add Stock
                            </label>

                            <input type="number" name="low_stock_alert" class="form-control" min="1" required>

                        </div>

                    </div>


                    <div class="modal-footer">

                        <button type="submit" class="btn btn-primary">
                            Update Stock
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
            const modal = document.getElementById("updateStockModal");

            modal.addEventListener("show.bs.modal", function(event) {
                const button = event.relatedTarget;
                console.log(button.getAttribute("data-id"));
                document.getElementById("modal_product_id1").value = button.getAttribute("data-id");
                //console.log(button.getAttribute("data-name"));
                document.getElementById("modal_product_name").value = button.getAttribute("data-name");
                document.getElementById("modal_current_stock").value = button.getAttribute("data-stock");
                document.getElementById("modal_price").value = button.getAttribute("data-price");
            });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const buttons = document.querySelectorAll(".updateStockBtnrto");
            const modal = new bootstrap.Modal(document.getElementById("updateStockModalrto"));

            buttons.forEach(button => {
                button.addEventListener("click", function() {

                    document.getElementById("modal_product_id").value = this.dataset.id;
                    document.getElementById("modal_product_name1").value = this.dataset.name;

                    modal.show();
                });
            });

        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const modal = document.getElementById("updateStockModal");

            if (!modal) {
                return;
            }

            modal.addEventListener("show.bs.modal", function(event) {

                const button = event.relatedTarget;

                const productId =
                    button.getAttribute("data-id");

                const productName =
                    button.getAttribute("data-name");

                const currentStock =
                    button.getAttribute("data-stock");

                const price =
                    button.getAttribute("data-price");


                document.getElementById(
                    "modal_product_id1"
                ).value = productId;


                document.getElementById(
                    "modal_product_name"
                ).value = productName;


                document.getElementById(
                    "modal_current_stock"
                ).value = currentStock;


                document.getElementById(
                    "modal_price"
                ).value = price;

            });

        });
    </script>


@endsection
