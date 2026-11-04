@extends('layouts/layoutMaster')

@section('title', 'Detail Modul Ajar')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')

  {{-- =========================================================== --}}
  {{-- HEADER                                                       --}}
  {{-- =========================================================== --}}
  <div class="row mb-4 g-4">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <a href="{{ route('learning-modules.index') }}" class="btn btn-outline-secondary btn-sm">
          <i class="ri ri-arrow-left-line me-1"></i> Kembali ke Daftar Modul
        </a>

        @if ($learningModule->status === 'generated' || $learningModule->status === 'published')
          <div class="d-flex gap-2">
            @if ($learningModule->status === 'generated' || $learningModule->status === 'published')
              <a href="{{ route('learning-modules.export', $learningModule->id) }}" class="btn btn-outline-primary btn-sm">
                <i class="ri ri-file-word-2-line me-1"></i> Download Word
              </a>
            @endif
          </div>
        @endif
      </div>

      <div class="card">
        <div class="card-body d-flex align-items-center gap-4 py-4">
          <div class="avatar avatar-xl flex-shrink-0">
            <span class="avatar-initial rounded-circle bg-label-primary">
              <i class="icon-base ri ri-file-list-3-line ri-28px"></i>
            </span>
          </div>
          <div class="flex-grow-1">
            <h4 class="mb-1">{{ $learningModule->judul_modul }}</h4>
            <div class="d-flex gap-4 flex-wrap">
              <span class="text-muted"><i
                  class="icon-base ri ri-book-2-line me-1"></i>{{ $learningModule->mapel->nama ?? '-' }}</span>
              <span class="text-muted"><i
                  class="icon-base ri ri-user-line me-1"></i>{{ $learningModule->ptk->nama_ptk ?? '-' }}</span>
              <span class="text-muted"><i class="icon-base ri ri-time-line me-1"></i>{{ $learningModule->jumlah_jp }} JP /
                {{ $learningModule->jumlah_pertemuan }}x Pertemuan</span>
            </div>
          </div>
          <div>
            @php
              $statusBadge = [
                  'draft' => 'bg-label-secondary',
                  'processing' => 'bg-label-warning',
                  'generated' => 'bg-label-success',
                  'failed' => 'bg-label-danger',
                  'published' => 'bg-label-info',
              ];
            @endphp
            <span class="badge {{ $statusBadge[$learningModule->status] ?? 'bg-label-secondary' }} fs-6">
              {{ ucfirst($learningModule->status) }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- STATUS: PROCESSING                                           --}}
  {{-- =========================================================== --}}
  @if ($learningModule->status === 'processing')
    <div class="card">
      <div class="card-body text-center py-5">
        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
        <h5>Modul ajar sedang diproses oleh AI...</h5>
        <p class="text-muted">Halaman ini akan otomatis update. Mohon tunggu sebentar.</p>
      </div>
    </div>

    <script>
      // auto-refresh tiap 5 detik selama masih processing
      setTimeout(() => window.location.reload(), 5000);
    </script>
  @endif

  {{-- =========================================================== --}}
  {{-- STATUS: FAILED                                               --}}
  {{-- =========================================================== --}}
  @if ($learningModule->status === 'failed')
    <div class="card">
      <div class="card-body text-center py-5">
        <div class="avatar avatar-lg mx-auto mb-3">
          <span class="avatar-initial rounded-circle bg-label-danger">
            <i class="ri ri-error-warning-line ri-24px"></i>
          </span>
        </div>
        <h5>Gagal membuat modul ajar</h5>
        <p class="text-muted">{{ $learningModule->failed_reason ?? 'Terjadi kesalahan saat memproses modul ajar.' }}</p>
        <button class="btn btn-primary" id="btn-retry-detail">
          <i class="ri ri-refresh-line me-1"></i> Coba Lagi
        </button>
      </div>
    </div>
  @endif

  {{-- =========================================================== --}}
  {{-- KONTEN MODUL AJAR (kalau sudah generated/published)          --}}
  {{-- =========================================================== --}}
  @if (in_array($learningModule->status, ['generated', 'published']))

    {{-- A. Identitas Modul --}}
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="ri ri-information-line me-2"></i>A. Identitas Modul</h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4"><strong>Nama Penyusun</strong>
            <div class="text-muted">{{ $learningModule->ptk->nama_ptk ?? '-' }}</div>
          </div>
          <div class="col-md-4"><strong>Satuan Pendidikan</strong>
            <div class="text-muted">{{ $learningModule->sekolah->nama_sekolah ?? '-' }}</div>
          </div>
          <div class="col-md-4"><strong>Mata Pelajaran</strong>
            <div class="text-muted">{{ $learningModule->mapel->nama ?? '-' }}</div>
          </div>
          <div class="col-md-3"><strong>Kelas/Fase</strong>
            <div class="text-muted">{{ $learningModule->kelas }} / {{ $learningModule->fase }}</div>
          </div>
          <div class="col-md-3"><strong>Bab</strong>
            <div class="text-muted">{{ $learningModule->bab ?? '-' }}</div>
          </div>
          <div class="col-md-3"><strong>Topik</strong>
            <div class="text-muted">{{ $learningModule->materi_pokok }}</div>
          </div>
          <div class="col-md-3"><strong>Alokasi Waktu</strong>
            <div class="text-muted">{{ $learningModule->jumlah_jp }} JP ({{ $learningModule->jumlah_pertemuan }}
              pertemuan)</div>
          </div>
        </div>
      </div>
    </div>

    {{-- B. Identifikasi Murid --}}
    @if ($learningModule->identifikasi_murid)
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="ri ri-user-search-line me-2"></i>B. Identifikasi Murid</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <strong>Pengetahuan Awal</strong>
            <p class="text-muted mb-0">{{ $learningModule->identifikasi_murid['pengetahuan_awal'] ?? '-' }}</p>
          </div>
          <div class="mb-3">
            <strong>Minat</strong>
            <p class="text-muted mb-0">{{ $learningModule->identifikasi_murid['minat'] ?? '-' }}</p>
          </div>
          <div class="mb-0">
            <strong>Kebutuhan Belajar</strong>
            <p class="text-muted mb-0">{{ $learningModule->identifikasi_murid['kebutuhan_belajar'] ?? '-' }}</p>
          </div>
        </div>
      </div>
    @endif

    {{-- C. Materi Pelajaran --}}
    @if ($learningModule->materi_pelajaran_list)
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="ri ri-book-open-line me-2"></i>C. Materi Pelajaran</h5>
        </div>
        <div class="card-body">
          <ol class="mb-0">
            @foreach ($learningModule->materi_pelajaran_list as $materi)
              <li class="text-muted">{{ $materi }}</li>
            @endforeach
          </ol>
        </div>
      </div>
    @endif

    {{-- D. Dimensi Profil Lulusan --}}
    @if ($learningModule->dimensi_profil_lulusan)
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="ri ri-award-line me-2"></i>D. Dimensi Profil Lulusan</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-bordered mb-0">
              <thead class="table-light">
                <tr>
                  <th width="30%">Dimensi</th>
                  <th>Elemen yang Dikembangkan</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($learningModule->dimensi_profil_lulusan as $dim)
                  <tr>
                    <td><strong>{{ $dim['dimensi'] ?? '-' }}</strong></td>
                    <td class="text-muted">{{ $dim['elemen'] ?? '-' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @endif

    {{-- E. Desain Pembelajaran --}}
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="ri ri-flag-line me-2"></i>E. Desain Pembelajaran</h5>
      </div>
      <div class="card-body">
        <div class="mb-3">
          <strong>Capaian Pembelajaran (CP)</strong>
          <p class="text-muted mb-0">{{ $learningModule->capaian_pembelajaran }}</p>
        </div>
        <div class="mb-3">
          <strong>Lintas Disiplin Ilmu</strong>
          <p class="text-muted mb-0">{{ $learningModule->lintas_disiplin_ilmu }}</p>
        </div>
        <div class="mb-3">
          <strong>Tujuan Pembelajaran</strong>
          <ul class="text-muted mb-0">
            @foreach ($learningModule->tujuan_pembelajaran ?? [] as $tp)
              <li>{{ $tp }}</li>
            @endforeach
          </ul>
        </div>
        <div class="mb-3">
          <strong>Praktik Pedagogis (Pendekatan Deep Learning)</strong>
          <p class="text-muted mb-0" style="white-space: pre-line;">{{ $learningModule->praktik_pedagogis }}</p>
        </div>
        <div class="mb-0">
          <strong>Pemanfaatan Teknologi Digital</strong>
          <p class="text-muted mb-0">{{ $learningModule->pemanfaatan_teknologi }}</p>
        </div>
      </div>
    </div>

    {{-- Pengalaman Belajar per Blok & Pertemuan --}}
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="ri ri-calendar-todo-line me-2"></i>Pengalaman Belajar (Rincian per Pertemuan)</h5>
      </div>
      <div class="card-body">
        @php
          $groupedByBlok = $learningModule->meetings->groupBy('blok_judul');
        @endphp

        @foreach ($groupedByBlok as $blokJudul => $meetingsInBlok)
          <div class="mb-4">
            <h6 class="bg-label-primary p-2 rounded">{{ $blokJudul ?? 'Blok Pembelajaran' }}</h6>

            @foreach ($meetingsInBlok as $meeting)
              <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <strong>{{ $meeting->rentang_pertemuan ?? 'Pertemuan ' . $meeting->pertemuan_ke }}</strong>
                  <span class="badge bg-label-secondary">{{ $meeting->jp }} JP</span>
                </div>

                @if ($meeting->kegiatan_awal_list)
                  <div class="mb-2">
                    <small class="text-uppercase text-muted fw-bold">Kegiatan Awal</small>
                    <ol class="mb-0">
                      @foreach ($meeting->kegiatan_awal_list as $item)
                        <li class="text-muted">{{ $item }}</li>
                      @endforeach
                    </ol>
                  </div>
                @endif

                @if ($meeting->kegiatan_inti_list)
                  <div class="mb-2">
                    <small class="text-uppercase text-muted fw-bold">Kegiatan Inti</small>
                    <ol class="mb-0">
                      @foreach ($meeting->kegiatan_inti_list as $item)
                        <li class="text-muted">{{ $item }}</li>
                      @endforeach
                    </ol>
                  </div>
                @endif

                @if ($meeting->kegiatan_penutup_list)
                  <div class="mb-0">
                    <small class="text-uppercase text-muted fw-bold">Kegiatan Penutup</small>
                    <ol class="mb-0">
                      @foreach ($meeting->kegiatan_penutup_list as $item)
                        <li class="text-muted">{{ $item }}</li>
                      @endforeach
                    </ol>
                  </div>
                @endif
              </div>
            @endforeach
          </div>
        @endforeach
      </div>
    </div>

    {{-- Asesmen --}}
    @if ($learningModule->asesmen)
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="mb-0"><i class="ri ri-clipboard-line me-2"></i>Asesmen</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <strong>Asesmen Awal (Diagnostik)</strong>
            <p class="text-muted mb-0">{{ $learningModule->asesmen['diagnostik'] ?? '-' }}</p>
          </div>
          <div class="mb-3">
            <strong>Asesmen Formatif (Proses)</strong>
            <ul class="text-muted mb-0">
              @foreach ($learningModule->asesmen['formatif'] ?? [] as $f)
                <li>{{ $f }}</li>
              @endforeach
            </ul>
          </div>
          <div class="mb-0">
            <strong>Asesmen Sumatif (Akhir Bab)</strong>
            <ul class="text-muted mb-0">
              @foreach ($learningModule->asesmen['sumatif'] ?? [] as $s)
                <li>{{ $s }}</li>
              @endforeach
            </ul>
          </div>
        </div>
      </div>
    @endif

    {{-- Pengayaan & Remedial --}}
    <div class="row mb-4">
      @if ($learningModule->pengayaan)
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-header">
              <h6 class="mb-0"><i class="ri ri-arrow-up-circle-line me-2"></i>Pengayaan</h6>
            </div>
            <div class="card-body">
              <ul class="text-muted mb-0">
                @foreach ($learningModule->pengayaan as $p)
                  <li>{{ $p }}</li>
                @endforeach
              </ul>
            </div>
          </div>
        </div>
      @endif

      @if ($learningModule->remedial)
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-header">
              <h6 class="mb-0"><i class="ri ri-arrow-down-circle-line me-2"></i>Remedial</h6>
            </div>
            <div class="card-body">
              <ul class="text-muted mb-0">
                @foreach ($learningModule->remedial as $r)
                  <li>{{ $r }}</li>
                @endforeach
              </ul>
            </div>
          </div>
        </div>
      @endif
    </div>

    {{-- Refleksi --}}
    <div class="row mb-4">
      @if ($learningModule->refleksi_peserta_didik)
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-header">
              <h6 class="mb-0"><i class="ri ri-question-line me-2"></i>Refleksi Peserta Didik</h6>
            </div>
            <div class="card-body">
              <ol class="mb-0">
                @foreach ($learningModule->refleksi_peserta_didik as $r)
                  <li class="text-muted">{{ $r }}</li>
                @endforeach
              </ol>
            </div>
          </div>
        </div>
      @endif

      @if ($learningModule->refleksi_pendidik)
        <div class="col-md-6">
          <div class="card h-100">
            <div class="card-header">
              <h6 class="mb-0"><i class="ri ri-question-answer-line me-2"></i>Refleksi Pendidik</h6>
            </div>
            <div class="card-body">
              <ol class="mb-0">
                @foreach ($learningModule->refleksi_pendidik as $r)
                  <li class="text-muted">{{ $r }}</li>
                @endforeach
              </ol>
            </div>
          </div>
        </div>
      @endif
    </div>

  @endif

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
          const btnRetry = document.getElementById('btn-retry-detail');

          if (btnRetry) {
            btnRetry.addEventListener('click', function() {
                  btnRetry.disabled = true;
                  btnRetry.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';

                  fetch('{{ route('learning-modules.retry', $learningModule->id) }}', {
                        method: 'POST',
                        headers: {
                          'X-CSRF-TOKEN': '{{ csrf_token() }}',
                          'Accept': 'application/json',
                        },
