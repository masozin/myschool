<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSemesterLock
{
  public function handle(Request $request, Closure $next)
  {
    // 1. Izinkan kalau cuma mau LIHAT data (GET)
    if ($request->isMethod('GET')) {
      return $next($request);
    }

    // 2. Izinkan kalau dia ADMIN (Admin biasanya boleh edit semester lama)
    if (Auth::check() && Auth::user()->hasRole('Admin')) {
      return $next($request);
    }

    // 3. Ambil semester dari session yang disiapkan middleware kamu tadi
    $semesterId = session('semester_id');
    $semester = Semester::find($semesterId);

    // 4. JIKA semester tidak aktif, TOLAK aksi Simpan/Edit/Hapus
    if (!$semester || !$semester->is_active) {
      if ($request->expectsJson()) {
        return response()->json([
          'message' => 'Gagal! Semester ini sudah dikunci.'
        ], 403);
      }
      return back()->with('error', 'Semester ini sudah dikunci.');
    }

    return $next($request);
  }
}
