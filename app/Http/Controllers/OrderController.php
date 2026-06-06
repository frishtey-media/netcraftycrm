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
use App\Models\Client;
use App\Exports\PostOfficeExport;


class OrderController extends Controller
{
    private function isClient()
    {
        return auth()->check() && auth()->user()->role === 'client';
    }

    private function clientId()
    {
        return auth()->user()->client_id;
    }
    public function index(Request $request)
    {
        $sortOrder = $request->get('sort_order', 'desc');

        $orders = Order::with([
            'callingOrder.staff'
        ])
            ->when($this->isClient(), function ($q) {
                $q->where('client_id', $this->clientId());
            })
            ->when($request->client_id && !$this->isClient(), function ($q) use ($request) {
                $q->where('client_id', $request->client_id);
            })
            ->when($request->date_from, function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->date_from);
            })
            ->when($request->date_to, function ($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->date_to);
            })
            ->orderBy('created_at', $sortOrder)
            ->get();

        if ($this->isClient()) {

            $clients = Client::where(
                'id',
                $this->clientId()
            )->get();

            $senders = LabelSender::where(
                'client_id',
                $this->clientId()
            )
                ->orderBy('customer_name')
                ->get();
        } else {

            $clients = Client::orderBy('client_name')->get();

            $senders = LabelSender::orderBy('customer_name')->get();
        }

        return view(
            'orders.index',
            compact(
                'orders',
                'clients',
                'senders'
            )
        );
    }

    public function downloadBarcodes(Request $request)
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date'   => 'required|date',
        ]);

        $query = DB::table('orders')
            ->whereDate('date', '>=', $request->from_date)
            ->whereDate('date', '<=', $request->to_date);

        if ($this->isClient()) {
            $query->where(
                'client_id',
                $this->clientId()
            );
        }

        $barcodes = $query
            ->whereNotNull('barcode')
            ->pluck('barcode')
            ->toArray();

        if (empty($barcodes)) {
            return back()->with(
                'error',
                'No barcodes found.'
            );
        }

        return response(
            implode(',', $barcodes)
        )
            ->header('Content-Type', 'text/plain')
            ->header(
                'Content-Disposition',
                'attachment; filename=barcodes_' .
                    now()->format('Ymd_His') .
                    '.txt'
            );
    }

    public function deleteOrdersWithLog(Request $request)
    {
        if ($this->isClient()) {
            abort(403);
        }

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
                    'deleted_by'  => Auth::user()->name,
                    'deleted_at'  => now(),
                ]);
            }

            DB::table('orders')
                ->whereDate('date', '>=', $request->from_date)
                ->whereDate('date', '<=', $request->to_date)
                ->delete();
        });

        return back()->with(
            'success',
            'Orders deleted successfully.'
        );
    }

    public function importForm()
    {
        if ($this->isClient()) {

            $clients = Client::where(
                'id',
                $this->clientId()
            )->get();
        } else {

            $clients = Client::orderBy(
                'client_name'
            )->get();
        }

        return view(
            'orders.import',
            compact('clients')
        );
    }


    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        $import = new OrdersImport(
            $this->isClient()
                ? $this->clientId()
                : null
        );

        Excel::import(
            $import,
            $request->file('file')
        );

        $message =
            $import->imported .
            ' orders imported successfully.';

        if (count($import->duplicates)) {

            $message .=
                ' Duplicate IDs: ' .
                implode(',', $import->duplicates);

            return back()->with(
                'warning',
                $message
            );
        }

        return back()->with(
            'success',
            $message
        );
    }

    public function finalLabelExport()
    {
        $query = ShopifyOrder::query();

        if ($this->isClient()) {

            $query->where(
                'client_id',
                $this->clientId()
            );
        }

        $orders = $query->get();

        return Excel::download(
            new FinalLabelExport($orders),
            'Courier-Labels.xlsx'
        );
    }
    public function labelIndex()
    {
        if ($this->isClient()) {

            $orders = ShopifyOrder::where(
                'client_id',
                $this->clientId()
            )
                ->latest()
                ->paginate(20);

            $senders = LabelSender::where(
                'client_id',
                $this->clientId()
            )
                ->orderBy('customer_name')
                ->get();

            $clients = Client::where(
                'id',
                $this->clientId()
            )->get();
        } else {

            $orders = ShopifyOrder::latest()
                ->paginate(20);

            $senders = LabelSender::orderBy(
                'customer_name'
            )->get();

            $clients = Client::orderBy(
                'client_name'
            )->get();
        }

        return view(
            'labels.index',
            compact(
                'orders',
                'senders',
                'clients'
            )
        );
    }
}
