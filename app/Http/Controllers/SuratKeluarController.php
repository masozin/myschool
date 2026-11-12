<?php

namespace App\Http\Controllers;

use App\Models\Ptk;
use App\Models\Sekolah;
use App\Models\SuratBerkas;
use App\Models\SuratKategori;
use App\Models\SuratKeluar;
use App\Models\SuratPengaturan;
use App\Support\NomorSurat;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

/**
 * Pengelola Surat Keluar — buku agenda digital per sekolah.
 *
 * Surat diterbitkan langsung oleh petugas (Operator Sekolah /
 * Tata Usaha) tanpa alur persetujuan: simpan sebagai draft
 * (belum bernomor resmi) atau langsung terbit (nomor resmi
 * ditetapkan App\Support\NomorSurat dan dibekukan). Kepala
 * Sekolah melihat semua surat sekolahnya, Admin memantau per
 * sekolah terpilih; keduanya tidak mengubah data.
 */
class SuratKeluarController extends Controller
{
  /** Konteks sekolah aktif (pola yang sama dengan modul Jadwal/Kartu). */
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

  /** Peran yang boleh membuat & mengubah data surat. */
  private function bolehKelola(): bool
  {
    return Auth::user()->hasAnyRole(['Operator Sekolah', 'Tata Usaha']);
  }

  /** Surat harus berada di sekolah konteks; di luar itu 404. */
  private function pastikanKonteks(SuratKeluar $surat): void
  {
    abort_unless((int) $surat->sekolah_id === (int) $this->sekolahKonteksIdNullable(), 404);
  }

  /** Query daftar dengan saringan tahun/status/kategori dari request. */
  private function queryDaftar(Request $request, int $sekolahId)
  {
    return SuratKeluar::with(['kategori', 'penandatangan', 'berkas'])
      ->where('sekolah_id', $sekolahId)
      ->when($request->filled('tahun'), fn($q) => $q->whereYear('tanggal_surat', $request->integer('tahun')))
      ->when($request->filled('status'), fn($q) => $q->where('status', $request->input('status')))
      ->when($request->filled('kategori_id'), fn($q) => $q->where('kategori_id', $request->integer('kategori_id')))
      ->orderByDesc('tanggal_surat')
      ->orderByDesc('id');
  }

  public function index(Request $request)
  {
    $user = Auth::user();
    $sekolahId = $this->sekolahKonteksIdNullable();
    $daftarSekolah = $user->hasRole('Admin')
      ? Sekolah::with('detailTerbaru')->orderBy('id')->get()
      : collect();

    if (!$sekolahId) {
      return view('content.surat-keluar.index', [
        'sekolahId' => null,
        'daftarSekolah' => $daftarSekolah,
        'sekolah' => null,
        'surats' => collect(),
        'kategoris' => collect(),
        'daftarTahun' => [],
        'bolehKelola' => $this->bolehKelola(),
        'statistik' => ['total' => 0, 'terbit' => 0, 'draft' => 0, 'batal' => 0],
      ]);
    }

    NomorSurat::pastikanKategoriBawaan($sekolahId);

    $sekolah = Sekolah::with('detailTerbaru')->find($sekolahId);
    $kategoris = SuratKategori::where('sekolah_id', $sekolahId)->orderBy('urutan')->orderBy('nama')->get();
    $surats = $this->queryDaftar($request, $sekolahId)->get();

    $daftarTahun = SuratKeluar::where('sekolah_id', $sekolahId)
      ->selectRaw('YEAR(tanggal_surat) as tahun')
      ->distinct()
      ->orderByDesc('tahun')
      ->pluck('tahun')
      ->map(fn($t) => (int) $t)
      ->all();
    $tahunIni = (int) now()->format('Y');
    if (!in_array($tahunIni, $daftarTahun, true)) {
      array_unshift($daftarTahun, $tahunIni);
    }

    $statistik = [
      'total' => SuratKeluar::where('sekolah_id', $sekolahId)->whereYear('tanggal_surat', $tahunIni)->count(),
      'terbit' => SuratKeluar::where('sekolah_id', $sekolahId)->where('status', 'terbit')->count(),
      'draft' => SuratKeluar::where('sekolah_id', $sekolahId)->where('status', 'draft')->count(),
      'batal' => SuratKeluar::where('sekolah_id', $sekolahId)->where('status', 'batal')->count(),
    ];

    return view('content.surat-keluar.index', compact(
      'sekolahId',
      'daftarSekolah',
      'sekolah',
      'surats',
      'kategoris',
      'daftarTahun',
      'statistik'
    ) + ['bolehKelola' => $this->bolehKelola()]);
  }

