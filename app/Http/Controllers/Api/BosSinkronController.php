<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BosAnggaran;
use App\Models\BosKasNota;
use App\Models\BosKasUmum;
use App\Models\BosPenjab;
use App\Models\BosRapbs;
use App\Models\BosRapbsPeriode;
use App\Models\BosRefAcuanBarang;
use App\Models\BosRefBku;
use App\Models\BosRefKode;
use App\Models\BosRefPeriode;
use App\Models\BosRefRekening;
use App\Models\BosRefSatuan;
use App\Models\BosSinkronLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Penerima sinkronisasi Kelola BOS.
 *
 * Agen di laptop ARKAS mengirim baris-baris cermin per tabel:
 *   POST /api/bos/sinkron/{tabel}
 *   { "agen_versi": "4.0.0", "baris": [ { "id_arkas": "...", ... } ] }
 *
 * Setiap baris di-upsert berdasarkan (sekolah_id, id_arkas);
 * sekolah_id TIDAK dipercaya dari kiriman — ia berasal dari
 * token (BosSinkronTokenMiddleware). Hanya kolom fillable
 * model yang diterima; kolom lain dibuang diam-diam. Satu
 * baris bos_sinkron_log ditulis per permintaan.
 *
 * Fase 1.5: penyimpanan memakai upsert massal (satu query
 * per potongan 500 baris) menggantikan updateOrCreate per
 * baris — sinkron penuh ±78 ribu baris (termasuk 60 ribu
 * ref_acuan_barang) kini jauh lebih cepat. Nilai tanggal
 * dinormalkan ke format "Y-m-d H:i:s" / "Y-m-d" mengikuti
 * casts model, karena upsert massal tidak melewati casts
 * Eloquent (ARKAS kadang mengirim format ISO 8601 dengan
 * zona waktu, yang ditolak kolom datetime MySQL).
 */
class BosSinkronController extends Controller
{
  /** Peta slug rute → model cermin. */
  private array $tabel = [
    'kas-umum' => BosKasUmum::class,
    'kas-nota' => BosKasNota::class,
    'rapbs' => BosRapbs::class,
    'rapbs-periode' => BosRapbsPeriode::class,
    'ref-periode' => BosRefPeriode::class,
    'anggaran' => BosAnggaran::class,
    'ref-rekening' => BosRefRekening::class,
    'ref-kode' => BosRefKode::class,
    'ref-bku' => BosRefBku::class,
    'ref-acuan-barang' => BosRefAcuanBarang::class,
    'ref-satuan' => BosRefSatuan::class,
    'penjab' => BosPenjab::class,
  ];

  /** Ukuran potongan per query upsert. */
  private const POTONGAN = 500;

  public function simpan(Request $request, string $tabel): JsonResponse
  {
    $model = $this->tabel[$tabel] ?? null;
    abort_unless($model, 404);

    $sekolahId = (int) $request->attributes->get('bos_sekolah_id');
    abort_unless($sekolahId, 401);

    $data = $request->validate([
      'agen_versi' => 'nullable|string|max:20',
      'baris' => 'required|array|max:5000',
      'baris.*.id_arkas' => 'required|string|max:60',
    ]);

    // Baris dibaca mentah dari request: validate() hanya
    // mengembalikan kunci yang tercakup aturan, sedangkan
    // kolom cermin lain (nama_bku, uraian, ...) harus lolos
    // ke penyaringan fillable di bawah.
    $semuaBaris = $request->input('baris', []);

    $contoh = new $model;
    $fillable = $contoh->getFillable();
    $casts = $contoh->getCasts();
    $sekarang = now()->format('Y-m-d H:i:s');

    $siap = [];
    foreach ($semuaBaris as $barisMentah) {
      if (!is_array($barisMentah) || empty($barisMentah['id_arkas'])) {
        continue;
      }
      $atribut = array_intersect_key($barisMentah, array_flip($fillable));
      unset($atribut['sekolah_id']);
      $atribut = $this->normalkanNilai($atribut, $casts);
      $atribut['sekolah_id'] = $sekolahId;
      $atribut['created_at'] = $sekarang;
      $atribut['updated_at'] = $sekarang;
      // Kunci ganda dalam satu kiriman: yang terakhir menang.
      $siap[(string) $atribut['id_arkas']] = $atribut;
    }
    $siap = array_values($siap);

    // Samakan kunci semua baris (upsert menyusun kolom dari
    // baris pertama); kunci yang hilang diisi null.
    $semuaKunci = array_fill_keys(array_merge($fillable, ['created_at', 'updated_at']), null);
    $siap = array_map(fn($b) => array_merge($semuaKunci, $b), $siap);

    $kolomPerbarui = array_values(array_diff($fillable, ['sekolah_id', 'id_arkas']));
    $kolomPerbarui[] = 'updated_at';

    foreach (array_chunk($siap, self::POTONGAN) as $potongan) {
      if ($potongan) {
        $model::upsert($potongan, ['sekolah_id', 'id_arkas'], $kolomPerbarui);
      }
    }

    $tersimpan = count($siap);

    BosSinkronLog::create([
      'sekolah_id' => $sekolahId,
      'tabel' => $tabel,
      'diterima' => count($semuaBaris),
      'tersimpan' => $tersimpan,
      'agen_versi' => $data['agen_versi'] ?? null,
    ]);

    return response()->json([
      'tabel' => $tabel,
      'diterima' => count($semuaBaris),
      'tersimpan' => $tersimpan,
    ]);
  }

  /**
   * Normalkan nilai mengikuti casts model: tanggal/datetime
   * diformat ulang (ISO 8601 ARKAS → format kolom), boolean
   * menjadi 0/1. Nilai yang tidak lolos parse dibiarkan apa
   * adanya agar perilaku sama seperti penyimpanan Fase 1.
   */
  private function normalkanNilai(array $atribut, array $casts): array
  {
    foreach ($atribut as $kunci => $nilai) {
      if ($nilai === null) {
        continue;
      }
      $cast = $casts[$kunci] ?? null;
      if (is_bool($nilai)) {
        $atribut[$kunci] = $nilai ? 1 : 0;
      } elseif ($cast === 'datetime' || $cast === 'immutable_datetime' || $cast === 'date' || $cast === 'immutable_date') {
        try {
          $atribut[$kunci] = Carbon::parse((string) $nilai)
            ->format(str_contains($cast, 'date') && !str_contains($cast, 'datetime') ? 'Y-m-d' : 'Y-m-d H:i:s');
        } catch (\Throwable) {
          // biarkan nilai asli
        }
      }
    }
    return $atribut;
  }
}
