@extends('layouts/layoutMaster')

@section('title', $viewMode === 'edit' ? 'Input Nilai SKL — ' . $skl->siswa->nama_siswa : 'Detail SKL — ' .
  $skl->siswa->nama_siswa)

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')

  @php
    $isEdit = $viewMode === 'edit';
    $isLocked = !in_array($skl->status, ['draft', 'ditolak']);
    $siswa = $skl->siswa;
    $isManual = $skl->mode_input === 'manual';

    $statusMap = [
        'draft' => ['info', 'Draft', 'ri-draft-line'],
        'pengajuan' => ['warning', 'Pengajuan', 'ri-send-plane-line'],
        'disetujui' => ['success', 'Disetujui', 'ri-checkbox-circle-line'],
        'ditolak' => ['danger', 'Ditolak', 'ri-close-circle-line'],
    ];
    [$stColor, $stLabel, $stIcon] = $statusMap[$skl->status] ?? ['secondary', $skl->status, 'ri-question-line'];
  @endphp

  {{-- ── Breadcrumb ──────────────────────────────────────────────────────── --}}
  <div class="mb-4 d-flex align-items-center gap-2">
    @if ($isEdit)
      <a href="{{ route('skl.show', $skl) }}" class="btn btn-sm btn-outline-secondary">
        <i class="icon-base ri ri-arrow-left-line me-1"></i>Kembali ke Detail
      </a>
    @else
      <a href="{{ route('skl.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="icon-base ri ri-arrow-left-line me-1"></i>Daftar SKL
      </a>
      <span class="text-muted">/</span>
      <span class="text-muted small">{{ $siswa->nama_siswa }}</span>
    @endif
  </div>

  {{-- ── Alert: nilai terkunci ────────────────────────────────────────────── --}}
  @if ($isEdit && $isLocked)
    <div class="alert alert-info d-flex align-items-center gap-3 mb-4">
      <i class="icon-base ri ri-lock-line ri-24px flex-shrink-0"></i>
      <div>
        <strong>Nilai tidak dapat diubah.</strong>
        Status SKL saat ini: <strong>{{ ucfirst($skl->status) }}</strong>.
      </div>
    </div>
  @endif

  {{-- ── Alert: catatan penolakan ────────────────────────────────────────── --}}
  @if ($skl->isDitolak() && $skl->catatan)
    <div class="alert alert-danger d-flex gap-3 mb-4">
      <i class="icon-base ri ri-error-warning-line ri-20px flex-shrink-0 mt-1"></i>
      <div><strong>Dikembalikan:</strong> {{ $skl->catatan }}</div>
    </div>
  @endif

  <div class="row g-4">

    {{-- ── Kolom Kiri: Identitas ────────────────────────────────────────── --}}
    <div class="col-12 col-lg-4">

      {{-- Status Card --}}
      @unless ($isEdit)
        <div class="card mb-4 border-{{ $stColor }}">
          <div class="card-body py-3 d-flex align-items-center gap-3">
            <div class="avatar">
              <span class="avatar-initial rounded-circle bg-label-{{ $stColor }}">
                <i class="icon-base ri {{ $stIcon }}"></i>
              </span>
            </div>
            <div>
              <div class="text-muted small">Status SKL</div>
              <strong class="text-{{ $stColor }}">{{ $stLabel }}</strong>
            </div>
          </div>
        </div>
      @endunless

      {{-- Identitas Siswa --}}
      <div class="card mb-4">
        <div class="card-header border-bottom">
          <h6 class="mb-0">Identitas Siswa</h6>
        </div>
        <div class="card-body">
          <table class="table table-sm table-borderless mb-0">
            @if ($isEdit)
              <tr>
                <td class="text-muted" width="120">Nama</td>
                <td><strong>{{ $siswa->nama_siswa }}</strong></td>
              </tr>
              <tr>
                <td class="text-muted">NISN</td>
                <td>{{ $siswa->nisn }}</td>
              </tr>
              <tr>
                <td class="text-muted">NIS</td>
                <td>{{ $skl->nis ?? '-' }}</td>
              </tr>
              <tr>
                <td class="text-muted">Tempat Lahir</td>
                <td>{{ $siswa->tempat_lahir }}</td>
              </tr>
              <tr>
                <td class="text-muted">Tanggal Lahir</td>
                <td>{{ $siswa->tgl_lahir->translatedFormat('d F Y') }}</td>
              </tr>
            @else
              @php
                $identitas = [
                    'Nama' => $siswa->nama_siswa,
                    'NISN' => $siswa->nisn,
                    'NIS' => $skl->nis ?? '-',
                    'NIK' => $siswa->nik ?? '-',
                    'Tempat Lahir' => $siswa->tempat_lahir,
                    'Tanggal Lahir' => $siswa->tgl_lahir?->translatedFormat('d F Y'),
                    'Agama' => $siswa->agama,
                    'Nama Ayah' => $siswa->nama_ayah,
                    'Nama Ibu' => $siswa->nama_ibu,
                ];
              @endphp
              @foreach ($identitas as $k => $v)
                <tr>
                  <td class="text-muted" width="120">{{ $k }}</td>
                  <td>{{ $v ?? '-' }}</td>
                </tr>
              @endforeach
            @endif
          </table>
        </div>
      </div>

      {{-- Info SKL --}}
      <div class="card">
        <div class="card-header border-bottom">
          <h6 class="mb-0">{{ $isEdit ? 'Info SKL' : 'Data SKL' }}</h6>
        </div>
        <div class="card-body">
          <table class="table table-sm table-borderless mb-0">
            @unless ($isEdit)
              <tr>
                <td class="text-muted" width="120">Nomor SKL</td>
                <td>{{ $skl->nomor_skl ?? '-' }}</td>
              </tr>
              <tr>
                <td class="text-muted">Tanggal Terbit</td>
                <td>{{ $skl->tanggal_terbit?->translatedFormat('d F Y') ?? '-' }}</td>
              </tr>
              <tr>
                <td class="text-muted">Tahun Pelajaran</td>
                <td>{{ $skl->tahun_pelajaran }}</td>
              </tr>
            @endunless
            <tr>
              <td class="text-muted" width="120">Mode</td>
              <td>
                @if ($skl->mode_input === 'jadi')
                  <span class="badge bg-label-info">Nilai Jadi</span>
                @else
                  <span class="badge bg-label-warning">Manual Berbobot</span>
                @endif
              </td>
            </tr>
            <tr>
              <td class="text-muted">Status</td>
              <td><span class="badge bg-label-{{ $stColor }}">{{ $stLabel }}</span></td>
            </tr>
            <tr>
              <td class="text-muted">Rata-rata</td>
              <td><strong id="sidebar-rata">{{ number_format($skl->nilaiRataRata(), 2) }}</strong></td>
            </tr>
            @if (!$isEdit && $skl->generated_at)
              <tr>
                <td class="text-muted">Disetujui</td>
                <td>{{ $skl->generated_at->translatedFormat('d F Y') }}</td>
              </tr>
            @endif
          </table>

          {{-- Info komponen (mode manual) --}}
          @if ($isManual)
            <hr>
            <h6 class="small fw-semibold text-muted mb-2">KOMPONEN NILAI</h6>
            @php $komponens = $skl->nilais->first()?->komponens ?? collect(); @endphp
            @foreach ($komponens as $kn)
              <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-muted">{{ $kn->komponen->label }}</small>
                <span class="badge bg-label-primary">{{ $kn->komponen->bobot }}%</span>
              </div>
            @endforeach
            @if ($isEdit)
              <div class="alert alert-info py-2 mt-2 mb-0 small">
                <i class="icon-base ri ri-information-line me-1"></i>
                Nilai akhir dihitung otomatis saat simpan.
              </div>
            @endif
          @endif
        </div>
      </div>

    </div>{{-- end kolom kiri --}}

    {{-- ── Kolom Kanan: Tabel Nilai ─────────────────────────────────────── --}}
    <div class="col-12 col-lg-8">
      <div class="card">

        {{-- Card Header --}}
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h6 class="mb-0">{{ $isEdit ? 'Input Nilai SKL' : 'Nilai SKL' }}</h6>
          <div class="d-flex gap-2 align-items-center flex-wrap">
            <small class="text-muted">
              Rata-rata: <strong id="nilai-rata-rata">{{ number_format($skl->nilaiRataRata(), 2) }}</strong>
            </small>

            @unless ($isEdit)
              @if (in_array($skl->status, ['draft', 'ditolak']))
                <a href="{{ route('skl.nilai.edit', $skl) }}" class="btn btn-outline-primary btn-sm">
                  <i class="icon-base ri ri-edit-line me-1"></i>Input Nilai
                </a>
              @endif
              @if ($skl->isDraft() || $skl->isDitolak())
                <button class="btn btn-warning btn-sm" id="btn-ajukan">
                  <i class="icon-base ri ri-send-plane-line me-1"></i>Ajukan
                </button>
              @endif
              @hasanyrole('Admin')
                @if ($skl->isPengajuan())
                  <button class="btn btn-success btn-sm" id="btn-setujui">
                    <i class="icon-base ri ri-checkbox-circle-line me-1"></i>Setujui
                  </button>
                  <button class="btn btn-danger btn-sm" id="btn-tolak">
                    <i class="icon-base ri ri-close-circle-line me-1"></i>Tolak
                  </button>
                @endif
              @endhasanyrole
            @endunless
          </div>
        </div>

        {{-- Card Body --}}
        <div class="card-body p-0">
          @php
            $nilaisGrouped = $nilais->groupBy(fn($n) => $n->mapelMapping?->masterMapel?->kelompok?->nama ?? 'Lainnya');
          @endphp

          @if ($isEdit)
            <form id="form-nilai" method="POST" action="{{ route('skl.nilai.update', $skl) }}">
              @csrf
              @method('PUT')
          @endif

          {{-- ── NILAI JADI: tabel sederhana ──────────────────────────────── --}}
          @if (!$isManual)

            <table class="table table-sm table-bordered mb-0">
              <thead>
                <tr>
                  <th width="50" class="text-center">No</th>
                  <th>Mata Pelajaran</th>
                  <th width="150" class="text-center">
                    {{ $isEdit ? 'Nilai (0–100)' : 'Nilai Akhir' }}
                  </th>
                </tr>
              </thead>
              <tbody>
                @php $no = 1; @endphp
                @foreach ($nilaisGrouped as $namaKelompok => $items)
                  <tr class="table-light">
                    <td colspan="3" class="fw-bold py-2 px-3 small text-uppercase text-muted">
                      {{ $namaKelompok }}
                    </td>
                  </tr>
                  @foreach ($items as $sklNilai)
                    <tr>
                      <td class="text-center">{{ $no++ }}</td>
                      <td>
                        {{ $sklNilai->nama_mapel }}
                        @if ($sklNilai->nama_sub_mapel)
                          <br><small class="text-muted ps-2">{{ $sklNilai->nama_sub_mapel }}</small>
                        @endif
                      </td>
                      <td class="{{ $isEdit ? 'p-1' : 'text-center fw-semibold' }}">
                        @if ($isEdit)
                          <input type="number" class="form-control form-control-sm text-center input-nilai"
                            name="nilai[{{ $sklNilai->id }}]" min="0" max="100" step="0.01"
                            value="{{ number_format($sklNilai->nilai_akhir ?? 0, 2, '.', '') }}"
                            {{ $isLocked ? 'disabled' : '' }}>
                        @else
                          @if ($sklNilai->nilai_akhir !== null)
                            {{ number_format($sklNilai->nilai_akhir, 2) }}
                          @else
                            <span class="text-muted">-</span>
                          @endif
                        @endif
                      </td>
                    </tr>
                  @endforeach
                @endforeach
              </tbody>
              <tfoot>
                <tr class="table-primary">
                  <td colspan="2" class="fw-bold text-end">Rata-rata</td>
                  <td class="text-center fw-bold" id="tfoot-rata">
                    {{ number_format($skl->nilaiRataRata(), 2) }}
                  </td>
                </tr>
              </tfoot>
            </table>

            {{-- ── NILAI MANUAL: TAB per komponen ───────────────────────────── --}}
          @else
            @php
              $firstNilai = $skl->nilais->first();
              $komponenHeaders = $firstNilai
                  ? $firstNilai->komponens->map(fn($kn) => $kn->komponen)->values()
                  : collect();
            @endphp

            {{-- Tab Nav --}}
            @if ($komponenHeaders->isNotEmpty())
              <div class="border-bottom px-3 pt-3">
                <ul class="nav nav-tabs nav-tabs-bordered gap-1" id="tabs-komponen" role="tablist">
                  {{-- Tab "Semua" = ringkasan nilai akhir per mapel --}}
                  <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-ringkasan" data-bs-toggle="tab"
                      data-bs-target="#panel-ringkasan" type="button" role="tab">
                      <i class="icon-base ri ri-list-check me-1"></i>Ringkasan
                    </button>
                  </li>
                  @foreach ($komponenHeaders as $komp)
                    <li class="nav-item" role="presentation">
                      <button class="nav-link" id="tab-komp-{{ $komp->id }}" data-bs-toggle="tab"
                        data-bs-target="#panel-komp-{{ $komp->id }}" type="button" role="tab">
                        {{ $komp->label }}
                        <span class="badge bg-label-primary ms-1">{{ $komp->bobot }}%</span>
                      </button>
                    </li>
                  @endforeach
                </ul>
              </div>

              <div class="tab-content p-0">

                {{-- Panel Ringkasan --}}
                <div class="tab-pane fade show active" id="panel-ringkasan" role="tabpanel">
                  <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                      <thead>
                        <tr>
                          <th width="40" class="text-center">No</th>
                          <th>Mata Pelajaran</th>
                          @foreach ($komponenHeaders as $komp)
                            <th class="text-center" width="110">
                              <div>{{ Str::limit($komp->label, 15) }}</div>
                              <small class="text-muted fw-normal">{{ $komp->bobot }}%</small>
                            </th>
                          @endforeach
                          <th class="text-center" width="100">Nilai Akhir</th>
                        </tr>
                      </thead>
                      <tbody>
                        @php $no = 1; @endphp
                        @foreach ($nilaisGrouped as $namaKelompok => $items)
                          <tr class="table-light">
                            <td colspan="{{ 3 + $komponenHeaders->count() }}"
                              class="fw-bold py-2 px-3 small text-uppercase text-muted">
                              {{ $namaKelompok }}
                            </td>
                          </tr>
                          @foreach ($items as $sklNilai)
                            @php $knMap = $sklNilai->komponens->keyBy('komponen_id'); @endphp
                            <tr>
                              <td class="text-center">{{ $no++ }}</td>
                              <td>
                                {{ $sklNilai->nama_mapel }}
                                @if ($sklNilai->nama_sub_mapel)
                                  <br><small class="text-muted ps-2">{{ $sklNilai->nama_sub_mapel }}</small>
                                @endif
                              </td>
                              @foreach ($komponenHeaders as $komp)
                                @php $kn = $knMap[$komp->id] ?? null; @endphp
                                <td class="text-center">
                                  <span id="ringkasan-kn-{{ $sklNilai->id }}-{{ $komp->id }}">
                                    {{ number_format($kn?->nilai ?? 0, 2) }}
                                  </span>
                                </td>
                              @endforeach
                              <td class="text-center fw-semibold" id="hasil-{{ $sklNilai->id }}">
                                {{ number_format($sklNilai->nilai_akhir ?? 0, 2) }}
                              </td>
                            </tr>
                          @endforeach
                        @endforeach
                      </tbody>
                      <tfoot>
                        <tr class="table-primary">
                          <td colspan="{{ 2 + $komponenHeaders->count() }}" class="fw-bold text-end">Rata-rata</td>
                          <td class="text-center fw-bold" id="tfoot-rata">
                            {{ number_format($skl->nilaiRataRata(), 2) }}
                          </td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>

                {{-- Panel per Komponen --}}
                @foreach ($komponenHeaders as $komp)
                  <div class="tab-pane fade" id="panel-komp-{{ $komp->id }}" role="tabpanel">
                    <div class="px-3 py-2 border-bottom bg-light d-flex align-items-center gap-2">
                      <span class="fw-semibold small">{{ $komp->label }}</span>
                      <span class="badge bg-label-primary">Bobot {{ $komp->bobot }}%</span>
                      @if ($komp->keterangan)
                        <span class="text-muted small ms-2">— {{ $komp->keterangan }}</span>
                      @endif
                    </div>
                    <div class="table-responsive">
                      <table class="table table-sm table-bordered mb-0">
                        <thead>
                          <tr>
                            <th width="40" class="text-center">No</th>
                            <th>Mata Pelajaran</th>
                            @php
                              // Cek apakah komponen ini punya sub
                              $kompFull = $skl->nilais->first()?->komponens->firstWhere('komponen_id', $komp->id)
                                  ?->komponen;
                              $subHeaders = $kompFull?->subKomponen ?? collect();
                            @endphp
                            @if ($subHeaders->isNotEmpty())
                              @foreach ($subHeaders as $sub)
                                <th class="text-center" width="110">
                                  {{ Str::limit($sub->label, 15) }}
                                </th>
                              @endforeach
                              <th class="text-center" width="100">Rata Sub</th>
                            @else
                              <th class="text-center" width="130">
                                {{ $isEdit ? 'Nilai (0–100)' : 'Nilai' }}
                              </th>
                            @endif
                          </tr>
                        </thead>
                        <tbody>
                          @php $no = 1; @endphp
                          @foreach ($nilaisGrouped as $namaKelompok => $items)
                            <tr class="table-light">
                              <td colspan="{{ 3 + $subHeaders->count() }}"
                                class="fw-bold py-2 px-3 small text-uppercase text-muted">
                                {{ $namaKelompok }}
                              </td>
                            </tr>
                            @foreach ($items as $sklNilai)
                              @php
                                $kn = $sklNilai->komponens->firstWhere('komponen_id', $komp->id);
                                $subNilais = $kn?->nilaiSubKomponen ?? collect();
                              @endphp
                              <tr>
                                <td class="text-center">{{ $no++ }}</td>
                                <td>
                                  {{ $sklNilai->nama_mapel }}
                                  @if ($sklNilai->nama_sub_mapel)
                                    <br><small class="text-muted ps-2">{{ $sklNilai->nama_sub_mapel }}</small>
                                  @endif
                                </td>
                                @if ($subHeaders->isNotEmpty())
                                  @foreach ($subHeaders as $sub)
                                    @php $nsk = $subNilais->firstWhere('sub_komponen_id', $sub->id); @endphp
                                    <td class="{{ $isEdit ? 'p-1' : 'text-center' }}">
                                      @if ($isEdit)
                                        <input type="number" class="form-control form-control-sm text-center input-sub"
                                          name="sub[{{ $sklNilai->id }}][{{ $komp->id }}][{{ $sub->id }}]"
                                          min="0" max="100" step="0.01"
                                          value="{{ number_format($nsk?->nilai ?? 0, 2, '.', '') }}"
                                          data-nilai-id="{{ $sklNilai->id }}" data-komp-id="{{ $komp->id }}"
                                          data-bobot="{{ $komp->bobot }}" {{ $isLocked ? 'disabled' : '' }}>
                                      @else
                                        {{ number_format($nsk?->nilai ?? 0, 2) }}
                                      @endif
                                    </td>
                                  @endforeach
                                  {{-- Rata sub --}}
                                  <td class="text-center fw-semibold"
                                    id="rata-sub-{{ $sklNilai->id }}-{{ $komp->id }}">
                                    {{ number_format($kn?->nilai ?? 0, 2) }}
                                  </td>
                                @else
                                  <td class="{{ $isEdit ? 'p-1' : 'text-center fw-semibold' }}">
                                    @if ($isEdit)
                                      <input type="number"
                                        class="form-control form-control-sm text-center input-komponen"
                                        name="nilai[{{ $sklNilai->id }}][{{ $komp->id }}]" min="0"
                                        max="100" step="0.01"
                                        value="{{ number_format($kn?->nilai ?? 0, 2, '.', '') }}"
                                        data-nilai-id="{{ $sklNilai->id }}" data-komp-id="{{ $komp->id }}"
                                        data-bobot="{{ $komp->bobot }}" {{ $isLocked ? 'disabled' : '' }}>
                                    @else
                                      {{ number_format($kn?->nilai ?? 0, 2) }}
                                    @endif
                                  </td>
                                @endif
                              </tr>
                            @endforeach
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                  </div>
                @endforeach

              </div>{{-- end tab-content --}}
            @else
              <div class="p-4 text-center text-muted">
                <i class="icon-base ri ri-error-warning-line ri-24px mb-2 d-block"></i>
                Komponen nilai belum dikonfigurasi. Minta Admin untuk mengatur komponen SKL.
              </div>
            @endif

          @endif{{-- end isManual --}}

          @if ($isEdit)
            </form>
          @endif

        </div>{{-- end card-body --}}

        {{-- Card Footer --}}
        <div class="card-footer d-flex justify-content-between align-items-center">
          <div>
            @if ($isEdit)
              <a href="{{ route('skl.show', $skl) }}" class="btn btn-outline-secondary btn-sm">
                <i class="icon-base ri ri-eye-line me-1"></i>Lihat Detail
              </a>
            @endif
          </div>
          @if ($isEdit && !$isLocked)
            <button type="button" class="btn btn-primary btn-sm" id="btn-simpan">
              <i class="icon-base ri ri-save-line me-1"></i>
              <span>Simpan Nilai</span>
            </button>
          @endif
        </div>

      </div>{{-- end card --}}
    </div>{{-- end col kanan --}}

  </div>{{-- end row --}}

  {{-- ── Modal Tolak ─────────────────────────────────────────────────────── --}}
  <div class="modal fade" id="modal-tolak" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Tolak Pengajuan SKL</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">Catatan <span class="text-danger">*</span></label>
          <textarea id="catatan-tolak" class="form-control" rows="3" placeholder="Alasan penolakan..."></textarea>
          <div id="error-catatan" class="text-danger small mt-1 d-none">Catatan wajib diisi.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-danger" id="btn-konfirm-tolak">Tolak</button>
        </div>
      </div>
    </div>
  </div>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const sklId = {{ $skl->id }};
      const isEdit = {{ $isEdit ? 'true' : 'false' }};
      const isManual = {{ $isManual ? 'true' : 'false' }};

      @if ($isEdit)
        // ── JS: EDIT ───────────────────────────────────────────────────────────

        function hitungRataSub(nilaiId, kompId) {
          const inputs = document.querySelectorAll(
          `.input-sub[data-nilai-id="${nilaiId}"][data-komp-id="${kompId}"]`);
          if (!inputs.length) return 0;
          let total = 0;
          inputs.forEach(i => total += parseFloat(i.value) || 0);
          const rata = total / inputs.length;
          const el = document.getElementById(`rata-sub-${nilaiId}-${kompId}`);
          if (el) el.textContent = rata.toFixed(2);
          // Sync ke ringkasan
          const ringsEl = document.getElementById(`ringkasan-kn-${nilaiId}-${kompId}`);
          if (ringsEl) ringsEl.textContent = rata.toFixed(2);
          return rata;
        }

        function hitungHasil(nilaiId) {
          // Cari semua input komponen (tanpa sub) + rata sub dari tiap komponen
          let total = 0;
          // komponen langsung
          document.querySelectorAll(`.input-komponen[data-nilai-id="${nilaiId}"]`).forEach(input => {
            const bobot = parseFloat(input.dataset.bobot) || 0;
            const kompId = input.dataset.kompId;
            total += (parseFloat(input.value) || 0) * bobot / 100;
            // sync ringkasan
            const rEl = document.getElementById(`ringkasan-kn-${nilaiId}-${kompId}`);
            if (rEl) rEl.textContent = (parseFloat(input.value) || 0).toFixed(2);
          });
          // sub-komponen: ambil rata dari elemen rata-sub
          document.querySelectorAll(`[id^="rata-sub-${nilaiId}-"]`).forEach(el => {
            const parts = el.id.split('-'); // rata-sub-{nilaiId}-{kompId}
            const kompId = parts[parts.length - 1];
            const kompInput = document.querySelector(
              `.input-sub[data-nilai-id="${nilaiId}"][data-komp-id="${kompId}"]`);
            const bobot = kompInput ? (parseFloat(kompInput.dataset.bobot) || 0) : 0;
            total += parseFloat(el.textContent) * bobot / 100;
          });
          const hasilEl = document.getElementById(`hasil-${nilaiId}`);
          if (hasilEl) hasilEl.textContent = total.toFixed(2);
          return total;
        }

        function hitungRataRata() {
          const hasilEls = document.querySelectorAll('[id^="hasil-"]');
          let total = 0,
            count = 0;
          hasilEls.forEach(el => {
            total += parseFloat(el.textContent) || 0;
            count++;
          });
          const nilaiEls = document.querySelectorAll('.input-nilai');
          if (nilaiEls.length) {
            total = 0;
            count = 0;
            nilaiEls.forEach(i => {
              const v = parseFloat(i.value) || 0;
              if (v > 0) {
                total += v;
                count++;
              }
            });
          }
          const rata = count > 0 ? (total / count).toFixed(2) : '0.00';
          document.getElementById('nilai-rata-rata').textContent = rata;
          document.getElementById('sidebar-rata').textContent = rata;
          const tf = document.getElementById('tfoot-rata');
          if (tf) tf.textContent = rata;
        }

        if (isManual) {
          // Sub-komponen events
          document.querySelectorAll('.input-sub').forEach(input => {
            input.addEventListener('input', function() {
              hitungRataSub(this.dataset.nilaiId, this.dataset.kompId);
              hitungHasil(this.dataset.nilaiId);
              hitungRataRata();
            });
          });
          // Komponen langsung events
          document.querySelectorAll('.input-komponen').forEach(input => {
            input.addEventListener('input', function() {
              hitungHasil(this.dataset.nilaiId);
              hitungRataRata();
            });
            hitungHasil(input.dataset.nilaiId);
          });
        } else {
          document.querySelectorAll('.input-nilai').forEach(i =>
            i.addEventListener('input', hitungRataRata)
          );
        }
        hitungRataRata();

        // Simpan via AJAX
        document.getElementById('btn-simpan')?.addEventListener('click', async function() {
          const btn = this;
          const oriTxt = btn.innerHTML;
          btn.disabled = true;
          btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

          try {
            const res = await fetch(document.getElementById('form-nilai').action, {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
              },
              body: new FormData(document.getElementById('form-nilai')),
            });
            const data = await res.json();
            if (res.ok) {
              Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: data.message ?? 'Nilai berhasil disimpan.',
                timer: 2000,
                showConfirmButton: false
              });
              setTimeout(() => location.reload(), 2100);
            } else {
              throw new Error(data.message ?? JSON.stringify(data.errors ?? 'Gagal menyimpan.'));
            }
          } catch (err) {
            Swal.fire({
              icon: 'error',
              title: 'Gagal',
              text: err.message
            });
          } finally {
            btn.disabled = false;
            btn.innerHTML = oriTxt;
          }
        });
      @else
        // ── JS: SHOW ───────────────────────────────────────────────────────────

        async function apiFetch(url, body = null) {
          return fetch(url, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
            body: body ? JSON.stringify(body) : null,
          });
        }

        // Ajukan
        document.getElementById('btn-ajukan')?.addEventListener('click', async function() {
          const konfirm = await Swal.fire({
            icon: 'question',
            title: 'Ajukan SKL?',
            text: 'Pastikan semua nilai sudah terisi dan benar.',
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
          const res = await apiFetch(`/skl/${sklId}/ajukan`);
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
              text: data.message ?? data.errors?.nilai
            });
          }
        });

        // Setujui
        document.getElementById('btn-setujui')?.addEventListener('click', async function() {
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
          const res = await apiFetch(`/skl/${sklId}/setujui`);
          const data = await res.json();
          if (res.ok) {
            Swal.fire({
              icon: 'success',
              title: 'Disetujui',
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

        // Tolak
        const modalTolak = new bootstrap.Modal(document.getElementById('modal-tolak'));
        const inputCatatan = document.getElementById('catatan-tolak');
        const errorCatatan = document.getElementById('error-catatan');

        document.getElementById('btn-tolak')?.addEventListener('click', () => {
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

          const res = await apiFetch(`/skl/${sklId}/tolak`, {
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
      @endif

    });
  </script>
@endsection
