@extends('layouts.admin')

@section('content')
    <div class="container-fluid py-4">

        {{-- HEADER --}}
        <div class="compare-header mb-4">

            <div>

                <h3 class="fw-bold mb-1">

                    <i class="fas fa-chart-bar me-2"></i>
                    Order Comparison Report

                </h3>

                <div class="opacity-75">

                    Web vs WhatsApp delivery performance

                </div>

            </div>

            <div>

                <a href="{{ route('orders.list', request()->query()) }}" class="btn btn-light">

                    <i class="fas fa-arrow-left me-2"></i>
                    Back to Orders

                </a>

            </div>

        </div>


        {{-- ACTIVE FILTER INFORMATION --}}
        <div class="card compare-card mb-4">

            <div class="card-body">

                <div class="d-flex flex-wrap gap-2 align-items-center">

                    <strong class="me-2">

                        <i class="fas fa-filter text-primary me-1"></i>
                        Current Filters:

                    </strong>


                    @if (request('client_id'))
                        <span class="badge bg-primary">
                            Client Filter
                        </span>
                    @endif


                    @if (request('product'))
                        <span class="badge bg-success">

                            Product:
                            {{ request('product') }}

                        </span>
                    @endif


                    @if (request('staff_id'))
                        <span class="badge bg-info">

                            Staff Filter

                        </span>
                    @endif


                    @if (request('delivery_status'))
                        <span class="badge bg-warning text-dark">

                            Status:
                            {{ request('delivery_status') === 'null' ? 'No Status' : request('delivery_status') }}

                        </span>
                    @endif


                    @if (request('date_from'))
                        <span class="badge bg-secondary">

                            From:
                            {{ request('date_from') }}

                        </span>
                    @endif


                    @if (request('date_to'))
                        <span class="badge bg-secondary">

                            To:
                            {{ request('date_to') }}

                        </span>
                    @endif


                    @if (
                        !request('client_id') &&
                            !request('product') &&
                            !request('staff_id') &&
                            !request('delivery_status') &&
                            !request('date_from') &&
                            !request('date_to'))
                        <span class="text-muted">

                            No filters applied — showing all orders.

                        </span>
                    @endif

                </div>

            </div>

        </div>


        {{-- TOP CARDS --}}
        <div class="row g-4 mb-4">

            <div class="col-xl-4">

                <div class="source-card source-total-card">

                    <div class="source-icon">

                        <i class="fas fa-layer-group"></i>

                    </div>

                    <div>

                        <div class="source-title">
                            Total Orders
                        </div>

                        <div class="source-value">

                            {{ number_format($totalOrders) }}

                        </div>

                        <small>
                            100% filtered orders
                        </small>

                    </div>

                </div>

            </div>


            <div class="col-xl-4">

                <div class="source-card source-web-card">

                    <div class="source-icon">

                        <i class="fas fa-globe"></i>

                    </div>

                    <div>

                        <div class="source-title">
                            Web Orders
                        </div>

                        <div class="source-value">

                            {{ number_format($webOrders) }}

                        </div>

                        <small>

                            {{ $totalOrders > 0 ? number_format(($webOrders / $totalOrders) * 100, 2) : 0 }}%

                            of total

                        </small>

                    </div>

                </div>

            </div>


            <div class="col-xl-4">

                <div class="source-card source-wa-card">

                    <div class="source-icon">

                        <i class="fab fa-whatsapp"></i>

                    </div>

                    <div>

                        <div class="source-title">
                            WhatsApp Orders
                        </div>

                        <div class="source-value">

                            {{ number_format($whatsappOrders) }}

                        </div>

                        <small>

                            {{ $totalOrders > 0 ? number_format(($whatsappOrders / $totalOrders) * 100, 2) : 0 }}%

                            of total

                        </small>

                    </div>

                </div>

            </div>

        </div>


        {{-- MAIN COMPARISON TABLE --}}
        <div class="card compare-card mb-4">

            <div class="card-header">

                <h5 class="mb-0">

                    <i class="fas fa-table text-primary me-2"></i>
                    Performance Comparison

                </h5>

            </div>


            <div class="card-body p-0">

                <div class="table-responsive">

                    <table class="table comparison-table mb-0">

                        <thead>

                            <tr>

                                <th>
                                    Metric
                                </th>

                                <th class="text-center">
                                    Total
                                </th>

                                <th class="text-center">
                                    Web
                                </th>

                                <th class="text-center">
                                    WhatsApp
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            {{-- ORDERS --}}
                            <tr>

                                <td>

                                    <div class="metric-name">

                                        <span class="metric-icon metric-orders">

                                            <i class="fas fa-shopping-cart"></i>

                                        </span>

                                        Orders

                                    </div>

                                </td>

                                <td class="text-center">

                                    <strong>
                                        {{ number_format($totalOrders) }}
                                    </strong>

                                    <small>100%</small>

                                </td>

                                <td class="text-center">

                                    <strong>
                                        {{ number_format($webOrders) }}
                                    </strong>

                                    <small>100%</small>

                                </td>

                                <td class="text-center">

                                    <strong>
                                        {{ number_format($whatsappOrders) }}
                                    </strong>

                                    <small>100%</small>

                                </td>

                            </tr>


                            {{-- DELIVERED --}}
                            <tr>

                                <td>

                                    <div class="metric-name">

                                        <span class="metric-icon metric-delivered">

                                            <i class="fas fa-check"></i>

                                        </span>

                                        Delivered

                                    </div>

                                </td>

                                <td class="text-center">

                                    <strong class="text-success">

                                        {{ number_format($totalDelivered) }}

                                    </strong>

                                    <small>

                                        {{ number_format($totalDeliveredPercent, 2) }}%

                                    </small>

                                </td>

                                <td class="text-center">

                                    <strong class="text-success">

                                        {{ number_format($webDelivered) }}

                                    </strong>

                                    <small>

                                        {{ number_format($webDeliveredPercent, 2) }}%

                                    </small>

                                </td>

                                <td class="text-center">

                                    <strong class="text-success">

                                        {{ number_format($whatsappDelivered) }}

                                    </strong>

                                    <small>

                                        {{ number_format($waDeliveredPercent, 2) }}%

                                    </small>

                                </td>

                            </tr>


                            {{-- RTO --}}
                            <tr>

                                <td>

                                    <div class="metric-name">

                                        <span class="metric-icon metric-rto">

                                            <i class="fas fa-undo-alt"></i>

                                        </span>

                                        RTO

                                    </div>

                                </td>

                                <td class="text-center">

                                    <strong class="text-danger">

                                        {{ number_format($totalRto) }}

                                    </strong>

                                    <small>

                                        {{ number_format($totalRtoPercent, 2) }}%

                                    </small>

                                </td>

                                <td class="text-center">

                                    <strong class="text-danger">

                                        {{ number_format($webRto) }}

                                    </strong>

                                    <small>

                                        {{ number_format($webRtoPercent, 2) }}%

                                    </small>

                                </td>

                                <td class="text-center">

                                    <strong class="text-danger">

                                        {{ number_format($whatsappRto) }}

                                    </strong>

                                    <small>

                                        {{ number_format($waRtoPercent, 2) }}%

                                    </small>

                                </td>

                            </tr>


                            {{-- RTO RECEIVED --}}
                            <tr class="rto-received-row">

                                <td>

                                    <div class="metric-name">

                                        <span class="metric-icon metric-received">

                                            <i class="fas fa-box-open"></i>

                                        </span>

                                        RTO Received

                                    </div>

                                </td>

                                <td class="text-center">

                                    <strong class="text-primary">

                                        {{ number_format($totalRtoReceived) }}

                                    </strong>

                                    <small>

                                        {{ number_format($totalRtoReceivedPercent, 2) }}%
                                        of RTO

                                    </small>

                                </td>

                                <td class="text-center">

                                    <strong class="text-primary">

                                        {{ number_format($webRtoReceived) }}

                                    </strong>

                                    <small>

                                        {{ number_format($webRtoReceivedPercent, 2) }}%
                                        of RTO

                                    </small>

                                </td>

                                <td class="text-center">

                                    <strong class="text-primary">

                                        {{ number_format($whatsappRtoReceived) }}

                                    </strong>

                                    <small>

                                        {{ number_format($waRtoReceivedPercent, 2) }}%
                                        of RTO

                                    </small>

                                </td>

                            </tr>


                            {{-- RTO RECEIVE PENDING --}}
                            <tr>

                                <td>

                                    <div class="metric-name">

                                        <span class="metric-icon metric-pending">

                                            <i class="fas fa-hourglass-half"></i>

                                        </span>

                                        RTO Receive Pending

                                    </div>

                                </td>

                                <td class="text-center">

                                    <strong class="text-warning">

                                        {{ number_format($totalRtoPending) }}

                                    </strong>

                                </td>

                                <td class="text-center">

                                    <strong class="text-warning">

                                        {{ number_format($webRtoPending) }}

                                    </strong>

                                </td>

                                <td class="text-center">

                                    <strong class="text-warning">

                                        {{ number_format($whatsappRtoPending) }}

                                    </strong>

                                </td>

                            </tr>


                            {{-- TRANSIT --}}
                            <tr>

                                <td>

                                    <div class="metric-name">

                                        <span class="metric-icon metric-transit">

                                            <i class="fas fa-truck"></i>

                                        </span>

                                        Transit / OFD

                                    </div>

                                </td>

                                <td class="text-center">

                                    <strong class="text-info">

                                        {{ number_format($totalTransit) }}

                                    </strong>

                                    <small>

                                        {{ number_format($totalTransitPercent, 2) }}%

                                    </small>

                                </td>

                                <td class="text-center">

                                    <strong class="text-info">

                                        {{ number_format($webTransit) }}

                                    </strong>

                                    <small>

                                        {{ number_format($webTransitPercent, 2) }}%

                                    </small>

                                </td>

                                <td class="text-center">

                                    <strong class="text-info">

                                        {{ number_format($whatsappTransit) }}

                                    </strong>

                                    <small>

                                        {{ number_format($waTransitPercent, 2) }}%

                                    </small>

                                </td>

                            </tr>


                            {{-- NO STATUS --}}
                            <tr>

                                <td>

                                    <div class="metric-name">

                                        <span class="metric-icon metric-nostatus">

                                            <i class="fas fa-clock"></i>

                                        </span>

                                        No Status

                                    </div>

                                </td>

                                <td class="text-center">

                                    <strong>

                                        {{ number_format($totalNoStatus) }}

                                    </strong>

                                    <small>

                                        {{ number_format($totalNoStatusPercent, 2) }}%

                                    </small>

                                </td>

                                <td class="text-center">

                                    <strong>

                                        {{ number_format($webNoStatus) }}

                                    </strong>

                                    <small>

                                        {{ number_format($webNoStatusPercent, 2) }}%

                                    </small>

                                </td>

                                <td class="text-center">

                                    <strong>

                                        {{ number_format($whatsappNoStatus) }}

                                    </strong>

                                    <small>

                                        {{ number_format($waNoStatusPercent, 2) }}%

                                    </small>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>


        {{-- RTO RECOVERY --}}
        <div class="row g-4">

            <div class="col-lg-6">

                <div class="card compare-card h-100">

                    <div class="card-header">

                        <h5 class="mb-0">
                            RTO Recovery
                        </h5>

                    </div>

                    <div class="card-body">

                        <div class="recovery-row">

                            <span>
                                Total RTO
                            </span>

                            <strong class="text-danger">
                                {{ number_format($totalRto) }}
                            </strong>

                        </div>


                        <div class="recovery-row">

                            <span>
                                Received
                            </span>

                            <strong class="text-success">
                                {{ number_format($totalRtoReceived) }}
                            </strong>

                        </div>


                        <div class="recovery-row">

                            <span>
                                Pending
                            </span>

                            <strong class="text-warning">
                                {{ number_format($totalRtoPending) }}
                            </strong>

                        </div>


                        <div class="progress mt-3">

                            <div class="progress-bar bg-success"
                                style="width: {{ min(100, $totalRtoReceivedPercent) }}%">

                                {{ number_format($totalRtoReceivedPercent, 1) }}%

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- WINNER --}}
            <div class="col-lg-6">

                <div class="card compare-card h-100">

                    <div class="card-header">

                        <h5 class="mb-0">

                            <i class="fas fa-trophy text-warning me-2"></i>
                            Delivery Performance

                        </h5>

                    </div>

                    <div class="card-body">

                        <div class="performance-box">

                            <div>

                                <span>
                                    Web Delivery Rate
                                </span>

                                <strong>

                                    {{ number_format($webDeliveredPercent, 2) }}%

                                </strong>

                            </div>

                            <div>

                                <span>
                                    WhatsApp Delivery Rate
                                </span>

                                <strong>

                                    {{ number_format($waDeliveredPercent, 2) }}%

                                </strong>

                            </div>

                        </div>


                        <div class="winner-box mt-4">

                            @if ($webDeliveredPercent > $waDeliveredPercent)
                                <i class="fas fa-trophy"></i>

                                Web is performing better by

                                <strong>

                                    {{ number_format($webDeliveredPercent - $waDeliveredPercent, 2) }}%

                                </strong>
                            @elseif($waDeliveredPercent > $webDeliveredPercent)
                                <i class="fas fa-trophy"></i>

                                WhatsApp is performing better by

                                <strong>

                                    {{ number_format($waDeliveredPercent - $webDeliveredPercent, 2) }}%

                                </strong>
                            @else
                                Web and WhatsApp have the same delivery rate.
                            @endif

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <style>
        .compare-header {
            padding: 25px;
            border-radius: 20px;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
        }

        .compare-card {
            border: 0;
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, .07);
            overflow: hidden;
        }

        .compare-card .card-header {
            padding: 18px 22px;
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
        }

        .source-card {
            min-height: 150px;
            border-radius: 18px;
            padding: 22px;
            display: flex;
            align-items: center;
            gap: 18px;
            color: #fff;
        }

        .source-total-card {
            background: linear-gradient(135deg, #334155, #0f172a);
        }

        .source-web-card {
            background: linear-gradient(135deg, #1d4ed8, #3b82f6);
        }

        .source-wa-card {
            background: linear-gradient(135deg, #15803d, #22c55e);
        }

        .source-icon {
            width: 58px;
            height: 58px;
            flex: 0 0 58px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            background: rgba(255, 255, 255, .15);
        }

        .source-title {
            font-size: 13px;
            font-weight: 700;
            opacity: .85;
            text-transform: uppercase;
        }

        .source-value {
            font-size: 32px;
            line-height: 1.2;
            font-weight: 800;
        }

        .comparison-table thead th {
            background: #0f172a;
            color: #fff;
            border: 0;
            padding: 16px 20px;
            font-size: 12px;
            text-transform: uppercase;
        }

        .comparison-table tbody td {
            padding: 17px 20px;
            vertical-align: middle;
            border-color: #f1f5f9;
        }

        .comparison-table td strong {
            display: block;
            font-size: 17px;
        }

        .comparison-table td small {
            display: block;
            color: #64748b;
            margin-top: 2px;
        }

        .metric-name {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
        }

        .metric-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .metric-orders {
            background: #eff6ff;
            color: #2563eb;
        }

        .metric-delivered {
            background: #f0fdf4;
            color: #16a34a;
        }

        .metric-rto {
            background: #fef2f2;
            color: #dc2626;
        }

        .metric-received {
            background: #eef2ff;
            color: #4f46e5;
        }

        .metric-pending {
            background: #fffbeb;
            color: #d97706;
        }

        .metric-transit {
            background: #ecfeff;
            color: #0891b2;
        }

        .metric-nostatus {
            background: #f1f5f9;
            color: #64748b;
        }

        .rto-received-row {
            background: #fafaff;
        }

        .recovery-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .recovery-row strong {
            font-size: 18px;
        }

        .progress {
            height: 14px;
            border-radius: 20px;
        }

        .performance-box {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .performance-box>div {
            padding: 18px;
            border-radius: 14px;
            background: #f8fafc;
        }

        .performance-box span {
            display: block;
            color: #64748b;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .performance-box strong {
            font-size: 24px;
        }

        .winner-box {
            padding: 18px;
            border-radius: 14px;
            background: #fffbeb;
            color: #92400e;
        }

        .winner-box i {
            color: #f59e0b;
            margin-right: 7px;
        }

        @media(max-width:767px) {

            .compare-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .performance-box {
                grid-template-columns: 1fr;
            }

        }
    </style>
@endsection
