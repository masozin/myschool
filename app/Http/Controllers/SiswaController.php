<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Imports\SiswaImport;
use App\Models\RombelAnggota;
use App\Models\SiswaRegistrasi;
use App\Models\Rombel;
use App\Models\Sekolah;
use App\Models\Semester;
use App\Models\Siswa;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SiswaExport;

class SiswaController extends Controller
{
  public function index()
  {
    $user = Auth::user();
    $semesterId = session('semester_id');

    // Data Sekolah untuk Admin
    $sekolahs = [];
    if ($user->hasRole('Admin')) {
      $sekolahs = Sekolah::with('detailTerbaru')->get();
    }

    // Data Rombel untuk Operator (sesuai sekolahnya)
    $rombels = [];
    if ($user->hasRole('Operator Sekolah')) {
      $rombels = Rombel::where('sekolah_id', $user->ptk->sekolah_id)
        ->where('semester_id', $semesterId)
        ->get();
    }
    return view('content.siswa.index', compact('sekolahs', 'rombels'));
  }

  public function show($id)
  {
    $siswa = Siswa::with([
      'registrasiTerbaru',
      'siswaRegistrasi',   // semua riwayat registrasi
    ])->findOrFail($id);

    // Opsional: cek izin jika pakai Policy
    // $this->authorize('view', $siswa);

    return view('content.siswa.show', compact('siswa'));
  }

