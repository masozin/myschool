<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Semester extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'tabel_semester';
  protected $fillable = ['kode_semester', 'semester', 'tahun_pelajaran', 'is_active', 'tanggal_mulai', 'tanggal_selesai'];

  protected $casts = [
    'is_active' => 'boolean',
    // tanggal_mulai & tanggal_selesai TIDAK di-cast di sini:
    // keduanya sudah ditangani accessor khusus dengan fallback hitung.
  ];

  public function rombel(): HasMany
  {
    return $this->hasMany(Rombel::class, 'semester_id');
  }

  public function sekolahDetail(): HasMany
  {
    return $this->hasMany(SekolahDetail::class, 'semester_id');
  }

  public function riwayatSiswa(): HasMany
  {
    return $this->hasMany(RombelAnggota::class, 'semester_id');
  }

  // NOTE: relasi kalenderPendidikan() dihapus — KalenderPendidikan tidak lagi
  // punya semester_id, sudah diputus jadi entitas independen (lihat tahun_pelajaran
  // + jenis_semester di model KalenderPendidikan).

  /**
   * Cari data Semester berdasarkan tanggal.
   * Aturan: Ganjil = Juli–Desember, Genap = Januari–Juni (tahun berikutnya).
   */
  public static function fromTanggal(string $tanggal): ?self
  {
    $date  = Carbon::parse($tanggal);
    $bulan = $date->month;

    $tahunAwal      = $bulan >= 7 ? $date->year : $date->year - 1;
    $tahunPelajaran = $tahunAwal . '/' . ($tahunAwal + 1);
    $namaSemester   = $bulan >= 7 ? 'Ganjil' : 'Genap';

    return self::where('tahun_pelajaran', $tahunPelajaran)
      ->whereRaw('LOWER(semester) = ?', [strtolower($namaSemester)])
      ->first();
  }

  /**
   * Kunci urutan kronologis semester, untuk membandingkan dua semester.
   * [tahun awal tahun pelajaran, 1 untuk Ganjil, 2 untuk Genap].
   * Dipakai salin data periodik: semester sumber harus lebih dulu
   * dari semester aktif — menyalin dari semester sesudahnya akan
   * menutup keanggotaan yang belum berjalan.
   */
  public static function kunciKronologis(self $s): array
  {
    return [
      (int) substr((string) $s->tahun_pelajaran, 0, 4),
      strtolower((string) $s->semester) === 'ganjil' ? 1 : 2,
    ];
  }

  /**
   * Accessor: $semester->tanggal_mulai
   * Kalau kolom `tanggal_mulai` di tabel sudah diisi manual, itu yang dipakai
   * (sumber kebenaran utama). Kalau masih NULL (mis. data lama / belum diisi),
   * fallback dihitung otomatis dari tahun_pelajaran ("2025/2026") + semester
   * ("Ganjil"/"Genap"), sama seperti aturan di fromTanggal():
   *   - Ganjil -> 1 Juli tahun awal
   *   - Genap  -> 1 Januari tahun akhir
   */
  public function getTanggalMulaiAttribute($value): ?Carbon
  {
    if ($value) {
      return Carbon::parse($value)->startOfDay();
    }

    return $this->rentangTanggalSemester()['mulai'] ?? null;
  }

  /**
   * Accessor: $semester->tanggal_selesai
   * Sama seperti di atas: kolom asli diprioritaskan, fallback ke konvensi:
   *   - Ganjil -> 31 Desember tahun awal
   *   - Genap  -> 30 Juni tahun akhir
   */
  public function getTanggalSelesaiAttribute($value): ?Carbon
  {
    if ($value) {
      return Carbon::parse($value)->endOfDay();
    }

    return $this->rentangTanggalSemester()['selesai'] ?? null;
  }

  /**
   * Hitung rentang tanggal semester dari tahun_pelajaran + semester.
   * Return null kalau formatnya tidak sesuai ekspektasi ("YYYY/YYYY").
   */
  private function rentangTanggalSemester(): ?array
  {
    if (! $this->tahun_pelajaran || ! $this->semester) {
      return null;
    }

    $bagian = explode('/', $this->tahun_pelajaran);
    if (count($bagian) !== 2 || ! is_numeric($bagian[0]) || ! is_numeric($bagian[1])) {
      return null;
    }

    [$tahunAwal, $tahunAkhir] = array_map('intval', $bagian);
    $namaSemester = strtolower($this->semester);

    if ($namaSemester === 'ganjil') {
      return [
        'mulai'   => Carbon::create($tahunAwal, 7, 1)->startOfDay(),
        'selesai' => Carbon::create($tahunAwal, 12, 31)->endOfDay(),
      ];
    }

    if ($namaSemester === 'genap') {
      return [
        'mulai'   => Carbon::create($tahunAkhir, 1, 1)->startOfDay(),
        'selesai' => Carbon::create($tahunAkhir, 6, 30)->endOfDay(),
      ];
    }

    return null;
  }

  public static function smtId(): ?int
  {
    return session('semester_id');
  }

  /**
   * supaya tidak menyimpan data dengan semester_id kosong.
   */
  public static function sessionSemesterId(): int
  {
    $id = self::smtId();

    if (!$id) {
      abort(422, 'Semester aktif belum dipilih.');
    }

    return $id;
  }

  /**
   * Berguna kalau butuh data semester (nama, tahun ajaran, dll), bukan cuma ID.
   */
  public static function sessionSemester(): ?self
  {
    $id = self::smtId();

    return $id ? self::find($id) : null;
  }
}
