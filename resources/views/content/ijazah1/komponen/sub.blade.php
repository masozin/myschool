@extends('layouts/layoutMaster')

@section('title', 'Sub-komponen — ' . $komponen->label)

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')

  <div class="mb-4 d-flex align-items-center gap-2">
    <a href="{{ route('ijazah.komponen.index', [
        'jenjang' => $komponen->jenjang,
        'tahun_pelajaran' => $komponen->tahun_pelajaran,
    ]) }}"
      class="btn btn-sm btn-outline-secondary">
      <i class="icon-base ri ri-arrow-left-line me-1"></i>Kembali
    </a>
    <span class="text-muted">/</span>
    <span class="text-muted small">{{ $komponen->label }}</span>
  </div>

  <div class="row justify-content-center">
    <div class="col-12 col-lg-7">

      {{-- Info Komponen Induk --}}
      <div class="card mb-4">
        <div class="card-body py-3 d-flex align-items-center gap-3">
          <div class="avatar">
            <span class="avatar-initial rounded-circle bg-label-primary">
              <i class="icon-base ri ri-stack-line"></i>
            </span>
          </div>
          <div class="flex-grow-1">
            <div class="fw-semibold">{{ $komponen->label }}</div>
            <small class="text-muted">
              {{ $komponen->jenjang }} · {{ $komponen->tahun_pelajaran }} ·
              Bobot <span class="badge bg-label-primary">{{ $komponen->bobot }}%</span>
            </small>
          </div>
        </div>
      </div>

      {{-- Alert info cara kerja --}}
      <div class="alert alert-info d-flex gap-2 mb-4">
        <i class="icon-base ri ri-information-line mt-1 flex-shrink-0"></i>
        <div class="small">
          Nilai komponen <strong>{{ $komponen->label }}</strong> akan dihitung otomatis dari
          <strong>rata-rata</strong> semua sub-komponen di bawah ini.<br>
          Contoh: Sem 7 = 85, Sem 8 = 88, Sem 9 = 80 → nilai komponen = <strong>84.33</strong>
        </div>
      </div>

      {{-- Form Tambah Sub-komponen --}}
      <div class="card mb-4">
        <div class="card-header border-bottom">
          <h6 class="mb-0"><i class="icon-base ri ri-add-line me-1"></i>Tambah Sub-komponen</h6>
        </div>
        <div class="card-body">
          <div class="row g-3 align-items-end">
            <div class="col-12 col-md-7">
              <label class="form-label form-label-sm">Label</label>
              <input type="text" id="input-label" class="form-control form-control-sm" placeholder="misal: Semester 7">
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label form-label-sm">Urutan</label>
              <input type="number" id="input-urutan" class="form-control form-control-sm" min="0"
                value="{{ $subKomponen->count() }}">
            </div>
            <div class="col-12 col-md-2">
              <button type="button" class="btn btn-primary btn-sm w-100" id="btn-tambah-sub">
                <i class="icon-base ri ri-add-line me-1"></i>Tambah
              </button>
            </div>
          </div>
          <div id="error-tambah" class="text-danger small mt-2 d-none"></div>
        </div>
      </div>

      {{-- Daftar Sub-komponen --}}
      <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Daftar Sub-komponen</h6>
          <span class="badge bg-label-primary" id="badge-jumlah">{{ $subKomponen->count() }} item</span>
        </div>

        <div id="list-sub">
          @if ($subKomponen->isEmpty())
            <div id="empty-state" class="text-center py-5">
              <i class="icon-base ri ri-inbox-line ri-36px text-muted d-block mb-2"></i>
              <p class="text-muted small mb-0">Belum ada sub-komponen. Tambahkan di atas.</p>
            </div>
          @else
            <div id="empty-state" class="text-center py-5 d-none">
              <i class="icon-base ri ri-inbox-line ri-36px text-muted d-block mb-2"></i>
              <p class="text-muted small mb-0">Belum ada sub-komponen. Tambahkan di atas.</p>
            </div>
          @endif

          <table class="table table-sm table-bordered mb-0 {{ $subKomponen->isEmpty() ? 'd-none' : '' }}" id="tabel-sub">
            <thead>
              <tr>
                <th width="50" class="text-center">No</th>
                <th>Label</th>
                <th width="80" class="text-center">Urutan</th>
                <th width="70" class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody id="tbody-sub">
              @foreach ($subKomponen as $i => $sub)
                <tr id="row-{{ $sub->id }}">
                  <td class="text-center">{{ $i + 1 }}</td>
                  <td>
                    <span class="label-text">{{ $sub->label }}</span>
                    <input type="text" class="form-control form-control-sm input-edit-label d-none"
                      value="{{ $sub->label }}" data-id="{{ $sub->id }}">
                  </td>
                  <td class="text-center">
                    <span class="urutan-text">{{ $sub->urutan }}</span>
                    <input type="number" class="form-control form-control-sm input-edit-urutan d-none"
                      value="{{ $sub->urutan }}" data-id="{{ $sub->id }}" min="0"
                      style="width:60px;margin:auto">
                  </td>
                  <td class="text-center">
                    <div class="d-flex gap-1 justify-content-center">
                      <button class="btn btn-sm btn-icon btn-text-primary rounded-pill btn-edit-sub"
                        data-id="{{ $sub->id }}" title="Edit">
                        <i class="icon-base ri ri-edit-line icon-18px"></i>
                      </button>
                      <button class="btn btn-sm btn-icon btn-text-danger rounded-pill btn-hapus-sub"
                        data-id="{{ $sub->id }}" data-label="{{ $sub->label }}" title="Hapus">
                        <i class="icon-base ri ri-delete-bin-line icon-18px"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        {{-- Footer: info kalkulasi --}}
        @if ($subKomponen->isNotEmpty())
          <div class="card-footer py-2">
            <small class="text-muted">
              <i class="icon-base ri ri-function-line me-1"></i>
              Nilai komponen = ({{ $subKomponen->pluck('label')->join(' + ') }}) ÷ {{ $subKomponen->count() }}
            </small>
          </div>
        @endif
      </div>

    </div>
  </div>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const komponenId = {{ $komponen->id }};
      const baseUrl = `/ijazah/komponen/${komponenId}/sub`;

      // ── Helper fetch ──────────────────────────────────────────────────────
      async function apiFetch(url, method = 'POST', body = null) {
        return fetch(url, {
          method,
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: body ? JSON.stringify(body) : null,
        });
      }

      // ── Update badge + tampilkan/sembunyikan tabel ───────────────────────
      function updateUI() {
        const rows = document.querySelectorAll('#tbody-sub tr');
        const n = rows.length;
        document.getElementById('badge-jumlah').textContent = n + ' item';

        if (n === 0) {
          document.getElementById('empty-state').classList.remove('d-none');
          document.getElementById('tabel-sub').classList.add('d-none');
        } else {
          document.getElementById('empty-state').classList.add('d-none');
          document.getElementById('tabel-sub').classList.remove('d-none');
        }

        // Renomor baris
        rows.forEach((row, i) => {
          row.cells[0].textContent = i + 1;
        });
      }

      // ── Tambah sub-komponen ────────────────────────────────────────────────
      document.getElementById('btn-tambah-sub').addEventListener('click', async function() {
        const label = document.getElementById('input-label').value.trim();
        const urutan = parseInt(document.getElementById('input-urutan').value) || 0;
        const errEl = document.getElementById('error-tambah');

        if (!label) {
          errEl.textContent = 'Label wajib diisi.';
          errEl.classList.remove('d-none');
          return;
        }
        errEl.classList.add('d-none');

        const btn = this;
        const oriTxt = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        const res = await apiFetch(baseUrl, 'POST', {
          label,
          urutan
        });
        const data = await res.json();

        btn.disabled = false;
        btn.innerHTML = oriTxt;

        if (!res.ok) {
          errEl.textContent = data.message ?? 'Gagal menambahkan.';
          errEl.classList.remove('d-none');
          return;
        }

        // Tambah baris ke tabel
        const tbody = document.getElementById('tbody-sub');
        const sub = data.sub_komponen;
        const tr = document.createElement('tr');
        tr.id = `row-${sub.id}`;
        tr.innerHTML = `
          <td class="text-center"></td>
          <td>
            <span class="label-text">${sub.label}</span>
            <input type="text" class="form-control form-control-sm input-edit-label d-none"
              value="${sub.label}" data-id="${sub.id}">
          </td>
          <td class="text-center">
            <span class="urutan-text">${sub.urutan}</span>
            <input type="number" class="form-control form-control-sm input-edit-urutan d-none"
              value="${sub.urutan}" data-id="${sub.id}" min="0" style="width:60px;margin:auto">
          </td>
          <td class="text-center">
            <div class="d-flex gap-1 justify-content-center">
              <button class="btn btn-sm btn-icon btn-text-primary rounded-pill btn-edit-sub"
                data-id="${sub.id}" title="Edit">
                <i class="icon-base ri ri-edit-line icon-18px"></i>
              </button>
              <button class="btn btn-sm btn-icon btn-text-danger rounded-pill btn-hapus-sub"
                data-id="${sub.id}" data-label="${sub.label}" title="Hapus">
                <i class="icon-base ri ri-delete-bin-line icon-18px"></i>
              </button>
            </div>
          </td>`;
        tbody.appendChild(tr);

        // Reset form + update urutan default
        document.getElementById('input-label').value = '';
        document.getElementById('input-urutan').value = tbody.children.length;
        updateUI();
      });

      // ── Edit inline ────────────────────────────────────────────────────────
      let editingId = null;

      document.addEventListener('click', async function(e) {
        const btn = e.target.closest('.btn-edit-sub');
        if (!btn) return;

        const id = parseInt(btn.dataset.id);
        const row = document.getElementById(`row-${id}`);

        if (editingId === id) {
          // Simpan
          const newLabel = row.querySelector('.input-edit-label').value.trim();
          const newUrutan = parseInt(row.querySelector('.input-edit-urutan').value) || 0;

          if (!newLabel) return;

          const res = await apiFetch(`${baseUrl}/${id}`, 'PUT', {
            label: newLabel,
            urutan: newUrutan
          });
          const data = await res.json();

          if (res.ok) {
            row.querySelector('.label-text').textContent = newLabel;
            row.querySelector('.urutan-text').textContent = newUrutan;
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Gagal',
              text: data.message
            });
          }

          // Keluar mode edit
          row.querySelector('.label-text').classList.remove('d-none');
          row.querySelector('.input-edit-label').classList.add('d-none');
          row.querySelector('.urutan-text').classList.remove('d-none');
          row.querySelector('.input-edit-urutan').classList.add('d-none');
          btn.innerHTML = '<i class="icon-base ri ri-edit-line icon-18px"></i>';
          editingId = null;
        } else {
          // Masuk mode edit
          if (editingId) {
            // Batalkan edit baris lain
            const prevRow = document.getElementById(`row-${editingId}`);
            if (prevRow) {
              prevRow.querySelector('.label-text').classList.remove('d-none');
              prevRow.querySelector('.input-edit-label').classList.add('d-none');
              prevRow.querySelector('.urutan-text').classList.remove('d-none');
              prevRow.querySelector('.input-edit-urutan').classList.add('d-none');
              prevRow.querySelector('.btn-edit-sub').innerHTML =
                '<i class="icon-base ri ri-edit-line icon-18px"></i>';
            }
          }
          row.querySelector('.label-text').classList.add('d-none');
          row.querySelector('.input-edit-label').classList.remove('d-none');
          row.querySelector('.urutan-text').classList.add('d-none');
          row.querySelector('.input-edit-urutan').classList.remove('d-none');
          row.querySelector('.input-edit-label').focus();
          btn.innerHTML = '<i class="icon-base ri ri-save-line icon-18px"></i>';
          editingId = id;
        }
      });

      // ── Hapus ──────────────────────────────────────────────────────────────
      document.addEventListener('click', async function(e) {
        const btn = e.target.closest('.btn-hapus-sub');
        if (!btn) return;

        const konfirm = await Swal.fire({
          icon: 'warning',
          title: 'Hapus Sub-komponen?',
          text: `"${btn.dataset.label}" akan dihapus.`,
          showCancelButton: true,
          confirmButtonText: 'Ya, Hapus',
          cancelButtonText: 'Batal',
          customClass: {
            confirmButton: 'btn btn-danger me-3',
            cancelButton: 'btn btn-outline-secondary'
          },
          buttonsStyling: false,
        });
        if (!konfirm.isConfirmed) return;

        const res = await apiFetch(`${baseUrl}/${btn.dataset.id}`, 'DELETE');
        const data = await res.json();

        if (res.ok) {
          document.getElementById(`row-${btn.dataset.id}`)?.remove();
          updateUI();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: data.message
          });
        }
      });
    });
  </script>
@endsection
