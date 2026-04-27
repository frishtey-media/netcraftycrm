<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\callingorder;

class CallingUser extends Authenticatable
{
    protected $table = 'calling_users';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password'];
    public function orders()
    {
        return $this->hasMany(CallingOrder::class, 'assigned_to');
    }
}
