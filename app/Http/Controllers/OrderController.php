<?php

namespace App\Http\Controllers;

use App\Exports\FinalLabelExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\OrdersImport;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\ShopifyOrder;
use App\Models\LabelSender;

class OrderController extends Controller
{

    public function index(Request $request)
    {
        $orders = Order::query()
            ->when($request->date_from, function ($q) use ($request) {
                $q->where('date', '>=', $request->date_from . ' 00:00:00');
            })
            ->when($request->date_to, function ($q) use ($request) {
                $q->where('date', '<=', $request->date_to . ' 23:59:59');
            })
            ->orderBy('date', 'desc')
            ->get(); // IMPORTANT: get() not paginate()

        return view('orders.index', compact('orders'));
    }




    public function downloadBarcodes(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date'   => 'required|date',
        ]);

        $barcodes = DB::table('orders')
            ->whereDate('date', '>=', $request->from_date)
            ->whereDate('date', '<=', $request->to_date)
            ->whereNotNull('barcode')
            ->pluck('barcode')
            ->toArray();

        if (count($barcodes) === 0) {
            return back()->with('error', 'No barcodes found for selected date range.');
        }

        $content = implode(',', $barcodes);

        $filename = 'barcodes_' . now()->format('Ymd_His') . '.txt';

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', "attachment; filename={$filename}");
    }

    public function deleteOrdersWithLog(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date'   => 'required|date',
        ]);

        DB::transaction(function () use ($request) {


            $orders = DB::table('orders')
                ->whereDate('date', '>=', $request->from_date)
                ->whereDate('date', '<=', $request->to_date)
                ->get();


            foreach ($orders as $order) {
                DB::table('order_delete_logs')->insert([
                    'barcode'     => $order->barcode,
                    'order_date'  => $order->date,
                    'deleted_by'  => Auth::user()->name ?? 'system',
                    'deleted_at'  => now(),
                ]);
            }


            DB::table('orders')
                ->whereDate('date', '>=', $request->from_date)
                ->whereDate('date', '<=', $request->to_date)
                ->delete();
        });

        return back()->with('success', 'Orders deleted and logged successfully.');
    }

    public function importForm()
    {
        return view('orders.import');
    }


    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        $import = new OrdersImport();
        Excel::import($import, $request->file('file'));

        $message = $import->imported . " orders imported successfully.";

        if (count($import->duplicates)) {
            $message .= " Duplicate Order IDs skipped: " . implode(', ', $import->duplicates);
            return back()->with('warning', $message);
        }

        return back()->with('success', $message);
    }

    public function finalLabelExport()
    {
        return Excel::download(new FinalLabelExport, 'Courier-Labels.xlsx');
    }
    public function labelIndex()
    {
        $orders = ShopifyOrder::latest()->paginate(20);

        $senders = LabelSender::orderBy('customer_name')->get();

        return view('labels.index', compact('orders', 'senders'));
    }
}
