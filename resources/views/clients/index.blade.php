@extends('layouts.admin')

@section('content')

    <div class="container-fluid">

        {{-- ========================================================= --}}
        {{-- SUCCESS --}}
        {{-- ========================================================= --}}

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">

                {{ session('success') }}

                <button type="button" class="btn-close" data-bs-dismiss="alert">
                </button>

            </div>
        @endif


        {{-- ========================================================= --}}
        {{-- ERRORS --}}
        {{-- ========================================================= --}}

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">

                <strong>Please fix the following:</strong>

                <ul class="mb-0 mt-2">

                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach

                </ul>

                <button type="button" class="btn-close" data-bs-dismiss="alert">
                </button>

            </div>
        @endif


        {{-- ========================================================= --}}
        {{-- HEADER --}}
        {{-- ========================================================= --}}

        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>

                <h3 class="mb-1">
                    Client Management
                </h3>

                <small class="text-muted">
                    Manage Clients, Shopify and WhatsApp Integration
                </small>

            </div>


            <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#clientForm">

                + Add Client

            </button>

        </div>


        {{-- ========================================================= --}}
        {{-- ADD CLIENT --}}
        {{-- ========================================================= --}}

        <div class="collapse mb-4 {{ $errors->any() ? 'show' : '' }}" id="clientForm">

            <div class="card shadow-sm">

                <div class="card-header bg-primary text-white">

                    <strong>
                        Add New Client
                    </strong>

                </div>


                <div class="card-body">

                    <form method="POST" action="{{ route('clients.store') }}">

                        @csrf


                        {{-- ================================================= --}}
                        {{-- BASIC INFORMATION --}}
                        {{-- ================================================= --}}

                        <h5 class="border-bottom pb-2 mb-3">
                            Client Information
                        </h5>


                        <div class="row">

                            {{-- Client Name --}}

                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    Client Name <span class="text-danger">*</span>
                                </label>

                                <input type="text" name="client_name" class="form-control"
                                    value="{{ old('client_name') }}" required>

                            </div>


                            {{-- Company --}}

                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    Company Name
                                </label>

                                <input type="text" name="company_name" class="form-control"
                                    value="{{ old('company_name') }}">

                            </div>


                            {{-- Mobile --}}

                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    Mobile
                                </label>

                                <input type="text" name="mobile" class="form-control" value="{{ old('mobile') }}">

                            </div>


                            {{-- Email --}}

                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    Email
                                </label>

                                <input type="email" name="email" class="form-control" value="{{ old('email') }}">

                            </div>


                            {{-- Address --}}

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Address
                                </label>

                                <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>

                            </div>


                            {{-- City --}}

                            <div class="col-md-2 mb-3">

                                <label class="form-label">
                                    City
                                </label>

                                <input type="text" name="city" class="form-control" value="{{ old('city') }}">

                            </div>


                            {{-- State --}}

                            <div class="col-md-2 mb-3">

                                <label class="form-label">
                                    State
                                </label>

                                <input type="text" name="state" class="form-control" value="{{ old('state') }}">

                            </div>


                            {{-- Pincode --}}

                            <div class="col-md-2 mb-3">

                                <label class="form-label">
                                    Pincode
                                </label>

                                <input type="text" name="pincode" class="form-control" value="{{ old('pincode') }}">

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- SHOPIFY --}}
                        {{-- ================================================= --}}

                        <h5 class="border-bottom pb-2 mt-4 mb-3">
                            Shopify Integration
                        </h5>


                        <div class="row">

                            {{-- Store URL --}}

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Shopify Store URL
                                </label>

                                <input type="text" name="shopify_store_url" class="form-control"
                                    placeholder="https://store.myshopify.com" value="{{ old('shopify_store_url') }}">

                            </div>


                            {{-- Client ID --}}

                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    Shopify Client ID
                                </label>

                                <input type="text" name="shopify_client_id" class="form-control"
                                    value="{{ old('shopify_client_id') }}">

                            </div>


                            {{-- Client Secret --}}

                            <div class="col-md-3 mb-3">

                                <label class="form-label">
                                    Shopify Client Secret
                                </label>

                                <input type="password" name="shopify_client_secret" class="form-control"
                                    value="{{ old('shopify_client_secret') }}">

                            </div>


                            {{-- Shopify Access Token --}}

                            <div class="col-md-12 mb-3">

                                <label class="form-label">
                                    Shopify Access Token
                                </label>

                                <textarea name="shopify_access_token" class="form-control" rows="3">{{ old('shopify_access_token') }}</textarea>

                            </div>


                            {{-- Status --}}

                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Shopify Status
                                </label>

                                <select name="shopify_status" class="form-select">

                                    <option value="pending">
                                        Pending
                                    </option>

                                    <option value="connected">
                                        Connected
                                    </option>

                                    <option value="disconnected">
                                        Disconnected
                                    </option>

                                </select>

                            </div>


                            {{-- Token Expiry --}}

                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Token Expires At
                                </label>

                                <input type="datetime-local" name="token_expires_at" class="form-control">

                            </div>


                            {{-- Token Updated --}}

                            <div class="col-md-4 mb-3">

                                <label class="form-label">
                                    Token Updated At
                                </label>

                                <input type="datetime-local" name="token_updated_at" class="form-control">

                            </div>


                            {{-- Last Error --}}

                            <div class="col-md-12 mb-3">

                                <label class="form-label">
                                    Shopify Last Error
                                </label>

                                <textarea name="shopify_last_error" class="form-control" rows="2">{{ old('shopify_last_error') }}</textarea>

                            </div>


                            {{-- Last Sync --}}

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Shopify Last Sync At
                                </label>

                                <input type="datetime-local" name="shopify_last_sync_at" class="form-control">

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- WHATSAPP --}}
                        {{-- ================================================= --}}

                        <h5 class="border-bottom pb-2 mt-4 mb-3">
                            WhatsApp Integration
                        </h5>


                        <div class="row">

                            {{-- Phone Number ID --}}

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Phone Number ID
                                </label>

                                <input type="text" name="phone_number_id" class="form-control"
                                    value="{{ old('phone_number_id') }}">

                            </div>


                            {{-- WhatsApp Number --}}

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    WhatsApp Number
                                </label>

                                <input type="text" name="whatsapp_number" class="form-control"
                                    value="{{ old('whatsapp_number') }}">

                            </div>


                            {{-- Webhook Secret --}}

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Webhook Secret
                                </label>

                                <textarea name="webhook_secret" class="form-control" rows="2">{{ old('webhook_secret') }}</textarea>

                            </div>


                            {{-- Access Token --}}

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    WhatsApp Access Token
                                </label>

                                <textarea name="access_token" class="form-control" rows="2">{{ old('access_token') }}</textarea>

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- SYNC --}}
                        {{-- ================================================= --}}

                        <h5 class="border-bottom pb-2 mt-4 mb-3">
                            Sync Settings
                        </h5>


                        <div class="form-check mb-3">

                            <input type="checkbox" name="warehouse_sync" value="1" class="form-check-input"
                                id="warehouse_sync" {{ old('warehouse_sync') ? 'checked' : '' }}>

                            <label class="form-check-label" for="warehouse_sync">
                                Enable Warehouse Sync
                            </label>

                        </div>


                        {{-- SUBMIT --}}

                        <button type="submit" class="btn btn-success">
                            Save Client
                        </button>

                    </form>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- CLIENT TABLE --}}
        {{-- ========================================================= --}}

        <div class="card shadow-sm">

            <div class="card-header">

                <strong>
                    All Clients
                </strong>

                <span class="badge bg-primary float-end">
                    {{ $clients->count() }}
                </span>

            </div>


            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-dark">

                            <tr>

                                <th>ID</th>
                                <th>Client</th>
                                <th>Company</th>
                                <th>Mobile</th>
                                <th>Email</th>
                                <th>Shopify</th>
                                <th>WhatsApp</th>
                                <th>Status</th>
                                <th>Warehouse</th>
                                <th>Created</th>
                                <th>Action</th>

                            </tr>

                        </thead>


                        <tbody>

                            @forelse($clients as $client)
                                <tr>

                                    <td>
                                        {{ $client->id }}
                                    </td>

                                    <td>
                                        <strong>
                                            {{ $client->client_name }}
                                        </strong>
                                    </td>

                                    <td>
                                        {{ $client->company_name ?: '-' }}
                                    </td>

                                    <td>
                                        {{ $client->mobile ?: '-' }}
                                    </td>

                                    <td>
                                        {{ $client->email ?: '-' }}
                                    </td>

                                    <td>

                                        @if ($client->shopify_store_url)
                                            <a href="{{ $client->shopify_store_url }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary">
                                                Open
                                            </a>
                                        @else
                                            -
                                        @endif

                                    </td>

                                    <td>

                                        @if ($client->whatsapp_number)
                                            <span class="badge bg-success">
                                                Connected
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                Not Set
                                            </span>
                                        @endif

                                    </td>

                                    <td>

                                        @if ($client->shopify_status === 'connected')
                                            <span class="badge bg-success">
                                                Connected
                                            </span>
                                        @elseif($client->shopify_status === 'disconnected')
                                            <span class="badge bg-danger">
                                                Disconnected
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark">
                                                Pending
                                            </span>
                                        @endif

                                    </td>

                                    <td>

                                        @if ($client->warehouse_sync)
                                            <span class="badge bg-success">
                                                ON
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                OFF
                                            </span>
                                        @endif

                                    </td>

                                    <td>
                                        {{ optional($client->created_at)->format('d-m-Y H:i') }}
                                    </td>

                                    <td>

                                        <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#viewClient{{ $client->id }}">
                                            View
                                        </button>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="11" class="text-center">
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


    {{-- ============================================================= --}}
    {{-- VIEW MODALS --}}
    {{-- ============================================================= --}}

    @foreach ($clients as $client)
        <div class="modal fade" id="viewClient{{ $client->id }}" tabindex="-1">

            <div class="modal-dialog modal-xl modal-dialog-scrollable">

                <div class="modal-content">


                    {{-- HEADER --}}

                    <div class="modal-header">

                        <h5 class="modal-title">

                            Client Details -
                            {{ $client->client_name }}

                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                    </div>


                    {{-- BODY --}}

                    <div class="modal-body">


                        {{-- ================================================= --}}
                        {{-- BASIC --}}
                        {{-- ================================================= --}}

                        <h5 class="border-bottom pb-2 mb-3">
                            Client Information
                        </h5>


                        <div class="row">

                            <div class="col-md-3 mb-3">

                                <label class="fw-bold">
                                    ID
                                </label>

                                <input class="form-control" readonly value="{{ $client->id }}">

                            </div>


                            <div class="col-md-3 mb-3">

                                <label class="fw-bold">
                                    Client Name
                                </label>

                                <input class="form-control" readonly value="{{ $client->client_name }}">

                            </div>


                            <div class="col-md-3 mb-3">

                                <label class="fw-bold">
                                    Company
                                </label>

                                <input class="form-control" readonly value="{{ $client->company_name ?: '-' }}">

                            </div>


                            <div class="col-md-3 mb-3">

                                <label class="fw-bold">
                                    Mobile
                                </label>

                                <input class="form-control" readonly value="{{ $client->mobile ?: '-' }}">

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="fw-bold">
                                    Email
                                </label>

                                <input class="form-control" readonly value="{{ $client->email ?: '-' }}">

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="fw-bold">
                                    Address
                                </label>

                                <input class="form-control" readonly value="{{ $client->address ?: '-' }}">

                            </div>


                            <div class="col-md-4 mb-3">

                                <label class="fw-bold">
                                    City
                                </label>

                                <input class="form-control" readonly value="{{ $client->city ?: '-' }}">

                            </div>


                            <div class="col-md-4 mb-3">

                                <label class="fw-bold">
                                    State
                                </label>

                                <input class="form-control" readonly value="{{ $client->state ?: '-' }}">

                            </div>


                            <div class="col-md-4 mb-3">

                                <label class="fw-bold">
                                    Pincode
                                </label>

                                <input class="form-control" readonly value="{{ $client->pincode ?: '-' }}">

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- SHOPIFY --}}
                        {{-- ================================================= --}}

                        <h5 class="border-bottom pb-2 mt-4 mb-3">
                            Shopify Integration
                        </h5>


                        <div class="row">

                            <div class="col-md-12 mb-3">

                                <label class="fw-bold">
                                    Store URL
                                </label>

                                <input class="form-control" readonly value="{{ $client->shopify_store_url ?: '-' }}">

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="fw-bold">
                                    Shopify Client ID
                                </label>

                                <input class="form-control" readonly value="{{ $client->shopify_client_id ?: '-' }}">

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="fw-bold">
                                    Shopify Client Secret
                                </label>

                                <input type="password" class="form-control" readonly
                                    value="{{ $client->shopify_client_secret ?: '' }}">

                            </div>


                            <div class="col-md-12 mb-3">

                                <label class="fw-bold">
                                    Shopify Access Token
                                </label>

                                <textarea class="form-control" rows="3" readonly>{{ $client->shopify_access_token ?: '-' }}</textarea>

                            </div>


                            <div class="col-md-4 mb-3">

                                <label class="fw-bold">
                                    Status
                                </label>

                                <div class="mt-2">

                                    @if ($client->shopify_status === 'connected')
                                        <span class="badge bg-success">
                                            Connected
                                        </span>
                                    @elseif($client->shopify_status === 'disconnected')
                                        <span class="badge bg-danger">
                                            Disconnected
                                        </span>
                                    @else
                                        <span class="badge bg-warning text-dark">
                                            Pending
                                        </span>
                                    @endif

                                </div>

                            </div>


                            <div class="col-md-4 mb-3">

                                <label class="fw-bold">
                                    Token Expires
                                </label>

                                <input class="form-control" readonly
                                    value="{{ optional($client->token_expires_at)->format('d-m-Y H:i:s') ?? '-' }}">

                            </div>


                            <div class="col-md-4 mb-3">

                                <label class="fw-bold">
                                    Token Updated
                                </label>

                                <input class="form-control" readonly
                                    value="{{ optional($client->token_updated_at)->format('d-m-Y H:i:s') ?? '-' }}">

                            </div>


                            <div class="col-md-12 mb-3">

                                <label class="fw-bold">
                                    Last Error
                                </label>

                                <textarea class="form-control" rows="2" readonly>{{ $client->shopify_last_error ?: '-' }}</textarea>

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="fw-bold">
                                    Last Sync
                                </label>

                                <input class="form-control" readonly
                                    value="{{ optional($client->shopify_last_sync_at)->format('d-m-Y H:i:s') ?? '-' }}">

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- WHATSAPP --}}
                        {{-- ================================================= --}}

                        <h5 class="border-bottom pb-2 mt-4 mb-3">
                            WhatsApp Integration
                        </h5>


                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="fw-bold">
                                    Phone Number ID
                                </label>

                                <input class="form-control" readonly value="{{ $client->phone_number_id ?: '-' }}">

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="fw-bold">
                                    WhatsApp Number
                                </label>

                                <input class="form-control" readonly value="{{ $client->whatsapp_number ?: '-' }}">

                            </div>


                            <div class="col-md-12 mb-3">

                                <label class="fw-bold">
                                    Webhook Secret
                                </label>

                                <textarea class="form-control" rows="2" readonly>{{ $client->webhook_secret ?: '-' }}</textarea>

                            </div>


                            <div class="col-md-12 mb-3">

                                <label class="fw-bold">
                                    Access Token
                                </label>

                                <textarea class="form-control" rows="3" readonly>{{ $client->access_token ?: '-' }}</textarea>

                            </div>

                        </div>


                        {{-- ================================================= --}}
                        {{-- SYNC --}}
                        {{-- ================================================= --}}

                        <h5 class="border-bottom pb-2 mt-4 mb-3">
                            Sync Information
                        </h5>


                        <div class="row">

                            <div class="col-md-4 mb-3">

                                <label class="fw-bold d-block">
                                    Warehouse Sync
                                </label>

                                @if ($client->warehouse_sync)
                                    <span class="badge bg-success mt-2">
                                        Enabled
                                    </span>
                                @else
                                    <span class="badge bg-secondary mt-2">
                                        Disabled
                                    </span>
                                @endif

                            </div>


                            <div class="col-md-4 mb-3">

                                <label class="fw-bold">
                                    Created At
                                </label>

                                <input class="form-control" readonly
                                    value="{{ optional($client->created_at)->format('d-m-Y H:i:s') ?? '-' }}">

                            </div>


                            <div class="col-md-4 mb-3">

                                <label class="fw-bold">
                                    Updated At
                                </label>

                                <input class="form-control" readonly
                                    value="{{ optional($client->updated_at)->format('d-m-Y H:i:s') ?? '-' }}">

                            </div>

                        </div>

                    </div>


                    {{-- FOOTER --}}

                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Close
                        </button>

                    </div>

                </div>

            </div>

        </div>
    @endforeach

@endsection
