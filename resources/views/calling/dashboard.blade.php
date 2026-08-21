@extends('layouts.calling')

@section('title', 'Dashboard')

@section('content')

    <style>
        .dashboard-wrap {
            padding: 0;
        }

        .filter-card {
            background: #fff;
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .07);
            margin-bottom: 24px;
        }

        .filter-label {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 7px;
            color: #20252b;
        }

        .filter-card .form-control {
            height: 42px;
            border-radius: 7px;
        }

        .filter-btn {
            height: 42px;
            width: 100%;
            border-radius: 7px;
            font-weight: 600;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .section-subtitle {
            color: #6c757d;
            font-size: 12px;
            margin-bottom: 15px;
        }

        .dashboard-card {
            border-radius: 14px;
            padding: 17px;
            color: #fff;
            min-height: 105px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 14px rgba(0, 0, 0, .10);
            margin-bottom: 16px;
        }

        .dashboard-card::after {
            content: "";
            position: absolute;
            width: 75px;
            height: 75px;
            border-radius: 50%;
            right: -18px;
            bottom: -28px;
            background: rgba(255, 255, 255, .10);
        }

        .dashboard-card .card-title {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 7px;
        }

        .dashboard-card .card-number {
            font-size: 26px;
            line-height: 1;
            font-weight: 700;
            position: relative;
            z-index: 2;
        }

        .dashboard-card .card-icon {
            position: absolute;
            right: 16px;
            top: 18px;
            font-size: 25px;
            opacity: .25;
        }

        /* Pending work */
        .web-card {
            background: #1677f2;
        }

        .whatsapp-card {
            background: #20c968;
        }

        .abandoned-card {
            background: #7043c4;
        }

        .rto-card {
            background: #e63247;
        }

        .reorder-card {
            background: #18bdd5;
        }

        /* Calling status */
        .total-card {
            background: #22272b;
        }

        .pending-card {
            background: #ffbd17;
            color: #111;
        }

        .verified-card {
            background: #198b59;
        }

        .cancel-card {
            background: #e63247;
        }

        .not-connected-card {
            background: #747d85;
        }

        .same-order-card {
            background: #747d85;
        }

        .other-card {
            background: #fff;
            color: #111;
            border: 1px solid #08b9e8;
        }

        .breakdown {
            margin-top: 13px;
            font-size: 11px;
            line-height: 1.7;
            position: relative;
            z-index: 2;
        }

        .breakdown span {
            white-space: nowrap;
        }

        .breakdown strong {
            font-weight: 700;
        }

        .status-card {
            min-height: 120px;
        }

        .conversion-card {
            background: #fff;
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 5px 18px rgba(0, 0, 0, .07);
            margin-top: 3px;
        }

        .conversion-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .conversion-title {
            font-weight: 700;
            font-size: 14px;
        }

        .conversion-value {
            font-weight: 700;
            font-size: 18px;
        }

        .progress {
            height: 16px;
            border-radius: 20px;
            background: #e9ecef;
        }

        .progress-bar {
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
        }

        @media(max-width: 767px) {
            .dashboard-card {
                min-height: 100px;
            }

            .filter-card {
                padding: 14px;
            }
        }
    </style>


    <div class="dashboard-wrap">

        {{-- ========================================================= --}}
        {{-- DATE FILTER --}}
        {{-- ========================================================= --}}

        <form method="GET" action="{{ route('calling.dashboard') }}" class="filter-card">

            <div class="row g-3 align-items-end">

                <div class="col-md-4">
                    <label class="filter-label">
                        📅 Date From
                    </label>

                    <input type="date" name="from" class="form-control" value="{{ $fromDate }}">
                </div>

                <div class="col-md-4">
                    <label class="filter-label">
                        📅 Date To
                    </label>

                    <input type="date" name="to" class="form-control" value="{{ $toDate }}">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary filter-btn">
                        🔽 Filter
                    </button>
                </div>

                <div class="col-md-2">
                    <a href="{{ route('calling.dashboard') }}"
                        class="btn btn-outline-secondary filter-btn d-flex align-items-center justify-content-center">
                        📅 Today
                    </a>
                </div>

            </div>

        </form>


        {{-- ========================================================= --}}
        {{-- PENDING WORK --}}
        {{-- ========================================================= --}}

        <div class="mb-4">

            <div class="section-title">
                📦 Pending Work
            </div>

            <div class="section-subtitle">
                Orders received according to their source
            </div>

            <div class="row g-0">

                {{-- WEB --}}
                <div class="col-xl-4 col-md-6 pe-md-2">
                    <div class="dashboard-card web-card">

                        <div class="card-title">
                            Web
                        </div>

                        <div class="card-number">
                            {{ $pendingWeb }}
                        </div>

                        <div class="card-icon">
                            🌐
                        </div>

                    </div>
                </div>


                {{-- WHATSAPP --}}
                <div class="col-xl-4 col-md-6 px-md-1">
                    <div class="dashboard-card whatsapp-card">

                        <div class="card-title">
                            WhatsApp
                        </div>

                        <div class="card-number">
                            {{ $pendingWhatsapp }}
                        </div>

                        <div class="card-icon">
                            💬
                        </div>

                    </div>
                </div>


                {{-- ABANDONED --}}
                <div class="col-xl-4 col-md-6 ps-md-2">
                    <div class="dashboard-card abandoned-card">

                        <div class="card-title">
                            Abandoned
                        </div>

                        <div class="card-number">
                            {{ $pendingAbandoned }}
                        </div>

                        <div class="card-icon">
                            🛒
                        </div>

                    </div>
                </div>


                {{-- RTO --}}
                <div class="col-xl-4 col-md-6 pe-md-2">
                    <div class="dashboard-card rto-card">

                        <div class="card-title">
                            RTO
                        </div>

                        <div class="card-number">
                            {{ $pendingRto }}
                        </div>

                        <div class="card-icon">
                            ↩
                        </div>

                    </div>
                </div>


                {{-- DELIVER RE-ORDER --}}
                <div class="col-xl-4 col-md-6 px-md-1">
                    <div class="dashboard-card reorder-card">

                        <div class="card-title">
                            Deliver Re-Order
                        </div>

                        <div class="card-number">
                            {{ $pendingDeliveredReorder }}
                        </div>

                        <div class="card-icon">
                            🔄
                        </div>

                    </div>
                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- CALLING STATUS --}}
        {{-- ========================================================= --}}

        <div class="mb-4">

            <div class="section-title">
                ☎ Calling Status
            </div>

            <div class="section-subtitle">
                Current status of assigned orders
            </div>


            <div class="row g-0">

                {{-- TOTAL LEADS --}}
                <div class="col-xl-4 col-md-6 pe-md-2">

                    <div class="dashboard-card status-card total-card">

                        <div class="card-title">
                            Total Leads
                        </div>

                        <div class="card-number">
                            {{ $totalOrders }}
                        </div>

                        <div class="card-icon">
                            ☷
                        </div>

                        <div class="breakdown">

                            <span>
                                Web:
                                <strong>{{ $webOrders }}</strong>
                            </span>

                            &nbsp; | &nbsp;

                            <span>
                                WhatsApp:
                                <strong>{{ $whatsappOrders }}</strong>
                            </span>

                            &nbsp; | &nbsp;

                            <span>
                                RTO:
                                <strong>{{ $rtoOrders }}</strong>
                            </span>

                            <br>

                            <span>
                                Deliver Re-Order:
                                <strong>{{ $deliveredReorderOrders }}</strong>
                            </span>

                            &nbsp; | &nbsp;

                            <span>
                                Abandoned:
                                <strong>{{ $abandonedOrders }}</strong>
                            </span>

                        </div>

                    </div>

                </div>


                {{-- PENDING --}}
                <div class="col-xl-4 col-md-6 px-md-1">

                    <div class="dashboard-card status-card pending-card">

                        <div class="card-title">
                            Pending
                        </div>

                        <div class="card-number">
                            {{ $pending }}
                        </div>

                        <div class="card-icon">
                            ⏳
                        </div>

                        <div class="breakdown">

                            Web:
                            <strong>{{ $pendingWeb }}</strong>

                            &nbsp; | &nbsp;

                            WhatsApp:
                            <strong>{{ $pendingWhatsapp }}</strong>

                            &nbsp; | &nbsp;

                            RTO:
                            <strong>{{ $pendingRto }}</strong>

                            <br>

                            Deliver Re-Order:
                            <strong>{{ $pendingDeliveredReorder }}</strong>

                            &nbsp; | &nbsp;

                            Abandoned:
                            <strong>{{ $pendingAbandoned }}</strong>

                        </div>

                    </div>

                </div>


                {{-- VERIFIED --}}
                <div class="col-xl-4 col-md-6 ps-md-2">

                    <div class="dashboard-card status-card verified-card">

                        <div class="card-title">
                            Verified
                        </div>

                        <div class="card-number">
                            {{ $verified }}
                        </div>

                        <div class="card-icon">
                            ✓
                        </div>

                        <div class="breakdown">

                            Web:
                            <strong>{{ $verifiedWeb }}</strong>

                            &nbsp; | &nbsp;

                            WhatsApp:
                            <strong>{{ $verifiedWhatsapp }}</strong>

                            &nbsp; | &nbsp;

                            RTO:
                            <strong>{{ $verifiedRto }}</strong>

                            <br>

                            Deliver Re-Order:
                            <strong>{{ $verifiedDeliveredReorder }}</strong>

                            &nbsp; | &nbsp;

                            Abandoned:
                            <strong>{{ $verifiedAbandoned }}</strong>

                        </div>

                    </div>

                </div>


                {{-- CANCELLED --}}
                <div class="col-xl-4 col-md-6 pe-md-2">

                    <div class="dashboard-card status-card cancel-card">

                        <div class="card-title">
                            Cancelled
                        </div>

                        <div class="card-number">
                            {{ $cancelled }}
                        </div>

                        <div class="card-icon">
                            ✕
                        </div>

                        <div class="breakdown">

                            Web:
                            <strong>{{ $cancelledWeb }}</strong>

                            &nbsp; | &nbsp;

                            WhatsApp:
                            <strong>{{ $cancelledWhatsapp }}</strong>

                            &nbsp; | &nbsp;

                            RTO:
                            <strong>{{ $cancelledRto }}</strong>

                            <br>

                            Deliver Re-Order:
                            <strong>{{ $cancelledDeliveredReorder }}</strong>

                            &nbsp; | &nbsp;

                            Abandoned:
                            <strong>{{ $cancelledAbandoned }}</strong>

                        </div>

                    </div>

                </div>


                {{-- NOT CONNECTED --}}
                <div class="col-xl-4 col-md-6 px-md-1">

                    <div class="dashboard-card status-card not-connected-card">

                        <div class="card-title">
                            Not Connected
                        </div>

                        <div class="card-number">
                            {{ $notConnected }}
                        </div>

                        <div class="card-icon">
                            ☎
                        </div>

                        <div class="breakdown">

                            Web:
                            <strong>{{ $notConnectedWeb }}</strong>

                            &nbsp; | &nbsp;

                            WhatsApp:
                            <strong>{{ $notConnectedWhatsapp }}</strong>

                            &nbsp; | &nbsp;

                            RTO:
                            <strong>{{ $notConnectedRto }}</strong>

                            <br>

                            Deliver Re-Order:
                            <strong>{{ $notConnectedDeliveredReorder }}</strong>

                            &nbsp; | &nbsp;

                            Abandoned:
                            <strong>{{ $notConnectedAbandoned }}</strong>

                        </div>

                    </div>

                </div>


                {{-- SAME ORDER --}}
                <div class="col-xl-4 col-md-6 ps-md-2">

                    <div class="dashboard-card status-card same-order-card">

                        <div class="card-title">
                            Same Order
                        </div>

                        <div class="card-number">
                            {{ $sameOrder }}
                        </div>

                        <div class="card-icon">
                            🔄
                        </div>

                        <div class="breakdown">

                            Web:
                            <strong>{{ $sameOrderWeb }}</strong>

                            &nbsp; | &nbsp;

                            WhatsApp:
                            <strong>{{ $sameOrderWhatsapp }}</strong>

                            &nbsp; | &nbsp;

                            RTO:
                            <strong>{{ $sameOrderRto }}</strong>

                            <br>

                            Deliver Re-Order:
                            <strong>{{ $sameOrderDeliveredReorder }}</strong>

                            &nbsp; | &nbsp;

                            Abandoned:
                            <strong>{{ $sameOrderAbandoned }}</strong>

                        </div>

                    </div>

                </div>


                {{-- OTHER --}}
                <div class="col-xl-4 col-md-6 pe-md-2">

                    <div class="dashboard-card status-card other-card">

                        <div class="card-title">
                            Other
                        </div>

                        <div class="card-number">
                            {{ $other }}
                        </div>

                        <div class="card-icon">
                            •••
                        </div>

                        <div class="breakdown">

                            Web:
                            <strong>{{ $otherWeb }}</strong>

                            &nbsp; | &nbsp;

                            WhatsApp:
                            <strong>{{ $otherWhatsapp }}</strong>

                            &nbsp; | &nbsp;

                            RTO:
                            <strong>{{ $otherRto }}</strong>

                            <br>

                            Deliver Re-Order:
                            <strong>{{ $otherDeliveredReorder }}</strong>

                            &nbsp; | &nbsp;

                            Abandoned:
                            <strong>{{ $otherAbandoned }}</strong>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- CONVERSION RATE --}}
        {{-- ========================================================= --}}

        <div class="conversion-card">

            <div class="conversion-head">

                <div>
                    <div class="conversion-title">
                        📈 Conversion Rate
                    </div>

                    <small class="text-muted">
                        Verified leads out of total leads
                    </small>
                </div>

                <div class="conversion-value">
                    {{ $successRate }}%
                </div>

            </div>

            <div class="progress">

                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $successRate }}%;">
                    {{ $successRate }}%
                </div>

            </div>

        </div>

    </div>

@endsection
