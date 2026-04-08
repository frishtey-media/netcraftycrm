@extends('layouts.inventory')

@section('title', 'Edit Warehouse')
@section('page-title', 'Edit Warehouse')

@section('content')

    <div class="card shadow-sm">
        <div class="card-body">

            {{-- Validation Errors --}}
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('warehouses.update', $warehouse->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Warehouse Name *</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $warehouse->name) }}"
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Location *</label>
                    <input type="text" name="location" class="form-control"
                        value="{{ old('location', $warehouse->location) }}" required>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Update Warehouse
                    </button>
                </div>

            </form>

        </div>
    </div>

@endsection
