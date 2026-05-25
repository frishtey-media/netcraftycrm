@extends('layouts.admin')

@section('content')
    <div class="container">

        <div class="card">
            <div class="card-header">
                <h4>India Post Bulk Delivery Update</h4>
            </div>

            <div class="card-body">

                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('delivery.upload') }}" enctype="multipart/form-data">

                    @csrf

                    <div class="mb-3">
                        <label>Select India Post Excel</label>

                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                    </div>

                    <button class="btn btn-success">
                        Upload & Update Status
                    </button>

                </form>

            </div>
        </div>

    </div>
@endsection
