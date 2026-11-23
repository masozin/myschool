<?php

namespace App\Http\Controllers;

use App\Models\Ptk;
use App\Models\Siswa;
use App\Models\Rombel;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RombelAnggota;
use App\Models\Semester;
use App\Support\InfoHariIni;

class DashboardController extends Controller
{
  /**
   * Definisi baku yang dipakai SELURUH angka di dashboard (dan disamakan
   * dengan Kartu Pelajar, Data Periodik, pivot, dan Web Sekolah):
   *
   * - Siswa aktif  : registrasi TERBARU siswa berstatus 'aktif'
   *                  (bukan sembarang registrasi aktif — registrasi lama
   *                  yang sudah tergantikan tidak ikut dihitung).
   * - PTK aktif    : tabel_ptk.status = 'aktif' (pensiun / tidak aktif
   *                  tidak ikut dihitung).
   * - Tendik       : PTK aktif yang jabatannya bukan 'Guru', termasuk
   *                  jabatan kosong — supaya Guru + Tendik selalu sama
   *                  dengan Total PTK.
   * - Rombel       : rombel pada semester sesi; bila sesi belum memilih
   *                  semester, dipakai semester aktif.
   */

  /** Query siswa aktif menurut definisi baku; $sekolahId null = semua sekolah. */
  private function querySiswaAktif(?int $sekolahId)
  {
    return Siswa::whereHas('registrasiTerbaru', function ($q) use ($sekolahId) {
      $q->where('status', 'aktif')
        ->when($sekolahId, fn($qq) => $qq->where('sekolah_id', $sekolahId));
    });
  }

  /** Query PTK aktif menurut definisi baku; $sekolahId null = semua sekolah. */
  private function queryPtkAktif(?int $sekolahId)
  {
    return Ptk::where('status', 'aktif')
      ->when($sekolahId, fn($q) => $q->where('sekolah_id', $sekolahId));
  }

