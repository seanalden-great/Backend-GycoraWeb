<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'region',
        'first_name_address',
        'last_name_address',
        'address_location',
        'location_type',
        'city',
        'province',
        'postal_code',
        'latitude',
        'longitude',
        'is_default'
    ];

    // Casting tipe data secara otomatis (meniru behavior Golang)
    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Relasi ke model User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
