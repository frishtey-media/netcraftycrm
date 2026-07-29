<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\callingorder;
use Carbon\Carbon;

class CallingOrderApiController extends Controller
{
    public function verifiedOrders()
    {
        try {

            $start = Carbon::today()->startOfDay();
            $end   = Carbon::today()->endOfDay();

            $orders = CallingOrder::where('client_id', 5)
                ->where('status', 'verified')
                ->whereBetween('updated_at', [$start, $end])
                ->orderBy('updated_at', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'date' => Carbon::today()->format('Y-m-d'),
                'client_id' => 5,
                'status' => 'verified',
                'total' => $orders->count(),
                'data' => $orders
            ], 200);
        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
