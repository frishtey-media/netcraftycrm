<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\callingorder;
use App\Models\OrderAssignmentScheduler;

class Client extends Model
{
    protected $table = 'clients';


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
        'shopify_access_token',
        'phone_number_id',
        'whatsapp_number',
        'webhook_secret',
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
