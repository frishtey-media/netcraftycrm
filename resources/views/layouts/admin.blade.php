<!DOCTYPE html>
<html lang="en">

<head>
    <title>Netcrafty CRM</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- jQuery & DataTables -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <style>
        body {
            background: #f4f6f9;
            font-size: 14px;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            min-height: 100vh;
            background: linear-gradient(180deg, #111827, #1f2937);
            padding: 15px;
        }

        .sidebar img {
            width: 100%;
            margin-bottom: 20px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar a:hover {
            background: #ef4444;
            color: #fff;
            transform: translateX(6px);
        }

        /* TOP NAVBAR */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 20px;
        }

        .topbar .brand {
            font-weight: 700;
            font-size: 18px;
        }

        /* MAIN CONTENT */
        .content-area {
            padding: 25px;
        }

        /* BUTTON */
        .btn-logout {
            background: #ef4444;
            border: none;
        }

        .btn-logout:hover {
            background: #dc2626;
        }
    </style>
</head>

<body>

    <div class="d-flex">

        <!-- SIDEBAR -->
        <div class="sidebar text-white">
            <img src="/images/netc2.png" alt="Netcrafty">

            <a href="{{ route('dashboard') }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <a href="{{ route('record.create') }}">
                <i class="bi bi-upload"></i> Import Records
            </a>

            <a href="{{ route('orders.list') }}">
                <i class="bi bi-truck"></i> Tracking Records
            </a>

            <a href="{{ route('labelsenders') }}">
                <i class="bi bi-person-lines-fill"></i> Senders
            </a>

            <a href="{{ route('clients.index') }}">
                <i class="bi bi-people"></i> Clients
            </a>

            <a href="{{ route('client.products') }}">
                <i class="bi bi-box-seam"></i> Products
            </a>

            <a href="{{ route('barcodes') }}">
                <i class="bi bi-upc-scan"></i> Barcodes
            </a>

            <a href="{{ route('shopify.import.page') }}">
                <i class="bi bi-shop"></i> Shopify Orders
            </a>

            <a href="{{ route('labels.index') }}">
                <i class="bi bi-printer"></i> Print Labels
            </a>

            <a href="{{ route('Invoice.index') }}">
                <i class="bi bi-file-earmark-text"></i> Download Invoice
            </a>
        </div>

        <!-- MAIN -->
        <div class="flex-grow-1">

            <!-- TOPBAR -->
            <div class="topbar d-flex justify-content-between align-items-center">
                <span class="brand">Netcrafty CRM</span>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-logout btn-sm text-white">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </div>

            <!-- PAGE CONTENT -->
            <div class="content-area">
                @yield('content')
            </div>

        </div>
    </div>

</body>

</html>
