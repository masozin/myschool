@extends('layouts/layoutMaster')

@section('title', 'Presensi Siswa')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')

  {{-- =========================================================== --}}
  {{-- HEADER INFO                                                  --}}
  {{-- =========================================================== --}}
  <div class="row mb-4 g-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body d-flex align-items-center gap-4 py-4">
          <div class="avatar avatar-xl flex-shrink-0">
            <span class="avatar-initial rounded-circle bg-label-primary">
              <i class="icon-base ri ri-calendar-check-line ri-28px"></i>
            </span>
          </div>
          <div>
            <h4 class="mb-1">Presensi Siswa</h4>
            <span class="text-muted">Pilih rombel dan tanggal untuk mulai mengisi presensi harian.</span>
            @if ($semester)
              <div class="mt-1">
                <span class="badge bg-label-primary">
                  {{ $semester->tahun_pelajaran }} - {{ $semester->semester }}
                </span>
                <small class="text-muted ms-1">
                  ({{ $semester->tanggal_mulai->format('d M Y') }} s/d {{ $semester->tanggal_selesai->format('d M Y') }})
                </small>
              </div>
            @else
              <div class="mt-1">
                <span class="badge bg-label-danger">Semester tidak ditemukan di sesi. Silakan login ulang.</span>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- FILTER: PILIH ROMBEL + TANGGAL                               --}}
  {{-- =========================================================== --}}
  <div class="card mb-4">
    <div class="card-body">
      <form id="form_filter" class="row g-3 align-items-end">
        <div class="col-12 col-md-5">
          <label for="filter_rombel" class="form-label">Rombel</label>
          <select id="filter_rombel" name="rombel_id" class="form-select">
            <option value="">Pilih rombel</option>
            @foreach ($rombelList as $r)
              <option value="{{ $r->id }}" {{ (int) request('rombel_id') === $r->id ? 'selected' : '' }}>{{ $r->nama_rombel }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-4">
          <label for="filter_tanggal" class="form-label">Tanggal</label>
          <input type="date" id="filter_tanggal" name="tanggal" class="form-control"
            value="{{ now()->between(optional($semester)->tanggal_mulai, optional($semester)->tanggal_selesai) ? now()->format('Y-m-d') : optional($semester)->tanggal_mulai?->format('Y-m-d') }}"
            @if ($semester) min="{{ $semester->tanggal_mulai->format('Y-m-d') }}"
              max="{{ $semester->tanggal_selesai->format('Y-m-d') }}"
            @else
              disabled @endif>
        </div>
        <div class="col-12 col-md-3">
          <button type="submit" id="btn_tampilkan" class="btn btn-primary w-100"
            @if (!$semester) disabled @endif>
            <i class="ri ri-search-line me-1"></i> Tampilkan
          </button>
        </div>
      </form>
      @hasanyrole('Operator Sekolah')
        <div class="d-flex justify-content-end mt-3">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal"
            data-bs-target="#modal-pengaturan-presensi">
            <i class="ri ri-settings-3-line me-1"></i> Pengaturan Presensi
          </button>
        </div>
      @endhasanyrole
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- STATE: BELUM DIPILIH                                         --}}
  {{-- =========================================================== --}}
  <div id="state_kosong" class="card">
    <div class="card-body text-center py-5 text-muted">
      <i class="ri ri-file-list-3-line ri-2x mb-2 d-block"></i>
      Silakan pilih rombel dan tanggal, lalu klik "Tampilkan".
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- STATE: LOADING                                                --}}
  {{-- =========================================================== --}}
  <div id="state_loading" class="card d-none">
    <div class="card-body text-center py-5 text-muted">
      <span class="spinner-border spinner-border-sm me-2"></span> Memuat data siswa...
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- STATE: GRID PRESENSI                                          --}}
  {{-- =========================================================== --}}
  <div id="state_grid" class="card d-none {{ !$isEditable ? 'semester-locked' : '' }}">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <h5 class="mb-0" id="grid_judul_rombel">-</h5>
        <small class="text-muted" id="grid_judul_tanggal">-</small>
        @if (!$isEditable)
          <div class="mt-1">
            <span class="badge bg-label-warning">
              <i class="ri ri-lock-line me-1"></i> Semester ini terkunci, presensi tidak dapat diubah.
            </span>
          </div>
        @endif
        <div class="mt-1 d-none" id="grid_info_akses">
          <span class="badge bg-label-info">
            <i class="ri ri-eye-line me-1"></i> Mode pantau — Anda hanya dapat melihat presensi ini.
          </span>
        </div>
      </div>
      <div class="d-flex gap-2 align-items-center">
        <button type="button" class="btn btn-outline-secondary btn-sm btn-tambah" id="btn_tandai_semua_hadir">
          <i class="ri ri-checkbox-circle-line me-1"></i> Tandai Semua Hadir
        </button>
        <button type="button" class="btn btn-primary btn-sm btn-simpan" id="btn_simpan_presensi"
          {{ !$isEditable ? 'disabled' : '' }}>
          <i class="ri ri-save-line me-1"></i> Simpan Presensi
        </button>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered mb-0">
        <thead class="table-light">
          <tr>
            <th width="40" class="text-center">#</th>
            <th>Nama Siswa</th>
            <th>NISN</th>
            <th class="text-center" width="320">Status</th>
            <th width="220">Keterangan</th>
          </tr>
        </thead>
        <tbody id="tbody_presensi"></tbody>
      </table>
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- STATE: TIDAK ADA SISWA / ERROR                                --}}
  {{-- =========================================================== --}}
  <div id="state_error" class="card d-none">
    <div class="card-body text-center py-5 text-warning" id="state_error_message">
      -
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- MODAL: PENGATURAN PRESENSI (Operator Sekolah)                --}}
  {{-- =========================================================== --}}
  @hasanyrole('Operator Sekolah')
    <div class="modal fade" id="modal-pengaturan-presensi" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Pengaturan Presensi</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <label for="batas_koreksi_hari" class="form-label">Batas koreksi presensi untuk Guru (hari)</label>
            <input type="number" id="batas_koreksi_hari" class="form-control" min="0" max="365"
              value="{{ $batasKoreksi ?? 7 }}">
            <div class="form-text">
              Guru wali kelas hanya dapat mengisi/mengubah presensi sampai sejumlah hari ke belakang
              dari hari ini. Operator Sekolah tidak dibatasi. Isi 0 bila Guru hanya boleh mengisi
              pada hari yang sama.
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-primary" id="btn_simpan_pengaturan">Simpan</button>
          </div>
        </div>
      </div>
    </div>
  @endhasanyrole

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {

      // ── Elemen & State ───────────────────────────────────────────────
      const formFilter = document.getElementById('form_filter');
      const stateKosong = document.getElementById('state_kosong');
      const stateLoading = document.getElementById('state_loading');
      const stateGrid = document.getElementById('state_grid');
      const stateError = document.getElementById('state_error');
      const stateErrorMsg = document.getElementById('state_error_message');
      const tbody = document.getElementById('tbody_presensi');
      const btnSimpan = document.getElementById('btn_simpan_presensi');
      const btnHadirSemua = document.getElementById('btn_tandai_semua_hadir');

      const STATUS_LABEL = {
        hadir: 'Hadir',
        izin: 'Izin',
        sakit: 'Sakit',
        alpa: 'Alpa',
      };

      let currentRombelId = null;
      let currentTanggal = null;

      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

      // ── Helper: tampilkan salah satu state, sembunyikan lainnya ──────
      function showState(state) {
        [stateKosong, stateLoading, stateGrid, stateError].forEach(el => el.classList.add('d-none'));
        state.classList.remove('d-none');
      }

      // ── Render baris siswa ke tabel ───────────────────────────────────
      function renderGrid(siswaList) {
        tbody.innerHTML = '';

        siswaList.forEach((s, idx) => {
          const tr = document.createElement('tr');
          tr.dataset.siswaId = s.siswa_id;

          const radios = Object.entries(STATUS_LABEL).map(([value, label]) => `
            <div class="form-check form-check-inline">
              <input class="form-check-input status-radio" type="radio"
                name="status_${s.siswa_id}" id="status_${s.siswa_id}_${value}"
                value="${value}" ${s.status === value ? 'checked' : ''}>
              <label class="form-check-label" for="status_${s.siswa_id}_${value}">${label}</label>
            </div>
          `).join('');

          tr.innerHTML = `
            <td class="text-center">${idx + 1}</td>
            <td>${s.nama_siswa}${s.terisi && s.diisi_oleh ? `<br><small class="text-muted">Terakhir diisi ${s.diisi_oleh} • ${s.diubah_pada}</small>` : ''}</td>
            <td>${s.nisn ?? '-'}</td>
            <td>${radios}</td>
            <td>
              <input type="text" class="form-control form-control-sm keterangan-input"
                placeholder="Opsional" value="${s.keterangan ?? ''}">
            </td>
          `;

          tbody.appendChild(tr);
        });
      }

      // ── Submit filter: load data siswa via AJAX ───────────────────────
      formFilter.addEventListener('submit', async function(e) {
        e.preventDefault();

        const rombelId = document.getElementById('filter_rombel').value;
        const tanggal = document.getElementById('filter_tanggal').value;

        if (!rombelId || !tanggal) {
          Swal.fire('Lengkapi data', 'Pilih rombel dan tanggal terlebih dahulu.', 'warning');
          return;
        }

        showState(stateLoading);

        try {
          const params = new URLSearchParams({
            rombel_id: rombelId,
            tanggal
          });
          const res = await fetch(`{{ route('presensi.ajaxSiswa') }}?${params.toString()}`, {
            headers: {
              'Accept': 'application/json'
            },
          });
          const data = await res.json();

          if (!res.ok || !data.success) {
            stateErrorMsg.textContent = data.message ?? 'Terjadi kesalahan.';
            showState(stateError);
            return;
          }

          if (data.siswa.length === 0) {
            stateErrorMsg.textContent = 'Tidak ada siswa aktif di rombel ini pada tanggal tersebut.';
            showState(stateError);
            return;
          }

          currentRombelId = rombelId;
          currentTanggal = tanggal;

          document.getElementById('grid_judul_rombel').textContent = data.rombel.nama_rombel;
          document.getElementById('grid_judul_tanggal').textContent =
            new Date(tanggal + 'T00:00:00').toLocaleDateString('id-ID', {
              weekday: 'long',
              year: 'numeric',
              month: 'long',
              day: 'numeric'
            });

          renderGrid(data.siswa);

          // Mode pantau (Kepala Sekolah/Admin) atau di luar jendela
          // koreksi Guru: kunci semua isian pada grid ini
          const bolehIsi = data.boleh_isi !== false;
          document.getElementById('grid_info_akses').classList.toggle('d-none', bolehIsi);
          btnSimpan.disabled = !bolehIsi;
          btnHadirSemua.disabled = !bolehIsi;
          if (!bolehIsi) {
            tbody.querySelectorAll('input').forEach(el => el.disabled = true);
          }

          showState(stateGrid);

        } catch (err) {
          console.error(err);
          stateErrorMsg.textContent = 'Gagal memuat data. Silakan coba lagi.';
          showState(stateError);
        }
      });

      // ── Tandai semua hadir ─────────────────────────────────────────────
      btnHadirSemua.addEventListener('click', function() {
        tbody.querySelectorAll('tr').forEach(tr => {
          const siswaId = tr.dataset.siswaId;
          const radioHadir = tr.querySelector(`#status_${siswaId}_hadir`);
          if (radioHadir) radioHadir.checked = true;
        });
      });

      // ── Simpan presensi via AJAX ────────────────────────────────────────
      btnSimpan.addEventListener('click', async function() {
        const rows = tbody.querySelectorAll('tr');
        const presensi = [];

        rows.forEach(tr => {
          const siswaId = tr.dataset.siswaId;
          const statusChecked = tr.querySelector(`input[name="status_${siswaId}"]:checked`);
          const keterangan = tr.querySelector('.keterangan-input').value.trim();

          presensi.push({
            siswa_id: siswaId,
            status: statusChecked ? statusChecked.value : 'alpa',
            keterangan: keterangan || null,
          });
        });

        btnSimpan.disabled = true;
        btnSimpan.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

        try {
          const res = await fetch(`{{ route('presensi.store') }}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
              rombel_id: currentRombelId,
              tanggal: currentTanggal,
              presensi,
            }),
          });
          const data = await res.json();

          if (!res.ok || !data.success) {
            Swal.fire('Gagal', data.message ?? 'Terjadi kesalahan saat menyimpan.', 'error');
            return;
          }

          Swal.fire({
            icon: 'success',
            title: 'Tersimpan',
            text: data.message,
            timer: 1500,
            showConfirmButton: false,
          });

        } catch (err) {
          console.error(err);
          Swal.fire('Gagal', 'Terjadi kesalahan jaringan.', 'error');
        } finally {
          btnSimpan.disabled = false;
          btnSimpan.innerHTML = '<i class="ri ri-save-line me-1"></i> Simpan Presensi';
        }
      });

      // ── Simpan pengaturan presensi (Operator Sekolah) ─────────────
      const btnPengaturan = document.getElementById('btn_simpan_pengaturan');
      if (btnPengaturan) {
        btnPengaturan.addEventListener('click', async function() {
          const nilai = document.getElementById('batas_koreksi_hari').value;
          btnPengaturan.disabled = true;

          try {
            const res = await fetch(`{{ route('presensi.pengaturan') }}`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
              },
              body: JSON.stringify({
                batas_koreksi_hari: nilai
              }),
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
              Swal.fire('Gagal', data.message ?? 'Terjadi kesalahan.', 'error');
              return;
            }

            bootstrap.Modal.getInstance(document.getElementById('modal-pengaturan-presensi'))?.hide();
            Swal.fire({
              icon: 'success',
              title: 'Tersimpan',
              text: data.message,
              timer: 1500,
              showConfirmButton: false,
            });
          } catch (err) {
            console.error(err);
            Swal.fire('Gagal', 'Terjadi kesalahan jaringan.', 'error');
          } finally {
            btnPengaturan.disabled = false;
          }
        });
      }

    });
  </script>
@endsection
