<div class="col-md-6 mb-3">
    <label class="form-label">Client *</label>
    <select name="category_id" class="form-control">
        <option value="">Select Client</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}"
                {{ old('category_id', $product->category_id ?? '') == $category->id ? 'selected' : '' }}>
                {{ $category->name }}
            </option>
        @endforeach
    </select>
</div>
