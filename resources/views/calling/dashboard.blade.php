@extends('layouts.calling')

@section('title', 'My Orders')

@section('content')
    <style>
        .stat-card {
            color: white;
            padding: 18px;
            border-radius: 14px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            transition: 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-card h6 {
            font-size: 13px;
            opacity: 0.9;
        }

        .stat-card h2 {
            font-weight: bold;
        }
    </style>
    <div class="row g-3 mb-4">

        <div class="col-6 col-md-3">
            <div class="card stat-card bg-primary">
                <h6>Web Orders</h6>
                <h2>{{ $total }}</h2>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card stat-card" style="background:#25D366;">
                <h6>WhatsApp</h6>
                <h2>{{ $whatsappOrders }}</h2>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card stat-card bg-success">
                <h6>Verified</h6>
                <h2>{{ $verified }}</h2>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card stat-card bg-danger">
                <h6>Not Reachable</h6>
                <h2>{{ $notReachable }}</h2>
            </div>
        </div>

    </div>

    <!-- 🎯 SUCCESS RATE -->
    <div class="card p-3 mb-4 shadow-sm">
        <h6>🎯 Conversion Rate</h6>

        <div class="progress" style="height: 20px;">
            <div class="progress-bar bg-success" style="width: {{ $successRate }}%">
                {{ $successRate }}%
            </div>
        </div>
    </div>

    <!-- 📊 GRAPH -->
    <div class="card p-3 shadow-sm">
        <h5>📊 Monthly Performance</h5>
        <canvas id="ordersChart" height="120"></canvas>
    </div>

    @push('scripts')
        <script>
            document.addEventListener("DOMContentLoaded", function() {

                const ctx = document.getElementById('ordersChart');

                if (!ctx) return;

                new Chart(ctx, {
                    data: {
                        labels: @json($months),
                        datasets: [

                            {
                                type: 'bar',
                                label: 'Web Orders',
                                data: @json($webData),
                                backgroundColor: 'rgba(0,123,255,0.6)'
                            },

                            {
                                type: 'bar',
                                label: 'WhatsApp',
                                data: @json($waData),
                                backgroundColor: 'rgba(40,167,69,0.6)'
                            },

                            {
                                type: 'line',
                                label: 'Verified',
                                data: @json($verifiedData),
                                borderColor: '#198754',
                                tension: 0.4
                            },

                            {
                                type: 'line',
                                label: 'Not Reachable',
                                data: @json($nrData),
                                borderColor: 'red',
                                tension: 0.4
                            }

                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top'
                            }
                        }
                    }
                });

            });
        </script>
    @endpush
@endsection
