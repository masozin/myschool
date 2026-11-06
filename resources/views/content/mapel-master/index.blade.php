@extends('layouts/layoutMaster')

@section('title', 'Data Master — Mata Pelajaran')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">Data Master Mata Pelajaran</h5>
      <button class="btn btn-primary btn-sm waves-effect waves-light" data-bs-toggle="offcanvas"
        data-bs-target="#offcanvas_mapel" id="btn-tambah">
        <i class="icon-base ri ri-add-line me-1"></i> Tambah
      </button>
    </div>
    <div class="card-body">
      <div class="table-responsive text-nowrap">
        <table class="table table-sm table-hover mb-0">
          <thead>
            <tr>
              <th>No</th>
              <th>Nama Mata Pelajaran</th>
              <th>Singkatan</th>
              <th>Sub Nama</th>
              <th>Kelompok</th>
              <th>Jenjang</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody id="tbody_mapel">
            <tr>
              <td colspan="7" class="text-center py-4">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>Memuat data...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- ═══ OFFCANVAS ══════════════════════════════════════════════════════════ --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas_mapel">
    <div class="offcanvas-header border-bottom">
      <h5 class="offcanvas-title" id="offcanvas_label">Tambah Mata Pelajaran</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
      <form id="form_mapel">
        <input type="hidden" id="mapel_id">

        {{-- Kelompok --}}
        <div class="form-floating form-floating-outline mb-5">
          <select class="form-select" id="mapel_kelompok_id" name="mapel_kelompok_id">
            <option value="">— Pilih Kelompok —</option>
            @foreach ($mapelKelompok as $k)
              <option value="{{ $k->id }}">{{ $k->nama }}</option>
            @endforeach
            {{-- diisi JS --}}
          </select>
          <label>Kelompok Mapel</label>
        </div>

        {{-- Nama --}}
        <div class="form-floating form-floating-outline mb-5">
          <input type="text" class="form-control" id="mapel_nama" name="nama" placeholder="Pendidikan Agama Islam"
            maxlength="100" />
          <label>Nama Mata Pelajaran</label>
        </div>

        {{-- Singkatan --}}
        <div class="form-floating form-floating-outline mb-5">
          <input type="text" class="form-control" id="mapel_singkat" name="nama_singkat" placeholder="PAI"
            maxlength="20" />
          <label>Singkatan <small class="text-muted">(opsional)</small></label>
        </div>

        {{-- Sub Nama --}}
        <div class="form-floating form-floating-outline mb-5">
          <input type="text" class="form-control" id="mapel_sub" name="nama_sub_mapel" placeholder="Islam"
            maxlength="100" />
          <label>Sub Nama <small class="text-muted">(opsional — mis. pecahan agama: Islam, Kristen)</small></label>
        </div>

        {{-- Status --}}
        <div class="mb-5">
          <label class="switch">
            <input type="checkbox" class="switch-input" id="mapel_aktif" checked />
            <span class="switch-toggle-slider">
              <span class="switch-on"><i class="icon-base ri ri-check-line"></i></span>
              <span class="switch-off"><i class="icon-base ri ri-close-line"></i></span>
            </span>
            <span class="switch-label" id="label_aktif">Aktif</span>
          </label>
        </div>

        {{-- Jenjang ─ dynamic rows --}}
        <div class="mb-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label mb-0 fw-semibold">Jenjang & Rentang Kelas</label>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-jenjang">
              <i class="icon-base ri ri-add-line"></i> Tambah
            </button>
          </div>
          <div id="jenjang_rows"></div>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-primary me-2" id="btn-simpan">Simpan</button>
          <button type="reset" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">Batal</button>
        </div>
      </form>
    </div>
  </div>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const tbody = document.getElementById('tbody_mapel');
      const form = document.getElementById('form_mapel');
      const canvas = document.getElementById('offcanvas_mapel');

      let saveMode = 'add';
      let globalId = null;
      let tableData = [];
      let kelompokData = [];

      const jenjangOptions = ['SD', 'SMP', 'SMA', 'SMK'];

      // ─── Helper fetch ──────────────────────────────────────────────────────────
      async function apiFetch(url, method = 'GET', body = null) {
        const opts = {
          method,
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
        };
        if (body) opts.body = JSON.stringify(body);
        return fetch(url, opts);
      }

      // ─── Render tabel ──────────────────────────────────────────────────────────
      function renderTable(data) {
        if (!data.length) {
          tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Belum ada data.</td></tr>';
          return;
        }

        tbody.innerHTML = data.map((row, index) => {
          const badges = row.jenjang.length ?
            row.jenjang.map(j => `<span class="badge bg-label-info me-1">${j}</span>`).join('') :
            '<span class="text-muted">—</span>';

          const tooltip = row.jenjang_detail.map(j =>
            `${j.jenjang} (Kls ${j.kelas_mulai}–${j.kelas_selesai})`
          ).join(', ');

          return `
        <tr>
          <td><span class="badge bg-label-secondary">${index + 1}</span></td>
          <td>${row.nama ?? '—'}</td>
          <td>${row.nama_singkat ?? '—'}</td>
          <td>${row.nama_sub_mapel ?? '—'}</td>
          <td>
            <span class="fw-medium">${row.kelompok_nama ?? '—'}</span><br>
          </td>
          <td title="${tooltip}">${badges}</td>
          <td>
            <span class="badge ${row.is_aktif ? 'bg-label-success' : 'bg-label-secondary'}">
              ${row.is_aktif ? 'Aktif' : 'Tidak Aktif'}
            </span>
          </td>
          <td>
            <button class="btn btn-icon btn-text-primary btn-sm rounded-pill btn-edit"
              data-id="${row.id}">
              <i class="icon-base ri ri-edit-line icon-18px"></i>
            </button>
            <button class="btn btn-icon btn-text-danger btn-sm rounded-pill btn-hapus"
              data-id="${row.id}" data-nama="${row.nama}">
              <i class="icon-base ri ri-delete-bin-line icon-18px"></i>
            </button>
          </td>
        </tr>`;
        }).join('');

        // Re-bind event setelah render
        bindEditHapus();
      }

      // ─── Load data ─────────────────────────────────────────────────────────────
      async function loadData() {
        try {
          const res = await apiFetch('/mapel/table');
          tableData = await res.json();
          renderTable(tableData);
        } catch {
          tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Gagal memuat data.</td></tr>';
        }
      }

      loadData();

      // ─── Jenjang rows ──────────────────────────────────────────────────────────
      function buildJenjangRow(data = {}) {
        const opts = jenjangOptions.map(j =>
          `<option value="${j}" ${data.jenjang === j ? 'selected' : ''}>${j}</option>`
        ).join('');

        const div = document.createElement('div');
        div.className = 'row g-2 align-items-center mb-2 jenjang-row';
        div.innerHTML = `
      <div class="col-4">
        <select class="form-select form-select-sm">
          <option value="">Jenjang</option>${opts}
        </select>
      </div>
      <div class="col-3">
        <input type="number" class="form-control form-control-sm" placeholder="Kls Mulai"
          min="1" max="12" value="${data.kelas_mulai ?? ''}">
      </div>
      <div class="col-3">
        <input type="number" class="form-control form-control-sm" placeholder="Kls Selesai"
          min="1" max="12" value="${data.kelas_selesai ?? ''}">
      </div>
      <div class="col-2 text-end">
        <button type="button" class="btn btn-icon btn-sm btn-text-danger rounded-pill btn-remove-jenjang">
          <i class="icon-base ri ri-delete-bin-line"></i>
        </button>
      </div>`;

        div.querySelector('.btn-remove-jenjang').addEventListener('click', () => div.remove());
        return div;
      }

      document.getElementById('btn-add-jenjang').addEventListener('click', () => {
        document.getElementById('jenjang_rows').appendChild(buildJenjangRow());
      });

      function getJenjangPayload() {
        return [...document.querySelectorAll('.jenjang-row')].map(row => {
          const [sel, mulai, selesai] = row.querySelectorAll('select, input');
          return {
            jenjang: sel.value,
            kelas_mulai: parseInt(mulai.value),
            kelas_selesai: parseInt(selesai.value),
          };
        });
      }

      function clearJenjangRows() {
        document.getElementById('jenjang_rows').innerHTML = '';
      }

      // ─── Switch aktif label ────────────────────────────────────────────────────
      document.getElementById('mapel_aktif').addEventListener('change', function() {
        document.getElementById('label_aktif').innerText = this.checked ? 'Aktif' : 'Tidak Aktif';
      });

      // ─── Tombol Tambah ─────────────────────────────────────────────────────────
      document.getElementById('btn-tambah').addEventListener('click', () => {
        saveMode = 'add';
        globalId = null;
        form.reset();
        clearJenjangRows();
        document.getElementById('offcanvas_label').innerText = 'Tambah Mata Pelajaran';
        document.getElementById('label_aktif').innerText = 'Aktif';
        document.getElementById('mapel_aktif').checked = true;
      });

      // ─── Bind edit & hapus ──────────────────────────────────────────────────────
      function bindEditHapus() {

        // Edit
        document.querySelectorAll('.btn-edit').forEach(btn => {
          btn.addEventListener('click', function() {
            const data = tableData.find(r => String(r.id) === String(this.dataset.id));
            if (!data) return;

            saveMode = 'edit';
            globalId = data.id;

            document.getElementById('offcanvas_label').innerText = 'Edit Mata Pelajaran';
            document.getElementById('mapel_id').value = data.id;
            document.getElementById('mapel_kelompok_id').value = data.kelompok_id ?? '';
            document.getElementById('mapel_nama').value = data.nama ?? '';
            document.getElementById('mapel_singkat').value = data.nama_singkat ?? '';
            document.getElementById('mapel_sub').value = data.nama_sub_mapel ?? '';
            document.getElementById('mapel_aktif').checked = !!data.is_aktif;
            document.getElementById('label_aktif').innerText = data.is_aktif ? 'Aktif' : 'Tidak Aktif';

            clearJenjangRows();
            const container = document.getElementById('jenjang_rows');
            data.jenjang_detail.forEach(j => container.appendChild(buildJenjangRow(j)));

            bootstrap.Offcanvas.getOrCreateInstance(canvas).show();
          });
        });

        // Hapus
        document.querySelectorAll('.btn-hapus').forEach(btn => {
          btn.addEventListener('click', async function() {
            const {
              id,
              nama
            } = this.dataset;
            const konfirm = await Swal.fire({
              icon: 'warning',
              title: 'Hapus Mata Pelajaran?',
              html: `<strong>${nama}</strong> akan dihapus permanen.`,
              showCancelButton: true,
              confirmButtonText: 'Ya, Hapus',
              cancelButtonText: 'Batal',
              customClass: {
                confirmButton: 'btn btn-danger me-3',
                cancelButton: 'btn btn-outline-secondary',
              },
              buttonsStyling: false,
            });
            if (!konfirm.isConfirmed) return;

            try {
              const res = await apiFetch(`/mapel/${id}`, 'DELETE');
              const data = await res.json();
              if (res.ok) {
                Swal.fire({
                  icon: 'success',
                  title: 'Berhasil',
                  text: data.message,
                  timer: 2000,
                  showConfirmButton: false
                });
                loadData();
              } else throw new Error(data.message);
            } catch (err) {
              Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: err.message
              });
            }
          });
        });
      }

      // ─── Submit form ───────────────────────────────────────────────────────────
      form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const btn = document.getElementById('btn-simpan');
        const payload = {
          mapel_kelompok_id: document.getElementById('mapel_kelompok_id').value,
          nama: document.getElementById('mapel_nama').value.trim(),
          nama_singkat: document.getElementById('mapel_singkat').value.trim() || null,
          nama_sub_mapel: document.getElementById('mapel_sub').value.trim() || null,
          is_aktif: document.getElementById('mapel_aktif').checked,
          jenjang: getJenjangPayload(),
        };

        // Validasi sederhana sisi klien
        if (!payload.mapel_kelompok_id || !payload.nama) {
          Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Kelompok, dan nama wajib diisi.'
          });
          return;
        }
        if (!payload.jenjang.length) {
          Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: 'Minimal satu jenjang harus ditambahkan.'
          });
          return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

        try {
          const url = saveMode === 'add' ? '/mapel' : `/mapel/${globalId}`;
          const method = saveMode === 'add' ? 'POST' : 'PUT';
          const res = await apiFetch(url, method, payload);
          const data = await res.json();

          if (res.ok) {
            Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              text: data.message,
              timer: 2000,
              showConfirmButton: false
            });
            bootstrap.Offcanvas.getInstance(canvas).hide();
            form.reset();
            clearJenjangRows();
            loadData();
          } else if (res.status === 422) {
            const errors = Object.values(data.errors).flat().join('\n');
            Swal.fire({
              icon: 'error',
              title: 'Validasi Gagal',
              text: errors
            });
          } else {
            throw new Error(data.message ?? 'Terjadi kesalahan.');
          }
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: err.message
          });
        } finally {
          btn.disabled = false;
          btn.innerHTML = 'Simpan';
        }
      });

    });
  </script>
@endsection
