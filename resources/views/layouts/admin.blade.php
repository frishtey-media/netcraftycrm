<!DOCTYPE html>
<html>

<head>
    <title>Netcrafty CRM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/datetime/1.4.1/css/dataTables.dateTime.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/datetime/1.4.1/js/dataTables.dateTime.min.js"></script>
    <script>
        $(document).ready(function() {

            var minDate, maxDate;

            minDate = new DateTime($('#min'), {
                format: 'YYYY-MM-DD'
            });
            maxDate = new DateTime($('#max'), {
                format: 'YYYY-MM-DD'
            });


            var table = $('#ordersTable').DataTable({
                order: [
                    [7, 'desc']
                ], // default sort by Date column (index 7)
            });

            // Date range filtering
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    var date = data[7]; // Date column index
                    var min = minDate.val();
                    var max = maxDate.val();

                    if (
                        (min === null && max === null) ||
                        (min === null && date <= max) ||
                        (min <= date && max === null) ||
                        (min <= date && date <= max)
                    ) {
                        return true;
                    }
                    return false;
                }
            );

            // Event listener to redraw on date change
            $('#min, #max').on('change', function() {
                table.draw();
            });
        });
    </script>
    <style>
        .p-3 {
            width: 250px;
            height: 320vh;
            padding: 0px !important;
            margin: 0;
        }

        .mb-2 {
            margin-bottom: 1px !important;
            padding: 10px;
            border-bottom: solid 1px #05689f;
            text-decoration: none;
        }

        .mb-2:hover {
            margin-bottom: 1px !important;
            padding: 10px;
            background: #fa2222;
            text-decoration: none;
        }

        .bg-light {

            background-color: rgb(0 0 0) !important;
        }

        .navbar-brand {
            color: white;
        }

        @media (min-width: 1200px) {
            .fs-1 {
                font-size: 24px !important;
                font-weight: bold;
                padding: 10px;
            }
        }
    </style>
</head>

<body>

    <div class="d-flex">

        <div class="bg-dark text-white p-3">
            <img src="/images/netc2.png" style="width: 100%;margin: auto;" />
            <hr>


            <a href="{{ route('dashboard') }}" class="text-white d-block mb-2">Dashboard</a>


            <a href="{{ route('record.create') }}" class="text-white d-block mb-2">Records</a>

            <a href="{{ route('orders.list') }}" class="text-white d-block mb-2">Tracking Orders</a>
            <a href="{{ route('labelsenders') }}" class="text-white d-block mb-2">Label Senders</a>
            <a href="{{ route('clients.index') }}" class="text-white d-block mb-2">Add Client</a>


            <a href="{{ route('client.products') }}" class="text-white d-block mb-2">
                Product Weight Setup
            </a>


            <a href="{{ route('barcodes') }}" class="text-white d-block mb-2">Barcodes</a>

            <a href="{{ route('shopify.import.page') }}" class="text-white d-block mb-2">
                Import Shopify Orders
            </a>

            <a href="{{ route('labels.index') }}" class="text-white d-block mb-2">
                Courier Labels
            </a>
            <a href="{{ route('Invoice.index') }}" class="text-white d-block mb-2">
                Invoice Genrate
            </a>



            <!-- <a href="{{ route('labelgenrate') }}" class="text-white d-block mb-2">Generate Labels</a>-->
        </div>



        <div class="flex-grow-1">

            <nav class="navbar navbar-light bg-light px-3">
                <span class="navbar-brand">Netcrafty - CRM</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-danger btn-sm">Logout</button>
                </form>
            </nav>


            <div class="p-4">
                @yield('content')
            </div>



        </div>
    </div>


</body>

</html>
