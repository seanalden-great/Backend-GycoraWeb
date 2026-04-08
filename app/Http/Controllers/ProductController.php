<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

// Import class Intervention Image v3
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProductController extends Controller
{
    // 1. MEMBACA DATA (BACA DARI REDIS CACHE, JIKA KOSONG BACA DARI MYSQL)
    // =========================================================================
    // public function index()
    // {
    //     // Menyimpan data di RAM (Redis) selama 1 Hari (86400 detik) dengan label 'catalog'
    //     $products = Cache::tags(['catalog'])->remember('products.active', 86400, function () {
    //         return Product::with('category')
    //             // ->withSum(['transactionDetails' => function ($query) {
    //             //     $query->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
    //             //           ->whereIn('transactions.status', ['completed']);
    //             // }], 'quantity')
    //             // ->where('status', 'active') // Gycora tidak ada field status di Model, tapi saya biarkan jika di DB ada
    //             ->latest()
    //             ->get();
    //     });

    //     // $products->map(function ($product) {
    //     //     $product->total_sold = (int) $product->transaction_details_sum_quantity ?? 0;
    //     //     unset($product->transaction_details_sum_quantity);
    //     //     return $product;
    //     // });

    //     return response()->json($products, 200);
    // }

    public function index()
    {
        try {
            // Gunakan metode pengambilan langsung (tanpa Cache/Redis) untuk debugging
            $products = Product::with('category')
                ->latest()
                ->get();

            return response()->json($products, 200);

        } catch (\Exception $e) {
            // Jika ada error dari database, kembalikan JSON, bukan halaman HTML
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function inactiveProducts()
    {
        $products = Cache::tags(['catalog'])->remember('products.inactive', 86400, function () {
            return Product::with('category')
                // ->where('status', 'inactive') // Gycora tidak ada field status di Model
                ->latest()
                ->get();
        });

        return response()->json($products, 200);
    }

    // public function show($id)
    // {
    //     // Cache per produk berdasarkan ID-nya
    //     $product = Cache::tags(['catalog'])->remember("products.detail.{$id}", 86400, function () use ($id) {
    //         return Product::with(['category', 'stocks' => function ($q) {
    //             $q->orderBy('created_at', 'asc');
    //         }])->findOrFail($id);
    //     });

    //     return response()->json($product, 200);
    // }

    public function show($id)
    {
        try {
            // Pengambilan langsung tanpa Cache untuk mencegah error Redis Tags
            $product = Product::with(['category', 'stocks' => function ($q) {
                $q->orderBy('created_at', 'asc');
            }])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $product
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Tangani error jika ID tidak ditemukan dengan anggun
            return response()->json([
                'status' => 'error',
                'message' => 'Produk tidak ditemukan.'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage() // Ini akan menampilkan pesan error aslinya di response API
            ], 500);
        }
    }

    public function store(Request $request)
    {
        // 1. Validasi Field Gycora
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'sku' => 'required|unique:products',
            'name' => 'required|string|max:255',
            // slug bisa di-generate otomatis di Model atau Controller
            'description' => 'nullable|string',
            'benefits' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image', // Menggunakan input file 'image' dari frontend
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
            // Ambil semua data selain file gambar
            $data = $request->except(['image']);

            // Generate Slug Otomatis dari nama jika tidak dikirim dari frontend
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']) . '-' . Str::random(5);
            }

            // 1. Upload & Optimasi Gambar Utama
            if ($request->hasFile('image')) {
                // Helper function mengembalikan path relatif. Kita masukkan ke 'image_url' sesuai Model Gycora
                $data['image_url'] = $this->optimizeAndSaveImage($request->file('image'), 'products');
            }

            // Simpan ke DB
            $product = Product::create($data);

            // Buat batch stok pertama kali
            if ($request->stock > 0) {
                $batchCode = 'STK-'.now()->format('YmdHis').'-'.strtoupper(Str::random(4));
                ProductStock::create([
                    'product_id' => $product->id,
                    'batch_code' => $batchCode,
                    'quantity' => $request->stock,
                    'initial_quantity' => $request->stock,
                ]);
            }

            DB::commit();

            // Bersihkan Cache agar Katalog di Web User langsung ter-update!
            Cache::tags(['catalog'])->flush();

            return response()->json($product, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'sku' => "required|unique:products,sku,$id", // SKU harus unique kecuali untuk produk ini sendiri
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'benefits' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Jangan izinkan update stok dari endpoint ini (Stok diupdate lewat ProductStockController)
        $data = $request->except(['image', 'stock', '_method']);

        // Jika nama diubah, ubah slug-nya juga
        if ($request->has('name') && $request->name !== $product->name) {
            $data['slug'] = Str::slug($data['name']) . '-' . Str::random(5);
        }

        // 1. Hapus & Ganti Gambar Utama
        if ($request->hasFile('image')) {
            if ($product->image_url) {
                $oldPath = str_replace('/storage/', '', $product->image_url);
                Storage::disk('public')->delete($oldPath);
            }
            // Gunakan fungsi helper untuk kompresi dan set ke field 'image_url'
            $data['image_url'] = $this->optimizeAndSaveImage($request->file('image'), 'products');
        }

        $product->update($data);

        Cache::tags(['catalog'])->flush();

        return response()->json($product, 200);
    }


    public function destroy($id)
    {
        // Gycora tidak memiliki field 'status' = 'inactive' berdasarkan Model yang Anda berikan.
        // Jika Anda ingin soft delete, pastikan tabel `products` memiliki kolom `deleted_at` (SoftDeletes).
        // Untuk sekarang, saya asumsikan ini melakukan hard delete atau Anda perlu menambahkan trait SoftDeletes di Model.

        $product = Product::findOrFail($id);

        // Hapus file gambar jika ada
        if ($product->image_url) {
            $oldPath = str_replace('/storage/', '', $product->image_url);
            Storage::disk('public')->delete($oldPath);
        }

        $product->delete();

        // Bersihkan Cache!
        Cache::tags(['catalog'])->flush();

        return response()->json(['message' => 'Product deleted'], 200);
    }

    public function restore($id)
    {
        // Fungsi ini hanya bekerja jika Anda menggunakan SoftDeletes di model Product
        $product = Product::withTrashed()->findOrFail($id);
        $product->restore();

        Cache::tags(['catalog'])->flush();

        return response()->json(['message' => 'Product activated/restored'], 200);
    }

    public function forceDelete($id)
    {
        // Gunakan withTrashed() jika menggunakan SoftDeletes
        $product = Product::withTrashed()->findOrFail($id);

        if ($product->image_url) {
            $oldPath = str_replace('/storage/', '', $product->image_url);
            Storage::disk('public')->delete($oldPath);
        }

        try {
            $product->forceDelete(); // Menghapus permanen dari DB

            Cache::tags(['catalog'])->flush();

            return response()->json(['message' => 'Product deleted permanently'], 200);

        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json(['message' => 'Produk tidak bisa dihapus karena sudah memiliki riwayat transaksi'], 422);
        }
    }

    /**
     * [BARU] FUNGSI HELPER UNTUK OPTIMASI GAMBAR
     * Fungsi ini akan me-resize gambar dan mengonversinya ke WebP
     */
    private function optimizeAndSaveImage($file, $folder)
    {
        // 1. Inisialisasi Image Manager dengan driver GD
        $manager = new ImageManager(new Driver());

        // 2. Baca file yang diunggah
        $image = $manager->read($file);

        // 3. Scale Down: Maksimal lebar 1000px.
        $image->scaleDown(width: 1000);

        // 4. Encode ke format WebP dengan kualitas 80%
        $encoded = $image->toWebp(80);

        // 5. Buat nama file unik dengan ekstensi .webp
        $filename = $folder . '/' . Str::random(40) . '.webp';

        // 6. Simpan ke Local Storage (disk public)
        Storage::disk('public')->put($filename, $encoded->toString());

        // 7. Kembalikan path relatifnya
        return '/storage/' . $filename;
    }
}
