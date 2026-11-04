<?php

namespace App\Jobs;

use App\Models\LearningModule;
use App\Models\LearningModuleMeeting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateLearningModuleJob implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  // Jumlah retry di level QUEUE (kalau job gagal total/exception, Laravel akan re-run job ini)
  public int $tries = 2;

  // Timeout job dalam detik — beri ruang lebih besar dari timeout Http client
  public int $timeout = 180;

  public function __construct(
    public LearningModule $learningModule
  ) {}

  public function handle(): void
  {
    $learningModule = $this->learningModule->fresh();

    if (! $learningModule || $learningModule->status !== 'processing') {
      return;
    }

    $mapel = $learningModule->mapel;
    $prompt = $this->buildPrompt($learningModule, $mapel->nama);

    $data = $this->callGeminiApi($prompt);

    $finishReason = $data['candidates'][0]['finishReason'] ?? null;
    $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if ($finishReason !== 'STOP' || ! $rawText) {
      throw new \RuntimeException("Response AI tidak lengkap (finish_reason: {$finishReason}).");
    }

    $konten = json_decode($rawText, true);

    Log::info('Konten AI mentah', ['konten' => $konten]);

    if (json_last_error() !== JSON_ERROR_NONE || ! isset($konten['blok_pembelajaran'])) {
      throw new \RuntimeException('Gagal parsing JSON dari AI: ' . json_last_error_msg());
    }

    DB::transaction(function () use ($learningModule, $prompt, $rawText, $konten) {
      $desain = $konten['desain_pembelajaran'] ?? [];

      $learningModule->update([
        'ai_prompt'                => $prompt,
        'ai_raw_response'          => $rawText,
        'konten'                   => $konten,
        'capaian_pembelajaran'     => $desain['capaian_pembelajaran'] ?? $learningModule->capaian_pembelajaran,
        'lintas_disiplin_ilmu'     => $desain['lintas_disiplin_ilmu'] ?? null,
        'tujuan_pembelajaran'      => $desain['tujuan_pembelajaran'] ?? null,
        'praktik_pedagogis'        => $desain['praktik_pedagogis'] ?? null,
        'pemanfaatan_teknologi'    => $desain['pemanfaatan_teknologi'] ?? null,
        'identifikasi_murid'       => $konten['identifikasi_murid'] ?? null,
        'materi_pelajaran_list'    => $konten['materi_pelajaran'] ?? null,
        'dimensi_profil_lulusan'   => $konten['dimensi_profil_lulusan'] ?? null,
        'asesmen'                  => $konten['asesmen'] ?? null,
        'pengayaan'                => $konten['pengayaan'] ?? null,
        'remedial'                 => $konten['remedial'] ?? null,
        'refleksi_peserta_didik'   => $konten['refleksi_peserta_didik'] ?? null,
        'refleksi_pendidik'        => $konten['refleksi_pendidik'] ?? null,
        'status'                   => 'generated',
        'generated_at'             => now(),
        'failed_reason'            => null,
      ]);

      // hapus meetings lama kalau ini proses retry/regenerate
      $learningModule->meetings()->delete();

      $pertemuanKe = 1;

      foreach ($konten['blok_pembelajaran'] as $blokUrutan => $blok) {
        foreach ($blok['pertemuan'] ?? [] as $pertemuan) {
          LearningModuleMeeting::create([
            'learning_module_id' => $learningModule->id,
            'pertemuan_ke'        => $pertemuanKe++,
            'blok_judul'          => $blok['judul_blok'] ?? null,
            'blok_urutan'         => $blokUrutan + 1,
            'rentang_pertemuan'   => $pertemuan['rentang_pertemuan'] ?? null,
            'jp'                  => $pertemuan['jp'] ?? 0,
            'kegiatan_awal_list'    => $pertemuan['kegiatan_awal'] ?? [],
            'kegiatan_inti_list'    => $pertemuan['kegiatan_inti'] ?? [],
            'kegiatan_penutup_list' => $pertemuan['kegiatan_penutup'] ?? [],
          ]);
        }
      }
    });
  }

  public function failed(\Throwable $exception): void
  {
    $this->learningModule->update([
      'status'        => 'failed',
      'failed_reason' => $exception->getMessage(),
    ]);

    Log::error('Gagal generate modul ajar AI (final)', [
      'learning_module_id' => $this->learningModule->id,
      'error'               => $exception->getMessage(),
    ]);
  }


  private function buildPrompt(LearningModule $learningModule, string $namaMapel): string
  {

    $manual = $learningModule->manual_inputs ?? [];

    $instruksiManual = '';
    if (! empty($manual)) {
      $instruksiManual = "\n\nPENTING — Untuk field berikut, JANGAN generate dari nol. Gunakan draft dari guru di bawah ini, HANYA rapikan bahasa, tata bahasa, dan strukturnya (ubah ke format sesuai skema JSON), TANPA mengubah substansi/isi yang sudah ditulis guru:\n";
      foreach ($manual as $field => $draft) {
        $instruksiManual .= "- {$field}: \"{$draft}\"\n";
      }
    }

    return <<<PROMPT
Kamu adalah asisten penyusun Modul Ajar / Rencana Pembelajaran Mendalam (Deep Learning) untuk guru di Indonesia, mengikuti Kurikulum Merdeka.
Buatkan modul ajar dalam format JSON PERSIS sesuai skema berikut, tanpa teks tambahan di luar JSON:

{
  "informasi_umum": {
    "satuan_pendidikan": string,
    "mata_pelajaran": string,
    "kelas_fase": string,
    "bab": string,
    "topik": string,
    "alokasi_waktu": string
  },
  "identifikasi_murid": {
    "pengetahuan_awal": string,
    "minat": string,
    "kebutuhan_belajar": string
  },
  "materi_pelajaran": [string],
  "dimensi_profil_lulusan": [
    { "dimensi": string, "elemen": string }
  ],
  "desain_pembelajaran": {
    "capaian_pembelajaran": string,
    "lintas_disiplin_ilmu": string,
    "tujuan_pembelajaran": [string],
    "praktik_pedagogis": string,
    "pemanfaatan_teknologi": string
  },
  "blok_pembelajaran": [
    {
      "judul_blok": string,
      "total_jp": number,
      "pertemuan": [
        {
          "rentang_pertemuan": string,
          "jp": number,
          "kegiatan_awal": [string],
          "kegiatan_inti": [string],
          "kegiatan_penutup": [string]
        }
      ]
    }
  ],
  "asesmen": {
    "diagnostik": string,
    "formatif": [string],
    "sumatif": [string]
  },
  "pengayaan": [string],
  "remedial": [string],
  "refleksi_peserta_didik": [string],
  "refleksi_pendidik": [string]
}

Data input dari guru:
- Satuan Pendidikan: {$learningModule->sekolah->nama_sekolah}
- Mata pelajaran: {$namaMapel}
- Kelas/Fase: {$learningModule->kelas} / {$learningModule->fase}
- Bab: {$learningModule->bab}
- Materi Pokok/Topik: {$learningModule->materi_pokok}
- Capaian Pembelajaran (CP): {$learningModule->capaian_pembelajaran}
- Jumlah JP total: {$learningModule->jumlah_jp}
- Jumlah pertemuan: {$learningModule->jumlah_pertemuan}
- Model pembelajaran: {$learningModule->model_pembelajaran}

Ketentuan:
- Gunakan pendekatan Pembelajaran Mendalam (Deep Learning): meaningful learning, joyful learning, dan mindful learning harus tercermin di "praktik_pedagogis".
- Bagi "blok_pembelajaran" secara logis berdasarkan sub-topik, total JP tiap blok harus sesuai proporsi jumlah_jp={$learningModule->jumlah_jp}.
- Total pertemuan di seluruh blok harus tepat {$learningModule->jumlah_pertemuan}.
- Setiap kegiatan (awal/inti/penutup) ditulis sebagai array langkah, bukan satu paragraf panjang.
- Bahasa Indonesia baku, gaya penulisan modul ajar resmi sekolah.
PROMPT;
  }

  private function callGeminiApi(string $prompt): array
  {
    $maxAttempts = 3;
    $attempt = 0;

    while (true) {
      $attempt++;

      $response = Http::timeout(120)
        ->connectTimeout(10)
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
              'maxOutputTokens'  => 8192,
            ],
          ]
        );

      if ($response->successful()) {
        return $response->json();
      }

      $errorMessage = $response->json('error.message') ?? $response->body();
      $isOverloaded = $response->status() === 503
        || str_contains(strtolower($errorMessage), 'high demand')
        || str_contains(strtolower($errorMessage), 'overloaded');

      if ($isOverloaded && $attempt < $maxAttempts) {
        sleep($attempt * 3); // 3s, 6s
        continue;
      }

      throw new \RuntimeException('Gemini API error: ' . $errorMessage);
    }
  }
}
