<?php

namespace App\Support;

use App\Models\SuratKategori;
use App\Models\SuratKeluar;
use App\Models\SuratPengaturan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Penomoran surat keluar. Nomor resmi hanya lahir di sini,
 * di dalam transaksi dengan kunci baris pengaturan sekolah,
 * sehingga dua petugas yang menerbitkan bersamaan tidak
 * mungkin mendapat nomor yang sama. Penghitung berjalan per
 * tahun kalender tanggal surat dan mulai lagi dari 1 setiap
 * tahun baru. Nomor surat yang sudah terbit dibekukan di
 * kolom nomor_surat dan tidak pernah dipakai ulang.
 */
class NomorSurat
{
  /** Kategori bawaan yang dibuat saat sekolah pertama kali membuka modul. */
  public static function pastikanKategoriBawaan(int $sekolahId): void
  {
    if (SuratKategori::where('sekolah_id', $sekolahId)->exists()) {
      return;
    }

    // Pola klasifikasi surat dinas (000–900): untuk SD yang
    // terpakai umumnya empat ini — 000 Umum, 420 Pendidikan,
    // 800 Kepegawaian, 900 Keuangan. Rincian lebih halus
    // (mis. 421, 422) bisa ditambah dari Pengaturan.
    $bawaan = [
      ['000', 'Umum'],
      ['420', 'Pendidikan'],
      ['800', 'Kepegawaian'],
      ['900', 'Keuangan'],
    ];

    foreach ($bawaan as $i => [$kode, $nama]) {
      SuratKategori::create([
        'sekolah_id' => $sekolahId,
        'kode' => $kode,
        'nama' => $nama,
        'urutan' => $i + 1,
      ]);
    }
  }

  /**
   * Terbitkan surat: tetapkan nomor resmi (kecuali nomor
   * manual yang sudah ditulis petugas) dan ubah status
   * menjadi terbit. Mengembalikan surat yang sudah diperbarui.
   */
  public static function terbitkan(SuratKeluar $surat): SuratKeluar
  {
    return DB::transaction(function () use ($surat) {
      if ($surat->nomor_manual) {
        $surat->status = 'terbit';
        $surat->save();
        return $surat;
      }

      $pengaturan = SuratPengaturan::where('sekolah_id', $surat->sekolah_id)
        ->lockForUpdate()
        ->first() ?? SuratPengaturan::untukSekolah($surat->sekolah_id);

      $tanggal = $surat->tanggal_surat ?? now();
      $tahun = (int) $tanggal->format('Y');

      if ((int) $pengaturan->tahun_berjalan !== $tahun) {
        $pengaturan->tahun_berjalan = $tahun;
        $pengaturan->nomor_terakhir = 0;
      }

      $pengaturan->nomor_terakhir = (int) $pengaturan->nomor_terakhir + 1;
      $pengaturan->save();

      $surat->nomor_urut = $pengaturan->nomor_terakhir;
      $surat->nomor_surat = static::renderFormat(
        $pengaturan->format_nomor ?: SuratPengaturan::FORMAT_BAWAAN,
        $pengaturan->nomor_terakhir,
        $surat->kategori?->kode,
        $tanggal
      );
      $surat->status = 'terbit';
      $surat->save();

      return $surat;
    });
  }

  /** Pratinjau nomor berikutnya (tidak mengonsumsi nomor). */
  public static function contohBerikutnya(int $sekolahId, ?SuratKategori $kategori = null, ?Carbon $tanggal = null): string
  {
    $pengaturan = SuratPengaturan::untukSekolah($sekolahId);
    $tanggal ??= now();
    $tahun = (int) $tanggal->format('Y');
    $berikutnya = (int) $pengaturan->tahun_berjalan === $tahun
      ? (int) $pengaturan->nomor_terakhir + 1
      : 1;

    return static::renderFormat(
      $pengaturan->format_nomor ?: SuratPengaturan::FORMAT_BAWAAN,
      $berikutnya,
      $kategori?->kode,
      $tanggal
    );
  }

  /**
   * Isi pola format nomor. Token: {no}, {no:3} / {no;3}
   * (angka nol di depan), {kode}, {bulan_romawi}, {bulan},
   * {tahun}, {tahun2}.
   */
  public static function renderFormat(string $format, int $no, ?string $kode, Carbon $tanggal): string
  {
    $hasil = preg_replace_callback(
      '/\{no[:;](\d+)\}/',
      fn($m) => str_pad((string) $no, (int) $m[1], '0', STR_PAD_LEFT),
      $format
    );

    $hasil = str_replace(
      ['{no}', '{kode}', '{bulan_romawi}', '{bulan}', '{tahun}', '{tahun2}'],
      [
        (string) $no,
        (string) ($kode ?? ''),
        static::bulanRomawi((int) $tanggal->format('n')),
        $tanggal->format('m'),
        $tanggal->format('Y'),
        $tanggal->format('y'),
      ],
      $hasil
    );

    // Token kosong (mis. belum ada kategori) tidak menyisakan garis miring ganda.
    $hasil = preg_replace('#/+#', '/', $hasil);

    return trim($hasil, '/');
  }

  public static function bulanRomawi(int $bulan): string
  {
    return ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$bulan - 1] ?? '';
  }
}
