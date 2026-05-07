<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage; // [BARU] Tambahkan facade Storage

class EventController extends Controller
{
    /**
     * PUBLIC API: Mengambil semua event, dipisah antara Upcoming dan Past
     */
    public function index()
    {
        try {
            $upcomingEvents = Event::upcoming()->get();
            $pastEvents = Event::past()->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'upcoming' => $upcomingEvents,
                    'past' => $pastEvents
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat data event.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ADMIN API: Menyimpan event baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'required|string',
            // [PERBAIKAN] Validasi diubah untuk menerima file gambar, max 5MB
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'link_url' => 'nullable|string',
            'is_active' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $data = $request->except('image_url');

        // [BARU] Logika Upload File ke Clever Cloud (Cellar / S3)
        if ($request->hasFile('image_url')) {
            // 's3' merujuk pada konfigurasi filesystem Clever Cloud Cellar di .env
            $path = $request->file('image_url')->store('events', 's3');
            $data['image_url'] = Storage::disk('s3')->url($path);
        }

        // Konversi is_active dari string "1"/"0" ke boolean untuk DB
        $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);

        $event = Event::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Event berhasil ditambahkan.',
            'data' => $event
        ], 201);
    }

    /**
     * ADMIN API: Update event
     */
    public function update(Request $request, $id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json(['status' => 'error', 'message' => 'Event tidak ditemukan.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'location' => 'sometimes|required|string|max:255',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'description' => 'sometimes|required|string',
            'image_url' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'link_url' => 'nullable|string',
            'is_active' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $data = $request->except('image_url');

        // [BARU] Logika Upload File jika ada gambar baru yang dikirim
        if ($request->hasFile('image_url')) {
            // Hapus gambar lama dari storage jika perlu
            // if ($event->image_url) { ... }

            $path = $request->file('image_url')->store('events', 's3');
            $data['image_url'] = Storage::disk('s3')->url($path);
        }

        if ($request->has('is_active')) {
            $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
        }

        $event->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Event berhasil diperbarui.',
            'data' => $event
        ], 200);
    }

    /**
     * ADMIN API: Hapus event
     */
    public function destroy($id)
    {
        $event = Event::find($id);

        if (!$event) {
            return response()->json(['status' => 'error', 'message' => 'Event tidak ditemukan.'], 404);
        }

        // Opsional: Hapus file dari Clever Cloud saat data dihapus
        // if ($event->image_url) {
        //     $path = str_replace(Storage::disk('s3')->url(''), '', $event->image_url);
        //     Storage::disk('s3')->delete($path);
        // }

        $event->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Event berhasil dihapus.'
        ], 200);
    }
}
