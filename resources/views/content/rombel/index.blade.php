@extends('layouts/layoutMaster')

@section('title', 'Data Rombel')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/chartjs/chartjs.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('content')

  {{-- =========================================================== --}}
  {{-- VIEW 1: DAFTAR ROMBEL (tabel biasa + AJAX)                  --}}
  {{-- =========================================================== --}}
  <div id="view-rombel">
    {{-- Info header --}}
    <div class="row mb-4 g-4">
      <div class="col-12 col-md-8">
        <div class="card h-100">
          <div class="card-body d-flex align-items-center gap-4 py-4">
            <div class="avatar avatar-xl flex-shrink-0">
              <span class="avatar-initial rounded-circle bg-label-primary">
                <i class="icon-base ri ri-group-line ri-28px"></i>
              </span>
            </div>
            <div>
              <h4 class="mb-1">Data Rombel</h4>
              <div class="d-flex gap-4 flex-wrap">
                <span class="text-muted"><i class="icon-base ri ri-stack-line me-1"></i>Jenjang
                  {{ strtoupper($sekolah->jenjang) }}</span>
                <span class="text-muted"><i class="icon-base ri ri-layout-grid-line me-1"></i><span
                    id="info-jumlah-rombel">0</span> Rombel</span>
                <span class="text-muted"><i class="icon-base ri ri-user-line me-1"></i><span
                    id="info-total-siswa">0</span> Siswa</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4 d-none d-md-block">
        <div class="card h-100">
          <div class="card-body py-4">
            <div style="position: relative; height: 80px;">
              <canvas id="chart-siswa-tingkat"></canvas>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Toolbar --}}
    <div class="card mb-4">
      <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex gap-2 flex-wrap">
          <input type="text" id="search-rombel" class="form-control form-control-sm" style="max-width:220px"
            placeholder="Cari nama rombel...">
        </div>
        <div class="d-flex gap-3 align-items-center">
          @can('create', App\Models\Rombel::class)
            <button id="tambah_rombel" class="btn btn-primary btn-sm" data-bs-toggle="offcanvas"
              data-bs-target="#offcanvas_rombel">
              <i class="ri ri-add-line me-1"></i> <span class="d-none d-sm-inline-block">Tambah Rombel</span>
            </button>
          @endcan
        </div>
      </div>
    </div>

    {{-- Tabel Rombel --}}
    <div class="card">
      <div class="table-responsive">
        <table class="table table-bordered table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th width="50" class="text-center">#</th>
              <th>Nama Rombel</th>
              <th class="text-center">Tingkat</th>
              <th>Wali Kelas</th>
              <th class="text-center">Jumlah Siswa</th>
              <th class="text-center" width="120">Aksi</th>
            </tr>
          </thead>
          <tbody id="tbody-rombel">
            <tr>
              <td colspan="6" class="text-center py-4 text-muted">
                <span class="spinner-border spinner-border-sm me-2"></span> Memuat data...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- VIEW 2: DETAIL & ANGGOTA ROMBEL (SPA, awalnya hidden)       --}}
  {{-- =========================================================== --}}
  <div id="view-anggota" class="d-none">

    {{-- Tombol Kembali --}}
    <div class="mb-4">
      <button class="btn btn-outline-secondary" id="btn-kembali">
        <i class="ri ri-arrow-left-line me-1"></i> Kembali ke Daftar Rombel
      </button>
    </div>

    {{-- Info Header Rombel --}}
    <div class="row mb-4 g-4">
      <div class="col-12 col-md-8">
        <div class="card h-100">
          <div class="card-body d-flex align-items-center gap-4 py-4">
            <div class="avatar avatar-xl flex-shrink-0">
              <span class="avatar-initial rounded-circle bg-label-primary">
                <i class="icon-base ri ri-graduation-cap-line ri-28px"></i>
              </span>
            </div>
            <div>
              <h4 class="mb-1" id="judul-header-rombel">Rombel</h4>
              <div class="d-flex gap-4 flex-wrap">
                <span class="text-muted d-none d-lg-inline-block small"><i
                    class="icon-base ri ri-stack-line me-1"></i>Tingkat
                  <span id="detail-tingkat">-</span></span>
                <span class="text-muted small"><i class="icon-base ri ri-book-2-line me-1"></i><span
                    id="detail-kurikulum">-</span></span>
                <span class="text-muted small"><i class="icon-base ri ri-user-line me-1"></i><span
                    id="detail-wali-kelas">-</span></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="card h-100">
          <div class="card-body py-4">
            <div class="mb-3">
              <h6><i class="icon-base ri ri-group-line me-2"></i>Jumlah Siswa : <span id="detail-jumlah-siswa">0</span>
              </h6>
            </div>
            <div class="d-flex gap-3">
              <div class="d-flex align-items-center gap-2">
                <span class="text-muted small"><i class="icon-base ri ri-men-line me-1 text-primary"></i><span
                    class="d-none d-sm-inline-block">Laki-laki</span></span>
                <strong id="detail-jumlah-l">0</strong>
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="text-muted small"><i class="icon-base ri ri-women-line me-1 text-danger"></i><span
                    class="d-none d-sm-inline-block">Perempuan</span></span>
                <strong id="detail-jumlah-p">0</strong>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Toolbar --}}
    <div class="card mb-4">
      <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <input type="text" id="search-anggota" class="form-control form-control-sm" style="max-width:220px"
          placeholder="Cari nama siswa...">
        <div class="d-flex gap-2">
          <button class="btn btn-danger btn-sm d-none" id="btn-keluarkan-massal">
            <i class="ri ri-user-unfollow-line me-1"></i> Keluarkan <span id="jumlah-terpilih">0</span> Terpilih
          </button>
          <button class="btn btn-primary btn-sm" id="btn-buka-tambah-anggota">
            <i class="ri ri-user-add-line me-1"></i> <span class="d-none d-sm-inline-block">Tambah Anggota</span>
          </button>
        </div>
      </div>
    </div>

    {{-- Card Anggota Rombel (DataTable) --}}
    <div class="card">
      <div class="card-datatable text-nowrap">
        <table class="table table-bordered" id="tabel_anggota_rombel">
          <thead>
            <tr>
              <th><input type="checkbox" class="form-check-input" id="check-all-anggota"
                  title="Centang semua di halaman ini"></th>
              <th>#</th>
              <th>Siswa</th>
              <th>NIS / NISN</th>
              <th>JK</th>
              <th>Aksi</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- OFFCANVAS: FORM TAMBAH / EDIT ROMBEL                        --}}
  {{-- =========================================================== --}}
  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas_rombel">
    <div class="offcanvas-header border-bottom">
      <h5 id="judul_form" class="offcanvas-title">Tambah Rombel Baru</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
      <form id="form_rombel">
        <input type="hidden" name="id" id="rombel_id">
        <input type="hidden" id="sekolah_id" name="sekolah_id" value="{{ $sekolah->id }}">
        <input type="hidden" id="jenjang_sekolah" value="{{ strtolower($sekolah->jenjang) }}">

        <div class="form-floating form-floating-outline mb-5">
          <input type="text" class="form-control" id="nama_rombel" name="nama_rombel" placeholder="Nama Rombel" />
          <label for="nama_rombel">Nama Rombel</label>
        </div>

        <div id="container_tingkat" class="form-floating form-floating-outline mb-5">
          <select id="tingkat" name="tingkat" class="form-select">
            <option value="">Pilih tingkat</option>
          </select>
          <label for="tingkat">Tingkat</label>
          <small id="info_tingkat_locked" class="text-warning d-none">
            <i class="ri ri-lock-line"></i> Tingkat tidak dapat diubah karena sudah ada anggota.
          </small>
        </div>

        <div class="form-floating form-floating-outline mb-5">
          <select id="kurikulum" name="kurikulum_id" class="form-select">
            <option value="">Pilih Kurikulum</option>
            @foreach ($kurikulum as $k)
              <option value="{{ $k->id }}">{{ $k->nama }}</option>
            @endforeach
          </select>
          <label for="kurikulum">Kurikulum</label>
        </div>

        <div class="form-floating form-floating-outline mb-5">
          <select id="wali_kelas" name="ptk_id" class="form-select">
            <option value="">Pilih wali kelas</option>
            @foreach ($ptks as $ptk)
              <option value="{{ $ptk->id }}">{{ $ptk->nama_ptk }}</option>
            @endforeach
          </select>
          <label for="wali_kelas">Wali Kelas</label>
        </div>

        <button type="submit" id="submit" class="btn btn-primary me-2">Simpan</button>
        <button type="reset" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">Batal</button>
      </form>
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- MODAL: TAMBAH ANGGOTA (pilih siswa tersedia)                --}}
  {{-- =========================================================== --}}
  <div class="modal fade" id="modal-tambah-anggota" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Pilih Siswa untuk Dimasukkan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">

          <div id="tersedia-loading" class="text-center py-4">
            <span class="spinner-border spinner-border-sm me-2"></span> Memuat daftar siswa...
          </div>

          <div id="tersedia-content" class="d-none">
            <div class="mb-3">
              <input type="text" id="search-tersedia" class="form-control form-control-sm"
                placeholder="Cari nama siswa...">
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-bordered table-hover">
                <thead class="table-light">
                  <tr>
                    <th width="40" class="text-center">
                      <input type="checkbox" class="form-check-input" id="check-all">
                    </th>
                    <th>Siswa</th>
                    <th>NIS / NISN</th>
                    <th class="text-center">Tingkat</th>
                  </tr>
                </thead>
                <tbody id="tbody-siswa-tersedia"></tbody>
              </table>
            </div>
            <small class="text-muted" id="info-tersedia">0 siswa tersedia</small>
          </div>

          <div id="tersedia-empty" class="d-none text-center py-4 text-muted">
            <i class="ri ri-inbox-line ri-2x mb-2 d-block"></i>
            Tidak ada siswa yang tersedia untuk ditambahkan.
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-primary" id="btn-simpan-anggota">
            <i class="ri ri-save-line me-1"></i> Simpan Terpilih
          </button>
        </div>
      </div>
    </div>
  </div>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {

      // ── State ─────────────────────────────────────────────────────────────────
      const formRombel = document.getElementById('form_rombel');
      const offcanvasEl = document.getElementById('offcanvas_rombel');
      const judulForm = document.getElementById('judul_form');
      const selectTingkat = document.getElementById('tingkat');
      const containerTingkat = document.getElementById('container_tingkat');
      const infoLocked = document.getElementById('info_tingkat_locked');
      const jenjangSekolah = document.getElementById('jenjang_sekolah').value;

      let saveMode = 'add';
      let globalRombelId = null;
      let globalEditId = null;
      let allRombel = [];
      let allTersedia = [];
      let dtAnggota = null;

      // ── Avatar Helpers ────────────────────────────────────────────────────────
      const AVATAR_PALETTE = [
        '#696cff', '#03c3ec', '#71dd37', '#ff3e1d',
        '#ffab00', '#20c997', '#f26cf9', '#8592a3',
      ];

      function getInitials(name) {
        return (name || '?').trim().split(/\s+/).slice(0, 2)
          .map(w => w.charAt(0)).join('').toUpperCase();
      }

      function avatarColor(name) {
        let h = 0;
        for (const c of (name || '')) h = (h * 31 + c.charCodeAt(0)) >>> 0;
        return AVATAR_PALETTE[h % AVATAR_PALETTE.length];
      }

      // Render avatar circle + nama sebagai link — dipakai di DataTable & modal tersedia
      function renderSiswaCell(nama, siswaId) {
        const initials = getInitials(nama);
        const color = avatarColor(nama);
        return `
          <div class="d-flex align-items-center gap-2">
            <a href="/siswa/${siswaId}/show"
               class="d-flex align-items-center justify-content-center rounded-circle
                      text-white text-decoration-none fw-semibold flex-shrink-0"
               style="width:36px;height:36px;background:${color};font-size:13px"
               title="Lihat detail ${nama}">
              ${initials}
            </a>
            <a href="/siswa/${siswaId}/show" class="text-body text-decoration-none">${nama}</a>
          </div>`;
      }

      // ── Tingkat Options ───────────────────────────────────────────────────────
      const jenjangMap = {
        sd: [1, 2, 3, 4, 5, 6],
        smp: [7, 8, 9],
        sma: [10, 11, 12],
        smk: [10, 11, 12]
      };

      const updateTingkatOptions = (selectedValue = null) => {
        selectTingkat.innerHTML = '<option value="">Pilih tingkat</option>';
        (jenjangMap[jenjangSekolah] ?? []).forEach(num => {
          selectTingkat.add(new Option(num, num, false, num == selectedValue));
        });
      };
      updateTingkatOptions();

      // ── Helper: Lock/Unlock Tingkat ───────────────────────────────────────────
      function setLockState(isLocked, tingkat = null) {
        selectTingkat.disabled = isLocked;
        containerTingkat.classList.toggle('opacity-50', isLocked);
        infoLocked.classList.toggle('d-none', !isLocked);
        if (isLocked) {
          fv.disableValidator('tingkat', 'notEmpty');
          if (tingkat) selectTingkat.value = tingkat;
        } else {
          fv.enableValidator('tingkat', 'notEmpty');
        }
      }

      // ── SPA: Switch View ──────────────────────────────────────────────────────
      function showViewAnggota(rowData) {
        document.getElementById('view-rombel').classList.add('d-none');
        document.getElementById('view-anggota').classList.remove('d-none');
        globalRombelId = rowData.id;

        document.getElementById('judul-header-rombel').textContent = rowData.nama_rombel;
        document.getElementById('detail-tingkat').textContent = rowData.tingkat;
        document.getElementById('detail-kurikulum').textContent = rowData.kurikulum;
        document.getElementById('detail-wali-kelas').textContent = rowData.wali_kelas || '-';
        document.getElementById('detail-jumlah-siswa').textContent = rowData.jumlah_siswa;
        document.getElementById('detail-jumlah-l').textContent = rowData.jumlah_l ?? 0;
        document.getElementById('detail-jumlah-p').textContent = rowData.jumlah_p ?? 0;

        initDtAnggota(rowData.id);
      }

      function refreshHeaderCounts() {
        if (!dtAnggota) return;
        const rows = dtAnggota.rows().data().toArray();
        const jumlahL = rows.filter(r => r.siswa.jenis_kelamin === 'L').length;
        const jumlahP = rows.filter(r => r.siswa.jenis_kelamin === 'P').length;

        document.getElementById('detail-jumlah-siswa').textContent = rows.length;
        document.getElementById('detail-jumlah-l').textContent = jumlahL;
        document.getElementById('detail-jumlah-p').textContent = jumlahP;
      }

      function showViewRombel() {
        document.getElementById('view-anggota').classList.add('d-none');
        document.getElementById('view-rombel').classList.remove('d-none');
        globalRombelId = null;
        // Instance DataTable anggota sengaja TIDAK dimusnahkan di sini;
        // ia dipakai ulang saat membuka rombel lain (lihat initDtAnggota).
      }

      document.getElementById('btn-kembali').addEventListener('click', function() {
        document.getElementById('search-anggota').value = '';
        showViewRombel();
        loadTabelRombel();
      });

      // ── Tabel Rombel (plain AJAX) ─────────────────────────────────────────────
      async function loadTabelRombel() {
        const tbody = document.getElementById('tbody-rombel');
        const info = document.getElementById('info-rombel');
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">
    <span class="spinner-border spinner-border-sm me-2"></span> Memuat data...</td></tr>`;

        try {
          const res = await fetch('/rombel/tabel', {
            headers: {
              Accept: 'application/json'
            }
          });
          const json = await res.json();
          if (!res.ok) throw new Error(json.message || 'Gagal memuat data');

          allRombel = json.data || [];
          updateHeaderStats(allRombel, json.statistik_tingkat); // ⬅️ diubah
          renderTabelRombel(allRombel);
        } catch (err) {
          tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">
      <i class="ri ri-error-warning-line me-1"></i> ${err.message}</td></tr>`;
          info.textContent = '';
        }
      }

      // ── Update Header Stats ──────────────────────────────────────────────────
      let chartSiswaTingkat = null;

      function updateHeaderStats(data, statistikTingkat) {
        const totalRombel = data.length;
        const totalSiswa = data.reduce((sum, r) => sum + (r.jumlah_siswa || 0), 0);

        document.getElementById('info-jumlah-rombel').textContent = totalRombel;
        document.getElementById('info-total-siswa').textContent = totalSiswa;

        renderChartSiswaTingkat(statistikTingkat || []);
      }

      function renderChartSiswaTingkat(statistikTingkat) {
        const tingkatList = jenjangMap[jenjangSekolah] ?? [];
        const map = Object.fromEntries(statistikTingkat.map(s => [s.tingkat, s]));

        const labels = tingkatList.map(t => `Tk ${t}`);
        const dataL = tingkatList.map(t => map[t]?.L || 0);
        const dataP = tingkatList.map(t => map[t]?.P || 0);

        const ctx = document.getElementById('chart-siswa-tingkat');

        if (chartSiswaTingkat) {
          chartSiswaTingkat.data.labels = labels;
          chartSiswaTingkat.data.datasets[0].data = dataL;
          chartSiswaTingkat.data.datasets[1].data = dataP;
          chartSiswaTingkat.update();
          return;
        }

        chartSiswaTingkat = new Chart(ctx, {
          type: 'bar',
          data: {
            labels,
            datasets: [{
                label: 'Laki-laki',
                data: dataL,
                backgroundColor: '#2196F3',
                borderRadius: 4,
                maxBarThickness: 28
              },
              {
                label: 'Perempuan',
                data: dataP,
                backgroundColor: '#E91E63',
                borderRadius: 4,
                maxBarThickness: 28
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
              legend: {
                display: false
              },
              tooltip: {
                mode: 'index',
                intersect: false
              }
            },
            scales: {
              x: {
                grid: {
                  display: false
                }
              },
              y: {
                beginAtZero: true,
                ticks: {
                  precision: 0
                }
              }
            }
          }
        });
      }

      function renderTabelRombel(data) {
        const tbody = document.getElementById('tbody-rombel');

        if (!data.length) {
          tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">
            <i class="ri ri-inbox-line me-1"></i> Belum ada data rombel.</td></tr>`;
          return;
        }

        tbody.innerHTML = data.map((row, i) => {
          let aksi = `<div class="d-flex justify-content-center gap-2">`;
          if (row.can_edit) {
            aksi += `
              <button class="btn btn-icon btn-text-secondary btn-sm rounded-pill btn-edit-rombel"
                data-id="${row.id}" data-bs-toggle="offcanvas" data-bs-target="#offcanvas_rombel"
                title="Edit Rombel">
                <i class="ri ri-edit-box-line icon-22px"></i>
              </button>
              <button class="btn btn-icon btn-text-secondary btn-sm rounded-pill btn-anggota-rombel"
                data-index="${i}" title="Kelola Anggota">
                <i class="ri ri-group-line icon-22px"></i>
              </button>`;
          }
          if (row.can_delete) {
            aksi += `
            <button class="btn btn-icon btn-text-secondary btn-sm rounded-pill btn-hapus-rombel"
              data-id="${row.id}" title="Hapus Rombel">
              <i class="ri ri-delete-bin-7-line icon-22px"></i>
            </button>`;
          }

          return `
            <tr>
              <td class="text-center">${i + 1}</td>
              <td>${row.nama_rombel}</td>
              <td class="text-center">${row.tingkat}</td>
              <td>${row.wali_kelas || '-'}</td>
              <td class="text-center">${row.jumlah_siswa}</td>
              <td>${aksi}</td>
            </tr>`;
        }).join('');
      }

      document.getElementById('search-rombel').addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        renderTabelRombel(q ? allRombel.filter(r => r.nama_rombel.toLowerCase().includes(q)) :
          allRombel);
      });

      loadTabelRombel();

      // ── DataTable Anggota Rombel ──────────────────────────────────────────────
      function initDtAnggota(rombelId) {
        // DataTable dibuat SEKALI saja. Pindah rombel cukup mengganti URL
        // ajax lalu memuat ulang — pola destroy + buat baru berulang memicu
        // warning "Cannot reinitialise DataTable" karena instance lama
        // belum benar-benar bersih saat instance baru dibuat.
        if (dtAnggota) {
          dtAnggota.search('');
          document.getElementById('search-anggota').value = '';
          dtAnggota.ajax.url(`/anggota-rombel/${rombelId}/list`).load();
          return;
        }

        dtAnggota = new DataTable('#tabel_anggota_rombel', {
          processing: true,
          serverSide: false,
          scrollX: true,
          ajax: {
            url: `/anggota-rombel/${rombelId}/list`,
            dataSrc: 'anggota'
          },
          columns: [{
              // Centang untuk keluarkan massal
              data: null,
              orderable: false,
              searchable: false,
              render: (d, t, row) =>
                `<input type="checkbox" class="form-check-input check-anggota" value="${row.id}">`
            },
            {
              // Nomor urut
              data: null,
              orderable: false,
              searchable: false,
              render: (d, t, r, m) => m.row + 1
            },
            {
              // Avatar + Nama (keduanya link ke detail siswa).
              // Data kolom berupa string nama — bukan objek siswa —
              // supaya pengurutan dan pencarian bekerja pada namanya
              data: 'siswa.nama_siswa',
              render: (nama, t, row) => renderSiswaCell(nama, row.siswa.id)
            },
            {
              // NIS / NISN
              data: 'siswa.nisn',
              render: (nisn, t, row) =>
                `<small class="text-muted">${nisn || '-'} / ${row.siswa.nis || '-'}</small>`
            },
            {
              // Jenis Kelamin
              data: 'siswa.jenis_kelamin',
              render: jk => jk === 'L' ?
                '<span class="badge bg-label-primary">L</span>' : '<span class="badge bg-label-danger">P</span>'
            },
            {
              // Aksi
              data: 'id',
              orderable: false,
              searchable: false,
              render: (id, t, row) => `
                <div class="d-flex gap-2">
                  <a href="/siswa/${row.siswa.id}/show"
                     class="btn btn-icon btn-sm btn-text-secondary rounded-pill"
                     title="Detail siswa">
                    <i class="ri ri-eye-line icon-18px"></i>
                  </a>
                  <button class="btn btn-icon btn-sm btn-text-secondary rounded-pill btn-hapus-anggota"
                    data-id="${id}" data-nama="${row.siswa.nama_siswa}"
                    title="Keluarkan dari rombel">
                    <i class="ri ri-user-unfollow-line icon-18px"></i>
                  </button>
                </div>`
            },
          ],
          order: [
            [2, 'asc']
          ],
          layout: {
            topStart: {
              rowClass: 'row mx-2 my-0 justify-content-beetwen',
              features: [{
                pageLength: {
                  menu: [10, 25, 50],
                  text: '_MENU_'
                }
              }, ]
            },
            topEnd: {},
            bottomStart: {
              rowClass: 'row mx-3 justify-content-between',
              features: [{
                info: {
                  text: '_END_ dari _TOTAL_ siswa'
                }
              }]
            },
            bottomEnd: 'paging'
          },
          displayLength: 10,
          language: {
            paginate: {
              next: '<i class="ri ri-arrow-right-s-line icon-22px"></i>',
              previous: '<i class="ri ri-arrow-left-s-line icon-22px"></i>',
            },
            emptyTable: '<span class="text-muted"><i class="ri ri-inbox-line me-1"></i> Belum ada anggota di rombel ini.</span>',
            zeroRecords: '<span class="text-muted"><i class="ri ri-search-line me-1"></i> Tidak ada siswa yang cocok.</span>',
          }
        });

        dtAnggota.on('draw.dt', function() {
          const info = dtAnggota.page.info();
          const checkAll = document.getElementById('check-all-anggota');
          if (checkAll) checkAll.checked = false;
          segarkanTombolMassal();
          dtAnggota.column(1, {
              search: 'applied',
              order: 'applied'
            })
            .nodes().each((cell, i) => {
              cell.innerHTML = i + 1 + info.start;
            });
        });
      }

      document.getElementById('search-anggota').addEventListener('input', function() {
        if (dtAnggota) dtAnggota.search(this.value).draw();
      });

      // ── Keluarkan Anggota Massal ─────────────────────────────────────────────
      function segarkanTombolMassal() {
        const terpilih = document.querySelectorAll('.check-anggota:checked').length;
        document.getElementById('jumlah-terpilih').textContent = terpilih;
        document.getElementById('btn-keluarkan-massal').classList.toggle('d-none', terpilih === 0);
      }

      document.addEventListener('change', function(e) {
        if (e.target.classList.contains('check-anggota')) {
          segarkanTombolMassal();
        }
        if (e.target.id === 'check-all-anggota') {
          document.querySelectorAll('.check-anggota')
            .forEach(c => c.checked = e.target.checked);
          segarkanTombolMassal();
        }
      });

      document.getElementById('btn-keluarkan-massal').addEventListener('click', async function() {
        const ids = [...document.querySelectorAll('.check-anggota:checked')].map(c => c.value);
        if (!ids.length) return;

        const konfirmasi = await Swal.fire({
          title: 'Keluarkan Siswa Terpilih?',
          text: `${ids.length} siswa akan dikeluarkan dari rombel ini. Catatan keanggotaan mereka di rombel ini akan dihapus.`,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Ya, Keluarkan!',
          cancelButtonText: 'Batal',
          customClass: {
            confirmButton: 'btn btn-danger me-3',
            cancelButton: 'btn btn-outline-secondary'
          },
          buttonsStyling: false
        });
        if (!konfirmasi.isConfirmed) return;

        try {
          const res = await fetch('/anggota-rombel/hapus-massal', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
              Accept: 'application/json'
            },
            body: JSON.stringify({
              rombel_id: globalRombelId,
              anggota_ids: ids
            })
          });
          const data = await res.json();
          if (res.ok) {
            Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              text: data.message
            });
            dtAnggota.ajax.reload(() => refreshHeaderCounts(), false);
          } else {
            throw new Error(data.message || 'Gagal mengeluarkan siswa');
          }
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: err.message
          });
        }
      });

      // ── Siswa Tersedia (plain table di modal) ─────────────────────────────────
      async function loadSiswaTersedia(rombelId) {
        document.getElementById('tersedia-loading').classList.remove('d-none');
        document.getElementById('tersedia-content').classList.add('d-none');
        document.getElementById('tersedia-empty').classList.add('d-none');

        try {
          const res = await fetch(`/anggota-rombel/${rombelId}/tersedia`, {
            headers: {
              Accept: 'application/json'
            }
          });
          const data = await res.json();
          if (!res.ok) throw new Error(data.message || 'Gagal memuat data');

          allTersedia = data.siswa_tersedia || [];
          document.getElementById('tersedia-loading').classList.add('d-none');

          if (!allTersedia.length) {
            document.getElementById('tersedia-empty').classList.remove('d-none');
            return;
          }
          document.getElementById('tersedia-content').classList.remove('d-none');
          renderTabelTersedia(allTersedia);

        } catch (err) {
          document.getElementById('tersedia-loading').classList.add('d-none');
          document.getElementById('tersedia-empty').innerHTML =
            `<i class="ri ri-error-warning-line ri-2x mb-2 d-block text-danger"></i>${err.message}`;
          document.getElementById('tersedia-empty').classList.remove('d-none');
        }
      }

      function renderTabelTersedia(data) {
        const tbody = document.getElementById('tbody-siswa-tersedia');
        const info = document.getElementById('info-tersedia');

        tbody.innerHTML = data.map(siswa => {
          const reg = siswa.registrasi_terbaru;
          const nisn = siswa.nisn || reg?.nisn || '-';
          const nis = siswa.nis || reg?.nis || '-';
          return `
            <tr>
              <td class="text-center">
                <input type="checkbox" class="form-check-input check-siswa" value="${siswa.id}">
              </td>
              <td>${renderSiswaCell(siswa.nama_siswa, siswa.id)}</td>
              <td><small class="text-muted">${nisn} / ${nis}</small></td>
              <td class="text-center">
                <span class="badge bg-label-secondary">Tingkat ${siswa.tingkat_acuan ?? reg?.tingkat ?? '-'}</span>
              </td>
            </tr>`;
        }).join('');

        info.textContent = `${data.length} siswa tersedia`;
        document.getElementById('check-all').checked = false;
      }

      document.getElementById('search-tersedia').addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        renderTabelTersedia(q ? allTersedia.filter(s => s.nama_siswa.toLowerCase().includes(q)) :
          allTersedia);
      });

      document.getElementById('check-all').addEventListener('change', function() {
        document.querySelectorAll('.check-siswa').forEach(cb => cb.checked = this.checked);
      });

      // ── Form Validation ───────────────────────────────────────────────────────
      const fv = FormValidation.formValidation(formRombel, {
        fields: {
          nama_rombel: {
            validators: {
              notEmpty: {
                message: 'Nama harus diisi'
              }
            }
          },
          tingkat: {
            validators: {
              notEmpty: {
                message: 'Pilih tingkat'
              }
            }
          },
          ptk_id: {
            validators: {
              notEmpty: {
                message: 'Pilih wali kelas'
              }
            }
          },
        },
        plugins: {
          trigger: new FormValidation.plugins.Trigger(),
          bootstrap5: new FormValidation.plugins.Bootstrap5({
            eleValidClass: '',
            rowSelector: '.mb-5'
          }),
          submitButton: new FormValidation.plugins.SubmitButton(),
          autoFocus: new FormValidation.plugins.AutoFocus(),
        }
      }).on('core.form.valid', async () => {
        const formData = new FormData(formRombel);
        if (saveMode !== 'add') formData.append('_method', 'PUT');

        const url = saveMode === 'add' ? '/rombel' : `/rombel/${globalEditId}`;
        const submitBtn = document.getElementById('submit');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

        try {
          const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                .content,
              'X-Requested-With': 'XMLHttpRequest',
              Accept: 'application/json'
            }
          });
          const res = await response.json();
          if (response.ok) {
            await Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              text: res.message
            });
            bootstrap.Offcanvas.getInstance(offcanvasEl).hide();
            formRombel.reset();
            fv.resetForm(true);
            loadTabelRombel();
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
          submitBtn.disabled = false;
          submitBtn.innerHTML = 'Simpan';
        }
      });

      // ── Event Delegation ──────────────────────────────────────────────────────
      document.addEventListener('click', async function(e) {

        // Tambah rombel
        if (e.target.closest('#tambah_rombel')) {
          saveMode = 'add';
          globalEditId = null;
          judulForm.textContent = 'Tambah Rombel Baru';
          formRombel.reset();
          fv.resetForm(true);
          updateTingkatOptions();
          setLockState(false);
        }

        // Edit rombel
        const editBtn = e.target.closest('.btn-edit-rombel');
        if (editBtn) {
          saveMode = 'edit';
          globalEditId = editBtn.dataset.id;
          judulForm.textContent = 'Edit Data Rombel';
          fv.resetForm(true);
          const rowData = allRombel.find(r => r.id == globalEditId);
          if (!rowData) return;
          document.getElementById('rombel_id').value = rowData.id;
          document.getElementById('nama_rombel').value = rowData.nama_rombel;
          document.getElementById('wali_kelas').value = rowData.ptk_id;
          document.getElementById('kurikulum').value = rowData.kurikulum_id ?? '';
          updateTingkatOptions(rowData.tingkat);
          setLockState(rowData.is_locked, rowData.tingkat);
        }

        // Hapus rombel
        const hapusBtn = e.target.closest('.btn-hapus-rombel');
        if (hapusBtn) {
          const id = hapusBtn.dataset.id;
          const confirm = await Swal.fire({
            title: 'Hapus data ini?',
            text: 'Data Rombel akan dihapus secara permanen!',
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
                const res = await fetch(`/rombel/${id}`, {
                  method: 'DELETE',
                  headers: {
                    'X-CSRF-TOKEN': document.querySelector(
                      'meta[name="csrf-token"]').content,
                    Accept: 'application/json'
                  }
                });
                const data = await res.json();
                if (!res.ok) throw new Error(
                  res.status === 422 && data.jumlah_siswa ?
                  `Rombel "${data.nama_rombel}" tidak bisa dihapus karena memiliki ${data.jumlah_siswa} siswa.` :
                  data.message || 'Gagal menghapus data'
                );
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
            loadTabelRombel();
          }
        }

        // Buka view anggota (SPA)
        const anggotaBtn = e.target.closest('.btn-anggota-rombel');
        if (anggotaBtn) {
          const rowData = allRombel[anggotaBtn.dataset.index];
          if (rowData) showViewAnggota(rowData);
        }

        // Buka modal tambah anggota
        if (e.target.closest('#btn-buka-tambah-anggota')) {
          document.getElementById('search-tersedia').value = '';
          allTersedia = [];
          document.getElementById('tbody-siswa-tersedia').innerHTML = '';
          document.getElementById('info-tersedia').textContent = '';
          new bootstrap.Modal(document.getElementById('modal-tambah-anggota')).show();
          loadSiswaTersedia(globalRombelId);
        }

        // Simpan anggota terpilih
        if (e.target.closest('#btn-simpan-anggota')) {
          const ids = [...document.querySelectorAll('.check-siswa:checked')].map(el => el
            .value);
          if (!ids.length) return Swal.fire({
            icon: 'warning',
            title: 'Pilih Siswa',
            text: 'Pilih minimal satu siswa.'
          });

          const btn = e.target.closest('#btn-simpan-anggota');
          btn.disabled = true;
          btn.innerHTML =
            '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

          try {
            const res = await fetch('/anggota-rombel', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector(
                  'meta[name="csrf-token"]').content,
                Accept: 'application/json'
              },
              body: JSON.stringify({
                rombel_id: globalRombelId,
                siswa_ids: ids
              })
            });
            const data = await res.json();
            if (res.ok) {
              bootstrap.Modal.getInstance(document.getElementById('modal-tambah-anggota'))
                .hide();
              Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: data.message
              });
              dtAnggota.ajax.reload(() => refreshHeaderCounts(), false);
            } else {
              throw new Error(data.message || 'Gagal menyimpan');
            }
          } catch (err) {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: err.message
            });
          } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="ri ri-save-line me-1"></i> Simpan Terpilih';
          }
        }

        // Hapus anggota
        const hapusAnggotaBtn = e.target.closest('.btn-hapus-anggota');
        if (hapusAnggotaBtn) {
          const result = await Swal.fire({
            title: 'Keluarkan Siswa?',
            html: `${hapusAnggotaBtn.dataset.nama} akan dikeluarkan dari rombel ini. Catatan keanggotaannya di rombel ini akan dihapus.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Keluarkan!',
            cancelButtonText: 'Batal',
            customClass: {
              confirmButton: 'btn btn-danger me-3',
              cancelButton: 'btn btn-outline-secondary'
            },
            buttonsStyling: false
          });
          if (result.isConfirmed) {
            try {
              const res = await fetch(`/anggota-rombel/${hapusAnggotaBtn.dataset.id}`, {
                method: 'DELETE',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector(
                    'meta[name="csrf-token"]').content,
                  Accept: 'application/json'
                },
              });
              const data = await res.json();
              if (res.ok) {
                Swal.fire({
                  icon: 'success',
                  title: 'Berhasil',
                  text: data.message
                });
                dtAnggota.ajax.reload(() => refreshHeaderCounts(), false);
              } else {
                throw new Error(data.message || 'Gagal mengeluarkan siswa');
              }
            } catch (err) {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.message
              });
            }
          }
        }

      });

    });
  </script>
@endsection
