@extends('layouts/layoutMaster')

@section('title', 'Pengaturan Web Sekolah')

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
    <div class="card-body py-4 d-flex align-items-center gap-4 flex-wrap">
      <div class="avatar avatar-xl flex-shrink-0">
        <span class="avatar-initial rounded-circle bg-label-primary"><i class="icon-base ri ri-global-line ri-28px"></i></span>
      </div>
      <div class="flex-grow-1">
        <h5 class="mb-1">Web Sekolah</h5>
        <p class="mb-0 text-muted">{{ $sekolah->detailTerbaru->nama_sekolah ?? 'Sekolah #' . $sekolah->id }}
          &middot; NPSN {{ $sekolah->npsn }}</p>
      </div>
      @if ($web)
        <div class="text-end">
          <div class="text-muted small">Alamat web publik</div>
          <a href="{{ url('/sekolah/' . $web->slug) }}" target="_blank" rel="noopener">{{ url('/sekolah/' . $web->slug) }}</a>
          <div><span class="badge {{ $web->is_aktif ? 'bg-label-success' : 'bg-label-secondary' }}">{{ $web->is_aktif ? 'Tampil ke publik' : 'Disembunyikan' }}</span></div>
        </div>
      @endif
    </div>
  </div>

  <div class="card">
    <div class="card-header border-bottom"><h5 class="card-title mb-0">Pengaturan</h5>
      <small class="text-muted">Nama, alamat, kontak, dan kepala sekolah diambil dari Profil Sekolah — di sini hanya isi khas web.</small>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('web-sekolah.kelola.pengaturan.simpan') }}" enctype="multipart/form-data" class="row g-4">
        @csrf
        @method('PUT')

        <div class="col-12 col-md-6">
          <label class="form-label" for="slug">Slug (alamat web) *</label>
          <div class="input-group">
            <span class="input-group-text">{{ url('/sekolah') }}/</span>
            <input type="text" id="slug" name="slug" class="form-control"
              value="{{ old('slug', $slugSaran) }}"
              placeholder="sdn-01-totorejo" required>
          </div>
          <small class="text-muted">Huruf kecil, angka, dan tanda hubung saja. Berubah = alamat lama tidak berlaku lagi.</small>
        </div>

        <div class="col-12 col-md-6 d-flex align-items-end">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_aktif" name="is_aktif" value="1"
              {{ old('is_aktif', $web->is_aktif ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_aktif">Tampilkan web ke publik</label>
          </div>
        </div>

        <div class="col-12">
          <label class="form-label" for="tagline">Tagline / Motto</label>
          <input type="text" id="tagline" name="tagline" class="form-control" maxlength="255"
            value="{{ old('tagline', $web->tagline ?? '') }}" placeholder="mis. Unggul dalam Prestasi, Santun dalam Perilaku">
        </div>

        <div class="col-6 col-md-3">
          <label class="form-label" for="akreditasi">Akreditasi</label>
          <input type="text" id="akreditasi" name="akreditasi" class="form-control" maxlength="10"
            value="{{ old('akreditasi', $web->akreditasi ?? '') }}" placeholder="A">
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label" for="tahun_berdiri">Tahun Berdiri</label>
          <input type="number" id="tahun_berdiri" name="tahun_berdiri" class="form-control" min="1800" max="{{ date('Y') + 1 }}"
            value="{{ old('tahun_berdiri', $web->tahun_berdiri ?? '') }}" placeholder="1985">
        </div>

        <div class="col-12">
          <label class="form-label" for="sambutan">Sambutan Kepala Sekolah</label>
          <textarea id="sambutan" name="sambutan" class="form-control" rows="6">{{ old('sambutan', $web->sambutan ?? '') }}</textarea>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label" for="visi">Visi</label>
          <textarea id="visi" name="visi" class="form-control" rows="4">{{ old('visi', $web->visi ?? '') }}</textarea>
        </div>
        <div class="col-12 col-md-6">
          <label class="form-label" for="misi">Misi</label>
          <textarea id="misi" name="misi" class="form-control" rows="4"
            placeholder="Satu misi per baris">{{ old('misi', $web->misi ?? '') }}</textarea>
        </div>

        <div class="col-12">
          <label class="form-label" for="foto_hero">Foto Utama (Hero)</label>
          <input type="file" id="foto_hero" name="foto_hero" class="form-control" accept=".png,.jpg,.jpeg,.webp">
          <small class="text-muted">Maks 10 MB, otomatis dikompresi saat tersimpan. Foto gedung / kegiatan sekolah, orientasi melebar lebih bagus.</small>
          @if ($web?->foto_hero && $web->is_aktif)
            <div class="mt-3">
              <img src="{{ route('web-sekolah.foto-hero', $web->slug) }}" alt="Foto hero saat ini"
                style="max-width:420px;width:100%;border-radius:10px">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="hapus_foto_hero" name="hapus_foto_hero" value="1">
                <label class="form-check-label" for="hapus_foto_hero">Hapus foto utama ini</label>
              </div>
            </div>
          @endif
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
          @if ($web)
            <a class="btn btn-outline-secondary" href="{{ url('/sekolah/' . $web->slug) }}" target="_blank" rel="noopener">Lihat Web</a>
          @endif
        </div>
      </form>
    </div>
  </div>
@endsection
