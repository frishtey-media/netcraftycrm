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
    public function clients()
    {
        return $this->belongsToMany(
            Client::class,
            'client_staff',
            'staff_id',
            'client_id'
        );
    }
}
