<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicAppointment extends Model
{
    use HasFactory;

    // Kolom-kolom ini wajib diizinkan untuk diisi (Mass Assignment)
    protected $fillable = [
        'email',
        'clinic_treatment_id',
        'appointment_time',
        'reason',
    ];

    // Karena di Controller Anda melakukan ->with('treatment'),
    // Anda WAJIB membuat relasi ini.
    public function treatment()
    {
        return $this->belongsTo(ClinicTreatment::class, 'clinic_treatment_id');
    }
}
