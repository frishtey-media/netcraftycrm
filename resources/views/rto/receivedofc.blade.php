@extends('layouts.admin')

@section('content')
    <div class="container-fluid">

        <div class="card mb-4">
            <div class="card-header">
                <h4 class="mb-0">RTO Received OFC Scan Report</h4>
            </div>

            <div class="card-body">

                <form method="GET" action="{{ route('rto.receivedofcBased') }}">
                    <div class="row g-2 mb-3 align-items-end">

                        <div class="col">
                            <label class="form-label">Client</label>
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

                        <div class="col">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                        </div>

                        <div class="col">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                        </div>

                        <div class="col-auto">
                            <label class="form-label d-block">Actions</label>
                            <div class="d-flex flex-nowrap gap-2">
                                <button type="submit" class="btn btn-primary">
                                    Filter
                                </button>

                                <a href="{{ route('rto.receivedofcBased') }}" class="btn btn-secondary">
                                    Reset
                                </a>

                                <a href="{{ route('rto.index') }}" class="btn btn-success text-nowrap">
                                    Order Based
                                </a>

                                <a href="{{ route('rto.receivedBased') }}" class="btn btn-danger text-nowrap">
                                    India Post Based
                                </a>
                            </div>
                        </div>

                    </div>
                </form>

            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h6 class="text-muted">Total OFC Scans</h6>
                <h3>{{ number_format($totalScanned) }}</h3>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <strong>Staff-wise OFC Scan Counting</strong>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Staff Name</th>
                            <th>Total OFC Scans</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($staffCounts as $staff)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $staff->staff_name }}</td>
                                <td>{{ number_format($staff->total_scans) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center">
                                    No scan records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($staffCounts->isNotEmpty())
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-end">Total</th>
                                <th>{{ number_format($totalScanned) }}</th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>


    </div>
@endsection
