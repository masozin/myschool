<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Fondasi modul Formulir K3S (penggabungan form-k3s ke myschool,
   * Pilihan C): lima tabel formulir dengan nama kolom mengikuti
   * form-k3s, kecuali sekolah kini merujuk tabel_sekolah myschool
   * (kolom sekolah_id) dan pembuat/pengisi merujuk users myschool.
   *
   * Setiap pembuatan tabel dijaga Schema::hasTable agar migrasi
   * aman diulang dari keadaan setengah jadi; down() menghapus
   * tabel anak sebelum tabel induknya.
   */
  public function up(): void
  {
    if (!Schema::hasTable('tabel_formulir')) {
      Schema::create('tabel_formulir', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')
          ->nullable()
          ->constrained('tabel_sekolah')
          ->nullOnDelete();
        $table->foreignId('created_by')
          ->constrained('users')
          ->restrictOnDelete();
        $table->string('title');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->string('banner_image')->nullable();
        $table->dateTime('start_at')->nullable();
        $table->dateTime('end_at')->nullable();
        $table->string('status', 20)->default('draft');
        $table->boolean('allow_multiple_submissions')->default(false);
        $table->boolean('require_login')->default(true);
        $table->string('target_role', 30)->nullable()->default('operator');
        $table->timestamps();
        $table->softDeletes();

        $table->index(['sekolah_id', 'status']);
      });
    }

    if (!Schema::hasTable('tabel_formulir_bagian')) {
      Schema::create('tabel_formulir_bagian', function (Blueprint $table) {
        $table->id();
        $table->foreignId('form_id')
          ->constrained('tabel_formulir')
          ->cascadeOnDelete();
        $table->string('title');
        $table->text('description')->nullable();
        $table->unsignedInteger('sort_order')->default(0);
        $table->timestamps();

        $table->index(['form_id', 'sort_order']);
      });
    }

    if (!Schema::hasTable('tabel_formulir_field')) {
      Schema::create('tabel_formulir_field', function (Blueprint $table) {
        $table->id();
        $table->foreignId('form_id')
          ->constrained('tabel_formulir')
          ->cascadeOnDelete();
        $table->foreignId('section_id')
          ->nullable()
          ->constrained('tabel_formulir_bagian')
          ->nullOnDelete();
        $table->string('type', 30);
        $table->string('name');
        $table->string('label');
        $table->text('description')->nullable();
        $table->json('config')->nullable();
        $table->boolean('is_required')->default(false);
        $table->unsignedInteger('sort_order')->default(0);
        $table->timestamps();

        $table->unique(['form_id', 'name']);
        $table->index(['form_id', 'sort_order']);
      });
    }

    if (!Schema::hasTable('tabel_formulir_jawaban')) {
      Schema::create('tabel_formulir_jawaban', function (Blueprint $table) {
        $table->id();
        $table->foreignId('form_id')
          ->constrained('tabel_formulir')
          ->cascadeOnDelete();
        $table->foreignId('user_id')
          ->nullable()
          ->constrained('users')
          ->nullOnDelete();
        $table->uuid('submission_token')->unique();
        $table->json('data');
        $table->timestamp('submitted_at')->nullable();
        $table->timestamps();

        $table->index(['form_id', 'submitted_at']);
        $table->index(['user_id', 'form_id']);
      });
    }

    if (!Schema::hasTable('tabel_formulir_berkas')) {
      Schema::create('tabel_formulir_berkas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('submission_id')
          ->constrained('tabel_formulir_jawaban')
          ->cascadeOnDelete();
        $table->foreignId('field_id')
          ->constrained('tabel_formulir_field')
          ->cascadeOnDelete();
        $table->string('original_name');
        $table->string('path');
        $table->string('disk')->default('public');
        $table->string('mime_type')->nullable();
        $table->unsignedBigInteger('size')->nullable();
        $table->timestamps();

        $table->index(['submission_id', 'field_id']);
      });
    }
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('tabel_formulir_berkas');
    Schema::dropIfExists('tabel_formulir_jawaban');
    Schema::dropIfExists('tabel_formulir_field');
    Schema::dropIfExists('tabel_formulir_bagian');
    Schema::dropIfExists('tabel_formulir');
  }
};
