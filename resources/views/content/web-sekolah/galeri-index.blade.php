@extends('layouts/layoutMaster')

@section('title', 'Galeri Web Sekolah')

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
      <h5 class="card-title mb-0">Unggah Foto Galeri</h5>
      <small class="text-muted">{{ $sekolah->detailTerbaru->nama_sekolah ?? '' }} — bisa pilih banyak foto sekaligus, maks 4 MB per foto.</small>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('web-sekolah.kelola.galeri.store') }}" enctype="multipart/form-data" class="row g-3">
        @csrf
        <div class="col-12 col-md-6">
          <label class="form-label" for="foto">Foto *</label>
          <input type="file" id="foto" name="foto[]" class="form-control" accept=".png,.jpg,.jpeg,.webp" multiple required>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label" for="judul">Judul Bersama (opsional)</label>
          <input type="text" id="judul" name="judul" class="form-control" maxlength="255" placeholder="mis. Upacara Hari Guru 2026">
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">Unggah</button>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-4">
    @forelse ($galeri as $g)
      <div class="col-12 col-sm-6 col-lg-4">
        <div class="card h-100">
          <img src="{{ route('web-sekolah.galeri.foto', $g) }}" alt="{{ $g->judul ?? 'Foto galeri' }}"
            style="height:190px;object-fit:cover" class="card-img-top">
          <div class="card-body">
            <form method="POST" action="{{ route('web-sekolah.kelola.galeri.update', $g) }}" class="row g-2">
              @csrf @method('PUT')
              <div class="col-12">
                <input type="text" name="judul" class="form-control form-control-sm" maxlength="255"
                  value="{{ $g->judul }}" placeholder="Judul foto">
              </div>
              <div class="col-8">
                <input type="text" name="keterangan" class="form-control form-control-sm" maxlength="255"
                  value="{{ $g->keterangan }}" placeholder="Keterangan">
              </div>
              <div class="col-4">
                <input type="number" name="urutan" class="form-control form-control-sm" min="0"
                  value="{{ $g->urutan }}" title="Urutan tampil">
              </div>
              <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-primary flex-grow-1">Simpan</button>
              </div>
            </form>
            <form method="POST" action="{{ route('web-sekolah.kelola.galeri.destroy', $g) }}" class="mt-2"
              onsubmit="return confirm('Hapus foto ini dari galeri?')">
              @csrf @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger w-100">Hapus Foto</button>
            </form>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12">
        <div class="card"><div class="card-body text-center text-muted py-4">
          Galeri masih kosong — unggah foto kegiatan pertama lewat formulir di atas.
        </div></div>
      </div>
    @endforelse
  </div>
@endsection
