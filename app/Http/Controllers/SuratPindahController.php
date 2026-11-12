<?php

namespace App\Http\Controllers;

use App\Models\Ptk;
use App\Models\Sekolah;
use App\Models\SekolahLogo;
use App\Models\SiswaRegistrasi;
use App\Models\SuratKategori;
use App\Models\SuratKeluar;
use App\Support\NomorSurat;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Surat Keterangan Pindah Sekolah untuk siswa berstatus
 * mutasi — anak modul Surat Keluar (Fase A integrasi).
 *
 * Nomornya diambil dari penghitung resmi Surat Keluar yang
 * sama (kategori 420 Pendidikan), baris agendanya tertulis di
 * tabel_surat_keluar dengan sumber 'surat-pindah' dan
 * sumber_id = id registrasi mutasi, dan seluruh isi cetaknya
 * dibekukan sebagai snapshot (data_cetak) supaya cetak ulang
 * selalu identik dengan saat terbit — kaidah yang sama dengan
 * snapshot identitas SKHU/SKL. Diterbitkan langsung oleh
 * petugas (Operator Sekolah / Tata Usaha), tanpa persetujuan.
 * Satu surat aktif per mutasi: koreksi lewat pembatalan di
 * agenda, lalu terbit ulang dari sini.
 */
class SuratPindahController extends Controller
{
  /** Konteks sekolah aktif — pola sama dengan SuratKeluarController. */
  private function sekolahKonteksIdNullable(): ?int
  {
    $user = Auth::user();

    if ($user->hasRole('Admin')) {
      if (request()->filled('sekolah_id')) {
        session(['admin_sekolah_id' => request()->integer('sekolah_id')]);
      }

      $sekolahId = session('admin_sekolah_id') ?? $user->ptk?->sekolah_id;

      if (!$sekolahId && Sekolah::count() === 1) {
        $sekolahId = Sekolah::value('id');
      }

      return $sekolahId ? (int) $sekolahId : null;
    }

    return $user->ptk?->sekolah_id ? (int) $user->ptk->sekolah_id : null;
  }

  /** Registrasi harus mutasi dan berada di sekolah konteks. */
  private function pastikanRegistrasi(SiswaRegistrasi $registrasi): void
  {
    abort_unless($registrasi->status === 'mutasi', 404);
    abort_unless((int) $registrasi->sekolah_id === (int) $this->sekolahKonteksIdNullable(), 404);
  }

  /** Surat pindah yang masih aktif (terbit) untuk registrasi ini. */
  private function suratAktif(SiswaRegistrasi $registrasi): ?SuratKeluar
  {
    return SuratKeluar::where('sumber', 'surat-pindah')
      ->where('sumber_id', $registrasi->id)
      ->where('status', 'terbit')
      ->latest('id')
      ->first();
  }

  /** Kategori surat pindah: 420 Pendidikan (bawaan pola klasifikasi). */
  private function kategoriPindah(int $sekolahId): ?SuratKategori
  {
    return SuratKategori::where('sekolah_id', $sekolahId)->where('kode', '420')->first()
      ?? SuratKategori::where('sekolah_id', $sekolahId)->orderBy('urutan')->first();
  }

