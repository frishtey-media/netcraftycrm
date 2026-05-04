@extends('layouts.inventory')

@section('title', 'Call Number')
@section('page-title', 'Staff Number List')

@section('content')

    <div class="card shadow-sm">
        <div class="card-body">

            {{-- Success eessage --}}
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Add Button --}}
            <div class="d-flex justify-content-between mb-3">
                <h5 class="mb-0">All Numbers</h5>
                <a href="{{ route('callnumber-issues.create') }}" class="btn btn-primary">
                    + Add Number
                </a>
            </div>
            <form method="GET" action="{{ route('callnumber-issues.index') }}" class="mb-3">
                <input type="text" name="search" class="form-control" placeholder="Search Number / Staff / Client"
                    value="{{ request('search') }}">
            </form>
            <div class="table-responsive">

                <table class="table">
                    <thead>
                        <tr>
                            <th>SR.No.</th>
                            <th>Number</th>
                            <th>Staff</th>
                            <th>Client</th>
                            <th>Remarks</th>
                            <th>Edit</th>
                            <th>Delete</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($issues as $index => $issue)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $issue->callnumber }}</td>
                                <td>{{ $issue->staff_name }}</td>
                                <td>{{ $issue->client_name }}</td>
                                <td>{{ $issue->remarks }}</td>
                                <td><a href="{{ route('callnumber-issues.edit', $issue->id) }}"
                                        class="btn btn-warning btn-sm">
                                        Edit
                                    </a></td>
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
