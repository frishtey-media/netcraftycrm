<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CallRecordingController extends Controller
{
    public function start(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string'],
            'customer_phone' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $phone = preg_replace(
            '/\D+/',
            '',
            $validated['customer_phone']
        );

        $phone = substr($phone, -10);

        $id = DB::table('calling_logs')->insertGetId([
            'order_id' => $validated['order_id'],
            'staff_id' => $user->id,
            'customer_phone' => $phone,
            'call_status' => 'started',
            'called_at' => now(),
            'call_started_at' => now(),
            'recording_status' => 'waiting',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Call started.',
            'data' => [
                'call_log_id' => $id,
            ],
        ]);
    }

    public function complete(Request $request)
    {
        $validated = $request->validate([
            'call_log_id' => ['required', 'integer'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $log = DB::table('calling_logs')
            ->where('id', $validated['call_log_id'])
            ->where('staff_id', $user->id)
            ->first();

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Call log not found.',
            ], 404);
        }

        DB::table('calling_logs')
            ->where('id', $log->id)
            ->update([
                'call_status' => 'completed',
                'call_ended_at' => now(),
                'duration_seconds' =>
                $validated['duration_seconds'] ?? null,
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Call completed.',
        ]);
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'call_log_id' => ['required', 'integer'],
            'recording' => [
                'required',
                'file',
                'mimes:mp3,m4a,wav,amr,3gp,aac,ogg',
                'max:51200',
            ],
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $log = DB::table('calling_logs')
            ->where('id', $validated['call_log_id'])
            ->where('staff_id', $user->id)
            ->first();

        if (!$log) {
            return response()->json([
                'success' => false,
                'message' => 'Call log not found.',
            ], 404);
        }

        $datePath = now()->format('Y/m/d');

        $filename =
            $log->order_id .
            '_' .
            $log->id .
            '_' .
            time() .
            '.' .
            $request->file('recording')->getClientOriginalExtension();

        $path = $request
            ->file('recording')
            ->storeAs(
                "call-recordings/{$datePath}",
                $filename,
                'local'
            );

        DB::table('calling_logs')
            ->where('id', $log->id)
            ->update([
                'recording_path' => $path,
                'recording_status' => 'uploaded',
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Recording uploaded successfully.',
            'data' => [
                'call_log_id' => $log->id,
                'recording_path' => $path,
            ],
        ]);
    }

    public function recording($id, Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        $log = DB::table('calling_logs')
            ->where('id', $id)
            ->where('staff_id', $user->id)
            ->first();

        if (!$log || !$log->recording_path) {
            return response()->json([
                'success' => false,
                'message' => 'Recording not found.',
            ], 404);
        }

        if (!Storage::disk('local')->exists($log->recording_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Recording file not found.',
            ], 404);
        }

        return Storage::disk('local')
            ->download($log->recording_path);
    }
}
