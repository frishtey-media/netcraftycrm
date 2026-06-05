@extends('layouts.admin')

@section('content')
    <div class="container-fluid">

        <div class="card">
            <div class="card-header">
                <h4>India Post Bulk Operations</h4>
            </div>

            <div class="card-body">

                <div class="row">

                    <!-- Delivery Status -->
                    <div class="col-md-4 mb-4">
                        <div class="card border">
                            <div class="card-header bg-primary text-white">
                                Delivery Status Update
                            </div>

                            <div class="card-body">

                                @if (session('delivery_success'))
                                    <div class="alert alert-success">
                                        {{ session('delivery_success') }}
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('delivery.upload') }}" enctype="multipart/form-data">
                                    @csrf

                                    <div class="mb-3">
                                        <label>India Post Delivery Excel</label>
                                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv"
                                            required>
                                    </div>

                                    <button class="btn btn-success w-100">
                                        Upload Delivery File
                                    </button>
                                </form>

                            </div>
                        </div>
                    </div>

                    <!-- Payment Status -->
                    <div class="col-md-4 mb-4">
                        <div class="card border">
                            <div class="card-header bg-info text-white">
                                Payment Received Update
                            </div>

                            <div class="card-body">

                                @if (session('payment_success'))
                                    <div class="alert alert-success">
                                        {{ session('payment_success') }}
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('delivery.paymentupload') }}"
                                    enctype="multipart/form-data">
                                    @csrf

                                    <div class="mb-3">
                                        <label>Payment Excel</label>
                                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv"
                                            required>
                                    </div>

                                    <button class="btn btn-success w-100">
                                        Upload Payment File
                                    </button>
                                </form>

                            </div>
                        </div>
                    </div>

                    <!-- RTO Received -->
                    <!--  <div class="col-md-4 mb-4">
                                                                        <div class="card border">
                                                                            <div class="card-header bg-warning">
                                                                                RTO Received Update
                                                                            </div>

                                                                            <div class="card-body">

                                                                                @if (session('rto_success'))
    <div class="alert alert-success">
                                                                                        {{ session('rto_success') }}
                                                                                    </div>
    @endif

                                                                                <form method="POST" action="{{ route('delivery.rtoReceivedUpload') }}"
                                                                                    enctype="multipart/form-data">
                                                                                    @csrf

                                                                                    <div class="mb-3">
                                                                                        <label>RTO Excel</label>
                                                                                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv"
                                                                                            required>
                                                                                    </div>

                                                                                    <button class="btn btn-success w-100">
                                                                                        Upload RTO File
                                                                                    </button>
                                                                                </form>

                                                                            </div>
                                                                        </div>
                                                                    </div>-->
                    <div class="card-body">

                        <form method="GET" action="{{ route('delivery.index') }}">
                            <div class="row mb-4">

                                <div class="col-md-4">
                                    <label>Select Client</label>

                                    <select name="client_id" class="form-control">
                                        <option value="">All Clients</option>

                                        @foreach ($clients as $client)
                                            <option value="{{ $client->id }}"
                                                {{ request('client_id') == $client->id ? 'selected' : '' }}>

                                                {{ $client->client_name }}

                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2 mt-4">
                                    <button class="btn btn-primary">
                                        Filter
                                    </button>
                                </div>

                            </div>
                        </form>
                        <div class="row mt-4">

                            <div class="col-md-3 mb-3">
                                <a href="{{ route('delivery.report', ['type' => 'all', 'client_id' => request('client_id')]) }}"
                                    style="text-decoration:none;">
                                    <div class="card bg-primary text-white">
                                        <div class="card-body text-center">
                                            <h6>Total Orders</h6>
                                            <h2>{{ $totalOrders }}</h2>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div class="col-md-3 mb-3">
                                <a href="{{ route('delivery.report', ['type' => 'delivered', 'client_id' => request('client_id')]) }}"
                                    style="text-decoration:none;">
                                    <div class="card bg-success text-white">
                                        <div class="card-body text-center">
                                            <h6>Total Delivered</h6>
                                            <h2>{{ $deliveredOrders }}</h2>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div class="col-md-3 mb-3">
                                <a href="{{ route('delivery.report', ['type' => 'rto', 'client_id' => request('client_id')]) }}"
                                    style="text-decoration:none;">
                                    <div class="card bg-danger text-white">
                                        <div class="card-body text-center">
                                            <h6>Total RTO</h6>
                                            <h2>{{ $totalRTO }}</h2>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div class="col-md-3 mb-3">
                                <a href="{{ route('delivery.report', ['type' => 'in_transit', 'client_id' => request('client_id')]) }}"
                                    style="text-decoration:none;">
                                    <div class="card bg-dark text-white">
                                        <div class="card-body text-center">
                                            <h6>Total In Transit</h6>
                                            <h2>{{ $inTransit }}</h2>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div class="col-md-3 mb-3">
                                <a href="{{ route('delivery.report', ['type' => 'payment_received', 'client_id' => request('client_id')]) }}"
                                    style="text-decoration:none;">
                                    <div class="card bg-info text-white">
                                        <div class="card-body text-center">
                                            <h6>Payment Received</h6>
                                            <h2>{{ $paymentReceived }}</h2>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div class="col-md-3 mb-3">
                                <a href="{{ route('delivery.report', ['type' => 'rto_received', 'client_id' => request('client_id')]) }}"
                                    style="text-decoration:none;">
                                    <div class="card bg-success text-white">
                                        <div class="card-body text-center">
                                            <h6>RTO Received</h6>
                                            <h2>{{ $rtoReceived }}</h2>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div class="col-md-3 mb-3">
                                <a href="{{ route('delivery.report', ['type' => 'payment_pending', 'client_id' => request('client_id')]) }}"
                                    style="text-decoration:none;">
                                    <div class="card bg-warning text-dark">
                                        <div class="card-body text-center">
                                            <h6>Payment Pending</h6>
                                            <h2>{{ $paymentPending }}</h2>
                                        </div>
                                    </div>
                                </a>
                            </div>

                            <div class="col-md-3 mb-3">
                                <a href="{{ route('delivery.report', ['type' => 'rto_pending', 'client_id' => request('client_id')]) }}"
                                    style="text-decoration:none;">
                                    <div class="card bg-secondary text-white">
                                        <div class="card-body text-center">
                                            <h6>RTO Pending</h6>
                                            <h2>{{ $rtoPending }}</h2>
                                        </div>
                                    </div>
                                </a>
                            </div>

                        </div>



                    </div>
                </div>

            </div>
        @endsection
