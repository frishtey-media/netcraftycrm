@extends('layouts.admin')

@section('content')

    <style>
        .loader-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, .9);
            z-index: 1056;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .loader-box {
            background: #fff;
            padding: 30px 40px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(0, 0, 0, .2);
        }

        .spinner {
            width: 55px;
            height: 55px;
            border: 5px solid #e5e5e5;
            border-top: 5px solid #198754;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: auto;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>


    <div class="container-fluid">

        {{-- =========================================================
        SUCCESS / ERRORS
    ========================================================== --}}

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif



        {{-- =========================================================
        CUSTOMER SEARCH
    ========================================================== --}}

        <div class="card shadow-sm border-0 mb-4">

            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-search me-2"></i>
                    Customer Order Search
                </h5>
            </div>

            <div class="card-body">

                <div class="row g-2">

                    <div class="col-md-10">

                        <input type="text" id="customer_phone_search" class="form-control"
                            placeholder="Enter Mobile Number" autocomplete="off">

                    </div>

                    <div class="col-md-2">

                        <button type="button" id="searchCustomer" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                            Search
                        </button>

                    </div>

                </div>


                <div class="mt-2 d-none" id="changeNumberBox">

                    <button type="button" id="changeNumber" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-edit"></i>
                        Change Number
                    </button>

                </div>

            </div>

        </div>



        {{-- =========================================================
        PREVIOUS ORDER HISTORY
    ========================================================== --}}

        <div class="card shadow-sm border-0 mb-4">

            <div
                class="card-header bg-success text-white
                   d-flex justify-content-between align-items-center">

                <h5 class="mb-0">
                    <i class="fas fa-history me-2"></i>
                    Previous Order History
                </h5>

                <span id="historyCount" class="badge bg-light text-dark">
                    0 Orders
                </span>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table table-hover table-bordered mb-0">

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

                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No customer searched.
                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>







        {{-- =========================================================
        MAIN LEAD FORM
    ========================================================== --}}

        <form method="POST" action="{{ route('record.store') }}" id="leadForm">

            @csrf


            {{-- =====================================================
            COMMON LEAD INFORMATION
        ====================================================== --}}

            <div id="leadSection" class="card shadow-sm border-0 mb-4 d-none">

                <div class="card-header bg-dark text-white">

                    <h5 class="mb-0">
                        Lead Information
                    </h5>

                </div>


                <div class="card-body">

                    <div class="row g-3">


                        {{-- DATE --}}

                        <div class="col-md-4">

                            <label class="form-label">
                                Import Date
                            </label>

                            <input type="date" name="created_at" id="lead_created_at" class="form-control"
                                value="{{ date('Y-m-d') }}" required>

                        </div>



                        {{-- CLIENT --}}

                        <div class="col-md-4">

                            <label class="form-label">
                                Client Name
                            </label>

                            <select name="client_id" id="lead_client_id" class="form-select" required>

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

                        <div class="col-md-4">

                            <label class="form-label">
                                Assign Staff
                            </label>

                            <select name="assigned_to" id="lead_staff_id" class="form-select" required>

                                <option value="">
                                    Select Staff
                                </option>

                                @foreach ($staffs as $staff)
                                    <option value="{{ $staff->id }}">
                                        {{ $staff->name }}
                                    </option>
                                @endforeach

                            </select>

                        </div>



                        {{-- ORDER ID --}}

                        <div class="col-md-4">

                            <label class="form-label">
                                Order ID
                            </label>

                            <input type="text" name="order_id" id="lead_order_id" class="form-control"
                                value="Auto Generate" readonly required>

                        </div>



                        {{-- CUSTOMER PHONE --}}

                        <div class="col-md-4">

                            <label class="form-label">
                                Customer Phone
                            </label>

                            <input type="text" name="customer_phone" id="lead_customer_phone" class="form-control"
                                readonly required>

                        </div>



                        {{-- CALL STATUS --}}

                        <div class="col-md-4">

                            <label class="form-label">
                                Call Status
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

                            </select>

                        </div>


                    </div>

                </div>

            </div>



            {{-- =====================================================
            NON VERIFIED STATUS
        ====================================================== --}}

            <div id="remarksSection" class="card shadow-sm border-0 mb-4 d-none">

                <div class="card-header bg-warning">

                    <strong>
                        Call Remarks
                    </strong>

                </div>


                <div class="card-body">

                    <div class="row">

                        <div class="col-md-10">

                            <label class="form-label">
                                Remarks
                            </label>

                            <textarea name="remarks" id="remarks" class="form-control" rows="3" placeholder="Enter call remarks..."></textarea>

                        </div>


                        <div class="col-md-2
                               d-flex align-items-end">

                            <button type="submit" class="btn btn-success w-100">
                                Save Lead
                            </button>

                        </div>

                    </div>

                </div>

            </div>



            {{-- =====================================================
            VERIFIED ORDER
        ====================================================== --}}

            <div id="verifiedOrderSection" class="card shadow-sm border-0 mb-4 d-none">



                <!--  <div class="mb-3">

                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                        data-bs-target="#bulkImportModal">
                                        <i class="fas fa-upload"></i>
                                        Bulk Order Import
                                    </button>

                                </div>-->

                <div class="card-header bg-success text-white">

                    <h5 class="mb-0">
                        Verified Customer Order
                    </h5>

                </div>


                <div class="card-body">

                    <div class="row g-3">


                        {{-- PRODUCTS --}}

                        <div class="col-md-8">

                            <label class="form-label">
                                Product
                            </label>

                            <div id="product-wrapper">

                                <div class="row product-row mb-2">


                                    <div class="col-md-6">

                                        <select name="products[0][product]" class="form-select product-select">

                                            <option value="">
                                                Select Product
                                            </option>

                                        </select>

                                    </div>


                                    <div class="col-md-2">

                                        <input type="number" name="products[0][quantity]"
                                            class="form-control product-qty" value="1" min="1">

                                    </div>


                                    <div class="col-md-3">

                                        <input type="number" name="products[0][weight]"
                                            class="form-control weight-input" placeholder="Weight" readonly>

                                    </div>


                                    <div class="col-md-1">

                                        <button type="button" class="btn btn-success add-product">
                                            +
                                        </button>

                                    </div>


                                </div>

                            </div>

                        </div>



                        {{-- TOTAL WEIGHT --}}

                        <div class="col-md-4">

                            <label class="form-label">
                                Total Weight (GM)
                            </label>

                            <input type="number" id="total_weight_display" class="form-control" readonly>

                        </div>



                        {{-- AMOUNT --}}

                        <div class="col-md-4">

                            <label class="form-label">
                                Amount
                            </label>

                            <input type="number" name="amount" step="0.01" class="form-control verified-required">

                        </div>



                        {{-- PAYMENT --}}

                        <div class="col-md-4">

                            <label class="form-label">
                                Payment Mode
                            </label>

                            <select name="payment_mode" class="form-select verified-required">

                                <option value="">
                                    Select Payment
                                </option>

                                <option value="VPP">
                                    VPP
                                </option>

                                <option value="COD">
                                    COD
                                </option>

                                <option value="Prepaid">
                                    Prepaid
                                </option>

                            </select>

                        </div>



                        {{-- CUSTOMER NAME --}}

                        <div class="col-md-4">

                            <label class="form-label">
                                Customer Name
                            </label>

                            <input type="text" name="customer_name" class="form-control verified-required">

                        </div>



                        {{-- AGE --}}

                        <div class="col-md-4">

                            <label class="form-label">
                                Age
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



                        {{-- CITY --}}

                        <div class="col-md-4">

                            <label class="form-label">
                                City
                            </label>

                            <input type="text" name="city" class="form-control">

                        </div>



                        {{-- STATE --}}

                        <div class="col-md-4">

                            <label class="form-label">
                                State
                            </label>

                            <input type="text" name="state" class="form-control">

                        </div>



                        {{-- PINCODE --}}

                        <div class="col-md-4">

                            <label class="form-label">
                                Shipping Pincode
                            </label>

                            <input type="text" name="shipping_pincode" class="form-control verified-required">

                        </div>



                        {{-- ADDRESS 1 --}}

                        <div class="col-md-6">

                            <label class="form-label">
                                Shipping Address Line 1
                            </label>

                            <textarea name="shipping_address_line1" class="form-control verified-required" rows="3"></textarea>

                        </div>



                        {{-- ADDRESS 2 --}}

                        <div class="col-md-6">

                            <label class="form-label">
                                Shipping Address Line 2
                            </label>

                            <textarea name="shipping_address_line2" class="form-control" rows="3"></textarea>

                        </div>



                        {{-- VERIFIED REMARKS --}}

                        <div class="col-md-12">

                            <label class="form-label">
                                Remarks
                            </label>

                            <textarea name="verified_remarks" class="form-control" rows="2" placeholder="Optional remarks"></textarea>

                        </div>



                        {{-- SAVE --}}

                        <div class="col-md-12">

                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i>
                                Save Verified Order
                            </button>

                        </div>


                    </div>

                </div>

            </div>


        </form>


    </div>



    {{-- =========================================================
    BULK IMPORT MODAL
========================================================== --}}

    <div class="modal fade" id="bulkImportModal" tabindex="-1">

        <div class="modal-dialog">

            <div class="modal-content">


                <form method="POST" action="{{ route('record.whstappimport') }}" enctype="multipart/form-data"
                    id="excelImportForm">

                    @csrf


                    <div class="modal-header">

                        <h5 class="modal-title">
                            Bulk Order Import
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>

                    </div>


                    <div class="modal-body">


                        <div class="mb-3">

                            <label class="form-label">
                                Import Date
                            </label>

                            <input type="date" name="created_at" class="form-control" value="{{ date('Y-m-d') }}"
                                required>

                        </div>



                        <div class="mb-3">

                            <label class="form-label">
                                Client
                            </label>

                            <select name="client_id" class="form-select" required>

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



                        <div class="mb-3">

                            <label class="form-label">
                                Assign Staff
                            </label>

                            <select name="assigned_to" class="form-select" required>

                                <option value="">
                                    Select Staff
                                </option>

                                @foreach ($staffs as $staff)
                                    <option value="{{ $staff->id }}">
                                        {{ $staff->name }}
                                    </option>
                                @endforeach

                            </select>

                        </div>



                        <div class="mb-3">

                            <label class="form-label">
                                Excel File
                            </label>

                            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>

                        </div>


                    </div>


                    <div class="modal-footer">

                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Close
                        </button>

                        <button type="submit" id="importBtn" class="btn btn-success">
                            Import Orders
                        </button>

                    </div>


                </form>


            </div>

        </div>

    </div>



    {{-- =========================================================
    IMPORT LOADER
========================================================== --}}

    <div id="excelImportLoader" class="d-none">

        <div class="loader-backdrop">

            <div class="loader-box">

                <div class="spinner"></div>

                <p>
                    Importing WhatsApp Excel...
                </p>

                <small>
                    Please wait, do not refresh
                </small>

            </div>

        </div>

    </div>



    {{-- =========================================================
    JAVASCRIPT
========================================================== --}}

    <script>
        $(document).ready(function() {

            let productOptions =
                '<option value="">Select Product</option>';

            let productIndex = 1;


            /* =====================================================
               NORMALIZE PHONE
            ====================================================== */

            function normalizePhone(phone) {

                phone = String(phone || '')
                    .replace(/\D/g, '');

                // 91XXXXXXXXXX
                if (
                    phone.length === 12 &&
                    phone.startsWith('91')
                ) {
                    phone = phone.substring(2);
                }

                // 0XXXXXXXXXX
                if (
                    phone.length === 11 &&
                    phone.startsWith('0')
                ) {
                    phone = phone.substring(1);
                }

                // Any extra prefix
                if (phone.length > 10) {
                    phone = phone.slice(-10);
                }

                return phone;
            }



            /* =====================================================
               CUSTOMER SEARCH
            ====================================================== */

            $('#searchCustomer').on('click', function() {

                let phone = normalizePhone(
                    $('#customer_phone_search').val()
                );


                if (phone.length !== 10) {

                    alert(
                        'Please enter valid 10 digit mobile number'
                    );

                    return;
                }


                $('#customer_phone_search')
                    .val(phone);


                $.ajax({

                    url: "{{ route('record.customer.history') }}",

                    type: "GET",

                    data: {
                        phone: phone
                    },


                    beforeSend: function() {

                        $('#searchCustomer')
                            .prop('disabled', true)
                            .text('Searching...');

                    },


                    success: function(rows) {


                        /* LOCK NUMBER */

                        $('#customer_phone_search')
                            .prop('readonly', true);


                        $('#changeNumberBox')
                            .removeClass('d-none');


                        /* SET PHONE */

                        $('#lead_customer_phone')
                            .val(phone);


                        /* SHOW LEAD */

                        $('#leadSection')
                            .removeClass('d-none');


                        /* RESET STATUS */

                        $('#call_status')
                            .val('');


                        $('#remarksSection')
                            .addClass('d-none');


                        $('#verifiedOrderSection')
                            .addClass('d-none');


                        $('.verified-required')
                            .prop('required', false);


                        $('#remarks')
                            .prop('required', false);


                        /* HISTORY COUNT */

                        $('#historyCount')
                            .text(rows.length + ' Orders');


                        let html = '';


                        if (rows.length === 0) {

                            html = `
                        <tr>
                            <td
                                colspan="8"
                                class="text-center text-danger py-4"
                            >
                                No Previous Orders Found
                            </td>
                        </tr>
                    `;

                        } else {


                            rows.forEach(function(row) {

                                let status = '';


                                if (
                                    row.delivery_status === 'Delivered'
                                ) {

                                    status =
                                        '<span class="badge bg-success">Delivered</span>';

                                } else if (
                                    row.delivery_status === 'RTO'
                                ) {

                                    status =
                                        '<span class="badge bg-danger">RTO</span>';

                                } else if (
                                    row.delivery_status === 'In Transit'
                                ) {

                                    status =
                                        '<span class="badge bg-warning text-dark">Transit</span>';

                                } else {

                                    status =
                                        '<span class="badge bg-secondary">' +
                                        (row.delivery_status ?? '-') +
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
                                    ${status}
                                </td>

                                <td>
                                    ${row.staff_name ?? '-'}
                                </td>

                            </tr>

                        `;

                            });

                        }


                        $('#historyTable')
                            .html(html);

                    },


                    error: function(xhr) {

                        let message =
                            xhr.responseJSON?.message ??
                            'Customer search failed.';

                        alert(message);

                    },


                    complete: function() {

                        $('#searchCustomer')
                            .prop('disabled', false)
                            .html(
                                '<i class="fas fa-search"></i> Search'
                            );

                    }

                });

            });



            /* =====================================================
               ENTER KEY SEARCH
            ====================================================== */

            $('#customer_phone_search')
                .on('keydown', function(e) {

                    if (e.key === 'Enter') {

                        e.preventDefault();

                        $('#searchCustomer')
                            .click();

                    }

                });



            /* =====================================================
               GENERATE ORDER ID
            ====================================================== */

            function generateOrderId() {

                let staffId =
                    $('#lead_staff_id').val();

                let createdAt =
                    $('#lead_created_at').val();


                if (!staffId || !createdAt) {

                    $('#lead_order_id')
                        .val('Auto Generate');

                    return;
                }


                $('#lead_order_id')
                    .val('Generating...');


                $.ajax({

                    url: "{{ url('/record/generate-order-id') }}/" +
                        staffId,

                    type: "GET",

                    data: {
                        created_at: createdAt
                    },


                    success: function(response) {

                        if (response.order_id) {

                            $('#lead_order_id')
                                .val(response.order_id);

                        } else {

                            $('#lead_order_id')
                                .val('Auto Generate');

                        }

                    },


                    error: function() {

                        $('#lead_order_id')
                            .val('Auto Generate');

                    }

                });

            }



            $('#lead_staff_id, #lead_created_at')
                .on('change', function() {

                    generateOrderId();

                });



            /* =====================================================
               CLIENT PRODUCTS
            ====================================================== */

            $('#lead_client_id')
                .on('change', function() {

                    let clientId =
                        $(this).val();


                    productOptions =
                        '<option value="">Select Product</option>';


                    $('.product-select')
                        .html(productOptions);


                    if (!clientId) {
                        return;
                    }


                    $.ajax({

                        url: "{{ url('/get-client-products') }}/" +
                            clientId,

                        type: "GET",


                        success: function(data) {

                            productOptions =
                                '<option value="">Select Product</option>';


                            data.forEach(function(item) {

                                productOptions += `

                            <option
                                value="${item.shopify_product_name}"
                                data-weight="${item.weight_per_unit}"
                            >
                                ${item.shopify_product_name}
                            </option>

                        `;

                            });


                            $('.product-select')
                                .html(productOptions);

                        },


                        error: function() {

                            alert(
                                'Unable to load client products.'
                            );

                        }

                    });

                });



            /* =====================================================
               CALL STATUS
            ====================================================== */

            $('#call_status')
                .on('change', function() {

                    let status =
                        $(this).val();


                    /* COMMON VALIDATION */

                    if (!status) {

                        $('#remarksSection')
                            .addClass('d-none');

                        $('#verifiedOrderSection')
                            .addClass('d-none');

                        return;
                    }


                    if (!$('#lead_client_id').val()) {

                        alert(
                            'Please select Client first.'
                        );

                        $(this).val('');

                        return;
                    }


                    if (!$('#lead_staff_id').val()) {

                        alert(
                            'Please select Staff first.'
                        );

                        $(this).val('');

                        return;
                    }


                    let orderId =
                        $('#lead_order_id').val();


                    if (
                        !orderId ||
                        orderId === 'Auto Generate' ||
                        orderId === 'Generating...'
                    ) {

                        alert(
                            'Order ID not generated.'
                        );

                        $(this).val('');

                        return;
                    }



                    /* RESET */

                    $('#remarksSection')
                        .addClass('d-none');


                    $('#verifiedOrderSection')
                        .addClass('d-none');


                    $('.verified-required')
                        .prop('required', false);


                    $('.product-select')
                        .prop('required', false);


                    $('.product-qty')
                        .prop('required', false);


                    $('#remarks')
                        .prop('required', false);



                    /* VERIFIED */

                    if (status === 'verified') {

                        $('#verifiedOrderSection')
                            .removeClass('d-none');


                        $('.verified-required')
                            .prop('required', true);


                        $('.product-select')
                            .prop('required', true);


                        $('.product-qty')
                            .prop('required', true);

                    }


                    /* OTHER STATUS */
                    else {

                        $('#remarksSection')
                            .removeClass('d-none');


                        $('#remarks')
                            .prop('required', true);

                    }

                });



            /* =====================================================
               PRODUCT CHANGE
            ====================================================== */

            $(document)
                .on(
                    'change',
                    '.product-select',
                    function() {

                        let weight =
                            parseFloat(
                                $(this)
                                .find(':selected')
                                .data('weight')
                            ) || 0;


                        $(this)
                            .closest('.product-row')
                            .find('.weight-input')
                            .val(weight);


                        calculateTotalWeight();

                    }
                );



            /* =====================================================
               QUANTITY CHANGE
            ====================================================== */

            $(document)
                .on(
                    'input',
                    '.product-qty',
                    function() {

                        calculateTotalWeight();

                    }
                );



            /* =====================================================
               TOTAL WEIGHT
            ====================================================== */

            function calculateTotalWeight() {

                let total = 0;


                $('#verifiedOrderSection .product-row')
                    .each(function() {


                        let qty =
                            parseInt(
                                $(this)
                                .find('.product-qty')
                                .val()
                            ) || 0;


                        let weight =
                            parseFloat(
                                $(this)
                                .find('.weight-input')
                                .val()
                            ) || 0;


                        total += qty * weight;

                    });


                $('#total_weight_display')
                    .val(total);

            }



            /* =====================================================
               ADD PRODUCT
            ====================================================== */

            $(document)
                .on(
                    'click',
                    '.add-product',
                    function() {


                        let html = `

                    <div class="row product-row mb-2">

                        <div class="col-md-6">

                            <select
                                name="products[${productIndex}][product]"
                                class="form-select product-select"
                            >

                                ${productOptions}

                            </select>

                        </div>


                        <div class="col-md-2">

                            <input
                                type="number"
                                name="products[${productIndex}][quantity]"
                                class="form-control product-qty"
                                value="1"
                                min="1"
                            >

                        </div>


                        <div class="col-md-3">

                            <input
                                type="number"
                                name="products[${productIndex}][weight]"
                                class="form-control weight-input"
                                readonly
                            >

                        </div>


                        <div class="col-md-1">

                            <button
                                type="button"
                                class="btn btn-danger remove-product"
                            >
                                ×
                            </button>

                        </div>

                    </div>

                `;


                        $('#product-wrapper')
                            .append(html);


                        if (
                            $('#call_status').val() === 'verified'
                        ) {

                            $('#product-wrapper')
                                .find('.product-select')
                                .last()
                                .prop('required', true);


                            $('#product-wrapper')
                                .find('.product-qty')
                                .last()
                                .prop('required', true);

                        }


                        productIndex++;

                    }
                );



            /* =====================================================
               REMOVE PRODUCT
            ====================================================== */

            $(document)
                .on(
                    'click',
                    '.remove-product',
                    function() {

                        $(this)
                            .closest('.product-row')
                            .remove();


                        calculateTotalWeight();

                    }
                );



            /* =====================================================
               CHANGE NUMBER
            ====================================================== */

            $('#changeNumber')
                .on('click', function() {


                    $('#customer_phone_search')
                        .prop('readonly', false)
                        .val('')
                        .focus();


                    $('#changeNumberBox')
                        .addClass('d-none');


                    $('#lead_customer_phone')
                        .val('');


                    $('#lead_client_id')
                        .val('');


                    $('#lead_staff_id')
                        .val('');


                    $('#lead_order_id')
                        .val('Auto Generate');


                    $('#call_status')
                        .val('');


                    $('#leadSection')
                        .addClass('d-none');


                    $('#remarksSection')
                        .addClass('d-none');


                    $('#verifiedOrderSection')
                        .addClass('d-none');


                    $('#remarks')
                        .val('')
                        .prop('required', false);


                    $('.verified-required')
                        .prop('required', false);


                    $('#historyCount')
                        .text('0 Orders');


                    $('#historyTable')
                        .html(`

                    <tr>

                        <td
                            colspan="8"
                            class="text-center text-muted py-4"
                        >
                            No customer searched.
                        </td>

                    </tr>

                `);

                });



            /* =====================================================
               FINAL FORM VALIDATION
            ====================================================== */

            $('#leadForm')
                .on('submit', function(e) {


                    let status =
                        $('#call_status').val();


                    if (!status) {

                        e.preventDefault();

                        alert(
                            'Please select Call Status.'
                        );

                        return false;
                    }


                    if (
                        status === 'verified'
                    ) {

                        let validProduct = true;


                        $('#verifiedOrderSection .product-row')
                            .each(function() {

                                let product =
                                    $(this)
                                    .find('.product-select')
                                    .val();

                                let qty =
                                    parseInt(
                                        $(this)
                                        .find('.product-qty')
                                        .val()
                                    ) || 0;


                                if (!product || qty <= 0) {

                                    validProduct = false;

                                }

                            });


                        if (!validProduct) {

                            e.preventDefault();

                            alert(
                                'Please select product and quantity.'
                            );

                            return false;
                        }

                    }

                });



            /* =====================================================
               EXCEL IMPORT LOADER
            ====================================================== */

            const excelImportForm =
                document.getElementById(
                    'excelImportForm'
                );


            if (excelImportForm) {

                excelImportForm
                    .addEventListener(
                        'submit',
                        function() {


                            const loader =
                                document.getElementById(
                                    'excelImportLoader'
                                );


                            const button =
                                document.getElementById(
                                    'importBtn'
                                );


                            if (loader) {

                                loader.classList
                                    .remove('d-none');

                            }


                            if (button) {

                                button.disabled = true;

                                button.innerText =
                                    'Importing...';

                            }

                        }
                    );

            }


        });
    </script>

@endsection
