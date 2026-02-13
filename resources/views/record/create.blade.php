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
                Imported: {{ session('imported') }}<br>
                Skipped: {{ session('skipped') }}
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


        <form method="POST" action="{{ route('record.store') }}">
            @csrf

            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Order ID</label>
                    <input type="text" name="order_id" class="form-control" value="{{ $orderId }}" readonly>
                </div>

                <div class="col-md-4">

                    <label class="form-label">Date</label>
                    <input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}">

                </div>
                <div class="col-md-4">
                    <label class="form-label">Barcode *</label>
                    <select name="barcode" class="form-control" required>
                        <option value="">Select Barcode</option>
                        @foreach ($barcodes as $barcode)
                            <option value="{{ $barcode }}">{{ $barcode }}</option>
                        @endforeach
                    </select>
                </div>



                <div class="col-md-4">
                    <label class="form-label">Client Name</label>
                    <select name="client_id" id="client_id" class="form-control">
                        <option value="">Select Client</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->client_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Product</label>
                    <select name="product" id="product" class="form-control">
                        <option value="">Select Product</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" value="1">

                </div>
                <div class="col-md-4">
                    <label class="form-label">Weight (in GM)</label>
                    <input type="number" name="weight_in_gm" id="weight_in_gm" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" name="amount" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payment Mode *</label>
                    <select name="payment_mode" class="form-control">
                        <option value="">Select</option>
                        <option>COD</option>
                        <option>Prepaid</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Customer Name *</label>
                    <input type="text" name="customer_name" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Father Name</label>
                    <input type="text" name="father_name" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Customer Phone *</label>
                    <input type="text" name="customer_phone" class="form-control">
                </div>


                <div class="col-md-4">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">State</label>
                    <input type="text" name="state" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Shipping Pincode *</label>
                    <input type="text" name="shipping_pincode" class="form-control">
                </div>



                <div class="col-md-6">
                    <label class="form-label">Shipping Address Line 1 *</label>

                    <textarea name="shipping_address_line1" class="form-control">   </textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Shipping Address Line 2</label>

                    <textarea name="shipping_address_line2" class="form-control">   </textarea>
                </div>
            </div>

            <button class="btn btn-success mt-4">Save Record</button>
        </form>
    </div>

    <script>
        document.getElementById('excelImportForm').addEventListener('submit', function() {
            document.getElementById('excelImportLoader').classList.remove('d-none');


            document.getElementById('importBtn').disabled = true;
        });
    </script>

    <script>
        document.getElementById('client_id').addEventListener('change', function() {
            let clientId = this.value;
            let productDropdown = document.getElementById('product');

            productDropdown.innerHTML = '<option value="">Loading...</option>';

            if (!clientId) {
                productDropdown.innerHTML = '<option value="">Select Product</option>';
                return;
            }

            fetch(`/get-client-products/${clientId}`)
                .then(response => response.json())
                .then(data => {
                    productDropdown.innerHTML = '<option value="">Select Product</option>';

                    data.forEach(item => {
                        let option = document.createElement('option');
                        option.value = item.shopify_product_name;
                        option.dataset.weight = item.weight_per_unit;
                        option.text = item.shopify_product_name;
                        productDropdown.appendChild(option);
                    });
                });
        });

        document.getElementById('product').addEventListener('change', function() {
            let weight = this.options[this.selectedIndex].dataset.weight || 0;
            let qty = document.getElementById('quantity').value || 1;

            document.getElementById('weight_in_gm').value = weight * qty;
        });

        document.getElementById('quantity').addEventListener('input', function() {
            let product = document.getElementById('product');
            let weight = product.options[product.selectedIndex]?.dataset.weight || 0;

            document.getElementById('weight_in_gm').value = weight * this.value;
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
