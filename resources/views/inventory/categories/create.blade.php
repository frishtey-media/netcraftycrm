@extends('layouts.inventory')

@section('title', 'Add Category')
@section('page-title', 'Add Category')

@section('content')

    <div class="card shadow-sm">
        <div class="card-body">

            <form action="{{ route('categories.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Client Name *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                        placeholder="Enter Client name">
                </div>

                <button type="submit" class="btn btn-success">
                    Save Client
                </button>

                <a href="{{ route('categories.index') }}" class="btn btn-secondary">
                    Cancel
                </a>

            </form>

        </div>
    </div>

@endsection
