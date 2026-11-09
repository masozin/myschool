<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Autentikasi aplikasi mobile (Flutter) berbasis token Sanctum.
 * Guru/Operator masuk dengan email + kata sandi yang sama dengan
 * aplikasi web; server menerbitkan token per perangkat yang bisa
 * dicabut kapan pun (logout / ganti perangkat).
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
      'email' => ['required', 'email'],
      'password' => ['required', 'string'],
      'nama_perangkat' => ['nullable', 'string', 'max:100'],
    ]);

    if (!Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
      return response()->json([
        'message' => 'Email atau kata sandi salah.',
      ], 401);
    }

    $user = Auth::user();
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
