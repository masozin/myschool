@extends('publik.web-sekolah.layout')

@section('title', 'Web Sekolah — Pilih Sekolah')

@section('content')
  <section class="ws-section">
    <div class="ws-container">
      <h2>Web Sekolah</h2>
      <p class="ws-sub">Pilih sekolah untuk membuka website resminya.</p>
      <div class="ws-grid">
        @foreach ($webs as $w)
          <div class="ws-card">
            <div class="ws-card-body">
              <h3>{{ $w->sekolah->detailTerbaru->nama_sekolah ?? 'Sekolah #' . $w->sekolah_id }}</h3>
              <p>NPSN {{ $w->sekolah->npsn }} &middot; {{ $w->sekolah->jenjang }}
                @if ($w->tagline) <br>{{ $w->tagline }} @endif
              </p>
              <a href="{{ route('web-sekolah.tampil', $w->slug) }}">Kunjungi website →</a>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>
@endsection
