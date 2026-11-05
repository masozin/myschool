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

      // Ada data semester lama yang bisa disalin? Yang dihitung hanya
      // semester sebelum semester aktif (secara kronologis) — semester
      // masa depan bukan sumber salin, jadi tidak boleh memicu
      // pengalihan ke halaman salin.
      $semuaSemester = Semester::all()
        ->sortBy(fn(Semester $s) => Semester::kunciKronologis($s))
        ->values();
      $posisiAktif = $semuaSemester->search(
        fn(Semester $s) => (int) $s->id === (int) $semesterId
      );
      $tahunAktif = $posisiAktif === false
        ? null
        : (int) substr((string) $semuaSemester[$posisiAktif]->tahun_pelajaran, 0, 4);
      // Hanya semester dalam jendela salin (maksimal 1 tahun pelajaran
      // di bawah semester aktif) yang dihitung sebagai data lama
      $idsSebelumnya = $posisiAktif === false
        ? []
        : $semuaSemester->slice(0, $posisiAktif)
            ->filter(fn(Semester $s) => $tahunAktif === null
              || (int) substr((string) $s->tahun_pelajaran, 0, 4) >= $tahunAktif - 1)
            ->pluck('id')
            ->all();

      $adaDataLama = !empty($idsSebelumnya) && Rombel::where('sekolah_id', $sekolahId)
        ->whereIn('semester_id', $idsSebelumnya)
        ->exists();

      // Sekolah baru: detail ada, rombel kosong, dan tidak ada data lama.
      // Tidak ada yang bisa disalin, jadi jangan diblokir.
      if ($status['hasDetail'] && !$status['hasRombel'] && !$adaDataLama) {
        return $next($request);
      }

      // Tidak ada data lama sama sekali yang bisa disalin (detail maupun
      // rombel kosong di semua semester lain): jangan jebak operator di
      // halaman salin — biarkan ia mengisi data secara manual.
      $adaDetailLama = !empty($idsSebelumnya) && SekolahDetail::where('sekolah_id', $sekolahId)
        ->whereIn('semester_id', $idsSebelumnya)
        ->exists();

      if (!$adaDataLama && !$adaDetailLama) {
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
