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
use App\Models\CallingUser;
use App\Exports\SelectedOrdersExport;


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

        $query = Order::with([
            'callingOrder.staff'
        ]);

        // Client Login
        if ($this->isClient()) {

            $query->where(
                'client_id',
                $this->clientId()
            );

            $clients = Client::where(
                'id',
                $this->clientId()
            )->get();

            $senders = LabelSender::where(
                'client_id',
                $this->clientId()
            )->orderBy('customer_name')->get();
        } else {

            $clients = Client::orderBy('client_name')->get();

            $senders = LabelSender::orderBy('customer_name')->get();

            if ($request->filled('client_id')) {
                $query->where(
                    'client_id',
                    $request->client_id
                );
            }
        }

        // Staff Filter
        if ($request->filled('staff_id')) {

            $query->whereHas('callingOrder', function ($q) use ($request) {

                $q->where(
                    'assigned_to',
                    $request->staff_id
                );
            });
        }
        $searchTerms = [];
        $notFound = [];

        if ($request->filled('search')) {

            $searchTerms = preg_split(
                '/[\r\n,]+/',
                trim($request->search)
            );

            $searchTerms = array_filter(
                array_map('trim', $searchTerms)
            );

            $query->where(function ($q) use ($searchTerms) {

                $q->whereIn('order_id', $searchTerms)
                    ->orWhereIn('barcode', $searchTerms)
                    ->orWhereIn('customer_phone', $searchTerms);

                foreach ($searchTerms as $term) {

                    $q->orWhere(
                        'customer_name',
                        'like',
                        "%{$term}%"
                    );
                }
            });
        }
        // Status Filter
        if ($request->filled('delivery_status')) {

            if ($request->delivery_status == 'null') {

                $query->where(function ($q) {

                    $q->whereNull('delivery_status')
                        ->orWhere('delivery_status', '');
                });
            } else {

                $query->where(
                    'delivery_status',
                    $request->delivery_status
                );
            }
        }

        // Date Filter
        if ($request->filled('date_from')) {

            $query->whereDate(
                'created_at',
                '>=',
                $request->date_from
            );
        }

        if ($request->filled('date_to')) {

            $query->whereDate(
                'created_at',
                '<=',
                $request->date_to
            );
        }
        // $summaryQuery = clone $query;

        // ORDERS
        $totalOrders = (clone $query)->count();

        $webOrders = (clone $query)
            ->where(function ($main) {
                $main->whereDoesntHave('callingOrder')
                    ->orWhereHas('callingOrder', function ($q) {
                        $q->whereNull('order_source')
                            ->orWhere('order_source', '');
                    });
            })
            ->count();

        $whatsappOrders = $totalOrders - $webOrders;


        // DELIVERED
        $totalDelivered = (clone $query)
            ->where('delivery_status', 'Delivered')
            ->count();

        $webDelivered = (clone $query)
            ->where('delivery_status', 'Delivered')
            ->where(function ($main) {
                $main->whereDoesntHave('callingOrder')
                    ->orWhereHas('callingOrder', function ($q) {
                        $q->whereNull('order_source')
                            ->orWhere('order_source', '');
                    });
            })
            ->count();

        $whatsappDelivered = $totalDelivered - $webDelivered;


        // PAYMENTS (Based on pay_bill_date)

        // PAYMENTS (Based on pay_bill_date)

        $paymentQuery = Order::query();

        // Client Filter
        if ($this->isClient()) {
            $paymentQuery->where('client_id', $this->clientId());
        } elseif ($request->filled('client_id')) {
            $paymentQuery->where('client_id', $request->client_id);
        }

        // Staff Filter
        if ($request->filled('staff_id')) {
            $paymentQuery->whereHas('callingOrder', function ($q) use ($request) {
                $q->where('assigned_to', $request->staff_id);
            });
        }

        // Payment Date Filter
        if ($request->filled('date_from')) {
            $paymentQuery->whereRaw(
                "STR_TO_DATE(pay_bill_date,'%d-%m-%Y') >= ?",
                [$request->date_from]
            );
        }

        if ($request->filled('date_to')) {
            $paymentQuery->whereRaw(
                "STR_TO_DATE(pay_bill_date,'%d-%m-%Y') <= ?",
                [$request->date_to]
            );
        }

        // Received
        $paymentReceivedOrders = (clone $paymentQuery)
            ->whereNotNull('pay_bill_date')
            ->where('pay_bill_date', '!=', '')
            ->count();

        $paymentReceivedAmount = (clone $paymentQuery)
            ->whereNotNull('pay_bill_date')
            ->where('pay_bill_date', '!=', '')
            ->sum('receivedcodamt');

        // Pending
        $paymentPendingOrders = (clone $paymentQuery)
            ->where(function ($q) {
                $q->whereNull('pay_bill_date')
                    ->orWhere('pay_bill_date', '');
            })
            ->count();

        $paymentPendingAmount = (clone $paymentQuery)
            ->where(function ($q) {
                $q->whereNull('pay_bill_date')
                    ->orWhere('pay_bill_date', '');
            })
            ->sum('amount');


        // RTO
        $totalRto = (clone $query)
            ->where('delivery_status', 'RTO')
            ->count();

        $webRto = (clone $query)
            ->where('delivery_status', 'RTO')
            ->where(function ($main) {
                $main->whereDoesntHave('callingOrder')
                    ->orWhereHas('callingOrder', function ($q) {
                        $q->whereNull('order_source')
                            ->orWhere('order_source', '');
                    });
            })
            ->count();

        $whatsappRto = $totalRto - $webRto;

        $rtoReceived = (clone $query)
            ->where('rtorecivedsts', 1)
            ->count();


        // IN TRANSIT
        $totalTransit = (clone $query)
            ->whereIn('delivery_status', [
                'In Transit',
                'Out For Delivery'
            ])
            ->count();

        $webTransit = (clone $query)
            ->whereIn('delivery_status', [
                'In Transit',
                'Out For Delivery'
            ])
            ->where(function ($main) {
                $main->whereDoesntHave('callingOrder')
                    ->orWhereHas('callingOrder', function ($q) {
                        $q->whereNull('order_source')
                            ->orWhere('order_source', '');
                    });
            })
            ->count();

        $whatsappTransit = $totalTransit - $webTransit;


        // NO STATUS
        $totalNoStatus = (clone $query)
            ->where(function ($q) {
                $q->whereNull('delivery_status')
                    ->orWhere('delivery_status', '');
            })
            ->count();

        $webNoStatus = (clone $query)
            ->where(function ($q) {
                $q->whereNull('delivery_status')
                    ->orWhere('delivery_status', '');
            })
            ->where(function ($main) {
                $main->whereDoesntHave('callingOrder')
                    ->orWhereHas('callingOrder', function ($q) {
                        $q->whereNull('order_source')
                            ->orWhere('order_source', '');
                    });
            })
            ->count();

        $whatsappNoStatus = $totalNoStatus - $webNoStatus;
        $perPage = $request->get('per_page', 100);

        $orders = $query
            ->orderBy('created_at', $sortOrder)
            ->paginate($perPage)
            ->withQueryString();

        $searchTerms = [];
        $notFound = [];

        if ($request->filled('search')) {

            $searchTerms = preg_split(
                '/[\r\n,]+/',
                trim($request->search)
            );

            $searchTerms = array_filter(
                array_map('trim', $searchTerms)
            );

            $query->where(function ($q) use ($searchTerms) {

                $q->whereIn('order_id', $searchTerms)
                    ->orWhereIn('barcode', $searchTerms)
                    ->orWhereIn('customer_phone', $searchTerms);

                foreach ($searchTerms as $term) {

                    $q->orWhere(
                        'customer_name',
                        'like',
                        "%{$term}%"
                    );
                }
            });

            // Find matched values from DB directly
            $foundBarcodes = Order::whereIn(
                'barcode',
                $searchTerms
            )->pluck('barcode')->toArray();

            $foundOrderIds = Order::whereIn(
                'order_id',
                $searchTerms
            )->pluck('order_id')->toArray();

            $foundPhones = Order::whereIn(
                'customer_phone',
                $searchTerms
            )->pluck('customer_phone')->toArray();

            $foundValues = collect(
                array_merge(
                    $foundBarcodes,
                    $foundOrderIds,
                    $foundPhones
                )
            )
                ->map(fn($v) => strtoupper(trim($v)))
                ->unique()
                ->toArray();

            $notFound = collect($searchTerms)
                ->map(fn($v) => strtoupper(trim($v)))
                ->reject(function ($item) use ($foundValues) {
                    return in_array($item, $foundValues);
                })
                ->values()
                ->toArray();
        }
        $staffs = CallingUser::orderBy('name')->get();

        return view(
            'orders.index',
            compact(
                'orders',
                'clients',
                'senders',
                'staffs',

                'totalOrders',
                'webOrders',
                'whatsappOrders',

                'totalDelivered',
                'webDelivered',
                'whatsappDelivered',

                'paymentReceivedOrders',
                'paymentReceivedAmount',

                'paymentPendingOrders',
                'paymentPendingAmount',

                'totalRto',
                'webRto',
                'whatsappRto',
                'rtoReceived',

                'totalTransit',
                'webTransit',
                'whatsappTransit',

                'totalNoStatus',
                'webNoStatus',
                'whatsappNoStatus',
                'searchTerms',
                'notFound'
            )
        );
    }
    public function exportSelected(Request $request)
    {
        $ids = explode(',', $request->ids);

        return Excel::download(
            new SelectedOrdersExport($ids),
            'Selected_Orders_' . now()->format('d-m-Y_H-i-s') . '.xlsx'
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
