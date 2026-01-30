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
        <div class="col-md-4">
            <div class="dashboard-card card-green action-card" data-bs-toggle="modal" data-bs-target="#barcodeModal">
                <div>
                    <div class="card-title">Download Barcodes</div>
                    <small>Export TXT by date</small>
                </div>
                <i class="bi bi-download card-icon"></i>
            </div>
        </div>

        {{-- Delete Records --}}
        <div class="col-md-4">
            <div class="dashboard-card card-red action-card" data-bs-toggle="modal" data-bs-target="#deleteOrdersModal">
                <div>
                    <div class="card-title text-danger">Delete Records</div>
                    <small class="text-danger">Permanent action</small>
                </div>
                <i class="bi bi-trash card-icon text-danger"></i>
            </div>
        </div>

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
                        <h5 class="modal-title text-danger">Delete Orders</h5>
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
                            ⚠️ All order data will be permanently deleted.
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
@endsection
