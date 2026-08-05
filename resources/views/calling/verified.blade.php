@extends('layouts.calling')

@section('title', 'Verified Orders')


@section('content')

    <style>
        .client-scroll {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 5px;
        }

        .client-chip {
            padding: 6px 14px;
            border-radius: 20px;
            background: #f1f1f1;
            text-decoration: none;
            color: #333;
            white-space: nowrap;
            font-size: 13px;
            transition: 0.3s;
        }

        .client-chip.active {
            background: #0d6efd;
            color: #fff;
        }

        .client-chip:hover {
            background: #0d6efd;
            color: #fff;
        }

        .order-card {
            border-radius: 12px;
            border: 1px solid #eee;
        }

        .label {
            font-size: 11px;
            color: #999;
            font-weight: 600;
        }

        .value {
            font-size: 14px;
            font-weight: 500;
            word-break: break-word;
        }

        .modal-dialog {
            max-width: 500px;
        }

        @media (max-width: 768px) {

            .card-body {
                font-size: 14px;
            }

            .modal-dialog {
                padding: 15px !important;
                margin: auto;
            }

        }
    </style>



    {{-- ============================================ --}}
    {{-- CLIENT FILTER --}}
    {{-- ============================================ --}}

    <div class="client-scroll mb-3">

        {{-- ALL --}}
        <a href="{{ route('calling.verified') }}" class="client-chip {{ request('client_id') ? '' : 'active' }}">

            All ({{ $statusCount }})

        </a>


        {{-- CLIENTS --}}
        @foreach ($clients as $row)
            <a href="{{ route('calling.verified', [
                'client_id' => $row->client_id,
            ]) }}"
                class="client-chip
           {{ request('client_id') == $row->client_id ? 'active' : '' }}">

                {{ $row->client->client_name ?? 'Client' }}

                ({{ $row->total }})
            </a>
        @endforeach

    </div>



    {{-- ============================================ --}}
    {{-- DESKTOP TABLE --}}
    {{-- ============================================ --}}

    <div class="table-responsive d-none d-md-block">

        <table id="ordersTable" class="table table-hover align-middle">

            <thead class="table-dark">

                <tr>

                    <th>#</th>

                    <th>Customer</th>

                    <th>Product</th>

                    <th>Phone</th>

                    <th>Location</th>

                    <th>Address</th>

                    <th>Qty</th>

                    <th>Payment</th>

                    <th>Price</th>

                    <th>Status</th>

                    <th>Action</th>

                </tr>

            </thead>


            <tbody>

                @foreach ($orders as $order)
                    <tr>

                        {{-- ORDER ID --}}
                        <td>

                            #{{ $order->order_id }}

                        </td>


                        {{-- CUSTOMER --}}
                        <td>

                            <strong>
                                {{ $order->customer_name }}
                            </strong>

                            <br>

                            <small class="text-muted">

                                {{ $order->city }},
                                {{ $order->state }}

                            </small>

                        </td>


                        {{-- PRODUCT --}}
                        <td>

                            {{ $order->product_name }}

                        </td>


                        {{-- PHONE --}}
                        <td>

                            <a href="tel:{{ $order->customer_phone }}">

                                {{ $order->customer_phone }}

                            </a>

                        </td>


                        {{-- LOCATION --}}
                        <td>

                            {{ $order->city }}

                            <br>

                            <strong>
                                {{ $order->pincode }}
                            </strong>

                        </td>


                        {{-- ADDRESS --}}
                        <td style="max-width:250px;">

                            {{ $order->shipping_address }}

                        </td>


                        {{-- QUANTITY --}}
                        <td>

                            {{ $order->quantity }}

                        </td>


                        {{-- PAYMENT --}}
                        <td>

                            @if ($order->payment_mode == 'COD')
                                <span class="badge bg-primary">
                                    COD
                                </span>
                            @elseif($order->payment_mode == 'Prepaid')
                                <span class="badge bg-success">
                                    Prepaid
                                </span>
                            @else
                                <span class="badge bg-warning text-dark">
                                    Pending
                                </span>
                            @endif

                        </td>


                        {{-- AMOUNT --}}
                        <td>

                            ₹{{ $order->amount }}

                        </td>


                        {{-- STATUS --}}
                        <td>

                            <span class="badge bg-success">

                                Verified

                            </span>

                        </td>


                        {{-- ACTION --}}
                        <td>

                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#edit{{ $order->id }}">

                                ✏ Edit

                            </button>

                        </td>

                    </tr>
                @endforeach

            </tbody>

        </table>

    </div>



    {{-- ============================================ --}}
    {{-- MOBILE CARDS --}}
    {{-- ============================================ --}}

    <div class="d-block d-md-none">

        @foreach ($orders as $order)
            <div class="card order-card mb-3">

                <div class="card-body">


                    {{-- TOP --}}

                    <div class="d-flex justify-content-between
                            align-items-center mb-2">

                        <strong>

                            #{{ $order->order_id }}

                        </strong>


                        <span class="badge bg-success">

                            Verified

                        </span>

                    </div>



                    {{-- CUSTOMER --}}

                    <div class="fw-semibold">

                        {{ $order->customer_name }}

                    </div>


                    <small class="text-muted">

                        {{ $order->city }},
                        {{ $order->state }}

                    </small>



                    {{-- DETAILS --}}

                    <div class="mt-3">

                        <div class="row g-2">


                            {{-- PRODUCT --}}

                            <div class="col-4 label">
                                Product
                            </div>

                            <div class="col-8 value">
                                {{ $order->product_name }}
                            </div>



                            {{-- MOBILE --}}

                            <div class="col-4 label">
                                Mobile
                            </div>

                            <div class="col-8 value">

                                <a href="tel:{{ $order->customer_phone }}">

                                    {{ $order->customer_phone }}

                                </a>

                            </div>



                            {{-- QUANTITY --}}

                            <div class="col-4 label">
                                Quantity
                            </div>

                            <div class="col-8 value">

                                {{ $order->quantity }}

                            </div>



                            {{-- PAYMENT --}}

                            <div class="col-4 label">
                                Payment
                            </div>

                            <div class="col-8 value">

                                @if ($order->payment_mode == 'COD')
                                    <span class="badge bg-primary">
                                        COD
                                    </span>
                                @elseif($order->payment_mode == 'Prepaid')
                                    <span class="badge bg-success">
                                        Prepaid
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark">
                                        Pending
                                    </span>
                                @endif

                            </div>



                            {{-- PRICE --}}

                            <div class="col-4 label">
                                Price
                            </div>

                            <div class="col-8 value">

                                ₹{{ $order->amount }}

                            </div>



                            {{-- CITY --}}

                            <div class="col-4 label">
                                City
                            </div>

                            <div class="col-8 value">

                                {{ $order->city }}

                            </div>



                            {{-- STATE --}}

                            <div class="col-4 label">
                                State
                            </div>

                            <div class="col-8 value">

                                {{ $order->state }}

                            </div>



                            {{-- PINCODE --}}

                            <div class="col-4 label">
                                Pincode
                            </div>

                            <div class="col-8 value">

                                {{ $order->pincode }}

                            </div>



                            {{-- ADDRESS --}}

                            <div class="col-4 label">
                                Address
                            </div>

                            <div class="col-8 value">

                                {{ $order->shipping_address }}

                            </div>

                        </div>

                    </div>



                    {{-- BUTTONS --}}

                    <div class="d-flex gap-2 mt-3">


                        <a href="tel:{{ $order->customer_phone }}" class="btn btn-success btn-sm w-50">

                            📞 Call

                        </a>


                        <button type="button" class="btn btn-primary btn-sm w-50" data-bs-toggle="modal"
                            data-bs-target="#edit{{ $order->id }}">

                            ✏ Edit

                        </button>


                    </div>

                </div>

            </div>
        @endforeach

    </div>



    {{-- ============================================ --}}
    {{-- EDIT MODALS --}}
    {{-- IMPORTANT: ONLY ONE LOOP --}}
    {{-- ============================================ --}}

    @foreach ($orders as $order)
        <div class="modal fade" id="edit{{ $order->id }}" tabindex="-1" aria-hidden="true">


            <div class="modal-dialog modal-dialog-centered">

                <div class="modal-content">


                    {{-- HEADER --}}

                    <div class="modal-header">

                        <h5 class="modal-title">

                            Edit Order
                            #{{ $order->order_id }}

                        </h5>


                        <button type="button" class="btn-close" data-bs-dismiss="modal">
                        </button>

                    </div>



                    {{-- FORM --}}

                    <form method="POST" action="/calling/update1/{{ $order->id }}" class="edit-order-form" novalidate>

                        @csrf


                        <div class="modal-body">


                            {{-- QUANTITY --}}

                            <div class="mb-3">

                                <label class="form-label fw-bold">

                                    Quantity

                                    <span class="text-danger">*</span>

                                </label>


                                <input type="number" name="quantity" value="{{ $order->quantity }}" class="form-control"
                                    min="1" step="1" required>


                                <div class="invalid-feedback">

                                    Quantity must be at least 1.

                                </div>

                            </div>



                            {{-- PAYMENT MODE --}}

                            <div class="mb-3">

                                <label class="form-label fw-bold">

                                    Payment Mode

                                    <span class="text-danger">*</span>

                                </label>


                                <select name="payment_mode" class="form-select" required>


                                    {{-- PENDING --}}

                                    <option value=""
                                        {{ empty($order->payment_mode) || strtolower($order->payment_mode) == 'pending' ? 'selected' : '' }}>

                                        Pending

                                    </option>


                                    {{-- COD --}}

                                    <option value="COD" {{ $order->payment_mode == 'COD' ? 'selected' : '' }}>

                                        COD

                                    </option>


                                    {{-- PREPAID --}}

                                    <option value="Prepaid" {{ $order->payment_mode == 'Prepaid' ? 'selected' : '' }}>

                                        Prepaid

                                    </option>


                                </select>


                                <div class="invalid-feedback">

                                    Please select COD or Prepaid.

                                </div>

                            </div>



                            {{-- PRICE --}}

                            <div class="mb-3">

                                <label class="form-label fw-bold">

                                    Price

                                    <span class="text-danger">*</span>

                                </label>


                                <input type="number" name="amount" value="{{ $order->amount }}" class="form-control"
                                    min="0.01" step="0.01" required>


                                <div class="invalid-feedback">

                                    Price must be greater than 0.

                                </div>

                            </div>


                        </div>



                        {{-- FOOTER --}}

                        <div class="modal-footer">


                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">

                                Cancel

                            </button>


                            <button type="submit" class="btn btn-success update-btn">

                                Update Order

                            </button>


                        </div>


                    </form>

                </div>

            </div>

        </div>
    @endforeach


    {{-- ============================================ --}}
    {{-- PAGINATION --}}
    {{-- ============================================ --}}

    @if (method_exists($orders, 'links'))
        <div class="mt-3">

            {{ $orders->links() }}

        </div>
    @endif


