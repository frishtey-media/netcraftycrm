@extends('layouts.admin')

@section('content')
    <h4>Clients Label Sender Details</h4>
    <form method="post" action="{{ route('labelsenders.store') }}" class="card mb-3">
        @csrf
        <div class="card-body">
            <div class="row g-2">

                <div class="col-md-3">
                    <label class="form-label">From:</label>
                    <input type="text" name="customer_name" class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Communication Address:</label>
                    <input type="text" name="customer_phone" class="form-control" required>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary">Save</button>
                </div>

            </div>
        </div>
    </form>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <table id="ordersTable" class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>From:</th>
                <th>Communication Address:</th>

            </tr>
        </thead>
        <tbody>
            @foreach ($senders as $key => $sender)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $sender->customer_name }}</td>
                    <td>{{ $sender->customer_phone }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
