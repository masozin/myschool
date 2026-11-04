<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SekolahDetail;
use App\Models\Fitur;
use Laravolt\Indonesia\Models\Province;
use App\Models\Sekolah;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class SekolahController extends Controller
{
  function index()
  {
    if (!Auth::user()->hasRole('Admin')) {
      abort(403, 'Anda tidak memiliki akses ke halaman ini.');
    }

    $sekolah = Sekolah::with('sekolahDetail')->get();

    $semester = Semester::orderBy('kode_semester', 'desc')->get();

    $provinces = Province::all();
    $fiturs    = Fitur::all();

    return view('content.sekolah.index', compact('sekolah', 'semester', 'provinces', 'fiturs'));
  }

  public function tabel(): JsonResponse
  {
    $sekolah = Sekolah::with(['detailPerSemester', 'ptk'])->orderBy('id', 'desc')->get();

    $data = $sekolah->map(function ($s) {

      $detail = $s->detailPerSemester;

      // Cek apakah ada PTK di sekolah ini yang jabatannya Operator Sekolah DAN punya user_id
      $hasAccount = $s->ptk->where('jabatan', 'Operator Sekolah')
        ->whereNotNull('user_id')
        ->first();

      return [
        'id'              => $s->id,
        'npsn'            => $s->npsn,
        'jenjang'         => $s->jenjang,
        'nama_sekolah'    => $detail->nama_sekolah ?? '-',
        'alamat_sekolah'  => $detail->alamat_sekolah ?? '-',
        'kepala_sekolah'  => $detail->kepala_sekolah ?? '-',
        'semester_id'     => $detail->semester_id ?? '-',
        'has_operator_account' => $hasAccount ? true : false,
      ];
    });

    // dd($data);
    return response()->json($data);
  }

  public function edit($id)
  {
    $sekolah = Sekolah::with('detailPerSemester')->findOrFail($id);
    $detail = $sekolah->detailPerSemester;

    $fiturAktif = $sekolah->fiturs()
      ->wherePivot('aktif', true)
      ->pluck('kode')
      ->toArray();

    return response()->json([
      'id'             => $sekolah->id,
      'npsn'           => $sekolah->npsn,
      'jenjang'        => $sekolah->jenjang,
      'nama_sekolah'   => $detail->nama_sekolah ?? '', // Sesuaikan jika pakai detail_terbaru
      'kepala_sekolah' => $detail->kepala_sekolah ?? '',
      'nip'            => $detail->nip ?? '',
      // ID Alamat untuk mentrigger cascade di JS
      'province'       => $detail->province_id ?? '',
      'regency'        => $detail->regency_id ?? '',
      'district'       => $detail->district_id ?? '',
      'village'        => $detail->village_id ?? '',
      'alamat_sekolah' => $detail->alamat_sekolah ?? '',
      'fitur_aktif'   => $fiturAktif,
      'libur_mingguan' => $detail->libur_mingguan ?? [],
    ]);
  }

  public function update(Request $request, $id)
  {
    // 1. Validasi Input
    $validator = Validator::make($request->all(), [
      'npsn'           => 'required|string|size:8|unique:tabel_sekolah,npsn,' . $id,
      'jenjang'        => 'required|in:SD,SMP,SMA,SMK',
      'nama_sekolah'   => 'required|string|max:255',
      'province'       => 'required',
      'regency'        => 'required',
      'district'       => 'required',
      'village'        => 'required',
      'alamat_sekolah' => 'required|string',
      'kepala_sekolah' => 'required|string|max:255',
      'nip'            => 'nullable|string|size:18',
      'libur_mingguan'   => 'nullable|array',
      'libur_mingguan.*' => 'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
    ]);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    DB::beginTransaction();

    try {
      // 2. Ambil data sekolah induk
      $sekolah = Sekolah::findOrFail($id);
      $semester_id = session('semester_id');

      // 3. Update data di tabel induk (tabel_sekolah)
      $sekolah->update([
        'npsn'    => $request->npsn,
        'jenjang' => $request->jenjang,
      ]);

      // 4. Logika History: Cek apakah sudah ada detail untuk semester INI
      // Kita gunakan relasi Sekolah yang merujuk ke tabel_sekolah_detail
      $detail = $sekolah->sekolahDetail()->where('semester_id', $semester_id)->first();

      $dataDetail = [
        'nama_sekolah'   => $request->nama_sekolah,
        'province_id'    => $request->province, // Simpan ID dari select2
        'regency_id'     => $request->regency,
        'district_id'    => $request->district,
        'village_id'     => $request->village,
        'alamat_sekolah' => $request->alamat_sekolah,
        'kepala_sekolah' => $request->kepala_sekolah,
        'nip'            => $request->nip,
        'libur_mingguan' => $request->libur_mingguan ?? [],
      ];

      if ($detail) {
        // Skenario A: Jika sudah ada detail di semester ini, UPDATE
        $detail->update($dataDetail);
        $message = "Data sekolah berhasil diperbarui.";
      } else {
        // Skenario B: Jika belum ada (migrasi dari semester lalu), CREATE
        $dataDetail['semester_id'] = $semester_id;
        $sekolah->sekolahDetail()->create($dataDetail);
        $message = "Data baru telah dicatatkan untuk semester ini.";
      }

      DB::commit();

      return response()->json([
        'message' => $message,
        'data'    => $sekolah->load('detailTerbaru')
      ], 200);
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json([
        'message' => 'Gagal memperbarui data.',
        'error'   => $e->getMessage()
      ], 500);
    }
  }

  public function store(Request $request)
  {
    // 1. Validasi Input
    $validator = Validator::make($request->all(), [
      'npsn'           => 'required|string|size:8|unique:tabel_sekolah,npsn',
      'jenjang'        => 'required|in:SD,SMP,SMA,SMK',
      'nama_sekolah'   => 'required|string|max:255',
      'province'       => 'nullable|string',
      'regency'        => 'nullable|string',
      'district'       => 'nullable|string',
      'village'        => 'nullable|string',
      'alamat_sekolah' => 'required|string',
      'kepala_sekolah' => 'required|string|max:255',
      'nip'            => 'nullable|string|size:18',
      'libur_mingguan'   => 'nullable|array',
      'libur_mingguan.*' => 'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
    ]);

    // dd($request);

    if ($validator->fails()) {
      return response()->json(['errors' => $validator->errors()], 422);
    }

    // 2. Mulai Transaction
    DB::beginTransaction();

    try {
      // A. Simpan ke tabel_sekolah
      $sekolah = Sekolah::create([
        'npsn'    => $request->npsn,
        'jenjang' => $request->jenjang,
      ]);

      // B. Simpan ke tabel_sekolah_detail (sebagai history pertama)
      // sekolah_id otomatis terisi oleh relasi
      $sekolah->sekolahDetail()->create([
        'semester_id'    => session('semester_id'),
        'nama_sekolah'   => $request->nama_sekolah,
        'province_id'    => $request->province,
        'regency_id'     => $request->regency,
        'district_id'    => $request->district,
        'village_id'     => $request->village,
        'alamat_sekolah' => $request->alamat_sekolah,
        'kepala_sekolah' => $request->kepala_sekolah,
        'nip'            => $request->nip,
        'libur_mingguan' => $request->libur_mingguan ?? [],
      ]);

      // C. Jika semua oke, simpan permanen
      DB::commit();

      return response()->json([
        'message' => 'Data sekolah berhasil ditambahkan!',
        'data'    => $sekolah->load('detailTerbaru')
      ], 201);
    } catch (\Exception $e) {
      // D. Jika ada error, batalkan semua perubahan
      DB::rollBack();

      return response()->json([
        'message' => 'Gagal menambahkan data.',
        'error'   => $e->getMessage()
      ], 500);
    }
  }

  public function destroy($id)
  {
    try {
      $sekolah = Sekolah::findOrFail($id);

      // 1. Daftar relasi yang ingin dicek (selain tabel detail)
      // Ganti 'siswas' atau 'gurus' sesuai dengan nama relasi di Model Sekolah Anda
      $hasRelations = $sekolah->siswaRegistrasi()->exists() ||
        $sekolah->ptk()->exists() ||
        $sekolah->rombel()->exists();

      if ($hasRelations) {
        return response()->json([
          'message' => 'Data tidak bisa dihapus karena sudah memiliki data Siswa atau Guru terkait.'
        ], 422);
      }

      // 2. Jika aman, lakukan Soft Delete
      $sekolah->delete();

      return response()->json([
        'message' => 'Data sekolah berhasil dihapus (soft delete).'
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'message' => 'Terjadi kesalahan saat menghapus data.',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  public function generateAkun($id)
  {
    // 1. Ambil data sekolah
    $sekolah = Sekolah::with('detailTerbaru')->findOrFail($id);
    $namaSekolah = $sekolah->detailTerbaru->nama_sekolah ?? 'Sekolah dengan NPSN ' . $sekolah->npsn ?? 'sekolah';

    if (!$sekolah) {
      return response()->json(['message' => 'Sekolah tidak ditemukan'], 404);
    }

    // 2. Cek apakah user dengan username NPSN tersebut sudah ada
    $userExists = User::where('user_name', $sekolah->npsn)->exists();

    if ($userExists) {
      return response()->json(['message' => 'Akun untuk NPSN ini sudah tersedia'], 400);
    }

    // 3. Gunakan Transaction untuk memastikan data konsisten di kedua tabel
    return DB::transaction(function () use ($sekolah, $namaSekolah) {


      // Buat User Baru
      $user = User::create([
        'name'      => 'Operator' . $namaSekolah,
        'user_name' => $sekolah->npsn,
        'password'  => Hash::make('admin' . $sekolah->npsn),
      ]);

      // Jika belum ada, Spatie akan otomatis membuatnya
      $role = Role::firstOrCreate(['name' => 'Operator Sekolah', 'guard_name' => 'web']);

      $user->assignRole($role);

      // Buat atau Update data di tabel_ptk sebagai Admin/Operator
      // Kita asumsikan satu sekolah memiliki satu record PTK khusus operator/admin
      DB::table('tabel_ptk')->insert([
        'nama_ptk'      => 'Operator ' . $namaSekolah,
        'nip'           => null,
        'jenis_kelamin' => 'L', // Default atau sesuaikan
        'jabatan'       => 'Operator Sekolah',
        'sekolah_id'    => $sekolah->id,
        'jenis_kepegawaian' => 'Honorer',
        'user_id'       => $user->id, // Menghubungkan ke user yang baru dibuat
        'created_at'    => now(),
        'updated_at'    => now(),
      ]);

      return response()->json([
        'message' => 'Akun operator untuk ' . $namaSekolah . ' berhasil dibuat',
        'data' => [
          'username' => $sekolah->npsn,
          'password' => 'admin' . $sekolah->npsn
        ]
      ]);
    });
  }

  // =========================================================================
  // GET FITUR AKTIF PER SEKOLAH
  // =========================================================================
  public function getFitur($sekolah_id)
  {
    $sekolah = Sekolah::findOrFail($sekolah_id);

    $fiturAktif = $sekolah->fiturs()
      ->wherePivot('aktif', true)
      ->pluck('kode')
      ->toArray();

    return response()->json([
      'sekolah_id'  => $sekolah->id,
      'fitur_aktif' => $fiturAktif,
    ]);
  }

  // =========================================================================
  // TOGGLE FITUR SEKOLAH
  // (Bisa juga dijadikan route closure di web.php seperti sebelumnya,
  //  tapi lebih rapi jika ada di controller)
  // =========================================================================
  public function toggleFitur(Request $request)
  {
    $request->validate([
      'sekolah_id' => 'required|exists:tabel_sekolah,id',
      'fitur_id'   => 'required|exists:tabel_fitur,id',
      'aktif'      => 'required|boolean',
    ]);

    \App\Models\FiturSekolah::updateOrCreate(
      [
        'sekolah_id' => $request->sekolah_id,
        'fitur_id'   => $request->fitur_id,
      ],
      ['aktif' => $request->boolean('aktif')]
    );

    \App\Helpers\FiturHelper::clearCache((int) $request->sekolah_id);

    return response()->json(['message' => 'Fitur berhasil diperbarui.']);
  }
}
