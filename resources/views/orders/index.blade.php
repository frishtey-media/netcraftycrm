@extends('layouts.admin')

@section('content')
    <style>
        #barcodeTable {
            padding-top: 25px;
        }
    </style>
    <div class="card mb-3">
        <h4 style="text-align: center;padding: 20px;">Date Wise Report</h4>
        <hr style="margin: 0;">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">

        <form method="GET" action="{{ route('orders.list') }}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label>Client</label>
                        <select name="client_id" id="client_id" class="form-control">
                            <option value="">Select Client</option>
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}"
                                    {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                    {{ $client->client_name }}
                                </option>
                            @endforeach
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

                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary me-2">Filter</button>
                        <a href="{{ route('orders.list') }}" class="btn btn-secondary">Reset</a>
                    </div>

                </div>
            </div>
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
        </div>

        <table id="barcodeTable" class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
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
                    <th>Weight</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td>
                            <input type="checkbox" class="order-checkbox" value="{{ $order->id }}">
                        </td>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $order->order_id }}</td>
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
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{-- SCRIPTS --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#barcodeTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                language: {
                    search: "Search Orders:"
                }
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