  public function index()
  {
    $user = Auth::user();
    $isAdmin = $user->hasRole('Admin'); // sesuaikan dengan implementasi role di project Anda
    $ptk = Ptk::where('user_id', $user->id)->first();

    $data = compact('user', 'ptk', 'isAdmin');

    // Info hari ini — kaldik & ulang tahun PTK, aturan bersama
    // dengan aplikasi mobile (App\Support\InfoHariIni).
    $data['kaldikHariIni'] = InfoHariIni::kaldikPada(now());
    $data['ultahHariIni'] = InfoHariIni::ulangTahunPada(
      now(),
      $isAdmin ? null : $ptk?->sekolah_id
    );
    $data['ultahSaya'] = $ptk
      ? $data['ultahHariIni']->contains('id', $ptk->id)
      : false;
    $data['ucapanSaya'] = $data['ultahSaya'] && $ptk
      ? InfoHariIni::ucapanUntuk($ptk, now())
      : null;

    // Semester tampilan: pilihan sesi, cadangan semester aktif.
    $semesterAktifId = Semester::where('is_active', true)->value('id');
    $semesterId = session('semester_id') ?: $semesterAktifId;
    $data['semesterId'] = $semesterId;

    if ($isAdmin) {
      // Statistik global untuk admin — definisi baku yang sama.
      $data['jumlahSekolah']  = Sekolah::count();
      $data['jumlahSiswa']    = $this->querySiswaAktif(null)->count();
      $data['jumlahRombel']   = Rombel::when(
        $semesterId,
        fn($q) => $q->where('semester_id', $semesterId)
      )->count();

      $ptkAktif = $this->queryPtkAktif(null);
      $data['jumlahPtk']      = (clone $ptkAktif)->count();
      $data['jumlahGuru']     = (clone $ptkAktif)->where('jabatan', 'Guru')->count();
      $data['jumlahTendik']   = (clone $ptkAktif)
        ->where(fn($q) => $q->where('jabatan', '!=', 'Guru')->orWhereNull('jabatan'))
        ->count();

      // Breakdown sekolah per jenjang (untuk chart)
      $data['sekolahPerJenjang'] = Sekolah::selectRaw('jenjang, count(*) as total')
        ->groupBy('jenjang')
        ->pluck('total', 'jenjang');

      $data['riwayatLogin'] = User::with('ptk.sekolah')
        ->whereNotNull('last_login_at')
        ->latest('last_login_at')
        ->take(10)
        ->get();
    } else {
      // Data spesifik sekolah untuk operator/kepala sekolah
      $sekolah = $user->ptk?->sekolah ?? null;
      $sekolahId = $sekolah?->id;

      $data['riwayatLogin'] = User::with('ptk')
        ->whereHas('ptk', fn($q) => $q->where('sekolah_id', $sekolahId))
        ->whereNotNull('last_login_at')
        ->latest('last_login_at')
        ->take(10)
        ->get();

      if ($sekolah) {
        $data['sekolah']         = $sekolah;
        $data['sekolahDetail']   = $sekolah->detailTerbaru;

        $siswaAktif = $this->querySiswaAktif($sekolahId);
        $data['jumlahSiswa']     = (clone $siswaAktif)->count();

        $data['jumlahRombel']    = $sekolah->rombel()
          ->when($semesterId, fn($q) => $q->where('semester_id', $semesterId))
          ->count();

        $ptkAktif = $this->queryPtkAktif($sekolahId);
        $data['jumlahGuru']      = (clone $ptkAktif)->where('jabatan', 'Guru')->count();
        $data['jumlahTendik']    = (clone $ptkAktif)
          ->where(fn($q) => $q->where('jabatan', '!=', 'Guru')->orWhereNull('jabatan'))
          ->count();

        // Siswa per jenis kelamin — dari himpunan siswa aktif yang SAMA
        // dengan kartu Siswa Aktif, supaya L + P selalu = Siswa Aktif.
        $data['siswaLaki']       = (clone $siswaAktif)->where('jenis_kelamin', 'L')->count();
        $data['siswaPerempuan']  = (clone $siswaAktif)->where('jenis_kelamin', 'P')->count();

        // Aset sekolah (logo dll)
        $data['logo']            = $sekolah->aset('logo');

        // Statistik penyebaran gender per tingkat: anggota rombel pada
        // semester tampilan, SELALU hanya siswa aktif menurut definisi
        // baku (registrasi terbaru aktif di sekolah ini) — tidak lagi
        // bergantung pada apakah semester sesi = semester aktif.
        // Satu siswa dihitung paling banyak satu kali per tingkat.
        $anggotaAktif = RombelAnggota::with(['siswa:id,jenis_kelamin', 'rombel:id,tingkat,sekolah_id,semester_id'])
          ->whereHas('siswa.registrasiTerbaru', function ($q) use ($sekolahId) {
            $q->where('status', 'aktif')->where('sekolah_id', $sekolahId);
          })
          ->whereHas('rombel', function ($q) use ($sekolahId, $semesterId) {
            $q->where('sekolah_id', $sekolahId)
              ->when($semesterId, fn($qq) => $qq->where('semester_id', $semesterId));
          })
          ->get();

        $statistikTingkat = [];
        $sudahDihitung = [];
        foreach ($anggotaAktif as $a) {
          if (!$a->rombel || !$a->siswa) continue;
          $tingkat = $a->rombel->tingkat;
          $kunci = $tingkat . ':' . $a->siswa_id;
          if (isset($sudahDihitung[$kunci])) continue;
          $sudahDihitung[$kunci] = true;

          $jk = $a->siswa->jenis_kelamin ?? null;

          if (!isset($statistikTingkat[$tingkat])) {
            $statistikTingkat[$tingkat] = ['tingkat' => $tingkat, 'L' => 0, 'P' => 0];
          }
          if ($jk === 'L') $statistikTingkat[$tingkat]['L']++;
          elseif ($jk === 'P') $statistikTingkat[$tingkat]['P']++;
        }
        ksort($statistikTingkat);
        $data['statistikTingkat'] = $statistikTingkat;
      } else {
        $data['sekolah'] = null;
      }
    }

    return view('content.dashboard', $data);
  }
}
