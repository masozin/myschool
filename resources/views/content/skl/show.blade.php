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

    $nilaisGrouped = $nilais->groupBy(fn($n) => $n->mapelMapping?->masterMapel?->kelompok?->nama ?? 'Lainnya');
  @endphp

  {{-- ── Breadcrumb ─────────────────────────────────────────────────────── --}}
  <div class="mb-4 d-flex align-items-center gap-2">
    <a href="{{ route('skl.index') }}" class="btn btn-sm btn-outline-secondary">
      <i class="icon-base ri ri-arrow-left-line me-1"></i>Daftar SKL
    </a>
    <span class="text-muted">/</span>
    <span class="text-muted small">{{ $siswa->nama_siswa }}</span>
  </div>

  {{-- ── Alert terkunci ──────────────────────────────────────────────────── --}}
  @if ($isEdit && $isLocked)
    <div class="alert alert-info d-flex align-items-center gap-3 mb-4">
      <i class="icon-base ri ri-lock-line ri-24px flex-shrink-0"></i>
      <div>
        <strong>Nilai tidak dapat diubah.</strong>
        Status SKL saat ini: <strong>{{ ucfirst($skl->status) }}</strong>.
      </div>
    </div>
  @endif

  {{-- ── Alert penolakan ─────────────────────────────────────────────────── --}}
  @if ($skl->isDitolak() && $skl->catatan)
    <div class="alert alert-danger d-flex gap-3 mb-4">
      <i class="icon-base ri ri-error-warning-line ri-20px flex-shrink-0 mt-1"></i>
      <div><strong>Dikembalikan:</strong> {{ $skl->catatan }}</div>
    </div>
  @endif

  <div class="row g-4">

    {{-- ══════════════════════════════════════════════════════════════════════
         KOLOM KIRI: Identitas + Info SKL
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="col-12">

      {{-- Status card (hanya di mode show) --}}
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
      {{-- <div class="card mb-4">
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
      </div> --}}

      {{-- Info SKL --}}
      {{-- <div class="card">
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
                @if ($isManual)
                  <span class="badge bg-label-warning">Manual Berbobot</span>
                @else
                  <span class="badge bg-label-info">Nilai Jadi</span>
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
              <div class="alert alert-info py-2 mt-3 mb-0 small">
                <i class="icon-base ri ri-information-line me-1"></i>
                Nilai akhir dihitung otomatis saat simpan.
              </div>
            @endif
          @endif
        </div>
      </div> --}}

    </div>{{-- end kolom kiri --}}

    {{-- ══════════════════════════════════════════════════════════════════════
         KOLOM KANAN: Nilai
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="col-12">
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

          @if ($isEdit)
            <form id="form-nilai" method="POST" action="{{ route('skl.nilai.update', $skl) }}">
              @csrf
              @method('PUT')
          @endif

          {{-- ══════════════════════════════════════════════════════════════
               MODE NILAI JADI
          ══════════════════════════════════════════════════════════════ --}}
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
                        @if ($sklNilai->mapelMapping?->jenis === 'tambahan')
                          <span class="badge bg-label-secondary ms-1" style="font-size:9px">Lokal</span>
                        @endif
                      </td>
                      <td class="{{ $isEdit ? 'p-1' : 'text-center fw-semibold' }}">
                        @if ($isEdit)
                          <input type="number" class="form-control form-control-sm text-center input-nilai"
                            name="nilai[{{ $sklNilai->id }}]" min="0" max="100" step="0.01"
                            {{-- value="{{ number_format($sklNilai->nilai_akhir ?? 0, 2, '.', '') }}" --}}
                            value="{{ $sklNilai->nilai_akhir !== null ? number_format($sklNilai->nilai_akhir, 2, '.', '') : '' }}"
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

            {{-- ══════════════════════════════════════════════════════════════
               MODE MANUAL BERBOBOT
               Edit  → tab per komponen, nested tab per sub-komponen
               Show  → ringkasan nilai per komponen (tabel)
          ══════════════════════════════════════════════════════════════ --}}
          @else
            @php
              $firstNilai = $skl->nilais->first();
              $komponenHeaders = $firstNilai
                  ? $firstNilai->komponens->map(fn($kn) => $kn->komponen)->values()
                  : collect();
            @endphp

            {{-- ── SHOW: tabel ringkasan ─────────────────────────────────── --}}
            @if (!$isEdit)

              <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                  <thead>
                    <tr>
                      <th width="40" class="text-center">No</th>
                      <th>Mata Pelajaran</th>
                      @foreach ($komponenHeaders as $komp)
                        <th class="text-center" width="100" title="{{ $komp->keterangan }}">
                          {{ str($komp->kode)->limit(14) }}
                          <br><small class="fw-normal text-muted">{{ $komp->bobot }}%</small>
                        </th>
                      @endforeach
                      <th class="text-center" width="90">Nilai Akhir</th>
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
                              {{ $kn?->nilai !== null ? number_format($kn->nilai, 2) : '-' }}
                            </td>
                          @endforeach
                          <td class="text-center fw-semibold">
                            {{ $sklNilai->nilai_akhir !== null ? number_format($sklNilai->nilai_akhir, 2) : '-' }}
                          </td>
                        </tr>
                      @endforeach
                    @endforeach
                  </tbody>
                  <tfoot>
                    <tr class="table-primary">
                      <td colspan="{{ 2 + $komponenHeaders->count() }}" class="fw-bold text-end">Rata-rata</td>
                      <td class="text-center fw-bold">{{ number_format($skl->nilaiRataRata(), 2) }}</td>
                    </tr>
                  </tfoot>
                </table>
              </div>

              {{-- ── EDIT: tab per komponen ────────────────────────────────── --}}
            @else
              @if ($komponenHeaders->isEmpty())
                <div class="p-4 text-center text-muted">
                  <i class="icon-base ri ri-error-warning-line ri-24px mb-2 d-block"></i>
                  Komponen nilai belum dikonfigurasi. Minta Admin untuk menambahkan komponen SKL.
                </div>
              @else
                {{-- Nav tab komponen (level 1) --}}
                <div class="border-bottom px-3 nav-align-top nav-tabs-shadow">
                  <ul class="nav nav-tabs nav-tabs-bordered" id="nav-komponen" role="tablist">
                    @foreach ($komponenHeaders as $ki => $komp)
                      <li class="nav-item" role="presentation">
                        <button class="nav-link {{ $ki === 0 ? 'active' : '' }}" id="tab-k{{ $komp->id }}"
                          data-bs-toggle="tab" data-bs-target="#panel-k{{ $komp->id }}" type="button"
                          role="tab">
                          {{ $komp->label }}
                          <span class="badge bg-label-primary ms-1">{{ $komp->bobot }}%</span>
                        </button>
                      </li>
                    @endforeach
                  </ul>
                </div>

                {{-- Tab content komponen --}}
                <div class="tab-content" id="content-komponen">
                  @foreach ($komponenHeaders as $ki => $komp)
                    @php $subHeaders = $komp->subKomponen ?? collect(); @endphp

                    <div class="tab-pane fade {{ $ki === 0 ? 'show active' : '' }}" id="panel-k{{ $komp->id }}"
                      role="tabpanel">

                      {{-- Keterangan komponen --}}
                      @if ($komp->keterangan)
                        <div class="px-3 pb-2">
                          <p class="text-muted small mb-0">
                            <i class="icon-base ri ri-information-line me-1"></i>{{ $komp->keterangan }}
                          </p>
                        </div>
                      @endif

                      {{-- ── KOMPONEN TANPA SUB: tabel input langsung ─────── --}}
                      @if ($subHeaders->isEmpty())
                        <div class="table-responsive">
                          <table class="table table-sm table-bordered mb-0">
                            <thead>
                              <tr>
                                <th width="40" class="text-center">No</th>
                                <th>Mata Pelajaran</th>
                                <th class="text-center" width="150">
                                  Nilai (0–100)
                                  <br><small class="fw-normal text-muted">Bobot {{ $komp->bobot }}%</small>
                                </th>
                                <th class="text-center" width="90">Nilai Akhir</th>
                              </tr>
                            </thead>
                            <tbody>
                              @php $no = 1; @endphp
                              @foreach ($nilaisGrouped as $namaKelompok => $items)
                                <tr class="table-light">
                                  <td colspan="4" class="fw-semibold py-1 px-3 small text-uppercase text-muted">
                                    {{ $namaKelompok }}
                                  </td>
                                </tr>
                                @foreach ($items as $sklNilai)
                                  @php $kn = $sklNilai->komponens->firstWhere('komponen_id', $komp->id); @endphp
                                  <tr>
                                    <td class="text-center">{{ $no++ }}</td>
                                    <td>
                                      {{ $sklNilai->nama_mapel }}
                                      @if ($sklNilai->nama_sub_mapel)
                                        <br><small class="text-muted ps-2">{{ $sklNilai->nama_sub_mapel }}</small>
                                      @endif
                                      @if ($sklNilai->mapelMapping?->jenis === 'tambahan')
                                        <span class="badge bg-label-secondary ms-1" style="font-size:9px">Lokal</span>
                                      @endif
                                    </td>
                                    <td class="p-1">
                                      <input type="number"
                                        class="form-control form-control-sm text-center input-komponen"
                                        name="nilai[{{ $sklNilai->id }}][{{ $komp->id }}]" min="0"
                                        max="100" step="0.01" {{-- value="{{ number_format($kn?->nilai ?? 0, 2, '.', '') }}" --}}
                                        value="{{ $kn?->nilai !== null ? number_format($kn->nilai, 2, '.', '') : '' }}"
                                        data-nilai-id="{{ $sklNilai->id }}" data-komp-id="{{ $komp->id }}"
                                        data-bobot="{{ $komp->bobot }}" {{ $isLocked ? 'disabled' : '' }}>
                                    </td>
                                    <td class="text-center fw-semibold hasil-mapel-akhir hasil-{{ $sklNilai->id }}"
                                      data-nilai-id="{{ $sklNilai->id }}">
                                      {{ number_format($sklNilai->nilai_akhir ?? 0, 2) }}
                                    </td>
                                  </tr>
                                @endforeach
                              @endforeach
                            </tbody>
                          </table>
                        </div>
                      @else
                        {{-- ── KOMPONEN DENGAN SUB: nested tab (pills) ──────── --}}
                        <div class="px-3">
                          {{-- Nav pills sub-komponen (level 2) --}}
                          <ul class="nav nav-pills gap-1 mb-3" role="tablist">
                            @foreach ($subHeaders as $si => $sub)
                              <li class="nav-item" role="presentation">
                                <button class="nav-link {{ $si === 0 ? 'active' : '' }}"
                                  id="tab-k{{ $komp->id }}-s{{ $sub->id }}" data-bs-toggle="tab"
                                  data-bs-target="#panel-k{{ $komp->id }}-s{{ $sub->id }}" type="button"
                                  role="tab">
                                  {{ $sub->label }}
                                </button>
                              </li>
                            @endforeach
                          </ul>

                          {{-- Tab content sub --}}
                          <div class="tab-content">
                            @foreach ($subHeaders as $si => $sub)
                              <div class="tab-pane fade {{ $si === 0 ? 'show active' : '' }}"
                                id="panel-k{{ $komp->id }}-s{{ $sub->id }}" role="tabpanel">

                                <div class="table-responsive">
                                  <table class="table table-sm mb-0 table-bordered">
                                    <thead>
                                      <tr>
                                        <th width="40" class="text-center">No</th>
                                        <th>Mata Pelajaran</th>
                                        <th class="text-center" width="150">
                                          {{ $sub->label }}
                                          <br><small class="fw-normal text-muted">Nilai (0–100)</small>
                                        </th>
                                        <th class="text-center" width="150">
                                          nilai items
                                          <br><small class="fw-normal text-muted">(bobot {{ $komp->bobot }}%)</small>
                                        </th>
                                        <th class="text-center" width="90">Nilai Akhir</th>
                                      </tr>
                                    </thead>
                                    <tbody>
                                      @php $no = 1; @endphp
                                      @foreach ($nilaisGrouped as $namaKelompok => $items)
                                        <tr class="table-light">
                                          <td colspan="5"
                                            class="fw-semibold py-1 px-3 small text-uppercase text-muted">
                                            {{ $namaKelompok }}
                                          </td>
                                        </tr>
                                        @foreach ($items as $sklNilai)
                                          @php
                                            $kn = $sklNilai->komponens->firstWhere('komponen_id', $komp->id);
                                            $nsk = $kn?->nilaiSubKomponen->firstWhere('sub_komponen_id', $sub->id);
                                            $rataSub = $kn
                                                ? $kn->nilaiSubKomponen->whereNotNull('nilai')->avg('nilai')
                                                : null;
                                          @endphp
                                          <tr>
                                            <td class="text-center">{{ $no++ }}</td>
                                            <td>
                                              {{ $sklNilai->nama_mapel }}
                                              @if ($sklNilai->nama_sub_mapel)
                                                <br><small
                                                  class="text-muted ps-2">{{ $sklNilai->nama_sub_mapel }}</small>
                                              @endif
                                              @if ($sklNilai->mapelMapping?->jenis === 'tambahan')
                                                <span class="badge bg-label-secondary ms-1"
                                                  style="font-size:9px">Lokal</span>
                                              @endif
                                            </td>
                                            <td class="p-1">
                                              <input type="number"
                                                class="form-control form-control-sm text-center input-sub"
                                                name="sub[{{ $sklNilai->id }}][{{ $komp->id }}][{{ $sub->id }}]"
                                                min="0" max="100" step="0.01" {{-- value="{{ number_format($nsk?->nilai ?? 0, 2, '.', '') }}" --}}
                                                value="{{ $nsk?->nilai !== null ? number_format($nsk->nilai, 2, '.', '') : '' }}"
                                                data-nilai-id="{{ $sklNilai->id }}" data-komp-id="{{ $komp->id }}"
                                                data-sub-id="{{ $sub->id }}" data-bobot="{{ $komp->bobot }}"
                                                {{ $isLocked ? 'disabled' : '' }}>
                                            </td>
                                            <td
                                              class="text-center text-muted rata-sub-{{ $sklNilai->id }}-{{ $komp->id }}">
                                              {{ $rataSub !== null ? number_format($rataSub, 2) : '0.00' }}
                                            </td>
                                            <td
                                              class="text-center fw-semibold hasil-mapel-akhir hasil-{{ $sklNilai->id }}"
                                              data-nilai-id="{{ $sklNilai->id }}">
                                              {{ number_format($sklNilai->nilai_akhir ?? 0, 2) }}
                                            </td>
                                          </tr>
                                        @endforeach
                                      @endforeach
                                    </tbody>
                                  </table>
                                </div>

                              </div>{{-- end panel sub --}}
                            @endforeach
                          </div>{{-- end tab-content sub --}}
                        </div>
                      @endif{{-- end sub check --}}

                    </div>{{-- end panel komponen --}}
                  @endforeach
                </div>{{-- end tab-content komponen --}}

                {{-- ── Ringkasan nilai akhir (selalu tampil di bawah tab) ── --}}
                {{-- <div class="border-top px-3 py-3">
                  <h6 class="small fw-semibold text-muted mb-2">RINGKASAN NILAI AKHIR</h6>
                  <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                      <thead>
                        <tr>
                          <th width="40" class="text-center">No</th>
                          <th>Mata Pelajaran</th>
                          @foreach ($komponenHeaders as $komp)
                            <th class="text-center" width="90" title="{{ $komp->label }}">
                              {{ str($komp->label)->limit(12) }}
                              <br><small class="fw-normal text-muted">{{ $komp->bobot }}%</small>
                            </th>
                          @endforeach
                          <th class="text-center" width="90">Nilai Akhir</th>
                        </tr>
                      </thead>
                      <tbody>
                        @php $no = 1; @endphp
                        @foreach ($nilaisGrouped as $namaKelompok => $items)
                          <tr class="table-light">
                            <td colspan="{{ 3 + $komponenHeaders->count() }}"
                              class="fw-semibold py-1 px-3 small text-uppercase text-muted">
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
                                <td class="text-center" id="ring-{{ $sklNilai->id }}-{{ $komp->id }}">
                                  {{ number_format($kn?->nilai ?? 0, 2) }}
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
                </div> --}}

              @endif{{-- end komponenHeaders not empty --}}
            @endif{{-- end isEdit --}}

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
              <i class="icon-base ri ri-save-line me-1"></i>Simpan Nilai
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

      document.addEventListener("wheel", function(event) {
        if (document.activeElement.type === "number") {
          document.activeElement.blur();
        }
      });

      document.addEventListener('input', function(e) {
        // Hanya jalankan jika target adalah input type="number"
        if (e.target.tagName === 'INPUT' && e.target.type === 'number') {
          const min = parseFloat(e.target.getAttribute('min') ?? 0);
          const max = parseFloat(e.target.getAttribute('max') ?? 100);
          let val = parseFloat(e.target.value);

          // Jika nilainya bukan angka (misal user menghapus semua), abaikan
          if (isNaN(val)) return;

          // Validasi batasan
          if (val > max) {
            e.target.value = max;
          } else if (val < min) {
            e.target.value = min;
          }
        }
      });

      const numberInputs = document.querySelectorAll('input[type="number"]');
      numberInputs.forEach(input => {
        // Saat input kehilangan fokus (selesai mengetik), format menjadi 2 desimal
        input.addEventListener('blur', function() {
          let val = this.value;

          if (val == "") return;

          let num = parseFloat(val);
          if (!isNaN(num)) {
            // Set value dengan 2 angka di belakang koma
            this.value = num.toFixed(2);
          } else {
            this.value = "0.00";
          }
        });
      });

      document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' && e.target.type === 'number') {
          // Daftar karakter yang dilarang (misalnya 'e', '+')
          if (['e', 'E', '+'].includes(e.key)) {
            e.preventDefault();
          }
        }
      });

      document.addEventListener('focus', function(e) {
        // Pastikan yang difokus adalah input type="number"
        if (e.target.tagName === 'INPUT' && e.target.type === 'number') {
          // Gunakan setTimeout agar pemilihan teks terjadi setelah event fokus selesai
          setTimeout(() => {
            e.target.select();
          }, 0);
        }
      }, true);

      // // ════════════════════════════════════════════════════════════════════
      // // FITUR: INGAT POSISI TAB SAAT RELOAD
      // // ════════════════════════════════════════════════════════════════════
      // if (isManual) {
      //   // 1. Simpan posisi tab aktif ke sessionStorage saat user mengklik tab
      //   document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tabEl => {
      //     tabEl.addEventListener('shown.bs.tab', function(event) {
      //       const tabId = event.target.id;
      //       // Deteksi apakah ini tab level 1 (Komponen) atau level 2 (Sub-Komponen)
      //       if (tabId.includes('-s')) {
      //         // Ini tab Sub-Komponen (contoh: tab-k1-s2)
      //         const mainTabId = tabId.split('-s')[0];
      //         sessionStorage.setItem(`skl_${sklId}_sub_${mainTabId}`, tabId);
      //       } else {
      //         // Ini tab Komponen Utama (contoh: tab-k1)
      //         sessionStorage.setItem(`skl_${sklId}_main`, tabId);
      //       }
      //     });
      //   });

      //   // 2. Kembalikan posisi tab aktif saat halaman selesai di-reload
      //   // Pulihkan tab utama (Level 1)
      //   const savedMainTab = sessionStorage.getItem(`skl_${sklId}_main`);
      //   if (savedMainTab) {
      //     const mainBtn = document.getElementById(savedMainTab);
      //     if (mainBtn) mainBtn.click();
      //   }

      //   // Pulihkan tab sub-komponen (Level 2)
      //   // Diberi sedikit delay agar tab level 1 selesai terbuka terlebih dahulu
      //   setTimeout(() => {
      //     document.querySelectorAll('.nav-pills').forEach(pillNav => {
      //       const parentPaneId = pillNav.closest('.tab-pane')?.id; // ex: panel-k1
      //       if (parentPaneId) {
      //         const parentTabId = parentPaneId.replace('panel-', 'tab-'); // ex: tab-k1
      //         const savedSubTab = sessionStorage.getItem(`skl_${sklId}_sub_${parentTabId}`);
      //         if (savedSubTab) {
      //           const subBtn = document.getElementById(savedSubTab);
      //           if (subBtn) subBtn.click();
      //         }
      //       }
      //     });
      //   }, 100);
      // }

      // ════════════════════════════════════════════════════════════════════
      // JS MODE: EDIT
      // ════════════════════════════════════════════════════════════════════
      @if ($isEdit)

        // ── Helpers kalkulasi realtime ────────────────────────────────────────
        //
        // Setiap input memiliki data attributes:
        //   input-komponen : data-nilai-id, data-komp-id, data-bobot
        //   input-sub      : data-nilai-id, data-komp-id, data-sub-id, data-bobot

        function getRataSubUntukKomponen(nilaiId, kompId) {
          const inputs = [...document.querySelectorAll(
            `.input-sub[data-nilai-id="${nilaiId}"][data-komp-id="${kompId}"]`
          )];
          if (!inputs.length) return 0;
          // rata-rata dari semua sub yang terisi (kosong dianggap 0)
          const total = inputs.reduce((s, i) => s + (parseFloat(i.value) || 0), 0);
          return total / inputs.length;
        }

        // ── GANTI FUNGSI hitungHasilMapel LAMA DENGAN INI ─────────────────────────────
        function hitungHasilMapel(nilaiId) {
          let total = 0;

          // 1. Komponen tanpa sub (input-komponen)
          document.querySelectorAll(`.input-komponen[data-nilai-id="${nilaiId}"]`).forEach(inp => {
            const bobot = parseFloat(inp.dataset.bobot) || 0;
            const val = parseFloat(inp.value) || 0;
            total += val * bobot / 100;

            // Sync ke elemen ringkasan (jika ada class ring)
            document.querySelectorAll(`.ring-${nilaiId}-${inp.dataset.kompId}`).forEach(el => {
              el.textContent = val.toFixed(2);
            });
          });

          // 2. Komponen dengan sub — Ambil daftar Komponen ID unik
          const subKompIds = new Set();
          document.querySelectorAll(`.input-sub[data-nilai-id="${nilaiId}"]`).forEach(inp => {
            subKompIds.add(inp.dataset.kompId);
          });

          subKompIds.forEach(kompId => {
            const anyInput = document.querySelector(
              `.input-sub[data-nilai-id="${nilaiId}"][data-komp-id="${kompId}"]`);
            const bobot = anyInput ? (parseFloat(anyInput.dataset.bobot) || 0) : 0;

            // Hitung rata-rata sub langsung lewat helper fungsi yang sudah kamu buat
            const val = getRataSubUntukKomponen(nilaiId, kompId);
            total += val * bobot / 100;

            // Sync ke elemen ringkasan (jika ada class ring)
            document.querySelectorAll(`.ring-${nilaiId}-${kompId}`).forEach(el => {
              el.textContent = val.toFixed(2);
            });
          });

          // UPDATE SEMUA ELEMEN HASIL DI SEMUA TAB SEKALIGUS
          document.querySelectorAll(`.hasil-${nilaiId}`).forEach(el => {
            el.textContent = total.toFixed(2);
          });

          return total;
        }

        // ── GANTI FUNGSI hitungRataRata LAMA DENGAN INI ──────────────────────────────
        function hitungRataRata() {
          let total = 0,
            count = 0;

          if (isManual) {
            const seen = new Set();
            // Gunakan class generic hasil-mapel-akhir untuk mengambil nilai unik tiap mapel
            document.querySelectorAll('.hasil-mapel-akhir').forEach(el => {
              const nilaiId = el.dataset.nilaiId;
              if (!seen.has(nilaiId)) {
                seen.add(nilaiId);
                total += parseFloat(el.textContent) || 0;
                count++;
              }
            });
          } else {
            document.querySelectorAll('.input-nilai').forEach(i => {
              total += parseFloat(i.value) || 0;
              count++;
            });
          }

          const rata = count > 0 ? (total / count).toFixed(2) : '0.00';
          ['nilai-rata-rata', 'sidebar-rata', 'tfoot-rata'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = rata;
          });
        }

        // ── GANTI BAGIAN INISIASI DAN EVENT input-sub DENGAN INI ──────────────────────
        if (isManual) {
          // Event untuk komponen tanpa sub
          document.querySelectorAll('.input-komponen').forEach(input => {
            hitungHasilMapel(input.dataset.nilaiId);
            input.addEventListener('input', function() {
              hitungHasilMapel(this.dataset.nilaiId);
              hitungRataRata();
            });
          });

          // Event untuk sub-komponen (Diperbaiki menggunakan querySelectorAll)
          document.querySelectorAll('.input-sub').forEach(input => {
            const rata = getRataSubUntukKomponen(input.dataset.nilaiId, input.dataset.kompId);

            document.querySelectorAll(`.rata-sub-${input.dataset.nilaiId}-${input.dataset.kompId}`).forEach(
              el => {
                el.textContent = rata.toFixed(2);
              });
            hitungHasilMapel(input.dataset.nilaiId);

            input.addEventListener('input', function() {
              const r = getRataSubUntukKomponen(this.dataset.nilaiId, this.dataset.kompId);

              // Update seluruh label rata-sub di semua tab yang sama
              document.querySelectorAll(`.rata-sub-${this.dataset.nilaiId}-${this.dataset.kompId}`).forEach(
                el => {
                  el.textContent = r.toFixed(2);
                });

              hitungHasilMapel(this.dataset.nilaiId);
              hitungRataRata();
            });
          });
        } else {
          // Mode nilai jadi
          document.querySelectorAll('.input-nilai').forEach(i =>
            i.addEventListener('input', hitungRataRata)
          );
        }

        hitungRataRata();

        // ── Simpan via AJAX ───────────────────────────────────────────────────
        document.getElementById('btn-simpan')?.addEventListener('click', async function() {
          const btn = this;
          const oriTxt = btn.innerHTML;
          btn.disabled = true;
          btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

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
                showConfirmButton: false,
              });
              // setTimeout(() => location.reload(), 2100);

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
        // ════════════════════════════════════════════════════════════════════
        // JS MODE: SHOW
        // ════════════════════════════════════════════════════════════════════

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
      {{-- end isEdit --}}

    });
  </script>
@endsection
