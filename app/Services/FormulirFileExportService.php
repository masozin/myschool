<?php
// app/Services/FormulirFileExportService.php
//
// Membuat ZIP berisi semua berkas unggahan dari seluruh jawaban satu
// formulir (Batch 4). Port dari FormFileExportService form-k3s,
// disesuaikan ke myschool: folder per sekolah diambil dari sekolah PTK
// pengisi, nama berkas memakai FormulirBerkas::exportFilename().

namespace App\Services;

use App\Models\Formulir;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class FormulirFileExportService
{
    /**
     * @return string Path absolut file ZIP yang dibuat (sementara, di storage/app/tmp)
     */
    public function export(Formulir $form): string
    {
        $files = $form->submissions()
            ->with('files.field', 'user.ptk.sekolah.detailTerbaru')
            ->get()
            ->flatMap->files
            ->filter(fn ($file) => Storage::disk($file->disk)->exists($file->path));

        $zipFilename = Str::slug($form->slug) . '-berkas-' . now()->format('Ymd-His') . '.zip';
        $zipPath = storage_path("app/tmp/{$zipFilename}");

        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($files->isEmpty()) {
            // Tetap buat ZIP dengan catatan, supaya unduhan tidak error
            $zip->addFromString('tidak-ada-file.txt', 'Belum ada berkas yang diunggah untuk formulir ini.');
            $zip->close();

            return $zipPath;
        }

        foreach ($files as $file) {
            $sekolah = $file->submission?->user?->ptk?->sekolah;
            $namaSekolah = $sekolah?->detailTerbaru?->nama_sekolah;
            $folderName = $namaSekolah ? Str::slug($namaSekolah) : 'tanpa-sekolah';

            $fullPath = Storage::disk($file->disk)->path($file->path);
            $zip->addFile($fullPath, "{$folderName}/{$file->exportFilename()}");
        }

        $zip->close();

        return $zipPath;
    }
}
