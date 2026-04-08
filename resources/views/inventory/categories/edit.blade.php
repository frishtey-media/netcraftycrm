@extends('layouts.inventory')

@section('title', 'Edit Category')
@section('page-title', 'Edit Category')

@section('content')

    <div class="card shadow-sm">
        <div class="card-body">

            <form action="{{ route('categories.update', $category->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Client Name *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}">
                </div>

                <button class="btn btn-primary">Update</button>
                <a href="{{ route('categories.index') }}" class="btn btn-secondary">Cancel</a>

            </form>

        </div>
    </div>

@endsection
