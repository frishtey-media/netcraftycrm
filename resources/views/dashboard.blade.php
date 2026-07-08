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
            text-decoration: none;
            color: inherit;
        }

        .dashboard-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
        }

        .card-green {
            background: linear-gradient(135deg, #dff3ea, #c8eadb);
        }

        .card-red {
            background: linear-gradient(135deg, #fde2e2, #f9caca);
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

        .action-card {
            cursor: pointer;
        }
    </style>

    <div class="row g-4">

        {{-- Unused Barcodes --}}
        <div class="col-md-4">
            <a href="/barcodes" class="dashboard-card card-green">
                <div>
                    <div class="card-title">Unused Barcodes</div>
                    <div class="card-count">{{ $barcodes->where('is_used', 0)->count() }}</div>
                </div>
                <i class="bi bi-upc-scan card-icon"></i>
            </a>
        </div>

        {{-- Total Orders --}}
        <div class="col-md-4">
            <a href="/orders" class="dashboard-card card-green">
                <div>
                    <div class="card-title">Total Orders</div>
                    <div class="card-count">{{ $totalOrders }}</div>
                </div>
                <i class="bi bi-cart-check card-icon"></i>
            </a>
        </div>

        {{-- RTO --}}
        <div class="col-md-4">
            <a href="/rto" class="dashboard-card card-green">
                <div>
                    <div class="card-title">RTO Find</div>
                    <div class="card-count">View</div>
                </div>
                <i class="bi bi-arrow-repeat card-icon"></i>
            </a>
        </div>
        <div class="col-md-4">
            <div class="dashboard-card card-green action-card" data-bs-toggle="modal" data-bs-target="#barcodeModal">
                <div>
                    <div class="card-title">Download Barcodes</div>
                    <small>Export TXT by date</small>
                </div>
                <i class="bi bi-download card-icon"></i>
            </div>
        </div>
        <div class="col-md-4">
            <a href="/delivery" class="dashboard-card card-green">
                <div>
                    <div class="card-title">Delivery Status Update</div>
                    <small>Format Excel India post bulk tracking</small>
                </div>
                <i class="bi bi-cart-check card-icon"></i>
            </a>
        </div>
        @if (auth()->user()->role == 'super_admin')
            <div class="col-lg-4 mb-3">
                <a href="{{ route('reports.index', 'transit7') }}" class="text-decoration-none">
                    <div class="card shadow border-0 alert-card" style="background: #f14444;cursor:pointer">

                        <div class="card-body d-flex justify-content-between align-items-center">

                            <div>
                                <h5 style="color: white;">7 days intrasit</h5>

                                <h2 style="color: white;">{{ $transit7 }}</h2>
                            </div>

                            <i class="fas fa-barcode fa-3x text-dark"></i>

                        </div>

                    </div>
                </a>
            </div>


            <div class="col-lg-4 mb-3">
                <a href="{{ route('reports.index', 'rto5') }}" class="text-decoration-none">
                    <div class="card shadow border-0 alert-card" style="background: #f14444;cursor:pointer">

                        <div class="card-body d-flex justify-content-between align-items-center">

                            <div>
                                <h5 style="color: white;">5 days Not Received RTO</h5>

                                <h2 style="color: white;">{{ $rto5 }}</h2>
                            </div>

                            <i class="fas fa-barcode fa-3x text-dark"></i>

                        </div>

                    </div>
                </a>
            </div>





            <div class="col-lg-4 mb-3">
                <a href="{{ route('reports.index', 'not_booked') }}" class="text-decoration-none">
                    <div class="card shadow border-0 alert-card" style="background: #f14444;cursor:pointer">

                        <div class="card-body d-flex justify-content-between align-items-center">

                            <div>
                                <h5 style="color: white;">Not Book India Post</h5>

                                <h2 style="color: white;">{{ $ourSidePending }}</h2>
                            </div>

                            <i class="fas fa-barcode fa-3x text-dark"></i>

                        </div>

                    </div>
                </a>
            </div>



            <div class="col-md-4">
                <a href="{{ route('payments.index') }}" class="dashboard-card card-green">
                    <div>
                        <div class="card-title">Payments</div>

                    </div>
                    <i class="bi bi-people card-icon"></i>
                </a>
            </div>
            {{-- Clients --}}

            <div class="col-md-4">
                <a href="/clients" class="dashboard-card card-green">
                    <div>
                        <div class="card-title">Clients</div>
                        <div class="card-count">{{ $totalclients }}</div>
                    </div>
                    <i class="bi bi-people card-icon"></i>
                </a>
            </div>

            {{-- Download Barcodes --}}


            {{-- Delete Records --}}
            <div class="col-md-4">
                <div class="dashboard-card card-green action-card" data-bs-toggle="modal"
                    data-bs-target="#deleteOrdersModal">
                    <div>
                        <div class="card-title text-danger">Delete Old Orders</div>
                        <small class="text-danger">Permanent action</small>
                    </div>
                    <i class="bi bi-trash card-icon text-danger"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card card-green action-card" data-bs-toggle="modal"
                    data-bs-target="#amazonOrdersModal">
                    <div>
                        <div class="card-title">Amazon To Telly</div>
                        <small>Format Excel</small>
                    </div>
                    <i class="bi bi-cart-check card-icon"></i>
                </div>
            </div>
        @endif

    </div>

    {{-- ================= BARCODE MODAL ================= --}}
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
                        <button type="submit" class="btn btn-success">
                            Download TXT
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= DELETE MODAL ================= --}}
    <div class="modal fade" id="deleteOrdersModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.orders.delete') }}">
                @csrf
                @method('DELETE')

                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">Delete Old Orders</h5>
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
                            All Barcodes will be permanently deleted.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-danger"
                            onclick="return confirm('Are you 100% sure? This cannot be undone.')">
                            Confirm Delete
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <div class="modal fade" id="amazonOrdersModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="{{ url('/amazon-to-tally') }}" enctype="multipart/form-data">
                @csrf



                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">Import Amazon Excel</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Import Amazon Excel</label>
                            <input type="file" name="excelfile" class="form-control" required>
                        </div>


                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-danger">
                            Convert and Download
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
