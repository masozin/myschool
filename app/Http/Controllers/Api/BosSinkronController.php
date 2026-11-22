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
use Illuminate\Support\Facades\Schema;

/**
 * Penerima sinkronisasi Kelola BOS.
 *
 * Agen di laptop ARKAS mengirim baris-baris cermin per tabel:
 *   POST /api/bos/sinkron/{tabel}
 *   { "agen_versi": "4.0.0", "baris": [ { "id_arkas": "...", ... } ] }
 *
 * Setiap baris tabel sekolah di-upsert berdasarkan
 * (sekolah_id, id_arkas); sekolah_id TIDAK dipercaya
 * dari kiriman — ia berasal dari token
 * (BosSinkronTokenMiddleware). Enam tabel referensi
 * bersifat GLOBAL (Fase 1.13): di-upsert berdasarkan
 * id_arkas saja dan dibagikan ke semua sekolah. Hanya
 * kolom fillable model yang diterima; kolom lain
 * dibuang diam-diam. Satu baris bos_sinkron_log
 * ditulis per permintaan.
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

  /**
   * Slug tabel referensi GLOBAL (Fase 1.13): satu salinan
   * bersama untuk semua sekolah — penyimpanan dan sidik
   * jari cek tidak dilingkupkan ke sekolah token.
   */
  private const TABEL_GLOBAL = [
    'ref-periode',
    'ref-rekening',
    'ref-kode',
    'ref-bku',
    'ref-acuan-barang',
    'ref-satuan',
  ];

  /**
   * Sidik jari cermin untuk sinkron delta (Fase 1.9).
   *
   *   GET /api/bos/sinkron/cek
   *
   * Agen membandingkan jumlah baris + arkas_last_update
   * maksimum per tabel dengan keadaan database ARKAS lokal;
   * tabel yang sama persis dilewati, sisanya dikirim delta.
   * Jawaban: peta slug → { jumlah, maks_update }.
   */
  public function cek(Request $request): JsonResponse
  {
    $sekolahId = (int) $request->attributes->get('bos_sekolah_id');
    abort_unless($sekolahId, 401);

    $hasil = [];
    foreach ($this->tabel as $slug => $model) {
      // Tabel referensi global: sidik jari dihitung dari
      // salinan bersama, bukan salinan sekolah ini — agen
      // sekolah kedua yang katalognya sama akan melihat
      // sidik jari yang cocok dan melewati tabel-tabel ini.
      $query = in_array($slug, self::TABEL_GLOBAL, true)
        ? $model::query()
        : $model::where('sekolah_id', $sekolahId);
      // bos_ref_satuan tidak punya kolom arkas_last_update
      // (satu-satunya cermin tanpa kolom itu): sidik jarinya
      // cukup jumlah baris, maks_update selalu null.
      if (Schema::hasColumn((new $model)->getTable(), 'arkas_last_update')) {
        $agregat = $query
          ->selectRaw('COUNT(*) AS jumlah, MAX(arkas_last_update) AS maks_update')
          ->first();
        $maks = $agregat->maks_update !== null ? (string) $agregat->maks_update : null;
        $jumlah = (int) ($agregat->jumlah ?? 0);
      } else {
        $jumlah = $query->count();
        $maks = null;
      }
      $hasil[$slug] = [
        'jumlah' => $jumlah,
        'maks_update' => $maks,
      ];
    }

    return response()->json($hasil);
  }

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

    $global = in_array($tabel, self::TABEL_GLOBAL, true);

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
      if (!$global) {
        $atribut['sekolah_id'] = $sekolahId;
      }
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

    $kunciUpsert = $global ? ['id_arkas'] : ['sekolah_id', 'id_arkas'];
    foreach (array_chunk($siap, self::POTONGAN) as $potongan) {
      if ($potongan) {
        $model::upsert($potongan, $kunciUpsert, $kolomPerbarui);
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