  public function tabel(Request $request): JsonResponse
  {
    $user = Auth::user();
    try {
      $semesterId     = session('semester_id');

      $query = Siswa::query()->whereHas('siswaRegistrasi');

      // ── Filter Guru Kelas ─────────────────────────────────────────────
      if ($request->filled('rombel_id')) {
        $rombelId = $request->integer('rombel_id');

        if (Auth::user()->hasRole('Guru')) {
          $rombelMilikGuru = \App\Models\Rombel::where('id', $rombelId)
            ->where('ptk_id', Auth::user()->ptk->id)
            ->exists();

          if (!$rombelMilikGuru) {
            return response()->json(['error' => 'Akses ditolak.'], 403);
          }
        }

        $query->whereHas('rombelAnggota', function ($q) use ($rombelId) {
          $q->where('rombel_id', $rombelId)->whereNull('end_date');
        });
      } else {
        // ── Filter semester untuk Operator/Admin ──────────────────────
        // Hanya tampilkan siswa yang terdaftar di semester yang dipilih
        // Siswa tanpa rombel di semester ini tetap tampil (whereDoesntHave dikecualikan)
        $query->where(function ($q) use ($semesterId) {

          // Siswa yang punya anggota rombel di semester ini
          $q->whereHas('rombelAnggota', function ($qa) use ($semesterId) {
            $qa->where('semester_id', $semesterId);
          })
            // ATAU siswa yang registrasinya aktif di tahun pelajaran ini
            // tapi belum masuk rombel (Tanpa Rombel)
            ->orWhereHas('siswaRegistrasi', function ($qr) {
              $qr->where('status', 'aktif');
              // SekolahScope sudah memfilter sekolah otomatis
            });
        });
      }

      // ── Filter Sekolah (hanya Admin yang bisa filter per sekolah) ─────
      if ($user->hasRole('Admin') && $request->filled('sekolah_id')) {
        $query->whereHas('siswaRegistrasi', function ($q) use ($request) {
          $q->where('sekolah_id', $request->integer('sekolah_id'));
        });
      }

      $totalData = $query->count();

      // Eager loading
      $query->with([
        'siswaRegistrasi' => function ($q) {
          $q->latest()->with('sekolah.detailTerbaru');
        },
        'rombelAnggota' => function ($q) use ($semesterId) {
          // Filter anggota rombel hanya di semester yang dipilih
          $q->where('semester_id', $semesterId)
            ->whereNull('end_date')
            ->latest('id')
            ->with('rombel');
        }
      ]);

      // Search
      if ($request->filled('search.value')) {
        $search = $request->input('search.value');
        $query->where(function ($q) use ($search) {
          $q->where('nama_siswa', 'LIKE', "%{$search}%")
            ->orWhere('nisn', 'LIKE', "%{$search}%")
            ->orWhere('nik', 'LIKE', "%{$search}%");
        });
      }

      $totalFiltered = $query->count();

      // Breakdown jenis kelamin sesuai filter yang sedang aktif (tanpa pagination)
      $jumlahLaki      = (clone $query)->where('jenis_kelamin', 'L')->count();
      $jumlahPerempuan = (clone $query)->where('jenis_kelamin', 'P')->count();

      // Pagination & Ordering
      $columns    = [1 => 'nama_siswa', 2 => 'nisn', 3 => 'jenis_kelamin', 4 => 'status'];
      $limit      = $request->input('length', 10);
      $start      = $request->input('start', 0);
      $orderIndex = $request->input('order.0.column');
      $order      = $columns[$orderIndex] ?? 'nama_siswa';
      $dir        = $request->input('order.0.dir', 'asc');

      // Jika length -1, ambil semua data (untuk non-admin/client-side)
      if ($limit == -1) {
        $siswaRecords = $query->orderBy($order, $dir)->get();
      } else {
        $siswaRecords = $query->offset($start)->limit($limit)->orderBy($order, $dir)->get();
      }
      // $siswaRecords = $query->offset($start)->limit($limit)->orderBy($order, $dir)->get();

      $data  = [];
      $index = $start;

      foreach ($siswaRecords as $item) {
        $registrasi    = $item->siswaRegistrasi->first();
        $rombelAnggota = $item->rombelAnggota->first();

        // Gunakan detailTerbaru yang sudah ada di model Sekolah
        $namaSekolah = $registrasi?->sekolah?->detailTerbaru?->nama_sekolah;

        $data[] = [
          'fake_id'      => ++$index,
          'id'           => $item->id,
          'nama_siswa'   => $item->nama_siswa,
          'nisn'         => $item->nisn,
          'nis'          => $registrasi?->nis,                 // tambahkan
          'tempat_lahir' => $item->tempat_lahir,                // tambahkan
          'tgl_lahir'    => $item->tgl_lahir,
          'nama_sekolah' => $namaSekolah,
          'rombel_id'    => $rombelAnggota?->rombel_id,
          'nama_rombel'  => $rombelAnggota?->rombel?->nama_rombel,
          'jenis_kelamin' => $item->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan',
          'status'        => $registrasi?->status ?? $item->status,
          'tgl_keluar'    => $registrasi?->tanggal_keluar,
          'photo'         => $item->photo,
        ];
      }

      return response()->json([
        'draw'            => intval($request->input('draw')),
        'recordsTotal'    => intval($totalData),
        'recordsFiltered' => intval($totalFiltered),
        'jumlah_laki'      => intval($jumlahLaki),
        'jumlah_perempuan' => intval($jumlahPerempuan),
        'data'            => $data,
      ]);
    } catch (\Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * Simpan data Siswa.
   */
  public function store(Request $request)
  {

    $sekolahId = Auth::user()->hasRole('Admin')
      ? $request->sekolah_id
      : Auth::user()->ptk->sekolah_id;

    $request->validate([
      // Validasi Tabel Siswa
      'nama_siswa'     => 'required|string|max:255',
      'nisn'           => 'required|digits:10|unique:tabel_siswa,nisn',
      'jenis_kelamin'  => 'required|in:L,P',
      'tempat_lahir'   => 'required|string|max:255',
      'tgl_lahir'      => 'required|date',
      'nik'            => 'nullable|digits:16|unique:tabel_siswa,nik',
      'agama'          => 'required|string',
      'alamat'         => 'required|string',
      'photo'          => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

      // Validasi Tabel Registrasi
      'sekolah_id'        => Auth::user()->hasRole('Admin') ? 'required|exists:tabel_sekolah,id' : 'nullable',
      'nis' => [
        'required',
        'string',
        Rule::unique('tabel_siswa_registrasi', 'nis')->where('sekolah_id', $sekolahId)
      ],
      'jenis_pendaftaran' => 'required|in:Baru,Pindahan',
      'tingkat'           => 'required|integer',
      'tanggal_diterima'  => 'required|date',
      'status_registrasi' => 'nullable|in:aktif,mutasi,lulus,keluar,meninggal',
    ]);

    $photoPath = null;
    if ($request->hasFile('photo')) {
      // Ambil ekstensi asli (jpg/png)
      $extension = $request->file('photo')->getClientOriginalExtension();

      // Beri nama file: NISN.ekstensi (Contoh: 12345678.jpg)
      $fileName = $request->nisn . '.' . $extension;

      // Simpan ke folder 'photos/siswa' dengan nama tersebut
      // 'public' di sini merujuk pada disk, 'photos/siswa' adalah foldernya
      $photoPath = $request->file('photo')->storeAs('photos/siswa', $fileName, 'public');
    }

    $wali_status = $request->wali_status;
    $nama_wali = $request->nama_wali;
    $pekerjaan_wali = $request->pekerjaan_wali;

    if ($wali_status === 'ayah') {
      $nama_wali = $request->nama_ayah;
      $nik_wali = $request->nik_ayah;
      $tahun_wali = $request->tahun_ayah;
      $pekerjaan_wali = $request->pekerjaan_ayah;
      $no_hp_wali = $request->no_hp;
    } elseif ($wali_status === 'ibu') {
      $nama_wali = $request->nama_ibu;
      $nik_wali = $request->nik_ibu;
      $tahun_wali = $request->tahun_ibu;
      $pekerjaan_wali = $request->pekerjaan_ibu;
      $no_hp_wali = $request->no_hp;
    } else {
      $nama_wali = $request->nama_wali;
      $nik_wali = $request->nik_wali;
      $tahun_wali = $request->tahun_wali;
      $pekerjaan_wali = $request->pekerjaan_wali;
      $no_hp_wali = $request->no_hp;
    }

    // Simpan ke database menggunakan variabel $nama_wali & $pekerjaan_wali

    // 2. Memulai Database Transaction
    DB::beginTransaction();

    try {
      // A. Simpan ke Tabel Siswa
      $siswa = Siswa::create([
        'nama_siswa'     => $request->nama_siswa,
        'nisn'           => $request->nisn,
        'jenis_kelamin'  => $request->jenis_kelamin,
        'tempat_lahir'   => $request->tempat_lahir,
        'tgl_lahir'      => $request->tgl_lahir,
        'nik'            => $request->nik,
        'agama'          => $request->agama,
        'alamat'         => $request->alamat,
        'nama_ayah'      => $request->nama_ayah,
        'tahun_ayah'     => $request->tahun_ayah,
        'nik_ayah'       => $request->nik_ayah,
        'pekerjaan_ayah' => $request->pekerjaan_ayah,
        'nama_ibu'       => $request->nama_ibu,
        'tahun_ibu'      => $request->tahun_ibu,
        'nik_ibu'        => $request->nik_ibu,
        'pekerjaan_ibu'  => $request->pekerjaan_ibu,
        'no_hp'          => $request->no_hp,
        'nama_wali'       => $nama_wali,
        'tahun_wali'      => $tahun_wali,
        'nik_wali'        => $nik_wali,
        'pekerjaan_wali'  => $pekerjaan_wali,
        'no_hp_wali'      => $no_hp_wali,
        'photo'           => $photoPath,
        'status'          => 'aktif',
      ]);

      // B. Simpan ke Tabel Registrasi Siswa
      // Kita menggunakan ID dari siswa yang baru saja dibuat ($siswa->id)
      SiswaRegistrasi::create([
        'siswa_id'          => $siswa->id, // Foreign Key dari tabel siswa
        'sekolah_id'        => $sekolahId,
        'nis'               => $request->nis,
        'jenis_pendaftaran' => $request->jenis_pendaftaran,
        'tingkat'           => $request->tingkat,
        'tanggal_diterima'  => $request->tanggal_diterima,
        'status'            => 'aktif',
      ]);

      // Jika semua berhasil, Commit transaksi
      DB::commit();

      return response()->json([
        'status'  => 'success',
        'message' => 'Data Siswa dan Registrasi berhasil disimpan.'
      ], 200);
    } catch (\Exception $e) {
      // Jika ada error, batalkan semua perubahan database
      DB::rollback();
      return response()->json([
        'status'  => 'error',
        'message' => 'Gagal menambahkan data.' . $e->getMessage(),
      ], 500);
    }
  }

  /**
   * Ambil data tunggal untuk modal Edit.
   */
  public function edit($id)
  {
    try {
      $siswa = Siswa::with('siswaRegistrasi')->findOrFail($id);

      // Cek apakah siswa sudah terdaftar di rombel
      $isLocked = RombelAnggota::where('siswa_id', $id)->exists();

      return response()->json([
        'status'    => 'success',
        'data'      => $siswa,
        'is_locked' => $isLocked // Kirim status kunci ke front-end
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'status'  => 'error',
        'message' => 'Data tidak ditemukan.'
      ], 404);
    }
  }

  public function update(Request $request, $id)
  {
    // 1. Ambil data siswa & registrasi
    $siswa = Siswa::findOrFail($id);
    $registrasi = SiswaRegistrasi::where('siswa_id', $id)->firstOrFail();

    // 2. CEK KONDISI: Apakah siswa sudah terdaftar di tabel_anggota_rombel?
    $isLocked = RombelAnggota::where('siswa_id', $id)->exists();

    // 3. Validasi
    $rules = [
      'nama_siswa'    => 'required|string|max:255',
      'photo'         => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
      'nisn'          => 'required|digits:10|unique:tabel_siswa,nisn,' . $id,
      'jenis_kelamin' => 'required|in:L,P',
      'tempat_lahir'  => 'required|string|max:255',
      'tgl_lahir'     => 'required|date',
      'nik'           => 'nullable|digits:16|unique:tabel_siswa,nik,' . $id,
      'agama'         => 'required|string',
      'alamat'        => 'required|string',

      // Data Ortu
      'nama_ayah'     => 'required|string',
      'nama_ibu'      => 'required|string',

      // Data Registrasi
      'nis'               => 'required|string',
      'jenis_pendaftaran' => 'required|in:Baru,Pindahan',
      'tanggal_diterima'  => 'required|date',
    ];

    // Jika siswa BELUM masuk anggota rombel, tingkat wajib divalidasi
    if (!$isLocked) {
      $rules['tingkat'] = 'required|integer';
    }

    $photoPath = null;
    if ($request->hasFile('photo')) {
      // Hapus foto lama jika ingin benar-benar bersih (opsional karena storeAs akan menimpa file dengan nama yang SAMA persis)
      if ($siswa->photo && Storage::disk('public')->exists($siswa->photo)) {
        Storage::disk('public')->delete($siswa->photo);
      }

      $extension = $request->file('photo')->getClientOriginalExtension();
      $fileName = $request->nisn . '.' . $extension;

      // Simpan file baru
      $photoPath = $request->file('photo')->storeAs('photos/siswa', $fileName, 'public');
      $siswa->photo = $photoPath;
    }

    $request->validate($rules);

    $wali_status = $request->wali_status;
    $nama_wali = $request->nama_wali;
    $pekerjaan_wali = $request->pekerjaan_wali;

    if ($wali_status === 'ayah') {
      $nama_wali = $request->nama_ayah;
      $nik_wali = $request->nik_ayah;
      $tahun_wali = $request->tahun_ayah;
      $pekerjaan_wali = $request->pekerjaan_ayah;
      $no_hp_wali = $request->no_hp;
    } elseif ($wali_status === 'ibu') {
      $nama_wali = $request->nama_ibu;
      $nik_wali = $request->nik_ibu;
      $tahun_wali = $request->tahun_ibu;
      $pekerjaan_wali = $request->pekerjaan_ibu;
      $no_hp_wali = $request->no_hp;
    } else {
      $nama_wali = $request->nama_wali;
      $nik_wali = $request->nik_wali;
      $tahun_wali = $request->tahun_wali;
      $pekerjaan_wali = $request->pekerjaan_wali;
      $no_hp_wali = $request->no_hp;
    }


    DB::beginTransaction();
    try {
      // 4. Update Tabel Siswa
      $siswa->update([
        'nama_siswa'     => $request->nama_siswa,
        'nisn'           => $request->nisn,
        'jenis_kelamin'  => $request->jenis_kelamin,
        'tempat_lahir'   => $request->tempat_lahir,
        'tgl_lahir'      => $request->tgl_lahir,
        'nik'            => $request->nik,
        'agama'          => $request->agama,
        'alamat'         => $request->alamat,
        'nama_ayah'      => $request->nama_ayah,
        'tahun_ayah'     => $request->tahun_ayah,
        'nik_ayah'       => $request->nik_ayah,
        'pekerjaan_ayah' => $request->pekerjaan_ayah,
        'nama_ibu'       => $request->nama_ibu,
        'tahun_ibu'      => $request->tahun_ibu,
        'nik_ibu'        => $request->nik_ibu,
        'pekerjaan_ibu'  => $request->pekerjaan_ibu,
        'no_hp'          => $request->no_hp,
        'nama_wali'       => $nama_wali,
        'tahun_wali'      => $tahun_wali,
        'nik_wali'        => $nik_wali,
        'pekerjaan_wali'  => $pekerjaan_wali,
        'no_hp_wali'      => $no_hp_wali,
        'photo'           => $photoPath,
      ]);

      // 5. Update Tabel Registrasi
      $dataRegistrasi = [
        'nis'               => $request->nis,
        'jenis_pendaftaran' => $request->jenis_pendaftaran,
        'tanggal_diterima'  => $request->tanggal_diterima,
      ];

      // HANYA update tingkat jika BELUM ada di anggota rombel
      if (!$isLocked) {
        $dataRegistrasi['tingkat'] = $request->tingkat;
      }

      $registrasi->update($dataRegistrasi);

      DB::commit();

      return response()->json([
        'status'  => 'success',
        'message' => 'Data Siswa berhasil diperbarui.'
      ], 200);
    } catch (\Exception $e) {
      DB::rollback();
      return response()->json([
        'status'  => 'error',
        'message' => 'Gagal memperbarui data: ' . $e->getMessage(),
      ], 500);
    }
  }

  public function registrasi(Request $request, $id)
  {
    $request->validate([
      'status'         => 'required|in:aktif,mutasi,lulus,keluar,meninggal',
      'tanggal_keluar' => 'required_unless:status,aktif|nullable|date',
      'alasan'         => 'nullable|string|max:255',
    ]);

    $user = Auth::user();

    DB::beginTransaction();
    try {
      // Ambil registrasi TERBARU, dan batasi sekolahnya untuk non-Admin
      $registrasi = SiswaRegistrasi::where('siswa_id', $id)
        ->when(!$user->hasRole('Admin'), fn($q) => $q->where('sekolah_id', $user->ptk->sekolah_id))
        ->latest('id')
        ->firstOrFail();

      $isAktif = $request->status === 'aktif';

      $registrasi->update([
        'status'         => $request->status,
        'tanggal_keluar' => $isAktif ? null : $request->tanggal_keluar,
        'alasan'         => $isAktif ? null : $request->alasan,
      ]);

      // Sinkronkan status di tabel siswa (dipakai oleh tabel())
      Siswa::where('id', $id)->update(['status' => $request->status]);

      // Siswa yang keluar/lulus/mutasi/meninggal dikeluarkan dari rombel aktif
      if (!$isAktif) {
        RombelAnggota::where('siswa_id', $id)
          ->whereNull('end_date')
          ->update(['end_date' => $request->tanggal_keluar]);
      }

      DB::commit();

      return response()->json([
        'status'  => 'success',
        'message' => 'Status siswa berhasil diperbarui menjadi ' . ucfirst($request->status) . '.',
      ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
      DB::rollBack();
      return response()->json(['status' => 'error', 'message' => 'Data registrasi siswa tidak ditemukan.'], 404);
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json(['status' => 'error', 'message' => 'Gagal mengubah status: ' . $e->getMessage()], 500);
    }
  }

  // =========================================================================
  // DOWNLOAD TEMPLATE EXCEL
  // =========================================================================
  public function downloadTemplate()
  {
    $path = public_path('templates/template-import-siswa.xlsx');

    if (!file_exists($path)) {
      return response()->json(['message' => 'Template tidak ditemukan.'], 404);
    }

    return response()->download($path, 'template-import-siswa.xlsx');
  }

  // =========================================================================
  // IMPORT SISWA DARI EXCEL
  // =========================================================================
  public function import(Request $request)
  {
    $user    = Auth::user();
    $isAdmin = $user->hasRole('Admin');

    // Validasi request
    $request->validate([
      'file_excel' => 'required|file|mimes:xlsx,xls|max:5120', // max 5MB
      'sekolah_id' => $isAdmin ? 'required|exists:tabel_sekolah,id' : 'nullable',
    ]);

    // Tentukan sekolah_id
    $sekolahId = $isAdmin
      ? $request->integer('sekolah_id')
      : $user->ptk->sekolah_id;

    if (!$sekolahId) {
      return response()->json([
        'message' => 'Sekolah tidak ditemukan.',
      ], 422);
    }

    try {
      $import = new SiswaImport($sekolahId);
      Excel::import($import, $request->file('file_excel'));

      // Jika ada error validasi — batalkan semua, kirim report
      if ($import->hasErrors()) {
        return response()->json([
          'status'  => 'error',
          'message' => 'Import dibatalkan. Terdapat ' . count($import->getErrors()) . ' kesalahan.',
          'errors'  => $import->getErrors(),
          'total_error' => count($import->getErrors()),
        ], 422);
      }

      return response()->json([
        'status'   => 'success',
        'message'  => 'Berhasil mengimport ' . $import->getBerhasil() . ' data siswa.',
        'berhasil' => $import->getBerhasil(),
      ]);
    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
      return response()->json([
        'status'  => 'error',
        'message' => 'File Excel tidak valid.',
        'errors'  => [['baris' => '-', 'kolom' => 'File', 'nilai' => '-', 'pesan' => $e->getMessage()]],
      ], 422);
    } catch (\Exception $e) {
      return response()->json([
        'status'  => 'error',
        'message' => 'Gagal memproses file: ' . $e->getMessage(),
      ], 500);
    }
  }

  public function export(Request $request)
  {
    $user = Auth::user();

    $sekolahId = $user->hasRole('Admin')
      ? $request->input('sekolah_id')
      : $user->ptk->sekolah_id;

    $rombelId = $request->input('rombel_id');
    $semesterId = session('semester_id');

    return Excel::download(
      new SiswaExport($sekolahId, $rombelId, $semesterId),
      'data-siswa-' . now()->format('Ymd-His') . '.xlsx'
    );
  }

  public function statsPivotUsia(Request $request): JsonResponse
  {
    $user = Auth::user();

    $query = Siswa::query()->whereHas('siswaRegistrasi', function ($q) {
      $q->where('status', 'aktif');
    });

    if ($user->hasRole('Admin')) {
      if ($request->filled('sekolah_id')) {
        $query->whereHas('siswaRegistrasi', function ($q) use ($request) {
          $q->where('sekolah_id', $request->integer('sekolah_id'))
            ->where('status', 'aktif');
        });
      }
    } else {
      $query->whereHas('siswaRegistrasi', function ($q) use ($user) {
        $q->where('sekolah_id', $user->ptk->sekolah_id)
          ->where('status', 'aktif');
      });
    }

    $query->with(['siswaRegistrasi' => function ($q) {
      $q->where('status', 'aktif')->latest();
    }]);

    $siswaRecords = $query->get();

    $pivot = []; // [tingkat][usia] = count
    $usiaSet = [];

    foreach ($siswaRecords as $siswa) {
      $registrasi = $siswa->siswaRegistrasi->first();
      if (!$registrasi || !$registrasi->tingkat || !$siswa->tgl_lahir) continue;

      $tingkat = $registrasi->tingkat;
      $usia = \Carbon\Carbon::parse($siswa->tgl_lahir)->age;

      $pivot[$tingkat][$usia] = ($pivot[$tingkat][$usia] ?? 0) + 1;
      $usiaSet[$usia] = true;
    }

    ksort($pivot);
    ksort($usiaSet);
    $usiaList = array_keys($usiaSet);

    $rows = [];
    $colTotals = array_fill_keys($usiaList, 0);
    $grandTotal = 0;

    foreach ($pivot as $tingkat => $counts) {
      $rowTotal = 0;
      $cells = [];

      foreach ($usiaList as $usia) {
        $count = $counts[$usia] ?? 0;
        $cells[$usia] = $count;
        $rowTotal += $count;
        $colTotals[$usia] += $count;
      }

      $grandTotal += $rowTotal;

      $rows[] = [
        'tingkat' => $tingkat,
        'cells'   => $cells,
        'total'   => $rowTotal,
      ];
    }

    return response()->json([
      'usiaList'   => $usiaList,
      'rows'       => $rows,
      'colTotals'  => $colTotals,
      'grandTotal' => $grandTotal,
    ]);
  }

  public function statsPivotAgama(Request $request)
  {
    $user = Auth::user();
    $isAdmin = $user->hasRole('Admin');
    $sekolahId = $isAdmin ? null : $user->ptk?->sekolah?->id;
    $semesterId = session('semester_id');

    $agamaList = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu', 'Lainnya'];

    $anggota = RombelAnggota::with(['siswa:id,agama', 'rombel:id,tingkat,sekolah_id,semester_id'])
      ->whereNull('end_date')
      ->whereHas('rombel', function ($q) use ($sekolahId, $semesterId) {
        $q->when($sekolahId, fn($qq) => $qq->where('sekolah_id', $sekolahId))
          ->when($semesterId, fn($qq) => $qq->where('semester_id', $semesterId));
      })
      ->get();

    $rows = [];
    $colTotals = array_fill_keys($agamaList, 0);
    $grandTotal = 0;

    foreach ($anggota as $a) {
      if (!$a->rombel) continue;
      $tingkat = $a->rombel->tingkat;
      $agama = $a->siswa->agama ?? 'Lainnya';

      if (!isset($rows[$tingkat])) {
        $rows[$tingkat] = ['tingkat' => $tingkat, 'cells' => array_fill_keys($agamaList, 0), 'total' => 0];
      }

      $rows[$tingkat]['cells'][$agama] = ($rows[$tingkat]['cells'][$agama] ?? 0) + 1;
      $rows[$tingkat]['total']++;
      $colTotals[$agama] = ($colTotals[$agama] ?? 0) + 1;
      $grandTotal++;
    }

    ksort($rows);

    return response()->json([
      'agamaList'  => $agamaList,
      'rows'       => array_values($rows),
      'colTotals'  => $colTotals,
      'grandTotal' => $grandTotal,
    ]);
  }

  // ── Proses kelulusan massal & alumni ──────────────────────────────

  /**
   * Konteks sekolah untuk halaman kelulusan/alumni: operator memakai
   * sekolah PTK-nya; Admin bisa memilih lewat parameter sekolah_id.
   */
  private function konteksSekolahId(Request $request): ?int
  {
    $user = Auth::user();

    if ($user->hasRole('Admin')) {
      return $request->integer('sekolah_id')
        ?: ($user->ptk?->sekolah_id ? (int) $user->ptk->sekolah_id : null);
    }

    return $user->ptk?->sekolah_id ? (int) $user->ptk->sekolah_id : null;
  }

  /**
   * Halaman alumni: siswa yang registrasi terakhirnya di sekolah ini
   * berstatus lulus — tampilan tersaring dari data registrasi, tanpa
   * tabel alumni terpisah. Disaring per tahun pelajaran kelulusan
   * (diturunkan dari tanggal_keluar); bawaan tahun pelajaran tepat
   * di bawah tahun pelajaran semester aktif.
   */
  public function alumni(Request $request)
  {
    $sekolahId = $this->konteksSekolahId($request);
    if (!$sekolahId) {
      return redirect()->route('siswa.index')
        ->with('error', 'Konteks sekolah tidak ditemukan. Untuk Admin, buka halaman ini dengan parameter sekolah_id.');
    }

    $semesterAktif = Semester::find((int) session('semester_id'));
    $tpSebelumnya  = $semesterAktif
      ? $this->tahunPelajaranSebelumnya($semesterAktif->tahun_pelajaran)
      : null;

    $semuaAlumni = Siswa::with([
        'registrasiTerbaru',
        'rombelAnggota' => fn($q) => $q->with('rombel.semester')->latest('id'),
      ])
      ->whereHas('registrasiTerbaru', fn($q) => $q
        ->where('sekolah_id', $sekolahId)
        ->where('status', 'lulus'))
      ->orderBy('nama_siswa')
      ->get()
      ->map(function ($siswa) {
        $siswa->tp_lulus = $this->tahunPelajaranDariTanggal(
          $siswa->registrasiTerbaru?->tanggal_keluar
        );
        return $siswa;
      });

    $tpList = $semuaAlumni->pluck('tp_lulus')->filter()->unique()
      ->sortDesc()->values();
    if ($tpSebelumnya && !$tpList->contains($tpSebelumnya)) {
      $tpList = $tpList->push($tpSebelumnya)->sortDesc()->values();
    }

    $tpDipilih = $request->get('tp') ?: $tpSebelumnya ?: $tpList->first();
    $alumni = $semuaAlumni
      ->filter(fn($s) => $s->tp_lulus === $tpDipilih)
      ->values();
    foreach ($alumni as $siswa) {
      $siswa->bisa_batal = $tpSebelumnya !== null && $siswa->tp_lulus === $tpSebelumnya;
    }

    return view('content.siswa.alumni', compact('alumni', 'tpList', 'tpDipilih', 'sekolahId'));
  }

  /**
   * Batalkan kelulusan seorang alumni: registrasinya dibuka lagi
   * sebagai aktif supaya siswa bisa dimasukkan ke rombel kembali.
   * Hanya untuk kelulusan tahun pelajaran tepat di bawah tahun
   * pelajaran semester aktif (jendela koreksi tahun berjalan);
   * keanggotaan rombel yang sudah tertutup tidak diubah.
   */
  public function batalkanKelulusan(Request $request, $id)
  {
    $sekolahId = $this->konteksSekolahId($request);
    if (!$sekolahId) {
      return back()->with('error', 'Konteks sekolah tidak ditemukan.');
    }

    $registrasi = SiswaRegistrasi::where('siswa_id', $id)
      ->where('sekolah_id', $sekolahId)
      ->where('status', 'lulus')
      ->latest('id')
      ->first();

    if (!$registrasi) {
      return back()->with('error', 'Data kelulusan siswa tidak ditemukan.');
    }

    $semesterAktif = Semester::find((int) session('semester_id'));
    $tpSebelumnya  = $semesterAktif
      ? $this->tahunPelajaranSebelumnya($semesterAktif->tahun_pelajaran)
      : null;

    if (!$tpSebelumnya
      || $this->tahunPelajaranDariTanggal($registrasi->tanggal_keluar) !== $tpSebelumnya) {
      return back()->with('error', 'Pembatalan hanya untuk kelulusan tahun pelajaran tepat di bawah tahun pelajaran sekarang.');
    }

    DB::beginTransaction();
    try {
      $registrasi->update([
        'status'         => 'aktif',
        'tanggal_keluar' => null,
        'alasan'         => null,
      ]);

      Siswa::where('id', $id)->update(['status' => 'aktif']);

      DB::commit();
    } catch (\Exception $e) {
      DB::rollBack();
      return back()->with('error', 'Gagal membatalkan kelulusan: ' . $e->getMessage());
    }

    return back()->with('success', 'Kelulusan dibatalkan. Siswa kembali aktif dan bisa dimasukkan ke rombel.');
  }

  /**
   * Halaman siswa keluar: siswa yang registrasi terakhirnya di
   * sekolah ini berakhir bukan karena lulus — keluar, mutasi, atau
   * meninggal — dengan saringan per status.
   */
  public function siswaKeluar(Request $request)
  {
    $sekolahId = $this->konteksSekolahId($request);
    if (!$sekolahId) {
      return redirect()->route('siswa.index')
        ->with('error', 'Konteks sekolah tidak ditemukan. Untuk Admin, buka halaman ini dengan parameter sekolah_id.');
    }

    $statusPilihan = ['keluar', 'mutasi', 'meninggal'];
    $statusDipilih = $request->get('status', 'semua');
    if ($statusDipilih !== 'semua' && !in_array($statusDipilih, $statusPilihan)) {
      $statusDipilih = 'semua';
    }

    $siswaKeluar = Siswa::with([
        'registrasiTerbaru',
        'rombelAnggota' => fn($q) => $q->with('rombel.semester')->latest('id'),
      ])
      ->whereHas('registrasiTerbaru', function ($q) use ($sekolahId, $statusPilihan, $statusDipilih) {
        $q->where('sekolah_id', $sekolahId);
        if ($statusDipilih === 'semua') {
          $q->whereIn('status', $statusPilihan);
        } else {
          $q->where('status', $statusDipilih);
        }
      })
      ->get()
      ->sortByDesc(fn($s) => $s->registrasiTerbaru?->tanggal_keluar ?? '')
      ->values();

    return view('content.siswa.keluar', compact('siswaKeluar', 'statusDipilih', 'sekolahId'));
  }

  /**
   * Tahun pelajaran dari sebuah tanggal: Jul–Des tahun Y masuk
   * tahun pelajaran Y/(Y+1) (semester Ganjil); Jan–Jun tahun Y
   * masuk (Y-1)/Y (semester Genap).
   */
  private function tahunPelajaranDariTanggal($tanggal): ?string
  {
    if (!$tanggal) {
      return null;
    }

    $carbon = \Carbon\Carbon::parse($tanggal);
    $tahun  = (int) $carbon->format('Y');

    return $carbon->month >= 7
      ? $tahun . '/' . ($tahun + 1)
      : ($tahun - 1) . '/' . $tahun;
  }

  /** Tahun pelajaran tepat sebelum "YYYY/YYYY". */
  private function tahunPelajaranSebelumnya(string $tahunPelajaran): string
  {
    $bagian = explode('/', $tahunPelajaran);
    if (count($bagian) !== 2) {
      return $tahunPelajaran;
    }

    return ((int) $bagian[0] - 1) . '/' . ((int) $bagian[1] - 1);
  }
}
