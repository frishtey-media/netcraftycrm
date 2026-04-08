<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Client *</label>

        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>

            <option value="">Select Client</option>

            @foreach ($categories as $category)
                <option value="{{ $category->id }}"
                    {{ old('category_id', $product->category_id ?? '') == $category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
            @endforeach

        </select>

        @error('category_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">Warehouse *</label>

        <select name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror" required>

            <option value="">Select Warehouse</option>

            @foreach ($Warehouse as $Warehouse)
                <option value="{{ $Warehouse->id }}"
                    {{ old('warehouse_id', $product->Warehouse ?? '') == $Warehouse->id ? 'selected' : '' }}>
                    {{ $Warehouse->name }}
                </option>
            @endforeach

        </select>

        @error('warehouse_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Product Name *</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $product->name ?? '') }}">
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
