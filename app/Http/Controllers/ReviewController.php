<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReviewController extends Controller
{
    // Mengambil ulasan untuk halaman Product Detail
    public function getProductReviews($productId)
    {
        $reviews = Review::with('user:id,first_name,last_name,profile_image')
            ->where('product_id', $productId)
            ->latest()
            ->get();

        // Hitung rata-rata rating
        $average = $reviews->avg('rating');
        $total = $reviews->count();

        return response()->json([
            'reviews' => $reviews,
            'average_rating' => round($average, 1),
            'total_reviews' => $total
        ]);
    }

    // User mengirimkan ulasan
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'transaction_id' => 'required',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120', // Maks 5MB
        ]);

        $user = auth()->user();

        // Cek apakah user sudah pernah mereview produk ini di transaksi ini
        $existingReview = Review::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->where('transaction_id', $request->transaction_id)
            ->first();

        if ($existingReview) {
            return response()->json(['message' => 'Anda sudah memberikan ulasan untuk produk ini.'], 400);
        }

        $imageUrl = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = Storage::disk('s3')->put('reviews', $file, 'public');
            $imageUrl = Storage::disk('s3')->url($path);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $request->product_id,
            'transaction_id' => $request->transaction_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'image_url' => $imageUrl,
        ]);

        return response()->json(['message' => 'Ulasan berhasil dikirim!', 'data' => $review], 201);
    }
}