@endsection



{{-- ============================================ --}}
{{-- SCRIPTS --}}
{{-- ============================================ --}}

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {


            // ============================================
            // VALIDATION HELPERS
            // ============================================

            function showError(field, message) {
                field.classList.add('is-invalid');

                field.classList.remove('is-valid');


                const feedback =
                    field.parentElement.querySelector(
                        '.invalid-feedback'
                    );


                if (feedback) {

                    feedback.textContent = message;

                }


                return false;
            }



            function showValid(field) {
                field.classList.remove('is-invalid');

                field.classList.add('is-valid');


                return true;
            }



            // ============================================
            // VALIDATE FIELD
            // ============================================

            function validateField(field) {
                const name = field.name;

                const value = field.value.trim();



                // ========================================
                // QUANTITY
                // ========================================

                if (name === 'quantity') {

                    if (value === '') {
                        return showError(
                            field,
                            'Quantity is required.'
                        );
                    }


                    const quantity = Number(value);


                    if (
                        !Number.isInteger(quantity) ||
                        quantity < 1
                    ) {
                        return showError(
                            field,
                            'Quantity must be at least 1.'
                        );
                    }


                    return showValid(field);
                }



                // ========================================
                // PAYMENT MODE
                // ========================================

                if (name === 'payment_mode') {

                    if (
                        value !== 'COD' &&
                        value !== 'Prepaid'
                    ) {
                        return showError(
                            field,
                            'Please select COD or Prepaid.'
                        );
                    }


                    return showValid(field);
                }



                // ========================================
                // AMOUNT
                // ========================================

                if (name === 'amount') {

                    if (value === '') {
                        return showError(
                            field,
                            'Price is required.'
                        );
                    }


                    const amount = Number(value);


                    if (
                        !Number.isFinite(amount) ||
                        amount <= 0
                    ) {
                        return showError(
                            field,
                            'Price must be greater than 0.'
                        );
                    }


                    return showValid(field);
                }


                return true;
            }



            // ============================================
            // EACH EDIT FORM
            // ============================================

            document
                .querySelectorAll('.edit-order-form')
                .forEach(function(form) {


                    const fields =
                        form.querySelectorAll(
                            '[name="quantity"],' +
                            '[name="payment_mode"],' +
                            '[name="amount"]'
                        );



                    // ====================================
                    // REAL-TIME VALIDATION
                    // ====================================

                    fields.forEach(function(field) {


                        field.addEventListener(
                            'input',
                            function() {
                                validateField(this);
                            }
                        );


                        field.addEventListener(
                            'change',
                            function() {
                                validateField(this);
                            }
                        );


                        field.addEventListener(
                            'blur',
                            function() {
                                validateField(this);
                            }
                        );


                    });



                    // ====================================
                    // FORM SUBMIT
                    // ====================================

                    form.addEventListener(
                        'submit',
                        function(event) {

                            let isValid = true;

                            let firstInvalid = null;



                            fields.forEach(
                                function(field) {

                                    if (!validateField(field)) {

                                        isValid = false;


                                        if (!firstInvalid) {
                                            firstInvalid = field;
                                        }

                                    }

                                }
                            );



                            // ==============================
                            // INVALID
                            // ==============================

                            if (!isValid) {

                                event.preventDefault();

                                event.stopPropagation();


                                if (firstInvalid) {
                                    firstInvalid.focus();
                                }


                                return false;
                            }



                            // ==============================
                            // VALID
                            // Prevent double click
                            // ==============================

                            const button =
                                form.querySelector(
                                    '.update-btn'
                                );


                            if (button) {

                                button.disabled = true;


                                button.innerHTML =
                                    '<span class="spinner-border ' +
                                    'spinner-border-sm me-2"></span>' +
                                    'Updating...';

                            }

                        }
                    );


                });


        });
    </script>



    {{-- DATATABLE --}}

    <script>
        $(document).ready(function() {

            if ($('#ordersTable').length) {

                $('#ordersTable').DataTable({

                    pageLength: 10,

                    responsive: true,

                    order: []

                });

            }

        });
    </script>
@endpush
