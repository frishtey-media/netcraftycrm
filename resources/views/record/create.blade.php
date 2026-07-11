@extends('layouts.admin')

@section('content')
    <style>
        .loader-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.9);
            z-index: 1056;
            /* above bootstrap modal */
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
            border-top: 5px solid #198754;
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



        @if (session('success'))
            <div class="alert alert-success">
                <strong>{{ session('success') }}</strong><br>

            </div>
        @endif

        @if (session('errors') && count(session('errors')) > 0)
            <div class="alert alert-danger">
                <strong>Import Errors:</strong>
                <ul class="mb-0">
                    @foreach (session('errors') as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#bulkImportModal">

            <i class="bi bi-upload"></i>
            Bulk Order Import

        </button>
        <div class="modal fade" id="bulkImportModal" tabindex="-1">

            <div class="modal-dialog">

                <div class="modal-content">

                    <form method="POST" action="{{ route('record.whstappimport') }}" enctype="multipart/form-data">

                        @csrf

                        <div class="modal-header">

                            <h5 class="modal-title">
                                Bulk Order Import
                            </h5>

                            <button type="button" class="btn-close" data-bs-dismiss="modal">
                            </button>

                        </div>

                        <div class="modal-body">

                            {{-- Client --}}
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

                                <select name="client_id" class="form-control" required>

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

                            {{-- Staff --}}

                            <div class="mb-3">

                                <label class="form-label">
                                    Assign Staff
                                </label>

                                <select name="assigned_to" class="form-control" required>

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

                            {{-- Excel --}}

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

                            <button type="submit" class="btn btn-success">

                                Import Orders

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>




        <form method="POST" action="{{ route('record.store') }}">
            @csrf

            <div class="row g-3">


                {{-- Date --}}
                <div class="col-md-4">
                    <label class="form-label">Import Date</label>

                    <input type="date" name="created_at" class="form-control"
                        value="{{ old('created_at', date('Y-m-d')) }}" required>
                </div>
                {{-- Client --}}
                <div class="col-md-4">
                    <label class="form-label">Client Name</label>
                    <select name="client_id" id="client_id" class="form-control" required>

                        <option value="">Select Client</option>

                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">
                                {{ $client->client_name }}
                            </option>
                        @endforeach

                    </select>
                </div>

                {{-- Assign Staff --}}
                <div class="col-md-4">
                    <label class="form-label">Assign Staff</label>

                    <select name="assigned_to" id="assigned_to" class="form-control" required>

                        <option value="">Select Staff</option>

                        @foreach ($staffs as $staff)
                            <option value="{{ $staff->id }}">
                                {{ $staff->name }}
                            </option>
                        @endforeach

                    </select>
                </div>
                {{-- Order ID --}}
                <div class="col-md-4">
                    <label class="form-label">Order ID</label>
                    <input type="text" name="order_id" id="order_id" class="form-control" readonly
                        value="Auto Generate">
                </div>


                {{-- Product --}}
                <div class="col-md-4">
                    <label class="form-label">Product</label>

                    <div id="product-wrapper">

                        <div class="row product-row mb-2">

                            <div class="col-md-5">
                                <select name="products[0][product]" class="form-control product-select" required>
                                    <option value="">Select Product</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <input type="number" name="products[0][quantity]" class="form-control" placeholder="Qty"
                                    value="1" min="1" required>
                            </div>

                            <div class="col-md-3">
                                <input type="number" name="products[0][weight]" class="form-control weight-input"
                                    readonly>
                            </div>

                            <div class="col-md-1">
                                <button type="button" class="btn btn-success add-product">
                                    +
                                </button>
                            </div>

                        </div>

                    </div>
                </div>

                {{-- Quantity --}}
                <div class="col-md-4">
                    <label class="form-label">Quantity</label>

                    <input type="number" name="quantity" id="quantity" class="form-control" value="1"
                        min="1">
                </div>

                {{-- Weight --}}
                <div class="col-md-4">
                    <label class="form-label">Weight (GM)</label>

                    <input type="number" name="weight_in_gm" id="weight_in_gm" class="form-control" readonly>
                </div>

                {{-- Amount --}}
                <div class="col-md-4">
                    <label class="form-label">Amount</label>

                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>

                {{-- Payment --}}
                <div class="col-md-4">
                    <label class="form-label">Payment Mode</label>

                    <select name="payment_mode" class="form-control" required>

                        <option value="">Select</option>
                        <option value="VPP">VPP</option>
                        <option value="COD">COD</option>
                        <option value="Prepaid">Prepaid</option>

                    </select>
                </div>

                {{-- Customer Name --}}
                <div class="col-md-4">
                    <label class="form-label">Customer Name</label>

                    <input type="text" name="customer_name" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Age</label>

                    <input type="number" name="age" class="form-control" required>
                </div>
                {{-- Father Name --}}
                <div class="col-md-4">
                    <label class="form-label">Father Name</label>

                    <input type="text" name="father_name" class="form-control">
                </div>

                {{-- Phone --}}
                <div class="col-md-3">
                    <label class="form-label">Customer Phone</label>

                    <input type="text" name="customer_phone" class="form-control" required>
                </div>

                {{-- City --}}
                <div class="col-md-3">
                    <label class="form-label">City</label>

                    <input type="text" name="city" class="form-control">
                </div>

                {{-- State --}}
                <div class="col-md-3">
                    <label class="form-label">State</label>

                    <input type="text" name="state" class="form-control">
                </div>

                {{-- Pincode --}}
                <div class="col-md-3">
                    <label class="form-label">Shipping Pincode</label>

                    <input type="text" name="shipping_pincode" class="form-control" required>
                </div>

                {{-- Address 1 --}}
                <div class="col-md-6">
                    <label class="form-label">
                        Shipping Address Line 1
                    </label>

                    <textarea name="shipping_address_line1" class="form-control" rows="3" required></textarea>
                </div>

                {{-- Address 2 --}}
                <div class="col-md-6">
                    <label class="form-label">
                        Shipping Address Line 2
                    </label>

                    <textarea name="shipping_address_line2" class="form-control" rows="3"></textarea>
                </div>

            </div>

            <button type="submit" class="btn btn-success mt-4">
                Save Order
            </button>
        </form>
    </div>

    <script>
        document.getElementById('excelImportForm').addEventListener('submit', function() {
            document.getElementById('excelImportLoader').classList.remove('d-none');


            document.getElementById('importBtn').disabled = true;
        });
    </script>
    <script>
        function generateOrderId() {

            let staffId = $('#assigned_to').val();
            let createdAt = $('input[name="created_at"]').val();

            if (!staffId || !createdAt) {

                $('#order_id').val('Auto Generate');
                return;
            }

            $.get('/generate-order-id/' + staffId, {
                created_at: createdAt
            }, function(response) {
                $('#order_id').val(response.order_id);
            });

        }

        $('#assigned_to').change(generateOrderId);

        $('input[name="created_at"]').change(generateOrderId);
    </script>
    <script>
        let productOptions = '';
        let rowIndex = 1;

        $('#client_id').change(function() {

            let clientId = $(this).val();

            $.get('/get-client-products/' + clientId, function(data) {

                productOptions =
                    '<option value="">Select Product</option>';

                data.forEach(function(item) {

                    productOptions += `
                <option
                    value="${item.shopify_product_name}"
                    data-weight="${item.weight_per_unit}">
                    ${item.shopify_product_name}
                </option>
            `;
                });

                $('.product-select').html(productOptions);
            });
        });


        $(document).on('click', '.add-product', function() {

            let html = `
        <div class="row product-row mb-2">

            <div class="col-md-5">
                <select name="products[${rowIndex}][product]"
                    class="form-control product-select"
                    required>
                    ${productOptions}
                </select>
            </div>

            <div class="col-md-3">
                <input type="number"
                    name="products[${rowIndex}][quantity]"
                    class="form-control"
                    value="1"
                    min="1"
                    required>
            </div>

            <div class="col-md-3">
                <input type="number"
                    name="products[${rowIndex}][weight]"
                    class="form-control weight-input"
                    readonly>
            </div>

            <div class="col-md-1">
                <button type="button"
                    class="btn btn-danger remove-product">
                    ×
                </button>
            </div>

        </div>
    `;

            $('#product-wrapper').append(html);

            rowIndex++;
        });


        $(document).on('click', '.remove-product', function() {
            $(this).closest('.product-row').remove();
        });


        $(document).on('change', '.product-select', function() {

            let weight = $(this).find(':selected').data('weight');

            $(this)
                .closest('.product-row')
                .find('.weight-input')
                .val(weight || 0);
        });
    </script>

    <div id="excelImportLoader" class="d-none">
        <div class="loader-backdrop">
            <div class="loader-box">
                <div class="spinner"></div>
                <p>Importing WhatsApp Excel…</p>
                <small>Please wait, do not refresh</small>
            </div>
        </div>
    </div>
@endsection
