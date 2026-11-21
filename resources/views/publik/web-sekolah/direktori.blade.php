@extends('publik.web-sekolah.layout')

@section('title', 'Web Sekolah — Pilih Sekolah')
@section('meta_description', 'Direktori website resmi sekolah-sekolah pengguna myschool')

@section('content')
  <header class="ws-pagehead">
    <div class="ws-hero-glow"></div>
    <div class="ws-container">
      <div class="ws-pagehead-in">
        <h1>Web Sekolah</h1>
        <p>Pilih sekolah untuk membuka website resminya.</p>
      </div>
    </div>
  </header>

  <section class="ws-section" style="padding-top:64px">
    <div class="ws-container">
      <div class="ws-grid">
        @foreach ($webs as $w)
          @php $logoW = $w->sekolah->asets->where('tipe', \App\Models\SekolahLogo::TIPE_LOGO_SEKOLAH)->first(); @endphp
          <article class="ws-card ws-dir-card" data-reveal>
            @if ($logoW)
              <img src="{{ route('web-sekolah.logo', $w->slug) }}" alt="Logo {{ $w->sekolah->detailTerbaru->nama_sekolah ?? 'sekolah' }}" loading="lazy">
            @endif
            <div>
              <h3>{{ $w->sekolah->detailTerbaru->nama_sekolah ?? 'Sekolah #' . $w->sekolah_id }}</h3>
              <p>NPSN {{ $w->sekolah->npsn }} &middot; {{ $w->sekolah->jenjang }}
                @if ($w->tagline) <br>{{ $w->tagline }} @endif
              </p>
            </div>
            <a class="ws-readmore" href="{{ route('web-sekolah.tampil', $w->slug) }}">Kunjungi website <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
          </article>
        @endforeach
      </div>
    </div>
  </section>
@endsection
