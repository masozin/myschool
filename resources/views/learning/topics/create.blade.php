@extends('layouts/layoutMaster')

@section('title', isset($topic) ? 'Edit Modul Pembelajaran' : 'Buat Modul Pembelajaran Interaktif')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/quill/typography.scss', 'resources/assets/vendor/libs/highlight/highlight.scss', 'resources/assets/vendor/libs/quill/katex.scss', 'resources/assets/vendor/libs/quill/editor.scss'])
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
@endsection

@section('vendor-script')
  <!-- Model Viewer 3D Google -->
  <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.4.0/model-viewer.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
  @vite(['resources/assets/vendor/libs/quill/katex.js', 'resources/assets/vendor/libs/highlight/highlight.js', 'resources/assets/vendor/libs/quill/quill.js'])
@endsection

@section('page-script')
  <!-- Model Viewer 3D Google -->
  <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.4.0/model-viewer.min.js"></script>
@endsection

@section('content')

  <div class="container-xxl flex-grow-1 container-p-y">

    <!-- HEADER Halaman -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div class="d-flex align-items-center gap-2">
        <span class="p-2 rounded bg-label-primary text-primary d-inline-flex align-items-center justify-content-center">
          <i class="ri ri-stack-line fs-4"></i>
        </span>
        <div>
          <h4 class="fw-bold mb-0">{{ isset($topic) ? 'Edit Modul Pembelajaran' : 'Structured Content Builder' }}</h4>
          <p class="text-muted mb-0">Isi materi pembelajaran tanpa pusing koding atau desain CSS.</p>
        </div>
      </div>
      <a href="{{ route('learning.topics.index') }}"
        class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
        <i class="ri ri-arrow-left-line"></i> Kembali
      </a>
    </div>

    @if (session('error'))
      <div class="alert alert-danger alert-dismissible mb-4 d-flex align-items-start gap-2" role="alert">
        <i class="ri ri-error-warning-line fs-5 mt-1"></i>
        <div>{{ session('error') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div id="alert-container"></div>

    <!-- Bungkus dengan div biasa, BUKAN <form> lagi -->
    <div id="topic-builder">
      @csrf {{-- taruh token CSRF di sini walau bukan form, tetap dibutuhkan untuk fetch() nanti --}}

      <!-- INFORMASI BAB / TOPIK -->
      <div class="card mb-4" id="info-modul-card">
        <div class="card-header bg-label-primary d-flex align-items-center gap-2">
          <i class="ri ri-book-2-line text-primary fs-5"></i>
          <h5 class="card-title mb-0 text-primary fw-bold">1. Informasi Modul Utama</h5>
          <span id="info-modul-status-badge" class="badge bg-label-warning ms-auto">Belum Disimpan</span>
        </div>
        <div class="card-body mt-3">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="mb-3">
                <label class="form-label fw-bold">Mata Pelajaran <span class="text-danger">*</span></label>
                <div class="row g-2 mb-3">
                  <div class="col-md-4">
                    <label class="form-label fw-bold">Kelas <span class="text-danger">*</span></label>
                    <select id="input-kelas" class="form-select" required onchange="loadMapelByKelas()"
                      {{ isset($topic) ? 'disabled' : '' }}>
                      <option value="">-- Pilih --</option>
                      @for ($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}" {{ isset($topic) && $topic->kelas == $i ? 'selected' : '' }}>
                          Kelas {{ $i }}</option>
                      @endfor
                    </select>
                  </div>
                  <div class="col-md-8">
                    <label class="form-label fw-bold">Mata Pelajaran <span class="text-danger">*</span></label>
                    <select id="input-mapel" class="form-select" required {{ isset($topic) ? 'disabled' : '' }}>
                      <option value="">-- Pilih Kelas dulu --</option>
                    </select>
                  </div>
                </div>
              </div>
              <div>
                <label class="form-label fw-bold">Judul Bab / Modul <span class="text-danger">*</span></label>
                <input type="text" id="input-title" class="form-control"
                  placeholder="Contoh: Sistem Pernapasan Manusia" value="{{ $topic->title ?? '' }}" required
                  {{ isset($topic) ? 'disabled' : '' }}>
              </div>
            </div>
            <div class="col-6">
              <div class="mb-2">
                <label class="form-label fw-bold">Deskripsi Singkat</label>
                <textarea class="form-control h-px-100" id="main-description" rows="4">{{ old('description', $topic->description ?? '') }}</textarea>
              </div>
              <div class="">
                <button type="button" id="btn-save-info" class="btn btn-primary d-inline-flex align-items-center gap-1"
                  onclick="saveTopicDraft()">
                  <i class="ri ri-save-3-line"></i>
                  <span
                    id="btn-save-info-label">{{ isset($topic) ? 'Perbarui Info Modul' : 'Simpan Info Modul & Lanjutkan' }}</span>
                </button>
              </div>
            </div>
          </div>

          {{-- <div class="text-end mt-3">
            <button type="button" id="btn-save-info" class="btn btn-primary d-inline-flex align-items-center gap-1"
              onclick="saveTopicDraft()">
              <i class="ri ri-save-3-line"></i> Simpan Info Modul & Lanjutkan
            </button>
          </div> --}}
        </div>
      </div>

      <!-- REPEATER SLIDE BUILDER (terkunci sampai info modul tersimpan) -->
      <div id="slide-section-wrapper" class="position-relative">

        <!-- Overlay penutup, muncul selama topic_id belum ada -->
        <div id="slide-locked-overlay"
          class="d-flex flex-column align-items-center justify-content-center text-center p-5 border rounded bg-light">
          <i class="ri ri-lock-line fs-1 text-muted mb-2"></i>
          <p class="text-muted mb-0 fw-semibold">Simpan "Informasi Modul Utama" di atas dulu untuk membuka bagian slide.
          </p>
        </div>

        <!-- Isi asli section slide, disembunyikan sampai terbuka -->
        <div id="slide-section-content" class="d-none">
          <div class="card-body bg-label-info rounded d-flex p-4 mb-3">
            <div class="d-flex align-items-center gap-2">
              <i class="ri ri-slideshow-line text-info fs-5"></i>
              <h5 class="card-title mb-0 text-info fw-bold">2. Susunan Slide Pembelajaran</h5>
            </div>
          </div>

          <div class="card mb-4">
            <div id="slides-container" class="d-flex flex-column gap-4"></div>
          </div>

          <div class="mt-4 text-center">
            <button type="button"
              class="btn btn-outline-primary w-100 py-3 border-2 border-dashed d-flex align-items-center justify-content-center gap-2"
              onclick="addSlide()">
              <i class="ri ri-add-circle-line fs-4"></i>
              <span class="fw-bold fs-6">Tambah Slide Baru</span>
            </button>
          </div>

          <div class="sticky-bottom border-top p-3 z-3 rounded d-flex justify-content-end gap-2 mb-5 bg-white">
            <a href="{{ route('learning.topics.index') }}"
              class="btn btn-label-secondary d-inline-flex align-items-center gap-1">
              <i class="ri ri-close-line"></i> Keluar (Draft Tersimpan)
            </a>
            <button type="button" id="btn-publish" class="btn btn-success btn-lg d-inline-flex align-items-center gap-1"
              onclick="publishTopic()">
              <i class="ri ri-check-double-line"></i> Selesai & Publikasikan Modul
            </button>
          </div>
        </div>
      </div>

    </div>

    {{-- toats --}}

    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
      <div id="app-toast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive"
        aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body" id="app-toast-body"></div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    </div>

    {{-- crop --}}
    <div class="modal fade" id="cropModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title"><i class="ri ri-crop-line me-1"></i>Sesuaikan & Potong Gambar</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="cancelCrop()"></button>
          </div>
          <div class="modal-body">
            <div style="max-height:60vh; overflow:hidden;">
              <img id="crop-image-target" style="max-width:100%; display:block;">
            </div>
            <small class="text-muted d-block mt-2">Geser & perbesar/perkecil area kotak untuk memilih bagian gambar yang
              dipakai.</small>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal"
              onclick="cancelCrop()">Batal</button>
            <button type="button" id="btn-confirm-crop" class="btn btn-primary" onclick="confirmCrop()">
              <i class="ri ri-check-line me-1"></i> Gunakan Gambar Ini
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if (isset($topic))
    <script>
      const EXISTING_TOPIC_ID = {{ $topic->id }};
      const EXISTING_SLIDES = @json($topic->slides->sortBy('slide_number')->values());
    </script>
  @endif

  <Style>
    #slide-section-content.locked-visual {
      opacity: 0.4;
      pointer-events: none;
      filter: grayscale(60%);
    }

    .card-locked {
      pointer-events: none;
      opacity: 0.65;
    }
  </Style>

  <!-- JAVASCRIPT BUILDER -->
  <script>
    let slideCount = 0;
    let quillInstances = {};

    // Metadata visual per tipe slide: icon, warna aksen, label
    const slideTypeMeta = {
      intro: {
        icon: 'ri ri-flag-2-fill',
        color: 'primary',
        label: 'Banner Pembuka'
      },
      timeline: {
        icon: 'ri ri-route-line',
        color: 'info',
        label: 'Timeline / Alur'
      },
      gambar: {
        icon: 'ri ri-image-2-line',
        color: 'success',
        label: 'Galeri Gambar'
      },
      article: {
        icon: 'ri ri-article-line',
        color: 'warning',
        label: 'Paragraf / Artikel'
      },
      '3d': {
        icon: 'ri ri-android-line',
        color: 'dark',
        label: 'Model 3D Interaktif'
      },
      html: {
        icon: 'ri ri-code-s-slash-line',
        color: 'secondary',
        label: 'HTML Kustom'
      },
      quiz: {
        icon: 'ri ri-question-line',
        color: 'danger',
        label: 'Kuis Selingan'
      }
    };

    function showToast(message, type = 'success') {
      const toastEl = document.getElementById('app-toast');
      const body = document.getElementById('app-toast-body');

      toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning');
      toastEl.classList.add(
        type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-warning'
      );
      body.innerText = message;

      const toast = new bootstrap.Toast(toastEl, {
        delay: 3000
      });
      toast.show();
    }

    async function loadMapelByKelas(selectedMapelId = null) {
      const kelas = document.getElementById('input-kelas').value;
      const mapelSelect = document.getElementById('input-mapel');

      if (!kelas) {
        mapelSelect.innerHTML = '<option value="">-- Pilih Kelas dulu --</option>';
        return;
      }

      mapelSelect.innerHTML = '<option value="">Memuat daftar mapel...</option>';
      mapelSelect.disabled = true;

      try {
        const response = await fetch(`/learning/mapel-by-kelas/${kelas}`, {
          headers: {
            'Accept': 'application/json'
          }
        });
        const result = await response.json();

        if (!result.success || result.mapels.length === 0) {
          mapelSelect.innerHTML = '<option value="">Tidak ada mapel untuk kelas ini</option>';
          return;
        }

        mapelSelect.innerHTML = '<option value="">-- Pilih Mata Pelajaran --</option>' +
          result.mapels.map(m =>
            `<option value="${m.id}" ${selectedMapelId == m.id ? 'selected' : ''}>${m.nama}${m.nama_sub_mapel ? ' - ' + m.nama_sub_mapel : ''}</option>`
          ).join('');

      } catch (err) {
        mapelSelect.innerHTML = '<option value="">Gagal memuat mapel</option>';
      } finally {
        mapelSelect.disabled = false;
      }
    }

    let currentTopicId = null;

    async function saveTopicDraft() {
      const kelas = document.getElementById('input-kelas').value;
      const mapelId = document.getElementById('input-mapel').value;
      const title = document.getElementById('input-title').value;
      const description = document.getElementById('main-description').value;

      if (!kelas || !mapelId || !title) {
        showToast('Kelas, Mata Pelajaran, dan Judul Bab wajib diisi.', 'error');
        return;
      }

      const isEdit = !!currentTopicId;
      const url = isEdit ?
        `/learning/topics/${currentTopicId}` :
        '{{ route('learning.topics.storeDraft') }}';

      const btn = document.getElementById('btn-save-info');
      btn.disabled = true;
      const originalHTML = btn.innerHTML;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

      try {
        const response = await fetch(url, {
          method: isEdit ? 'PUT' : 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Accept': 'application/json',
          },
          body: JSON.stringify({
            mapel_master_id: mapelId,
            kelas: kelas,
            title,
            description
          }),
        });

        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Gagal menyimpan info modul.');

        if (!isEdit) {
          currentTopicId = result.topic_id;
          document.getElementById('slide-locked-overlay').classList.add('d-none');
          document.getElementById('slide-section-content').classList.remove('d-none');
        }

        document.getElementById('info-modul-status-badge').className = 'badge bg-success ms-auto';
        document.getElementById('info-modul-status-badge').innerText = 'Tersimpan';
        showToast('Info modul berhasil disimpan.', 'success');

      } catch (err) {
        showToast(err.message, 'error');
      } finally {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
      }
    }

    function setQuillContent(key, html) {
      const quill = quillInstances[key];
      if (quill) {
        quill.root.innerHTML = html || '';
        const input = document.getElementById(`quill-input-${key}`);
        if (input) input.value = html || '';
      }
    }

    function loadExistingSlide(slide) {
      slideCount++;
      const slideId = slideCount;
      const data = slide.content_data || {};

      // 1. Buat kerangka kartu kosong (sama seperti addSlide, tapi tanpa increment lagi)
      buildSlideCardSkeleton(slideId);

      // 2. Isi field dasar
      document.querySelector(`#slide-card-${slideId} input[name="slides[${slideId}][title]"]`).value = slide.title;
      document.getElementById(`slide-db-id-${slideId}`).value = slide.id;

      const typeSelect = document.getElementById(`slide-type-select-${slideId}`);
      typeSelect.value = slide.type;
      renderSlideFields(slide.type, slideId); // render field sesuai tipe (repeater default 1 item ikut kebuat)

      // 3. Isi field per-tipe SETELAH render (karena elemen baru ada di DOM setelah ini)
      setTimeout(() => {
        if (slide.type === 'intro') {
          setInputValue(slideId, 'subtitle', data.subtitle);
          setInputValue(slideId, 'badge', data.badge);
          setQuillContent(`slide-${slideId}`, slide.content_text);
          showExistingImagePreview(`intro-${slideId}`, slide.media_url);

        } else if (slide.type === 'timeline') {
          setQuillContent(`top-desc-${slideId}`, data.top_description);
          setQuillContent(`infobox-${slideId}`, data.info_box);

          clearRepeater(`timeline-container-${slideId}`); // buang item default kosong
          (data.items || []).forEach(item => {
            addTimelineItem(slideId);
            const idx = timelineCount[slideId];
            const key = `tl-${slideId}-${idx}`;
            setNestedInputValue(slideId, 'timeline', idx, 'icon', item.icon);
            setNestedInputValue(slideId, 'timeline', idx, 'heading', item.heading);
            setQuillContent(key, item.text);
            document.getElementById(`existing-img-${key}`).value = item.image || '';
            showExistingImagePreview(key, item.image);
          });

        } else if (slide.type === 'gambar') {
          setQuillContent(`top-desc-${slideId}`, data.top_description);

          clearRepeater(`gambar-container-${slideId}`);
          (data.images || []).forEach(img => {
            addGambarItem(slideId);
            const idx = gambarCount[slideId];
            const key = `gimg-${slideId}-${idx}`;
            document.querySelector(`[name="slides[${slideId}][gambar][${idx}][position]"]`).value = img
              .position || 'center';
            setNestedInputValue(slideId, 'gambar', idx, 'title', img.title);
            setQuillContent(key, img.text);
            document.getElementById(`existing-media-${key}`).value = img.media_url || '';
            showExistingImagePreview(key, img.media_url);
          });

        } else if (slide.type === 'article') {
          setInputValue(slideId, 'heading', data.heading);
          setInputValue(slideId, 'heading_description', data.heading_description);
          setQuillContent(`infopenting-${slideId}`, data.info_penting);
          showExistingImagePreview(`article-${slideId}`, slide.media_url);

          clearRepeater(`paragraph-container-${slideId}`);
          (data.paragraphs || []).forEach(p => {
            addParagraphItem(slideId);
            const idx = paragraphCount[slideId];
            const key = `para-${slideId}-${idx}`;
            document.querySelector(`[name="slides[${slideId}][paragraphs][${idx}][heading]"]`).value = p
              .heading || '';
            setQuillContent(key, p.content);
          });

        } else if (slide.type === '3d') {
          setQuillContent(`top-desc-${slideId}`, data.top_description);
          setQuillContent(`slide-${slideId}`, slide.content_text);
          setQuillContent(`infopenting-${slideId}`, data.info_penting);
          if (slide.media_url) reloadModelPreviewFromUrl(slideId, slide.media_url);

          clearRepeater(`hotspot-container-${slideId}`);
          (data.hotspots || []).forEach(h => {
            addHotspotItem(slideId);
            const idx = hotspotCount[slideId];
            const key = `hs-${slideId}-${idx}`;
            document.querySelector(`[name="slides[${slideId}][hotspots][${idx}][title]"]`).value = h.title || '';
            document.querySelector(`[name="slides[${slideId}][hotspots][${idx}][text]"]`).value = h.text || '';
            document.getElementById(`hotspot-pos-${key}`).value = h.position || '';

            // ⬅️ TAMBAHAN: pulihkan juga 4 field kamera, supaya tidak hilang saat slide di-edit ulang
            document.getElementById(`hotspot-normal-${key}`).value = h.normal || '';
            document.getElementById(`hotspot-orbit-${key}`).value = h.camera_orbit || '';
            document.getElementById(`hotspot-target-${key}`).value = h.camera_target || '';
            document.getElementById(`hotspot-fov-${key}`).value = h.field_of_view || '';
          });
        } else if (slide.type === 'html') {
          const el = document.getElementById(`html-source-${slideId}`);
          if (el) el.value = slide.content_text || '';
        } else if (slide.type === 'quiz') {
          const mode = data.quiz_mode || 'pilihan_ganda';
          document.getElementById(mode === 'pilihan_ganda' ? `quiz-mode-pg-${slideId}` : `quiz-mode-bs-${slideId}`)
            .checked = true;
          renderQuizModeFields(slideId);
          document.querySelector(`[name="slides[${slideId}][explanation]"]`).value = data.explanation || '';

          if (mode === 'pilihan_ganda') {
            document.getElementById(`quiz-options-container-${slideId}`).innerHTML = '';
            quizOptionCount[slideId] = 0;
            (data.options || []).forEach(opt => {
              addQuizOptionItem(slideId);
              const idx = quizOptionCount[slideId];
              document.querySelector(`[name="slides[${slideId}][options][${idx}][text]"]`).value = opt.text || '';
              if (opt.correct) document.querySelector(`[name="slides[${slideId}][quiz_correct]"][value="${idx}"]`)
                .checked = true;
            });
          } else {
            document.getElementById(`quiz-statements-container-${slideId}`).innerHTML = '';
            quizStatementCount[slideId] = 0;
            (data.statements || []).forEach(st => {
              addQuizStatementItem(slideId);
              const idx = quizStatementCount[slideId];
              document.querySelector(`[name="slides[${slideId}][statements][${idx}][text]"]`).value = st.text ||
                '';
              document.querySelector(`[name="slides[${slideId}][statements][${idx}][answer]"]`).value = st
                .answer ? '1' : '0';
            });
          }
        }

        lockSlide(slideId); // slide lama = sudah tersimpan, langsung terkunci
      }, 50); // beri jeda sedikit supaya Quill selesai inisialisasi dulu

      updateSlideNumbers();
    }

    // ==== Helper-helper kecil ====
    function setInputValue(slideId, field, value) {
      const el = document.querySelector(`[name="slides[${slideId}][${field}]"]`);
      if (el) el.value = value || '';
    }

    function setNestedInputValue(slideId, group, idx, field, value) {
      const el = document.querySelector(`[name="slides[${slideId}][${group}][${idx}][${field}]"]`);
      if (el) el.value = value || '';
    }

    function clearRepeater(containerId) {
      document.getElementById(containerId).innerHTML = '';
    }

    function showExistingImagePreview(key, url) {
      if (!url) return;
      const img = document.getElementById(`file-preview-${key}`);
      const placeholder = document.getElementById(`file-placeholder-${key}`);
      const removeBtn = document.getElementById(`file-remove-${key}`);
      if (img) {
        img.src = `/${url}`;
        img.classList.remove('d-none');
      }
      if (placeholder) placeholder.classList.add('d-none');
      if (removeBtn) removeBtn.classList.remove('d-none');
    }

    function reloadModelPreviewFromUrl(slideId, url) {
      const mv = document.getElementById(`preview-3d-${slideId}`);
      if (mv) mv.src = `/${url}`;
    }

    const slideColorList = Object.values(slideTypeMeta).map(m => m.color);

    function addSlide() {
      slideCount++;
      buildSlideCardSkeleton(slideCount);
      renderSlideFields('intro', slideCount);
      updateSlideNumbers();
    }

    function buildSlideCardSkeleton(slideId) {
      const container = document.getElementById('slides-container');
      const slideHtml = `
          <div class="card slide-card border" id="slide-card-${slideId}">
            <input type="hidden" name="slides[${slideId}][theme]" id="slide-theme-input-${slideId}" value="primary">
            <input type="hidden" id="slide-db-id-${slideId}" value="">
            <div id="slide-header-${slideId}" class="card-header bg-label-primary d-flex justify-content-between align-items-center py-3 flex-wrap gap-2 border-bottom">
              <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-icon btn-sm btn-text-secondary rounded-circle" data-bs-toggle="collapse" data-bs-target="#slide-body-${slideId}" aria-expanded="true">
                  <i class="ri ri-arrow-down-s-line fs-4"></i>
                </button>
                <span class="badge bg-primary fs-6 fw-bold">Slide <span class="slide-number-label">${slideId}</span></span>
                <span id="slide-type-badge-${slideId}" class="fs-6 badge bg-primary text-white d-inline-flex align-items-center gap-1">
                  <i class="ri-flag-2-fill"></i> Banner Pembuka
                </span>
              </div>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span id="slide-status-badge-${slideId}" class="badge bg-label-secondary">Belum Disimpan</span>
                <button type="button" id="btn-save-slide-${slideId}" class="btn btn-success btn-sm d-inline-flex align-items-center gap-1" onclick="saveOrUpdateSlide(${slideId})">
                  <i class="ri ri-save-3-line"></i> Simpan Slide
                </button>
                <button type="button" id="btn-edit-slide-${slideId}" class="btn btn-outline-primary btn-sm d-none d-inline-flex align-items-center gap-1" onclick="unlockSlide(${slideId})">
                  <i class="ri ri-edit-line"></i> Edit
                </button>
                <button type="button" class="btn btn-icon btn-outline-danger btn-sm rounded-circle" onclick="deleteSlide(${slideId})" title="Hapus slide ini">
                  <i class="ri ri-delete-bin-line"></i>
                </button>
              </div>
            </div>
            <div class="collapse show" id="slide-body-${slideId}">
              <div class="card-body mt-3">
                <div class="row g-4">
                  <div class="col-md-7">
                    <label class="form-label fw-bold">Judul Slide (Judul Atas) <span class="text-danger">*</span></label>
                    <input type="text" name="slides[${slideId}][title]" class="form-control" placeholder="Contoh: Pengenalan Paru-Paru" required>
                  </div>
                  <div class="col-md-5">
                    <label class="form-label fw-bold">Tipe Template Slide <span class="text-danger">*</span></label>
                    <select id="slide-type-select-${slideId}" name="slides[${slideId}][type]" class="form-select bg-label-primary fw-bold" onchange="renderSlideFields(this.value, ${slideId})" required>
                      <option value="intro">🌟 1. Judul / Banner Pembuka</option>
                      <option value="timeline">🚀 2. Timeline / Alur Tahapan</option>
                      <option value="gambar">🖼️ 3. Galeri Gambar</option>
                      <option value="article">📄 4. Paragraf / Artikel</option>
                      <option value="3d">🧊 5. Interaktif 3D Model (.glb)</option>
                      <option value="html">💻 6. HTML Kustom (dari AI/manual)</option>
                      <option value="quiz">❓ 7. Kuis Selingan (Pilihan Ganda/Benar-Salah)</option>
                    </select>
                  </div>
                  <div class="col-12" id="slide-dynamic-fields-${slideId}"></div>
                </div>
              </div>
            </div>
          </div>
        `;
      container.insertAdjacentHTML('beforeend', slideHtml);
    }

    // Ambil semua field milik 1 slide dari DOM, ubah nama field dari "slides[N][...]" jadi "slide[...]"
    function extractSlideFormData(slideId) {
      const card = document.getElementById(`slide-card-${slideId}`);
      const fd = new FormData();
      const prefix = `slides[${slideId}]`;

      card.querySelectorAll('[name]').forEach(el => {
        const name = el.getAttribute('name');
        if (!name || !name.startsWith(prefix)) return;

        // Radio & checkbox: HANYA kirim yang benar-benar dipilih/tercentang
        if ((el.type === 'radio' || el.type === 'checkbox') && !el.checked) return;

        const newName = 'slide' + name.slice(prefix.length); // "slide[title]", "slide[timeline][1][heading]", dst

        if (el.type === 'file') {
          if (el.files && el.files[0]) fd.append(newName, el.files[0]);
        } else {
          fd.append(newName, el.value);
        }
      });

      return fd;
    }

    async function saveOrUpdateSlide(slideId) {
      if (!currentTopicId) {
        alert('Simpan info modul dulu sebelum menyimpan slide.');
        return;
      }

      const dbIdInput = document.getElementById(`slide-db-id-${slideId}`);
      const isUpdate = dbIdInput.value !== '';

      const fd = extractSlideFormData(slideId);
      const url = isUpdate ?
        `/learning/topics/${currentTopicId}/slides/${dbIdInput.value}` :
        `/learning/topics/${currentTopicId}/slides`;

      if (isUpdate) fd.append('_method', 'PUT');

      const btn = document.getElementById(`btn-save-slide-${slideId}`);
      const defaultLabel =
        '<i class="ri ri-save-3-line"></i> Simpan Slide'; // ⬅️ label tetap, bukan diambil dari innerHTML saat ini
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Menyimpan...';

      try {
        const response = await fetch(url, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Accept': 'application/json',
          },
          body: fd,
        });

        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Gagal menyimpan slide.');

        if (!isUpdate) dbIdInput.value = result.slide_id;
        lockSlide(slideId);
        showToast(isUpdate ? 'Slide berhasil diperbarui!' : 'Slide berhasil disimpan!', 'success'); // ⬅️ toast sukses

      } catch (err) {
        showToast(err.message, 'error'); // ⬅️ toast gagal, ganti dari alert()
      } finally {
        btn.disabled = false;
        btn.innerHTML = defaultLabel; // ⬅️ SELALU direset, sukses maupun gagal
      }
    }

    function lockSlide(slideId) {

      const card = document.getElementById(`slide-card-${slideId}`);
      card.classList.remove('border-danger', 'border-2'); // ⬅️ tambahkan baris ini
      document.getElementById(`slide-body-${slideId}`).classList.add('card-locked');
      document.getElementById(`slide-type-select-${slideId}`).closest('.col-md-5').classList.add('card-locked');

      const badge = document.getElementById(`slide-status-badge-${slideId}`);
      badge.className = 'badge bg-success';
      badge.innerText = 'Tersimpan';

      document.getElementById(`btn-save-slide-${slideId}`).classList.add('d-none');
      document.getElementById(`btn-edit-slide-${slideId}`).classList.remove('d-none');
    }

    function unlockSlide(slideId) {
      document.getElementById(`slide-body-${slideId}`).classList.remove('card-locked');

      const badge = document.getElementById(`slide-status-badge-${slideId}`);
      badge.className = 'badge bg-warning text-dark';
      badge.innerText = 'Sedang Diedit';

      document.getElementById(`btn-save-slide-${slideId}`).classList.remove('d-none');
      document.getElementById(`btn-edit-slide-${slideId}`).classList.add('d-none');
    }

    async function deleteSlide(slideId) {
      if (document.querySelectorAll('.slide-card').length <= 1) {
        showToast('Minimal harus ada 1 slide!', 'error');
        return;
      }
      if (!confirm('Yakin ingin menghapus slide ini?')) return;

      const dbIdInput = document.getElementById(`slide-db-id-${slideId}`);

      if (dbIdInput && dbIdInput.value) {
        try {
          const response = await fetch(`/learning/topics/${currentTopicId}/slides/${dbIdInput.value}`, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
              'Accept': 'application/json',
            },
            body: new URLSearchParams({
              _method: 'DELETE'
            }),
          });
          const result = await response.json();
          if (!response.ok || !result.success) throw new Error(result.message || 'Gagal menghapus slide di server.');
        } catch (err) {
          showToast(err.message, 'error'); // ⬅️ diperbaiki: huruf T besar
          return;
        }
      }

      document.getElementById(`slide-card-${slideId}`).remove();
      updateSlideNumbers();
      showToast('Slide berhasil dihapus.', 'success'); // ⬅️ dipindah ke akhir, cuma jalan kalau beneran sukses
    }

    async function publishTopic() {
      if (!currentTopicId) return;

      const allCards = Array.from(document.querySelectorAll('.slide-card'));

      // Kartu dianggap "belum aman" kalau body-nya BELUM berstatus terkunci (card-locked)
      // Ini mencakup 2 kasus sekaligus:
      // 1. Slide baru yang belum pernah disimpan sama sekali
      // 2. Slide lama yang sedang dibuka lewat "Edit" tapi belum disimpan ulang
      const unsavedCards = allCards.filter(card => {
        const body = card.querySelector('[id^="slide-body-"]');
        return !body || !body.classList.contains('card-locked');
      });

      allCards.forEach(card => card.classList.remove('border-danger', 'border-2'));

      if (unsavedCards.length > 0) {
        unsavedCards.forEach(card => card.classList.add('border-danger', 'border-2'));
        unsavedCards[0].scrollIntoView({
          behavior: 'smooth',
          block: 'center'
        });
        showToast(
          `Ada ${unsavedCards.length} slide dengan perubahan belum tersimpan (ditandai merah). Klik "Simpan Slide" dulu sebelum publikasi.`,
          'error'
        );
        return;
      }

      if (allCards.length < 1) {
        showToast('Minimal harus ada 1 slide sebelum bisa dipublikasikan.', 'error');
        return;
      }

      const btn = document.getElementById('btn-publish');
      const defaultLabel = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Memublikasikan...';

      try {
        const response = await fetch(`/learning/topics/${currentTopicId}/publish`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Accept': 'application/json',
          },
        });

        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Gagal mempublikasikan modul.');

        showToast('Modul berhasil dipublikasikan! Mengalihkan...', 'success');
        setTimeout(() => {
          window.location.href = result.redirect;
        }, 1200);

      } catch (err) {
        showToast(err.message, 'error');
        btn.disabled = false;
        btn.innerHTML = defaultLabel;
      }
    }
    // Update warna aksen border, header card, tema tersembunyi, dan badge tipe slide
    function updateSlideMeta(slideId, type) {
      const meta = slideTypeMeta[type] || slideTypeMeta['intro'];
      const card = document.getElementById(`slide-card-${slideId}`);
      const header = document.getElementById(`slide-header-${slideId}`);
      const badge = document.getElementById(`slide-type-badge-${slideId}`);
      const select = document.getElementById(`slide-type-select-${slideId}`);
      const themeInput = document.getElementById(`slide-theme-input-${slideId}`);

      if (themeInput) themeInput.value = meta.color;

      if (card) {
        slideColorList.forEach(c => card.classList.remove(`border-${c}`));
        card.classList.add('border-start-2', `border-${meta.color}`);
      }
      if (header) {
        slideColorList.forEach(c => header.classList.remove(`bg-label-${c}`));
        header.classList.add(`bg-label-${meta.color}`);
      }
      if (badge) {
        slideColorList.forEach(c => badge.classList.remove(`bg-label-${c}`, `bg-${c}`));
        badge.classList.add(`bg-${meta.color}`, `text-white`);
        badge.innerHTML = `<i class="${meta.icon}"></i> ${meta.label}`;
      }
      if (select) {
        slideColorList.forEach(c => select.classList.remove(`bg-label-${c}`));
        select.classList.add(`bg-label-${meta.color}`);
      }
    }

    // Inisialisasi Quill Editor dengan membatasi tinggi langsung pada quill.root (.ql-editor)
    function initQuillEditor(editorId, inputId, key, minHeightPx = 80, maxHeightPx = 200) {
      const el = document.getElementById(editorId);
      if (!el) return;

      delete quillInstances[key];

      const quill = new Quill(`#${editorId}`, {
        theme: 'snow',
        modules: {
          toolbar: [
            ['bold', 'italic', 'underline'],
            [{
              color: []
            }],
            [{
              list: 'ordered'
            }, {
              list: 'bullet'
            }],
            ['clean']
          ]
        }
      });

      // MENCEGAH OVERFLOW: Atur tinggi dan scrollbar internal langsung ke editor bawaan Quill
      quill.root.style.minHeight = minHeightPx + 'px';
      quill.root.style.maxHeight = maxHeightPx + 'px';
      quill.root.style.overflowY = 'auto';

      quill.on('text-change', () => {
        const input = document.getElementById(inputId);
        if (input) input.value = quill.root.innerHTML;
      });

      quillInstances[key] = quill;
    }

    function richTextField(label, name, key, placeholder = '', required = false) {
      return `
        <label class="form-label fw-bold">${label} ${required ? '<span class="text-danger">*</span>' : ''}</label>
        <input type="hidden" name="${name}" id="quill-input-${key}">
        <div id="quill-editor-${key}" class="rounded border" data-placeholder="${placeholder}"></div>
      `;
    }


    // FRAME UPLOAD GAMBAR INTERAKTIF DENGAN PLACEHOLDER
    function fileUploadFrame(label, name, key, accept = 'image/*', helpText = 'Klik atau seret file gambar ke sini',
      extraOnChange = '', removeMediaFieldName = null, extraOnRemove = '') {
      const inputId = `file-input-${key}`;
      const previewId = `file-preview-${key}`;
      const placeholderId = `file-placeholder-${key}`;
      const doCrop = accept.includes('image');

      return `
    <div class="mb-2">
      ${label ? `<label class="form-label fw-bold">${label}</label>` : ''}
      <div class="border border-2 border-dashed rounded p-3 text-center position-relative" style="cursor: pointer; min-height: 140px;" onclick="document.getElementById('${inputId}').click()">
        <input type="file" name="${name}" id="${inputId}" class="d-none" accept="${accept}"
          onchange="handleFileSelected(this, '${previewId}', '${placeholderId}', '${key}', ${doCrop}); ${extraOnChange}">

        ${removeMediaFieldName ? `<input type="hidden" name="${removeMediaFieldName}" id="remove-media-${key}" value="0">` : ''}

        <button type="button" class="btn btn-icon btn-danger btn-sm rounded-circle position-absolute d-none"
          id="file-remove-${key}"
          style="top: 6px; right: 6px; width: 26px; height: 26px; padding: 0; z-index: 5;"
          onclick="event.stopPropagation(); clearFileFrame('${key}'); ${extraOnRemove}" title="Hapus gambar">
          <i class="ri ri-close-line"></i>
        </button>

        <div id="${placeholderId}" class="py-2 d-flex flex-column align-items-center justify-content-center">
          <i class="ri ri-image-add-line fs-1 text-secondary mb-1"></i>
          <span class="fw-semibold text-secondary d-block">${helpText}</span>
          <small class="text-muted file-name-label">Format: ${accept}</small>
        </div>
        <img id="${previewId}" class="img-fluid rounded d-none w-100 object-fit-cover" style="max-height: 220px;" alt="Preview">
      </div>
    </div>
  `;
    }

    function previewFileFrame(input, previewId, placeholderId) {
      const img = document.getElementById(previewId);
      const placeholder = document.getElementById(placeholderId);
      const file = input.files && input.files[0];
      const key = previewId.replace('file-preview-', '');
      const removeBtn = document.getElementById(`file-remove-${key}`);
      const removeFlag = document.getElementById(`remove-media-${key}`);

      if (!file) {
        if (img) img.classList.add('d-none');
        if (placeholder) placeholder.classList.remove('d-none');
        if (removeBtn) removeBtn.classList.add('d-none');
        return;
      }

      if (removeFlag) removeFlag.value = '0'; // ⬅️ pilih file baru = batal hapus

      if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => {
          if (img) {
            img.src = e.target.result;
            img.classList.remove('d-none');
          }
          if (placeholder) placeholder.classList.add('d-none');
          if (removeBtn) removeBtn.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
      } else {
        if (img) img.classList.add('d-none');
        if (placeholder) {
          placeholder.classList.remove('d-none');
          const nameEl = placeholder.querySelector('.file-name-label');
          if (nameEl) nameEl.textContent = `File dipilih: ${file.name}`;
        }
        if (removeBtn) removeBtn.classList.remove('d-none');
      }
    }

    function clearFileFrame(key) {
      const input = document.getElementById(`file-input-${key}`);
      const img = document.getElementById(`file-preview-${key}`);
      const placeholder = document.getElementById(`file-placeholder-${key}`);
      const removeBtn = document.getElementById(`file-remove-${key}`);
      const removeFlag = document.getElementById(`remove-media-${key}`);

      if (input) input.value = '';
      if (img) {
        img.classList.add('d-none');
        img.src = '';
      }
      if (placeholder) placeholder.classList.remove('d-none');
      if (removeBtn) removeBtn.classList.add('d-none');
      if (removeFlag) removeFlag.value = '1'; // ⬅️ tandai: user memang mau hapus, bukan sekadar batal pilih

      const existingImg = document.getElementById(`existing-img-${key}`);
      if (existingImg) existingImg.value = '';
      const existingMedia = document.getElementById(`existing-media-${key}`);
      if (existingMedia) existingMedia.value = '';
    }

    const MAX_IMAGE_DIMENSION = 1600; // px, sisi terpanjang setelah crop
    const MAX_IMAGE_SIZE_BYTES = 800 * 1024; // target ~800KB per gambar

    let cropperInstance = null;
    let cropContext = {};

    // Ganti pemanggilan dari onchange <input type="file">, menggantikan previewFileFrame langsung
    function handleFileSelected(input, previewId, placeholderId, key, enableCrop) {
      const file = input.files && input.files[0];
      if (!file) return;

      // Bukan gambar (mis. .glb) atau crop dimatikan → langsung preview biasa, skip crop
      if (!enableCrop || !file.type.startsWith('image/')) {
        previewFileFrame(input, previewId, placeholderId);
        return;
      }

      cropContext = {
        input,
        previewId,
        placeholderId,
        key
      };

      const reader = new FileReader();
      reader.onload = (e) => {
        const imgEl = document.getElementById('crop-image-target');
        imgEl.src = e.target.result;

        const modalEl = document.getElementById('cropModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        const onShown = () => {
          if (cropperInstance) cropperInstance.destroy();
          cropperInstance = new Cropper(imgEl, {
            viewMode: 1,
            autoCropArea: 1,
            responsive: true
          });
          modalEl.removeEventListener('shown.bs.modal', onShown);
        };
        modalEl.addEventListener('shown.bs.modal', onShown);
      };
      reader.readAsDataURL(file);
    }

    // Kompresi iteratif: turunkan kualitas JPEG bertahap sampai di bawah target ukuran
    function compressCanvasToFile(canvas, filename, maxBytes = MAX_IMAGE_SIZE_BYTES) {
      return new Promise((resolve) => {
        let quality = 0.85;
        const tryCompress = () => {
          canvas.toBlob((blob) => {
            if (!blob) return resolve(null);
            if (blob.size <= maxBytes || quality <= 0.3) {
              resolve(new File([blob], filename, {
                type: 'image/jpeg'
              }));
            } else {
              quality -= 0.1;
              tryCompress();
            }
          }, 'image/jpeg', quality);
        };
        tryCompress();
      });
    }

    async function confirmCrop() {
      if (!cropperInstance) return;

      const canvas = cropperInstance.getCroppedCanvas({
        maxWidth: MAX_IMAGE_DIMENSION,
        maxHeight: MAX_IMAGE_DIMENSION,
      });

      const btnConfirm = document.getElementById('btn-confirm-crop');
      const originalLabel = btnConfirm.innerHTML;
      btnConfirm.disabled = true;
      btnConfirm.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';

      const compressedFile = await compressCanvasToFile(canvas, `${cropContext.key}.jpg`);

      btnConfirm.disabled = false;
      btnConfirm.innerHTML = originalLabel;

      if (!compressedFile) return;

      // Suntikkan file hasil crop+kompresi balik ke <input type="file"> aslinya
      const dt = new DataTransfer();
      dt.items.add(compressedFile);
      cropContext.input.files = dt.files;

      previewFileFrame(cropContext.input, cropContext.previewId, cropContext.placeholderId);

      bootstrap.Modal.getInstance(document.getElementById('cropModal'))?.hide();
      cropperInstance.destroy();
      cropperInstance = null;
    }

    function cancelCrop() {
      if (cropContext.input) cropContext.input.value = ''; // kosongkan biar tidak ada file "setengah pilih"
      if (cropperInstance) {
        cropperInstance.destroy();
        cropperInstance = null;
      }
    }

    function renderSlideFields(type, slideId) {
      const target = document.getElementById(`slide-dynamic-fields-${slideId}`);
      let html = '';

      if (type === 'intro') {
        html = `
          <div class="row g-3 p-3">
            <div class="col-md-6">
              <label class="form-label fw-bold">Sub Judul</label>
              <input type="text" name="slides[${slideId}][subtitle]" class="form-control" placeholder="Cth: Modul Bab 1">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Badge Label Atas</label>
              <input type="text" name="slides[${slideId}][badge]" class="form-control" placeholder="Cth: Pengenalan Materi">
            </div>
            <div class="col-3">
              ${fileUploadFrame('Gambar Cover (Opsional)', `slides[${slideId}][media_file]`, `intro-${slideId}`, 'image/*', 'Klik atau seret file gambar ke sini', '', `slides[${slideId}][remove_media]`)}
            </div>
            <div class="col-9">
              ${richTextField('Keterangan', `slides[${slideId}][content_text]`, `slide-${slideId}`)}
            </div>
          </div>
        `;
      } else if (type === 'timeline') {
        html = `
          <div class="row g-3 p-3">
            <div class="col-6">
              ${richTextField('Keterangan Atas', `slides[${slideId}][top_description]`, `top-desc-${slideId}`, 'Penjelasan singkat sebelum tahapan...')}
            </div>
            <div class="col-6">
              ${richTextField('Catatan Box Info Bawah (Opsional)', `slides[${slideId}][info_box]`, `infobox-${slideId}`, 'Cth: Proses ini terjadi secara konstan.')}
            </div>
            <div class="col-12">
              <label class="form-label fw-bold d-flex justify-content-between align-items-center">
                <span><i class="ri ri-route-line me-1 text-info"></i>Kartu Tahapan</span>
                <button type="button" class="btn btn-xs btn-primary rounded-pill d-inline-flex align-items-center gap-1" onclick="addTimelineItem(${slideId})">
                  <i class="ri ri-add-circle-line"></i> Tambah Tahap
                </button>
              </label>
              <div id="timeline-container-${slideId}"></div>
            </div>
          </div>
        `;
      } else if (type === 'gambar') {
        html = `
          <div class="row g-3 p-3">
            <div class="col-12">
              ${richTextField('Keterangan Atas', `slides[${slideId}][top_description]`, `top-desc-${slideId}`, 'Penjelasan singkat galeri gambar...')}
            </div>
            <div class="col-12">
              <label class="form-label fw-bold d-flex justify-content-between align-items-center">
                <span><i class="ri ri-image-2-line me-1 text-success"></i>Daftar Gambar</span>
                <button type="button" class="btn btn-xs btn-primary rounded-pill d-inline-flex align-items-center gap-1" onclick="addGambarItem(${slideId})">
                  <i class="ri ri-add-circle-line"></i> Tambah Gambar
                </button>
              </label>
              <div id="gambar-container-${slideId}"></div>
            </div>
          </div>
        `;
      } else if (type === 'article') {
        html = `
          <div class="row g-3 p-3">
            <div class="col-md-4">
              <div class="mb-2">
                <label class="form-label fw-bold">Judul</label>
                <input type="text" name="slides[${slideId}][heading]" class="form-control" placeholder="Cth: Struktur Paru-Paru">
              </div>
              <div>
                <label class="form-label fw-bold">Keterangan Judul</label>
                <input type="text" name="slides[${slideId}][heading_description]" class="form-control" placeholder="Cth: Bagaimana organ ini bekerja">
              </div>
            </div>
            <div class="col-5">
              <div class="alert alert-warning mb-0">
                <label class="form-label fw-bold text-warning d-flex align-items-center gap-1">
                  <i class="ri ri-lightbulb-flash-line"></i>Info Penting (Opsional)
                </label>
                ${richTextField('', `slides[${slideId}][info_penting]`, `infopenting-${slideId}`, 'Cth: Paru-paru kanan memiliki 3 lobus, sedangkan kiri 2 lobus.')}
              </div>
            </div>
            <div class="col-3">
              ${fileUploadFrame('Gambar Pendukung (Opsional )', `slides[${slideId}][media_file]`, `article-${slideId}`, 'image/*', 'Klik atau seret file gambar ke sini', '', `slides[${slideId}][remove_media]`)}
            </div>
            <div class="col-12">
              <label class="form-label fw-bold d-flex justify-content-between align-items-center">
                <span><i class="ri ri-article-line me-1 text-warning"></i>Paragraf</span>
                <button type="button" class="btn btn-xs btn-primary rounded-pill d-inline-flex align-items-center gap-1" onclick="addParagraphItem(${slideId})">
                  <i class="ri ri-add-circle-line"></i> Tambah Paragraf
                </button>
              </label>
              <div id="paragraph-container-${slideId}"></div>
            </div>
          </div>
        `;
      } else if (type === '3d') {
        html = `
          <div class="row g-3 p-3">
            <div class="col-12">
              ${richTextField('Keterangan Atas', `slides[${slideId}][top_description]`, `top-desc-${slideId}`, 'Penjelasan singkat sebelum model 3D...')}
               <div class="col-12">
              ${richTextField('Teks Penjelasan Materi', `slides[${slideId}][content_text]`, `slide-${slideId}`)}
            </div>
          </div>
          <div class="col-12">
              <div class="alert alert-warning mb-0">
                <label class="fw-bold text-warning mb-0 d-flex align-items-center gap-1">
                  <i class="ri ri-lightbulb-flash-line"></i>Info Penting (Opsional)
                </label>
                ${richTextField('', `slides[${slideId}][info_penting]`, `infopenting-${slideId}`)}
              </div>
            </div>
            <div class="col-md-6">
              ${fileUploadFrame('Upload Model 3D (.glb)', `slides[${slideId}][media_file]`, `3d-${slideId}`, '.glb,.gltf', 'Klik/seret file .glb disini',
              `loadModelFromFile(this, ${slideId})`,
              `slides[${slideId}][remove_media]`,
              `document.getElementById('media-url-${slideId}').value=''; const mv=document.getElementById('preview-3d-${slideId}'); if(mv) mv.removeAttribute('src');`
            )}
            </div>
            <div class="col-md-6">
              <label class="form-label fw-bold">Atau Link .glb External</label>
              <input type="text" id="media-url-${slideId}" name="slides[${slideId}][media_url]" class="form-control" placeholder="https://..."
                oninput="reloadModelPreview(${slideId}); const f=document.getElementById('remove-media-3d-${slideId}'); if(f) f.value='0';">
            </div>

            <!-- PREVIEW MODEL 3D + AREA KLIK -->
            <div class="col-12">
              <label class="form-label fw-bold d-flex justify-content-between align-items-center">
                <span><i class="ri ri-cursor-line me-1 text-dark"></i>Preview Model &amp; Titik Hotspot</span>
                <button type="button" class="btn btn-xs btn-info rounded-pill d-inline-flex align-items-center gap-1" onclick="addHotspotItem(${slideId})">
                  <i class="ri ri-add-circle-line"></i> Tambah Hotspot
                </button>
              </label>
              <div class="border rounded position-relative" style="height:380px; background:#f5f5f5;">
                <model-viewer id="preview-3d-${slideId}" camera-controls shadow-intensity="1"
                  style="width:100%; height:100%;"></model-viewer>
                <div id="picking-banner-${slideId}" class="d-none position-absolute top-0 start-0 w-100 text-center bg-warning text-dark small fw-bold py-1">
                  <i class="ri ri-cursor-line"></i> Klik titik pada model untuk hotspot: <span id="picking-target-label-${slideId}"></span>
                </div>
              </div>
              <small class="text-muted d-block mt-1">Upload model dulu, lalu klik "Tambah Hotspot" → klik tombol "Klik di Model" pada baris hotspot → klik titik di preview.</small>
              <div id="hotspot-container-${slideId}" class="mt-2"></div>
            </div>
          </div>
        `;
      } else if (type === 'html') {
        html = `
        <div class="row g-3 p-3">
          <div class="col-12">
            <div class="alert alert-info d-flex align-items-start gap-2 mb-2">
              <i class="ri ri-information-line fs-5"></i>
              <div>
                <strong>Tips:</strong> Minta AI (ChatGPT/Claude/dll) buatkan potongan HTML lengkap dengan CSS di dalamnya (pakai <code>&lt;style&gt;</code> inline), lalu tempel di sini. Contoh prompt: <em>"Buatkan slide HTML interaktif tentang fotosintesis, self-contained, tanpa dependency eksternal."</em>
              </div>
            </div>
            <label class="form-label fw-bold d-flex justify-content-between align-items-center">
              <span>Kode HTML</span>
              <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill" onclick="toggleHtmlPreview(${slideId})">
                <i class="ri ri-eye-line"></i> Lihat Preview
              </button>
            </label>
            <textarea name="slides[${slideId}][content_text]" id="html-source-${slideId}"
              class="form-control font-monospace" rows="10"
              placeholder="<div>Tempel kode HTML dari AI di sini...</div>"
              oninput="syncHtmlPreview(${slideId})"></textarea>
          </div>
          <div class="col-12 d-none" id="html-preview-wrapper-${slideId}">
            <label class="form-label fw-bold small text-muted">Preview Langsung</label>
            <iframe id="html-preview-${slideId}" class="w-100 border rounded" style="height:300px;" sandbox="allow-scripts"></iframe>
          </div>
        </div>
      `;
      } else if (type === 'quiz') {
        html = `
          <div class="row g-3 p-3">
            <div class="col-12">
              <label class="form-label fw-bold">Bentuk Soal</label>
              <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="slides[${slideId}][quiz_mode]" id="quiz-mode-pg-${slideId}" value="pilihan_ganda" onchange="renderQuizModeFields(${slideId})" checked>
                <label class="btn btn-outline-primary" for="quiz-mode-pg-${slideId}">Pilihan Ganda</label>

                <input type="radio" class="btn-check" name="slides[${slideId}][quiz_mode]" id="quiz-mode-bs-${slideId}" value="benar_salah" onchange="renderQuizModeFields(${slideId})">
                <label class="btn btn-outline-primary" for="quiz-mode-bs-${slideId}">Benar / Salah</label>
              </div>
            </div>
            <div class="col-12" id="quiz-fields-${slideId}"></div>
            <div class="col-12">
              <label class="form-label fw-bold">Penjelasan Jawaban (Opsional)</label>
              <textarea name="slides[${slideId}][explanation]" class="form-control" rows="2" placeholder="Muncul setelah user menjawab, untuk memperkuat pemahaman..."></textarea>
            </div>
          </div>
        `;

      }

      target.innerHTML = html;
      updateSlideMeta(slideId, type);

      // Inisialisasi Quill untuk bidang teks teratas
      ['top-desc-' + slideId, 'slide-' + slideId, 'infobox-' + slideId, 'infopenting-' + slideId].forEach(key => {
        const editorId = `quill-editor-${key}`;
        const inputId = `quill-input-${key}`;
        if (document.getElementById(editorId)) {
          const isTall = key.startsWith('slide-');
          initQuillEditor(editorId, inputId, key, isTall ? 120 : 70, isTall ? 250 : 150);
        }
      });

      if (type === 'timeline') addTimelineItem(slideId);
      if (type === 'gambar') addGambarItem(slideId);
      if (type === 'article') addParagraphItem(slideId);
      if (type === '3d') bindModelClickListener(slideId);
      if (type === 'quiz') renderQuizModeFields(slideId);
    }

    let picking = {}; // { [slideId]: hotspotKey atau null }

    function reloadModelPreview(slideId) {
      const mv = document.getElementById(`preview-3d-${slideId}`);
      const urlInput = document.getElementById(`media-url-${slideId}`);
      if (mv && urlInput && urlInput.value) {
        mv.src = urlInput.value;
      }
    }

    // Dipanggil dari onchange input file .glb (lihat fileUploadFrame di bawah)
    function loadModelFromFile(input, slideId) {
      const file = input.files && input.files[0];
      if (!file) return;
      const mv = document.getElementById(`preview-3d-${slideId}`);
      if (mv) mv.src = URL.createObjectURL(file);
    }

    function bindModelClickListener(slideId) {
      const mv = document.getElementById(`preview-3d-${slideId}`);
      if (!mv || mv.dataset.bound) return;
      mv.dataset.bound = '1';

      mv.addEventListener('click', (event) => {
        const activeKey = picking[slideId];
        if (!activeKey) return;
        if (event.target.closest('.Hotspot')) return;

        const hit = mv.positionAndNormalFromPoint(event.clientX, event.clientY);
        if (!hit) {
          alert('Klik meleset dari permukaan model, coba lagi.');
          return;
        }

        // Posisi & normal titik yang diklik
        document.getElementById(`hotspot-pos-${activeKey}`).value = hit.position.toString();
        document.getElementById(`hotspot-normal-${activeKey}`).value = hit.normal.toString();

        // State kamera PERSIS saat klik terjadi (orbit, target, zoom)
        document.getElementById(`hotspot-orbit-${activeKey}`).value = getCameraOrbitString(mv);
        document.getElementById(`hotspot-target-${activeKey}`).value = getCameraTargetString(mv);
        document.getElementById(`hotspot-fov-${activeKey}`).value = getFieldOfViewString(mv);

        // Feedback visual kecil ke user
        const badge = document.getElementById(`hotspot-captured-${activeKey}`);
        if (badge) badge.classList.remove('d-none');

        stopPicking(slideId);
      });
    }

    function startPicking(slideId, hotspotKey, label) {
      picking[slideId] = hotspotKey;
      document.getElementById(`picking-banner-${slideId}`)?.classList.remove('d-none');
      document.getElementById(`picking-target-label-${slideId}`).innerText = label;
    }

    function stopPicking(slideId) {
      picking[slideId] = null;
      document.getElementById(`picking-banner-${slideId}`)?.classList.add('d-none');
    }

    function getCameraOrbitString(mv) {
      const o = mv.getCameraOrbit(); // theta & phi dalam radian
      const theta = (o.theta * 180 / Math.PI).toFixed(2);
      const phi = (o.phi * 180 / Math.PI).toFixed(2);
      const radius = o.radius.toFixed(3);
      return `${theta}deg ${phi}deg ${radius}m`;
    }

    function getCameraTargetString(mv) {
      const t = mv.getCameraTarget();
      return `${t.x.toFixed(3)}m ${t.y.toFixed(3)}m ${t.z.toFixed(3)}m`;
    }

    function getFieldOfViewString(mv) {
      return `${mv.getFieldOfView().toFixed(2)}deg`;
    }

    // Repeater: Timeline
    let timelineCount = {};

    function addTimelineItem(slideId) {
      if (!timelineCount[slideId]) timelineCount[slideId] = 0;
      timelineCount[slideId]++;
      const tIdx = timelineCount[slideId];
      const key = `tl-${slideId}-${tIdx}`;

      const container = document.getElementById(`timeline-container-${slideId}`);
      const html = `
        <!-- Tambahkan 'position-relative' dan styling card (p-3 ) -->
        <div class="row g-2 my-2 p-3 rounded border align-items-start position-relative" id="timeline-item-${key}">

          <!-- Tombol Close Melayang menggunakan btn-outline-danger -->
          <button type="button"
                  class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2 rounded-circle"
                  style="width: 30px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center; z-index: 10;"
                  onclick="document.getElementById('timeline-item-${key}').remove()"
                  title="Hapus tahap ini">
            <i class="ri ri-close-line fs-5"></i>
          </button>

          <div class="col-md-3">
            <div class="mb-2">
              <label class="form-label small text-muted mb-1">Icon</label>
              <input type="text" name="slides[${slideId}][timeline][${tIdx}][icon]" class="form-control form-control-sm" placeholder="ri ri-heart-line">
            </div>
            <div>
              <label class="form-label small text-muted mb-1">Judul Tahap</label>
              <input type="text" name="slides[${slideId}][timeline][${tIdx}][heading]" class="form-control form-control-sm" placeholder="Cth: Hidung" required>
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label small text-muted mb-1">Keterangan</label>
            <input type="hidden" name="slides[${slideId}][timeline][${tIdx}][text]" id="quill-input-${key}">
            <div id="quill-editor-${key}" class="rounded border"></div>
          </div>

          <!-- Diperlebar menjadi col-md-3 agar grid pas 12 kolom (3 + 6 + 3 = 12) -->
          <div class="col-md-3">
            <div class="small text-muted mb-1">Gambar</div>
            ${fileUploadFrame('', `slides[${slideId}][timeline][${tIdx}][image_file]`, key, 'image/*', 'Upload Gambar')}
          </div>

          // Di dalam HTML addTimelineItem(), tambahkan baris ini (boleh taruh di mana saja dalam div item):
          <input type="hidden" name="slides[${slideId}][timeline][${tIdx}][existing_image]" id="existing-img-${key}" value="">

        </div>
      `;
      container.insertAdjacentHTML('beforeend', html);
      initQuillEditor(`quill-editor-${key}`, `quill-input-${key}`, key, 60, 120);
    }

    // Repeater: Gambar
    let gambarCount = {};

    function addGambarItem(slideId) {
      if (!gambarCount[slideId]) gambarCount[slideId] = 0;
      gambarCount[slideId]++;
      const gIdx = gambarCount[slideId];
      const key = `gimg-${slideId}-${gIdx}`;

      const container = document.getElementById(`gambar-container-${slideId}`);
      const html = `
        <!-- Tambahkan 'position-relative' pada container utama -->
        <div class="row g-2 my-2 p-3 rounded border align-items-start position-relative " id="gambar-item-${key}">

          <!-- Tombol Close (Absolute Position) -->
          <button type="button"
                  class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2 rounded-circle"
                  style="width: 30px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center; z-index: 10;"
                  onclick="document.getElementById('gambar-item-${key}').remove()"
                  title="Hapus gambar ini">
            <i class="ri ri-close-line fs-5"></i>
          </button>

          <div class="col-md-3">
            ${fileUploadFrame('', `slides[${slideId}][gambar][${gIdx}][media_file]`, key, 'image/*', 'Upload Gambar')}
          </div>

          <div class="col-md-3">
            <div class="mb-2">
              <label class="form-label small text-muted mb-1">Posisi</label>
              <select name="slides[${slideId}][gambar][${gIdx}][position]" class="form-select form-select-sm">
                <option value="center">Tengah</option>
                <option value="left">Kiri</option>
                <option value="right">Kanan</option>
                <option value="full">Penuh</option>
              </select>
            </div>
            <div>
              <label class="form-label small text-muted mb-1">Judul Gambar</label>
              <input type="text" name="slides[${slideId}][gambar][${gIdx}][title]" class="form-control form-control-sm" placeholder="Judul Gambar">
            </div>
          </div>

          <!-- Kolom diperlebar menjadi col-md-6 (sebelumnya 5) karena tombol close sudah tidak memakan grid -->
          <div class="col-md-6">
            <label class="form-label small text-muted mb-1">Keterangan</label>
            <input type="hidden" name="slides[${slideId}][gambar][${gIdx}][text]" id="quill-input-${key}">
            <div id="quill-editor-${key}" class="rounded border"></div>
          </div>

          // Di dalam HTML addGambarItem():
          <input type="hidden" name="slides[${slideId}][gambar][${gIdx}][existing_media]" id="existing-media-${key}" value="">

        </div>
      `;
      container.insertAdjacentHTML('beforeend', html);
      initQuillEditor(`quill-editor-${key}`, `quill-input-${key}`, key, 70, 120);
    }

    // Repeater: Paragraf
    let paragraphCount = {};

    function addParagraphItem(slideId) {
      if (!paragraphCount[slideId]) paragraphCount[slideId] = 0;
      paragraphCount[slideId]++;
      const pIdx = paragraphCount[slideId];
      const key = `para-${slideId}-${pIdx}`;

      const container = document.getElementById(`paragraph-container-${slideId}`);
      const html = `
        <!-- Tambahkan 'position-relative' dan perbesar sedikit padding menjadi p-3 -->
        <div class="mb-3 p-3 rounded border position-relative" id="paragraph-item-${key}">

          <!-- Tombol Close Melayang (Absolute Position) -->
          <button type="button"
                  class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2 rounded-circle"
                  style="width: 30px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center; z-index: 10;"
                  onclick="document.getElementById('paragraph-item-${key}').remove()"
                  title="Hapus paragraf ini">
            <i class="ri ri-close-line fs-5"></i>
          </button>

          <!-- Menghilangkan d-flex agar lebih rapi. Ditambahkan pe-5 (padding-end) agar jika teks panjang tidak tertutup tombol di pojok -->
          <div class="mb-2 pe-8">
            <input type="text" name="slides[${slideId}][paragraphs][${pIdx}][heading]" class="form-control form-control-sm" placeholder="Heading Paragraf (Opsional)">
          </div>

          <input type="hidden" name="slides[${slideId}][paragraphs][${pIdx}][content]" id="quill-input-${key}">
          <div id="quill-editor-${key}" class="rounded border"></div>

        </div>
      `;
      container.insertAdjacentHTML('beforeend', html);
      initQuillEditor(`quill-editor-${key}`, `quill-input-${key}`, key, 100, 200);
    }

    // Repeater: Hotspot 3D
    let hotspotCount = {};

    function addHotspotItem(slideId) {
      if (!hotspotCount[slideId]) hotspotCount[slideId] = 0;
      hotspotCount[slideId]++;
      const hIdx = hotspotCount[slideId];
      const key = `hs-${slideId}-${hIdx}`;

      const container = document.getElementById(`hotspot-container-${slideId}`);
      const html = `
    <div class="row g-2 mb-2 p-2 rounded border align-items-center" id="hotspot-item-${key}">
      <div class="col-md-3">
        <input type="text" name="slides[${slideId}][hotspots][${hIdx}][title]" class="form-control form-control-sm" placeholder="Judul Organ" required>
      </div>
      <div class="col-md-4">
        <input type="text" name="slides[${slideId}][hotspots][${hIdx}][text]" class="form-control form-control-sm" placeholder="Penjelasan...">
      </div>
      <div class="col-md-3">
        <input type="text" id="hotspot-pos-${key}" name="slides[${slideId}][hotspots][${hIdx}][position]" class="form-control form-control-sm" placeholder="Belum diisi" readonly>
        <small id="hotspot-captured-${key}" class="text-success d-none"><i class="ri ri-checkbox-circle-line"></i> Sudut kamera tersimpan</small>
      </div>
      <div class="col-md-1">
        <button type="button" class="btn btn-icon btn-outline-info btn-sm rounded-circle" title="Klik di Model" onclick="startPicking(${slideId}, '${key}', document.querySelector('#hotspot-item-${key} input[name*=title]')?.value || 'Hotspot ${hIdx}')">
          <i class="ri ri-crosshair-line"></i>
        </button>
      </div>
      <div class="col-md-1 text-end">
        <button type="button" class="btn btn-icon btn-outline-danger btn-sm rounded-circle" onclick="document.getElementById('hotspot-item-${key}').remove()" title="Hapus hotspot ini">
          <i class="ri ri-close-line"></i>
        </button>
      </div>

      <!-- Field tersembunyi, terisi otomatis saat user klik di model -->
      <input type="hidden" id="hotspot-normal-${key}" name="slides[${slideId}][hotspots][${hIdx}][normal]">
      <input type="hidden" id="hotspot-orbit-${key}" name="slides[${slideId}][hotspots][${hIdx}][camera_orbit]">
      <input type="hidden" id="hotspot-target-${key}" name="slides[${slideId}][hotspots][${hIdx}][camera_target]">
      <input type="hidden" id="hotspot-fov-${key}" name="slides[${slideId}][hotspots][${hIdx}][field_of_view]">
    </div>
  `;
      container.insertAdjacentHTML('beforeend', html);
    }

    function removeSlide(id) {
      const slideCard = document.getElementById(`slide-card-${id}`);
      if (document.querySelectorAll('.slide-card').length > 1) {
        slideCard.remove();
        updateSlideNumbers();
      } else {
        alert('Minimal harus ada 1 slide!');
      }
    }

    function updateSlideNumbers() {
      document.querySelectorAll('.slide-number-label').forEach((label, idx) => {
        label.innerText = idx + 1;
      });
    }

    function toggleHtmlPreview(slideId) {
      const wrapper = document.getElementById(`html-preview-wrapper-${slideId}`);
      wrapper.classList.toggle('d-none');
      if (!wrapper.classList.contains('d-none')) syncHtmlPreview(slideId);
    }

    function syncHtmlPreview(slideId) {
      const frame = document.getElementById(`html-preview-${slideId}`);
      const source = document.getElementById(`html-source-${slideId}`);
      if (frame && source) frame.srcdoc = source.value;
    }

    function renderQuizModeFields(slideId) {
      const mode = document.querySelector(`input[name="slides[${slideId}][quiz_mode]"]:checked`).value;
      const target = document.getElementById(`quiz-fields-${slideId}`);

      if (mode === 'pilihan_ganda') {
        target.innerHTML = `
      <label class="form-label fw-bold">Pertanyaan</label>
      <input type="text" name="slides[${slideId}][question]" class="form-control mb-3" placeholder="Cth: Organ apa yang memompa darah?">
      <label class="form-label fw-bold d-flex justify-content-between align-items-center">
        <span>Pilihan Jawaban</span>
        <button type="button" class="btn btn-xs btn-primary rounded-pill" onclick="addQuizOptionItem(${slideId})">
          <i class="ri ri-add-circle-line"></i> Tambah Pilihan
        </button>
      </label>
      <div id="quiz-options-container-${slideId}"></div>
      <small class="text-muted d-block mt-1">Pilih bulatan di samping jawaban yang BENAR.</small>
    `;
        quizOptionCount[slideId] = 0;
        addQuizOptionItem(slideId);
        addQuizOptionItem(slideId);
      } else {
        target.innerHTML = `
      <label class="form-label fw-bold d-flex justify-content-between align-items-center">
        <span>Daftar Pernyataan</span>
        <button type="button" class="btn btn-xs btn-primary rounded-pill" onclick="addQuizStatementItem(${slideId})">
          <i class="ri ri-add-circle-line"></i> Tambah Pernyataan
        </button>
      </label>
      <div id="quiz-statements-container-${slideId}"></div>
    `;
        quizStatementCount[slideId] = 0;
        addQuizStatementItem(slideId);
      }
    }

    let quizOptionCount = {};

    function addQuizOptionItem(slideId) {
      if (!quizOptionCount[slideId]) quizOptionCount[slideId] = 0;
      quizOptionCount[slideId]++;
      const idx = quizOptionCount[slideId];
      const container = document.getElementById(`quiz-options-container-${slideId}`);
      container.insertAdjacentHTML('beforeend', `
    <div class="d-flex align-items-center gap-2 mb-2" id="quiz-opt-${slideId}-${idx}">
      <input type="radio" name="slides[${slideId}][quiz_correct]" value="${idx}" ${idx === 1 ? 'checked' : ''} title="Tandai jawaban benar">
      <input type="text" name="slides[${slideId}][options][${idx}][text]" class="form-control form-control-sm" placeholder="Pilihan ${idx}">
      <button type="button" class="btn btn-icon btn-outline-danger btn-sm rounded-circle" onclick="document.getElementById('quiz-opt-${slideId}-${idx}').remove()">
        <i class="ri ri-close-line"></i>
      </button>
    </div>
  `);
    }

    let quizStatementCount = {};

    function addQuizStatementItem(slideId) {
      if (!quizStatementCount[slideId]) quizStatementCount[slideId] = 0;
      quizStatementCount[slideId]++;
      const idx = quizStatementCount[slideId];
      const container = document.getElementById(`quiz-statements-container-${slideId}`);
      container.insertAdjacentHTML('beforeend', `
    <div class="row g-2 mb-2 align-items-center" id="quiz-st-${slideId}-${idx}">
      <div class="col-md-7">
        <input type="text" name="slides[${slideId}][statements][${idx}][text]" class="form-control form-control-sm" placeholder="Pernyataan ${idx}">
      </div>
      <div class="col-md-4">
        <select name="slides[${slideId}][statements][${idx}][answer]" class="form-select form-select-sm">
          <option value="1">Benar</option>
          <option value="0">Salah</option>
        </select>
      </div>
      <div class="col-md-1 text-end">
        <button type="button" class="btn btn-icon btn-outline-danger btn-sm rounded-circle" onclick="document.getElementById('quiz-st-${slideId}-${idx}').remove()">
          <i class="ri ri-close-line"></i>
        </button>
      </div>
    </div>
  `);
    }

    document.addEventListener('DOMContentLoaded', () => {

      if (typeof EXISTING_TOPIC_ID !== 'undefined') {
        // MODE EDIT: langsung buka section slide, isi ulang semua slide lama
        currentTopicId = EXISTING_TOPIC_ID;
        loadMapelByKelas({{ $topic->mapel_master_id ?? 'null' }});
        document.getElementById('info-modul-status-badge').className = 'badge bg-success ms-auto';
        document.getElementById('info-modul-status-badge').innerText = 'Tersimpan';
        document.getElementById('slide-locked-overlay').classList.add('d-none');
        document.getElementById('slide-section-content').classList.remove('d-none');

        EXISTING_SLIDES.forEach(slide => loadExistingSlide(slide));

        if (EXISTING_SLIDES.length === 0) addSlide(); // kalau draft kosong belum ada slide sama sekali
      } else {
        addSlide(); // mode buat baru, seperti biasa
      }
    });

    window.addEventListener('beforeunload', function(e) {
      const hasUnsaved = Array.from(document.querySelectorAll('.slide-card')).some(card => {
        const body = card.querySelector('[id^="slide-body-"]');
        return !body || !body.classList.contains('card-locked');
      });

      if (hasUnsaved) {
        e.preventDefault();
        e.returnValue = ''; // wajib di-set walau isinya kosong, supaya browser munculkan dialog konfirmasi bawaan
      }
    });
  </script>
@endsection
