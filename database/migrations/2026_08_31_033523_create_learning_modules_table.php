<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('learning_modules', function (Blueprint $table) {
      $table->id();

      // Relasi utama
      $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
      $table->foreignId('semester_id')->nullable()->constrained('tabel_semester')->nullOnDelete();
      $table->foreignId('ptk_id')->constrained('tabel_ptk')->cascadeOnDelete(); // guru penyusun
      $table->foreignId('mapel_master_id')->constrained('tabel_mapel_master')->cascadeOnDelete();
      $table->foreignId('kurikulum_id')->nullable()->constrained('tabel_kurikulum')->nullOnDelete();

      // Info umum
      $table->string('judul_modul');
      $table->string('jenjang', 20)->nullable();      // SD/SMP/SMA/SMK
      $table->string('fase', 10)->nullable();          // Fase A-F
      $table->string('kelas', 20)->nullable();         // Kelas X, XI, dst (teks bebas, tidak wajib ikat ke rombel)
      $table->string('materi_pokok');

      // Kurikulum & tujuan
      $table->text('capaian_pembelajaran')->nullable();       // CP
      $table->text('alur_tujuan_pembelajaran')->nullable();   // ATP (ringkasan)
      $table->json('tujuan_pembelajaran')->nullable();        // list TP per pertemuan/keseluruhan
      $table->text('kompetensi_awal')->nullable();
      $table->text('pemahaman_bermakna')->nullable();
      $table->text('pertanyaan_pemantik')->nullable();

      // Perencanaan
      $table->unsignedSmallInteger('jumlah_jp')->default(0);
      $table->unsignedTinyInteger('jumlah_pertemuan')->default(1);
      $table->string('model_pembelajaran')->nullable();   // PBL, PjBL, Discovery Learning, dst
      $table->json('metode_pembelajaran')->nullable();    // ["diskusi","ceramah","eksperimen"]
      $table->json('profil_pelajar_pancasila')->nullable();
      $table->text('sarana_prasarana')->nullable();
      $table->string('target_peserta_didik')->nullable();

      // AI generation
      $table->string('ai_provider', 30)->default('gemini');
      $table->string('ai_model', 50)->nullable();      // gemini-1.5-flash, dst
      $table->longText('ai_prompt')->nullable();       // prompt yang dikirim (untuk audit/regenerate)
      $table->longText('ai_raw_response')->nullable(); // response mentah (debug)
      $table->json('konten')->nullable();               // hasil akhir terstruktur sesuai template
      $table->json('manual_inputs')->nullable();
      $table->string('file_path')->nullable();          // path hasil export docx/pdf

      $table->enum('status', ['draft', 'processing', 'generated', 'failed', 'published'])
        ->default('draft');
      $table->text('failed_reason')->nullable();
      $table->timestamp('generated_at')->nullable();

      $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
      $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

      $table->timestamps();
      $table->softDeletes();

      $table->index(['sekolah_id', 'semester_id', 'mapel_master_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('learning_modules');
  }
};
