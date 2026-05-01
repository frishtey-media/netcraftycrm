<!DOCTYPE html>
<html>

<head>
    <title>@yield('title')</title>

    <!-- ✅ MOBILE FIX -->
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- jQuery FIRST -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <style>
        body {
            background: #f4f6f9;
            overflow-x: hidden;
        }

        /* SIDEBAR */
        .sidebar {
            width: 240px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: #1f2d3d;
            padding: 20px;
            z-index: 1001;
            transition: 0.3s;
        }

        /* MENU */
        .menu-link {
            display: block;
            padding: 12px;
            color: #cfd8dc;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 6px;
            transition: 0.3s;
        }

        .menu-link:hover {
            background: #3c8dbc;
            color: white;
        }

        .menu-link.active {
            background: #0d6efd;
            color: white;
        }

        /* MAIN */
        .main {
            margin-left: 240px;
            transition: 0.3s;
        }

        /* TOPBAR */
        .topbar {
            background: white;
            padding: 15px;
            border-bottom: 1px solid #ddd;
        }

        /* 🔥 OVERLAY */
        #overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 1000;
        }

        #overlay.active {
            display: block;
        }

        /* MOBILE */
        @media (max-width: 768px) {

            .sidebar {
                left: -260px;
            }

            .sidebar.active {
                left: 0;
            }

            .main {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">

        <!-- USER -->
        <div class="text-center mb-4 text-white">
            <div class="bg-light text-dark rounded-circle mx-auto mb-2"
                style="width:50px;height:50px;display:flex;align-items:center;justify-content:center;font-weight:bold;">
                {{ strtoupper(substr(Auth::guard('calling_user')->user()->name, 0, 1)) }}
            </div>

            <div class="fw-bold">
                {{ Auth::guard('calling_user')->user()->name }}
            </div>
            <small>Netcrafty Calling Executive</small>
        </div>

        <!-- MENU -->
        <a href="{{ route('calling.dashboard') }}" class="menu-link">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <a href="{{ route('calling.orders') }}" class="menu-link">
            <i class="bi bi-list-check"></i> My Orders
        </a>

        <a href="{{ route('calling.verified') }}" class="menu-link">
            <i class="bi bi-check-circle"></i> Verified
        </a>

        <a href="{{ route('calling.not.reachable') }}" class="menu-link">
            <i class="bi bi-telephone-x"></i> Not Reachable
        </a>

        <a href="{{ route('calling.inbox') }}" class="menu-link">
            <i class="bi bi-whatsapp text-success"></i> Whatsapp Inbox
        </a>

        <a href="{{ route('calling.manual') }}" class="menu-link">
            <i class="bi bi-whatsapp text-success"></i> Add WhatsApp Order
        </a>

        <a href="{{ route('calling.whatsapp') }}" class="menu-link">
            <i class="bi bi-whatsapp text-success"></i> WhatsApp Orders
        </a>

        <!-- LOGOUT -->
        <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
            class="menu-link text-danger mt-3">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>

        <form id="logout-form" action="{{ route('calling.logout') }}" method="POST" style="display:none;">
            @csrf
        </form>
    </div>

    <!-- 🔥 OVERLAY -->
    <div id="overlay"></div>

    <!-- MAIN -->
    <div class="main">

        <!-- TOPBAR -->
        <div class="topbar d-flex justify-content-between align-items-center">

            <!-- MENU BUTTON -->
            <button class="btn btn-dark d-md-none" onclick="toggleSidebar()">
                ☰
            </button>

            <h5 class="mb-0">@yield('title')</h5>
            <span class="badge bg-{{ $statusClass ?? 'danger' }}">
                {{ $statusLabel ?? 'Orders' }}: {{ $statusCount ?? 0 }}
            </span>

        </div>

        <!-- CONTENT -->
        <div class="p-3">
            @yield('content')
        </div>

    </div>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }

        // 🔥 Click outside close
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });

        // 🔥 Menu click close (mobile)
        document.querySelectorAll('.menu-link').forEach(link => {
            link.addEventListener('click', () => {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        });
    </script>
    @stack('scripts')
</body>

</html>
