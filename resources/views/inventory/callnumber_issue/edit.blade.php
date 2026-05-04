@extends('layouts.inventory')

@section('title', 'Edit callnumber')
@section('page-title', 'Edit callnumber')

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

            <form action="{{ route('callnumber-issues.update', $issue->id) }}" method="POST">
                @csrf
                @method('PUT')

                <label>Call Number</label>
                <input type="text" name="callnumber" value="{{ $issue->callnumber }}" class="form-control" required>

                <label>Staff Name</label>
                <input type="text" name="staff_name" value="{{ $issue->staff_name }}" class="form-control" required>

                <label>Client Name</label>
                <input type="text" name="client_name" value="{{ $issue->client_name }}" class="form-control" required>

                <label>Remarks</label>
                <textarea name="remarks" class="form-control">{{ $issue->remarks }}</textarea>

                <br>
                <button class="btn btn-success">Update</button>
            </form>
        </div>
    </div>

@endsection
