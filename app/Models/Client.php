<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\callingorder;
use App\Models\OrderAssignmentScheduler;

class Client extends Model
{
    protected $table = 'clients';
    protected $casts = [
        'token_expires_at' => 'datetime',
        'token_updated_at' => 'datetime',
        'shopify_last_sync_at' => 'datetime',
    ];

    protected $fillable = [
        'client_name',
        'company_name',
        'mobile',
        'email',
        'address',
        'city',
        'state',
        'pincode',
        'shopify_store_url',

        'shopify_client_id',
        'shopify_client_secret',

        'shopify_access_token',

        'token_expires_at',
        'token_updated_at',

        'shopify_status',
        'shopify_last_error',
        'shopify_last_sync_at',
    ];

    public function products()
    {
        return $this->hasMany(ClientProduct::class);
    }
    public function orders()
    {
        return $this->hasMany(CallingOrder::class, 'client_id');
    }
    public function assignmentSchedulers()
    {
        return $this->hasMany(
            OrderAssignmentScheduler::class,
            'client_id'
        );
    }
    public function callingOrders()
    {
        return $this->hasMany(
            CallingOrder::class,
            'client_id'
        );
    }

    public function staffs()
    {
        return $this->belongsToMany(
            CallingUser::class,
            'client_staff',
            'client_id',
            'staff_id'
        );
    }
}
