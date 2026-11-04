@extends('layouts/layoutMaster')

@section('title', 'Modul Ajar AI')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('content')

  {{-- =========================================================== --}}
  {{-- HEADER INFO                                                  --}}
  {{-- =========================================================== --}}
  <div class="row mb-4 g-4">
    <div class="col-12 col-md-8">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-4 py-4">
          <div class="avatar avatar-xl flex-shrink-0">
            <span class="avatar-initial rounded-circle bg-label-primary">
              <i class="icon-base ri ri-magic-line ri-28px"></i>
            </span>
          </div>
          <div>
            <h4 class="mb-1">Modul Ajar (AI Generator)</h4>
            <div class="d-flex gap-4 flex-wrap">
              <span class="text-muted"><i class="icon-base ri ri-file-list-3-line me-1"></i><span
                  id="info-jumlah-modul">0</span> Modul</span>
              <span class="text-muted"><i class="icon-base ri ri-time-line me-1"></i>Estimasi proses ±30-60 detik
                per modul</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- TOOLBAR                                                      --}}
  {{-- =========================================================== --}}
  <div class="card mb-4">
    <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div class="d-flex gap-2 flex-wrap">
        <input type="text" id="search-modul" class="form-control form-control-sm" style="max-width:220px"
          placeholder="Cari materi/mapel...">
        <select id="filter-status" class="form-select form-select-sm" style="max-width:160px">
          <option value="">Semua Status</option>
          <option value="draft">Draft</option>
          <option value="processing">Diproses</option>
          <option value="generated">Selesai</option>
          <option value="failed">Gagal</option>
          <option value="published">Diterbitkan</option>
        </select>
      </div>
      <a href="{{ route('learning-modules.create') }}" class="btn btn-primary btn-sm">
        <i class="ri ri-add-line me-1"></i> <span class="d-none d-sm-inline-block">Buat Modul Ajar</span>
      </a>
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- TABEL MODUL AJAR                                             --}}
  {{-- =========================================================== --}}
  <div class="card">
    <div class="table-responsive">
      <table class="table table-bordered table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th width="50" class="text-center">#</th>
            <th>Judul Modul</th>
            <th>Mapel</th>
            <th class="text-center">Fase / Kelas</th>
            <th class="text-center">JP / Pertemuan</th>
            <th class="text-center">Status</th>
            <th class="text-center" width="140">Aksi</th>
          </tr>
        </thead>
        <tbody id="tbody-modul">
          <tr>
            <td colspan="7" class="text-center py-4 text-muted">
              <span class="spinner-border spinner-border-sm me-2"></span> Memuat data...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {

      const $tbody = document.getElementById('tbody-modul');
      const $infoJumlah = document.getElementById('info-jumlah-modul');
      const $searchModul = document.getElementById('search-modul');
      const $filterStatus = document.getElementById('filter-status');

      const statusBadge = {
        draft: '<span class="badge bg-label-secondary">Draft</span>',
        processing: '<span class="badge bg-label-warning"><span class="spinner-border spinner-border-sm me-1" style="width:.7rem;height:.7rem"></span>Diproses</span>',
        generated: '<span class="badge bg-label-success">Selesai</span>',
        failed: '<span class="badge bg-label-danger">Gagal</span>',
        published: '<span class="badge bg-label-info">Diterbitkan</span>',
      };

      let pollingTimer = null;

      function loadModul() {
        const params = new URLSearchParams({
          search: $searchModul.value || '',
          status: $filterStatus.value || '',
        });

        fetch(`{{ route('learning-modules.data') }}?${params.toString()}`, {
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
          .then(res => res.json())
          .then(data => {
            $infoJumlah.textContent = data.length;

            if (data.length === 0) {
              $tbody.innerHTML =
                `<tr><td colspan="7" class="text-center py-4 text-muted">Belum ada modul ajar.</td></tr>`;
              return;
            }

            let html = '';
            data.forEach((m, i) => {
              html += `
                <tr>
                  <td class="text-center">${i + 1}</td>
                  <td>${m.judul_modul}</td>
                  <td>${m.mapel?.nama ?? '-'}</td>
                  <td class="text-center">${m.fase ?? '-'} / ${m.kelas ?? '-'}</td>
                  <td class="text-center">${m.jumlah_jp} JP / ${m.jumlah_pertemuan}x</td>
                  <td class="text-center">${statusBadge[m.status] ?? m.status}</td>
                  <td class="text-center">
                    ${m.status === 'generated' || m.status === 'published'
                      ? `<a href="/learning-modules/${m.id}" class="btn btn-icon btn-sm btn-outline-primary" title="Lihat"><i class="ri ri-eye-line"></i></a>`
                      : ''}
                    ${m.status === 'failed'
                      ? `<button class="btn btn-icon btn-sm btn-outline-danger btn-retry" data-id="${m.id}" title="Coba Lagi"><i class="ri ri-refresh-line"></i></button>`
                      : ''}
                  </td>
                </tr>
              `;
            });
            $tbody.innerHTML = html;

            // kalau masih ada yang processing, polling ulang tiap 5 detik
            const stillProcessing = data.some(m => m.status === 'processing');
            clearTimeout(pollingTimer);
            if (stillProcessing) {
              pollingTimer = setTimeout(loadModul, 5000);
            }
          })
          .catch(() => {
            $tbody.innerHTML =
              `<tr><td colspan="7" class="text-center py-4 text-danger">Gagal memuat data.</td></tr>`;
          });
      }

      loadModul();
      $searchModul.addEventListener('input', debounce(loadModul, 400));
      $filterStatus.addEventListener('change', loadModul);

      function debounce(fn, delay) {
        let t;
        return (...args) => {
          clearTimeout(t);
          t = setTimeout(() => fn(...args), delay);
        };
      }

      // =========================================================
      // SUBMIT FORM
      // =========================================================
      const $form = document.getElementById('form_modul');
      const $submitBtn = document.getElementById('submit_modul');

      $form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData($form);

        $submitBtn.disabled = true;
        $submitBtn.innerHTML =
          '<span class="spinner-border spinner-border-sm me-2"></span>Menghasilkan modul... (mohon tunggu)';

        fetch('{{ route('learning-modules.store') }}', {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
          })
          .then(async res => {
            const data = await res.json();
            if (!res.ok) throw data;
            return data;
          })
          .then(data => {
            bootstrap.Offcanvas.getInstance(document.getElementById('offcanvas_modul'))?.hide();
            $form.reset();
            Swal.fire({
              icon: 'success',
              title: 'Modul ajar berhasil dibuat!',
              text: 'AI telah menyusun modul ajar sesuai data yang kamu masukkan.',
              confirmButtonText: 'Lihat Modul',
            }).then(result => {
              if (result.isConfirmed && data.redirect) {
                window.location.href = data.redirect;
              } else {
                loadModul();
              }
            });
          })
          .catch(err => {
            let message = err.message || 'Terjadi kesalahan saat membuat modul ajar.';
            if (err.errors) {
              message = Object.values(err.errors).flat().join('<br>');
            }
            Swal.fire({
              icon: 'error',
              title: 'Gagal',
              html: message
            });
          })
          .finally(() => {
            $submitBtn.disabled = false;
            $submitBtn.innerHTML = '<i class="ri ri-magic-line me-1"></i> Buat dengan AI';
          });
      });

      // =========================================================
      // RETRY (untuk modul yang gagal)
      // =========================================================
      $tbody.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-retry');
        if (!btn) return;

        const id = btn.dataset.id;
        btn.disabled = true;

        fetch(`/learning-modules/${id}/retry`, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Accept': 'application/json',
            },
          })
          .then(res => res.json())
          .then(() => loadModul())
          .catch(() => {
            Swal.fire({
              icon: 'error',
              title: 'Gagal retry modul ajar.'
            });
            btn.disabled = false;
          });
      });

    });
  </script>
@endsection
