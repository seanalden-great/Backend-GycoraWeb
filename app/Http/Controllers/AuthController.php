<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
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

    // 1. FUNGSI BARU UNTUK S3 PRE-SIGNED URL PROFIL
    public function getProfilePresignedUrl(Request $request)
    {
        $request->validate([
            'extension' => 'required|string',
            'content_type' => 'required|string',
        ]);

        $filename = 'profiles/'.Str::random(40).'.'.$request->extension;

        $uploadResponse = Storage::disk('s3')->temporaryUploadUrl(
            $filename,
            now()->addMinutes(15),
            [
                'ContentType' => $request->content_type,
                'ACL' => 'public-read', // Penting agar foto profil bisa diakses publik
            ]
        );

        $fileUrl = env('AWS_URL').'/'.$filename;

        return response()->json([
            'upload_url' => $uploadResponse['url'],
            'upload_headers' => $uploadResponse['headers'],
            'file_url' => $fileUrl,
        ]);
    }

    // 2. UBAH FUNGSI UPDATE GAMBAR
    public function updateAdminImage(Request $request)
    {
        // Sekarang kita hanya menerima string URL, BUKAN file mentah
        $request->validate([
            'image_url' => 'required|url',
        ]);

        $admin = $request->user();

        try {
            // Jika ada foto lama di S3, hapus dulu agar tidak menumpuk
            if ($admin->profile_image) {
                // Ekstrak path setelah domain AWS
                $oldKey = str_replace(env('AWS_URL').'/', '', $admin->profile_image);

                // Pastikan key-nya benar-benar ada dan tidak kosong
                if ($oldKey && $oldKey !== $admin->profile_image) {
                    Storage::disk('s3')->delete($oldKey);
                }
            }

            // Simpan URL penuh S3 ke database
            $admin->profile_image = $request->image_url;
            $admin->save();

            return response()->json([
                'message' => 'Admin photo updated',
                'admin' => $admin->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update admin photo: '.$e->getMessage(),
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
