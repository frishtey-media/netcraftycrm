@extends('layouts.admin')

@section('content')
    <style>
        .dashboard-card {
            border-radius: 16px;
            padding: 22px;
            min-height: 120px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        }

        .dashboard-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.12);
        }

        .card-green {
            background: linear-gradient(135deg, #dff3ea, #c8eadb);
        }

        .card-icon {
            font-size: 42px;
            opacity: 0.9;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
        }

        .card-count {
            font-size: 30px;
            font-weight: 700;
        }
    </style>

    <div class="container">

        <div class="row g-4">

            {{-- ORDERS CARDS --}}
            @foreach ($ordersData as $data)
                <div class="col-md-4">
                    <div class="dashboard-card card-green"
                        onclick="openAssignModal({{ $data['client_id'] }}, {{ $data['total_orders'] }})">

                        <div>
                            <div class="card-title"> Shopify Orders</div>
                            <spam>{{ $data['client_name'] }}</spam>
                            <div class="card-count">{{ $data['total_orders'] }}</div>
                            <small>Pending Orders</small>
                        </div>

                        <i class="bi bi-cart-check card-icon"></i>
                    </div>
                </div>
            @endforeach

            @foreach ($ordersData as $data)
                <div class="col-md-4">
                    <div class="dashboard-card card-green"
                        onclick="openAssignModal1({{ $data['client_id'] }}, {{ $data['rto_pending'] }})">

                        <div>
                            <div class="card-title"> RTO Received</div>
                            <spam>{{ $data['client_name'] }}</spam>
                            <div class="card-count"> {{ $data['rto_pending'] }}</div>

                            <small>Pending Orders</small>
                        </div>

                        <i class="bi bi-cart-check card-icon"></i>
                    </div>
                </div>
            @endforeach


        </div>

    </div>

    {{-- ASSIGN MODAL --}}
    <div class="modal fade" id="assignModal">
        <div class="modal-dialog">
            <div class="modal-content p-3">

                <h5>Assign Orders</h5>

                <p>Total Orders: <strong id="totalOrders"></strong></p>

                <form method="POST" action="{{ route('assign.orders') }}">
                    @csrf

                    <input type="hidden" name="client_id" id="client_id">

                    @foreach ($allStaff as $staff)
                        <div class="d-flex justify-content-between mb-2">
                            <label>{{ $staff->name }}</label>

                            <input type="number" name="assign[{{ $staff->id }}]" class="form-control w-25"
                                min="0" placeholder="0">
                        </div>
                    @endforeach

                    <button class="btn btn-primary mt-3 w-100">
                        Assign Orders
                    </button>
                </form>

            </div>
        </div>
    </div>
    <div class="modal fade" id="assignModal1">
        <div class="modal-dialog">
            <div class="modal-content p-3">

                <h5>Assign Orders</h5>

                <p>Total Orders: <strong id="totalOrders1"></strong></p>

                <form method="POST" action="{{ route('assign.rto.orders') }}">
                    @csrf

                    <input type="hidden" name="client_id" id="client_id1">

                    @foreach ($allStaff as $staff)
                        <div class="d-flex justify-content-between mb-2">
                            <label>{{ $staff->name }}</label>

                            <input type="number" name="assign[{{ $staff->id }}]" class="form-control w-25"
                                min="0" placeholder="0">
                        </div>
                    @endforeach

                    <button class="btn btn-primary mt-3 w-100">
                        Assign Orders
                    </button>
                </form>

            </div>
        </div>
    </div>
    <script>
        function openAssignModal(clientId, totalOrders) {
            document.getElementById('client_id').value = clientId;
            document.getElementById('totalOrders').innerText = totalOrders;

            new bootstrap.Modal(document.getElementById('assignModal')).show();
        }
    </script>
    <script>
        function openAssignModal1(clientId, totalOrders) {
            document.getElementById('client_id1').value = clientId;
            document.getElementById('totalOrders1').innerText = totalOrders;

            new bootstrap.Modal(document.getElementById('assignModal1')).show();
        }
    </script>
@endsection
