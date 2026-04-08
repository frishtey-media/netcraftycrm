@extends('layouts.inventory')

@section('title', 'Invoice')

@section('content')
    <style>
        @media print {
            body * {
                visibility: hidden;
            }

            .print-area,
            .print-area * {
                visibility: visible;
            }

            .print-area {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
            }
        }

        @media print {
            button {
                display: none;
            }
        }
    </style>
    <div class="card p-4 print-area">

        <h4>Invoice: {{ $sale->invoice_no }}</h4>
        <p>Date: {{ $sale->sale_date }}</p>

        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>{{ $item->price }}</td>
                        <td>{{ $item->quantity * $item->price }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <h5 class="text-end">
            Grand Total: ₹ {{ $sale->items->sum(fn($item) => $item->quantity * $item->price) }}
        </h5>

        <button onclick="window.print()" class="btn btn-primary mt-3">
            Print
        </button>

    </div>

@endsection
