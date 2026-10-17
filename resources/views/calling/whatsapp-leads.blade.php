@extends('layouts.calling')

@section('content')
    <style>
        .wa-page {
            color: #1e293b;
        }

        .wa-page .wa-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(15, 23, 42, .04);
        }

        .wa-page .wa-title {
            font-size: 23px;
            font-weight: 700;
        }

        .wa-page .wa-muted {
            color: #64748b;
        }

        .wa-page .wa-client-tabs {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .wa-page .wa-client-tab {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 13px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #334155;
            text-decoration: none;
            background: #fff;
            font-size: 13px;
        }

        .wa-page .wa-client-tab.active {
            color: #fff;
            background: #12805c;
            border-color: #12805c;
        }

        .wa-page .wa-count {
            background: rgba(100, 116, 139, .12);
            padding: 2px 7px;
            border-radius: 20px;
            font-size: 11px;
        }

        .wa-page .wa-client-tab.active .wa-count {
            background: rgba(255, 255, 255, .2);
        }

        .wa-page .table thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .04em;
            white-space: nowrap;
            padding: 13px 12px;
        }

        .wa-page .table tbody td {
            padding: 13px 12px;
            vertical-align: middle;
        }

        .wa-page .wa-badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .wa-page .wa-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .wa-page .wa-not-reachable {
            background: #ffedd5;
            color: #9a3412;
        }

        .wa-page .wa-verified {
            background: #dcfce7;
            color: #166534;
        }

        .wa-page .wa-same-order {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .wa-page .wa-cancel {
            background: #fee2e2;
            color: #991b1b;
        }

        .wa-page .wa-other {
            background: #e2e8f0;
            color: #334155;
        }

        .wa-page .wa-mobile-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
            background: #fff;
        }

        .wa-page .wa-field-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .wa-page .wa-field-value {
            font-size: 13px;
            color: #1e293b;
            overflow-wrap: anywhere;
        }

        .wa-page .wa-phone {
            color: #087f5b;
            font-weight: 600;
            text-decoration: none;
        }

        .wa-page .wa-action-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .wa-page .wa-action-row form {
            margin: 0;
        }

        .wa-page .wa-action-row .btn {
            font-size: 12px;
        }

        /* Edit modal: the form is a flex child of modal-content. */
        .wa-modal .modal-dialog {
            width: calc(100% - 1rem);
            max-width: 760px;
            margin: .5rem auto;
        }

        .wa-modal .modal-content {
            height: calc(100vh - 1rem);
            max-height: calc(100vh - 1rem);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 0;
            border-radius: 14px;
        }

        .wa-modal .modal-content>form {
            display: flex;
            flex-direction: column;
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
        }

        .wa-modal .modal-header,
        .wa-modal .modal-footer {
            flex: 0 0 auto;
            background: #fff;
            z-index: 2;
        }

        .wa-modal .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto !important;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
        }

        .wa-modal .form-label {
            font-size: 13px;
            font-weight: 600;
        }

        .wa-modal .form-control,
        .wa-modal .form-select {
            min-height: 42px;
        }

        .wa-modal textarea.form-control {
            min-height: 90px;
        }

        @media (min-width:768px) {
            .wa-modal .modal-dialog {
                margin: 1rem auto;
            }

            .wa-modal .modal-content {
                height: calc(100vh - 2rem);
                max-height: calc(100vh - 2rem);
            }
        }

        @media (max-width:767px) {
            .wa-page .wa-title {
                font-size: 20px;
            }

            .wa-page .wa-mobile-card .wa-action-row>* {
                flex: 1 1 45%;
            }

            .wa-page .wa-mobile-card .wa-action-row .btn {
                width: 100%;
            }
        }
    </style>

    <div class="container-fluid wa-page py-3">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">



        </div>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Please check the following:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Client tabs --}}
        <div class="wa-card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <strong>Filter by Client</strong>
                <span class="small wa-muted">Select a client to filter leads</span>
            </div>

            <div class="wa-client-tabs">
                <a href="{{ request()->fullUrlWithQuery(['client_id' => null]) }}"
                    class="wa-client-tab {{ !request()->filled('client_id') ? 'active' : '' }}">
                    All Clients
                    <span class="wa-count">{{ $orders->count() }}</span>
                </a>

                @foreach ($clients as $client)
                    <a href="{{ request()->fullUrlWithQuery(['client_id' => $client->client_id]) }}"
                        class="wa-client-tab {{ (string) request('client_id') === (string) $client->client_id ? 'active' : '' }}">
                        {{ $client->client_name ?? 'Client #' . $client->client_id }}
                        <span class="wa-count">{{ $client->total }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Search --}}
        <div class="wa-card p-3 mb-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <input type="search" id="waSearch" class="form-control"
                        placeholder="Search by order ID, customer, phone, product, city...">
                </div>
                <div class="col-md-6 text-md-end">
                    <span class="small wa-muted">
                        Search works on the leads currently displayed.
                    </span>
                </div>
            </div>
        </div>

        {{-- Desktop table --}}
        <div class="wa-card d-none d-md-block overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="waLeadsTable">
                    <thead>
                        <tr>
                            <th>Order / Date</th>
                            <th>Customer</th>
                            <th>Product</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th style="min-width:220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            @php
                                $status = strtolower(trim((string) ($order->status ?? 'pending')));
                                $badgeClass = match ($status) {
                                    'pending' => 'wa-pending',
                                    'not_reachable', 'not reachable', 'not-reachable' => 'wa-not-reachable',
                                    'verified' => 'wa-verified',
                                    'same_order', 'same order' => 'wa-same-order',
                                    'cancel' => 'wa-cancel',
                                    default => 'wa-other',
                                };
                                $statusText = match ($status) {
                                    'not_reachable', 'not-reachable' => 'Not Reachable',
                                    'same_order' => 'Same Order',
                                    default => ucwords(str_replace('_', ' ', $status)),
                                };
                            @endphp

                            <tr class="wa-lead-row">
                                <td>
                                    <div class="fw-bold">#{{ $order->order_id ?? $order->id }}</div>
                                    <div class="small wa-muted">
                                        {{ !empty($order->order_date) ? \Carbon\Carbon::parse($order->order_date)->format('d M Y') : '—' }}
                                    </div>
                                    @if (!empty($order->lead_type) && $order->lead_type === 'shared')
                                        <span class="badge bg-info text-dark mt-1">Shared Lead</span>
                                    @endif
                                </td>

                                <td>
                                    <div class="fw-semibold">{{ $order->customer_name ?? '—' }}</div>
                                    @if (!empty($order->father_name))
                                        <div class="small wa-muted">S/O: {{ $order->father_name }}</div>
                                    @endif
                                </td>

                                <td>
                                    <div>{{ $order->product_name ?? '—' }}</div>
                                    @if (isset($order->quantity) && $order->quantity !== '')
                                        <div class="small wa-muted">Qty: {{ $order->quantity }}</div>
                                    @endif
                                </td>

                                <td>
                                    @if (!empty($order->customer_phone))
                                        <a class="wa-phone" href="tel:{{ $order->customer_phone }}">
                                            {{ $order->customer_phone }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>

                                <td style="min-width:180px; max-width:260px;">
                                    <div>{{ $order->shipping_address ?? '' }}</div>
                                    <div class="small wa-muted">
                                        {{ $order->city ?? '' }}{{ !empty($order->state) ? ', ' . $order->state : '' }}
                                        {{ !empty($order->pincode) ? ' - ' . $order->pincode : '' }}
                                    </div>
                                </td>

                                <td>
                                    <div>{{ $order->payment_mode ?? '—' }}</div>
                                    <div class="small fw-semibold">
                                        ₹{{ number_format((float) ($order->amount ?? 0), 2) }}
                                    </div>
                                </td>

                                <td>
                                    <span class="wa-badge {{ $badgeClass }}">{{ $statusText }}</span>
                                </td>

                                <td>
                                    <div class="wa-action-row">
                                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                                            data-bs-target="#editWhatsapp{{ $order->id }}">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>

                                        @if (!empty($order->customer_phone))
                                            <a href="tel:{{ $order->customer_phone }}"
                                                class="btn btn-outline-success btn-sm">
                                                <i class="bi bi-telephone"></i> Call
                                            </a>
                                        @endif
                                    </div>

                                    <form method="POST" action="{{ route('calling.whatsapp-leads.status', $order->id) }}"
                                        class="mt-2 d-flex gap-1">
                                        @csrf
                                        <select name="status" class="form-select form-select-sm" required>
                                            <option value="verified">Verified</option>
                                            <option value="same_order">Same Order</option>
                                            <option value="not_reachable">Not Reachable</option>
                                            <option value="cancel">Cancel</option>
                                        </select>
                                        <button type="submit" class="btn btn-success btn-sm" title="Update status">
                                            Save
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 wa-muted">
                                    <div class="fs-5 mb-1">No WhatsApp leads found</div>
                                    <div class="small">There are no matching pending or not-reachable leads.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Mobile cards --}}
        <div class="d-md-none" id="waMobileCards">
            @forelse($orders as $order)
                @php
                    $status = strtolower(trim((string) ($order->status ?? 'pending')));
                    $badgeClass = match ($status) {
                        'pending' => 'wa-pending',
                        'not_reachable', 'not reachable', 'not-reachable' => 'wa-not-reachable',
                        'verified' => 'wa-verified',
                        'same_order', 'same order' => 'wa-same-order',
                        'cancel' => 'wa-cancel',
                        default => 'wa-other',
                    };
                    $statusText = match ($status) {
                        'not_reachable', 'not-reachable' => 'Not Reachable',
                        'same_order' => 'Same Order',
                        default => ucwords(str_replace('_', ' ', $status)),
                    };
                @endphp

                <div class="wa-mobile-card wa-lead-row">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                        <div>
                            <div class="fw-bold">#{{ $order->order_id ?? $order->id }}</div>
                            <div class="small wa-muted">
                                {{ !empty($order->order_date) ? \Carbon\Carbon::parse($order->order_date)->format('d M Y') : '—' }}
                            </div>
                        </div>
                        <span class="wa-badge {{ $badgeClass }}">{{ $statusText }}</span>
                    </div>

                    <div class="fw-semibold mb-1">{{ $order->customer_name ?? '—' }}</div>
                    @if (!empty($order->father_name))
                        <div class="small wa-muted mb-2">Father Name: {{ $order->father_name }}</div>
                    @endif

                    <div class="mb-3">
                        <div class="wa-field-label">Product</div>
                        <div class="wa-field-value">{{ $order->product_name ?? '—' }}</div>
                        <div class="small wa-muted">
                            Qty: {{ $order->quantity ?? '—' }}
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="wa-field-label">Phone</div>
                        @if (!empty($order->customer_phone))
                            <a class="wa-phone" href="tel:{{ $order->customer_phone }}">
                                {{ $order->customer_phone }}
                            </a>
                        @else
                            <div class="wa-field-value">—</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <div class="wa-field-label">Address</div>
                        <div class="wa-field-value">{{ $order->shipping_address ?? '' }}</div>
                        <div class="small wa-muted">
                            {{ $order->city ?? '' }}{{ !empty($order->state) ? ', ' . $order->state : '' }}
                            {{ !empty($order->pincode) ? ' - ' . $order->pincode : '' }}
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="wa-field-label">Payment</div>
                            <div class="wa-field-value">{{ $order->payment_mode ?? '—' }}</div>
                        </div>
                        <div class="text-end">
                            <div class="wa-field-label">Amount</div>
                            <div class="fw-bold">₹{{ number_format((float) ($order->amount ?? 0), 2) }}</div>
                        </div>
                    </div>

                    <div class="wa-action-row mb-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#editWhatsapp{{ $order->id }}">
                            Edit Lead
                        </button>

                        @if (!empty($order->customer_phone))
                            <a href="tel:{{ $order->customer_phone }}" class="btn btn-outline-success btn-sm">
                                Call Customer
                            </a>
                        @endif
                    </div>

                    <form method="POST" action="{{ route('calling.whatsapp-leads.status', $order->id) }}"
                        class="d-flex gap-2">
                        @csrf
                        <select name="status" class="form-select form-select-sm" required>
                            <option value="verified">Verified</option>
                            <option value="same_order">Same Order</option>
                            <option value="not_reachable">Not Reachable</option>
                            <option value="cancel">Cancel</option>
                        </select>
                        <button type="submit" class="btn btn-success btn-sm">Save</button>
                    </form>
                </div>
            @empty
                <div class="wa-card text-center p-4 wa-muted">
                    No matching WhatsApp leads found.
                </div>
            @endforelse
        </div>

    </div>

    {{-- Edit modals: kept outside the table/cards to prevent broken markup --}}
    @foreach ($orders as $order)
        <div class="modal fade wa-modal" id="editWhatsapp{{ $order->id }}" tabindex="-1"
            aria-labelledby="editWhatsappLabel{{ $order->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <form method="POST" action="{{ route('calling.whatsapp-leads.update', $order->id) }}">
                        @csrf

                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title fw-bold" id="editWhatsappLabel{{ $order->id }}">
                                    Edit WhatsApp Lead
                                </h5>
                                <div class="small wa-muted">
                                    Order #{{ $order->order_id ?? $order->id }}
                                </div>
                            </div>

                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>

                        {{-- This body scrolls while header/footer stay visible --}}
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Customer Name</label>
                                    <input type="text" name="customer_name" class="form-control"
                                        value="{{ old('customer_name', $order->customer_name ?? '') }}" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Customer Phone</label>
                                    <input type="text" name="customer_phone" class="form-control"
                                        value="{{ old('customer_phone', $order->customer_phone ?? '') }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Product Name</label>
                                    <input type="text" name="product_name" class="form-control"
                                        value="{{ old('product_name', $order->product_name ?? '') }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Father Name</label>
                                    <input type="text" name="father_name" class="form-control"
                                        value="{{ old('father_name', $order->father_name ?? '') }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" min="1" name="quantity" class="form-control"
                                        value="{{ old('quantity', $order->quantity ?? 1) }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Payment Mode</label>
                                    <select name="payment_mode" class="form-select">
                                        <option value="">Select Payment Mode</option>
                                        <option value="COD"
                                            {{ strtoupper((string) old('payment_mode', $order->payment_mode ?? '')) === 'COD' ? 'selected' : '' }}>
                                            COD
                                        </option>
                                        <option value="Prepaid"
                                            {{ strtoupper((string) old('payment_mode', $order->payment_mode ?? '')) === 'PREPAID' ? 'selected' : '' }}>
                                            Prepaid
                                        </option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Amount</label>
                                    <input type="number" step="0.01" min="0" name="amount"
                                        class="form-control" value="{{ old('amount', $order->amount ?? '') }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Age</label>
                                    <input type="number" min="0" name="age" class="form-control"
                                        value="{{ old('age', $order->age ?? '') }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">City</label>
                                    <input type="text" name="city" class="form-control"
                                        value="{{ old('city', $order->city ?? '') }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">State</label>
                                    <input type="text" name="state" class="form-control"
                                        value="{{ old('state', $order->state ?? '') }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Pincode</label>
                                    <input type="text" name="pincode" class="form-control"
                                        value="{{ old('pincode', $order->pincode ?? '') }}">
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Shipping Address</label>
                                    <textarea name="shipping_address" class="form-control" rows="3">{{ old('shipping_address', $order->shipping_address ?? '') }}</textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Remarks</label>
                                    <textarea name="remarks" class="form-control" rows="3">{{ old('remarks', $order->remarks ?? '') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                                Close
                            </button>
                            <button type="submit" class="btn btn-success px-4">
                                Save Changes
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    @endforeach

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('waSearch');

            if (!searchInput) return;

            searchInput.addEventListener('input', function() {
                const term = this.value.toLowerCase().trim();

                document.querySelectorAll('.wa-lead-row').forEach(function(row) {
                    const text = (row.innerText || row.textContent || '').toLowerCase();
                    row.style.display = text.includes(term) ? '' : 'none';
                });
            });
        });
    </script>
@endsection
