@extends('layouts/layoutMaster')

@section('title', $kegiatan ? 'Edit Album — ' . $kegiatan->nama : 'Foto Lainnya — Galeri')

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

  <p class="mb-3">
    <a href="{{ route('web-sekolah.kelola.galeri.index') }}" class="text-decoration-none">
      <i class="ri ri-arrow-left-line me-1"></i> Kembali ke Daftar Album
    </a>
  </p>

  @if ($kegiatan)
    <div class="card mb-4">
      <div class="card-header border-bottom">
        <h5 class="card-title mb-0">Rincian Album</h5>
        <small class="text-muted">{{ $foto->count() }} foto di album ini.</small>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('web-sekolah.kelola.galeri.kegiatan.update', $kegiatan) }}" class="row g-3">
          @csrf @method('PUT')
          <div class="col-12 col-md-5">
            <label class="form-label" for="nama">Nama Album / Kegiatan *</label>
            <input type="text" id="nama" name="nama" class="form-control" maxlength="255" required value="{{ $kegiatan->nama }}">
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label" for="tanggal">Tanggal Kegiatan</label>
            <input type="date" id="tanggal" name="tanggal" class="form-control" value="{{ $kegiatan->tanggal?->format('Y-m-d') }}">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label" for="keterangan">Keterangan (opsional)</label>
            <input type="text" id="keterangan" name="keterangan" class="form-control" value="{{ $kegiatan->keterangan }}" placeholder="mis. Diikuti seluruh siswa & guru">
          </div>
          <div class="col-12 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">Simpan Rincian Album</button>
          </div>
        </form>
        <form method="POST" action="{{ route('web-sekolah.kelola.galeri.kegiatan.destroy', $kegiatan) }}" class="mt-3"
          onsubmit="return confirm('Hapus album ini? Foto-fotonya TIDAK ikut terhapus, hanya menjadi tanpa album.')">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-outline-danger btn-sm"><i class="ri ri-delete-bin-line me-1"></i> Hapus Album Ini</button>
        </form>
      </div>
    </div>
  @else
    <div class="alert alert-secondary">
      <strong>Foto Lainnya</strong> — foto-foto yang belum masuk album mana pun. Dari sini foto bisa diunggah tanpa album, atau dipindahkan ke album lewat pilihan di kartu masing-masing foto.
    </div>
  @endif

  <div class="card mb-4">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">Tambah Foto{{ $kegiatan ? ' ke Album Ini' : '' }}</h5>
      <small class="text-muted">Pilih foto dari file manager — pratinjaunya muncul dulu di bawah, periksa, baru klik Unggah. Foto dikompresi di browser lalu diperiksa ulang di server (maks 10 MB per foto).</small>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('web-sekolah.kelola.galeri.store') }}" enctype="multipart/form-data" class="row g-3">
        @csrf
        @if ($kegiatan)
          <input type="hidden" name="kegiatan_id" value="{{ $kegiatan->id }}">
        @endif
        <div class="col-12 col-md-6">
          <label class="form-label" for="foto">Foto *</label>
          <input type="file" id="foto" name="foto[]" class="form-control kompres-foto" data-maks="1600"
            data-pratinjau="#pratinjauFoto" accept=".png,.jpg,.jpeg,.webp" multiple required>
          <div id="pratinjauFoto" class="d-flex flex-wrap gap-2 mt-2"></div>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label" for="judul">Judul Bersama (opsional)</label>
          <input type="text" id="judul" name="judul" class="form-control" maxlength="255" placeholder="Judul yang sama untuk semua foto">
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">Unggah</button>
        </div>
      </form>
    </div>
  </div>

  <div class="row g-4">
    @forelse ($foto as $g)
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
              <div class="col-12">
                <select name="kegiatan_id" class="form-select form-select-sm" title="Album kegiatan foto ini">
                  <option value="">— Tanpa album —</option>
                  @foreach ($daftarKegiatan as $k)
                    <option value="{{ $k->id }}" @selected($g->kegiatan_id === $k->id)>{{ $k->nama }}</option>
                  @endforeach
                </select>
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
          {{ $kegiatan ? 'Album ini masih kosong — tambahkan foto lewat formulir di atas.' : 'Tidak ada foto tanpa album saat ini.' }}
        </div></div>
      </div>
    @endforelse
  </div>

  @include('_partials.kompres-foto-js')
  @include('_partials.pratinjau-foto-js')
@endsection
