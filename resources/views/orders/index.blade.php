@extends('layouts.admin')

@section('content')
    <style>
        #barcodeTable {
            padding-top: 25px;
        }

        .report-card {
            border-radius: 15px;
            transition: all .3s;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .08);
        }

        .report-card:hover {
            transform: translateY(-3px);
        }

        .report-card .count {
            font-size: 34px;
            font-weight: 700;
        }

        .report-card .title {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .dashboard-card {
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
        }

        .dashboard-card .card-header {
            padding: 8px 15px;
            font-size: 16px;
            font-weight: 600;
        }

        .dashboard-card .card-body {
            padding: 12px 15px;
        }

        .dashboard-card p {
            margin-bottom: 6px;
            font-size: 14px;
        }

        .dashboard-card b {
            font-size: 16px;
        }

        .row.g-4 {
            --bs-gutter-y: 12px;
        }
    </style>
    <div class="card mb-3">
        <h4 style="text-align: center;padding: 20px;">Date Wise Report</h4>
        <hr style="margin: 0;">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

        <form method="GET" action="{{ route('orders.list') }}">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-filter"></i> Filters
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label>Client</label>
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
                        <div class="col-md-2">
                            <label>Staff</label>

                            <select name="staff_id" class="form-control">

                                <option value="">
                                    All Staff
                                </option>

                                @foreach ($staffs as $staff)
                                    <option value="{{ $staff->id }}"
                                        {{ request('staff_id') == $staff->id ? 'selected' : '' }}>

                                        {{ $staff->name }}

                                    </option>
                                @endforeach

                            </select>
                        </div>

                        <div class="col-md-2">
                            <label>Status</label>

                            <select name="delivery_status" class="form-control">

                                <option value="">
                                    All Status
                                </option>

                                <option value="Delivered">
                                    Delivered
                                </option>

                                <option value="RTO">
                                    RTO
                                </option>

                                <option value="In Transit">
                                    In Transit
                                </option>

                                <option value="Out For Delivery">
                                    Out For Delivery
                                </option>

                                <option value="null">
                                    No Status
                                </option>

                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Date From</label>
                            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label>Date To</label>
                            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label>Search</label>
                            <textarea name="search" class="form-control" rows="3"
                                placeholder="Order ID / Barcode / Phone / Name (One per line)">{{ request('search') }}</textarea>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button class="btn btn-primary me-2">Filter</button>
                            <a href="{{ route('orders.list') }}" class="btn btn-secondary">Reset</a>
                        </div>

                    </div>
                </div>
                <!-- <div class="col-md-2">
                                                                                                                                                                                                                                                                <label>Records</label>

                                                                                                                                                                                                                                                                <select name="per_page" class="form-control" onchange="this.form.submit()">
                                                                                                                                                                                                                                                                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                                                                                                                                                                                                                                                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                                                                                                                                                                                                                                                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                                                                                                                                                                                                                                                    <option value="100" {{ request('per_page', 100) == 100 ? 'selected' : '' }}>100</option>
                                                                                                                                                                                                                                                                    <option value="500" {{ request('per_page') == 500 ? 'selected' : '' }}>500</option>
                                                                                                                                                                                                                                                                    <option value="1000" {{ request('per_page') == 1000 ? 'selected' : '' }}>1000</option>
                                                                                                                                                                                                                                                                </select>
                                                                                                                                                                                                                                                            </div>-->
        </form>
    </div>
    <div class="card mb-3" style="padding: 15px;">
        <div id="bulkActions" class="mb-3 d-none">
            <button class="btn btn-danger" id="downloadInvoice">
                Download Invoice PDF
            </button>

            <button class="btn btn-success" id="downloadLabel">
                Re-Download Label PDF
            </button>

            <button class="btn btn-primary" id="downloadExcel">
                Download Post Office Excel
            </button>

            <button class="btn btn-danger" id="downloadmoney">
                Download Money order Pdf
            </button>
            <button class="btn btn-success" id="downloadexcelsss">
                Download Excel
            </button>
        </div>
        <div class="table-responsive">
            @if (!empty($searchTerms))
                <div class="alert alert-info mb-3">

                    <strong>Total Search Items:</strong>
                    {{ count($searchTerms) }}

                    &nbsp;&nbsp;|&nbsp;&nbsp;

                    <strong>Matched:</strong>
                    {{ count($searchTerms) - count($notFound) }}

                    &nbsp;&nbsp;|&nbsp;&nbsp;

                    <strong>Not Found:</strong>
                    {{ count($notFound) }}

                </div>
            @endif
            @if (!empty($notFound))
                <div class="alert alert-danger">

                    <strong>Records Not Found ({{ count($notFound) }})</strong>

                    <textarea class="form-control mt-2" rows="6" readonly>{{ implode("\n", $notFound) }}</textarea>

                </div>
            @endif
            @if (request()->anyFilled(['client_id', 'staff_id', 'delivery_status', 'date_from', 'date_to', 'search']))
                <div class="alert alert-info">

                    <strong>Applied Filters:</strong>

                    @if (request('search'))
                        Search: {{ request('search') }} |
                    @endif

                    @if (request('date_from'))
                        From: {{ request('date_from') }} |
                    @endif

                    @if (request('date_to'))
                        To: {{ request('date_to') }} |
                    @endif

                    @if (request('delivery_status'))
                        Status: {{ request('delivery_status') }} |
                    @endif

                </div>
            @endif
            @if (auth()->user()->role == 'super_admin')
                <div class="row g-4">

                    <!-- Orders -->
                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <div class="card dashboard-card border-primary">
                            <div class="card-header bg-primary text-white">
                                📦 Orders
                            </div>

                            <div class="card-body">
                                <p>Total: <b>{{ $totalOrders }}</b></p>
                                <p>Web: <b>{{ $webOrders }}</b></p>
                                <p>WhatsApp: <b>{{ $whatsappOrders }}</b></p>
                            </div>
                        </div>
                    </div>

                    <!-- Delivered -->
                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <div class="card dashboard-card border-success">
                            <div class="card-header bg-success text-white">
                                ✅ Delivered
                            </div>

                            <div class="card-body">
                                <p>Total: <b>{{ $totalDelivered }}</b></p>
                                <p>Web: <b>{{ $webDelivered }}</b></p>
                                <p>WhatsApp: <b>{{ $whatsappDelivered }}</b></p>
                            </div>
                        </div>
                    </div>

                    <!-- RTO -->
                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <div class="card dashboard-card border-danger">
                            <div class="card-header bg-danger text-white">
                                ↩️ RTO
                            </div>

                            <div class="card-body">
                                <p>Tracking: <b>{{ $totalRto }}</b></p>
                                <p>Web: <b>{{ $webRto }}</b></p>
                                <p>WhatsApp: <b>{{ $whatsappRto }}</b></p>
                                <p>Received: <b>{{ $rtoReceived }}</b></p>
                            </div>
                        </div>
                    </div>

                    <!-- Transit -->
                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <div class="card dashboard-card border-info">
                            <div class="card-header bg-info text-white">
                                🚚 In Transit
                            </div>

                            <div class="card-body">
                                <p>Total: <b>{{ $totalTransit }}</b></p>
                                <p>Web: <b>{{ $webTransit }}</b></p>
                                <p>WhatsApp: <b>{{ $whatsappTransit }}</b></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <div class="card dashboard-card border-success">
                            <div class="card-header bg-success text-white">
                                💰 Payments
                            </div>

                            <div class="card-body">
                                <p>
                                    Payment Received Orders:
                                    <b>{{ $paymentReceivedOrders }}</b>
                                </p>

                                <p>
                                    Received Amount:
                                    <b>₹{{ number_format($paymentReceivedAmount, 2) }}</b>
                                </p>

                                <hr>

                                <p>
                                    Pending Orders:
                                    <b>{{ $paymentPendingOrders }}</b>
                                </p>

                                <p>
                                    Pending Amount:
                                    <b>₹{{ number_format($paymentPendingAmount, 2) }}</b>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- No Status -->
                    <div class="col-xl-2 col-lg-4 col-md-6">
                        <div class="card dashboard-card border-warning">
                            <div class="card-header bg-warning text-dark">
                                ⏳ No Status
                            </div>

                            <div class="card-body">
                                <p>Total: <b>{{ $totalNoStatus }}</b></p>
                                <p>Web: <b>{{ $webNoStatus }}</b></p>
                                <p>WhatsApp: <b>{{ $whatsappNoStatus }}</b></p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <table id="barcodeTable" class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>#</th>
                        <th>Order ID</th>

                        <th>Delivery Status</th>
                        <th>Payment Status</th>
                        <th>RTO Receive Status</th>
                        <th>Barcode</th>
                        <th>Customer Name</th>
                        <th>Customer Phone</th>
                        <th>Shipping Address</th>
                        <th>Payment Mode</th>
                        <th>Amount</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Weight</th>
                        <th>Date</th>
                        <th>Delivery Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td>
                                <input type="checkbox" class="order-checkbox" value="{{ $order->id }}">
                            </td>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-placement="top"
                                    title="Assigned To: {{ $order->callingOrder->staff->name ?? 'Not Assigned' }}">

                                    {{ $order->order_id }}
                                </a>
                            </td>
                            <td>
                                @if (strtolower($order->delivery_status) == 'delivered')
                                    <span class="badge bg-success fs-6">
                                        {{ $order->delivery_status }}
                                    </span>
                                @elseif(strtolower($order->delivery_status) == 'in transit' || strtolower($order->delivery_status) == 'out for delivery')
                                    <span class="badge bg-primary fs-6">
                                        {{ $order->delivery_status }}
                                    </span>
                                @else
                                    <span class="badge bg-danger fs-6">
                                        {{ $order->delivery_status }}
                                    </span>
                                @endif
                            </td>
                            <td class="{{ $order->recivedpaysts == 1 ? 'bg-success text-white fw-bold' : '' }}">
                                {{ $order->recivedpaysts == 1 ? 'Payment Received' : '' }}
                            </td>

                            <td class="{{ $order->rtorecivedsts == 1 ? 'bg-warning text-dark fw-bold' : '' }}">
                                {{ $order->rtorecivedsts == 1 ? 'RTO Received' : '' }}
                            </td>
                            <td>{{ $order->barcode }}</td>

                            <td>{{ $order->customer_name }}</td>
                            <td>{{ $order->customer_phone }}</td>
                            <td>{{ $order->shipping_address }}</td>
                            <td>{{ $order->payment_mode }}</td>
                            <td>{{ $order->amount }}</td>
                            <td>{{ $order->product }}</td>
                            <td>{{ $order->quantity }}</td>
                            <td>{{ $order->weight }}</td>
                            <td>{{ $order->date }}</td>
                            <td>{{ $order->delivery_remark }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="d-flex justify-content-between align-items-center mt-3">

                <div>
                    Showing
                    <strong>{{ $orders->firstItem() }}</strong>
                    to
                    <strong>{{ $orders->lastItem() }}</strong>
                    of
                    <strong>{{ $orders->total() }}</strong>
                    records
                </div>

                <div>
                    {{ $orders->links() }}
                </div>

            </div>
        </div>
    </div>
    {{-- SCRIPTS --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>



    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var tooltipTriggerList = [].slice.call(
                document.querySelectorAll('[data-bs-toggle="tooltip"]')
            );

            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
    <script>
        $(document).ready(function() {

            // Select All
            $('#selectAll').on('change', function() {
                $('.order-checkbox').prop('checked', $(this).prop('checked'));
                toggleButtons();
            });

            // Individual checkbox
            $(document).on('change', '.order-checkbox', function() {
                toggleButtons();
            });

            function toggleButtons() {
                let checkedCount = $('.order-checkbox:checked').length;
                if (checkedCount > 0) {
                    $('#bulkActions').removeClass('d-none');
                } else {
                    $('#bulkActions').addClass('d-none');
                }
            }

            function getSelectedOrders() {
                let ids = [];
                $('.order-checkbox:checked').each(function() {
                    ids.push($(this).val());
                });
                return ids;
            }

            // Invoice PDF
            $('#downloadInvoice').click(function() {
                let ids = getSelectedOrders();
                window.location.href = "{{ route('orders.invoice.pdf') }}?ids=" + ids.join(',');
            });

            // Excel
            $('#downloadExcel').click(function() {
                let ids = getSelectedOrders();
                window.location.href = "{{ route('orders.postoffice.excel') }}?ids=" + ids.join(',');
            });

            $('#downloadmoney').click(function() {
                let ids = getSelectedOrders();

                if (ids.length === 0) {
                    alert('Please select orders');
                    return;
                }

                let form = $('<form>', {
                    method: 'POST',
                    action: "{{ route('orders.Moneyorder.pdf') }}"
                });

                form.append('@csrf');
                form.append(`<input type="hidden" name="ids" value="${ids.join(',')}">`);

                $('body').append(form);
                form.submit();
            });




            $('#downloadexcelsss').click(function() {

                let ids = getSelectedOrders();

                if (ids.length === 0) {
                    alert('Please select orders');
                    return;
                }

                let form = $('<form>', {
                    method: 'POST',
                    action: "{{ route('orders.export.selected') }}"
                });

                form.append('@csrf');

                form.append(
                    $('<input>', {
                        type: 'hidden',
                        name: 'ids',
                        value: ids.join(',')
                    })
                );

                $('body').append(form);
                form.submit();
            });


        });
    </script>

    <div class="modal fade" id="senderModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="labelDownloadForm" method="POST" action="{{ route('labels.selected.pdf') }}">
                @csrf

                <input type="hidden" name="ids" id="selected_ids">

                <div class="modal-content">
                    <div class="modal-header">
                        <h5>Select Sender</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <select name="sender_id" id="modal_sender_id" class="form-control" required>
                            <option value="">-- Select Sender --</option>
                            @foreach ($senders as $sender)
                                <option value="{{ $sender->id }}">
                                    {{ $sender->customer_name }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" name="use_old_barcode" id="use_old_barcode"
                                value="1">
                            <label class="form-check-label" for="use_old_barcode">
                                Use Old Barcode (Assign existing barcode)
                            </label>
                        </div>
                    </div>


                    <div class="modal-footer">
                        <button type="button" id="confirmDownloadLabel" class="btn btn-success">
                            Download Label PDF
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <script>
        // get selected orders
        function getSelectedOrders() {
            let ids = [];
            $('.order-checkbox:checked').each(function() {
                ids.push($(this).val());
            });
            return ids;
        }

        // open sender modal
        $('#downloadLabel').on('click', function() {
            let ids = getSelectedOrders();

            if (ids.length === 0) {
                alert('Please select at least one order');
                return;
            }

            $('#selected_ids').val(ids.join(','));
            $('#senderModal').modal('show');
        });

        // select all
        $('#selectAll').on('change', function() {
            $('.order-checkbox').prop('checked', this.checked);
        });

        // confirm download
        $('#confirmDownloadLabel').on('click', function() {
            let senderId = $('#modal_sender_id').val();

            if (!senderId) {
                alert('Please select sender');
                return;
            }

            // submit POST form
            $('#labelDownloadForm').submit();

            $('#senderModal').modal('hide');
        });
    </script>
@endsection
