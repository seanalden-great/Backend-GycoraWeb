<?php

namespace App\Http\Controllers;

use App\Mail\AdminResponseMail;
use App\Mail\WelcomeSubscriberMail;
use App\Models\Contact;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * POST: Kirim Pesan Contact Us
     */
    // public function submit(Request $request)
    // {
    //     // 1. Validasi input (hanya description yang wajib dikirim frontend)
    //     $validated = $request->validate([
    //         'description' => 'required|string',
    //     ], [
    //         'description.required' => 'Pesan tidak boleh kosong.',
    //     ]);

    //     // 2. Ambil data user yang sedang login via token Sanctum
    //     // Ini otomatis mengembalikan model User utuh, pengganti query SELECT manual di Golang
    //     $user = $request->user();

    //     // 3. Simpan ke tabel contacts menggunakan Mass Assignment Eloquent
    //     Contact::create([
    //         'user_id' => $user->id,
    //         'name' => trim($user->first_name.' '.$user->last_name),
    //         'email' => $user->email,
    //         'phone' => $user->phone ?? '', // Fallback ke string kosong jika phone bernilai null
    //         'description' => $validated['description'],
    //         'is_read' => false, // Set default 0 (belum dibaca)
    //     ]);

    //     // 4. Return JSON response
    //     return response()->json([
    //         'message' => 'Pesan Anda berhasil dikirim. Tim kami akan segera merespons.',
    //     ], 201);
    // }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => 'nullable',
            'description' => 'required',
        ]);

        if ($request->user('sanctum')) {
            $data['user_id'] = $request->user('sanctum')->id;
        }

        Contact::create($data);

        return response()->json(['message' => 'Message sent successfully'], 201);
    }

    // Fungsi Admin mengambil semua pesan
    public function getInboundMessages()
    {
        // Ubah langsung hit ke model agar data lengkap sesuai DB terbaca di Vue
        $messages = Contact::with('user')->latest()->get();

        return response()->json($messages);
    }

    // [BARU] Fungsi Admin melihat detail (Sekaligus mark as read)
    public function showAdminMessage($id)
    {
        $contact = Contact::with('user')->findOrFail($id);

        // Jika belum dibaca, ubah jadi sudah dibaca saat dibuka
        if (! $contact->is_read) {
            $contact->update(['is_read' => true]);
        }

        return response()->json($contact);
    }

    // [BARU] Fungsi Admin membalas pesan
    public function respondMessage(Request $request, $id)
    {
        $request->validate(['response' => 'required|string']);

        $contact = Contact::findOrFail($id);

        $contact->update([
            'response' => $request->response,
            'is_read' => true, // Pastikan juga ter-read
        ]);

        // Kirim Email
        try {
            Mail::to($contact->email)->send(new AdminResponseMail($contact));
        } catch (\Exception $e) {
            // Lanjutkan saja meskipun email gagal, data tetap tersimpan di web
            \Log::error('Gagal kirim email kontak: '.$e->getMessage());
        }

        return response()->json(['message' => 'Response sent successfully']);
    }

    // [BARU] Fungsi User melihat riwayat pesan mereka sendiri
    public function userHistory(Request $request)
    {
        $messages = Contact::where('user_id', $request->user()->id)->latest()->get();

        return response()->json($messages);
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email:rfc,dns',
        ], [
            'email.dns' => 'The email domain does not seem to be valid or active.',
        ]);

        $email = $request->email;

        // Cek apakah ini email dari user yang sudah terdaftar di web kita
        $user = User::where('email', $email)->first();
        $isRegistered = $user ? true : false;

        $subscriber = Subscriber::where('email', $email)->first();

        if ($subscriber) {
            if (! $subscriber->is_active) {
                $subscriber->update(['is_active' => true, 'is_registered' => $isRegistered]);
            } else {
                return response()->json(['message' => 'You are already subscribed!'], 400);
            }
        } else {
            Subscriber::create([
                'email' => $email,
                'is_registered' => $isRegistered,
                'is_active' => true,
            ]);
        }

        // Jika dia user terdaftar, update status di tabel users juga
        if ($user) {
            $user->update(['is_subscribed' => true]);
        }

        // Kirim Email Welcome
        try {
            Mail::to($email)->send(new WelcomeSubscriberMail($email));
        } catch (\Exception $e) {
            Log::error('Subscribe Mail Error: '.$e->getMessage());
        }

        return response()->json(['message' => 'Subscription successful! Welcome to our newsletter.']);
    }
}
