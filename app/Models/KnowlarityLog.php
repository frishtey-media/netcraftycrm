<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KnowlarityLog extends Model
{
    protected $table = 'knowlarity_log';

    protected $fillable = [
        'call_date',
        'call_time',
        'caller_number',
        'call_direction',
        'called_number',
        'call_status',
        'agent_number',
        'call_transfer_status',
        'caller_duration',
        'recording_url',
        'call_uuid',
        'hangup_cause',
        'menu_extension',
    ];

    protected $casts = [
        'call_date' => 'date',
        'call_time' => 'datetime:H:i:s',
    ];
}
