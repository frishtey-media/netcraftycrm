<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OrderAssignmentScheduler;
use App\Models\CallingUser;
use App\Models\callingorder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunOrderAssignmentSchedulers extends Command
{
    protected $signature = 'orders:run-assignment-schedulers';

    protected $description = 'Automatically assign pending orders according to scheduler rules';


    /*
    |--------------------------------------------------------------------------
    | HANDLE
    |--------------------------------------------------------------------------
    */

    public function handle()
    {
        $now = Carbon::now('Asia/Kolkata');

        $currentTime = $now->format('H:i:s');

        $today = strtolower($now->format('l'));

        Log::info('==========================================');

        Log::info('AUTO ASSIGNMENT START', [
            'time' => $now->toDateTimeString(),
            'timezone' => 'Asia/Kolkata',
            'today' => $today,
        ]);

        Log::info('==========================================');


        /*
        |--------------------------------------------------------------------------
        | ACTIVE SCHEDULERS
        |--------------------------------------------------------------------------
        */

        $schedulers = OrderAssignmentScheduler::where(
            'is_active',
            true
        )->get();


        Log::info('ACTIVE SCHEDULERS FOUND', [
            'count' => $schedulers->count(),
            'current_time' => $currentTime,
            'today' => $today,
            'timezone' => 'Asia/Kolkata',
        ]);


        /*
        |--------------------------------------------------------------------------
        | PROCESS EACH SCHEDULER
        |--------------------------------------------------------------------------
        */

        foreach ($schedulers as $scheduler) {

            /*
            |--------------------------------------------------------------------------
            | Decode Days
            |--------------------------------------------------------------------------
            */

            $days = $scheduler->days ?? [];

            if (is_string($days)) {
                $days = json_decode($days, true) ?? [];
            }

            if (!is_array($days)) {
                $days = [];
            }

            $days = array_map(
                fn($day) => strtolower(trim($day)),
                $days
            );


            /*
            |--------------------------------------------------------------------------
            | Decode Order Types
            |--------------------------------------------------------------------------
            */

            $orderTypes = $scheduler->order_types ?? [];

            if (is_string($orderTypes)) {
                $orderTypes = json_decode($orderTypes, true) ?? [];
            }

            if (!is_array($orderTypes)) {
                $orderTypes = [];
            }

            $orderTypes = array_map(
                fn($type) => strtolower(trim($type)),
                $orderTypes
            );


            /*
            |--------------------------------------------------------------------------
            | Decode Staff Assignments
            |--------------------------------------------------------------------------
            */

            $staffAssignments = $scheduler->staff_assignments ?? [];

            if (is_string($staffAssignments)) {
                $staffAssignments = json_decode(
                    $staffAssignments,
                    true
                ) ?? [];
            }

            if (!is_array($staffAssignments)) {
                $staffAssignments = [];
            }


            Log::info('------------------------------------------');

            Log::info('CHECKING SCHEDULER', [
                'scheduler_id' => $scheduler->id,
                'client_id' => $scheduler->client_id,
                'start_time' => $scheduler->start_time,
                'end_time' => $scheduler->end_time,
                'days' => $days,
                'order_types' => $orderTypes,
                'staff_assignments' => $staffAssignments,
            ]);


            /*
            |--------------------------------------------------------------------------
            | TIME CHECK
            |--------------------------------------------------------------------------
            */

            if (
                $currentTime < $scheduler->start_time ||
                $currentTime >= $scheduler->end_time
            ) {

                Log::info('SCHEDULER OUTSIDE TIME', [
                    'scheduler_id' => $scheduler->id,
                    'current_time' => $currentTime,
                    'start_time' => $scheduler->start_time,
                    'end_time' => $scheduler->end_time,
                ]);

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | DAY CHECK
            |--------------------------------------------------------------------------
            */

            if (
                !empty($days) &&
                !in_array($today, $days, true)
            ) {

                Log::info('SCHEDULER DAY NOT MATCHED', [
                    'scheduler_id' => $scheduler->id,
                    'today' => $today,
                    'allowed_days' => $days,
                ]);

                continue;
            }


            Log::info('SCHEDULER PASSED TIME AND DAY CHECK', [
                'scheduler_id' => $scheduler->id,
            ]);


            /*
            |--------------------------------------------------------------------------
            | PROCESS SCHEDULER
            |--------------------------------------------------------------------------
            */

            try {

                $this->processScheduler(
                    $scheduler,
                    $orderTypes,
                    $staffAssignments
                );
            } catch (\Throwable $e) {

                Log::error('AUTO ASSIGNMENT ERROR', [
                    'scheduler_id' => $scheduler->id,
                    'client_id' => $scheduler->client_id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }


        Log::info('==========================================');

        Log::info('AUTO ASSIGNMENT END');

        Log::info('==========================================');


        return Command::SUCCESS;
    }


    /*
    |--------------------------------------------------------------------------
    | PROCESS SCHEDULER
    |--------------------------------------------------------------------------
    */

    private function processScheduler(
        OrderAssignmentScheduler $scheduler,
        array $orderTypes,
        array $staffAssignments
    ) {

        Log::info('PROCESS SCHEDULER START', [
            'scheduler_id' => $scheduler->id,
            'client_id' => $scheduler->client_id,
        ]);


        /*
        |--------------------------------------------------------------------------
        | ORDER TYPES
        |--------------------------------------------------------------------------
        */

        Log::info('SCHEDULER ORDER TYPES', [
            'raw' => $scheduler->order_types,
            'parsed' => $orderTypes,
            'is_array' => is_array($orderTypes),
        ]);


        if (empty($orderTypes)) {

            Log::warning('NO ORDER TYPES CONFIGURED', [
                'scheduler_id' => $scheduler->id,
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | STAFF ASSIGNMENTS
        |--------------------------------------------------------------------------
        */

        Log::info('SCHEDULER STAFF ASSIGNMENTS', [
            'staff_assignments' => $staffAssignments,
        ]);


        $isRtoScheduler = in_array('rto', $orderTypes, true);

        if (
            empty($staffAssignments) &&
            !$isRtoScheduler
        ) {
            Log::warning('NO STAFF ASSIGNMENTS CONFIGURED', [
                'scheduler_id' => $scheduler->id,
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | PENDING ORDERS
        |--------------------------------------------------------------------------
        |
        | Only:
        |
        | client_id = scheduler client
        | assigned_to IS NULL
        | status = pending
        |
        */
        if ($isRtoScheduler) {

            $this->processRtoScheduler($scheduler);

            return;
        }
        $ordersQuery = Callingorder::query()
            ->where(
                'client_id',
                $scheduler->client_id
            )
            ->whereNull('assigned_to')
            ->where(
                'status',
                'pending'
            );


        /*
        |--------------------------------------------------------------------------
        | ORDER TYPE FILTER
        |--------------------------------------------------------------------------
        */

        $ordersQuery->where(function ($q) use ($orderTypes) {

            $validTypeFound = false;

            foreach ($orderTypes as $index => $type) {

                $type = strtolower(trim($type));


                /*
                |--------------------------------------------------------------------------
                | SHOPIFY
                |--------------------------------------------------------------------------
                |
                | IMPORTANT:
                |
                | NULL = SHOPIFY
                |
                | Do NOT include:
                | empty string
                | web
                | shopify
                |
                */

                if ($type === 'shopify') {

                    $validTypeFound = true;

                    Log::info('ORDER TYPE FILTER: SHOPIFY', [
                        'rule' => 'order_source IS NULL',
                        'meaning' => 'NULL order_source = Shopify',
                    ]);


                    $condition = function ($query) {

                        $query->whereNull(
                            'order_source'
                        );
                    };
                }


                /*
                |--------------------------------------------------------------------------
                | WEB
                |--------------------------------------------------------------------------
                */ elseif ($type === 'web') {

                    $validTypeFound = true;

                    Log::info('ORDER TYPE FILTER: WEB', [
                        'rule' => 'LOWER(TRIM(order_source)) = web',
                    ]);


                    $condition = function ($query) {

                        $query->whereRaw(
                            'LOWER(TRIM(order_source)) = ?',
                            ['web']
                        );
                    };
                }


                /*
                |--------------------------------------------------------------------------
                | ABANDONED CHECKOUT
                |--------------------------------------------------------------------------
                */ elseif ($type === 'abandoned_checkout') {

                    $validTypeFound = true;

                    Log::info(
                        'ORDER TYPE FILTER: ABANDONED CHECKOUT',
                        [
                            'rule' =>
                            'LOWER(TRIM(order_source)) = shopify_abandoned_checkout',
                        ]
                    );


                    $condition = function ($query) {

                        $query->whereRaw(
                            'LOWER(TRIM(order_source)) = ?',
                            ['shopify_abandoned_checkout']
                        );
                    };
                }


                /*
                |--------------------------------------------------------------------------
                | DELIVERED REORDER
                |--------------------------------------------------------------------------
                */ elseif ($type === 'deliveredreorder') {

                    $validTypeFound = true;

                    Log::info(
                        'ORDER TYPE FILTER: DELIVERED REORDER',
                        [
                            'rule' =>
                            'LOWER(TRIM(order_source)) = deliveredreorder',
                        ]
                    );


                    $condition = function ($query) {

                        $query->whereRaw(
                            'LOWER(TRIM(order_source)) = ?',
                            ['deliveredreorder']
                        );
                    };
                }


                /*
                |--------------------------------------------------------------------------
                | RTO
                |--------------------------------------------------------------------------
                */ elseif ($type === 'rto') {

                    $validTypeFound = true;

                    Log::info('ORDER TYPE FILTER: RTO', [
                        'rule' =>
                        'LOWER(TRIM(order_source)) = rto',
                    ]);


                    $condition = function ($query) {

                        $query->whereRaw(
                            'LOWER(TRIM(order_source)) = ?',
                            ['rto']
                        );
                    };
                }


                /*
                |--------------------------------------------------------------------------
                | UNKNOWN TYPE
                |--------------------------------------------------------------- -----------
                */ else {

                    //  Log::warning('UNKNOWN ORDER TYPE', [
                    //      'type' => $type,
                    // 'scheduler_id' => $scheduler->id,
                    //]);

                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | APPLY CONDITION
                |--------------------------------------------------------------------------
                */

                if ($validTypeFound && $index === 0) {

                    $q->where($condition);
                } elseif ($validTypeFound) {

                    $q->orWhere($condition);
                }
            }
        });


        /*
        |--------------------------------------------------------------------------
        | QUERY LOG
        |--------------------------------------------------------------------------
        */

        Log::info('PENDING ORDER QUERY', [
            'sql' => $ordersQuery->toSql(),
            'bindings' => $ordersQuery->getBindings(),
        ]);


        /*
        |--------------------------------------------------------------------------
        | GET ORDERS
        |--------------------------------------------------------------------------
        */

        $orders = $ordersQuery
            ->orderBy('created_at', 'asc')
            ->get();


        Log::info('PENDING ORDERS FOUND', [
            'count' => $orders->count(),
            'order_ids' => $orders->pluck('id')->toArray(),
        ]);


        /*
        |--------------------------------------------------------------------------
        | NO ORDERS - DEBUG
        |--------------------------------------------------------------------------
        */

        if ($orders->isEmpty()) {

            $allPending = Callingorder::query()
                ->where(
                    'client_id',
                    $scheduler->client_id
                )
                ->whereNull('assigned_to')
                ->where(
                    'status',
                    'pending'
                )
                ->select([
                    'id',
                    'order_id',
                    'order_source',
                    'status',
                    'assigned_to',
                ])
                ->orderBy('id', 'asc')
                ->get();


            Log::warning(
                'NO PENDING ORDERS FOUND FOR SCHEDULER',
                [
                    'scheduler_id' => $scheduler->id,
                    'client_id' => $scheduler->client_id,
                    'order_types' => $orderTypes,

                    'all_pending_unassigned_count' =>
                    $allPending->count(),

                    'all_pending_unassigned_orders' =>
                    $allPending->toArray(),
                ]
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | ASSIGN ORDERS
        |--------------------------------------------------------------------------
        */

        $assigned = 0;


        foreach ($orders as $order) {

            Log::info('PROCESSING ORDER', [
                'order_db_id' => $order->id,
                'order_id' => $order->order_id,
                'client_id' => $order->client_id,
                'order_source' => $order->order_source,
                'status' => $order->status,
                'assigned_to' => $order->assigned_to,
            ]);


            /*
            |--------------------------------------------------------------------------
            | SELECT STAFF
            |--------------------------------------------------------------------------
            */

            $staffId = $this->selectStaff(
                $scheduler,
                $staffAssignments,
                $orderTypes
            );


            Log::info('STAFF SELECTED', [
                'scheduler_id' => $scheduler->id,
                'order_id' => $order->id,
                'staff_id' => $staffId,
            ]);


            /*
            |--------------------------------------------------------------------------
            | NO STAFF
            |--------------------------------------------------------------------------
            */

            if (!$staffId) {

                Log::warning('NO STAFF AVAILABLE', [
                    'scheduler_id' => $scheduler->id,
                    'order_id' => $order->id,
                ]);

                break;
            }


            /*
            |--------------------------------------------------------------------------
            | FINAL SAFETY CHECK
            |--------------------------------------------------------------------------
            |
            | Prevent another scheduler/process from assigning
            | an already assigned order.
            |
            */

            $updated = Callingorder::query()
                ->where('id', $order->id)
                ->whereNull('assigned_to')
                ->where('status', 'pending')
                ->update([
                    'assigned_to' => $staffId,
                ]);


            if ($updated === 0) {

                Log::warning(
                    'ORDER ASSIGNMENT SKIPPED - ALREADY UPDATED',
                    [
                        'scheduler_id' => $scheduler->id,
                        'order_id' => $order->id,
                        'staff_id' => $staffId,
                    ]
                );

                continue;
            }


            $assigned++;


            Log::info('ORDER AUTO ASSIGNED', [
                'scheduler_id' => $scheduler->id,
                'order_id' => $order->id,
                'client_id' => $scheduler->client_id,
                'staff_id' => $staffId,
                'order_source' => $order->order_source,
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        Log::info('SCHEDULER SUMMARY', [
            'scheduler_id' => $scheduler->id,
            'client_id' => $scheduler->client_id,
            'orders_found' => $orders->count(),
            'orders_assigned' => $assigned,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | SELECT STAFF
    |--------------------------------------------------------------------------
    */
    private function processRtoScheduler(
        OrderAssignmentScheduler $scheduler
    ) {
        Log::info('RTO SCHEDULER START', [
            'scheduler_id' => $scheduler->id,
            'client_id' => $scheduler->client_id,
        ]);

        /*
    |--------------------------------------------------------------------------
    | Get Pending RTO Reports
    |--------------------------------------------------------------------------
    */

        $rtoOrders = DB::table('rto_reports')
            ->join(
                'orders',
                'orders.order_id',
                '=',
                'rto_reports.order_id'
            )
            ->where(
                'orders.client_id',
                $scheduler->client_id
            )
            ->where(
                'rto_reports.is_exported',
                0
            )
            ->orderBy('rto_reports.id', 'asc')
            ->select(
                'rto_reports.*',

                // Original Shopify order ID
                'orders.order_id as original_order_id',

                // Fields actually coming from orders
                'orders.client_id',
                'orders.city',
                'orders.state',
                'orders.pincode',
                'orders.father_name'
            )
            ->get();


        if ($rtoOrders->isEmpty()) {

            Log::info('NO PENDING RTO ORDERS', [
                'scheduler_id' => $scheduler->id,
            ]);

            return;
        }


        /*
    |--------------------------------------------------------------------------
    | Active Staff
    |--------------------------------------------------------------------------
    */

        $activeStaff = CallingUser::where('status', 1)
            ->orderBy('id', 'asc')
            ->get();


        if ($activeStaff->isEmpty()) {

            Log::warning('NO ACTIVE STAFF FOR RTO', [
                'scheduler_id' => $scheduler->id,
            ]);

            return;
        }


        $assigned = 0;


        foreach ($rtoOrders as $order) {

            /*
        |--------------------------------------------------------------------------
        | Find Original CallingOrder
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        | orders.order_id = original CallingOrder.order_id
        |
        */

            $originalCallingOrder = CallingOrder::query()
                ->where(
                    'order_id',
                    $order->order_id
                )
                ->whereNotNull('assigned_to')
                ->where(function ($q) {
                    $q->whereNull('order_source')
                        ->orWhereRaw(
                            'LOWER(order_source) != ?',
                            ['rto']
                        );
                })
                ->orderBy('id', 'desc')
                ->first();


            if (!$originalCallingOrder) {

                Log::warning(
                    'ORIGINAL CALLING ORDER NOT FOUND FOR RTO',
                    [
                        'scheduler_id' => $scheduler->id,
                        'original_order_id' =>
                        $order->original_order_id,
                    ]
                );

                continue;
            }


            /*
        |--------------------------------------------------------------------------
        | Original Staff
        |--------------------------------------------------------------------------
        */

            $originalStaffId =
                (int) $originalCallingOrder->assigned_to;


            /*
        |--------------------------------------------------------------------------
        | Check Original Staff
        |--------------------------------------------------------------------------
        */

            $originalStaff = CallingUser::find(
                $originalStaffId
            );


            if (
                $originalStaff &&
                (int) $originalStaff->status === 1
            ) {

                // Original staff active
                $assignTo = $originalStaffId;
            } else {

                /*
            |--------------------------------------------------------------------------
            | Original Staff Inactive
            | Find NEXT ACTIVE STAFF
            |--------------------------------------------------------------------------
            */

                $replacement = $activeStaff->first(
                    function ($staff) use ($originalStaffId) {

                        return (int) $staff->id >
                            $originalStaffId;
                    }
                );


                /*
            |--------------------------------------------------------------------------
            | If no next staff → first active staff
            |--------------------------------------------------------------------------
            */

                if (!$replacement) {
                    $replacement = $activeStaff->first();
                }


                if (!$replacement) {
                    continue;
                }


                $assignTo = (int) $replacement->id;
            }


            /*
        |--------------------------------------------------------------------------
        | Generate Calling Order ID
        |--------------------------------------------------------------------------
        */

            $staff = CallingUser::findOrFail($assignTo);

            $name = strtoupper(trim($staff->name));

            $prefix =
                substr($name, 0, 1) .
                substr($name, -1, 1);

            $date = now()->format('d-m-y');


            $lastSerial = CallingOrder::where(
                'assigned_to',
                $assignTo
            )
                ->whereDate(
                    'created_at',
                    today()
                )
                ->count();


            $lastSerial++;

            $callingOrderId =
                $prefix . '-' .
                $date . '-' .
                $lastSerial;


            /*
        |--------------------------------------------------------------------------
        | CREATE RTO CALLING ORDER
        |--------------------------------------------------------------------------
        */

            CallingOrder::create([

                'client_id' => $order->client_id,

                'order_id' => $callingOrderId,

                'order_date' => $order->order_date,

                'product_name' => $order->product,

                'quantity' => $order->quantity,

                'weight' => $order->weight,

                'customer_name' => $order->customer_name,

                'father_name' => $order->father_name,

                'city' => $order->city,

                'state' => $order->state,

                'pincode' => $order->pincode,

                'customer_phone' => $order->customer_phone,

                'shipping_address' =>
                $order->shipping_address,

                'payment_mode' =>
                $order->payment_mode,

                'amount' => $order->amount,

                'assigned_to' => $assignTo,

                'status' => 'pending',

                'order_source' => 'RTO',
            ]);


            /*
        |--------------------------------------------------------------------------
        | Mark RTO Report Exported
        |--------------------------------------------------------------------------
        */

            $updated = DB::table('rto_reports')
                ->where(
                    'order_id',
                    $order->original_order_id
                )
                ->where(
                    'is_exported',
                    0
                )
                ->update([
                    'is_exported' => 1,
                    'assign_staff' => $assignTo,
                    'assigndate' => now(),
                ]);


            if (!$updated) {

                Log::warning(
                    'RTO REPORT UPDATE FAILED',
                    [
                        'scheduler_id' => $scheduler->id,
                        'order_id' =>
                        $order->original_order_id,
                    ]
                );

                continue;
            }


            $assigned++;


            Log::info('RTO AUTO ASSIGNED', [

                'scheduler_id' =>
                $scheduler->id,

                'original_order_id' =>
                $order->original_order_id,

                'original_staff_id' =>
                $originalStaffId,

                'assigned_to' =>
                $assignTo,

                'replacement' =>
                $originalStaffId !== $assignTo,
            ]);
        }


        Log::info('RTO SCHEDULER SUMMARY', [

            'scheduler_id' =>
            $scheduler->id,

            'orders_found' =>
            $rtoOrders->count(),

            'orders_assigned' =>
            $assigned,
        ]);
    }
    private function selectStaff(
        OrderAssignmentScheduler $scheduler,
        array $staffAssignments,
        array $orderTypes
    ) {

        $bestStaff = null;

        $bestScore = PHP_FLOAT_MAX;


        foreach ($staffAssignments as $assignment) {

            $staffId = (int) (
                $assignment['staff_id'] ?? 0
            );

            $percentage = (float) (
                $assignment['percentage'] ?? 0
            );


            /*
            |--------------------------------------------------------------------------
            | INVALID ASSIGNMENT
            |--------------------------------------------------------------------------
            */

            if (
                $staffId <= 0 ||
                $percentage <= 0
            ) {

                Log::warning(
                    'INVALID STAFF ASSIGNMENT CONFIG',
                    [
                        'scheduler_id' => $scheduler->id,
                        'assignment' => $assignment,
                    ]
                );

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | CHECK STAFF ACTIVE
            |--------------------------------------------------------------------------
            */

            $staffExists = DB::table('calling_users')
                ->where(
                    'id',
                    $staffId
                )
                ->where(
                    'status',
                    1
                )
                ->exists();


            if (!$staffExists) {

                Log::warning('STAFF NOT ACTIVE', [
                    'scheduler_id' => $scheduler->id,
                    'staff_id' => $staffId,
                ]);

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | TODAY'S ASSIGNED ORDERS
            |--------------------------------------------------------------------------
            */

            $assignedQuery = Callingorder::query()
                ->where(
                    'client_id',
                    $scheduler->client_id
                )
                ->where(
                    'assigned_to',
                    $staffId
                )
                ->whereDate(
                    'created_at',
                    Carbon::now('Asia/Kolkata')->toDateString()
                );


            /*
            |--------------------------------------------------------------------------
            | APPLY SAME ORDER TYPE RULE
            |--------------------------------------------------------------------------
            */

            $assignedQuery->where(function ($q) use ($orderTypes) {

                $validTypeFound = false;


                foreach ($orderTypes as $index => $type) {

                    $type = strtolower(trim($type));


                    /*
                    |--------------------------------------------------------------------------
                    | SHOPIFY = NULL ONLY
                    |--------------------------------------------------------------------------
                    */

                    if ($type === 'shopify') {

                        $validTypeFound = true;

                        $condition = function ($query) {

                            $query->whereNull(
                                'order_source'
                            );
                        };
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | WEB
                    |--------------------------------------------------------------------------
                    */ elseif ($type === 'web') {

                        $validTypeFound = true;

                        $condition = function ($query) {

                            $query->whereRaw(
                                'LOWER(TRIM(order_source)) = ?',
                                ['web']
                            );
                        };
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | ABANDONED CHECKOUT
                    |--------------------------------------------------------------------------
                    */ elseif ($type === 'abandoned_checkout') {

                        $validTypeFound = true;

                        $condition = function ($query) {

                            $query->whereRaw(
                                'LOWER(TRIM(order_source)) = ?',
                                ['shopify_abandoned_checkout']
                            );
                        };
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | DELIVERED REORDER
                    |--------------------------------------------------------------------------
                    */ elseif ($type === 'deliveredreorder') {

                        $validTypeFound = true;

                        $condition = function ($query) {

                            $query->whereRaw(
                                'LOWER(TRIM(order_source)) = ?',
                                ['deliveredreorder']
                            );
                        };
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | RTO
                    |--------------------------------------------------------------------------
                    */ elseif ($type === 'rto') {

                        $validTypeFound = true;

                        $condition = function ($query) {

                            $query->whereRaw(
                                'LOWER(TRIM(order_source)) = ?',
                                ['rto']
                            );
                        };
                    } else {

                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | APPLY CONDITION
                    |--------------------------------------------------------------------------
                    */

                    if ($validTypeFound && $index === 0) {

                        $q->where($condition);
                    } elseif ($validTypeFound) {

                        $q->orWhere($condition);
                    }
                }
            });


            /*
            |--------------------------------------------------------------------------
            | ASSIGNED COUNT
            |--------------------------------------------------------------------------
            */

            $count = $assignedQuery->count();


            /*
            |--------------------------------------------------------------------------
            | DISTRIBUTION SCORE
            |--------------------------------------------------------------------------
            |
            | Example:
            |
            | Staff 1 = 100%
            | Staff 2 = 0%
            |
            | Staff with lower score gets next order.
            |
            */

            $score = $count / $percentage;


            Log::info('STAFF DISTRIBUTION CHECK', [
                'scheduler_id' => $scheduler->id,
                'staff_id' => $staffId,
                'percentage' => $percentage,
                'assigned_count_today' => $count,
                'score' => $score,
            ]);


            /*
            |--------------------------------------------------------------------------
            | SELECT LOWEST SCORE
            |--------------------------------------------------------------------------
            */

            if ($score < $bestScore) {

                $bestScore = $score;

                $bestStaff = $staffId;
            }
        }


        return $bestStaff;
    }
}
