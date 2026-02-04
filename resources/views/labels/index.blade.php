@extends('layouts.admin')

@section('content')
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <!--<a href="{{ route('labels.final.export') }}" class="btn btn-primary">
                                                                                                <i class="fas fa-file-excel"></i>
                                                                                                Export Courier Excel
                                                                                            </a>-->

            @php
                use App\Models\ShopifyOrder;
            @endphp

            <div class="col-md-4">
                @if (ShopifyOrder::exists())
                    <div class="card text-center alert" style="cursor:pointer">
                        <a href="{{ route('postoffice.export') }}" class="btn btn-primary">
                            <div class="card-body">
                                <h5 class="mt-2">
                                    Export Post office Format <i class="bi bi-download fs-1"></i>
                                </h5>
                            </div>
                        </a>
                    </div>

                    <div id="exportCard" class="card text-center alert" style="cursor:pointer; display:none;"
                        data-bs-toggle="modal" data-bs-target="#barcodeModal">

                        <a href="javascript:void(0)" class="btn btn-success" data-bs-toggle="modal"
                            data-bs-target="#senderModal">
                            <div class="card-body">
                                <h5 class="mt-2">
                                    Export Labels <i class="bi bi-download fs-1"></i>
                                </h5>
                            </div>
                        </a>

                    </div>
                @else
                    <div class="alert alert-warning text-center">
                        <strong>No record for label generation now</strong>
                    </div>
                @endif
            </div>




        </div>

        <div class="modal fade" id="senderModal">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('labels.export') }}">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5>Select Sender</h5>
                        </div>
                        <div class="modal-body">
                            <select name="sender_id" class="form-control" required>
                                @foreach ($senders as $sender)
                                    <option value="{{ $sender->id }}">
                                        {{ $sender->customer_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-success">Generate PDF</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!--<div class="card">
                                                                                    <div class="card-body table-responsive">
                                                                                    <table class="table table-bordered table-striped">
                                                                                        <thead class="table-dark">
                                                                                            <tr>
                                                                                                <th>#</th>
                                                                                                <th>Order ID</th>
                                                                                                <th>Client</th>
                                                                                                <th>Customer</th>
                                                                                                <th>Phone</th>
                                                                                                <th>Product</th>
                                                                                                <th>Qty</th>
                                                                                                <th>Weight per Unit (GM)</th>
                                                                                                <th>Total Weight (GM)</th>
                                                                                                <th>Barcode</th>
                                                                                                <th>Amount</th>
                                                                                                <th>Payment Mode</th>
                                                                                                <th>Shipping Address</th>
                                                                                                <th>City</th>
                                                                                                <th>State</th>
                                                                                                <th>Pincode</th>
                                                                                                <th>Order Date</th>
                                                                                                <th>Created At</th>
                                                                                            </tr>
                                                                                        </thead>
                                                                                        <tbody>
                                                                                            @forelse($orders as $order)
    <tr>
                                                                                                    <td>{{ $loop->iteration }}</td>
                                                                                                    <td>{{ $order->order_id }}</td>
                                                                                                    <td>{{ $order->client->client_name ?? '-' }}</td>
                                                                                                    <td>{{ $order->customer_name ?? '-' }}</td>
                                                                                                    <td>{{ $order->customer_phone ?? '-' }}</td>
                                                                                                    <td>{{ $order->shopify_product_name ?? '-' }}</td>
                                                                                                    <td>{{ $order->quantity ?? 0 }}</td>
                                                                                                    <td>{{ $order->weight_per_unit ?? 0 }}</td>
                                                                                                    <td>{{ $order->total_weight ?? 0 }}</td>
                                                                                                    <td>{{ $order->barcode ?? '-' }}</td>
                                                                                                    <td>₹{{ $order->amount ?? 0 }}</td>
                                                                                                    <td>{{ $order->payment_mode ?? '-' }}</td>
                                                                                                    <td>{{ $order->shipping_address ?? '-' }}</td>
                                                                                                    <td>{{ $order->city ?? '-' }}</td>
                                                                                                    <td>{{ $order->state ?? '-' }}</td>
                                                                                                    <td>{{ $order->pincode ?? '-' }}</td>
                                                                                                    <td>{{ $order->order_date ? \Carbon\Carbon::parse($order->order_date)->format('d-m-Y H:i') : '-' }}
                                                                                                    </td>
                                                                                                    <td>{{ $order->created_at ? \Carbon\Carbon::parse($order->created_at)->format('d-m-Y H:i') : '-' }}
                                                                                                    </td>
                                                                                                </tr>
@empty
                                                                                                <tr>
                                                                                                    <td colspan="18" class="text-center">No records found</td>
                                                                                                </tr>
    @endforelse
                                                                                        </tbody>
                                                                                    </table>
                                                                                    </div>

                                                                                    @if ($orders->count())
    <div class="card-footer">
                                                                                                                                                                                                                                                                                                                                        {{ $orders->links() }}
                                                                                                                                                                                                                                                                                                                                    </div>
    @endif
                                                                                                                                                                                                                                                                                                                            </div>-->


        @if (session('show_export_card'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {

                    // Step 1: Reload page once AFTER download
                    if (!sessionStorage.getItem('exportReloaded')) {
                        sessionStorage.setItem('exportReloaded', '1');
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                        return; // STOP here (do not show card yet)
                    }

                    // Step 2: After reload → show card
                    let card = document.getElementById('exportCard');
                    if (card) {
                        card.style.display = 'block';
                    }

                    // OPTIONAL: auto-open modal
                    let modalEl = document.getElementById('senderModal');
                    if (modalEl) {
                        let modal = new bootstrap.Modal(modalEl);
                        modal.show();
                    }

                    // Cleanup
                    sessionStorage.removeItem('exportReloaded');
                });
            </script>
        @endif



    </div>
@endsection
