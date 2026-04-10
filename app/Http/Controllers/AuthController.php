<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
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
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'usertype' => 'user', // Default usertype
                'is_subscribed' => $isSubscribed,
            ]);
        });

        return response()->json([
            'message' => 'User berhasil didaftarkan',
            'user' => $user,
        ], 201);
    }

    // =========================================================================
    // 2. LOGIN USER (Hanya role 'user' yang diizinkan)
    // =========================================================================
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
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
            'message' => 'Login Berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    // =========================================================================
    // 3. LOGIN ADMIN (Hanya role manajerial yang diizinkan)
    // =========================================================================
    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        $allowedAdminRoles = ['admin', 'superadmin', 'gudang', 'accounting'];

        // Cek autentikasi dan otorisasi role
        if (! $user || ! Hash::check($request->password, $user->password) || ! in_array($user->usertype, $allowedAdminRoles)) {
            return response()->json([
                'message' => 'Akses ditolak. Email/Password salah atau Anda tidak memiliki akses ke panel ini.',
            ], 401);
        }

        $token = $user->createToken('admin_auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login Berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
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
            'last_name' => 'required|string|max:255',
            // Validasi email: pastikan format email benar dan unik di tabel users, KECUALI untuk ID user ini sendiri
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
        ], [
            // Kustomisasi pesan error agar sama seperti logika Golang Anda
            'email.unique' => 'Email sudah digunakan oleh akun lain',
        ]);

        // Eloquent secara otomatis menangani `updated_at`
        $user->update([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ]);

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user' => $user, // Mengembalikan objek user yang sudah terupdate
        ]);
    }

    public function updateAdminProfileInfo(Request $request)
    {
        $admin = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$admin->id,
            'phone' => 'nullable|string|max:20', // [BARU] Tambahkan validasi phone
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // [BARU] Sertakan phone saat update
        $admin->update($request->only('first_name', 'last_name', 'email', 'phone'));

        return response()->json([
            'message' => 'Admin profile updated successfully',
            'admin' => $admin,
        ]);
    }

    public function updateAdminImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg',
        ]);

        $admin = $request->user();

        try {
            // [PERBAIKAN] Jika ada foto lama, hapus dari Local Storage
            if ($admin->profile_image) {
                // Bersihkan URL agar hanya menyisakan path relatifnya saja
                $oldPath = str_replace(url(Storage::url('')), '', $admin->profile_image);
                $oldPath = ltrim(str_replace('/storage/', '', $oldPath), '/');

                Storage::disk('public')->delete($oldPath);
            }

            // [PERBAIKAN] Upload foto baru ke Local Storage
            $path = $request->file('image')->store('profiles', 'public');

            // [PERBAIKAN] Simpan URL penuhnya ke database
            $admin->profile_image = url(Storage::url($path));
            $admin->save();

            return response()->json([
                'message' => 'Admin photo updated',
                'admin' => $admin->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update admin photo',
            ], 500);
        }
    }

    public function updateAdminPassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $admin = $request->user();

        if (! Hash::check($request->old_password, $admin->password)) {
            return response()->json([
                'message' => 'Old password does not match',
            ], 401);
        }

        $admin->password = Hash::make($request->password);
        $admin->save();

        return response()->json([
            'message' => 'Password updated successfully',
        ]);
    }
}
