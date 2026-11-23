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

  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
      <h4 class="mb-1">Galeri — Album Kegiatan</h4>
      <small class="text-muted">{{ $sekolah->detailTerbaru->nama_sekolah ?? '' }} — foto dikelompokkan per album kegiatan. Klik “Edit Album” untuk menambah &amp; mengelola foto di dalamnya.</small>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAlbumBaru">
      <i class="ri ri-add-line me-1"></i> Tambah Album Baru
    </button>
  </div>

  <div class="row g-4">
    @foreach ($daftarKegiatan as $k)
      <div class="col-12 col-md-6 col-xl-4">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex gap-2 mb-3">
              @forelse ($k->galeri->take(4) as $gf)
                <img src="{{ route('web-sekolah.galeri.foto', $gf) }}" alt="{{ $gf->judul ?? $k->nama }}"
                  style="width:64px;height:64px;object-fit:cover;border-radius:10px">
              @empty
                <div class="d-flex align-items-center justify-content-center text-muted"
                  style="width:64px;height:64px;border:1px dashed #cbd5e1;border-radius:10px">
                  <i class="ri ri-image-line ri-lg"></i>
                </div>
              @endforelse
              @if ($k->galeri_count > 4)
                <div class="d-flex align-items-center justify-content-center bg-label-secondary fw-semibold"
                  style="width:64px;height:64px;border-radius:10px">+{{ $k->galeri_count - 4 }}</div>
              @endif
            </div>
            <h5 class="card-title mb-1">{{ $k->nama }}</h5>
            <p class="text-muted small mb-2">
              @if ($k->tanggal)<i class="ri ri-calendar-line me-1"></i>{{ $k->tanggal->translatedFormat('d F Y') }} &middot; @endif
              <span class="badge bg-label-info">{{ $k->galeri_count }} foto</span>
              @if (!$k->is_tampil)<span class="badge bg-label-warning">Tidak tampil di web</span>@endif
            </p>
            @if ($k->keterangan)
              <p class="small mb-3">{{ \Illuminate\Support\Str::limit($k->keterangan, 110) }}</p>
            @endif
            <div class="d-flex gap-2">
              <a href="{{ route('web-sekolah.kelola.galeri.album.edit', $k) }}" class="btn btn-sm btn-primary flex-grow-1">
                <i class="ri ri-edit-line me-1"></i> Edit Album
              </a>
              <form method="POST" action="{{ route('web-sekolah.kelola.galeri.kegiatan.destroy', $k) }}"
                onsubmit="return confirm('Hapus album ini? Foto-fotonya TIDAK ikut terhapus, hanya menjadi tanpa album.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus album"><i class="ri ri-delete-bin-line"></i></button>
              </form>
            </div>
          </div>
        </div>
      </div>
    @endforeach

    {{-- Foto tanpa album --}}
    <div class="col-12 col-md-6 col-xl-4">
      <div class="card h-100" style="border-style:dashed">
        <div class="card-body">
          <div class="d-flex gap-2 mb-3">
            @forelse ($fotoLainnya->take(4) as $gf)
              <img src="{{ route('web-sekolah.galeri.foto', $gf) }}" alt="{{ $gf->judul ?? 'Foto lainnya' }}"
                style="width:64px;height:64px;object-fit:cover;border-radius:10px">
            @empty
              <div class="d-flex align-items-center justify-content-center text-muted"
                style="width:64px;height:64px;border:1px dashed #cbd5e1;border-radius:10px">
                <i class="ri ri-image-line ri-lg"></i>
              </div>
            @endforelse
            @if ($fotoLainnya->count() > 4)
              <div class="d-flex align-items-center justify-content-center bg-label-secondary fw-semibold"
                style="width:64px;height:64px;border-radius:10px">+{{ $fotoLainnya->count() - 4 }}</div>
            @endif
          </div>
          <h5 class="card-title mb-1">Foto Lainnya</h5>
          <p class="text-muted small mb-3">Foto yang belum masuk album mana pun. <span class="badge bg-label-secondary">{{ $fotoLainnya->count() }} foto</span></p>
          <a href="{{ route('web-sekolah.kelola.galeri.tanpa-album') }}" class="btn btn-sm btn-outline-primary w-100">
            <i class="ri ri-edit-line me-1"></i> Kelola Foto Lainnya
          </a>
        </div>
      </div>
    </div>
  </div>

  @if ($daftarKegiatan->isEmpty())
    <div class="card mt-4"><div class="card-body text-center text-muted py-4">
      Belum ada album kegiatan — klik “Tambah Album Baru” untuk membuat album pertama dan langsung mengisinya dengan foto.
    </div></div>
  @endif

  {{-- Modal tambah album baru (sekalian mengisi foto) --}}
  <div class="modal fade" id="modalAlbumBaru" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <form method="POST" action="{{ route('web-sekolah.kelola.galeri.kegiatan.store') }}" enctype="multipart/form-data" class="form-unggah-progres">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">Tambah Album Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label" for="album-nama">Nama Album / Kegiatan *</label>
                <input type="text" id="album-nama" name="nama" class="form-control" maxlength="255" required placeholder="mis. Upacara HUT RI 2026">
              </div>
              <div class="col-12 col-md-5">
                <label class="form-label" for="album-tanggal">Tanggal Kegiatan</label>
                <input type="date" id="album-tanggal" name="tanggal" class="form-control">
              </div>
              <div class="col-12 col-md-7">
                <label class="form-label" for="album-keterangan">Keterangan (opsional)</label>
                <input type="text" id="album-keterangan" name="keterangan" class="form-control" placeholder="mis. Diikuti seluruh siswa & guru">
              </div>
              <div class="col-12">
                <label class="form-label" for="album-is-tampil">Tampil di Halaman Web</label>
                <select id="album-is-tampil" name="is_tampil" class="form-select">
                  <option value="1" selected>Tampilkan — album &amp; fotonya terlihat pengunjung</option>
                  <option value="0">Sembunyikan — hanya terlihat di halaman kelola</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label" for="album-foto">Foto untuk Album Ini (boleh banyak, bisa juga diisi nanti)</label>
                <input type="file" id="album-foto" name="foto[]" class="form-control kompres-foto" data-maks="1600"
                  data-pratinjau="#pratinjauAlbumBaru" accept=".png,.jpg,.jpeg,.webp" multiple>
                <small class="text-muted">Pratinjau muncul di bawah sebelum diunggah. Foto dikompresi dulu di browser, lalu diperiksa ulang di server. Maksimal 20 foto sekali unggah.</small>
                <div id="pratinjauAlbumBaru" class="d-flex flex-wrap gap-2 mt-2"></div>
              </div>
              <div class="col-12">
                <label class="form-label" for="album-judul">Judul Bersama Foto (opsional)</label>
                <input type="text" id="album-judul" name="judul" class="form-control" maxlength="255" placeholder="Judul yang sama untuk semua foto di atas">
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Buat Album</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  @include('_partials.kompres-foto-js')
  @include('_partials.pratinjau-foto-js')
  @include('_partials.unggah-progres-js')
@endsection
