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
            <div class="col-md-12">
                @if (auth()->user()->role == 'super_admin')
                    <a class="btn btn-primary"
                        style="
                                            margin: 20px;
                                            float: right;
                                        "
                        href="{{ route('assignment.scheduler') }}">
                        Order Assign Scheduler
                    </a>
                @endif
            </div>
        </div>

        <div class="row g-4">
            @foreach ($clients as $client)
                <div class="col-md-4">
                    <a href="{{ route('ordersdashboard', $client->id) }}"
                        class="dashboard-card card-green text-decoration-none d-block">

                        <div class="card-title">
                            {{ $client->client_name }}
                        </div>

                        <span>Click Here</span>
                    </a>
                </div>
            @endforeach
        </div>


    </div>
@endsection
