<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffCallRecording;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StaffCallRecordingController extends Controller
{

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_id'          => 'required|integer',
            'device_phone'      => 'nullable|string|max:30',
            'customer_phone' => 'nullable|string|max:30',
            'customer_name'  => 'nullable|string|max:191',
            'call_type'         => 'required|in:incoming,outgoing,missed,unknown',
            'call_started_at'   => 'nullable|date',
            'duration_seconds'  => 'nullable|integer|min:0',
            'device_record_id'  => 'required|string|max:191',
            'recording'         => 'nullable|file|mimes:mp3,m4a,mp4,wav,amr,3gp,aac,ogg,opus|max:51200',
        ]);

        $staffId = (int) $validated['staff_id'];
        $uploadDate = now();

        $customerPhone = !empty($validated['customer_phone'])
            ? substr(preg_replace('/\D+/', '', $validated['customer_phone']), -10)
            : null;

        $customerName = !empty($validated['customer_name'])
            ? trim($validated['customer_name'])
            : null;

        $relativePath = null;
        $mimeType = null;
        $fileSize = null;

        if ($request->hasFile('recording')) {
            $file = $request->file('recording');

            $extension = strtolower(
                $file->getClientOriginalExtension() ?: 'mp3'
            );

            $folder = sprintf(
                'call-recordings/%s/staff_%d',
                $uploadDate->format('Y/m/d'),
                $staffId
            );

            $filename = Str::slug(
                pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
            );

            $filename = ($filename ?: 'recording') . '_' .
                $uploadDate->format('His') . '_' .
                Str::lower(Str::random(6)) . '.' . $extension;

            $relativePath = $file->storeAs(
                $folder,
                $filename,
                'local'
            );

            $mimeType = $file->getMimeType();
            $fileSize = $file->getSize();
        }

        $record = StaffCallRecording::create([
            'staff_id'         => $staffId,
            'device_phone'     => $validated['device_phone'] ?? null,
            'customer_phone'   => $customerPhone,
            'customer_name'    => $customerName,
            'call_type'        => $validated['call_type'],
            'call_started_at'  => $validated['call_started_at'] ?? null,
            'duration_seconds' => $validated['duration_seconds'] ?? null,
            'device_record_id' => $validated['device_record_id'],
            'recording_path'   => $relativePath,
            'recording_mime'   => $mimeType,
            'recording_size'   => $fileSize,
            'upload_status'    => $relativePath ? 'uploaded' : 'metadata_only',
            'upload_error'     => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Recording uploaded successfully.',
            'data' => $record,
        ], 201);
    }

    private function attachRecording(Request $request, StaffCallRecording $record)
    {
        $request->validate([
            'recording' => [
                'required',
                'file',
                'max:51200',
                'mimetypes:audio/mpeg,audio/mp4,audio/x-m4a,audio/wav,audio/x-wav,audio/3gpp,audio/amr,application/octet-stream',
            ],
        ]);

        $file = $request->file('recording');
        $path = $file->store('call-recordings/' . now()->format('Y/m'), 'local');

        if (!$path) {
            return response()->json(['message' => 'Could not store recording.'], 500);
        }

        $record->update([
            'recording_path' => $path,
            'recording_mime' => $file->getMimeType(),
            'recording_size' => $file->getSize(),
            'upload_status' => 'uploaded',
            'upload_error' => null,
        ]);

        return response()->json([
            'success' => true,
            'recording_id' => $record->id,
            'upload_status' => $record->upload_status,
        ]);
    }

    public function index(Request $request)
    {
        // Authenticated user's own rows only. Replace this with a dedicated
        // CRM-admin policy if the CRM needs a cross-staff recording dashboard.
        $query = StaffCallRecording::query();

        // Until staff auth is mapped to staff_id, do not expose all records.
        abort_unless($request->user()->can('viewAny', StaffCallRecording::class), 403);

        return response()->json($query->latest()->paginate(25));
    }
}
