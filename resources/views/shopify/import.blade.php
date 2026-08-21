@extends('layouts.admin')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @push('scripts')
        <script>
            $(function() {
                if (!$.fn.DataTable.isDataTable('#ordersTable')) {
                    $('#ordersTable').DataTable({
                        language: {
                            emptyTable: "No orders found"
                        }
                    });
                }
            });
        </script>
    @endpush

    <style>
        .loader-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.9);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .loader-box {
            background: #fff;
            padding: 30px 40px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .spinner {
            width: 55px;
            height: 55px;
            border: 5px solid #e5e5e5;
            border-top: 5px solid #0d6efd;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: auto;
        }

        .loader-box p {
            margin-top: 15px;
            font-weight: 600;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>

    <div class="container">
        <h4>Import Shopify Orders</h4>
        @if (session('delhivery_import_summary'))
            @php
                $summary = session('delhivery_import_summary');
            @endphp

            <div class="card mt-3 shadow-sm">

                <div class="card-header bg-primary text-white">
                    <strong>Delhivery Import Summary</strong>
                </div>

                <div class="card-body">

                    <div class="row g-3">

                        {{-- TOTAL --}}
                        <div class="col-md-2">
                            <div class="border rounded p-3 text-center">
                                <small>Total Rows</small>
                                <h4>
                                    {{ $summary['total_rows'] ?? 0 }}
                                </h4>
                            </div>
                        </div>

                        {{-- IMPORTED --}}
                        <div class="col-md-2">
                            <div class="border rounded p-3 text-center">
                                <small>Imported</small>
                                <h4 class="text-success">
                                    {{ $summary['imported'] ?? 0 }}
                                </h4>
                            </div>
                        </div>

                        {{-- EXCEL ERRORS --}}
                        <div class="col-md-2">
                            <div class="border rounded p-3 text-center">
                                <small>Excel Errors</small>
                                <h4 class="text-danger">
                                    {{ $summary['skipped'] ?? 0 }}
                                </h4>
                            </div>
                        </div>

                        {{-- QUEUED --}}
                        <div class="col-md-2">
                            <div class="border rounded p-3 text-center">
                                <small>Booking Queued</small>
                                <h4 class="text-primary">
                                    {{ $summary['booking_queued'] ?? 0 }}
                                </h4>
                            </div>
                        </div>

                        {{-- SUCCESS --}}
                        <div class="col-md-2">
                            <div class="border rounded p-3 text-center">
                                <small>Booking Success</small>
                                <h4 class="text-success">
                                    {{ $summary['booking_success'] ?? 0 }}
                                </h4>
                            </div>
                        </div>

                        {{-- FAILED --}}
                        <div class="col-md-2">
                            <div class="border rounded p-3 text-center">
                                <small>Booking Failed</small>
                                <h4 class="text-danger">
                                    {{ $summary['booking_failed'] ?? 0 }}
                                </h4>
                            </div>
                        </div>

                    </div>

                    <div class="mt-3">

                        <span class="badge bg-warning text-dark">
                            Background Booking
                        </span>

                        <span class="ms-2">
                            Booking is processed through queue.
                        </span>

                    </div>

                </div>
            </div>
        @endif

        @if (session('delhivery_import_errors'))

            @php
                $errors = session('delhivery_import_errors');
            @endphp

            @if (count($errors))
                <div class="card mt-3">

                    <div class="card-header bg-danger text-white">
                        <strong>
                            Excel Import Errors
                        </strong>
                    </div>

                    <div class="card-body p-0">

                        <div class="table-responsive">

                            <table class="table table-bordered mb-0">

                                <thead>

                                    <tr>
                                        <th>Type</th>
                                        <th>Excel Row</th>
                                        <th>Order ID</th>
                                        <th>Error</th>
                                    </tr>

                                </thead>

                                <tbody>

                                    @foreach ($errors as $error)
                                        <tr>

                                            <td>
                                                <span class="badge bg-danger">
                                                    {{ $error['type'] ?? 'excel' }}
                                                </span>
                                            </td>

                                            <td>
                                                {{ $error['row'] ?? '-' }}
                                            </td>

                                            <td>
                                                {{ $error['order_id'] ?? '-' }}
                                            </td>

                                            <td class="text-danger">
                                                {{ $error['error'] ?? 'Unknown error' }}
                                            </td>

                                        </tr>
                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>
            @endif

        @endif
        @if (session('duplicate_orders') || session('duplicate_barcodes'))
            <script>
                let orders =
                    @json(session('duplicate_orders', []));

                let barcodes =
                    @json(session('duplicate_barcodes', []));

                let html = '';

                if (orders.length) {

                    html +=
                        '<b>Duplicate Orders</b><br>' +
                        orders.join('<br>') +
                        '<br><br>';
                }

                if (barcodes.length) {

                    html +=
                        '<b>Duplicate Barcodes</b><br>' +
                        barcodes.join('<br>');
                }

                Swal.fire({

                    icon: 'warning',

                    title: 'Duplicate Records Found',

                    html: html,

                    width: 700
                });
            </script>
        @endif
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('import_warning'))
            <div class="alert alert-warning alert-dismissible fade show">
                {{ session('import_warning') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session('errors') && count(session('errors')))
            <div class="alert alert-danger">

                <strong>Import Errors</strong>

                <ul class="mb-0">

                    @foreach (session('errors') as $error)
                        @if (is_array($error))
                            <li>
                                <strong>Row:</strong>
                                {{ $error['row'] ?? '-' }}

                                @if (!empty($error['order_id']))
                                    |
                                    <strong>Order ID:</strong>
                                    {{ $error['order_id'] }}
                                @endif

                                |
                                <strong>Error:</strong>
                                {{ $error['error'] ?? 'Unknown error' }}
                            </li>
                        @else
                            <li>
                                {{ $error }}
                            </li>
                        @endif
                    @endforeach
                </ul>

            </div>
        @endif
        <div style="text-align:right;">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#excelImportModal">
                WhatsApp Excel Import India Post
            </button>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#excelImportModaldelhivery">
                Import Delhivery Partner
            </button>
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#delhiveryPickupModal">
                <i class="fas fa-truck"></i>
                Request Pickup
            </button>
        </div>
        <!-- ========================================================= -->
        <!-- DELHIVERY REQUEST PICKUP MODAL -->
        <!-- ========================================================= -->

        <div class="modal fade" id="delhiveryPickupModal" tabindex="-1" aria-labelledby="delhiveryPickupModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-md">

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title" id="delhiveryPickupModalLabel">
                            <i class="fas fa-truck me-2"></i>
                            Request Delhivery Pickup
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                    </div>

                    <form method="POST" action="{{ route('delhivery.request.pickup') }}">

                        @csrf

                        <div class="modal-body">

                            <!-- Ready Packages -->
                            <div class="alert alert-info">

                                <strong>
                                    Ready for Pickup:
                                </strong>

                                <span class="fs-5">
                                    {{ \App\Models\Shipment::where('courier', 'delhivery')->whereNotNull('awb')->whereIn('status', ['booked', 'label_generated'])->whereNull('picked_up_at')->count() }}
                                </span>

                                packages

                            </div>


                            <!-- Pickup Date -->
                            <div class="mb-3">

                                <label class="form-label">
                                    <strong>Pickup Date</strong>
                                </label>

                                <input type="date" name="pickup_date" class="form-control"
                                    value="{{ now()->format('Y-m-d') }}" min="{{ now()->format('Y-m-d') }}" required>

                            </div>


                            <!-- Pickup Time -->
                            <div class="mb-3">

                                <label class="form-label">
                                    <strong>Pickup Time</strong>
                                </label>

                                <input type="time" name="pickup_time" class="form-control" value="16:00" required>

                                <small class="text-muted">
                                    Enter pickup time according to your Delhivery pickup schedule.
                                </small>

                            </div>


                            <!-- Package Count -->
                            <div class="mb-3">

                                <label class="form-label">
                                    <strong>Expected Package Count</strong>
                                </label>

                                <input type="number" name="expected_package_count" class="form-control"
                                    value="{{ \App\Models\Shipment::where('courier', 'delhivery')->whereNotNull('awb')->whereIn('status', ['booked', 'label_generated'])->whereNull('picked_up_at')->count() }}"
                                    min="1" required>

                                <small class="text-muted">
                                    You can change the quantity if required.
                                </small>

                            </div>


                            <!-- Pickup Location -->
                            <div class="mb-3">

                                <label class="form-label">
                                    <strong>Pickup Location</strong>
                                </label>

                                <input type="text" class="form-control"
                                    value="{{ config('services.delhivery.pickup_location') }}" readonly>

                            </div>


                            <div class="alert alert-warning mb-0">

                                <i class="fas fa-info-circle"></i>

                                After submitting, Delhivery will receive the pickup
                                request. The pickup request ID will be saved in your CRM.

                            </div>

                        </div>


                        <div class="modal-footer">

                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Cancel
                            </button>

                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-truck"></i>
                                Create Pickup
                            </button>

                        </div>

                    </form>

                </div>

            </div>
        </div>

        <div class="modal fade" id="excelImportModal">
            <div class="modal-dialog">
                <form id="excelImportForm" method="POST" action="{{ route('whatsapp.excel.import') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5>WhatsApp Excel Import</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <label>Client *</label>
                            <select name="client_id" class="form-control mb-3" required>
                                <option value="">Select Client</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->client_name }}</option>
                                @endforeach
                            </select>


                            <label class="form-label">Import Date <span class="text-danger">*</span></label>

                            <input type="date" name="import_date" class="form-control" value="{{ date('Y-m-d') }}"
                                required>


                            <label>Excel File *</label>
                            <input type="file" name="file" class="form-control" accept=".xls,.xlsx" required>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" id="importBtn" class="btn btn-success">
                                Import
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="excelImportModaldelhivery">
            <div class="modal-dialog">
                <form id="excelImportForm" method="POST" action="{{ route('delhivery.excel.import') }}"
                    enctype="multipart/form-data">
                    @csrf

                    <div class="modal-content">

                        <div class="modal-header">
                            <h5>
                                Import & Book Delhivery
                            </h5>

                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">

                            <label>
                                Client *
                            </label>

                            <select name="client_id" class="form-control mb-3" required>
                                <option value="">
                                    Select Client
                                </option>

                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}">
                                        {{ $client->client_name }}
                                    </option>
                                @endforeach

                            </select>

                            <label>
                                Import Date *
                            </label>

                            <input type="date" name="import_date" class="form-control mb-3"
                                value="{{ date('Y-m-d') }}" required>

                            <label>
                                Excel File *
                            </label>

                            <input type="file" name="file" class="form-control" accept=".xls,.xlsx" required>

                            <div class="alert alert-info mt-3">

                                Only
                                <strong>COD</strong>
                                and
                                <strong>PREPAID</strong>
                                orders are accepted.

                            </div>

                        </div>

                        <div class="modal-footer">

                            <button type="submit" id="importBtn" class="btn btn-success">
                                Import & Book Delhivery
                            </button>

                        </div>

                    </div>
                </form>
            </div>
        </div>

        <form id="shopifyImportForm" style="margin: 35px 0px 35px 0px;" method="POST"
            action="{{ route('shopify.import') }}" enctype="multipart/form-data">

            @csrf

            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Select Client</label>
                    @if (auth()->user()->role == 'client')
                        <input type="hidden" name="client_id" value="{{ $clients->first()->id }}">

                        <input type="text" class="form-control" value="{{ $clients->first()->client_name }}"
                            readonly>
                    @else
                        <select name="client_id" id="client_id" class="form-control">

                            <option value="">
                                Select Client
                            </option>

                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}">
                                    {{ $client->client_name }}
                                </option>
                            @endforeach

                        </select>
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label">Upload Shopify Excel</label>
                    <input type="file" name="file" class="form-control" required>
                </div>

                <div class="col-md-4">
                    <button type="submit" id="shopifyImportBtn" class="btn btn-primary w-100">
                        Import Orders
                    </button>

                </div>
            </div>
        </form>

        <!--  <div class="row mb-4">

                                                                                                                                                                                                                                                                @if (($showPostOffice ?? false) && !($showLabel ?? false))
    <div class="col-md-6">
                                                                                                                                                                                                                                                                        <div class="card text-center shadow-sm mb-3">

                                                                                                                                                                                                                                                                            <form action="{{ route('postoffice.export') }}" method="POST">
                                                                                                                                                                                                                                                                                @csrf

                                                                                                                                                                                                                                                                                <button type="submit" class="btn btn-primary w-100 p-4">

                                                                                                                                                                                                                                                                                    <h5>Export Post Office Format</h5>

                                                                                                                                                                                                                                                                                </button>

                                                                                                                                                                                                                                                                            </form>

                                                                                                                                                                                                                                                                        </div>
                                                                                                                                                                                                                                                                    </div>
    @endif
                                                                                                                                                                                                                                                                @if ($showLabel ?? false)
    <div class="col-md-6">

                                                                                                                                                                                                                                                                        <div class="card text-center shadow-sm">

                                                                                                                                                                                                                                                                            <button class="btn btn-success p-4 w-100" data-bs-toggle="modal" data-bs-target="#senderModal">

                                                                                                                                                                                                                                                                                <h5>Export Labels</h5>

                                                                                                                                                                                                                                                                            </button>

                                                                                                                                                                                                                                                                        </div>

                                                                                                                                                                                                                                                                    </div>
    @endif

                                                                                                                                                                                                                                                            </div>-->

        <table id="ordersTable" class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Order ID</th>
                    <th>Barcode</th>
                    <th>Customer Name</th>
                    <th>Customer Phone</th>
                    <th>Shipping Address</th>
                    <th>Payment Mode</th>
                    <th>Amount</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Total Weight (GM)</th>
                    <th>Order Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $order->order_id }}</td>
                        <td>{{ $order->barcode }}</td>
                        <td>{{ $order->customer_name }}</td>
                        <td>{{ $order->customer_phone }}</td>
                        <td>{{ $order->shipping_address }}</td>
                        <td>{{ $order->payment_mode }}</td>
                        <td>{{ $order->amount }}</td>
                        <td>{{ $order->shopify_product_name }}</td>
                        <td>{{ $order->quantity }}</td>
                        <td>{{ $order->total_weight }}</td>
                        <td>{{ \Carbon\Carbon::parse($order->order_date)->format('d-m-Y') }}</td>
                    </tr>
                @endforeach
            </tbody>

        </table>

    </div>
    <script>
        document.getElementById('shopifyImportForm').addEventListener('submit', function() {
            document.getElementById('shopifyImportLoader').classList.remove('d-none');
            document.getElementById('shopifyImportBtn').disabled = true;
        });
    </script>

    <!-- Shopify Import Loader -->
    <div id="shopifyImportLoader" class="d-none">
        <div class="loader-backdrop">
            <div class="loader-box">
                <div class="spinner"></div>
                <p>Importing Shopify Orders…</p>
                <small>Please wait, do not refresh</small>
            </div>
        </div>
    </div>

    <div class="modal fade" id="senderModal" tabindex="-1">

        <div class="modal-dialog">

            <form method="POST" action="{{ route('labels.export') }}">

                @csrf

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title">
                            Select Sender
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal">
                        </button>

                    </div>

                    <div class="modal-body">

                        @if (auth()->user()->role == 'client')
                            <div class="mb-3">

                                <label class="form-label">
                                    Client Name
                                </label>

                                <input type="text" class="form-control" value="{{ $clients->first()->client_name }}"
                                    readonly>

                            </div>
                        @endif

                        <div class="mb-3">

                            <label class="form-label">
                                Select Sender
                            </label>

                            <select name="sender_id" class="form-control" required>

                                <option value="">
                                    Select Sender
                                </option>

                                @foreach ($senders as $sender)
                                    <option value="{{ $sender->id }}">
                                        {{ $sender->customer_name }}
                                    </option>
                                @endforeach

                            </select>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="submit" class="btn btn-success">

                            Generate PDF

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>
@endsection
