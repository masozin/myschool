<?php
// app/Http/Controllers/FormulirPublikController.php

namespace App\Http\Controllers;

use App\Http\Requests\JawabanFormulirRequest;
use App\Models\Formulir;
use App\Models\FormulirJawaban;
use App\Models\FormulirBerkas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class FormulirPublikController extends Controller
{
    public function show(Formulir $formulir): Response|RedirectResponse
    {
        if (auth()->check() && !$formulir->isTargetedAtUser(auth()->user())) {
            abort(404);
        }

        if ($response = $this->closedResponse($formulir)) {
            return $response;
        }

        if ($formulir->needsLogin() && !auth()->check()) {
            return redirect()->guest(route('login', ['redirect' => route('formulir.publik.show', $formulir->slug)]));
        }

        $userId = auth()->id();

        if ($formulir->allow_multiple_submissions && $userId) {
            return $this->renderSubmissionList($formulir);
        }

        if (!$formulir->allow_multiple_submissions && $userId) {
            $submission = $formulir->submissions()->where('user_id', $userId)->first();
            if ($submission) {
                return redirect()->route('formulir.publik.review', [$formulir->slug, $submission->id]);
            }
        }

        return $this->renderFillForm($formulir);
    }

    protected function renderSubmissionList(Formulir $formulir): Response
    {
        $submissions = $formulir->submissionsBy(auth()->id())->get(['id', 'submission_token', 'submitted_at']);

        return Inertia::render('Formulir/SubmissionList', [
            'form' => $formulir->only('id', 'title', 'slug', 'description', 'banner_image', 'end_at', 'status'),
            'submissions' => $submissions,
        ]);
    }

    // ---------------------------------------------------------------
    // URL helper (guest memakai signed URL, user login memakai route biasa)
    // ---------------------------------------------------------------

    protected function signed(string $route, Formulir $formulir, FormulirJawaban $submission): string
    {
        // (Batch 4.5) Masa berlaku disamakan dengan teks modal sukses: 30 hari.
        return URL::temporarySignedRoute($route, now()->addDays(30), [
            'formulir' => $formulir->slug,
            'submission' => $submission->id,
        ]);
    }

    protected function reviewUrl(Formulir $formulir, FormulirJawaban $submission): string
    {
        return $submission->user_id
            ? route('formulir.publik.review', [$formulir->slug, $submission->id])
            : $this->signed('formulir.publik.tamu.review', $formulir, $submission);
    }

    protected function editUrl(Formulir $formulir, FormulirJawaban $submission): string
    {
        return $submission->user_id
            ? route('formulir.publik.edit', [$formulir->slug, $submission->id])
            : $this->signed('formulir.publik.tamu.edit', $formulir, $submission);
    }

    protected function updateUrl(Formulir $formulir, FormulirJawaban $submission): string
    {
        return $submission->user_id
            ? route('formulir.publik.update', [$formulir->slug, $submission->id])
            : $this->signed('formulir.publik.tamu.update', $formulir, $submission);
    }

    protected function closedResponse(Formulir $formulir): ?Response
    {
        $reason = $formulir->closedReason();

        if ($reason === null) {
            return null;
        }

        return Inertia::render('Formulir/Closed', [
            'form' => $formulir->only('title', 'description', 'banner_image', 'status', 'start_at', 'end_at'),
            'reason' => $reason,
        ]);
    }

    // ---------------------------------------------------------------
    // Isi / edit form
    // ---------------------------------------------------------------

    protected function renderFillForm(Formulir $formulir, ?FormulirJawaban $submission = null, bool $isGuestEdit = false): Response
    {
        $formulir->load([
            'sections.fields' => fn($q) => $q->orderBy('sort_order'),
            'fields' => fn($q) => $q->whereNull('section_id')->orderBy('sort_order'),
        ]);

        if ($submission) {
            $submission->load('files.field');
        }

        return Inertia::render('Formulir/Show', [
            'form' => $formulir,
            'submission' => $submission ? [
                'id' => $submission->id,
                'data' => $submission->data,
                'files' => $submission->files->mapWithKeys(fn($f) => [
                    $f->field->name => ['original_name' => $f->original_name, 'url' => $f->url()],
                ]),
            ] : null,
            'updateUrl' => $submission ? $this->updateUrl($formulir, $submission) : null,
            'reviewUrl' => $submission ? $this->reviewUrl($formulir, $submission) : null,
            'isGuestEdit' => $isGuestEdit,
        ]);
    }

    public function create(Formulir $formulir): Response|RedirectResponse
    {
        if ($response = $this->closedResponse($formulir)) {
            return $response;
        }
        abort_unless($formulir->allow_multiple_submissions, 403);

        if ($formulir->needsLogin() && !auth()->check()) {
            return redirect()->guest(route('login'));
        }

        return $this->renderFillForm($formulir);
    }

    public function edit(Formulir $formulir, FormulirJawaban $submission): Response
    {
        if ($response = $this->closedResponse($formulir)) {
            return $response;
        }
        Gate::authorize('update', $submission);

        return $this->renderFillForm($formulir, $submission);
    }

    public function editGuest(Formulir $formulir, FormulirJawaban $submission): Response
    {
        if ($response = $this->closedResponse($formulir)) {
            return $response;
        }
        abort_if($submission->user_id !== null, 403);

        return $this->renderFillForm($formulir, $submission, isGuestEdit: true);
    }

    // ---------------------------------------------------------------
    // Review (preview jawaban, read-only)
    // ---------------------------------------------------------------

    public function review(Formulir $formulir, FormulirJawaban $submission): Response
    {
        Gate::authorize('update', $submission);

        return $this->renderReview($formulir, $submission);
    }

    public function reviewGuest(Formulir $formulir, FormulirJawaban $submission): Response
    {
        abort_if($submission->user_id !== null, 403);

        return $this->renderReview($formulir, $submission, isGuest: true);
    }

    protected function renderReview(Formulir $formulir, FormulirJawaban $submission, bool $isGuest = false): Response
    {
        $formulir->load([
            'sections.fields' => fn($q) => $q->orderBy('sort_order'),
            'fields' => fn($q) => $q->whereNull('section_id')->orderBy('sort_order'),
        ]);
        $submission->load('files.field');

        return Inertia::render('Formulir/Review', [
            'form' => $formulir,
            'submission' => [
                'id' => $submission->id,
                'submission_token' => $submission->submission_token,
                'submitted_at' => $submission->submitted_at,
                'data' => $submission->data,
                'files' => $submission->files->mapWithKeys(fn($f) => [
                    $f->field->name => ['original_name' => $f->original_name, 'url' => $f->url()],
                ]),
            ],
            'canEdit' => $formulir->closedReason() === null,
            'editUrl' => $this->editUrl($formulir, $submission),
            'isGuest' => $isGuest,
        ]);
    }

    // ---------------------------------------------------------------
    // Simpan / update
    // ---------------------------------------------------------------

    public function update(JawabanFormulirRequest $request, Formulir $formulir, FormulirJawaban $submission): RedirectResponse
    {
        Gate::authorize('update', $submission);
        abort_if($formulir->closedReason() !== null, 403, 'Formulir sudah ditutup.');

        return $this->processUpdate($request, $formulir, $submission);
    }

    public function updateGuest(JawabanFormulirRequest $request, Formulir $formulir, FormulirJawaban $submission): RedirectResponse
    {
        abort_if($submission->user_id !== null, 403);
        abort_if($formulir->closedReason() !== null, 403, 'Formulir sudah ditutup.');

        return $this->processUpdate($request, $formulir, $submission);
    }

    public function store(JawabanFormulirRequest $request, Formulir $formulir): RedirectResponse|Response
    {
        if ($response = $this->closedResponse($formulir)) {
            return $response;
        }

        $validated = $request->validated();
        $data = $validated['data'] ?? [];
        $files = $validated['files'] ?? [];
        $userId = $request->user()?->id;

        $submission = DB::transaction(function () use ($formulir, $userId, $data, $files) {
            // (Batch 4.5) Penjaga kirim ganda di dalam transaksi: untuk
            // form sekali-isi, pengguna login tidak boleh punya dua
            // jawaban — apa pun require_login form-nya. Pemeriksaan di
            // authorize() bisa terlewat oleh dua kiriman bersamaan.
            if ($userId && $formulir->hasSubmittedBy($userId)) {
                abort(403, 'Anda sudah mengirim jawaban untuk formulir ini.');
            }

            $submission = FormulirJawaban::create([
                'form_id' => $formulir->id,
                'user_id' => $userId,
                'data' => $data,
                'submitted_at' => now(),
            ]);

            $this->storeFiles($formulir, $submission, $files);

            return $submission;
        });

        return $this->redirectWithSuccessFlash($formulir, $submission);
    }

    protected function processUpdate(JawabanFormulirRequest $request, Formulir $formulir, FormulirJawaban $submission): RedirectResponse
    {
        $validated = $request->validated();
        $data = $validated['data'] ?? [];
        $files = $validated['files'] ?? [];

        DB::transaction(function () use ($formulir, $submission, $data, $files) {
            $submission->update(['data' => $data, 'submitted_at' => now()]);
            $this->storeFiles($formulir, $submission, $files, replace: true);
        });

        return $this->redirectWithSuccessFlash($formulir, $submission, isUpdate: true);
    }

    /**
     * Susun data flash untuk modal sukses, lalu arahkan ke halaman review
     * (atau ke daftar jawaban untuk form multi-submit).
     */
    protected function redirectWithSuccessFlash(Formulir $formulir, FormulirJawaban $submission, bool $isUpdate = false): RedirectResponse
    {
        $payload = [
            'is_update' => $isUpdate,
            'submission_token' => $submission->submission_token,
        ];

        $toList = $formulir->allow_multiple_submissions && auth()->check();

        if ($formulir->allow_multiple_submissions) {
            $payload['list_url'] = auth()->check() ? route('formulir.publik.show', $formulir->slug) : null;
        } else {
            $payload['review_url'] = $this->reviewUrl($formulir, $submission);
            $payload['is_guest_link'] = $submission->user_id === null;
        }

        $redirectTo = $toList
            ? route('formulir.publik.show', $formulir->slug)
            : $this->reviewUrl($formulir, $submission);

        return redirect($redirectTo)->with('success_submission', $payload);
    }

    protected function storeFiles(Formulir $formulir, FormulirJawaban $submission, array $files, bool $replace = false): void
    {
        foreach ($files as $fieldName => $uploadedFile) {
            if (!$uploadedFile) continue;

            $field = $formulir->fields()->where('name', $fieldName)->first();
            if (!$field) continue;

            if ($replace) {
                $old = $submission->files()->where('field_id', $field->id)->first();
                $old?->delete();
            }

            $path = $uploadedFile->store("formulir/{$formulir->id}/{$submission->submission_token}", 'public');

            FormulirBerkas::create([
                'submission_id' => $submission->id,
                'field_id' => $field->id,
                'original_name' => $uploadedFile->getClientOriginalName(),
                'path' => $path,
                'disk' => 'public',
                'mime_type' => $uploadedFile->getClientMimeType(),
                'size' => $uploadedFile->getSize(),
            ]);
        }
    }
}