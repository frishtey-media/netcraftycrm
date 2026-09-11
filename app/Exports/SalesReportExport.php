<?php

namespace App\Exports;

use App\Models\ClientProduct;
use Maatwebsite\Excel\Concerns\FromArray;

class SalesReportExport implements FromArray
{
    protected $salesreport;

    public function __construct($salesreport)
    {
        $this->salesreport = $salesreport;
    }

    public function array(): array
    {
        $data = [];

        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        $data[] = [
            '#',
            'Date',
            'Invoice No',
            'Product',
            'Quantity',
            'Price (₹)',
            'Subtotal (₹)'
        ];


        /*
        |--------------------------------------------------------------------------
        | Get Client Product IDs
        |--------------------------------------------------------------------------
        |
        | products.name contains client_products.id
        |
        */

        $clientProductIds = $this->salesreport
            ->pluck('product.name')
            ->filter()
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values();


        /*
        |--------------------------------------------------------------------------
        | Get Actual Product Names
        |--------------------------------------------------------------------------
        */

        $clientProducts = ClientProduct::whereIn(
            'id',
            $clientProductIds
        )
            ->pluck(
                'shopify_product_name',
                'id'
            );


        /*
        |--------------------------------------------------------------------------
        | Grand Total
        |--------------------------------------------------------------------------
        */

        $grandTotal = 0;


        /*
        |--------------------------------------------------------------------------
        | Rows
        |--------------------------------------------------------------------------
        */

        foreach (
            $this->salesreport
            as $key => $item
        ) {

            $grandTotal +=
                (float) $item->subtotal;


            /*
            |--------------------------------------------------------------------------
            | Product ID
            |--------------------------------------------------------------------------
            */

            $productId =
                $item->product->name
                ?? null;


            /*
            |--------------------------------------------------------------------------
            | Actual Product Name
            |--------------------------------------------------------------------------
            */

            $productName =
                $clientProducts[$productId]
                ?? '-';


            $data[] = [

                $key + 1,

                $item->created_at
                    ? $item->created_at
                    ->format('d-m-Y')
                    : '-',

                $item->sale->invoice_no
                    ?? '-',

                $productName,

                $item->quantity,

                '₹ ' .
                    number_format(
                        $item->price,
                        2
                    ),

                '₹ ' .
                    number_format(
                        $item->subtotal,
                        2
                    ),
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Grand Total Row
        |--------------------------------------------------------------------------
        */

        $data[] = [

            '',

            '',

            '',

            '',

            '',

            'Grand Total',

            '₹ ' .
                number_format(
                    $grandTotal,
                    2
                ),

        ];


        return $data;
    }
}
