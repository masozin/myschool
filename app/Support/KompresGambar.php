<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Simpan foto unggahan dalam keadaan terkompresi (GD, tanpa paket baru).
 *
 * Latar: foto kamera HP umumnya 4–10 MB sehingga menabrak batas validasi
 * unggah (galeri web sekolah menolak dengan "The foto.0 field must not be
 * greater than 4096 kilobytes"). Sekarang batas terima dinaikkan dan
 * berkasnya dikecilkan di server: sisi terpanjang dibatasi, orientasi
 * EXIF diperbaiki, hasil disimpan sebagai JPEG kualitas 82.
 *
 * Bila berkas tidak bisa diproses GD (format tak didukung / GD tidak
 * tersedia), berkas asli disimpan apa adanya — unggahan tidak boleh
 * gagal hanya karena kompresornya menyerah.
 */
class KompresGambar
{
  /** Simpan dengan nama acak. Mengembalikan path relatif di disk public. */
  public static function simpan(UploadedFile $file, string $folder, int $maksSisi = 1600, int $kualitas = 82): string
  {
    $hasil = static::proses($file, $maksSisi, $kualitas);

    if ($hasil === null) {
      return $file->store($folder, 'public');
    }

    $path = trim($folder, '/') . '/' . Str::random(24) . '.jpg';
    Storage::disk('public')->put($path, $hasil);

    return $path;
  }

  /** Simpan dengan nama tetap (tanpa ekstensi) — pola foto siswa NISN.jpg. */
  public static function simpanDenganNama(UploadedFile $file, string $folder, string $nama, int $maksSisi = 1200, int $kualitas = 82): string
  {
    $hasil = static::proses($file, $maksSisi, $kualitas);

    if ($hasil === null) {
      return $file->storeAs($folder, $nama . '.' . $file->getClientOriginalExtension(), 'public');
    }

    $path = trim($folder, '/') . '/' . $nama . '.jpg';
    Storage::disk('public')->put($path, $hasil);

    return $path;
  }

  /** Isi JPEG terkompresi, atau null bila tidak bisa / tidak perlu diproses. */
  private static function proses(UploadedFile $file, int $maksSisi, int $kualitas): ?string
  {
    if (!extension_loaded('gd')) {
      return null;
    }

    $pathAsli = $file->getRealPath();
    if (!$pathAsli) {
      return null;
    }

    $info = @getimagesize($pathAsli);
    if ($info === false) {
      return null;
    }

    $sumber = match ($info[2]) {
      IMAGETYPE_JPEG => @imagecreatefromjpeg($pathAsli),
      IMAGETYPE_PNG => @imagecreatefrompng($pathAsli),
      IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($pathAsli) : false,
      IMAGETYPE_GIF => @imagecreatefromgif($pathAsli),
      default => false,
    };
    if (!$sumber) {
      return null;
    }

    // Perbaiki orientasi dari EXIF (foto HP sering tersimpan miring).
    if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
      $exif = @exif_read_data($pathAsli);
      $orientasi = (int) ($exif['Orientation'] ?? 1);
      $sumber = match ($orientasi) {
        3 => imagerotate($sumber, 180, 0),
        6 => imagerotate($sumber, -90, 0),
        8 => imagerotate($sumber, 90, 0),
        2 => static::balik($sumber, IMG_FLIP_HORIZONTAL),
        4 => static::balik($sumber, IMG_FLIP_VERTICAL),
        5 => static::balik(imagerotate($sumber, -90, 0), IMG_FLIP_HORIZONTAL),
        7 => static::balik(imagerotate($sumber, 90, 0), IMG_FLIP_HORIZONTAL),
        default => $sumber,
      };
      if (!$sumber) {
        return null;
      }
    }

    $lebar = imagesx($sumber);
    $tinggi = imagesy($sumber);
    $skala = min(1.0, $maksSisi / max($lebar, $tinggi));

    // Berkas yang dimensinya sudah pas dan ukurannya kecil tidak
    // perlu di-encode ulang — hasilnya hanya akan lebih buruk /
    // lebih besar, dan di shared hosting ini menghemat CPU server
    // (kasus umum: foto yang sudah dikompresi browser lebih dulu).
    if ($skala >= 1.0 && $file->getSize() <= 1_500_000) {
      imagedestroy($sumber);
      return null;
    }

    $lebarBaru = max(1, (int) round($lebar * $skala));
    $tinggiBaru = max(1, (int) round($tinggi * $skala));

    $kanvas = imagecreatetruecolor($lebarBaru, $tinggiBaru);
    // Ratakan transparansi PNG/WebP ke putih (keluaran JPEG tidak punya alpha).
    $putih = imagecolorallocate($kanvas, 255, 255, 255);
    imagefill($kanvas, 0, 0, $putih);
    imagecopyresampled($kanvas, $sumber, 0, 0, 0, 0, $lebarBaru, $tinggiBaru, $lebar, $tinggi);
    imagedestroy($sumber);

    ob_start();
    imagejpeg($kanvas, null, $kualitas);
    $hasil = ob_get_clean();
    imagedestroy($kanvas);

    return $hasil !== '' ? $hasil : null;
  }

  private static function balik($gambar, int $mode)
  {
    imageflip($gambar, $mode);
    return $gambar;
  }
}
