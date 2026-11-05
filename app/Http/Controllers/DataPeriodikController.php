<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RombelAnggota;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Models\SiswaRegistrasi;
use App\Models\SekolahDetail;
use App\Models\Rombel;
use App\Models\Semester;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DataPeriodikController extends Controller
{
  /**
   * Menampilkan Form Salin Data.
   *
   * Pengguna memilih sendiri semester sumbernya (bawaan: semester
   * persis sebelum semester aktif) dan data apa saja yang disalin.
   * Semester sumber dibatasi hanya untuk semester sebelum semester
   * aktif: menyalin dari semester sesudahnya akan menutup
   * keanggotaan yang belum berjalan.
   */
  public function showCopyForm()
  {
    $user = Auth::user();

    if (!$user->ptk) {
      return redirect()->route('home')
        ->with('error', 'Halaman salin data periodik hanya untuk akun yang terhubung ke PTK (operator sekolah).');
    }

    $sekolahId        = $user->ptk->sekolah_id;
    $semesterSekarang = Semester::smtId();

    // Urutan kronologis semester: tahun awal tahun pelajaran,
    // lalu Ganjil sebelum Genap dalam tahun yang sama.
    $semuaSemester = Semester::all()
      ->sortBy(fn(Semester $s) => Semester::kunciKronologis($s))
      ->values();

    // Posisi semester aktif dalam urutan kronologis; semester
    // sesudahnya (termasuk yang aktif sendiri) bukan sumber salin.
    $posisiSekarang = $semuaSemester->search(
      fn(Semester $s) => (int) $s->id === (int) $semesterSekarang
    );
    if ($posisiSekarang === false) {
      $posisiSekarang = null;
    }

    $statusTarget = $semesterSekarang
      ? SekolahDetail::status($sekolahId, (int) $semesterSekarang)
      : ['hasDetail' => false, 'hasRombel' => false];

    $semesterAktifModel = $posisiSekarang !== null ? $semuaSemester[$posisiSekarang] : null;
    $tahunAktif   = $semesterAktifModel
      ? (int) substr((string) $semesterAktifModel->tahun_pelajaran, 0, 4)
      : null;
    $tingkatAkhir = $this->tingkatAkhirSekolah($sekolahId);

    // Kandidat semester sumber: semester sebelum semester aktif
    // yang punya detail sekolah atau rombel untuk sekolah ini.
    // Semester sesudah aktif tidak ditampilkan sama sekali.
    $sumber = [];

    foreach ($semuaSemester as $i => $smt) {
      if ($posisiSekarang !== null && $i >= $posisiSekarang) {
        continue;
      }

      // Jendela sumber: maksimal 1 tahun pelajaran di bawah semester
      // aktif — tahun pelajaran yang sama atau sebelumnya persis
      if ($tahunAktif !== null
        && (int) substr((string) $smt->tahun_pelajaran, 0, 4) < $tahunAktif - 1) {
        continue;
      }

      $detail = SekolahDetail::with(['province', 'regency', 'district', 'village'])
        ->where('sekolah_id', $sekolahId)
        ->where('semester_id', $smt->id)
        ->first();

      $rombels = Rombel::where('sekolah_id', $sekolahId)
        ->where('semester_id', $smt->id)
        ->orderBy('tingkat')
        ->orderBy('nama_rombel')
        ->get();

      if (!$detail && $rombels->isEmpty()) {
        continue;
      }

      $selisih = $tahunAktif !== null
        ? $tahunAktif - (int) substr((string) $smt->tahun_pelajaran, 0, 4)
        : 0;
      $naikKelas = $selisih > 0;

      $sumber[] = [
        'id'     => $smt->id,
        'label'  => $smt->tahun_pelajaran . ' — Semester ' . $smt->semester,
        'naik_kelas' => $naikKelas,
        'selisih' => $selisih,
        'detail' => $detail ? [
          'nama_sekolah'   => $detail->nama_sekolah,
          'kepala_sekolah' => $detail->kepala_sekolah,
          'nip'            => $detail->nip,
          'no_telp'        => $detail->no_telp,
          'alamat_sekolah' => $detail->alamat_sekolah,
          'wilayah'        => collect([
            $detail->village?->name,
            $detail->district?->name,
            $detail->regency?->name,
            $detail->province?->name,
          ])->filter()->implode(', '),
        ] : null,
        'rombel' => $rombels->map(function ($r) use ($naikKelas, $selisih, $tingkatAkhir) {
          $lulus = $naikKelas && $tingkatAkhir
            && (int) $r->tingkat + $selisih > $tingkatAkhir;
          return [
            'nama_rombel'    => $r->nama_rombel,
            'tingkat'        => $r->tingkat,
            'tingkat_baru'   => $naikKelas && !$lulus ? (int) $r->tingkat + $selisih : (int) $r->tingkat,
            'nama_baru'      => $naikKelas && !$lulus
              ? $this->namaNaikKelas($r->nama_rombel, (int) $r->tingkat, $selisih)
              : $r->nama_rombel,
            'lulus'          => (bool) $lulus,
            'jumlah_anggota' => $r->rombelAnggota()->whereNull('end_date')->count(),
          ];
        })->all(),
      ];
    }

    // Bawaan semester asal: semester persis sebelum semester aktif
    // (secara kronologis) yang punya data; bila tidak ada, kandidat
    // paling baru sebelum semester aktif yang punya data.
    $defaultAsalId = null;
    if ($posisiSekarang !== null && $posisiSekarang > 0) {
      $idSebelumnya = (int) $semuaSemester[$posisiSekarang - 1]->id;
      if (collect($sumber)->contains('id', $idSebelumnya)) {
        $defaultAsalId = $idSebelumnya;
      }
    }
    if (!$defaultAsalId && !empty($sumber)) {
      $defaultAsalId = $sumber[count($sumber) - 1]['id'];
    }

    return view('operator.copy_data_periodik', compact('sumber', 'defaultAsalId', 'statusTarget'));
  }

  /**
   * Eksekusi Salin Data dari Semester Pilihan Pengguna.
   *
   * Item yang disalin dipilih lewat checkbox `salin[]`:
   * detail (sekolah), rombel, dan anggota (siswa aktif di rombel sumber).
   * Item yang sudah ada di semester tujuan dilewati, bukan ditimpa.
   */
  public function copyData(Request $request)
  {
    $request->validate([
      'semester_asal' => 'required|exists:tabel_semester,id',
      'salin'         => 'nullable|array',
      'salin.*'       => 'in:rombel,anggota',
    ]);

    $user = Auth::user();

    if (!$user->ptk) {
      return back()->with('error', 'Akun Anda tidak terhubung ke PTK.');
    }

    $sekolahId        = $user->ptk->sekolah_id;
    $semesterSekarang = Semester::sessionSemesterId();
    $semesterLalu     = (int) $request->semester_asal;
    $pilihan          = $request->input('salin', []);

    if ($semesterLalu === $semesterSekarang) {
      return back()->with('error', 'Semester asal tidak boleh sama dengan semester aktif.');
    }

    if (in_array('anggota', $pilihan) && !in_array('rombel', $pilihan)) {
      return back()->with('error', 'Anggota rombel hanya bisa disalin bersama rombelnya. Centang juga pilihan Rombel.');
    }

    $status         = SekolahDetail::status($sekolahId, $semesterSekarang);
    $semesterTarget = Semester::find($semesterSekarang);

    // Penjaga sisi server (dropdown sudah menyaring, tapi request
    // bisa dibuat manual): sumber harus semester sebelum aktif.
    // Menyalin dari semester sesudah aktif akan menutup keanggotaan
    // siswa di semester yang belum berjalan.
    $semesterAsal = Semester::find($semesterLalu);
    if ($semesterAsal && $semesterTarget
      && Semester::kunciKronologis($semesterAsal) >= Semester::kunciKronologis($semesterTarget)) {
      return back()->with('error', 'Semester asal harus sebelum semester aktif. Semester yang sama dengan atau sesudah semester aktif tidak bisa dijadikan sumber salin.');
    }

    // Jendela sumber yang sama seperti di form: maksimal 1 tahun
    // pelajaran di bawah semester aktif
    $selisih = $semesterAsal && $semesterTarget
      ? (int) substr((string) $semesterTarget->tahun_pelajaran, 0, 4)
        - (int) substr((string) $semesterAsal->tahun_pelajaran, 0, 4)
      : 0;
    if ($selisih > 1) {
      return back()->with('error', 'Semester asal terlalu jauh: yang bisa disalin maksimal 1 tahun pelajaran di bawah semester aktif.');
    }

    // Nama kepala sekolah wajib diisi bila detail sekolah akan disalin
    if (!$status['hasDetail']) {
      $adaDetailSumber = SekolahDetail::where('sekolah_id', $sekolahId)
        ->where('semester_id', $semesterLalu)
        ->exists();
      if ($adaDetailSumber) {
        $request->validate([
          'kepala_sekolah' => 'required|string|max:255',
        ], [
          'kepala_sekolah.required' => 'Nama kepala sekolah wajib diisi untuk detail sekolah semester ini.',
        ]);
      }
    }

    DB::beginTransaction();
    try {
      $hasil   = [];
      $catatan = [];

      // ── Detail sekolah: selalu disalin bila tujuan belum punya ────
      if ($status['hasDetail']) {
        $catatan[] = 'detail sekolah sudah ada di semester ini';
      } else {
        $oldDetail = SekolahDetail::where('sekolah_id', $sekolahId)
          ->where('semester_id', $semesterLalu)
          ->first();

        if (!$oldDetail) {
          $catatan[] = 'detail sekolah tidak ditemukan di semester asal';
        } else {
          $data = $oldDetail->toArray();
          unset($data['id'], $data['created_at'], $data['updated_at']);
          $data['sekolah_id']  = $sekolahId;
          $data['semester_id'] = $semesterSekarang;
          // Nama kepala sekolah mengikuti isian form (wajib diisi di form)
          $data['kepala_sekolah'] = $request->input('kepala_sekolah')
            ?: $oldDetail->kepala_sekolah;
          SekolahDetail::create($data);
          $hasil[] = 'detail sekolah';
        }
      }

      // ── Rombel ──────────────────────────────────────────────────────
      $petaRombel  = []; // id rombel lama => id rombel baru
      $rombelLulus = []; // id rombel tingkat akhir yang tidak disalin
      if (in_array('rombel', $pilihan)) {
        if ($status['hasRombel']) {
          $catatan[] = 'rombel sudah ada di semester ini';
        } else {
          $oldRombels = Rombel::where('sekolah_id', $sekolahId)
            ->where('semester_id', $semesterLalu)
            ->get();

          if ($oldRombels->isEmpty()) {
            $catatan[] = 'rombel tidak ditemukan di semester asal';
          } else {
            // Mode naik kelas: tingkat naik sesuai selisih tahun
            // pelajaran sumber ke tujuan (di sini 0 atau 1)
            $naikKelas = $selisih > 0;
            $tingkatAkhirSumber = $naikKelas ? $this->tingkatAkhirSekolah($sekolahId) : null;
            $namaTakBerubah = [];

            foreach ($oldRombels as $rombel) {
              if ($naikKelas && $tingkatAkhirSumber && (int) $rombel->tingkat + $selisih > $tingkatAkhirSumber) {
                // Rombel tingkat akhir tidak disalin: siswanya lulus
                $rombelLulus[] = $rombel->id;
                continue;
              }

              $data = $rombel->toArray();
              unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);
              $data['sekolah_id']  = $sekolahId;
              $data['semester_id'] = $semesterSekarang;

              if ($naikKelas) {
                $data['tingkat']     = (int) $rombel->tingkat + $selisih;
                $data['nama_rombel'] = $this->namaNaikKelas($rombel->nama_rombel, (int) $rombel->tingkat, $selisih);
                if ($data['nama_rombel'] === $rombel->nama_rombel) {
                  $namaTakBerubah[] = $rombel->nama_rombel;
                }
              }

              $baru = Rombel::create($data);
              $petaRombel[$rombel->id] = $baru->id;
            }

            if (!empty($petaRombel)) {
              $hasil[] = count($petaRombel) . ' rombel' . ($naikKelas ? ' (naik ' . $selisih . ' tingkat)' : '');
            }
            if (!empty($rombelLulus)) {
              $catatan[] = count($rombelLulus) . ' rombel tingkat akhir tidak disalin (siswa lulus)';
            }
            if ($naikKelas && !$tingkatAkhirSumber) {
              $catatan[] = 'tingkat akhir jenjang ini tidak dikenal — semua rombel dinaikkan tanpa pengecualian kelas akhir';
            }
            if (!empty($namaTakBerubah)) {
              $catatan[] = 'nama rombel berikut tidak berubah otomatis (sesuaikan manual): ' . implode(', ', $namaTakBerubah);
            }
          }
        }
      }

      // ── Anggota rombel (siswa yang masih aktif di rombel sumber) ────
      if (in_array('anggota', $pilihan)) {
        // Keanggotaan di semester baru berlaku sejak semester itu mulai
        $startDate = $semesterTarget?->tanggal_mulai?->format('Y-m-d')
          ?? now()->toDateString();
        // Keanggotaan lama ditutup di akhir semester sumber
        $endDateSumber = Semester::find($semesterLalu)?->tanggal_selesai?->format('Y-m-d')
          ?? $startDate;

        if (empty($petaRombel)) {
          $catatan[] = 'anggota tidak disalin karena rombel tidak tersalin';
        } else {
          $tersalin = 0;
          $dilewati = 0;

          foreach ($petaRombel as $rombelLamaId => $rombelBaruId) {
            $anggotaAktif = RombelAnggota::where('rombel_id', $rombelLamaId)
              ->whereNull('end_date')
              ->get();

            foreach ($anggotaAktif as $anggota) {
              $sudahAktif = RombelAnggota::where('siswa_id', $anggota->siswa_id)
                ->where('semester_id', $semesterSekarang)
                ->whereNull('end_date')
                ->exists();

              if ($sudahAktif) {
                $dilewati++;
                continue;
              }

              RombelAnggota::create([
                'siswa_id'    => $anggota->siswa_id,
                'rombel_id'   => $rombelBaruId,
                'semester_id' => $semesterSekarang,
                'start_date'  => $startDate,
                'end_date'    => null,
              ]);

              // Tutup keanggotaan di semester sumber supaya riwayat siswa
              // tidak menampilkan dua keanggotaan berstatus Aktif sekaligus
              $anggota->update([
                'end_date' => $endDateSumber < $anggota->start_date
                  ? $anggota->start_date
                  : $endDateSumber,
              ]);
              $tersalin++;
            }
          }

          if ($tersalin) {
            $hasil[] = $tersalin . ' anggota rombel';
          }
          if ($dilewati) {
            $catatan[] = $dilewati . ' anggota dilewati (sudah punya rombel aktif di semester ini)';
          }
        }
      }

      // ── Kelulusan otomatis kelas akhir ────────────────────────────
      // Berjalan bersama penyalinan rombel pada salin lintas tahun
      // pelajaran — tidak tergantung centang anggota rombel. Siswa di
      // rombel sumber yang tingkatnya melewati tingkat akhir setelah
      // kenaikan diluluskan penuh seperti jalur registrasi(): registrasi
      // ditutup sebagai lulus per akhir semester sumber, status siswa
      // disinkronkan, keanggotaan aktif ditutup.
      if ($selisih >= 1 && in_array('rombel', $pilihan)) {
        $tingkatAkhirLulus = $this->tingkatAkhirSekolah($sekolahId);

        if ($tingkatAkhirLulus) {
          $tanggalLulus = $semesterAsal?->tanggal_selesai?->format('Y-m-d')
            ?? now()->toDateString();

          $calonLulus = RombelAnggota::where('semester_id', $semesterLalu)
            ->whereNull('end_date')
            ->whereHas('rombel', fn($q) => $q
              ->where('sekolah_id', $sekolahId)
              ->where('tingkat', '>', $tingkatAkhirLulus - $selisih))
            ->pluck('siswa_id')
            ->unique();

          $jumlahLulus = 0;
          foreach ($calonLulus as $siswaId) {
            $registrasi = SiswaRegistrasi::where('siswa_id', $siswaId)
              ->where('sekolah_id', $sekolahId)
              ->where('status', 'aktif')
              ->latest('id')
              ->first();

            if (!$registrasi) {
              continue;
            }

            $registrasi->update([
              'status'         => 'lulus',
              'tanggal_keluar' => $tanggalLulus,
              'alasan'         => 'Lulus otomatis saat salin data periodik',
            ]);

            Siswa::where('id', $siswaId)->update(['status' => 'lulus']);

            RombelAnggota::where('siswa_id', $siswaId)
              ->whereNull('end_date')
              ->where(fn($q) => $q->whereNull('start_date')->orWhere('start_date', '<=', $tanggalLulus))
              ->update(['end_date' => $tanggalLulus]);

            $jumlahLulus++;
          }

          if ($jumlahLulus) {
            $hasil[] = $jumlahLulus . ' siswa kelas akhir diluluskan otomatis';
          }
        } else {
          $catatan[] = 'kelulusan otomatis dilewati: tingkat akhir jenjang ini tidak dikenal';
        }
      }

      DB::commit();
    } catch (\Exception $e) {
      DB::rollBack();
      return back()->with('error', 'Gagal menyalin data: ' . $e->getMessage());
    }

    // Redirect dilakukan di luar try/catch: kegagalan menyusun redirect
    // tidak boleh dilaporkan seolah-olah penyalinan datanya yang gagal.
    if (empty($hasil)) {
      return back()->with('error', 'Tidak ada data yang disalin. ' . implode('; ', $catatan) . '.');
    }

    $pesan = 'Data berhasil disalin ke semester ini: ' . implode(', ', $hasil) . '.';
    if (!empty($catatan)) {
      $pesan .= ' Catatan: ' . implode('; ', $catatan) . '.';
    }

    return redirect()->route('home')->with('success', $pesan);
  }

  /**
   * Tingkat akhir per jenjang sekolah (peta yang sama seperti yang
   * dipakai modul SKHU): SD 6, SMP 9, SMA/SMK 12. Null bila jenjang
   * tidak dikenal — mode naik kelas lalu menaikkan semua rombel
   * tanpa pengecualian kelas akhir.
   */
  private function tingkatAkhirSekolah(int $sekolahId): ?int
  {
    $peta    = ['SD' => 6, 'SMP' => 9, 'SMA' => 12, 'SMK' => 12];
    $jenjang = strtoupper((string) (Sekolah::find($sekolahId)?->jenjang ?? ''));

    return $peta[$jenjang] ?? null;
  }

  /**
   * Sesuaikan nama rombel saat naik kelas: ganti angka tingkat yang
   * berdiri sebagai token tersendiri, mis. "Kelas 5A" -> "Kelas 6A",
   * "1.A" -> "2.A", "Kelas 10" -> "Kelas 11". Nama tanpa angka
   * tingkat yang jelas dikembalikan apa adanya.
   */
  private function namaNaikKelas(string $nama, int $tingkatLama, int $selisih = 1): string
  {
    $hasil = preg_replace(
      '/(?<!\d)' . $tingkatLama . '(?!\d)/',
      (string) ($tingkatLama + $selisih),
      $nama,
      1
    );

    return $hasil ?? $nama;
  }
}
