<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CallnumberIssue extends Model
{
    protected $fillable = [
        'callnumber',
        'staff_name',
        'client_name',
        'issued_at',
        'remarks'
    ];
}
