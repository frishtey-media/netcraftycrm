@extends('layouts.admin')

@section('content')
    <style>
        /* =========================================================
                                               COMPACT PROFESSIONAL ADMIN DASHBOARD
                                            ========================================================= */

        .admin-dashboard {
            background: #f6f8fb;
            min-height: calc(100vh - 70px);
            padding: 5px 0 12px;
        }

        /* ================= HEADER ================= */

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .dashboard-header h3 {
            margin: 0;
            font-size: 21px;
            font-weight: 700;
            color: #182230;
        }

        .dashboard-header p {
            margin: 2px 0 0;
            color: #7a8699;
            font-size: 12px;
        }

        /* ================= SECTION TITLE ================= */

        .section-title {
            display: flex;
            align-items: center;
            gap: 8px;

            margin: 11px 0 7px;

            color: #1e293b;
            font-size: 14px;
            font-weight: 700;
        }

        .section-title::before {
            content: "";
            width: 3px;
            height: 16px;
            border-radius: 10px;
            background: #198754;
        }

        /* ================= GRID SPACING ================= */

        .row.g-4 {
            --bs-gutter-x: 10px;
            --bs-gutter-y: 10px;
        }

        /* ================= DASHBOARD CARD ================= */

        .dashboard-card {
            position: relative;
            overflow: hidden;

            height: 82px;
            min-height: 82px;

            padding: 12px 15px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            border-radius: 12px;

            background: #ffffff;
            border: 1px solid #edf0f4;

            text-decoration: none;
            color: inherit;

            box-shadow: 0 3px 12px rgba(15, 23, 42, 0.045);

            transition:
                transform .2s ease,
                box-shadow .2s ease,
                border-color .2s ease;
        }

        .dashboard-card::after {
            content: "";

            position: absolute;

            width: 70px;
            height: 70px;

            right: -28px;
            bottom: -35px;

            border-radius: 50%;

            background: rgba(255, 255, 255, .25);

            pointer-events: none;
        }

        .dashboard-card:hover {
            transform: translateY(-2px);

            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.09);

            border-color: transparent;

            color: inherit;
        }

        .card-content {
            position: relative;
            z-index: 2;

            min-width: 0;
        }

        .card-title {
            font-size: 13px;
            line-height: 1.2;

            font-weight: 600;

            color: #526071;

            margin-bottom: 4px;
        }

        .card-count {
            font-size: 22px;
            line-height: 1;

            font-weight: 750;

            color: #172033;
        }

        .card-description {
            color: #7b8798;

            font-size: 10px;

            line-height: 1.2;

            margin-top: 3px;

            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;

            max-width: 250px;
        }

        /* ================= ICON ================= */

        .card-icon-wrapper {
            width: 40px;
            height: 40px;

            border-radius: 11px;

            display: flex;
            align-items: center;
            justify-content: center;

            flex-shrink: 0;

            position: relative;
            z-index: 2;
        }

        .card-icon {
            font-size: 18px;
        }

        /* ================= CARD COLORS ================= */

        .card-green {
            background: linear-gradient(135deg,
                    #ffffff 0%,
                    #eefaf4 100%);
        }

        .card-green .card-icon-wrapper {
            background: #d9f3e6;
            color: #16824d;
        }

        .card-blue {
            background: linear-gradient(135deg,
                    #ffffff 0%,
                    #eef5ff 100%);
        }

        .card-blue .card-icon-wrapper {
            background: #dceaff;
            color: #2563eb;
        }

        .card-orange {
            background: linear-gradient(135deg,
                    #ffffff 0%,
                    #fff7e8 100%);
        }

        .card-orange .card-icon-wrapper {
            background: #ffebc2;
            color: #d97706;
        }

        .danger-action {
            background: linear-gradient(135deg,
                    #ffffff 0%,
                    #fff5f5 100%);
        }

        .danger-action .card-icon-wrapper {
            background: #ffe1e1;
            color: #dc3545;
        }

        .action-card {
            cursor: pointer;
        }

        /* ================= ALERT CARDS ================= */

        .alert-card {
            position: relative;

            overflow: hidden;

            border-radius: 12px;

            border: 0;

            box-shadow:
                0 4px 14px rgba(15, 23, 42, 0.07);

            transition: all .2s ease;
        }

        .alert-card:hover {
            transform: translateY(-2px);

            box-shadow:
                0 9px 22px rgba(15, 23, 42, 0.12);
        }

        .alert-card-body {
            height: 82px;
            min-height: 82px;

            padding: 12px 15px;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .alert-card h6 {
            margin: 0 0 4px;

            font-size: 11px;

            line-height: 1.2;

            font-weight: 600;

            opacity: .92;
        }

        .alert-card h2 {
            margin: 0;

            font-size: 22px;

            line-height: 1;

            font-weight: 750;
        }

        .alert-icon {
            width: 40px;
            height: 40px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 11px;

            background: rgba(255, 255, 255, .20);

            font-size: 17px;
        }

        .alert-transit {
            background: linear-gradient(135deg,
                    #ef4444,
                    #dc2626);

            color: #ffffff;
        }

        .alert-rto {
            background: linear-gradient(135deg,
                    #f97316,
                    #ea580c);

            color: #ffffff;
        }

        .alert-booking {
            background: linear-gradient(135deg,
                    #7c3aed,
                    #6d28d9);

            color: #ffffff;
        }

        /* ================= MODALS ================= */

        .modal-content {
            border: 0;

            border-radius: 14px;

            overflow: hidden;

            box-shadow:
                0 20px 60px rgba(15, 23, 42, .20);
        }

        .modal-header {
            padding: 16px 20px;

            border-bottom: 1px solid #edf0f4;
        }

        .modal-title {
            font-size: 17px;

            font-weight: 700;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            padding: 14px 20px;

            border-top: 1px solid #edf0f4;
        }

        .modal-body label {
            display: block;

            font-size: 12px;

            font-weight: 600;

            color: #526071;

            margin-bottom: 6px;
        }

        .modal-body .form-control {
            min-height: 42px;

            border-radius: 9px;

            border: 1px solid #dfe4ea;

            box-shadow: none;

            font-size: 13px;
        }

        .modal-body .form-control:focus {
            border-color: #198754;

            box-shadow:
                0 0 0 3px rgba(25, 135, 84, .10);
        }

        .modal-footer .btn {
            border-radius: 9px;

            padding: 8px 16px;

            font-size: 13px;

            font-weight: 600;
        }

        /* ================= DESKTOP ================= */

        @media (min-width: 1200px) {

            .col-xl-4 {
                width: 33.333333%;
            }

        }

        /* ================= TABLET ================= */

        @media (max-width: 1199px) {

            .dashboard-card {
                height: 80px;
                min-height: 80px;
            }

            .alert-card-body {
                height: 80px;
                min-height: 80px;
            }

        }

        /* ================= MOBILE ================= */

        @media (max-width: 767px) {

            .admin-dashboard {
                padding: 4px 0 15px;
            }

            .dashboard-header {
                margin-bottom: 8px;
            }

            .dashboard-header h3 {
                font-size: 19px;
            }

            .dashboard-header p {
                font-size: 11px;
            }

            .section-title {
                margin: 10px 0 7px;

                font-size: 13px;
            }

            .row.g-4 {
                --bs-gutter-x: 8px;
                --bs-gutter-y: 8px;
            }

            .dashboard-card {
                height: 76px;
                min-height: 76px;

                padding: 10px 12px;

                border-radius: 11px;
            }

            .alert-card-body {
                height: 76px;
                min-height: 76px;

                padding: 10px 12px;
            }

            .card-title {
                font-size: 12px;
            }

            .card-count,
            .alert-card h2 {
                font-size: 20px;
            }

            .card-description {
                font-size: 9px;
            }

            .card-icon-wrapper,
            .alert-icon {
                width: 38px;
                height: 38px;

                border-radius: 10px;
            }

            .card-icon {
                font-size: 17px;
            }

            .alert-icon {
                font-size: 16px;
            }

        }
    </style>

    <div class="admin-dashboard">

        {{-- =====================================================
     HEADER
====================================================== --}}

        <div class="dashboard-header">

            <div>
                <h3>Dashboard</h3>

                <p>
                    Manage orders, barcodes, deliveries and operations
                </p>
            </div>

        </div>


        {{-- =====================================================
     OVERVIEW
====================================================== --}}

        <div class="section-title">
            Overview
        </div>


        <div class="row g-4">

            {{-- UNUSED BARCODES --}}
            <div class="col-xl-4 col-md-6">

                <a href="/barcodes" class="dashboard-card card-green">

                    <div class="card-content">

                        <div class="card-title">
                            Unused Barcodes
                        </div>

                        <div class="card-count">
                            {{ $barcodes->where('is_used', 0)->count() }}
                        </div>

                        <div class="card-description">
                            Available barcodes
                        </div>

                    </div>


                    <div class="card-icon-wrapper">

                        <i class="bi bi-upc-scan card-icon"></i>

                    </div>

                </a>

            </div>


            {{-- TOTAL ORDERS --}}
            <div class="col-xl-4 col-md-6">

                <a href="/orders" class="dashboard-card card-blue">

                    <div class="card-content">

                        <div class="card-title">
                            Total Orders
                        </div>

                        <div class="card-count">
                            {{ $totalOrders }}
                        </div>

                        <div class="card-description">
                            All orders
                        </div>

                    </div>


                    <div class="card-icon-wrapper">

                        <i class="bi bi-cart-check card-icon"></i>

                    </div>

                </a>

            </div>


            {{-- RTO --}}
            <div class="col-xl-4 col-md-6">

                <a href="/rto" class="dashboard-card card-orange">

                    <div class="card-content">

                        <div class="card-title">
                            RTO Find
                        </div>

                        <div class="card-count">
                            View
                        </div>

                        <div class="card-description">
                            Find RTO orders
                        </div>

                    </div>


                    <div class="card-icon-wrapper">

                        <i class="bi bi-arrow-repeat card-icon"></i>

                    </div>

                </a>

            </div>


            {{-- DOWNLOAD BARCODES --}}
            <div class="col-xl-4 col-md-6">

                <div class="dashboard-card card-green action-card" data-bs-toggle="modal" data-bs-target="#barcodeModal">

                    <div class="card-content">

                        <div class="card-title">
                            Download Barcodes
                        </div>

                        <div class="card-description">
                            Export TXT by date
                        </div>

                    </div>


                    <div class="card-icon-wrapper">

                        <i class="bi bi-download card-icon"></i>

                    </div>

                </div>

            </div>


            {{-- DELIVERY STATUS --}}
            <div class="col-xl-4 col-md-6">

                <a href="/delivery" class="dashboard-card card-blue">

                    <div class="card-content">

                        <div class="card-title">
                            Delivery Status Update
                        </div>

                        <div class="card-description">
                            Format Excel India Post bulk tracking
                        </div>

                    </div>


                    <div class="card-icon-wrapper">

                        <i class="bi bi-truck card-icon"></i>

                    </div>

                </a>

            </div>

        </div>


        @if (auth()->user()->role == 'super_admin')
            {{-- =================================================
         OPERATIONS ALERTS
    ================================================== --}}

            <div class="section-title">
                Operations Alerts
            </div>


            <div class="row g-4">

                {{-- 7 DAYS TRANSIT --}}
                <div class="col-xl-4 col-md-6">

                    <a href="{{ route('reports.index', 'transit7') }}" class="text-decoration-none">

                        <div class="alert-card alert-transit">

                            <div class="alert-card-body">

                                <div>

                                    <h6>
                                        7 Days In Transit
                                    </h6>

                                    <h2>
                                        {{ $transit7 }}
                                    </h2>

                                </div>


                                <div class="alert-icon">

                                    <i class="fas fa-barcode"></i>

                                </div>

                            </div>

                        </div>

                    </a>

                </div>


                {{-- 5 DAYS RTO --}}
                <div class="col-xl-4 col-md-6">

                    <a href="{{ route('reports.index', 'rto5') }}" class="text-decoration-none">

                        <div class="alert-card alert-rto">

                            <div class="alert-card-body">

                                <div>

                                    <h6>
                                        5 Days Not Received RTO
                                    </h6>

                                    <h2>
                                        {{ $rto5 }}
                                    </h2>

                                </div>


                                <div class="alert-icon">

                                    <i class="fas fa-undo"></i>

                                </div>

                            </div>

                        </div>

                    </a>

                </div>


                {{-- NOT BOOKED --}}
                <div class="col-xl-4 col-md-6">

                    <a href="{{ route('reports.index', 'not_booked') }}" class="text-decoration-none">

                        <div class="alert-card alert-booking">

                            <div class="alert-card-body">

                                <div>

                                    <h6>
                                        Not Booked India Post
                                    </h6>

                                    <h2>
                                        {{ $ourSidePending }}
                                    </h2>

                                </div>


                                <div class="alert-icon">

                                    <i class="fas fa-barcode"></i>

                                </div>

                            </div>

                        </div>

                    </a>

                </div>

            </div>


            {{-- =================================================
         ADMINISTRATION
    ================================================== --}}

            <div class="section-title">
                Administration
            </div>


            <div class="row g-4">

                {{-- PAYMENTS --}}
                <div class="col-xl-4 col-md-6">

                    <a href="{{ route('payments.index') }}" class="dashboard-card card-blue">

                        <div class="card-content">

                            <div class="card-title">
                                Payments
                            </div>

                            <div class="card-description">
                                Manage payment records
                            </div>

                        </div>


                        <div class="card-icon-wrapper">

                            <i class="bi bi-credit-card card-icon"></i>

                        </div>

                    </a>

                </div>


                {{-- CLIENTS --}}
                <div class="col-xl-4 col-md-6">

                    <a href="/clients" class="dashboard-card card-green">

                        <div class="card-content">

                            <div class="card-title">
                                Clients
                            </div>

                            <div class="card-count">
                                {{ $totalclients }}
                            </div>

                            <div class="card-description">
                                Active clients
                            </div>

                        </div>


                        <div class="card-icon-wrapper">

                            <i class="bi bi-people card-icon"></i>

                        </div>

                    </a>

                </div>


                {{-- DELETE ORDERS --}}
                <div class="col-xl-4 col-md-6">

                    <div class="dashboard-card danger-action action-card" data-bs-toggle="modal"
                        data-bs-target="#deleteOrdersModal">

                        <div class="card-content">

                            <div class="card-title text-danger">
                                Delete Old Orders
                            </div>

                            <div class="card-description text-danger">
                                Permanent action
                            </div>

                        </div>


                        <div class="card-icon-wrapper">

                            <i class="bi bi-trash card-icon"></i>

                        </div>

                    </div>

                </div>


                {{-- AMAZON TO TALLY --}}
                <div class="col-xl-4 col-md-6">

                    <div class="dashboard-card card-orange action-card" data-bs-toggle="modal"
                        data-bs-target="#amazonOrdersModal">

                        <div class="card-content">

                            <div class="card-title">
                                Amazon To Tally
                            </div>

                            <div class="card-description">
                                Format Excel
                            </div>

                        </div>


                        <div class="card-icon-wrapper">

                            <i class="bi bi-file-earmark-spreadsheet card-icon"></i>

                        </div>

                    </div>

                </div>

            </div>
        @endif


    </div>

    {{-- =========================================================
BARCODE MODAL
========================================================= --}}

    <div class="modal fade" id="barcodeModal" tabindex="-1">


        <div class="modal-dialog modal-dialog-centered">

            <form action="{{ route('admin.download.barcodes') }}" method="POST" class="w-100">

                @csrf

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title">

                            <i class="bi bi-download me-2 text-success"></i>

                            Download Barcodes

                        </h5>


                        <button type="button" class="btn-close" data-bs-dismiss="modal">
                        </button>

                    </div>


                    <div class="modal-body">

                        <div class="mb-3">

                            <label>
                                From Date
                            </label>

                            <input type="date" name="from_date" class="form-control" required>

                        </div>


                        <div class="mb-3">

                            <label>
                                To Date
                            </label>

                            <input type="date" name="to_date" class="form-control" required>

                        </div>

                    </div>


                    <div class="modal-footer">

                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">

                            Cancel

                        </button>


                        <button type="submit" class="btn btn-success">

                            <i class="bi bi-download me-1"></i>

                            Download TXT

                        </button>

                    </div>

                </div>

            </form>

        </div>


    </div>

    {{-- =========================================================
DELETE ORDERS MODAL
========================================================= --}}

    <div class="modal fade" id="deleteOrdersModal" tabindex="-1">


        <div class="modal-dialog modal-dialog-centered">

            <form method="POST" action="{{ route('admin.orders.delete') }}" class="w-100">

                @csrf

                @method('DELETE')

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title text-danger">

                            <i class="bi bi-trash me-2"></i>

                            Delete Old Orders

                        </h5>


                        <button type="button" class="btn-close" data-bs-dismiss="modal">
                        </button>

                    </div>


                    <div class="modal-body">

                        <div class="mb-3">

                            <label>
                                From Date
                            </label>

                            <input type="date" name="from_date" class="form-control" required>

                        </div>


                        <div class="mb-3">

                            <label>
                                To Date
                            </label>

                            <input type="date" name="to_date" class="form-control" required>

                        </div>


                        <div class="alert alert-danger d-flex align-items-center gap-2">

                            <i class="bi bi-exclamation-triangle-fill"></i>

                            <div>
                                All Barcodes will be permanently deleted.
                            </div>

                        </div>

                    </div>


                    <div class="modal-footer">

                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">

                            Cancel

                        </button>


                        <button type="submit" class="btn btn-danger"
                            onclick="return confirm('Are you 100% sure? This cannot be undone.')">

                            <i class="bi bi-trash me-1"></i>

                            Confirm Delete

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

    {{-- =========================================================
AMAZON TO TALLY MODAL
========================================================= --}}

    <div class="modal fade" id="amazonOrdersModal" tabindex="-1">


        <div class="modal-dialog modal-dialog-centered">

            <form method="POST" action="{{ url('/amazon-to-tally') }}" enctype="multipart/form-data" class="w-100">

                @csrf

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title">

                            <i class="bi bi-file-earmark-spreadsheet me-2 text-warning"></i>

                            Import Amazon Excel

                        </h5>


                        <button type="button" class="btn-close" data-bs-dismiss="modal">
                        </button>

                    </div>


                    <div class="modal-body">

                        <div class="mb-3">

                            <label>
                                Import Amazon Excel
                            </label>

                            <input type="file" name="excelfile" class="form-control" required>

                        </div>

                    </div>


                    <div class="modal-footer">

                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">

                            Cancel

                        </button>


                        <button type="submit" class="btn btn-warning">

                            <i class="bi bi-arrow-repeat me-1"></i>

                            Convert and Download

                        </button>

                    </div>

                </div>

            </form>

        </div>


    </div>
@endsection
