<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function globalSearch(Request $request)
    {
        $q = $request->query('q');
        $time = $request->query('time', 'all');

        // Pengecekan User secara manual (tanpa melempar error 401 jika guest)
        $user = auth('sanctum')->user();

        // Jika query pencarian kosong, langsung kembalikan array kosong
        if (empty(trim($q))) {
            return response()->json([
                'products' => [],
                'transactions' => [],
                'carts' => []
            ]);
        }

        // 1. Logika filter waktu menggunakan Carbon
        $dateQuery = null;
        switch ($time) {
            case '7d':
                $dateQuery = now()->subDays(7);
                break;
            case '30d':
                $dateQuery = now()->subDays(30);
                break;
            case '90d':
                $dateQuery = now()->subDays(90);
                break;
            case 'all':
            default:
                $dateQuery = null;
                break;
        }

        // 2. Cari Produk (Bisa diakses Public / Guest)
        // Pencarian dibungkus closure function($query) agar kondisi 'status' tidak tertembus oleh 'orWhere'
        $products = Product::where('status', 'active')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('sku', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get();

        // 3. Inisialisasi array kosong untuk data yang butuh autentikasi
        $transactions = [];
        $carts = [];

        // 4. Cari Transaksi & Keranjang (HANYA jika user terdeteksi login)
        if ($user) {

            // --- A. Pencarian Transaksi ---
            $trxQuery = Transaction::where('user_id', $user->id)
                ->where(function ($query) use ($q) {
                    // Bisa mencari berdasarkan Nomor Invoice (order_id) atau Status (pending, paid, dll)
                    $query->where('order_id', 'like', "%{$q}%")
                          ->orWhere('status', 'like', "%{$q}%");
                });

            // Terapkan filter waktu HANYA pada transaksi
            if ($dateQuery) {
                $trxQuery->where('created_at', '>=', $dateQuery);
            }

            // Ambil 5 data transaksi terbaru yang cocok
            $transactions = $trxQuery->latest()->limit(5)->get();

            // --- B. Pencarian Keranjang ---
            // Mencari nama produk yang ada di dalam keranjang user saat ini
            $carts = Cart::with('product')
                ->where('user_id', $user->id)
                ->whereHas('product', function ($query) use ($q) {
                    $query->where('name', 'like', "%{$q}%");
                })
                ->latest()
                ->limit(5)
                ->get();
        }

        // 5. Kembalikan Response JSON dengan struktur yang diharapkan oleh Frontend
        return response()->json([
            'products' => $products,
            'transactions' => $transactions,
            'carts' => $carts
        ]);
    }
}
