<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderAssignmentScheduler extends Model
{
    protected $table = 'order_assignment_schedulers';

    protected $fillable = [
        'client_id',
        'order_types',
        'start_time',
        'end_time',
        'days',
        'staff_assignments',
        'is_active',
    ];

    protected $casts = [
        'order_types' => 'array',
        'days' => 'array',
        'staff_assignments' => 'array',
        'is_active' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
