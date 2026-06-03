@extends('layouts.admin')

@section('content')
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

            {{-- DUPLICATE ORDERS --}}
            @if (session('duplicate_orders'))
                <script>
                    let duplicates = @json(session('duplicate_orders'));

                    Swal.fire({

                        icon: 'warning',

                        title: 'Duplicate Orders Found!',

                        html: '<div style="max-height:200px;overflow:auto;">' +
                            duplicates.join('<br>') +
                            '</div>',

                        showCancelButton: true,

                        confirmButtonText: 'Clear & Move to Log',

                        cancelButtonText: 'Cancel'

                    }).then((result) => {

                        if (result.isConfirmed) {

                            let form = document.createElement('form');

                            form.method = 'POST';

                            form.action =
                                "{{ route('clear.duplicates') }}";

                            let csrf = document.createElement('input');

                            csrf.type = 'hidden';
                            csrf.name = '_token';
                            csrf.value = "{{ csrf_token() }}";

                            let input = document.createElement('input');

                            input.type = 'hidden';

                            input.name = 'order_ids';

                            input.value =
                                JSON.stringify(duplicates);

                            form.appendChild(csrf);

                            form.appendChild(input);

                            document.body.appendChild(form);

                            form.submit();
                        }

                    });
                </script>
            @endif


            {{-- SUCCESS --}}
            @if (session('success'))
                <script>
                    Swal.fire({

                        icon: 'success',

                        title: 'Done!',

                        text: "{{ session('success') }}",

                        timer: 2000,

                        showConfirmButton: false,

                        timerProgressBar: true

                    });
                </script>
            @endif


            {{-- ERROR --}}
            @if (session('error'))
                <div class="alert alert-danger">

                    {{ session('error') }}

                </div>
            @endif


            <div class="col-md-4">

                @if ($orders->count())
                    {{-- POST OFFICE EXPORT --}}
                    <div class="card text-center shadow-sm mb-3">

                        <form action="{{ route('postoffice.export') }}" method="POST">

                            @csrf

                            <button type="submit" class="btn btn-primary w-100 p-4">

                                <div class="card-body">

                                    <h5 class="mt-2">

                                        Export Post Office Format

                                        <i class="bi bi-download fs-1"></i>

                                    </h5>

                                </div>

                            </button>

                        </form>

                    </div>


                    {{-- LABEL EXPORT --}}
                    <div class="card text-center shadow-sm">

                        <button class="btn btn-success p-4" data-bs-toggle="modal" data-bs-target="#senderModal">

                            <div class="card-body">

                                <h5 class="mt-2">

                                    Export Labels

                                    <i class="bi bi-download fs-1"></i>

                                </h5>

                            </div>

                        </button>

                    </div>
                @else
                    <div class="alert alert-warning text-center">

                        <strong>
                            No record for label generation now
                        </strong>

                    </div>
                @endif

            </div>

        </div>

    </div>


    {{-- SENDER MODAL --}}
    <div class="modal fade" id="senderModal" tabindex="-1">

        <div class="modal-dialog">

            <form method="POST" action="{{ route('labels.export') }}">

                @csrf

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title">

                            Select Sender

                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal">
                        </button>

                    </div>

                    <div class="modal-body">

                        {{-- CLIENT NAME --}}
                        @if (auth()->user()->role == 'client')
                            <div class="mb-3">

                                <label class="form-label">

                                    Client Name

                                </label>

                                <input type="text" class="form-control" value="{{ $clients->first()->client_name }}"
                                    readonly>

                            </div>
                        @endif


                        {{-- SENDER --}}
                        <div class="mb-3">

                            <label class="form-label">

                                Select Sender

                            </label>

                            <select name="sender_id" class="form-control" required>

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

                    <div class="modal-footer">

                        <button type="submit" class="btn btn-success">

                            Generate PDF

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>
@endsection
