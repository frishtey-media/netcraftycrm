@extends('layouts.admin')

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="card text-center alert alert-success">
                <div class="card-body">
                    <h5>Total Orders - {{ $totalOrders }}</h5>
                </div>
            </div>
        </div>


        <div class="col-md-4">
            <a href="/rto">
                <div class="card text-center alert alert-info" style="cursor:pointer">
                    <div class="card-body">

                        <h5 class="mt-2">RTO Find </h5>
                    </div>
                </div>
            </a>
        </div>


        <div class="col-md-4">
            <div class="card text-center alert alert-info" style="cursor:pointer" data-bs-toggle="modal"
                data-bs-target="#barcodeModal">
                <div class="card-body">

                    <h5 class="mt-2">Download Barcodes <i class="bi bi-download fs-1"></i></h5>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card text-center alert alert-danger" style="cursor:pointer" data-bs-toggle="modal"
                data-bs-target="#deleteOrdersModal">
                <div class="card-body">

                    <h5 class="mt-2">Delete Records <i class="bi bi-trash fs-1"></i></h5>
                </div>
            </div>
        </div>

    </div>

    <!-- BARCODE MODAL -->
    <div class="modal fade" id="barcodeModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.download.barcodes') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Download Barcodes</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label>From Date</label>
                            <input type="date" name="from_date" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>To Date</label>
                            <input type="date" name="to_date" class="form-control" required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" data-bs-dismiss="modal">
                            Download TXT
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="deleteOrdersModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.orders.delete') }}">
                @csrf
                @method('DELETE')

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">Delete Orders by Date</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label>From Date</label>
                            <input type="date" name="from_date" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>To Date</label>
                            <input type="date" name="to_date" class="form-control" required>
                        </div>

                        <div class="alert alert-danger">
                            All order data will be permanently deleted.

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-danger" data-bs-dismiss="modal"
                            onclick="return confirm('Are you 100% sure you want to delete these orders?')">
                            Confirm Delete
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
