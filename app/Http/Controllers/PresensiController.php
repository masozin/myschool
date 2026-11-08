<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\PresensiPengaturan;
use App\Models\Rombel;
use App\Models\RombelAnggota;
use App\Models\Semester;
use App\Models\KalenderPendidikan;
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
    $rombelList = Rombel::where('semester_id', session('semester_id'))
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

    return view('content.presensi.index', compact('rombelList', 'semester', 'batasKoreksi'));
  }

  /**
   * Hak melihat presensi satu rombel: Admin semua rombel;
   * Operator & Kepala Sekolah rombel sekolahnya; Guru hanya
   * rombel wali kelasnya. Peran lain tidak berhak.
   */
  private function bolehLihat(Rombel $rombel): bool
  {
    $user = Auth::user();

    if ($user->hasRole('Admin')) {
      return true;
    }

    if ($user->hasAnyRole(['Operator Sekolah', 'Kepala Sekolah'])) {
      return (int) $rombel->sekolah_id === (int) $user->ptk?->sekolah_id;
    }

    if ($user->hasRole('Guru')) {
      return (int) $rombel->ptk_id === (int) $user->ptk?->id;
    }

    return false;
  }

  /**
   * Hak mengisi/mengubah presensi satu rombel: hanya wali kelas
   * rombel itu (Guru) dan Operator Sekolah sekolah itu. Admin
   * dan Kepala Sekolah hanya memantau — tidak pernah mengisi.
   */
  private function bolehIsi(Rombel $rombel): bool
  {
    $user = Auth::user();

    if ($user->hasRole('Operator Sekolah')) {
      return (int) $rombel->sekolah_id === (int) $user->ptk?->sekolah_id;
    }

    if ($user->hasRole('Guru')) {
      return (int) $rombel->ptk_id === (int) $user->ptk?->id;
    }

    return false;
  }

  /**
   * Jendela koreksi untuk Guru: tanggal lampau hanya boleh
   * diisi/diubah dalam batas hari yang diatur Operator sekolah
   * (bawaan 7 hari). Operator sendiri tidak dibatasi jendela.
   */
  private function dalamJendelaKoreksi(Rombel $rombel, Carbon $tanggal): bool
  {
    $batas = PresensiPengaturan::batasUntuk((int) $rombel->sekolah_id);

    return $tanggal->gte(now()->startOfDay()->subDays($batas));
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
        'siswa_id'   => $a->siswa->id,
        'nama_siswa' => $a->siswa->nama_siswa,
        'nisn'       => $a->siswa->nisn,
        'status'     => $existing->status ?? Presensi::STATUS_HADIR,
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
    return RombelAnggota::with('siswa')
      ->where('rombel_id', $rombel->id)
      ->whereHas('siswa', function ($q) use ($rombel, $tanggal) {
        $q->whereHas('siswaRegistrasi', function ($qr) use ($rombel, $tanggal) {
          $qr->where('sekolah_id', $rombel->sekolah_id)
            ->where(function ($qw) use ($tanggal) {
              $qw->whereNull('tanggal_diterima')
                ->orWhereDate('tanggal_diterima', '<=', $tanggal);
            })
            ->where(function ($qw) use ($tanggal) {
              $qw->whereNull('tanggal_keluar')
                ->orWhereDate('tanggal_keluar', '>=', $tanggal);
            });
        });
      })
      ->get();
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
