<?php
// app/Exports/FormulirJawabanExport.php
//
// Ekspor Excel semua jawaban satu formulir (Batch 4).
// Port dari FormSubmissionsExport form-k3s, disesuaikan ke myschool:
// identitas pengisi dari user + PTK + sekolah (bukan kolom email/school_id
// ala form-k3s), dan field file ditulis sebagai nama file ekspor
// (FormulirBerkas::exportFilename()) alih-alih URL.

namespace App\Exports;

use App\Models\Formulir;
use App\Models\FormulirField;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FormulirJawabanExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles
{
    protected Formulir $form;

    /** @var \Illuminate\Support\Collection<int, FormulirField> */
    protected $answerableFields;

    protected $nomor = 0;

    public function __construct(Formulir $form)
    {
        $this->form = $form;

        // Muat field lepas (tanpa bagian) + field berbagian, lalu urutkan
        // mengikuti urutan tampil form — sama seperti halaman detail jawaban.
        $form->load([
            'sections.fields' => fn ($q) => $q->orderBy('sort_order'),
            'fields' => fn ($q) => $q->whereNull('section_id')->orderBy('sort_order'),
        ]);

        $this->answerableFields = $form->fields
            ->merge($form->sections->flatMap->fields)
            ->unique('id')
            ->whereNotIn('type', FormulirField::DISPLAY_ONLY_TYPES)
            ->sortBy('sort_order')
            ->values();
    }

    public function collection()
    {
        return $this->form->submissions()
            ->with(['user.ptk.sekolah.detailTerbaru', 'files.field'])
            ->latest('submitted_at')
            ->get();
    }

    public function headings(): array
    {
        $headings = ['No', 'Nama Pengisi', 'Sekolah', 'Waktu Kirim'];

        foreach ($this->answerableFields as $field) {
            $headings[] = $field->label;
        }

        return $headings;
    }

    public function map($submission): array
    {
        $this->nomor++;

        $user = $submission->user;

        $row = [
            $this->nomor,
            $user?->name ?? 'Tamu',
            $user?->ptk?->sekolah?->detailTerbaru?->nama_sekolah ?? '—',
            $submission->submitted_at?->translatedFormat('d M Y H:i') ?? '—',
        ];

        foreach ($this->answerableFields as $field) {
            $row[] = $this->formatAnswer($field, $submission);
        }

        return $row;
    }

    protected function formatAnswer(FormulirField $field, $submission): string
    {
        if ($field->isFileType()) {
            $files = $submission->files->where('field_id', $field->id);

            if ($files->isEmpty()) {
                return '—';
            }

            return $files->map(fn ($file) => $file->exportFilename())->implode(', ');
        }

        $value = $submission->data[$field->name] ?? null;

        if ($field->type === 'date' && $value) {
            try {
                return Carbon::parse($value)->translatedFormat('d M Y');
            } catch (\Throwable) {
                return (string) $value;
            }
        }

        if (is_array($value)) {
            return $value ? implode(', ', $value) : '—';
        }

        return $value !== null && $value !== '' ? (string) $value : '—';
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 5, 'B' => 26, 'C' => 26, 'D' => 18];

        // Kolom jawaban dinamis, mulai dari E
        $col = 'E';
        foreach ($this->answerableFields as $field) {
            $widths[$col] = 28;
            $col++;
        }

        return $widths;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
