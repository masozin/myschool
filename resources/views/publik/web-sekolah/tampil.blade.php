@extends('publik.web-sekolah.layout')

@section('title', $namaSekolah . ' — Website Resmi')
@section('meta_description', 'Website resmi ' . $namaSekolah . ', NPSN ' . $sekolah->npsn)

@section('content')
  {{-- ── Hero ─────────────────────────────────────────────────────── --}}
  <header class="ws-hero">
    <div class="ws-container ws-hero-grid">
      <div>
        <h1>{{ $namaSekolah }}</h1>
        @if ($web->tagline)
          <p class="ws-tagline">{{ $web->tagline }}</p>
        @endif
        <p class="ws-meta">
          NPSN {{ $sekolah->npsn }} &middot; {{ $sekolah->jenjang }}
          @if ($web->akreditasi) &middot; Akreditasi {{ $web->akreditasi }} @endif
          @if ($detail?->alamat_sekolah)
            <br>{{ $detail->alamat_sekolah }}
            @if ($detail->district) &middot; Kec. {{ $detail->district->name ?? '' }} @endif
            @if ($detail->regency) &middot; {{ $detail->regency->name ?? '' }} @endif
          @endif
        </p>
        <div class="ws-hero-cta">
          <a class="ws-btn ws-btn-accent" href="{{ route('validasi-kelulusan.index') }}">Cek Kelulusan</a>
          <a class="ws-btn ws-btn-outline" style="border-color:#fff;color:#fff!important" href="#kontak">Hubungi Sekolah</a>
        </div>
      </div>
      @if ($web->foto_hero)
        <div class="ws-hero-photo">
          <img src="{{ route('web-sekolah.foto-hero', $web->slug) }}" alt="Foto {{ $namaSekolah }}">
        </div>
      @endif
    </div>
  </header>

  {{-- ── Statistik hidup ──────────────────────────────────────────── --}}
  <div class="ws-container">
    <div class="ws-stats">
      <div class="ws-stat"><div class="ws-num">{{ number_format($statistik['siswa'], 0, ',', '.') }}</div><div class="ws-lbl">Siswa Aktif</div></div>
      <div class="ws-stat"><div class="ws-num">{{ number_format($statistik['guru'], 0, ',', '.') }}</div><div class="ws-lbl">Guru</div></div>
      <div class="ws-stat"><div class="ws-num">{{ number_format($statistik['ptk'], 0, ',', '.') }}</div><div class="ws-lbl">Guru &amp; Tendik</div></div>
      <div class="ws-stat"><div class="ws-num">{{ number_format($statistik['rombel'], 0, ',', '.') }}</div><div class="ws-lbl">Rombongan Belajar</div></div>
    </div>
    @if ($semesterAktif)
      <p style="text-align:center;color:var(--ws-muted);font-size:.88rem;margin-top:14px">
        Data semester {{ $semesterAktif->semester }} {{ $semesterAktif->tahun_pelajaran }}
      </p>
    @endif
  </div>

  {{-- ── Sambutan + Visi Misi ─────────────────────────────────────── --}}
  @if ($web->sambutan || $web->visi || $web->misi)
    <section class="ws-section" id="sambutan">
      <div class="ws-container ws-duo">
        @if ($web->sambutan)
          <div class="ws-box">
            <h3>Sambutan Kepala Sekolah</h3>
            <p class="ws-pre">{{ $web->sambutan }}</p>
            @if ($detail?->kepala_sekolah)
              <p style="margin-top:16px"><strong>{{ $detail->kepala_sekolah }}</strong><br>
                <span style="color:var(--ws-muted)">Kepala {{ $namaSekolah }}@if ($detail->nip) &middot; NIP {{ $detail->nip }} @endif</span></p>
            @endif
          </div>
        @endif
        @if ($web->visi || $web->misi)
          <div class="ws-box" id="visi-misi">
            @if ($web->visi)
              <h3>Visi</h3>
              <p class="ws-pre" style="margin-bottom:18px">{{ $web->visi }}</p>
            @endif
            @if ($web->misi)
              <h3>Misi</h3>
              <p class="ws-pre">{{ $web->misi }}</p>
            @endif
          </div>
        @endif
      </div>
    </section>
  @endif

  {{-- ── Berita terbaru ───────────────────────────────────────────── --}}
  <section class="ws-section ws-section-alt">
    <div class="ws-container">
      <h2>Berita &amp; Pengumuman</h2>
      <p class="ws-sub">Kabar terbaru dari {{ $namaSekolah }}.
        <a href="{{ route('web-sekolah.berita.index', $web->slug) }}">Lihat semua berita →</a></p>
      @if ($beritaTerbaru->isEmpty())
        <div class="ws-empty">Belum ada berita yang diterbitkan.</div>
      @else
        <div class="ws-grid">
          @foreach ($beritaTerbaru as $b)
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
      @endif
    </div>
  </section>

  {{-- ── Galeri ───────────────────────────────────────────────────── --}}
  @if ($galeri->isNotEmpty())
    <section class="ws-section" id="galeri">
      <div class="ws-container">
        <h2>Galeri Kegiatan</h2>
        <p class="ws-sub">Dokumentasi kegiatan {{ $namaSekolah }}.</p>
        <div class="ws-galeri">
          @foreach ($galeri as $g)
            <figure>
              <img src="{{ route('web-sekolah.galeri.foto', $g) }}" alt="{{ $g->judul ?? 'Galeri ' . $namaSekolah }}" loading="lazy">
              @if ($g->judul)
                <figcaption>{{ $g->judul }}</figcaption>
              @endif
            </figure>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- ── Layanan publik ───────────────────────────────────────────── --}}
  <section class="ws-section ws-section-alt">
    <div class="ws-container">
      <h2>Layanan Publik</h2>
      <p class="ws-sub">Layanan daring yang disediakan sekolah.</p>
      <div class="ws-grid">
        <div class="ws-card"><div class="ws-card-body">
          <h3>🎓 Cek Kelulusan / SKHU</h3>
          <p>Periksa keaslian dan data Surat Keterangan Hasil Ujian / kelulusan dengan NISN siswa.</p>
          <a href="{{ route('validasi-kelulusan.index') }}">Buka layanan →</a>
        </div></div>
        <div class="ws-card"><div class="ws-card-body">
          <h3>📰 Berita Sekolah</h3>
          <p>Pengumuman, agenda, dan kabar kegiatan sekolah terbaru.</p>
          <a href="{{ route('web-sekolah.berita.index', $web->slug) }}">Buka berita →</a>
        </div></div>
        <div class="ws-card"><div class="ws-card-body">
          <h3>🔐 Portal myschool</h3>
          <p>Masuk untuk guru, tenaga kependidikan, dan pengelola sekolah.</p>
          <a href="{{ route('login') }}">Masuk →</a>
        </div></div>
      </div>
    </div>
  </section>

  {{-- ── Kontak ───────────────────────────────────────────────────── --}}
  <section class="ws-section" id="kontak">
    <div class="ws-container">
      <h2>Kontak &amp; Lokasi</h2>
      <p class="ws-sub">Silakan hubungi atau kunjungi kami.</p>
      <div class="ws-box">
        <p><strong>{{ $namaSekolah }}</strong></p>
        @if ($detail?->alamat_sekolah)
          <p>📍 {{ $detail->alamat_sekolah }}
            @if ($detail->village) , {{ $detail->village->name ?? '' }} @endif
            @if ($detail->district) , Kec. {{ $detail->district->name ?? '' }} @endif
            @if ($detail->regency) , {{ $detail->regency->name ?? '' }} @endif
            @if ($detail->province) , {{ $detail->province->name ?? '' }} @endif
          </p>
        @endif
        @if ($detail?->no_telp) <p>📞 {{ $detail->no_telp }}</p> @endif
        @if ($detail?->email) <p>✉️ <a href="mailto:{{ $detail->email }}">{{ $detail->email }}</a></p> @endif
        @if ($detail?->kepala_sekolah) <p>👤 Kepala Sekolah: {{ $detail->kepala_sekolah }}</p> @endif
      </div>
    </div>
  </section>
@endsection
