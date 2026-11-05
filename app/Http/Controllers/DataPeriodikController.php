<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RombelAnggota;
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
   */
  public function showCopyForm()
  {
    $user = Auth::user();

    if (!$user->ptk) {
      return redirect()->route('dashboard')
        ->with('error', 'Halaman salin data periodik hanya untuk akun yang terhubung ke PTK (operator sekolah).');
    }

    $sekolahId        = $user->ptk->sekolah_id;
    $semesterSekarang = Semester::smtId();

    // Urutan kronologis semester: tahun awal tahun pelajaran,
    // lalu Ganjil sebelum Genap dalam tahun yang sama.
    $kunciUrut = fn(Semester $s) => [
      (int) substr((string) $s->tahun_pelajaran, 0, 4),
      strtolower((string) $s->semester) === 'ganjil' ? 1 : 2,
    ];
    $semuaSemester = Semester::all()->sortBy($kunciUrut)->values();

    $statusTarget = $semesterSekarang
      ? SekolahDetail::status($sekolahId, (int) $semesterSekarang)
      : ['hasDetail' => false, 'hasRombel' => false];

    // Kandidat semester sumber: semester selain yang aktif yang
    // punya detail sekolah atau rombel untuk sekolah ini.
    $sumber         = [];
    $posisiSekarang = null;

    foreach ($semuaSemester as $i => $smt) {
      if ((int) $smt->id === (int) $semesterSekarang) {
        $posisiSekarang = $i;
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

      $sumber[] = [
        'id'     => $smt->id,
        'label'  => $smt->tahun_pelajaran . ' — Semester ' . $smt->semester,
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
        'rombel' => $rombels->map(fn($r) => [
          'nama_rombel'    => $r->nama_rombel,
          'tingkat'        => $r->tingkat,
          'jumlah_anggota' => $r->rombelAnggota()->whereNull('end_date')->count(),
        ])->all(),
      ];
    }

    // Bawaan semester asal: semester persis sebelum semester aktif
    // (secara kronologis) yang punya data; bila tidak ada, kandidat
    // paling baru yang punya data.
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
      'salin'         => 'required|array|min:1',
      'salin.*'       => 'in:detail,rombel,anggota',
    ], [
      'salin.required' => 'Pilih minimal satu data yang akan disalin.',
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

    DB::beginTransaction();
    try {
      $hasil   = [];
      $catatan = [];

      // ── Detail sekolah ──────────────────────────────────────────────
      if (in_array('detail', $pilihan)) {
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
            SekolahDetail::create($data);
            $hasil[] = 'detail sekolah';
          }
        }
      }

      // ── Rombel ──────────────────────────────────────────────────────
      $petaRombel = []; // id rombel lama => id rombel baru
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
            foreach ($oldRombels as $rombel) {
              $data = $rombel->toArray();
              unset($data['id'], $data['created_at'], $data['updated_at'], $data['deleted_at']);
              $data['sekolah_id']  = $sekolahId;
              $data['semester_id'] = $semesterSekarang;
              $baru = Rombel::create($data);
              $petaRombel[$rombel->id] = $baru->id;
            }
            $hasil[] = count($petaRombel) . ' rombel';
          }
        }
      }

      // ── Anggota rombel (siswa yang masih aktif di rombel sumber) ────
      if (in_array('anggota', $pilihan)) {
        if (empty($petaRombel)) {
          $catatan[] = 'anggota tidak disalin karena rombel tidak tersalin';
        } else {
          // Keanggotaan di semester baru berlaku sejak semester itu mulai
          $startDate = $semesterTarget?->tanggal_mulai?->format('Y-m-d')
            ?? now()->toDateString();
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

      DB::commit();

      if (empty($hasil)) {
        return back()->with('error', 'Tidak ada data yang disalin. ' . implode('; ', $catatan) . '.');
      }

      $pesan = 'Data berhasil disalin ke semester ini: ' . implode(', ', $hasil) . '.';
      if (!empty($catatan)) {
        $pesan .= ' Catatan: ' . implode('; ', $catatan) . '.';
      }

      return redirect()->route('dashboard')->with('success', $pesan);
    } catch (\Exception $e) {
      DB::rollBack();
      return back()->with('error', 'Gagal menyalin data: ' . $e->getMessage());
    }
  }
}
