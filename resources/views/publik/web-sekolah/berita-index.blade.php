@extends('publik.web-sekolah.layout')

@section('title', 'Berita — ' . $namaSekolah)

@section('content')
  <section class="ws-section">
    <div class="ws-container">
      <h2>Berita &amp; Pengumuman</h2>
      <p class="ws-sub">Semua kabar terbit dari {{ $namaSekolah }}.</p>

      @if ($berita->isEmpty())
        <div class="ws-empty">Belum ada berita yang diterbitkan.</div>
      @else
        <div class="ws-grid">
          @foreach ($berita as $b)
            <article class="ws-card">
              @if ($b->foto)
                <img src="{{ route('web-sekolah.berita.foto', $b) }}" alt="{{ $b->judul }}">
              @endif
              <div class="ws-card-body">
                <span class="ws-date">{{ $b->diterbitkan_pada?->translatedFormat('d F Y') }}</span>
                <h3><a href="{{ route('web-sekolah.berita.tampil', [$web->slug, $b->slug]) }}" style="color:inherit">{{ $b->judul }}</a></h3>
                <p>{{ $b->ringkasan }}</p>
                <a href="{{ route('web-sekolah.berita.tampil', [$web->slug, $b->slug]) }}">Baca selengkapnya →</a>
              </div>
            </article>
          @endforeach
        </div>

        @if ($berita->hasPages())
          <nav class="ws-pagination">
            @if ($berita->onFirstPage())
              <span>‹ Sebelumnya</span>
            @else
              <a href="{{ $berita->previousPageUrl() }}">‹ Sebelumnya</a>
            @endif
            <span class="active">Halaman {{ $berita->currentPage() }} dari {{ $berita->lastPage() }}</span>
            @if ($berita->hasMorePages())
              <a href="{{ $berita->nextPageUrl() }}">Berikutnya ›</a>
            @else
              <span>Berikutnya ›</span>
            @endif
          </nav>
        @endif
      @endif
    </div>
  </section>
@endsection
