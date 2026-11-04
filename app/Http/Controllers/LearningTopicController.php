<?php

namespace App\Http\Controllers;

use App\Models\LearningSlide;
use App\Models\LearningSubject;
use App\Models\LearningTopic;
use App\Models\MapelMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LearningTopicController extends Controller
{
  public function index()
  {
    $topics = LearningTopic::with(['mapelMaster', 'slides', 'quiz'])
      ->orderBy('order', 'asc')
      ->get();

    return view('learning.topics.index', compact('topics'));
  }

  public function mapelByKelas(int $kelas)
  {
    $ptkSekolahId = auth()->user()->ptk->sekolah_id ?? null;

    $mapels = MapelMaster::with('kelompok')
      ->where('is_aktif', true)
      ->whereHas('mapelJenjang', function ($q) use ($kelas) {
        $q->where('kelas_mulai', '<=', $kelas)
          ->where('kelas_selesai', '>=', $kelas);
      })
      ->when($ptkSekolahId, function ($q) use ($ptkSekolahId) {
        $q->where(function ($sub) use ($ptkSekolahId) {
          $sub->whereNull('sekolah_id')->orWhere('sekolah_id', $ptkSekolahId);
        });
      })
      ->orderBy('nama')
      ->get(['id', 'nama', 'nama_sub_mapel', 'kode']);

    return response()->json(['success' => true, 'mapels' => $mapels]);
  }

  public function create()
  {
    return view('learning.topics.create'); // tidak perlu lagi compact('subjects')
  }


  public function storeDraft(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'mapel_master_id' => 'required|exists:tabel_mapel_master,id',
      'kelas'           => 'required|integer|min:1|max:12',
      'title'           => 'required|string|max:255',
      'description'     => 'nullable|string',
      'order'           => 'nullable|integer',
    ]);

    if ($validator->fails()) {
      return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
    }

    $ptk = auth()->user()->ptk ?? null;

    $topic = LearningTopic::create([
      'mapel_master_id' => $request->mapel_master_id,
      'kelas'           => $request->kelas,
      'title'           => $request->title,
      'description'     => $request->description,
      'order'           => $request->order ?? 1,
      'is_active'       => false,
      'sekolah_id'      => $ptk->sekolah_id ?? null,
      'ptk_id'          => $ptk->id ?? null,
    ]);

    return response()->json(['success' => true, 'message' => 'Info modul tersimpan sebagai draft.', 'topic_id' => $topic->id]);
  }

  /**
   * FASE 1 — simpan 1 slide baru ke topic yang sudah ada (draft).
   * Field dikirim dengan prefix "slide[...]" (bukan "slides[N][...]" seperti form lama).
   */
  public function storeSlide(Request $request, LearningTopic $topic)
  {
    $validator = Validator::make($request->all(), [
      'slide.title' => 'required|string|max:255',
      'slide.type' => 'required|in:intro,timeline,gambar,article,3d,html,quiz',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => $validator->errors()->first()
      ], 422);
    }

    $uploadedPaths = [];

    DB::beginTransaction();
    try {
      $nextNumber = ($topic->slides()->max('slide_number') ?? 0) + 1;

      $payload = $this->buildSlidePayload($request, 'slide', $uploadedPaths);

      $slide = LearningSlide::create(array_merge($payload, [
        'topic_id'     => $topic->id,
        'slide_number' => $nextNumber,
      ]));

      DB::commit();

      return response()->json([
        'success' => true,
        'message' => 'Slide berhasil disimpan.',
        'slide_id' => $slide->id,
        'slide_number' => $slide->slide_number,
      ]);
    } catch (\Exception $e) {
      DB::rollBack();
      foreach ($uploadedPaths as $path) {
        Storage::disk('public')->delete($path);
      }
      return response()->json([
        'success' => false,
        'message' => 'Gagal menyimpan slide: ' . $e->getMessage()
      ], 500);
    }
  }

  /**
   * FASE 1 — update slide yang sudah tersimpan.
   * Kalau ada file baru diupload, file lama otomatis dihapus & diganti.
   */
  public function updateSlide(Request $request, LearningTopic $topic, LearningSlide $slide)
  {
    if ($slide->topic_id !== $topic->id) {
      return response()->json(['success' => false, 'message' => 'Slide tidak ditemukan pada modul ini.'], 404);
    }

    $validator = Validator::make($request->all(), [
      'slide.title' => 'required|string|max:255',
      'slide.type' => 'required|in:intro,timeline,gambar,article,3d,html,quiz',
    ]);

    if ($validator->fails()) {
      return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
    }

    $uploadedPaths = [];
    $oldPaths = $this->extractMediaPaths($slide->media_url, $slide->content_data ?? []); // ⬅️ SEBELUM diubah

    DB::beginTransaction();
    try {
      $payload = $this->buildSlidePayload($request, 'slide', $uploadedPaths);
      $explicitRemove = $request->boolean('slide.remove_media');

      if (empty($payload['media_url']) && !$explicitRemove) {      // ⬅️ tambahkan syarat
        $payload['media_url'] = $slide->media_url; // pertahankan HANYA kalau bukan penghapusan sengaja
      }

      $slide->update($payload);

      // Bandingkan path lama vs baru → hapus yang sudah tidak dipakai lagi
      $newPaths = $this->extractMediaPaths($payload['media_url'], $payload['content_data']);
      $orphaned = array_diff($oldPaths, $newPaths);
      foreach ($orphaned as $path) {
        Storage::disk('public')->delete(substr($path, strlen('storage/')));
      }

      DB::commit();

      return response()->json(['success' => true, 'message' => 'Slide berhasil diperbarui.', 'slide_id' => $slide->id]);
    } catch (\Exception $e) {
      DB::rollBack();
      foreach ($uploadedPaths as $path) {
        Storage::disk('public')->delete($path);
      }
      return response()->json(['success' => false, 'message' => 'Gagal memperbarui slide: ' . $e->getMessage()], 500);
    }
  }

  /**
   * FASE 1 — hapus 1 slide + file-file terkait, lalu rapikan ulang slide_number yang tersisa.
   */
  public function destroySlide(LearningTopic $topic, LearningSlide $slide)
  {
    if ($slide->topic_id !== $topic->id) {
      return response()->json(['success' => false, 'message' => 'Slide tidak ditemukan pada modul ini.'], 404);
    }

    DB::beginTransaction();
    try {
      $mediaPaths = $this->extractMediaPaths($slide->media_url, $slide->content_data ?? []);
      foreach ($mediaPaths as $path) {
        Storage::disk('public')->delete(substr($path, strlen('storage/')));
      }

      $slide->delete();

      // Rapikan ulang slide_number supaya tetap berurutan 1,2,3,...
      $topic->slides()->orderBy('slide_number')->get()->values()->each(function ($s, $idx) {
        $s->update(['slide_number' => $idx + 1]);
      });

      DB::commit();

      return response()->json(['success' => true, 'message' => 'Slide berhasil dihapus.']);
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json(['success' => false, 'message' => 'Gagal menghapus slide: ' . $e->getMessage()], 500);
    }
  }

  public function destroy(LearningTopic $topic)
  {
    foreach ($topic->slides as $slide) {
      if ($slide->media_url && str_starts_with($slide->media_url, 'storage/')) {
        Storage::disk('public')->delete(substr($slide->media_url, strlen('storage/')));
      }

      $data = $slide->content_data ?? [];
      foreach (($data['items'] ?? []) as $item) {
        if (!empty($item['image']) && str_starts_with($item['image'], 'storage/')) {
          Storage::disk('public')->delete(substr($item['image'], strlen('storage/')));
        }
      }
      foreach (($data['images'] ?? []) as $img) {
        if (!empty($img['media_url']) && str_starts_with($img['media_url'], 'storage/')) {
          Storage::disk('public')->delete(substr($img['media_url'], strlen('storage/')));
        }
      }
    }

    $topic->slides()->delete();
    $topic->delete();

    return redirect()->route('learning.topics.index')->with('success', 'Modul berhasil dihapus.');
  }


  /**
   * FASE 1 — finalisasi: draft -> published. Butuh minimal 1 slide.
   */
  public function publish(LearningTopic $topic)
  {
    if ($topic->slides()->count() < 1) {
      return response()->json(['success' => false, 'message' => 'Modul harus punya minimal 1 slide sebelum dipublikasikan.'], 422);
    }

    $topic->update(['is_active' => true]);

    return response()->json([
      'success' => true,
      'message' => 'Modul berhasil dipublikasikan!',
      'redirect' => route('learning.topics.index'),
    ]);
  }

  /**
   * Helper bersama: susun payload 1 slide (title, type, content_text, media_url, content_data)
   * dari field ber-prefix tertentu (default "slide", dipakai storeSlide & updateSlide).
   */
  private function buildSlidePayload(Request $request, string $prefix, array &$uploadedPaths): array
  {
    $type = $request->input("{$prefix}.type");

    // Hanya anggap ada media_url baru kalau field-nya BENAR-BENAR terisi (bukan cuma "ada tapi kosong")
    $finalMediaUrl = $request->filled("{$prefix}.media_url") ? $request->input("{$prefix}.media_url") : null;
    if ($request->hasFile("{$prefix}.media_file")) {
      $path = $this->storeUploadedFile($request->file("{$prefix}.media_file"));
      $uploadedPaths[] = $path;
      $finalMediaUrl = 'storage/' . $path;
    }

    $contentData = [];

    if ($type === 'intro') {
      $contentData = [
        'badge'    => $request->input("{$prefix}.badge"),
        'subtitle' => $request->input("{$prefix}.subtitle"),
        'theme'    => $request->input("{$prefix}.theme", 'primary'),
      ];
    } elseif ($type === 'timeline') {
      $items = [];
      foreach (($request->input("{$prefix}.timeline") ?? []) as $tIdx => $item) {
        $imageUrl = $item['existing_image'] ?? null;   // ⬅️ default: pakai yang lama
        if ($request->hasFile("{$prefix}.timeline.{$tIdx}.image_file")) {
          $p = $this->storeUploadedFile($request->file("{$prefix}.timeline.{$tIdx}.image_file"));
          $uploadedPaths[] = $p;
          $imageUrl = 'storage/' . $p;                // ⬅️ ganti hanya kalau ada file baru
        }
        $items[] = [
          'icon'    => $item['icon'] ?? null,
          'heading' => $item['heading'] ?? null,
          'text'    => $item['text'] ?? null,
          'image'   => $imageUrl,
        ];
      }
      $contentData = [
        'top_description' => $request->input("{$prefix}.top_description"),
        'info_box'        => $request->input("{$prefix}.info_box"),
        'items'           => $items,
      ];
    } elseif ($type === 'gambar') {
      $images = [];
      foreach (($request->input("{$prefix}.gambar") ?? []) as $gIdx => $item) {
        $imageUrl = $item['existing_media'] ?? null;    // ⬅️ default: pakai yang lama
        if ($request->hasFile("{$prefix}.gambar.{$gIdx}.media_file")) {
          $p = $this->storeUploadedFile($request->file("{$prefix}.gambar.{$gIdx}.media_file"));
          $uploadedPaths[] = $p;
          $imageUrl = 'storage/' . $p;
        }
        $images[] = [
          'media_url' => $imageUrl,
          'position'  => $item['position'] ?? 'center',
          'title'     => $item['title'] ?? null,
          'text'      => $item['text'] ?? null,
        ];
      }
      $contentData = [
        'top_description' => $request->input("{$prefix}.top_description"),
        'images'          => $images,
      ];
    } elseif ($type === 'article') {
      $contentData = [
        'heading'             => $request->input("{$prefix}.heading"),
        'heading_description' => $request->input("{$prefix}.heading_description"),
        'info_penting'        => $request->input("{$prefix}.info_penting"),
        'paragraphs'          => array_values($request->input("{$prefix}.paragraphs") ?? []),
      ];
    } elseif ($type === '3d') {
      $contentData = [
        'top_description' => $request->input("{$prefix}.top_description"),
        'info_penting'     => $request->input("{$prefix}.info_penting"),
        'hotspots'         => array_values($request->input("{$prefix}.hotspots") ?? []),
      ];
    } elseif ($type === 'html') {
      // Konten HTML mentah disimpan langsung ke content_text (field yang sudah ada),
      // tidak perlu kolom baru — cukup pakai field generik yang sudah tersedia.
      $contentData = []; // tidak butuh content_data tambahan untuk tipe ini
    } elseif ($type === 'quiz') {
      $quizMode = $request->input("{$prefix}.quiz_mode", 'pilihan_ganda');

      if ($quizMode === 'pilihan_ganda') {
        $options = [];
        $correctIdx = (int) $request->input("{$prefix}.quiz_correct", -1);
        foreach (($request->input("{$prefix}.options") ?? []) as $idx => $opt) {
          $options[] = [
            'text'    => $opt['text'] ?? null,
            'correct' => ((int) $idx === $correctIdx),
          ];
        }
        $contentData = [
          'quiz_mode'   => 'pilihan_ganda',
          'question'    => $request->input("{$prefix}.question"),
          'options'     => $options,
          'explanation' => $request->input("{$prefix}.explanation"),
        ];
      } else {
        $statements = [];
        foreach (($request->input("{$prefix}.statements") ?? []) as $st) {
          $statements[] = [
            'text'   => $st['text'] ?? null,
            'answer' => filter_var($st['answer'] ?? false, FILTER_VALIDATE_BOOLEAN),
          ];
        }
        $contentData = [
          'quiz_mode'   => 'benar_salah',
          'statements'  => $statements,
          'explanation' => $request->input("{$prefix}.explanation"),
        ];
      }
    }


    return [
      'title'        => $request->input("{$prefix}.title"),
      'type'         => $type,
      'content_text' => $request->input("{$prefix}.content_text"),
      'media_url'    => $finalMediaUrl,
      'content_data' => $contentData,
    ];
  }

  public function edit(LearningTopic $topic)
  {
    $topic->load(['slides', 'mapelMaster']);
    return view('learning.topics.create', compact('topic'));
  }

  /**
   * Simpan file upload dengan mempertahankan ekstensi ASLI-nya,
   * karena Laravel->store() bisa salah tebak ekstensi (mis. .glb jadi .bin)
   * kalau MIME type-nya tidak dikenali sistem.
   */
  private function storeUploadedFile($file, string $folder = 'learning_assets'): string
  {
    $allowedExt = ['glb', 'gltf', 'jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext = strtolower($file->getClientOriginalExtension());

    if (!in_array($ext, $allowedExt)) {
      throw new \Exception("Format file .$ext tidak diizinkan.");
    }

    $filename = Str::random(40) . '.' . $ext;
    return $file->storeAs($folder, $filename, 'public');
  }

  public function updateInfo(Request $request, LearningTopic $topic)
  {
    $validator = Validator::make($request->all(), [
      'mapel_master_id' => 'required|exists:tabel_mapel_master,id',
      'kelas'           => 'required|integer|min:1|max:12',
      'title'           => 'required|string|max:255',
      'description'     => 'nullable|string',
    ]);

    if ($validator->fails()) {
      return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
    }

    $topic->update([
      'mapel_master_id' => $request->mapel_master_id,
      'kelas'           => $request->kelas,
      'title'           => $request->title,
      'description'     => $request->description,
    ]);

    return response()->json(['success' => true, 'message' => 'Info modul berhasil diperbarui.']);
  }

  private function extractMediaPaths(?string $mainMediaUrl, array $contentData): array
  {
    $paths = [];

    if ($mainMediaUrl && str_starts_with($mainMediaUrl, 'storage/')) {
      $paths[] = $mainMediaUrl;
    }
    foreach (($contentData['items'] ?? []) as $item) {
      if (!empty($item['image']) && str_starts_with($item['image'], 'storage/')) {
        $paths[] = $item['image'];
      }
    }
    foreach (($contentData['images'] ?? []) as $img) {
      if (!empty($img['media_url']) && str_starts_with($img['media_url'], 'storage/')) {
        $paths[] = $img['media_url'];
      }
    }

    return $paths;
  }
}
