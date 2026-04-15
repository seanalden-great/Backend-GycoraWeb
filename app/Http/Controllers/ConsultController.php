<?php

namespace App\Http\Controllers;

use App\Models\ClinicTreatment;
use App\Models\Product;
use Illuminate\Http\Request;
// use App\Models\Faq; // Buat model ini jika ingin FAQ dinamis

class ConsultController extends Controller
{
    // =========================================================================
    // ENDPOINT PUBLIK (UNTUK HALAMAN ConsultWithUs)
    // =========================================================================
    public function getConsultPageData()
    {
        // Ambil Treatments aktif
        $treatments = ClinicTreatment::where('is_active', true)->get();

        // Ambil produk OTC (misalnya produk yang tidak butuh resep, kita batasi 4)
        $otcProducts = Product::where('status', 'active')
            ->where('category_id', 2) // Sesuaikan dengan ID kategori 'Essentials/OTC' Anda
            ->limit(4)
            ->get();

        // Dummy FAQ dari Backend (Bisa diganti dari DB)
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
    // ENDPOINT ADMIN (MANAJEMEN CLINIC TREATMENTS)
    // =========================================================================

    // 1. Ambil semua data (Termasuk yang inactive)
    public function indexAdmin(Request $request)
    {
        // Jika hanya admin yang boleh mengakses, idealnya ada pengecekan role/permission di sini.
        // if ($request->user()->role !== 'admin') abort(403);

        $treatments = ClinicTreatment::orderBy('created_at', 'desc')->get();
        return response()->json($treatments);
    }

    // 2. Simpan data baru
    public function storeAdmin(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'image_url' => 'nullable|string', // Bisa menampung path lokal atau URL
            'is_active' => 'boolean',
        ]);

        $treatment = ClinicTreatment::create([
            'title' => $validatedData['title'],
            'price' => $validatedData['price'],
            'image_url' => $validatedData['image_url'] ?? null,
            'is_active' => $validatedData['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Clinic Treatment berhasil ditambahkan.',
            'data' => $treatment
        ], 201);
    }

    // 3. Ambil 1 data spesifik
    public function showAdmin($id)
    {
        $treatment = ClinicTreatment::findOrFail($id);
        return response()->json($treatment);
    }

    // 4. Update data
    public function updateAdmin(Request $request, $id)
    {
        $treatment = ClinicTreatment::findOrFail($id);

        $validatedData = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'image_url' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $treatment->update($validatedData);

        return response()->json([
            'message' => 'Clinic Treatment berhasil diperbarui.',
            'data' => $treatment
        ]);
    }

    // 5. Hapus data (Hard delete)
    public function destroyAdmin($id)
    {
        $treatment = ClinicTreatment::findOrFail($id);
        $treatment->delete();

        return response()->json([
            'message' => 'Clinic Treatment berhasil dihapus.'
        ]);
    }
}
