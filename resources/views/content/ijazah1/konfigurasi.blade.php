@extends('layouts/layoutMaster')

@section('title', 'Konfigurasi SKL')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')

  <div class="mb-4">
    <a href="{{ route('skl.index') }}" class="btn btn-sm btn-outline-secondary">
      <i class="icon-base ri ri-arrow-left-line me-1"></i>Kembali
    </a>
  </div>

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

              @if ($konfigurasi->is_terkunci)
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
                  <div class="card border h-100 {{ $konfigurasi->isModeJadi() ? 'border-primary' : '' }}"
                    style="cursor: {{ $konfigurasi->is_terkunci ? 'not-allowed' : 'pointer' }}" id="card-jadi"
                    onclick="{{ $konfigurasi->is_terkunci ? '' : 'selectMode(\'jadi\')' }}">
                    <div class="card-body py-3">
                      <div class="d-flex align-items-center gap-3 mb-2">
                        <input type="radio" name="mode_input" value="jadi" id="mode-jadi"
                          class="form-check-input mt-0" {{ $konfigurasi->isModeJadi() ? 'checked' : '' }}
                          {{ $konfigurasi->is_terkunci ? 'disabled' : '' }}>
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
                  <div class="card border h-100 {{ $konfigurasi->isModeManual() ? 'border-warning' : '' }}"
                    style="cursor: {{ $konfigurasi->is_terkunci ? 'not-allowed' : 'pointer' }}" id="card-manual"
                    onclick="{{ $konfigurasi->is_terkunci ? '' : 'selectMode(\'manual\')' }}">
                    <div class="card-body py-3">
                      <div class="d-flex align-items-center gap-3 mb-2">
                        <input type="radio" name="mode_input" value="manual" id="mode-manual"
                          class="form-check-input mt-0" {{ $konfigurasi->isModeManual() ? 'checked' : '' }}
                          {{ $konfigurasi->is_terkunci ? 'disabled' : '' }}>
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

            <hr>

            {{-- ── Tempat & Tanggal Terbit ─────────────────────────────────── --}}
            <div class="mb-4">
              <label class="form-label fw-semibold">
                <i class="icon-base ri ri-map-pin-line me-1"></i>Tempat Penerbitan SKL
              </label>
              <input type="text" name="tempat_terbit" class="form-control @error('tempat_terbit') is-invalid @enderror"
                placeholder="misal: Jakarta" value="{{ old('tempat_terbit', $konfigurasi->tempat_terbit) }}">
              <div class="form-text">
                Nama kota/kabupaten yang akan tercetak di baris tanda tangan SKL,
                misal: <em>Jakarta, 15 Juni 2025</em>.
              </div>
              @error('tempat_terbit')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold">
                <i class="icon-base ri ri-calendar-line me-1"></i>Tanggal Terbit Default
              </label>
              <input type="date" name="tanggal_terbit"
                class="form-control @error('tanggal_terbit') is-invalid @enderror" style="max-width:220px"
                value="{{ old('tanggal_terbit', $konfigurasi->tanggal_terbit?->format('Y-m-d')) }}">
              <div class="form-text">
                Tanggal default yang akan otomatis terisi saat membuat nomor SKL.
                Bisa diubah per-siswa saat input nomor.
              </div>
              @error('tanggal_terbit')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- ── Prefix Nomor ────────────────────────────────────────────── --}}
            <div class="mb-5">
              <label class="form-label fw-semibold">
                <i class="icon-base ri ri-hashtag me-1"></i>Prefix Nomor SKL
              </label>
              <div class="input-group" style="max-width: 240px">
                <input type="text" name="prefix_nomor" class="form-control @error('prefix_nomor') is-invalid @enderror"
                  placeholder="SKL" value="{{ old('prefix_nomor', $konfigurasi->prefix_nomor ?? 'SKL') }}">
                <span class="input-group-text text-muted">/001/JUNI/2025</span>
              </div>
              <div class="form-text">
                Prefix untuk nomor SKL yang di-generate otomatis.
                Format lengkap: <code>PREFIX/001/BULAN/TAHUN</code>.
              </div>
              @error('prefix_nomor')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Preview lokasi & tanggal --}}
            <div class="alert alert-light border mb-5" id="preview-lokasi">
              <small class="text-muted d-block mb-1 fw-semibold">PREVIEW TANDA TANGAN SKL</small>
              <div id="preview-lokasi-text" class="text-muted fst-italic small">
                {{ $konfigurasi->lokasiTanggalTerbit() }}
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
      // Live preview lokasi & tanggal
      const inputTempat = document.querySelector('input[name="tempat_terbit"]');
      const inputTanggal = document.querySelector('input[name="tanggal_terbit"]');
      const previewEl = document.getElementById('preview-lokasi-text');

      function updatePreview() {
        const tempat = inputTempat?.value?.trim() || '...';
        const tanggal = inputTanggal?.value ?
          new Date(inputTanggal.value).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
          }) :
          '...';
        previewEl.textContent = `${tempat}, ${tanggal}`;
      }

      inputTempat?.addEventListener('input', updatePreview);
      inputTanggal?.addEventListener('change', updatePreview);

      // Radio card click
      document.getElementById('mode-jadi')?.addEventListener('change', () => selectMode('jadi'));
      document.getElementById('mode-manual')?.addEventListener('change', () => selectMode('manual'));
    });
  </script>
@endsection
