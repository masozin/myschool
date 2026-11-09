<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\PresensiPengaturan;
use App\Models\Rombel;
use App\Models\RombelAnggota;
use App\Models\Semester;
use App\Models\SiswaRegistrasi;
use App\Models\KalenderPendidikan;
use App\Support\PresensiLayanan;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PresensiController extends Controller
{
  /**
   * Halaman utama presensi (blade biasa).
   * Hanya menyiapkan dropdown rombel, grid siswa di-load via AJAX.
   */
  public function index()
  {
    $user = Auth::user();

    // Hanya rombel semester sesi: tanggal presensi divalidasi
    // terhadap semester sesi, jadi rombel semester lain tidak
    // boleh bisa dipilih (snapshot rombel/semester akan bertentangan).
    // Daftar rombel mengikuti peran: Guru hanya rombel wali
    // kelasnya, Operator & Kepala Sekolah rombel sekolahnya,
    // Admin semua rombel semester sesi (memantau lintas sekolah)
    $rombelList = Rombel::with('sekolah.detailTerbaru')
      ->where('semester_id', session('semester_id'))
      ->when(
        $user->hasRole('Guru') && !$user->hasAnyRole(['Operator Sekolah', 'Admin']),
        fn($q) => $q->where('ptk_id', $user->ptk?->id)
      )
      ->when(
        $user->hasAnyRole(['Operator Sekolah', 'Kepala Sekolah']),
        fn($q) => $q->where('sekolah_id', $user->ptk?->sekolah_id)
      )
      ->orderBy('tingkat')
      ->orderBy('nama_rombel')
      ->get();
    $semester     = Semester::find(session('semester_id'));
    $batasKoreksi = PresensiPengaturan::batasUntuk($user->ptk?->sekolah_id);

    // Data kalender sebulan pada halaman presensi: seluruh
    // kegiatan Kalender Pendidikan yang bersinggungan dengan
    // rentang semester sesi — untuk menandai hari libur pada
    // kalender dan daftar kegiatan per bulan
    $kegiatanSemester = $semester
      ? KalenderPendidikan::whereDate('tanggal_mulai', '<=', $semester->tanggal_selesai)
        ->whereDate('tanggal_selesai', '>=', $semester->tanggal_mulai)
        ->orderBy('tanggal_mulai')
        ->get()
      : collect();

    // Libur mingguan per rombel, dari detail sekolah pemilik
    // rombel, sebagai nomor hari (0=Minggu s/d 6=Sabtu).
    // Bawaan Minggu bila sekolah belum mengaturnya.
    $petaNamaHari = [
      'Minggu' => 0, 'Senin' => 1, 'Selasa' => 2, 'Rabu' => 3,
      'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6,
    ];
    $liburMingguanPerRombel = $rombelList->mapWithKeys(function ($r) use ($petaNamaHari) {
      $namaHari = $r->sekolah?->detailTerbaru?->libur_mingguan;
      $dow = collect(is_array($namaHari) && $namaHari ? $namaHari : ['Minggu'])
        ->map(fn($n) => $petaNamaHari[$n] ?? null)
        ->filter(fn($d) => $d !== null)
        ->values()
        ->all();

      return [$r->id => $dow];
    });

    return view('content.presensi.index', compact(
      'rombelList',
      'semester',
      'batasKoreksi',
      'kegiatanSemester',
      'liburMingguanPerRombel'
    ));
  }

  /**
   * Tanggal yang tidak boleh dipresensi selain hari libur
   * Kalender Pendidikan: tanggal yang belum berjalan (masa
   * depan) dan hari libur mingguan sekolah pemilik rombel.
   * Mengembalikan pesan penolakan, atau null bila tanggal
   * boleh dipakai.
   */
  private function penolakanTanggal(Rombel $rombel, Carbon $tanggal): ?string
  {
    return PresensiLayanan::penolakanTanggal($rombel, $tanggal);
  }

  /**
   * Hak melihat presensi satu rombel: Admin semua rombel;
   * Operator & Kepala Sekolah rombel sekolahnya; Guru hanya
   * rombel wali kelasnya. Peran lain tidak berhak.
   */
  private function bolehLihat(Rombel $rombel): bool
  {
    return PresensiLayanan::bolehLihat(Auth::user(), $rombel);
  }

  /**
   * Hak mengisi/mengubah presensi satu rombel: hanya wali kelas
   * rombel itu (Guru) dan Operator Sekolah sekolah itu. Admin
   * dan Kepala Sekolah hanya memantau — tidak pernah mengisi.
   */
  private function bolehIsi(Rombel $rombel): bool
  {
    return PresensiLayanan::bolehIsi(Auth::user(), $rombel);
  }

  /**
   * Jendela koreksi untuk Guru: tanggal lampau hanya boleh
   * diisi/diubah dalam batas hari yang diatur Operator sekolah
   * (bawaan 7 hari). Operator sendiri tidak dibatasi jendela.
   */
  private function dalamJendelaKoreksi(Rombel $rombel, Carbon $tanggal): bool
  {
    return PresensiLayanan::dalamJendelaKoreksi($rombel, $tanggal);
  }

  /**
   * AJAX: ambil daftar siswa + status presensi (jika ada) untuk rombel & tanggal tertentu.
   * Dipanggil dari JS saat user klik "Tampilkan".
   */
  public function ajaxSiswa(Request $request)
  {
    $request->validate([
      'rombel_id' => 'required|exists:tabel_rombel,id',
      'tanggal'   => 'required|date',
    ]);

    $rombelId = $request->rombel_id;
    $tanggal  = Carbon::parse($request->tanggal);

    // Ambil semester dari session (di-set saat login / SemesterSessionMiddleware)
    $semester = Semester::find(session('semester_id'));

    if (!$semester) {
      return response()->json([
        'success' => false,
        'message' => 'Semester aktif tidak ditemukan di sesi. Silakan login ulang.',
      ], 422);
    }

    // Tanggal tidak boleh di luar rentang semester yang sedang dipilih
    if ($tanggal->lt($semester->tanggal_mulai) || $tanggal->gt($semester->tanggal_selesai)) {
      return response()->json([
        'success' => false,
        'message' => 'Tanggal di luar rentang semester ' . $semester->tahun_pelajaran . ' - ' . $semester->semester
          . ' (' . $semester->tanggal_mulai->format('d-m-Y') . ' s/d ' . $semester->tanggal_selesai->format('d-m-Y') . ').',
      ], 422);
    }

    // Cek hari libur
    $libur = KalenderPendidikan::where('is_libur', true)
      ->whereDate('tanggal_mulai', '<=', $tanggal)
      ->whereDate('tanggal_selesai', '>=', $tanggal)
      ->first();

    if ($libur) {
      return response()->json([
        'success' => false,
        'message' => 'Tanggal ini hari libur (' . $libur->kegiatan . '). Presensi tidak dapat diinput.',
      ], 422);
    }

    $rombel = Rombel::findOrFail($rombelId);

    if ((int) $rombel->semester_id !== (int) $semester->id) {
      return response()->json([
        'success' => false,
        'message' => 'Rombel ini bukan rombel semester ' . $semester->tahun_pelajaran . ' - ' . $semester->semester . '.',
      ], 422);
    }

    // Tanggal masa depan dan libur mingguan sekolah tidak dapat
    // dibuka untuk presensi (sama seperti libur kalender di atas)
    if ($pesan = $this->penolakanTanggal($rombel, $tanggal)) {
      return response()->json([
        'success' => false,
        'message' => $pesan,
      ], 422);
    }

    if (!$this->bolehLihat($rombel)) {
      return response()->json([
        'success' => false,
        'message' => 'Anda tidak berhak melihat presensi rombel ini.',
      ], 403);
    }

    // Roster = anggota rombel itu pada semesternya, dibatasi hari
    // efektif siswa dari tanggal registrasinya di sekolah rombel
    // ini: belum bisa dipresensi sebelum tanggal_diterima dan tidak
    // lagi sesudah tanggal_keluar. Keanggotaan rombel sendiri tidak
    // bertanggal — ia berlaku untuk semester rombel itu, jadi guru
    // rombel yang terdaftar pada semester tanggal inilah yang
    // mengelola presensinya.
    $anggota = $this->roster($rombel, $tanggal);

    // Presensi melekat ke siswa + tanggal, bukan ke rombel: status
    // tersimpan dicari tanpa memandang rombel asalnya, supaya
    // membuka tanggal yang sama dari rombel lain (mis. setelah
    // siswa pindah rombel) tetap menampilkan status yang tersimpan
    $presensiExisting = Presensi::with('inputOleh')
      ->whereIn('siswa_id', $anggota->pluck('siswa_id'))
      ->whereDate('tanggal', $tanggal)
      ->get()
      ->keyBy('siswa_id');

    $data = $anggota->map(function ($a) use ($presensiExisting) {
      $existing = $presensiExisting->get($a->siswa_id);

      return [
        'siswa_id'      => $a->siswa->id,
        'nama_siswa'    => $a->siswa->nama_siswa,
        'nisn'          => $a->siswa->nisn,
        'photo'         => $a->siswa->photo,
        'jenis_kelamin' => $a->siswa->jenis_kelamin,
        // Belum tercatat = null (bukan bawaan hadir): kartu tampil
        // tanpa status terpilih sampai guru memilihnya sendiri
        // atau menekan "Semua Hadir"
        'status'        => $existing->status ?? null,
        'keterangan' => $existing->keterangan ?? null,
        // Jejak pengubah: input_by selama ini sudah mencatat
        // pengisi/pengubah terakhir, kini ikut ditampilkan
        'terisi'      => $existing !== null,
        'diisi_oleh'  => $existing?->inputOleh?->name,
        'diubah_pada' => $existing?->updated_at?->format('d M Y H:i'),
      ];
    });

    // Apakah pengguna ini boleh menyimpan untuk rombel + tanggal
    // ini (peran pengisi dan, untuk Guru, jendela koreksi)
    $user = Auth::user();
    $bolehIsi = $this->bolehIsi($rombel)
      && (!$user->hasRole('Guru') || $user->hasRole('Operator Sekolah') || $this->dalamJendelaKoreksi($rombel, $tanggal));

    return response()->json([
      'success'   => true,
      'rombel'    => [
        'id'          => $rombel->id,
        'nama_rombel' => $rombel->nama_rombel,
      ],
      'tanggal'   => $tanggal->format('Y-m-d'),
      'boleh_isi' => $bolehIsi,
      'siswa'     => $data,
    ]);
  }

  /**
   * Roster presensi: anggota rombel yang hari efektifnya — dari
   * tanggal registrasi di sekolah rombel itu — mencakup $tanggal
   * (belum bisa dipresensi sebelum tanggal_diterima, tidak lagi
   * sesudah tanggal_keluar). Dipakai bersama oleh ajaxSiswa dan
   * store() supaya aturan yang ditampilkan sama dengan yang
   * ditegakkan saat menyimpan.
   */
  private function roster(Rombel $rombel, Carbon $tanggal)
  {
    return PresensiLayanan::roster($rombel, $tanggal);
  }

  /**
   * AJAX: simpan/update presensi sekaligus untuk satu rombel + tanggal.
   * Boleh dipanggil berulang (edit kapan saja) -> upsert berdasarkan (siswa_id, tanggal).
   */
  public function store(Request $request)
  {
    $request->validate([
      'rombel_id' => 'required|exists:tabel_rombel,id',
      'tanggal'   => 'required|date',
      'presensi'  => 'required|array|min:1',
      'presensi.*.siswa_id'   => 'required|exists:tabel_siswa,id',
      'presensi.*.status'     => 'required|in:hadir,izin,sakit,alpa',
      'presensi.*.keterangan' => 'nullable|string',
    ]);

    $tanggal = Carbon::parse($request->tanggal);

    $semester = Semester::find(session('semester_id'));

    if (!$semester) {
      return response()->json([
        'success' => false,
        'message' => 'Semester aktif tidak ditemukan di sesi. Silakan login ulang.',
      ], 422);
    }

    // Kunci semester selaras aturan global: semester sesi harus
    // aktif atau dibuka Admin (kunci_dibuka). Pengecualian lokal
    // lama untuk Admin dihapus — Admin kini hanya memantau dan
    // tidak pernah menyimpan presensi (lihat bolehIsi di bawah).
    if (!$semester->is_active && !$semester->kunci_dibuka) {
      return response()->json([
        'success' => false,
        'message' => 'Semester ' . $semester->tahun_pelajaran . ' - ' . $semester->semester
          . ' sudah tidak aktif. Presensi tidak dapat diubah.',
      ], 403);
    }

    if ($tanggal->lt($semester->tanggal_mulai) || $tanggal->gt($semester->tanggal_selesai)) {
      return response()->json([
        'success' => false,
        'message' => 'Tanggal di luar rentang semester ' . $semester->tahun_pelajaran . ' - ' . $semester->semester
          . ' (' . $semester->tanggal_mulai->format('d-m-Y') . ' s/d ' . $semester->tanggal_selesai->format('d-m-Y') . ').',
      ], 422);
    }

    $libur = KalenderPendidikan::where('is_libur', true)
      ->whereDate('tanggal_mulai', '<=', $tanggal)
      ->whereDate('tanggal_selesai', '>=', $tanggal)
      ->exists();

    if ($libur) {
      return response()->json([
        'success' => false,
        'message' => 'Tanggal ini hari libur, presensi tidak dapat disimpan.',
      ], 422);
    }

    $rombel = Rombel::findOrFail($request->rombel_id);

    if ((int) $rombel->semester_id !== (int) $semester->id) {
      return response()->json([
        'success' => false,
        'message' => 'Rombel ini bukan rombel semester ' . $semester->tahun_pelajaran . ' - ' . $semester->semester . '.',
      ], 422);
    }

    // Tanggal masa depan dan libur mingguan sekolah ditolak di
    // server juga, bukan hanya disembunyikan di tampilan
    if ($pesan = $this->penolakanTanggal($rombel, $tanggal)) {
      return response()->json([
        'success' => false,
        'message' => $pesan,
      ], 422);
    }

    // Hak mengisi: hanya wali kelas rombel ini dan Operator
    // Sekolah sekolah ini. Kepala Sekolah & Admin hanya memantau.
    if (!$this->bolehIsi($rombel)) {
      return response()->json([
        'success' => false,
        'message' => 'Anda tidak berhak mengisi presensi rombel ini. Hanya wali kelas rombel ini dan Operator Sekolah yang dapat mengisi.',
      ], 403);
    }

    // Jendela koreksi khusus Guru (Operator tidak dibatasi):
    // tanggal lampau di luar batas hari yang diatur sekolah
    // tidak dapat lagi diisi/diubah oleh wali kelas
    if (
      Auth::user()->hasRole('Guru')
      && !Auth::user()->hasRole('Operator Sekolah')
      && !$this->dalamJendelaKoreksi($rombel, $tanggal)
    ) {
      $batas = PresensiPengaturan::batasUntuk((int) $rombel->sekolah_id);

      return response()->json([
        'success' => false,
        'message' => 'Tanggal ini sudah melewati batas koreksi presensi untuk Guru (' . $batas . ' hari). Silakan hubungi Operator Sekolah bila perlu perubahan.',
      ], 403);
    }

    // Tegakkan aturan roster yang sama dengan yang ditampilkan
    // ajaxSiswa: setiap siswa yang dikirim harus anggota rombel ini
    // dan hari efektif registrasinya mencakup tanggal presensi.
    // Tanpa ini, POST langsung bisa menyimpan presensi untuk
    // non-anggota, sebelum tanggal_diterima, atau sesudah
    // tanggal_keluar.
    $rosterIds = $this->roster($rombel, $tanggal)->pluck('siswa_id');
    $diLuarRoster = collect($request->presensi)
      ->pluck('siswa_id')
      ->map(fn($id) => (int) $id)
      ->diff($rosterIds->map(fn($id) => (int) $id));

    if ($diLuarRoster->isNotEmpty()) {
      return response()->json([
        'success' => false,
        'message' => 'Ada ' . $diLuarRoster->count() . ' siswa di luar daftar presensi rombel ini untuk tanggal tersebut — presensi ditolak.',
      ], 422);
    }

    DB::transaction(function () use ($request, $tanggal, $semester) {
      $rows = [];

      foreach ($request->presensi as $item) {
        $rows[] = [
          'siswa_id'    => $item['siswa_id'],
          'rombel_id'   => $request->rombel_id,
          'semester_id' => $semester->id,
          'tanggal'     => $tanggal->format('Y-m-d'),
          'status'      => $item['status'],
          'keterangan'  => $item['keterangan'] ?? null,
          'input_by'    => Auth::id(),
          'created_at'  => now(),
          'updated_at'  => now(),
        ];
      }

      Presensi::upsert(
        $rows,
        ['siswa_id', 'tanggal'],
        ['rombel_id', 'semester_id', 'status', 'keterangan', 'input_by', 'updated_at']
      );
    });

    return response()->json([
      'success' => true,
      'message' => 'Presensi berhasil disimpan.',
    ]);
  }

  // =========================================================================
  // REKAP PRESENSI
  // =========================================================================

  /**
   * Kumpulkan seluruh data rekap untuk filter saat ini — dipakai
   * bersama halaman rekap, ekspor Excel, dan ekspor PDF.
   *
   * Definisi "belum absen" (Mark): hari efektif adalah hari
   * dalam rentang semester periode sampai hari berjalan yang
   * BUKAN libur Kalender Pendidikan dan BUKAN hari libur
   * mingguan sekolah (detail sekolah), dibatasi masa registrasi
   * aktif siswa di sekolahnya. "Belum absen" = hari efektif
   * siswa itu dikurangi hari yang sudah tercatat presensinya.
   */
  private function kumpulkanRekap(Request $request): array
  {
    $user = Auth::user();

    // ── Periode: per semester atau per tahun pelajaran ──────────
    $mode = $request->get('mode') === 'tahun' ? 'tahun' : 'semester';

    $daftarSemester = Semester::orderByDesc('kode_semester')->get();
    $daftarTahun    = $daftarSemester->pluck('tahun_pelajaran')->unique()->values();

    if ($mode === 'tahun') {
      $sesi            = Semester::find(session('semester_id'));
      $tpDipilih       = (string) $request->get('tahun', $sesi?->tahun_pelajaran ?? $daftarTahun->first());
      $semesters       = $daftarSemester->where('tahun_pelajaran', $tpDipilih)->values();
      $semesterDipilih = null;
      $labelPeriode    = 'Tahun Pelajaran ' . $tpDipilih;
    } else {
      $semesterDipilih = $daftarSemester->firstWhere('id', (int) $request->get('semester_id', session('semester_id')))
        ?? $daftarSemester->first();
      $semesters    = collect([$semesterDipilih]);
      $tpDipilih    = $semesterDipilih?->tahun_pelajaran;
      $labelPeriode = $semesterDipilih
        ? $semesterDipilih->tahun_pelajaran . ' — Semester ' . $semesterDipilih->semester
        : '-';
    }

    // ── Cakupan rombel mengikuti peran (sama seperti halaman isi) ─
    $rombelCakupan = Rombel::with(['sekolah.detailTerbaru', 'semester'])
      ->whereIn('semester_id', $semesters->pluck('id'))
      ->when(
        $user->hasAnyRole(['Operator Sekolah', 'Kepala Sekolah']),
        fn($q) => $q->where('sekolah_id', $user->ptk?->sekolah_id)
      )
      ->when(
        $user->hasRole('Guru') && !$user->hasAnyRole(['Operator Sekolah', 'Admin']),
        fn($q) => $q->where('ptk_id', $user->ptk?->id)
      )
      ->orderBy('nama_rombel')
      ->get();

    $rombelDipilih = (int) $request->get('rombel_id') ?: null;
    if ($rombelDipilih && !$rombelCakupan->contains('id', $rombelDipilih)) {
      $rombelDipilih = null;
    }
    $rombelAktif = $rombelDipilih
      ? $rombelCakupan->where('id', $rombelDipilih)->values()
      : $rombelCakupan;

    // ── Baris presensi pada cakupan peran ──────────────────────────
    $barisSemua = Presensi::whereIn('semester_id', $semesters->pluck('id'))
      ->whereIn('rombel_id', $rombelCakupan->pluck('id'))
      ->get();

    $barisAktif = $barisSemua->whereIn('rombel_id', $rombelAktif->pluck('id'));

    // ── Hari efektif (definisi Mark): tanggal dalam rentang
    // semester periode, hanya sampai hari berjalan, BUKAN libur
    // Kalender Pendidikan, dan BUKAN hari libur mingguan sekolah
    // (libur_mingguan pada detail sekolah; bawaan Minggu bila
    // belum diatur). Dihitung per sekolah karena libur mingguan
    // dapat berbeda antar sekolah ────────────────────────────────
    $hariIni = now()->startOfDay();

    $daftarTanggal = collect();
    foreach ($semesters as $sem) {
      if (!$sem) {
        continue;
      }
      $awal  = Carbon::parse($sem->tanggal_mulai)->startOfDay();
      $akhir = Carbon::parse($sem->tanggal_selesai)->startOfDay();
      if ($akhir->gt($hariIni)) {
        $akhir = $hariIni->copy();
      }
      for ($t = $awal->copy(); $t->lte($akhir); $t->addDay()) {
        $daftarTanggal->push($t->format('Y-m-d'));
      }
    }
    $daftarTanggal = $daftarTanggal->unique()->values();

    $rentangLibur = KalenderPendidikan::where('is_libur', true)
      ->whereDate('tanggal_mulai', '<=', $hariIni)
      ->get()
      ->map(fn($k) => [
        Carbon::parse($k->tanggal_mulai)->format('Y-m-d'),
        Carbon::parse($k->tanggal_selesai)->format('Y-m-d'),
      ]);
    $tanggalLibur = array_flip(
      $daftarTanggal
        ->filter(fn($tgl) => $rentangLibur->contains(fn($r) => $tgl >= $r[0] && $tgl <= $r[1]))
        ->all()
    );

    $petaHari = [
      'Minggu' => 0, 'Senin' => 1, 'Selasa' => 2, 'Rabu' => 3,
      'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6,
    ];

    $efektifPerSekolah = [];
    foreach ($rombelCakupan->pluck('sekolah')->filter()->unique('id') as $sekolah) {
      $liburNama = $sekolah->detailTerbaru?->libur_mingguan;
      $liburDow  = collect(is_array($liburNama) && $liburNama ? $liburNama : ['Minggu'])
        ->map(fn($nama) => $petaHari[$nama] ?? null)
        ->filter(fn($d) => $d !== null)
        ->all();

      $efektifPerSekolah[$sekolah->id] = $daftarTanggal
        ->filter(fn($tgl) => !isset($tanggalLibur[$tgl])
          && !in_array(Carbon::parse($tgl)->dayOfWeek, $liburDow, true))
        ->values();
    }

    // Angka hari efektif untuk tampilan: gabungan tanggal efektif
    // sekolah-sekolah pada rombel aktif
    $hariEfektif = collect($efektifPerSekolah)
      ->only($rombelAktif->pluck('sekolah_id')->unique()->all())
      ->flatten()
      ->unique()
      ->count();

    // Hitungan status per (siswa, rombel)
    $hitungan = [];
    foreach ($barisAktif as $p) {
      $kunci = $p->siswa_id . ':' . $p->rombel_id;
      $hitungan[$kunci][$p->status] = ($hitungan[$kunci][$p->status] ?? 0) + 1;
    }

    // Keanggotaan pada rombel aktif + interval registrasi siswa
    $anggotaList = RombelAnggota::with('siswa')
      ->whereIn('rombel_id', $rombelAktif->pluck('id'))
      ->get();
    $registrasi = SiswaRegistrasi::whereIn('siswa_id', $anggotaList->pluck('siswa_id')->unique()->all())
      ->get()
      ->groupBy('siswa_id');

    $perSiswa = [];
    foreach ($anggotaList as $anggota) {
      $rombel = $rombelAktif->firstWhere('id', $anggota->rombel_id);
      $siswa  = $anggota->siswa;
      if (!$rombel || !$siswa) {
        continue;
      }

      $mulai   = Carbon::parse($rombel->semester->tanggal_mulai)->format('Y-m-d');
      $selesai = Carbon::parse($rombel->semester->tanggal_selesai)->format('Y-m-d');
      $interval = ($registrasi->get($siswa->id) ?? collect())
        ->filter(fn($r) => (int) $r->sekolah_id === (int) $rombel->sekolah_id);

      $tanggalEfektif = $efektifPerSekolah[$rombel->sekolah_id] ?? collect();
      $berhak = $tanggalEfektif->filter(function ($tgl) use ($mulai, $selesai, $interval) {
        if ($tgl < $mulai || $tgl > $selesai) {
          return false;
        }

        return $interval->contains(fn($r) =>
          (!$r->tanggal_diterima || Carbon::parse($r->tanggal_diterima)->format('Y-m-d') <= $tgl)
          && (!$r->tanggal_keluar || Carbon::parse($r->tanggal_keluar)->format('Y-m-d') >= $tgl));
      })->count();

      $c = $hitungan[$siswa->id . ':' . $rombel->id] ?? [];
      $h = $c['hadir'] ?? 0;
      $s = $c['sakit'] ?? 0;
      $i = $c['izin'] ?? 0;
      $a = $c['alpa'] ?? 0;
      $belum = max(0, $berhak - ($h + $s + $i + $a));

      $perSiswa[] = [
        'siswa'  => $siswa,
        'rombel' => $rombel,
        'hadir'  => $h,
        'sakit'  => $s,
        'izin'   => $i,
        'alpa'   => $a,
        'belum'  => $belum,
        'berhak' => $berhak,
        'persen' => $berhak > 0 ? min(100, round($h / $berhak * 100, 1)) : 0,
      ];
    }

    $perSiswa = collect($perSiswa)
      ->sort(function ($x, $y) {
        $perRombel = strcmp($x['rombel']->nama_rombel, $y['rombel']->nama_rombel);

        return $perRombel !== 0
          ? $perRombel
          : strcmp($x['siswa']->nama_siswa, $y['siswa']->nama_siswa);
      })
      ->values();

    // ── Agregat per kelas ──────────────────────────────────────────
    $agregat = function ($kumpulan) {
      $h = $kumpulan->sum('hadir');
      $berhak = $kumpulan->sum('berhak');

      return [
        'hadir'  => $h,
        'sakit'  => $kumpulan->sum('sakit'),
        'izin'   => $kumpulan->sum('izin'),
        'alpa'   => $kumpulan->sum('alpa'),
        'belum'  => $kumpulan->sum('belum'),
        'persen' => $berhak > 0 ? min(100, round($h / $berhak * 100, 1)) : 0,
      ];
    };

    $perKelas = $rombelAktif->map(function ($rombel) use ($perSiswa, $agregat) {
      $baris = $perSiswa->filter(fn($r) => $r['rombel']->id === $rombel->id)->values();

      return array_merge([
        'rombel'       => $rombel,
        'jumlah_siswa' => $baris->count(),
      ], $agregat($baris));
    })->values();

    $total = array_merge([
      'jumlah_siswa' => $perSiswa->pluck('siswa.id')->unique()->count(),
    ], $agregat($perSiswa));

    return [
      'mode'            => $mode,
      'labelPeriode'    => $labelPeriode,
      'daftarSemester'  => $daftarSemester,
      'daftarTahun'     => $daftarTahun,
      'semesterDipilih' => $semesterDipilih,
      'tpDipilih'       => $tpDipilih,
      'rombelCakupan'   => $rombelCakupan,
      'rombelDipilih'   => $rombelDipilih,
      'hariEfektif'     => $hariEfektif,
      'perSiswa'        => $perSiswa,
      'perKelas'        => $perKelas,
      'total'           => $total,
    ];
  }

  /** Halaman rekap presensi (per siswa, per kelas, seluruh cakupan). */
  public function rekap(Request $request)
  {
    return view('content.presensi.rekap', $this->kumpulkanRekap($request));
  }

  /** Ekspor rekap ke Excel: lembar Per Kelas + Per Siswa. */
  public function rekapExcel(Request $request)
  {
    $data = $this->kumpulkanRekap($request);

    $spreadsheet = new Spreadsheet();

    // ── Lembar Per Kelas ─────────────────────────────────────────
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Per Kelas');
    $sheet->fromArray([
      ['Rekap Presensi — ' . $data['labelPeriode']],
      ['Hari efektif: ' . $data['hariEfektif'] . ' hari'],
      [],
      ['Rombel', 'Sekolah', 'Siswa', 'Hadir', 'Sakit', 'Izin', 'Alpa', 'Belum Absen', '% Hadir'],
    ], null, 'A1');

    $baris = 5;
    foreach ($data['perKelas'] as $k) {
      $sheet->fromArray([[
        $k['rombel']->nama_rombel,
        $k['rombel']->sekolah?->detailTerbaru?->nama_sekolah ?? '-',
        $k['jumlah_siswa'],
        $k['hadir'],
        $k['sakit'],
        $k['izin'],
        $k['alpa'],
        $k['belum'],
        $k['persen'],
      ]], null, 'A' . $baris);
      $baris++;
    }
    $sheet->fromArray([[
      'TOTAL', '', $data['total']['jumlah_siswa'],
      $data['total']['hadir'], $data['total']['sakit'], $data['total']['izin'],
      $data['total']['alpa'], $data['total']['belum'], $data['total']['persen'],
    ]], null, 'A' . $baris);
    $sheet->getStyle('A4:I4')->getFont()->setBold(true);
    $sheet->getStyle('A' . $baris . ':I' . $baris)->getFont()->setBold(true);
    foreach (range('A', 'I') as $kolom) {
      $sheet->getColumnDimension($kolom)->setAutoSize(true);
    }

    // ── Lembar Per Siswa ─────────────────────────────────────────
    $sheet2 = $spreadsheet->createSheet();
    $sheet2->setTitle('Per Siswa');
    $sheet2->fromArray([
      ['Rekap Presensi — ' . $data['labelPeriode']],
      ['Hari efektif: ' . $data['hariEfektif'] . ' hari'],
      [],
      ['Nama Siswa', 'NISN', 'Kelas', 'Hadir', 'Sakit', 'Izin', 'Alpa', 'Belum Absen', '% Hadir'],
    ], null, 'A1');

    $baris = 5;
    foreach ($data['perSiswa'] as $r) {
      $sheet2->fromArray([[
        $r['siswa']->nama_siswa,
        $r['siswa']->nisn,
        $r['rombel']->nama_rombel,
        $r['hadir'],
        $r['sakit'],
        $r['izin'],
        $r['alpa'],
        $r['belum'],
        $r['persen'],
      ]], null, 'A' . $baris);
      $baris++;
    }
    $sheet2->getStyle('A4:I4')->getFont()->setBold(true);
    foreach (range('A', 'I') as $kolom) {
      $sheet2->getColumnDimension($kolom)->setAutoSize(true);
    }

    $namaFile = 'rekap-presensi-' . \Illuminate\Support\Str::slug($data['labelPeriode']) . '.xlsx';
    $writer   = new XlsxWriter($spreadsheet);

    return response()->streamDownload(function () use ($writer) {
      if (ob_get_contents()) {
        ob_end_clean();
      }
      $writer->save('php://output');
    }, $namaFile, [
      'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'Cache-Control' => 'max-age=0',
    ]);
  }

  /** Ekspor rekap ke PDF (tampilan cetak ringkas). */
  public function rekapPdf(Request $request)
  {
    $data = $this->kumpulkanRekap($request);
    $html = view('content.presensi.rekap-pdf', $data)->render();

    return Pdf::loadHTML($html)
      ->setPaper('a4', 'landscape')
      ->download('rekap-presensi-' . \Illuminate\Support\Str::slug($data['labelPeriode']) . '.pdf');
  }

  /**
   * Simpan pengaturan presensi sekolah (khusus Operator Sekolah):
   * batas koreksi presensi (hari) untuk Guru wali kelas.
   */
  public function simpanPengaturan(Request $request)
  {
    $request->validate([
      'batas_koreksi_hari' => 'required|integer|min:0|max:365',
    ]);

    $sekolahId = Auth::user()->ptk?->sekolah_id;

    if (!$sekolahId) {
      return response()->json([
        'success' => false,
        'message' => 'Akun Anda tidak terhubung ke sekolah mana pun.',
      ], 422);
    }

    PresensiPengaturan::updateOrCreate(
      ['sekolah_id' => $sekolahId],
      ['batas_koreksi_hari' => $request->integer('batas_koreksi_hari')]
    );

    return response()->json([
      'success' => true,
      'message' => 'Pengaturan presensi berhasil disimpan.',
    ]);
  }
}
