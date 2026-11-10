<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Autentikasi aplikasi mobile (Flutter) berbasis token Sanctum.
 * Guru/Operator masuk dengan kredensial yang sama dengan aplikasi
 * web: kolom "email" boleh diisi email ATAU user_name (NIP/NPSN),
 * persis aturan Fortify::authenticateUsing di web. Server
 * menerbitkan token per perangkat yang bisa dicabut kapan pun
 * (logout / ganti perangkat).
 */
class AuthApiController extends Controller
{
  /** Susunan data pengguna yang dikirim ke aplikasi mobile. */
  public static function ringkasanPengguna(User $user): array
  {
    $ptk = $user->ptk;

    return [
      'id' => $user->id,
      'nama' => $user->name,
      'email' => $user->email,
      'peran' => $user->getRoleNames()->values(),
      'ptk' => $ptk ? [
        'id' => $ptk->id,
        'nama_ptk' => $ptk->nama_ptk,
        'nip' => $ptk->nip,
        'jabatan' => $ptk->jabatan,
        'sekolah_id' => $ptk->sekolah_id,
        'nama_sekolah' => $ptk->sekolah?->detailTerbaru?->nama_sekolah,
      ] : null,
    ];
  }

  public function login(Request $request)
  {
    $data = $request->validate([
      // BUKAN format email: isinya boleh email atau user_name (NIP).
      'email' => ['required', 'string', 'max:255'],
      'password' => ['required', 'string'],
      'nama_perangkat' => ['nullable', 'string', 'max:100'],
    ]);

    // Sama seperti login web: cocokkan ke email ATAU user_name.
    $isian = trim($data['email']);
    $user = User::where('email', $isian)->orWhere('user_name', $isian)->first();

    if (!$user || !Hash::check($data['password'], $user->password)) {
      return response()->json([
        'message' => 'Email/NIP atau kata sandi salah.',
      ], 401);
    }

    $token = $user->createToken($data['nama_perangkat'] ?? 'Aplikasi Mobile')->plainTextToken;

    return response()->json([
      'token' => $token,
      'pengguna' => self::ringkasanPengguna($user),
    ]);
  }

  public function saya(Request $request)
  {
    return response()->json([
      'pengguna' => self::ringkasanPengguna($request->user()),
    ]);
  }

  public function logout(Request $request)
  {
    $request->user()->currentAccessToken()?->delete();

    return response()->json([
      'message' => 'Berhasil keluar. Token perangkat ini sudah dicabut.',
    ]);
  }
}
