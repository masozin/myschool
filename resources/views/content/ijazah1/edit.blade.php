@extends('layouts/layoutMaster')

@section('title', 'Edit Ijazah - ' . $ijazah->siswa->nama_siswa)

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

@section('content')

  <div class="mb-4">
    <a href="{{ route('ijazah.show', $ijazah) }}" class="btn btn-sm btn-outline-secondary">
      <i class="icon-base ri ri-arrow-left-line me-1"></i>Kembali
    </a>
  </div>

  <div class="row justify-content-center">
    <div class="col-12 col-lg-6">
      <div class="card">
        <div class="card-header border-bottom">
          <h5 class="mb-0">
            <i class="icon-base ri ri-settings-3-line me-2"></i>
            Edit Header Ijazah — {{ $ijazah->siswa->nama_siswa }}
          </h5>
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

          <form action="{{ route('ijazah.update', $ijazah) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
              <label class="form-label">Nomor Ijazah</label>
              <input type="text" name="nomor_ijazah" class="form-control" placeholder="Nomor seri ijazah resmi"
                value="{{ old('nomor_ijazah', $ijazah->nomor_ijazah) }}">
            </div>

            <div class="mb-5">
              <label class="form-label">Tanggal Terbit</label>
              <input type="text" name="tanggal_terbit" id="tanggal_terbit" class="form-control flatpickr-date"
                placeholder="YYYY-MM-DD" value="{{ old('tanggal_terbit', $ijazah->tanggal_terbit?->format('Y-m-d')) }}">
            </div>

            <div class="d-flex justify-content-end gap-2">
              <a href="{{ route('ijazah.show', $ijazah) }}" class="btn btn-outline-secondary">Batal</a>
              <button type="submit" class="btn btn-primary">
                <i class="icon-base ri ri-save-line me-1"></i>Simpan
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
    flatpickr('#tanggal_terbit', {
      dateFormat: 'Y-m-d',
      allowInput: true
    });
  </script>
@endsection
