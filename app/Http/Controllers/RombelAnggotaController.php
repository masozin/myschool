<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\Rombel;
use App\Models\Semester;
use App\Models\RombelAnggota;
use App\Models\SiswaRegistrasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RombelAnggotaController extends Controller
{

  use AuthorizesRequests;
  // =========================================================================
  // LIHAT DAFTAR ANGGOTA ROMBEL
  // =========================================================================
  public function rombelAnggota($rombel_id)
  {
    $rombel = Rombel::with(['semester', 'ptk'])->findOrFail($rombel_id);
    $this->authorize('view', $rombel);

    // Satu baris keanggotaan berlaku untuk semester pada baris itu
    // sendiri: daftar anggota rombel adalah seluruh barisnya, sama
    // untuk semester aktif maupun semester lampau
    // Di semester aktif, daftar anggota adalah daftar rombel yang
    // sedang berjalan: siswa yang registrasinya sudah ditutup
    // (keluar/mutasi/lulus/meninggal) tidak ditampilkan lagi, sama
    // seperti di daftar siswa — baris keanggotaannya tetap
    // tersimpan sebagai catatan semester itu. Di semester lampau
    // daftarnya adalah daftar historis apa adanya.
    $anggota = RombelAnggota::with('siswa.registrasiTerbaru')
      ->where('rombel_id', $rombel_id)
      ->when($rombel->semester?->is_active, fn($q) => $q->whereHas('siswa.registrasiTerbaru', fn($qr) => $qr->where('status', 'aktif')))
      ->get()
      ->map(function ($item) {
        return [
          'id'         => $item->id,
          'siswa'      => [
            'id'           => $item->siswa->id,
            'nama_siswa'   => $item->siswa->nama_siswa,
            'jenis_kelamin' => $item->siswa->jenis_kelamin,
            'nisn'         => $item->siswa->nisn ?: '-',
            'nis'          => $item->siswa->registrasiTerbaru->nis  ?? '-',
          ],
        ];
      });

    return response()->json([
      'rombel'  => $rombel,
      'anggota' => $anggota,
    ]);
  }

  // =========================================================================
  // LIST SISWA YANG TERSEDIA UNTUK DIMASUKKAN KE ROMBEL
  // =========================================================================
  public function listSiswaTersedia($rombel_id)
  {
    $rombel           = Rombel::with('semester')->findOrFail($rombel_id);
    $semesterSekarang = $rombel->semester;
    $semesterAktiv    = session('semester_id');

    // Cek izin: hanya yang boleh kelola anggota rombel ini
    $this->authorize('manageMembers', $rombel);

    // Proteksi: tidak bisa cari siswa jika semester sudah tidak aktif
    if (!$semesterSekarang->is_active) {
      return response()->json([
        'message' => 'Semester sudah tidak aktif. Tidak dapat menambah siswa ke rombel.'
      ], 403);
    }

    $query = Siswa::query();

    // [A] Registrasi terbaru harus aktif & sekolahnya sama
    // SekolahScope di SiswaRegistrasi sudah memfilter sekolah otomatis,
    // tapi kita tetap eksplisit filter sekolah_id untuk keamanan
    $query->whereHas('registrasiTerbaru', function ($q) use ($rombel) {
      $q->where('sekolah_id', $rombel->sekolah_id)
        ->where('status', 'aktif');
    });

    // [B] Belum terdaftar di rombel manapun pada semester yang sama
    $query->whereDoesntHave('rombelAnggota', function ($q) use ($rombel, $semesterSekarang) {
      // Baris di sekolah LAIN pada semester yang sama (mutasi
      // masuk tengah semester) tidak menghalangi penempatan di sini
      $q->where('semester_id', $semesterSekarang->id)
        ->where('sekolah_id', $rombel->sekolah_id);
    });

    // [C] Kelayakan tingkat — dinilai dari bukti terakhir siswa:
    // - tingkat pada registrasinya sama dengan tingkat rombel ini, atau
    // - keanggotaan terakhirnya SEBELUM semester ini menunjukkan ia
    //   memang berada di tingkat ini: tingkatnya sama (pindah rombel
    //   di tengah jalan, atau tinggal kelas), atau tingkatnya
    //   ditambah selisih tahun pelajaran sejak keanggotaan itu sama
    //   dengan tingkat ini (naik kelas).
    // Penilaiannya di PHP karena "keanggotaan terakhir" mengikuti
    // urutan kronologis semester. Mengeluarkan siswa dari rombel
    // kini menghapus barisnya, jadi bukti yang dipakai adalah baris
    // terakhir yang masih tersisa (semester-semester sebelumnya).
    $semuaSemester = Semester::all()->keyBy('id');
    $kunciSemester = function ($sem) {
      if (!$sem) return 0;
      $k = Semester::kunciKronologis($sem);
      return $k[0] * 10 + $k[1];
    };
    $kunciTarget = $kunciSemester($semesterSekarang);
    $tahunTarget = (int) substr($semesterSekarang->tahun_pelajaran, 0, 4);

    $siswa = $query->with(['registrasiTerbaru', 'rombelAnggota.rombel.semester'])->get()
      ->filter(function ($s) use ($rombel, $semuaSemester, $kunciSemester, $kunciTarget, $tahunTarget) {
        // Tingkat yang dinyatakan pada registrasi siswa
        if ((int) ($s->registrasiTerbaru?->tingkat ?? 0) === (int) $rombel->tingkat) {
          return true;
        }

        // Keanggotaan terakhir sebelum semester target
        $terakhir = $s->rombelAnggota
          ->map(function ($a) use ($semuaSemester, $kunciSemester) {
            $a->semester_urut = $kunciSemester($a->rombel?->semester ?? $semuaSemester->get($a->semester_id));
            return $a;
          })
          ->filter(fn($a) => $a->semester_urut > 0 && $a->semester_urut < $kunciTarget)
          ->sortByDesc('semester_urut')
          ->first();

        if (!$terakhir || !$terakhir->rombel) {
          return false;
        }

        $semTerakhir = $terakhir->rombel->semester ?? $semuaSemester->get($terakhir->semester_id);
        $selisihTp   = $tahunTarget - (int) substr($semTerakhir->tahun_pelajaran, 0, 4);
        $tingkatLalu = (int) $terakhir->rombel->tingkat;

        return $tingkatLalu === (int) $rombel->tingkat
          || $tingkatLalu + $selisihTp === (int) $rombel->tingkat;
      })
      ->values();

    return response()->json([
      'rombel_target'  => $rombel,
      'semester_aktif' => $semesterSekarang,
      'siswa_tersedia' => $siswa
    ]);
  }

  // =========================================================================
  // TAMBAH SISWA KE ROMBEL
  // =========================================================================
  public function store(Request $request)
  {
    $request->validate([
      'rombel_id'   => 'required|exists:tabel_rombel,id',
      'siswa_ids'   => 'required|array',
      'siswa_ids.*' => 'exists:tabel_siswa,id',
    ]);

    $rombel   = Rombel::with('semester')->findOrFail($request->rombel_id);
    $semester = $rombel->semester;

    // Cek izin: hanya yang boleh kelola anggota rombel ini
    $this->authorize('manageMembers', $rombel);

    // Proteksi: tidak bisa menambah siswa jika semester sudah tidak aktif
    if (!$semester->is_active) {
      return response()->json([
        'message' => 'Semester sudah tidak aktif. Tidak dapat menambah siswa ke rombel.'
      ], 403);
    }

    try {
      DB::beginTransaction();

      $dataToInsert = [];
      $skipped      = 0;
      $dipindah     = 0;

      foreach ($request->siswa_ids as $siswa_id) {
        // Satu baris per siswa per semester: bila siswa sudah punya
        // baris di semester ini pada rombel LAIN, baris semester itu
        // DIPINDAH ke rombel ini (di-update, bukan dibuatkan baris
        // kedua); bila sudah di rombel ini juga, lewati
        $barisAda = RombelAnggota::where('siswa_id', $siswa_id)
          ->where('semester_id', $semester->id)
          ->where('sekolah_id', $rombel->sekolah_id)
          ->first();

        if ($barisAda) {
          if ((int) $barisAda->rombel_id === (int) $request->rombel_id) {
            $skipped++;
          } else {
            $barisAda->update(['rombel_id' => $request->rombel_id]);
            $dipindah++;
          }
          continue;
        }

        $dataToInsert[] = [
          'siswa_id'    => $siswa_id,
          'rombel_id'   => $request->rombel_id,
          'semester_id' => $semester->id,
          'sekolah_id'  => $rombel->sekolah_id,
          'created_at'  => now(),
          'updated_at'  => now(),
        ];
      }

      if (!empty($dataToInsert)) {
        RombelAnggota::insert($dataToInsert);
      }

      DB::commit();

      $ringkasan = [];
      if (count($dataToInsert)) $ringkasan[] = count($dataToInsert) . ' siswa ditambahkan';
      if ($dipindah) $ringkasan[] = $dipindah . ' siswa dipindahkan dari rombel lain di semester ini';
      if ($skipped) $ringkasan[] = $skipped . ' siswa dilewati (sudah di rombel ini)';

      return response()->json([
        'message'  => 'Berhasil: ' . (implode(', ', $ringkasan) ?: 'tidak ada perubahan') . '.',
        'ditambah' => count($dataToInsert),
        'dipindah' => $dipindah,
        'dilewati' => $skipped,
      ]);
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json([
        'message' => 'Gagal menyimpan data',
        'error'   => $e->getMessage()
      ], 500);
    }
  }

  // =========================================================================
  // KELUARKAN SISWA DARI ROMBEL
  // =========================================================================
  public function destroy(Request $request, $id)
  {
    $anggota = RombelAnggota::with(['siswa', 'rombel.semester'])->findOrFail($id);

    // Cek izin: hanya yang boleh kelola anggota rombel ini
    $this->authorize('manageMembers', $anggota->rombel);

    // Proteksi: tidak bisa mengeluarkan siswa jika semester sudah tidak aktif
    if (!$anggota->rombel->semester->is_active) {
      return response()->json([
        'message' => 'Semester sudah tidak aktif. Data anggota rombel tidak dapat diubah.'
      ], 403);
    }

    // Keanggotaan dihapus sungguhan: tanpa tanggal mulai/selesai,
    // mengeluarkan siswa berarti baris keanggotaannya di rombel ini
    // hilang. Jejak pindah rombel di tengah semester yang sama
    // memang tidak disimpan pada model ini.
    $namaSiswa  = $anggota->siswa->nama_siswa;
    $namaRombel = $anggota->rombel->nama_rombel;

    $anggota->delete();

    return response()->json([
      'message' => "Siswa {$namaSiswa} berhasil dikeluarkan dari rombel {$namaRombel}.",
      'siswa'   => $namaSiswa,
      'rombel'  => $namaRombel,
    ]);
  }

  // =========================================================================
  // KELUARKAN BANYAK SISWA DARI ROMBEL SEKALIGUS
  // =========================================================================
  public function destroyMassal(Request $request)
  {
    $request->validate([
      'rombel_id'     => 'required|exists:tabel_rombel,id',
      'anggota_ids'   => 'required|array|min:1',
      'anggota_ids.*' => 'integer',
    ]);

    $rombel = Rombel::with('semester')->findOrFail($request->rombel_id);

    // Cek izin: hanya yang boleh kelola anggota rombel ini
    $this->authorize('manageMembers', $rombel);

    // Proteksi: tidak bisa mengubah anggota jika semester sudah tidak aktif
    if (!$rombel->semester->is_active) {
      return response()->json([
        'message' => 'Semester sudah tidak aktif. Data anggota rombel tidak dapat diubah.'
      ], 403);
    }

    // Hanya baris yang memang milik rombel ini yang dihapus
    $jumlah = RombelAnggota::where('rombel_id', $rombel->id)
      ->whereIn('id', $request->anggota_ids)
      ->delete();

    return response()->json([
      'message' => $jumlah . ' siswa berhasil dikeluarkan dari rombel ' . $rombel->nama_rombel . '.',
      'dihapus' => $jumlah,
    ]);
  }

  // =========================================================================
  // RIWAYAT PERPINDAHAN ROMBEL SISWA
  // =========================================================================
  public function riwayatSiswa($siswa_id)
  {
    // Pastikan siswa ada
    $siswa = Siswa::findOrFail($siswa_id);

    $semesterAktifId = Semester::where('is_active', true)->value('id');

    $riwayat = RombelAnggota::with(['rombel.semester'])
      ->where('siswa_id', $siswa_id)
      ->get()
      // Urutan KRONOLOGIS semester (tahun pelajaran, lalu
      // Ganjil sebelum Genap) — BUKAN semester_id: id semester
      // mengikuti urutan pembuatannya di basis data, sehingga
      // semester lama yang dibuat belakangan (mis. saat mengimpor
      // riwayat) akan tampak acak bila diurutkan berdasar id
      ->sortBy(function ($item) {
        $sem = $item->rombel?->semester;
        if (!$sem) {
          return PHP_INT_MAX;
        }
        $k = Semester::kunciKronologis($sem);
        return $k[0] * 10 + $k[1];
      })
      ->values()
      ->map(function ($item) use ($semesterAktifId) {
        return [
          'id'         => $item->id,
          'rombel'     => $item->rombel->nama_rombel,
          'tingkat'    => $item->rombel->tingkat,
          'semester'   => $item->rombel->semester
            ? $item->rombel->semester->tahun_pelajaran . ' - ' . $item->rombel->semester->semester
            : '-',
          'status'     => (int) $item->semester_id === (int) $semesterAktifId ? 'Aktif' : 'Selesai',
        ];
      });

    return response()->json([
      'siswa_id'   => $siswa_id,
      'nama_siswa' => $siswa->nama_siswa,
      'riwayat'    => $riwayat
    ]);
  }
}
