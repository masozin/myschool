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

  <div class="d-flex justify-content-end mb-4">
    <a href="{{ route('presensi.rekap') }}" class="btn btn-label-primary">
      <i class="ri ri-bar-chart-2-line me-1"></i> Rekap Presensi
    </a>
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
          <div class="input-group">
            <button type="button" class="btn btn-outline-secondary" id="btn_tgl_kurang" title="Hari sebelumnya"
              @if (!$semester) disabled @endif>
              <i class="ri ri-arrow-left-s-line"></i>
            </button>
            <input type="date" id="filter_tanggal" name="tanggal" class="form-control"
              value="{{ now()->between(optional($semester)->tanggal_mulai, optional($semester)->tanggal_selesai) ? now()->format('Y-m-d') : optional($semester)->tanggal_mulai?->format('Y-m-d') }}"
              @if ($semester) min="{{ $semester->tanggal_mulai->format('Y-m-d') }}"
                max="{{ $semester->tanggal_selesai->format('Y-m-d') }}"
              @else
                disabled @endif>
            <button type="button" class="btn btn-outline-secondary" id="btn_tgl_tambah" title="Hari berikutnya"
              @if (!$semester) disabled @endif>
              <i class="ri ri-arrow-right-s-line"></i>
            </button>
          </div>
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
      Pilih rombel dan tanggal — daftar siswa tampil otomatis.
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
  {{-- STATE: GRID PRESENSI (kartu siswa, responsif)                 --}}
  {{-- =========================================================== --}}
  <style>
    .btn-status {
      min-width: 46px;
      min-height: 40px;
      font-weight: 600;
      border: 1px solid var(--bs-border-color);
      background-color: transparent;
      color: var(--bs-body-color);
    }

    .btn-status.aktif[data-status="hadir"] {
      background-color: var(--bs-success);
      border-color: var(--bs-success);
      color: #fff;
    }

    .btn-status.aktif[data-status="izin"] {
      background-color: var(--bs-info);
      border-color: var(--bs-info);
      color: #fff;
    }

    .btn-status.aktif[data-status="sakit"] {
      background-color: var(--bs-warning);
      border-color: var(--bs-warning);
      color: #fff;
    }

    .btn-status.aktif[data-status="alpa"] {
      background-color: var(--bs-danger);
      border-color: var(--bs-danger);
      color: #fff;
    }

    .btn-status:disabled {
      opacity: .55;
    }

    .bilah-simpan {
      position: sticky;
      bottom: 0;
      z-index: 6;
      background-color: var(--bs-card-bg, #fff);
    }
  </style>

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
      <button type="button" class="btn btn-outline-success btn-sm btn-tambah" id="btn_tandai_semua_hadir">
        <i class="ri ri-checkbox-circle-line me-1"></i> Semua Hadir
      </button>
    </div>

    <div class="px-4 pt-3 pb-2 border-bottom">
      <div class="row g-2 align-items-center">
        <div class="col-12 col-md-5">
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="ri ri-search-line"></i></span>
            <input type="text" id="cari_siswa" class="form-control" placeholder="Cari nama siswa...">
          </div>
        </div>
        <div class="col-12 col-md-7">
          <div class="d-flex flex-wrap gap-2 justify-content-md-end" id="ringkasan_status">
            <span class="badge bg-label-success">Hadir <span id="jml_hadir">0</span></span>
            <span class="badge bg-label-info">Izin <span id="jml_izin">0</span></span>
            <span class="badge bg-label-warning">Sakit <span id="jml_sakit">0</span></span>
            <span class="badge bg-label-danger">Alpa <span id="jml_alpa">0</span></span>
          </div>
        </div>
      </div>
    </div>

    <div id="daftar_siswa" class="list-group list-group-flush"></div>

    <div class="card-footer bilah-simpan d-flex justify-content-between align-items-center flex-wrap gap-2">
      <small class="text-muted" id="info_terisi">-</small>
      <button type="button" class="btn btn-primary btn-simpan" id="btn_simpan_presensi"
        {{ !$isEditable ? 'disabled' : '' }}>
        <i class="ri ri-save-line me-1"></i> Simpan Presensi
      </button>
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
      const daftarSiswa = document.getElementById('daftar_siswa');
      const btnSimpan = document.getElementById('btn_simpan_presensi');
      const btnHadirSemua = document.getElementById('btn_tandai_semua_hadir');
      const inputCari = document.getElementById('cari_siswa');
      const infoTerisi = document.getElementById('info_terisi');

      const STATUS_LABEL = {
        hadir: 'Hadir',
        izin: 'Izin',
        sakit: 'Sakit',
        alpa: 'Alpa',
      };
      const STATUS_HURUF = {
        hadir: 'H',
        izin: 'I',
        sakit: 'S',
        alpa: 'A',
      };

      let currentRombelId = null;
      let currentTanggal = null;

      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

      // ── Helper: tampilkan salah satu state, sembunyikan lainnya ──────
      function showState(state) {
        [stateKosong, stateLoading, stateGrid, stateError].forEach(el => el.classList.add('d-none'));
        state.classList.remove('d-none');
      }

      // ── Kartu siswa ───────────────────────────────────────────────────
      function inisial(nama) {
        const parts = (nama || '').trim().split(/\s+/);
        return ((parts[0]?.[0] ?? '') + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
      }

      function renderGrid(siswaList) {
        daftarSiswa.innerHTML = '';

        siswaList.forEach((s) => {
          const kartu = document.createElement('div');
          kartu.className = 'list-group-item kartu-siswa py-3';
          kartu.dataset.siswaId = s.siswa_id;
          kartu.dataset.status = s.status || '';
          kartu.dataset.nama = (s.nama_siswa || '').toLowerCase();

          const avatar = s.photo ?
            `<img src="/storage/${s.photo}" alt="" class="rounded-circle" width="42" height="42" style="object-fit:cover;">` :
            `<span class="avatar-initial rounded-circle bg-label-${s.jenis_kelamin === 'L' ? 'primary' : 'danger'}" style="width:42px;height:42px;font-size:14px;display:flex;align-items:center;justify-content:center;">${inisial(s.nama_siswa)}</span>`;

          const tombol = Object.entries(STATUS_LABEL).map(([nilai, label]) => `
            <button type="button" class="btn btn-status ${s.status === nilai ? 'aktif' : ''}"
              data-status="${nilai}" title="${label}">${STATUS_HURUF[nilai]}</button>
          `).join('');

          kartu.innerHTML = `
            <div class="d-flex gap-3 align-items-start">
              <div class="avatar avatar-sm flex-shrink-0">${avatar}</div>
              <div class="flex-grow-1" style="min-width:0;">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                  <div>
                    <div class="fw-medium text-heading">${s.nama_siswa}</div>
                    <small class="text-muted">${s.nisn ?? '-'}${s.terisi && s.diisi_oleh ? ` • Terakhir diisi ${s.diisi_oleh} • ${s.diubah_pada}` : ''}</small>
                  </div>
                  <div class="btn-group pilihan-status" role="group">${tombol}</div>
                </div>
                <input type="text" class="form-control form-control-sm keterangan-input mt-2"
                  placeholder="Keterangan (opsional)" value="${s.keterangan ?? ''}">
              </div>
            </div>`;

          daftarSiswa.appendChild(kartu);
        });

        perbaruiRingkasan();
      }

      function aturStatus(kartu, status) {
        kartu.dataset.status = status;
        kartu.querySelectorAll('.btn-status')
          .forEach(b => b.classList.toggle('aktif', b.dataset.status === status));
        perbaruiRingkasan();
      }

      function perbaruiRingkasan() {
        const hitung = {
          hadir: 0,
          izin: 0,
          sakit: 0,
          alpa: 0
        };
        let total = 0;

        daftarSiswa.querySelectorAll('.kartu-siswa').forEach(k => {
          if (hitung[k.dataset.status] !== undefined) hitung[k.dataset.status]++;
          total++;
        });

        document.getElementById('jml_hadir').textContent = hitung.hadir;
        document.getElementById('jml_izin').textContent = hitung.izin;
        document.getElementById('jml_sakit').textContent = hitung.sakit;
        document.getElementById('jml_alpa').textContent = hitung.alpa;
        const belumDipilih = total - (hitung.hadir + hitung.izin + hitung.sakit + hitung.alpa);
        infoTerisi.textContent =
          `${total} siswa • ${hitung.hadir} hadir, ${hitung.izin + hitung.sakit + hitung.alpa} tidak hadir` +
          (belumDipilih > 0 ? `, ${belumDipilih} belum dipilih` : '');
      }

      // Mode pantau (Kepala Sekolah/Admin) atau di luar jendela
      // koreksi Guru: kunci semua isian pada daftar ini
      function terapkanAkses(bolehIsi) {
        document.getElementById('grid_info_akses').classList.toggle('d-none', bolehIsi);
        btnSimpan.disabled = !bolehIsi;
        btnHadirSemua.disabled = !bolehIsi;
        daftarSiswa.querySelectorAll('.btn-status, .keterangan-input')
          .forEach(el => el.disabled = !bolehIsi);
      }

      // Ketuk tombol status pada kartu siswa
      daftarSiswa.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-status');
        if (!btn || btn.disabled) return;
        aturStatus(btn.closest('.kartu-siswa'), btn.dataset.status);
      });

      // Cari siswa berdasarkan nama
      inputCari.addEventListener('input', function() {
        const kunci = this.value.trim().toLowerCase();
        daftarSiswa.querySelectorAll('.kartu-siswa').forEach(k => {
          k.classList.toggle('d-none', kunci !== '' && !k.dataset.nama.includes(kunci));
        });
      });

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
          inputCari.value = '';
          terapkanAkses(data.boleh_isi !== false);

          showState(stateGrid);

        } catch (err) {
          console.error(err);
          stateErrorMsg.textContent = 'Gagal memuat data. Silakan coba lagi.';
          showState(stateError);
        }
      });

      // ── Filter berubah = langsung muat, tanpa tombol Tampilkan ──
      const filterRombelEl = document.getElementById('filter_rombel');
      const filterTanggalEl = document.getElementById('filter_tanggal');
      function muatBilaLengkap() {
        if (filterRombelEl.value && filterTanggalEl.value) {
          formFilter.requestSubmit();
        }
      }
      filterRombelEl.addEventListener('change', muatBilaLengkap);
      filterTanggalEl.addEventListener('change', muatBilaLengkap);
      muatBilaLengkap();

      // ── Tandai semua hadir ─────────────────────────────────────────────
      btnHadirSemua.addEventListener('click', function() {
        daftarSiswa.querySelectorAll('.kartu-siswa')
          .forEach(k => aturStatus(k, 'hadir'));
      });

      // ── Navigasi tanggal: sehari sebelum / sesudah ─────────────────────
      function geserTanggal(selisih) {
        const input = document.getElementById('filter_tanggal');
        if (!input.value) return;

        const t = new Date(input.value + 'T00:00:00');
        t.setDate(t.getDate() + selisih);
        const hasil = `${t.getFullYear()}-${String(t.getMonth() + 1).padStart(2, '0')}-${String(t.getDate()).padStart(2, '0')}`;

        if ((input.min && hasil < input.min) || (input.max && hasil > input.max)) return;
        input.value = hasil;

        if (document.getElementById('filter_rombel').value) {
          formFilter.requestSubmit();
        }
      }
      document.getElementById('btn_tgl_kurang')?.addEventListener('click', () => geserTanggal(-1));
      document.getElementById('btn_tgl_tambah')?.addEventListener('click', () => geserTanggal(1));

      // ── Simpan presensi via AJAX ────────────────────────────────────────
      btnSimpan.addEventListener('click', async function() {
        const presensi = [];

        daftarSiswa.querySelectorAll('.kartu-siswa').forEach(k => {
          // Siswa yang belum dipilih statusnya TIDAK dikirim —
          // ia memang belum absen, bukan alpa dan bukan hadir
          if (!k.dataset.status) return;
          presensi.push({
            siswa_id: k.dataset.siswaId,
            status: k.dataset.status,
            keterangan: k.querySelector('.keterangan-input').value.trim() || null,
          });
        });

        if (presensi.length === 0) {
          Swal.fire('Belum ada isian', 'Pilih status minimal satu siswa terlebih dahulu, atau gunakan tombol Semua Hadir.', 'warning');
          return;
        }

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
