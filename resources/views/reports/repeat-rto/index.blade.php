@extends('layouts.admin')

@section('content')
    <div class="card">

        <div class="card-header">

            <h4>

                Repeat Customers

            </h4>

        </div>

        <div class="card-body">
            <form method="GET">

                <div class="row">

                    <div class="col-md-2">
                        <select name="client" class="form-control">

                            <option value="">All Clients</option>

                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}" {{ request('client') == $client->id ? 'selected' : '' }}>

                                    {{ $client->client_name }}

                                </option>
                            @endforeach

                        </select>
                    </div>

                    <div class="col-md-2">

                        <input type="date" name="from" class="form-control" value="{{ request('from') }}">

                    </div>

                    <div class="col-md-2">

                        <input type="date" name="to" class="form-control" value="{{ request('to') }}">

                    </div>

                    <div class="col-md-2">

                        <input type="text" name="phone" class="form-control" placeholder="Phone"
                            value="{{ request('phone') }}">

                    </div>

                    <div class="col-md-2">

                        <button class="btn btn-primary w-100">

                            Search

                        </button>

                    </div>

                    <div class="col-md-2">

                        <a href="{{ route('reports.repeat.rto') }}" class="btn btn-secondary w-100">

                            Reset

                        </a>

                    </div>

                </div>

            </form>

            <hr>
            <table class="table table-bordered">

                <thead>

                    <tr>

                        <th>Customer</th>

                        <th>Phone</th>

                        <th>City</th>

                        <th>Orders</th>

                        <th>Delivered</th>

                        <th>RTO</th>

                        <th>Last Staff</th>

                        <th></th>

                    </tr>

                </thead>

                <tbody>

                    @foreach ($customers as $row)
                        <tr>

                            <td>{{ $row->customer_name }}</td>

                            <td>{{ $row->customer_phone }}</td>

                            <td>{{ $row->city }}</td>

                            <td>{{ $row->total_orders }}</td>

                            <td>{{ $row->delivered }}</td>

                            <td>

                                <span class="badge badge-danger">

                                    {{ $row->total_rto }}

                                </span>

                            </td>

                            <td>{{ $row->last_staff }}</td>

                            <td>
                                <a href="{{ route('reports.repeat.rto.detail', [
                                    'phone' => $row->customer_phone,
                                    'client' => $row->client_id,
                                ]) }}"
                                    class="btn btn-primary btn-sm">
                                    View
                                </a>

                            </td>

                        </tr>
                    @endforeach

                </tbody>

            </table>

            {{ $customers->links() }}

        </div>

    </div>
@endsection
