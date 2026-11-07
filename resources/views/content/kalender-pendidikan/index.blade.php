@extends('layouts/layoutMaster')

@section('title', 'Kalender Pendidikan')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')

  {{-- =========================================================== --}}
  {{-- INFO HEADER + FILTER TAHUN PELAJARAN                          --}}
  {{-- =========================================================== --}}
  <div class="card mb-4">
    <div class="card-body py-4">
      <div class="row align-items-center g-3">
        <div class="col-12 col-md-7">
          <div class="d-flex align-items-center gap-4">
            <div class="avatar avatar-xl flex-shrink-0">
              <span class="avatar-initial rounded-circle bg-label-primary">
                <i class="icon-base ri ri-calendar-event-line ri-28px"></i>
              </span>
            </div>
            <div>
              <h4 class="mb-1">Kalender Pendidikan</h4>
              <div class="d-flex gap-4 flex-wrap">
                <span class="text-muted"><i class="icon-base ri ri-list-check-2 me-1"></i><span
                    id="info-jumlah-kegiatan">0</span> Kegiatan</span>
                <span class="text-muted"><i class="icon-base ri ri-close-circle-line me-1 text-danger"></i><span
                    id="info-jumlah-libur">0</span> Hari Libur</span>
                <span class="text-muted"><i class="icon-base ri ri-calendar-line me-1"></i>Tahun <span
                    id="info-tahun-aktif">—</span></span>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-5">
          <label for="filter-tahun" class="form-label mb-1">Tahun Pelajaran</label>
          <select id="filter-tahun" class="form-select">
            <option value="">Semua Tahun Pelajaran</option>
            @foreach ($daftarTahunPelajaran as $tp)
              <option value="{{ $tp }}" {{ $tp === ($tahunPelajaranSesi ?? null) ? 'selected' : '' }}>{{ $tp }}</option>
            @endforeach
          </select>
          <small class="text-muted">
            <i class="ri ri-information-line me-1"></i>
            Tahun sesi login terpilih otomatis. Pilih tahun untuk menambah kegiatan & melihat ringkasannya.
          </small>
        </div>
      </div>
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- TOOLBAR                                                       --}}
  {{-- =========================================================== --}}
  <div class="card mb-4">
    <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 gap-md-3">
      <div class="d-flex gap-2 flex-wrap align-items-center flex-grow-1">
        <input type="text" id="search-kalender" class="form-control form-control-sm" style="max-width:220px; min-width:150px;"
          placeholder="Cari kegiatan...">
        <div class="btn-group btn-group-sm" role="group">
          <button type="button" id="btn-view-tabel" class="btn btn-primary">
            <i class="ri ri-table-line me-1"></i> Tabel
          </button>
          <button type="button" id="btn-view-kalender" class="btn btn-outline-primary">
            <i class="ri ri-calendar-2-line me-1"></i> Kalender
          </button>
        </div>
      </div>
      <div class="d-flex gap-3 align-items-center">
        <button id="btn-download-pdf" type="button" class="btn btn-outline-primary btn-sm">
          <i class="ri ri-file-pdf-2-line me-1"></i> <span class="d-none d-sm-inline-block">Download PDF</span>
        </button>
        @can('create', App\Models\KalenderPendidikan::class)
          <button id="btn-import-kalender" type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal"
            data-bs-target="#modal-import-kalender">
            <i class="ri ri-file-excel-line me-1"></i> <span class="d-none d-sm-inline-block">Import Excel</span>
          </button>
        @endcan
        @can('create', App\Models\KalenderPendidikan::class)
          <button id="btn-tambah-kalender" type="button" class="btn btn-primary btn-sm">
            <i class="ri ri-add-line me-1"></i> <span class="d-none d-sm-inline-block">Tambah Kegiatan</span>
          </button>
        @endcan
      </div>
    </div>
  </div>

  {{-- Legenda jenis: klik untuk menyaring tabel per jenis --}}
  <div id="legend-jenis" class="d-flex flex-wrap gap-2 mb-4"></div>

  {{-- Bilah aksi massal: muncul hanya bila ada baris tercentang --}}
  <div id="bulk-bar" class="card mb-4 d-none" style="border: 1px solid var(--bs-danger);">
    <div class="card-body py-2">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="small"><i class="ri ri-checkbox-multiple-line me-1 text-danger"></i><strong id="bulk-count">0</strong>
          kegiatan terpilih</span>
        <div class="d-flex gap-2 flex-wrap">
          <button type="button" id="btn-bulk-batal" class="btn btn-sm btn-outline-secondary">Batalkan Pilihan</button>
          <button type="button" id="btn-bulk-hapus" class="btn btn-sm btn-danger">
            <i class="ri ri-delete-bin-line me-1"></i>Hapus Terpilih
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- VIEW TABEL (input langsung di dalam baris tabel)              --}}
  {{-- =========================================================== --}}
  <div id="view-tabel-kalender">
    <div class="card">
      <div class="table-responsive" style="max-height: 72vh;">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
            <tr>
              <th width="36" class="text-center">
                <input type="checkbox" id="check-semua" class="form-check-input" title="Pilih semua pada tampilan ini">
              </th>
              <th width="40" class="text-center">#</th>
              <th width="150">Tanggal Mulai</th>
              <th width="150">Tanggal Selesai</th>
              <th width="190">Jenis</th>
              <th width="100">Semester</th>
              <th class="text-center" width="90">Libur</th>
              <th>Kegiatan</th>
              <th class="text-center" width="110">Aksi</th>
            </tr>
          </thead>
          <tbody id="tbody-kalender">
            <tr>
              <td colspan="9" class="text-center py-4 text-muted">
                <span class="spinner-border spinner-border-sm me-2"></span> Memuat data...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- VIEW KALENDER (mini-kalender 12 bulan sesuai tahun pelajaran) --}}
  {{-- =========================================================== --}}
  <div id="view-kalender-semester" class="d-none">

    <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
          <h5 class="mb-0" id="judul-ringkasan-semester">Kalender Tahun Pelajaran</h5>
          <div id="legend-kalender" class="d-flex flex-wrap gap-2"></div>
        </div>
        <div id="ringkasan-semester"></div>
      </div>
    </div>

  </div>

  {{-- =========================================================== --}}
  {{-- MODAL: Import Excel                                          --}}
  {{-- =========================================================== --}}
  <div class="modal fade" id="modal-import-kalender" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="ri ri-file-excel-line me-2 text-success"></i>Import Kalender dari Excel</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info small mb-3">
            Tahun pelajaran mengikuti filter yang aktif: <strong id="import-info-tahun">—</strong>.
            Semua tanggal di file harus berada dalam rentang tahun itu.
          </div>
          <div class="mb-3">
            <a href="{{ route('kalender-pendidikan.import-template') }}" class="btn btn-outline-primary btn-sm">
              <i class="ri ri-download-line me-1"></i> Download Template
            </a>
          </div>
          <label class="form-label" for="file-import-kalender">File Excel (.xlsx / .xls)</label>
          <input type="file" id="file-import-kalender" class="form-control" accept=".xlsx,.xls">
          <div class="form-text">
            Kolom: Tanggal Mulai, Tanggal Selesai, Jenis, Libur (Ya/Tidak), Kegiatan.
            Baris duplikat dilewati; baris yang tidak valid dilaporkan, sisanya tetap diimpor.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-success" id="btn-proses-import">
            <i class="ri ri-upload-line me-1"></i> Import
          </button>
        </div>
      </div>
    </div>
  </div>

  <style>
    .ringkasan-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 3px;
    }

    .ringkasan-hari-label {
      text-align: center;
      font-size: .72rem;
      font-weight: 600;
      color: #8592a3;
      padding-bottom: 4px;
    }

    .ringkasan-hari {
      aspect-ratio: 1 / 1;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: .375rem;
      font-size: .78rem;
      cursor: pointer;
      user-select: none;
    }

    .ringkasan-hari:not(.ringkasan-hari-kosong):hover {
      outline: 2px solid var(--bs-primary, #696cff);
      outline-offset: -2px;
    }

    .ringkasan-hari-kosong {
      cursor: default;
      visibility: hidden;
    }

    .ringkasan-daftar-bulan {
      margin-top: .75rem;
      padding-top: .6rem;
      border-top: 1px solid var(--bs-border-color, #eceef1);
      min-height: 1px;
    }

    .ringkasan-daftar-item {
      display: flex;
      align-items: flex-start;
      gap: .5rem;
      margin-top: .4rem;
    }

    .ringkasan-daftar-dot {
      flex-shrink: 0;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      margin-top: .35rem;
    }
  </style>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {

      // ── Data statis dari backend ────────────────────────────────────────────
      const DAFTAR_JENIS = @json($jenisKegiatan);
      const NAMA_BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September',
        'Oktober', 'November', 'Desember'
      ];
      const NAMA_HARI = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

      const JENIS_COLOR = {
        'Libur Nasional': 'danger',
        'Libur Semester': 'warning',
        'Penilaian': 'info',
        'Libur Puasa dan Lebaran': 'secondary',
        'Pembagian Rapor': 'primary',
        'Lainnya': 'dark',
      };

      function warnaJenis(nama) {
        return JENIS_COLOR[nama] || 'secondary';
      }

      // ── State ─────────────────────────────────────────────────────────────
      const filterTahun = document.getElementById('filter-tahun');
      const tbody = document.getElementById('tbody-kalender');
      const searchInput = document.getElementById('search-kalender');

      let allKalender = [];
      let currentView = 'tabel'; // 'tabel' | 'kalender'
      let editingRowId = null; // null | 'new' | <id>
      let presetTanggalBaru = null; // dipakai saat tambah baris lewat klik tanggal di kalender
      const selectedIds = new Set(); // id baris tercentang untuk aksi massal

      // ── Helper rentang tahun pelajaran aktif ─────────────────────────────────
      // Dihitung langsung dari string "2025/2026" (1 Juli tahun awal - 30 Juni tahun akhir),
      // TIDAK bergantung data dari server sama sekali.
      function rentangTahunAktif() {
        if (!filterTahun.value) return null;
        const [tahunAwal, tahunAkhir] = filterTahun.value.split('/');
        if (!tahunAwal || !tahunAkhir) return null;
        return {
          mulai: `${tahunAwal}-07-01`,
          selesai: `${tahunAkhir}-06-30`,
          label: filterTahun.value
        };
      }

      // ── Load Tabel (plain AJAX) ───────────────────────────────────────────────
      async function loadTabelKalender() {
        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted">
          <span class="spinner-border spinner-border-sm me-2"></span> Memuat data...</td></tr>`;

        try {
          const res = await fetch('/kalender-pendidikan/tabel', {
            headers: {
              Accept: 'application/json'
            }
          });
          const json = await res.json();
          if (!res.ok) throw new Error(json.message || 'Gagal memuat data');

          allKalender = json.data || [];
          renderTabel();
          if (currentView === 'kalender') renderRingkasanSemester();
        } catch (err) {
          tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-danger">
            <i class="ri ri-error-warning-line me-1"></i> ${err.message}</td></tr>`;
        }
      }

      // Statistik header mengikuti data pada tahun terpilih (bukan
      // seluruh tahun) + legenda jenis yang bisa diklik untuk menyaring
      function perbaruiStatistikDanLegenda() {
        const tp = filterTahun.value;
        const dataTp = tp ? allKalender.filter(r => r.tahun_pelajaran === tp) : allKalender;

        document.getElementById('info-jumlah-kegiatan').textContent = dataTp.length;
        document.getElementById('info-jumlah-libur').textContent = dataTp.filter(r => r.is_libur).length;
        document.getElementById('info-tahun-aktif').textContent = tp || 'Semua';

        const q = searchInput.value.toLowerCase().trim();
        document.getElementById('legend-jenis').innerHTML = DAFTAR_JENIS.map(jenis => {
          const n = dataTp.filter(r => r.jenis === jenis).length;
          const aktif = q === jenis.toLowerCase();
          return `<button type="button" class="btn btn-sm ${aktif ? 'btn-primary' : 'btn-outline-secondary'} btn-legenda-jenis"
            data-jenis="${jenis}">
            <span class="badge bg-label-${warnaJenis(jenis)} me-1">&nbsp;</span>${jenis}
            <span class="badge ${aktif ? 'bg-white text-primary' : 'bg-label-secondary'} ms-1">${n}</span>
          </button>`;
        }).join('');
      }

      document.addEventListener('click', function(e) {
        const chip = e.target.closest('.btn-legenda-jenis');
        if (!chip) return;
        const jenis = chip.dataset.jenis;
        searchInput.value = searchInput.value.toLowerCase().trim() === jenis.toLowerCase() ? '' : jenis;
        searchInput.dispatchEvent(new Event('input'));
      });

      function formatTanggal(tgl) {
        if (!tgl) return '-';
        const [yyyy, mm, dd] = tgl.substring(0, 10).split('-');
        return `${dd}/${mm}/${yyyy}`;
      }

      // ── Filter (search + tahun pelajaran) ────────────────────────────────────
      function dataTerfilter() {
        const q = searchInput.value.toLowerCase().trim();
        const tp = filterTahun.value;

        let filtered = allKalender;
        if (tp) filtered = filtered.filter(r => r.tahun_pelajaran === tp);
        if (q) {
          filtered = filtered.filter(r =>
            r.jenis.toLowerCase().includes(q) ||
            (r.kegiatan || '').toLowerCase().includes(q)
          );
        }
        return filtered;
      }

      function eventsPadaTanggal(tglStr, data) {
        return data.filter(r => {
          const mulai = r.tanggal_mulai.substring(0, 10);
          const selesai = (r.tanggal_selesai || r.tanggal_mulai).substring(0, 10);
          return tglStr >= mulai && tglStr <= selesai;
        });
      }

      searchInput.addEventListener('input', function() {
        renderTabel();
        if (currentView === 'kalender') renderRingkasanSemester();
      });

      filterTahun.addEventListener('change', function() {
        editingRowId = null;
        renderTabel();
        if (currentView === 'kalender') renderRingkasanSemester();
      });

      // ── Render baris statis vs baris edit/tambah (Tabel) ─────────────────────
      function optionsJenis(selected) {
        return DAFTAR_JENIS.map(k =>
          `<option value="${k}" ${k === selected ? 'selected' : ''}>${k}</option>`
        ).join('');
      }

      function baseRowHtml(row, nomor) {
        const badgeJenis = `<span class="badge bg-label-${warnaJenis(row.jenis)}">${row.jenis}</span>`;
        const badgeLibur = row.is_libur ?
          '<span class="badge bg-label-danger">Ya</span>' :
          '<span class="badge bg-label-secondary">Tidak</span>';

        let aksi = '<div class="d-flex justify-content-center gap-2">';
        if (row.can_edit ?? true) {
          aksi += `<button class="btn btn-icon btn-text-secondary btn-sm rounded-pill btn-edit-kalender"
                     data-id="${row.id}" title="Edit Kegiatan"><i class="ri ri-edit-box-line icon-22px"></i></button>`;
        }
        if (row.can_delete ?? true) {
          aksi +=
            `<button class="btn btn-icon btn-text-secondary btn-sm rounded-pill btn-hapus-kalender"
                     data-id="${row.id}" title="Hapus Kegiatan"><i class="ri ri-delete-bin-7-line icon-22px"></i></button>`;
        }
        aksi += '</div>';

        const badgeSemester = row.jenis_semester === 'Ganjil' ?
          '<span class="badge bg-label-primary">Ganjil</span>' :
          '<span class="badge bg-label-info">Genap</span>';

        const selCheck = (row.can_delete ?? false) ?
          `<input type="checkbox" class="form-check-input check-baris" data-id="${row.id}" ${selectedIds.has(String(row.id)) ? 'checked' : ''}>` :
          '';

        return `
          <tr data-id="${row.id}">
            <td class="text-center">${selCheck}</td>
            <td class="text-center">${nomor}</td>
            <td>${formatTanggal(row.tanggal_mulai)}</td>
            <td>${formatTanggal(row.tanggal_selesai)}</td>
            <td>${badgeJenis}</td>
            <td>${badgeSemester}</td>
            <td class="text-center">${badgeLibur}</td>
            <td>${row.kegiatan ?? '-'}</td>
            <td>${aksi}</td>
          </tr>`;
      }

      function editRowHtml(row, nomor) {
        const rentang = rentangTahunAktif();
        const minAttr = rentang ? `min="${rentang.mulai}"` : '';
        const maxAttr = rentang ? `max="${rentang.selesai}"` : '';
        const id = row?.id ?? '';
        const tglMulai = row?.tanggal_mulai ?? presetTanggalBaru ?? '';
        const tglSelesai = row?.tanggal_selesai ?? presetTanggalBaru ?? '';

        return `
          <tr data-id="${id}" data-editing="true">
            <td></td>
            <td class="text-center">${nomor}</td>
            <td><input type="date" class="form-control form-control-sm inp-mulai" value="${tglMulai}" ${minAttr} ${maxAttr}></td>
            <td><input type="date" class="form-control form-control-sm inp-selesai" value="${tglSelesai}" ${minAttr} ${maxAttr} placeholder="Opsional"></td>
            <td>
              <select class="form-select form-select-sm inp-jenis">
                ${optionsJenis(row?.jenis)}
              </select>
            </td>
            <td class="text-muted small">otomatis</td>
            <td class="text-center">
              <div class="form-check form-switch d-flex justify-content-center mb-0">
                <input class="form-check-input inp-libur" type="checkbox" role="switch" ${row?.is_libur ? 'checked' : ''}>
              </div>
            </td>
            <td><input type="text" class="form-control form-control-sm inp-kegiatan" value="${row?.kegiatan ?? ''}" placeholder="Kegiatan (opsional)"></td>
            <td>
              <div class="d-flex justify-content-center gap-2">
                <button class="btn btn-icon btn-text-success btn-sm rounded-pill btn-simpan-row" title="Simpan"><i class="ri ri-check-line icon-22px"></i></button>
                <button class="btn btn-icon btn-text-secondary btn-sm rounded-pill btn-batal-row" title="Batal"><i class="ri ri-close-line icon-22px"></i></button>
              </div>
            </td>
          </tr>`;
      }

      function renderTabel() {
        perbaruiStatistikDanLegenda();
        const data = dataTerfilter();
        let rows = '';
        let nomor = 1;

        if (editingRowId === 'new') {
          rows += editRowHtml(null, '+');
        }

        if (!data.length && editingRowId !== 'new') {
          tbody.innerHTML = `<tr><td colspan="9" class="text-center py-5 text-muted">
            <i class="ri ri-calendar-line ri-36px d-block mb-2"></i>
            Belum ada kegiatan pada pilihan ini.<br>
            <small>Klik <strong>Tambah Kegiatan</strong> untuk input manual, atau <strong>Import Excel</strong> untuk memasukkan banyak kegiatan sekaligus.</small>
            </td></tr>`;
          perbaruiBilahMassal();
          return;
        }

        data.forEach(row => {
          rows += (String(row.id) === String(editingRowId)) ?
            editRowHtml(row, nomor) :
            baseRowHtml(row, nomor);
          nomor++;
        });

        tbody.innerHTML = rows;
        presetTanggalBaru = null;
        perbaruiBilahMassal();
      }

      // ── Pilihan massal (centang baris) ──────────────────────────────────────
      function perbaruiBilahMassal() {
        const bar = document.getElementById('bulk-bar');
        document.getElementById('bulk-count').textContent = selectedIds.size;
        bar.classList.toggle('d-none', selectedIds.size === 0);

        // Keadaan kotak centang kepala: mengikuti baris yang tampil
        const kepala = document.getElementById('check-semua');
        const tampilBisaHapus = dataTerfilter().filter(r => r.can_delete);
        const terpilihTampil = tampilBisaHapus.filter(r => selectedIds.has(String(r.id))).length;
        kepala.checked = tampilBisaHapus.length > 0 && terpilihTampil === tampilBisaHapus.length;
        kepala.indeterminate = terpilihTampil > 0 && terpilihTampil < tampilBisaHapus.length;
      }

      document.addEventListener('change', function(e) {
        if (e.target.classList?.contains('check-baris')) {
          const id = String(e.target.dataset.id);
          if (e.target.checked) selectedIds.add(id);
          else selectedIds.delete(id);
          perbaruiBilahMassal();
        }
      });

      document.getElementById('check-semua')?.addEventListener('change', function() {
        dataTerfilter().filter(r => r.can_delete).forEach(r => {
          if (this.checked) selectedIds.add(String(r.id));
          else selectedIds.delete(String(r.id));
        });
        renderTabel();
      });

      document.getElementById('btn-bulk-batal')?.addEventListener('click', function() {
        selectedIds.clear();
        renderTabel();
      });

      document.getElementById('btn-bulk-hapus')?.addEventListener('click', async function() {
        if (!selectedIds.size) return;

        const jumlah = selectedIds.size;
        const confirm = await Swal.fire({
          title: `Hapus ${jumlah} kegiatan?`,
          text: 'Kegiatan terpilih akan dihapus secara permanen!',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Ya, Hapus Semua!',
          cancelButtonText: 'Batal',
          customClass: {
            confirmButton: 'btn btn-danger me-3',
            cancelButton: 'btn btn-outline-secondary'
          },
          buttonsStyling: false
        });
        if (!confirm.isConfirmed) return;

        const btn = this;
        const ikonAsli = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menghapus...';

        let berhasil = 0;
        const gagal = [];
        for (const id of [...selectedIds]) {
          try {
            const res = await fetch(`/kalender-pendidikan/${id}`, {
              method: 'DELETE',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                Accept: 'application/json'
              }
            });
            if (!res.ok) throw new Error();
            berhasil++;
            selectedIds.delete(id);
          } catch {
            gagal.push(id);
          }
        }

        btn.disabled = false;
        btn.innerHTML = ikonAsli;

        await Swal.fire({
          icon: gagal.length ? 'warning' : 'success',
          title: 'Hapus Massal Selesai',
          text: gagal.length ?
            `${berhasil} kegiatan terhapus, ${gagal.length} gagal dihapus.` :
            `${berhasil} kegiatan berhasil dihapus.`
        });

        editingRowId = null;
        await loadTabelKalender();
      });

      // ── Tambah baris baru ─────────────────────────────────────────────────
      document.getElementById('btn-tambah-kalender')?.addEventListener('click', function() {
        if (!filterTahun.value) {
          Swal.fire({
            icon: 'warning',
            title: 'Pilih tahun pelajaran dulu',
            text: 'Silakan pilih tahun pelajaran pada filter di atas sebelum menambah kegiatan baru.'
          });
          return;
        }
        if (editingRowId !== null) return;
        mulaiTambahBaris();
      });

      function mulaiTambahBaris(tanggal) {
        presetTanggalBaru = tanggal || null;
        editingRowId = 'new';
        if (currentView !== 'tabel') pindahKeViewTabel();
        renderTabel();
      }

      // ── Delegasi klik: edit / batal / simpan / hapus (Tabel) ─────────────────
      document.addEventListener('click', async function(e) {

        const editBtn = e.target.closest('.btn-edit-kalender');
        if (editBtn) {
          if (editingRowId !== null) return;
          editingRowId = editBtn.dataset.id;
          renderTabel();
          return;
        }

        const batalBtn = e.target.closest('.btn-batal-row');
        if (batalBtn) {
          editingRowId = null;
          renderTabel();
          return;
        }

        const simpanBtn = e.target.closest('.btn-simpan-row');
        if (simpanBtn) {
          await simpanBaris(simpanBtn.closest('tr'), simpanBtn);
          return;
        }

        const hapusBtn = e.target.closest('.btn-hapus-kalender');
        if (hapusBtn) {
          const id = hapusBtn.dataset.id;
          const confirm = await Swal.fire({
            title: 'Hapus data ini?',
            text: 'Data kegiatan akan dihapus secara permanen!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            customClass: {
              confirmButton: 'btn btn-danger me-3',
              cancelButton: 'btn btn-outline-secondary'
            },
            buttonsStyling: false,
            showLoaderOnConfirm: true,
            preConfirm: async () => {
              try {
                const res = await fetch(`/kalender-pendidikan/${id}`, {
                  method: 'DELETE',
                  headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json'
                  }
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Gagal menghapus data');
                return data;
              } catch (err) {
                Swal.showValidationMessage(err.message);
              }
            },
            allowOutsideClick: () => !Swal.isLoading()
          });
          if (confirm.isConfirmed) {
            Swal.fire({
              icon: 'success',
              title: 'Berhasil!',
              text: confirm.value.message
            });
            editingRowId = null;
            loadTabelKalender();
          }
        }
      });

      // ── Simpan baris (create / update) ───────────────────────────────────────
      async function simpanBaris(tr, btn) {
        const id = tr.dataset.id;
        const isNew = !id;

        const tanggalMulai = tr.querySelector('.inp-mulai').value;
        const tanggalSelesaiInput = tr.querySelector('.inp-selesai').value;
        const jenis = tr.querySelector('.inp-jenis').value;
        const isLibur = tr.querySelector('.inp-libur').checked;
        const kegiatan = tr.querySelector('.inp-kegiatan').value;

        if (!tanggalMulai) {
          Swal.fire({
            icon: 'error',
            title: 'Tanggal mulai harus diisi'
          });
          return;
        }
        const tanggalSelesai = tanggalSelesaiInput || tanggalMulai;
        if (tanggalSelesai < tanggalMulai) {
          Swal.fire({
            icon: 'error',
            title: 'Tanggal selesai tidak boleh sebelum tanggal mulai'
          });
          return;
        }

        const rentang = rentangTahunAktif();
        if (!rentang) {
          Swal.fire({
            icon: 'error',
            title: 'Pilih tahun pelajaran terlebih dahulu',
            text: 'Rentang tanggal kegiatan mengikuti tahun pelajaran yang dipilih pada filter di atas.'
          });
          return;
        }
        if (tanggalMulai < rentang.mulai || tanggalSelesai > rentang.selesai) {
          Swal.fire({
            icon: 'error',
            title: 'Tanggal di luar rentang tahun pelajaran',
            text: `Tanggal harus berada di antara ${formatTanggal(rentang.mulai)} - ${formatTanggal(rentang.selesai)}`
          });
          return;
        }

        const formData = new FormData();
        formData.set('tanggal_mulai', tanggalMulai);
        formData.set('tanggal_selesai', tanggalSelesai);
        formData.set('jenis', jenis);
        formData.set('is_libur', isLibur ? '1' : '0');
        formData.set('kegiatan', kegiatan);
        formData.set('tahun_pelajaran', filterTahun.value);
        if (!isNew) formData.append('_method', 'PUT');

        const url = isNew ? '/kalender-pendidikan' : `/kalender-pendidikan/${id}`;
        const originalIcon = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
          const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
              'X-Requested-With': 'XMLHttpRequest',
              Accept: 'application/json'
            }
          });
          const res = await response.json();
          if (response.ok) {
            editingRowId = null;
            await loadTabelKalender();
          } else if (response.status === 422) {
            const items = Object.values(res.errors).map(e => `<li>${e[0]}</li>`).join('');
            Swal.fire({
              icon: 'error',
              title: 'Gagal Validasi',
              html: `<ul style="text-align:left">${items}</ul>`
            });
          } else {
            throw new Error(res.message || 'Terjadi kesalahan sistem');
          }
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: err.message
          });
        } finally {
          btn.disabled = false;
          btn.innerHTML = originalIcon;
        }
      }

      // ── Ringkasan Kalender Tahun Pelajaran (mini-kalender custom) ────────────

      function renderLegendKalender() {
        const legendEl = document.getElementById('legend-kalender');
        legendEl.innerHTML = DAFTAR_JENIS.map(k =>
          `<span class="badge bg-label-${warnaJenis(k)}"><i class="ri ri-checkbox-blank-circle-fill me-1"></i>${k}</span>`
        ).join('');
      }

      // Daftar {tahun, bulan(0-11)} dari tanggal mulai s/d tanggal selesai tahun pelajaran
      function daftarBulanDalamRentang(mulaiStr, selesaiStr) {
        const bulanList = [];
        const cursor = new Date(mulaiStr + 'T00:00:00');
        cursor.setDate(1);
        const akhir = new Date(selesaiStr + 'T00:00:00');
        while (cursor <= akhir) {
          bulanList.push({
            tahun: cursor.getFullYear(),
            bulan: cursor.getMonth()
          });
          cursor.setMonth(cursor.getMonth() + 1);
        }
        return bulanList;
      }

      // Ambil kegiatan yang menyentuh bulan ini, tanggalnya "diclip" ke batas bulan
      // (kalau rentang aslinya melewati bulan lain, di bulan ini cuma tampil
      // potongan yang ada di bulan ini saja — mis. 28 Jun - 3 Jul akan muncul
      // sebagai "28-30" di kartu Juni dan "1-3" di kartu Juli).
      function kegiatanUntukBulan(tahun, bulanIdx, data) {
        const bulanAwalStr = `${tahun}-${String(bulanIdx + 1).padStart(2, '0')}-01`;
        const hariTerakhir = new Date(tahun, bulanIdx + 1, 0).getDate();
        const bulanAkhirStr =
          `${tahun}-${String(bulanIdx + 1).padStart(2, '0')}-${String(hariTerakhir).padStart(2, '0')}`;

        return data
          .map(r => {
            const mulaiAsli = r.tanggal_mulai.substring(0, 10);
            const selesaiAsli = (r.tanggal_selesai || r.tanggal_mulai).substring(0, 10);

            // Tidak menyentuh bulan ini sama sekali -> lewati
            if (selesaiAsli < bulanAwalStr || mulaiAsli > bulanAkhirStr) return null;

            const mulaiClip = mulaiAsli < bulanAwalStr ? bulanAwalStr : mulaiAsli;
            const selesaiClip = selesaiAsli > bulanAkhirStr ? bulanAkhirStr : selesaiAsli;

            return {
              ...r,
              mulaiClip,
              selesaiClip
            };
          })
          .filter(Boolean)
          .sort((a, b) => a.mulaiClip.localeCompare(b.mulaiClip));
      }

      function renderDaftarBulanHtml(items) {
        if (!items.length) {
          return `<div class="text-center text-muted small mt-2">Belum ada kegiatan</div>`;
        }

        return items.map(r => {
          const hariMulai = parseInt(r.mulaiClip.substring(8, 10), 10);
          const hariSelesai = parseInt(r.selesaiClip.substring(8, 10), 10);
          const labelTanggal = hariMulai === hariSelesai ? `${hariMulai}` : `${hariMulai}–${hariSelesai}`;
          const teksKegiatan = r.kegiatan || r.jenis;
          const kelasTeks = r.is_libur ? 'text-danger' : 'text-body';

          return `
            <div class="ringkasan-daftar-item">
              <span class="ringkasan-daftar-dot bg-${warnaJenis(r.jenis)}"></span>
              <span class="small ${kelasTeks}"><strong>${labelTanggal}</strong> ${teksKegiatan}</span>
            </div>`;
        }).join('');
      }

      function buatMiniKalenderHtml(tahun, bulanIdx, data) {
        const jumlahHari = new Date(tahun, bulanIdx + 1, 0).getDate();
        const offset = new Date(tahun, bulanIdx, 1).getDay(); // 0=Minggu

        let selHtml = '';
        for (let i = 0; i < offset; i++) {
          selHtml += `<div class="ringkasan-hari ringkasan-hari-kosong"></div>`;
        }

        for (let d = 1; d <= jumlahHari; d++) {
          const tglStr = `${tahun}-${String(bulanIdx + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
          const eventsHariItu = eventsPadaTanggal(tglStr, data);
          let kelas = 'ringkasan-hari';
          let titleAttr = '';
          if (eventsHariItu.length) {
            kelas += ` bg-label-${warnaJenis(eventsHariItu[0].jenis)}`;
            titleAttr = eventsHariItu
              .map(ev => `${ev.jenis}${ev.kegiatan ? ': ' + ev.kegiatan : ''}`)
              .join(' | ');
          }
          selHtml +=
            `<div class="${kelas}" data-tgl="${tglStr}" title="${titleAttr.replace(/"/g, '&quot;')}">${d}</div>`;
        }

        const headerHari = NAMA_HARI.map(h => `<div class="ringkasan-hari-label">${h}</div>`).join('');
        const daftarBulanIni = kegiatanUntukBulan(tahun, bulanIdx, data);
        const daftarHtml = renderDaftarBulanHtml(daftarBulanIni);

        return `
          <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100">
              <div class="card-body">
                <h6 class="text-center mb-3">${NAMA_BULAN[bulanIdx]} ${tahun}</h6>
                <div class="ringkasan-grid">${headerHari}${selHtml}</div>
                <div class="ringkasan-daftar-bulan">${daftarHtml}</div>
              </div>
            </div>
          </div>`;
      }

      function renderRingkasanSemester() {
        const container = document.getElementById('ringkasan-semester');
        const judulEl = document.getElementById('judul-ringkasan-semester');
        const rentang = rentangTahunAktif();

        renderLegendKalender();

        if (!rentang) {
          judulEl.textContent = 'Kalender Tahun Pelajaran';
          container.innerHTML = `<div class="text-center text-muted py-5">
            <i class="ri ri-calendar-line ri-36px d-block mb-2"></i>
            Pilih tahun pelajaran pada filter di atas untuk melihat kalender ringkasan.
          </div>`;
          return;
        }

        judulEl.textContent = `Kalender Tahun Pelajaran — ${rentang.label}`;

        const data = dataTerfilter();
        const bulanList = daftarBulanDalamRentang(rentang.mulai, rentang.selesai);

        container.innerHTML =
          `<div class="row g-4">${bulanList.map(b => buatMiniKalenderHtml(b.tahun, b.bulan, data)).join('')}</div>`;
      }

      // Klik tanggal di mini-kalender -> tambah / lihat kegiatan hari itu
      document.getElementById('ringkasan-semester').addEventListener('click', function(e) {
        const cell = e.target.closest('.ringkasan-hari[data-tgl]');
        if (!cell) return;

        const tglKlik = cell.dataset.tgl;
        const eventsHariItu = eventsPadaTanggal(tglKlik, allKalender);

        if (eventsHariItu.length === 0) {
          if (editingRowId !== null) return;
          pindahKeViewTabel();
          mulaiTambahBaris(tglKlik);
          return;
        }

        const listHtml = eventsHariItu.map(r => `
          <div class="d-flex justify-content-between align-items-start border-bottom py-2 text-start">
            <div>
              <div class="fw-semibold">
                <span class="badge bg-label-${warnaJenis(r.jenis)} me-1">${r.jenis}</span>
              </div>
              <small class="text-muted">Semester ${r.jenis_semester ?? '-'} ${r.is_libur ? '• Libur' : ''}</small>
              ${r.kegiatan ? `<div class="small mt-1">${r.kegiatan}</div>` : ''}
            </div>
          </div>`).join('');

        Swal.fire({
          title: formatTanggal(tglKlik),
          html: `<div class="text-start">${listHtml}</div>`,
          showCancelButton: true,
          confirmButtonText: 'Tambah Kegiatan Baru',
          cancelButtonText: 'Tutup',
          customClass: {
            confirmButton: 'btn btn-primary me-3',
            cancelButton: 'btn btn-outline-secondary'
          },
          buttonsStyling: false,
        }).then(result => {
          if (result.isConfirmed) {
            if (editingRowId !== null) return;
            pindahKeViewTabel();
            mulaiTambahBaris(tglKlik);
          }
        });
      });

      // ── Toggle Tabel / Kalender ───────────────────────────────────────────────
      function pindahKeViewTabel() {
        currentView = 'tabel';
        document.getElementById('view-tabel-kalender').classList.remove('d-none');
        document.getElementById('view-kalender-semester').classList.add('d-none');
        document.getElementById('btn-view-tabel').classList.replace('btn-outline-primary', 'btn-primary');
        document.getElementById('btn-view-kalender').classList.replace('btn-primary', 'btn-outline-primary');
      }

      document.getElementById('btn-view-tabel').addEventListener('click', pindahKeViewTabel);

      document.getElementById('btn-view-kalender').addEventListener('click', function() {
        currentView = 'kalender';
        document.getElementById('view-tabel-kalender').classList.add('d-none');
        document.getElementById('view-kalender-semester').classList.remove('d-none');
        this.classList.replace('btn-outline-primary', 'btn-primary');
        document.getElementById('btn-view-tabel').classList.replace('btn-primary', 'btn-outline-primary');
        renderRingkasanSemester();
      });

      // ── Download PDF (modal pilihan libur mingguan + model) ──────────────────
      const HARI_OPSI = [{
          value: 0,
          label: 'Minggu'
        },
        {
          value: 1,
          label: 'Senin'
        },
        {
          value: 2,
          label: 'Selasa'
        },
        {
          value: 3,
          label: 'Rabu'
        },
        {
          value: 4,
          label: 'Kamis'
        },
        {
          value: 5,
          label: "Jum'at"
        },
        {
          value: 6,
          label: 'Sabtu'
        },
      ];

      document.getElementById('btn-download-pdf')?.addEventListener('click', async function() {
        if (!filterTahun.value) {
          Swal.fire({
            icon: 'warning',
            title: 'Pilih tahun pelajaran dulu',
            text: 'Silakan pilih tahun pelajaran pada filter di atas sebelum mengunduh PDF.'
          });
          return;
        }

        let liburMingguanTerpilih = [0]; // default: Minggu

        const checkboxHtml = HARI_OPSI.map(h => `
          <label class="d-flex align-items-center gap-2 mb-2" style="text-align:left;">
            <input type="checkbox" class="form-check-input chk-libur-mingguan" value="${h.value}" ${h.value === 0 ? 'checked' : ''}>
            <span>${h.label}</span>
          </label>`).join('');

        const result = await Swal.fire({
          title: 'Download Kalender Pendidikan',
          html: `
            <div style="text-align:left;">
              <p class="mb-1 fw-semibold">Tempat (untuk tanda tangan, khusus Model Tabel):</p>
              <input type="text" id="input-tempat-ttd" class="form-control form-control-sm mb-3"
                placeholder="Kosongkan untuk diisi tulis tangan">
              <p class="mb-2 fw-semibold">Pilih hari libur mingguan:</p>
              <div>${checkboxHtml}</div>
            </div>`,
          showDenyButton: true,
          showCancelButton: true,
          confirmButtonText: 'Model Tabel',
          denyButtonText: 'Model Kalender',
          cancelButtonText: 'Batal',
          customClass: {
            confirmButton: 'btn btn-primary me-2',
            denyButton: 'btn btn-outline-primary me-2',
            cancelButton: 'btn btn-outline-secondary'
          },
          buttonsStyling: false,
          didOpen: () => {
            document.querySelectorAll('.chk-libur-mingguan').forEach(chk => {
              chk.addEventListener('change', () => {
                liburMingguanTerpilih = Array.from(document.querySelectorAll(
                  '.chk-libur-mingguan:checked')).map(c => c.value);
              });
            });
          }
        });

        if (!result.isConfirmed && !result.isDenied) return; // Batal

        const tempatTtd = document.getElementById('input-tempat-ttd')?.value.trim() || '';

        const rute = result.isConfirmed ? '/kalender-pendidikan/cetak-tabel' : '/kalender-pendidikan/cetak';
        const params = new URLSearchParams({
          tahun_pelajaran: filterTahun.value,
          libur_mingguan: liburMingguanTerpilih.join(','),
          tempat: tempatTtd
        });
        window.location.href = `${rute}?${params.toString()}`;
      });

      // ── Import Excel ────────────────────────────────────────────────────────
      const modalImport = document.getElementById('modal-import-kalender');
      modalImport?.addEventListener('show.bs.modal', function() {
        document.getElementById('import-info-tahun').textContent = filterTahun.value || '(belum dipilih)';
        document.getElementById('file-import-kalender').value = '';
      });

      document.getElementById('btn-proses-import')?.addEventListener('click', async function() {
        if (!filterTahun.value) {
          Swal.fire({
            icon: 'warning',
            title: 'Pilih tahun pelajaran dulu',
            text: 'Silakan pilih tahun pelajaran pada filter di atas sebelum mengimpor.'
          });
          return;
        }

        const fileInput = document.getElementById('file-import-kalender');
        if (!fileInput.files.length) {
          Swal.fire({
            icon: 'warning',
            title: 'File belum dipilih',
            text: 'Pilih file Excel yang akan diimpor.'
          });
          return;
        }

        const formData = new FormData();
        formData.set('file_excel', fileInput.files[0]);
        formData.set('tahun_pelajaran', filterTahun.value);

        const btn = this;
        const ikonAsli = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengimpor...';

        try {
          const response = await fetch('/kalender-pendidikan/import', {
            method: 'POST',
            body: formData,
            headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
              'X-Requested-With': 'XMLHttpRequest',
              Accept: 'application/json'
            }
          });
          const res = await response.json();
          if (!response.ok) throw new Error(res.message || 'Gagal mengimpor file');

          bootstrap.Modal.getInstance(modalImport)?.hide();

          let html = `<p>${res.message}</p>`;
          if (res.errors && res.errors.length) {
            html += '<ul style="text-align:left">' + res.errors.map(e => `<li>${e}</li>`).join('') + '</ul>';
          }
          await Swal.fire({
            icon: res.errors && res.errors.length ? 'warning' : 'success',
            title: 'Import Selesai',
            html
          });

          await loadTabelKalender();
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Import Gagal',
            text: err.message
          });
        } finally {
          btn.disabled = false;
          btn.innerHTML = ikonAsli;
        }
      });

      loadTabelKalender();
    });
  </script>
@endsection
