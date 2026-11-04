{{-- resources/views/operator/copy_data_periodik.blade.php --}}
@extends('layouts/contentNavbarLayout')

@section('title', 'Salin Data Periodik')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="fw-bold mb-1">Salin Data Periodik</h4>

  @if ($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
  @endif

  @if (session('warning'))
  <div class="alert alert-warning">{{ session('warning') }}</div>
  @endif
  @if (session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  @if (!$bisaSalin || !$detailTerbaru)
  <div class="alert alert-info mt-3">
    Belum ada data semester sebelumnya yang bisa disalin.
    Silakan tambahkan rombel secara manual.
  </div>
  <a href="{{ route('rombel.index') }}" class="btn btn-primary">Kelola Rombel</a>
  @else
  <p class="text-muted mb-4">
    Data periodik untuk semester aktif belum lengkap. Periksa data berikut,
    lalu klik <strong>Salin Data</strong>.
  </p>

  <div class="card mb-4">
    <div class="card-header">
      <h6 class="mb-0">Detail Sekolah (Semester Terakhir Diisi)</h6>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label text-muted">Nama Sekolah</label>
          <input type="text" class="form-control" value="{{ $detailTerbaru->nama_sekolah }}" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label text-muted">Kepala Sekolah</label>
          <input type="text" class="form-control" value="{{ $detailTerbaru->kepala_sekolah }}" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label text-muted">NIP Kepala Sekolah</label>
          <input type="text" class="form-control" value="{{ $detailTerbaru->nip ?? '-' }}" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label text-muted">No. Telp</label>
          <input type="text" class="form-control" value="{{ $detailTerbaru->no_telp ?? '-' }}" readonly>
        </div>
        <div class="col-12">
          <label class="form-label text-muted">Alamat Sekolah</label>
          <textarea class="form-control" rows="2" readonly>{{ $detailTerbaru->alamat_sekolah }}</textarea>
        </div>
        <div class="col-md-4">
          <label class="form-label text-muted">Provinsi</label>
          <input type="text" class="form-control" value="{{ $detailTerbaru->province->name ?? '-' }}" readonly>
        </div>
        <div class="col-md-4">
          <label class="form-label text-muted">Kabupaten/Kota</label>
          <input type="text" class="form-control" value="{{ $detailTerbaru->regency->name ?? '-' }}" readonly>
        </div>
        <div class="col-md-4">
          <label class="form-label text-muted">Kecamatan</label>
          <input type="text" class="form-control" value="{{ $detailTerbaru->district->name ?? '-' }}" readonly>
        </div>
      </div>
    </div>
  </div>

  @if ($rombelTerakhir->count())
  <div class="card mb-4">
    <div class="card-header">
      <h6 class="mb-0">Rombel yang Akan Disalin ({{ $rombelTerakhir->count() }})</h6>
    </div>
    <div class="card-body">
      <ul class="mb-0">
        @foreach ($rombelTerakhir as $rombel)
        <li>{{ $rombel->nama_rombel }} — Tingkat {{ $rombel->tingkat }}</li>
        @endforeach
      </ul>
    </div>
  </div>
  @endif

  <form action="{{ route('periodic.copy') }}" method="POST">
    @csrf
    <input type="hidden" name="semester_asal" value="{{ $detailTerbaru->semester_id }}">

    <button type="submit" class="btn btn-primary">
      <i class="ri-file-copy-line me-1"></i> Salin Data Periodik ke Semester Ini
    </button>
  </form>
  @endif
</div>
@endsection