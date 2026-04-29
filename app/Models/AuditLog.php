<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    // Matikan updated_at jika Anda hanya ingin menyimpan created_at
    // const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address'
    ];

    // Pastikan Laravel membaca kolom ini sebagai Array/JSON otomatis
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // Relasi ke pembuat log (Admin/Staf)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
