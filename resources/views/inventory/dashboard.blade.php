@extends('layouts.inventory')

@section('content')
    <style>
        @keyframes bounceAlert {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-6px);
            }
        }

        .low-stock-alert {
            animation: bounceAlert 1s infinite;
            border-left: 6px solid red;
        }
    </style>

    <div class="container">

        <h2 class="mb-4">Inventory Dashboard</h2>

        {{-- ================= STATS ================= --}}
        <div class="row mb-4">

            <div class="col-md-3">
                <div class="card text-center shadow">
                    <div class="card-body">
                        <h5>Total Inventory Products</h5>
                        <a href="/inventory/productreport">
                            <h3 class="text-primary">{{ $totalProducts }}</h3>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-center shadow">
                    <div class="card-body">
                        <h5>Total Sales</h5>
                        <a href="/inventory/salesreport">
                            <h3 class="text-primary">{{ $totalSales }}</h3>
                        </a>
                    </div>
                </div>
            </div>

            {{-- CURRENT MONTH SALE --}}
            <div class="col-md-3">
                <div class="card text-center shadow">
                    <div class="card-body">
                        <h5>Current Month Sale</h5>

                        @php
                            $currentMonth = date('n');
                            $currentMonthSale = 0;

                            foreach ($salesData as $product) {
                                if (isset($product[$currentMonth])) {
                                    $currentMonthSale += $product[$currentMonth];
                                }
                            }
                        @endphp

                        <h3 class="text-success">{{ $currentMonthSale }}</h3>
                    </div>
                </div>
            </div>

            {{-- CURRENT MONTH RTO --}}
            <div class="col-md-3">
                <div class="card text-center shadow">
                    <div class="card-body">
                        <h5>Current Month RTO</h5>

                        @php
                            $currentMonth = date('n');
                            $currentMonthRTO = 0;

                            foreach ($rtoData as $product) {
                                if (isset($product[$currentMonth])) {
                                    $currentMonthRTO += $product[$currentMonth];
                                }
                            }
                        @endphp

                        <h3 class="text-danger">{{ $currentMonthRTO }}</h3>
                    </div>
                </div>
            </div>

        </div>

        {{-- ================= MAIN CARD ================= --}}
        <div class="card shadow p-4">

            {{-- 🔥 LOW STOCK ALERT --}}
            @if ($lowStockList->count() > 0)
                <div id="lowStockBox" class="alert alert-danger low-stock-alert">
                    <h5>⚠ Low Stock Alert</h5>
                    <ul class="mb-0">
                        @foreach ($lowStockList as $item)
                            <li>
                                <b>{{ $item['name'] }}</b> - Only {{ $item['qty'] }} left
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div id="lowStockBox" style="display:none;"></div>
            @endif

            {{-- PRODUCT SELECT --}}
            <select id="productSelect" class="form-control mb-3">
                @foreach ($products as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>

            <h4>Monthly Sales Graph</h4>

            <div style="height:300px;">
                <canvas id="salesChart"></canvas>
            </div>

        </div>

    </div>
@endsection


@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        let salesData = {!! json_encode($salesData) !!};
        let rtoData = {!! json_encode($rtoData) !!};

        let chart;

        function loadChart(productId) {

            let labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

            let sales = Array(12).fill(0);
            let rto = Array(12).fill(0);

            if (salesData[productId]) {
                Object.keys(salesData[productId]).forEach(m => {
                    sales[m - 1] = salesData[productId][m];
                });
            }

            if (rtoData[productId]) {
                Object.keys(rtoData[productId]).forEach(m => {
                    rto[m - 1] = rtoData[productId][m];
                });
            }

            const ctx = document.getElementById('salesChart').getContext('2d');

            if (chart) chart.destroy();

            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Sold Quantity',
                            data: sales,
                            borderColor: 'green',
                            backgroundColor: 'rgba(0,128,0,0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: 'RTO Quantity',
                            data: rto,
                            borderColor: 'red',
                            backgroundColor: 'rgba(255,0,0,0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + " pcs";
                                }
                            }
                        }
                    }
                }
            });
        }

        document.addEventListener("DOMContentLoaded", function() {

            let select = document.getElementById('productSelect');

            loadChart(select.value);

            select.addEventListener('change', function() {
                loadChart(this.value);
            });

        });
    </script>


    <script>
        setInterval(() => {
            fetch('/api/low-stock')
                .then(res => res.json())
                .then(res => {

                    let box = document.getElementById('lowStockBox');

                    if (res.data.length > 0) {

                        let html = `<h5>⚠ Low Stock Alert</h5><ul>`;

                        res.data.forEach(item => {
                            html += `<li><b>${item.name}</b> - Only ${item.stock} left</li>`;
                        });

                        html += `</ul>`;

                        box.innerHTML = html;
                        box.style.display = 'block';

                    } else {
                        box.style.display = 'none';
                    }

                });
        }, 10000);
    </script>
@endsection
