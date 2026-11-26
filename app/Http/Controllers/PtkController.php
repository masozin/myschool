<?php

namespace App\Http\Controllers;

use App\Models\Ptk;
use App\Support\KompresGambar;
use App\Models\User;
use App\Models\Sekolah;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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
      'tempat_lahir'  => 'nullable|string|max:100',
      'tanggal_lahir' => 'nullable|date',
      'photo'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
      'kode'          => 'nullable|string|max:20',
      'nip'           => 'nullable|digits:18|unique:tabel_ptk,nip',
      'npwp'          => 'nullable|string|max:30',
      'alamat'        => 'nullable|string|max:1000',
      'no_rekening'   => 'nullable|string|max:30',
      'nama_bank'     => 'nullable|string|max:100',
      'jenis_kelamin' => 'required|in:L,P',
      'jabatan'       => [
        'required',
        'string',
        'max:100',
        function ($attribute, $value, $fail) use ($sekolahId, $namaSekolah) {
          // Kepala Sekolah maksimal 1 per sekolah, dihitung gabungan
          // jabatan utama + peran tambahan + role akun orang lain.
          if ($value === 'Kepala Sekolah' && $this->peranTerbatasTerisi($sekolahId, $value)) {
            $fail("Jabatan $value sudah terisi di $namaSekolah.");
          }

          // Operator Sekolah sebagai jabatan utama tetap maksimal 1
          // per sekolah dari kolom jabatan saja — peran tambahan
          // Operator untuk guru memang diperbolehkan tanpa batas.
          if ($value === 'Operator Sekolah') {
            $exists = \App\Models\Ptk::where('sekolah_id', $sekolahId)
              ->where('jabatan', $value)
              ->exists();

            if ($exists) {
              $fail("Jabatan $value sudah terisi di $namaSekolah.");
            }
          }
        },
      ],
      'peran_tambahan'   => [
        'nullable',
        'array',
        function ($attribute, $value, $fail) use ($sekolahId, $namaSekolah) {
          // Peran terbatas maksimal 1 orang per sekolah, dihitung
          // dengan aturan gabungan yang sama seperti jabatan utama.
          foreach (['Kepala Sekolah', 'Bendahara'] as $peranTerbatas) {
            if (in_array($peranTerbatas, $value ?? [], true)
              && $this->peranTerbatasTerisi($sekolahId, $peranTerbatas)) {
              $fail("Peran $peranTerbatas sudah terisi di $namaSekolah.");
            }
          }
        },
      ],
      'peran_tambahan.*' => [
        'string',
        Rule::in(Ptk::PERAN_TERKELOLA),
        function ($attribute, $value, $fail) use ($request) {
          if ($value === $request->input('jabatan')) {
            $fail('Peran tambahan tidak boleh sama dengan jabatan utama.');
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

    $peranTambahan = $this->bersihkanPeranTambahan($validated['peran_tambahan'] ?? [], $validated['jabatan']);

    // Pengaman pemberi peran: pada PTK baru semua peran tambahan
    // dihitung sebagai penambahan, dan aktor non-Admin tidak boleh
    // menambahkan peran Operator Sekolah / Kepala Sekolah / Admin.
    $terlarang = $this->penambahanPeranTerlarang($peranTambahan, []);
    if ($terlarang) {
      return response()->json([
        'message' => 'Akses ditolak. Hanya Admin yang dapat memberikan peran tambahan: ' . implode(', ', $terlarang) . '.'
      ], 403);
    }

    // Foto disimpan di folder yang sama dengan unggahan lewat profil
    // (UserController): ptk_photos di disk public.
    $pathFoto = $request->hasFile('photo')
      ? KompresGambar::simpan($request->file('photo'), 'ptk_photos', 1200)
      : null;

    Ptk::create([
      'nama_ptk'      => $validated['nama_ptk'],
      'gelar_depan'   => $validated['gelar_depan'] ?? null,
      'gelar_belakang' => $validated['gelar_belakang'] ?? null,
      'tempat_lahir'  => $validated['tempat_lahir'] ?? null,
      'tanggal_lahir' => $validated['tanggal_lahir'] ?? null,
      'photo'         => $pathFoto,
      'kode'          => $validated['kode'] ?? null,
      'nip'           => $validated['nip'],
      'npwp'          => $validated['npwp'] ?? null,
      'alamat'        => $validated['alamat'] ?? null,
      'no_rekening'   => $validated['no_rekening'] ?? null,
      'nama_bank'     => $validated['nama_bank'] ?? null,
      'jenis_kelamin' => $validated['jenis_kelamin'],
      'jabatan'       => $validated['jabatan'],
      'peran_tambahan' => $peranTambahan ?: null,
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
      'tempat_lahir'  => 'nullable|string|max:100',
      'tanggal_lahir' => 'nullable|date',
      'photo'         => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
      'hapus_photo'   => 'nullable|boolean',
      'kode'          => 'nullable|string|max:20',
      'nip'           => 'nullable|digits:18|unique:tabel_ptk,nip,' . $id,
      'npwp'          => 'nullable|string|max:30',
      'alamat'        => 'nullable|string|max:1000',
      'no_rekening'   => 'nullable|string|max:30',
      'nama_bank'     => 'nullable|string|max:100',
      'jenis_kelamin' => 'required|in:L,P',
      'jabatan'       => [
        'required',
        'string',
        'max:100',
        function ($attribute, $value, $fail) use ($sekolahId, $id, $namaSekolah) {
          // Kepala Sekolah maksimal 1 per sekolah, dihitung gabungan
          // jabatan utama + peran tambahan + role akun orang lain.
          if ($value === 'Kepala Sekolah' && $this->peranTerbatasTerisi($sekolahId, $value, $id)) {
            $fail("Jabatan $value sudah terisi di $namaSekolah.");
          }

          // Operator Sekolah sebagai jabatan utama tetap maksimal 1
          // per sekolah dari kolom jabatan saja — peran tambahan
          // Operator untuk guru memang diperbolehkan tanpa batas.
          if ($value === 'Operator Sekolah') {
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
      'peran_tambahan'   => [
        'nullable',
        'array',
        function ($attribute, $value, $fail) use ($sekolahId, $id, $namaSekolah) {
          // Peran terbatas maksimal 1 orang per sekolah, dihitung
          // dengan aturan gabungan yang sama seperti jabatan utama.
          foreach (['Kepala Sekolah', 'Bendahara'] as $peranTerbatas) {
            if (in_array($peranTerbatas, $value ?? [], true)
              && $this->peranTerbatasTerisi($sekolahId, $peranTerbatas, $id)) {
              $fail("Peran $peranTerbatas sudah terisi di $namaSekolah.");
            }
          }
        },
      ],
      'peran_tambahan.*' => [
        'string',
        Rule::in(Ptk::PERAN_TERKELOLA),
        function ($attribute, $value, $fail) use ($request) {
          if ($value === $request->input('jabatan')) {
            $fail('Peran tambahan tidak boleh sama dengan jabatan utama.');
          }
        },
      ],
    ]);

    // Simpan jabatan lama sebelum diupdate untuk pengecekan
    $jabatanLama = $ptk->jabatan;
    $peranLama = $ptk->peran_tambahan ?? [];

    // Peran tambahan yang tidak dikirim dianggap dikosongkan: form
    // edit selalu menampilkan checkbox-nya, jadi kotak yang tidak
    // dicentang memang berarti peran itu dicabut.
    $peranTambahan = $this->bersihkanPeranTambahan($request->input('peran_tambahan', []), $validated['jabatan']);
    $validated['peran_tambahan'] = $peranTambahan ?: null;

    // Pengaman pemberi peran: hanya PENAMBAHAN peran terlarang yang
    // ditolak bagi aktor non-Admin; peran lama yang sudah terpasang
    // boleh tetap dipertahankan lewat edit biasa.
    $terlarang = $this->penambahanPeranTerlarang($peranTambahan, $peranLama);
    if ($terlarang) {
      return response()->json([
        'message' => 'Akses ditolak. Hanya Admin yang dapat memberikan peran tambahan: ' . implode(', ', $terlarang) . '.'
      ], 403);
    }

    // Foto & hapus_photo ditangani manual di bawah, bukan mass-update.
    $fileFoto = $request->file('photo');
    $hapusFoto = $request->boolean('hapus_photo');
    unset($validated['photo'], $validated['hapus_photo']);

    $ptk->update($validated);

    // Ganti foto: berkas lama dihapus, berkas baru disimpan ke folder
    // yang sama dengan unggahan profil (ptk_photos di disk public).
    // Tanpa berkas baru + centang hapus_photo: foto dikosongkan.
    if ($fileFoto || $hapusFoto) {
      if ($ptk->photo && Storage::disk('public')->exists($ptk->photo)) {
        Storage::disk('public')->delete($ptk->photo);
      }
      $ptk->photo = $fileFoto ? KompresGambar::simpan($fileFoto, 'ptk_photos', 1200) : null;
      $ptk->save();
    }

    // 2. Sinkronisasi Role jika PTK sudah punya akun dan jabatan
    // atau peran tambahannya berubah. Yang diganti hanya peran yang
    // dikelola lewat data PTK (Ptk::PERAN_TERKELOLA); role di luar
    // daftar itu — mis. Admin yang dipasang manual — dipertahankan.
    $peranBerubah = $jabatanLama !== $validated['jabatan']
      || array_values($peranLama) !== $peranTambahan;

    if ($ptk->user_id && $peranBerubah) {
      $user = \App\Models\User::find($ptk->user_id);

      if ($user) {
        // Pastikan semua Role tersedia di database, buat bila belum ada
        foreach ($ptk->semuaPeran() as $namaPeran) {
          \Spatie\Permission\Models\Role::firstOrCreate(['name' => $namaPeran, 'guard_name' => 'web']);
        }

        $peranTakTerkelola = $user->getRoleNames()
          ->reject(fn($nama) => in_array($nama, Ptk::PERAN_TERKELOLA, true))
          ->values()
          ->all();

        $user->syncRoles(array_merge($peranTakTerkelola, $ptk->semuaPeran()));
      }
    }

    // Kembalikan JSON agar AJAX bisa menangkapnya
    return response()->json([
      'success' => true,
      'message' => 'Data PTK berhasil diperbarui.',
      'data'    => $ptk
    ]);
  }

  /**
   * Alirkan berkas foto PTK untuk tampilan (avatar navbar, profil,
   * pratinjau form). SENGAJA lewat route, bukan asset('storage/...'):
   * symlink public/storage tidak selalu ada (di hosting bersama memang
   * tidak ada) sehingga foto tampil sebagai gambar pecah. Pola yang sama
   * seperti SekolahLogoController::lihat dan foto-foto Web Sekolah.
   */
  public function foto(Ptk $ptk)
  {
    abort_if(!$ptk->photo || !Storage::disk('public')->exists($ptk->photo), 404);

    return Storage::disk('public')->response($ptk->photo);
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

        // Pasang SEMUA peran PTK: jabatan utama + peran tambahan
        $roles = [];
        foreach ($ptk->semuaPeran() as $namaPeran) {
          $roles[] = Role::firstOrCreate(['name' => $namaPeran, 'guard_name' => 'web']);
        }

        if ($roles) {
          $user->assignRole($roles);
        }

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
   * Bersihkan daftar peran tambahan dari request: hanya role
   * terkelola, unik, dan tidak mengulang jabatan utama.
   */
  private function bersihkanPeranTambahan($input, ?string $jabatan): array
  {
    if (!is_array($input)) {
      return [];
    }

    $peran = array_filter(array_map(fn($nama) => trim((string) $nama), $input), function ($nama) use ($jabatan) {
      return $nama !== '' && $nama !== $jabatan && in_array($nama, Ptk::PERAN_TERKELOLA, true);
    });

    return array_values(array_unique($peran));
  }

  /**
   * Apakah peran terbatas (maksimal 1 orang per sekolah) sudah terisi
   * PTK LAIN di sekolah ini — dihitung gabungan dari jabatan utama,
   * peran tambahan, dan role Spatie pada akun user mereka, agar tidak
   * ada pemegang "bayangan" lewat salah satu jalur.
   */
  private function peranTerbatasTerisi(int $sekolahId, string $peran, ?int $kecualiPtkId = null): bool
  {
    $daftarPtk = Ptk::where('sekolah_id', $sekolahId)
      ->when($kecualiPtkId, fn($q) => $q->where('id', '!=', $kecualiPtkId))
      ->with('user.roles')
      ->get();

    foreach ($daftarPtk as $ptkLain) {
      if ($ptkLain->jabatan === $peran) {
        return true;
      }

      if (in_array($peran, $ptkLain->peran_tambahan ?? [], true)) {
        return true;
      }

      if ($ptkLain->user && $ptkLain->user->roles->contains('name', $peran)) {
        return true;
      }
    }

    return false;
  }

  /**
   * Peran tambahan terlarang yang BARU ditambahkan oleh aktor
   * non-Admin. Admin boleh memberikan semua peran; selain Admin,
   * penambahan peran Operator Sekolah, Kepala Sekolah, dan Admin
   * ditolak (mencegah eskalasi hak akses). Peran yang sudah terpasang
   * sebelumnya tidak dihitung, agar edit biasa pada PTK yang sudah
   * memilikinya tidak ikut terkunci.
   */
  private function penambahanPeranTerlarang(array $peranBaru, array $peranLama): array
  {
    if (Auth::user()->hasRole('Admin')) {
      return [];
    }

    $terlarang = ['Admin', 'Kepala Sekolah', 'Operator Sekolah'];

    return array_values(array_intersect(array_diff($peranBaru, $peranLama), $terlarang));
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

  /**
   * Daftar riwayat kepangkatan seorang PTK, terbaru dulu.
   * Ptk::findOrFail tunduk pada SekolahScope, jadi PTK sekolah lain
   * tidak bisa diakses operator sekolah ini.
   */
  public function daftarPangkat($id)
  {
    $ptk = Ptk::findOrFail($id);

    return response()->json([
      'data' => $ptk->riwayatPangkat()->get(),
    ]);
  }

  /** Tambah satu entri riwayat kepangkatan untuk PTK ini. */
  public function simpanPangkat(Request $request, $id)
  {
    $ptk = Ptk::findOrFail($id);

    $validated = $request->validate([
      'pangkat'    => 'required|string|max:100',
      'golongan'   => 'required|string|max:10',
      'tmt'        => 'required|date',
      'nomor_sk'   => 'nullable|string|max:100',
      'tanggal_sk' => 'nullable|date',
    ]);

    $riwayat = $ptk->riwayatPangkat()->create([
      'pangkat'    => $validated['pangkat'],
      'golongan'   => $validated['golongan'],
      'tmt'        => $validated['tmt'],
      'nomor_sk'   => $validated['nomor_sk'] ?? null,
      'tanggal_sk' => $validated['tanggal_sk'] ?? null,
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Riwayat kepangkatan berhasil ditambahkan.',
      'data'    => $riwayat,
    ]);
  }

  /** Hapus satu entri riwayat kepangkatan milik PTK ini. */
  public function hapusPangkat($id, $riwayatId)
  {
    $ptk = Ptk::findOrFail($id);

    $riwayat = $ptk->riwayatPangkat()->findOrFail($riwayatId);
    $riwayat->delete();

    return response()->json([
      'message' => 'Riwayat kepangkatan berhasil dihapus.',
    ]);
  }
}
