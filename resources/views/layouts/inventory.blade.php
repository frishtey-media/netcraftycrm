<!DOCTYPE html>
<html>

<head>
    <title>@yield('title', 'Inventory')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            overflow-x: hidden;
            background-color: #f4f6f9;
        }

        .sidebar {
            height: 100vh;
            background: linear-gradient(180deg, #0f2027, #203a43, #2c5364);
            color: white;
            padding-top: 20px;
            position: fixed;
            width: 250px;
        }

        .sidebar a {
            color: #cfd8dc;
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            border-radius: 8px;
            margin: 5px 10px;
        }

        .sidebar a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .main-content {
            margin-left: 250px;
        }

        .topbar {
            background: white;
            padding: 15px 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .dashboard-card {
            background-color: #b7d4c7;
            border-radius: 20px;
            padding: 25px;
            transition: 0.3s;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
    </style>

    @stack('styles')
</head>

<body>

    <!-- Sidebar -->
    <div class="sidebar">


        <p class="px-3 text-light small">INVENTORY</p>
        <a href="{{ route('inventory.dashboard') }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="{{ route('warehouses.index') }}">
            <i class="bi bi-building"></i> Warehouses
        </a>
        <a href="{{ route('categories.index') }}">
            <i class="bi bi-tags"></i> Clients
        </a>
        <a href="{{ route('products.index') }}">
            <i class="bi bi-box"></i> Products
        </a>
        <!--<a href="{{ route('stocks.index') }}">
            <i class="bi bi-box"></i> Stock
        </a>-->

        <!--<a href="{{ route('purchases.index') }}">
            <i class="bi bi-cart-plus"></i> Purchases
        </a>-->

        <a href="{{ route('sales.index') }}">
            <i class="bi bi-cash-stack"></i> Sales
        </a>

        <hr class="text-light">

        <p class="px-3 text-light small">REPORTS</p>

        <a href="{{ route('sales.report') }}">
            <i class="bi bi-bar-chart"></i> Sale Report
        </a>
        <a href="{{ route('products.report') }}">
            <i class="bi bi-bar-chart"></i> Inventory Report
        </a>
        <a href="{{ route('rto.report') }}">
            <i class="bi bi-bar-chart"></i> RTO Report
        </a>
        <form method="POST" action="{{ route('inventory.logout') }}" class="mt-4 px-3">
            @csrf
            <button class="btn btn-danger w-100">Logout</button>
        </form>
    </div>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Topbar -->
        <div class="topbar d-flex justify-content-between align-items-center">
            <h4 class="mb-0">@yield('page-title')</h4>
        </div>

        <!-- Page Content -->
        <div class="p-4">
            @yield('content')
        </div>

    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


    @yield('scripts')
</body>

</html>
