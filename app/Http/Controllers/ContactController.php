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

    public function subscribe(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'email' => 'required|email:rfc,dns'
        ], [
            'email.dns' => 'The email domain does not seem to be valid or active.'
        ]);

        $email = $request->email;

        // Cek apakah ini email dari user yang sudah terdaftar di web kita
        $user = \App\Models\User::where('email', $email)->first();
        $isRegistered = $user ? true : false;

        $subscriber = \App\Models\Subscriber::where('email', $email)->first();

        if ($subscriber) {
            if (!$subscriber->is_active) {
                $subscriber->update(['is_active' => true, 'is_registered' => $isRegistered]);
            } else {
                return response()->json(['message' => 'You are already subscribed!'], 400);
            }
        } else {
            \App\Models\Subscriber::create([
                'email' => $email,
                'is_registered' => $isRegistered,
                'is_active' => true
            ]);
        }

        // Jika dia user terdaftar, update status di tabel users juga
        if ($user) {
            $user->update(['is_subscribed' => true]);
        }

        // Kirim Email Welcome
        try {
            \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\WelcomeSubscriberMail($email));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Subscribe Mail Error: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Subscription successful! Welcome to our newsletter.']);
    }
}
