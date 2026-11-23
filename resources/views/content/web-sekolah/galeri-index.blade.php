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
        <div class="card h-100 shadow-sm overflow-hidden">
          {{-- Kolase sampul: filmstrip hingga 4 foto pertama --}}
          <div class="d-flex position-relative" style="gap:2px;background:#f1f5f9">
            @forelse ($k->galeri->take(4) as $gf)
              <img src="{{ route('web-sekolah.galeri.foto', $gf) }}" alt="{{ $gf->judul ?? $k->nama }}"
                style="flex:1 1 0;min-width:0;height:120px;object-fit:cover">
            @empty
              <div class="d-flex flex-column align-items-center justify-content-center text-muted w-100" style="height:120px">
                <i class="ri ri-image-line" style="font-size:2rem"></i>
                <small>Belum ada foto</small>
              </div>
            @endforelse
            @if ($k->galeri_count > 4)
              <span class="badge bg-dark position-absolute" style="right:.5rem;bottom:.5rem;opacity:.85">+{{ $k->galeri_count - 4 }} foto</span>
            @endif
          </div>
          <div class="card-body d-flex flex-column">
            <div class="d-flex align-items-start justify-content-between gap-2">
              <h5 class="card-title mb-1">{{ $k->nama }}</h5>
              <span class="badge bg-label-info flex-shrink-0">{{ $k->galeri_count }} foto</span>
            </div>
            <p class="text-muted small mb-2">
              @if ($k->tanggal)<i class="ri ri-calendar-line me-1"></i>{{ $k->tanggal->translatedFormat('d F Y') }}@endif
              <span class="badge bg-label-warning lencana-tidak-tampil" data-album="{{ $k->id }}" @if ($k->is_tampil) style="display:none" @endif>Tidak tampil di web</span>
            </p>
            @if ($k->keterangan)
              <p class="small text-muted mb-0 flex-grow-1">{{ \Illuminate\Support\Str::limit($k->keterangan, 110) }}</p>
            @else
              <p class="flex-grow-1 mb-0"></p>
            @endif
            <div class="border-top mt-3 pt-3 d-flex align-items-center justify-content-between gap-2 flex-wrap">
              <div class="form-check form-switch mb-0">
                <input class="form-check-input saklar-tampil" type="checkbox" role="switch"
                  id="tampil-{{ $k->id }}" data-album="{{ $k->id }}"
                  data-url="{{ route('web-sekolah.kelola.galeri.kegiatan.tampil', $k) }}"
                  @checked($k->is_tampil) style="width:2.5em;height:1.35em;cursor:pointer">
                <label class="form-check-label small" for="tampil-{{ $k->id }}">Tampil di web</label>
              </div>
              <div class="d-flex gap-2">
                <a href="{{ route('web-sekolah.kelola.galeri.album.edit', $k) }}" class="btn btn-sm btn-primary">
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
      </div>
    @endforeach

    {{-- Foto tanpa album --}}
    <div class="col-12 col-md-6 col-xl-4">
      <div class="card h-100 shadow-sm overflow-hidden" style="border-style:dashed">
        <div class="d-flex position-relative" style="gap:2px;background:#f8fafc">
          @forelse ($fotoLainnya->take(4) as $gf)
            <img src="{{ route('web-sekolah.galeri.foto', $gf) }}" alt="{{ $gf->judul ?? 'Foto lainnya' }}"
              style="flex:1 1 0;min-width:0;height:120px;object-fit:cover">
          @empty
            <div class="d-flex flex-column align-items-center justify-content-center text-muted w-100" style="height:120px">
              <i class="ri ri-image-line" style="font-size:2rem"></i>
              <small>Belum ada foto lepas</small>
            </div>
          @endforelse
          @if ($fotoLainnya->count() > 4)
            <span class="badge bg-dark position-absolute" style="right:.5rem;bottom:.5rem;opacity:.85">+{{ $fotoLainnya->count() - 4 }} foto</span>
          @endif
        </div>
        <div class="card-body d-flex flex-column">
          <div class="d-flex align-items-start justify-content-between gap-2">
            <h5 class="card-title mb-1">Foto Lainnya</h5>
            <span class="badge bg-label-secondary flex-shrink-0">{{ $fotoLainnya->count() }} foto</span>
          </div>
          <p class="small text-muted flex-grow-1">Foto yang belum masuk album mana pun — tampil sebagai kelompok tersendiri di web publik.</p>
          <div class="border-top mt-3 pt-3">
            <a href="{{ route('web-sekolah.kelola.galeri.tanpa-album') }}" class="btn btn-sm btn-outline-primary w-100">
              <i class="ri ri-edit-line me-1"></i> Kelola Foto Lainnya
            </a>
          </div>
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
                <label class="form-label d-block">Tampil di Halaman Web</label>
                <div class="form-check form-switch">
                  <input type="hidden" name="is_tampil" value="0">
                  <input class="form-check-input" type="checkbox" role="switch" id="album-is-tampil" name="is_tampil" value="1" checked style="width:2.6em;height:1.4em;cursor:pointer">
                  <label class="form-check-label ms-1" for="album-is-tampil">Terlihat oleh pengunjung web</label>
                </div>
                <small class="text-muted">Bila saklar mati, album &amp; seluruh fotonya disembunyikan dari web publik — tetap terlihat di halaman kelola.</small>
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
  <script>
    // Saklar "Tampil di web" pada kartu album: simpan seketika via AJAX;
    // bila gagal, saklar dikembalikan ke posisi semula.
    (function() {
      var token = '{{ csrf_token() }}';
      document.querySelectorAll('.saklar-tampil').forEach(function(sw) {
        sw.addEventListener('change', function() {
          var tampil = sw.checked;
          sw.disabled = true;
          fetch(sw.dataset.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ is_tampil: tampil ? 1 : 0 })
          }).then(function(r) {
            if (!r.ok) throw new Error('gagal');
            document.querySelectorAll('.lencana-tidak-tampil').forEach(function(b) {
              if (b.dataset.album === sw.dataset.album) b.style.display = tampil ? 'none' : '';
            });
          }).catch(function() {
            sw.checked = !tampil;
            alert('Gagal mengubah tampilan album. Periksa koneksi lalu coba lagi.');
          }).finally(function() { sw.disabled = false; });
        });
      });
    })();
  </script>
@endsection
