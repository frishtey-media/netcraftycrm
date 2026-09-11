<div class="row">
    <div class="col-md-6 mb-3">

        <label class="form-label">
            Client *
        </label>

        <select name="client_id" class="form-select @error('client_id') is-invalid @enderror" required>

            <option value="">
                Select Client
            </option>

            @foreach ($Client as $client)
                <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                    {{ $client->client_name }}
                </option>
            @endforeach

        </select>

        @error('client_id')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror

    </div>

    <div class="col-md-6 mb-3">

        <label class="form-label">
            Warehouse *
        </label>

        <select name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror" required>

            <option value="">
                Select Warehouse
            </option>

            @foreach ($Warehouse as $warehouse)
                <option value="{{ $warehouse->id }}"
                    {{ old('warehouse_id', $product->warehouse_id ?? '') == $warehouse->id ? 'selected' : '' }}>
                    {{ $warehouse->name }}
                </option>
            @endforeach

        </select>

        @error('warehouse_id')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror

    </div>
    <div class="col-md-6 mb-3">

        <label class="form-label">
            Product Name *
        </label>

        <select name="name" class="form-select @error('name') is-invalid @enderror" required>

            <option value="">
                Select Product
            </option>

            @foreach ($client_products as $clientProduct)
                <option value="{{ $clientProduct->id }}" {{ old('name') == $clientProduct->id ? 'selected' : '' }}>
                    {{ $clientProduct->shopify_product_name }}
                </option>
            @endforeach

        </select>

        @error('name')
            <div class="invalid-feedback">
                {{ $message }}
            </div>
        @enderror

    </div>



    {{-- Per Stock Price --}}
    <div class="col-md-6 mb-3">
        <label class="form-label">Per Stock Price *</label>
        <input type="number" step="0.01" id="price" name="price" class="form-control"
            placeholder="Enter price" required>
    </div>

    {{-- Quantity --}}

    <div class="col-md-6 mb-3">
        <label class="form-label">Stock*</label>
        <input type="number" name="low_stock_alert" id="quantity" class="form-control"
            value="{{ old('low_stock_alert', $product->low_stock_alert ?? 0) }}">
    </div>
    {{-- Total Price --}}
    <div class="col-md-6 mb-3">
        <label class="form-label">Total Price</label>
        <input type="number" name="total_price" id="total_price" class="form-control" readonly>
    </div>

</div>
