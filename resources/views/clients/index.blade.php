@extends('layouts.admin')

@section('content')
    <div class="container-fluid">
        <h4 class="mb-4">Add Client</h4>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

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

        {{-- Add Client Form --}}
        <form method="POST" action="{{ route('clients.store') }}">
            @csrf
            <div class="row mb-3">
                <div class="col-md-3">
                    <input type="text" name="client_name" class="form-control" placeholder="Client Name"
                        value="{{ old('client_name') }}" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="company_name" class="form-control" placeholder="Company Name"
                        value="{{ old('company_name') }}">
                </div>
                <div class="col-md-3">
                    <input type="text" name="mobile" class="form-control" placeholder="Mobile"
                        value="{{ old('mobile') }}">
                </div>
                <div class="col-md-3">
                    <input type="email" name="email" class="form-control" placeholder="Email"
                        value="{{ old('email') }}">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <input type="text" name="address" class="form-control" placeholder="Address"
                        value="{{ old('address') }}">
                </div>
                <div class="col-md-2">
                    <input type="text" name="city" class="form-control" placeholder="City"
                        value="{{ old('city') }}">
                </div>
                <div class="col-md-2">
                    <input type="text" name="state" class="form-control" placeholder="State"
                        value="{{ old('state') }}">
                </div>
                <div class="col-md-2">
                    <input type="text" name="pincode" class="form-control" placeholder="Pincode"
                        value="{{ old('pincode') }}">
                </div>
                <div class="col-md-2">
                    <input type="text" name="shopify_store_url" class="form-control" placeholder="Shopify Store URL"
                        value="{{ old('shopify_store_url') }}">
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-2">
                    <button class="btn btn-primary w-100">Add Client</button>
                </div>
            </div>
        </form>

        {{-- Client Table --}}
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Client Name</th>
                    <th>Company Name</th>
                    <th>Mobile</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Pincode</th>
                    <th>Shopify Store URL</th>
                    <th>Created At</th>

                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $client->client_name }}</td>
                        <td>{{ $client->company_name }}</td>
                        <td>{{ $client->mobile }}</td>
                        <td>{{ $client->email }}</td>
                        <td>{{ $client->address }}</td>
                        <td>{{ $client->city }}</td>
                        <td>{{ $client->state }}</td>
                        <td>{{ $client->pincode }}</td>
                        <td>{{ $client->shopify_store_url }}</td>
                        <td>{{ $client->created_at->format('d-m-Y') }}</td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center">No clients added yet</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
