<?php

namespace App\Http\Controllers;

use App\Models\Barcode;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\LabelSender;
use App\Models\Client;
use Carbon\Carbon;
use App\Models\callingorder;
use App\Models\User;
use App\Models\CallingUser;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\VerifiedOrdersExport;
use Illuminate\Support\Facades\DB;
use App\Models\conversation;
use App\Models\Payment;
use App\Exports\StaffPerformanceExport;
use App\Exports\StaffPerformanceOrdersExport;
use App\Exports\SelectedStaffExport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\RtoReport;
use App\Models\OrderAssignmentScheduler;
use App\Imports\SelloshipImport;

use Illuminate\Support\Facades\File;

use Symfony\Component\Process\Process;

class AdminController extends Controller
{

    private function isClient()
    {
        return auth()->check() && auth()->user()->role === 'client';
    }

    private function clientId()
    {
        return auth()->user()?->client_id;
    }

    private function getRecordingMime(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'm4a' => 'audio/mp4',
            'mp3' => 'audio/mpeg',
            'amr' => 'audio/mpeg', // AMR is converted to MP3 for browser playback
            'wav' => 'audio/wav',
            'aac' => 'audio/aac',
            'ogg', 'opus' => 'audio/ogg',
            '3gp', '3gpp' => 'audio/3gpp',
            default => 'application/octet-stream',
        };
    }
    public function allStaffRecordings(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|integer|exists:calling_users,id',
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $staffId = (int) $request->staff_id;

        if ($this->isClient()) {
            $allowed = DB::table('callingorder')
                ->where('client_id', $this->clientId())
                ->where('assigned_to', $staffId)
                ->exists();

            abort_unless($allowed, 403, 'Unauthorized staff access.');
        }

        $from = Carbon::parse($request->from)->startOfDay();
        $to = Carbon::parse($request->to)->endOfDay();

        $recordings = DB::table('staff_call_recordings')
            ->where('staff_id', $staffId)
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('created_at')
            ->paginate(100);

        $recordings->getCollection()->transform(function ($item) {

            $uploaded = strtolower((string) $item->upload_status) === 'uploaded'
                && !empty($item->recording_path);

            $item->recording_url = $uploaded
                ? route('admin.staff-recordings.play', $item->id)
                : null;

            // Correct MIME based on actual file extension
            $item->recording_mime = $uploaded
                ? $this->getRecordingMime($item->recording_path)
                : null;

            return $item;
        });

        return response()->json([
            'success' => true,
            'total' => $recordings->total(),
            'current_page' => $recordings->currentPage(),
            'last_page' => $recordings->lastPage(),
            'recordings' => $recordings->items(),
        ]);
    }



    public function staffRecordingHistory(Request $request)
    {
        $request->validate([
            'staff_id'       => 'required|integer|exists:calling_users,id',
            'customer_phone' => 'required|string|max:30',
            'from'           => 'required|date',
            'to'             => 'required|date|after_or_equal:from',
        ]);

        $staffId = (int) $request->staff_id;

        // Client access restriction
        if ($this->isClient()) {
            $allowed = DB::table('callingorder')
                ->where('assigned_to', $staffId)
                ->where('client_id', $this->clientId())
                ->exists();

            abort_unless($allowed, 403, 'Unauthorized staff access.');
        }

        // Normalize phone numbers
        $normalizePhone = function ($phone) {
            $phone = preg_replace('/\D+/', '', (string) $phone);

            return strlen($phone) > 10
                ? substr($phone, -10)
                : $phone;
        };

        $customerPhone = $normalizePhone($request->customer_phone);

        if (strlen($customerPhone) !== 10) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid customer phone number.',
                'recordings' => [],
            ], 422);
        }

        $from = Carbon::parse($request->from)->startOfDay();
        $to = Carbon::parse($request->to)->endOfDay();

        // Fetch recordings for selected staff and date range
        $recordings = DB::table('staff_call_recordings')
            ->where('staff_id', $staffId)
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('customer_phone')
            ->orderByDesc('created_at')
            ->get()
            ->filter(function ($recording) use (
                $normalizePhone,
                $customerPhone
            ) {
                return $normalizePhone($recording->customer_phone)
                    === $customerPhone;
            })
            ->values();

        $result = $recordings->map(function ($recording) {

            $uploaded = strtolower(trim((string) $recording->upload_status)) === 'uploaded'
                && !empty($recording->recording_path);

            return [
                'id' => $recording->id,
                'staff_id' => $recording->staff_id,
                'customer_phone' => $recording->customer_phone,
                'device_phone' => $recording->device_phone,
                'customer_name' => $recording->customer_name,
                'call_type' => $recording->call_type,
                'call_started_at' => $recording->call_started_at,
                'duration_seconds' => $recording->duration_seconds,
                'created_at' => $recording->created_at,
                'recording_path' => $recording->recording_path,

                'recording_mime' => $uploaded
                    ? $this->getRecordingMime($recording->recording_path)
                    : null,

                'recording_size' => $recording->recording_size,
                'upload_status' => $recording->upload_status,
                'upload_error' => $recording->upload_error,

                'recording_url' => $uploaded
                    ? route('admin.staff-recordings.play', $recording->id)
                    : null,
            ];
        });

        return response()->json([
            'success' => true,
            'staff_id' => $staffId,
            'customer_phone' => $request->customer_phone,
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'total' => $result->count(),
            'recordings' => $result,
        ]);
    }




    /**
     * Play a recording from staff_call_recordings.
     * This is a separate playback route.
     */

    public function playStaffRecording($id)
    {
        $recording = DB::table('staff_call_recordings')
            ->where('id', $id)
            ->where('upload_status', 'uploaded')
            ->first();

        abort_if(
            !$recording || empty($recording->recording_path),
            404,
            'Recording not found.'
        );

        $relativePath = ltrim($recording->recording_path, '/');

        if (
            str_contains($relativePath, '..') ||
            str_contains($relativePath, "\0")
        ) {
            abort(403);
        }

        // Original files are stored in storage/app/private
        $privateRoot = realpath(storage_path('app/private'));

        abort_if(!$privateRoot, 500, 'Private storage directory not found.');

        $originalPath = realpath(
            $privateRoot . DIRECTORY_SEPARATOR . $relativePath
        );

        // Ensure the resolved file remains inside private storage
        abort_if(
            !$originalPath ||
                !str_starts_with(
                    $originalPath,
                    $privateRoot . DIRECTORY_SEPARATOR
                ) ||
                !is_file($originalPath),
            404,
            'Recording file not found on server.'
        );

        $extension = strtolower(pathinfo($originalPath, PATHINFO_EXTENSION));

        $playPath = $originalPath;
        $mime = $this->getRecordingMime($relativePath);

        // AMR is not reliably supported by Chrome.
        // Convert it to MP3 using FFmpeg.
        if ($extension === 'amr') {

            $convertedDirectory = storage_path(
                'app/private/call-recordings/converted'
            );

            File::ensureDirectoryExists($convertedDirectory);

            $convertedPath = $convertedDirectory . DIRECTORY_SEPARATOR
                . 'recording_' . $recording->id . '.mp3';

            if (
                !is_file($convertedPath) ||
                filemtime($convertedPath) < filemtime($originalPath)
            ) {

                $process = new Process([
                    '/usr/bin/ffmpeg',
                    '-y',
                    '-i',
                    $originalPath,
                    '-vn',
                    '-codec:a',
                    'libmp3lame',
                    '-q:a',
                    '4',
                    $convertedPath,
                ]);

                $process->setTimeout(180);
                $process->run();

                if (!$process->isSuccessful() || !is_file($convertedPath)) {
                    abort(
                        500,
                        'AMR conversion failed. Please check FFmpeg installation.'
                    );
                }
            }

            $playPath = $convertedPath;
            $mime = 'audio/mpeg';
        }

        return response()->file($playPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="recording.' .
                ($extension === 'amr' ? 'mp3' : $extension) . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
        ]);
    }

    public function dayWiseStaffPerformance(Request $request)
    {

        $dateFrom = $request->filled('date_from')
            ? Carbon::parse($request->date_from)->format('Y-m-d')
            : now()->format('Y-m-d');

        $dateTo = $request->filled('date_to')
            ? Carbon::parse($request->date_to)->format('Y-m-d')
            : now()->format('Y-m-d');

        if (Carbon::parse($dateFrom)->gt(Carbon::parse($dateTo))) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }
        if ($this->isClient()) {

            $clients = DB::table('clients')
                ->select(
                    'id',
                    'client_name'
                )
                ->where(
                    'id',
                    $this->clientId()
                )
                ->get();
        } else {

            $clients = DB::table('clients')
                ->select(
                    'id',
                    'client_name'
                )
                ->orderBy('client_name')
                ->get();
        }


        if ($this->isClient()) {

            // Force logged-in client's ID
            $clientId = (int) $this->clientId();
        } else {

            // Admin / Super Admin can select client
            $clientId = $request->filled('client_id')
                ? (int) $request->client_id
                : null;
        }

        $selectedStaffIds = $request->input('staff_id', []);

        if (!is_array($selectedStaffIds)) {
            $selectedStaffIds = [$selectedStaffIds];
        }

        $selectedStaffIds = collect($selectedStaffIds)
            ->filter(function ($id) {
                return is_numeric($id) && (int) $id > 0;
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values()
            ->toArray();

        $staffs = collect();

        if ($clientId) {

            $staffs = DB::table('calling_users as cu')
                ->join(
                    'callingorder as c',
                    'c.assigned_to',
                    '=',
                    'cu.id'
                )
                ->select(
                    'cu.id',
                    'cu.name'
                )
                ->where('cu.status', 1)
                ->where(
                    'c.client_id',
                    $clientId
                )
                ->distinct()
                ->orderBy('cu.name')
                ->get();
        }

        if (!$clientId) {

            return view(
                'reports.day-wise-staff-performance',
                [
                    'clients' => $clients,

                    'clientId' => null,

                    'staffs' => collect(),

                    'selectedStaffIds' => [],

                    'dateFrom' => $dateFrom,

                    'dateTo' => $dateTo,

                    'staffWiseReport' => collect(),

                    'summary' => [
                        'total_leads' => 0,
                        'confirm' => 0,
                        'confirm_percent' => 0,
                        'delivery' => 0,
                        'delivery_percent' => 0,
                        'rto' => 0,
                        'rto_percent' => 0,
                    ],
                ]
            );
        }

        if (empty($selectedStaffIds)) {

            return view(
                'reports.day-wise-staff-performance',
                [
                    'clients' => $clients,

                    'clientId' => $clientId,

                    'staffs' => $staffs,

                    'selectedStaffIds' => [],

                    'dateFrom' => $dateFrom,

                    'dateTo' => $dateTo,

                    'staffWiseReport' => collect(),

                    'summary' => [
                        'total_leads' => 0,
                        'confirm' => 0,
                        'confirm_percent' => 0,
                        'delivery' => 0,
                        'delivery_percent' => 0,
                        'rto' => 0,
                        'rto_percent' => 0,
                    ],
                ]
            );
        }

        $selectedStaffIds = $staffs
            ->whereIn(
                'id',
                $selectedStaffIds
            )
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->values()
            ->toArray();

        if (empty($selectedStaffIds)) {

            return view(
                'reports.day-wise-staff-performance',
                [
                    'clients' => $clients,

                    'clientId' => $clientId,

                    'staffs' => $staffs,

                    'selectedStaffIds' => [],

                    'dateFrom' => $dateFrom,

                    'dateTo' => $dateTo,

                    'staffWiseReport' => collect(),

                    'summary' => [
                        'total_leads' => 0,
                        'confirm' => 0,
                        'confirm_percent' => 0,
                        'delivery' => 0,
                        'delivery_percent' => 0,
                        'rto' => 0,
                        'rto_percent' => 0,
                    ],
                ]
            );
        }

        $latestOrders = DB::table('orders')
            ->select(
                DB::raw('TRIM(order_id) as normalized_order_id'),
                DB::raw('MAX(id) as latest_id')
            )
            ->whereNotNull('order_id')
            ->whereRaw("TRIM(order_id) <> ''")
            ->groupBy(
                DB::raw('TRIM(order_id)')
            );

        $query = DB::table('callingorder as c')

            ->join(
                'calling_users as cu',
                'cu.id',
                '=',
                'c.assigned_to'
            )

            ->leftJoin(
                'clients as cl',
                'cl.id',
                '=',
                'c.client_id'
            )

            ->leftJoinSub(
                $latestOrders,
                'lo',
                function ($join) {

                    $join->on(
                        'lo.normalized_order_id',
                        '=',
                        DB::raw('TRIM(c.order_id)')
                    );
                }
            )

            ->leftJoin(
                'orders as o',
                'o.id',
                '=',
                'lo.latest_id'
            )

            ->select([
                'c.id',

                'c.client_id',

                'cl.client_name',

                'c.assigned_to as staff_id',

                'cu.name as staff_name',

                'c.order_id',

                'c.status as call_status',

                'c.updated_at as calling_updated_at',

                'o.delivery_status',
            ])

            ->where(
                'c.client_id',
                $clientId
            )

            ->whereBetween(
                'c.updated_at',
                [
                    Carbon::parse($dateFrom)->startOfDay(),

                    Carbon::parse($dateTo)->endOfDay(),
                ]
            )

            ->whereIn(
                'c.assigned_to',
                $selectedStaffIds
            );

        $rows = $query
            ->orderBy('cu.name')
            ->orderBy('c.updated_at')
            ->orderBy('c.id')
            ->get();

        $normalize = function ($value) {

            return strtolower(
                trim(
                    (string) ($value ?? '')
                )
            );
        };

        $groupedRows = $rows->groupBy(
            function ($row) {

                return Carbon::parse(
                    $row->calling_updated_at
                )->format('Y-m-d')
                    . '_'
                    . $row->staff_id;
            }
        );

        $reportStaffs = $staffs
            ->whereIn(
                'id',
                $selectedStaffIds
            )
            ->sortBy('name')
            ->values();

        $staffWiseReport = collect();


        $start = Carbon::parse(
            $dateFrom
        )->startOfDay();

        $end = Carbon::parse(
            $dateTo
        )->startOfDay();


        foreach ($reportStaffs as $staff) {

            $staffDailyRows = collect();


            $current = $start->copy();


            while ($current->lte($end)) {

                $currentDate = $current->format('Y-m-d');

                $key = $currentDate . '_' . $staff->id;

                $staffRows = $groupedRows->get(
                    $key,
                    collect()
                );

                $totalLeads = $staffRows->count();

                $confirm = $staffRows
                    ->filter(function ($row) use ($normalize) {

                        return in_array(
                            $normalize($row->call_status),
                            [
                                'verified',
                                'confirm',
                                'confirmed',
                            ],
                            true
                        );
                    })
                    ->count();

                $delivery = $staffRows
                    ->filter(function ($row) use ($normalize) {

                        return $normalize(
                            $row->delivery_status
                        ) === 'delivered';
                    })
                    ->count();

                $rto = $staffRows
                    ->filter(function ($row) use ($normalize) {

                        return $normalize(
                            $row->delivery_status
                        ) === 'rto-intrasit';
                    })
                    ->count();

                $confirmPercent = $totalLeads > 0
                    ? round(
                        ($confirm / $totalLeads) * 100,
                        2
                    )
                    : 0;


                $deliveryPercent = $totalLeads > 0
                    ? round(
                        ($delivery / $totalLeads) * 100,
                        2
                    )
                    : 0;


                $rtoPercent = $totalLeads > 0
                    ? round(
                        ($rto / $totalLeads) * 100,
                        2
                    )
                    : 0;

                $staffDailyRows->push([
                    'date' => $currentDate,

                    'staff_id' => $staff->id,

                    'staff_name' => $staff->name,

                    'total_leads' => $totalLeads,

                    'confirm' => $confirm,

                    'confirm_percent' => $confirmPercent,

                    'delivery' => $delivery,

                    'delivery_percent' => $deliveryPercent,

                    'rto' => $rto,

                    'rto_percent' => $rtoPercent,
                ]);


                $current->addDay();
            }

            $staffTotalLeads = $staffDailyRows->sum(
                'total_leads'
            );

            $staffConfirm = $staffDailyRows->sum(
                'confirm'
            );

            $staffDelivery = $staffDailyRows->sum(
                'delivery'
            );

            $staffRto = $staffDailyRows->sum(
                'rto'
            );


            /*
        |--------------------------------------------------------------------------
        | STAFF SUMMARY PERCENTAGES
        |--------------------------------------------------------------------------
        |
        | Based on total counts.
        | NOT average of daily percentages.
        |
        */

            $staffConfirmPercent = $staffTotalLeads > 0
                ? round(
                    ($staffConfirm / $staffTotalLeads) * 100,
                    2
                )
                : 0;


            $staffDeliveryPercent = $staffTotalLeads > 0
                ? round(
                    ($staffDelivery / $staffTotalLeads) * 100,
                    2
                )
                : 0;


            $staffRtoPercent = $staffTotalLeads > 0
                ? round(
                    ($staffRto / $staffTotalLeads) * 100,
                    2
                )
                : 0;


            /*
        |--------------------------------------------------------------------------
        | STAFF SECTION
        |--------------------------------------------------------------------------
        */

            $staffWiseReport->push([

                'staff_id' => $staff->id,

                'staff_name' => $staff->name,

                'rows' => $staffDailyRows,

                'total_leads' => $staffTotalLeads,

                'confirm' => $staffConfirm,

                'confirm_percent' => $staffConfirmPercent,

                'delivery' => $staffDelivery,

                'delivery_percent' => $staffDeliveryPercent,

                'rto' => $staffRto,

                'rto_percent' => $staffRtoPercent,
            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | OVERALL SUMMARY
    |--------------------------------------------------------------------------
    |
    | Selected staff + selected date range + selected client.
    |
    */

        $summaryTotalLeads = $staffWiseReport->sum(
            'total_leads'
        );

        $summaryConfirm = $staffWiseReport->sum(
            'confirm'
        );

        $summaryDelivery = $staffWiseReport->sum(
            'delivery'
        );

        $summaryRto = $staffWiseReport->sum(
            'rto'
        );


        /*
    |--------------------------------------------------------------------------
    | OVERALL PERCENTAGES
    |--------------------------------------------------------------------------
    */

        $summaryConfirmPercent = $summaryTotalLeads > 0
            ? round(
                ($summaryConfirm / $summaryTotalLeads) * 100,
                2
            )
            : 0;


        $summaryDeliveryPercent = $summaryTotalLeads > 0
            ? round(
                ($summaryDelivery / $summaryTotalLeads) * 100,
                2
            )
            : 0;


        $summaryRtoPercent = $summaryTotalLeads > 0
            ? round(
                ($summaryRto / $summaryTotalLeads) * 100,
                2
            )
            : 0;


        /*
    |--------------------------------------------------------------------------
    | SUMMARY ARRAY
    |--------------------------------------------------------------------------
    */

        $summary = [

            'total_leads' => $summaryTotalLeads,

            'confirm' => $summaryConfirm,

            'confirm_percent' => $summaryConfirmPercent,

            'delivery' => $summaryDelivery,

            'delivery_percent' => $summaryDeliveryPercent,

            'rto' => $summaryRto,

            'rto_percent' => $summaryRtoPercent,
        ];


        /*
    |--------------------------------------------------------------------------
    | VIEW
    |--------------------------------------------------------------------------
    */

        return view(
            'reports.day-wise-staff-performance',
            compact(
                'clients',
                'clientId',
                'staffs',
                'selectedStaffIds',
                'dateFrom',
                'dateTo',
                'staffWiseReport',
                'summary'
            )
        );
    }
    public function assignmentScheduler()
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

        $staff = CallingUser::where(
            'status',
            1
        )->orderBy('name')->get();

        $schedulers = OrderAssignmentScheduler::with(
            'client'
        )
            ->when(
                $this->isClient(),
                function ($q) {
                    $q->where(
                        'client_id',
                        $this->clientId()
                    );
                }
            )
            ->latest()
            ->get();

        return view(
            'assignment-scheduler',
            compact(
                'clients',
                'staff',
                'schedulers'
            )
        );
    }

    public function editCallingOrder($id)
    {
        $order = CallingOrder::findOrFail($id);

        return response()->json($order);
    }

    public function updateCallingOrder(Request $request, $id)
    {
        $request->validate([

            'product_name' => 'nullable|string|max:1000',
            'quantity' => 'nullable|integer|min:1',

            'customer_name' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',

            'customer_phone' => 'nullable|string|max:30',

            'shipping_address' => 'nullable|string|max:2000',

            'age' => 'nullable|integer|min:0|max:150',

            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',

            'payment_mode' => 'nullable|string|max:100',

            'amount' => 'nullable|numeric|min:0',

            'status' => 'nullable|string|max:100',

            'remarks' => 'nullable|string|max:2000',

        ]);


        $order = CallingOrder::findOrFail($id);


        $order->update([

            'product_name' =>
            $request->product_name,

            'quantity' =>
            $request->quantity,

            'customer_name' =>
            $request->customer_name,

            'father_name' =>
            $request->father_name,

            'customer_phone' =>
            $request->customer_phone,

            'shipping_address' =>
            $request->shipping_address,

            'age' =>
            $request->age,

            'city' =>
            $request->city,

            'state' =>
            $request->state,

            'pincode' =>
            $request->pincode,

            'payment_mode' =>
            $request->payment_mode,

            'amount' =>
            $request->amount,

            'status' =>
            $request->status,

            'remarks' =>
            $request->remarks,

        ]);


        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'order' => $order->fresh(),
        ]);
    }
    public function saveAssignmentScheduler(Request $request)
    {
        $request->validate([

            'scheduler_id' => [
                'nullable',
                'integer',
                'exists:order_assignment_schedulers,id'
            ],

            'client_id' => [
                'required',
                'exists:clients,id'
            ],

            'order_types' => [
                'required',
                'array',
                'min:1',
            ],

            'order_types.*' => [
                'in:shopify,abandoned_checkout,deliveredreorder,rto',
            ],

            'start_time' => [
                'required',
                'date_format:H:i'
            ],

            'end_time' => [
                'required',
                'date_format:H:i'
            ],

            'days' => [
                'nullable',
                'array',
            ],

            'days.*' => [
                'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            ],

            'staff_ids' => [
                'nullable',
                'array',
            ],

            'staff_ids.*' => [
                'integer',
                'exists:calling_users,id',
            ],

            'staff_percentages' => [
                'nullable',
                'array',
            ],

            'is_active' => [
                'nullable',
            ],
        ]);

        if (
            $this->isClient() &&
            (int) $request->client_id !==
            (int) $this->clientId()
        ) {
            abort(403, 'Unauthorized Access');
        }

        if ($request->start_time >= $request->end_time) {

            return back()
                ->withInput()
                ->with(
                    'error',
                    'End time must be greater than start time.'
                );
        }

        $isRtoScheduler = in_array(
            'rto',
            $request->order_types ?? [],
            true
        );

        $staffAssignments = [];

        $totalPercentage = 0;

        if (!$isRtoScheduler) {

            foreach ($request->staff_ids ?? [] as $staffId) {

                $percentage = (float) (
                    $request->staff_percentages[$staffId] ?? 0
                );

                if ($percentage <= 0) {
                    continue;
                }

                $staffAssignments[] = [
                    'staff_id' => (int) $staffId,
                    'percentage' => $percentage,
                ];

                $totalPercentage += $percentage;
            }

            if (abs($totalPercentage - 100) > 0.01) {

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Staff percentage must be exactly 100%. Current: '
                            . $totalPercentage . '%'
                    );
            }
        } else {

            $staffAssignments = [];
        }

        $scheduler = $request->scheduler_id
            ? OrderAssignmentScheduler::findOrFail(
                $request->scheduler_id
            )
            : new OrderAssignmentScheduler();

        if (
            $scheduler->exists &&
            $this->isClient() &&
            (int) $scheduler->client_id !==
            (int) $this->clientId()
        ) {
            abort(403, 'Unauthorized Access');
        }


        $scheduler->client_id =
            $request->client_id;

        $scheduler->order_types =
            $request->order_types;

        $scheduler->start_time =
            $request->start_time;

        $scheduler->end_time =
            $request->end_time;

        $scheduler->days =
            $request->days ?? [];

        $scheduler->staff_assignments =
            $staffAssignments;

        $scheduler->is_active =
            $request->has('is_active');


        $scheduler->save();


        return back()->with(
            'success',
            'Assignment Scheduler Saved Successfully.'
        );
    }

    public function selloshipImportPage()
    {
        if ($this->isClient()) {

            $clients = Client::where(
                'id',
                $this->clientId()
            )
                ->orderBy('client_name')
                ->get();
        } else {

            $clients = Client::orderBy(
                'client_name'
            )->get();
        }

        return view(
            'selloship-import',
            compact('clients')
        );
    }

    public function selloshipImport(
        Request $request
    ) {

        $request->validate([

            'client_id' =>
            'required|exists:clients,id',

            'file' =>
            'required|file|mimes:xlsx,xls,csv|max:20480',

        ]);

        if (
            $this->isClient() &&
            (int) $request->client_id !==
            (int) $this->clientId()
        ) {

            abort(
                403,
                'Unauthorized Access'
            );
        }


        try {

            Excel::import(

                new SelloshipImport(
                    $request->client_id
                ),

                $request->file('file')

            );


            return back()->with(
                'success',
                'Selloship records imported successfully.'
            );
        } catch (\Throwable $e) {

            return back()->with(
                'error',
                'Selloship import failed: ' .
                    $e->getMessage()
            );
        }
    }
    public function rtoStaffAllocation($clientId)
    {
        try {

            $rtoOrders = DB::table('rto_reports')
                ->join(
                    'orders',
                    'orders.order_id',
                    '=',
                    'rto_reports.order_id'
                )
                ->leftJoin(
                    'callingorder',
                    'callingorder.order_id',
                    '=',
                    'orders.order_id'
                )
                ->where('orders.client_id', $clientId)
                ->where('rto_reports.is_exported', 0)
                ->select(
                    'rto_reports.order_id',
                    'callingorder.assigned_to as original_staff_id'
                )
                ->orderBy('rto_reports.id')
                ->get();

            $allStaff = CallingUser::orderBy('id')->get();

            $activeStaff = $allStaff
                ->where('status', 1)
                ->values();

            $allocation = [];


            foreach ($rtoOrders as $order) {

                $originalStaffId = $order->original_staff_id;

                if (!$originalStaffId) {
                    continue;
                }


                $originalStaff = $allStaff->firstWhere(
                    'id',
                    $originalStaffId
                );

                if (
                    $originalStaff &&
                    (int) $originalStaff->status === 1
                ) {

                    $assignTo = (int) $originalStaff->id;
                } else {

                    $replacement = $activeStaff
                        ->first(function ($staff) use ($originalStaffId) {

                            return (int) $staff->id >
                                (int) $originalStaffId;
                        });

                    if (!$replacement) {
                        $replacement = $activeStaff->first();
                    }


                    if (!$replacement) {
                        continue;
                    }


                    $assignTo = (int) $replacement->id;
                }

                $allocation[$assignTo] =
                    ($allocation[$assignTo] ?? 0) + 1;
            }


            $staff = $activeStaff->map(function ($member) use ($allocation) {

                return [
                    'id'    => $member->id,
                    'name'  => $member->name,
                    'count' => $allocation[$member->id] ?? 0,
                ];
            })->values();


            return response()->json([
                'success' => true,
                'total'   => $rtoOrders->count(),
                'staff'   => $staff,
            ]);
        } catch (\Throwable $e) {

            Log::error('RTO Staff Allocation Error', [
                'client_id' => $clientId,
                'message'   => $e->getMessage(),
                'line'      => $e->getLine(),
                'file'      => $e->getFile(),
            ]);


            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function toggleAssignmentScheduler($id)
    {
        $scheduler =
            OrderAssignmentScheduler::findOrFail($id);


        if (
            $this->isClient() &&
            (int) $scheduler->client_id !==
            (int) $this->clientId()
        ) {
            abort(403, 'Unauthorized Access');
        }


        $scheduler->is_active =
            !$scheduler->is_active;

        $scheduler->save();


        return back()->with(
            'success',
            $scheduler->is_active
                ? 'Auto Assignment Enabled.'
                : 'Auto Assignment Disabled.'
        );
    }

    public function deleteAssignmentScheduler($id)
    {
        $scheduler =
            OrderAssignmentScheduler::findOrFail($id);


        if (
            $this->isClient() &&
            (int) $scheduler->client_id !==
            (int) $this->clientId()
        ) {
            abort(403, 'Unauthorized Access');
        }


        $scheduler->delete();


        return back()->with(
            'success',
            'Scheduler Deleted Successfully.'
        );
    }

    public function assignmentSchedulerSuggestion(Request $request)
    {
        $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
        ]);

        $clientId = (int) $request->client_id;

        $from = Carbon::now()
            ->subDays(30)
            ->startOfDay();

        $to = Carbon::now()
            ->endOfDay();

        $allStaff = DB::table('calling_users as cu')
            ->join(
                'callingorder as c',
                'c.assigned_to',
                '=',
                'cu.id'
            )
            ->where(
                'c.client_id',
                $clientId
            )
            ->where(
                'cu.status',
                1
            )
            ->select(
                'cu.id',
                'cu.name'
            )
            ->distinct()
            ->orderBy('cu.name')
            ->get();


        if ($allStaff->isEmpty()) {

            return response()->json([
                'success' => false,
                'message' => 'No active staff found for this client.',
                'staff' => [],
            ]);
        }

        $latestOrders = DB::table('orders')
            ->select(
                'order_id',
                DB::raw('MAX(id) as latest_id')
            )
            ->whereNotNull('order_id')
            ->where('order_id', '!=', '')
            ->groupBy('order_id');

        $rows = DB::table('callingorder as c')

            ->join(
                'calling_users as cu',
                'cu.id',
                '=',
                'c.assigned_to'
            )

            ->leftJoin(
                'clients as cl',
                'cl.id',
                '=',
                'c.client_id'
            )

            ->leftJoinSub(
                $latestOrders,
                'lo',
                function ($join) {

                    $join->on(
                        'lo.order_id',
                        '=',
                        'c.order_id'
                    );
                }
            )

            ->leftJoin(
                'orders as o',
                'o.id',
                '=',
                'lo.latest_id'
            )

            ->where(
                'c.client_id',
                $clientId
            )

            ->whereBetween(
                'c.updated_at',
                [
                    $from,
                    $to
                ]
            )

            ->select([
                'c.id',
                'c.client_id',
                'cl.client_name',
                'c.assigned_to as staff_id',
                'cu.name as staff_name',
                'c.order_id',
                'c.order_source',
                'c.status as call_status',
                'o.delivery_status',
                'c.created_at as calling_date',
                'c.updated_at as calling_updated_at',
                'o.updated_at as delivery_updated_at',
            ])

            ->get();

        $grouped =
            $rows->groupBy('staff_id');

        $staffPerformance = [];


        foreach ($allStaff as $staff) {

            $staffRows =
                $grouped->get(
                    $staff->id,
                    collect()
                );


            $total =
                $staffRows->count();

            $verified =
                $staffRows->filter(
                    function ($row) {

                        return in_array(
                            strtolower(
                                trim(
                                    (string)
                                    $row->call_status
                                )
                            ),
                            [
                                'verified',
                                'confirm',
                                'confirmed'
                            ],
                            true
                        );
                    }
                )->count();


            $pending =
                $staffRows->filter(
                    function ($row) {

                        return strtolower(
                            trim(
                                (string)
                                $row->call_status
                            )
                        ) === 'pending';
                    }
                )->count();


            $cancel =
                $staffRows->filter(
                    function ($row) {

                        return in_array(
                            strtolower(
                                trim(
                                    (string)
                                    $row->call_status
                                )
                            ),
                            [
                                'cancel',
                                'cancelled',
                                'canceled'
                            ],
                            true
                        );
                    }
                )->count();


            $notReachable =
                $staffRows->filter(
                    function ($row) {

                        return in_array(
                            strtolower(
                                trim(
                                    (string)
                                    $row->call_status
                                )
                            ),
                            [
                                'not reachable',
                                'not_reachable',
                                'not-reachable'
                            ],
                            true
                        );
                    }
                )->count();


            $sameOrder =
                $staffRows->filter(
                    function ($row) {

                        return in_array(
                            strtolower(
                                trim(
                                    (string)
                                    $row->call_status
                                )
                            ),
                            [
                                'same order',
                                'same_order'
                            ],
                            true
                        );
                    }
                )->count();

            $delivered =
                $staffRows->filter(
                    function ($row) {

                        return trim(
                            (string)
                            $row->delivery_status
                        ) === 'Delivered';
                    }
                )->count();


            $rtoIntransit =
                $staffRows->filter(
                    function ($row) {

                        return trim(
                            (string)
                            $row->delivery_status
                        ) === 'RTO-intrasit';
                    }
                )->count();


            $rtoReceived =
                $staffRows->filter(
                    function ($row) {

                        return trim(
                            (string)
                            $row->delivery_status
                        ) === 'RTO Received';
                    }
                )->count();

            $confirmationRate =
                $total > 0
                ? (
                    $verified /
                    $total
                ) * 100
                : 0;


            $reachable =
                max(
                    0,
                    $total -
                        $notReachable
                );


            $reachabilityRate =
                $total > 0
                ? (
                    $reachable /
                    $total
                ) * 100
                : 0;


            $cancelRate =
                $total > 0
                ? (
                    $cancel /
                    $total
                ) * 100
                : 0;


            $deliveryRate =
                $verified > 0
                ? (
                    $delivered /
                    $verified
                ) * 100
                : 0;


            $rtoTotal =
                $rtoIntransit +
                $rtoReceived;


            $rtoRate =
                $verified > 0
                ? (
                    $rtoTotal /
                    $verified
                ) * 100
                : 0;

            $volumeScore =
                min(
                    (
                        $total /
                        100
                    ) * 5,
                    5
                );

            $score =

                ($confirmationRate * 0.30)

                +

                ($deliveryRate * 0.30)

                +

                ($reachabilityRate * 0.15)

                +

                ((100 - $cancelRate) * 0.10)

                +

                ((100 - $rtoRate) * 0.10)

                +

                $volumeScore;


            $score =
                min(
                    round(
                        $score,
                        2
                    ),
                    100
                );

            if ($score >= 80) {

                $rating = 'Excellent';
            } elseif ($score >= 70) {

                $rating = 'Very Good';
            } elseif ($score >= 60) {

                $rating = 'Good';
            } elseif ($score >= 50) {

                $rating = 'Average';
            } else {

                $rating = 'Needs Improvement';
            }


            $staffPerformance[] = [

                'staff_id' =>
                (int) $staff->id,

                'staff_name' =>
                $staff->name,

                'leads' =>
                $total,

                'pending' =>
                $pending,

                'verified' =>
                $verified,

                'cancel' =>
                $cancel,

                'not_reachable' =>
                $notReachable,

                'same_order' =>
                $sameOrder,

                'delivered' =>
                $delivered,

                'rto_intransit' =>
                $rtoIntransit,

                'rto_received' =>
                $rtoReceived,

                'confirmation_rate' =>
                round(
                    $confirmationRate,
                    2
                ),

                'reachability_rate' =>
                round(
                    $reachabilityRate,
                    2
                ),

                'cancel_rate' =>
                round(
                    $cancelRate,
                    2
                ),

                'delivery_rate' =>
                round(
                    $deliveryRate,
                    2
                ),

                'rto_rate' =>
                round(
                    $rtoRate,
                    2
                ),

                'score' =>
                $score,

                'rating' =>
                $rating,

                'suggested_percentage' =>
                0,

                'top_five' =>
                false,
            ];
        }

        usort(
            $staffPerformance,
            function ($a, $b) {

                if (
                    $a['score'] !=
                    $b['score']
                ) {

                    return
                        $b['score']
                        <=>
                        $a['score'];
                }

                if (
                    $a['verified'] !=
                    $b['verified']
                ) {

                    return
                        $b['verified']
                        <=>
                        $a['verified'];
                }

                return
                    $b['leads']
                    <=>
                    $a['leads'];
            }
        );

        $topFive =
            collect(
                $staffPerformance
            )
            ->filter(
                function ($staff) {

                    return
                        $staff['leads'] > 0;
                }
            )
            ->take(5)
            ->values();

        $topFiveScore =
            $topFive->sum(
                function ($staff) {

                    return max(
                        0,
                        (float)
                        $staff['score']
                    );
                }
            );

        if (
            $topFiveScore > 0
        ) {

            $assignedPercentage = 0;

            $topFiveCount =
                $topFive->count();


            foreach (
                $topFive
                as $index => $topStaff
            ) {

                $percentage =
                    (
                        $topStaff['score'] /
                        $topFiveScore
                    ) * 100;

                $percentage =
                    round(
                        $percentage,
                        2
                    );

                if (
                    $index ===
                    ($topFiveCount - 1)
                ) {

                    $percentage =
                        round(
                            100 -
                                $assignedPercentage,
                            2
                        );
                }

                foreach (
                    $staffPerformance
                    as $staffIndex => $staff
                ) {

                    if (
                        $staff['staff_id'] ===
                        $topStaff['staff_id']
                    ) {

                        $staffPerformance[$staffIndex]['suggested_percentage'] =
                            $percentage;

                        $staffPerformance[$staffIndex]['top_five'] =
                            true;

                        break;
                    }
                }


                $assignedPercentage +=
                    $percentage;
            }
        }

        usort(
            $staffPerformance,
            function ($a, $b) {

                if (
                    $a['top_five'] !=
                    $b['top_five']
                ) {

                    return
                        $b['top_five']
                        <=>
                        $a['top_five'];
                }


                return
                    $b['score']
                    <=>
                    $a['score'];
            }
        );

        $totalPercentage =
            round(
                array_sum(
                    array_column(
                        $staffPerformance,
                        'suggested_percentage'
                    )
                ),
                2
            );

        $bestStaff =
            $topFive->first();

        return response()->json([

            'success' =>
            true,

            'client_id' =>
            $clientId,

            'period' => [

                'from' =>
                $from->format('Y-m-d'),

                'to' =>
                $to->format('Y-m-d'),

                'days' =>
                30,
            ],

            'total_staff' =>
            count(
                $staffPerformance
            ),

            'top_five_count' =>
            $topFive->count(),

            'best_staff' =>
            $bestStaff,

            'staff' =>
            array_values(
                $staffPerformance
            ),

            'total_suggested_percentage' =>
            $totalPercentage,
        ]);
    }
    public function dashboard()
    {
        $dashboardQuery = Order::query();


        if ($this->isClient()) {

            $dashboardQuery->where('client_id', $this->clientId());

            $totalclients = 1;
            $barcodes = Barcode::where('client_id', $this->clientId())
                ->orderBy('is_used')
                ->orderByDesc('created_at')
                ->get();
        } else {

            $totalclients = Client::count();

            $barcodes = Barcode::orderBy('is_used')
                ->orderByDesc('created_at')
                ->get();
        }
        $dashboardQuery1 = Order::query();


        $dashboardQuery->whereDate(
            'created_at',
            '>=',
            Carbon::now()->subDays(30)
        );

        $totalOrders = (clone $dashboardQuery1)->count();

        $ourSidePending = (clone $dashboardQuery)
            ->where(function ($q) {
                $q->whereNull('delivery_status')
                    ->orWhere('delivery_status', '');
            })
            ->count();

        $transit7 = (clone $dashboardQuery)
            ->whereIn('delivery_status', [
                'In Transit',
                'Out For Delivery'
            ])
            ->whereDate('intransitdate', '<=', now()->subDays(7))
            ->count();

        $rto5 = (clone $dashboardQuery)
            ->where('delivery_status', 'RTO')
            ->where('rtorecivedsts', 0)
            ->whereDate('rtodate', '<=', now()->subDays(5))
            ->count();


        $paymentQuery = Payment::whereDate(
            'bill_date',
            '>=',
            now()->subDays(30)
        );

        $paymentCount = (clone $paymentQuery)->count();

        $paymentAmount = (clone $paymentQuery)->sum('cod_value');
        return view('dashboard', compact(
            'barcodes',
            'totalOrders',
            'totalclients',
            'ourSidePending',
            'transit7',
            'paymentCount',
            'paymentAmount',
            'rto5'
        ));
    }
    public function dashboardOrders(Request $request)
    {
        $query = Order::query();

        if ($this->isClient()) {
            $query->where('client_id', $this->clientId());
        }

        $query->whereDate(
            'created_at',
            '>=',
            now()->subDays(30)
        );

        switch ($request->type) {

            case 'not_booked':

                $query->where(function ($q) {

                    $q->whereNull('delivery_status')
                        ->orWhere('delivery_status', '');
                });

                break;

            case 'transit7':

                $query->whereIn('delivery_status', [
                    'In Transit',
                    'Out For Delivery'
                ])
                    ->whereDate(
                        'created_at',
                        '<=',
                        now()->subDays(7)
                    );

                break;

            case 'rto5':

                $query->where('delivery_status', 'RTO')
                    ->where('rtorecivedsts', 0)
                    ->whereDate(
                        'created_at',
                        '<=',
                        now()->subDays(5)
                    );

                break;
        }

        return response()->json(

            $query->select(
                'order_id',
                'barcode',
                'customer_name',
                'customer_phone',
                'delivery_status',
                'amount',
                'created_at'
            )
                ->latest()
                ->get()

        );
    }

    public function staffVerified(Request $request)
    {
        $staffId = $request->staff_id;

        $query = CallingOrder::query()
            ->where('assigned_to', $staffId)
            ->where('status', 'verified')
            ->where('is_exported', 0);

        // Client sirf apna data dekhega
        if ($this->isClient()) {
            $query->where('client_id', $this->clientId());
        }

        // Date Filter
        if ($request->filled('from') && $request->filled('to')) {

            $from = Carbon::parse($request->from)->startOfDay();
            $to   = Carbon::parse($request->to)->endOfDay();

            $query->whereBetween('updated_at', [$from, $to]);
        }

        // Sirf Super Admin client filter use kar sakta hai
        if (!$this->isClient() && $request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->latest()->get();

        return view('staff_verified', compact(
            'orders',
            'staffId'
        ));
    }
    public function staffVerifiedExport(Request $request)
    {
        $staffId = $request->staff_id;

        $query = CallingOrder::with(['staff', 'client'])
            ->where('assigned_to', $staffId)
            ->where('status', 'verified')
            ->where('is_exported', 0);

        // Client Login
        if ($this->isClient()) {
            $query->where('client_id', $this->clientId());
        } else {
            if ($request->filled('client_id')) {
                $query->where('client_id', $request->client_id);
            }
        }

        // Date Filter
        if ($request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->from)->startOfDay();
            $to   = Carbon::parse($request->to)->endOfDay();

            $query->whereBetween('updated_at', [$from, $to]);
        }

        // Product Wise Sort
        $orders = $query
            ->orderBy('product_name', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();

        // Mark Exported
        if ($orders->isNotEmpty()) {
            CallingOrder::whereIn('id', $orders->pluck('id'))
                ->update([
                    'is_exported' => 1
                ]);
        }

        return Excel::download(
            new VerifiedOrdersExport($orders),
            'staff_verified_' . now()->format('d_m_Y_H_i') . '.xlsx'
        );
    }
    public function exportSelected1(Request $request)
    {
        $staffIds = is_array($request->staff_ids)
            ? $request->staff_ids
            : explode(',', $request->staff_ids);

        $query = CallingOrder::whereIn('assigned_to', $staffIds)

            ->where('status', 'verified')

            ->where('is_exported', 0)

            ->whereBetween('updated_at', [
                Carbon::parse($request->from)->startOfDay(),
                Carbon::parse($request->to)->endOfDay()
            ]);

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $orders = $query->get();

        if ($orders->isEmpty()) {

            return back()->with(
                'error',
                'No new verified orders available for export.'
            );
        }

        // Mark exported
        CallingOrder::whereIn('id', $orders->pluck('id'))
            ->update([
                'is_exported' => 1,

            ]);

        return Excel::download(
            new VerifiedOrdersExport($orders),
            'Verified_Orders_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function verifySelected(Request $request)
    {
        $from = \Carbon\Carbon::parse($request->from)->startOfDay();
        $to   = \Carbon\Carbon::parse($request->to)->endOfDay();

        $updated = CallingOrder::whereIn('assigned_to', $request->staff_ids)
            ->where('status', 'pending')
            ->whereBetween('updated_at', [$from, $to])
            ->update([
                'status' => 'verified'
            ]);

        return response()->json([
            'updated' => $updated,
            'message' => "$updated Orders Verified Successfully"
        ]);
    }
    public function orderDetails(Request $request)
    {

        $request->validate([
            'staff_id' => 'required|exists:calling_users,id',
        ]);

        $staff = CallingUser::findOrFail(
            $request->staff_id
        );

        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : Carbon::today()->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : Carbon::today()->endOfDay();

        $standardStatuses = [
            'pending',
            'verified',
            'cancel',
            'not_reachable',
            'same_order'
        ];


        $staffId = (int) $request->staff_id;

        $query = CallingOrder::query()
            ->where(function ($q) use ($staffId) {
                $q->where('callingorder.assigned_to', $staffId)
                    ->orWhereIn('callingorder.id', function ($sub) use ($staffId) {
                        $sub->select('callingorder_id')
                            ->from('whatsapp_fresh_lead_shares')
                            ->where('shared_with_staff_id', $staffId);
                    });
            })
            ->whereBetween('callingorder.updated_at', [$from, $to]);


        if ($request->filled('order_source')) {

            if ($request->order_source === 'web') {


                $query->where(function ($q) {

                    $q->whereNull('order_source')
                        ->orWhere('order_source', '')
                        ->orWhereRaw(
                            'LOWER(TRIM(order_source)) = ?',
                            ['web']
                        );
                });
            } elseif ($request->order_source === 'whatsapp') {

                $query->whereRaw(
                    'LOWER(TRIM(order_source)) = ?',
                    ['whatsapp']
                );
            } elseif ($request->order_source === 'RTO') {

                $query->whereRaw(
                    'UPPER(TRIM(order_source)) = ?',
                    ['RTO']
                );
            } elseif ($request->order_source === 'deliveredreorder') {

                $query->whereRaw(
                    'LOWER(TRIM(order_source)) = ?',
                    ['deliveredreorder']
                );
            } elseif ($request->order_source === 'shopify_abandoned_checkout') {

                $query->whereRaw(
                    'LOWER(TRIM(order_source)) = ?',
                    ['shopify_abandoned_checkout']
                );
            }
        }

        if ($request->filled('status')) {

            if ($request->status === 'other') {

                $query->where(function ($q) use ($standardStatuses) {

                    $q->whereNotIn(
                        'status',
                        $standardStatuses
                    )
                        ->orWhereNull('status');
                });
            } else {

                $query->where(
                    'status',
                    $request->status
                );
            }
        }

        if ($request->filled('search')) {

            $search = trim(
                $request->search
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'order_id',
                    'LIKE',
                    "%{$search}%"
                )
                    ->orWhere(
                        'customer_name',
                        'LIKE',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'customer_phone',
                        'LIKE',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'product_name',
                        'LIKE',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'remarks',
                        'LIKE',
                        "%{$search}%"
                    );
            });
        }


        $totalOrders = (clone $query)
            ->count();

        $webOrders = (clone $query)
            ->where(function ($q) {

                $q->whereNull('order_source')
                    ->orWhere('order_source', '')
                    ->orWhereRaw(
                        'LOWER(TRIM(order_source)) = ?',
                        ['web']
                    );
            })
            ->count();

        $whatsappOrders = (clone $query)
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['whatsapp']
            )
            ->count();


        // RTO
        $rtoOrders = (clone $query)
            ->whereRaw(
                'UPPER(TRIM(order_source)) = ?',
                ['RTO']
            )
            ->count();
        $deliveredReorderOrders = (clone $query)
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['deliveredreorder']
            )
            ->count();
        $abandonedOrders = (clone $query)
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['shopify_abandoned_checkout']
            )
            ->count();

        $pendingDeliveredReorder = (clone $query)
            ->where('status', 'pending')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['deliveredreorder']
            )
            ->count();

        $pendingAbandoned = (clone $query)
            ->where('status', 'pending')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['shopify_abandoned_checkout']
            )
            ->count();
        $verifiedDeliveredReorder = (clone $query)
            ->where('status', 'verified')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['deliveredreorder']
            )
            ->count();
        $verifiedAbandoned = (clone $query)
            ->where('status', 'verified')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['shopify_abandoned_checkout']
            )
            ->count();

        $cancelDeliveredReorder = (clone $query)
            ->where('status', 'cancel')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['deliveredreorder']
            )
            ->count();

        $cancelAbandoned = (clone $query)
            ->where('status', 'cancel')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['shopify_abandoned_checkout']
            )
            ->count();

        $notReachableDeliveredReorder = (clone $query)
            ->where('status', 'not_reachable')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['deliveredreorder']
            )
            ->count();

        $notReachableAbandoned = (clone $query)
            ->where('status', 'not_reachable')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['shopify_abandoned_checkout']
            )
            ->count();

        $sameOrderDeliveredReorder = (clone $query)
            ->where('status', 'same_order')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['deliveredreorder']
            )
            ->count();

        $sameOrderAbandoned = (clone $query)
            ->where('status', 'same_order')
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['shopify_abandoned_checkout']
            )
            ->count();

        $otherDeliveredReorder = (clone $query)
            ->where(function ($q) use ($standardStatuses) {

                $q->whereNotIn(
                    'status',
                    $standardStatuses
                )
                    ->orWhereNull('status');
            })
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['deliveredreorder']
            )
            ->count();

        $otherAbandoned = (clone $query)
            ->where(function ($q) use ($standardStatuses) {

                $q->whereNotIn(
                    'status',
                    $standardStatuses
                )
                    ->orWhereNull('status');
            })
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['shopify_abandoned_checkout']
            )
            ->count();

        /*
    |--------------------------------------------------------------------------
    | PENDING
    |--------------------------------------------------------------------------
    */

        $pendingOrders = (clone $query)
            ->where(
                'status',
                'pending'
            )
            ->count();


        // Pending Web
        $pendingWeb = (clone $query)
            ->where(
                'status',
                'pending'
            )
            ->where(function ($q) {

                $q->whereNull('order_source')
                    ->orWhere('order_source', '')
                    ->orWhereRaw(
                        'LOWER(TRIM(order_source)) = ?',
                        ['web']
                    );
            })
            ->count();


        // Pending WhatsApp
        $pendingWhatsapp = (clone $query)
            ->where(
                'status',
                'pending'
            )
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['whatsapp']
            )
            ->count();


        // Pending RTO
        $rtoPending = (clone $query)
            ->where(
                'status',
                'pending'
            )
            ->whereRaw(
                'UPPER(TRIM(order_source)) = ?',
                ['RTO']
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | VERIFIED
    |--------------------------------------------------------------------------
    */

        $verifiedOrders = (clone $query)
            ->where(
                'status',
                'verified'
            )
            ->count();


        // Verified Web
        $verifiedWeb = (clone $query)
            ->where(
                'status',
                'verified'
            )
            ->where(function ($q) {

                $q->whereNull('order_source')
                    ->orWhere('order_source', '')
                    ->orWhereRaw(
                        'LOWER(TRIM(order_source)) = ?',
                        ['web']
                    );
            })
            ->count();


        // Verified WhatsApp
        $verifiedWhatsapp = (clone $query)
            ->where(
                'status',
                'verified'
            )
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['whatsapp']
            )
            ->count();


        // Verified RTO
        $rtoVerified = (clone $query)
            ->where(
                'status',
                'verified'
            )
            ->whereRaw(
                'UPPER(TRIM(order_source)) = ?',
                ['RTO']
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | CANCEL
    |--------------------------------------------------------------------------
    */

        $cancelOrders = (clone $query)
            ->where(
                'status',
                'cancel'
            )
            ->count();


        // Cancel Web
        $cancelWeb = (clone $query)
            ->where(
                'status',
                'cancel'
            )
            ->where(function ($q) {

                $q->whereNull('order_source')
                    ->orWhere('order_source', '')
                    ->orWhereRaw(
                        'LOWER(TRIM(order_source)) = ?',
                        ['web']
                    );
            })
            ->count();


        // Cancel WhatsApp
        $cancelWhatsapp = (clone $query)
            ->where(
                'status',
                'cancel'
            )
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['whatsapp']
            )
            ->count();


        // Cancel RTO
        $rtoCancel = (clone $query)
            ->where(
                'status',
                'cancel'
            )
            ->whereRaw(
                'UPPER(TRIM(order_source)) = ?',
                ['RTO']
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | NOT REACHABLE
    |--------------------------------------------------------------------------
    */

        $notReachableOrders = (clone $query)
            ->where(
                'status',
                'not_reachable'
            )
            ->count();


        // Not Reachable Web
        $notReachableWeb = (clone $query)
            ->where(
                'status',
                'not_reachable'
            )
            ->where(function ($q) {

                $q->whereNull('order_source')
                    ->orWhere('order_source', '')
                    ->orWhereRaw(
                        'LOWER(TRIM(order_source)) = ?',
                        ['web']
                    );
            })
            ->count();


        // Not Reachable WhatsApp
        $notReachableWhatsapp = (clone $query)
            ->where(
                'status',
                'not_reachable'
            )
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['whatsapp']
            )
            ->count();


        // Not Reachable RTO
        $rtoNotReachable = (clone $query)
            ->where(
                'status',
                'not_reachable'
            )
            ->whereRaw(
                'UPPER(TRIM(order_source)) = ?',
                ['RTO']
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | SAME ORDER
    |--------------------------------------------------------------------------
    */

        $sameOrderOrders = (clone $query)
            ->where(
                'status',
                'same_order'
            )
            ->count();


        // Same Order Web
        $sameOrderWeb = (clone $query)
            ->where(
                'status',
                'same_order'
            )
            ->where(function ($q) {

                $q->whereNull('order_source')
                    ->orWhere('order_source', '')
                    ->orWhereRaw(
                        'LOWER(TRIM(order_source)) = ?',
                        ['web']
                    );
            })
            ->count();


        // Same Order WhatsApp
        $sameOrderWhatsapp = (clone $query)
            ->where(
                'status',
                'same_order'
            )
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['whatsapp']
            )
            ->count();


        // Same Order RTO
        $rtoSameOrder = (clone $query)
            ->where(
                'status',
                'same_order'
            )
            ->whereRaw(
                'UPPER(TRIM(order_source)) = ?',
                ['RTO']
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | OTHER
    |--------------------------------------------------------------------------
    */

        $otherOrders = (clone $query)
            ->where(function ($q) use ($standardStatuses) {

                $q->whereNotIn(
                    'status',
                    $standardStatuses
                )
                    ->orWhereNull('status');
            })
            ->count();


        // Other Web
        $otherWeb = (clone $query)
            ->where(function ($q) use ($standardStatuses) {

                $q->whereNotIn(
                    'status',
                    $standardStatuses
                )
                    ->orWhereNull('status');
            })
            ->where(function ($q) {

                $q->whereNull('order_source')
                    ->orWhere('order_source', '')
                    ->orWhereRaw(
                        'LOWER(TRIM(order_source)) = ?',
                        ['web']
                    );
            })
            ->count();


        // Other WhatsApp
        $otherWhatsapp = (clone $query)
            ->where(function ($q) use ($standardStatuses) {

                $q->whereNotIn(
                    'status',
                    $standardStatuses
                )
                    ->orWhereNull('status');
            })
            ->whereRaw(
                'LOWER(TRIM(order_source)) = ?',
                ['whatsapp']
            )
            ->count();


        // Other RTO
        $rtoOther = (clone $query)
            ->where(function ($q) use ($standardStatuses) {

                $q->whereNotIn(
                    'status',
                    $standardStatuses
                )
                    ->orWhereNull('status');
            })
            ->whereRaw(
                'UPPER(TRIM(order_source)) = ?',
                ['RTO']
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | PAYMENT
    |--------------------------------------------------------------------------
    */

        $codOrders = (clone $query)
            ->whereIn(
                'payment_mode',
                ['COD', 'cod']
            )
            ->count();


        $prepaidOrders = (clone $query)
            ->whereIn(
                'payment_mode',
                [
                    'Prepaid',
                    'prepaid',
                    'PREPAID'
                ]
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | ORDERS LIST
    |--------------------------------------------------------------------------
    */

        $orders = (clone $query)
            ->with('client')
            ->addSelect([
                'call_log_id' => DB::table('calling_logs as cl')
                    ->select('cl.id')
                    ->whereColumn('cl.order_id', 'callingorder.order_id')
                    ->whereColumn('cl.staff_id', 'callingorder.assigned_to')
                    ->orderByDesc('cl.id')
                    ->limit(1),

                'call_started_at' => DB::table('calling_logs as cl')
                    ->select('cl.call_started_at')
                    ->whereColumn('cl.order_id', 'callingorder.order_id')
                    ->whereColumn('cl.staff_id', 'callingorder.assigned_to')
                    ->orderByDesc('cl.id')
                    ->limit(1),

                'call_ended_at' => DB::table('calling_logs as cl')
                    ->select('cl.call_ended_at')
                    ->whereColumn('cl.order_id', 'callingorder.order_id')
                    ->whereColumn('cl.staff_id', 'callingorder.assigned_to')
                    ->orderByDesc('cl.id')
                    ->limit(1),

                'call_duration_seconds' => DB::table('calling_logs as cl')
                    ->select('cl.duration_seconds')
                    ->whereColumn('cl.order_id', 'callingorder.order_id')
                    ->whereColumn('cl.staff_id', 'callingorder.assigned_to')
                    ->orderByDesc('cl.id')
                    ->limit(1),

                'call_recording_id' => DB::table('calling_logs as cl')
                    ->select('cl.id')
                    ->whereColumn('cl.order_id', 'callingorder.order_id')
                    ->whereColumn('cl.staff_id', 'callingorder.assigned_to')
                    ->where('cl.recording_status', 'uploaded')
                    ->whereNotNull('cl.recording_path')
                    ->where('cl.recording_path', '!=', '')
                    ->orderByDesc('cl.id')
                    ->limit(1),
            ])
            ->orderByDesc('updated_at')
            ->paginate(100)
            ->withQueryString();

        $existingOrderIds = DB::table('orders')
            ->whereNotNull('order_id')
            ->pluck('order_id')
            ->map(function ($id) {
                return (string) $id;
            })
            ->toArray();

        /*
    |--------------------------------------------------------------------------
    | VIEW
    |--------------------------------------------------------------------------
    */

        return view(
            'performance-orders',
            compact(

                // Delivered Re-Order
                'deliveredReorderOrders',
                'pendingDeliveredReorder',
                'verifiedDeliveredReorder',
                'cancelDeliveredReorder',
                'notReachableDeliveredReorder',
                'sameOrderDeliveredReorder',
                'otherDeliveredReorder',
                'existingOrderIds',
                // Abandoned Checkout
                'abandonedOrders',
                'pendingAbandoned',
                'verifiedAbandoned',
                'cancelAbandoned',
                'notReachableAbandoned',
                'sameOrderAbandoned',
                'otherAbandoned',

                // Orders
                'orders',
                'staff',
                'from',
                'to',

                // Total
                'totalOrders',

                // Sources
                'webOrders',
                'whatsappOrders',
                'rtoOrders',

                // Pending
                'pendingOrders',
                'pendingWeb',
                'pendingWhatsapp',
                'rtoPending',

                // Verified
                'verifiedOrders',
                'verifiedWeb',
                'verifiedWhatsapp',
                'rtoVerified',

                // Cancel
                'cancelOrders',
                'cancelWeb',
                'cancelWhatsapp',
                'rtoCancel',

                // Not Reachable
                'notReachableOrders',
                'notReachableWeb',
                'notReachableWhatsapp',
                'rtoNotReachable',

                // Same Order
                'sameOrderOrders',
                'sameOrderWeb',
                'sameOrderWhatsapp',
                'rtoSameOrder',

                // Other
                'otherOrders',
                'otherWeb',
                'otherWhatsapp',
                'rtoOther',

                // Payment
                'codOrders',
                'prepaidOrders'
            )
        );
    }
    public function play($id)
    {
        $log = DB::table('calling_logs')
            ->where('id', $id)
            ->where('recording_status', 'uploaded')
            ->first();

        abort_unless($log && $log->recording_path, 404);

        $path = $log->recording_path;

        abort_unless(Storage::disk('local')->exists($path), 404);

        return response()->file(
            Storage::disk('local')->path($path),
            [
                'Content-Type' => 'audio/mpeg',
                'Content-Disposition' => 'inline',
                'Accept-Ranges' => 'bytes',
            ]
        );
    }
    public function exportOrderDetails(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:calling_users,id',
        ]);

        $staff = CallingUser::findOrFail(
            $request->staff_id
        );


        $fileName =
            'Performance-'
            . str_replace(
                ' ',
                '-',
                $staff->name
            )
            . '-'
            . now()->format('d-m-Y-H-i-s')
            . '.xlsx';


        return Excel::download(
            new StaffPerformanceOrdersExport($request),
            $fileName
        );
    }

    private function generateOrderId($staffId)
    {
        $staff = CallingUser::findOrFail($staffId);

        $name = trim($staff->name);

        if ($name === '') {
            throw new \Exception('Staff name is empty.');
        }

        $staffCode =
            mb_substr($name, 0, 1) .
            mb_substr($name, -1, 1);

        $date = now()->format('d-m-y');

        $prefix = $staffCode . '-' . $date . '-';

        // Saare today's IDs nikalo
        $existingOrders = CallingOrder::where(
            'order_id',
            'like',
            $prefix . '%'
        )->pluck('order_id');

        $maxNumber = 0;

        foreach ($existingOrders as $orderId) {

            $number = (int) substr(
                $orderId,
                strrpos($orderId, '-') + 1
            );

            if ($number > $maxNumber) {
                $maxNumber = $number;
            }
        }

        $nextNumber = $maxNumber + 1;

        // Final duplicate safety
        do {
            $newOrderId = $prefix . $nextNumber;
            $nextNumber++;
        } while (
            CallingOrder::where('order_id', $newOrderId)->exists()
        );

        return $newOrderId;
    }

    public function shiftOrders(Request $request)
    {
        if ($this->isClient()) {
            abort(403, 'Unauthorized Access');
        }


        $request->validate([

            'from_staff'       => 'required',

            'to_staff'         => 'required|different:from_staff',

            'filter_from'      => 'required|date',

            'filter_to'        => 'required|date',

            'order_source'     => 'required',

            'shift_type'       => 'required|in:same,fresh',

            'remark'           => 'required|string',

        ]);


        if ($request->shift_type === 'fresh') {

            $request->validate([
                'new_order_source' => 'required',
            ]);
        }


        /*
    |--------------------------------------------------------------------------
    | DATE RANGE
    |--------------------------------------------------------------------------
    */

        $from =
            Carbon::parse(
                $request->filter_from
            )->startOfDay();


        $to =
            Carbon::parse(
                $request->filter_to
            )->endOfDay();


        /*
    |--------------------------------------------------------------------------
    | PENDING ORDERS
    |--------------------------------------------------------------------------
    */

        $query = CallingOrder::where(
            'assigned_to',
            $request->from_staff
        )
            ->where(
                'status',
                'pending'
            )
            ->whereBetween(
                'created_at',
                [$from, $to]
            );


        /*
    |--------------------------------------------------------------------------
    | SOURCE FILTER
    |--------------------------------------------------------------------------
    */

        if ($request->order_source !== 'all') {

            if (
                $request->order_source === '__NULL__'
            ) {

                // Web
                $query->whereNull(
                    'order_source'
                );
            } else {

                $query->where(
                    'order_source',
                    $request->order_source
                );
            }
        }


        $orders =
            $query->orderBy('id')->get();


        if ($orders->isEmpty()) {

            return back()->with(
                'error',
                'Selected date/source ke koi pending orders nahi mile.'
            );
        }


        DB::beginTransaction();


        try {

            $shiftedCount = 0;


            foreach ($orders as $order) {

                /*
            |--------------------------------------------------------------------------
            | OLD DATA
            |--------------------------------------------------------------------------
            */

                $oldStaff =
                    $order->assigned_to;


                $oldOrderId =
                    $order->order_id;


                $oldOrderSource =
                    $order->order_source;


                $oldCreatedAt =
                    $order->created_at;


                /*
            |--------------------------------------------------------------------------
            | DEFAULT NEW DATA
            |--------------------------------------------------------------------------
            */

                $newOrderId =
                    $oldOrderId;


                $newOrderSource =
                    $oldOrderSource;


                /*
            |--------------------------------------------------------------------------
            | CHANGE STAFF
            |--------------------------------------------------------------------------
            */

                $order->assigned_to =
                    $request->to_staff;


                /*
            |--------------------------------------------------------------------------
            | FRESH LEAD
            |--------------------------------------------------------------------------
            */

                if (
                    $request->shift_type === 'fresh'
                ) {

                    /*
                | Target staff ke naam + TODAY
                */

                    $newOrderId =
                        $this->generateOrderId(
                            $request->to_staff
                        );


                    $order->order_id =
                        $newOrderId;


                    /*
                | NEW SOURCE
                */

                    if (
                        $request->new_order_source
                        === '__NULL__'
                    ) {

                        $order->order_source =
                            null;

                        $newOrderSource =
                            'Web';
                    } else {

                        $order->order_source =
                            $request->new_order_source;

                        $newOrderSource =
                            $request->new_order_source;
                    }


                    /*
                | Today's date
                */

                    $order->created_at =
                        now();
                }


                /*
            |--------------------------------------------------------------------------
            | SAVE
            |--------------------------------------------------------------------------
            */

                $order->save();


                /*
            |--------------------------------------------------------------------------
            | LOG
            |--------------------------------------------------------------------------
            */

                DB::table(
                    'order_shift_logs'
                )->insert([

                    'order_id' =>
                    $order->id,

                    'from_staff' =>
                    $oldStaff,

                    'to_staff' =>
                    $request->to_staff,

                    'old_order_id' =>
                    $oldOrderId,

                    'new_order_id' =>
                    $newOrderId,

                    'old_order_source' =>
                    $oldOrderSource,

                    'new_order_source' =>
                    $newOrderSource,

                    'shift_type' =>
                    $request->shift_type,

                    'old_created_at' =>
                    $oldCreatedAt,

                    'remark' =>
                    $request->remark,

                    'created_at' =>
                    now(),

                    'updated_at' =>
                    now(),

                ]);


                $shiftedCount++;
            }


            DB::commit();


            return back()->with(
                'success',
                $shiftedCount .
                    ' orders shifted successfully.'
            );
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('ORDER SHIFT FAILED', [
                'from_staff'       => $request->from_staff,
                'to_staff'         => $request->to_staff,
                'filter_from'      => $request->filter_from,
                'filter_to'        => $request->filter_to,
                'order_source'     => $request->order_source,
                'shift_type'       => $request->shift_type,
                'new_order_source' => $request->new_order_source,
                'remark'           => $request->remark,
                'error'            => $e->getMessage(),
                'file'             => $e->getFile(),
                'line'             => $e->getLine(),
                'user_id'          => auth()->id(),
                'created_at'       => now(),
            ]);

            return back()->with(
                'error',
                'Order shift failed: ' . $e->getMessage()
            );
        }
    }
    public function shiftOrderSources(Request $request, $staffId)
    {
        if ($this->isClient()) {
            abort(403, 'Unauthorized Access');
        }

        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date',
        ]);

        $from = \Carbon\Carbon::parse($request->from)->startOfDay();
        $to   = \Carbon\Carbon::parse($request->to)->endOfDay();

        $baseQuery = CallingOrder::where('assigned_to', $staffId)
            ->where('status', 'pending')
            ->whereBetween('created_at', [$from, $to]);

        $allCount = (clone $baseQuery)->count();

        $sourceList = [
            'whatsapp' => 'WhatsApp',
            'deliveredreorder' => 'Delivered Re-Order',
            'shopify_abandoned_checkout' => 'Abandoned Checkout',
            'RTO' => 'RTO',
        ];

        $sources = [];

        foreach ($sourceList as $dbValue => $displayName) {

            $count = (clone $baseQuery)
                ->where('order_source', $dbValue)
                ->count();

            $sources[] = [
                'order_source' => $dbValue,
                'name' => $displayName,
                'total' => $count,
            ];
        }

        // NULL = Web
        $webCount = (clone $baseQuery)
            ->whereNull('order_source')
            ->count();

        $sources[] = [
            'order_source' => '__NULL__',
            'name' => 'Web',
            'total' => $webCount,
        ];

        return response()->json([
            'success' => true,
            'all' => $allCount,
            'sources' => $sources,
        ]);
    }




    /**
     * WhatsApp Fresh Leads
     * Shows original leads plus leads shared with the selected staff member.
     */

    public function whatsappFreshLeads(Request $request, $clientId = null)
    {
        // Client login can only access its own data.
        if ($this->isClient()) {
            $clientId = (int) $this->clientId();
        } else {
            $clientId = (int) ($clientId ?: $request->input('client_id'));
        }

        abort_unless($clientId > 0, 422, 'Please select a client.');

        if ($this->isClient() && $clientId !== (int) $this->clientId()) {
            abort(403, 'Unauthorized Access');
        }

        $client = Client::findOrFail($clientId);

        $staffId = $request->filled('staff_id')
            ? (int) $request->staff_id
            : null;

        // Today's date range only.
        $from = Carbon::today()->startOfDay();
        $to   = Carbon::tomorrow()->startOfDay();

        // Only WhatsApp fresh leads.
        $query = DB::table('callingorder')
            ->where('callingorder.client_id', $clientId)
            ->where('callingorder.order_date', '>=', $from)
            ->where('callingorder.order_date', '<', $to)
            ->whereRaw(
                "LOWER(TRIM(COALESCE(callingorder.order_source, ''))) = ?",
                ['whatsapp']
            )
            ->whereIn(
                DB::raw('LOWER(TRIM(callingorder.status))'),
                ['pending', 'not_reachable', 'not reachable', 'not-reachable']
            )
            ->where(function ($q) {
                $q->whereNull('callingorder.remarks')
                    ->orWhere('callingorder.remarks', '<>', 'Selloship Import');
            });

        // Include original leads and leads shared with the selected staff.
        if ($staffId) {
            $query->where(function ($q) use ($staffId, $clientId) {
                $q->where('callingorder.assigned_to', $staffId)
                    ->orWhereExists(function ($sub) use ($staffId, $clientId) {
                        $sub->selectRaw('1')
                            ->from('whatsapp_fresh_lead_shares as shares')
                            ->whereColumn(
                                'shares.callingorder_id',
                                'callingorder.id'
                            )
                            ->where('shares.client_id', $clientId)
                            ->where('shares.shared_with_staff_id', $staffId);
                    });
            });
        }

        $leads = $query
            ->select('callingorder.*')
            ->orderByDesc('callingorder.order_date')
            ->get();

        // Load staff names once instead of querying for every row.
        $staffNames = CallingUser::pluck('name', 'id');

        $leads->transform(function ($lead) use ($staffNames, $clientId) {
            $lead->assigned_staff_name =
                $staffNames[$lead->assigned_to ?? 0] ?? 'Unassigned';

            $lead->shared_staff_names = DB::table('whatsapp_fresh_lead_shares as shares')
                ->join(
                    'calling_users',
                    'calling_users.id',
                    '=',
                    'shares.shared_with_staff_id'
                )
                ->where('shares.client_id', $clientId)
                ->where('shares.callingorder_id', $lead->id)
                ->pluck('calling_users.name')
                ->toArray();

            return $lead;
        });

        // Active staff.
        $staff = CallingUser::where('status', 1)
            ->orderBy('name')
            ->get();

        return view('whatsapp-fresh-leads', compact(
            'client',
            'clientId',
            'staffId',
            'staff',
            'leads',
            'from',
            'to'
        ));
    }



    public function assignWhatsappFreshLeads(Request $request)
    {
        $request->validate([
            'client_id'  => 'required|integer|exists:clients,id',
            'staff_id'   => 'required|integer|exists:calling_users,id',
            'lead_ids'   => 'required|array|min:1',
            'lead_ids.*' => 'required|integer|distinct',
        ]);

        $clientId = (int) $request->client_id;
        $staffId  = (int) $request->staff_id;

        // Client access validation
        if (
            $this->isClient() &&
            $clientId !== (int) $this->clientId()
        ) {
            abort(403, 'Unauthorized Access');
        }

        // Validate receiving staff
        $targetStaff = CallingUser::where('status', 1)
            ->findOrFail($staffId);

        // Generate receiving staff prefix
        $nameParts = preg_split(
            '/\s+/',
            trim($targetStaff->name),
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        $prefix = count($nameParts) >= 2
            ? strtoupper(
                mb_substr($nameParts[0], 0, 1) .
                    mb_substr($nameParts[1], 0, 1)
            )
            : strtoupper(
                mb_substr($nameParts[0] ?? 'ST', 0, 2)
            );

        $orderPrefix = $prefix . '-' . now()->format('d-m-y') . '-';

        // Last 3 calendar days: today and previous 2 days
        $from = Carbon::today()->subDays(2)->startOfDay();
        $to   = Carbon::today()->endOfDay();

        $sharedCount = DB::transaction(function () use (
            $request,
            $clientId,
            $staffId,
            $from,
            $to,
            $orderPrefix
        ) {
            // Existing shared order IDs for this staff and date prefix
            $existingIds = DB::table('whatsapp_fresh_lead_shares')
                ->where('shared_with_staff_id', $staffId)
                ->where('shared_order_id', 'like', $orderPrefix . '%')
                ->lockForUpdate()
                ->pluck('shared_order_id');

            // Existing original order IDs with the same prefix
            $originalIds = DB::table('callingorder')
                ->where('order_id', 'like', $orderPrefix . '%')
                ->lockForUpdate()
                ->pluck('order_id');

            $maxSerial = 0;

            foreach ($existingIds->merge($originalIds) as $id) {
                if (preg_match('/-(\d+)$/', (string) $id, $matches)) {
                    $maxSerial = max($maxSerial, (int) $matches[1]);
                }
            }

            $serial = $maxSerial + 1;
            $count  = 0;

            foreach ($request->lead_ids as $leadId) {

                // Lock and validate the original lead
                $lead = DB::table('callingorder')
                    ->where('id', (int) $leadId)
                    ->where('client_id', $clientId)
                    ->whereRaw(
                        "LOWER(TRIM(COALESCE(order_source, ''))) = ?",
                        ['whatsapp']
                    )
                    ->whereBetween('order_date', [$from, $to])
                    ->whereIn(
                        DB::raw('LOWER(TRIM(status))'),
                        [
                            'pending',
                            'not_reachable',
                            'not reachable',
                            'not-reachable',
                        ]
                    )
                    ->where(function ($query) {
                        $query->whereNull('remarks')
                            ->orWhere('remarks', '<>', 'Selloship Import');
                    })
                    ->lockForUpdate()
                    ->first();

                if (!$lead) {
                    continue;
                }

                // Do not share a lead with its current assigned staff member
                if ((int) $lead->assigned_to === $staffId) {
                    continue;
                }

                // Prevent sharing the same lead with the same staff member twice
                $alreadyShared = DB::table('whatsapp_fresh_lead_shares')
                    ->where('client_id', $clientId)
                    ->where('callingorder_id', $lead->id)
                    ->where('shared_with_staff_id', $staffId)
                    ->exists();

                if ($alreadyShared) {
                    continue;
                }

                // Create the share record
                DB::table('whatsapp_fresh_lead_shares')->insert([
                    'client_id'            => $clientId,
                    'callingorder_id'      => $lead->id,
                    'original_staff_id'    => $lead->assigned_to,
                    'shared_with_staff_id' => $staffId,
                    'shared_order_id'      => $orderPrefix . $serial,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ]);

                // Reset status and remarks for the shared lead.
                // Keep assigned_to unchanged.
                DB::table('callingorder')
                    ->where('id', $lead->id)
                    ->update([
                        'status'     => 'pending',
                        'remarks'    => '',
                        'updated_at' => now(),
                    ]);

                $serial++;
                $count++;
            }

            return $count;
        });

        return redirect()
            ->route('whatsapp.fresh-leads.index', [
                'clientId' => $clientId,
            ])
            ->with(
                'success',
                "{$sharedCount} lead(s) shared with {$targetStaff->name}."
            );
    }


    /**
     * Update the existing callingorder row.
     * A status change is automatically visible to both original and shared staff.
     */
    public function updateWhatsappFreshLeadStatus(Request $request)
    {
        $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'lead_id'   => 'required|integer|exists:callingorder,id',
            'status'    => 'required|in:pending,verified,cancel,not_reachable,same_order',
        ]);

        $clientId = (int) $request->client_id;
        $leadId   = (int) $request->lead_id;

        if ($this->isClient() && $clientId !== (int) $this->clientId()) {
            abort(403, 'Unauthorized Access');
        }

        $staffId = (int) $request->input('staff_id', 0);

        DB::transaction(function () use ($clientId, $leadId, $staffId, $request) {
            $lead = DB::table('callingorder')
                ->where('id', $leadId)
                ->where('client_id', $clientId)
                ->lockForUpdate()
                ->first();

            abort_unless($lead, 404, 'Lead not found.');

            // If a staff ID is provided, make sure that staff owns or shares this lead.
            if ($staffId > 0) {
                $isOriginalStaff = (int) ($lead->assigned_to ?? 0) === $staffId;

                $isSharedStaff = DB::table('whatsapp_fresh_lead_shares')
                    ->where('client_id', $clientId)
                    ->where('callingorder_id', $leadId)
                    ->where('shared_with_staff_id', $staffId)
                    ->exists();

                abort_unless(
                    $isOriginalStaff || $isSharedStaff,
                    403,
                    'This lead is not assigned or shared with this staff member.'
                );
            }

            // If another staff member already completed the lead, do not overwrite it.
            $currentStatus = strtolower(trim((string) $lead->status));

            if (in_array($currentStatus, ['verified', 'cancel', 'same_order'], true)) {
                return;
            }

            DB::table('callingorder')
                ->where('id', $leadId)
                ->where('client_id', $clientId)
                ->update([
                    'status'     => $request->status,
                    'updated_at' => now(),
                ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Lead status updated. The change applies to both staff members.',
        ]);
    }

    public function clientsorders()
    {
        if ($this->isClient()) {

            $clients = Client::where('id', $this->clientId())
                ->orderBy('client_name')
                ->get();
        } else {

            $clients = Client::orderBy('client_name')->get();
        }

        return view('clientsorders', compact('clients'));
    }

    public function ordersdashboard($client_id = null)
    {
        $from = Carbon::yesterday()->startOfDay();
        $to   = Carbon::now();


        $getRepeatPending = function ($clientId) {

            $repeatDays = ((int) $clientId === 2) ? 20 : 45;

            $cutoffDate = now()
                ->subDays($repeatDays)
                ->toDateString();

            return DB::table('orders')
                ->where('orders.client_id', $clientId)
                ->whereRaw(
                    'LOWER(TRIM(orders.delivery_status)) = ?',
                    ['delivered']
                )
                ->whereNotNull('orders.delivery_date')
                ->where('orders.delivery_date', '<=', $cutoffDate)
                ->whereNotNull('orders.customer_phone_last10')
                ->where('orders.customer_phone_last10', '<>', '')

                ->whereNotExists(function ($q) {

                    $q->selectRaw('1')
                        ->from('callingorder')
                        ->whereColumn(
                            'callingorder.client_id',
                            'orders.client_id'
                        )
                        ->where(
                            'callingorder.order_source',
                            'deliveredreorder'
                        )
                        ->whereColumn(
                            'callingorder.customer_phone_last10',
                            'orders.customer_phone_last10'
                        )
                        ->where(function ($subQuery) {

                            // Assigned repeat customer: exclude from pending
                            $subQuery->whereNotNull(
                                'callingorder.assigned_to'
                            )

                                // Also exclude a reorder placed after delivery
                                ->orWhereColumn(
                                    'callingorder.order_date',
                                    '>=',
                                    'orders.delivery_date'
                                );
                        });
                })

                ->selectRaw(
                    'COUNT(DISTINCT orders.customer_phone_last10) AS total'
                )
                ->value('total');
        };


        if ($this->isClient()) {

            $clientId = $this->clientId();

            $ordersData = Client::where(
                'id',
                $clientId
            )

                ->withCount([

                    'orders as total_orders' => function ($q) use ($from, $to) {

                        $q->whereNull('assigned_to')
                            ->where('status', 'pending')
                            ->whereBetween(
                                'order_date',
                                [$from, $to]
                            );
                    },

                    'callingOrders as total_abandoned_orders' => function ($q) {

                        $q->whereNull('assigned_to')
                            ->where('status', 'pending')
                            ->where(
                                'order_source',
                                'shopify_abandoned_checkout'
                            );
                    },

                    'callingOrders as selloship_records' => function ($q) {

                        $q->whereNull('assigned_to')
                            ->where('status', 'pending')
                            ->where(
                                'order_source',
                                'deliveredreorder'
                            )
                            ->where(
                                'remarks',
                                'Selloship Import'
                            );
                    },

                ])

                ->get()

                ->map(function ($client) use ($getRepeatPending) {

                    $rtoPending = DB::table('rto_reports')

                        ->join(
                            'orders',
                            'orders.order_id',
                            '=',
                            'rto_reports.order_id'
                        )

                        ->where(
                            'orders.client_id',
                            $client->id
                        )

                        ->where(
                            'rto_reports.is_exported',
                            0
                        )

                        ->count();

                    // $repeatPending = 0;
                    $repeatPending = $getRepeatPending($client->id);

                    return [

                        'client_name' =>
                        $client->client_name,

                        'client_id' =>
                        $client->id,

                        'total_orders' =>
                        (int) $client->total_orders,

                        'total_abandoned_orders' =>
                        (int) $client->total_abandoned_orders,

                        'selloship_records' =>
                        (int) $client->selloship_records,

                        'rto_pending' =>
                        (int) $rtoPending,

                        'repeat_pending' =>
                        (int) $repeatPending,
                    ];
                });

            $waClients = Conversation::select(
                'client_id',
                DB::raw('COUNT(*) as total')
            )

                ->where(
                    'client_id',
                    $clientId
                )

                // ->whereBetween('updated_at',[$from, $to])
                ->where('updated_at', '>=', Carbon::today()->startOfDay())
                ->where('updated_at', '<', Carbon::tomorrow()->startOfDay())
                ->groupBy(
                    'client_id'
                )

                ->with(
                    'client'
                )

                ->get();

            //  $freshFrom = Carbon::today()->subDays(2)->startOfDay();
            // $freshTo   = Carbon::today()->endOfDay();
            $freshFrom = Carbon::today()->startOfDay();
            $freshTo   = Carbon::tomorrow()->startOfDay();

            $ordersData = $ordersData->map(function ($data) use ($freshFrom, $freshTo) {
                $data['fresh_leads'] = DB::table('callingorder')
                    ->where('callingorder.client_id', $data['client_id'])
                    ->whereBetween('callingorder.order_date', [$freshFrom, $freshTo])
                    ->whereIn(
                        DB::raw('LOWER(TRIM(callingorder.status))'),
                        ['pending', 'not_reachable', 'not reachable', 'not-reachable']
                    )
                    ->where(function ($q) {
                        $q->whereNull('callingorder.remarks')
                            ->orWhere('callingorder.remarks', '<>', 'Selloship Import');
                    })
                    ->count();

                return $data;
            });

            return view(
                'ordersdashboard',
                [

                    'ordersData' =>
                    $ordersData,

                    'waClients' =>
                    $waClients,

                    'staff' =>
                    collect(),

                    'allStaff' =>
                    CallingUser::where(
                        'status',
                        1
                    )->get(),
                ]
            );
        }

        $query = Client::query();

        if ($client_id) {

            $query->where(
                'id',
                $client_id
            );
        }

        $ordersData = $query

            ->withCount([

                'orders as total_orders' => function ($q) use ($from, $to) {

                    $q->whereNull('assigned_to')
                        ->where('status', 'pending')
                        ->whereBetween(
                            'order_date',
                            [$from, $to]
                        );
                },

                'callingOrders as total_abandoned_orders' => function ($q) {

                    $q->whereNull('assigned_to')
                        ->where('status', 'pending')
                        ->where(
                            'order_source',
                            'shopify_abandoned_checkout'
                        );
                },

                'callingOrders as selloship_records' => function ($q) {

                    $q->whereNull('assigned_to')
                        ->where('status', 'pending')
                        ->where(
                            'order_source',
                            'deliveredreorder'
                        )
                        ->where(
                            'remarks',
                            'Selloship Import'
                        );
                },

            ])

            ->get()

            ->map(function ($client) use ($getRepeatPending) {

                $rtoPending = DB::table('rto_reports')

                    ->join(
                        'orders',
                        'orders.order_id',
                        '=',
                        'rto_reports.order_id'
                    )

                    ->where(
                        'orders.client_id',
                        $client->id
                    )

                    ->where(
                        'rto_reports.is_exported',
                        0
                    )

                    ->count();

                // $repeatPending = 0;

                $repeatPending = $getRepeatPending($client->id);
                return [

                    'client_name' =>
                    $client->client_name,

                    'client_id' =>
                    $client->id,

                    'total_orders' =>
                    (int) $client->total_orders,

                    'total_abandoned_orders' =>
                    (int) $client->total_abandoned_orders,

                    'selloship_records' =>
                    (int) $client->selloship_records,

                    'rto_pending' =>
                    (int) $rtoPending,

                    'repeat_pending' =>
                    (int) $repeatPending,
                ];
            });

        $waClients = Conversation::select(
            'client_id',
            DB::raw('COUNT(*) as total')
        )

            ->when(
                $client_id,
                function ($q) use ($client_id) {

                    $q->where(
                        'client_id',
                        $client_id
                    );
                }
            )

            ->whereBetween(
                'updated_at',
                [$from, $to]
            )

            ->groupBy(
                'client_id'
            )

            ->with(
                'client'
            )

            ->get();

        $staff = Conversation::select(
            'assigned_to',
            DB::raw('COUNT(*) as total')
        )

            ->whereBetween(
                'updated_at',
                [$from, $to]
            )

            ->groupBy(
                'assigned_to'
            )

            ->with(
                'staff'
            )

            ->get();
        $freshFrom = Carbon::today()->startOfDay();
        $freshTo   = Carbon::tomorrow()->startOfDay();

        $ordersData = $ordersData->map(function ($data) use ($freshFrom, $freshTo) {
            $data['fresh_leads'] = DB::table('callingorder')
                ->where('callingorder.client_id', $data['client_id'])
                ->where('callingorder.order_date', '>=', $freshFrom)
                ->where('callingorder.order_date', '<', $freshTo)
                ->whereRaw(
                    "LOWER(TRIM(COALESCE(callingorder.order_source, ''))) = ?",
                    ['whatsapp']
                )
                ->whereIn(
                    DB::raw('LOWER(TRIM(callingorder.status))'),
                    ['pending', 'not_reachable', 'not reachable', 'not-reachable']
                )
                ->where(function ($q) {
                    $q->whereNull('callingorder.remarks')
                        ->orWhere('callingorder.remarks', '<>', 'Selloship Import');
                })
                ->count();

            return $data;
        });
        return view(
            'ordersdashboard',
            [

                'ordersData' =>
                $ordersData,

                'waClients' =>
                $waClients,

                'staff' =>
                $staff,

                'allStaff' =>
                CallingUser::all(),
            ]
        );
    }
    public function staffRecordingReport(Request $request)
    {
        $staffList = DB::table('calling_users')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $recordings = collect();

        // Default page par recordings load nahi hongi
        if ($request->filled('from') && $request->filled('to')) {

            $request->validate([
                'staff_id' => 'nullable|integer',
                'from'     => 'required|date',
                'to'       => 'required|date|after_or_equal:from',
            ]);

            $from = Carbon::parse($request->from)->startOfDay();
            $to   = Carbon::parse($request->to)->endOfDay();

            $query = DB::table('staff_call_recordings as r')
                ->leftJoin('calling_users as s', 's.id', '=', 'r.staff_id')
                ->select(
                    'r.*',
                    's.name as staff_name'
                )
                ->whereBetween('r.created_at', [$from, $to]);

            if ($request->filled('staff_id')) {
                $query->where('r.staff_id', $request->staff_id);
            }

            // Client access restriction
            if ($this->isClient()) {
                $clientId = $this->clientId();

                $query->whereExists(function ($q) use ($clientId) {
                    $q->select(DB::raw(1))
                        ->from('callingorder as co')
                        ->whereColumn('co.assigned_to', 'r.staff_id')
                        ->where('co.client_id', $clientId);
                });

                $staffList = $staffList->filter(function ($staff) use ($clientId) {
                    return DB::table('callingorder')
                        ->where('assigned_to', $staff->id)
                        ->where('client_id', $clientId)
                        ->exists();
                })->values();
            }

            $recordings = $query
                ->orderByDesc('r.created_at')
                ->paginate(50)
                ->appends($request->query());
        }

        return view('staff-recording-report', compact(
            'staffList',
            'recordings'
        ));
    }
    public function assignSelloshipOrders(Request $request)
    {
        $clientId = (int) $request->client_id;
        $assignments = $request->input('assign', []);

        if (!$clientId) {
            return response()->json([
                'success' => false,
                'message' => 'Client ID is required.'
            ], 422);
        }

        if (!is_array($assignments)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid staff assignment data.'
            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | Clean Assignments
    |--------------------------------------------------------------------------
    */

        $cleanAssignments = [];
        $totalRequested = 0;

        foreach ($assignments as $staffId => $quantity) {

            $staffId = (int) $staffId;
            $quantity = (int) $quantity;

            if ($staffId <= 0 || $quantity <= 0) {
                continue;
            }

            $cleanAssignments[$staffId] = $quantity;
            $totalRequested += $quantity;
        }

        if ($totalRequested <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Enter quantity for at least one staff.'
            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | Get Staff
    |--------------------------------------------------------------------------
    */

        $staffMembers = CallingUser::whereIn(
            'id',
            array_keys($cleanAssignments)
        )->get()->keyBy('id');

        if ($staffMembers->count() !== count($cleanAssignments)) {
            return response()->json([
                'success' => false,
                'message' => 'One or more selected staff members were not found.'
            ], 422);
        }

        /*
    |--------------------------------------------------------------------------
    | Date & MySQL Advisory Lock
    |--------------------------------------------------------------------------
    */

        $date = now()->format('d-m-y');

        // Global lock because order_id has a UNIQUE index across the table.
        $lockName = 'selloship_orderid_' . $date;

        $lockAcquired = false;

        try {

            // Prevent simultaneous assignments generating the same Order ID.
            $lockResult = DB::selectOne(
                'SELECT GET_LOCK(?, 15) AS acquired',
                [$lockName]
            );

            if (!$lockResult || (int) $lockResult->acquired !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Another assignment is in progress. Please try again.'
                ], 409);
            }

            $lockAcquired = true;

            /*
        |--------------------------------------------------------------------------
        | Start Transaction
        |--------------------------------------------------------------------------
        */

            DB::beginTransaction();

            /*
        |--------------------------------------------------------------------------
        | Get Available Selloship Records
        |--------------------------------------------------------------------------
        */

            $orders = CallingOrder::where('client_id', $clientId)
                ->whereNull('assigned_to')
                ->where('status', 'pending')
                ->where('order_source', 'deliveredreorder')
                ->where('remarks', 'Selloship Import')
                ->orderBy('id')
                ->limit($totalRequested)
                ->lockForUpdate()
                ->get();

            $availableCount = $orders->count();

            if ($availableCount < $totalRequested) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => "Only {$availableCount} Selloship records are available, but {$totalRequested} were requested."
                ], 422);
            }

            /*
        |--------------------------------------------------------------------------
        | Serial Counters
        |--------------------------------------------------------------------------
        */

            $serialCounters = [];

            $orderIndex = 0;
            $assignedTotal = 0;

            /*
        |--------------------------------------------------------------------------
        | Assign Orders Staff Wise
        |--------------------------------------------------------------------------
        */

            foreach ($cleanAssignments as $staffId => $quantity) {

                $staff = $staffMembers->get($staffId);

                $name = trim($staff->name);

                $nameParts = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY);

                // Generate Staff Prefix
                if (count($nameParts) >= 2) {

                    $prefix = strtoupper(
                        substr($nameParts[0], 0, 1) .
                            substr(end($nameParts), 0, 1)
                    );
                } else {

                    $prefix = strtoupper(substr($name, 0, 2));
                }

                if ($prefix === '') {
                    $prefix = 'ST';
                }

                $serialKey = $prefix . '-' . $date;

                /*
            |--------------------------------------------------------------------------
            | Get Last Serial Once Per Staff Prefix
            |--------------------------------------------------------------------------
            */

                if (!isset($serialCounters[$serialKey])) {

                    $existingOrderIds = CallingOrder::where(
                        'order_id',
                        'like',
                        $serialKey . '-%'
                    )->pluck('order_id');

                    $lastSerial = 0;

                    foreach ($existingOrderIds as $existingOrderId) {

                        // Extract serial after the complete prefix and date.
                        $serialPart = substr(
                            $existingOrderId,
                            strlen($serialKey) + 1
                        );

                        if (ctype_digit($serialPart)) {
                            $lastSerial = max(
                                $lastSerial,
                                (int) $serialPart
                            );
                        }
                    }

                    $serialCounters[$serialKey] = $lastSerial;
                }

                /*
            |--------------------------------------------------------------------------
            | Assign Individual Records
            |--------------------------------------------------------------------------
            */

                for ($i = 0; $i < $quantity; $i++) {

                    if (!isset($orders[$orderIndex])) {
                        break;
                    }

                    $callingOrder = $orders[$orderIndex];

                    // Generate next serial number.
                    do {

                        $serialCounters[$serialKey]++;

                        $newOrderId = $serialKey . '-' .
                            $serialCounters[$serialKey];
                    } while (
                        CallingOrder::where(
                            'order_id',
                            $newOrderId
                        )->exists()
                    );

                    // Update record.
                    $callingOrder->assigned_to = $staff->id;
                    $callingOrder->order_id = $newOrderId;
                    $callingOrder->status = 'pending';

                    $callingOrder->save();

                    $orderIndex++;
                    $assignedTotal++;
                }
            }

            /*
        |--------------------------------------------------------------------------
        | Commit Transaction
        |--------------------------------------------------------------------------
        */

            DB::commit();

            /*
        |--------------------------------------------------------------------------
        | Remaining Selloship Orders
        |--------------------------------------------------------------------------
        */

            $remaining = CallingOrder::where('client_id', $clientId)
                ->whereNull('assigned_to')
                ->where('status', 'pending')
                ->where('order_source', 'deliveredreorder')
                ->where('remarks', 'Selloship Import')
                ->count();

            return response()->json([
                'success' => true,
                'message' => "{$assignedTotal} Selloship records assigned successfully.",
                'assigned' => $assignedTotal,
                'remaining' => $remaining
            ]);
        } catch (\Throwable $e) {

            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Selloship Assignment Error', [
                'client_id' => $clientId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Selloship assignment failed. Please try again.'
            ], 500);
        } finally {

            /*
        |--------------------------------------------------------------------------
        | Release MySQL Lock
        |--------------------------------------------------------------------------
        */

            if ($lockAcquired) {

                DB::selectOne(
                    'SELECT RELEASE_LOCK(?) AS released',
                    [$lockName]
                );
            }
        }
    }


    /**
     * Clients 2 and 14 use separate Shopify stores but share staff.
     * Other clients remain isolated.
     */
    private function rtoLookupClientIds(int $clientId): array
    {
        return in_array($clientId, [2, 14], true)
            ? [2, 14]
            : [$clientId];
    }

    /**
     * Find the most recent assigned parent order, including an earlier RTO.
     * This is necessary when the same order returns as RTO repeatedly.
     */
    private function findRtoParentOrder(int $clientId, string $orderId): ?CallingOrder
    {
        $clientIds = $this->rtoLookupClientIds($clientId);
        $orderId = trim($orderId);

        $parent = CallingOrder::query()
            ->whereIn('client_id', $clientIds)
            ->where('order_id', $orderId)
            ->whereNotNull('assigned_to')
            ->orderByDesc('id')
            ->first();

        if ($parent) {
            return $parent;
        }

        // Fallback for data where the report points to an RTO ID
        // but only the original/base calling order exists.
        $baseOrderId = preg_replace('/R+$/i', '', $orderId);

        if (!$baseOrderId || $baseOrderId === $orderId) {
            return null;
        }

        return CallingOrder::query()
            ->whereIn('client_id', $clientIds)
            ->where('order_id', $baseOrderId)
            ->whereNotNull('assigned_to')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Append one more R for each repeated RTO.
     * S2-3186 => S2-3186R => S2-3186RR => S2-3186RRR.
     */
    private function nextRepeatedRtoOrderId(string $sourceOrderId): string
    {
        $sourceOrderId = trim($sourceOrderId);
        $baseOrderId = preg_replace('/R+$/i', '', $sourceOrderId);

        if ($baseOrderId === '') {
            throw new \RuntimeException('Cannot generate an RTO ID from an empty order ID.');
        }

        $rCount = strlen($sourceOrderId) - strlen($baseOrderId);

        // Find existing IDs with this exact base and one or more trailing R characters.
        $existingIds = CallingOrder::query()
            ->where('order_id', 'like', $baseOrderId . 'R%')
            ->pluck('order_id');

        foreach ($existingIds as $existingId) {
            if (preg_match(
                '/^' . preg_quote($baseOrderId, '/') . '(R+)$/i',
                trim($existingId),
                $matches
            )) {
                $rCount = max($rCount, strlen($matches[1]));
            }
        }

        do {
            $rCount++;
            $candidate = $baseOrderId . str_repeat('R', $rCount);

            $exists = CallingOrder::query()
                ->where('order_id', $candidate)
                ->exists();
        } while ($exists);

        return $candidate;
    }

    public function assignRtoOrders(Request $request)
    {
        $request->validate([
            'client_id' => ['required', 'integer'],
            'assign'    => ['required', 'array'],
            'assign.*'  => ['nullable', 'integer', 'min:0'],
        ]);

        $clientId = (int) $request->client_id;
        $lockName = 'rto_order_id_generation';
        $lockAcquired = false;

        try {
            // Manual aur scheduler assignment ke beech ID collision rokne ke liye.
            $lock = DB::selectOne(
                'SELECT GET_LOCK(?, 10) AS acquired',
                [$lockName]
            );

            if (!$lock || (int) $lock->acquired !== 1) {
                throw new \RuntimeException(
                    'RTO assignment is busy. Please try again.'
                );
            }

            $lockAcquired = true;

            DB::beginTransaction();

            // Sirf isi client ke pending RTO records.
            $rtoOrders = DB::table('rto_reports')
                ->join(
                    'orders',
                    'orders.order_id',
                    '=',
                    'rto_reports.order_id'
                )
                ->where('orders.client_id', $clientId)
                ->where('rto_reports.is_exported', 0)
                ->orderBy('rto_reports.id')
                ->select(
                    'rto_reports.id as rto_report_id',
                    'rto_reports.order_id as original_order_id',
                    'rto_reports.order_date',
                    'rto_reports.product',
                    'rto_reports.quantity',
                    'rto_reports.weight',
                    'rto_reports.customer_name',
                    'rto_reports.customer_phone',
                    'rto_reports.shipping_address',
                    'rto_reports.payment_mode',
                    'rto_reports.amount',
                    'orders.client_id',
                    'orders.city',
                    'orders.state',
                    'orders.pincode',
                    'orders.father_name'
                )
                ->lockForUpdate()
                ->get();

            if ($rtoOrders->isEmpty()) {
                throw new \RuntimeException('No pending RTO orders found.');
            }

            /*
         * Pehle har RTO ka original staff find karein.
         * Original staff missing/inactive ho to order pending rahega.
         */
            $eligibleByStaff = [];

            foreach ($rtoOrders as $order) {
                $originalCallingOrder = $this->findRtoParentOrder(
                    $clientId,
                    (string) $order->original_order_id
                );

                if (!$originalCallingOrder) {
                    Log::warning('RTO left pending: original assignment not found', [
                        'client_id'        => $clientId,
                        'rto_report_id'    => $order->rto_report_id,
                        'original_order_id' => $order->original_order_id,
                    ]);

                    continue;
                }

                $originalStaffId = (int) $originalCallingOrder->assigned_to;

                $originalStaff = CallingUser::query()
                    ->where('id', $originalStaffId)
                    ->where('status', 1)
                    ->first();

                if (!$originalStaff) {
                    Log::warning('RTO left pending: original staff inactive/missing', [
                        'client_id'         => $clientId,
                        'rto_report_id'     => $order->rto_report_id,
                        'original_order_id' => $order->original_order_id,
                        'original_staff_id' => $originalStaffId,
                    ]);

                    continue;
                }

                $order->original_staff_id = $originalStaffId;

                if (!isset($eligibleByStaff[$originalStaffId])) {
                    $eligibleByStaff[$originalStaffId] = collect();
                }

                $eligibleByStaff[$originalStaffId]->push($order);
            }

            // Quantity ko sirf usi staff ke eligible RTO records se match karein.
            $assignments = $request->input('assign', []);
            $selectedOrdersByStaff = [];

            foreach ($assignments as $staffId => $qty) {
                $staffId = (int) $staffId;
                $qty = (int) ($qty ?? 0);

                if ($qty <= 0) {
                    continue;
                }

                $staff = CallingUser::query()
                    ->where('id', $staffId)
                    ->where('status', 1)
                    ->first();

                if (!$staff) {
                    throw new \RuntimeException(
                        "Selected staff ID {$staffId} is inactive or unavailable."
                    );
                }

                $available = $eligibleByStaff[$staffId] ?? collect();

                if ($qty > $available->count()) {
                    throw new \RuntimeException(
                        "Staff {$staff->name} has only {$available->count()} eligible RTO orders. You requested {$qty}. No orders were assigned."
                    );
                }

                $selectedOrdersByStaff[$staffId] = $available->take($qty);
            }

            $assignedTotal = 0;

            foreach ($selectedOrdersByStaff as $staffId => $selectedOrders) {
                foreach ($selectedOrders as $order) {
                    // Safety check: only the staff assigned to the parent order can receive this RTO.
                    if ((int) $order->original_staff_id !== (int) $staffId) {
                        throw new \RuntimeException(
                            "RTO staff mismatch for source order {$order->original_order_id}."
                        );
                    }

                    // Each repeat gets one additional trailing R.
                    $callingOrderId = $this->nextRepeatedRtoOrderId(
                        (string) $order->original_order_id
                    );

                    CallingOrder::create([
                        'client_id'        => $order->client_id,
                        'order_id'         => $callingOrderId,
                        'order_date'       => $order->order_date,
                        'product_name'     => $order->product,
                        'quantity'         => $order->quantity,
                        'weight'           => $order->weight,
                        'customer_name'    => $order->customer_name,
                        'father_name'      => $order->father_name,
                        'city'             => $order->city,
                        'state'            => $order->state,
                        'pincode'          => $order->pincode,
                        'customer_phone'   => $order->customer_phone,
                        'shipping_address' => $order->shipping_address,
                        'payment_mode'     => $order->payment_mode,
                        'amount'           => $order->amount,
                        'assigned_to'      => $staffId,
                        'status'           => 'pending',
                        'order_source'     => 'RTO',
                    ]);

                    // Update only this exact RTO report row.
                    $updated = DB::table('rto_reports')
                        ->where('id', $order->rto_report_id)
                        ->where('is_exported', 0)
                        ->update([
                            'is_exported' => 1,
                            'assign_staff' => $staffId,
                            'assigndate' => now(),
                        ]);

                    if ($updated !== 1) {
                        throw new \RuntimeException(
                            "RTO report was already assigned or changed. Original order: {$order->original_order_id}"
                        );
                    }

                    $assignedTotal++;
                }
            }

            DB::commit();

            return back()->with(
                'success',
                "{$assignedTotal} RTO orders assigned to their original staff only."
            );
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Manual RTO assignment failed', [
                'client_id' => $clientId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return back()->with('error', $e->getMessage());
        } finally {
            if ($lockAcquired) {
                DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
            }
        }
    }

    public function assignabandonedOrders(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'assign'    => 'required|array',
        ]);

        $clientId = $request->client_id;

        if ($this->isClient() && $clientId != $this->clientId()) {
            abort(403, 'Unauthorized Access');
        }

        $assignData = $request->assign;



        $orders = CallingOrder::where('client_id', $clientId)

            ->whereNull('assigned_to')

            ->where('status', 'pending')

            ->where(
                'order_source',
                'shopify_abandoned_checkout'
            )

            ->inRandomOrder()

            ->get();


        $totalRequested = array_sum($assignData);



        if ($totalRequested > $orders->count()) {

            return back()->with(
                'error',
                'Assigned quantity exceeds available abandoned checkouts'
            );
        }


        $orderIndex = 0;

        foreach ($assignData as $staffId => $qty) {

            $qty = (int) $qty;

            if ($qty <= 0) {
                continue;
            }

            for ($i = 0; $i < $qty; $i++) {

                if (!isset($orders[$orderIndex])) {
                    break;
                }

                $orders[$orderIndex]->assigned_to = $staffId;

                $orders[$orderIndex]->save();

                $orderIndex++;
            }
        }

        return back()->with(
            'success',
            'Abandoned Checkouts Assigned Successfully'
        );
    }
    public function assigndeliveredOrders(Request $request)
    {
        $request->validate([
            'client_id' => 'required|integer|exists:clients,id',
            'assign'    => 'required|array',
        ]);

        DB::beginTransaction();

        try {

            $clientId = (int) $request->client_id;
            $repeatDays = ($clientId == 2) ? 20 : 25;
            $repeatOrders = DB::table('orders')
                ->where('orders.client_id', $clientId)

                ->whereRaw(
                    'LOWER(TRIM(orders.delivery_status)) = ?',
                    ['delivered']
                )

                ->whereNotNull('orders.delivery_date')

                ->whereDate(
                    'orders.delivery_date',
                    '<=',
                    now()->subDays($repeatDays)->toDateString()
                )

                ->whereNotExists(function ($q) {

                    $q->select(DB::raw(1))
                        ->from('callingorder')
                        ->whereColumn(
                            'callingorder.client_id',
                            'orders.client_id'
                        )
                        ->whereColumn(
                            'callingorder.customer_phone',
                            'orders.customer_phone'
                        )
                        ->where(
                            'callingorder.order_source',
                            'deliveredreorder'
                        );
                })

                ->select([
                    'orders.id',
                    'orders.order_id',
                    'orders.client_id',
                    'orders.date',
                    'orders.delivery_date',
                    'orders.product',
                    'orders.quantity',
                    'orders.weight',
                    'orders.customer_name',
                    'orders.father_name',
                    'orders.age',
                    'orders.city',
                    'orders.state',
                    'orders.pincode',
                    'orders.customer_phone',
                    'orders.shipping_address',
                    'orders.payment_mode',
                    'orders.amount',
                ])

                ->orderBy('orders.delivery_date', 'asc')

                ->get();

            $totalRequested = collect($request->assign)
                ->sum(function ($qty) {
                    return (int) $qty;
                });


            $availableCount = $repeatOrders->count();


            if ($totalRequested > $availableCount) {

                throw new \Exception(
                    "Only {$availableCount} repeat customers are available. " .
                        "You requested {$totalRequested}."
                );
            }

            foreach ($request->assign as $staffId => $qty) {

                $qty = (int) $qty;

                if ($qty <= 0) {
                    continue;
                }

                $staff = CallingUser::find($staffId);

                if (!$staff) {

                    throw new \Exception(
                        "Staff not found. Staff ID: {$staffId}"
                    );
                }

                $selectedOrders = $repeatOrders->splice(0, $qty);

                $name = strtoupper(trim($staff->name));

                $prefix =
                    substr($name, 0, 1) .
                    substr($name, -1, 1);


                $date = now()->format('d-m-y');


                $lastOrder = CallingOrder::where('assigned_to', $staffId)
                    ->whereDate('created_at', today())
                    ->count();

                foreach ($selectedOrders as $order) {

                    $lastOrder++;

                    $callingOrderId =
                        $prefix . '-' . $date . '-' . $lastOrder . 'D';


                    while (
                        CallingOrder::where(
                            'order_id',
                            $callingOrderId
                        )->exists()
                    ) {

                        $lastOrder++;

                        $callingOrderId =
                            $prefix . '-' . $date . '-' . $lastOrder . 'D';
                    }

                    $alreadyAssigned = CallingOrder::where(
                        'client_id',
                        $order->client_id
                    )
                        ->where(
                            'customer_phone',
                            $order->customer_phone
                        )
                        ->where(
                            'order_source',
                            'deliveredreorder'
                        )
                        ->exists();


                    if ($alreadyAssigned) {
                        continue;
                    }

                    CallingOrder::create([

                        'client_id' =>
                        $order->client_id,

                        // New Calling Order ID
                        'order_id' =>
                        $callingOrderId,

                        // orders.date -> callingorder.order_date
                        'order_date' =>
                        $order->date,

                        'product_name' =>
                        $order->product,

                        'quantity' =>
                        $order->quantity,

                        'weight' =>
                        $order->weight,

                        'customer_name' =>
                        $order->customer_name,

                        'father_name' =>
                        $order->father_name,

                        'age' =>
                        $order->age,

                        'city' =>
                        $order->city,

                        'state' =>
                        $order->state,

                        'pincode' =>
                        $order->pincode,

                        'customer_phone' =>
                        $order->customer_phone,

                        'shipping_address' =>
                        $order->shipping_address,

                        'payment_mode' =>
                        $order->payment_mode,

                        'amount' =>
                        $order->amount,

                        'assigned_to' =>
                        $staffId,

                        'status' =>
                        'pending',

                        'order_source' =>
                        'deliveredreorder',
                    ]);
                }
            }


            DB::commit();


            return redirect()
                ->back()
                ->with(
                    'success',
                    'Repeat Customers assigned successfully.'
                );
        } catch (\Throwable $e) {

            DB::rollBack();

            return redirect()
                ->back()
                ->with(
                    'error',
                    'Repeat Customer Assignment Failed: ' .
                        $e->getMessage()
                );
        }
    }

    public function performance(Request $request)
    {
        /*
    |--------------------------------------------------------------------------
    | DATE FILTER
    |--------------------------------------------------------------------------
    */

        $from = $request->filled('from')
            ? Carbon::parse($request->from)->startOfDay()
            : now()->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->to)->endOfDay()
            : now()->endOfDay();


        /*
    |--------------------------------------------------------------------------
    | CLIENT USER
    |--------------------------------------------------------------------------
    */

        $isClientUser = $this->isClient();


        if ($isClientUser) {

            // Client panel
            $clientId = $this->clientId();
        } else {

            // Admin / Super Admin
            $clientId = $request->filled('client_id')
                ? (int) $request->client_id
                : null;
        }


        /*
    |--------------------------------------------------------------------------
    | STAFF FILTER
    |--------------------------------------------------------------------------
    */

        $staffIds = $request->input('staff_ids', []);

        if (!is_array($staffIds)) {
            $staffIds = [$staffIds];
        }

        $staffIds = collect($staffIds)
            ->filter(function ($id) {
                return is_numeric($id);
            })
            ->map(function ($id) {
                return (int) $id;
            })
            ->unique()
            ->values()
            ->toArray();


        /*
    |--------------------------------------------------------------------------
    | CLIENT ALLOWED STAFF
    |--------------------------------------------------------------------------
    |
    | Client panel mein sirf us client ke assigned staff allow honge.
    |
    */

        $allowedStaffIds = [];

        if ($isClientUser) {

            $allowedStaffIds = CallingOrder::query()
                ->where('client_id', $clientId)
                ->whereNotNull('assigned_to')
                ->pluck('assigned_to')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->unique()
                ->values()
                ->toArray();


            /*
        |--------------------------------------------------------------------------
        | SECURITY
        |--------------------------------------------------------------------------
        | Client manually kisi doosre client ka staff ID URL mein bheje
        | to woh staff filter nahi ho sakta.
        */

            if (!empty($staffIds)) {

                $staffIds = array_values(
                    array_intersect(
                        $staffIds,
                        $allowedStaffIds
                    )
                );
            }
        }


        /*
    |--------------------------------------------------------------------------
    | EFFECTIVE STAFF FILTER
    |--------------------------------------------------------------------------
    |
    | Admin:
    |   selected staff => selected staff
    |   no selection    => all staff
    |
    | Client:
    |   selected staff => selected allowed staff
    |   no selection    => all client staff
    |
    */

        if ($isClientUser) {

            $effectiveStaffIds = !empty($staffIds)
                ? $staffIds
                : $allowedStaffIds;
        } else {

            $effectiveStaffIds = $staffIds;
        }


        /*
    |--------------------------------------------------------------------------
    | CLIENT LIST
    |--------------------------------------------------------------------------
    */

        if ($isClientUser) {

            $clients = Client::where(
                'id',
                $clientId
            )
                ->orderBy('client_name')
                ->get();
        } else {

            $clients = Client::orderBy(
                'client_name'
            )->get();
        }


        /*
    |--------------------------------------------------------------------------
    | BASE CALLING ORDER QUERY
    |--------------------------------------------------------------------------
    */

        $baseQuery = function () use (
            $clientId,
            $effectiveStaffIds,
            $from,
            $to
        ) {

            return CallingOrder::query()

                /*
            |--------------------------------------------------------------------------
            | CLIENT
            |--------------------------------------------------------------------------
            */

                ->when(
                    $clientId !== null,
                    function ($q) use ($clientId) {

                        $q->where(
                            'client_id',
                            $clientId
                        );
                    }
                )


                /*
            |--------------------------------------------------------------------------
            | STAFF
            |--------------------------------------------------------------------------
            */

                ->when(
                    !empty($effectiveStaffIds),
                    function ($q) use ($effectiveStaffIds) {

                        $q->whereIn(
                            'assigned_to',
                            $effectiveStaffIds
                        );
                    }
                )


                /*
            |--------------------------------------------------------------------------
            | DATE
            |--------------------------------------------------------------------------
            */

                ->whereBetween(
                    'updated_at',
                    [$from, $to]
                );
        };


        /*
    |--------------------------------------------------------------------------
    | STAFF LIST
    |--------------------------------------------------------------------------
    */

        if ($isClientUser) {

            // Client panel
            // Sirf client ke assigned active staff

            $allStaff = CallingUser::query()
                ->where('status', 1)
                ->whereIn(
                    'id',
                    $allowedStaffIds
                )
                ->orderBy('name')
                ->get();
        } else {

            // Admin / Super Admin
            // All active staff

            $allStaff = CallingUser::query()
                ->where('status', 1)
                ->orderBy('name')
                ->get();
        }


        /*
    |--------------------------------------------------------------------------
    | STAFF REPORT LIST
    |--------------------------------------------------------------------------
    */

        $staffs = CallingUser::query()
            ->where('status', 1)

            ->when(
                !empty($effectiveStaffIds),
                function ($q) use ($effectiveStaffIds) {

                    $q->whereIn(
                        'id',
                        $effectiveStaffIds
                    );
                }
            )

            ->when(
                $isClientUser,
                function ($q) use ($allowedStaffIds) {

                    $q->whereIn(
                        'id',
                        $allowedStaffIds
                    );
                }
            )

            ->orderBy('name')
            ->get();


        /*
    |--------------------------------------------------------------------------
    | STAFF STATISTICS
    |--------------------------------------------------------------------------
    */

        $staffStats = CallingOrder::query()

            /*
        |--------------------------------------------------------------------------
        | CLIENT
        |--------------------------------------------------------------------------
        */

            ->when(
                $clientId !== null,
                function ($q) use ($clientId) {

                    $q->where(
                        'client_id',
                        $clientId
                    );
                }
            )


            /*
        |--------------------------------------------------------------------------
        | STAFF
        |--------------------------------------------------------------------------
        */

            ->when(
                !empty($effectiveStaffIds),
                function ($q) use ($effectiveStaffIds) {

                    $q->whereIn(
                        'assigned_to',
                        $effectiveStaffIds
                    );
                }
            )


            /*
        |--------------------------------------------------------------------------
        | DATE
        |--------------------------------------------------------------------------
        */

            ->whereBetween(
                'updated_at',
                [$from, $to]
            )


            /*
        |--------------------------------------------------------------------------
        | SELECT
        |--------------------------------------------------------------------------
        */

            ->select(
                'assigned_to',

                DB::raw("
                COUNT(*) AS total_orders
            "),

                /*
            |--------------------------------------------------------------------------
            | WEB VERIFIED
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'verified'
                        AND order_source IS NULL
                        THEN 1
                        ELSE 0
                    END
                ) AS web_verified_orders
            "),

                /*
            |--------------------------------------------------------------------------
            | WHATSAPP VERIFIED
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'verified'
                        AND order_source = 'whatsapp'
                        THEN 1
                        ELSE 0
                    END
                ) AS whatsapp_verified_orders
            "),

                /*
            |--------------------------------------------------------------------------
            | RTO VERIFIED
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'verified'
                        AND order_source = 'RTO'
                        THEN 1
                        ELSE 0
                    END
                ) AS rto_verified_orders
            "),

                /*
            |--------------------------------------------------------------------------
            | DELIVERED REORDER
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'verified'
                        AND order_source = 'deliveredreorder'
                        THEN 1
                        ELSE 0
                    END
                ) AS delivered_reorder_orders
            "),

                /*
            |--------------------------------------------------------------------------
            | ABANDONED TOTAL
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN order_source = 'shopify_abandoned_checkout'
                        THEN 1
                        ELSE 0
                    END
                ) AS abandoned_orders
            "),

                /*
            |--------------------------------------------------------------------------
            | ABANDONED VERIFIED
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'verified'
                        AND order_source = 'shopify_abandoned_checkout'
                        THEN 1
                        ELSE 0
                    END
                ) AS abandoned_verified
            "),

                /*
            |--------------------------------------------------------------------------
            | ABANDONED PENDING
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'pending'
                        AND order_source = 'shopify_abandoned_checkout'
                        THEN 1
                        ELSE 0
                    END
                ) AS abandoned_pending
            "),

                /*
            |--------------------------------------------------------------------------
            | ABANDONED CANCEL
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'cancel'
                        AND order_source = 'shopify_abandoned_checkout'
                        THEN 1
                        ELSE 0
                    END
                ) AS abandoned_cancel
            "),

                /*
            |--------------------------------------------------------------------------
            | ABANDONED NOT REACHABLE
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'not_reachable'
                        AND order_source = 'shopify_abandoned_checkout'
                        THEN 1
                        ELSE 0
                    END
                ) AS abandoned_not_reachable
            "),

                /*
            |--------------------------------------------------------------------------
            | ABANDONED SAME ORDER
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'same_order'
                        AND order_source = 'shopify_abandoned_checkout'
                        THEN 1
                        ELSE 0
                    END
                ) AS abandoned_same_order
            "),

                /*
            |--------------------------------------------------------------------------
            | ABANDONED OTHER
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN order_source = 'shopify_abandoned_checkout'
                        AND (
                            status NOT IN (
                                'pending',
                                'verified',
                                'cancel',
                                'not_reachable',
                                'same_order'
                            )
                            OR status IS NULL
                        )
                        THEN 1
                        ELSE 0
                    END
                ) AS abandoned_other
            "),

                /*
            |--------------------------------------------------------------------------
            | PENDING
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'pending'
                        THEN 1
                        ELSE 0
                    END
                ) AS pending_orders
            "),

                /*
            |--------------------------------------------------------------------------
            | NOT REACHABLE
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'not_reachable'
                        THEN 1
                        ELSE 0
                    END
                ) AS not_reachable_orders
            "),

                /*
            |--------------------------------------------------------------------------
            | CANCEL
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'cancel'
                        THEN 1
                        ELSE 0
                    END
                ) AS cancel_orders
            "),

                /*
            |--------------------------------------------------------------------------
            | SAME ORDER
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'same_order'
                        THEN 1
                        ELSE 0
                    END
                ) AS same_order
            "),

                /*
            |--------------------------------------------------------------------------
            | OTHER
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN status NOT IN (
                            'pending',
                            'verified',
                            'cancel',
                            'not_reachable',
                            'same_order'
                        )
                        OR status IS NULL
                        THEN 1
                        ELSE 0
                    END
                ) AS other
            "),

                /*
            |--------------------------------------------------------------------------
            | RTO PENDING
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN order_source = 'RTO'
                        AND status = 'pending'
                        THEN 1
                        ELSE 0
                    END
                ) AS rto_orders
            "),

                /*
            |--------------------------------------------------------------------------
            | RTO VERIFIED
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN order_source = 'RTO'
                        AND status = 'verified'
                        THEN 1
                        ELSE 0
                    END
                ) AS rto_verified
            "),

                /*
            |--------------------------------------------------------------------------
            | DELIVERED REORDER TOTAL
            |--------------------------------------------------------------------------
            */

                DB::raw("
                SUM(
                    CASE
                        WHEN order_source = 'deliveredreorder'
                        THEN 1
                        ELSE 0
                    END
                ) AS delivered_reorder_total
            ")
            )

            ->groupBy('assigned_to')

            ->get()

            ->keyBy('assigned_to');


        /*
    |--------------------------------------------------------------------------
    | WHATSAPP DATA
    |--------------------------------------------------------------------------
    */

        $waData = Conversation::query()

            ->when(
                $clientId !== null,
                function ($q) use ($clientId) {

                    $q->where(
                        'client_id',
                        $clientId
                    );
                }
            )

            ->when(
                !empty($effectiveStaffIds),
                function ($q) use ($effectiveStaffIds) {

                    $q->whereIn(
                        'assigned_to',
                        $effectiveStaffIds
                    );
                }
            )

            ->whereBetween(
                'created_at',
                [$from, $to]
            )

            ->select(
                'assigned_to',

                DB::raw("
                COUNT(*) AS wa_total
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'verified'
                        THEN 1
                        ELSE 0
                    END
                ) AS wa_verified
            "),

                DB::raw("
                SUM(
                    CASE
                        WHEN status = 'open'
                        THEN 1
                        ELSE 0
                    END
                ) AS wa_pending
            ")
            )

            ->groupBy('assigned_to')

            ->get()

            ->keyBy('assigned_to');


        /*
    |--------------------------------------------------------------------------
    | ATTACH STAFF STATS
    |--------------------------------------------------------------------------
    */

        foreach ($staffs as $staff) {

            $stats = $staffStats->get(
                $staff->id
            );

            $wa = $waData->get(
                $staff->id
            );


            $staff->total_orders =
                (int) ($stats->total_orders ?? 0);

            $staff->web_verified_orders =
                (int) ($stats->web_verified_orders ?? 0);

            $staff->whatsapp_verified_orders =
                (int) ($stats->whatsapp_verified_orders ?? 0);

            $staff->abandoned_orders =
                (int) ($stats->abandoned_orders ?? 0);

            $staff->abandoned_verified =
                (int) ($stats->abandoned_verified ?? 0);

            $staff->abandoned_pending =
                (int) ($stats->abandoned_pending ?? 0);

            $staff->abandoned_cancel =
                (int) ($stats->abandoned_cancel ?? 0);

            $staff->abandoned_not_reachable =
                (int) ($stats->abandoned_not_reachable ?? 0);

            $staff->abandoned_same_order =
                (int) ($stats->abandoned_same_order ?? 0);

            $staff->abandoned_other =
                (int) ($stats->abandoned_other ?? 0);

            $staff->rto_verified_orders =
                (int) ($stats->rto_verified_orders ?? 0);

            $staff->delivered_reorder_orders =
                (int) ($stats->delivered_reorder_orders ?? 0);

            $staff->pending_orders =
                (int) ($stats->pending_orders ?? 0);

            $staff->not_reachable_orders =
                (int) ($stats->not_reachable_orders ?? 0);

            $staff->cancel_orders =
                (int) ($stats->cancel_orders ?? 0);

            $staff->same_order =
                (int) ($stats->same_order ?? 0);

            $staff->other =
                (int) ($stats->other ?? 0);

            $staff->rto_orders =
                (int) ($stats->rto_orders ?? 0);

            $staff->rto_verified =
                (int) ($stats->rto_verified ?? 0);

            $staff->delivered_reorder_total =
                (int) ($stats->delivered_reorder_total ?? 0);


            /*
        |--------------------------------------------------------------------------
        | WHATSAPP
        |--------------------------------------------------------------------------
        */

            $staff->wa_total =
                (int) ($wa->wa_total ?? 0);

            $staff->wa_verified =
                (int) ($wa->wa_verified ?? 0);

            $staff->wa_pending =
                (int) ($wa->wa_pending ?? 0);


            $staff->delivered_reorder =
                $staff->delivered_reorder_orders;
        }


        /*
    |--------------------------------------------------------------------------
    | CLIENT WISE DATA
    |--------------------------------------------------------------------------
    */

        $clientData = CallingOrder::with('client')

            ->when(
                $clientId !== null,
                function ($q) use ($clientId) {

                    $q->where(
                        'client_id',
                        $clientId
                    );
                }
            )

            ->when(
                !empty($effectiveStaffIds),
                function ($q) use ($effectiveStaffIds) {

                    $q->whereIn(
                        'assigned_to',
                        $effectiveStaffIds
                    );
                }
            )

            ->whereBetween(
                'updated_at',
                [$from, $to]
            )

            ->selectRaw("
            assigned_to,
            client_id,
            COUNT(*) AS total
        ")

            ->groupBy(
                'assigned_to',
                'client_id'
            )

            ->get();


        /*
    |--------------------------------------------------------------------------
    | CLIENT WISE ARRAY
    |--------------------------------------------------------------------------
    */

        $clientWise = [];


        foreach ($clientData as $row) {

            $clientWise[$row->assigned_to][] = [

                'client' =>
                $row->client->client_name
                    ?? 'N/A',

                'total' =>
                (int) $row->total,
            ];
        }


        /*
    |--------------------------------------------------------------------------
    | TOTAL WEB
    |--------------------------------------------------------------------------
    */

        $totalWeb = $baseQuery()
            ->whereNull('order_source')
            ->count();


        /*
    |--------------------------------------------------------------------------
    | TOTAL WHATSAPP
    |--------------------------------------------------------------------------
    */

        $totalWhatsapp = $baseQuery()
            ->where(
                'order_source',
                'whatsapp'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | TOTAL RTO ALL
    |--------------------------------------------------------------------------
    */

        $totalRtoAll = $baseQuery()
            ->where(
                'order_source',
                'RTO'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | TOTAL DELIVERED REORDER
    |--------------------------------------------------------------------------
    */

        $totalDeliveredReorder = $baseQuery()
            ->where(
                'order_source',
                'deliveredreorder'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | TOTAL PENDING
    |--------------------------------------------------------------------------
    */

        $totalPending = $baseQuery()
            ->where(
                'status',
                'pending'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | PENDING WEB
    |--------------------------------------------------------------------------
    */

        $pendingWeb = $baseQuery()
            ->where(
                'status',
                'pending'
            )
            ->whereNull('order_source')
            ->count();


        /*
    |--------------------------------------------------------------------------
    | PENDING WHATSAPP
    |--------------------------------------------------------------------------
    */

        $pendingWhatsapp = $baseQuery()
            ->where(
                'status',
                'pending'
            )
            ->where(
                'order_source',
                'whatsapp'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | PENDING RTO
    |--------------------------------------------------------------------------
    */

        $pendingRto = $baseQuery()
            ->where(
                'status',
                'pending'
            )
            ->where(
                'order_source',
                'RTO'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | PENDING DELIVERED REORDER
    |--------------------------------------------------------------------------
    */

        $pendingDeliveredReorder = $baseQuery()
            ->where(
                'status',
                'pending'
            )
            ->where(
                'order_source',
                'deliveredreorder'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | TOTAL VERIFIED
    |--------------------------------------------------------------------------
    */

        $totalVerified = $baseQuery()
            ->where(
                'status',
                'verified'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | VERIFIED WEB
    |--------------------------------------------------------------------------
    */

        $verifiedWeb = $baseQuery()
            ->where(
                'status',
                'verified'
            )
            ->whereNull('order_source')
            ->count();


        /*
    |--------------------------------------------------------------------------
    | VERIFIED WHATSAPP
    |--------------------------------------------------------------------------
    */

        $verifiedWhatsapp = $baseQuery()
            ->where(
                'status',
                'verified'
            )
            ->where(
                'order_source',
                'whatsapp'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | VERIFIED RTO
    |--------------------------------------------------------------------------
    */

        $verifiedRto = $baseQuery()
            ->where(
                'status',
                'verified'
            )
            ->where(
                'order_source',
                'RTO'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | VERIFIED DELIVERED REORDER
    |--------------------------------------------------------------------------
    */

        $verifiedDeliveredReorder = $baseQuery()
            ->where(
                'status',
                'verified'
            )
            ->where(
                'order_source',
                'deliveredreorder'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | TOTAL CANCEL
    |--------------------------------------------------------------------------
    */

        $totalCancel = $baseQuery()
            ->where(
                'status',
                'cancel'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | CANCEL WEB
    |--------------------------------------------------------------------------
    */

        $cancelWeb = $baseQuery()
            ->where(
                'status',
                'cancel'
            )
            ->whereNull('order_source')
            ->count();


        /*
    |--------------------------------------------------------------------------
    | CANCEL WHATSAPP
    |--------------------------------------------------------------------------
    */

        $cancelWhatsapp = $baseQuery()
            ->where(
                'status',
                'cancel'
            )
            ->where(
                'order_source',
                'whatsapp'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | CANCEL RTO
    |--------------------------------------------------------------------------
    */

        $cancelRto = $baseQuery()
            ->where(
                'status',
                'cancel'
            )
            ->where(
                'order_source',
                'RTO'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | CANCEL DELIVERED REORDER
    |--------------------------------------------------------------------------
    */

        $cancelDeliveredReorder = $baseQuery()
            ->where(
                'status',
                'cancel'
            )
            ->where(
                'order_source',
                'deliveredreorder'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | TOTAL NOT REACHABLE
    |--------------------------------------------------------------------------
    */

        $totalNotReachable = $baseQuery()
            ->where(
                'status',
                'not_reachable'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | NOT REACHABLE WEB
    |--------------------------------------------------------------------------
    */

        $notReachableWeb = $baseQuery()
            ->where(
                'status',
                'not_reachable'
            )
            ->whereNull('order_source')
            ->count();


        /*
    |--------------------------------------------------------------------------
    | NOT REACHABLE WHATSAPP
    |--------------------------------------------------------------------------
    */

        $notReachableWhatsapp = $baseQuery()
            ->where(
                'status',
                'not_reachable'
            )
            ->where(
                'order_source',
                'whatsapp'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | NOT REACHABLE RTO
    |--------------------------------------------------------------------------
    */

        $notReachableRto = $baseQuery()
            ->where(
                'status',
                'not_reachable'
            )
            ->where(
                'order_source',
                'RTO'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | NOT REACHABLE DELIVERED REORDER
    |--------------------------------------------------------------------------
    */

        $notReachableDeliveredReorder = $baseQuery()
            ->where(
                'status',
                'not_reachable'
            )
            ->where(
                'order_source',
                'deliveredreorder'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | TOTAL SAME ORDER
    |--------------------------------------------------------------------------
    */

        $totalSameOrder = $baseQuery()
            ->where(
                'status',
                'same_order'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | SAME ORDER WEB
    |--------------------------------------------------------------------------
    */

        $sameOrderWeb = $baseQuery()
            ->where(
                'status',
                'same_order'
            )
            ->whereNull('order_source')
            ->count();


        /*
    |--------------------------------------------------------------------------
    | SAME ORDER WHATSAPP
    |--------------------------------------------------------------------------
    */

        $sameOrderWhatsapp = $baseQuery()
            ->where(
                'status',
                'same_order'
            )
            ->where(
                'order_source',
                'whatsapp'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | SAME ORDER RTO
    |--------------------------------------------------------------------------
    */

        $sameOrderRto = $baseQuery()
            ->where(
                'status',
                'same_order'
            )
            ->where(
                'order_source',
                'RTO'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | SAME ORDER DELIVERED REORDER
    |--------------------------------------------------------------------------
    */

        $sameOrderDeliveredReorder = $baseQuery()
            ->where(
                'status',
                'same_order'
            )
            ->where(
                'order_source',
                'deliveredreorder'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | KNOWN STATUSES
    |--------------------------------------------------------------------------
    */

        $knownStatuses = [
            'pending',
            'verified',
            'cancel',
            'not_reachable',
            'same_order',
        ];


        /*
    |--------------------------------------------------------------------------
    | TOTAL OTHER
    |--------------------------------------------------------------------------
    */

        $totalOther = $baseQuery()
            ->where(function ($q) use ($knownStatuses) {

                $q->whereNotIn(
                    'status',
                    $knownStatuses
                )
                    ->orWhereNull('status');
            })
            ->count();


        /*
    |--------------------------------------------------------------------------
    | OTHER WEB
    |--------------------------------------------------------------------------
    */

        $otherWeb = $baseQuery()
            ->whereNull('order_source')
            ->where(function ($q) use ($knownStatuses) {

                $q->whereNotIn(
                    'status',
                    $knownStatuses
                )
                    ->orWhereNull('status');
            })
            ->count();


        /*
    |--------------------------------------------------------------------------
    | OTHER WHATSAPP
    |--------------------------------------------------------------------------
    */

        $otherWhatsapp = $baseQuery()
            ->where(
                'order_source',
                'whatsapp'
            )
            ->where(function ($q) use ($knownStatuses) {

                $q->whereNotIn(
                    'status',
                    $knownStatuses
                )
                    ->orWhereNull('status');
            })
            ->count();


        /*
    |--------------------------------------------------------------------------
    | OTHER RTO
    |--------------------------------------------------------------------------
    */

        $otherRto = $baseQuery()
            ->where(
                'order_source',
                'RTO'
            )
            ->where(function ($q) use ($knownStatuses) {

                $q->whereNotIn(
                    'status',
                    $knownStatuses
                )
                    ->orWhereNull('status');
            })
            ->count();


        /*
    |--------------------------------------------------------------------------
    | OTHER DELIVERED REORDER
    |--------------------------------------------------------------------------
    */

        $otherDeliveredReorder = $baseQuery()
            ->where(
                'order_source',
                'deliveredreorder'
            )
            ->where(function ($q) use ($knownStatuses) {

                $q->whereNotIn(
                    'status',
                    $knownStatuses
                )
                    ->orWhereNull('status');
            })
            ->count();


        /*
    |--------------------------------------------------------------------------
    | TOTAL RTO PENDING
    |--------------------------------------------------------------------------
    */

        $totalRto = $baseQuery()
            ->where(
                'order_source',
                'RTO'
            )
            ->where(
                'status',
                'pending'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | TOTAL ORDERS
    |--------------------------------------------------------------------------
    */

        $totalOrders = $baseQuery()
            ->count();


        /*
    |--------------------------------------------------------------------------
    | ABANDONED TOTAL
    |--------------------------------------------------------------------------
    */

        $totalAbandoned = $baseQuery()
            ->where(
                'order_source',
                'shopify_abandoned_checkout'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | ABANDONED PENDING
    |--------------------------------------------------------------------------
    */

        $pendingAbandoned = $baseQuery()
            ->where(
                'status',
                'pending'
            )
            ->where(
                'order_source',
                'shopify_abandoned_checkout'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | ABANDONED VERIFIED
    |--------------------------------------------------------------------------
    */

        $verifiedAbandoned = $baseQuery()
            ->where(
                'status',
                'verified'
            )
            ->where(
                'order_source',
                'shopify_abandoned_checkout'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | ABANDONED CANCEL
    |--------------------------------------------------------------------------
    */

        $cancelAbandoned = $baseQuery()
            ->where(
                'status',
                'cancel'
            )
            ->where(
                'order_source',
                'shopify_abandoned_checkout'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | ABANDONED NOT REACHABLE
    |--------------------------------------------------------------------------
    */

        $notReachableAbandoned = $baseQuery()
            ->where(
                'status',
                'not_reachable'
            )
            ->where(
                'order_source',
                'shopify_abandoned_checkout'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | ABANDONED SAME ORDER
    |--------------------------------------------------------------------------
    */

        $sameOrderAbandoned = $baseQuery()
            ->where(
                'status',
                'same_order'
            )
            ->where(
                'order_source',
                'shopify_abandoned_checkout'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | ABANDONED OTHER
    |--------------------------------------------------------------------------
    */

        $otherAbandoned = $baseQuery()
            ->where(
                'order_source',
                'shopify_abandoned_checkout'
            )
            ->where(function ($q) use ($knownStatuses) {

                $q->whereNotIn(
                    'status',
                    $knownStatuses
                )
                    ->orWhereNull('status');
            })
            ->count();


        /*
    |--------------------------------------------------------------------------
    | RTO VERIFIED
    |--------------------------------------------------------------------------
    */

        $totalRtoVerified = $baseQuery()
            ->where(
                'order_source',
                'RTO'
            )
            ->where(
                'status',
                'verified'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | WHATSAPP TOTAL
    |--------------------------------------------------------------------------
    */

        $waBaseQuery = function () use (
            $clientId,
            $effectiveStaffIds,
            $from,
            $to
        ) {

            return Conversation::query()

                ->when(
                    $clientId !== null,
                    function ($q) use ($clientId) {

                        $q->where(
                            'client_id',
                            $clientId
                        );
                    }
                )

                ->when(
                    !empty($effectiveStaffIds),
                    function ($q) use ($effectiveStaffIds) {

                        $q->whereIn(
                            'assigned_to',
                            $effectiveStaffIds
                        );
                    }
                )

                ->whereBetween(
                    'created_at',
                    [$from, $to]
                );
        };


        $totalWA = $waBaseQuery()
            ->count();


        /*
    |--------------------------------------------------------------------------
    | WHATSAPP VERIFIED
    |--------------------------------------------------------------------------
    */

        $verifiedWA = $waBaseQuery()
            ->where(
                'status',
                'verified'
            )
            ->count();


        /*
    |--------------------------------------------------------------------------
    | RETURN VIEW
    |--------------------------------------------------------------------------
    */

        return view(
            'performance',
            compact(

                'staffs',

                'from',
                'to',

                'clients',
                'clientId',

                'staffIds',

                'isClientUser',

                'clientWise',

                'allStaff',

                /*
            |--------------------------------------------------------------------------
            | TOTALS
            |--------------------------------------------------------------------------
            */

                'totalOrders',

                'totalWeb',

                'totalWhatsapp',

                'totalRtoAll',

                'totalDeliveredReorder',

                'totalPending',

                'pendingWeb',

                'pendingWhatsapp',

                'pendingRto',

                'pendingDeliveredReorder',

                'totalVerified',

                'verifiedWeb',

                'verifiedWhatsapp',

                'verifiedRto',

                'verifiedDeliveredReorder',

                'totalCancel',

                'cancelWeb',

                'cancelWhatsapp',

                'cancelRto',

                'cancelDeliveredReorder',

                'totalNotReachable',

                'notReachableWeb',

                'notReachableWhatsapp',

                'notReachableRto',

                'notReachableDeliveredReorder',

                'totalSameOrder',

                'sameOrderWeb',

                'sameOrderWhatsapp',

                'sameOrderRto',

                'sameOrderDeliveredReorder',

                'totalOther',

                'otherWeb',

                'otherWhatsapp',

                'otherRto',

                'otherDeliveredReorder',

                'totalRto',

                'totalRtoVerified',

                /*
            |--------------------------------------------------------------------------
            | ABANDONED
            |--------------------------------------------------------------------------
            */

                'totalAbandoned',

                'pendingAbandoned',

                'verifiedAbandoned',

                'cancelAbandoned',

                'notReachableAbandoned',

                'sameOrderAbandoned',

                'otherAbandoned',

                /*
            |--------------------------------------------------------------------------
            | WHATSAPP
            |--------------------------------------------------------------------------
            */

                'totalWA',

                'verifiedWA'
            )
        );
    }
    public function assignOrders(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'assign'    => 'required|array'
        ]);

        $clientId = $request->client_id;

        // Client sirf apne orders assign kar sakta hai
        if ($this->isClient() && $clientId != $this->clientId()) {

            abort(403, 'Unauthorized Access');
        }

        $assignData = $request->assign;

        $orders = CallingOrder::where('client_id', $clientId)
            ->whereNull('assigned_to')
            ->where('status', 'pending')
            ->inRandomOrder()
            ->get();

        $totalRequested = array_sum($assignData);

        if ($totalRequested > $orders->count()) {

            return back()->with(
                'error',
                'Assigned quantity exceeds available orders'
            );
        }

        $orderIndex = 0;

        foreach ($assignData as $staffId => $qty) {

            if ($qty <= 0) {
                continue;
            }

            for ($i = 0; $i < $qty; $i++) {

                if (!isset($orders[$orderIndex])) {
                    break;
                }

                $orders[$orderIndex]->assigned_to = $staffId;
                $orders[$orderIndex]->save();

                $orderIndex++;
            }
        }

        return back()->with(
            'success',
            'Orders Assigned Successfully'
        );
    }
    public function assignPage()
    {
        if ($this->isClient()) {

            $clients = Client::where(
                'id',
                $this->clientId()
            )->get();
        } else {

            $clients = Client::all();
        }

        $staffs = User::where('role', 'staff')->get();

        return view(
            'assign-orders',
            compact('clients', 'staffs')
        );
    }


    public function labelsenders()
    {
        if ($this->isClient()) {

            $senders = LabelSender::where(
                'client_id',
                $this->clientId()
            )->latest()->get();

            $clients = Client::where(
                'id',
                $this->clientId()
            )->get();
        } else {

            $senders = LabelSender::latest()->get();

            $clients = Client::orderBy('client_name')->get();
        }

        return view(
            'labelsenders',
            compact(
                'senders',
                'clients'
            )
        );
    }
    public function storeLabelSenders(Request $request)
    {
        $request->validate([
            'customer_name'  => 'required|string|max:255',
            'customer_phone' => 'required|string|max:255',
        ]);

        $clientId = $this->isClient()
            ? $this->clientId()
            : $request->client_id;

        LabelSender::create([
            'client_id'      => $clientId,
            'customer_name'  => $request->customer_name,
            'customer_phone' => $request->customer_phone,
        ]);

        return redirect()
            ->route('labelsenders')
            ->with(
                'success',
                'Label sender saved successfully'
            );
    }
    public function labelgenrate()
    {
        if ($this->isClient()) {

            $senders = LabelSender::where(
                'client_id',
                $this->clientId()
            )
                ->latest()
                ->get();
        } else {

            $senders = LabelSender::latest()->get();
        }

        return view(
            'labelgenrate',
            compact('senders')
        );
    }
}
