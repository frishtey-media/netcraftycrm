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

        <div style="text-align:right;">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#excelImportModal">
                WhatsApp Excel Import
            </button>
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

        <form id="shopifyImportForm" style="margin: 35px 0px 35px 0px;" method="POST"
            action="{{ route('shopify.import') }}" enctype="multipart/form-data">

            @csrf

            <div class="row align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Select Client</label>
                    @if (auth()->user()->role == 'client')
                        <input type="hidden" name="client_id" value="{{ $clients->first()->id }}">

                        <input type="text" class="form-control" value="{{ $clients->first()->client_name }}" readonly>
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
