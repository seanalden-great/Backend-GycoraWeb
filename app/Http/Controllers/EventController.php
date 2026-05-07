<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    /**
     * PUBLIC API: Mengambil semua event, dipisah antara Upcoming dan Past
     */
    public function index()
    {
        try {
            // Menggunakan helper scope dari Model
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
            'image_url' => 'nullable|string',
            'link_url' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $event = Event::create($request->all());

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
            'image_url' => 'nullable|string',
            'link_url' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $event->update($request->all());

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

        $event->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Event berhasil dihapus.'
        ], 200);
    }
}
