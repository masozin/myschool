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
      <small class="text-muted">{{ $sekolah->detailTerbaru->nama_sekolah ?? '' }} — bisa pilih banyak foto sekaligus, maks 10 MB per foto dan otomatis dikompresi saat tersimpan.</small>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('web-sekolah.kelola.galeri.store') }}" enctype="multipart/form-data" class="row g-3">
        @csrf
        <div class="col-12 col-md-6">
          <label class="form-label" for="foto">Foto *</label>
          <input type="file" id="foto" name="foto[]" class="form-control kompres-foto" data-maks="1600" accept=".png,.jpg,.jpeg,.webp" multiple required>
          <small class="text-muted">Foto dikompresi dulu di browser (lebih cepat &amp; hemat kuota), lalu diperiksa ulang di server.</small>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label" for="judul">Judul Bersama (opsional)</label>
          <input type="text" id="judul" name="judul" class="form-control" maxlength="255" placeholder="mis. Upacara Hari Guru 2026">
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label" for="kegiatan_id">Masuk Album Kegiatan</label>
          <select id="kegiatan_id" name="kegiatan_id" class="form-select">
            <option value="">— Tanpa album (Foto Lainnya) —</option>
            @foreach ($daftarKegiatan as $k)
              <option value="{{ $k->id }}">{{ $k->nama }}{{ $k->tanggal ? ' (' . $k->tanggal->format('d/m/Y') . ')' : '' }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label" for="kegiatan_baru">atau Buat Album Baru</label>
          <input type="text" id="kegiatan_baru" name="kegiatan_baru" class="form-control" maxlength="255" placeholder="mis. Lomba 17 Agustus 2026">
          <small class="text-muted">Bila diisi, pilihan album di kiri diabaikan. Tanggal &amp; keterangan album bisa dilengkapi di kartu Album Kegiatan di bawah.</small>
        </div>
        <div class="col-12 col-md-2 d-flex align-items-end">
          <button type="submit" class="btn btn-primary w-100">Unggah</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">Album Kegiatan</h5>
      <small class="text-muted">Foto galeri dikelompokkan per kegiatan. Di web publik, album tampil sebagai kartu dengan foto sampul; pengunjung mengkliknya untuk melihat semua foto kegiatan itu. Menghapus album tidak menghapus foto — foto kembali menjadi tanpa album.</small>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('web-sekolah.kelola.galeri.kegiatan.store') }}" class="row g-3 mb-2">
        @csrf
        <div class="col-12 col-md-5">
          <label class="form-label" for="album-nama">Nama Album Baru *</label>
          <input type="text" id="album-nama" name="nama" class="form-control" maxlength="255" required placeholder="mis. Upacara HUT RI 2026">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label" for="album-tanggal">Tanggal Kegiatan</label>
          <input type="date" id="album-tanggal" name="tanggal" class="form-control">
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label" for="album-keterangan">Keterangan (opsional)</label>
          <input type="text" id="album-keterangan" name="keterangan" class="form-control" placeholder="mis. Diikuti seluruh siswa & guru">
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-outline-primary">Buat Album</button>
        </div>
      </form>

      @forelse ($daftarKegiatan as $k)
        <div class="border rounded p-3 mb-2">
          <form method="POST" action="{{ route('web-sekolah.kelola.galeri.kegiatan.update', $k) }}" class="row g-2 align-items-end">
            @csrf @method('PUT')
            <div class="col-12 col-md-4">
              <label class="form-label small mb-1">Nama Album <span class="badge bg-label-info">{{ $k->galeri_count }} foto</span></label>
              <input type="text" name="nama" class="form-control form-control-sm" maxlength="255" required value="{{ $k->nama }}">
            </div>
            <div class="col-6 col-md-2">
              <label class="form-label small mb-1">Tanggal</label>
              <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ $k->tanggal?->format('Y-m-d') }}">
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label small mb-1">Keterangan</label>
              <input type="text" name="keterangan" class="form-control form-control-sm" value="{{ $k->keterangan }}" placeholder="Keterangan">
            </div>
            <div class="col-12 col-md-2">
              <button type="submit" class="btn btn-sm btn-outline-primary w-100">Simpan Album</button>
            </div>
          </form>
          <form method="POST" action="{{ route('web-sekolah.kelola.galeri.kegiatan.destroy', $k) }}" class="mt-2"
            onsubmit="return confirm('Hapus album ini? Foto-fotonya TIDAK ikut terhapus, hanya menjadi tanpa album.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger">Hapus Album</button>
          </form>
        </div>
      @empty
        <p class="text-muted mb-0">Belum ada album — buat lewat formulir di atas, atau langsung dari formulir unggah foto.</p>
      @endforelse
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
          Galeri masih kosong — unggah foto kegiatan pertama lewat formulir di atas.
        </div></div>
      </div>
    @endforelse
  </div>

  @include('_partials.kompres-foto-js')
@endsection
