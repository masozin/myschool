@extends('publik.web-sekolah.layout')

@section('title', 'Berita — ' . $namaSekolah)
@section('meta_description', 'Berita dan pengumuman terbaru dari ' . $namaSekolah)

@section('content')
  <header class="ws-pagehead">
    <div class="ws-hero-glow"></div>
    <div class="ws-container">
      <div class="ws-pagehead-in">
        <nav class="ws-crumb">
          <a href="{{ route('web-sekolah.tampil', $web->slug) }}">Beranda</a>
          <span aria-hidden="true">/</span>
          <span>Berita</span>
        </nav>
        <h1>Berita &amp; Pengumuman</h1>
        <p>Semua kabar terbit dari {{ $namaSekolah }} — pengumuman resmi, agenda, dan kegiatan sekolah.</p>
      </div>
    </div>
  </header>

  <section class="ws-section" style="padding-top:64px">
    <div class="ws-container">
      @if ($berita->isEmpty())
        <div class="ws-empty">Belum ada berita yang diterbitkan.</div>
      @else
        <div class="ws-grid">
          @foreach ($berita as $b)
            <article class="ws-card" data-reveal>
              <div class="ws-card-media">
                @if ($b->foto)
                  <img src="{{ route('web-sekolah.berita.foto', $b) }}" alt="{{ $b->judul }}" loading="lazy">
                @else
                  <span class="ws-nofoto"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-4.6-4.6a2 2 0 0 0-2.8 0L6 18"/></svg></span>
                @endif
                <span class="ws-datechip">{{ $b->diterbitkan_pada?->translatedFormat('d M Y') }}</span>
              </div>
              <div class="ws-card-body">
                <h3><a href="{{ route('web-sekolah.berita.tampil', [$web->slug, $b->slug]) }}">{{ $b->judul }}</a></h3>
                <p>{{ $b->ringkasan }}</p>
                <a class="ws-readmore" href="{{ route('web-sekolah.berita.tampil', [$web->slug, $b->slug]) }}">Baca selengkapnya <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
              </div>
            </article>
          @endforeach
        </div>

        @if ($berita->hasPages())
          <nav class="ws-pagination">
            @if ($berita->onFirstPage())
              <span class="mati">&lsaquo; Sebelumnya</span>
            @else
              <a href="{{ $berita->previousPageUrl() }}">&lsaquo; Sebelumnya</a>
            @endif
            <span class="active">Halaman {{ $berita->currentPage() }} dari {{ $berita->lastPage() }}</span>
            @if ($berita->hasMorePages())
              <a href="{{ $berita->nextPageUrl() }}">Berikutnya &rsaquo;</a>
            @else
              <span class="mati">Berikutnya &rsaquo;</span>
            @endif
          </nav>
        @endif
      @endif
    </div>
  </section>
@endsection
