<?php

namespace App\Http\Controllers;

use App\Models\Ptk;
use App\Models\User;
use App\Models\Sekolah;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PtkController extends Controller
{
  public function index()
  {
    $dataSekolah = [];

    if (Auth::user()->hasRole('Admin')) {
      $dataSekolah = Sekolah::with(['detailTerbaru' => function ($q) {
        $q->select('id', 'nama_sekolah');
      }])->get();
    }

    return view('content.ptk.index', compact('dataSekolah'));
  }

  public function data()
  {
    // Mengambil data PTK beserta relasi sekolahnya
    // SekolahScope akan otomatis membatasi data sesuai sekolah si operator
    $query = Ptk::with('sekolah.detailTerbaru');

    return response()->json([
      'data' => $query->get()
    ]);
  }

  public function store(Request $request)
  {

    $sekolahId = Auth::user()->hasRole('Admin')
      ? $request->sekolah_id
      : Auth::user()->ptk->sekolah_id;

    $sekolah = Sekolah::with('detailTerbaru')->find($sekolahId);
    $namaSekolah = $sekolah->detailTerbaru->nama_sekolah ?? 'sekolah dengan NPSN' . $sekolah->npsn ?? 'Sekolah';

    $rules = [
      'nama_ptk'      => 'required|string|max:255',
      'gelar_depan'   => 'nullable|string|max:50',
      'gelar_belakang' => 'nullable|string|max:100',
      'tanggal_lahir' => 'nullable|date',
      'kode'          => 'nullable|string|max:20',
      'nip'           => 'nullable|digits:18|unique:tabel_ptk,nip',
      'jenis_kelamin' => 'required|in:L,P',
      'jabatan'       => [
        'required',
        'string',
        'max:100',
        function ($attribute, $value, $fail) use ($sekolahId, $namaSekolah) {
          // Daftar jabatan yang dibatasi (hanya boleh 1 per sekolah)
          $jabatanTerbatas = ['Kepala Sekolah', 'Operator Sekolah'];

          if (in_array($value, $jabatanTerbatas)) {
            // Cek apakah sudah ada orang lain di sekolah ini dengan jabatan tersebut
            $exists = \App\Models\Ptk::where('sekolah_id', $sekolahId)
              ->where('jabatan', $value)
              ->exists();

            if ($exists) {
              $fail("Jabatan $value sudah terisi di $namaSekolah.");
            }
          }
        },
      ],
      'jenis_kepegawaian' => 'required',
    ];

    // Jika Admin, sekolah_id wajib diisi dari form
    if (Auth::user()->hasRole('Admin')) {
      $rules['sekolah_id'] = 'required|exists:tabel_sekolah,id';
    }

    $validated = $request->validate($rules);

    Ptk::create([
      'nama_ptk'      => $validated['nama_ptk'],
      'gelar_depan'   => $validated['gelar_depan'] ?? null,
      'gelar_belakang' => $validated['gelar_belakang'] ?? null,
      'tanggal_lahir' => $validated['tanggal_lahir'] ?? null,
      'kode'          => $validated['kode'] ?? null,
      'nip'           => $validated['nip'],
      'jenis_kelamin' => $validated['jenis_kelamin'],
      'jabatan'       => $validated['jabatan'],
      'jenis_kepegawaian' => $validated['jenis_kepegawaian'],
      'sekolah_id'    => $sekolahId, // Set otomatis agar tidak bisa manipulasi sekolah lain
      'user_id'       => null,       // Default null, diisi jika nanti buat akun login untuk guru ini
    ]);

    return response()->json(['message' => 'Data PTK berhasil ditambahkan.']);
  }

  public function edit($id)
  {
    $ptk = Ptk::findOrFail($id);
    return response()->json($ptk);
  }

  public function update(Request $request, $id)
  {
    $ptk = Ptk::findOrFail($id); // Scope akan memastikan user tidak bisa ambil PTK sekolah lain
    $sekolahId = $ptk->sekolah_id; // Ambil sekolah_id dari data PTK yang sedang diedit
    $sekolah = Sekolah::with('detailTerbaru')->find($sekolahId);
    $namaSekolah = $sekolah->detailTerbaru->nama_sekolah ?? 'sekolah dengna NPSN' . $sekolah->npsn ?? 'sekolah';

    $validated = $request->validate([
      'nama_ptk'      => 'required|string|max:255',
      'gelar_depan'   => 'nullable|string|max:50',
      'gelar_belakang' => 'nullable|string|max:100',
      'tanggal_lahir' => 'nullable|date',
      'kode'          => 'nullable|string|max:20',
      'nip'           => 'nullable|digits:18|unique:tabel_ptk,nip,' . $id,
      'jenis_kelamin' => 'required|in:L,P',
      'jabatan'       => [
        'required',
        'string',
        'max:100',
        function ($attribute, $value, $fail) use ($sekolahId, $id, $namaSekolah) {
          // Daftar jabatan yang dibatasi (hanya boleh 1 per sekolah)
          $jabatanTerbatas = ['Kepala Sekolah', 'Operator Sekolah'];

          if (in_array($value, $jabatanTerbatas)) {
            // Cek apakah sudah ada orang lain di sekolah ini dengan jabatan tersebut
            $exists = \App\Models\Ptk::where('sekolah_id', $sekolahId)
              ->where('jabatan', $value)
              ->where('id', '!=', $id) // Kecuali dirinya sendiri (karena sedang edit)
              ->exists();

            if ($exists) {
              $fail("Jabatan $value sudah terisi di $namaSekolah.");
            }
          }
        },
      ],
    ]);

    // Simpan jabatan lama sebelum diupdate untuk pengecekan
    $jabatanLama = $ptk->jabatan;

    $ptk->update($validated);

    // 2. Sinkronisasi Role jika PTK sudah punya akun
    if ($ptk->user_id && $jabatanLama !== $validated['jabatan']) {
      $user = \App\Models\User::find($ptk->user_id);

      if ($user) {
        // Pastikan Role tersedia di database, jika belum buat otomatis
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $validated['jabatan']]);

        // Hapus semua role lama dan pasang role baru sesuai jabatan baru
        $user->syncRoles([$role->name]);
      }
    }

    // Kembalikan JSON agar AJAX bisa menangkapnya
    return response()->json([
      'success' => true,
      'message' => 'Data PTK berhasil diperbarui.',
      'data'    => $ptk
    ]);
  }

  public function destroy($id)
  {
    try {
      $ptk = Ptk::findOrFail($id);
      $currentUser = Auth::user();

      // 1. Validasi Hak Akses untuk Operator Sekolah
      // Catatan: Sesuaikan pengecekan role di bawah ini dengan struktur aplikasi Anda
      $isAdmin = $currentUser->hasRole('Admin');

      if (strcasecmp($ptk->jabatan, 'Operator Sekolah') === 0 && !$isAdmin) {
        return response()->json([
          'message' => 'Akses ditolak. Hanya Admin yang diizinkan menghapus data Operator Sekolah.'
        ], 403);
      }

      // 2. Jalankan penghapusan dalam Database Transaction
      DB::transaction(function () use ($ptk) {
        // Ambil relasi user sebelum data PTK dihapus
        $relatedUser = $ptk->user;

        // Hapus data PTK
        $ptk->delete();

        // Jika PTK terhubung ke akun User, hapus akun User tersebut
        if ($relatedUser) {
          $relatedUser->delete();
        }
      });

      return response()->json([
        'message' => 'Data PTK dan akun user terkait berhasil dihapus.'
      ], 200);
    } catch (\Exception $e) {
      return response()->json([
        'message' => 'Terjadi kesalahan saat menghapus data.',
        'error'   => $e->getMessage()
      ], 500);
    }
  }

  public function generateAkun($id)
  {
    // 1. Ambil data sekolah
    $ptk = Ptk::findOrFail($id);

    // 2. Cek apakah PTK sudah punya akun user_id
    if ($ptk->user_id) {
      return response()->json([
        'status' => 'error',
        'message' => 'PTK ini sudah memiliki akun login.'
      ], 422);
    }

    // 3. Standarisasi Username (NIP jika ada, jika tidak gunakan nama tanpa spasi & lowercase)
    $userName = $ptk->nip ?? Str::slug($ptk->nama_ptk, '');

    // 4. Cek apakah username sudah dipakai orang lain
    if (User::where('user_name', $userName)->exists()) {
      $userName = $userName . Str::lower(Str::random(3)); // Tambah angka acak jika bentrok
    }

    // Password dibuat acak per akun (bukan lagi '12345678' yang seragam)
    // dan hanya ditampilkan sekali di respons ini.
    $plainPassword = Str::random(12);

    try {
      // Gunakan Database Transaction agar jika satu gagal, semua batal
      $result = DB::transaction(function () use ($ptk, $userName, $plainPassword) {

        // 5. Buat User Baru
        $user = User::create([
          'name'      => $ptk->nama_ptk,
          'user_name' => $userName,
          'password'  => Hash::make($plainPassword),
        ]);

        $role = Role::firstOrCreate(['name' => $ptk->jabatan, 'guard_name' => 'web']);

        $user->assignRole($role);

        // 7. Update user_id di tabel ptk
        $ptk->update(['user_id' => $user->id]);

        return [
          'username' => $userName,
          'password' => $plainPassword
        ];
      });

      return response()->json([
        'message' => 'Akun untuk ' . $ptk->nama_ptk . ' berhasil dibuat',
        'data' => $result
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'status' => 'error',
        'message' => 'Gagal membuat akun: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * Ubah status kepegawaian PTK (aktif / tidak_aktif / pensiun)
   * beserta tanggal keluarnya. Mengaktifkan kembali mengosongkan
   * tanggal keluar; status lain wajib memiliki tanggal keluar.
   */
  public function updateStatus(Request $request, $id)
  {
    $ptk = Ptk::findOrFail($id);

    $data = $request->validate([
      'status'         => 'required|in:aktif,tidak_aktif,pensiun',
      'tanggal_keluar' => 'nullable|date|required_if:status,tidak_aktif,pensiun',
    ]);

    $ptk->update([
      'status'         => $data['status'],
      'tanggal_keluar' => $data['status'] === 'aktif' ? null : $data['tanggal_keluar'],
    ]);

    $label = [
      'aktif'       => 'Aktif',
      'tidak_aktif' => 'Tidak Aktif',
      'pensiun'     => 'Pensiun',
    ][$data['status']];

    return response()->json([
      'message' => 'Status ' . $ptk->nama_ptk . ' kini ' . $label . '.',
    ]);
  }
}
