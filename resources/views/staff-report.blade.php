@extends('layouts.admin')

@section('content')
    <div class="container-fluid">

        <h3 class="mb-4">
            {{ $staff->name }} Performance Report
        </h3>

        <form method="GET" class="row mb-4">

            <div class="col-md-3">
                <input type="date" name="from" value="{{ $from }}" class="form-control">
            </div>

            <div class="col-md-3">
                <input type="date" name="to" value="{{ $to }}" class="form-control">
            </div>

            <div class="col-md-2">
                <button class="btn btn-primary">
                    Filter
                </button>
            </div>

        </form>

        <div class="row">

            <div class="col-md-3">
                <div class="card bg-primary text-white p-3">
                    <h6>Total Orders</h6>
                    <h2>{{ $totalOrders }}</h2>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-success text-white p-3">
                    <h6>Delivered</h6>
                    <h2>{{ $delivered }}</h2>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-warning text-dark p-3">
                    <h6>In Transit</h6>
                    <h2>{{ $inTransit }}</h2>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card bg-danger text-white p-3">
                    <h6>RTO</h6>
                    <h2>{{ $rto }}</h2>
                </div>
            </div>

        </div>

        <div class="row mt-4">

            <div class="col-md-3">
                <div class="card p-3">
                    <h6>Web Orders</h6>
                    <h2>{{ $webOrders }}</h2>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card p-3">
                    <h6>WhatsApp Orders</h6>
                    <h2>{{ $whatsappOrders }}</h2>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card p-3">
                    <h6>Confirmed Orders</h6>
                    <h2>{{ $confirmedOrders }}</h2>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card p-3">
                    <h6>Pending Orders</h6>
                    <h2>{{ $pendingOrders }}</h2>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card p-3">
                    <h6>Not Reachable Orders</h6>
                    <h2>{{ $not_reachable }}</h2>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card p-3">
                    <h6>Same Orders</h6>
                    <h2>{{ $same_order }}</h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3">
                    <h6>Cancel Orders</h6>
                    <h2>{{ $cancel }}</h2>
                </div>
            </div>
        </div>

    </div>
@endsection
