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

    // Di semester aktif, daftar anggota hanya yang masih berjalan
    // (end_date kosong). Di semester lampau, seluruh anggota rombel
    // adalah daftar historisnya — end_date mereka memang sudah
    // ditutup oleh proses kenaikan kelas, jadi menyaring end_date
    // kosong akan mengosongkan daftar.
    $semesterAktifId = Semester::where('is_active', true)->value('id');
    $riwayat = $semesterAktifId
      && (int) $rombel->semester_id !== (int) $semesterAktifId;

    $anggota = RombelAnggota::with('siswa.registrasiTerbaru')
      ->where('rombel_id', $rombel_id)
      ->when(!$riwayat, fn($q) => $q->whereNull('end_date'))
      ->get()
      ->map(function ($item) {
        return [
          'id'         => $item->id,
          'start_date' => $item->start_date,
          'end_date'   => $item->end_date,
          'status'     => $item->end_date ? 'Selesai' : 'Aktif',
          'siswa'      => [
            'id'           => $item->siswa->id,
            'nama_siswa'   => $item->siswa->nama_siswa,
            'jenis_kelamin' => $item->siswa->jenis_kelamin,
            'nisn'         => $item->siswa->registrasiTerbaru->nisn ?? '-',
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

    $isGanjil = substr($semesterSekarang->kode_semester, -1) === '1';

    $query = Siswa::query();

    // [A] Registrasi terbaru harus aktif & sekolahnya sama
    // SekolahScope di SiswaRegistrasi sudah memfilter sekolah otomatis,
    // tapi kita tetap eksplisit filter sekolah_id untuk keamanan
    $query->whereHas('registrasiTerbaru', function ($q) use ($rombel) {
      $q->where('sekolah_id', $rombel->sekolah_id)
        ->where('status', 'aktif');
    });

    // [B] Belum terdaftar aktif di rombel manapun pada semester yang sama
    $query->whereDoesntHave('rombelAnggota', function ($q) use ($semesterSekarang) {
      $q->where('semester_id', $semesterSekarang->id)
        ->whereNull('end_date');
    });

    // [C] Logika kelayakan tingkat
    if ($isGanjil) {
      $query->where(function ($q) use ($rombel, $semesterSekarang) {

        // C1. Siswa BARU: belum pernah punya rombel AKTIF sama sekali
        // Perbaikan: doesntHave → whereDoesntHave dengan filter end_date
        // agar siswa yang pernah keluar dari rombel tetap bisa masuk
        $q->where(function ($qBaru) use ($rombel) {
          $qBaru->whereDoesntHave('rombelAnggota', function ($qa) {
            $qa->whereNull('end_date'); // tidak punya rombel aktif
          })
            ->whereHas('registrasiTerbaru', function ($qr) use ($rombel) {
              $qr->where('tingkat', $rombel->tingkat);
            });
        })

          // C2 & C3. Siswa NAIK KELAS atau TINGGAL KELAS
          ->orWhereHas('rombelAnggota', function ($q) use ($rombel, $semesterSekarang) {
            $q->whereHas('rombel', function ($qr) use ($rombel, $semesterSekarang) {
              $qr->whereIn('tingkat', [
                $rombel->tingkat,      // tinggal kelas
                $rombel->tingkat - 1,  // naik kelas
              ])
                ->whereHas('semester', function ($qs) use ($semesterSekarang) {
                  $qs->where('tahun_pelajaran', $semesterSekarang->tahun_pelajaran);
                });
            });
          });
      });
    } else {
      $idSemesterGanjil = substr($semesterSekarang->kode_semester, 0, -1) . '1';

      $query->where(function ($q) use ($rombel, $idSemesterGanjil, $semesterSekarang) {

        // D1. Siswa REGULER: ada di rombel tingkat sama di semester ganjil ini
        $q->whereHas('rombelAnggota', function ($qa) use ($rombel, $idSemesterGanjil) {
          $qa->whereHas('rombel', function ($qr) use ($rombel, $idSemesterGanjil) {
            $qr->where('tingkat', $rombel->tingkat)
              ->whereHas('semester', function ($qs) use ($idSemesterGanjil) {
                $qs->where('kode_semester', $idSemesterGanjil);
              });
          });
        })

          // D2. Siswa BARU/MUTASI: tidak punya rombel AKTIF sama sekali
          // Perbaikan: doesntHave → whereDoesntHave dengan filter end_date
          ->orWhere(function ($qb) use ($rombel) {
            $qb->whereDoesntHave('rombelAnggota', function ($qa) {
              $qa->whereNull('end_date'); // tidak punya rombel aktif
            })
              ->whereHas('registrasiTerbaru', function ($qr) use ($rombel) {
                $qr->where('tingkat', $rombel->tingkat);
              });
          });
      });
    }

    $siswa = $query->with(['registrasiTerbaru'])->get();

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
      'start_date'  => 'nullable|date',
    ]);

    $rombel   = Rombel::with('semester')->findOrFail($request->rombel_id);
    $semester = $rombel->semester;

    // Tanggal mulai boleh dipilih lewat form; kosong = tanggal hari ini
    $startDate = $request->input('start_date') ?: now()->toDateString();

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

      foreach ($request->siswa_ids as $siswa_id) {
        // Cek duplikasi: siswa masih aktif di rombel manapun semester ini
        $sudahAktif = RombelAnggota::where('siswa_id', $siswa_id)
          ->where('semester_id', $semester->id)
          ->whereNull('end_date')
          ->exists();

        if ($sudahAktif) {
          $skipped++;
          continue;
        }

        $dataToInsert[] = [
          'siswa_id'    => $siswa_id,
          'rombel_id'   => $request->rombel_id,
          'semester_id' => $semester->id,
          'start_date'  => $startDate,
          'end_date'    => null,
          'created_at'  => now(),
          'updated_at'  => now(),
        ];
      }

      if (!empty($dataToInsert)) {
        RombelAnggota::insert($dataToInsert);
      }

      DB::commit();

      return response()->json([
        'message'  => 'Berhasil menambahkan ' . count($dataToInsert) . ' siswa ke rombel.',
        'ditambah' => count($dataToInsert),
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

    // Proteksi: siswa memang sedang aktif di rombel ini
    if (!is_null($anggota->end_date)) {
      return response()->json([
        'message' => 'Siswa ini sudah tidak aktif di rombel ini.'
      ], 409);
    }

    // Tanggal selesai boleh dipilih lewat form; kosong = tanggal hari ini
    $request->validate(['end_date' => 'nullable|date']);
    $endDate = $request->input('end_date') ?: now()->toDateString();

    if ($endDate < $anggota->start_date) {
      return response()->json([
        'message' => 'Tanggal selesai tidak boleh sebelum tanggal mulai (' . $anggota->start_date . ').'
      ], 422);
    }

    // Isi end_date — record tetap ada sebagai history perpindahan
    $anggota->update(['end_date' => $endDate]);

    return response()->json([
      'message'  => "Siswa {$anggota->siswa->nama_siswa} berhasil dikeluarkan dari rombel {$anggota->rombel->nama_rombel}.",
      'siswa'    => $anggota->siswa->nama_siswa,
      'rombel'   => $anggota->rombel->nama_rombel,
      'end_date' => $anggota->end_date,
    ]);
  }

  // =========================================================================
  // RIWAYAT PERPINDAHAN ROMBEL SISWA
  // =========================================================================
  public function riwayatSiswa($siswa_id)
  {
    // Pastikan siswa ada
    $siswa = Siswa::findOrFail($siswa_id);

    $riwayat = RombelAnggota::with(['rombel.semester'])
      ->where('siswa_id', $siswa_id)
      ->orderBy('start_date', 'asc')
      ->get()
      ->map(function ($item) {
        return [
          'id'         => $item->id,
          'rombel'     => $item->rombel->nama_rombel,
          'tingkat'    => $item->rombel->tingkat,
          'semester'   => $item->rombel->semester
            ? $item->rombel->semester->tahun_pelajaran . ' - ' . $item->rombel->semester->semester
            : '-',
          'start_date' => $item->start_date,
          'end_date'   => $item->end_date,
          'status'     => is_null($item->end_date) ? 'Aktif' : 'Keluar',
        ];
      });

    return response()->json([
      'siswa_id'   => $siswa_id,
      'nama_siswa' => $siswa->nama_siswa,
      'riwayat'    => $riwayat
    ]);
  }
}
