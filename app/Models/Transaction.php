<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'client_reference',
        'hubtel_transaction_id',
        'account_number',
        'service_type',
        'amount',
        'customer_name',
        'mobile_number',
        'email',
        'status',
        'response_data',
        'completed_at',
    ];

    protected $casts = [
        'response_data' => 'array',
        'completed_at' => 'datetime',
        'amount' => 'decimal:2',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
