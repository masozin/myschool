@extends('publik.web-sekolah.layout')

@section('title', $namaSekolah . ' — Website Resmi')
@section('meta_description', 'Website resmi ' . $namaSekolah . ', NPSN ' . $sekolah->npsn)

@section('content')
  {{-- ── Hero ─────────────────────────────────────────────────────── --}}
  <header class="ws-hero" id="beranda">
    @if ($web->foto_hero)
      <div class="ws-hero-bg"><img src="{{ route('web-sekolah.foto-hero', $web->slug) }}" alt="" aria-hidden="true"></div>
    @endif
    <div class="ws-hero-glow"></div>
    <div class="ws-container">
      <div class="ws-hero-in">
        <span class="ws-eyebrow"><i></i> Website Resmi Sekolah</span>
        <h1>{{ $namaSekolah }}</h1>
        @if ($web->tagline)
          <p class="ws-tagline">{{ $web->tagline }}</p>
        @else
          <p class="ws-tagline">Selamat datang di website resmi {{ $namaSekolah }} — pusat informasi, pengumuman, dan layanan daring bagi siswa, orang tua, dan masyarakat.</p>
        @endif
        <div class="ws-hero-chips">
          <span class="ws-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M8 4v5"/></svg> NPSN {{ $sekolah->npsn }}</span>
          <span class="ws-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 9.5 12 4 2 9.5l10 5.5 10-5.5z"/><path d="M6 11.8V16c0 1.7 2.7 3 6 3s6-1.3 6-3v-4.2"/></svg> {{ $sekolah->jenjang }}</span>
          @if ($web->akreditasi)
            <span class="ws-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="6"/><path d="m8.5 14-2 7 5.5-3 5.5 3-2-7"/></svg> Akreditasi {{ $web->akreditasi }}</span>
          @endif
          @if ($web->tahun_berdiri)
            <span class="ws-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg> Berdiri {{ $web->tahun_berdiri }}</span>
          @endif
        </div>
        <div class="ws-hero-cta">
          <a class="ws-btn ws-btn-accent" href="{{ route('validasi-kelulusan.index') }}">Cek Kelulusan <span class="ws-arrow">&rarr;</span></a>
          <a class="ws-btn ws-btn-ghost" href="{{ route('web-sekolah.berita.index', $web->slug) }}">Berita Terbaru</a>
        </div>
      </div>
    </div>
    <div class="ws-hero-wave" aria-hidden="true"><svg viewBox="0 0 1440 56" preserveAspectRatio="none"><path d="M0 56h1440V28c-180 18-420 26-720 12C420 26 180 30 0 44v12z" fill="#f6f8fc"/></svg></div>
  </header>

  {{-- ── Statistik hidup ──────────────────────────────────────────── --}}
  <div class="ws-container">
    <div class="ws-stats">
      <div class="ws-stat" data-reveal><div class="ws-num" data-count="{{ $statistik['siswa'] }}">0</div><div class="ws-lbl">Siswa Aktif</div></div>
      <div class="ws-stat" data-reveal><div class="ws-num" data-count="{{ $statistik['guru'] }}">0</div><div class="ws-lbl">Guru</div></div>
      <div class="ws-stat" data-reveal><div class="ws-num" data-count="{{ $statistik['ptk'] }}">0</div><div class="ws-lbl">Guru &amp; Tendik</div></div>
      <div class="ws-stat" data-reveal><div class="ws-num" data-count="{{ $statistik['rombel'] }}">0</div><div class="ws-lbl">Rombongan Belajar</div></div>
    </div>
    @if ($semesterAktif)
      <p class="ws-stat-note">Data berjalan — semester {{ $semesterAktif->semester }} tahun pelajaran {{ $semesterAktif->tahun_pelajaran }}</p>
    @endif
  </div>

  {{-- ── Sambutan + Visi Misi ─────────────────────────────────────── --}}
  @if ($web->sambutan || $web->visi || $web->misi)
    <section class="ws-section" id="sambutan" style="padding-bottom:70px">
      <div class="ws-container">
        <div class="ws-sec-head" data-reveal>
          <span class="ws-eyebrow-sm">Profil Sekolah</span>
          <h2>Mengenal {{ $namaSekolah }}</h2>
        </div>
        @if ($web->sambutan)
          <div class="ws-sambutan">
            <aside class="ws-kepsek" data-reveal>
              <svg class="ws-quote" viewBox="0 0 24 24" fill="currentColor"><path d="M10 7H6a3 3 0 0 0-3 3v7h7v-7H7c0-1.7 1.3-3 3-3V7zm11 0h-4a3 3 0 0 0-3 3v7h7v-7h-3c0-1.7 1.3-3 3-3V7z"/></svg>
              <span class="ws-jabatan">Sambutan Kepala Sekolah</span>
              @if ($detail?->kepala_sekolah)
                <span class="ws-nama">{{ $detail->kepala_sekolah }}</span>
                <span class="ws-nip">Kepala {{ $namaSekolah }}@if ($detail->nip) &middot; NIP {{ $detail->nip }} @endif</span>
              @else
                <span class="ws-nama">Kepala {{ $namaSekolah }}</span>
              @endif
            </aside>
            <div class="ws-sambutan-teks" data-reveal>
              <div class="ws-kaya" data-lipat>{!! \App\Support\TeksKaya::tampil($web->sambutan) !!}</div>
            </div>
          </div>
        @endif
        @if ($web->visi || $web->misi)
          <div class="ws-visimisi" id="visi-misi">
            @if ($web->visi)
              <div class="ws-vm" data-reveal>
                <span class="ws-vm-ikon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1.2" fill="currentColor"/></svg></span>
                <h3>Visi</h3>
                <div class="ws-kaya" data-lipat>{!! \App\Support\TeksKaya::tampil($web->visi) !!}</div>
              </div>
            @endif
            @if ($web->misi)
              <div class="ws-vm" data-reveal>
                <span class="ws-vm-ikon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V4"/><path d="M4 5h13l-2.5 4L17 13H4"/></svg></span>
                <h3>Misi</h3>
                <div class="ws-kaya" data-lipat>{!! \App\Support\TeksKaya::tampil($web->misi) !!}</div>
              </div>
            @endif
          </div>
        @endif
      </div>
    </section>
  @endif

  {{-- ── Berita terbaru ───────────────────────────────────────────── --}}
  <section class="ws-section ws-section-alt" style="padding-top:70px">
    <div class="ws-container">
      <div class="ws-sec-head" data-reveal style="display:flex;gap:20px;align-items:flex-end;max-width:none;flex-wrap:wrap">
        <div style="flex:1;min-width:260px">
          <span class="ws-eyebrow-sm">Kabar Sekolah</span>
          <h2>Berita &amp; Pengumuman</h2>
          <p>Informasi dan kegiatan terbaru dari {{ $namaSekolah }}.</p>
        </div>
        <a class="ws-btn ws-btn-outline" href="{{ route('web-sekolah.berita.index', $web->slug) }}">Semua Berita <span class="ws-arrow">&rarr;</span></a>
      </div>
      @if ($beritaTerbaru->isEmpty())
        <div class="ws-empty">Belum ada berita yang diterbitkan.</div>
      @else
        <div class="ws-grid">
          @foreach ($beritaTerbaru as $b)
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
      @endif
    </div>
  </section>

  {{-- ── Galeri: album kegiatan + foto lainnya ───────────────────── --}}
  @if ($albumGaleri->isNotEmpty() || $galeri->isNotEmpty())
    <section class="ws-section" id="galeri" style="padding-top:70px">
      <div class="ws-container">
        <div class="ws-sec-head" data-reveal>
          <span class="ws-eyebrow-sm">Dokumentasi</span>
          <h2>Galeri Kegiatan</h2>
          <p>Momen dan aktivitas {{ $namaSekolah }}@if ($albumGaleri->isNotEmpty()) — ketuk kartu album untuk melihat semua foto kegiatannya, atau ketuk foto untuk memperbesar.@else — ketuk foto untuk memperbesar.@endif</p>
        </div>
        @if ($albumGaleri->isNotEmpty())
          <div class="ws-galeri">
            @foreach ($albumGaleri as $album)
              @php $sampul = $album->fotoSampul(); @endphp
              @if ($sampul)
                <a class="ws-album-kartu" href="{{ route('web-sekolah.galeri.album', [$web->slug, $album]) }}" data-reveal
                  aria-label="Lihat album {{ $album->nama }}">
                  <img src="{{ route('web-sekolah.galeri.foto', $sampul) }}" alt="{{ $album->nama }}" loading="lazy">
                  <span class="ws-album-lencana">Album</span>
                  <span class="ws-album-info">
                    <strong>{{ $album->nama }}</strong>
                    <small>@if ($album->tanggal){{ $album->tanggal->translatedFormat('d F Y') }} &middot; @endif{{ $album->galeri_count }} foto</small>
                  </span>
                </a>
              @endif
            @endforeach
          </div>
        @endif
        @if ($galeri->isNotEmpty())
          @if ($albumGaleri->isNotEmpty())
            <h3 style="margin:34px 0 18px;font-size:1.25rem">Foto Lainnya</h3>
          @endif
          <div class="ws-galeri">
            @foreach ($galeri as $g)
              <figure data-reveal>
                <img src="{{ route('web-sekolah.galeri.foto', $g) }}" alt="{{ $g->judul ?? 'Galeri ' . $namaSekolah }}" loading="lazy">
                @if ($g->judul)
                  <figcaption>{{ $g->judul }}@if ($g->keterangan) — {{ $g->keterangan }} @endif</figcaption>
                @endif
              </figure>
            @endforeach
          </div>
        @endif
      </div>
    </section>
  @endif

  {{-- ── Keunggulan (dari data sekolah) ───────────────────────────── --}}
  <section class="ws-section ws-section-alt" style="padding-top:70px;padding-bottom:70px">
    <div class="ws-container">
      <div class="ws-sec-head" data-reveal>
        <span class="ws-eyebrow-sm">Keunggulan</span>
        <h2>Mengapa {{ $namaSekolah }}?</h2>
      </div>
      <div class="ws-unggul">
        @if ($web->akreditasi)
          <div class="ws-unggul-item" data-reveal>
            <span class="ws-ikon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="6"/><path d="m8.5 14-2 7 5.5-3 5.5 3-2-7"/></svg></span>
            <div><h3>Terakreditasi {{ $web->akreditasi }}</h3><p>Mutu pendidikan teruji melalui akreditasi BAN-S/M.</p></div>
          </div>
        @endif
        @if ($web->tahun_berdiri)
          <div class="ws-unggul-item" data-reveal>
            <span class="ws-ikon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18M5 21V8l7-5 7 5v13"/><path d="M9 21v-6h6v6"/></svg></span>
            <div><h3>{{ date('Y') - $web->tahun_berdiri }} Tahun Mengabdi</h3><p>Berdiri sejak {{ $web->tahun_berdiri }}, melayani pendidikan di lingkungan sekitar.</p></div>
          </div>
        @endif
        @if ($statistik['guru'] > 0)
          <div class="ws-unggul-item" data-reveal>
            <span class="ws-ikon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
            <div><h3>Rasio Guru 1 : {{ max(1, (int) round($statistik['siswa'] / max(1, $statistik['guru']))) }}</h3><p>{{ $statistik['ptk'] }} guru &amp; tenaga kependidikan mendampingi {{ number_format($statistik['siswa'], 0, ',', '.') }} siswa.</p></div>
          </div>
        @endif
        <div class="ws-unggul-item" data-reveal>
          <span class="ws-ikon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a15 15 0 0 1 0 18M12 3a15 15 0 0 0 0 18"/></svg></span>
          <div><h3>Layanan Daring 24 Jam</h3><p>Cek kelulusan, berita, dan informasi sekolah dapat diakses kapan saja dari mana saja.</p></div>
        </div>
      </div>
    </div>
  </section>

  {{-- ── Agenda kalender pendidikan ───────────────────────────────── --}}
  @if (($agenda ?? collect())->isNotEmpty())
    <section class="ws-section" style="padding-top:70px">
      <div class="ws-container">
        <div class="ws-sec-head" data-reveal>
          <span class="ws-eyebrow-sm">Kalender Pendidikan</span>
          <h2>Agenda Terdekat</h2>
          <p>Kegiatan dan hari libur pada kalender pendidikan yang sedang berjalan.</p>
        </div>
        <div class="ws-agenda">
          @foreach ($agenda as $a)
            <div class="ws-agenda-item" data-reveal>
              <div class="ws-agenda-tgl">
                <div class="ws-d">{{ $a->tanggal_mulai->format('d') }}</div>
                <div class="ws-m">{{ $a->tanggal_mulai->translatedFormat('M') }}</div>
              </div>
              <div class="ws-agenda-isi">
                <h3>{{ $a->kegiatan }}</h3>
                <p>
                  {{ $a->tanggal_mulai->translatedFormat('l, d F Y') }}
                  @if ($a->tanggal_selesai && $a->tanggal_selesai->ne($a->tanggal_mulai))
                    &ndash; {{ $a->tanggal_selesai->translatedFormat('l, d F Y') }}
                  @endif
                  &middot; {{ $a->jenis }}
                </p>
              </div>
              @if ($a->is_libur)
                <span class="ws-lencana ws-lencana-libur">Libur</span>
              @else
                <span class="ws-lencana ws-lencana-kegiatan">Kegiatan</span>
              @endif
            </div>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- ── Jadwal pelajaran hari ini ────────────────────────────────── --}}
  @if ($jadwalHariIni['tampil'] ?? false)
    <section class="ws-section" id="jadwal" style="padding-top:10px;padding-bottom:80px">
      <div class="ws-container">
        <div class="ws-sec-head" data-reveal style="display:flex;gap:20px;align-items:flex-end;max-width:none;flex-wrap:wrap;margin-bottom:30px">
          <div style="flex:1;min-width:260px">
            <span class="ws-eyebrow-sm">Jadwal Pelajaran</span>
            <h2>Jadwal Hari Ini — {{ $jadwalHariIni['namaHari'] }}</h2>
            <p>{{ $jadwalHariIni['tanggal'] }}@if ($semesterAktif) &middot; Semester {{ $semesterAktif->semester }} {{ $semesterAktif->tahun_pelajaran }} @endif</p>
          </div>
          <span class="ws-jam-chip"><i></i> Pukul <span id="wsJam">{{ now()->format('H.i') }}</span> WIB</span>
        </div>

        @if ($jadwalHariIni['libur'])
          <div class="ws-libur" data-reveal>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/><path d="m9.5 17 2 2 4-4.5"/></svg>
            <h3>Hari Ini Libur</h3>
            <p>{{ $jadwalHariIni['alasanLibur'] }} Jadwal pelajaran akan tampil kembali pada hari sekolah berikutnya.</p>
          </div>
        @else
          <div class="ws-jadwal" data-reveal>
            @if (count($jadwalHariIni['daftar']) > 1)
              <div class="ws-pills" role="tablist" aria-label="Pilih kelas">
                @foreach ($jadwalHariIni['daftar'] as $i => $r)
                  <button type="button" class="ws-pill {{ $i === 0 ? 'aktif' : '' }}" data-panel="wsPanel{{ $i }}" role="tab" aria-selected="{{ $i === 0 ? 'true' : 'false' }}">{{ $r['nama'] }}</button>
                @endforeach
              </div>
            @endif
            @foreach ($jadwalHariIni['daftar'] as $i => $r)
              <div class="ws-panel {{ $i === 0 ? 'aktif' : '' }}" id="wsPanel{{ $i }}" role="tabpanel">
                <div class="ws-panel-head">
                  <h3>{{ $r['nama'] }}</h3>
                  @if ($r['wali'])
                    <span>Wali Kelas: {{ $r['wali'] }}</span>
                  @endif
                </div>
                @foreach ($r['blok'] as $b)
                  <div class="ws-row {{ $b['jenis'] }} {{ ($b['aktif'] ?? false) ? 'berlangsung' : '' }}">
                    <span class="ws-waktu">{{ $b['mulai'] }} – {{ $b['selesai'] }}</span>
                    <span class="ws-titik" aria-hidden="true"></span>
                    <span class="ws-isi">
                      <strong>{{ $b['label'] === '' ? '—' : $b['label'] }}</strong>
                      @if ($b['sub'] !== '')
                        <span>{{ $b['sub'] }}</span>
                      @endif
                    </span>
                    @if ($b['aktif'] ?? false)
                      <span class="ws-lencana-live">Berlangsung</span>
                    @endif
                  </div>
                @endforeach
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </section>
  @endif

  {{-- ── Dokumen publik ─────────────────────────────────────────── --}}
  @if (($dokumenPublik ?? collect())->isNotEmpty())
    <section class="ws-section" id="dokumen" style="padding-top:70px;padding-bottom:80px">
      <div class="ws-container">
        <div class="ws-sec-head" data-reveal>
          <span class="ws-eyebrow-sm">Informasi Resmi</span>
          <h2>Dokumen Publik</h2>
          <p>Dokumen resmi {{ $namaSekolah }} yang dapat dilihat dan diunduh oleh publik.</p>
        </div>
        @foreach ($dokumenPublik as $kategori => $daftar)
          <div class="ws-doc-group" data-reveal>
            <h3 class="ws-doc-title">{{ \App\Models\WebDokumen::KATEGORI[$kategori] ?? 'Lainnya' }}</h3>
            <div class="ws-doc-list">
              @foreach ($daftar as $d)
                <div class="ws-doc-item">
                  <span class="ws-doc-icon {{ $d->isPdf() ? 'is-pdf' : 'is-img' }}">
                    @if ($d->isPdf())
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 14h6M9 17h4"/></svg>
                    @else
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="m4 18 5-4 4 3 3-2 4 3"/></svg>
                    @endif
                  </span>
                  <div class="ws-doc-main">
                    <strong>{{ $d->judul }}</strong>
                    <small>
                      @if ($d->tahun) Tahun {{ $d->tahun }} @endif
                      @if ($d->ukuranTampil()) &middot; {{ $d->ukuranTampil() }} @endif
                      @if ($d->keterangan) &middot; {{ $d->keterangan }} @endif
                    </small>
                  </div>
                  <div class="ws-doc-actions">
                    <a class="ws-doc-btn" href="{{ route('web-sekolah.dokumen.lihat', $d) }}" target="_blank" rel="noopener">Lihat</a>
                    <a class="ws-doc-btn is-primary" href="{{ route('web-sekolah.dokumen.unduh', $d) }}">Unduh</a>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
    </section>
  @endif

  {{-- ── Dewan guru & tendik ──────────────────────────────────────── --}}
  @if (($dewanGuru ?? collect())->isNotEmpty())
    <section class="ws-section ws-section-alt" style="padding-top:70px">
      <div class="ws-container">
        <div class="ws-sec-head" data-reveal>
          <span class="ws-eyebrow-sm">Sumber Daya Manusia</span>
          <h2>Guru &amp; Tenaga Kependidikan</h2>
          <p>Pendidik dan tenaga kependidikan yang mengabdi di {{ $namaSekolah }}.</p>
        </div>
        <div class="ws-guru">
          @foreach ($dewanGuru as $p)
            <div class="ws-guru-card" data-reveal>
              @if ($p->photo)
                <img src="{{ route('web-sekolah.ptk.foto', $p) }}" alt="Foto {{ $p->nama_lengkap }}" loading="lazy" onerror="this.outerHTML = '<span class=&quot;ws-inisial&quot;>{{ strtoupper(substr($p->nama_ptk, 0, 1)) }}</span>'">
              @else
                <span class="ws-inisial">{{ strtoupper(substr($p->nama_ptk, 0, 1)) }}</span>
              @endif
              <h3>{{ $p->nama_lengkap }}</h3>
              <p class="ws-jbt">{{ $p->jabatan }}</p>
              @if ($p->jenis_kepegawaian)
                <p class="ws-peg">{{ $p->jenis_kepegawaian }}</p>
              @endif
            </div>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- ── Layanan publik ───────────────────────────────────────────── --}}
  <section class="ws-section" style="padding-top:70px">
    <div class="ws-container">
      <div class="ws-sec-head" data-reveal>
        <span class="ws-eyebrow-sm">Layanan Publik</span>
        <h2>Layanan Daring Sekolah</h2>
        <p>Layanan yang dapat diakses masyarakat tanpa harus datang ke sekolah.</p>
      </div>
      <div class="ws-layanan">
        <div class="ws-card" data-reveal>
          <span class="ws-ikon ws-ikon-ambar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 9.5 12 4 2 9.5l10 5.5 10-5.5z"/><path d="M6 11.8V16c0 1.7 2.7 3 6 3s6-1.3 6-3v-4.2"/><path d="M22 9.5V16"/></svg></span>
          <h3>Cek Kelulusan / SKHU</h3>
          <p>Periksa keaslian dan data Surat Keterangan Hasil Ujian / kelulusan cukup dengan NISN siswa.</p>
          <a class="ws-readmore" href="{{ route('validasi-kelulusan.index') }}">Buka layanan <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
        </div>
        <div class="ws-card" data-reveal>
          <span class="ws-ikon ws-ikon-biru"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 22h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-4 0V9"/><path d="M18 14h-8M15 18h-5M10 6h8v4h-8z"/></svg></span>
          <h3>Berita Sekolah</h3>
          <p>Pengumuman resmi, agenda, dan kabar kegiatan sekolah yang diterbitkan berkala.</p>
          <a class="ws-readmore" href="{{ route('web-sekolah.berita.index', $web->slug) }}">Buka berita <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
        </div>
        <div class="ws-card" data-reveal>
          <span class="ws-ikon ws-ikon-navy"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
          <h3>Portal myschool</h3>
          <p>Area masuk bagi guru, tenaga kependidikan, dan pengelola sekolah untuk layanan internal.</p>
          <a class="ws-readmore" href="{{ route('login') }}">Masuk portal <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
        </div>
      </div>
    </div>
  </section>

  {{-- ── Kontak ───────────────────────────────────────────────────── --}}
  <section class="ws-section ws-section-alt" id="kontak" style="padding-top:70px">
    <div class="ws-container">
      <div class="ws-sec-head" data-reveal>
        <span class="ws-eyebrow-sm">Hubungi Kami</span>
        <h2>Kontak &amp; Lokasi</h2>
        <p>Silakan hubungi atau kunjungi kami pada hari dan jam kerja.</p>
      </div>
      <div class="ws-kontak">
        <div class="ws-kontak-list" data-reveal>
          @if ($detail?->alamat_sekolah)
            <div class="ws-kontak-item">
              <span class="ws-ikon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
              <div><h4>Alamat</h4><p>{{ $detail->alamat_sekolah }}@if ($detail->village), {{ $detail->village->name ?? '' }}@endif @if ($detail->district), Kec. {{ $detail->district->name ?? '' }}@endif @if ($detail->regency), {{ $detail->regency->name ?? '' }}@endif @if ($detail->province), {{ $detail->province->name ?? '' }}@endif</p></div>
            </div>
          @endif
          @if ($detail?->no_telp)
            <div class="ws-kontak-item">
              <span class="ws-ikon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.13.96.36 1.9.7 2.8a2 2 0 0 1-.45 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.45c.9.34 1.84.57 2.8.7A2 2 0 0 1 22 16.9z"/></svg></span>
              <div><h4>Telepon</h4><p>{{ $detail->no_telp }}</p></div>
            </div>
          @endif
          @if ($detail?->email)
            <div class="ws-kontak-item">
              <span class="ws-ikon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg></span>
              <div><h4>Surel</h4><p><a href="mailto:{{ $detail->email }}">{{ $detail->email }}</a></p></div>
            </div>
          @endif
          @if ($detail?->kepala_sekolah)
            <div class="ws-kontak-item">
              <span class="ws-ikon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
              <div><h4>Kepala Sekolah</h4><p>{{ $detail->kepala_sekolah }}@if ($detail->nip) &middot; NIP {{ $detail->nip }} @endif</p></div>
            </div>
          @endif
        </div>
        <aside class="ws-cta" data-reveal>
          <h3>Perlu memeriksa kelulusan putra-putri Anda?</h3>
          <p>Layanan cek kelulusan daring tersedia setiap saat — cukup siapkan NISN siswa, tanpa perlu datang ke sekolah.</p>
          <a class="ws-btn ws-btn-accent" href="{{ route('validasi-kelulusan.index') }}">Cek Kelulusan Sekarang <span class="ws-arrow">&rarr;</span></a>
        </aside>
      </div>
    </div>
  </section>

  {{-- ── MySchool Mobile ──────────────────────────────────────────── --}}
  <section class="ws-section" style="padding-top:56px">
    <div class="ws-container">
      <div class="ws-aplikasi" data-reveal>
        <div class="ws-aplikasi-teks">
          <span class="ws-eyebrow-sm" style="color:var(--sky)">Aplikasi Pendamping</span>
          <h2>Kini Tersedia <span style="color:var(--sky)">MySchool Mobile</span></h2>
          <p>Aplikasi Android untuk guru dan staf {{ $namaSekolah }} — urusan kelas dapat dikerjakan langsung dari ponsel, tanpa harus membuka laptop.</p>
          <ul class="ws-aplikasi-fitur">
            <li>Presensi siswa per rombel, langsung dari kelas</li>
            <li>Data siswa sekolah dalam genggaman</li>
            <li>Masuk mudah dengan NIP — tanpa perlu surel</li>
          </ul>
          <p class="ws-aplikasi-catatan">Aplikasi khusus guru &amp; staf sekolah — hubungi operator sekolah untuk mendapatkan dan memasang aplikasinya.</p>
        </div>
        <div class="ws-aplikasi-visual" aria-hidden="true">
          <div class="ws-phone">
            <div class="ws-phone-layar">
              <div class="ws-phone-kepala">
                <span class="ws-phone-logo">M</span>
                <div><strong>MySchool</strong><small>{{ $namaSekolah }}</small></div>
              </div>
              <div class="ws-phone-kartu"><small>Presensi Hari Ini</small><strong>Presensi kelas tercatat rapi setiap hari</strong></div>
              <div class="ws-phone-grid"><span></span><span></span><span></span><span></span></div>
              <div class="ws-phone-baris"></div>
              <div class="ws-phone-baris" style="width:70%"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection

@section('page-script')
  <script>
    (function () {
      // Tab kelas pada Jadwal Hari Ini.
      var pills = document.querySelectorAll('.ws-pill[data-panel]');
      pills.forEach(function (pill) {
        pill.addEventListener('click', function () {
          pills.forEach(function (p) { p.classList.remove('aktif'); p.setAttribute('aria-selected', 'false'); });
          document.querySelectorAll('.ws-panel').forEach(function (pn) { pn.classList.remove('aktif'); });
          pill.classList.add('aktif');
          pill.setAttribute('aria-selected', 'true');
          var panel = document.getElementById(pill.getAttribute('data-panel'));
          if (panel) panel.classList.add('aktif');
        });
      });

      // Jam berjalan pada chip jadwal (diperbarui tiap 20 detik).
      var jam = document.getElementById('wsJam');
      if (jam) {
        function segarkan() {
          var d = new Date();
          jam.textContent = String(d.getHours()).padStart(2, '0') + '.' + String(d.getMinutes()).padStart(2, '0');
        }
        segarkan();
        setInterval(segarkan, 20000);
      }
    })();
  </script>
@endsection
