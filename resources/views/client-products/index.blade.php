@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <h4 class="mb-4">Client Product Weight Setup</h4>


        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @error('shopify_product_name')
            <span class="alert alert-danger">{{ $message }}</span>
        @enderror

        <div class="card mb-4">
            <div class="card-header">Add / Update Product Weight</div>
            <div class="card-body">
                <form method="POST" action="{{ route('client.products.store') }}">
                    @csrf

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Client</label>
                            <select name="client_id" class="form-control" required>
                                <option value="">Select Client</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->client_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Product Name (Shopify)</label>
                            <input type="text" name="shopify_product_name" class="form-control" required>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>Weight per unit (gm)</label>
                            <input type="number" name="weight_per_unit" class="form-control" required>
                        </div>

                        <div class="col-md-2 mb-3 d-flex align-items-end">
                            <button class="btn btn-primary w-100">Save</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>

        {{-- PRODUCT WEIGHT LIST --}}
        <div class="card">
            <div class="card-header">Client Product List</div>
            <div class="card-body p-0">
                <table class="table table-bordered table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th>Product</th>
                            <th>Weight (gm)</th>
                            <!-- <th>Action</th>-->
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td>{{ $loop->iteration }}</td>


                                <td>{{ $product->client->client_name ?? '-' }}</td>


                                <td>{{ $product->shopify_product_name }}</td>


                                <td>{{ $product->weight_per_unit }}</td>


                                <!--<td>
                                                            <form method="POST" action="{{ route('client.products.delete', $product->id) }}"
                                                                onsubmit="return confirm('Delete this product?')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="btn btn-sm btn-danger">
                                                                    Delete
                                                                </button>
                                                            </form>
                                                        </td>--->
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center">
                                    No products added yet
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>
    </div>
@endsection
