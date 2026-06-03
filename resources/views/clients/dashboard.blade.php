@extends('layouts.admin')

@section('content')
    <div class="container-fluid">

        {{-- Success Message --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
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

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Client Management</h3>

            <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#clientForm">
                + Add Client
            </button>
        </div>

        {{-- Add Client Form --}}
        <div class="collapse mb-4" id="clientForm">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Add New Client</strong>
                </div>

                <div class="card-body">

                    <form method="POST" action="{{ route('clients.store') }}">
                        @csrf

                        <div class="row">

                            <div class="col-md-3 mb-3">
                                <label>Client Name *</label>
                                <input type="text" name="client_name" class="form-control" required>
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Company Name</label>
                                <input type="text" name="company_name" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Mobile</label>
                                <input type="text" name="mobile" class="form-control">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Address</label>
                                <input type="text" name="address" class="form-control">
                            </div>

                            <div class="col-md-2 mb-3">
                                <label>City</label>
                                <input type="text" name="city" class="form-control">
                            </div>

                            <div class="col-md-2 mb-3">
                                <label>State</label>
                                <input type="text" name="state" class="form-control">
                            </div>

                            <div class="col-md-2 mb-3">
                                <label>Pincode</label>
                                <input type="text" name="pincode" class="form-control">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Shopify Store URL</label>
                                <input type="text" name="shopify_store_url" class="form-control">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Shopify Access Token</label>
                                <textarea name="shopify_access_token" rows="3" class="form-control"></textarea>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Webhook Secret</label>
                                <textarea name="webhook_secret" rows="3" class="form-control"></textarea>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Phone Number ID</label>
                                <input type="text" name="phone_number_id" class="form-control">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>WhatsApp Number</label>
                                <input type="text" name="whatsapp_number" class="form-control">
                            </div>

                        </div>

                        <button class="btn btn-success">
                            Save Client
                        </button>

                    </form>

                </div>
            </div>
        </div>

        {{-- Client List --}}
        <div class="card shadow-sm">
            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover">

                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Client</th>
                                <th>Company</th>
                                <th>Mobile</th>
                                <th>Store URL</th>
                                <th>Created</th>
                                <th width="180">Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($clients as $client)
                                <tr>

                                    <td>{{ $client->id }}</td>

                                    <td>{{ $client->client_name }}</td>

                                    <td>{{ $client->company_name }}</td>

                                    <td>{{ $client->mobile }}</td>

                                    <td>{{ $client->shopify_store_url }}</td>

                                    <td>
                                        {{ optional($client->created_at)->format('d-m-Y') ?? '-' }}
                                    </td>

                                    <td>

                                        <button class="btn btn-info btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#viewClient{{ $client->id }}">
                                            View
                                        </button>


                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="7" class="text-center">
                                        No Clients Found
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>
        </div>

    </div>

    {{-- ALL MODALS BELOW TABLE --}}

    @foreach ($clients as $client)
        <div class="modal fade" id="viewClient{{ $client->id }}" tabindex="-1">

            <div class="modal-dialog modal-xl">

                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $client->client_name }}
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <label>Client Name</label>
                                <input class="form-control" readonly value="{{ $client->client_name }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Company Name</label>
                                <input class="form-control" readonly value="{{ $client->company_name }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Mobile</label>
                                <input class="form-control" readonly value="{{ $client->mobile }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Email</label>
                                <input class="form-control" readonly value="{{ $client->email }}">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Address</label>
                                <textarea class="form-control" rows="2" readonly>{{ $client->address }}</textarea>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>City</label>
                                <input class="form-control" readonly value="{{ $client->city }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>State</label>
                                <input class="form-control" readonly value="{{ $client->state }}">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label>Pincode</label>
                                <input class="form-control" readonly value="{{ $client->pincode }}">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Store URL</label>
                                <input class="form-control" readonly value="{{ $client->shopify_store_url }}">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Access Token</label>
                                <textarea class="form-control" rows="3" readonly>{{ $client->shopify_access_token }}</textarea>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>Phone Number ID</label>
                                <input class="form-control" readonly value="{{ $client->phone_number_id }}">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label>WhatsApp Number</label>
                                <input class="form-control" readonly value="{{ $client->whatsapp_number }}">
                            </div>

                            <div class="col-md-12 mb-3">
                                <label>Webhook Secret</label>
                                <textarea class="form-control" rows="3" readonly>{{ $client->webhook_secret }}</textarea>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>
    @endforeach

@endsection
