@extends('layouts.admin')

@section('content')
    <style>
        .dashboard-card {
            border-radius: 16px;
            padding: 22px;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        }

        .dashboard-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
        }

        .card-green {
            background: linear-gradient(135deg, #dff3ea, #c8eadb);
        }

        .card-icon {
            font-size: 42px;
            opacity: 0.9;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
        }

        .card-count {
            font-size: 30px;
            font-weight: 700;
        }
    </style>

    <div class="container">

        <div class="row g-4">

            {{-- ORDERS CARDS --}}
            @foreach ($ordersData as $data)
                <div class="col-md-4">
                    <div class="dashboard-card card-green"
                        onclick="openAssignModal({{ $data['client_id'] }}, {{ $data['total_orders'] }})">

                        <div>
                            <div class="card-title"> Shopify Orders</div>
                            <spam>{{ $data['client_name'] }}</spam>
                            <div class="card-count">{{ $data['total_orders'] }}</div>
                            <small>Pending Orders</small>
                        </div>

                        <i class="bi bi-cart-check card-icon"></i>
                    </div>
                </div>
            @endforeach

            @foreach ($ordersData as $data)
                <div class="col-md-4">
                    <div class="dashboard-card card-green"
                        onclick="openAssignModal1({{ $data['client_id'] }}, {{ $data['rto_pending'] }})">

                        <div>
                            <div class="card-title"> RTO Received</div>
                            <spam>{{ $data['client_name'] }}</spam>
                            <div class="card-count"> {{ $data['rto_pending'] }}</div>

                            <small>Pending Orders</small>
                        </div>

                        <i class="bi bi-cart-check card-icon"></i>
                    </div>
                </div>
            @endforeach

            @foreach ($ordersData as $data)
                <div class="col-md-4">

                    <div class="dashboard-card card-green"
                        onclick="openAssignModal2(
                {{ $data['client_id'] }},
                {{ $data['repeat_pending'] ?? 0 }}
            )">

                        <div>

                            <div class="card-title">
                                Repeat Customer
                            </div>

                            <span>
                                {{ $data['client_name'] }}
                            </span>

                            <div class="card-count">
                                {{ $data['repeat_pending'] ?? 0 }}
                            </div>

                            <small>
                                Pending Reorder
                            </small>

                        </div>

                        <i class="bi bi-arrow-repeat card-icon"></i>

                    </div>

                </div>
            @endforeach

            @foreach ($ordersData as $data)
                <div class="col-md-4">

                    <div class="dashboard-card card-green"
                        onclick="openAssignModal3(
                {{ $data['client_id'] }},
                {{ $data['total_abandoned_orders'] ?? 0 }}
            )">

                        <div>

                            <div class="card-title">
                                Abandoned checkouts
                            </div>

                            <span>
                                {{ $data['client_name'] }}
                            </span>

                            <div class="card-count">
                                {{ $data['total_abandoned_orders'] ?? 0 }}
                            </div>

                            <small>
                                Pending Reorder
                            </small>

                        </div>

                        <i class="bi bi-arrow-repeat card-icon"></i>

                    </div>

                </div>
            @endforeach
            {{-- SELLOSHIP RECORDS --}}

            {{-- SELLOSHIP RECORDS --}}
            @foreach ($ordersData as $data)
                <div class="col-md-4">

                    <div class="dashboard-card card-green"
                        onclick="openSelloshipAssignModal(
                {{ $data['client_id'] }},
                {{ $data['selloship_records'] ?? 0 }}
            )">

                        <div>

                            <div class="card-title">
                                <i class="bi bi-file-earmark-spreadsheet"></i>
                                Selloship Records
                            </div>

                            <span>
                                {{ $data['client_name'] }}
                            </span>

                            <div class="card-count">
                                {{ $data['selloship_records'] ?? 0 }}
                            </div>

                            <small>
                                Pending Assignment
                            </small>

                        </div>

                        <i class="bi bi-file-earmark-arrow-up card-icon"></i>

                    </div>

                </div>
            @endforeach
        </div>

    </div>
    {{-- =========================================================
     SELLOSHIP ASSIGN MODAL
========================================================= --}}

    <div class="modal fade" id="selloshipAssignModal" tabindex="-1" aria-labelledby="selloshipAssignModalLabel"
        aria-hidden="true">

        <div class="modal-dialog modal-dialog-centered">

            <div class="modal-content">

                {{-- HEADER --}}
                <div class="modal-header">

                    <h5 class="modal-title" id="selloshipAssignModalLabel">

                        <i class="bi bi-file-earmark-spreadsheet me-2"></i>
                        Assign Selloship Records

                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>

                </div>


                {{-- FORM --}}
                <form id="selloshipAssignForm">

                    @csrf

                    <div class="modal-body">

                        {{-- CLIENT ID --}}
                        <input type="hidden" name="client_id" id="selloship_client_id">


                        {{-- AVAILABLE RECORDS --}}
                        <div class="alert alert-info">

                            <span>
                                Available Records:
                            </span>

                            <strong id="selloshipTotalOrders">
                                0
                            </strong>

                        </div>


                        {{-- STAFF LIST --}}
                        <div id="selloshipStaffList">

                            @forelse ($allStaff as $member)
                                <div class="d-flex justify-content-between align-items-center mb-3">

                                    <div>

                                        <strong>
                                            {{ $member->name }}
                                        </strong>

                                        <div class="text-muted small">
                                            Staff ID: {{ $member->id }}
                                        </div>

                                    </div>


                                    <input type="number" name="assign[{{ $member->id }}]" min="0" max="99999"
                                        value="0" class="form-control selloship-qty"
                                        data-staff-id="{{ $member->id }}" style="width:125px;">

                                </div>

                            @empty

                                <div class="alert alert-danger">
                                    No active staff found.
                                </div>
                            @endforelse

                        </div>


                        {{-- WARNING --}}
                        <div class="alert alert-warning" id="selloshipAssignmentWarning">

                            Enter quantity for at least one staff.

                        </div>


                        {{-- ERROR --}}
                        <div class="alert alert-danger d-none" id="selloshipAssignmentError">
                        </div>


                        {{-- SUCCESS --}}
                        <div class="alert alert-success d-none" id="selloshipAssignmentSuccess">
                        </div>

                    </div>


                    {{-- FOOTER --}}
                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">

                            Cancel

                        </button>


                        <button type="submit" id="assignSelloshipBtn" class="btn btn-primary">

                            <i class="bi bi-person-check me-1"></i>
                            Assign Selloship Orders

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>
    {{-- ASSIGN MODAL --}}
    <div class="modal fade" id="assignModal">
        <div class="modal-dialog">
            <div class="modal-content p-3">

                <h5>Assign Orders</h5>

                <p>Total Orders: <strong id="totalOrders"></strong></p>

                <form method="POST" action="{{ route('assign.orders') }}">
                    @csrf

                    <input type="hidden" name="client_id" id="client_id">

                    @foreach ($allStaff as $staff)
                        <div class="d-flex justify-content-between mb-2">
                            <label>{{ $staff->name }}</label>

                            <input type="number" name="assign[{{ $staff->id }}]" class="form-control w-25"
                                min="0" placeholder="0">
                        </div>
                    @endforeach

                    <button class="btn btn-primary mt-3 w-100">
                        Assign Orders
                    </button>
                </form>

            </div>
        </div>
    </div>
    <div class="modal fade" id="assignModal1">
        <div class="modal-dialog">
            <div class="modal-content p-3">

                <h5>Assign RTO Orders</h5>

                <p>
                    Total Orders:
                    <strong id="totalOrders1">0</strong>
                </p>

                <form method="POST" action="{{ route('assign.rto.orders') }}">
                    @csrf

                    <input type="hidden" name="client_id" id="client_id1">

                    <div id="rtoStaffList">
                        <div class="text-center py-3">
                            Loading...
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3 w-100">
                        Assign Orders
                    </button>

                </form>

            </div>
        </div>
    </div>
    <div class="modal fade" id="assignModal2" tabindex="-1">

        <div class="modal-dialog">

            <div class="modal-content p-3">

                <h5>Assign Repeat Customers</h5>

                <p>
                    Total Orders:
                    <strong id="totalOrders2">0</strong>
                </p>

                <form method="POST" action="{{ route('assign.delivered.orders') }}">

                    @csrf

                    <input type="hidden" name="client_id" id="client_id2">

                    @foreach ($allStaff as $staff)
                        <div class="d-flex justify-content-between mb-2">

                            <label>
                                {{ $staff->name }}
                            </label>

                            <input type="number" name="assign[{{ $staff->id }}]" class="form-control w-25"
                                min="0" placeholder="0">

                        </div>
                    @endforeach

                    <button type="submit" class="btn btn-primary mt-3 w-100">

                        Assign Repeat Customers

                    </button>

                </form>

            </div>

        </div>

    </div>
    <div class="modal fade" id="assignModal3" tabindex="-1">

        <div class="modal-dialog">

            <div class="modal-content p-3">

                <h5>Abandoned checkouts</h5>

                <p>
                    Total Orders:
                    <strong id="totalOrders3">0</strong>
                </p>

                <form method="POST" action="{{ route('assign.abandoned.orders') }}">

                    @csrf

                    <input type="hidden" name="client_id" id="client_id3">

                    @foreach ($allStaff as $staff)
                        <div class="d-flex justify-content-between mb-2">

                            <label>
                                {{ $staff->name }}
                            </label>

                            <input type="number" name="assign[{{ $staff->id }}]" class="form-control w-25"
                                min="0" placeholder="0">

                        </div>
                    @endforeach

                    <button type="submit" class="btn btn-primary mt-3 w-100">

                        Assign Repeat Customers

                    </button>

                </form>

            </div>

        </div>

    </div>
    <script>
        /*
                    |--------------------------------------------------------------------------
                    | Open Selloship Assignment Modal
                    |--------------------------------------------------------------------------
                    */

        function openSelloshipAssignModal(clientId, count) {

            // Set client ID
            $('#selloship_client_id').val(clientId);

            // Set available count
            $('#selloshipTotalOrders').text(count);

            // Reset all quantities
            $('.selloship-qty').val(0);

            // Reset messages
            $('#selloshipAssignmentWarning')
                .removeClass('d-none');

            $('#selloshipAssignmentError')
                .addClass('d-none')
                .text('');

            $('#selloshipAssignmentSuccess')
                .addClass('d-none')
                .text('');

            // Enable button
            $('#assignSelloshipBtn')
                .prop('disabled', false)
                .html(
                    '<i class="bi bi-person-check me-1"></i>' +
                    ' Assign Selloship Orders'
                );

            // Open Bootstrap modal
            const modalElement =
                document.getElementById('selloshipAssignModal');

            const modal =
                bootstrap.Modal.getOrCreateInstance(modalElement);

            modal.show();
        }


        /*
        |--------------------------------------------------------------------------
        | Selloship Assignment Form Submit
        |--------------------------------------------------------------------------
        */

        $(document).on(
            'submit',
            '#selloshipAssignForm',
            function(e) {

                e.preventDefault();

                let clientId =
                    $('#selloship_client_id').val();

                let available =
                    parseInt(
                        $('#selloshipTotalOrders').text()
                    ) || 0;

                let assignments = {};

                let totalRequested = 0;


                /*
                |--------------------------------------------------------------------------
                | Collect Staff Quantities
                |--------------------------------------------------------------------------
                */

                $('.selloship-qty').each(function() {

                    let staffId =
                        $(this).data('staff-id');

                    let quantity =
                        parseInt($(this).val()) || 0;

                    if (quantity < 0) {
                        quantity = 0;
                    }

                    if (quantity > 0) {

                        assignments[staffId] =
                            quantity;

                        totalRequested += quantity;
                    }

                });


                /*
                |--------------------------------------------------------------------------
                | Validation
                |--------------------------------------------------------------------------
                */

                $('#selloshipAssignmentError')
                    .addClass('d-none')
                    .text('');

                $('#selloshipAssignmentSuccess')
                    .addClass('d-none')
                    .text('');


                if (!clientId) {

                    showSelloshipError(
                        'Client ID is missing.'
                    );

                    return;
                }


                if (totalRequested <= 0) {

                    showSelloshipError(
                        'Enter quantity for at least one staff.'
                    );

                    return;
                }


                if (totalRequested > available) {

                    showSelloshipError(
                        'You requested ' +
                        totalRequested +
                        ' records, but only ' +
                        available +
                        ' records are available.'
                    );

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Disable Button
                |--------------------------------------------------------------------------
                */

                $('#assignSelloshipBtn')
                    .prop('disabled', true)
                    .html(
                        '<span class="spinner-border spinner-border-sm me-1"></span>' +
                        ' Assigning...'
                    );


                /*
                |--------------------------------------------------------------------------
                | AJAX
                |--------------------------------------------------------------------------
                */

                $.ajax({

                    url: "{{ route('assign.selloship.orders') }}",

                    type: 'POST',

                    data: {

                        _token: "{{ csrf_token() }}",

                        client_id: clientId,

                        assign: assignments

                    },


                    /*
                    |--------------------------------------------------------------------------
                    | Success
                    |--------------------------------------------------------------------------
                    */

                    success: function(response) {

                        console.log(
                            'Selloship Assignment:',
                            response
                        );


                        if (
                            response &&
                            response.success
                        ) {

                            let assigned =
                                response.assigned ||
                                totalRequested;

                            let remaining =
                                response.remaining !== undefined ?
                                response.remaining :
                                available - assigned;


                            $('#selloshipAssignmentWarning')
                                .addClass('d-none');


                            $('#selloshipAssignmentSuccess')
                                .removeClass('d-none')
                                .text(
                                    assigned +
                                    ' Selloship records assigned successfully.'
                                );


                            /*
                            |--------------------------------------------------------------------------
                            | Update count
                            |--------------------------------------------------------------------------
                            */

                            $('#selloshipTotalOrders')
                                .text(remaining);


                            /*
                            |--------------------------------------------------------------------------
                            | Close + Reload
                            |--------------------------------------------------------------------------
                            */

                            setTimeout(function() {

                                const modalElement =
                                    document.getElementById(
                                        'selloshipAssignModal'
                                    );

                                const modal =
                                    bootstrap.Modal
                                    .getOrCreateInstance(
                                        modalElement
                                    );

                                modal.hide();

                                location.reload();

                            }, 800);


                        } else {

                            showSelloshipError(
                                response.message ||
                                'Unable to assign Selloship records.'
                            );

                            enableSelloshipButton();
                        }

                    },


                    /*
                    |--------------------------------------------------------------------------
                    | Error
                    |--------------------------------------------------------------------------
                    */

                    error: function(xhr) {

                        console.error(
                            'Selloship Assignment Error:',
                            xhr
                        );


                        let message =
                            'Something went wrong while assigning records.';


                        if (
                            xhr.responseJSON &&
                            xhr.responseJSON.message
                        ) {

                            message =
                                xhr.responseJSON.message;

                        } else if (
                            xhr.responseText
                        ) {

                            console.error(
                                xhr.responseText
                            );
                        }


                        showSelloshipError(message);

                        enableSelloshipButton();

                    },


                    /*
                    |--------------------------------------------------------------------------
                    | Complete
                    |--------------------------------------------------------------------------
                    */

                    complete: function() {

                        // Only enable if not successful
                        // Successful request reloads page.
                    }

                });

            }
        );


        /*
        |--------------------------------------------------------------------------
        | Show Error
        |--------------------------------------------------------------------------
        */

        function showSelloshipError(message) {

            $('#selloshipAssignmentError')
                .removeClass('d-none')
                .text(message);

            $('#selloshipAssignmentSuccess')
                .addClass('d-none');

        }


        /*
        |--------------------------------------------------------------------------
        | Enable Button
        |--------------------------------------------------------------------------
        */

        function enableSelloshipButton() {

            $('#assignSelloshipBtn')
                .prop('disabled', false)
                .html(
                    '<i class="bi bi-person-check me-1"></i>' +
                    ' Assign Selloship Orders'
                );

        }
    </script>
    <script>
        function openAssignModal(clientId, totalOrders) {
            document.getElementById('client_id').value = clientId;
            document.getElementById('totalOrders').innerText = totalOrders;
            new bootstrap.Modal(document.getElementById('assignModal')).show();
        }
    </script>
    <script>
        function openAssignModal1(clientId, totalOrders) {

            document.getElementById('client_id1').value = clientId;
            document.getElementById('totalOrders1').innerText = totalOrders;

            const staffList = document.getElementById('rtoStaffList');

            staffList.innerHTML = `
        <div class="text-center py-3">
            Loading staff allocation...
        </div>
    `;

            new bootstrap.Modal(
                document.getElementById('assignModal1')
            ).show();

            let url = "{{ route('rto.staff.allocation', ':clientId') }}";
            url = url.replace(':clientId', clientId);

            fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(async response => {

                    const text = await response.text();

                    console.log('RTO Allocation Status:', response.status);
                    console.log('RTO Allocation Response:', text);

                    if (!response.ok) {
                        throw new Error(
                            'HTTP ' + response.status + ': ' + text
                        );
                    }

                    return JSON.parse(text);
                })
                .then(data => {

                    console.log('RTO Allocation:', data);

                    if (!data.success) {
                        throw new Error(
                            data.message || 'Allocation failed'
                        );
                    }

                    let html = '';

                    data.staff.forEach(staff => {

                        html += `
                <div class="d-flex justify-content-between align-items-center mb-2">

                    <label class="mb-0">
                        ${staff.name}
                    </label>

                    <input
                        type="number"
                        name="assign[${staff.id}]"
                        value="${staff.count}"
                        class="form-control w-25"
                        readonly
                    >

                </div>
            `;
                    });

                    staffList.innerHTML = html;

                    document.getElementById('totalOrders1').innerText =
                        data.total;
                })
                .catch(error => {

                    console.error('RTO Allocation Error:', error);

                    staffList.innerHTML = `
            <div class="text-danger">
                ${error.message}
            </div>
        `;
                });
        }
    </script>
    <script>
        function openAssignModal2(clientId, totalOrders) {
            document.getElementById('client_id2').value = clientId;

            document.getElementById('totalOrders2').innerText = totalOrders;

            new bootstrap.Modal(
                document.getElementById('assignModal2')
            ).show();
        }
    </script>
    <script>
        function openAssignModal3(clientId, totalOrders) {
            document.getElementById('client_id3').value = clientId;

            document.getElementById('totalOrders3').innerText = totalOrders;

            new bootstrap.Modal(
                document.getElementById('assignModal3')
            ).show();
        }
    </script>
@endsection
