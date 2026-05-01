<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'client_id',
        'assigned_to',
        'customer_phone',
        'last_message',
        'last_message_at',
        'status'
    ];

    // 🔥 RELATIONS

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function staff()
    {
        return $this->belongsTo(CallingUser::class, 'assigned_to');
    }
}
