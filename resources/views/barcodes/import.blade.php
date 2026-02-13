@extends('layouts.admin')

@section('content')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    @push('scripts')
        <script>
            $(document).ready(function() {
                $('#barcodeTable').DataTable({
                    paging: true,
                    searching: true,
                    ordering: true,
                    lengthChange: true,
                    pageLength: 10,
                    language: {
                        search: "Search Barcode:",
                        lengthMenu: "Show _MENU_ entries"
                    }
                });
            });
        </script>

        <div class="container">
            <h4>Import Barcodes</h4>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="POST" style="margin: 35px 0px 35px 0px;" action="{{ route('barcodes.import') }}"
                enctype="multipart/form-data">
                @csrf
                <div class="card-body">

                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Select Client</label>
                            <select name="client_id" class="form-control" required>
                                <option value="">-- Select Client --</option>
                                @foreach ($clients as $client)
                                    <option value="{{ $client->id }}">{{ $client->client_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Upload Barcode Excel</label>
                            <input type="file" name="file" class="form-control" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Barcode Excel</label><br>
                            <button class="btn btn-primary">Import </button>
                        </div>
                        <div class="col-md-3">
                            <table class="table table-bordered text-center">

                                <tbody>
                                    @foreach ($clients as $client)
                                        @php
                                            $unusedCount = $barcodes
                                                ->where('client_id', $client->id)
                                                ->where('is_used', 0)
                                                ->count();
                                        @endphp

                                        <tr>
                                            <td>{{ $client->client_name }} Pending</td>
                                            <td>
                                                @if ($unusedCount == 0)
                                                    <span class="badge bg-danger">
                                                        {{ $unusedCount }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-success">
                                                        {{ $unusedCount }}
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </form>

            <table id="barcodeTable" class="table table-bordered table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Barcode</th>
                        <th>Client Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($barcodes as $product)
                        <tr>
                            <td>{{ $product->barcode }}</td>

                            <td>
                                {{ $product->client->client_name ?? '—' }}

                            </td>

                            <td>
                                @if ($product->is_used == 1)
                                    <span class="badge bg-success">Used</span>
                                @else
                                    <span class="badge bg-danger">Not Used</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center">
                                No Barcode added yet
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>
        </div>
    @endsection
