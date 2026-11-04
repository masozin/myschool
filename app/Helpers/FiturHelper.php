<?php

namespace App\Helpers;

use App\Models\Sekolah;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class FiturHelper
{
  // Cek apakah sekolah user yang login punya fitur tertentu
  public static function punya(string $kode): bool
  {
    $user = Auth::user();
    if (!$user) return false;

    // Admin selalu bisa akses semua fitur
    if ($user->hasRole('Admin')) return true;

    $sekolahId = $user->ptk->sekolah_id ?? null;
    if (!$sekolahId) return false;

    // Cache per sekolah agar tidak query berulang tiap request
    return Cache::remember("fitur_{$sekolahId}_{$kode}", 300, function () use ($sekolahId, $kode) {
      return Sekolah::find($sekolahId)?->hasFitur($kode) ?? false;
    });
  }

  // Ambil semua kode fitur yang aktif untuk sekolah user
  public static function semua(): array
  {
    $user = Auth::user();
    if (!$user) return [];
    if ($user->hasRole('Admin')) {
      return \App\Models\Fitur::pluck('kode')->toArray(); // literal semua kode, bukan '*'
    }

    $sekolahId = $user->ptk->sekolah_id ?? null;
    if (!$sekolahId) return [];

    return Cache::remember("fitur_{$sekolahId}_semua", 300, function () use ($sekolahId) {
      return Sekolah::find($sekolahId)
        ?->fiturs()
        ->wherePivot('aktif', true)
        ->pluck('kode')
        ->toArray() ?? [];
    });
  }

  // Hapus cache fitur sekolah (dipanggil saat admin ubah fitur)
  public static function clearCache(int $sekolahId): void
  {
    $fiturs = \App\Models\Fitur::pluck('kode');
    foreach ($fiturs as $kode) {
      Cache::forget("fitur_{$sekolahId}_{$kode}");
    }
    Cache::forget("fitur_{$sekolahId}_semua");
  }
}
