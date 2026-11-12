<?php
// app/Http/Controllers/FormulirSubmissionController.php
//
// Sisi Admin untuk jawaban formulir (Batch 4): daftar jawaban +
// pencarian server, detail jawaban, ekspor Excel, ekspor berkas (ZIP),
// dan pemantauan pengisian per sekolah.
// Port dari FormSubmissionController form-k3s, disesuaikan ke myschool:
// identitas pengisi dari user + PTK + sekolah (tanpa email/school_id).

namespace App\Http\Controllers;

use App\Exports\FormulirJawabanExport;
use App\Models\Formulir;
use App\Models\FormulirJawaban;
use App\Models\Sekolah;
use App\Services\FormulirFileExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class FormulirSubmissionController extends Controller
{
    public function index(Request $request, Formulir $formulir): Response
    {
        Gate::authorize('view', $formulir);

        $search = $request->string('search')->toString();

        $submissions = $formulir->submissions()
            ->with(['user:id,name', 'user.ptk:id,user_id,sekolah_id', 'user.ptk.sekolah.detailTerbaru'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('user.ptk.sekolah.detailTerbaru', fn ($d) => $d->where('nama_sekolah', 'like', "%{$search}%"))
                        ->orWhereRaw('CAST(data AS CHAR) LIKE ?', ["%{$search}%"]);
                });
            })
            ->latest('submitted_at')
            ->paginate(15)
            ->withQueryString();

        $formulir->load([
            'sections.fields' => fn ($q) => $q->orderBy('sort_order'),
            'fields' => fn ($q) => $q->whereNull('section_id')->orderBy('sort_order'),
        ]);

        $submissions->getCollection()->transform(fn (FormulirJawaban $submission) => [
            'id' => $submission->id,
            'submission_token' => $submission->submission_token,
            'submitted_at' => $submission->submitted_at,
            'pengisi' => $this->fillerIdentity($submission),
            'preview' => $this->buildPreview($formulir, $submission->data ?? []),
        ]);

        $monitoring = $this->schoolMonitoring($formulir);

        return Inertia::render('FormBuilder/Submissions/Index', [
            'form' => $formulir->only('id', 'title', 'slug', 'status', 'sekolah_id', 'allow_multiple_submissions'),
            'submissions' => $submissions,
            'filters' => ['search' => $search],
            'ringkasan' => [
                'total_jawaban' => $formulir->submissions()->count(),
                'sekolah_sudah' => collect($monitoring)->where('sudah', true)->count(),
                'sekolah_total' => count($monitoring),
            ],
            'monitoring' => $monitoring,
        ]);
    }

    public function show(Formulir $formulir, FormulirJawaban $submission): Response
    {
        Gate::authorize('view', $formulir);
        abort_unless($submission->form_id === $formulir->id, 404);

        $submission->load('files.field', 'user.ptk.sekolah.detailTerbaru');

        $formulir->load([
            'sections.fields' => fn ($q) => $q->orderBy('sort_order'),
            'fields' => fn ($q) => $q->whereNull('section_id')->orderBy('sort_order'),
        ]);

        return Inertia::render('FormBuilder/Submissions/Show', [
            'form' => $formulir,
            'submission' => [
                'id' => $submission->id,
                'submission_token' => $submission->submission_token,
                'submitted_at' => $submission->submitted_at,
                'data' => $submission->data,
                'pengisi' => $this->fillerIdentity($submission),
                'files' => $submission->files->mapWithKeys(fn ($f) => [
                    $f->field->name => [
                        'original_name' => $f->original_name,
                        'url' => $f->url(),
                        'size' => $f->humanSize(),
                    ],
                ]),
            ],
        ]);
    }

    public function exportExcel(Formulir $formulir)
    {
        Gate::authorize('view', $formulir);

        $filename = Str::slug($formulir->title) . '-jawaban-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new FormulirJawabanExport($formulir), $filename);
    }

    public function exportFiles(Formulir $formulir, FormulirFileExportService $exportService)
    {
        Gate::authorize('view', $formulir);

        $zipPath = $exportService->export($formulir);

        $downloadName = Str::slug($formulir->title) . '-berkas-' . now()->format('Y-m-d') . '.zip';

        return response()->download($zipPath, $downloadName)->deleteFileAfterSend(true);
    }

    /**
     * Identitas pengisi ala myschool: nama dari user; tamu (tanpa user)
     * ditampilkan sebagai "Tamu" + potongan token. Nama sekolah dari
     * detail terbaru sekolah PTK pengisi (null-safe untuk Admin,
     * pengguna tanpa PTK, dan tamu).
     */
    protected function fillerIdentity(FormulirJawaban $submission): array
    {
        $user = $submission->user;

        return [
            'nama' => $user?->name ?? 'Tamu',
            'is_tamu' => $user === null,
            'token_pendek' => Str::substr($submission->submission_token, 0, 8),
            'sekolah' => $user?->ptk?->sekolah?->detailTerbaru?->nama_sekolah,
        ];
    }

    /**
     * Ambil 2-3 jawaban pertama sebagai cuplikan di daftar
     * (bukan field file/heading/paragraph).
     */
    protected function buildPreview(Formulir $formulir, array $data): array
    {
        $fields = $formulir->fields
            ->merge($formulir->sections->flatMap->fields)
            ->unique('id')
            ->whereNotIn('type', ['heading', 'paragraph', 'file'])
            ->sortBy('sort_order')
            ->take(3);

        return $fields->map(function ($field) use ($data) {
            $value = $data[$field->name] ?? null;
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            return [
                'label' => $field->label,
                'value' => $value ?: '—',
            ];
        })->values()->all();
    }

    /**
     * Pemantauan pengisian per sekolah: sekolah tujuan bila form khusus
     * satu sekolah, atau semua sekolah bila form untuk semua sekolah.
     * Status diturunkan dari jawaban pengguna login: jawaban → user →
     * PTK → sekolah_id (jawaban tamu tidak teratribusi ke sekolah).
     */
    protected function schoolMonitoring(Formulir $formulir): array
    {
        $rows = $formulir->submissions()
            ->whereNotNull('user_id')
            ->with('user.ptk:id,user_id,sekolah_id')
            ->get(['id', 'user_id', 'submitted_at']);

        $perSekolah = [];
        foreach ($rows as $row) {
            $sekolahId = $row->user?->ptk?->sekolah_id;
            if (! $sekolahId) {
                continue;
            }

            $perSekolah[$sekolahId]['jumlah'] = ($perSekolah[$sekolahId]['jumlah'] ?? 0) + 1;

            if (! isset($perSekolah[$sekolahId]['terakhir'])
                || $row->submitted_at > $perSekolah[$sekolahId]['terakhir']) {
                $perSekolah[$sekolahId]['terakhir'] = $row->submitted_at;
            }
        }

        $schools = Sekolah::query()
            ->with('detailTerbaru')
            ->when($formulir->sekolah_id, fn ($q) => $q->whereKey($formulir->sekolah_id))
            ->get();

        return $schools
            ->map(fn (Sekolah $sekolah) => [
                'id' => $sekolah->id,
                'nama' => $sekolah->detailTerbaru?->nama_sekolah ?? ('NPSN ' . $sekolah->npsn),
                'npsn' => $sekolah->npsn,
                'sudah' => isset($perSekolah[$sekolah->id]),
                'jumlah' => $perSekolah[$sekolah->id]['jumlah'] ?? 0,
                'terakhir' => isset($perSekolah[$sekolah->id])
                    ? $perSekolah[$sekolah->id]['terakhir']?->toIso8601String()
                    : null,
            ])
            ->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
