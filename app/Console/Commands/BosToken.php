<?php

namespace App\Console\Commands;

use App\Models\BosSinkronToken;
use App\Models\Sekolah;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Buat / ganti token agen sinkron Kelola BOS untuk satu sekolah.
 *
 *   php artisan bos:token {sekolah_id}
 *
 * Token asli ditampilkan SEKALI di layar ini; yang disimpan
 * di database hanya hash sha256-nya. Menjalankan ulang
 * perintah ini mencabut token lama sekolah tersebut.
 */
class BosToken extends Command
{
  protected $signature = 'bos:token {sekolah_id : ID sekolah (tabel_sekolah.id)}';

  protected $description = 'Buat token agen sinkron Kelola BOS untuk satu sekolah (ditampilkan sekali)';

  public function handle(): int
  {
    $sekolahId = (int) $this->argument('sekolah_id');
    $sekolah = Sekolah::find($sekolahId);

    if (!$sekolah) {
      $this->error("Sekolah #{$sekolahId} tidak ditemukan.");
      return self::FAILURE;
    }

    $token = Str::random(48);

    BosSinkronToken::updateOrCreate(
      ['sekolah_id' => $sekolahId],
      ['token_hash' => hash('sha256', $token), 'terakhir_dipakai' => null]
    );

    $nama = $sekolah->detailTerbaru?->nama_sekolah ?? ('Sekolah #' . $sekolahId);
    $this->info("Token sinkron Kelola BOS untuk {$nama}:");
    $this->line('');
    $this->line('  ' . $token);
    $this->line('');
    $this->warn('Token ini hanya ditampilkan sekali. Simpan sebagai BOS_TOKEN di laptop ARKAS.');

    return self::SUCCESS;
  }
}
