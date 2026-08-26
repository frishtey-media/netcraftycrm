<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\callingorder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\KnowlarityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CallingOrderApiController extends Controller
{
    public function verifiedOrders()
    {
        try {

            $start = Carbon::today()->startOfDay();
            $end   = Carbon::today()->endOfDay();

            $orders = callingorder::where('client_id', 5)
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

    public function store(Request $request)
    {
        try {

            $data = $request->json()->all();

            if (empty($data)) {
                $data = $request->all();
            }

            Log::info('Knowlarity Log Push Received', [
                'data' => $data
            ]);

            /*
        |--------------------------------------------------------------------------
        | Required Field
        |--------------------------------------------------------------------------
        */

            if (empty($data['call_uuid'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'call_uuid is required'
                ], 422);
            }

            /*
        |--------------------------------------------------------------------------
        | Check Duplicate Call UUID
        |--------------------------------------------------------------------------
        */

            $existing = DB::table('knowlarity_log')
                ->where('call_uuid', $data['call_uuid'])
                ->first();

            if ($existing) {
                return response()->json([
                    'success' => true,
                    'message' => 'Call log already exists',
                    'call_uuid' => $existing->call_uuid,
                    'id' => $existing->id
                ], 200);
            }

            /*
        |--------------------------------------------------------------------------
        | Insert Call Log
        |--------------------------------------------------------------------------
        */

            $id = DB::table('knowlarity_log')->insertGetId([
                'call_date' => $data['call_date'] ?? null,
                'call_time' => $data['call_time'] ?? null,
                'caller_number' => $data['caller_number'] ?? null,
                'call_direction' => $data['call_direction'] ?? null,
                'called_number' => $data['called_number'] ?? null,
                'call_status' => $data['call_status'] ?? null,
                'agent_number' => $data['agent_number'] ?? null,
                'call_transfer_status' => $data['call_transfer_status'] ?? null,
                'caller_duration' => $data['caller_duration'] ?? null,
                'recording_url' => $data['recording_url'] ?? null,
                'call_uuid' => $data['call_uuid'],
                'hangup_cause' => $data['hangup_cause'] ?? null,
                'menu_extension' => $data['menu_extension'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            /*
        |--------------------------------------------------------------------------
        | Success Response
        |--------------------------------------------------------------------------
        */

            return response()->json([
                'success' => true,
                'message' => 'Call log received successfully',
                'call_uuid' => $data['call_uuid'],
                'id' => $id
            ], 200);
        } catch (\Throwable $e) {

            Log::error('Knowlarity Log Push Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request' => $request->all()
            ]);

            /*
        |--------------------------------------------------------------------------
        | Local Testing Response
        |--------------------------------------------------------------------------
        */

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
