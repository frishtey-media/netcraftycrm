@extends('layouts.admin')

@section('content')
    <div class="container-fluid py-4">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <div>
                <h3 class="mb-1">WhatsApp Fresh Leads</h3>
                <p class="text-muted mb-0">
                    Client: {{ $client->client_name }}
                    | Last 3 days:
                    {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}
                </p>
            </div>

            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                Back
            </a>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
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

        {{-- Share Form --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Share Fresh Leads With Another Staff Member</h5>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('whatsapp.fresh-leads.assign') }}">
                    @csrf

                    <input type="hidden" name="client_id" value="{{ $clientId }}">

                    <div class="row align-items-end">
                        <div class="col-md-5 mb-3">
                            <label for="staff_id" class="form-label">
                                Select New Staff
                            </label>

                            <select name="staff_id" id="staff_id" class="form-control" required>
                                <option value="">-- Select Staff --</option>

                                @foreach ($staff as $member)
                                    <option value="{{ $member->id }}"
                                        {{ old('staff_id') == $member->id ? 'selected' : '' }}>
                                        {{ $member->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3 mb-3">
                            <div class="text-muted">
                                Selected leads:
                                <strong id="selectedCount">0</strong>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-share-alt"></i>
                                Share Selected Leads
                            </button>
                        </div>
                    </div>

                    {{-- Leads Table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:50px">
                                        <input type="checkbox" id="selectAll">
                                    </th>
                                    <th>Order ID</th>
                                    <th>Customer Name</th>
                                    <th>Phone</th>
                                    <th>Product</th>
                                    <th>Original Staff</th>
                                    <th>Shared With</th>
                                    <th>Source</th>
                                    <th>Status</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($leads as $lead)
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="lead_ids[]" value="{{ $lead->id }}"
                                                class="lead-checkbox">
                                        </td>

                                        <td>{{ $lead->order_id }}</td>

                                        <td>{{ $lead->customer_name ?: '-' }}</td>

                                        <td>{{ $lead->customer_phone ?: '-' }}</td>

                                        <td>{{ $lead->product_name ?: '-' }}</td>

                                        <td>
                                            <span class="fw-semibold">
                                                {{ $lead->assigned_staff_name }}
                                            </span>
                                        </td>

                                        <td>
                                            @if (!empty($lead->shared_staff_names))
                                                {{ implode(', ', $lead->shared_staff_names) }}
                                            @else
                                                <span class="text-muted">Not shared</span>
                                            @endif
                                        </td>

                                        <td>{{ $lead->order_source ?: 'Web' }}</td>

                                        <td>
                                            @php
                                                $status = strtolower(trim($lead->status ?? 'pending'));
                                            @endphp

                                            <span
                                                class="badge
                                            {{ $status === 'pending'
                                                ? 'bg-warning text-dark'
                                                : ($status === 'verified'
                                                    ? 'bg-success'
                                                    : (in_array($status, ['cancel', 'same_order'])
                                                        ? 'bg-danger'
                                                        : 'bg-secondary')) }}">
                                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            No fresh leads found for these dates.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="text-muted small mt-2">
                        Sharing a lead does not change its original staff assignment or order ID.
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = Array.from(document.querySelectorAll('.lead-checkbox'));
            const selectedCount = document.getElementById('selectedCount');

            function updateCount() {
                const checkedCount = checkboxes.filter(cb => cb.checked).length;

                selectedCount.textContent = checkedCount;

                selectAll.checked =
                    checkboxes.length > 0 && checkedCount === checkboxes.length;

                selectAll.indeterminate =
                    checkedCount > 0 && checkedCount < checkboxes.length;
            }

            selectAll.addEventListener('change', function() {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
                updateCount();
            });

            checkboxes.forEach(cb => cb.addEventListener('change', updateCount));
            updateCount();
        });
    </script>
@endsection
