<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LearningTopic;
use App\Models\LearningSubject;
use App\Models\LearningSlide;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LearningTopicController extends Controller
{
  public function index()
  {
    $topics = LearningTopic::with(['subject.grade', 'slides', 'quiz'])
      ->orderBy('order', 'asc')
      ->get();

    return view('learning.topics.index', compact('topics'));
  }

  public function create()
  {
    $subjects = LearningSubject::with('grade')->get();
    return view('learning.topics.create', compact('subjects'));
  }


  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'learning_subject_id' => 'required|exists:learning_subjects,id',
      'title'                => 'required|string|max:255',
      'description'          => 'nullable|string',
      'order'                => 'nullable|integer',

      'slides'               => 'required|array|min:1',
      'slides.*.title'       => 'required|string|max:255',
      'slides.*.type'        => 'required|in:intro,timeline,gambar,article,3d',
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
      $topic = LearningTopic::create([
        'subject_id'  => $request->learning_subject_id,
        'title'       => $request->title,
        'description' => $request->description,
        'order'       => $request->order ?? 1,
        'is_active'   => $request->has('is_active'),
      ]);

      $slideNumber = 0;

      foreach ($request->slides as $index => $slideData) {
        $slideNumber++;
        $type = $slideData['type'];

        // Media utama slide (cover / model 3D / gambar tunggal artikel)
        $finalMediaUrl = $slideData['media_url'] ?? null;
        if ($request->hasFile("slides.{$index}.media_file")) {
          $path = $this->storeUploadedFile($request->file("slides.{$index}.media_file"));
          $uploadedPaths[] = $path;
          $finalMediaUrl = 'storage/' . $path;
        }

        $contentData = [];

        if ($type === 'intro') {
          $contentData = [
            'badge'    => $slideData['badge'] ?? null,
            'subtitle' => $slideData['subtitle'] ?? null,
            'theme'    => $slideData['theme'] ?? 'primary',
          ];
        } elseif ($type === 'timeline') {
          $items = [];
          foreach (($slideData['timeline'] ?? []) as $tIdx => $item) {
            $imageUrl = null;
            if ($request->hasFile("slides.{$index}.timeline.{$tIdx}.image_file")) {
              $p = $this->storeUploadedFile($request->file("slides.{$index}.timeline.{$tIdx}.image_file"));
              $uploadedPaths[] = $p;
              $imageUrl = 'storage/' . $p;
            }
            $items[] = [
              'icon'    => $item['icon'] ?? null,
              'heading' => $item['heading'] ?? null,
              'text'    => $item['text'] ?? null,
              'image'   => $imageUrl,
            ];
          }
          $contentData = [
            'top_description' => $slideData['top_description'] ?? null,
            'info_box'        => $slideData['info_box'] ?? null,
            'items'           => $items,
          ];
        } elseif ($type === 'gambar') {
          $images = [];
          foreach (($slideData['gambar'] ?? []) as $gIdx => $item) {
            $imageUrl = null;
            if ($request->hasFile("slides.{$index}.gambar.{$gIdx}.media_file")) {
              $p = $this->storeUploadedFile($request->file("slides.{$index}.gambar.{$gIdx}.media_file"));
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
            'top_description' => $slideData['top_description'] ?? null,
            'images'          => $images,
          ];
        } elseif ($type === 'article') {
          $contentData = [
            'heading'             => $slideData['heading'] ?? null,
            'heading_description' => $slideData['heading_description'] ?? null,
            'info_penting'        => $slideData['info_penting'] ?? null,
            'paragraphs'          => array_values($slideData['paragraphs'] ?? []),
          ];
        } elseif ($type === '3d') {
          $contentData = [
            'top_description' => $slideData['top_description'] ?? null,
            'info_penting'     => $slideData['info_penting'] ?? null,
            'hotspots'         => array_values($slideData['hotspots'] ?? []),
          ];
        }

        LearningSlide::create([
          'topic_id'     => $topic->id,
          'slide_number' => $slideNumber,
          'title'        => $slideData['title'],
          'type'         => $type,
          'content_text' => $slideData['content_text'] ?? null,
          'media_url'    => $finalMediaUrl,
          'content_data' => $contentData,
        ]);
      }

      DB::commit();

      return response()->json([
        'success'  => true,
        'message'  => 'Modul pembelajaran berhasil disimpan!',
        'redirect' => route('learning.topic.show', $topic->id),
      ]);
    } catch (\Exception $e) {
      DB::rollBack();

      foreach ($uploadedPaths as $path) {
        Storage::disk('public')->delete($path);
      }

      return response()->json([
        'success' => false,
        'message' => 'Gagal menyimpan: ' . $e->getMessage()
      ], 500);
    }
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
}
