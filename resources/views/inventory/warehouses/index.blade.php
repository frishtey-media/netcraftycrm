@extends('layouts.inventory')

@section('title', 'Warehouses')
@section('page-title', 'Warehouse List')

@section('content')

    <div class="card shadow-sm">
        <div class="card-body">

            {{-- Success Message --}}
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Add Button --}}
            <div class="d-flex justify-content-between mb-3">
                <h5 class="mb-0">All Warehouses</h5>
                <a href="{{ route('warehouses.create') }}" class="btn btn-primary">
                    + Add Warehouse
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Created Date</th>
                            <th width="150">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($warehouses as $key => $warehouse)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $warehouse->name }}</td>
                                <td>{{ $warehouse->location }}</td>
                                <td>{{ $warehouse->created_at->format('d-m-Y') }}</td>
                                <td>
                                    <a href="{{ route('warehouses.edit', $warehouse->id) }}" class="btn btn-sm btn-warning">
                                        Edit
                                    </a>

                                    <!--<form action="{{ route('warehouses.destroy', $warehouse->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                Delete
                                            </button>
                                        </form>-->
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">
                                    No Warehouses Found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>

@endsection
