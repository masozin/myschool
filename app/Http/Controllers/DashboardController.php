<?php

namespace App\Http\Controllers;

use App\Models\Ptk;
use App\Models\SiswaRegistrasi;
use App\Models\Rombel;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RombelAnggota;

class DashboardController extends Controller
{
  public function index()
  {
    $user = Auth::user();
    $isAdmin = $user->hasRole('Admin'); // sesuaikan dengan implementasi role di project Anda
    $ptk = Ptk::where('user_id', $user->id)->first();

    $data = compact('user', 'ptk', 'isAdmin');

    if ($isAdmin) {
      // Statistik global untuk admin
      $data['jumlahSekolah']  = Sekolah::count();
      $data['jumlahSiswa']    = SiswaRegistrasi::where('status', 'aktif')->distinct('siswa_id')->count('siswa_id');
      $data['jumlahRombel']   = Rombel::count();


      // Guru: jabatan = 'Guru'
      $data['jumlahPtk']      = Ptk::count();
      $data['jumlahGuru']     = Ptk::where('jabatan', 'Guru')->count();
      $data['jumlahTendik']   = Ptk::where('jabatan', '!=', 'Guru')->count();

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
        $semesterId = session('semester_id');

        $data['sekolah']         = $sekolah;
        $data['sekolahDetail']   = $sekolah->detailTerbaru;
        $data['jumlahSiswa']     = $sekolah->siswaRegistrasi()
          ->where('status', 'aktif')
          ->count();
        $data['jumlahRombel']    = $sekolah->rombel()
          ->when($semesterId, fn($q) => $q->where('semester_id', $semesterId))
          ->count();
        $data['jumlahGuru']      = $sekolah->ptk()
          ->where('jabatan', 'Guru')
          ->count();
        $data['jumlahTendik']    = $sekolah->ptk()
          ->where('jabatan', '!=', 'Guru')
          ->count();

        // Siswa per jenis kelamin (untuk chart donut kecil)
        $data['siswaLaki']       = $sekolah->siswa()->where('jenis_kelamin', 'L')->count();
        $data['siswaPerempuan']  = $sekolah->siswa()->where('jenis_kelamin', 'P')->count();

        // Aset sekolah (logo dll)
        $data['logo']            = $sekolah->aset('logo');

        // Statistik penyebaran gender
        $anggotaAktif = RombelAnggota::with(['siswa:id,jenis_kelamin', 'rombel:id,tingkat,sekolah_id,semester_id'])
          ->whereNull('end_date')
          ->whereHas('rombel', function ($q) use ($sekolahId, $semesterId) {
            $q->where('sekolah_id', $sekolahId)
              ->when($semesterId, fn($qq) => $qq->where('semester_id', $semesterId));
          })
          ->get();

        $statistikTingkat = [];
        foreach ($anggotaAktif as $a) {
          if (!$a->rombel) continue;
          $tingkat = $a->rombel->tingkat;
          $jk = $a->siswa->jenis_kelamin ?? null;

          if (!isset($statistikTingkat[$tingkat])) {
            $statistikTingkat[$tingkat] = ['tingkat' => $tingkat, 'L' => 0, 'P' => 0];
          }
          if ($jk === 'L') $statistikTingkat[$tingkat]['L']++;
          elseif ($jk === 'P') $statistikTingkat[$tingkat]['P']++;
        }
        $data['statistikTingkat'] = $statistikTingkat;
      } else {
        $data['sekolah'] = null;
      }
    }

    return view('content.dashboard', $data);
  }
}
