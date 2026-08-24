@extends('layouts.admin')

@section('content')

    <div class="container-fluid">

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        {{-- =========================================================
        DUPLICATE ORDER ALERT
    ========================================================== --}}

        @if (session('duplicate_orders'))
            <script>
                let duplicates = @json(session('duplicate_orders'));

                Swal.fire({

                    icon: 'warning',

                    title: 'Duplicate Orders Found',

                    html: '<div style="max-height:250px;overflow:auto;text-align:left;">' +
                        duplicates.join('<br>') +
                        '</div>',

                    confirmButtonText: 'Clear Duplicate',

                    showCancelButton: true,

                    cancelButtonText: 'Cancel'

                }).then((result) => {

                    if (result.isConfirmed) {

                        let form =
                            document.createElement('form');

                        form.method = 'POST';

                        form.action =
                            "{{ route('clear.duplicates') }}";

                        let token =
                            document.createElement('input');

                        token.type = 'hidden';
                        token.name = '_token';
                        token.value = "{{ csrf_token() }}";

                        form.appendChild(token);

                        let ids =
                            document.createElement('input');

                        ids.type = 'hidden';
                        ids.name = 'order_ids';
                        ids.value = JSON.stringify(duplicates);

                        form.appendChild(ids);

                        let importDate =
                            document.createElement('input');

                        importDate.type = 'hidden';
                        importDate.name = 'import_date';

                        importDate.value =
                            document.getElementById(
                                'import_date'
                            ).value;

                        form.appendChild(importDate);

                        document.body.appendChild(form);

                        form.submit();
                    }

                });
            </script>
        @endif


        {{-- =========================================================
        SUCCESS
    ========================================================== --}}

        @if (session('success'))
            <script>
                Swal.fire({

                    icon: 'success',

                    title: 'Success',

                    text: "{{ session('success') }}",

                    timer: 2500,

                    showConfirmButton: false,

                    timerProgressBar: true

                });
            </script>
        @endif


        {{-- =========================================================
        ERROR
    ========================================================== --}}

        @if (session('error'))
            <script>
                Swal.fire({

                    icon: 'error',

                    title: 'Oops',

                    text: "{{ session('error') }}"

                });
            </script>
        @endif


        @if ($errors->any())
            <div class="alert alert-danger">

                <ul class="mb-0">

                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach

                </ul>

            </div>
        @endif


        {{-- =========================================================
        PAGE HEADER
    ========================================================== --}}

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h3 class="fw-bold mb-1">
                    <i class="bi bi-box-seam me-2"></i>
                    India Post Export
                </h3>

                <div class="text-muted">
                    Export imported orders to India Post format
                </div>

            </div>

            <div>

                <span class="badge bg-primary px-3 py-2">
                    Import Date:
                    {{ \Carbon\Carbon::parse($importDate)->format('d-m-Y') }}
                </span>

            </div>

        </div>


        {{-- =========================================================
        INDIA POST EXPORT CARD
    ========================================================== --}}

        @if ($orders->count())
            <div class="row justify-content-center">

                <div class="col-xl-7 col-lg-8 col-md-10">

                    <div class="card border-0 shadow-sm">

                        <div class="card-header bg-primary text-white py-3">

                            <h5 class="mb-0">

                                <i class="bi bi-file-earmark-excel me-2"></i>

                                Export India Post File

                            </h5>

                        </div>


                        <div class="card-body p-4">

                            <div class="alert alert-info">

                                <i class="bi bi-info-circle me-2"></i>

                                Select the import date and download the
                                India Post file. After successful export,
                                the imported temporary data will be moved
                                to the main Orders table.

                            </div>


                            <form action="{{ route('postoffice.export') }}" method="POST" id="postOfficeForm">

                                @csrf


                                {{-- IMPORT DATE --}}

                                <div class="mb-4">

                                    <label for="import_date" class="form-label fw-semibold">
                                        Import Date
                                    </label>

                                    <input type="date" id="import_date" name="import_date"
                                        class="form-control form-control-lg" value="{{ $importDate }}" required>

                                </div>


                                {{-- ORDER COUNT --}}

                                <div class="bg-light rounded p-3 mb-4">

                                    <div class="row text-center">

                                        <div class="col-6">

                                            <div class="text-muted small">
                                                Orders Found
                                            </div>

                                            <div class="fs-3 fw-bold text-primary">
                                                {{ $orders->total() }}
                                            </div>

                                        </div>


                                        <div class="col-6 border-start">

                                            <div class="text-muted small">
                                                Selected Date
                                            </div>

                                            <div class="fw-bold">
                                                {{ \Carbon\Carbon::parse($importDate)->format('d M Y') }}
                                            </div>

                                        </div>

                                    </div>

                                </div>


                                {{-- EXPORT BUTTON --}}

                                <button type="submit" class="btn btn-primary btn-lg w-100">

                                    <i class="bi bi-download me-2"></i>

                                    Export India Post Format

                                </button>

                            </form>

                        </div>

                    </div>

                </div>

            </div>
        @else
            {{-- =====================================================
            NO ORDERS
        ====================================================== --}}

            <div class="row justify-content-center">

                <div class="col-lg-8">

                    <div class="card border-0 shadow-sm">

                        <div class="card-body text-center py-5">

                            <div class="mb-3">

                                <i class="bi bi-inbox" style="font-size:60px;color:#adb5bd;"></i>

                            </div>

                            <h5 class="fw-bold">
                                No Orders Available
                            </h5>

                            <p class="text-muted mb-0">

                                No imported orders were found for

                                <strong>
                                    {{ \Carbon\Carbon::parse($importDate)->format('d-m-Y') }}
                                </strong>

                            </p>

                        </div>

                    </div>

                </div>

            </div>
        @endif


    </div>


    {{-- =============================================================
    EXPORT LOADING
============================================================== --}}

    @push('scripts')
        <script>
            $(function() {

                $('#postOfficeForm').on(
                    'submit',
                    function() {

                        let btn =
                            $(this).find(
                                'button[type=submit]'
                            );

                        btn.prop(
                            'disabled',
                            true
                        );

                        btn.html(
                            '<span class="spinner-border spinner-border-sm me-2"></span>' +
                            'Preparing India Post File...'
                        );

                    }
                );

            });
        </script>
    @endpush

@endsection
