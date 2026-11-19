<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BosAnggaran;
use App\Models\BosKasNota;
use App\Models\BosKasUmum;
use App\Models\BosPenjab;
use App\Models\BosRapbs;
use App\Models\BosRefBku;
use App\Models\BosRefKode;
use App\Models\BosRefRekening;
use App\Models\BosSinkronLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Penerima sinkronisasi Kelola BOS.
 *
 * Agen di laptop ARKAS mengirim baris-baris cermin per tabel:
 *   POST /api/bos/sinkron/{tabel}
 *   { "agen_versi": "1.0.0", "baris": [ { "id_arkas": "...", ... } ] }
 *
 * Setiap baris di-upsert berdasarkan (sekolah_id, id_arkas);
 * sekolah_id TIDAK dipercaya dari kiriman — ia berasal dari
 * token (BosSinkronTokenMiddleware). Hanya kolom fillable
 * model yang diterima; kolom lain dibuang diam-diam. Satu
 * baris bos_sinkron_log ditulis per permintaan.
 */
class BosSinkronController extends Controller
{
  /** Peta slug rute → model cermin. */
  private array $tabel = [
    'kas-umum' => BosKasUmum::class,
    'kas-nota' => BosKasNota::class,
    'rapbs' => BosRapbs::class,
    'anggaran' => BosAnggaran::class,
    'ref-rekening' => BosRefRekening::class,
    'ref-kode' => BosRefKode::class,
    'ref-bku' => BosRefBku::class,
    'penjab' => BosPenjab::class,
  ];

  public function simpan(Request $request, string $tabel): JsonResponse
  {
    $model = $this->tabel[$tabel] ?? null;
    abort_unless($model, 404);

    $sekolahId = (int) $request->attributes->get('bos_sekolah_id');
    abort_unless($sekolahId, 401);

    $data = $request->validate([
      'agen_versi' => 'nullable|string|max:20',
      'baris' => 'required|array|max:5000',
      'baris.*.id_arkas' => 'required|string|max:40',
    ]);

    // Baris dibaca mentah dari request: validate() hanya
    // mengembalikan kunci yang tercakup aturan, sedangkan
    // kolom cermin lain (nama_bku, uraian, ...) harus lolos
    // ke penyaringan fillable di bawah.
    $semuaBaris = $request->input('baris', []);

    $fillable = (new $model)->getFillable();
    $tersimpan = 0;

    foreach ($semuaBaris as $barisMentah) {
      $atribut = array_intersect_key($barisMentah, array_flip($fillable));
      unset($atribut['sekolah_id']);
      $idArkas = $atribut['id_arkas'];
      unset($atribut['id_arkas']);

      $model::updateOrCreate(
        ['sekolah_id' => $sekolahId, 'id_arkas' => $idArkas],
        $atribut
      );
      $tersimpan++;
    }

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
}
