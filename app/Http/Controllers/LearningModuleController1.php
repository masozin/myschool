<?php

namespace App\Http\Controllers;

use App\Models\MapelMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class LearningModuleController extends Controller
{
  public function generateTest1(Request $request)
  {
    $validated = $request->validate([
      'mapel_master_id'          => ['required', 'exists:tabel_mapel_master,id'],
      'materi_pokok'             => ['required', 'string'],
      'capaian_pembelajaran'     => ['required', 'string'],
      'fase'                     => ['nullable', 'string'],
      'kelas'                    => ['nullable', 'string'],
      'jumlah_jp'                => ['required', 'integer', 'min:1'],
      'jumlah_pertemuan'         => ['required', 'integer', 'min:1'],
      'model_pembelajaran'       => ['nullable', 'string'],
      'metode_pembelajaran'      => ['nullable', 'array'],
      'target_peserta_didik'     => ['nullable', 'string'],
    ]);

    $mapel = MapelMaster::findOrFail($validated['mapel_master_id']);

    $prompt = $this->buildPrompt($validated, $mapel);

    $response = Http::timeout(60)
      ->withHeaders(['Content-Type' => 'application/json'])
      ->post(
        'https://generativelanguage.googleapis.com/v1beta/models/'
          . config('services.gemini.model')
          . ':generateContent?key=' . config('services.gemini.key'),
        [
          'contents' => [
            ['role' => 'user', 'parts' => [['text' => $prompt]]],
          ],
          'generationConfig' => [
            'temperature'      => 0.7,
            'responseMimeType' => 'application/json',
          ],
        ]
      );

    // dd dulu untuk cek response mentahnya
    dd([
      'status'      => $response->status(),
      'successful'  => $response->successful(),
      'body'        => $response->json(),
      'prompt_sent' => $prompt,
    ]);
  }

  public function generateTest(Request $request)
  {
    // dummy data sementara untuk testing manual
    $validated = [
      'mapel_master_id'      => 1, // ganti sesuai id mapel yang ada di DB kamu
      'materi_pokok'         => 'Persamaan Linear Satu Variabel',
      'capaian_pembelajaran' => 'Peserta didik mampu menyelesaikan persamaan linear satu variabel dalam konteks nyata.',
      'fase'                 => 'D',
      'kelas'                => 'VII',
      'jumlah_jp'            => 6,
      'jumlah_pertemuan'     => 3,
      'model_pembelajaran'   => 'Problem Based Learning',
      'metode_pembelajaran'  => ['diskusi', 'tanya jawab', 'penugasan'],
      'target_peserta_didik' => 'Reguler',
    ];

    $mapel = MapelMaster::findOrFail($validated['mapel_master_id']);

    $prompt = $this->buildPrompt($validated, $mapel);

    $response = Http::timeout(60)
      ->withHeaders(['Content-Type' => 'application/json'])
      ->post(
        'https://generativelanguage.googleapis.com/v1beta/models/'
          . config('services.gemini.model')
          . ':generateContent?key=' . config('services.gemini.key'),
        [
          'contents' => [
            ['role' => 'user', 'parts' => [['text' => $prompt]]],
          ],
          'generationConfig' => [
            'temperature'      => 0.7,
            'responseMimeType' => 'application/json',
          ],
        ]
      );

    $data = $response->json();

    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    $finishReason = $data['candidates'][0]['finishReason'] ?? null;

    dd([
      'status'        => $response->status(),
      'finish_reason' => $finishReason,
      'raw_text'      => $text,
      'decoded_json'  => $text ? json_decode($text, true) : null,
      'json_error'    => json_last_error_msg(),
    ]);
  }

  private function buildPrompt(array $data, MapelMaster $mapel): string
  {
    $metode = !empty($data['metode_pembelajaran'])
      ? implode(', ', $data['metode_pembelajaran'])
      : '-';

    return <<<PROMPT
Kamu adalah asisten penyusun Modul Ajar Kurikulum Merdeka untuk guru di Indonesia.
Buatkan modul ajar dalam format JSON PERSIS sesuai skema berikut, tanpa teks tambahan di luar JSON:

{
  "identitas": {
    "mata_pelajaran": string,
    "fase": string,
    "kelas": string,
    "alokasi_waktu": string
  },
  "capaian_pembelajaran": string,
  "alur_tujuan_pembelajaran": string,
  "tujuan_pembelajaran": [string],
  "kompetensi_awal": string,
  "profil_pelajar_pancasila": [string],
  "sarana_prasarana": string,
  "target_peserta_didik": string,
  "model_pembelajaran": string,
  "pemahaman_bermakna": string,
  "pertanyaan_pemantik": string,
  "pertemuan": [
    {
      "pertemuan_ke": number,
      "jp": number,
      "tujuan_pembelajaran": string,
      "kegiatan_pendahuluan": string,
      "kegiatan_inti": string,
      "kegiatan_penutup": string,
      "asesmen": string,
      "pengayaan_remedial": string
    }
  ]
}

Data input dari guru:
- Mata pelajaran: {$mapel->nama}
- Materi pokok: {$data['materi_pokok']}
- Fase: {$data['fase']}
- Kelas: {$data['kelas']}
- Capaian Pembelajaran (CP): {$data['capaian_pembelajaran']}
- Jumlah JP total: {$data['jumlah_jp']}
- Jumlah pertemuan: {$data['jumlah_pertemuan']}
- Model pembelajaran: {$data['model_pembelajaran']}
- Metode pembelajaran: {$metode}
- Target peserta didik: {$data['target_peserta_didik']}

Ketentuan:
- Bagi jumlah_jp secara proporsional ke setiap pertemuan sesuai jumlah_pertemuan.
- Bahasa Indonesia baku, sesuai gaya penulisan modul ajar resmi.
- Array "pertemuan" harus berisi tepat {$data['jumlah_pertemuan']} item.
PROMPT;
  }

  public function listModels1()
  {
    $response = Http::get('https://generativelanguage.googleapis.com/v1beta/models', [
      'key' => config('services.gemini.key'),
    ]);

    dd($response->json());
  }

  public function listModels()
  {
    $response = Http::get('https://generativelanguage.googleapis.com/v1beta/models', [
      'key' => config('services.gemini.key'),
    ]);

    $models = collect($response->json('models'))
      ->pluck('name')
      ->filter(fn($name) => str_contains($name, '3.') || str_contains($name, 'latest'))
      ->values();

    dd($models);
  }
}
