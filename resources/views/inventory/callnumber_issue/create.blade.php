@extends('layouts.inventory')

@section('title', 'Add callnumber')
@section('page-title', 'Add callnumber')

@section('content')

    <div class="card shadow-sm">
        <div class="card-body">

            <form action="{{ route('callnumber-issues.store') }}" method="POST">
                @csrf

                <label>Call Number</label>
                <input type="text" name="callnumber" class="form-control" required>

                <label>Staff Name</label>
                <input type="text" name="staff_name" class="form-control" required>

                <label>Client Name</label>
                <input type="text" name="client_name" class="form-control" required>

                <label>Remarks</label>
                <textarea name="remarks" class="form-control"></textarea>

                <br>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>

        </div>
    </div>

@endsection
