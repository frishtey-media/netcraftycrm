@extends('layouts.inventory')

@section('title', 'Call Number')
@section('page-title', 'Number List')

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
                <h5 class="mb-0">All Number</h5>
                <a href="{{ route('callnumber-issues.create') }}" class="btn btn-primary">
                    + Add Number
                </a>
            </div>

            <div class="table-responsive">

                <table class="table">
                    <thead>
                        <tr>
                            <th>Number</th>
                            <th>Staff</th>
                            <th>Client</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($issues as $issue)
                            <tr>
                                <td>{{ $issue->callnumber }}</td>
                                <td>{{ $issue->staff_name }}</td>
                                <td>{{ $issue->client_name }}</td>
                                <td>{{ $issue->issued_at }}</td>
                                <td>
                                    <form action="{{ route('callnumber-issues.destroy', $issue->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

        </div>
    </div>

@endsection
