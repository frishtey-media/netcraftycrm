@extends('layouts.admin')

@section('content')
    <div class="card p-3 mb-3">
        <div class="d-flex gap-2 mb-3 flex-wrap">

            <a href="{{ route('admin.staff.verified', request()->except('client_id')) }}"
                class="btn btn-sm {{ request('client_id') ? 'btn-outline-secondary' : 'btn-primary' }}">
                All
            </a>

            @foreach ($orders->groupBy('client_id') as $clientId => $group)
                <a href="{{ route('admin.staff.verified', array_merge(request()->all(), ['client_id' => $clientId])) }}"
                    class="btn btn-sm {{ request('client_id') == $clientId ? 'btn-primary' : 'btn-outline-secondary' }}">

                    {{ optional($group->first()->client)->client_name ?? 'Client' }}
                    ({{ $group->count() }})
                </a>
            @endforeach

        </div>
        <form method="GET" class="row g-2">

            <input type="hidden" name="staff_id" value="{{ $staffId }}">

            <div class="col-md-3">
                <input type="date" name="from" class="form-control" value="{{ request('from') }}">
            </div>

            <div class="col-md-3">
                <input type="date" name="to" class="form-control" value="{{ request('to') }}">
            </div>

            <div class="col-md-3">
                <button class="btn btn-primary w-100">Apply Filter</button>
            </div>

        </form>

    </div>

    @if ($orders->count() <= 10)
        <a href="{{ route('admin.staff.verified.export', request()->all()) }}" class="btn btn-success mb-3">
            ⬇ Export Excel
        </a>
    @endif
    <div class="table-responsive">
        <table class="table table-bordered">

            <thead>
                <tr>
                    <th>#</th>
                    <th>Order Id</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Product</th>
                    <th>City</th>
                    <th>Date</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($orders as $key => $o)
                    <tr>

                        <td>{{ $key + 1 }}</td>

                        <td>#{{ $o->order_id }}</td>

                        <td>{{ $o->customer_name }}</td>

                        <td>{{ $o->customer_phone }}</td>

                        <td>{{ $o->product_name }}</td>

                        <td>{{ $o->city }}</td>

                        <td>{{ $o->updated_at }}</td>

                    </tr>
                @endforeach
            </tbody>

        </table>
    </div>
@endsection
