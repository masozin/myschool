<?php

namespace App\Http\Middleware;

use App\Models\Rombel;
use App\Models\SekolahDetail;
use App\Models\Semester;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CekDataPeriodik
{
  public function handle(Request $request, Closure $next)
  {
    $user = Auth::user();

    if ($user && $user->ptk) {

      if ($request->routeIs('periodic.copy-form', 'periodic.copy', 'sekolah.belum-aktif', 'logout')) {
        return $next($request);
      }

      $semesterId = Semester::smtId();

      if (!$semesterId) {
        return $next($request);
      }

      $sekolahId = $user->ptk->sekolah_id;

      $status = SekolahDetail::status($sekolahId, $semesterId);

      // Data lengkap -> lanjut
      if ($status['hasDetail'] && $status['hasRombel']) {
        return $next($request);
      }

      // Ada data semester lama yang bisa disalin?
      $adaDataLama = Rombel::where('sekolah_id', $sekolahId)
        ->where('semester_id', '!=', $semesterId)
        ->exists();

      // Sekolah baru: detail ada, rombel kosong, dan tidak ada data lama.
      // Tidak ada yang bisa disalin, jadi jangan diblokir.
      if ($status['hasDetail'] && !$status['hasRombel'] && !$adaDataLama) {
        return $next($request);
      }

      if ($user->hasRole('Operator Sekolah')) {
        return redirect()->route('periodic.copy-form');
      }

      return redirect()->route('sekolah.belum-aktif');
    }

    return $next($request);
  }
}
