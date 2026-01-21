@extends('layouts.admin')

@section('content')
    <h4>Import Orders Excel</h4>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning">
            {{ session('warning') }}
        </div>
    @endif


    <form method="POST" action="{{ route('orders.import.post') }}" enctype="multipart/form-data">
        @csrf

        <div class="row">

            <div class="col-md-4 mb-3">
                <input type="file" name="file" class="form-control">
            </div>


            <div class="col-md-4 mb-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    Upload
                </button>
            </div>


            <div class="col-md-4 mb-3 d-flex align-items-end">
                <a href="{{ route('orders.list') }}" class="btn btn-success w-100">
                    View Orders
                </a>
            </div>
        </div>
    </form>
@endsection
