<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'start_date',
        'end_date',
        'description',
        'image_url',
        'link_url',
        'is_active'
    ];

    // Otomatis mengubah string tanggal dari DB menjadi objek Carbon/Date
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    // --- HELPER SCOPES (Mempermudah Query di Controller) ---

    // Scope untuk mencari event yang masih berlangsung atau di masa depan
    public function scopeUpcoming($query)
    {
        return $query->where('is_active', true)
                     ->whereDate('end_date', '>=', Carbon::today())
                     ->orderBy('start_date', 'asc');
    }

    // Scope untuk mencari event yang sudah selesai
    public function scopePast($query)
    {
        return $query->where('is_active', true)
                     ->whereDate('end_date', '<', Carbon::today())
                     ->orderBy('start_date', 'desc');
    }
}
