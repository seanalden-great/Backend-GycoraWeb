<?php

// namespace App\Models;

// use Illuminate\Database\Eloquent\Model;

// class Review extends Model
// {
//     protected $fillable = [
//         'user_id', 'product_id', 'clinic_treatment_id', 'transaction_id',
//         'rating', 'comment', 'image_url'
//     ];

//     public function user() {
//         return $this->belongsTo(User::class);
//     }
// }

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id', 'product_id', 'clinic_treatment_id', 'transaction_id',
        'rating', 'comment', 'image_url'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    // Ttambahkan relasi ke Product
    public function product() {
        return $this->belongsTo(Product::class);
    }
}
