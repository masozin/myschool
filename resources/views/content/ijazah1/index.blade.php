@extends('layouts/layoutMaster')

@section('title', 'Surat Keterangan Lulus (SKL)')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')

  {{-- ── Info Header ──────────────────────────────────────────────────────── --}}
  <div class="row mb-4 g-4">
    <div class="col-12 col-md-7">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-4 py-4">
          <div class="avatar avatar-xl">
            <span class="avatar-initial rounded-circle bg-label-success">
              <i class="icon-base ri ri-file-text-line ri-28px"></i>
            </span>
          </div>
          <div>
            <h4 class="mb-1">Surat Keterangan Lulus (SKL)</h4>
            <div class="d-flex gap-4 flex-wrap">
              <span class="text-muted">
                <i class="icon-base ri ri-calendar-line me-1"></i>
                {{ $semester->tahun_pelajaran ?? '-' }}
              </span>
              <span class="text-muted">
                <i class="icon-base ri ri-group-line me-1"></i>
                {{ $daftarSiswa->count() }} Siswa Kelas Akhir (Tingkat {{ $tingkatAkhir }})
              </span>
              @if ($konfigurasi->tempat_terbit)
                <span class="text-muted">
                  <i class="icon-base ri ri-map-pin-line me-1"></i>
                  {{ $konfigurasi->lokasiTanggalTerbit() }}
                </span>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Statistik Status --}}
    <div class="col-12 col-md-5">
      <div class="card h-100">
        <div class="card-body py-4">
          <h6 class="mb-3">Rekap Status SKL</h6>
          <div class="d-flex flex-column gap-2">
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted small">Belum Dibuat</span>
              <span class="badge bg-label-secondary">{{ $statBelum ?? 0 }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted small">Draft</span>
              <span class="badge bg-label-info">{{ $statDraft ?? 0 }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted small">Pengajuan</span>
              <span class="badge bg-label-warning">{{ $statPengajuan ?? 0 }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted small">Disetujui</span>
              <span class="badge bg-label-success">{{ $statDisetujui ?? 0 }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="text-muted small">Ditolak</span>
              <span class="badge bg-label-danger">{{ $statDitolak ?? 0 }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Toolbar ──────────────────────────────────────────────────────────── --}}
  <div class="card mb-4">
    <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div class="d-flex gap-2 flex-wrap">
        @hasanyrole('Admin')
          <a href="{{ route('skl.komponen.index') }}" class="btn btn-label-primary btn-sm">
            <i class="icon-base ri ri-settings-3-line me-1"></i>Komponen Nilai
          </a>
        @endhasanyrole
        <a href="{{ route('skl.konfigurasi.edit') }}" class="btn btn-label-secondary btn-sm">
          <i class="icon-base ri ri-equalizer-line me-1"></i>Konfigurasi SKL
        </a>
      </div>
      <div class="d-flex gap-2 flex-wrap align-items-center">
        {{-- Filter Status --}}
        <select class="form-select form-select-sm" id="filter-status" style="width:auto">
          <option value="">Semua Status</option>
          <option value="belum" {{ request('status') === 'belum' ? 'selected' : '' }}>Belum Dibuat</option>
          <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
          <option value="pengajuan" {{ request('status') === 'pengajuan' ? 'selected' : '' }}>Pengajuan</option>
          <option value="disetujui" {{ request('status') === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
          <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
        </select>
      </div>
    </div>
  </div>

  {{-- ── Tabel Daftar Siswa ───────────────────────────────────────────────── --}}
  <div class="card">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">Daftar Siswa Kelas Akhir — SKL</h5>
    </div>
    <div class="card-datatable text-nowrap">
      <table class="table table-bordered table-hover" id="tabel-skl">
        <thead>
          <tr>
            <th>#</th>
            <th>Nama Siswa</th>
            <th>NISN</th>
            <th>Kelas / Rombel</th>
            <th>Mode Input</th>
            <th>Nomor SKL</th>
            <th>Status</th>
            <th>Kelengkapan Nilai</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($daftarSiswa as $item)
            @php
              $siswa = $item['siswa'];
              $skl = $item['skl'];
              $words = explode(' ', $siswa->nama_siswa);
              $initials = strtoupper(substr($words[0], 0, 1) . (count($words) > 1 ? substr(end($words), 0, 1) : ''));
              $avatarColor = $siswa->jenis_kelamin === 'L' ? 'primary' : 'danger';
              $rombel = $siswa->rombelAnggota->last()?->rombel?->nama_rombel ?? '-';

              // Kelengkapan nilai
              if ($skl) {
                  $totalMapel = $skl->nilais->count();
                  $sudahDiisi = $skl->nilais->whereNotNull('nilai_akhir')->count();
                  $persen = $totalMapel > 0 ? round(($sudahDiisi / $totalMapel) * 100) : 0;
              }
            @endphp
            <tr>
              <td></td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <div class="avatar avatar-sm">
                    @if ($siswa->photo)
                      <img src="{{ asset('storage/' . $siswa->photo) }}" class="rounded-circle"
                        style="object-fit:cover;width:32px;height:32px;">
                    @else
                      <span
                        class="avatar-initial rounded-circle bg-label-{{ $avatarColor }}">{{ $initials }}</span>
                    @endif
                  </div>
                  <div>
                    <span class="fw-medium d-block text-heading">{{ $siswa->nama_siswa }}</span>
                    <small class="text-muted">{{ $siswa->nisn }}</small>
                  </div>
                </div>
              </td>
              <td>{{ $siswa->nisn }}</td>
              <td>{{ $rombel }}</td>
              <td>
                @if ($skl)
                  @if ($skl->mode_input === 'jadi')
                    <span class="badge bg-label-info">Nilai Jadi</span>
                  @else
                    <span class="badge bg-label-purple">Manual</span>
                  @endif
                @else
                  <span class="text-muted small">-</span>
                @endif
              </td>
              <td>{{ $skl?->nomor_skl ?? '-' }}</td>
              <td>
                @if (!$skl)
                  <span class="badge bg-label-secondary">Belum Dibuat</span>
                @else
                  @php
                    $statusMap = [
                        'draft' => ['info', 'Draft'],
                        'pengajuan' => ['warning', 'Pengajuan'],
                        'disetujui' => ['success', 'Disetujui'],
                        'ditolak' => ['danger', 'Ditolak'],
                    ];
                    [$color, $label] = $statusMap[$skl->status] ?? ['secondary', $skl->status];
                  @endphp
                  <span class="badge bg-label-{{ $color }}">{{ $label }}</span>
                @endif
              </td>
              <td style="min-width:130px">
                @if ($skl)
                  <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:6px">
                      <div class="progress-bar bg-{{ $persen === 100 ? 'success' : 'primary' }}"
                        style="width:{{ $persen }}%"></div>
                    </div>
                    <small class="text-muted">{{ $sudahDiisi }}/{{ $totalMapel }}</small>
                  </div>
                @else
                  <span class="text-muted small">-</span>
                @endif
              </td>
              <td>
                <div class="d-flex gap-1 flex-wrap">
                  @if (!$skl)
                    {{-- Buat SKL --}}
                    @hasanyrole('Admin|Operator Sekolah|Guru')
                      <button class="btn btn-sm btn-icon btn-text-primary rounded-pill btn-buat-skl"
                        data-siswa-id="{{ $siswa->id }}" data-nama="{{ $siswa->nama_siswa }}" title="Buat SKL">
                        <i class="icon-base ri ri-add-circle-line icon-18px"></i>
                      </button>
                    @endhasanyrole
                  @else
                    {{-- Input Nilai --}}
                    @if (in_array($skl->status, ['draft', 'ditolak']))
                      <a href="{{ route('skl.nilai.edit', $skl) }}"
                        class="btn btn-sm btn-icon btn-text-primary rounded-pill" title="Input Nilai">
                        <i class="icon-base ri ri-edit-line icon-18px"></i>
                      </a>
                    @endif

                    {{-- Input Nomor SKL --}}
                    <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill btn-nomor-skl"
                      data-id="{{ $skl->id }}" data-nomor="{{ $skl->nomor_skl ?? '' }}"
                      data-tanggal="{{ $skl->tanggal_terbit?->format('Y-m-d') ?? '' }}"
                      data-nama="{{ $siswa->nama_siswa }}" title="Input Nomor SKL">
                      <i class="icon-base ri ri-hashtag icon-18px"></i>
                    </button>

                    {{-- Detail --}}
                    <a href="{{ route('skl.show', $skl) }}" class="btn btn-sm btn-icon btn-text-info rounded-pill"
                      title="Lihat Detail">
                      <i class="icon-base ri ri-eye-line icon-18px"></i>
                    </a>

                    {{-- Ajukan --}}
                    @if (in_array($skl->status, ['draft', 'ditolak']))
                      <button class="btn btn-sm btn-icon btn-text-warning rounded-pill btn-ajukan"
                        data-id="{{ $skl->id }}" data-nama="{{ $siswa->nama_siswa }}" title="Ajukan">
                        <i class="icon-base ri ri-send-plane-line icon-18px"></i>
                      </button>
                    @endif

                    {{-- Setujui / Tolak (Admin) --}}
                    @hasanyrole('Admin')
                      @if ($skl->status === 'pengajuan')
                        <button class="btn btn-sm btn-icon btn-text-success rounded-pill btn-setujui"
                          data-id="{{ $skl->id }}" title="Setujui">
                          <i class="icon-base ri ri-checkbox-circle-line icon-18px"></i>
                        </button>
                        <button class="btn btn-sm btn-icon btn-text-danger rounded-pill btn-tolak"
                          data-id="{{ $skl->id }}" title="Tolak">
                          <i class="icon-base ri ri-close-circle-line icon-18px"></i>
                        </button>
                      @endif
                    @endhasanyrole

                    {{-- Hapus --}}
                    @if ($skl->status !== 'disetujui')
                      <button class="btn btn-sm btn-icon btn-text-danger rounded-pill btn-hapus"
                        data-id="{{ $skl->id }}" data-nama="{{ $siswa->nama_siswa }}" title="Hapus">
                        <i class="icon-base ri ri-delete-bin-line icon-18px"></i>
                      </button>
                    @endif
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  {{-- ── Modal Tolak ─────────────────────────────────────────────────────── --}}
  <div class="modal fade" id="modal-tolak" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="icon-base ri ri-close-circle-line me-1"></i>Tolak Pengajuan SKL</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">Catatan / Alasan Penolakan <span class="text-danger">*</span></label>
          <textarea id="catatan-tolak" class="form-control" rows="3" placeholder="Tuliskan alasan penolakan..."></textarea>
          <div id="error-catatan" class="text-danger small mt-1 d-none">Catatan wajib diisi.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-danger" id="btn-konfirm-tolak">
            <i class="icon-base ri ri-close-circle-line me-1"></i>Tolak
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Modal Input Nomor SKL ────────────────────────────────────────────── --}}
  <div class="modal fade" id="modal-nomor-skl" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="icon-base ri ri-hashtag me-1"></i>Input Nomor SKL
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small mb-3" id="nomor-skl-nama-siswa"></p>

          <div class="mb-3">
            <label class="form-label fw-semibold">Nomor SKL</label>
            <div class="input-group">
              <input type="text" id="input-nomor-skl" class="form-control" placeholder="Contoh: SKL/001/JUNI/2025">
              <button class="btn btn-outline-secondary btn-sm" type="button" id="btn-generate-nomor"
                title="Generate otomatis">
                <i class="icon-base ri ri-magic-line"></i>
              </button>
            </div>
            <div class="form-text">Klik <i class="ri ri-magic-line"></i> untuk generate nomor otomatis, atau isi manual.
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Tanggal Terbit</label>
            <input type="date" id="input-tanggal-skl" class="form-control">
            <div class="form-text">
              @if ($konfigurasi->tanggal_terbit)
                Default dari konfigurasi: {{ $konfigurasi->tanggal_terbit->translatedFormat('d F Y') }}
              @else
                Tanggal terbit SKL (opsional).
              @endif
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-primary" id="btn-simpan-nomor">
            <i class="icon-base ri ri-save-line me-1"></i>Simpan
          </button>
        </div>
      </div>
    </div>
  </div>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;

      // ── DataTable ─────────────────────────────────────────────────────────────
      new DataTable('#tabel-skl', {
        scrollX: true,
        order: [
          [1, 'asc']
        ],
        columnDefs: [{
          targets: 0,
          orderable: false,
          searchable: false
        }],
        drawCallback: function() {
          const api = this.api();
          const start = api.page.info().start;
          api.rows({
            page: 'current'
          }).nodes().each(function(row, i) {
            row.cells[0].innerHTML = start + i + 1;
          });
        },
        layout: {
          topStart: {
            features: [{
              pageLength: {
                menu: [10, 25, 50],
                text: '_MENU_'
              }
            }]
          },
          topEnd: {
            rowClass: 'row ms-1 me-2 my-0 py-0 justify-content-between',
            features: [{
              search: {
                text: '',
                placeholder: 'Cari nama / NISN...'
              }
            }],
          },
          bottomStart: {
            rowClass: 'row mx-3 justify-content-between',
            features: [{
              info: {
                text: '_END_ dari _TOTAL_ siswa'
              }
            }],
          },
          bottomEnd: 'paging',
        },
      });

      // ── Filter Status ─────────────────────────────────────────────────────────
      document.getElementById('filter-status')?.addEventListener('change', function() {
        const url = new URL(window.location.href);
        if (this.value) url.searchParams.set('status', this.value);
        else url.searchParams.delete('status');
        window.location = url;
      });

      // ── Helper fetch ──────────────────────────────────────────────────────────
      async function apiFetch(url, body = null, method = 'POST') {
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

      // ── Buat SKL baru ─────────────────────────────────────────────────────────
      document.addEventListener('click', async function(e) {
        const btn = e.target.closest('.btn-buat-skl');
        if (!btn) return;

        const konfirm = await Swal.fire({
          icon: 'question',
          title: 'Buat Data SKL?',
          text: `Buat data SKL untuk ${btn.dataset.nama}?`,
          showCancelButton: true,
          confirmButtonText: 'Ya, Buat',
          cancelButtonText: 'Batal',
          customClass: {
            confirmButton: 'btn btn-primary me-3',
            cancelButton: 'btn btn-outline-secondary'
          },
          buttonsStyling: false,
        });
        if (!konfirm.isConfirmed) return;

        const res = await apiFetch('{{ route('skl.store') }}', {
          siswa_id: btn.dataset.siswaId
        });
        const data = await res.json();

        if (res.ok) {
          Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: data.message,
            showCancelButton: true,
            confirmButtonText: 'Input Nilai Sekarang',
            cancelButtonText: 'Nanti',
            customClass: {
              confirmButton: 'btn btn-primary me-3',
              cancelButton: 'btn btn-outline-secondary'
            },
            buttonsStyling: false,
          }).then(r => {
            if (r.isConfirmed && data.edit_url) {
              window.location = data.edit_url;
            } else {
              location.reload();
            }
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: data.message ?? 'Terjadi kesalahan.'
          });
        }
      });

      // ── Modal Nomor SKL ───────────────────────────────────────────────────────
      let nomorSklId = null;
      const modalNomor = new bootstrap.Modal(document.getElementById('modal-nomor-skl'));
      const inputNomor = document.getElementById('input-nomor-skl');
      const inputTanggal = document.getElementById('input-tanggal-skl');
      const defaultTanggal = '{{ $konfigurasi->tanggal_terbit?->format('Y-m-d') ?? '' }}';

      document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-nomor-skl');
        if (!btn) return;
        nomorSklId = btn.dataset.id;
        inputNomor.value = btn.dataset.nomor || '';
        inputTanggal.value = btn.dataset.tanggal || defaultTanggal;
        document.getElementById('nomor-skl-nama-siswa').textContent = 'Siswa: ' + btn.dataset.nama;
        modalNomor.show();
      });

      // Generate nomor otomatis
      document.getElementById('btn-generate-nomor')?.addEventListener('click', async function() {
        if (!nomorSklId) return;
        this.disabled = true;
        const res = await apiFetch(`/skl/${nomorSklId}/generate-nomor`);
        const data = await res.json();
        if (res.ok) inputNomor.value = data.nomor;
        this.disabled = false;
      });

      // Simpan nomor SKL
      document.getElementById('btn-simpan-nomor')?.addEventListener('click', async function() {
        if (!nomorSklId) return;
        this.disabled = true;
        const res = await apiFetch(`/skl/${nomorSklId}/nomor`, {
          nomor_skl: inputNomor.value,
          tanggal_terbit: inputTanggal.value,
        }, 'PUT');
        const data = await res.json();
        this.disabled = false;

        if (res.ok) {
          modalNomor.hide();
          Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: data.message,
            timer: 2000,
            showConfirmButton: false
          });
          setTimeout(() => location.reload(), 2100);
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: data.message
          });
        }
      });

      // ── Ajukan ────────────────────────────────────────────────────────────────
      document.addEventListener('click', async function(e) {
        const btn = e.target.closest('.btn-ajukan');
        if (!btn) return;
        const konfirm = await Swal.fire({
          icon: 'question',
          title: 'Ajukan SKL?',
          text: `SKL ${btn.dataset.nama} akan diajukan untuk persetujuan.`,
          showCancelButton: true,
          confirmButtonText: 'Ya, Ajukan',
          cancelButtonText: 'Batal',
          customClass: {
            confirmButton: 'btn btn-warning me-3',
            cancelButton: 'btn btn-outline-secondary'
          },
          buttonsStyling: false,
        });
        if (!konfirm.isConfirmed) return;

        const res = await apiFetch(`/skl/${btn.dataset.id}/ajukan`);
        const data = await res.json();
        if (res.ok) {
          Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: data.message,
            timer: 2000,
            showConfirmButton: false
          });
          setTimeout(() => location.reload(), 2100);
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: data.message ?? data.errors?.nilai ?? 'Terjadi kesalahan.'
          });
        }
      });

      // ── Setujui ───────────────────────────────────────────────────────────────
      document.addEventListener('click', async function(e) {
        const btn = e.target.closest('.btn-setujui');
        if (!btn) return;
        const konfirm = await Swal.fire({
          icon: 'question',
          title: 'Setujui SKL?',
          showCancelButton: true,
          confirmButtonText: 'Ya, Setujui',
          cancelButtonText: 'Batal',
          customClass: {
            confirmButton: 'btn btn-success me-3',
            cancelButton: 'btn btn-outline-secondary'
          },
          buttonsStyling: false,
        });
        if (!konfirm.isConfirmed) return;

        const res = await apiFetch(`/skl/${btn.dataset.id}/setujui`);
        const data = await res.json();
        if (res.ok) {
          Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: data.message,
            timer: 2000,
            showConfirmButton: false
          });
          setTimeout(() => location.reload(), 2100);
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: data.message
          });
        }
      });

      // ── Tolak ─────────────────────────────────────────────────────────────────
      let tolakId = null;
      const modalTolak = new bootstrap.Modal(document.getElementById('modal-tolak'));
      const inputCatatan = document.getElementById('catatan-tolak');
      const errorCatatan = document.getElementById('error-catatan');

      document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-tolak');
        if (!btn) return;
        tolakId = btn.dataset.id;
        inputCatatan.value = '';
        errorCatatan.classList.add('d-none');
        modalTolak.show();
      });

      document.getElementById('btn-konfirm-tolak')?.addEventListener('click', async function() {
        const catatan = inputCatatan.value.trim();
        if (!catatan) {
          errorCatatan.classList.remove('d-none');
          return;
        }
        errorCatatan.classList.add('d-none');
        this.disabled = true;

        const res = await apiFetch(`/skl/${tolakId}/tolak`, {
          catatan
        });
        const data = await res.json();
        this.disabled = false;

        if (res.ok) {
          modalTolak.hide();
          Swal.fire({
            icon: 'success',
            title: 'Ditolak',
            text: data.message,
            timer: 2000,
            showConfirmButton: false
          });
          setTimeout(() => location.reload(), 2100);
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: data.message
          });
        }
      });

      // ── Hapus ─────────────────────────────────────────────────────────────────
      document.addEventListener('click', async function(e) {
        const btn = e.target.closest('.btn-hapus');
        if (!btn) return;
        const konfirm = await Swal.fire({
          icon: 'warning',
          title: 'Hapus Data SKL?',
          text: `Data SKL ${btn.dataset.nama} akan dihapus permanen.`,
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

        const res = await fetch(`/skl/${btn.dataset.id}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
          },
        });
        const data = await res.json();
        if (res.ok) {
          Swal.fire({
            icon: 'success',
            title: 'Dihapus',
            text: data.message,
            timer: 2000,
            showConfirmButton: false
          });
          setTimeout(() => location.reload(), 2100);
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
