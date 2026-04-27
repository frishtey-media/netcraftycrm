@extends('layouts.calling')

@section('title', 'Add Whatsapp Order')

@section('content')

    <div class="card p-4 shadow-sm">

        <h4 class="mb-3 d-flex align-items-center gap-2">
            <i class="bi bi-whatsapp text-success fs-2"></i>
            <span>Add WhatsApp Order</span>
        </h4>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('calling.manual.store') }}">
            @csrf

            <div class="row">

                <div class="col-md-6 mb-2">
                    <label>Name</label>
                    <input type="text" name="customer_name" class="form-control" required>
                </div>

                <div class="col-md-6 mb-2">
                    <label>Phone</label>
                    <input type="text" name="customer_phone" class="form-control" required>
                </div>

                <div class="col-md-12 mb-2">
                    <label>Product</label>
                    <input type="text" name="product_name" class="form-control" required>
                </div>

                <div class="col-md-6 mb-2">
                    <label>Quantity</label>
                    <input type="number" name="quantity" class="form-control">
                </div>

                <div class="col-md-6 mb-2">
                    <label>City</label>
                    <input type="text" name="city" class="form-control">
                </div>

                <div class="col-md-6 mb-2">
                    <label>State</label>
                    <input type="text" name="state" class="form-control">
                </div>

                <div class="col-md-6 mb-2">
                    <label>Pincode</label>
                    <input type="text" name="pincode" class="form-control">
                </div>

                <div class="col-md-12 mb-2">
                    <label>Address</label>
                    <textarea name="address" class="form-control"></textarea>
                </div>

                <div class="col-md-6 mb-2">
                    <label>Amount</label>
                    <input type="number" name="amount" class="form-control">
                </div>

                <div class="col-md-6 mb-2">
                    <label>Payment Mode</label>
                    <select name="payment_mode" class="form-control">
                        <option value="cod">COD</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>

            </div>

            <button class="btn btn-primary w-100 mt-3">Submit Order</button>

        </form>

    </div>

@endsection
