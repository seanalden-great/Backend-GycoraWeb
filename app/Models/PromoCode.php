<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    protected $fillable = [
        'code',
        'discount_value',
        'max_uses',
        'times_used',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
