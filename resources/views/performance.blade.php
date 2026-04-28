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

        <!-- FILTER -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">

                    <div class="col-md-3 col-6">
                        <label>From</label>
                        <input type="date" name="from" value="{{ $from }}" class="form-control">
                    </div>

                    <div class="col-md-3 col-6">
                        <label>To</label>
                        <input type="date" name="to" value="{{ $to }}" class="form-control">
                    </div>

                    <div class="col-md-2 col-12">
                        <button class="btn btn-primary w-100">Apply</button>
                    </div>

                </form>
            </div>
        </div>

        <!-- SUMMARY -->
        @php
            $totalOrders = $staffs->sum('total_orders');
            $totalVerified = $staffs->sum('verified_orders');
            $totalPending = $staffs->sum('pending_orders');
        @endphp

        <div class="row mb-4">

            <div class="col-md-4 col-6">
                <div class="card bg-primary text-white p-3">
                    <h6>Total Orders</h6>
                    <h3>{{ $totalOrders }}</h3>
                </div>
            </div>

            <div class="col-md-4 col-6">
                <div class="card bg-success text-white p-3">
                    <h6>Verified</h6>
                    <h3>{{ $totalVerified }}</h3>
                </div>
            </div>

            <div class="col-md-4 col-12 mt-3 mt-md-0">
                <div class="card bg-warning text-dark p-3">
                    <h6>Pending</h6>
                    <h3>{{ $totalPending }}</h3>
                </div>
            </div>

        </div>

        <!-- TABLE -->
        <div class="card shadow-sm">
            <div class="card-header fw-bold">Staff Report</div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">

                    <thead class="table-dark">
                        <tr>
                            <th>Staff</th>
                            <th>Clients</th>
                            <th>Total</th>
                            <th>Verified</th>
                            <th>Pending</th>
                            <th>Not Reachable</th>
                            <th>%</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($staffs as $staff)
                            @php
                                $success =
                                    $staff->total_orders > 0
                                        ? round(($staff->verified_orders / $staff->total_orders) * 100, 1)
                                        : 0;
                            @endphp

                            <tr>

                                <td>{{ $staff->name }}</td>

                                <td>
                                    @if (isset($clientWise[$staff->id]))
                                        @foreach ($clientWise[$staff->id] as $c)
                                            <div class="badge bg-light text-dark mb-1">
                                                {{ $c['client'] }} ({{ $c['total'] }})
                                            </div>
                                        @endforeach
                                    @endif
                                </td>

                                <td>
                                    <span class="badge bg-secondary">
                                        {{ $staff->total_orders }}
                                    </span>
                                </td>

                                <!-- ✅ CLICKABLE VERIFIED -->
                                <td>
                                    <a href="{{ route('admin.staff.verified', [
                                        'staff_id' => $staff->id,
                                        'from' => request('from'),
                                        'to' => request('to'),
                                    ]) }}"
                                        class="badge bg-success text-decoration-none">
                                        {{ $staff->verified_orders }}
                                    </a>
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
                                    <small>{{ $success }}%</small>
                                </td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>
            </div>
        </div>

    </div>
@endsection
