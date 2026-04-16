<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationLog extends Model
{
    use HasFactory;

    // Kolom-kolom ini wajib diizinkan untuk diisi (Mass Assignment)
    protected $fillable = [
        'email',
        'category_title',
        'consultation_type',
        'notes',
    ];
}
