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
            border: solid 1px #141c2b;
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
            font-size: 26px;
            font-weight: 600;
        }

        .card-count {
            font-size: 30px;
            font-weight: 700;
        }
    </style>

    <div class="container">


        <div class="row g-4">

            @foreach ($clients as $client)
                <div class="col-md-4">
                    <div class="dashboard-card card-green"
                        onclick="window.location='{{ route('ordersdashboard', $client->id) }}'">

                        <div onclick="window.location='{{ route('ordersdashboard', $client->id) }}'">
                            <div class="card-title">{{ $client->client_name }}</div>
                            <spam>Click Here</spam>
                        </div>


                    </div>
                </div>
            @endforeach
        </div>

    </div>
@endsection
