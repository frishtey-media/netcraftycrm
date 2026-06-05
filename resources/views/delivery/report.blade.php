@extends('layouts.admin')

@section('content')
    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                {{ ucfirst(str_replace('_', ' ', $type)) }} Orders
            </h4>

            <span class="badge bg-primary fs-6">
                Total Records : {{ $orders->count() }}
            </span>
        </div>

        <div class="card-body">

            <!-- Filter -->

            <div class="card mb-3">
                <div class="card-body">

                    <form method="GET" class="row g-3 align-items-end">

                        <input type="hidden" name="client_id" value="{{ request('client_id') }}">

                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                        </div>

                        <div class="col-md-2">
                            <button class="btn btn-primary w-100">
                                Filter
                            </button>
                        </div>

                        <div class="col-md-2">
                            <a href="{{ route('delivery.report', [
                                'type' => $type,
                                'client_id' => request('client_id'),
                            ]) }}"
                                class="btn btn-secondary w-100">
                                Reset
                            </a>
                        </div>

                        <div class="col-md-2">
                            <a href="{{ route('delivery.export', [
                                'type' => $type,
                                'client_id' => request('client_id'),
                                'from_date' => request('from_date'),
                                'to_date' => request('to_date'),
                            ]) }}"
                                class="btn btn-success w-100">
                                Export Excel
                            </a>
                        </div>

                    </form>

                </div>
            </div>
            <!-- Table -->

            <div class="table-responsive">

                <table id="reportTable" class="table table-bordered table-striped">

                    <thead>
                        <tr>
                            <th>SR No</th>
                            <th>Order ID</th>
                            <th>Client</th>
                            <th>Tracking No</th>
                            <th>Customer</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Payment Status</th>
                            <th>RTO Status</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($orders as $key => $order)
                            <tr>
                                <td>
                                    {{ $key + 1 }}
                                </td>

                                <td>{{ $order->order_id }}</td>
                                <td>
                                    {{ $order->client->company_name ?? ($order->client->client_name ?? '-') }}
                                </td>

                                <td>{{ $order->barcode }}</td>

                                <td>{{ $order->customer_name }}</td>

                                <td>{{ $order->customer_phone }}</td>

                                <td>{{ $order->delivery_status }}</td>

                                <td>
                                    @if ($order->recivedpaysts == 1)
                                        <span class="badge bg-success">
                                            Received
                                        </span>
                                    @else
                                        <span class="badge bg-warning">
                                            Pending
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @if ($order->rtorecivedsts == 1)
                                        <span class="badge bg-success">
                                            Received
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            Pending
                                        </span>
                                    @endif
                                </td>

                                <td>₹{{ $order->receivedcodamt }}</td>

                                <td>{{ $order->date }}</td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="10" class="text-center">
                                    No Records Found
                                </td>
                            </tr>
                        @endforelse

                    </tbody>

                </table>

            </div>



        </div>

    </div>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>

    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#reportTable').DataTable({
                pageLength: 50,
                responsive: true,
                order: [
                    [10, 'desc']
                ],
                language: {
                    search: "Search Orders:",
                    lengthMenu: "Show _MENU_ Records"
                }
            });
        });
    </script>
@endsection
