<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>@yield('title', 'Dashboard')</title>

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>


    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100%;
        }

        body {
            background: #f4f6f9;
            font-family: "Segoe UI", Arial, sans-serif;
            overflow-x: hidden;
        }


        /* =====================================================
           SIDEBAR
        ===================================================== */

        .sidebar {

            width: 245px;
            height: 100vh;

            position: fixed;

            top: 0;
            left: 0;

            background: #1f2d3d;

            z-index: 1100;

            padding: 20px 14px;

            overflow-y: auto;
            overflow-x: hidden;

            transition: transform .3s ease;

            box-shadow: 3px 0 15px rgba(0, 0, 0, .08);
        }


        /* Sidebar scrollbar */

        .sidebar::-webkit-scrollbar {
            width: 5px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, .2);
            border-radius: 10px;
        }


        /* =====================================================
           USER PROFILE
        ===================================================== */

        .user-profile {

            text-align: center;

            color: white;

            padding: 5px 5px 20px;

            margin-bottom: 8px;

            border-bottom: 1px solid rgba(255, 255, 255, .08);
        }


        .user-avatar {

            width: 58px;
            height: 58px;

            border-radius: 50%;

            background: #ffffff;

            color: #1f2d3d;

            display: flex;

            align-items: center;
            justify-content: center;

            margin: 0 auto 10px;

            font-size: 20px;

            font-weight: 700;

            box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
        }


        .user-name {

            font-size: 16px;

            font-weight: 700;

            color: #fff;

            margin-bottom: 2px;
        }


        .user-role {

            font-size: 12px;

            color: #cfd8dc;
        }


        /* =====================================================
           MENU
        ===================================================== */

        .menu-section {

            margin-top: 8px;

            margin-bottom: 5px;

            padding-left: 12px;

            font-size: 10px;

            font-weight: 700;

            color: #7f8c98;

            text-transform: uppercase;

            letter-spacing: .8px;
        }


        .menu-link {

            display: flex;

            align-items: center;

            gap: 12px;

            width: 100%;

            min-height: 46px;

            padding: 11px 13px;

            color: #d7dee4;

            text-decoration: none;

            border-radius: 9px;

            margin-bottom: 4px;

            font-size: 14px;

            transition: all .2s ease;

            white-space: nowrap;
        }


        .menu-link i {

            width: 20px;

            min-width: 20px;

            text-align: center;

            font-size: 16px;
        }


        .menu-link:hover {

            background: rgba(60, 141, 188, .25);

            color: #fff;

            transform: translateX(2px);
        }


        .menu-link.active {

            background: #0d6efd;

            color: #fff;

            box-shadow: 0 4px 10px rgba(13, 110, 253, .25);
        }


        .menu-link.logout {

            margin-top: 15px;

            color: #ff4d5a;
        }


        .menu-link.logout:hover {

            background: rgba(220, 53, 69, .15);

            color: #ff6b75;
        }


        /* =====================================================
           MAIN
        ===================================================== */

        .main {

            margin-left: 245px;

            min-height: 100vh;

            width: calc(100% - 245px);

            transition: all .3s ease;
        }


        /* =====================================================
           TOPBAR
        ===================================================== */

        .topbar {

            height: 62px;

            background: #fff;

            border-bottom: 1px solid #e5e7eb;

            padding: 0 20px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            position: sticky;

            top: 0;

            z-index: 900;

            box-shadow: 0 2px 8px rgba(0, 0, 0, .03);
        }


        .page-title {

            font-size: 18px;

            font-weight: 600;

            color: #202938;

            margin: 0;
        }


        .mobile-menu-btn {

            width: 42px;

            height: 42px;

            border: 0;

            border-radius: 9px;

            background: #1f2d3d;

            color: #fff;

            display: none;

            align-items: center;

            justify-content: center;

            font-size: 20px;
        }


        .top-status {

            font-size: 11px;

            padding: 6px 10px;

            border-radius: 20px;
        }


        /* =====================================================
           CONTENT
        ===================================================== */

        .content-wrapper {

            padding: 20px;
        }


        /* Prevent wide children from breaking mobile */

        .content-wrapper>* {

            max-width: 100%;
        }


        /* =====================================================
           OVERLAY
        ===================================================== */

        #overlay {

            position: fixed;

            inset: 0;

            background: rgba(0, 0, 0, .5);

            z-index: 1050;

            display: none;

            opacity: 0;

            transition: opacity .25s ease;
        }


        #overlay.active {

            display: block;

            opacity: 1;
        }


        /* =====================================================
           MOBILE
        ===================================================== */

        @media (max-width: 991px) {

            .sidebar {

                width: 260px;

                transform: translateX(-100%);

                left: 0;
            }


            .sidebar.active {

                transform: translateX(0);
            }


            .main {

                margin-left: 0;

                width: 100%;
            }


            .mobile-menu-btn {

                display: flex;
            }


            .topbar {

                height: 58px;

                padding: 0 14px;

                gap: 10px;
            }


            .page-title {

                font-size: 16px;

                flex: 1;

                margin-left: 8px;
            }


            .content-wrapper {

                padding: 12px;
            }
        }


        /* =====================================================
           SMALL MOBILE
        ===================================================== */

        @media (max-width: 575px) {

            .topbar {

                height: 56px;

                padding: 0 10px;
            }


            .page-title {

                font-size: 15px;
            }


            .top-status {

                display: none;
            }


            .content-wrapper {

                padding: 10px;
            }


            .mobile-menu-btn {

                width: 40px;

                height: 40px;
            }


            /* Bootstrap rows fix */

            .row {

                margin-left: 0;

                margin-right: 0;
            }


            .row>* {

                padding-left: 6px;

                padding-right: 6px;
            }


            /* Cards */

            .card {

                max-width: 100%;

                overflow: hidden;
            }


            /* Tables */

            .table-responsive {

                width: 100%;

                overflow-x: auto;

                -webkit-overflow-scrolling: touch;
            }


            table {

                white-space: nowrap;
            }
        }


        /* =====================================================
           EXTRA SMALL
        ===================================================== */

        @media (max-width: 380px) {

            .sidebar {

                width: 250px;
            }


            .menu-link {

                font-size: 13px;

                min-height: 44px;

                padding: 10px 11px;
            }


            .user-avatar {

                width: 52px;

                height: 52px;
            }


            .content-wrapper {

                padding: 8px;
            }
        }


        /* =====================================================
           FORM / INPUT MOBILE FIX
        ===================================================== */

        input,
        select,
        textarea,
        button {

            max-width: 100%;
        }


        /* =====================================================
           CHART RESPONSIVE
        ===================================================== */

        canvas {

            max-width: 100% !important;
        }


        /* =====================================================
           DATATABLE RESPONSIVE
        ===================================================== */

        .dataTables_wrapper {

            width: 100%;

            overflow-x: auto;
        }


        /* =====================================================
           NICE SCROLLBAR MAIN
        ===================================================== */

        body::-webkit-scrollbar {

            width: 7px;
        }


        body::-webkit-scrollbar-thumb {

            background: #cbd5e1;

            border-radius: 10px;
        }
    </style>

