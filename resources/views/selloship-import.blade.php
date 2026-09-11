@extends('layouts.admin')

@section('content')

    <style>
        .import-wrapper {
            max-width: 900px;
            margin: 20px auto;
        }

        .import-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, .06);
            overflow: hidden;
        }

        .import-header {
            background: linear-gradient(135deg,
                    #111827,
                    #1f2937);
            color: #fff;
            padding: 22px 25px;
        }

        .import-header h3 {
            margin: 0;
            font-weight: 700;
        }

        .import-body {
            padding: 30px;
        }

        .upload-box {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            background: #f9fafb;
        }

        .upload-box i {
            font-size: 45px;
            color: #ef4444;
        }

        .rule-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 18px;
        }
    </style>


    <div class="import-wrapper">

        @if (session('success'))
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif


        @if (session('error'))
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i>
                {{ session('error') }}
            </div>
        @endif


        @if ($errors->any())
            <div class="alert alert-danger">

                @foreach ($errors->all() as $error)
                    <div>
                        {{ $error }}
                    </div>
                @endforeach

            </div>
        @endif


        <div class="import-card">

            <div class="import-header">

                <h3>
                    <i class="bi bi-file-earmark-spreadsheet"></i>
                    Selloship Import
                </h3>

                <small>
                    Import Selloship orders into Calling Orders
                </small>

            </div>


            <div class="import-body">

                <form method="POST" action="{{ route('selloship.import') }}" enctype="multipart/form-data">

                    @csrf


                    <div class="mb-4">

                        <label class="form-label fw-bold">
                            Select Client
                        </label>

                        <select name="client_id" class="form-select" required>

                            <option value="">
                                -- Select Client --
                            </option>

                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}">

                                    {{ $client->client_name }}

                                </option>
                            @endforeach

                        </select>

                    </div>

                    <div class="upload-box mb-4">

                        <i class="bi bi-cloud-arrow-up"></i>

                        <h5 class="mt-3">
                            Upload Selloship Excel
                        </h5>

                        <p class="text-muted">
                            XLSX, XLS or CSV
                        </p>

                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>

                    </div>


                    <div class="rule-box mb-4">

                        <strong>
                            Import Rules
                        </strong>

                        <ul class="mt-2 mb-0">

                            <li>
                                Data will be imported client-wise.
                            </li>

                            <li>
                                Order source will be
                                <strong>selloship</strong>.
                            </li>

                            <li>
                                Status will be
                                <strong>pending</strong>.
                            </li>

                            <li>
                                Orders will initially remain
                                <strong>unassigned</strong>.
                            </li>

                            <li>
                                Existing Selloship orders will not
                                be imported again.
                            </li>

                            <li>
                                Staff assignment ke time new CRM
                                Order ID generate hogi.
                            </li>

                        </ul>

                    </div>


                    <button type="submit" class="btn btn-danger w-100">

                        <i class="bi bi-upload"></i>

                        Import Selloship Records

                    </button>

                </form>

            </div>

        </div>

    </div>

@endsection
