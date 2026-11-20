@extends('publik.web-sekolah.layout')

@section('title', $berita->judul . ' — ' . $namaSekolah)
@section('meta_description', $berita->ringkasan)

@section('content')
  <section class="ws-section">
    <div class="ws-container" style="max-width:860px">
      <p style="margin-bottom:14px"><a href="{{ route('web-sekolah.berita.index', $web->slug) }}">← Semua berita</a></p>
      <article class="ws-article">
        <span class="ws-date">{{ $berita->diterbitkan_pada?->translatedFormat('d F Y') }}</span>
        <h2 style="margin:8px 0 18px">{{ $berita->judul }}</h2>
        @if ($berita->foto)
          <img class="ws-cover" src="{{ route('web-sekolah.berita.foto', $berita) }}" alt="{{ $berita->judul }}">
        @endif
        <div class="ws-isi">{{ $berita->isi }}</div>
      </article>

      @if ($beritaLain->isNotEmpty())
        <h3 style="margin:30px 0 14px;color:var(--ws-dark)">Berita Lainnya</h3>
        <div class="ws-grid">
          @foreach ($beritaLain as $b)
            <article class="ws-card">
              @if ($b->foto)
                <img src="{{ route('web-sekolah.berita.foto', $b) }}" alt="{{ $b->judul }}">
              @endif
              <div class="ws-card-body">
                <span class="ws-date">{{ $b->diterbitkan_pada?->translatedFormat('d F Y') }}</span>
                <h3><a href="{{ route('web-sekolah.berita.tampil', [$web->slug, $b->slug]) }}" style="color:inherit">{{ $b->judul }}</a></h3>
                <p>{{ $b->ringkasan }}</p>
              </div>
            </article>
          @endforeach
        </div>
      @endif
    </div>
  </section>
@endsection
