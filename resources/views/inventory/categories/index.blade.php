@extends('layouts.inventory')

@section('title', 'Clients')
@section('page-title', 'Clients')

@section('content')

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Client List</h5>

            <a href="{{ route('categories.create') }}" class="btn btn-primary btn-sm">
                + Add Client
            </a>
        </div>

        <div class="card-body">

            {{-- Success Message --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Client Name</th>
                            <th width="150">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold">
                                    {{ $category->name }}
                                </td>

                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('categories.edit', $category->id) }}"
                                            class="btn btn-sm btn-warning">
                                            Edit
                                        </a>

                                        <!--<form action="{{ route('categories.destroy', $category->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Delete this category?')">
                                                    Delete
                                                </button>
                                            </form>-->
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">
                                    No categories found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-3">
                {{ $categories->links() }}
            </div>

        </div>
    </div>

@endsection
