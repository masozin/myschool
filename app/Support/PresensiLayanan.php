<?php

namespace App\Support;

use App\Models\KalenderPendidikan;
use App\Models\PresensiPengaturan;
use App\Models\Rombel;
use App\Models\RombelAnggota;
use App\Models\User;
use Carbon\Carbon;

/**
 * Aturan inti presensi yang dipakai BERSAMA oleh halaman web
 * (PresensiController) dan API mobile (Api\PresensiApiController)
 * supaya aturan yang tampil di kedua tempat selalu sama:
 * hak melihat/mengisi, jendela koreksi Guru, penolakan tanggal,
 * dan susunan roster. Method di sini tidak membaca sesi sama
 * sekali — pengguna dan konteks diteruskan sebagai parameter.
 */
class PresensiLayanan
{
  /**
   * Tanggal yang tidak boleh dipresensi selain hari libur
   * Kalender Pendidikan: tanggal yang belum berjalan (masa
   * depan) dan hari libur mingguan sekolah pemilik rombel.
   * Mengembalikan pesan penolakan, atau null bila tanggal
   * boleh dipakai.
   */
  public static function penolakanTanggal(Rombel $rombel, Carbon $tanggal): ?string
  {
    if ($tanggal->copy()->startOfDay()->gt(now()->startOfDay())) {
      return 'Tanggal belum berjalan — presensi hanya dapat diisi untuk hari ini dan hari-hari sebelumnya.';
    }

    $namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'][$tanggal->dayOfWeek];
    $liburNama = $rombel->sekolah?->detailTerbaru?->libur_mingguan;
    $liburNama = is_array($liburNama) && $liburNama ? $liburNama : ['Minggu'];

    if (in_array($namaHari, $liburNama, true)) {
      return 'Tanggal ini hari libur mingguan sekolah (' . $namaHari . ') — presensi tidak dapat diisi.';
    }

    return null;
  }

  /**
   * Hak melihat presensi satu rombel: Admin semua rombel;
   * Operator & Kepala Sekolah rombel sekolahnya; Guru hanya
   * rombel wali kelasnya. Peran lain tidak berhak.
   */
  public static function bolehLihat(User $user, Rombel $rombel): bool
  {
    if ($user->hasRole('Admin')) {
      return true;
    }

    if ($user->hasAnyRole(['Operator Sekolah', 'Kepala Sekolah'])) {
      return (int) $rombel->sekolah_id === (int) $user->ptk?->sekolah_id;
    }

    if ($user->hasRole('Guru')) {
      return (int) $rombel->ptk_id === (int) $user->ptk?->id;
    }

    return false;
  }

  /**
   * Hak mengisi/mengubah presensi satu rombel: hanya wali kelas
   * rombel itu (Guru) dan Operator Sekolah sekolah itu. Admin
   * dan Kepala Sekolah hanya memantau — tidak pernah mengisi.
   */
  public static function bolehIsi(User $user, Rombel $rombel): bool
  {
    if ($user->hasRole('Operator Sekolah')) {
      return (int) $rombel->sekolah_id === (int) $user->ptk?->sekolah_id;
    }

    if ($user->hasRole('Guru')) {
      return (int) $rombel->ptk_id === (int) $user->ptk?->id;
    }

    return false;
  }

  /**
   * Jendela koreksi untuk Guru: tanggal lampau hanya boleh
   * diisi/diubah dalam batas hari yang diatur Operator sekolah
   * (bawaan 7 hari). Operator sendiri tidak dibatasi jendela.
   */
  public static function dalamJendelaKoreksi(Rombel $rombel, Carbon $tanggal): bool
  {
    $batas = PresensiPengaturan::batasUntuk((int) $rombel->sekolah_id);

    return $tanggal->gte(now()->startOfDay()->subDays($batas));
  }

  /** Kegiatan Kalender Pendidikan yang membuat tanggal ini libur, bila ada. */
  public static function liburPada(Carbon $tanggal): ?KalenderPendidikan
  {
    return KalenderPendidikan::where('is_libur', true)
      ->whereDate('tanggal_mulai', '<=', $tanggal)
      ->whereDate('tanggal_selesai', '>=', $tanggal)
      ->first();
  }

  /**
   * Roster presensi: anggota rombel yang hari efektifnya — dari
   * tanggal registrasi di sekolah rombel itu — mencakup $tanggal
   * (belum bisa dipresensi sebelum tanggal_diterima, tidak lagi
   * sesudah tanggal_keluar). Dipakai bersama oleh tampilan dan
   * penyimpanan supaya aturan yang ditampilkan sama dengan
   * yang ditegakkan saat menyimpan.
   */
  public static function roster(Rombel $rombel, Carbon $tanggal)
  {
    return RombelAnggota::with('siswa')
      ->where('rombel_id', $rombel->id)
      ->whereHas('siswa', function ($q) use ($rombel, $tanggal) {
        $q->whereHas('siswaRegistrasi', function ($qr) use ($rombel, $tanggal) {
          $qr->where('sekolah_id', $rombel->sekolah_id)
            ->where(function ($qw) use ($tanggal) {
              $qw->whereNull('tanggal_diterima')
                ->orWhereDate('tanggal_diterima', '<=', $tanggal);
            })
            ->where(function ($qw) use ($tanggal) {
              $qw->whereNull('tanggal_keluar')
                ->orWhereDate('tanggal_keluar', '>=', $tanggal);
            });
        });
      })
      ->get();
  }
}
