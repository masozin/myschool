@extends('layouts/layoutMaster')

@section('title', $berita->exists ? 'Ubah Berita' : 'Tulis Berita')

@section('content')
  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <div class="card">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">{{ $berita->exists ? 'Ubah Berita' : 'Tulis Berita' }}</h5>
      <small class="text-muted">{{ $sekolah->detailTerbaru->nama_sekolah ?? '' }}</small>
    </div>
    <div class="card-body">
      <form method="POST"
        action="{{ $berita->exists ? route('web-sekolah.kelola.berita.update', $berita) : route('web-sekolah.kelola.berita.store') }}"
        enctype="multipart/form-data" class="row g-4">
        @csrf
        @if ($berita->exists) @method('PUT') @endif

        <div class="col-12">
          <label class="form-label" for="judul">Judul *</label>
          <input type="text" id="judul" name="judul" class="form-control" maxlength="255" required
            value="{{ old('judul', $berita->judul) }}">
        </div>

        <div class="col-12">
          <label class="form-label" for="isi">Isi Berita *</label>
          <textarea id="isi" name="isi" class="form-control" rows="12" required>{{ old('isi', $berita->isi) }}</textarea>
          <small class="text-muted">Teks biasa — baris baru akan tampil apa adanya di web publik.</small>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label" for="foto">Foto Berita</label>
          <input type="file" id="foto" name="foto" class="form-control kompres-foto" data-maks="1600" accept=".png,.jpg,.jpeg,.webp">
          <small class="text-muted">Maks 10 MB, otomatis dikompresi saat tersimpan. Kosongkan bila tidak mengganti foto.</small>
          @if ($berita->foto && $berita->status === 'terbit')
            <div class="mt-2"><img src="{{ route('web-sekolah.berita.foto', $berita) }}" alt="Foto berita"
              style="max-width:320px;width:100%;border-radius:10px"></div>
          @elseif ($berita->foto)
            <div class="mt-2"><small class="text-muted">Foto sudah tersimpan — tampil di web setelah berita diterbitkan.</small></div>
          @endif
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label" for="status">Status *</label>
          <select id="status" name="status" class="form-select">
            <option value="draf" {{ old('status', $berita->status) === 'draf' ? 'selected' : '' }}>Draf — belum tampil</option>
            <option value="terbit" {{ old('status', $berita->status) === 'terbit' ? 'selected' : '' }}>Terbit — tampil di web publik</option>
          </select>
        </div>

        <div class="col-12">
          <button type="submit" class="btn btn-primary">Simpan Berita</button>
          <a href="{{ route('web-sekolah.kelola.berita.index') }}" class="btn btn-outline-secondary">Kembali</a>
        </div>
      </form>
    </div>
  </div>

  @include('_partials.kompres-foto-js')
@endsection
