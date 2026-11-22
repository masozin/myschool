@extends('layouts/layoutMaster')

@section('title', 'Dokumen Publik Web Sekolah')

@section('content')
  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <div class="card mb-4">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">Tambah Dokumen Publik</h5>
      <small class="text-muted">{{ $sekolah->detailTerbaru->nama_sekolah ?? '' }} — dokumen tampil di landing page
        sekolah. Berkas PDF/JPG/PNG, maks 10 MB.</small>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('web-sekolah.kelola.dokumen.store') }}" enctype="multipart/form-data"
        class="row g-3">
        @csrf
        <div class="col-12 col-md-6">
          <label class="form-label" for="judul">Judul Dokumen *</label>
          <input type="text" id="judul" name="judul" class="form-control" maxlength="255" required
            value="{{ old('judul') }}" placeholder="mis. Sertifikat Akreditasi Tahun 2024">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label" for="kategori">Kategori *</label>
          <select id="kategori" name="kategori" class="form-select" required>
            @foreach (\App\Models\WebDokumen::KATEGORI as $kode => $label)
              <option value="{{ $kode }}" {{ old('kategori') === $kode ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label" for="tahun">Tahun</label>
          <input type="number" id="tahun" name="tahun" class="form-control" min="1990" max="2100"
            value="{{ old('tahun') }}" placeholder="mis. 2026">
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label" for="berkas">Berkas *</label>
          <input type="file" id="berkas" name="berkas" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label" for="keterangan">Keterangan (opsional)</label>
          <input type="text" id="keterangan" name="keterangan" class="form-control" maxlength="255"
            value="{{ old('keterangan') }}" placeholder="mis. Berlaku s.d. 2029">
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end">
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="is_tampil" name="is_tampil" value="1"
              {{ old('is_tampil', '1') ? 'checked' : '' }}>
            <label class="form-check-label" for="is_tampil">Tampilkan ke publik</label>
          </div>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary">Tambah Dokumen</button>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-4">
    @forelse ($dokumen as $d)
      <div class="col-12 col-lg-6">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex align-items-start gap-3 mb-3">
              <span class="badge {{ $d->isPdf() ? 'bg-label-danger' : 'bg-label-info' }} p-2">
                <i class="{{ $d->isPdf() ? 'ri-file-pdf-2-line' : 'ri-image-line' }} ri-24px"></i>
              </span>
              <div class="flex-grow-1">
                <div class="fw-semibold">{{ $d->judul }}</div>
                <small class="text-muted d-block">{{ $d->nama_berkas ?? basename($d->berkas) }}
                  @if ($d->ukuranTampil()) &middot; {{ $d->ukuranTampil() }} @endif
                </small>
                <span class="badge bg-label-primary mt-1">{{ $d->labelKategori() }}</span>
                @if ($d->is_tampil)
                  <span class="badge bg-label-success mt-1">Tampil ke publik</span>
                @else
                  <span class="badge bg-label-secondary mt-1">Disembunyikan</span>
                @endif
              </div>
              @if ($d->is_tampil)
                <a href="{{ route('web-sekolah.dokumen.lihat', $d) }}" target="_blank" rel="noopener"
                  class="btn btn-sm btn-outline-secondary">Lihat</a>
              @endif
            </div>

            <form method="POST" action="{{ route('web-sekolah.kelola.dokumen.update', $d) }}"
              enctype="multipart/form-data" class="row g-2">
              @csrf @method('PUT')
              <div class="col-12">
                <input type="text" name="judul" class="form-control form-control-sm" maxlength="255" required
                  value="{{ $d->judul }}" placeholder="Judul dokumen">
              </div>
              <div class="col-7">
                <select name="kategori" class="form-select form-select-sm">
                  @foreach (\App\Models\WebDokumen::KATEGORI as $kode => $label)
                    <option value="{{ $kode }}" {{ $d->kategori === $kode ? 'selected' : '' }}>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-5">
                <input type="number" name="tahun" class="form-control form-control-sm" min="1990" max="2100"
                  value="{{ $d->tahun }}" placeholder="Tahun">
              </div>
              <div class="col-12">
                <input type="text" name="keterangan" class="form-control form-control-sm" maxlength="255"
                  value="{{ $d->keterangan }}" placeholder="Keterangan (opsional)">
              </div>
              <div class="col-12">
                <input type="file" name="berkas" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png">
                <small class="text-muted">Isi hanya bila ingin mengganti berkas.</small>
              </div>
              <div class="col-12 d-flex align-items-center gap-3">
                <div class="form-check mb-0">
                  <input class="form-check-input" type="checkbox" name="is_tampil" value="1"
                    id="tampil-{{ $d->id }}" {{ $d->is_tampil ? 'checked' : '' }}>
                  <label class="form-check-label" for="tampil-{{ $d->id }}">Tampilkan ke publik</label>
                </div>
                <button type="submit" class="btn btn-sm btn-outline-primary flex-grow-1">Simpan Perubahan</button>
              </div>
            </form>

            <form method="POST" action="{{ route('web-sekolah.kelola.dokumen.destroy', $d) }}" class="mt-2"
              onsubmit="return confirm('Hapus dokumen ini beserta berkasnya?')">
              @csrf @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger w-100">Hapus Dokumen</button>
            </form>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12">
        <div class="card"><div class="card-body text-center text-muted py-4">
          Belum ada dokumen publik — tambahkan sertifikat akreditasi, KOSP, RKT, atau RKJM pertama lewat formulir di atas.
        </div></div>
      </div>
    @endforelse
  </div>
@endsection