  /**
   * Data dasar surat dari registrasi mutasi: identitas siswa,
   * kelas terakhir, pilihan orang tua/wali pemohon, identitas
   * sekolah, dan Kepala Sekolah penandatangan.
   */
  private function dataDasar(SiswaRegistrasi $registrasi): array
  {
    $siswa = $registrasi->siswa;
    $sekolah = Sekolah::with('detailTerbaru')->find($registrasi->sekolah_id);
    $detail = $sekolah?->detailTerbaru;

    $anggota = $siswa->rombelAnggota()
      ->whereHas('rombel', fn($q) => $q->where('sekolah_id', $registrasi->sekolah_id))
      ->with('rombel.semester')
      ->latest('id')
      ->first();

    $tingkat = (int) ($anggota?->rombel?->tingkat ?? $registrasi->tingkat ?? 0);

    $pemohon = [];
    foreach (['ayah' => 'Ayah', 'ibu' => 'Ibu', 'wali' => 'Wali'] as $kunci => $label) {
      $nama = $siswa->{'nama_' . $kunci};
      if ($nama) {
        $pemohon[$kunci] = [
          'relasi' => $label,
          'nama' => $nama,
          'pekerjaan' => $siswa->{'pekerjaan_' . $kunci},
          'alamat' => $siswa->alamat,
        ];
      }
    }

    $kepsek = Ptk::where('sekolah_id', $registrasi->sekolah_id)
      ->where('jabatan', 'Kepala Sekolah')
      ->first();

    return [
      'siswa' => $siswa,
      'tingkat' => $tingkat,
      'rombel' => $anggota?->rombel?->nama_rombel,
      'tahun_pelajaran' => $anggota?->rombel?->semester?->tahun_pelajaran,
      'pemohon' => $pemohon,
      'sekolah' => [
        'nama' => $detail?->nama_sekolah,
        'npsn' => $sekolah?->npsn,
        'kecamatan' => $detail?->district?->name,
        'kabupaten' => $detail?->regency?->name,
        'provinsi' => $detail?->province?->name,
        'desa' => $detail?->village?->name,
        'alamat' => $detail?->alamat_sekolah,
      ],
      'kepsek' => [
        'nama' => $kepsek?->nama_lengkap ?? $detail?->kepala_sekolah,
        'nip' => $kepsek?->nip ?? $detail?->nip,
        'ptk_id' => $kepsek?->id,
      ],
    ];
  }

  /** Form penerbitan: pratinjau data + isian yang boleh disunting. */
  public function create(SiswaRegistrasi $registrasi)
  {
    $this->pastikanRegistrasi($registrasi);

    if ($aktif = $this->suratAktif($registrasi)) {
      return redirect()
        ->route('siswa.keluar', ['sekolah_id' => $registrasi->sekolah_id, 'status' => 'mutasi'])
        ->with('error', 'Siswa ini sudah punya Surat Pindah aktif bernomor '
          . $aktif->nomor_surat . '. Batalkan dulu dari agenda Surat Keluar bila perlu menerbitkan ulang.');
    }

    $dasar = $this->dataDasar($registrasi);
    NomorSurat::pastikanKategoriBawaan((int) $registrasi->sekolah_id);
    $kategori = $this->kategoriPindah((int) $registrasi->sekolah_id);

    return view('content.surat-pindah.form', [
      'registrasi' => $registrasi,
      'contohNomor' => NomorSurat::contohBerikutnya((int) $registrasi->sekolah_id, $kategori, now()),
    ] + $dasar);
  }

  /** Terbitkan surat: ambil nomor resmi, bekukan snapshot, buka PDF. */
  public function store(Request $request, SiswaRegistrasi $registrasi)
  {
    $this->pastikanRegistrasi($registrasi);

    if ($this->suratAktif($registrasi)) {
      return back()->with('error', 'Siswa ini sudah punya Surat Pindah aktif. '
        . 'Batalkan dulu dari agenda Surat Keluar bila perlu menerbitkan ulang.');
    }

    $data = $request->validate([
      'tanggal_surat' => 'required|date',
      'pemohon' => 'required|in:ayah,ibu,wali',
      'nama_pemohon' => 'required|string|max:100',
      'pekerjaan_pemohon' => 'nullable|string|max:100',
      'alamat_pemohon' => 'nullable|string|max:250',
      'tujuan_teks' => 'required|string|max:200',
      'alasan' => 'nullable|string|max:250',
      'catatan' => 'nullable|string|max:500',
    ]);

    $dasar = $this->dataDasar($registrasi);
    $kategori = $this->kategoriPindah((int) $registrasi->sekolah_id);
    $siswa = $dasar['siswa'];

    $surat = DB::transaction(function () use ($data, $dasar, $kategori, $registrasi, $siswa) {
      $surat = SuratKeluar::create([
        'sekolah_id' => $registrasi->sekolah_id,
        'kategori_id' => $kategori?->id,
        'tanggal_surat' => $data['tanggal_surat'],
        'tujuan' => $data['tujuan_teks'],
        'perihal' => 'Surat Keterangan Pindah Sekolah a.n. ' . $siswa->nama_siswa,
        'isi_ringkas' => 'Pindah ke ' . $data['tujuan_teks']
          . ($data['alasan'] ? ' — ' . $data['alasan'] : '')
          . '. Kelas terakhir ' . $dasar['tingkat']
          . ($dasar['tahun_pelajaran'] ? ', TP ' . $dasar['tahun_pelajaran'] : '') . '.',
        'penandatangan_ptk_id' => $dasar['kepsek']['ptk_id'],
        'sifat' => 'Biasa',
        'status' => 'draft',
        'dibuat_oleh' => Auth::id(),
        'sumber' => 'surat-pindah',
        'sumber_id' => $registrasi->id,
      ]);

      $surat = NomorSurat::terbitkan($surat);

      // Isi cetak dibekukan SETELAH nomor resmi ditetapkan.
      $surat->data_cetak = $this->snapshot($data, $dasar, $registrasi, $surat);
      $surat->save();

      return $surat;
    });

    return redirect()->route('surat-keluar.pindah.cetak', $surat);
  }

