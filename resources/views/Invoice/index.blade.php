@extends('layouts.admin')

@section('content')
    <div class="container-fluid">

        {{-- PAGE TITLE --}}
        <div class="row mb-3">
            <div class="col-md-12">
                <h4 class="fw-bold">Invoice Generator</h4>
            </div>
        </div>

        {{-- SUCCESS MESSAGE --}}
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="row">

            {{-- SELLER FORM --}}
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header fw-bold">Seller Details</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('seller.store') }}">
                            @csrf

                            <div>
                                <label>Seller Name</label>
                                <input type="text" name="seller_name" class="form-control" required>
                            </div>

                            <div>
                                <label>Trade Name</label>
                                <input type="text" name="trade_name" class="form-control">
                            </div>

                            <div>
                                <label>GST No</label>
                                <input type="text" name="gst_no" class="form-control" required>
                            </div>

                            <div>
                                <label>PAN No</label>
                                <input type="text" name="pan_no" class="form-control">
                            </div>

                            <div>
                                <label>Address</label>
                                <textarea name="address" class="form-control" rows="3" required></textarea>
                            </div>

                            <button class="btn btn-primary w-100 mt-2">
                                Save Seller
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- EXCEL IMPORT --}}
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-header fw-bold">Upload Excel & Generate Invoices</div>
                    <div class="card-body">

                        <form method="POST" action="{{ route('invoice.import') }}" enctype="multipart/form-data">
                            @csrf

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label>Select Seller</label>
                                    <select name="seller_id" class="form-control" required>
                                        <option value="">-- Select Seller --</option>
                                        @foreach ($sellers as $seller)
                                            <option value="{{ $seller->id }}">
                                                {{ $seller->seller_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label>Excel File</label>
                                    <input type="file" name="excel" class="form-control" accept=".xls,.xlsx" required>
                                </div>
                            </div>

                            <div class="alert alert-info small">
                                <b>Excel Columns Order:</b><br>
                                Invoice No | Order ID | Date | Barcode | Payment Mode | Amount | Customer Name |
                                Customer Phone | Shipping Address | City | State | Pincode |
                                Product | Quantity | Weight (GM)
                            </div>

                            <button class="btn btn-success">
                                Generate & Download Invoices
                            </button>

                        </form>

                    </div>
                </div>
            </div>

        </div>

    </div>
@endsection
