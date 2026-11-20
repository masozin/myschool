<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', $namaSekolah ?? 'Web Sekolah')</title>
  <meta name="description" content="@yield('meta_description', 'Website resmi ' . ($namaSekolah ?? 'sekolah'))">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{--ws-primary:#1a56a4;--ws-dark:#0f2444;--ws-accent:#f59e0b;--ws-muted:#64748b;--ws-border:#e2e8f0;--ws-bg:#f8fafc;--ws-card:#fff;--ws-text:#1e293b}
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;color:var(--ws-text);background:var(--ws-bg);line-height:1.6}
    a{color:var(--ws-primary);text-decoration:none}
    img{max-width:100%;display:block}
    .ws-container{max-width:1140px;margin:0 auto;padding:0 20px}
    /* Navbar */
    .ws-nav{background:#fff;border-bottom:1px solid var(--ws-border);position:sticky;top:0;z-index:50}
    .ws-nav-in{display:flex;align-items:center;gap:16px;padding:12px 0;flex-wrap:wrap}
    .ws-brand{display:flex;align-items:center;gap:12px;color:var(--ws-dark);font-weight:800;font-size:1.05rem}
    .ws-brand img{width:44px;height:44px;object-fit:contain}
    .ws-brand .ws-badge{display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:12px;background:var(--ws-primary);color:#fff;font-size:1.2rem}
    .ws-links{display:flex;gap:22px;margin-left:auto;flex-wrap:wrap;align-items:center}
    .ws-links a{color:var(--ws-text);font-weight:600;font-size:.95rem}
    .ws-links a:hover{color:var(--ws-primary)}
    .ws-btn{display:inline-block;background:var(--ws-primary);color:#fff!important;padding:9px 20px;border-radius:10px;font-weight:700;font-size:.95rem}
    .ws-btn:hover{background:var(--ws-dark)}
    .ws-btn-accent{background:var(--ws-accent);color:var(--ws-dark)!important}
    .ws-btn-accent:hover{background:#d97706;color:#fff!important}
    .ws-btn-outline{background:transparent;border:2px solid var(--ws-primary);color:var(--ws-primary)!important}
    /* Hero */
    .ws-hero{background:linear-gradient(135deg,var(--ws-dark),var(--ws-primary));color:#fff;padding:64px 0}
    .ws-hero-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:36px;align-items:center}
    @media(max-width:820px){.ws-hero-grid{grid-template-columns:1fr}}
    .ws-hero h1{font-size:2.3rem;line-height:1.2;margin-bottom:12px}
    .ws-hero .ws-tagline{font-size:1.1rem;opacity:.92;margin-bottom:10px}
    .ws-hero .ws-meta{opacity:.85;font-size:.95rem;margin-bottom:24px}
    .ws-hero-photo{border-radius:18px;overflow:hidden;box-shadow:0 18px 40px rgba(0,0,0,.3)}
    .ws-hero-photo img{width:100%;height:320px;object-fit:cover}
    .ws-hero-cta{display:flex;gap:12px;flex-wrap:wrap}
    /* Sections */
    .ws-section{padding:56px 0}
    .ws-section-alt{background:#fff}
    .ws-section h2{font-size:1.6rem;color:var(--ws-dark);margin-bottom:8px}
    .ws-section .ws-sub{color:var(--ws-muted);margin-bottom:28px}
    /* Stats */
    .ws-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:-38px;position:relative;z-index:2}
    @media(max-width:820px){.ws-stats{grid-template-columns:repeat(2,1fr)}}
    .ws-stat{background:var(--ws-card);border:1px solid var(--ws-border);border-radius:14px;padding:20px;text-align:center;box-shadow:0 6px 18px rgba(15,36,68,.06)}
    .ws-stat .ws-num{font-size:1.9rem;font-weight:800;color:var(--ws-primary)}
    .ws-stat .ws-lbl{color:var(--ws-muted);font-size:.9rem;font-weight:600}
    /* Cards */
    .ws-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
    @media(max-width:820px){.ws-grid{grid-template-columns:1fr}}
    .ws-card{background:var(--ws-card);border:1px solid var(--ws-border);border-radius:14px;overflow:hidden;display:flex;flex-direction:column}
    .ws-card img{width:100%;height:180px;object-fit:cover}
    .ws-card-body{padding:18px;display:flex;flex-direction:column;gap:8px;flex:1}
    .ws-card h3{font-size:1.05rem;color:var(--ws-dark);line-height:1.35}
    .ws-card p{color:var(--ws-muted);font-size:.92rem;flex:1}
    .ws-date{color:var(--ws-accent);font-weight:700;font-size:.82rem;text-transform:uppercase;letter-spacing:.03em}
    /* Galeri */
    .ws-galeri{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}
    @media(max-width:820px){.ws-galeri{grid-template-columns:repeat(2,1fr)}}
    .ws-galeri figure{border-radius:12px;overflow:hidden;position:relative}
    .ws-galeri img{width:100%;height:170px;object-fit:cover}
    .ws-galeri figcaption{position:absolute;left:0;right:0;bottom:0;background:linear-gradient(transparent,rgba(15,36,68,.85));color:#fff;font-size:.82rem;padding:22px 10px 8px}
    /* Sambutan & visi */
    .ws-duo{display:grid;grid-template-columns:1fr 1fr;gap:28px}
    @media(max-width:820px){.ws-duo{grid-template-columns:1fr}}
    .ws-box{background:var(--ws-card);border:1px solid var(--ws-border);border-radius:14px;padding:26px}
    .ws-box h3{color:var(--ws-dark);margin-bottom:10px}
    .ws-pre{white-space:pre-line}
    /* Footer */
    .ws-footer{background:var(--ws-dark);color:#cbd5e1;padding:40px 0 26px;margin-top:0}
    .ws-footer h4{color:#fff;margin-bottom:8px}
    .ws-footer a{color:#93c5fd}
    .ws-footer .ws-copy{border-top:1px solid rgba(255,255,255,.15);margin-top:24px;padding-top:16px;font-size:.88rem;color:#94a3b8}
    .ws-empty{color:var(--ws-muted);background:#fff;border:1px dashed var(--ws-border);border-radius:12px;padding:22px;text-align:center}
    .ws-article{background:#fff;border:1px solid var(--ws-border);border-radius:14px;padding:30px}
    .ws-article img.ws-cover{width:100%;max-height:380px;object-fit:cover;border-radius:12px;margin-bottom:20px}
    .ws-article .ws-isi{white-space:pre-line;font-size:1rem}
    .ws-pagination{display:flex;gap:8px;justify-content:center;margin-top:26px;flex-wrap:wrap}
    .ws-pagination a,.ws-pagination span{padding:7px 14px;border:1px solid var(--ws-border);border-radius:8px;background:#fff;font-weight:600}
    .ws-pagination .active{background:var(--ws-primary);color:#fff;border-color:var(--ws-primary)}
  </style>
  @yield('page-style')
</head>
<body>
  <nav class="ws-nav">
    <div class="ws-container ws-nav-in">
      @isset($web)
        <a class="ws-brand" href="{{ route('web-sekolah.tampil', $web->slug) }}">
          @if ($punyaLogo ?? false)
            <img src="{{ route('web-sekolah.logo', $web->slug) }}" alt="Logo {{ $namaSekolah }}">
          @else
            <span class="ws-badge">🏫</span>
          @endif
          <span>{{ $namaSekolah }}</span>
        </a>
        <div class="ws-links">
          <a href="{{ route('web-sekolah.tampil', $web->slug) }}">Beranda</a>
          <a href="{{ route('web-sekolah.tampil', $web->slug) }}#sambutan">Sambutan</a>
          <a href="{{ route('web-sekolah.tampil', $web->slug) }}#visi-misi">Visi &amp; Misi</a>
          <a href="{{ route('web-sekolah.berita.index', $web->slug) }}">Berita</a>
          <a href="{{ route('web-sekolah.tampil', $web->slug) }}#galeri">Galeri</a>
          <a href="{{ route('web-sekolah.tampil', $web->slug) }}#kontak">Kontak</a>
          @auth
            <a class="ws-btn" href="{{ route('home') }}">Dashboard</a>
          @else
            <a class="ws-btn" href="{{ route('login') }}">Masuk</a>
          @endauth
        </div>
      @else
        <span class="ws-brand"><span class="ws-badge">🏫</span><span>Web Sekolah</span></span>
        <div class="ws-links">
          @auth
            <a class="ws-btn" href="{{ route('home') }}">Dashboard</a>
          @else
            <a class="ws-btn" href="{{ route('login') }}">Masuk</a>
          @endauth
        </div>
      @endisset
    </div>
  </nav>

  @yield('content')

  <footer class="ws-footer">
    <div class="ws-container">
      @isset($web)
        <h4>{{ $namaSekolah }}</h4>
        <p>
          NPSN {{ $sekolah->npsn }} &middot; {{ $sekolah->jenjang }}
          @if ($web->akreditasi) &middot; Akreditasi {{ $web->akreditasi }} @endif
          @if ($web->tahun_berdiri) &middot; Berdiri {{ $web->tahun_berdiri }} @endif
        </p>
        @if ($detail?->alamat_sekolah)
          <p>{{ $detail->alamat_sekolah }}@if ($detail->village) , {{ $detail->village->name ?? '' }}@endif
            @if ($detail->district) , Kec. {{ $detail->district->name ?? '' }}@endif
            @if ($detail->regency) , {{ $detail->regency->name ?? '' }}@endif</p>
        @endif
      @else
        <h4>Web Sekolah</h4>
        <p>Portal publik sekolah-sekolah pengguna myschool.</p>
      @endisset
      <div class="ws-copy">&copy; {{ date('Y') }} {{ $namaSekolah ?? 'Web Sekolah' }} &middot; Didukung oleh myschool</div>
    </div>
  </footer>
</body>
</html>
