<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // =========================================================================
    // 1. REGISTER USER
    // =========================================================================
    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:users',
            'password'   => 'required|string|min:8',
        ]);

        $isSubscribed = false;

        DB::transaction(function () use ($validated, &$isSubscribed, &$user) {
            // Cek apakah email ini sudah pernah subscribe saat menjadi Guest
            // Asumsi Anda memiliki model Subscriber atau tabel subscribers
            $subscriber = DB::table('subscribers')->where('email', $validated['email'])->first();

            if ($subscriber) {
                $isSubscribed = true;
                // Tandai bahwa dia kini Registered
                DB::table('subscribers')->where('id', $subscriber->id)->update(['is_registered' => 1]);
            }

            // Buat User baru
            $user = User::create([
                'first_name'    => $validated['first_name'],
                'last_name'     => $validated['last_name'],
                'email'         => $validated['email'],
                'password'      => Hash::make($validated['password']),
                'usertype'      => 'user', // Default usertype
                'is_subscribed' => $isSubscribed,
            ]);
        });

        return response()->json([
            'message' => 'User berhasil didaftarkan',
            'user'    => $user
        ], 201);
    }

    // =========================================================================
    // 2. LOGIN USER (Hanya role 'user' yang diizinkan)
    // =========================================================================
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // Cek jika user tidak ditemukan, password salah, atau usertype BUKAN 'user'
        if (! $user || ! Hash::check($request->password, $user->password) || $user->usertype !== 'user') {
            throw ValidationException::withMessages([
                'email' => ['Email atau Password salah.'],
            ]);
        }

        // Generate Token via Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Login Berhasil',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user
        ]);
    }

    // =========================================================================
    // 3. LOGIN ADMIN (Hanya role manajerial yang diizinkan)
    // =========================================================================
    public function adminLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        $allowedAdminRoles = ['admin', 'superadmin', 'gudang', 'accounting'];

        // Cek autentikasi dan otorisasi role
        if (! $user || ! Hash::check($request->password, $user->password) || ! in_array($user->usertype, $allowedAdminRoles)) {
            return response()->json([
                'message' => 'Akses ditolak. Email/Password salah atau Anda tidak memiliki akses ke panel ini.'
            ], 401);
        }

        $token = $user->createToken('admin_auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Login Berhasil',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => $user
        ]);
    }

    // =========================================================================
    // 4. AMBIL SEMUA USER (Khusus Pelanggan)
    // =========================================================================
    public function getAllUsers()
    {
        // Mengambil user yang bukan admin/staf
        $users = User::where('usertype', 'user')
                     ->orderBy('id', 'desc')
                     ->get(['id', 'first_name', 'last_name', 'email', 'usertype', 'is_subscribed', 'created_at']);

        // Jika Anda ingin memastikan format tanggal sama persis seperti respons Golang,
        // Anda bisa melakukan map pada collection, tapi default JSON Eloquent biasanya sudah cukup baik.
        // Format default created_at Eloquent: "2024-05-12T10:00:00.000000Z"

        return response()->json($users);
    }

    // =========================================================================
    // 5. UPDATE PROFIL USER
    // =========================================================================
    public function updateProfile(Request $request)
    {
        $user = $request->user(); // Mendapatkan user yang sedang login via Sanctum

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name'  => 'required|string|max:255',
            // Validasi email: pastikan format email benar dan unik di tabel users, KECUALI untuk ID user ini sendiri
            'email'      => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone'      => 'nullable|string|max:20',
        ], [
            // Kustomisasi pesan error agar sama seperti logika Golang Anda
            'email.unique' => 'Email sudah digunakan oleh akun lain'
        ]);

        // Eloquent secara otomatis menangani `updated_at`
        $user->update([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'],
            'phone'      => $validated['phone'],
        ]);

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user'    => $user // Mengembalikan objek user yang sudah terupdate
        ]);
    }
}
