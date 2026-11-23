@extends('publik.web-sekolah.layout')

@section('title', $kegiatan->nama . ' — Galeri ' . $namaSekolah)
@section('meta_description', $kegiatan->keterangan ?? ('Album foto kegiatan ' . $kegiatan->nama . ' di ' . $namaSekolah))
@section('nav-modifier', 'is-solid')

@section('content')
  <section class="ws-section" style="padding-top:128px">
    <div class="ws-container">
      <nav class="ws-crumb" style="color:var(--muted);opacity:1">
        <a href="{{ route('web-sekolah.tampil', $web->slug) }}" style="color:var(--primary)">Beranda</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('web-sekolah.tampil', $web->slug) }}#galeri" style="color:var(--primary)">Galeri</a>
        <span aria-hidden="true">/</span>
        <span style="color:var(--text)">{{ \Illuminate\Support\Str::limit($kegiatan->nama, 42) }}</span>
      </nav>

      <div class="ws-sec-head" style="margin-top:22px">
        <span class="ws-eyebrow-sm">Album Kegiatan</span>
        <h2>{{ $kegiatan->nama }}</h2>
        <p>
          @if ($kegiatan->tanggal){{ $kegiatan->tanggal->translatedFormat('l, d F Y') }} &middot; @endif{{ $fotoAlbum->count() }} foto
          @if ($kegiatan->keterangan)<br>{{ $kegiatan->keterangan }}@endif
        </p>
      </div>

      @if ($fotoAlbum->isNotEmpty())
        <div class="ws-galeri">
          @foreach ($fotoAlbum as $g)
            <figure>
              <img src="{{ route('web-sekolah.galeri.foto', $g) }}" alt="{{ $g->judul ?? $kegiatan->nama }}" loading="lazy">
              @if ($g->judul)
                <figcaption>{{ $g->judul }}@if ($g->keterangan) — {{ $g->keterangan }} @endif</figcaption>
              @endif
            </figure>
          @endforeach
        </div>
      @else
        <p style="color:var(--muted)">Album ini belum berisi foto.</p>
      @endif

      <p style="margin-top:30px"><a class="ws-btn ws-btn-outline" href="{{ route('web-sekolah.tampil', $web->slug) }}#galeri">&larr; Kembali ke Galeri</a></p>
    </div>
  </section>
@endsection
