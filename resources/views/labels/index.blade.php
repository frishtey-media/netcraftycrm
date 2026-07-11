@extends('layouts.admin')

@section('content')

    <div class="container-fluid">

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        {{-- =========================
        DUPLICATE ORDER ALERT
    ========================== --}}

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

                        let form = document.createElement('form');

                        form.method = 'POST';

                        form.action = "{{ route('clear.duplicates') }}";

                        let token = document.createElement('input');

                        token.type = 'hidden';

                        token.name = '_token';

                        token.value = "{{ csrf_token() }}";

                        form.appendChild(token);

                        let ids = document.createElement('input');

                        ids.type = 'hidden';

                        ids.name = 'order_ids';

                        ids.value = JSON.stringify(duplicates);

                        form.appendChild(ids);

                        let importDate = document.createElement('input');

                        importDate.type = 'hidden';

                        importDate.name = 'import_date';

                        importDate.value = document.getElementById('import_date').value;

                        form.appendChild(importDate);

                        document.body.appendChild(form);

                        form.submit();

                    }

                });
            </script>
        @endif



        {{-- =========================
        SUCCESS
    ========================== --}}

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



        {{-- =========================
        ERROR
    ========================== --}}

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

        <div class="row">

            @if ($orders->count())
                <div class="col-lg-6">

                    <!-- Post Office Export Card -->

                    <div class="card shadow-sm border-0">

                        <div class="card-header bg-primary text-white">

                            <h5 class="mb-0">
                                Post Office Export
                            </h5>

                        </div>

                        <div class="card-body">

                            <form action="{{ route('postoffice.export') }}" method="POST">

                                @csrf

                                <div class="mb-3">

                                    <label>Import Date</label>

                                    <input type="date" id="import_date" name="import_date" class="form-control"
                                        value="{{ $importDate }}" required>

                                </div>

                                <button class="btn btn-primary w-100">

                                    Export Post Office Format

                                </button>

                            </form>

                        </div>

                    </div>

                </div>


                <div class="col-lg-6">

                    <div class="card shadow-sm border-0">

                        <div class="card-header bg-success text-white">

                            <h5 class="mb-0">

                                Label Export

                            </h5>

                        </div>

                        <div class="card-body">

                            <button class="btn btn-success" {{ $canGenerate ? '' : 'disabled' }} data-bs-toggle="modal"
                                data-bs-target="#senderModal">

                                Generate Label PDF

                            </button>

                        </div>

                    </div>

                </div>
            @else
                <div class="col-12">

                    <div class="alert alert-warning text-center">

                        <h5 class="mb-0">

                            No Orders Available for Selected Import Date

                        </h5>

                    </div>

                </div>
            @endif

        </div>
        {{-- ===========================================
    LABEL EXPORT MODAL
============================================ --}}
        <div class="modal fade" id="senderModal" tabindex="-1" aria-hidden="true">

            <div class="modal-dialog modal-lg">

                <form method="POST" action="{{ route('labels.export') }}">

                    @csrf
                    <input type="hidden" name="import_date" value="{{ $importDate }}">
                    <div class="modal-content">

                        <div class="modal-header bg-success text-white">

                            <h5 class="modal-title">

                                <i class="bi bi-printer"></i>

                                Generate India Post Labels

                            </h5>

                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal">
                            </button>

                        </div>

                        <div class="modal-body">

                            <div class="row">

                                {{-- CLIENT --}}

                                @if (auth()->user()->role == 'client')
                                    <div class="col-md-12 mb-3">

                                        <label class="form-label">

                                            Client

                                        </label>

                                        <input type="text" class="form-control"
                                            value="{{ $clients->first()->client_name }}" readonly>

                                    </div>
                                @else
                                    <div class="col-md-6 mb-3">

                                        <label class="form-label">

                                            Client

                                        </label>

                                        <select class="form-control" disabled>

                                            @foreach ($clients as $client)
                                                <option>

                                                    {{ $client->client_name }}

                                                </option>
                                            @endforeach

                                        </select>

                                    </div>
                                @endif


                                {{-- IMPORT DATE --}}

                                <div class="col-md-6 mb-3">

                                    <label class="form-label">

                                        Import Date

                                    </label>

                                    <input type="date" class="form-control" value="{{ $importDate }}" readonly>

                                </div>


                                {{-- SENDER --}}

                                <div class="col-md-12">

                                    <label class="form-label">

                                        Sender

                                    </label>

                                    <select class="form-control" name="sender_id" required>

                                        <option value="">

                                            Select Sender

                                        </option>

                                        @foreach ($senders as $sender)
                                            <option value="{{ $sender->id }}">

                                                {{ $sender->customer_name }}

                                            </option>
                                        @endforeach

                                    </select>

                                </div>

                            </div>

                        </div>

                        <div class="modal-footer">

                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">

                                Close

                            </button>

                            <button type="submit" class="btn btn-success">

                                <i class="bi bi-file-earmark-pdf"></i>

                                Generate Labels

                            </button>

                        </div>

                    </div>

                </form>

            </div>

        </div>
        @push('scripts')
            <script>
                $(function() {

                    /*
                    |--------------------------------------------------------------------------
                    | Post Office Export
                    |--------------------------------------------------------------------------
                    */

                    $('form[action="{{ route('postoffice.export') }}"]').on('submit', function() {

                        let btn = $(this).find('button[type=submit]');

                        btn.prop('disabled', true);

                        btn.html(
                            '<span class="spinner-border spinner-border-sm"></span> Preparing Export...'
                        );

                    });

                    /*
                    |--------------------------------------------------------------------------
                    | Label Export
                    |--------------------------------------------------------------------------
                    */

                    $('form[action="{{ route('labels.export') }}"]').on('submit', function() {

                        let sender = $(this).find('[name=sender_id]').val();

                        if (sender == '') {

                            Swal.fire({

                                icon: 'warning',

                                title: 'Select Sender',

                                text: 'Please select sender first.'

                            });

                            return false;
                        }

                        let btn = $(this).find('button[type=submit]');

                        btn.prop('disabled', true);

                        btn.html(
                            '<span class="spinner-border spinner-border-sm"></span> Generating PDF...'
                        );

                    });

                    /*
                    |--------------------------------------------------------------------------
                    | Focus Sender
                    |--------------------------------------------------------------------------
                    */

                    $('#senderModal').on('shown.bs.modal', function() {

                        $(this).find('[name=sender_id]').focus();

                    });

                });
            </script>
        @endpush

    </div>

@endsection
