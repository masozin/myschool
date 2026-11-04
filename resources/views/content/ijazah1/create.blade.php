@extends('layouts/layoutMaster')

@section('title', 'Tambah Ijazah')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')

  <div class="mb-4">
    <a href="{{ route('ijazah.index') }}" class="btn btn-sm btn-outline-secondary">
      <i class="icon-base ri ri-arrow-left-line me-1"></i>Kembali
    </a>
  </div>

  <div class="row justify-content-center">
    <div class="col-12 col-lg-6">

      {{-- Info Mode Input (dari konfigurasi sekolah) --}}
      <div class="card mb-4 border-{{ $konfigurasi->isModeManual() ? 'warning' : 'info' }}">
        <div class="card-body py-3 d-flex align-items-center gap-3">
          <div class="avatar">
            <span class="avatar-initial rounded-circle bg-label-{{ $konfigurasi->isModeManual() ? 'warning' : 'info' }}">
              <i class="icon-base ri ri-{{ $konfigurasi->isModeManual() ? 'calculator' : 'input-cursor-move' }}-line"></i>
            </span>
          </div>
          <div class="flex-grow-1">
            <div class="fw-semibold">
              Mode Input:
              @if ($konfigurasi->isModeManual())
                <span class="text-warning">Manual Berbobot</span>
              @else
                <span class="text-info">Nilai Jadi</span>
              @endif
            </div>
            <small class="text-muted">
              @if ($konfigurasi->isModeManual())
                Nilai dihitung otomatis dari komponen berbobot yang sudah dikonfigurasi.
              @else
                Operator input langsung nilai akhir per mata pelajaran.
              @endif
              {{-- Jika belum terkunci, operator masih bisa ubah konfigurasi --}}
              @if (!$konfigurasi->is_terkunci)
                <a href="{{ route('ijazah.konfigurasi.edit') }}" class="ms-2">Ubah konfigurasi</a>
              @else
                <span class="ms-2 badge bg-label-secondary">Terkunci</span>
              @endif
            </small>
          </div>
        </div>
      </div>

      {{-- Peringatan jika mode manual tapi komponen belum ada --}}
      @if ($konfigurasi->isModeManual())
        @php
          $adaKomponen =
              \App\Models\IjazahKomponen::untukSekolah(
                  $konfigurasi->sekolah_id ?? 0,
                  $semester->tahun_pelajaran,
              )->exists() ?? false;

          // Cek via scope
          $jumlahKomponen = \App\Models\IjazahKomponen::where('tahun_pelajaran', $semester->tahun_pelajaran)->count();
        @endphp
        @if ($jumlahKomponen === 0)
          <div class="alert alert-warning d-flex gap-2 mb-4">
            <i class="icon-base ri ri-error-warning-line mt-1 flex-shrink-0"></i>
            <div>
              <strong>Komponen nilai belum dikonfigurasi.</strong>
              Minta Admin untuk mengatur komponen nilai ijazah terlebih dahulu di menu
              <a href="{{ route('ijazah.komponen.index') }}">Konfigurasi Komponen</a>.
            </div>
          </div>
        @endif
      @endif

      <div class="card">
        <div class="card-header border-bottom">
          <h5 class="mb-0"><i class="icon-base ri ri-award-line me-2"></i>Tambah Data Ijazah</h5>
        </div>
        <div class="card-body">

          @if ($errors->any())
            <div class="alert alert-danger py-2 mb-4">
              <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $err)
                  <li>{{ $err }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form action="{{ route('ijazah.store') }}" method="POST" id="form-tambah-ijazah">
            @csrf

            {{-- Pilih Siswa --}}
            <div class="mb-4">
              <label class="form-label fw-semibold">
                Siswa <span class="text-danger">*</span>
              </label>
              <select name="siswa_id" id="select-siswa" class="form-select select2"
                data-placeholder="Cari nama atau NISN siswa...">
                <option value=""></option>
                @foreach ($siswaList as $siswa)
                  <option value="{{ $siswa->id }}" {{ old('siswa_id') == $siswa->id ? 'selected' : '' }}
                    data-nisn="{{ $siswa->nisn }}"
                    data-rombel="{{ $siswa->rombelAnggota->last()?->rombel?->nama_rombel ?? '-' }}">
                    {{ $siswa->nama_siswa }} — {{ $siswa->nisn }}
                  </option>
                @endforeach
              </select>
              <div class="form-text">Hanya siswa yang belum memiliki data ijazah yang ditampilkan.</div>

              {{-- Preview siswa terpilih --}}
              <div id="info-siswa" class="mt-3 d-none">
                <div class="alert alert-primary py-2 mb-0 d-flex align-items-center gap-3">
                  <i class="icon-base ri ri-user-line ri-20px flex-shrink-0"></i>
                  <div>
                    <strong id="info-nama"></strong>
                    <span class="text-muted ms-2 small" id="info-nisn"></span>
                    <br>
                    <small class="text-muted">Rombel: <span id="info-rombel"></span></small>
                  </div>
                </div>
              </div>
            </div>

            @if ($siswaList->isEmpty())
              <div class="alert alert-info py-2 mb-4">
                <i class="icon-base ri ri-information-line me-1"></i>
                Semua siswa sudah memiliki data ijazah.
              </div>
            @endif

            <div class="d-flex justify-content-end gap-2">
              <a href="{{ route('ijazah.index') }}" class="btn btn-outline-secondary">Batal</a>
              <button type="submit" class="btn btn-primary" {{ $siswaList->isEmpty() ? 'disabled' : '' }}>
                <i class="icon-base ri ri-save-line me-1"></i>Buat Data Ijazah
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
    $('#select-siswa').select2({
      placeholder: 'Cari nama atau NISN siswa...',
      allowClear: true,
      width: '100%'
    });

    $('#select-siswa').on('change', function() {
      const opt = this.options[this.selectedIndex];
      const infoEl = document.getElementById('info-siswa');
      if (this.value) {
        document.getElementById('info-nama').textContent = opt.text.split('—')[0].trim();
        document.getElementById('info-nisn').textContent = opt.dataset.nisn;
        document.getElementById('info-rombel').textContent = opt.dataset.rombel;
        infoEl.classList.remove('d-none');
      } else {
        infoEl.classList.add('d-none');
      }
    });

    if ($('#select-siswa').val()) $('#select-siswa').trigger('change');
  </script>
@endsection
