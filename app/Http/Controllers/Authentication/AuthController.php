<?php

namespace App\Http\Controllers\Authentication;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $messages = [
            'required' => ':attribute wajib di isi !!!',
        ];

        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ], $messages);

        $user = User::with(['role', 'pegawai.jabatan'])->where('email', $request->email)->first();

        // Validasi User & Password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah!',
            ], 401);
        }

        // Validasi Status User
        if ($user->status != 1) {
            return response()->json([
                'success' => false,
                'message' => 'User Account Belum Aktif!',
            ], 403);
        }

        // Hapus token lama untuk mencegah token menumpuk (opsional)
        $user->tokens()->delete();

        // Buat token baru untuk API
        $token = $user->createToken('authToken')->plainTextToken;

        // Kumpulkan data user yang diperlukan untuk dikirim ke Vue.js
        $userData = [
            'id' => $user->id,
            'email' => $user->email,
            'nama' => $user->pegawai->nama ?? '-',
            'jabatan' => $user->pegawai->jabatan->jabatan ?? '-',
            'role' => $user->role->role ?? '-',
            'image' => $user->pegawai->image_pegawai ?? 'default.png',
        ];

        return response()->json([
            'success' => true,
            'message' => 'Login Berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $userData,
        ], 200);
    }

    public function logout(Request $request)
    {
        // Pastikan user terotentikasi dan hapus token saat ini
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ], 200);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load(['role', 'pegawai.jabatan']);

        $userData = [
            'id' => $user->id,
            'email' => $user->email,
            'nama' => $user->pegawai->nama ?? '-',
            'jabatan' => $user->pegawai->jabatan->jabatan ?? '-',
            'role' => $user->role->role ?? '-',
            'image' => $user->pegawai->image_pegawai ?? 'default.png',
        ];

        return response()->json([
            'success' => true,
            'user' => $userData,
        ], 200);
    }
}
