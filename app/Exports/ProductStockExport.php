<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class ProductStockExport implements FromArray
{
    protected $movements;

    public function __construct($movements)
    {
        $this->movements = $movements;
    }

    public function array(): array
    {
        $data = [];

        $grandQty = 0;
        $grandValue = 0;

        foreach ($this->movements as $productId => $items) {

            $product = $items->first()->product;


            $data[] = ["Product: " . $product->name];
            $data[] = [];


            $data[] = [
                'Warehouse',
                'Category',
                'Product Name',
                'Unit Price (₹)',
                'Quantity',
                'Total Value (₹)',
                'Date',
                'Status'
            ];

            $totalQty = 0;
            $totalValue = 0;


            $positiveTypes = ['in', 'created', 'updated', 'rto_restored'];
            foreach ($items as $item) {


                $qty = in_array($item->type, $positiveTypes)
                    ? $item->quantity
                    : -$item->quantity;

                $value = $qty * $item->price;


                $status = match ($item->type) {
                    'created' => 'Stock Created',
                    'updated' => 'Stock Updated',
                    'rto_restored' => 'RTO Restock',
                    default   => 'Stock Out',
                };

                $totalQty += $qty;
                $totalValue += $value;


                $data[] = [
                    $product->warehouse->name ?? '',
                    $product->category->name ?? '',
                    $product->name,
                    '₹ ' . number_format($item->price, 2),
                    $qty,
                    '₹ ' . number_format($value, 2),
                    \Carbon\Carbon::parse($item->movement_date)->format('d-m-Y'),
                    $status
                ];
            }


            $data[] = [
                '',
                '',
                '',
                'Product Total',
                $totalQty,
                '₹ ' . number_format($totalValue, 2),
                '',
                ''
            ];

            $data[] = [];

            $grandQty += $totalQty;
            $grandValue += $totalValue;
        }


        $data[] = [
            '',
            '',
            '',
            'Grand Total',
            $grandQty,
            '₹ ' . number_format($grandValue, 2),
            '',
            ''
        ];

        return $data;
    }
}
