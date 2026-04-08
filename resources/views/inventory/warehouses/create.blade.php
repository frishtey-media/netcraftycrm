@extends('layouts.inventory')

@section('title', 'Add Warehouse')
@section('page-title', 'Add Warehouse')

@section('content')

    <div class="card shadow-sm">
        <div class="card-body">

            <form action="{{ route('warehouses.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Warehouse Name *</label>
                    <input type="text" name="name" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Location *</label>
                    <input type="text" name="location" class="form-control">
                </div>

                <button class="btn btn-primary">Save</button>
                <a href="{{ route('warehouses.index') }}" class="btn btn-secondary">Cancel</a>

            </form>

        </div>
    </div>

@endsection
