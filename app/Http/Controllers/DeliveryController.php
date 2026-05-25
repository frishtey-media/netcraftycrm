<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class DeliveryController extends Controller
{
    public function index()
    {
        return view('delivery.index');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $rows = Excel::toArray([], $request->file('file'));

        $updated  = 0;
        $notFound = 0;

        foreach ($rows[0] as $key => $row) {

            // Skip Header Row
            if ($key == 0) {
                continue;
            }

            // Excel Columns
            $trackingNo = trim($row[1] ?? ''); // Article Number (Column B)
            $status     = trim($row[6] ?? ''); // Status (Column G)
            $lastEvent  = trim($row[7] ?? ''); // Last Event (Column H)

            if (empty($trackingNo)) {
                continue;
            }

            $order = Order::where('barcode', $trackingNo)->first();

            if (!$order) {
                $notFound++;
                continue;
            }

            // Status Mapping
            $crmStatus = match (strtolower($status)) {
                'delivered'      => 'Delivered',
                'not delivered'  => 'In Transit',
                default          => $status,
            };

            // Extract Delivery Date only when Delivered
            $deliveryDate = null;

            if (
                strtolower($status) === 'delivered' &&
                preg_match('/(\d{2}\/\d{2}\/\d{4})/', $lastEvent, $matches)
            ) {
                try {
                    $deliveryDate = Carbon::createFromFormat(
                        'd/m/Y',
                        $matches[1]
                    )->format('Y-m-d');
                } catch (\Exception $e) {
                    $deliveryDate = null;
                }
            }

            // Save Data
            $order->delivery_status = $crmStatus;
            $order->delivery_remark = $lastEvent;

            if ($deliveryDate) {
                $order->delivery_date = $deliveryDate;
            }

            $order->save();

            $updated++;
        }

        return redirect()
            ->back()
            ->with(
                'success',
                "{$updated} records updated successfully. {$notFound} tracking numbers not found."
            );
    }
}
