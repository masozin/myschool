<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLearningModuleRequest;
use App\Jobs\GenerateLearningModuleJob;
use App\Models\LearningModule;
use App\Models\MapelMaster;
use App\Exports\LearningModuleDocxExporter;
use Illuminate\Http\Request;

class LearningModuleController extends Controller
{
  public function index()
  {
    $ptk = auth()->user()->ptk;

    if (! $ptk) {
      abort(403, 'Akun kamu belum terhubung ke data PTK/guru.');
    }

    $sekolah = $ptk->sekolah;

    $mapelList = MapelMaster::aktifUntukJenjang(strtolower($sekolah->jenjang ?? ''))
      ->orderBy('nama')
      ->get();

    return view('learning.modules.index', compact('mapelList', 'sekolah'));
  }

  public function data(Request $request)
  {
    $ptk = auth()->user()->ptk;

    $query = LearningModule::with('mapel')
      ->where('ptk_id', $ptk->id)
      ->latest();

    if ($search = $request->query('search')) {
      $query->where(function ($q) use ($search) {
        $q->where('materi_pokok', 'like', "%{$search}%")
          ->orWhere('judul_modul', 'like', "%{$search}%")
          ->orWhereHas('mapel', fn($m) => $m->where('nama', 'like', "%{$search}%"));
      });
    }

    if ($status = $request->query('status')) {
      $query->where('status', $status);
    }

    return response()->json(
      $query->get()->map(fn($m) => [
        'id'               => $m->id,
        'judul_modul'      => $m->judul_modul,
        'mapel'            => ['nama' => $m->mapel->nama ?? null],
        'fase'             => $m->fase,
        'kelas'            => $m->kelas,
        'jumlah_jp'        => $m->jumlah_jp,
        'jumlah_pertemuan' => $m->jumlah_pertemuan,
        'status'           => $m->status,
      ])
    );
  }

  public function create()
  {
    $ptk = auth()->user()->ptk;

    if (! $ptk) {
      abort(403, 'Akun kamu belum terhubung ke data PTK/guru.');
    }

    $sekolah = $ptk->sekolah;

    $mapelList = MapelMaster::aktifUntukJenjang(strtolower($sekolah->jenjang ?? ''))
      ->orderBy('nama')
      ->get();

    return view('learning.modules.create', compact('mapelList', 'ptk', 'sekolah'));
  }

  public function store(StoreLearningModuleRequest $request)
  {
    $validated = $request->validated();
    $mapel = MapelMaster::findOrFail($validated['mapel_master_id']);
    $ptk = auth()->user()->ptk;

    if (! $ptk) {
      return response()->json(['message' => 'Akun kamu belum terhubung ke data PTK/guru.'], 422);
    }

    $manualFields = ['tujuan_pembelajaran', 'dimensi_profil_lulusan', 'lintas_disiplin_ilmu', 'praktik_pedagogis', 'pemanfaatan_teknologi'];

    $manualInputs = [];
    foreach ($manualFields as $field) {
      $mode = $request->input("mode_{$field}", 'auto');
      if ($mode === 'manual' && filled($request->input("manual_{$field}"))) {
        $manualInputs[$field] = $request->input("manual_{$field}");
      }
    }

    $learningModule = LearningModule::create([
      'sekolah_id'            => $ptk->sekolah_id,
      'semester_id'           => $validated['semester_id'] ?? null,
      'ptk_id'                => $ptk->id,
      'mapel_master_id'       => $mapel->id,
      'kurikulum_id'          => $validated['kurikulum_id'] ?? null,
      'bab'                   => $validated['bab'] ?? null,
      'judul_modul'           => $validated['judul_modul'] ?? "Modul Ajar {$mapel->nama} - {$validated['materi_pokok']}",
      'fase'                  => $validated['fase'] ?? null,
      'kelas'                 => $validated['kelas'] ?? null,
      'materi_pokok'          => $validated['materi_pokok'],
      'capaian_pembelajaran'  => $validated['capaian_pembelajaran'],
      'jumlah_jp'             => $validated['jumlah_jp'],
      'jumlah_pertemuan'      => $validated['jumlah_pertemuan'],
      'model_pembelajaran'    => $validated['model_pembelajaran'] ?? null,
      'metode_pembelajaran'   => $validated['metode_pembelajaran'] ?? null,
      'target_peserta_didik'  => $validated['target_peserta_didik'] ?? null,
      'ai_provider'           => 'gemini',
      'ai_model'              => config('services.gemini.model'),
      'status'                => 'processing',
      'created_by'            => auth()->id(),

      'manual_inputs' => $manualInputs ?: null,
    ]);

    GenerateLearningModuleJob::dispatch($learningModule);

    return response()->json([
      'message'  => 'Modul ajar sedang diproses. Halaman akan update otomatis saat selesai.',
      'redirect' => route('learning-modules.show', $learningModule),
      'id'       => $learningModule->id,
    ]);
  }

  public function show(LearningModule $learningModule)
  {
    $this->authorizeAccess($learningModule);

    $learningModule->load('meetings', 'mapel', 'ptk');

    return view('learning.modules.show', compact('learningModule'));
  }

  public function retry(LearningModule $learningModule)
  {
    $this->authorizeAccess($learningModule);

    $learningModule->update(['status' => 'processing', 'failed_reason' => null]);

    GenerateLearningModuleJob::dispatch($learningModule);

    return response()->json(['message' => 'Modul ajar sedang dibuat ulang.']);
  }

  private function authorizeAccess(LearningModule $learningModule): void
  {
    if ($learningModule->ptk_id !== auth()->user()->ptk?->id) {
      abort(403);
    }
  }

  public function export(LearningModule $learningModule)
  {
    $this->authorizeAccess($learningModule);

    if ($learningModule->status !== 'generated' && $learningModule->status !== 'published') {
      return back()->withErrors(['error' => 'Modul ajar belum selesai diproses.']);
    }

    $learningModule->load('meetings', 'mapel', 'ptk', 'sekolah');

    $exporter = new LearningModuleDocxExporter($learningModule);
    $relativePath = $exporter->export();

    $learningModule->update(['file_path' => $relativePath]);

    return response()->download(storage_path('app/public/' . $relativePath))->deleteFileAfterSend(false);
  }
}
