@extends('layouts.admin')

@section('content')
    <div class="container-fluid">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold">📊 Staff Performance Dashboard</h4>

            <div class="d-flex gap-2">
                <a href="?from={{ date('Y-m-d') }}&to={{ date('Y-m-d') }}" class="btn btn-sm btn-primary">Today</a>

                <a href="?from={{ date('Y-m-d', strtotime('-1 day')) }}&to={{ date('Y-m-d', strtotime('-1 day')) }}"
                    class="btn btn-sm btn-outline-secondary">Yesterday</a>

                <a href="?from={{ date('Y-m-d', strtotime('-7 days')) }}&to={{ date('Y-m-d') }}"
                    class="btn btn-sm btn-dark">Last 7 Days</a>
            </div>
        </div>

        <!-- FILTER CARD -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">

                <form method="GET" class="row g-3 align-items-end">

                    <div class="col-md-3 col-6">
                        <label class="form-label">From Date</label>
                        <input type="date" name="from" value="{{ $from }}" class="form-control">
                    </div>

                    <div class="col-md-3 col-6">
                        <label class="form-label">To Date</label>
                        <input type="date" name="to" value="{{ $to }}" class="form-control">
                    </div>

                    <div class="col-md-2 col-12">
                        <button class="btn btn-primary w-100">
                            🔍 Apply Filter
                        </button>
                    </div>

                </form>

            </div>
        </div>

        <!-- SUMMARY CARDS -->
        <div class="row mb-4">

            @php
                $totalOrders = $staffs->sum('total_orders');
                $totalVerified = $staffs->sum('verified_orders');
                $totalPending = $staffs->sum('pending_orders');
            @endphp

            <div class="col-md-4 col-6">
                <div class="card bg-primary text-white shadow-sm p-3">
                    <h6>Total Orders</h6>
                    <h3>{{ $totalOrders }}</h3>
                </div>
            </div>

            <div class="col-md-4 col-6">
                <div class="card bg-success text-white shadow-sm p-3">
                    <h6>Verified</h6>
                    <h3>{{ $totalVerified }}</h3>
                </div>
            </div>

            <div class="col-md-4 col-12 mt-3 mt-md-0">
                <div class="card bg-warning text-dark shadow-sm p-3">
                    <h6>Pending</h6>
                    <h3>{{ $totalPending }}</h3>
                </div>
            </div>

        </div>

        <!-- TABLE -->
        <div class="card shadow-sm">
            <div class="card-header fw-bold">
                Staff Report
            </div>

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-dark">
                        <tr>
                            <th>👤 Staff</th>
                            <th>Clients</th>
                            <th>Total</th>
                            <th class="text-success">Verified</th>
                            <th class="text-warning">Pending</th>
                            <th class="text-danger">Not Reachable</th>
                            <th>Success %</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse ($staffs as $staff)
                            @php
                                $success =
                                    $staff->total_orders > 0
                                        ? round(($staff->verified_orders / $staff->total_orders) * 100, 1)
                                        : 0;
                            @endphp

                            <tr>

                                <td class="fw-semibold">
                                    {{ $staff->name }}
                                </td>
                                <td>
                                    @if (isset($clientWise[$staff->id]))
                                        @foreach ($clientWise[$staff->id] as $c)
                                            <div class="badge bg-light text-dark mb-1">
                                                {{ $c['client'] }} ({{ $c['total'] }})
                                            </div>
                                        @endforeach
                                    @else
                                        <span class="text-muted">No Data</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        {{ $staff->total_orders }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge bg-success">
                                        {{ $staff->verified_orders }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge bg-warning text-dark">
                                        {{ $staff->pending_orders }}
                                    </span>
                                </td>

                                <td>
                                    <span class="badge bg-danger">
                                        {{ $staff->not_reachable_orders }}
                                    </span>
                                </td>

                                <td>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-info" style="width: {{ $success }}%">
                                        </div>
                                    </div>
                                    <small>{{ $success }}%</small>
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="6" class="text-center p-4">
                                    No data found
                                </td>
                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>
        </div>

    </div>
@endsection