</head>


<body>


    <!-- =====================================================
         SIDEBAR
    ====================================================== -->

    <aside class="sidebar" id="sidebar">


        <!-- USER -->

        <div class="user-profile">

            <div class="user-avatar">

                {{ strtoupper(substr(Auth::guard('calling_user')->user()->name ?? 'U', 0, 1)) }}

            </div>


            <div class="user-name">

                {{ Auth::guard('calling_user')->user()->name ?? 'User' }}

            </div>


            <div class="user-role">

                Netcrafty Calling Executive

            </div>

        </div>


        <!-- MENU -->

        <div class="menu-section">
            Main
        </div>


        <a href="{{ route('calling.dashboard') }}"
            class="menu-link {{ request()->routeIs('calling.dashboard') ? 'active' : '' }}">

            <i class="bi bi-speedometer2"></i>

            <span>Dashboard</span>

        </a>


        <a href="{{ route('calling.orders') }}"
            class="menu-link {{ request()->routeIs('calling.orders') ? 'active' : '' }}">

            <i class="bi bi-list-check"></i>

            <span>My Orders</span>

        </a>


        <div class="menu-section">
            Orders
        </div>


        <a href="{{ route('calling.rtoorders') }}"
            class="menu-link {{ request()->routeIs('calling.rtoorders') ? 'active' : '' }}">

            <i class="bi bi-arrow-return-left"></i>

            <span>RTO Orders</span>

        </a>


        <a href="{{ route('calling.deliverorders') }}"
            class="menu-link {{ request()->routeIs('calling.deliverorders') ? 'active' : '' }}">

            <i class="bi bi-box-seam"></i>

            <span>Deliver Orders</span>

        </a>


        <a href="{{ route('calling.abandoned') }}"
            class="menu-link {{ request()->routeIs('calling.abandoned') ? 'active' : '' }}">

            <i class="bi bi-cart-x"></i>

            <span>Abandoned Orders</span>

        </a>


        <div class="menu-section">
            Calling Status
        </div>


        <a href="{{ route('calling.verified') }}"
            class="menu-link {{ request()->routeIs('calling.verified') ? 'active' : '' }}">

            <i class="bi bi-check-circle"></i>

            <span>Verified</span>

        </a>


        <a href="{{ route('calling.same_order') }}"
            class="menu-link {{ request()->routeIs('calling.same_order') ? 'active' : '' }}">

            <i class="bi bi-arrow-repeat"></i>

            <span>Same Order</span>

        </a>


        <a href="{{ route('calling.cancel') }}"
            class="menu-link {{ request()->routeIs('calling.cancel') ? 'active' : '' }}">

            <i class="bi bi-x-circle"></i>

            <span>Cancel Order</span>

        </a>


        <a href="{{ route('calling.not.reachable') }}"
            class="menu-link {{ request()->routeIs('calling.not.reachable') ? 'active' : '' }}">

            <i class="bi bi-telephone-x"></i>

            <span>Not Reachable</span>

        </a>


        <div class="menu-section">
            WhatsApp
        </div>


        <a href="{{ route('calling.manual') }}"
            class="menu-link {{ request()->routeIs('calling.manual') ? 'active' : '' }}">

            <i class="bi bi-whatsapp text-success"></i>

            <span>Add WhatsApp Order</span>

        </a>


        <a href="{{ route('calling.whatsapp') }}"
            class="menu-link {{ request()->routeIs('calling.whatsapp') ? 'active' : '' }}">

            <i class="bi bi-whatsapp text-success"></i>

            <span>WhatsApp Orders</span>

        </a>


        <!-- LOGOUT -->

        <a href="#" class="menu-link logout"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">

            <i class="bi bi-box-arrow-right"></i>

            <span>Logout</span>

        </a>


        <form id="logout-form" action="{{ route('calling.logout') }}" method="POST" style="display:none;">

            @csrf

        </form>


    </aside>


    <!-- =====================================================
         OVERLAY
    ====================================================== -->

    <div id="overlay"></div>


    <!-- =====================================================
         MAIN
    ====================================================== -->

    <main class="main">


        <!-- TOPBAR -->

        <header class="topbar">


            <!-- MOBILE BUTTON -->

            <button type="button" class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Open Menu">

                <i class="bi bi-list"></i>

            </button>


            <!-- PAGE TITLE -->

            <h5 class="page-title">

                @yield('title', 'Dashboard')

            </h5>


            <!-- STATUS -->

            <span class="badge bg-{{ $statusClass ?? 'danger' }} top-status">

                {{ $statusLabel ?? 'Orders' }}:

                {{ $statusCount ?? 0 }}

            </span>


        </header>


        <!-- CONTENT -->

        <div class="content-wrapper">

            @yield('content')

        </div>


    </main>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


    <!-- =====================================================
         SIDEBAR JS
    ====================================================== -->

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const sidebar = document.getElementById("sidebar");

            const overlay = document.getElementById("overlay");

            const menuBtn = document.getElementById("mobileMenuBtn");


            /*
             * OPEN SIDEBAR
             */

            function openSidebar() {

                sidebar.classList.add("active");

                overlay.classList.add("active");

                document.body.style.overflow = "hidden";
            }


            /*
             * CLOSE SIDEBAR
             */

            function closeSidebar() {

                sidebar.classList.remove("active");

                overlay.classList.remove("active");

                document.body.style.overflow = "";
            }


            /*
             * MENU BUTTON
             */

            if (menuBtn) {

                menuBtn.addEventListener("click", function() {

                    if (sidebar.classList.contains("active")) {

                        closeSidebar();

                    } else {

                        openSidebar();

                    }

                });

            }


            /*
             * OVERLAY CLICK
             */

            overlay.addEventListener("click", function() {

                closeSidebar();

            });


            /*
             * MENU CLICK ON MOBILE
             */

            document.querySelectorAll(".menu-link").forEach(function(link) {

                link.addEventListener("click", function() {

                    if (window.innerWidth <= 991) {

                        closeSidebar();

                    }

                });

            });


            /*
             * ESC KEY
             */

            document.addEventListener("keydown", function(event) {

                if (event.key === "Escape") {

                    closeSidebar();

                }

            });


            /*
             * RESIZE FIX
             */

            window.addEventListener("resize", function() {

                if (window.innerWidth > 991) {

                    closeSidebar();

                }

            });

        });
    </script>


    @stack('scripts')


</body>

</html>
