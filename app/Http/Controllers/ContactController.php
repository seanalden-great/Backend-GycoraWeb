<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * POST: Kirim Pesan Contact Us
     */
    public function submit(Request $request)
    {
        // 1. Validasi input (hanya description yang wajib dikirim frontend)
        $validated = $request->validate([
            'description' => 'required|string',
        ], [
            'description.required' => 'Pesan tidak boleh kosong.',
        ]);

        // 2. Ambil data user yang sedang login via token Sanctum
        // Ini otomatis mengembalikan model User utuh, pengganti query SELECT manual di Golang
        $user = $request->user();

        // 3. Simpan ke tabel contacts menggunakan Mass Assignment Eloquent
        Contact::create([
            'user_id'     => $user->id,
            'name'        => trim($user->first_name . ' ' . $user->last_name),
            'email'       => $user->email,
            'phone'       => $user->phone ?? '', // Fallback ke string kosong jika phone bernilai null
            'description' => $validated['description'],
            'is_read'     => false, // Set default 0 (belum dibaca)
        ]);

        // 4. Return JSON response
        return response()->json([
            'message' => 'Pesan Anda berhasil dikirim. Tim kami akan segera merespons.',
        ], 201);
    }
}
