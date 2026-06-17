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
                                @if ($lastDeliveryUpdate)
                                    <div class="alert alert-info">
                                        <strong>Last Delivery Update:</strong>
                                        {{ \Carbon\Carbon::parse($lastDeliveryUpdate)->format('d-m-Y h:i A') }}
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
                                @if ($lastPaymentUpdate)
                                    <div class="alert alert-info">
                                        <strong>Last Payment Update:</strong>
                                        {{ \Carbon\Carbon::parse($lastPaymentUpdate)->format('d-m-Y h:i A') }}
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



                </div>

            </div>
        @endsection
