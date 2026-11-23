@extends('publik.web-sekolah.layout')

@section('title', $berita->judul . ' — ' . $namaSekolah)
@section('meta_description', $berita->ringkasan)
@section('nav-modifier', 'is-solid')

@section('content')
  <section class="ws-section" style="padding-top:128px">
    <div class="ws-container" style="max-width:880px">
      <nav class="ws-crumb" style="color:var(--muted);opacity:1">
        <a href="{{ route('web-sekolah.tampil', $web->slug) }}" style="color:var(--primary)">Beranda</a>
        <span aria-hidden="true">/</span>
        <a href="{{ route('web-sekolah.berita.index', $web->slug) }}" style="color:var(--primary)">Berita</a>
        <span aria-hidden="true">/</span>
        <span style="color:var(--text)">{{ \Illuminate\Support\Str::limit($berita->judul, 42) }}</span>
      </nav>

      <article class="ws-article">
        <span class="ws-date">{{ $berita->diterbitkan_pada?->translatedFormat('l, d F Y') }}</span>
        <h1>{{ $berita->judul }}</h1>
        <div class="ws-meta-artikel">
          <span>{{ $namaSekolah }}</span>
          @if ($berita->penulis)
            <span>&middot;</span><span>Oleh {{ $berita->penulis->name }}</span>
          @endif
        </div>
        @if ($berita->foto)
          <img class="ws-cover" src="{{ route('web-sekolah.berita.foto', $berita) }}" alt="{{ $berita->judul }}">
        @endif
        <div class="ws-isi ws-kaya">{!! \App\Support\TeksKaya::tampil($berita->isi) !!}</div>
      </article>

      <p style="margin-top:26px"><a class="ws-btn ws-btn-outline" href="{{ route('web-sekolah.berita.index', $web->slug) }}">&larr; Semua Berita</a></p>

      @if ($beritaLain->isNotEmpty())
        <div class="ws-sec-head" style="margin-top:56px;margin-bottom:26px">
          <span class="ws-eyebrow-sm">Lainnya</span>
          <h2 style="font-size:1.5rem">Berita Lainnya</h2>
        </div>
        <div class="ws-grid" style="grid-template-columns:repeat(auto-fit,minmax(230px,1fr))">
          @foreach ($beritaLain as $b)
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
              </div>
            </article>
          @endforeach
        </div>
      @endif
    </div>
  </section>
@endsection
