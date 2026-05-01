<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table = 'clients';


    protected $fillable = [
        'client_name',
        'shopify_store_url',
        'company_name',
        'mobile',
        'email',
        'address',
        'city',
        'state',
        'pincode',
    ];

    public function products()
    {
        return $this->hasMany(ClientProduct::class);
    }
    public function orders()
    {
        return $this->hasMany(CallingOrder::class, 'client_id');
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
