<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffCallRecording extends Model
{
    protected $table = 'staff_call_recordings';

    protected $fillable = [
        'staff_id',
        'device_phone',
        'customer_phone',
        'call_type',
        'call_started_at',
        'duration_seconds',
        'device_record_id',
        'recording_path',
        'recording_mime',
        'recording_size',
        'upload_status',
        'upload_error',
        'customer_name',
    ];

    protected function casts(): array
    {
        return [
            'call_started_at' => 'datetime',
            'duration_seconds' => 'integer',
            'recording_size' => 'integer',
        ];
    }
}
