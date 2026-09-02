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

                    <div class="row g-3 row-cols-2 row-cols-md-3 row-cols-xl-6">

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
        @if (session('open_delhivery_review'))
            <script>
                document.addEventListener(
                    'DOMContentLoaded',
                    function() {

                        const modal =
                            new bootstrap.Modal(
                                document.getElementById(
                                    'delhiveryConfirmModal'
                                )
                            );

                        modal.show();

                        const reviewIds = @json(session('delhivery_review_import_ids', []));
                        const hiddenIds = document.getElementById('delhiveryReviewImportIds');
                        if (hiddenIds) {
                            hiddenIds.value = Array.isArray(reviewIds) ? reviewIds.join(',') : '';
                        }

                        if (typeof loadDelhiveryReview === 'function') {
                            loadDelhiveryReview(
                                {{ (int) session('delhivery_review_client', session('delhivery_import_client_id', 0)) }},
                                @json(session('delhivery_review_date', ''))
                            );
                        }

                    }
                );
            </script>
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

        <!-- ========================================================= -->
        <!-- DELHIVERY IMPORT MODAL -->
        <!-- ========================================================= -->

        <div class="modal fade" id="excelImportModaldelhivery" tabindex="-1" aria-hidden="true">

            <div class="modal-dialog modal-lg">

                <form id="delhiveryImportForm" method="POST" action="{{ route('delhivery.import') }}"
                    enctype="multipart/form-data">

                    @csrf


                    <div class="modal-content">


                        <!-- HEADER -->

                        <div class="modal-header">

                            <div>

                                <h5 class="modal-title mb-1">
                                    <i class="fas fa-truck me-2"></i>

                                    Delhivery Shipment Import
                                </h5>

                                <small class="text-muted">
                                    Import → Review → Confirm → Book
                                </small>

                            </div>


                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                        </div>


                        <!-- BODY -->

                        <div class="modal-body">


                            <!-- CLIENT -->

                            <div class="mb-3">

                                <label class="form-label fw-bold">
                                    Client *
                                </label>


                                <select name="client_id" class="form-select" required>

                                    <option value="">
                                        Select Client
                                    </option>


                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}">
                                            {{ $client->client_name }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>


                            <!-- DATE -->

                            <div class="mb-3">

                                <label class="form-label fw-bold">
                                    Import Date *
                                </label>

                                <input type="date" name="import_date" class="form-control"
                                    value="{{ now()->format('Y-m-d') }}" required>

                            </div>


                            <!-- FILE -->

                            <div class="mb-3">

                                <label class="form-label fw-bold">
                                    Excel File *
                                </label>

                                <input type="file" name="file" class="form-control" accept=".xls,.xlsx" required>

                                <small class="text-muted">
                                    Excel will be imported first.
                                    No booking will happen automatically.
                                </small>

                            </div>


                            <div class="row g-3">


                                <!-- PACKAGE -->

                                <div class="col-md-6">

                                    <label class="form-label fw-bold">
                                        Package Type
                                    </label>


                                    <div class="row g-2">


                                        <div class="col-6">

                                            <label class="border rounded p-3 w-100" style="cursor:pointer;">

                                                <input type="radio" name="package_type" value="flyer"
                                                    {{ session('delhivery_review_package_type', 'flyer') === 'flyer' ? 'checked' : '' }}>

                                                <strong>
                                                    Flyer
                                                </strong>

                                                <div class="small text-muted">
                                                    Lightweight
                                                </div>

                                            </label>

                                        </div>


                                        <div class="col-6">

                                            <label class="border rounded p-3 w-100" style="cursor:pointer;">

                                                <input type="radio" name="package_type" value="box"
                                                    {{ session('delhivery_review_package_type', 'flyer') === 'box' ? 'checked' : '' }}>

                                                <strong>
                                                    Box
                                                </strong>

                                                <div class="small text-muted">
                                                    Box packaging
                                                </div>

                                            </label>

                                        </div>


                                    </div>

                                </div>


                                <!-- SHIPPING -->

                                <div class="col-md-6">

                                    <label class="form-label fw-bold">
                                        Shipping Mode
                                    </label>


                                    <div class="row g-2">


                                        <div class="col-6">

                                            <label class="border rounded p-3 w-100" style="cursor:pointer;">

                                                <input type="radio" name="shipping_mode" value="surface"
                                                    {{ session('delhivery_review_shipping_mode', 'express') === 'surface' ? 'checked' : '' }}>

                                                <strong>
                                                    Surface
                                                </strong>

                                                <div class="small text-muted">
                                                    Economy
                                                </div>

                                            </label>

                                        </div>


                                        <div class="col-6">

                                            <label class="border rounded p-3 w-100" style="cursor:pointer;">

                                                <input type="radio" name="shipping_mode" value="express"
                                                    {{ session('delhivery_review_shipping_mode', 'express') === 'express' ? 'checked' : '' }}>

                                                <strong>
                                                    Express
                                                </strong>

                                                <div class="small text-muted">
                                                    Faster delivery
                                                </div>

                                            </label>

                                        </div>


                                    </div>

                                </div>

                            </div>


                            <!-- INFO -->

                            <div class="alert alert-info mt-3 mb-0">

                                <i class="fas fa-info-circle me-1"></i>

                                <strong>No automatic booking.</strong>

                                After import, review all articles and
                                click <strong>Confirm & Book</strong>.

                            </div>


                        </div>


                        <!-- FOOTER -->

                        <div class="modal-footer">

                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Cancel
                            </button>


                            <button type="submit" id="delhiveryImportBtn" class="btn btn-danger">

                                <i class="fas fa-file-import me-1"></i>

                                Import & Review

                            </button>

                        </div>


                    </div>

                </form>

            </div>

        </div>
        <!-- ========================================================= -->
        <!-- DELHIVERY CONFIRM MODAL -->
        <!-- ========================================================= -->

        <input type="hidden" id="delhiveryReviewImportIds" value="">

        <div class="modal fade" id="delhiveryConfirmModal" tabindex="-1">

            <div class="modal-dialog modal-xl">

                <div class="modal-content">


                    <div class="modal-header">

                        <h5 class="modal-title">
                            Delhivery Booking Review
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                    </div>


                    <div class="modal-body">


                        <!-- SUMMARY -->

                        <div class="row g-2 mb-3">


                            <div class="col-md-2">

                                <div class="border rounded p-2 text-center">

                                    <small>Total</small>

                                    <h5 id="deliveryTotal">
                                        0
                                    </h5>

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="border rounded p-2 text-center">

                                    <small>Ready</small>

                                    <h5 id="deliveryReady" class="text-success">
                                        0
                                    </h5>

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="border rounded p-2 text-center">

                                    <small>Pending</small>

                                    <h5 id="deliveryPending" class="text-warning">
                                        0
                                    </h5>

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="border rounded p-2 text-center">

                                    <small>Booked</small>

                                    <h5 id="deliveryBooked" class="text-primary">
                                        0
                                    </h5>

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="border rounded p-2 text-center">

                                    <small>Errors</small>

                                    <h5 id="deliveryErrors" class="text-danger">
                                        0
                                    </h5>

                                </div>

                            </div>


                            <div class="col-md-2">

                                <div class="border rounded p-2 text-center">

                                    <small>Total Cost</small>

                                    <h5 id="deliveryCost">
                                        ₹0
                                    </h5>

                                </div>

                            </div>

                        </div>


                        <!-- TABLE -->

                        <div class="table-responsive">

                            <table class="table table-bordered table-hover table-sm align-middle">

                                <thead class="table-dark">

                                    <tr>

                                        <th style="width:45px;">
                                            <input type="checkbox" id="selectAllDelivery">
                                        </th>

                                        <th>Order ID</th>

                                        <th>Customer</th>

                                        <th>Phone</th>

                                        <th>Shipping Address</th>

                                        <th>City</th>

                                        <th>State</th>

                                        <th>Pincode</th>

                                        <th>Payment</th>

                                        <th>Amount</th>

                                        <th>Product</th>

                                        <th>Qty</th>

                                        <th>Weight</th>

                                        <th>Serviceability</th>

                                        <th>Status</th>

                                        <th>AWB</th>

                                        <th>Cost</th>

                                        <th>Error</th>

                                        <th>API Report</th>

                                    </tr>

                                </thead>


                                <tbody id="delhiveryReviewBody">

                                    <tr>

                                        <td colspan="19" class="text-center text-muted py-4">
                                            No imported records.
                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>


                    <div class="modal-footer">

                        <form method="POST" action="{{ route('delhivery.import.confirm') }}" id="delhiveryConfirmForm">

                            @csrf


                            <input type="hidden" name="package_type" id="confirmPackageType" value="flyer">


                            <input type="hidden" name="shipping_mode" id="confirmShippingMode" value="express">


                            <div id="selectedDeliveryIds"></div>


                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Close
                            </button>


                            <button type="submit" class="btn btn-success" id="confirmDeliveryButton">

                                <i class="fas fa-check me-1"></i>

                                Check Rates & Serviceability

                            </button>

                        </form>

                    </div>


                </div>

            </div>

        </div>

        <!-- ========================================================= -->
        <!-- DELHIVERY API REPORT MODAL -->
        <!-- ========================================================= -->
        <div class="modal fade" id="delhiveryApiReportModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-dark text-white">
                        <div>
                            <h5 class="modal-title mb-0">
                                <i class="fas fa-file-code me-2"></i>Delhivery API Report
                            </h5>
                            <small id="delhiveryApiReportTitle" class="text-white-50"></small>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">

                            <div class="col-md-3">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted">
                                        Order ID
                                    </small>

                                    <div id="apiReportOrder" class="fw-bold">
                                        -
                                    </div>
                                </div>
                            </div>


                            <div class="col-md-2">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted">
                                        Pincode
                                    </small>

                                    <div id="apiReportPincode" class="fw-bold">
                                        -
                                    </div>
                                </div>
                            </div>


                            <div class="col-md-2">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted">
                                        Weight
                                    </small>

                                    <div id="apiReportWeight" class="fw-bold">
                                        -
                                    </div>
                                </div>
                            </div>


                            <div class="col-md-2">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted">
                                        Serviceability
                                    </small>

                                    <div id="apiReportServiceabilityStatus" class="fw-bold">
                                        -
                                    </div>
                                </div>
                            </div>


                            <div class="col-md-2">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted">
                                        Estimated Cost
                                    </small>

                                    <div id="apiReportCost" class="fw-bold text-success">
                                        -
                                    </div>
                                </div>
                            </div>


                            <div class="col-md-2">
                                <div class="border rounded p-3 h-100">
                                    <small class="text-muted">
                                        Rate Mode
                                    </small>

                                    <div id="apiReportShippingMode" class="fw-bold">
                                        -
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="alert alert-danger d-none" id="apiReportErrorBox">
                            <strong>API Error:</strong>
                            <span id="apiReportError"></span>
                        </div>

                        <h6 class="fw-bold mt-3">
                            Serviceability API Response
                        </h6>

                        <pre id="apiReportServiceability" class="bg-light border rounded p-3 small"
                            style="max-height:300px;overflow:auto;white-space:pre-wrap;"></pre>


                        <h6 class="fw-bold mt-4">
                            Shipping Cost API Response
                        </h6>

                        <pre id="apiReportShippingCost" class="bg-light border rounded p-3 small"
                            style="max-height:350px;overflow:auto;white-space:pre-wrap;"></pre>


                        <h6 class="fw-bold mt-4">
                            Booking API Response
                        </h6>

                        <pre id="apiReportBooking" class="bg-light border rounded p-3 small"
                            style="max-height:350px;overflow:auto;white-space:pre-wrap;"></pre>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
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
    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function() {


                const importForm =
                    document.getElementById(
                        'delhiveryImportForm'
                    );


                const importButton =
                    document.getElementById(
                        'delhiveryImportBtn'
                    );


                const reviewBody =
                    document.getElementById(
                        'delhiveryReviewBody'
                    );


                const selectAll =
                    document.getElementById(
                        'selectAllDelivery'
                    );


                /*
                |--------------------------------------------------------------------------
                | IMPORT
                |--------------------------------------------------------------------------
                */

                if (importForm) {

                    importForm.addEventListener(
                        'submit',
                        function() {

                            importButton.disabled =
                                true;

                            importButton.innerHTML = `
                        <span
                            class="spinner-border spinner-border-sm me-1"
                        ></span>

                        Importing...
                    `;

                        }
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | PACKAGE TYPE
                |--------------------------------------------------------------------------
                */

                document
                    .querySelectorAll(
                        'input[name="package_type"]'
                    )
                    .forEach(
                        function(input) {

                            input.addEventListener(
                                'change',
                                function() {

                                    if (
                                        this.checked
                                    ) {

                                        document
                                            .getElementById(
                                                'confirmPackageType'
                                            )
                                            .value =
                                            this.value;

                                    }

                                }
                            );

                        }
                    );


                /*
                |--------------------------------------------------------------------------
                | SHIPPING MODE
                |--------------------------------------------------------------------------
                */

                document
                    .querySelectorAll(
                        'input[name="shipping_mode"]'
                    )
                    .forEach(
                        function(input) {

                            input.addEventListener(
                                'change',
                                function() {

                                    if (
                                        this.checked
                                    ) {

                                        document
                                            .getElementById(
                                                'confirmShippingMode'
                                            )
                                            .value =
                                            this.value;

                                    }

                                }
                            );

                        }
                    );


                /*
                |--------------------------------------------------------------------------
                | SELECT ALL
                |--------------------------------------------------------------------------
                */

                if (selectAll) {

                    selectAll.addEventListener(
                        'change',
                        function() {

                            document
                                .querySelectorAll(
                                    '.delivery-checkbox'
                                )
                                .forEach(
                                    function(checkbox) {

                                        if (
                                            !checkbox.disabled
                                        ) {

                                            checkbox.checked =
                                                selectAll.checked;

                                        }

                                    }
                                );


                            updateSelected();

                        }
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | UPDATE SELECTED
                |--------------------------------------------------------------------------
                */

                function updateSelected() {

                    const container =
                        document.getElementById(
                            'selectedDeliveryIds'
                        );


                    container.innerHTML =
                        '';


                    const selected =
                        document.querySelectorAll(
                            '.delivery-checkbox:checked'
                        );


                    selected.forEach(
                        function(checkbox) {

                            const input =
                                document.createElement(
                                    'input'
                                );


                            input.type =
                                'hidden';


                            input.name =
                                'import_ids[]';


                            input.value =
                                checkbox.value;


                            container.appendChild(
                                input
                            );

                        }
                    );


                    document
                        .getElementById(
                            'confirmDeliveryButton'
                        )
                        .innerHTML =

                        selected.length

                        ?

                        `
                    <i class="fas fa-check me-1"></i>
                    Confirm & Book (${selected.length})
                `

                        :

                        `
                    <i class="fas fa-check me-1"></i>
                    Confirm & Book
                `;

                }


                /*
                |--------------------------------------------------------------------------
                | AUTO BIND
                |--------------------------------------------------------------------------
                */

                document.addEventListener(
                    'change',
                    function(event) {

                        if (
                            event.target.classList
                            .contains(
                                'delivery-checkbox'
                            )
                        ) {

                            updateSelected();

                        }

                    }
                );


                /*
                |--------------------------------------------------------------------------
                | CONFIRM
                |--------------------------------------------------------------------------
                */

                const confirmForm =
                    document.getElementById(
                        'delhiveryConfirmForm'
                    );


                if (confirmForm) {

                    confirmForm.addEventListener(
                        'submit',
                        function(event) {

                            const selected =
                                document.querySelectorAll(
                                    '.delivery-checkbox:checked'
                                );


                            if (
                                selected.length === 0
                            ) {

                                event.preventDefault();

                                Swal.fire(
                                    'Select Shipment',
                                    'Please select at least one ready shipment.',
                                    'warning'
                                );

                                return;

                            }


                            const confirmed =
                                confirm(
                                    'Confirm booking for ' +
                                    selected.length +
                                    ' shipment(s)?'
                                );


                            if (!confirmed) {

                                event.preventDefault();

                            }

                        }
                    );

                }

            }
        );
    </script>
    <script>
        let delhiveryReviewItems = [];

        /*
        |--------------------------------------------------------------------------
        | HELPERS
        |--------------------------------------------------------------------------
        */

        function escapeHtml(value) {

            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }


        function prettyJson(value) {

            if (
                value === null ||
                value === undefined ||
                value === ''
            ) {
                return 'No response saved.';
            }

            try {

                if (typeof value === 'string') {

                    const parsed =
                        JSON.parse(value);

                    return JSON.stringify(
                        parsed,
                        null,
                        2
                    );
                }

                return JSON.stringify(
                    value,
                    null,
                    2
                );

            } catch (error) {

                return String(value);
            }
        }


        function money(value) {

            const number =
                Number.parseFloat(value);

            if (!Number.isFinite(number)) {
                return '-';
            }

            return '₹' + number.toFixed(2);
        }


        function numberValue(value) {

            const number =
                Number.parseFloat(value);

            return Number.isFinite(number) ?
                number :
                0;
        }


        /*
        |--------------------------------------------------------------------------
        | LOAD REVIEW
        |--------------------------------------------------------------------------
        */

        async function loadDelhiveryReview(
            clientId,
            selectedDate = '',
            shippingMode = ''
        ) {

            if (!clientId) {
                return;
            }

            const body =
                document.getElementById(
                    'delhiveryReviewBody'
                );

            if (body) {

                body.innerHTML = `
                <tr>
                    <td colspan="19"
                        class="text-center py-5">

                        <div class="spinner-border text-primary"></div>

                        <div class="mt-2">
                            Checking Delhivery
                            serviceability & rates...
                        </div>

                    </td>
                </tr>
            `;
            }

            try {

                let url =
                    "{{ route('delhivery.import.preview') }}" +
                    '?client_id=' +
                    encodeURIComponent(clientId);

                /*
                |--------------------------------------------------------------------------
                | DATE
                |--------------------------------------------------------------------------
                */

                if (selectedDate) {

                    url +=
                        '&date=' +
                        encodeURIComponent(
                            selectedDate
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | SHIPPING MODE
                |--------------------------------------------------------------------------
                */

                if (shippingMode) {

                    url +=
                        '&shipping_mode=' +
                        encodeURIComponent(
                            shippingMode
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | FETCH
                |--------------------------------------------------------------------------
                */

                const response =
                    await fetch(
                        url, {
                            method: 'GET',

                            headers: {
                                'Accept': 'application/json',

                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        }
                    );

                const data =
                    await response.json();

                if (
                    !response.ok ||
                    !data.success
                ) {

                    throw new Error(
                        data.message ||
                        'Unable to load Delhivery review.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | STORE
                |--------------------------------------------------------------------------
                */

                delhiveryReviewItems =
                    Array.isArray(data.items) ?
                    data.items : [];

                /*
                |--------------------------------------------------------------------------
                | RENDER
                |--------------------------------------------------------------------------
                */

                renderDelhiveryReview(
                    delhiveryReviewItems
                );

            } catch (error) {

                console.error(
                    'Delhivery Review Error:',
                    error
                );

                if (body) {

                    body.innerHTML = `
                    <tr>
                        <td colspan="19"
                            class="text-center text-danger py-4">

                            ${escapeHtml(
                                error.message
                            )}

                        </td>
                    </tr>
                `;
                }

                updateDeliverySummary(
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0
                );

                updateSelected();
            }
        }


        /*
        |--------------------------------------------------------------------------
        | RENDER REVIEW
        |--------------------------------------------------------------------------
        */

        function renderDelhiveryReview(items) {

            const body =
                document.getElementById(
                    'delhiveryReviewBody'
                );

            if (!body) {
                return;
            }

            body.innerHTML = '';

            let total = 0;
            let ready = 0;
            let pending = 0;
            let queued = 0;
            let booked = 0;
            let errors = 0;
            let cost = 0;


            /*
            |--------------------------------------------------------------------------
            | EMPTY
            |--------------------------------------------------------------------------
            */

            if (
                !Array.isArray(items) ||
                items.length === 0
            ) {

                body.innerHTML = `
                <tr>
                    <td colspan="19"
                        class="text-center text-muted py-4">

                        No imported records for selected
                        client/date.

                    </td>
                </tr>
            `;

                updateDeliverySummary(
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0
                );

                updateSelected();

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | ITEMS
            |--------------------------------------------------------------------------
            */

            items.forEach(function(item) {

                total++;

                /*
                |--------------------------------------------------------------------------
                | STATUS
                |--------------------------------------------------------------------------
                */

                const status =
                    String(
                        item.status ||
                        'pending'
                    ).toLowerCase();


                const isQueued = [
                    'booking_queued',
                    'queued'
                ].includes(status);


                const isBooked = [
                    'booked',
                    'order_created',
                    'label_generated',
                    'completed'
                ].includes(status);


                const isError = [
                    'rate_failed',
                    'serviceability_failed',
                    'pincode_not_serviceable',
                    'booking_failed',
                    'tracking_failed',
                    'label_failed'
                ].includes(status);


                const isReady = [
                    'pending',
                    'ready',
                    'serviceability_checked'
                ].includes(status);


                /*
                |--------------------------------------------------------------------------
                | COUNTERS
                |--------------------------------------------------------------------------
                */

                if (isQueued) {

                    queued++;

                } else if (isBooked) {

                    booked++;

                } else if (isError) {

                    errors++;

                } else if (isReady) {

                    ready++;

                } else {

                    pending++;
                }


                /*
                |--------------------------------------------------------------------------
                | COST
                |--------------------------------------------------------------------------
                */

                const itemCost =
                    numberValue(
                        item.shipping_cost
                    );

                cost += itemCost;


                /*
                |--------------------------------------------------------------------------
                | SERVICEABILITY
                |--------------------------------------------------------------------------
                */

                const serviceability =
                    String(
                        item.serviceability_status ||
                        'Pending'
                    );

                const serviceabilityLower =
                    serviceability.toLowerCase();


                let serviceabilityClass =
                    'bg-secondary';


                if (
                    serviceabilityLower ===
                    'serviceable'
                ) {

                    serviceabilityClass =
                        'bg-success';

                } else if (
                    serviceabilityLower ===
                    'not serviceable'
                ) {

                    serviceabilityClass =
                        'bg-danger';

                } else if (
                    serviceabilityLower ===
                    'failed'
                ) {

                    serviceabilityClass =
                        'bg-danger';
                }


                /*
                |--------------------------------------------------------------------------
                | STATUS CLASS
                |--------------------------------------------------------------------------
                */

                let statusClass =
                    'bg-secondary';


                if (isError) {

                    statusClass =
                        'bg-danger';

                } else if (isBooked) {

                    statusClass =
                        'bg-success';

                } else if (isQueued) {

                    statusClass =
                        'bg-info text-dark';

                } else if (isReady) {

                    statusClass =
                        'bg-primary';

                } else {

                    statusClass =
                        'bg-warning text-dark';
                }


                /*
                |--------------------------------------------------------------------------
                | ERROR
                |--------------------------------------------------------------------------
                */

                const errorMessage =
                    item.error_message ||
                    item.booking_error ||
                    item.serviceability_error ||
                    '-';


                /*
                |--------------------------------------------------------------------------
                | COST DISPLAY
                |--------------------------------------------------------------------------
                */

                const costDisplay =
                    itemCost > 0 ?
                    money(itemCost) :
                    '<span class="text-danger">Not Returned</span>';


                /*
                |--------------------------------------------------------------------------
                | CHECKBOX
                |--------------------------------------------------------------------------
                */

                const checkbox =
                    isReady ?
                    `
                        <input
                            type="checkbox"
                            class="delivery-checkbox"
                            value="${escapeHtml(item.id)}"
                        >
                    ` :
                    '';


                /*
                |--------------------------------------------------------------------------
                | ROW
                |--------------------------------------------------------------------------
                */

                const tr =
                    document.createElement(
                        'tr'
                    );


                tr.innerHTML = `

                <td class="text-center">
                    ${checkbox}
                </td>


                <td>
                    ${escapeHtml(
                        item.order_id || '-'
                    )}
                </td>


                <td>
                    ${escapeHtml(
                        item.customer_name || '-'
                    )}
                </td>


                <td>
                    ${escapeHtml(
                        item.customer_phone || '-'
                    )}
                </td>


                <td style="
                    min-width:220px;
                    max-width:350px;
                    white-space:normal;
                ">
                    ${escapeHtml(
                        item.shipping_address || '-'
                    )}
                </td>


                <td>
                    ${escapeHtml(
                        item.city || '-'
                    )}
                </td>


                <td>
                    ${escapeHtml(
                        item.state || '-'
                    )}
                </td>


                <td>
                    ${escapeHtml(
                        item.pincode || '-'
                    )}
                </td>


                <td>
                    ${escapeHtml(
                        item.payment_mode || '-'
                    )}
                </td>


                <td class="text-nowrap">
                    ${
                        item.amount !== null &&
                        item.amount !== undefined &&
                        item.amount !== ''
                            ? money(item.amount)
                            : '-'
                    }
                </td>


                <td>
                    ${escapeHtml(
                        item.product || '-'
                    )}
                </td>


                <td class="text-center">
                    ${escapeHtml(
                        item.quantity || '-'
                    )}
                </td>


                <td class="text-nowrap">
                    ${escapeHtml(
                        item.weight || '-'
                    )} GM
                </td>


                <td>
                    <span class="badge ${serviceabilityClass}">
                        ${escapeHtml(
                            serviceability
                        )}
                    </span>
                </td>


                <td>
                    <span class="badge ${statusClass}">
                        ${escapeHtml(
                            item.status || 'pending'
                        )}
                    </span>
                </td>


                <td>
                    ${escapeHtml(
                        item.awb || '-'
                    )}
                </td>


                <td class="text-nowrap fw-bold">
                    ${costDisplay}
                </td>


                <td
                    class="text-danger"
                    style="
                        min-width:220px;
                        max-width:350px;
                        white-space:normal;
                    "
                >
                    ${escapeHtml(
                        errorMessage
                    )}
                </td>


                <td>
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-dark api-report-btn"
                        data-id="${escapeHtml(item.id)}"
                    >
                        <i class="fas fa-file-code me-1"></i>
                        Report
                    </button>
                </td>

            `;

                body.appendChild(tr);

            });


            /*
            |--------------------------------------------------------------------------
            | SUMMARY
            |--------------------------------------------------------------------------
            */

            updateDeliverySummary(
                total,
                ready,
                pending,
                queued,
                booked,
                errors,
                cost
            );


            /*
            |--------------------------------------------------------------------------
            | SELECTED
            |--------------------------------------------------------------------------
            */

            updateSelected();
        }


        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        function updateDeliverySummary(
            total,
            ready,
            pending,
            queued,
            booked,
            errors,
            cost
        ) {

            const setValue =
                function(id, value) {

                    const element =
                        document.getElementById(id);

                    if (element) {

                        element.innerText =
                            value;
                    }
                };


            setValue(
                'deliveryTotal',
                total
            );

            setValue(
                'deliveryReady',
                ready
            );

            setValue(
                'deliveryPending',
                pending
            );

            setValue(
                'deliveryBooked',
                booked
            );

            setValue(
                'deliveryErrors',
                errors
            );

            setValue(
                'deliveryCost',
                '₹' +
                Number(
                    cost || 0
                ).toFixed(2)
            );
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE SELECTED
        |--------------------------------------------------------------------------
        */

        function updateSelected() {

            const container =
                document.getElementById(
                    'selectedDeliveryIds'
                );

            const button =
                document.getElementById(
                    'confirmDeliveryButton'
                );

            if (
                !container ||
                !button
            ) {
                return;
            }


            container.innerHTML = '';


            const selected =
                document.querySelectorAll(
                    '.delivery-checkbox:checked'
                );


            selected.forEach(
                function(checkbox) {

                    const input =
                        document.createElement(
                            'input'
                        );

                    input.type =
                        'hidden';

                    input.name =
                        'import_ids[]';

                    input.value =
                        checkbox.value;

                    container.appendChild(
                        input
                    );
                }
            );


            button.disabled =
                selected.length === 0;


            button.innerHTML =
                selected.length

                ?
                `
                    <i class="fas fa-check me-1"></i>
                    Confirm & Book
                    (${selected.length})
                `

                :
                `
                    <i class="fas fa-check me-1"></i>
                    Confirm & Book
                `;
        }


        /*
        |--------------------------------------------------------------------------
        | SELECT ALL
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'change',
            function(event) {

                if (
                    event.target.classList.contains(
                        'delivery-checkbox'
                    )
                ) {

                    updateSelected();
                }


                if (
                    event.target.id ===
                    'selectAllDelivery'
                ) {

                    document
                        .querySelectorAll(
                            '.delivery-checkbox'
                        )
                        .forEach(
                            function(checkbox) {

                                if (
                                    !checkbox.disabled
                                ) {

                                    checkbox.checked =
                                        event.target.checked;
                                }
                            }
                        );

                    updateSelected();
                }
            }
        );


        /*
        |--------------------------------------------------------------------------
        | API REPORT
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'click',
            function(event) {

                const button =
                    event.target.closest(
                        '.api-report-btn'
                    );

                if (!button) {
                    return;
                }


                const item =
                    delhiveryReviewItems.find(
                        function(row) {

                            return String(row.id) ===
                                String(
                                    button.dataset.id
                                );
                        }
                    );


                if (!item) {
                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | BASIC
                |--------------------------------------------------------------------------
                */

                const setText =
                    function(id, value) {

                        const element =
                            document.getElementById(id);

                        if (element) {

                            element.innerText =
                                value;
                        }
                    };


                setText(
                    'apiReportOrder',
                    item.order_id || '-'
                );


                setText(
                    'apiReportAwb',
                    item.awb || '-'
                );


                setText(
                    'apiReportStatus',
                    item.status || '-'
                );


                setText(
                    'apiReportPincode',
                    item.pincode || '-'
                );


                setText(
                    'apiReportWeight',
                    item.weight ?
                    item.weight + ' GM' :
                    '-'
                );


                setText(
                    'apiReportServiceabilityStatus',
                    item.serviceability_status || '-'
                );


                setText(
                    'apiReportTitle',
                    'Import ID: ' + item.id
                );


                /*
                |--------------------------------------------------------------------------
                | COST
                |--------------------------------------------------------------------------
                */

                const cost =
                    numberValue(
                        item.shipping_cost
                    );


                setText(
                    'apiReportCost',
                    cost > 0 ?
                    money(cost) :
                    'Not Returned'
                );


                /*
                |--------------------------------------------------------------------------
                | RATE DETAILS
                |--------------------------------------------------------------------------
                */

                const rate =
                    item.rate_details || {};


                const rateDetails =
                    document.getElementById(
                        'apiReportRateDetails'
                    );


                if (rateDetails) {

                    rateDetails.innerHTML = `

                    <div class="row g-3">

                        <div class="col-md-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted">
                                    Gross Amount
                                </small>
                                <div class="fw-bold fs-5">
                                    ${money(rate.gross_amount)}
                                </div>
                            </div>
                        </div>


                        <div class="col-md-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted">
                                    Total Amount
                                </small>
                                <div class="fw-bold fs-5 text-success">
                                    ${money(rate.total_amount || cost)}
                                </div>
                            </div>
                        </div>


                        <div class="col-md-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted">
                                    Tax
                                </small>
                                <div class="fw-bold fs-5">
                                    ${money(rate.tax_amount)}
                                </div>
                            </div>
                        </div>


                        <div class="col-md-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted">
                                    Charged Weight
                                </small>
                                <div class="fw-bold fs-5">
                                    ${
                                        rate.charged_weight
                                            ? rate.charged_weight + ' GM'
                                            : '-'
                                    }
                                </div>
                            </div>
                        </div>


                        <div class="col-md-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted">
                                    Delivery Charge
                                </small>
                                <div class="fw-bold">
                                    ${money(rate.charge_DL)}
                                </div>
                            </div>
                        </div>


                        <div class="col-md-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted">
                                    COD Charge
                                </small>
                                <div class="fw-bold">
                                    ${money(rate.charge_COD)}
                                </div>
                            </div>
                        </div>


                        <div class="col-md-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted">
                                    DPH
                                </small>
                                <div class="fw-bold">
                                    ${money(rate.charge_DPH)}
                                </div>
                            </div>
                        </div>


                        <div class="col-md-3">
                            <div class="border rounded p-3 h-100">
                                <small class="text-muted">
                                    Peak Charge
                                </small>
                                <div class="fw-bold">
                                    ${money(rate.charge_PEAK)}
                                </div>
                            </div>
                        </div>

                    </div>
                `;
                }


                /*
                |--------------------------------------------------------------------------
                | SERVICEABILITY API
                |--------------------------------------------------------------------------
                */

                const serviceabilityBox =
                    document.getElementById(
                        'apiReportServiceability'
                    );

                if (serviceabilityBox) {

                    serviceabilityBox.textContent =
                        prettyJson(
                            item.serviceability_response
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | SHIPPING COST API
                |--------------------------------------------------------------------------
                */

                const shippingCostBox =
                    document.getElementById(
                        'apiReportShippingCost'
                    );

                if (shippingCostBox) {

                    shippingCostBox.textContent =
                        prettyJson(
                            item.shipping_cost_response
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | BOOKING API
                |--------------------------------------------------------------------------
                */

                const bookingBox =
                    document.getElementById(
                        'apiReportBooking'
                    );

                if (bookingBox) {

                    bookingBox.textContent =
                        prettyJson(
                            item.booking_response
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | ERROR
                |--------------------------------------------------------------------------
                */

                const errorBox =
                    document.getElementById(
                        'apiReportErrorBox'
                    );

                const errorText =
                    document.getElementById(
                        'apiReportError'
                    );


                /*
                | IMPORTANT:
                | Don't show old parser error if a valid
                | shipping cost exists.
                */

                if (
                    item.error_message &&
                    cost <= 0
                ) {

                    if (errorText) {

                        errorText.innerText =
                            item.error_message;
                    }

                    if (errorBox) {

                        errorBox.classList.remove(
                            'd-none'
                        );
                    }

                } else {

                    if (errorText) {

                        errorText.innerText = '';
                    }

                    if (errorBox) {

                        errorBox.classList.add(
                            'd-none'
                        );
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | OPEN MODAL
                |--------------------------------------------------------------------------
                */

                const modalElement =
                    document.getElementById(
                        'delhiveryApiReportModal'
                    );


                if (modalElement) {

                    bootstrap.Modal
                        .getOrCreateInstance(
                            modalElement
                        )
                        .show();
                }

            }
        );


        /*
        |--------------------------------------------------------------------------
        | CONFIRM BOOKING
        |--------------------------------------------------------------------------
        */

        const confirmForm =
            document.getElementById(
                'delhiveryConfirmForm'
            );


        if (confirmForm) {

            confirmForm.addEventListener(
                'submit',
                function(event) {

                    const selected =
                        document.querySelectorAll(
                            '.delivery-checkbox:checked'
                        );


                    if (
                        selected.length === 0
                    ) {

                        event.preventDefault();

                        Swal.fire(
                            'Select Shipment',
                            'Please select at least one ready shipment.',
                            'warning'
                        );

                        return;
                    }


                    /*
                    | Check that selected rows
                    | have valid serviceability + cost.
                    */

                    const selectedIds =
                        Array.from(
                            selected
                        ).map(
                            checkbox =>
                            String(
                                checkbox.value
                            )
                        );


                    const invalid =
                        delhiveryReviewItems.filter(
                            function(item) {

                                if (
                                    !selectedIds.includes(
                                        String(item.id)
                                    )
                                ) {
                                    return false;
                                }

                                const cost =
                                    numberValue(
                                        item.shipping_cost
                                    );

                                const serviceability =
                                    String(
                                        item.serviceability_status ||
                                        ''
                                    ).toLowerCase();

                                return (
                                    cost <= 0 ||
                                    serviceability !==
                                    'serviceable'
                                );
                            }
                        );


                    if (invalid.length > 0) {

                        event.preventDefault();

                        Swal.fire(
                            'Unable to Book',
                            invalid.length +
                            ' selected shipment(s) do not have a valid rate/serviceability.',
                            'error'
                        );

                        return;
                    }


                    const confirmed =
                        confirm(
                            'Confirm booking for ' +
                            selected.length +
                            ' shipment(s)?'
                        );


                    if (!confirmed) {

                        event.preventDefault();

                        return;
                    }


                    const button =
                        document.getElementById(
                            'confirmDeliveryButton'
                        );


                    if (button) {

                        button.disabled =
                            true;

                        button.innerHTML =
                            `
                        <span class="spinner-border spinner-border-sm me-1"></span>
                        Queuing...
                        `;
                    }

                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SHIPPING MODE CHANGE
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                'input[name="shipping_mode"]'
            )
            .forEach(
                function(input) {

                    input.addEventListener(
                        'change',
                        function() {

                            if (!this.checked) {
                                return;
                            }


                            const confirmMode =
                                document.getElementById(
                                    'confirmShippingMode'
                                );


                            if (confirmMode) {

                                confirmMode.value =
                                    this.value;
                            }


                            /*
                            | Reload rates for selected mode
                            */

                            const clientSelect =
                                document.querySelector(
                                    '#delhiveryImportForm select[name="client_id"]'
                                );


                            if (
                                clientSelect &&
                                clientSelect.value
                            ) {

                                loadDelhiveryReview(
                                    clientSelect.value,
                                    '',
                                    this.value
                                );
                            }

                        }
                    );

                }
            );


        /*
        |--------------------------------------------------------------------------
        | CLIENT CHANGE
        |--------------------------------------------------------------------------
        */

        const delhiveryClientSelect =
            document.querySelector(
                '#delhiveryImportForm select[name="client_id"]'
            );


        if (delhiveryClientSelect) {

            delhiveryClientSelect.addEventListener(
                'change',
                function() {

                    if (!this.value) {
                        return;
                    }


                    const selectedMode =
                        document.querySelector(
                            '#delhiveryImportForm input[name="shipping_mode"]:checked'
                        );


                    loadDelhiveryReview(
                        this.value,
                        '',
                        selectedMode ?
                        selectedMode.value :
                        'express'
                    );

                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PACKAGE TYPE
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll(
                '#delhiveryImportForm input[name="package_type"]'
            )
            .forEach(
                function(input) {

                    input.addEventListener(
                        'change',
                        function() {

                            const target =
                                document.getElementById(
                                    'confirmPackageType'
                                );

                            if (
                                target &&
                                this.checked
                            ) {

                                target.value =
                                    this.value;
                            }
                        }
                    );

                }
            );


        /*
        |--------------------------------------------------------------------------
        | INITIAL SELECTED VALUES
        |--------------------------------------------------------------------------
        */

        document.addEventListener(
            'DOMContentLoaded',
            function() {

                const packageType =
                    document.querySelector(
                        '#delhiveryImportForm input[name="package_type"]:checked'
                    );


                const confirmPackageType =
                    document.getElementById(
                        'confirmPackageType'
                    );


                if (
                    packageType &&
                    confirmPackageType
                ) {

                    confirmPackageType.value =
                        packageType.value;
                }


                const shippingMode =
                    document.querySelector(
                        '#delhiveryImportForm input[name="shipping_mode"]:checked'
                    );


                const confirmShippingMode =
                    document.getElementById(
                        'confirmShippingMode'
                    );


                if (
                    shippingMode &&
                    confirmShippingMode
                ) {

                    confirmShippingMode.value =
                        shippingMode.value;
                }


                /*
                | Existing selected client
                */

                const client =
                    document.querySelector(
                        '#delhiveryImportForm select[name="client_id"]'
                    );


                if (
                    client &&
                    client.value
                ) {

                    loadDelhiveryReview(
                        client.value,
                        '',
                        shippingMode ?
                        shippingMode.value :
                        'express'
                    );
                }

            }
        );
    </script>
@endsection
