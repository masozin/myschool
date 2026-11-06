@extends('layouts/layoutMaster')

@section('title', 'Data Master — Mata Pelajaran')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/nouislider/nouislider.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sortablejs/sortable.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/nouislider/nouislider.js'])
@endsection

@section('page-style')
  <style>
    tbody tr {
      cursor: grab;
    }

    tbody tr.sortable-ghost {
      opacity: .4;
      background: #e3f2fd;
    }

    tbody tr.sortable-chosen {
      background: #fff9c4;
    }

    tbody tr.sortable-drag {
      cursor: grabbing;
    }
  </style>
@endsection

@section('content')

  {{-- Header --}}
  <div class="row mb-4 g-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body d-flex align-items-center gap-4 py-4">
          <div class="avatar avatar-xl flex-shrink-0">
            <span class="avatar-initial rounded-circle bg-label-primary">
              <i class="icon-base ri ri-book-2-line ri-28px"></i>
            </span>
          </div>
          <div>
            <h4 class="mb-1">Data Mata Pelajaran</h4>
            <small class="text-muted mb-0">
              @if ($isAdmin)
                Mapping mapel pokok semua jenjang.
              @else
                Mapping mapel pokok adalah mapping mapel bawaan. Anda bisa menambahkan mapping mapel sesuai
                mapel yang diajarkan di sekolah Anda jika belum ada di daftar.
              @endif
            </small>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Filter Jenjang & Tingkat --}}
  <div class="card mb-4">
    <div class="card-body py-3">
      <div class="d-flex gap-5 flex-wrap align-items-end">

        {{-- Filter Jenjang (Admin only) --}}
        @if ($isAdmin)
          <div>
            <label class="form-label mb-1">Jenjang</label>
            <div class="d-flex gap-2" id="filter-jenjang-wrapper">
              @foreach ($jenjangList as $j)
                <button type="button"
                  class="btn btn-sm btn-filter-jenjang {{ $jenjangDipilih === $j ? 'btn-primary' : 'btn-outline-secondary' }}"
                  data-jenjang="{{ $j }}">
                  {{ $j }}
                </button>
              @endforeach
            </div>
          </div>
        @endif

        {{-- Filter Tingkat --}}
        <div>
          <select id="filter-tingkat" class="form-select form-select-sm" style="min-width: 140px;">
            @foreach ($tingkatList as $t)
              <option value="{{ $t }}" {{ $tingkatDipilih === $t ? 'selected' : '' }}>
                Kelas {{ $t }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="ms-auto d-flex gap-2">
          @if ($isAdmin)
            <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal"
              data-bs-target="#modal-salin">
              <i class="icon-base ri ri-file-copy-line me-1"></i>Salin Semester
            </button>
          @endif

          @if ($isOperator)
            <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal"
              data-bs-target="#modal-salin-tambahan">
              <i class="icon-base ri ri-file-copy-line me-1"></i>Salin dari Semester Lalu
            </button>
          @endif

          <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-mapel"
            id="btn-tambah-mapel">
            <i class="icon-base ri ri-add-line me-1"></i>Tambah Mapel{{ $isOperator ? ' Lokal' : '' }}
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- ═══ Area Tabel (diisi oleh renderTabel) ══════════════════════════════ --}}
  <div id="tabel-area">
    {{-- Skeleton loader awal --}}
    <div class="card mb-4" id="skeleton-loader">
      <div class="card-body text-center py-5 text-muted">
        <div class="spinner-border text-primary mb-3" role="status"></div>
        <p class="mb-0">Memuat data mata pelajaran…</p>
      </div>
    </div>
  </div>

  {{-- ═══ MODAL: Tambah / Edit Mapel ═══════════════════════════════════════ --}}
  <div class="modal fade" id="modal-mapel" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modal-mapel-title">Tambah Mata Pelajaran</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="mapel-id">
          <input type="hidden" id="mapel-jenjang" value="{{ $jenjangDipilih }}">

          <div class="mb-4" id="wrap-mapel-master">
            <label class="form-label">Mata Pelajaran (Master) <span class="text-danger">*</span></label>
            <select id="mapel-master" class="form-select" required>
              <option value="">— Pilih Mata Pelajaran —</option>
              @foreach ($masterMapelList as $master)
                <option value="{{ $master->id }}"
                  data-kelompok="{{ $master->mapel_kelompok_id }}"
                  data-kurikulum="{{ $master->kurikulum->count() === 1 ? $master->kurikulum->first()->id : '' }}">
                  {{ $master->kelompok?->nama ?? 'Tanpa Kelompok' }} —
                  {{ $master->nama }}
                  {{ $master->nama_sub_mapel ? ' — ' . $master->nama_sub_mapel : '' }}
                </option>
              @endforeach
            </select>
            @if ($isOperator)
              <button type="button" class="btn btn-link btn-sm p-0 mt-1" id="btn-toggle-lokal">
                Tidak ada di daftar? Buat mapel baru
              </button>
            @endif

            <div class="row g-3 mt-2">
              <div class="col-6">
                <label class="form-label">Kelompok Penempatan</label>
                <select id="mapel-kelompok" class="form-select">
                  @foreach ($mapelKelompok as $k)
                    <option value="{{ $k->id }}">{{ $k->nama }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-6">
                <label class="form-label">Kurikulum</label>
                <select id="mapel-kurikulum" class="form-select">
                  <option value="">Semua Kurikulum</option>
                  @foreach ($kurikulumList as $kur)
                    <option value="{{ $kur->id }}">{{ $kur->nama }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div>

          <div class="mb-2" id="wrap-mapel-tingkat">
            <label class="form-label">Tingkat / Kelas <span class="text-danger">*</span></label>
            <input type="text" id="mapel-tingkat" class="form-control" required disabled>
          </div>

          @if ($isOperator)
            <div id="wrap-mapel-lokal" class="d-none">
              <div class="mb-4">
                <label class="form-label">Kelompok Mapel <span class="text-danger">*</span></label>
                <select id="lokal-kelompok" class="form-select">
                  <option value="">— Pilih Kelompok —</option>
                  @foreach ($mapelKelompok as $k)
                    <option value="{{ $k->id }}">{{ $k->nama }}</option>
                  @endforeach
                </select>
              </div>
              <div class="mb-4">
                <label class="form-label">Nama Mata Pelajaran <span class="text-danger">*</span></label>
                <input type="text" id="lokal-nama" class="form-control" maxlength="100" placeholder="Bahasa Daerah">
              </div>
              <div class="mb-4">
                <label class="form-label">Singkatan <small class="text-muted">(opsional)</small></label>
                <input type="text" id="lokal-singkat" class="form-control" maxlength="20" placeholder="BD">
              </div>
              <div class="mb-4">
                <label class="form-label">Sub Nama <small class="text-muted">(opsional)</small></label>
                <input type="text" id="lokal-sub" class="form-control" maxlength="100" placeholder="Bahasa Jawa">
              </div>
              <div class="mb-4">
                <label class="form-label">Rentang Tingkat <span class="text-danger">*</span></label>
                <div class="d-flex justify-content-between mb-2">
                  <span class="badge bg-label-primary" id="lokal-tingkat-label-mulai">Kelas -</span>
                  <span class="badge bg-label-primary" id="lokal-tingkat-label-selesai">Kelas -</span>
                </div>
                <div id="lokal-tingkat-slider" class="mt-3 mb-4 mx-1"></div>
                <input type="hidden" id="lokal-tingkat-mulai">
                <input type="hidden" id="lokal-tingkat-selesai">
                <small class="text-muted">Geser untuk memilih rentang kelas tempat mapel ini berlaku.</small>
              </div>
              <button type="button" class="btn btn-link btn-sm p-0 mt-1" id="btn-toggle-master">
                ← Pilih dari daftar master
              </button>
            </div>
          @endif
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-primary" id="btn-simpan-mapel">
            <i class="icon-base ri ri-save-line me-1"></i>Simpan
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- ═══ MODAL: Salin Semester (Hanya Admin) ══════════════════════════════ --}}
  @if ($isAdmin)
    <div class="modal fade" id="modal-salin" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Salin Mapel ke Semester Lain</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info d-flex gap-2 mb-4">
              <i class="icon-base ri ri-information-line mt-1"></i>
              <span>Semua mapel pokok dari semester sumber akan diduplikat ke semester tujuan.
                Mapel muatan lokal (tambahan) <strong>tidak</strong> ikut disalin.</span>
            </div>

            <div class="mb-4">
              <label class="form-label">Jenjang</label>
              <input type="text" class="form-control" id="salin-jenjang-display" value="{{ $jenjangDipilih }}"
                readonly>
              <input type="hidden" id="salin-jenjang" value="{{ $jenjangDipilih }}">
            </div>

            <div class="mb-4">
              <label class="form-label">Dari Semester <span class="text-danger">*</span></label>
              <select id="salin-dari" class="form-select">
                @foreach ($semesterList as $s)
                  <option value="{{ $s->id }}" {{ $semester->id === $s->id ? 'selected' : '' }}>
                    {{ $s->tahun_pelajaran }} — Semester {{ $s->semester }}
                  </option>
                @endforeach
              </select>
            </div>

            <div class="mb-2">
              <label class="form-label">Ke Semester <span class="text-danger">*</span></label>
              <select id="salin-ke" class="form-select">
                @foreach ($semesterList as $s)
                  <option value="{{ $s->id }}">
                    {{ $s->tahun_pelajaran }} — Semester {{ $s->semester }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-info text-white" id="btn-salin">
              <i class="icon-base ri ri-file-copy-line me-1"></i>Salin Sekarang
            </button>
          </div>
        </div>
      </div>
    </div>
  @endif

  {{-- ═══ MODAL: Salin Mapel Tambahan (Operator) ═══════════════════════════ --}}
  @if ($isOperator)
    <div class="modal fade" id="modal-salin-tambahan" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Salin Mapel Tambahan dari Semester Lalu</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted small mb-4">
              Mapel tambahan (lokal) milik sekolah Anda dari semester asal disalin ke semester
              aktif ini; yang sudah ada dilewati. Mapel pokok tidak perlu disalin — itu bagian Admin.
            </p>
            <label class="form-label">Semester Asal</label>
            <select id="salin-tambahan-dari" class="form-select">
              <option value="">— Pilih Semester —</option>
              @foreach ($semesterList as $smt)
                @if ($smt->id !== $semester?->id)
                  <option value="{{ $smt->id }}">{{ $smt->tahun_pelajaran }} — {{ $smt->semester }}</option>
                @endif
              @endforeach
            </select>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-info text-white" id="btn-salin-tambahan">
              <i class="icon-base ri ri-file-copy-line me-1"></i>Salin Sekarang
            </button>
          </div>
        </div>
      </div>
    </div>
  @endif

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const baseUrl = '/mapel-mapping';
      const dataUrl = '/mapel-mapping/table';

      const isOperator = @json($isOperator);
      const tingkatPerJenjang = @json($tingkatPerJenjang);

      // ====================== STATE UTAMA ======================
      let state = {
        jenjang: '',
        kelompok: []
      };

      let activeJenjang = @json($jenjangDipilih);
      let activeTingkat = @json($tingkatDipilih);

      // ====================== CORE FUNCTIONS ======================
      async function loadData() {
        const area = document.getElementById('tabel-area');
        showLoading(area);

        try {
          const params = new URLSearchParams({
            jenjang: activeJenjang
          });
          if (activeTingkat) params.append('tingkat', activeTingkat);

          const res = await fetch(`${dataUrl}?${params}`, {
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrf
            }
          });

          if (!res.ok) throw new Error('Gagal memuat data');

          state = await res.json(); // Update full state
          renderTabel();

        } catch (err) {
          showError(area, err.message);
        }
      }

      function refreshTable() {
        renderTabel();
      }

      function showLoading(area) {
        area.innerHTML = `
          <div class="card mb-4">
            <div class="card-body text-center py-5 text-muted">
              <div class="spinner-border text-primary mb-3" role="status"></div>
              <p class="mb-0">Memuat data…</p>
            </div>
          </div>`;
      }

      function showError(area, message) {
        area.innerHTML = `
          <div class="card">
            <div class="card-body text-center py-5 text-danger">
              <i class="icon-base ri ri-error-warning-line ri-48px mb-3 d-block"></i>
              <p>${message}</p>
            </div>
          </div>`;
      }

      // ====================== RENDER FUNCTIONS ======================
      function renderTabel() {
        const area = document.getElementById('tabel-area');

        if (!state.kelompok?.length) {
          area.innerHTML = `
            <div class="card">
              <div class="card-body text-center py-5 text-muted">
                <i class="icon-base ri ri-error-warning-line ri-48px mb-3 d-block"></i>
                <p>Belum ada kelompok mapel untuk jenjang <strong>${state.jenjang || activeJenjang}</strong>.</p>
              </div>
            </div>`;
          return;
        }

        let html = '';

        for (const grp of state.kelompok) {
          html += `
            <div class="card mb-4">
              <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                <div>
                  <h6 class="mb-0">${escHtml(grp.kelompok)}</h6>
                  <small class="text-muted">${grp.count} mata pelajaran</small>
                </div>
                <span class="badge bg-label-primary">${escHtml(grp.jenjang)}</span>
              </div>
              <div class="card-datatable text-nowrap">
                <table class="table table-sm table-bordered table-hover mb-0">
                  <thead>
                    <tr>
                      <th width="60" class="text-center">Urutan</th>
                      <th width="80" class="text-center">Kelas</th>
                      <th>Nama Mata Pelajaran</th>
                      <th>Sub Nama</th>
                      <th width="140" class="text-center">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${grp.rows.length
                      ? grp.rows.map(renderRow).join('')
                      : `
                                                                          <tr>
                                                                            <td colspan="5" class="text-center text-muted py-4">
                                                                              <i class="icon-base ri ri-inbox-line ri-24px mb-2 d-block"></i>
                                                                              Belum ada mapel untuk kelompok ini.
                                                                            </td>
                                                                          </tr>`}
                  </tbody>
                </table>
              </div>
            </div>`;
        }

        area.innerHTML = html;
        bindRowEvents();

        // Re-init tooltips
        area.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
          new bootstrap.Tooltip(el);
        });
      }

      function renderRow(row) {
        const lokBadge = row.jenis === 'tambahan' ?
          `<span class="badge bg-label-warning ms-2" style="font-size:.7rem;">Lokal</span>` :
          '';

        const nonaktifBadge = row.nonaktif ?
          `<span class="badge bg-label-secondary ms-2" style="font-size:.7rem;">Nonaktif</span>` :
          '';

        const subNama = row.nama_sub ?
          `<span class="text-muted">${escHtml(row.nama_sub)}</span>` :
          `<span class="text-muted fst-italic">—</span>`;

        let aksi = '';
        if (row.can_edit) {
          aksi = `
      <i class="icon-base ri ri-drag-move-line icon-18px text-muted me-1"
         title="Geser untuk ubah urutan" data-bs-toggle="tooltip"
         style="cursor:grab;"></i>
      <button class="btn btn-sm btn-icon btn-text-primary rounded-pill btn-edit"
        data-id="${row.id}" data-master="${row.master_id}"
        data-tingkat="${row.tingkat}" data-nama="${escAttr(row.nama)}"
        data-kelompok-id="${row.kelompok_id ?? ''}" data-kurikulum-id="${row.kurikulum_id ?? ''}"
        data-lokal="${row.lokal ? escAttr(JSON.stringify(row.lokal)) : ''}">
        <i class="icon-base ri ri-edit-line icon-18px"></i>
      </button>
      <button class="btn btn-sm btn-icon btn-text-danger rounded-pill btn-hapus"
        data-id="${row.id}" data-nama="${escAttr(row.nama)}">
        <i class="icon-base ri ri-delete-bin-line icon-18px"></i>
      </button>`;
        } else if (row.can_nonaktif) {
          aksi = `
      <button class="btn btn-sm btn-icon btn-text-${row.nonaktif ? 'success' : 'warning'} rounded-pill btn-nonaktif"
        data-id="${row.id}" data-nama="${escAttr(row.nama)}"
        title="${row.nonaktif ? 'Aktifkan kembali untuk sekolah saya' : 'Nonaktifkan untuk sekolah saya (tidak diajarkan)'}">
        <i class="icon-base ri ${row.nonaktif ? 'ri-eye-line' : 'ri-eye-off-line'} icon-18px"></i>
      </button>`;
        } else {
          aksi = `<span class="text-muted" title="Read-Only" data-bs-toggle="tooltip">
              <i class="icon-base ri ri-lock-line ri-18px"></i>
            </span>`;
        }

        return `
    <tr data-mapel-id="${row.id}"${row.nonaktif ? ' style="opacity:.55"' : ''}>
      <td class="text-center"><span class="badge bg-label-secondary urutan-badge">${row.urutan}</span></td>
      <td class="text-center"><span class="badge bg-label-info">Kelas ${row.tingkat}</span></td>
      <td>${escHtml(row.nama)}${lokBadge}${nonaktifBadge}</td>
      <td>${subNama}</td>
      <td><div class="d-flex gap-1 justify-content-center align-items-center">${aksi}</div></td>
    </tr>`;
      }

      // ====================== STATE MUTATION HELPERS ======================
      function updateLocalUrutan(id, direction) {
        for (const grp of state.kelompok) {
          const idx = grp.rows.findIndex(r => r.id == id);
          if (idx === -1) continue;

          if (direction === 'naik' && idx > 0) {
            [grp.rows[idx - 1], grp.rows[idx]] = [grp.rows[idx], grp.rows[idx - 1]];
          } else if (direction === 'turun' && idx < grp.rows.length - 1) {
            [grp.rows[idx], grp.rows[idx + 1]] = [grp.rows[idx + 1], grp.rows[idx]];
          } else {
            return false;
          }

          // Update nomor urutan
          grp.rows.forEach((r, i) => r.urutan = i + 1);
          return true;
        }
        return false;
      }

      function removeFromState(id) {
        for (const grp of state.kelompok) {
          grp.rows = grp.rows.filter(r => r.id != id);
          grp.count = grp.rows.length;
        }
        state.kelompok = state.kelompok.filter(grp => grp.rows.length > 0);
      }

      // Sinkronkan state.kelompok berdasarkan urutan ID baru dari DOM
      function syncStateUrutan(ids) {
        for (const grp of state.kelompok) {
          const idSet = new Set(ids.map(String));
          const affected = grp.rows.filter(r => idSet.has(String(r.id)));
          if (!affected.length) continue;

          // Susun ulang rows sesuai urutan ids
          const mapped = new Map(grp.rows.map(r => [String(r.id), r]));
          const ordered = ids.map(id => mapped.get(String(id))).filter(Boolean);

          // Ganti slice rows yang terdampak
          let oi = 0;
          grp.rows = grp.rows.map(r => idSet.has(String(r.id)) ? ordered[oi++] : r);
          grp.rows.forEach((r, i) => r.urutan = i + 1);
          break;
        }
      }

      // ====================== EVENT BINDING ======================
      function bindRowEvents() {
        // ── Drag & Drop per tbody ────────────────────────────────────────────
        document.querySelectorAll('.card-datatable tbody').forEach(tbody => {
          // Hanya aktifkan drag pada baris yang bisa di-edit
          if (!tbody.querySelector('.btn-edit')) return; // semua read-only, skip

          Sortable.create(tbody, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            handle: 'tr', // seluruh baris bisa di-drag
            filter: 'tr:not(:has(.btn-edit))',
            preventOnFilter: true,
            onEnd: async function(evt) {
              if (evt.oldIndex === evt.newIndex) return;

              // Kumpulkan urutan ID baru dari DOM — hanya baris yang
              // boleh digeser pengguna ini; baris pokok tidak ikut
              // terkirim untuk Operator (server menolaknya)
              const barisBisaGeser = [...tbody.querySelectorAll('tr[data-mapel-id]:has(.btn-edit)')];
              const ids = barisBisaGeser.map(tr => tr.dataset.mapelId);

              // Update badge urutan di DOM (optimistic)
              barisBisaGeser.forEach((tr, i) => {
                const badge = tr.querySelector('.urutan-badge');
                if (badge) badge.textContent = i + 1;
              });

              try {
                const res = await apiFetch(`${baseUrl}/reorder`, 'POST', {
                  ids
                });
                if (!res.ok) {
                  const data = await res.json();
                  throw new Error(data.message || 'Gagal menyimpan urutan.');
                }
                // Sync state.kelompok agar refreshTable() konsisten
                syncStateUrutan(ids);
                toastSukses('Urutan mapel berhasil disimpan.');
              } catch (err) {
                toastError(err.message);
                refreshTable(); // rollback tampilan
              }
            }
          });
        });

        // ── Edit ────────────────────────────────────────────────────────────
        document.querySelectorAll('.btn-edit').forEach(btn => {
          btn.addEventListener('click', function() {
            // Mapel lokal milik sekolah: yang diedit datanya
            // (nama, singkatan, sub nama, kelompok, rentang kelas)
            if (isOperator && this.dataset.lokal) {
              const lokal = JSON.parse(this.dataset.lokal);
              document.getElementById('modal-mapel-title').textContent = 'Edit Mata Pelajaran Lokal';
              tampilkanModeLokal(true);
              document.getElementById('lokal-kelompok').value = lokal.kelompok_id ?? '';
              document.getElementById('lokal-nama').value = lokal.nama ?? '';
              document.getElementById('lokal-singkat').value = lokal.nama_singkat ?? '';
              document.getElementById('lokal-sub').value = lokal.nama_sub ?? '';
              if (lokalSliderInstance && lokal.kelas_mulai && lokal.kelas_selesai) {
                lokalSliderInstance.set([lokal.kelas_mulai, lokal.kelas_selesai]);
              }
              const btnSimpan = document.getElementById('btn-simpan-mapel');
              btnSimpan.dataset.mode = 'edit-lokal';
              btnSimpan.dataset.masterLokal = lokal.master_id;
              new bootstrap.Modal(document.getElementById('modal-mapel')).show();
              return;
            }

            document.getElementById('modal-mapel-title').textContent =
              isOperator ? 'Edit Mata Pelajaran Lokal' : 'Edit Mata Pelajaran';
            document.getElementById('mapel-id').value = this.dataset.id;
            document.getElementById('mapel-master').value = this.dataset.master;
            document.getElementById('mapel-tingkat').value = this.dataset.tingkat;
            document.getElementById('mapel-kelompok').value = this.dataset.kelompokId ?? '';
            document.getElementById('mapel-kurikulum').value = this.dataset.kurikulumId ?? '';
            document.getElementById('btn-simpan-mapel').dataset.mode = 'edit';
            tampilkanModeMaster();
            new bootstrap.Modal(document.getElementById('modal-mapel')).show();
          });
        });

        // ── Nonaktif/aktifkan pokok untuk sekolah sendiri ─────────────────
        document.querySelectorAll('.btn-nonaktif').forEach(btn => {
          btn.addEventListener('click', async function() {
            try {
              const res = await apiFetch(`${baseUrl}/${this.dataset.id}/nonaktif`, 'POST', {});
              const data = await res.json();
              if (!res.ok) throw new Error(data.message || 'Gagal mengubah status mapel.');
              toastSukses(data.message);
              refreshTable();
            } catch (err) {
              toastError(err.message);
            }
          });
        });

        // ── Hapus ───────────────────────────────────────────────────────────
        document.querySelectorAll('.btn-hapus').forEach(btn => {
          btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            const nama = this.dataset.nama;

            const konfirm = await Swal.fire({
              icon: 'warning',
              title: 'Hapus Mata Pelajaran?',
              html: `Mata pelajaran <strong>${nama}</strong> akan dihapus permanen.<br>
               <small class="text-muted">Data nilai yang sudah ada akan ikut terhapus.</small>`,
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
              const res = await apiFetch(`${baseUrl}/${id}`, 'DELETE');
              if (res.ok) {
                const data = await res.json();
                toastSukses(data.message);
                removeFromState(id);
                refreshTable();
              } else {
                const data = await res.json();
                throw new Error(data.message);
              }
            } catch (err) {
              toastError(err.message);
            }
          });
        });

        // Re-init tooltips
        document.getElementById('tabel-area')
          ?.querySelectorAll('[data-bs-toggle="tooltip"]')
          .forEach(el => new bootstrap.Tooltip(el));
      }

      // ====================== FILTERS ======================
      document.querySelectorAll('.btn-filter-jenjang').forEach(btn => {
        btn.addEventListener('click', function() {
          document.querySelectorAll('.btn-filter-jenjang').forEach(b => {
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline-secondary');
          });
          this.classList.add('btn-primary');
          this.classList.remove('btn-outline-secondary');

          activeJenjang = this.dataset.jenjang;
          document.getElementById('mapel-jenjang').value = activeJenjang;

          updateTingkatOptions(activeJenjang);

          // set tingkat aktif ke pilihan pertama yang valid utk jenjang baru
          const allowed = tingkatPerJenjang[activeJenjang] || [];
          activeTingkat = allowed[0] ?? '';
          const filterSelect = document.getElementById('filter-tingkat');
          if (filterSelect) filterSelect.value = activeTingkat;

          loadData(); // ← diaktifkan
        });
      });

      document.getElementById('filter-tingkat')?.addEventListener('change', function() {
        activeTingkat = this.value;
        loadData();
      });

      function updateTingkatOptions(jenjang) {
        const allowed = tingkatPerJenjang[jenjang] || [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12];
        const filterSelect = document.getElementById('filter-tingkat');
        if (filterSelect) {
          let html = '<option value="" disabled selected>pilih tingkat</option>';
          allowed.forEach(t => {
            html += `<option value="${t}">Kelas ${t}</option>`;
          });
          filterSelect.innerHTML = html;
        }
      }

      // Pilihan master mengisi bawaan kelompok & kurikulum penempatan
      document.getElementById('mapel-master')?.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        if (!opt) return;
        const kelompokEl = document.getElementById('mapel-kelompok');
        const kurikulumEl = document.getElementById('mapel-kurikulum');
        if (kelompokEl && opt.dataset.kelompok) kelompokEl.value = opt.dataset.kelompok;
        if (kurikulumEl) kurikulumEl.value = opt.dataset.kurikulum ?? '';
      });

      // ====================== MODAL & SIMPAN ======================
      document.getElementById('btn-tambah-mapel')?.addEventListener('click', function() {
        document.getElementById('modal-mapel-title').textContent =
          isOperator ? 'Tambah Mata Pelajaran Lokal' : 'Tambah Mata Pelajaran';

        document.getElementById('mapel-id').value = '';
        document.getElementById('mapel-master').value = '';
        document.getElementById('mapel-tingkat').value = activeTingkat || '';
        document.getElementById('btn-simpan-mapel').dataset.mode = 'tambah';
        tampilkanModeMaster();
      });

      document.getElementById('btn-simpan-mapel')?.addEventListener('click', async function() {
        const mode = this.dataset.mode ?? 'tambah';
        const submode = this.dataset.submode ?? 'master';

        if (submode === 'lokal' && (mode === 'tambah' || mode === 'edit-lokal')) {
          await simpanMapelLokal(this);
          return;
        }

        const mapelId = document.getElementById('mapel-id').value;
        const master = document.getElementById('mapel-master').value;
        const tingkat = document.getElementById('mapel-tingkat').value;
        const jenjang = document.getElementById('mapel-jenjang').value;

        if (!master || !tingkat) {
          toastError('Mata pelajaran dan kelas wajib dipilih.');
          return;
        }

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

        try {
          let res;
          if (mode === 'tambah') {
            res = await apiFetch(baseUrl, 'POST', {
              mapel_master_id: master,
              tingkat,
              jenjang,
              mapel_kelompok_id: document.getElementById('mapel-kelompok').value || null,
              kurikulum_id: document.getElementById('mapel-kurikulum').value || null
            });
          } else {
            res = await apiFetch(`${baseUrl}/${mapelId}`, 'PUT', {
              mapel_master_id: master,
              tingkat,
              mapel_kelompok_id: document.getElementById('mapel-kelompok').value || null,
              kurikulum_id: document.getElementById('mapel-kurikulum').value || null
            });
          }

          const data = await res.json();
          if (res.ok) {
            toastSukses(data.message);
            bootstrap.Modal.getInstance(document.getElementById('modal-mapel')).hide();
            setTimeout(() => loadData(), 800);
          } else {
            throw new Error(data.message ?? 'Gagal menyimpan.');
          }
        } catch (err) {
          toastError(err.message);
        } finally {
          this.disabled = false;
          this.innerHTML = '<i class="icon-base ri ri-save-line me-1"></i>Simpan';
        }
      });

      // ====================== TOGGLE MASTER / LOKAL ======================
      // ====================== SLIDER TINGKAT LOKAL ======================
      let lokalSliderInstance = null;

      function bangunLokalTingkatSlider() {
        const allowed = tingkatPerJenjang[activeJenjang] || [];
        if (!allowed.length) return;

        const min = allowed[0];
        const max = allowed[allowed.length - 1];
        const defaultVal = activeTingkat && allowed.includes(parseInt(activeTingkat)) ?
          parseInt(activeTingkat) :
          min;

        const el = document.getElementById('lokal-tingkat-slider');
        if (!el) return;

        // Hancurkan instance lama biar tidak numpuk saat modal dibuka berkali-kali
        if (lokalSliderInstance) {
          lokalSliderInstance.destroy();
          lokalSliderInstance = null;
        }

        if (min === max) {
          // Cuma 1 tingkat tersedia utk jenjang ini (kasus SMA/SMK: 10-12 masih 3 opsi, tapi jaga-jaga)
          document.getElementById('lokal-tingkat-mulai').value = min;
          document.getElementById('lokal-tingkat-selesai').value = max;
          document.getElementById('lokal-tingkat-label-mulai').textContent = `Kelas ${min}`;
          document.getElementById('lokal-tingkat-label-selesai').textContent = `Kelas ${max}`;
          el.innerHTML = '<div class="text-muted small">Hanya tersedia Kelas ' + min + ' untuk jenjang ini.</div>';
          return;
        }

        lokalSliderInstance = noUiSlider.create(el, {
          start: [defaultVal, defaultVal],
          connect: true,
          step: 1,
          range: {
            min,
            max
          },
          tooltips: false,
          format: {
            to: v => Math.round(v),
            from: v => Math.round(v),
          },
        });

        lokalSliderInstance.on('update', function(values) {
          const [mulai, selesai] = values.map(v => parseInt(v));
          document.getElementById('lokal-tingkat-mulai').value = mulai;
          document.getElementById('lokal-tingkat-selesai').value = selesai;
          document.getElementById('lokal-tingkat-label-mulai').textContent = `Kelas ${mulai}`;
          document.getElementById('lokal-tingkat-label-selesai').textContent = `Kelas ${selesai}`;
        });
      }

      function tampilkanModeMaster() {
        document.getElementById('wrap-mapel-master')?.classList.remove('d-none');
        document.getElementById('wrap-mapel-tingkat')?.classList.remove('d-none');
        document.getElementById('wrap-mapel-lokal')?.classList.add('d-none');
        document.getElementById('btn-simpan-mapel').dataset.submode = 'master';
      }

      function tampilkanModeLokal(untukEdit = false) {
        document.getElementById('wrap-mapel-master')?.classList.add('d-none');
        document.getElementById('wrap-mapel-tingkat')?.classList.add('d-none');
        document.getElementById('wrap-mapel-lokal')?.classList.remove('d-none');
        if (!untukEdit) {
          document.getElementById('lokal-kelompok').value = '';
          document.getElementById('lokal-nama').value = '';
          document.getElementById('lokal-singkat').value = '';
          document.getElementById('lokal-sub').value = '';
        }
        bangunLokalTingkatSlider();
        document.getElementById('btn-simpan-mapel').dataset.submode = 'lokal';
      }

      document.getElementById('btn-toggle-lokal')?.addEventListener('click', tampilkanModeLokal);
      document.getElementById('btn-toggle-master')?.addEventListener('click', tampilkanModeMaster);

      // ====================== SIMPAN MAPEL LOKAL ======================
      async function simpanMapelLokal(btn) {
        const kelompok = document.getElementById('lokal-kelompok').value;
        const nama = document.getElementById('lokal-nama').value.trim();
        const singkat = document.getElementById('lokal-singkat').value.trim();
        const subNama = document.getElementById('lokal-sub').value.trim();
        const mulai = parseInt(document.getElementById('lokal-tingkat-mulai').value);
        const selesai = parseInt(document.getElementById('lokal-tingkat-selesai').value);

        if (!kelompok || !nama) {
          toastError('Kelompok dan nama mata pelajaran wajib diisi.');
          return;
        }
        if (!mulai || !selesai) {
          toastError('Pilih rentang tingkat.');
          return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

        try {
          const payloadLokal = {
            mapel_kelompok_id: kelompok,
            nama,
            nama_singkat: singkat || null,
            nama_sub_mapel: subNama || null,
            kelas_mulai: mulai,
            kelas_selesai: selesai,
          };

          const res = btn.dataset.mode === 'edit-lokal'
            ? await apiFetch(`/mapel-mapping/master-lokal/${btn.dataset.masterLokal}`, 'PUT', payloadLokal)
            : await apiFetch('/mapel-mapping/quick-create', 'POST', payloadLokal);
          const data = await res.json();

          if (res.ok) {
            toastSukses(data.message);
            bootstrap.Modal.getInstance(document.getElementById('modal-mapel')).hide();
            setTimeout(() => loadData(), 800);
          } else {
            throw new Error(data.message ?? 'Gagal menyimpan.');
          }
        } catch (err) {
          toastError(err.message);
        } finally {
          btn.disabled = false;
          btn.innerHTML = '<i class="icon-base ri ri-save-line me-1"></i>Simpan';
        }
      }

      // ====================== HELPERS ======================
      async function apiFetch(url, method = 'POST', body = null) {
        const opts = {
          method,
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          }
        };
        if (body) opts.body = JSON.stringify(body);
        return fetch(url, opts);
      }

      function toastSukses(msg) {
        Swal.fire({
          icon: 'success',
          title: 'Berhasil',
          text: msg,
          timer: 2000,
          showConfirmButton: false
        });
      }

      function toastError(msg) {
        Swal.fire({
          icon: 'error',
          title: 'Gagal',
          text: msg
        });
      }

      function escHtml(str) {
        return String(str ?? '')
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;');
      }

      function escAttr(str) {
        return String(str ?? '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
      }

      // ── Salin Mapel Antar Semester ────────────────────────────────────
      // ── Salin mapel tambahan dari semester lalu (Operator) ──
      document.getElementById('btn-salin-tambahan')?.addEventListener('click', async function() {
        const dari = document.getElementById('salin-tambahan-dari').value;
        if (!dari) {
          toastError('Pilih semester asal dulu.');
          return;
        }
        this.disabled = true;
        try {
          const res = await apiFetch(`${baseUrl}/salin-tambahan`, 'POST', {
            dari_semester: dari
          });
          const data = await res.json();
          if (!res.ok) throw new Error(data.message || 'Gagal menyalin mapel tambahan.');
          toastSukses(data.message);
          bootstrap.Modal.getInstance(document.getElementById('modal-salin-tambahan')).hide();
          refreshTable();
        } catch (err) {
          toastError(err.message);
        } finally {
          this.disabled = false;
        }
      });

      const btnSalin = document.getElementById('btn-salin');
      if (btnSalin) {
        btnSalin.addEventListener('click', async function() {
          const jenjang = document.getElementById('salin-jenjang').value;
          const dari = document.getElementById('salin-dari').value;
          const ke = document.getElementById('salin-ke').value;

          if (!dari || !ke) {
            toastError('Pilih semester asal dan tujuan terlebih dahulu.');
            return;
          }
          if (dari === ke) {
            toastError('Semester asal dan tujuan tidak boleh sama.');
            return;
          }

          btnSalin.disabled = true;
          try {
            const res = await apiFetch(`${baseUrl}/salin-semester`, 'POST', {
              jenjang: jenjang,
              dari_semester: dari,
              ke_semester: ke,
            });
            const data = await res.json();
            if (!res.ok) throw new Error(data.message || 'Gagal menyalin mapel.');
            bootstrap.Modal.getInstance(document.getElementById('modal-salin')).hide();
            toastSukses(data.message);
            refreshTable();
          } catch (e) {
            toastError(e.message);
          } finally {
            btnSalin.disabled = false;
          }
        });
      }

      // ====================== INIT ======================
      loadData();
    });
  </script>
@endsection
