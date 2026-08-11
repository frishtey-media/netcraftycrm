@extends('layouts.calling')

@section('title', 'Orders')

@section('content')
    <style>
        .order-card {
            border-radius: 12px;
            border: 1px solid #eee;
            background: #fff;
        }

        .customer-name {
            font-size: 15px;
        }

        .label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
            margin-top: 6px;
        }

        .value {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }

        .phone {
            color: #0d6efd;
            font-weight: 600;
            text-decoration: none;
        }

        .badge {
            font-size: 11px;
            padding: 5px 10px;
            border-radius: 6px;
        }


        .client-scroll {
            display: flex;
            overflow-x: auto;
            gap: 8px;
            padding-bottom: 5px;
        }

        /* SCROLLBAR HIDE */
        .client-scroll::-webkit-scrollbar {
            display: none;
        }

        /* CHIP STYLE */
        .client-chip {
            white-space: nowrap;
            padding: 6px 14px;
            border-radius: 20px;
            background: #f1f1f1;
            color: #333;
            font-size: 13px;
            text-decoration: none;
            border: 1px solid #ddd;
        }

        /* ACTIVE */
        .client-chip.active {
            background: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }
    </style>
    <!-- ================= DESKTOP TABLE ================= -->
    <div class="table-responsive d-none d-md-block">
        <div class="client-scroll mb-3">

            <a href="{{ route('calling.orderspending') }}" class="client-chip {{ request('client_id') ? '' : 'active' }}">
                All
            </a>

            @foreach ($clients as $row)
                <a href="{{ route('calling.orderspending', ['client_id' => $row->client_id]) }}"
                    class="client-chip {{ request('client_id') == $row->client_id ? 'active' : '' }}">

                    {{ $row->client->client_name ?? 'Client' }}
                    ({{ $row->total }})
                </a>
            @endforeach

        </div>
        <table id="ordersTable" class="table table-hover align-middle">

            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Order Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td>#{{ $order->order_id }}</td>

                        <td>
                            <strong>{{ $order->customer_name }}</strong><br>
                            <small class="text-muted">{{ $order->city }}, {{ $order->state }}</small>
                        </td>

                        <td>{{ $order->product_name }}</td>

                        <td>
                            <a href="tel:{{ $order->customer_phone }}" class="text-primary">
                                {{ $order->customer_phone }}
                            </a>
                        </td>

                        <td>
                            {{ $order->shipping_address }}<br>
                            <strong>{{ $order->pincode }}</strong>
                        </td>
                        <td>
                            {{ $order->order_date }}
                        </td>
                        <td>
                            @if ($order->status == 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @elseif($order->status == 'verified')
                                <span class="badge bg-success">Verified</span>
                            @else
                                <span class="badge bg-danger">Not Reachable</span>
                            @endif

                        </td>

                        <td>
                            <!-- STATUS UPDATE -->
                            <form method="POST" action="/calling/statusupdate/{{ $order->id }}">
                                @csrf
                                <div class="d-flex gap-1 mb-1">
                                    <select name="status" class="form-select form-select-sm">
                                        <option value="verified">Confirm</option>
                                        <option value="same_order">Same Order</option>
                                        <option value="not_reachable">Not Reachable</option>
                                        <option value="cancel">Cancel</option>

                                    </select>
                                    <button class="btn btn-success btn-sm">✔</button>
                                </div>
                            </form>

                            <!-- EDIT -->
                            <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                data-bs-target="#edit{{ $order->id }}">
                                ✏ Edit
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>

        </table>
    </div>


    <!-- ================= MOBILE VIEW ================= -->
    <div class="d-block d-md-none">
        <div class="client-scroll mb-3">

            <a href="{{ route('calling.orderspending') }}" class="client-chip {{ request('client_id') ? '' : 'active' }}">
                All
            </a>

            @foreach ($clients as $row)
                <a href="{{ route('calling.orderspending', ['client_id' => $row->client_id]) }}"
                    class="client-chip {{ request('client_id') == $row->client_id ? 'active' : '' }}">

                    {{ $row->client->client_name ?? 'Client' }}
                    ({{ $row->total }})
                </a>
            @endforeach

        </div>
        @foreach ($orders as $order)
            <div class="card order-card mb-3">

                <div class="card-body">

                    <!-- TOP -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-bold">#{{ $order->order_id }}</div>
                        {{ $order->order_date }}
                        @if ($order->status == 'pending')
                            <span class="badge bg-warning">Pending</span>
                        @elseif($order->status == 'verified')
                            <span class="badge bg-success">Verified</span>
                        @else
                            <span class="badge bg-danger">Not Reachable</span>
                        @endif
                    </div>

                    <!-- CUSTOMER -->
                    <div class="fw-semibold customer-name">
                        {{ $order->customer_name }}
                    </div>

                    <div class="text-muted small mb-2">
                        {{ $order->city }}, {{ $order->state }}
                    </div>

                    <!-- PRODUCT -->
                    <div class="label">Product</div>
                    <div class="value mb-2">
                        {{ $order->product_name }}
                    </div>
                    <div class="label">Payment Mode</div>
                    <div class="value mb-2">
                        {{ $order->payment_mode }}
                    </div>

                    <!-- PHONE -->
                    <div class="label">Mobile</div>
                    <div class="value mb-2">
                        <a href="tel:{{ $order->customer_phone }}" class="phone">
                            {{ $order->customer_phone }}
                        </a>
                    </div>

                    <!-- ADDRESS -->
                    <div class="label">Address</div>
                    <div class="value">
                        {{ $order->shipping_address }},
                        {{ $order->city }},
                        {{ $order->state }} - <strong>{{ $order->pincode }}</strong>
                    </div>

                    <!-- BUTTONS -->
                    <div class="d-flex gap-2 mt-3">

                        <a href="tel:{{ $order->customer_phone }}" class="btn btn-success btn-sm w-50">
                            📞 Call
                        </a>

                        <button class="btn btn-primary btn-sm w-50" data-bs-toggle="modal"
                            data-bs-target="#edit{{ $order->id }}">
                            ✏ Edit
                        </button>

                    </div>

                    <!-- STATUS UPDATE -->
                    <form method="POST" action="/calling/statusupdate/{{ $order->id }}">
                        @csrf

                        <div class="d-flex gap-2 mt-3">
                            <select name="status" class="form-select form-select-sm">
                                <option value="verified">Confirm</option>
                                <option value="same_order">Same Order</option>
                                <option value="not_reachable">Not Reachable</option>
                                <option value="cancel">Cancel</option>
                            </select>

                            <button class="btn btn-success btn-sm">✔</button>
                        </div>
                    </form>

                </div>
            </div>
        @endforeach

    </div>


    <!-- ================= MODALS ================= -->
    @foreach ($orders as $order)
        <div class="modal fade" id="edit{{ $order->id }}" tabindex="-1">

            <div class="modal-dialog" style="padding: 35px;">

                <div class="modal-content p-3">

                    <form method="POST" action="/calling/update/{{ $order->id }}" class="edit-order-form" novalidate>

                        @csrf



                        {{-- CUSTOMER NAME --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Customer Name <span class="text-danger">*</span>
                            </label>

                            <input type="text" name="customer_name" value="{{ $order->customer_name }}"
                                class="form-control english-field" placeholder="Enter customer name" required>
                        </div>


                        {{-- PHONE --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Customer Phone <span class="text-danger">*</span>
                            </label>

                            <input type="text" name="customer_phone" value="{{ $order->customer_phone }}"
                                class="form-control" inputmode="numeric" maxlength="10"
                                placeholder="Enter 10 digit phone number" required>
                        </div>


                        {{-- PRODUCT --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Product Name <span class="text-danger">*</span>
                            </label>

                            <input type="text" name="product_name" value="{{ $order->product_name }}"
                                class="form-control" placeholder="Enter Product Name" required>
                        </div>


                        {{-- FATHER NAME - OPTIONAL --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Customer Father Name
                            </label>

                            <input type="text" name="father_name" value="{{ $order->father_name }}"
                                class="form-control english-field" placeholder="Enter Father Name">
                        </div>


                        {{-- QUANTITY --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Quantity <span class="text-danger">*</span>
                            </label>

                            <input type="number" name="quantity" value="{{ $order->quantity }}" class="form-control"
                                min="1" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Payment Mode <span class="text-danger">*</span>
                            </label>

                            <select name="payment_mode" class="form-select" required>

                                <option value=""
                                    {{ empty($order->payment_mode) || $order->payment_mode == 'pending' ? 'selected' : '' }}>
                                    Pending
                                </option>

                                <option value="COD" {{ $order->payment_mode == 'COD' ? 'selected' : '' }}>
                                    COD
                                </option>

                                <option value="Prepaid" {{ $order->payment_mode == 'Prepaid' ? 'selected' : '' }}>
                                    Prepaid
                                </option>

                            </select>

                            <div class="invalid-feedback">
                                Please select payment mode.
                            </div>
                        </div>
                        {{-- PRICE --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Price <span class="text-danger">*</span>
                            </label>

                            <input type="number" name="amount" value="{{ $order->amount }}" class="form-control"
                                step="0.01" min="0.01" required>
                        </div>


                        {{-- AGE --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Age <span class="text-danger">*</span>
                            </label>

                            <input type="number" name="age" value="{{ $order->age }}" class="form-control"
                                min="1" max="120" required>
                        </div>


                        {{-- CITY --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                City <span class="text-danger">*</span>
                            </label>

                            <input type="text" name="city" value="{{ $order->city }}"
                                class="form-control english-field" placeholder="Enter city" required>
                        </div>


                        {{-- STATE --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                State <span class="text-danger">*</span>
                            </label>

                            <input type="text" name="state" value="{{ $order->state }}"
                                class="form-control english-field" placeholder="Enter state" required>
                        </div>


                        {{-- PINCODE --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Pincode <span class="text-danger">*</span>
                            </label>

                            <input type="text" name="pincode" value="{{ $order->pincode }}" class="form-control"
                                inputmode="numeric" maxlength="6" placeholder="Enter 6 digit pincode" required>
                        </div>


                        {{-- SHIPPING ADDRESS --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold">
                                Shipping Address <span class="text-danger">*</span>
                            </label>

                            <textarea name="shipping_address" class="form-control english-field" rows="4"
                                placeholder="Enter shipping address" required>{{ $order->shipping_address }}</textarea>
                        </div>


                        <button type="submit" class="btn btn-success w-100">

                            Update Order

                        </button>

                    </form>

                </div>

            </div>

        </div>
    @endforeach

@endsection


@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const forms = document.querySelectorAll('.edit-order-form');

            forms.forEach(function(form) {

                // ==========================================
                // CREATE FEEDBACK ELEMENTS AUTOMATICALLY
                // ==========================================
                form.querySelectorAll('input, textarea, select').forEach(function(field) {

                    if (!field.name || field.type === 'hidden') {
                        return;
                    }

                    let feedback = field.parentElement.querySelector('.invalid-feedback');

                    if (!feedback) {

                        feedback = document.createElement('div');

                        feedback.className = 'invalid-feedback';

                        field.parentElement.appendChild(feedback);
                    }

                });


                // ==========================================
                // SHOW ERROR
                // ==========================================
                function showError(field, message) {

                    field.classList.add('is-invalid');
                    field.classList.remove('is-valid');

                    const feedback =
                        field.parentElement.querySelector('.invalid-feedback');

                    if (feedback) {
                        feedback.textContent = message;
                    }

                    return false;
                }


                // ==========================================
                // SHOW VALID
                // ==========================================
                function showValid(field) {

                    field.classList.remove('is-invalid');
                    field.classList.add('is-valid');

                    const feedback =
                        field.parentElement.querySelector('.invalid-feedback');

                    if (feedback) {
                        feedback.textContent = '';
                    }

                    return true;
                }


                // ==========================================
                // ENGLISH VALIDATION
                // ==========================================

                // Names
                const nameRegex =
                    /^[A-Za-z0-9\s.,\/\-()]+$/;

                // City / State
                const cityStateRegex =
                    /^[A-Za-z\s.\-]+$/;

                // Address
                const addressRegex =
                    /^[A-Za-z0-9\s.,\/\-#()]+$/;


                // ==========================================
                // VALIDATE SINGLE FIELD
                // ==========================================
                function validateField(field) {

                    const name = field.name;

                    const value =
                        typeof field.value === 'string' ?
                        field.value.trim() :
                        field.value;


                    // ======================================
                    // FATHER NAME
                    // OPTIONAL
                    // ======================================
                    if (name === 'father_name') {

                        if (value === '') {

                            field.classList.remove(
                                'is-invalid',
                                'is-valid'
                            );

                            return true;
                        }

                        if (!nameRegex.test(value)) {

                            return showError(
                                field,
                                'Father name must be entered in English only.'
                            );
                        }

                        return showValid(field);
                    }

                    if (name === 'payment_mode') {

                        if (value !== 'COD' && value !== 'Prepaid') {

                            return showError(
                                field,
                                'Please select COD or Prepaid.'
                            );
                        }

                        return showValid(field);
                    }
                    // ======================================
                    // ALL OTHER FIELDS REQUIRED
                    // ======================================
                    if (value === '') {

                        return showError(
                            field,
                            'This field is required.'
                        );
                    }


                    // ======================================
                    // CUSTOMER NAME
                    // ======================================
                    if (name === 'customer_name') {

                        if (!nameRegex.test(value)) {

                            return showError(
                                field,
                                'Customer name must be entered in English only.'
                            );
                        }

                        return showValid(field);
                    }


                    // ======================================
                    // PHONE
                    // ======================================
                    if (name === 'customer_phone') {

                        if (!/^[0-9]{10}$/.test(value)) {

                            return showError(
                                field,
                                'Phone number must contain exactly 10 digits.'
                            );
                        }

                        return showValid(field);
                    }


                    // ======================================
                    // PRODUCT
                    // ======================================
                    if (name === 'product_name') {

                        if (value.length > 255) {

                            return showError(
                                field,
                                'Product name is too long.'
                            );
                        }

                        return showValid(field);
                    }


                    // ======================================
                    // QUANTITY
                    // ======================================
                    if (name === 'quantity') {

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


                    // ======================================
                    // AMOUNT
                    // ======================================
                    if (name === 'amount') {

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


                    // ======================================
                    // AGE
                    // ======================================
                    if (name === 'age') {

                        const age = Number(value);

                        if (
                            !Number.isInteger(age) ||
                            age < 1 ||
                            age > 120
                        ) {

                            return showError(
                                field,
                                'Age must be between 1 and 120.'
                            );
                        }

                        return showValid(field);
                    }


                    // ======================================
                    // CITY
                    // ======================================
                    if (name === 'city') {

                        if (!cityStateRegex.test(value)) {

                            return showError(
                                field,
                                'City must be entered in English only.'
                            );
                        }

                        return showValid(field);
                    }


                    // ======================================
                    // STATE
                    // ======================================
                    if (name === 'state') {

                        if (!cityStateRegex.test(value)) {

                            return showError(
                                field,
                                'State must be entered in English only.'
                            );
                        }

                        return showValid(field);
                    }


                    // ======================================
                    // PINCODE
                    // ======================================
                    if (name === 'pincode') {

                        if (!/^[0-9]{6}$/.test(value)) {

                            return showError(
                                field,
                                'Pincode must contain exactly 6 digits.'
                            );
                        }

                        return showValid(field);
                    }


                    // ======================================
                    // SHIPPING ADDRESS
                    // ======================================
                    if (name === 'shipping_address') {

                        if (!addressRegex.test(value)) {

                            return showError(
                                field,
                                'Shipping address must be entered in English only.'
                            );
                        }

                        if (value.length > 1000) {

                            return showError(
                                field,
                                'Shipping address is too long.'
                            );
                        }

                        return showValid(field);
                    }


                    return showValid(field);
                }



                // ==========================================
                // REAL-TIME VALIDATION
                // ==========================================
                form.querySelectorAll(
                    'input:not([type="hidden"]), textarea, select'
                ).forEach(function(field) {

                    field.addEventListener('input', function() {

                        validateField(this);

                    });

                    field.addEventListener('change', function() {

                        validateField(this);

                    });

                    field.addEventListener('blur', function() {

                        validateField(this);

                    });

                });



                // ==========================================
                // PHONE - NUMBERS ONLY
                // ==========================================
                const phone =
                    form.querySelector('[name="customer_phone"]');

                if (phone) {

                    phone.addEventListener('input', function() {

                        this.value =
                            this.value
                            .replace(/\D/g, '')
                            .slice(0, 10);

                        validateField(this);

                    });

                }



                // ==========================================
                // PINCODE - NUMBERS ONLY
                // ==========================================
                const pincode =
                    form.querySelector('[name="pincode"]');

                if (pincode) {

                    pincode.addEventListener('input', function() {

                        this.value =
                            this.value
                            .replace(/\D/g, '')
                            .slice(0, 6);

                        validateField(this);

                    });

                }



                // ==========================================
                // PREVENT INVALID FORM SUBMISSION
                // ==========================================
                form.addEventListener('submit', function(event) {

                    let formValid = true;

                    let firstInvalid = null;


                    const fields =
                        form.querySelectorAll(
                            'input:not([type="hidden"]), textarea, select'
                        );


                    fields.forEach(function(field) {

                        if (!field.name) {
                            return;
                        }


                        const valid =
                            validateField(field);


                        if (!valid) {

                            formValid = false;

                            if (!firstInvalid) {
                                firstInvalid = field;
                            }

                        }

                    });


                    // ======================================
                    // INVALID
                    // ======================================
                    if (!formValid) {

                        event.preventDefault();

                        event.stopPropagation();


                        if (firstInvalid) {

                            firstInvalid.focus();

                            firstInvalid.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });

                        }

                        return false;
                    }


                    // ======================================
                    // VALID
                    // Prevent double click
                    // ======================================
                    const button =
                        form.querySelector(
                            'button[type="submit"]'
                        );

                    if (button) {

                        button.disabled = true;

                        button.innerHTML =
                            '<span class="spinner-border spinner-border-sm me-2"></span> Updating...';
                    }

                });

            });

        });
    </script>

    <script>
        $(document).ready(function() {

            let table = $('#ordersTable');

            if (table.length) {

                if ($.fn.DataTable.isDataTable(table)) {
                    table.DataTable().destroy();
                }

                table.DataTable({
                    pageLength: 10,
                    searching: true,
                    ordering: true,
                    responsive: true
                });
            }

        });
    </script>
@endpush


<style>
    .card {
        transition: 0.3s;
    }

    .card:hover {
        transform: translateY(-3px);
    }
</style>
