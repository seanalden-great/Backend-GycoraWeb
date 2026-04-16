<?php

namespace App\Http\Controllers;

use App\Models\ClinicTreatment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ConsultController extends Controller
{
    // =========================================================================
    // ENDPOINT PUBLIK
    // =========================================================================
    public function getConsultPageData()
    {
        $treatments = ClinicTreatment::where('is_active', true)->get();

        $otcProducts = Product::where('status', 'active')
            ->where('category_id', 2)
            ->limit(4)
            ->get();

        $faqs = [
            ["q" => "Apa itu Gycora Care?", "a" => "Gycora Care adalah klinik kesehatan kulit dan rambut terdepan."],
            ["q" => "Apakah produk dari Gycora aman?", "a" => "Ya, seluruh produk kami diformulasikan oleh tim dokter ahli."],
        ];

        return response()->json([
            'treatments' => $treatments,
            'otc_products' => $otcProducts,
            'faqs' => $faqs
        ]);
    }

    // =========================================================================
    // ENDPOINT ADMIN
    // =========================================================================

    public function indexAdmin(Request $request)
    {
        $treatments = ClinicTreatment::orderBy('created_at', 'desc')->get();
        return response()->json($treatments);
    }

    public function storeAdmin(Request $request)
    {
        // 1. Validasi input, pastikan 'image' adalah file gambar
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Maksimal 5MB
            'is_active' => 'boolean',
        ]);

        $imageUrl = null;

        // 2. Logika Upload ke S3
        if ($request->hasFile('image')) {
            $file = $request->file('image');

            // Simpan file ke folder 'treatments' di bucket S3.
            // Gunakan visibility 'public' jika file ingin bisa diakses langsung via URL.
            $path = Storage::disk('s3')->put('treatments', $file, 'public');

            // Dapatkan URL publik dari S3 untuk disimpan ke database
            $imageUrl = Storage::disk('s3')->url($path);
        }

        // 3. Simpan ke Database
        $treatment = ClinicTreatment::create([
            'title' => $validatedData['title'],
            'price' => $validatedData['price'],
            'image_url' => $imageUrl,
            'is_active' => $validatedData['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Clinic Treatment berhasil ditambahkan.',
            'data' => $treatment
        ], 201);
    }

    public function showAdmin($id)
    {
        $treatment = ClinicTreatment::findOrFail($id);
        return response()->json($treatment);
    }

    public function updateAdmin(Request $request, $id)
    {
        $treatment = ClinicTreatment::findOrFail($id);

        $validatedData = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'is_active' => 'boolean',
        ]);

        // Cek jika admin mengunggah gambar baru
        if ($request->hasFile('image')) {
            // Opsional: Hapus gambar lama dari S3 untuk menghemat storage
            if ($treatment->image_url) {
                // Ekstrak path (key) dari URL untuk dihapus
                $oldPath = parse_url($treatment->image_url, PHP_URL_PATH);
                $oldPath = ltrim($oldPath, '/');
                Storage::disk('s3')->delete($oldPath);
            }

            $file = $request->file('image');
            $path = Storage::disk('s3')->put('treatments', $file, 'public');
            $validatedData['image_url'] = Storage::disk('s3')->url($path);
        }

        // Hapus key 'image' dari array karena kolom di DB adalah 'image_url'
        unset($validatedData['image']);

        $treatment->update($validatedData);

        return response()->json([
            'message' => 'Clinic Treatment berhasil diperbarui.',
            'data' => $treatment
        ]);
    }

    public function destroyAdmin($id)
    {
        $treatment = ClinicTreatment::findOrFail($id);

        // Opsional: Hapus gambar dari S3 sebelum menghapus record dari DB
        if ($treatment->image_url) {
            $path = parse_url($treatment->image_url, PHP_URL_PATH);
            $path = ltrim($path, '/');
            Storage::disk('s3')->delete($path);
        }

        $treatment->delete();

        return response()->json([
            'message' => 'Clinic Treatment berhasil dihapus.'
        ]);
    }
}
