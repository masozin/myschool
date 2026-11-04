<?php

namespace App\Http\Controllers;

use App\Models\SekolahLogo;
use App\Models\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SekolahLogoController extends Controller
{
  // Daftar tipe aset yang dikelola
  private array $tipeAset = [
    SekolahLogo::TIPE_TTD_KS        => [
      'label'      => 'Tanda Tangan Kepala Sekolah',
      'keterangan' => 'Format PNG/JPG, background transparan lebih baik',
      'icon'       => 'icon-base ri ri-quill-pen-line',
    ],
    SekolahLogo::TIPE_STEMPEL       => [
      'label'      => 'Stempel Sekolah',
      'keterangan' => 'Format PNG, background transparan',
      'icon'       => 'icon-base ri ri-shield-check-line',
    ],
    SekolahLogo::TIPE_LOGO_SEKOLAH  => [
      'label'      => 'Logo Sekolah',
      'keterangan' => 'Format PNG/JPG',
      'icon'       => 'icon-base ri ri-building-line',
    ],
    SekolahLogo::TIPE_LOGO_KABUPATEN => [
      'label'      => 'Logo Kabupaten/Kota',
      'keterangan' => 'Format PNG/JPG, dipakai di header dokumen',
      'icon'       => 'icon-base ri ri-government-line',
    ],
    SekolahLogo::TIPE_KOP_POTRAIT   => [
      'label'      => 'Kop Sekolah (Potrait)',
      'keterangan' => 'Format PNG/JPG, orientasi potrait untuk dokumen surat',
      'icon'       => 'icon-base ri ri-file-text-line',
    ],
    SekolahLogo::TIPE_KOP_LANDSCAPE => [
      'label'      => 'Kop Sekolah (Landscape)',
      'keterangan' => 'Format PNG/JPG, orientasi landscape untuk dokumen lebar',
      'icon'       => 'icon-base ri ri-layout-landscape-line',
    ],
    SekolahLogo::TIPE_KOP_KARTU     => [
      'label'      => 'Kop Sekolah (Kartu)',
      'keterangan' => 'Format PNG/JPG, ukuran kecil untuk kartu identitas',
      'icon'       => 'icon-base ri ri-bank-card-line',
    ],
  ];

  // ── Halaman utama aset sekolah ────────────────────────────────────────
  public function index()
  {
    $user      = Auth::user();
    $sekolahId = $user->ptk->sekolah_id;
    $sekolah   = Sekolah::with(['asets', 'detailTerbaru'])->findOrFail($sekolahId);

    // Map aset yang sudah ada ke array tipe
    $asets = [];
    foreach ($this->tipeAset as $tipe => $info) {
      $asetAda = $sekolah->asets->where('tipe', $tipe)->first();
      $asets[$tipe] = array_merge($info, [
        'tipe'      => $tipe,
        'aset'      => $asetAda,
        'url'       => $asetAda ? Storage::url($asetAda->path_file) : null,
      ]);
    }

    return view('content.logo.index', compact('sekolah', 'asets'));
  }

  // ── Upload / update aset ──────────────────────────────────────────────
  public function upload(Request $request)
  {
    $request->validate([
      'tipe'  => 'required|in:' . implode(',', array_keys($this->tipeAset)),
      'file'  => 'required|file|mimes:png,jpg,jpeg|max:2048', // max 2MB
    ]);

    $user      = Auth::user();
    $sekolahId = $user->ptk->sekolah_id;
    $tipe      = $request->tipe;

    // Hapus file lama jika ada
    $asetLama = SekolahLogo::where('sekolah_id', $sekolahId)
      ->where('tipe', $tipe)
      ->first();

    if ($asetLama) {
      Storage::disk('public')->delete($asetLama->path_file);
    }

    // Simpan file baru
    $path = $request->file('file')->store(
      "logo/{$sekolahId}/{$tipe}",
      'public'
    );

    // Upsert record
    SekolahLogo::updateOrCreate(
      ['sekolah_id' => $sekolahId, 'tipe' => $tipe],
      ['path_file'  => $path, 'keterangan' => $this->tipeAset[$tipe]['label']]
    );

    return response()->json([
      'message' => $this->tipeAset[$tipe]['label'] . ' berhasil diupload.',
      'url'     => Storage::url($path),
    ]);
  }

  // ── Hapus aset ────────────────────────────────────────────────────────
  public function hapus(Request $request)
  {
    $request->validate([
      'tipe' => 'required|in:' . implode(',', array_keys($this->tipeAset)),
    ]);

    $user      = Auth::user();
    $sekolahId = $user->ptk->sekolah_id;

    $aset = SekolahLogo::where('sekolah_id', $sekolahId)
      ->where('tipe', $request->tipe)
      ->firstOrFail();

    Storage::disk('public')->delete($aset->path_file);
    $aset->delete();

    return response()->json(['message' => 'Aset berhasil dihapus.']);
  }
}
