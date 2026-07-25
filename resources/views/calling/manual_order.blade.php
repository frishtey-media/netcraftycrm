@extends('layouts.calling')

@section('title', 'Add WhatsApp Order')

@section('content')

    <style>
        .section-card {
            border: 0;
            border-radius: 12px;
            overflow: hidden;
        }

        .section-title {
            font-weight: 600;
        }

        .form-label {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .required {
            color: red;
        }

        .staff-box {
            background: #f8f9fa;
        }
    </style>


    {{-- ========================================================= --}}
    {{-- SUCCESS / ERROR --}}
    {{-- ========================================================= --}}

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">

            {{ session('success') }}

            <button type="button" class="btn-close" data-bs-dismiss="alert">
            </button>

        </div>
    @endif


    @if (session('error'))
        <div class="alert alert-danger">

            {{ session('error') }}

        </div>
    @endif


    @if ($errors->any())

        <div class="alert alert-danger">

            <strong>Please correct following errors:</strong>

            <ul class="mb-0 mt-2">

                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif



    {{-- ========================================================= --}}
    {{-- CUSTOMER SEARCH --}}
    {{-- ========================================================= --}}

    <div class="card shadow-sm section-card mb-4">

        <div class="card-header bg-primary text-white">

            <h5 class="mb-0">

                <i class="fa fa-search"></i>

                Customer Search

            </h5>

        </div>


        <div class="card-body">

            <div class="row align-items-end">

                <div class="col-md-10">

                    <label class="form-label">
                        Customer Mobile Number
                    </label>

                    <input type="text" id="customer_phone_search" class="form-control form-control-lg"
                        placeholder="Enter Mobile Number">

                </div>


                <div class="col-md-2">

                    <button type="button" id="searchCustomer" class="btn btn-primary btn-lg w-100">

                        <i class="fa fa-search"></i>

                        Search

                    </button>

                </div>

            </div>


            <div class="mt-2">

                <button type="button" id="changeNumber" class="btn btn-outline-secondary btn-sm" style="display:none;">

                    Change Number

                </button>

            </div>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- PREVIOUS ORDER HISTORY --}}
    {{-- ========================================================= --}}

    <div class="card shadow-sm section-card mb-4" id="historyCard" style="display:none;">


        <div class="card-header bg-success text-white
               d-flex justify-content-between align-items-center">

            <h5 class="mb-0">

                <i class="fa fa-history"></i>

                Previous Order History

            </h5>


            <span id="historyCount" class="badge bg-light text-dark">

                0 Orders

            </span>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-bordered table-hover mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>Date</th>

                            <th>Order ID</th>

                            <th>Barcode</th>

                            <th>Client</th>

                            <th>Product</th>

                            <th>Amount</th>

                            <th>Status</th>

                            <th>Staff</th>

                        </tr>

                    </thead>


                    <tbody id="historyTable">

                    </tbody>

                </table>

            </div>

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- CALL / ORDER FORM --}}
    {{-- ========================================================= --}}

    <div id="callSection" style="display:none;">


        <form method="POST" action="{{ route('calling.manual.store') }}" id="manualOrderForm">

            @csrf


            {{-- ========================================================= --}}
            {{-- BASIC LEAD DETAILS --}}
            {{-- ========================================================= --}}

            <div class="card shadow-sm section-card mb-4">

                <div class="card-header bg-dark text-white">

                    <h5 class="mb-0">

                        <i class="fa fa-phone"></i>

                        Call Details

                    </h5>

                </div>


                <div class="card-body">

                    <div class="row g-3">


                        {{-- DATE --}}

                        <div class="col-md-3">

                            <label class="form-label">
                                Date <span class="text-danger">*</span>
                            </label>

                            <input type="date" name="created_at" id="created_at" class="form-control"
                                value="{{ date('Y-m-d') }}" required>

                        </div>



                        {{-- CLIENT --}}

                        <div class="col-md-3">

                            <label class="form-label">

                                Client

                                <span class="required">*</span>

                            </label>


                            <select name="client_id" id="client_id" class="form-select" required>

                                <option value="">
                                    Select Client
                                </option>


                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}">

                                        {{ $client->client_name }}

                                    </option>
                                @endforeach

                            </select>

                        </div>



                        {{-- STAFF --}}

                        <div class="col-md-3">

                            <label class="form-label">
                                Staff
                            </label>

                            <input type="text" class="form-control staff-box" value="{{ $staff->name }}" readonly>

                        </div>



                        {{-- ORDER ID --}}

                        <div class="col-md-3">

                            <label class="form-label">
                                Order ID
                            </label>

                            <input type="text" name="order_id" id="order_id" class="form-control" value="Auto Generate"
                                readonly>

                        </div>



                        {{-- PHONE --}}

                        <div class="col-md-4">

                            <label class="form-label">

                                Customer Phone

                                <span class="required">*</span>

                            </label>

                            <input type="text" name="customer_phone" id="customer_phone" class="form-control" required>

                        </div>



                        {{-- CALL STATUS --}}

                        <div class="col-md-4">

                            <label class="form-label">

                                Call Status

                                <span class="required">*</span>

                            </label>


                            <select name="status" id="call_status" class="form-select" required>

                                <option value="">
                                    Select Status
                                </option>

                                <option value="verified">
                                    Verified
                                </option>

                                <option value="pending">
                                    Pending
                                </option>

                                <option value="not_reachable">
                                    Not Reachable
                                </option>

                                <option value="same_order">
                                    Same Order
                                </option>

                                <option value="cancel">
                                    Cancel
                                </option>
                                <option value="Other">
                                    Other
                                </option>

                            </select>

                        </div>


                    </div>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- NON VERIFIED REMARKS --}}
            {{-- ========================================================= --}}

            <div class="card shadow-sm section-card mb-4" id="remarksSection" style="display:none;">


                <div class="card-header bg-warning">

                    <strong>
                        Call Remarks
                    </strong>

                </div>


                <div class="card-body">

                    <label class="form-label">

                        Remarks

                        <span class="required">*</span>

                    </label>


                    <textarea name="remarks" id="remarks" class="form-control" rows="4" placeholder="Enter call remarks..."></textarea>


                    <div class="mt-3">

                        <button type="submit" class="btn btn-success">

                            <i class="fa fa-save"></i>

                            Save Lead

                        </button>

                    </div>

                </div>

            </div>



            {{-- ========================================================= --}}
            {{-- VERIFIED ORDER --}}
            {{-- ========================================================= --}}

            <div class="card shadow-sm section-card mb-4" id="verifiedSection" style="display:none;">


                <div class="card-header bg-success text-white">

                    <h5 class="mb-0">

                        <i class="fa fa-check-circle"></i>

                        Verified Order Details

                    </h5>

                </div>


                <div class="card-body">

                    <div class="row g-3">


                        {{-- NAME --}}

                        <div class="col-md-4">

                            <label class="form-label">

                                Customer Name

                                <span class="required">*</span>

                            </label>

                            <input type="text" name="customer_name" class="form-control verified-required">

                        </div>



                        {{-- AGE --}}

                        <div class="col-md-4">

                            <label class="form-label">

                                Age

                                <span class="required">*</span>

                            </label>

                            <input type="number" name="age" class="form-control verified-required">

                        </div>



                        {{-- FATHER NAME --}}

                        <div class="col-md-4">

                            <label class="form-label">
                                Father Name
                            </label>

                            <input type="text" name="father_name" class="form-control">

                        </div>



                        {{-- PRODUCT --}}

                        <div class="col-md-6">

                            <label class="form-label">

                                Product

                                <span class="required">*</span>

                            </label>


                            <select name="product_name" id="product_name" class="form-select verified-required">

                                <option value="">
                                    Select Client First
                                </option>

                            </select>

                        </div>



                        {{-- QUANTITY --}}

                        <div class="col-md-2">

                            <label class="form-label">

                                Quantity

                                <span class="required">*</span>

                            </label>

                            <input type="number" name="quantity" id="quantity" value="1" min="1"
                                class="form-control verified-required">

                        </div>



                        {{-- WEIGHT --}}

                        <div class="col-md-2">

                            <label class="form-label">
                                Weight / Unit
                            </label>

                            <input type="number" step="0.01" name="weight" id="weight" class="form-control">

                        </div>



                        {{-- TOTAL WEIGHT --}}

                        <div class="col-md-2">

                            <label class="form-label">
                                Total Weight
                            </label>

                            <input type="text" id="total_weight_display" class="form-control" readonly>

                        </div>



                        {{-- AMOUNT --}}

                        <div class="col-md-4">

                            <label class="form-label">

                                Amount

                                <span class="required">*</span>

                            </label>

                            <input type="number" step="0.01" name="amount" class="form-control verified-required">

                        </div>



                        {{-- PAYMENT --}}

                        <div class="col-md-4">

                            <label class="form-label">

                                Payment Mode

                                <span class="required">*</span>

                            </label>

                            <select name="payment_mode" class="form-select verified-required">

                                <option value="">
                                    Select
                                </option>

                                <option value="COD">
                                    COD
                                </option>

                                <option value="VPP">
                                    VPP
                                </option>

                                <option value="Prepaid">
                                    Prepaid
                                </option>

                            </select>

                        </div>



                        {{-- PINCODE --}}

                        <div class="col-md-4">

                            <label class="form-label">

                                Pincode

                                <span class="required">*</span>

                            </label>

                            <input type="text" name="pincode" class="form-control verified-required">

                        </div>



                        {{-- CITY --}}

                        <div class="col-md-6">

                            <label class="form-label">
                                City
                            </label>

                            <input type="text" name="city" class="form-control">

                        </div>



                        {{-- STATE --}}

                        <div class="col-md-6">

                            <label class="form-label">
                                State
                            </label>

                            <input type="text" name="state" class="form-control">

                        </div>



                        {{-- ADDRESS --}}

                        <div class="col-md-12">

                            <label class="form-label">

                                Shipping Address

                                <span class="required">*</span>

                            </label>

                            <textarea name="address" class="form-control verified-required" rows="3"></textarea>

                        </div>


                    </div>


                    <div class="text-end mt-4">

                        <button type="submit" class="btn btn-success btn-lg">

                            <i class="fa fa-check"></i>

                            Save Verified Order

                        </button>

                    </div>

                </div>

            </div>


        </form>

    </div>



    {{-- ========================================================= --}}
    {{-- JAVASCRIPT --}}
    {{-- ========================================================= --}}

    <script>
        $(document).ready(function() {


            /*
            |--------------------------------------------------------------------------
            | SEARCH CUSTOMER
            |--------------------------------------------------------------------------
            */

            $('#searchCustomer').on('click', function() {

                let phone =
                    $('#customer_phone_search')
                    .val()
                    .trim();


                if (!phone) {

                    alert('Enter Mobile Number');

                    return;
                }


                let button = $(this);


                button
                    .prop('disabled', true)
                    .html(
                        '<i class="fa fa-spinner fa-spin"></i> Searching...'
                    );


                $.ajax({

                    url: "{{ route('calling.customer.history') }}",

                    type: "GET",

                    data: {
                        phone: phone
                    },


                    success: function(res) {

                        /*
                        |--------------------------------------------------------------------------
                        | Lock searched phone
                        |--------------------------------------------------------------------------
                        */

                        $('#customer_phone_search')
                            .val(res.phone)
                            .prop('readonly', true);


                        $('#customer_phone')
                            .val(res.phone);


                        $('#changeNumber')
                            .show();


                        /*
                        |--------------------------------------------------------------------------
                        | History
                        |--------------------------------------------------------------------------
                        */

                        $('#historyCard')
                            .slideDown();


                        $('#historyCount')
                            .text(
                                res.orders.length + ' Orders'
                            );


                        let html = '';


                        if (res.orders.length === 0) {

                            html = `

                        <tr>

                            <td
                                colspan="8"
                                class="text-center text-danger py-4">

                                No Previous Orders Found

                            </td>

                        </tr>

                    `;

                        } else {


                            $.each(
                                res.orders,
                                function(i, row) {

                                    let badge = '';


                                    if (
                                        row.delivery_status ===
                                        'Delivered'
                                    ) {

                                        badge =
                                            '<span class="badge bg-success">Delivered</span>';

                                    } else if (
                                        row.delivery_status ===
                                        'RTO'
                                    ) {

                                        badge =
                                            '<span class="badge bg-danger">RTO</span>';

                                    } else if (
                                        row.delivery_status ===
                                        'In Transit'
                                    ) {

                                        badge =
                                            '<span class="badge bg-warning text-dark">In Transit</span>';

                                    } else {

                                        badge =
                                            '<span class="badge bg-secondary">' +
                                            (
                                                row.delivery_status ??
                                                'No Status'
                                            ) +
                                            '</span>';

                                    }


                                    html += `

                                <tr>

                                    <td>
                                        ${row.created_at ?? '-'}
                                    </td>

                                    <td>
                                        ${row.order_id ?? '-'}
                                    </td>

                                    <td>
                                        ${row.barcode ?? '-'}
                                    </td>

                                    <td>
                                        ${row.client_name ?? '-'}
                                    </td>

                                    <td>
                                        ${row.product ?? '-'}
                                    </td>

                                    <td>
                                        ₹ ${row.amount ?? 0}
                                    </td>

                                    <td>
                                        ${badge}
                                    </td>

                                    <td>
                                        ${row.staff_name ?? '-'}
                                    </td>

                                </tr>

                            `;

                                }
                            );

                        }


                        $('#historyTable')
                            .html(html);


                        /*
                        |--------------------------------------------------------------------------
                        | Show call section
                        |--------------------------------------------------------------------------
                        */

                        $('#callSection')
                            .slideDown();

                    },


                    error: function(xhr) {

                        let message =
                            xhr.responseJSON?.message ??
                            'Unable to search customer.';

                        alert(message);

                    },


                    complete: function() {

                        button
                            .prop('disabled', false)
                            .html(
                                '<i class="fa fa-search"></i> Search'
                            );

                    }

                });

            });



            /*
            |--------------------------------------------------------------------------
            | CHANGE NUMBER
            |--------------------------------------------------------------------------
            */

            $('#changeNumber').on(
                'click',
                function() {

                    $('#customer_phone_search')
                        .prop('readonly', false)
                        .val('')
                        .focus();


                    $('#customer_phone')
                        .val('');


                    $('#historyCard')
                        .hide();


                    $('#callSection')
                        .hide();


                    $('#verifiedSection')
                        .hide();


                    $('#remarksSection')
                        .hide();


                    $('#call_status')
                        .val('');


                    $(this).hide();

                }
            );



            /*
            |--------------------------------------------------------------------------
            | CALL STATUS
            |--------------------------------------------------------------------------
            */

            $('#call_status').on(
                'change',
                function() {

                    let status =
                        $(this).val();


                    /*
                    |--------------------------------------------------------------------------
                    | Reset
                    |--------------------------------------------------------------------------
                    */

                    $('#verifiedSection')
                        .hide();


                    $('#remarksSection')
                        .hide();


                    $('.verified-required')
                        .prop('required', false);


                    $('#remarks')
                        .prop('required', false);


                    /*
                    |--------------------------------------------------------------------------
                    | Verified
                    |--------------------------------------------------------------------------
                    */

                    if (status === 'verified') {

                        $('#verifiedSection')
                            .slideDown();


                        $('.verified-required')
                            .prop('required', true);

                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Other status
                    |--------------------------------------------------------------------------
                    */
                    else if (status) {

                        $('#remarksSection')
                            .slideDown();


                        $('#remarks')
                            .prop('required', true);

                    }

                }
            );



            /*
            |--------------------------------------------------------------------------
            | CLIENT PRODUCTS
            |--------------------------------------------------------------------------
            */

            $('#client_id').on(
                'change',
                function() {

                    let clientId =
                        $(this).val();


                    $('#product_name')
                        .html(
                            '<option value="">Loading...</option>'
                        );


                    if (!clientId) {

                        $('#product_name')
                            .html(
                                '<option value="">Select Client First</option>'
                            );

                        return;
                    }


                    let url =
                        "{{ route('calling.client.products', ':id') }}";

                    url =
                        url.replace(
                            ':id',
                            clientId
                        );


                    $.get(
                        url,
                        function(products) {

                            let options =
                                '<option value="">Select Product</option>';


                            $.each(
                                products,
                                function(i, product) {

                                    options += `

                                <option
                                    value="${product.shopify_product_name}"
                                    data-weight="${product.weight_per_unit ?? 0}">

                                    ${product.shopify_product_name}

                                </option>

                            `;

                                }
                            );


                            $('#product_name')
                                .html(options);

                        }
                    );

                }
            );



            /*
            |--------------------------------------------------------------------------
            | PRODUCT WEIGHT
            |--------------------------------------------------------------------------
            */

            $('#product_name').on(
                'change',
                function() {

                    let weight =

                        $(this)
                        .find(':selected')
                        .data('weight')

                        ??
                        0;


                    $('#weight')
                        .val(weight);


                    calculateWeight();

                }
            );



            /*
            |--------------------------------------------------------------------------
            | QUANTITY / WEIGHT
            |--------------------------------------------------------------------------
            */

            $('#quantity, #weight').on(
                'input',
                function() {

                    calculateWeight();

                }
            );


            function calculateWeight() {
                let quantity =
                    parseFloat(
                        $('#quantity').val()
                    ) || 0;


                let weight =
                    parseFloat(
                        $('#weight').val()
                    ) || 0;


                let total =
                    quantity * weight;


                $('#total_weight_display')
                    .val(total);
            }


        });
    </script>
    <script>
        $(document).ready(function() {

            function loadOrderId() {

                let selectedDate = $('#created_at').val();

                if (!selectedDate) {
                    $('#order_id').val('Auto Generate');
                    return;
                }

                $('#order_id').val('Generating...');

                $.ajax({
                    url: "{{ route('calling.preview.order.id') }}",
                    type: "GET",

                    data: {
                        date: selectedDate
                    },

                    success: function(response) {

                        if (response.success) {
                            $('#order_id').val(response.order_id);
                        } else {
                            $('#order_id').val('Auto Generate');
                        }
                    },

                    error: function(xhr) {

                        console.log(xhr.responseText);

                        $('#order_id').val('Error');
                    }
                });
            }


            // Page load
            loadOrderId();


            // Date change
            $('#created_at').on('change', function() {
                loadOrderId();
            });

        });
    </script>
@endsection
