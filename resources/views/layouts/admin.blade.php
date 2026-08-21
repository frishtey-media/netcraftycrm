<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Netcrafty CRM</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <style>
        :root {
            --sidebar-width: 270px;
            --sidebar-bg: #111827;
            --sidebar-hover: #1f2937;
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --danger: #ef4444;
            --body-bg: #f5f7fb;
            --text-dark: #111827;
            --text-muted: #6b7280;
            --border: #e5e7eb;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        body {
            background: var(--body-bg);
            color: var(--text-dark);
            font-size: 14px;
            font-family:
                Inter,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Arial,
                sans-serif;
            overflow-x: hidden;
        }

        /* =========================================
           SIDEBAR
        ========================================= */

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1100;

            background:
                radial-gradient(circle at top left,
                    rgba(99, 102, 241, 0.16),
                    transparent 35%),
                linear-gradient(180deg, #0f172a 0%, #111827 100%);

            border-right: 1px solid rgba(255, 255, 255, 0.06);

            padding: 18px 14px;

            display: flex;
            flex-direction: column;

            overflow-y: auto;

            transition: transform .3s ease;
        }

        /* Custom scrollbar */

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: #374151;
            border-radius: 10px;
        }

        /* LOGO */

        .logo-area {
            padding: 5px 8px 20px;
            margin-bottom: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, .07);
        }

        .logo-area img {
            width: 100%;
            max-width: 205px;
            height: auto;
            object-fit: contain;
        }

        /* MENU LABEL */

        .menu-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;

            padding: 15px 12px 8px;
        }

        /* SIDEBAR LINKS */

        .sidebar a,
        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 11px;

            width: 100%;

            padding: 11px 13px;
            margin-bottom: 4px;

            color: #cbd5e1;
            text-decoration: none;

            border-radius: 10px;

            font-size: 13.5px;
            font-weight: 500;

            cursor: pointer;

            transition:
                background .2s ease,
                color .2s ease,
                transform .2s ease;
        }

        .sidebar a i,
        .sidebar-link i:first-child {
            width: 20px;
            min-width: 20px;
            font-size: 17px;
            text-align: center;
        }

        .sidebar a:hover,
        .sidebar-link:hover {
            background: rgba(255, 255, 255, .07);
            color: #fff;
        }

        .sidebar a:hover {
            transform: translateX(3px);
        }

        /* ACTIVE */

        .sidebar a.active {
            background: linear-gradient(135deg,
                    var(--primary),
                    var(--primary-dark));

            color: #fff;

            box-shadow:
                0 7px 18px rgba(79, 70, 229, .25);
        }

        /* DROPDOWN */

        .sidebar-dropdown {
            margin-bottom: 4px;
        }

        .sidebar-link .arrow {
            margin-left: auto;
            width: auto;
            min-width: auto;
            font-size: 12px;

            transition: transform .25s ease;
        }

        .sidebar-dropdown.open .arrow {
            transform: rotate(180deg);
        }

        .sidebar-submenu {
            display: none;

            margin: 2px 0 7px 17px;
            padding-left: 10px;

            border-left: 1px solid rgba(255, 255, 255, .08);
        }

        .sidebar-dropdown.open .sidebar-submenu {
            display: block;
            animation: submenu .2s ease;
        }

        @keyframes submenu {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .sidebar-submenu a {
            padding: 9px 10px;
            margin-bottom: 2px;

            font-size: 12.5px;
            color: #94a3b8;
        }

        .sidebar-submenu a i {
            font-size: 14px;
        }

        .sidebar-submenu a:hover {
            color: #fff;
            background: rgba(255, 255, 255, .06);
            transform: translateX(2px);
        }

        /* =========================================
           MAIN AREA
        ========================================= */

        .main-wrapper {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left .3s ease;
        }

        /* =========================================
           TOPBAR
        ========================================= */

        .topbar {
            height: 70px;

            position: sticky;
            top: 0;
            z-index: 1000;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 0 25px;

            background: rgba(255, 255, 255, .92);

            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);

            border-bottom: 1px solid var(--border);

            box-shadow: 0 2px 12px rgba(15, 23, 42, .04);
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .mobile-menu-btn {
            display: none;

            width: 40px;
            height: 40px;

            border: 1px solid var(--border);
            background: #fff;

            border-radius: 10px;

            font-size: 20px;
            color: #374151;
        }

        .page-title {
            font-size: 17px;
            font-weight: 700;
            color: #111827;
        }

        .page-subtitle {
            font-size: 11px;
            color: #9ca3af;
            margin-top: 1px;
        }

        /* USER AREA */

        .user-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-info {
            text-align: right;
            line-height: 1.2;
        }

        .user-name {
            font-size: 13px;
            font-weight: 700;
            color: #111827;
        }

        .user-role {
            font-size: 10px;
            color: #9ca3af;
            text-transform: uppercase;
        }

        .user-avatar {
            width: 39px;
            height: 39px;

            border-radius: 12px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: linear-gradient(135deg,
                    #6366f1,
                    #8b5cf6);

            color: #fff;

            font-size: 15px;
            font-weight: 700;

            box-shadow: 0 5px 15px rgba(99, 102, 241, .25);
        }

        .logout-btn {
            width: 38px;
            height: 38px;

            display: flex;
            align-items: center;
            justify-content: center;

            border: 1px solid #fee2e2;

            background: #fff5f5;
            color: #ef4444;

            border-radius: 10px;

            transition: all .2s ease;
        }

        .logout-btn:hover {
            background: #ef4444;
            color: #fff;
            border-color: #ef4444;
        }

        /* =========================================
           CONTENT
        ========================================= */

        .content-area {
            padding: 25px;
            min-height: calc(100vh - 70px);
        }

        /* =========================================
           MOBILE OVERLAY
        ========================================= */

        .sidebar-overlay {
            display: none;

            position: fixed;
            inset: 0;

            background: rgba(15, 23, 42, .55);

            z-index: 1050;

            backdrop-filter: blur(2px);
        }

        /* =========================================
           RESPONSIVE
        ========================================= */

        @media (max-width: 991px) {

            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
                box-shadow: 10px 0 35px rgba(0, 0, 0, .2);
            }

            .sidebar-overlay.show {
                display: block;
            }

            .main-wrapper {
                margin-left: 0;
            }

            .mobile-menu-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .topbar {
                padding: 0 18px;
            }

            .content-area {
                padding: 20px;
            }
        }

        @media (max-width: 576px) {

            .topbar {
                height: 62px;
                padding: 0 12px;
            }

            .content-area {
                padding: 14px;
            }

            .page-title {
                font-size: 15px;
            }

            .page-subtitle {
                display: none;
            }

            .user-info {
                display: none;
            }

            .user-avatar {
                width: 36px;
                height: 36px;
                border-radius: 10px;
            }

            .logout-btn {
                width: 35px;
                height: 35px;
            }

            .topbar-left {
                gap: 9px;
            }
        }

        /* =========================================
           GENERAL UI IMPROVEMENTS
        ========================================= */

        .card {
            border: 1px solid var(--border);
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(15, 23, 42, .04);
        }

        .btn {
            border-radius: 9px;
        }

        .table-responsive {
            border-radius: 12px;
        }
    </style>
</head>


<body>

    <!-- MOBILE OVERLAY -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>


    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">

        <!-- LOGO -->
        <div class="logo-area">
            <img src="/images/netc2.png" alt="Netcrafty">
        </div>


        <!-- MAIN -->
        <div class="menu-label">Main Menu</div>


        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>


        @if (auth()->user()->role == 'super_admin')
            <div class="menu-label">Administration</div>

            <a href="{{ route('client_staff_form') }}">
                <i class="bi bi-diagram-3"></i>
                <span>Assign Staff Mapping</span>
            </a>

            <a href="{{ route('calling.users') }}">
                <i class="bi bi-headset"></i>
                <span>Calling Staff</span>
            </a>

            <a href="{{ route('labelsenders') }}">
                <i class="bi bi-person-lines-fill"></i>
                <span>Senders</span>
            </a>

            <a href="{{ route('clients.index') }}">
                <i class="bi bi-buildings"></i>
                <span>Clients</span>
            </a>

            <a href="{{ route('client.products') }}">
                <i class="bi bi-box-seam"></i>
                <span>Products</span>
            </a>

            <a href="{{ route('Invoice.index') }}">
                <i class="bi bi-receipt"></i>
                <span>Bulk Invoice Download</span>
            </a>

            <a href="{{ route('users.index') }}">
                <i class="bi bi-people"></i>
                <span>Client Users</span>
            </a>
        @endif


        <div class="menu-label">Orders</div>


        <a href="{{ route('clientsorders') }}">
            <i class="bi bi-speedometer2"></i>
            <span>Orders Dashboard</span>
        </a>


        <a href="{{ route('record.create') }}">
            <i class="bi bi-cloud-arrow-up"></i>
            <span>Import Records</span>
        </a>


        <a href="{{ route('barcodes') }}">
            <i class="bi bi-upc-scan"></i>
            <span>Barcodes</span>
        </a>


        <!-- REPORTS -->

        <div class="menu-label">Analytics</div>

        <div class="sidebar-dropdown">

            <div class="sidebar-link">
                <i class="bi bi-bar-chart-line"></i>

                <span>Reports</span>

                <i class="bi bi-chevron-down arrow"></i>
            </div>


            <div class="sidebar-submenu">

                <a href="{{ route('orders.list') }}">
                    <i class="bi bi-truck"></i>
                    <span>Orders Reports</span>
                </a>


                <a href="/rto">
                    <i class="bi bi-arrow-return-left"></i>
                    <span>RTO Reports</span>
                </a>


                <a href="{{ route('performance.dashboard') }}">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Staff Performance</span>
                </a>


                @if (auth()->user()->role == 'super_admin')
                    <a href="{{ route('delivered.index') }}">
                        <i class="bi bi-check2-circle"></i>
                        <span>Delivery Reports</span>
                    </a>


                    <a href="/payments">
                        <i class="bi bi-credit-card"></i>
                        <span>Payment Reports</span>
                    </a>


                    <a href="{{ route('performance.dashboard') }}">
                        <i class="bi bi-person-check"></i>
                        <span>Customer Repeat Delivery</span>
                    </a>


                    <a href="{{ route('reports.repeat.rto') }}">
                        <i class="bi bi-arrow-repeat"></i>
                        <span>Customer Repeat RTO</span>
                    </a>


                    <a href="https://crm.netcrafty.com/inventory/login">
                        <i class="bi bi-boxes"></i>
                        <span>Inventory Login</span>
                    </a>
                @endif

            </div>

        </div>


        <!-- LABELS -->

        <div class="menu-label">Labels</div>


        <a href="{{ route('shopify.import.page') }}">
            <i class="bi bi-shop"></i>
            <span>Bulk Label Import</span>
        </a>


        <a href="{{ route('labels.index') }}">
            <i class="bi bi-printer"></i>
            <span>Print Labels</span>
        </a>


    </aside>


    <!-- MAIN WRAPPER -->

    <div class="main-wrapper">


        <!-- TOPBAR -->

        <header class="topbar">

            <div class="topbar-left">

                <button type="button" class="mobile-menu-btn" id="mobileMenuBtn">

                    <i class="bi bi-list"></i>

                </button>


                <div>

                    <div class="page-title">
                        Netcrafty CRM
                    </div>

                    <div class="page-subtitle">
                        Management Dashboard
                    </div>

                </div>

            </div>


            <!-- USER -->

            <div class="user-area">

                <div class="user-info">

                    <div class="user-name">

                        @if (auth()->check() && auth()->user()->role == 'client')
                            {{ auth()->user()->client->client_name ?? auth()->user()->name }}
                        @else
                            Super Admin
                        @endif

                    </div>

                    <div class="user-role">
                        {{ auth()->user()->role ?? 'Admin' }}
                    </div>

                </div>


                <div class="user-avatar">

                    @if (auth()->check())
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    @else
                        A
                    @endif

                </div>


                <form method="POST" action="{{ route('logout') }}">

                    @csrf

                    <button type="submit" class="logout-btn" title="Logout">

                        <i class="bi bi-box-arrow-right"></i>

                    </button>

                </form>

            </div>

        </header>


        <!-- CONTENT -->

        <main class="content-area">

            @yield('content')

        </main>


    </div>


    <!-- JAVASCRIPT -->

    <script>
        $(document).ready(function() {

            /*
            |--------------------------------------------------------------------------
            | REPORTS DROPDOWN
            |--------------------------------------------------------------------------
            */

            $('.sidebar-link').on('click', function() {

                $(this)
                    .parent('.sidebar-dropdown')
                    .toggleClass('open');

            });


            /*
            |--------------------------------------------------------------------------
            | MOBILE SIDEBAR
            |--------------------------------------------------------------------------
            */

            const sidebar = $('#sidebar');
            const overlay = $('#sidebarOverlay');
            const menuBtn = $('#mobileMenuBtn');


            function openSidebar() {

                sidebar.addClass('show');
                overlay.addClass('show');

                $('body').css('overflow', 'hidden');

            }


            function closeSidebar() {

                sidebar.removeClass('show');
                overlay.removeClass('show');

                $('body').css('overflow', '');

            }


            menuBtn.on('click', function() {

                if (sidebar.hasClass('show')) {

                    closeSidebar();

                } else {

                    openSidebar();

                }

            });


            overlay.on('click', function() {

                closeSidebar();

            });


            /*
            |--------------------------------------------------------------------------
            | CLOSE SIDEBAR AFTER CLICKING MOBILE LINK
            |--------------------------------------------------------------------------
            */

            $('.sidebar a').on('click', function() {

                if (window.innerWidth <= 991) {

                    closeSidebar();

                }

            });


            /*
            |--------------------------------------------------------------------------
            | ESC KEY
            |--------------------------------------------------------------------------
            */

            $(document).on('keydown', function(e) {

                if (e.key === 'Escape') {

                    closeSidebar();

                }

            });


            /*
            |--------------------------------------------------------------------------
            | RESET ON DESKTOP
            |--------------------------------------------------------------------------
            */

            $(window).on('resize', function() {

                if (window.innerWidth > 991) {

                    closeSidebar();

                }

            });

        });
    </script>


</body>

</html>