  /** Data bersama formulir buat/ubah. */
  private function dataFormulir(int $sekolahId): array
  {
    NomorSurat::pastikanKategoriBawaan($sekolahId);

    $kategoris = SuratKategori::where('sekolah_id', $sekolahId)->orderBy('urutan')->orderBy('nama')->get();
    $ptks = Ptk::where('sekolah_id', $sekolahId)
      ->where('status', 'aktif')
      ->orderBy('nama_ptk')
      ->get();
    $kepsek = $ptks->firstWhere('jabatan', 'Kepala Sekolah');

    return [
      'kategoris' => $kategoris,
      'ptks' => $ptks,
      'kepsekId' => $kepsek?->id,
      'contohNomor' => NomorSurat::contohBerikutnya($sekolahId),
    ];
  }

  public function create()
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    abort_unless($sekolahId, 403);

    return view('content.surat-keluar.form', [
      'surat' => null,
      'sekolahId' => $sekolahId,
      'bolehKelola' => true,
    ] + $this->dataFormulir($sekolahId));
  }

  /** Validasi isian surat; $surat null = pembuatan baru. */
  private function validasiSurat(Request $request, int $sekolahId, ?SuratKeluar $surat): array
  {
    $data = $request->validate([
      'tanggal_surat' => 'required|date',
      'tujuan' => 'required|string|max:200',
      'perihal' => 'required|string|max:250',
      'kategori_id' => 'nullable|integer',
      'isi_ringkas' => 'nullable|string',
      'lampiran' => 'nullable|string|max:50',
      'penandatangan_ptk_id' => 'nullable|integer',
      'sifat' => 'required|in:Biasa,Penting,Segera,Rahasia',
      'mode_nomor' => 'required|in:otomatis,manual',
      'nomor_surat' => 'nullable|string|max:150',
      'catatan' => 'nullable|string',
    ]);

    if ($data['mode_nomor'] === 'manual' && trim((string) ($data['nomor_surat'] ?? '')) === '') {
      throw ValidationException::withMessages([
        'nomor_surat' => 'Nomor surat wajib diisi pada mode manual.',
      ]);
    }

    // Kategori & penandatangan harus milik sekolah yang sama.
    if (!empty($data['kategori_id'])) {
      abort_unless(
        SuratKategori::where('sekolah_id', $sekolahId)->where('id', $data['kategori_id'])->exists(),
        422
      );
    }
    if (!empty($data['penandatangan_ptk_id'])) {
      abort_unless(
        Ptk::where('sekolah_id', $sekolahId)->where('id', $data['penandatangan_ptk_id'])->exists(),
        422
      );
    }

    return $data;
  }

  public function store(Request $request)
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    abort_unless($sekolahId, 403);

    $data = $this->validasiSurat($request, $sekolahId, null);
    $manual = $data['mode_nomor'] === 'manual';

    $surat = SuratKeluar::create([
      'sekolah_id' => $sekolahId,
      'kategori_id' => $data['kategori_id'] ?? null,
      'nomor_surat' => $manual ? trim($data['nomor_surat']) : null,
      'nomor_manual' => $manual,
      'tanggal_surat' => $data['tanggal_surat'],
      'tujuan' => $data['tujuan'],
      'perihal' => $data['perihal'],
      'isi_ringkas' => $data['isi_ringkas'] ?? null,
      'lampiran' => $data['lampiran'] ?? null,
      'penandatangan_ptk_id' => $data['penandatangan_ptk_id'] ?? null,
      'sifat' => $data['sifat'],
      'status' => 'draft',
      'dibuat_oleh' => Auth::id(),
      'catatan' => $data['catatan'] ?? null,
    ]);

    if ($request->input('aksi') === 'terbit') {
      NomorSurat::terbitkan($surat);
      $pesan = 'Surat diterbitkan dengan nomor ' . $surat->nomor_surat . '.';
    } else {
      $pesan = 'Draft surat tersimpan. Nomor resmi ditetapkan saat surat diterbitkan.';
    }

    if ($request->hasFile('berkas_arsip')) {
      $this->simpanBerkas($request, $surat, 'berkas_arsip', 'arsip');
    }

    return redirect()->route('surat-keluar.edit', $surat)->with('success', $pesan);
  }

  public function edit(SuratKeluar $surat)
  {
    $this->pastikanKonteks($surat);
    $surat->load(['berkas.pengunggah', 'kategori', 'penandatangan']);

    return view('content.surat-keluar.form', [
      'surat' => $surat,
      'sekolahId' => (int) $surat->sekolah_id,
      'bolehKelola' => $this->bolehKelola(),
    ] + $this->dataFormulir((int) $surat->sekolah_id));
  }

  public function update(Request $request, SuratKeluar $surat)
  {
    $this->pastikanKonteks($surat);

    if ($surat->status === 'batal') {
      return back()->with('error', 'Surat yang sudah dibatalkan tidak dapat diubah.');
    }

    $data = $this->validasiSurat($request, (int) $surat->sekolah_id, $surat);
    $manual = $data['mode_nomor'] === 'manual';

    $surat->fill([
      'kategori_id' => $data['kategori_id'] ?? null,
      'tanggal_surat' => $data['tanggal_surat'],
      'tujuan' => $data['tujuan'],
      'perihal' => $data['perihal'],
      'isi_ringkas' => $data['isi_ringkas'] ?? null,
      'lampiran' => $data['lampiran'] ?? null,
      'penandatangan_ptk_id' => $data['penandatangan_ptk_id'] ?? null,
      'sifat' => $data['sifat'],
      'catatan' => $data['catatan'] ?? null,
    ]);

    // Nomor yang sudah terbit dari penghitung resmi DIBEKUKAN.
    // Mode manual tetap boleh dikoreksi petugas (nomornya
    // memang tulisan petugas, bukan hasil penghitung).
    if ($surat->status === 'draft') {
      $surat->nomor_manual = $manual;
      $surat->nomor_surat = $manual ? trim((string) $data['nomor_surat']) : null;
    } elseif ($surat->nomor_manual) {
      $surat->nomor_surat = trim((string) $data['nomor_surat']);
    }

    $surat->save();

    return back()->with('success', 'Perubahan surat tersimpan.');
  }

  public function terbitkan(SuratKeluar $surat)
  {
    $this->pastikanKonteks($surat);

    if ($surat->status !== 'draft') {
      return back()->with('error', 'Hanya draft yang dapat diterbitkan.');
    }
    if ($surat->nomor_manual && trim((string) $surat->nomor_surat) === '') {
      return back()->with('error', 'Nomor manual masih kosong — lengkapi dulu lewat Ubah.');
    }

    NomorSurat::terbitkan($surat);

    return back()->with('success', 'Surat diterbitkan dengan nomor ' . $surat->nomor_surat . '.');
  }

  public function batalkan(Request $request, SuratKeluar $surat)
  {
    $this->pastikanKonteks($surat);

    if ($surat->status !== 'terbit') {
      return back()->with('error', 'Hanya surat terbit yang dapat dibatalkan.');
    }

    $data = $request->validate([
      'alasan_batal' => 'required|string|max:500',
    ]);

    // Nomor tetap tercatat di agenda sebagai batal — tidak dipakai ulang.
    $surat->status = 'batal';
    $surat->catatan = trim(($surat->catatan ? $surat->catatan . "\n" : '')
      . '[Dibatalkan ' . now()->format('d-m-Y H:i') . '] ' . $data['alasan_batal']);
    $surat->save();

    return back()->with('success', 'Surat dibatalkan. Nomornya tetap tercatat di agenda dan tidak dipakai ulang.');
  }

  public function hapus(SuratKeluar $surat)
  {
    $this->pastikanKonteks($surat);

    if ($surat->status !== 'draft') {
      return back()->with('error', 'Surat yang sudah terbit/batal tidak dapat dihapus — agenda harus utuh. Gunakan Batalkan untuk surat terbit.');
    }

    foreach ($surat->berkas as $berkas) {
      Storage::disk('local')->delete($berkas->path);
    }
    $surat->berkas()->delete();
    $surat->delete();

    return redirect()->route('surat-keluar.index')->with('success', 'Draft surat dihapus.');
  }

  // ── Berkas arsip & lampiran (disk privat, dibuka lewat route) ──

  private function simpanBerkas(Request $request, SuratKeluar $surat, string $field, string $jenis): SuratBerkas
  {
    $file = $request->file($field);
    $nama = Str::uuid()->toString() . '.' . strtolower($file->getClientOriginalExtension());
    $path = $file->storeAs('surat-keluar/' . $surat->sekolah_id . '/' . $surat->id, $nama, 'local');

    return SuratBerkas::create([
      'surat_id' => $surat->id,
      'jenis' => $jenis,
      'nama_asli' => $file->getClientOriginalName(),
      'path' => $path,
      'mime' => $file->getClientMimeType(),
      'ukuran' => $file->getSize(),
      'diunggah_oleh' => Auth::id(),
    ]);
  }

  public function berkasUnggah(Request $request, SuratKeluar $surat)
  {
    $this->pastikanKonteks($surat);

    $data = $request->validate([
      'jenis' => 'required|in:arsip,lampiran',
      'berkas' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
    ]);

    $this->simpanBerkas($request, $surat, 'berkas', $data['jenis']);

    return back()->with('success', 'Berkas terunggah.');
  }

  public function berkasLihat(SuratBerkas $berkas)
  {
    $berkas->load('surat');
    $this->pastikanKonteks($berkas->surat);

    abort_unless(Storage::disk('local')->exists($berkas->path), 404);

    return Storage::disk('local')->response($berkas->path, $berkas->nama_asli);
  }

  public function berkasHapus(SuratBerkas $berkas)
  {
    $berkas->load('surat');
    $this->pastikanKonteks($berkas->surat);

    Storage::disk('local')->delete($berkas->path);
    $berkas->delete();

    return back()->with('success', 'Berkas dihapus.');
  }

  // ── Ekspor buku agenda ────────────────────────────────────────

  public function eksporExcel(Request $request)
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    abort_unless($sekolahId, 403);

    $sekolah = Sekolah::with('detailTerbaru')->find($sekolahId);
    $namaSekolah = $sekolah?->detailTerbaru?->nama_sekolah ?? 'Sekolah';
    $surats = $this->queryDaftar($request, $sekolahId)->get();

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Agenda Surat Keluar');
    $sheet->fromArray([
      ['BUKU AGENDA SURAT KELUAR'],
      [$namaSekolah],
      [],
      ['No', 'Nomor Surat', 'Tanggal', 'Tujuan', 'Perihal', 'Kategori', 'Sifat', 'Penandatangan', 'Status', 'Lampiran'],
    ], null, 'A1');

    $baris = 5;
    foreach ($surats as $i => $s) {
      $sheet->fromArray([[
        $i + 1,
        $s->nomor_surat ?? '(draft)',
        $s->tanggal_surat?->format('d-m-Y'),
        $s->tujuan,
        $s->perihal,
        $s->kategori?->nama ?? '-',
        $s->sifat,
        $s->penandatangan?->nama_lengkap ?? '-',
        $s->labelStatus(),
        $s->lampiran ?? '-',
      ]], null, 'A' . $baris);
      $baris++;
    }

    $sheet->getStyle('A4:J4')->getFont()->setBold(true);
    foreach (range('A', 'J') as $kolom) {
      $sheet->getColumnDimension($kolom)->setAutoSize(true);
    }
    $sheet->getColumnDimension('E')->setAutoSize(false)->setWidth(60);

    $namaFile = 'agenda-surat-keluar-' . now()->format('Ymd-His') . '.xlsx';
    $writer = new XlsxWriter($spreadsheet);

    return response()->streamDownload(function () use ($writer) {
      if (ob_get_contents()) {
        ob_end_clean();
      }
      $writer->save('php://output');
    }, $namaFile, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
  }

  public function cetakAgenda(Request $request)
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    abort_unless($sekolahId, 403);

    $sekolah = Sekolah::with('detailTerbaru')->find($sekolahId);
    $surats = $this->queryDaftar($request, $sekolahId)
      ->orderBy('tanggal_surat')
      ->orderBy('id')
      ->get();

    return Pdf::loadView('content.surat-keluar.agenda-pdf', [
      'sekolah' => $sekolah,
      'surats' => $surats,
      'tahun' => $request->integer('tahun') ?: null,
    ])->setPaper('a4', 'landscape')
      ->download('agenda-surat-keluar-' . now()->format('Ymd') . '.pdf');
  }

  // ── Pengaturan format nomor & kategori (petugas) ─────────────

  public function pengaturan()
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    abort_unless($sekolahId, 403);

    NomorSurat::pastikanKategoriBawaan($sekolahId);
    $pengaturan = SuratPengaturan::untukSekolah($sekolahId);
    $kategoris = SuratKategori::where('sekolah_id', $sekolahId)
      ->withCount('surat')
      ->orderBy('urutan')
      ->orderBy('nama')
      ->get();

    return view('content.surat-keluar.pengaturan', [
      'pengaturan' => $pengaturan,
      'kategoris' => $kategoris,
      'contohNomor' => NomorSurat::contohBerikutnya($sekolahId, $kategoris->first()),
      'sekolahId' => $sekolahId,
    ]);
  }

  public function simpanPengaturan(Request $request)
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    abort_unless($sekolahId, 403);

    $data = $request->validate([
      'format_nomor' => ['required', 'string', 'max:150',
        function ($attribute, $value, $fail) {
          if (!str_contains($value, '{no')) {
            $fail('Format nomor wajib memuat token {no} atau {no:3}.');
          }
        }],
    ]);

    $pengaturan = SuratPengaturan::untukSekolah($sekolahId);
    $pengaturan->format_nomor = trim($data['format_nomor']);
    $pengaturan->save();

    return back()->with('success', 'Format nomor tersimpan. Nomor yang sudah terbit tidak berubah.');
  }

  public function kategoriSimpan(Request $request)
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    abort_unless($sekolahId, 403);

    $data = $request->validate([
      'kategori_id' => 'nullable|integer',
      'kode' => 'required|string|max:20',
      'nama' => 'required|string|max:100',
      'urutan' => 'nullable|integer|min:0',
    ]);

    $duplikat = SuratKategori::where('sekolah_id', $sekolahId)
      ->where('kode', $data['kode'])
      ->when(!empty($data['kategori_id']), fn($q) => $q->where('id', '!=', $data['kategori_id']))
      ->exists();
    if ($duplikat) {
      throw ValidationException::withMessages(['kode' => 'Kode kategori ini sudah dipakai.']);
    }

    if (!empty($data['kategori_id'])) {
      $kategori = SuratKategori::where('sekolah_id', $sekolahId)->findOrFail($data['kategori_id']);
      $kategori->update(['kode' => $data['kode'], 'nama' => $data['nama'], 'urutan' => $data['urutan'] ?? 0]);
      $pesan = 'Kategori diperbarui.';
    } else {
      SuratKategori::create([
        'sekolah_id' => $sekolahId,
        'kode' => $data['kode'],
        'nama' => $data['nama'],
        'urutan' => $data['urutan'] ?? 0,
      ]);
      $pesan = 'Kategori ditambahkan.';
    }

    return back()->with('success', $pesan);
  }

  public function kategoriHapus(SuratKategori $kategori)
  {
    abort_unless((int) $kategori->sekolah_id === (int) $this->sekolahKonteksIdNullable(), 404);

    // Surat lama tidak ikut terhapus: kategori_id menjadi null
    // (nullOnDelete), nomor suratnya sudah dibekukan sebagai teks.
    $kategori->delete();

    return back()->with('success', 'Kategori dihapus. Surat lama yang memakainya tetap tercatat tanpa kategori.');
  }
}
