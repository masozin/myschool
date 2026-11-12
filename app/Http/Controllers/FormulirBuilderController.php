<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFormulirRequest;
use App\Http\Requests\UpdateFormulirRequest;
use App\Http\Requests\UpdateFormulirStructureRequest;
use App\Models\Formulir;
use App\Models\FormulirBagian;
use App\Models\FormulirField;
use App\Models\Sekolah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FormulirBuilderController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Formulir::class);

        $forms = Formulir::query()
            ->with(['creator:id,name', 'sekolah.detailTerbaru'])
            ->withCount('submissions')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Nama sekolah tujuan untuk tampilan daftar (null = Semua Sekolah)
        $forms->getCollection()->transform(function (Formulir $form) {
            $form->sekolah_nama = $form->sekolah?->detailTerbaru?->nama_sekolah;
            $form->unsetRelation('sekolah');

            return $form;
        });

        return Inertia::render('FormBuilder/Index', [
            'forms' => $forms,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Formulir::class);

        return Inertia::render('FormBuilder/Create', [
            'sekolahList' => $this->sekolahList(),
        ]);
    }

    public function store(StoreFormulirRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $request->file('banner_image')
                ->store('formulir/banners', 'public');
        }

        // Sekolah tujuan dipilih Admin lewat form (null = semua sekolah);
        // kolom di tabel_formulir bernama sekolah_id.
        $data['sekolah_id'] = $data['school_id'] ?? null;
        unset($data['school_id']);

        $data['created_by'] = $request->user()->id;

        $form = Formulir::create($data);

        return redirect()
            ->route('formulir.kelola.edit', $form)
            ->with('success', 'Form berhasil dibuat. Sekarang tambahkan field.');
    }

    public function edit(Formulir $formulir): Response
    {
        Gate::authorize('update', $formulir);

        // Sections + fields dimuat sekaligus untuk halaman builder
        $formulir->load(['sections.fields', 'fields' => fn ($q) => $q->whereNull('section_id')]);

        return Inertia::render('FormBuilder/Edit', [
            'form' => $formulir,
            'sekolahList' => $this->sekolahList(),
        ]);
    }

    public function update(UpdateFormulirRequest $request, Formulir $formulir): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('banner_image')) {
            if ($formulir->banner_image) {
                Storage::disk('public')->delete($formulir->banner_image);
            }
            $data['banner_image'] = $request->file('banner_image')
                ->store('formulir/banners', 'public');
        }

        if (array_key_exists('school_id', $data)) {
            $data['sekolah_id'] = $data['school_id'];
            unset($data['school_id']);
        }

        $formulir->update($data);

        return back()->with('success', 'Form berhasil diperbarui.');
    }

    public function destroy(Formulir $formulir): RedirectResponse
    {
        Gate::authorize('delete', $formulir);

        $formulir->delete(); // soft delete

        return redirect()
            ->route('formulir.kelola.index')
            ->with('success', 'Form berhasil dihapus.');
    }

    public function updateStructure(UpdateFormulirStructureRequest $request, Formulir $formulir): RedirectResponse
    {
        $data = $request->validated();
        $usedNames = [];

        DB::transaction(function () use ($data, $formulir, &$usedNames) {
            $keepSectionIds = [];
            $keepFieldIds = [];

            foreach ($data['sections'] ?? [] as $sectionData) {
                $section = FormulirBagian::updateOrCreate(
                    ['id' => $sectionData['id'] ?? null, 'form_id' => $formulir->id],
                    [
                        'title' => $sectionData['title'],
                        'description' => $sectionData['description'] ?? null,
                        'sort_order' => $sectionData['sort_order'],
                    ]
                );
                $keepSectionIds[] = $section->id;

                foreach ($sectionData['fields'] ?? [] as $fieldData) {
                    $name = $this->resolveFieldName($formulir, $fieldData['name'] ?? null, $fieldData['label'], $usedNames, $fieldData['id'] ?? null);

                    $field = FormulirField::updateOrCreate(
                        ['id' => $fieldData['id'] ?? null, 'form_id' => $formulir->id],
                        [
                            'section_id' => $section->id,
                            'type' => $fieldData['type'],
                            'name' => $name,
                            'label' => $fieldData['label'],
                            'description' => $fieldData['description'] ?? null,
                            'config' => empty($fieldData['config']) ? null : $fieldData['config'],
                            'is_required' => $fieldData['is_required'] ?? false,
                            'sort_order' => $fieldData['sort_order'],
                        ]
                    );
                    $keepFieldIds[] = $field->id;
                }
            }

            foreach ($data['fields'] ?? [] as $fieldData) {
                $name = $this->resolveFieldName($formulir, $fieldData['name'] ?? null, $fieldData['label'], $usedNames, $fieldData['id'] ?? null);

                $field = FormulirField::updateOrCreate(
                    ['id' => $fieldData['id'] ?? null, 'form_id' => $formulir->id],
                    [
                        'section_id' => null,
                        'type' => $fieldData['type'],
                        'name' => $name,
                        'label' => $fieldData['label'],
                        'description' => $fieldData['description'] ?? null,
                        'config' => empty($fieldData['config']) ? null : $fieldData['config'],
                        'is_required' => $fieldData['is_required'] ?? false,
                        'sort_order' => $fieldData['sort_order'],
                    ]
                );
                $keepFieldIds[] = $field->id;
            }

            $formulir->sections()->whereNotIn('id', $keepSectionIds)->delete();
            $formulir->fields()->whereNotIn('id', $keepFieldIds)->delete();
        });

        return back()->with('success', 'Struktur form berhasil disimpan.');
    }

    private function resolveFieldName(Formulir $form, ?string $rawName, string $label, array &$usedNames, ?int $currentFieldId): string
    {
        $base = $rawName ? Str::slug($rawName, '_') : Str::slug($label, '_');
        $base = $base ?: 'field';
        $name = $base;
        $i = 1;

        $nameTaken = function (string $candidate) use ($form, $usedNames, $currentFieldId) {
            if (in_array($candidate, $usedNames, true)) {
                return true;
            }
            return $form->fields()
                ->where('name', $candidate)
                ->when($currentFieldId, fn ($q) => $q->where('id', '!=', $currentFieldId))
                ->exists();
        };

        while ($nameTaken($name)) {
            $name = "{$base}_{$i}";
            $i++;
        }

        $usedNames[] = $name;
        return $name;
    }

    /**
     * Daftar semua sekolah untuk pilihan "Sekolah Tujuan" di form Admin.
     * Nama dari detail sekolah terbaru; fallback ke NPSN bila detail kosong.
     */
    private function sekolahList(): array
    {
        return Sekolah::query()
            ->with('detailTerbaru')
            ->get()
            ->map(fn (Sekolah $sekolah) => [
                'id' => $sekolah->id,
                'nama' => $sekolah->detailTerbaru?->nama_sekolah ?? ('NPSN ' . $sekolah->npsn),
            ])
            ->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