  /** Snapshot isi surat persis seperti yang tercetak. */
  private function snapshot(array $data, array $dasar, SiswaRegistrasi $registrasi, SuratKeluar $surat): array
  {
    $siswa = $dasar['siswa'];
    $relasi = ['ayah' => 'Ayah', 'ibu' => 'Ibu', 'wali' => 'Wali'][$data['pemohon']] ?? 'Wali';

    return [
      'nomor' => $surat->nomor_surat,
      'tanggal' => $surat->tanggal_surat?->format('Y-m-d'),
      'sekolah' => $dasar['sekolah'],
      'siswa' => [
        'nama' => $siswa->nama_siswa,
        'tempat_lahir' => $siswa->tempat_lahir,
        'tanggal_lahir' => $siswa->tgl_lahir?->format('Y-m-d'),
        'nis' => $registrasi->nis,
        'nisn' => $siswa->nisn,
        'jenis_kelamin' => $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan',
        'kelas_romawi' => $this->romawi($dasar['tingkat']),
        'kelas_kata' => $this->kata($dasar['tingkat']),
        'rombel' => $dasar['rombel'],
        'tahun_pelajaran' => $dasar['tahun_pelajaran'],
      ],
      'pemohon' => [
        'relasi' => $relasi,
        'nama' => $data['nama_pemohon'],
        'pekerjaan' => $data['pekerjaan_pemohon'],
        'alamat' => $data['alamat_pemohon'],
      ],
      'tujuan_teks' => $data['tujuan_teks'],
      'alasan' => $data['alasan'],
      'tanggal_keluar' => $registrasi->tanggal_keluar,
      'catatan' => $data['catatan'],
      'kepsek' => $dasar['kepsek'],
    ];
  }

  /** Cetak / cetak ulang PDF dari snapshot (2 halaman: keterangan + permohonan). */
  public function cetak(SuratKeluar $surat)
  {
    abort_unless($surat->sumber === 'surat-pindah', 404);
    abort_unless((int) $surat->sekolah_id === (int) $this->sekolahKonteksIdNullable(), 404);

    $logos = SekolahLogo::where('sekolah_id', $surat->sekolah_id)
      ->whereIn('tipe', [
        SekolahLogo::TIPE_KOP_POTRAIT,
        SekolahLogo::TIPE_TTD_KS,
        SekolahLogo::TIPE_STEMPEL,
      ])
      ->get()
      ->keyBy('tipe');

    $ambilPath = function (string $tipe) use ($logos): ?string {
      $logo = $logos->get($tipe);
      return $logo && file_exists($logo->absolute_path) ? $logo->absolute_path : null;
    };

    $pdf = Pdf::loadView('content.surat-pindah.cetak', [
      'surat' => $surat,
      'd' => $surat->data_cetak ?? [],
      'kopPath' => $ambilPath(SekolahLogo::TIPE_KOP_POTRAIT),
      'ttdPath' => $ambilPath(SekolahLogo::TIPE_TTD_KS),
      'stempelPath' => $ambilPath(SekolahLogo::TIPE_STEMPEL),
    ])->setPaper('a4', 'portrait');

    return $pdf->stream('surat-pindah-' . $surat->id . '.pdf');
  }

  /** Angka romawi tingkat kelas (1–12). */
  private function romawi(int $n): string
  {
    return ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$n - 1] ?? (string) $n;
  }

  /** Terbilang tingkat kelas (1–12), huruf kecil seperti contoh surat. */
  private function kata(int $n): string
  {
    return [
      'satu', 'dua', 'tiga', 'empat', 'lima', 'enam',
      'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas', 'dua belas',
    ][$n - 1] ?? (string) $n;
  }
}
