<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Semester;
use Illuminate\Http\Request;

/**
 * Kunci semester menyeluruh: data semester yang BUKAN semester
 * aktif hanya boleh dilihat. Setiap permintaan pengubah data
 * (POST/PUT/PATCH/DELETE) pada semester sesi yang tidak aktif
 * ditolak, kecuali Admin telah membuka kunci semester itu
 * (kolom kunci_dibuka pada tabel_semester, diatur dari halaman
 * Semester). Terpasang pada grup web di bootstrap/app.php.
 *
 * Pengecualian:
 * - Permintaan baca (GET/HEAD/OPTIONS) selalu lolos.
 * - Rute akun & autentikasi (login, profil, Livewire Jetstream)
 *   tidak terkait data semester.
 * - Modul SKHU/SKL punya kunci angkatan sendiri yang lebih
 *   halus (App\Support\KunciKelulusan) dan Kalender Pendidikan
 *   datanya per tahun pelajaran, bukan per semester.
 * - Pembaruan Aplikasi (Admin) adalah urusan pemeliharaan
 *   sistem, tidak terkait data semester mana pun.
 * - Surat Keluar digerakkan tanggal surat, bukan semester
 *   sesi; mencatat surat bertanggal semester lalu adalah
 *   pekerjaan administrasi yang sah.
 * - Admin tetap dapat mengelola halaman Semester (mengaktifkan
 *   semester dan membuka/menerapkan kunci).
 */
class CheckSemesterLock
{
  /** Awalan path yang tidak pernah dikunci middleware ini. */
  private array $bebas = [
    'login',
    'logout',
    'register',
    'forgot-password',
    'reset-password',
    'two-factor',
    'verify-email',
    'confirm-password',
    'user/',
    'livewire/',
    'skhu',
    'skl',
    'kalender-pendidikan',
    'surat-keluar',
    'pembaruan',
    'up',
  ];

  public function handle(Request $request, Closure $next)
  {
    // 1. Baca saja selalu boleh
    if ($request->isMethod('GET') || $request->isMethod('HEAD') || $request->isMethod('OPTIONS')) {
      return $next($request);
    }

    // 2. Belum login: tidak ada konteks semester untuk dikunci
    if (!$request->user()) {
      return $next($request);
    }

    $path = $request->path();

    foreach ($this->bebas as $prefix) {
      if (str_starts_with($path, $prefix)) {
        return $next($request);
      }
    }

    // 3. Admin harus selalu dapat mengelola semester itu sendiri
    //    (mengaktifkan semester, membuka / menerapkan kunci)
    if ($request->user()->hasRole('Admin') && str_starts_with($path, 'semester')) {
      return $next($request);
    }

    $semesterId = session('semester_id');
    $semester = $semesterId ? Semester::find($semesterId) : null;

    // 4. Tanpa semester sesi, semester sesi adalah yang aktif,
    //    atau kuncinya sedang dibuka Admin: lolos
    if (!$semester || $semester->is_active || $semester->kunci_dibuka) {
      return $next($request);
    }

    // 5. Terkunci: hanya boleh lihat
    $pesan = 'Semester ' . $semester->tahun_pelajaran . ' ' . $semester->semester
      . ' bukan semester aktif — datanya hanya dapat dilihat. '
      . 'Admin dapat membuka kunci semester ini dari halaman Semester bila memang diperlukan.';

    if ($request->expectsJson()) {
      return response()->json(['message' => $pesan], 403);
    }

    return back()->with('error', $pesan);
  }
}
