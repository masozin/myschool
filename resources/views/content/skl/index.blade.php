@extends('layouts/layoutMaster')

@section('title', 'Surat Keterangan Lulus (SKL)')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')

  @if ((int) session('semester_id') !== (int) $semester->id)
    <div class="alert alert-info">
      <i class="icon-base ri ri-information-line me-1"></i>
      Anda login pada semester <strong>{{ $namaSemester }}</strong>. Dokumen kelulusan selalu milik semester
      <strong>Genap</strong>, jadi yang ditampilkan di sini adalah data
      <strong>{{ $semester->tahun_pelajaran }} — {{ $semester->semester }}</strong>.
    </div>
  @endif

  {{-- ── Info Header ──────────────────────────────────────────────────────── --}}
  <div class="row mb-4 g-4">
    <div class="col-12 col-md-7">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-4 py-4">
          <div class="avatar avatar-xl flex-shrink-0">
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
              @if ($konfigurasi?->tempat_terbit ?? 'Tempat terbit belum diatur')
                <span class="text-muted">
                  <i class="icon-base ri ri-map-pin-line me-1"></i>
                  {{ $konfigurasi?->lokasiTanggalTerbit() ?? 'Lokasi, tanggal terbit belum diatur' }}
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

  {{-- Pemilih konteks sekolah untuk Admin --}}
  @hasanyrole('Admin')
    <div class="card mb-4">
      <div class="card-body py-3 d-flex align-items-center gap-3 flex-wrap">
        <span class="text-muted"><i class="icon-base ri ri-school-line me-1"></i>Anda masuk sebagai Admin — konteks
          sekolah:</span>
        <form method="GET" action="{{ url('/skl') }}">
          <select name="sekolah_id" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach ($daftarSekolah as $ds)
              <option value="{{ $ds->id }}" {{ $ds->id === $sekolahId ? 'selected' : '' }}>
                {{ $ds->detailTerbaru->nama_sekolah ?? 'Sekolah #' . $ds->id }}</option>
            @endforeach
          </select>
        </form>
        <span class="ms-auto d-flex gap-2">
          <a href="{{ route('skl.komponen.index') }}" class="btn btn-label-secondary btn-sm">
            <i class="icon-base ri ri-scales-3-line me-1"></i>Komponen Nilai
          </a>
          <a href="{{ url('/skl/template') }}" class="btn btn-label-secondary btn-sm">
            <i class="icon-base ri ri-layout-line me-1"></i>Kelola Template Cetak
          </a>
        </span>
      </div>
    </div>
  @endhasanyrole

  {{-- ── Toolbar ──────────────────────────────────────────────────────────── --}}
  <div class="card mb-4">
    <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div class="d-flex gap-2 flex-wrap">
        @hasanyrole('Operator Sekolah|Guru')
          <a href="{{ route('skl.konfigurasi.edit') }}" class="btn btn-label-secondary btn-sm">
            <i class="icon-base ri ri-equalizer-line me-1"></i>Konfigurasi SKL
          </a>
          @hasanyrole('Operator Sekolah')
            <a href="{{ route('skl.pengaturan-nomor') }}" class="btn btn-label-secondary btn-sm">
              <i class="icon-base ri ri-hashtag me-1"></i>Tanggal &amp; Penomoran
            </a>
          @endhasanyrole
          <button class="btn btn-label-secondary btn-sm" id="btn-sync-skl" title="Sinkronisasi Data & Bobot">
            <i class="ri ri-refresh-line "></i>
          </button>
          {{-- Tombol Trigger Modal Import --}}
          <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal-import">
            <i class="ri ri-upload-cloud-2-line me-1"></i> Import Nilai
          </button>
        @endhasanyrole
      </div>
      <div class="d-flex gap-2 flex-wrap align-items-center">
        {{-- PANEL AKSI MASAL (Disembunyikan secara default) --}}
        <div id="bulk-action-container"
          class="d-none bg-label-primary px-3 py-1 rounded d-flex align-items-center gap-2 me-2">
          <span class="text-primary small fw-semibold"><span id="selected-count">0</span> terpilih</span>
          <div class="vr mx-1"></div>

          @hasanyrole('Operator Sekolah|Guru')
            <button class="btn btn-sm btn-warning btn-bulk" data-action="ajukan" title="Ajukan Masal"><i
                class="ri ri-send-plane-line"></i></button>
            <button class="btn btn-sm btn-danger btn-bulk" data-action="hapus" title="Hapus Masal"><i
                class="ri ri-delete-bin-line"></i></button>
            <button class="btn btn-sm btn-info" id="btn-bulk-print" title="Cetak ZIP Masal">
              <i class="ri ri-printer-line"></i>
            </button>
          @endhasanyrole

          @hasanyrole('Kepala Sekolah')
            <button class="btn btn-sm btn-success btn-bulk" data-action="setujui" title="Setujui Masal"><i
                class="ri ri-checkbox-circle-line"></i></button>
            <button class="btn btn-sm btn-danger btn-bulk" data-action="tolak" title="Tolak Masal"><i
                class="ri ri-close-circle-line"></i></button>
            <button class="btn btn-sm btn-secondary btn-bulk" data-action="batal-setujui" title="Batal Setujui Masal">
              <i class="ri ri-arrow-go-back-line"></i>
            </button>
          @endhasanyrole
        </div>
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
            <th style="width: 30px;"><input type="checkbox" class="form-check-input" id="check-all"></th>
            <th>#</th>
            <th>Nama Siswa</th>
            <th>Kelas / Rombel</th>
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

              // Reset Variabel Kelengkapan Nilai
              $persen = 0;
              $textKelengkapan = '-';

              if ($skl) {
                  if ($skl->mode_input === 'jadi') {
                      // Mode Jadi: Dihitung berdasarkan jumlah MAPEL yang terisi nilai_akhir
                      $totalMapel = $skl->nilais->count();
                      $sudahDiisi = $skl->nilais->whereNotNull('nilai_akhir')->count();

                      $persen = $totalMapel > 0 ? round(($sudahDiisi / $totalMapel) * 100) : 0;
                      $textKelengkapan = $sudahDiisi . '/' . $totalMapel;
                  } else {
                      // Mode Manual: Dihitung berdasarkan jumlah KOTAK INPUT yang terisi nilai
                      $totalInput = 0;
                      $sudahInput = 0;

                      foreach ($skl->nilais as $sklNilai) {
                          foreach ($sklNilai->komponens as $kn) {
                              // Jika komponen memiliki sub-komponen, hitung jumlah sub-komponennya
                              if ($kn->komponen && $kn->komponen->subKomponen->isNotEmpty()) {
                                  foreach ($kn->nilaiSubKomponen as $nsk) {
                                      $totalInput++;
                                      if ($nsk->nilai !== null) {
                                          $sudahInput++;
                                      }
                                  }
                              } else {
                                  // Jika komponen tidak punya sub-komponen, hitung komponen itu sendiri
                                  $totalInput++;
                                  if ($kn->nilai !== null) {
                                      $sudahInput++;
                                  }
                              }
                          }
                      }

                      $persen = $totalInput > 0 ? round(($sudahInput / $totalInput) * 100) : 0;
                      $textKelengkapan = $sudahInput . '/' . $totalInput;
                  }
              }
            @endphp
            <tr>
              <td>
                @if ($skl)
                  <input type="checkbox" class="form-check-input check-item" value="{{ $skl->id }}">
                @else
                  <input type="checkbox" class="form-check-input" disabled title="SKL belum dibuat">
                @endif
              </td>
              <td></td>
              <td>
                <a href="{{ route('siswa.show', $siswa->id) }}" class="text-decoration-none text-reset">
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
                </a>
              </td>
              <td>{{ $rombel }}</td>
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
              {{-- Kolom Progress Kelengkapan Nilai yang Baru --}}
              <td style="min-width:130px">
                @if ($skl)
                  <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:6px">
                      <div class="progress-bar bg-{{ $persen === 100 ? 'success' : 'primary' }}"
                        style="width:{{ $persen }}%"></div>
                    </div>
                    <small class="text-muted">{{ $textKelengkapan }}</small>
                  </div>
                @else
                  <span class="text-muted small">-</span>
                @endif
              </td>
              <td>
                <div class="d-flex gap-1">
                  @if (!$skl)
                    @hasanyrole('Operator Sekolah|Guru')
                      <button class="btn btn-sm btn-icon btn-text-primary rounded-pill btn-buat-skl"
                        data-siswa-id="{{ $siswa->id }}" data-nama="{{ $siswa->nama_siswa }}" title="Buat SKL">
                        <i class="icon-base ri ri-add-circle-line icon-18px"></i>
                      </button>
                    @endhasanyrole
                  @else
                    @hasanyrole('Operator Sekolah|Guru')
                      @if (in_array($skl->status, ['draft', 'ditolak']))
                        <a href="{{ route('skl.nilai.edit', $skl) }}"
                          class="btn btn-sm btn-icon btn-text-primary rounded-pill" title="Input Nilai">
                          <i class="icon-base ri ri-edit-line icon-18px"></i>
                        </a>
                      @endif

                      @if ($skl->status == 'disetujui')
                        <a href="{{ route('skl.preview', $skl->id) }}" target="_blank"
                          class="btn btn-sm btn-icon btn-text-secondary rounded-pill" title="Preview SKL">
                          <i class="ri ri-eye-line text-info icon-18px"></i>
                        </a>

                        {{-- Button Download Langsung --}}
                        <a href="{{ route('skl.download', $skl->id) }}"
                          class="btn btn-sm btn-icon btn-text-primary rounded-pill" title="Unduh PDF">
                          <i class="ri ri-download-2-line text-primary icon-18px"></i>
                        </a>
                      @endif
                    @endhasanyrole

                    <a href="{{ route('skl.show', $skl) }}" class="btn btn-sm btn-icon btn-text-info rounded-pill"
                      title="Lihat Detail">
                      <i class="icon-base ri ri-eye-line icon-18px"></i>
                    </a>

                    <div class="dropdown">
                      <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="icon-base ri ri-more-2-fill icon-18px"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end">
                        @hasanyrole('Operator Sekolah|Guru')
                          <li>
                            <button class="dropdown-item btn-nomor-skl" data-id="{{ $skl->id }}"
                              data-nomor="{{ $skl->nomor_skl ?? '' }}"
                              data-tanggal="{{ $skl->tanggal_terbit?->format('Y-m-d') ?? '' }}"
                              data-nama="{{ $siswa->nama_siswa }}">
                              <i class="ri ri-hashtag me-2"></i> Input Nomor
                            </button>
                          </li>

                          @if (in_array($skl->status, ['draft', 'ditolak']))
                            <li>
                              <button class="dropdown-item btn-ajukan" data-id="{{ $skl->id }}"
                                data-nama="{{ $siswa->nama_siswa }}">
                                <i class="ri ri-send-plane-line me-2"></i> Ajukan
                              </button>
                            </li>
                          @endif


                          @if ($skl->status !== 'disetujui')
                            <li>
                              <hr class="dropdown-divider">
                            </li>
                            <li>
                              <button class="dropdown-item text-danger btn-hapus" data-id="{{ $skl->id }}"
                                data-nama="{{ $siswa->nama_siswa }}">
                                <i class="ri ri-delete-bin-line me-2"></i> Hapus
                              </button>
                            </li>
                          @endif
                        @endhasanyrole

                        @hasanyrole('Kepala Sekolah')
                          @if ($skl->status === 'pengajuan')
                            <li>
                              <hr class="dropdown-divider">
                            </li>
                            <li>
                              <button class="dropdown-item text-success btn-setujui" data-id="{{ $skl->id }}">
                                <i class="ri ri-checkbox-circle-line me-2"></i> Setujui
                              </button>
                            </li>
                            <li>
                              <button class="dropdown-item text-danger btn-tolak" data-id="{{ $skl->id }}">
                                <i class="ri ri-close-circle-line me-2"></i> Tolak
                              </button>
                            </li>
                          @endif
                        @endhasanyrole
                      </ul>
                    </div>
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

          <div class="form-text">
            Tanggal terbit mengikuti konfigurasi angkatan
            ({{ $konfigurasi?->tanggal_terbit?->translatedFormat('d F Y') ?? 'belum diatur' }}) —
            ubah di halaman Tanggal &amp; Penomoran.
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

  {{-- ── Modal Import SKL ────────────────────────────────────────────── --}}
  <div class="modal fade" id="modal-import" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">

        <div class="modal-header">
          <h5 class="modal-title"><i class="icon-base ri ri-upload-2-line me-1"></i>Import Nilai SKL</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <div class="alert alert-info d-flex gap-2 py-2 mb-4">
            <i class="icon-base ri ri-information-line mt-1 flex-shrink-0"></i>
            <div class="small">
              <strong>Cara import:</strong><br>
              Download template → isi kolom nilai dan nomor surat → upload kembali.<br>
              Kolom <em>No, Nama Siswa, NISN, tidak perlu diubah (terkunci).<br>
                Nilai kosong tidak menghapus data lama. Jika sudah ada data SKL, nilai <strong>ditimpa</strong>.<br>
                Pastikan Anda menggunakan template Excel terbaru yang diunduh dari sistem agar format kolom sesuai dengan
                mata pelajaran.
            </div>
          </div>

          {{-- Download Template --}}
          <div class="mb-6">
            <button type="button" class="btn btn-outline-success btn-sm" id="btn-download-template">
              <i class="icon-base ri ri-download-2-line me-1"></i>Download Template
            </button>
          </div>

          {{-- Upload --}}
          <div class="mb-3">
            <div class="upload-area-import" id="upload-area-import">
              <input type="file" id="import-file-excel" accept=".xlsx,.xls" class="d-none">
              <div id="import-placeholder" class="text-center py-3">
                <i class="icon-base ri ri-file-excel-2-line ri-28px text-success mb-1"></i>
                <p class="mb-0 small fw-semibold">Klik atau drag file ke sini</p>
                <small class="text-muted">.xlsx / .xls — Maks. 10 MB</small>
              </div>
              <div id="import-preview" class="d-none d-flex align-items-center gap-3 py-2 px-3">
                <i class="icon-base ri ri-file-excel-2-line ri-24px text-success flex-shrink-0"></i>
                <div class="flex-grow-1 overflow-hidden">
                  <div id="import-filename" class="fw-semibold text-truncate"></div>
                  <small id="import-filesize" class="text-muted"></small>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-text-danger flex-shrink-0" id="btn-hapus-import">
                  <i class="icon-base ri ri-close-line"></i>
                </button>
              </div>
            </div>
          </div>

          {{-- Panel hasil --}}
          <div id="import-hasil" class="d-none">
            <hr>
            <div id="import-sukses" class="d-none">
              <div class="alert alert-success py-2">
                <i class="icon-base ri ri-checkbox-circle-line me-1"></i>
                <span id="import-sukses-msg"></span>
              </div>
            </div>
            <div id="import-error" class="d-none">
              <div class="alert alert-danger py-2">
                <i class="icon-base ri ri-error-warning-line me-1"></i>
                <span id="import-error-msg"></span>
              </div>
              <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                <table class="table table-sm table-bordered mb-0">
                  <thead class="table-danger sticky-top">
                    <tr>
                      <th width="55">Baris</th>
                      <th width="120">Kolom</th>
                      <th width="100">Nilai</th>
                      <th>Pesan</th>
                    </tr>
                  </thead>
                  <tbody id="import-tbody-error"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
          <button type="button" class="btn btn-primary" id="btn-proses-import" disabled>
            <i class="icon-base ri ri-upload-2-line me-1"></i>Proses Import
          </button>
        </div>

      </div>
    </div>
  </div>

  <style>
    .upload-area-import {
      border: 2px dashed #cbd5e1;
      border-radius: .6rem;
      cursor: pointer;
      transition: border-color .2s, background .2s;
    }

    .upload-area-import:hover,
    .upload-area-import.dragover {
      border-color: var(--bs-primary);
      background: rgba(var(--bs-primary-rgb), .04);
    }
  </style>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;

      // ── Logika Checkbox All & Item ─────────────────────────────────────────────────────────────
      const checkAll = document.getElementById('check-all');
      const bulkContainer = document.getElementById('bulk-action-container');
      const selectedCount = document.getElementById('selected-count');

      function getSelectedIds() {
        return Array.from(document.querySelectorAll('.check-item:checked')).map(cb => cb.value);
      }

      function toggleBulkPanel() {
        const ids = getSelectedIds();
        const total = document.querySelectorAll('.check-item').length;

        if (checkAll) {
          checkAll.checked = ids.length > 0 && ids.length === total;
          checkAll.indeterminate = ids.length > 0 && ids.length < total;
        }

        if (ids.length > 0) {
          bulkContainer.classList.remove('d-none');
          selectedCount.textContent = ids.length;
        } else {
          bulkContainer.classList.add('d-none');
        }
      }

      if (checkAll) {
        checkAll.addEventListener('change', function() {
          document.querySelectorAll('.check-item:not([disabled])').forEach(cb => {
            cb.checked = this.checked;
          });
          toggleBulkPanel();
        });
      }

      document.getElementById('tabel-skl').addEventListener('change', function(e) {
        if (e.target.classList.contains('check-item')) {
          toggleBulkPanel();
        }
      });

      // ── DataTable ─────────────────────────────────────────────────────────────
      new DataTable('#tabel-skl', {
        scrollX: true,
        order: [
          [2, 'asc']
        ],
        columnDefs: [{
          targets: [0, 1],
          orderable: false,
          searchable: false
        }],
        drawCallback: function() {
          const api = this.api();
          const start = api.page.info().start;
          api.rows({
            page: 'current'
          }).nodes().each(function(row, i) {
            row.cells[1].innerHTML = start + i + 1;
          });
          toggleBulkPanel();
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


      // 3. Eksekusi Aksi Masal
      document.querySelectorAll('.btn-bulk').forEach(btn => {
        btn.addEventListener('click', async function() {
          const action = this.dataset.action;
          const ids = getSelectedIds();
          if (ids.length === 0) return;

          let payload = {
            ids: ids
          };
          let url = `/skl/bulk/${action}`;
          let konfirmText = `Anda akan memproses ${ids.length} data SKL secara masal.`;

          // Khusus tolak butuh input catatan
          if (action === 'tolak') {
            const {
              value: catatan,
              isConfirmed
            } = await Swal.fire({
              title: 'Tolak Masal SKL',
              input: 'textarea',
              inputLabel: 'Alasan Penolakan',
              inputPlaceholder: 'Masukkan catatan untuk semua siswa...',
              showCancelButton: true,
              confirmButtonColor: '#dc3545',
              inputValidator: (value) => value ? null : 'Catatan wajib diisi!'
            });
            if (!isConfirmed) return;
            payload.catatan = catatan;
          } else {
            // Konfirmasi standar untuk Ajukan, Setujui, Hapus
            const isDanger = action === 'hapus';
            const konfirm = await Swal.fire({
              title: action.toUpperCase() + ' MASAL?',
              text: konfirmText,
              icon: isDanger ? 'warning' : 'question',
              showCancelButton: true,
              confirmButtonColor: isDanger ? '#dc3545' : '#0d6efd',
              confirmButtonText: 'Ya, Proses Masal'
            });
            if (!konfirm.isConfirmed) return;
          }

          Swal.fire({
            title: 'Memproses...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
          });

          try {
            const method = action === 'hapus' ? 'DELETE' : 'POST';
            const res = await apiFetch(url, payload, method);
            const data = await res.json();

            if (res.ok) {
              Swal.fire('Selesai', data.message, 'success').then(() => location.reload());
            } else {
              Swal.fire('Gagal', data.message, 'error');
            }
          } catch (err) {
            Swal.fire('Error Sistem', err.message, 'error');
          }
        });
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

      document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-nomor-skl');
        if (!btn) return;
        nomorSklId = btn.dataset.id;
        inputNomor.value = btn.dataset.nomor || '';
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

      document.addEventListener('click', function(e) {
        const btn = e.target.closest('#btn-sync-skl')
        if (!btn) return;
        Swal.fire({
          title: 'Sinkronisasi SKL?',
          text: "Sistem akan menyesuaikan mata pelajaran baru/terhapus dan menghitung ulang seluruh nilai siswa berdasarkan bobot terbaru.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Ya, Sinkronkan!',
          showLoaderOnConfirm: true,
          preConfirm: async () => { // WAJIB tambah async di sini karena ada await di dalam
            try {
              const response = await fetch("{{ route('skl.sync') }}", {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': "{{ csrf_token() }}"
                }
              });

              // Jika HTTP status bukan 200-299
              if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Terjadi kesalahan sistem');
              }

              return await response.json(); // Mengembalikan data sukses
            } catch (error) {
              Swal.showValidationMessage(`Gagal: ${error.message}`);
            }
          },
          allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
          if (result.isConfirmed && result.value) {
            Swal.fire('Berhasil!', result.value.message || 'Data berhasil disinkronkan', 'success')
              .then(() => {
                window.location.reload();
              });
          }
        });
      });

      // Reset modal import saat ditutup
      document.getElementById('modal-import')?.addEventListener('hidden.bs.modal', () => {
        document.getElementById('import-file-excel').value = '';
        document.getElementById('import-placeholder').classList.remove('d-none');
        document.getElementById('import-preview').classList.add('d-none');
        document.getElementById('import-hasil').classList.add('d-none');
        document.getElementById('import-sukses').classList.add('d-none');
        document.getElementById('import-error').classList.add('d-none');
        document.getElementById('btn-proses-import').disabled = true;
      });

      // Download Template
      document.getElementById('btn-download-template')?.addEventListener('click', async function() {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
        try {
          const res = await fetch('/skl/template/download', {
            headers: {
              'X-CSRF-TOKEN': csrf
            }
          });
          if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            throw new Error(data.message ?? 'Gagal generate template.');
          }
          const blob = await res.blob();
          const url = URL.createObjectURL(blob);
          const a = document.createElement('a');
          const cd = res.headers.get('Content-Disposition') ?? '';
          const filename = cd.match(/filename="?([^"]+)"?/)?.[1] ?? 'template-skhu.xlsx';
          a.href = url;
          a.download = filename;
          a.click();
          URL.revokeObjectURL(url);
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: err.message
          });
        } finally {
          this.disabled = false;
          this.innerHTML = '<i class="icon-base ri ri-download-2-line me-1"></i>Download Template';
        }
      });

      // Upload area
      const uploadArea = document.getElementById('upload-area-import');
      const fileInput = document.getElementById('import-file-excel');
      const placeholder = document.getElementById('import-placeholder');
      const preview = document.getElementById('import-preview');
      const btnHapus = document.getElementById('btn-hapus-import');
      const btnProses = document.getElementById('btn-proses-import');

      uploadArea?.addEventListener('click', e => {
        if (!e.target.closest('#btn-hapus-import')) fileInput.click();
      });
      uploadArea?.addEventListener('dragover', e => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
      });
      uploadArea?.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
      uploadArea?.addEventListener('drop', e => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        if (e.dataTransfer.files.length) setFile(e.dataTransfer.files[0]);
      });
      fileInput?.addEventListener('change', () => {
        if (fileInput.files.length) setFile(fileInput.files[0]);
      });
      btnHapus?.addEventListener('click', () => {
        fileInput.value = '';
        placeholder.classList.remove('d-none');
        preview.classList.add('d-none');
        btnProses.disabled = true;
      });

      function setFile(file) {
        document.getElementById('import-filename').textContent = file.name;
        document.getElementById('import-filesize').textContent = (file.size / 1024).toFixed(1) + ' KB';
        placeholder.classList.add('d-none');
        preview.classList.remove('d-none');
        btnProses.disabled = false;
        document.getElementById('import-hasil').classList.add('d-none');
        document.getElementById('import-sukses').classList.add('d-none');
        document.getElementById('import-error').classList.add('d-none');
      }

      // Proses Import
      btnProses?.addEventListener('click', async function() {
        if (!fileInput.files.length) return;

        const konfirm = await Swal.fire({
          icon: 'question',
          title: 'Mulai Import?',
          text: 'Sistem akan memvalidasi seluruh data terlebih dahulu. Jika ada error, tidak ada yang disimpan.',
          showCancelButton: true,
          confirmButtonText: 'Ya, Import',
          cancelButtonText: 'Batal',
          customClass: {
            confirmButton: 'btn btn-primary me-3',
            cancelButton: 'btn btn-outline-secondary'
          },
          buttonsStyling: false,
        });
        if (!konfirm.isConfirmed) return;

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';

        const formData = new FormData();
        formData.append('file_excel', fileInput.files[0]);
        formData.append('_token', csrf);

        try {
          const res = await fetch('/skl/import', {
            method: 'POST',
            body: formData
          });
          const data = await res.json();

          document.getElementById('import-hasil').classList.remove('d-none');
          document.getElementById('import-sukses').classList.add('d-none');
          document.getElementById('import-error').classList.add('d-none');

          if (res.ok) {
            document.getElementById('import-sukses').classList.remove('d-none');
            document.getElementById('import-sukses-msg').textContent = data.message;
            setTimeout(() => location.reload(), 2500);
          } else {
            document.getElementById('import-error').classList.remove('d-none');
            document.getElementById('import-error-msg').textContent = data.message;
            document.getElementById('import-tbody-error').innerHTML = (data.errors ?? []).map(e => `
          <tr>
            <td class="text-center">${e.baris}</td>
            <td>${e.kolom}</td>
            <td><code>${e.nilai}</code></td>
            <td class="text-danger small">${e.pesan}</td>
          </tr>
        `).join('');
          }
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: 'Gagal menghubungi server: ' + err.message
          });
        } finally {
          this.disabled = false;
          this.innerHTML = '<i class="icon-base ri ri-upload-2-line me-1"></i>Proses Import';
        }
      });

    });
  </script>
@endsection
