@extends('layouts/layoutMaster')

@section('title', 'Konfigurasi SKL')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')

  @if ($konfigurasi)
    <div class="mb-4">
      <a href="{{ route('skl.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="icon-base ri ri-arrow-left-line me-1"></i>Lihat Daftar SKL
      </a>
    </div>
  @endif

  <div class="row justify-content-center">
    <div class="col-12 col-lg-7">

      <div class="card">
        <div class="card-header border-bottom">
          <h5 class="mb-0">
            <i class="icon-base ri ri-equalizer-line me-2"></i>Konfigurasi SKL
          </h5>
          <small class="text-muted">Semester {{ $semester->tahun_pelajaran ?? '-' }}</small>
        </div>
        <div class="card-body">

          @if (session('success'))
            <div class="alert alert-success py-2 mb-4">
              <i class="icon-base ri ri-checkbox-circle-line me-1"></i>{{ session('success') }}
            </div>
          @endif

          @if ($errors->any())
            <div class="alert alert-danger py-2 mb-4">
              <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $err)
                  <li>{{ $err }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form action="{{ route('skl.konfigurasi.update') }}" method="POST">
            @csrf
            @method('PUT')

            {{-- ── Mode Input ──────────────────────────────────────────────── --}}
            <div class="mb-5">
              <label class="form-label fw-semibold">Mode Input Nilai</label>

              @if ($konfigurasi?->is_terkunci ?? '')
                <div class="alert alert-warning py-2 mb-3 d-flex gap-2">
                  <i class="icon-base ri ri-lock-line mt-1 flex-shrink-0"></i>
                  <div class="small">
                    Mode input sudah <strong>terkunci</strong> karena sudah ada data SKL yang dibuat.
                    Hubungi Admin untuk mengubah.
                  </div>
                </div>
              @endif

              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <div class="card border h-100 {{ $konfigurasi?->isModeJadi() ? 'border-primary' : '' }}"
                    style="cursor: {{ $konfigurasi?->is_terkunci ? 'not-allowed' : 'pointer' }}" id="card-jadi"
                    onclick="{{ $konfigurasi?->is_terkunci ? '' : 'selectMode(\'jadi\')' }}">
                    <div class="card-body py-3">
                      <div class="d-flex align-items-center gap-3 mb-2">
                        <input type="radio" name="mode_input" value="jadi" id="mode-jadi"
                          class="form-check-input mt-0" {{ $konfigurasi?->isModeJadi() ? 'checked' : '' }}
                          {{ $konfigurasi?->is_terkunci ? 'disabled' : '' }}>
                        <label for="mode-jadi" class="fw-semibold mb-0" style="cursor:pointer">Nilai Jadi</label>
                        <span class="badge bg-label-info ms-auto">Simpel</span>
                      </div>
                      <p class="text-muted small mb-0">
                        Operator input langsung nilai akhir per mata pelajaran (0–100).
                        Cocok jika nilai sudah dihitung di luar sistem.
                      </p>
                    </div>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="card border h-100 {{ $konfigurasi?->isModeManual() ? 'border-warning' : '' }}"
                    style="cursor: {{ $konfigurasi?->is_terkunci ? 'not-allowed' : 'pointer' }}" id="card-manual"
                    onclick="{{ $konfigurasi?->is_terkunci ? '' : 'selectMode(\'manual\')' }}">
                    <div class="card-body py-3">
                      <div class="d-flex align-items-center gap-3 mb-2">
                        <input type="radio" name="mode_input" value="manual" id="mode-manual"
                          class="form-check-input mt-0" {{ $konfigurasi?->isModeManual() ? 'checked' : '' }}
                          {{ $konfigurasi?->is_terkunci ? 'disabled' : '' }}>
                        <label for="mode-manual" class="fw-semibold mb-0" style="cursor:pointer">Manual Berbobot</label>
                        <span class="badge bg-label-warning ms-auto">Fleksibel</span>
                      </div>
                      <p class="text-muted small mb-0">
                        Nilai dihitung otomatis dari komponen berbobot (misal: Rapor 40% + Ujian 60%).
                        Form input akan ditampilkan dalam tab per komponen.
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="alert alert-info py-2 mb-4 d-flex gap-2">
              <i class="icon-base ri ri-information-line mt-1 flex-shrink-0"></i>
              <div class="small">
                Tempat terbit, tanggal terbit, dan penomoran SKL kini diatur di halaman
                <a href="{{ route('skl.pengaturan-nomor') }}" class="alert-link">Tanggal &amp; Penomoran</a>
                (tombol di daftar SKL). Halaman ini hanya untuk memilih mode input nilai.
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
              <a href="{{ route('skl.index') }}" class="btn btn-outline-secondary">Batal</a>
              <button type="submit" class="btn btn-primary">
                <i class="icon-base ri ri-save-line me-1"></i>Simpan Konfigurasi
              </button>
            </div>

          </form>

        </div>
      </div>

    </div>
  </div>

@endsection

@section('page-script')
  <script>
    function selectMode(mode) {
      document.getElementById('mode-' + mode).checked = true;
      // Update border visual
      ['jadi', 'manual'].forEach(m => {
        const card = document.getElementById('card-' + m);
        card.classList.toggle('border-primary', m === 'jadi' && mode === 'jadi');
        card.classList.toggle('border-warning', m === 'manual' && mode === 'manual');
        card.classList.toggle('border', true);
      });
    }

    document.addEventListener('DOMContentLoaded', function() {
      // Radio card click
      document.getElementById('mode-jadi')?.addEventListener('change', () => selectMode('jadi'));
      document.getElementById('mode-manual')?.addEventListener('change', () => selectMode('manual'));
    });
  </script>
@endsection
