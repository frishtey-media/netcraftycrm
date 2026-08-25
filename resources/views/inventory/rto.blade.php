@extends('layouts.inventory')

@section('content')

    <style>
        .rto-page {
            padding: 15px;
        }

        .rto-header {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 20px;
        }

        .rto-upload-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #dee2e6;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .04);
        }

        .rto-upload-header {
            background: #212529;
            color: #fff;
            padding: 16px 20px;
            border-radius: 12px 12px 0 0;
        }

        .result-card {
            border-radius: 10px;
            overflow: hidden;
            margin-top: 20px;
        }

        .stat-box {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 18px;
            background: #fff;
            height: 100%;
        }

        .stat-number {
            font-size: 28px;
            font-weight: 700;
        }

        .stat-title {
            color: #6c757d;
            font-size: 13px;
        }

        .barcode-badge {
            display: inline-block;
            margin: 3px;
            padding: 6px 10px;
            border-radius: 5px;
            font-size: 12px;
        }
    </style>


    <div class="container-fluid rto-page">

        {{-- =====================================================
         HEADER
    ====================================================== --}}

        <div class="rto-header">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h3 class="mb-1">
                        <i class="bi bi-arrow-return-left me-2"></i>
                        RTO Received
                    </h3>

                    <div class="text-muted">
                        Upload RTO barcode Excel and mark returned orders as received.
                    </div>

                </div>

                <span class="badge bg-warning text-dark px-3 py-2">
                    RTO PROCESS
                </span>

            </div>

        </div>


        {{-- =====================================================
         ERROR MESSAGE
    ====================================================== --}}

        @if (session('rto_error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">

                <div class="d-flex align-items-start">

                    <i class="bi bi-x-circle-fill fs-4 me-3"></i>

                    <div>

                        <strong>RTO Upload Failed</strong>

                        <div class="mt-1">
                            {!! session('rto_error') !!}
                        </div>

                    </div>

                </div>

                <button type="button" class="btn-close" data-bs-dismiss="alert">
                </button>

            </div>
        @endif


        {{-- =====================================================
         SUCCESS MESSAGE
    ====================================================== --}}

        @if (session('rto_success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">

                <div class="d-flex align-items-start">

                    <i class="bi bi-check-circle-fill fs-4 me-3"></i>

                    <div>

                        <strong>RTO Upload Successful</strong>

                        <div class="mt-1">
                            {!! session('rto_success') !!}
                        </div>

                    </div>

                </div>

                <button type="button" class="btn-close" data-bs-dismiss="alert">
                </button>

            </div>
        @endif


        {{-- =====================================================
         VALIDATION ERRORS
    ====================================================== --}}

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">

                <div class="d-flex align-items-start">

                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>

                    <div>

                        <strong>Please check the following:</strong>

                        <ul class="mb-0 mt-2">

                            @foreach ($errors->all() as $error)
                                <li>
                                    {{ $error }}
                                </li>
                            @endforeach

                        </ul>

                    </div>

                </div>

                <button type="button" class="btn-close" data-bs-dismiss="alert">
                </button>

            </div>
        @endif


        {{-- =====================================================
         UPLOAD CARD
    ====================================================== --}}

        <div class="rto-upload-card">

            <div class="rto-upload-header">

                <h5 class="mb-0">

                    <i class="bi bi-file-earmark-excel me-2"></i>

                    Upload RTO Barcode Excel

                </h5>

            </div>


            <div class="p-4">

                <form method="POST" action="{{ route('inventory.rto.upload') }}" enctype="multipart/form-data">

                    @csrf


                    <div class="row g-3 align-items-end">

                        <div class="col-md-8">

                            <label class="form-label fw-semibold">

                                RTO Barcode File

                            </label>

                            <input type="file" name="rtobarcodes" class="form-control" accept=".xls,.xlsx" required>

                            <small class="text-muted">

                                Supported formats:
                                <strong>.xls, .xlsx</strong>

                            </small>

                        </div>


                        <div class="col-md-4">

                            <button type="submit" class="btn btn-dark w-100 py-2">

                                <i class="bi bi-upload me-2"></i>

                                Upload & Receive RTO

                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>


        {{-- =====================================================
         SKIPPED BAR CODES
    ====================================================== --}}

        @if (session('rto_skipped') && count(session('rto_skipped')))
            <div class="card result-card border-warning">

                <div class="card-header bg-warning text-dark">

                    <strong>

                        <i class="bi bi-exclamation-circle me-2"></i>

                        Already Scanned / Skipped

                    </strong>

                    <span class="badge bg-dark ms-2">

                        {{ count(session('rto_skipped')) }}

                    </span>

                </div>


                <div class="card-body">

                    <div class="mb-2 text-muted">

                        These RTO barcodes were already received earlier.

                    </div>

                    @foreach (session('rto_skipped') as $barcode)
                        <span class="badge bg-danger barcode-badge">

                            {{ $barcode }}

                        </span>
                    @endforeach

                </div>

            </div>
        @endif


        {{-- =====================================================
         NOT FOUND
    ====================================================== --}}

        @if (session('rto_not_found') && count(session('rto_not_found')))
            <div class="card result-card border-danger">

                <div class="card-header bg-danger text-white">

                    <strong>

                        <i class="bi bi-x-circle me-2"></i>

                        Barcodes Not Found

                    </strong>

                    <span class="badge bg-light text-danger ms-2">

                        {{ count(session('rto_not_found')) }}

                    </span>

                </div>


                <div class="card-body">

                    <div class="mb-2 text-muted">

                        These barcodes were not found in the Orders table.

                    </div>


                    @foreach (session('rto_not_found') as $barcode)
                        <span class="badge bg-dark barcode-badge">

                            {{ $barcode }}

                        </span>
                    @endforeach

                </div>

            </div>
        @endif


    </div>

@endsection
