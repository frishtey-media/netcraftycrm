@extends('layouts.inventory')

@section('content')
    <div class="container">

        <h2 class="mb-4">Inventory Dashboard</h2>

        <div class="row mb-4">

            <div class="col-md-3">
                <div class="card text-center shadow">
                    <div class="card-body">
                        <h5>Total Inventory</h5>
                        <h3 class="text-primary">{{ $totalProducts }}</h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-center shadow">
                    <div class="card-body">
                        <h5>Total Sales</h5>
                        <h3 class="text-primary"> {{ $totalSales }}</h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-center shadow">
                    <div class="card-body">
                        <h5>Current Month Sale</h5>
                        <h3 class="text-success">
                            {{ $monthlySales[date('n')] ?? '0 sale this month' }}
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-center shadow">
                    <div class="card-body">
                        <h5>Current Month RTO</h5>
                        <h3 class="text-danger">
                            {{ $totalRTO }}
                        </h3>
                    </div>
                </div>
            </div>

        </div>


        <div class="card shadow p-4">
            <h4>Monthly Sales Graph</h4>

            <!-- IMPORTANT HEIGHT -->
            <canvas id="salesChart" style="height:300px;"></canvas>

        </div>

    </div>
@endsection



@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            let salesData = @json($monthlySales);
            let rtoData = @json($monthlyRTO);

            let labels = [
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
            ];

            let sales = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
            let rto = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

            for (let month in salesData) {
                sales[month - 1] = salesData[month];
            }

            for (let month in rtoData) {
                rto[month - 1] = rtoData[month];
            }

            const ctx = document.getElementById('salesChart');

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                            label: 'Sales ₹',
                            data: sales,
                            backgroundColor: 'green'
                        },
                        {
                            label: 'RTO Orders',
                            data: rto,
                            backgroundColor: 'red'
                        }
                    ]
                }
            });

        });
    </script>
@endsection
