@extends('layouts.admin')

@section('content')
    <div class="container-fluid">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold">📊 Staff Performance Dashboard</h4>

            <div class="d-flex gap-2">
                <a href="?from={{ date('Y-m-d') }}&to={{ date('Y-m-d') }}" class="btn btn-sm btn-primary">Today</a>

                <a href="?from={{ date('Y-m-d', strtotime('-1 day')) }}&to={{ date('Y-m-d', strtotime('-1 day')) }}"
                    class="btn btn-sm btn-outline-secondary">Yesterday</a>

                <a href="?from={{ date('Y-m-d', strtotime('-7 days')) }}&to={{ date('Y-m-d') }}"
                    class="btn btn-sm btn-dark">Last 7 Days</a>
            </div>
        </div>

        <!-- FILTER -->
        <div class="card shadow-sm mb-4">

            <div class="card-body">

                <form method="GET" action="{{ url()->current() }}" class="row g-3 align-items-end">

                    {{-- FROM DATE --}}
                    <div class="col-md-2 col-6">

                        <label class="form-label">
                            From
                        </label>

                        <input type="date" name="from" value="{{ \Carbon\Carbon::parse($from)->format('Y-m-d') }}"
                            class="form-control">

                    </div>


                    {{-- TO DATE --}}
                    <div class="col-md-2 col-6">

                        <label class="form-label">
                            To
                        </label>

                        <input type="date" name="to" value="{{ \Carbon\Carbon::parse($to)->format('Y-m-d') }}"
                            class="form-control">

                    </div>


                    {{-- CLIENT --}}
                    <div class="col-md-2 col-12">

                        <label class="form-label">
                            Client
                        </label>

                        <select name="client_id" class="form-select" {{ $isClientUser ? 'disabled' : '' }}>

                            @if (!$isClientUser)
                                <option value="">
                                    All Clients
                                </option>
                            @endif


                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}"
                                    {{ (string) $clientId === (string) $client->id ? 'selected' : '' }}>

                                    {{ $client->client_name }}

                                </option>
                            @endforeach

                        </select>

                        @if ($isClientUser)
                            <input type="hidden" name="client_id" value="{{ $clientId }}">
                        @endif

                    </div>


                    {{-- APPLY --}}
                    <div class="col-md-2 col-6">

                        <button type="submit" class="btn btn-primary w-100">
                            Apply
                        </button>

                    </div>


                    {{-- RESET --}}
                    <div class="col-md-2 col-6">

                        <a href="{{ url()->current() }}" class="btn btn-secondary w-100">
                            Reset
                        </a>

                    </div>

                    <!-- <div class="col-md-2 col-6">

                                                                                                                                                                                                                                                                                                <button type="button" class="btn btn-success" id="exportSelected">

                                                                                                                                                                                                                                                                                                    <i class="fas fa-file-excel"></i>
                                                                                                                                                                                                                                                                                                    Export Verify Selected

                                                                                                                                                                                                                                                                                                </button>



                                                                                                                                                                                                                                                                                            </div>-->


                </form>

            </div>

        </div>



        <div class="row mb-4">

            <div class="row g-3">

                {{-- TOTAL ORDERS --}}
                <div class="col-md-2 col-6 mb-2">
                    <div class="card bg-primary text-white p-3 text-center h-100">
                        <h6>Total Orders</h6>
                        <h3>{{ $totalOrders }}</h3>

                        <small>
                            Web: {{ $totalWeb }}
                            &nbsp; | &nbsp;
                            WhatsApp: {{ $totalWhatsapp }}
                        </small>

                        <small class="d-block">
                            RTO: {{ $totalRtoAll }}
                            &nbsp; | &nbsp;
                            Deliver Re-Order: {{ $totalDeliveredReorder }}
                        </small>

                        <small class="d-block">
                            Abandoned: {{ $totalAbandoned }}
                        </small>
                    </div>
                </div>


                {{-- PENDING --}}
                <div class="col-md-2 col-6 mb-2">
                    <div class="card bg-warning text-dark p-3 text-center h-100">
                        <h6>Pending</h6>
                        <h3>{{ $totalPending }}</h3>

                        <small>
                            Web: {{ $pendingWeb }}
                            &nbsp; | &nbsp;
                            WhatsApp: {{ $pendingWhatsapp }}
                        </small>

                        <small class="d-block">
                            RTO: {{ $pendingRto }}
                            &nbsp; | &nbsp;
                            Deliver Re-Order: {{ $pendingDeliveredReorder }}
                        </small>
                        <small class="d-block">
                            Abandoned: {{ $pendingAbandoned }}
                        </small>
                    </div>
                </div>


                {{-- VERIFIED --}}
                <div class="col-md-2 col-6 mb-2">
                    <div class="card bg-success text-white p-3 text-center h-100">
                        <h6>Verified</h6>
                        <h3>{{ $totalVerified }}</h3>

                        <small>
                            Web: {{ $verifiedWeb }}
                            &nbsp; | &nbsp;
                            WhatsApp: {{ $verifiedWhatsapp }}
                        </small>

                        <small class="d-block">
                            RTO: {{ $verifiedRto }}
                            &nbsp; | &nbsp;
                            Deliver Re-Order: {{ $verifiedDeliveredReorder }}
                        </small>
                        <small class="d-block">
                            Abandoned: {{ $verifiedAbandoned }}
                        </small>
                    </div>
                </div>


                {{-- CANCEL --}}
                <div class="col-md-2 col-6 mb-2">
                    <div class="card bg-danger text-white p-3 text-center h-100">
                        <h6>Cancel</h6>
                        <h3>{{ $totalCancel }}</h3>

                        <small>
                            Web: {{ $cancelWeb }}
                            &nbsp; | &nbsp;
                            WhatsApp: {{ $cancelWhatsapp }}
                        </small>

                        <small class="d-block">
                            RTO: {{ $cancelRto }}
                            &nbsp; | &nbsp;
                            Deliver Re-Order: {{ $cancelDeliveredReorder }}
                        </small>
                        <small class="d-block">
                            Abandoned: {{ $cancelAbandoned }}
                        </small>
                    </div>
                </div>


                {{-- NOT REACHABLE --}}
                <div class="col-md-2 col-6 mb-2">
                    <div class="card bg-dark text-white p-3 text-center h-100">
                        <h6>Not Reachable</h6>
                        <h3>{{ $totalNotReachable }}</h3>

                        <small>
                            Web: {{ $notReachableWeb }}
                            &nbsp; | &nbsp;
                            WhatsApp: {{ $notReachableWhatsapp }}
                        </small>

                        <small class="d-block">
                            RTO: {{ $notReachableRto }}
                            &nbsp; | &nbsp;
                            Deliver Re-Order: {{ $notReachableDeliveredReorder }}
                        </small>
                        <small class="d-block">
                            Abandoned: {{ $notReachableAbandoned }}
                        </small>
                    </div>
                </div>


                {{-- SAME ORDER --}}
                <div class="col-md-2 col-6 mb-2">
                    <div class="card bg-secondary text-white p-3 text-center h-100">
                        <h6>Same Order</h6>
                        <h3>{{ $totalSameOrder }}</h3>

                        <small>
                            Web: {{ $sameOrderWeb }}
                            &nbsp; | &nbsp;
                            WhatsApp: {{ $sameOrderWhatsapp }}
                        </small>

                        <small class="d-block">
                            RTO: {{ $sameOrderRto }}
                            &nbsp; | &nbsp;
                            Deliver Re-Order: {{ $sameOrderDeliveredReorder }}
                        </small>
                        <small class="d-block">
                            Abandoned: {{ $sameOrderAbandoned }}
                        </small>
                    </div>
                </div>


                {{-- OTHER --}}
                <div class="col-md-2 col-6 mb-2">
                    <div class="card bg-white border-primary p-3 text-center h-100">
                        <h6>Other</h6>
                        <h3>{{ $totalOther }}</h3>

                        <small>
                            Web: {{ $otherWeb }}
                            &nbsp; | &nbsp;
                            WhatsApp: {{ $otherWhatsapp }}
                        </small>

                        <small class="d-block">
                            RTO: {{ $otherRto }}
                            &nbsp; | &nbsp;
                            Deliver Re-Order: {{ $otherDeliveredReorder }}
                        </small>
                        <small class="d-block">
                            Abandoned: {{ $otherAbandoned }}
                        </small>
                    </div>
                </div>

            </div>
            <!--  <div class="col-md-2 col-6 mb-2">
                                                                                                                                                                                                        <div class="card bg-dark text-white p-3">
                                                                                                                                                                                                            <h6>WA Leads</h6>
                                                                                                                                                                                                            <h3>{{ $totalWA }}</h3>
                                                                                                                                                                                                        </div>
                                                                                                                                                                                                    </div>

                                                                                                                                                                                                    <div class="col-md-2 col-6 mb-2">
                                                                                                                                                                                                        <div class="card bg-info text-white p-3">
                                                                                                                                                                                                            <h6>WA Verified</h6>
                                                                                                                                                                                                            <h3>{{ $verifiedWA }}</h3>
                                                                                                                                                                                                        </div>
                                                                                                                                                                                                    </div>-->

        </div>

        <!-- TABLE -->
        <div class="card shadow-sm">
            <div class="card-header fw-bold">Staff Report</div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th width="50">

                                <input type="checkbox" id="checkAll">

                            </th>

                            <th>Staff</th>
                            <th>Clients</th>
                            <th>Total</th>
                            <th>Web Verified</th>
                            <th>WA Verified</th>
                            <th>RTO Verified</th>
                            <th>Re-Order Verified</th>

                            <th>Abandoned Verified</th>
                            <th>Pending</th>
                            <!-- <th>RTO</th>-->
                            <th>Not Reachable</th>
                            <th>Cancel</th>
                            <th>Same Order</th>
                            <th>Other</th>
                            <!--  <th>WA Total</th>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                  <th>WA Verified</th>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                   <th>WA Pending</th>
                                                                                                                                                                                                                                                                                                                                                                                                                                                                   <th>Combined %</th>-->
                            <th>Order %</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach ($staffs as $staff)
                            @php
                                $success =
                                    $staff->total_orders > 0
                                        ? round(
                                            (($staff->web_verified_orders + $staff->whatsapp_verified_orders) /
                                                max($staff->total_orders, 1)) *
                                                100,
                                            1,
                                        )
                                        : 0;

                                $combinedTotal = $staff->total_orders + $staff->wa_total;
                                $combinedVerified =
                                    $staff->web_verified_orders +
                                    $staff->whatsapp_verified_orders +
                                    ($staff->wa_verified ?? 0);

                                $combinedRate =
                                    $combinedTotal > 0 ? round(($combinedVerified / $combinedTotal) * 100, 1) : 0;
                            @endphp

                            <td>
                                <input type="checkbox" class="staff-checkbox" value="{{ $staff->id }}">
                            </td>

                            <td>

                                {{ $staff->name }}

                            </td>
                            <td>
                                @if (isset($clientWise[$staff->id]))
                                    @foreach ($clientWise[$staff->id] as $c)
                                        <div class="badge bg-light text-dark mb-1">
                                            {{ $c['client'] }} ({{ $c['total'] }})
                                        </div>
                                    @endforeach
                                @endif
                            </td>

                            <td>
                                <a href="{{ route('performance.orders', [
                                    'staff_id' => $staff->id,
                                    'from' => request('from'),
                                    'to' => request('to'),
                                ]) }}"
                                    class="badge bg-secondary text-decoration-none">
                                    {{ $staff->total_orders }}
                                </a>
                            </td>

                            <!-- WEB VERIFIED -->
                            <td>

                                <a href="{{ route('admin.staff.verified', [
                                    'staff_id' => $staff->id,
                                    'type' => 'web',
                                    'from' => request('from'),
                                    'to' => request('to'),
                                ]) }}"
                                    class="badge bg-success text-decoration-none">

                                    {{ $staff->web_verified_orders }}

                                </a>

                            </td>

                            <!-- WHATSAPP VERIFIED -->
                            <td>

                                <a href="{{ route('admin.staff.verified', [
                                    'staff_id' => $staff->id,
                                    'type' => 'whatsapp',
                                    'from' => request('from'),
                                    'to' => request('to'),
                                ]) }}"
                                    class="badge bg-primary text-decoration-none">

                                    {{ $staff->whatsapp_verified_orders }}

                                </a>

                            </td>
                            <td>
                                <span class="badge bg-success">
                                    {{ $staff->rto_verified_orders }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-success">
                                    {{ $staff->delivered_reorder_orders }}
                                </span>
                            </td>

                            <td>
                                <span class="badge bg-success">
                                    {{ $staff->abandoned_verified ?? 0 }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-warning text-dark" style="cursor:pointer"
                                    onclick="openShiftModal({{ $staff->id }})">
                                    {{ $staff->pending_orders }}
                                </span>
                            </td>
                            <!-- <td>
                                                                                                                                                                                                                    <span class="badge bg-danger">
                                                                                                                                                                                                                        {{ $staff->rto_orders }}
                                                                                                                                                                                                                    </span>
                                                                                                                                                                                                                </td>-->
                            <td><span class="badge bg-danger">{{ $staff->not_reachable_orders }}</span></td>
                            <td><span class="badge bg-danger">{{ $staff->cancel }}</span></td>
                            <td><span class="badge bg-danger">{{ $staff->same_order }}</span></td>
                            <td><span class="badge bg-danger">{{ $staff->other }}</span></td>
                            <!--<td><span class="badge bg-dark">{{ $staff->wa_total ?? 0 }}</span></td>

                                                                                                                                                                                                                                                                                                                                                                                                                                                                            <td><span class="badge bg-success">{{ $staff->wa_verified ?? 0 }}</span></td>

                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td><span class="badge bg-warning text-dark">{{ $staff->wa_pending ?? 0 }}</span></td>

                                                                                                                                                                                                                                                                                                                                                                                                                                                                                <td><strong>{{ $combinedRate }}%</strong></td>-->

                            <td><small>{{ $success }}%</small></td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>
            </div>
        </div>
        @if (auth()->user()->role == 'super_admin')
            <div class="modal fade" id="shiftModal">
                <div class="modal-dialog">
                    <div class="modal-content p-3">

                        <h5>Shift Pending Orders</h5>

                        <form method="POST" action="{{ route('shift.orders') }}">
                            @csrf

                            <input type="hidden" name="from_staff" id="from_staff">

                            {{-- NEW STAFF --}}
                            <div class="mb-2">
                                <label>Select Staff</label>
                                <select name="to_staff" class="form-control" required>
                                    <option value="">Select Staff</option>
                                    @foreach ($allStaff as $s)
                                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- REMARK --}}
                            <div class="mb-2">
                                <label>Remark</label>
                                <textarea name="remark" class="form-control" required></textarea>
                            </div>

                            <button class="btn btn-primary w-100 mt-2">
                                Shift Orders
                            </button>

                        </form>

                    </div>
                </div>
            </div>
        @endif
        <script>
            function openShiftModal(staffId) {
                document.getElementById('from_staff').value = staffId;

                let modal = new bootstrap.Modal(document.getElementById('shiftModal'));
                modal.show();
            }
        </script>
        <script>
            $('#checkAll').on('change', function() {

                $('.staff-checkbox').prop('checked', this.checked);

            });

            $(document).on('change', '.staff-checkbox', function() {

                $('#checkAll').prop(

                    'checked',

                    $('.staff-checkbox').length == $('.staff-checkbox:checked').length

                );

            });







            $('#exportSelected').click(function() {

                let ids = [];

                $('.staff-checkbox:checked').each(function() {
                    ids.push($(this).val());
                });

                if (ids.length == 0) {
                    alert('Please select staff');
                    return;
                }

                let url = "{{ route('performance.export.selected') }}?";

                ids.forEach(function(id) {
                    url += "staff_ids[]=" + id + "&";
                });

                url += "from={{ request('from') }}";
                url += "&to={{ request('to') }}";
                url += "&client_id={{ request('client_id') }}";

                window.location.href = url;
            });
        </script>
    </div>
@endsection
