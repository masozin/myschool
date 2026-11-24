<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <script>
    // Mode gelap: terapkan pilihan tersimpan (atau preferensi sistem)
    // SEBELUM halaman digambar agar tidak berkedip terang dulu.
    (function () {
      try {
        var t = localStorage.getItem('ws-tema');
        if (!t && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) t = 'dark';
        if (t === 'dark') document.documentElement.setAttribute('data-tema', 'dark');
      } catch (e) { /* abaikan */ }
    })();
  </script>
  <title>@yield('title', $namaSekolah ?? 'Web Sekolah')</title>
  <meta name="description" content="@yield('meta_description', 'Website resmi ' . ($namaSekolah ?? 'sekolah'))">
  <meta property="og:title" content="@yield('title', $namaSekolah ?? 'Web Sekolah')">
  <meta property="og:description" content="@yield('meta_description', 'Website resmi ' . ($namaSekolah ?? 'sekolah'))">
  <meta property="og:type" content="website">
  @isset($web)
    @if ($web->foto_hero)
      <meta property="og:image" content="{{ route('web-sekolah.foto-hero', $web->slug) }}">
    @elseif ($punyaLogo ?? false)
      <meta property="og:image" content="{{ route('web-sekolah.logo', $web->slug) }}">
    @endif
    {{-- Favicon: logo sekolah bila ada; bila tidak, favicon aplikasi. --}}
    @if ($punyaLogo ?? false)
      <link rel="icon" href="{{ route('web-sekolah.logo', $web->slug) }}">
      <link rel="apple-touch-icon" href="{{ route('web-sekolah.logo', $web->slug) }}">
    @else
      <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}">
    @endif
  @else
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}">
  @endisset
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
  <style>
    :root{
      --navy-950:#061630;--navy-900:#0a2450;--navy-800:#0e2f66;
      --primary:#1d5cbf;--primary-dark:#174a9e;--sky:#4f96ff;
      --accent:#f6a609;--accent-dark:#d98e04;
      --ink:#0e1e3d;--text:#3c4a68;--muted:#6b7a99;
      --line:#e3e9f4;--bg:#f6f8fc;--card:#ffffff;
      --shadow-sm:0 2px 10px rgba(10,36,80,.06);
      --shadow-md:0 14px 34px -12px rgba(10,36,80,.18);
      --shadow-lg:0 30px 60px -18px rgba(10,36,80,.28);
      --radius:18px;
    }
    /* ── Mode gelap: aktif bila <html data-tema="dark"> ── */
    :root[data-tema="dark"]{
      color-scheme:dark;
      --ink:#eef3fc;--text:#c3cfe2;--muted:#8fa1bd;
      --line:#22345c;--bg:#081226;--card:#0e1e3d;
      --shadow-sm:0 2px 10px rgba(0,0,0,.35);
      --shadow-md:0 14px 34px -12px rgba(0,0,0,.5);
      --shadow-lg:0 30px 60px -18px rgba(0,0,0,.6);
    }
    :root[data-tema="dark"] .ws-section-alt{background:#0b1a36}
    :root[data-tema="dark"] .ws-nav.is-solid,:root[data-tema="dark"] .ws-nav.scrolled{background:rgba(8,18,38,.92)}
    :root[data-tema="dark"] .ws-nav.is-solid .ws-links a:hover,:root[data-tema="dark"] .ws-nav.scrolled .ws-links a:hover,
    :root[data-tema="dark"] .ws-nav.is-solid .ws-links a.aktif,:root[data-tema="dark"] .ws-nav.scrolled .ws-links a.aktif{background:rgba(255,255,255,.09)}
    :root[data-tema="dark"] .ws-nav.is-solid .ws-burger,:root[data-tema="dark"] .ws-nav.scrolled .ws-burger,
    :root[data-tema="dark"] .ws-nav.is-solid .ws-tema,:root[data-tema="dark"] .ws-nav.scrolled .ws-tema{background:rgba(255,255,255,.08)}
    :root[data-tema="dark"] .ws-mobile{background:var(--card)}
    :root[data-tema="dark"] .ws-mobile a{border-bottom-color:var(--line)}
    :root[data-tema="dark"] .ws-article,:root[data-tema="dark"] .ws-empty,
    :root[data-tema="dark"] .ws-pagination a,:root[data-tema="dark"] .ws-pagination span,
    :root[data-tema="dark"] .ws-pill{background:var(--card)}
    :root[data-tema="dark"] .ws-pills{background:#0b1a36}
    /* Jadwal hari ini — mode gelap */
    :root[data-tema="dark"] .ws-jam-chip{background:rgba(255,255,255,.09);color:var(--ink)}
    :root[data-tema="dark"] .ws-row + .ws-row{border-top-color:var(--line)}
    :root[data-tema="dark"] .ws-row .ws-waktu{color:var(--ink)}
    :root[data-tema="dark"] .ws-row.istirahat{background:rgba(246,166,9,.12)}
    :root[data-tema="dark"] .ws-row.istirahat .ws-isi strong{color:#f6c453}
    :root[data-tema="dark"] .ws-row.berlangsung{background:linear-gradient(90deg,rgba(79,150,255,.22),rgba(79,150,255,.08));border-left-color:var(--sky)}
    /* ── Tombol saklar tema ── */
    .ws-tema{margin-left:6px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);border-radius:10px;width:44px;height:44px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;flex:none}
    .ws-tema svg{width:20px;height:20px}
    .ws-tema .ikon-matahari{display:none}
    :root[data-tema="dark"] .ws-tema .ikon-matahari{display:block}
    :root[data-tema="dark"] .ws-tema .ikon-bulan{display:none}
    .ws-nav.is-solid .ws-tema,.ws-nav.scrolled .ws-tema{background:#eef3fb;border-color:var(--line);color:var(--ink)}
    *{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{font-family:'Plus Jakarta Sans',system-ui,-apple-system,sans-serif;color:var(--text);background:var(--bg);line-height:1.65;font-size:16px;-webkit-font-smoothing:antialiased}
    a{color:var(--primary);text-decoration:none}
    img{max-width:100%;display:block}
    section{scroll-margin-top:92px}
    .ws-container{max-width:1180px;margin:0 auto;padding:0 24px}
    ::selection{background:var(--primary);color:#fff}

    /* ── Tombol ─────────────────────────────────────────── */
    .ws-btn{display:inline-flex;align-items:center;gap:9px;background:var(--primary);color:#fff!important;padding:13px 26px;border-radius:999px;font-weight:700;font-size:.95rem;border:2px solid var(--primary);transition:all .25s ease;cursor:pointer}
    .ws-btn:hover{background:var(--primary-dark);border-color:var(--primary-dark);transform:translateY(-2px);box-shadow:0 12px 24px -10px rgba(29,92,191,.55)}
    .ws-btn .ws-arrow{transition:transform .25s ease}
    .ws-btn:hover .ws-arrow{transform:translateX(3px)}
    .ws-btn-accent{background:var(--accent);border-color:var(--accent);color:#231a02!important}
    .ws-btn-accent:hover{background:var(--accent-dark);border-color:var(--accent-dark);box-shadow:0 12px 24px -10px rgba(246,166,9,.6)}
    .ws-btn-ghost{background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.55);color:#fff!important;backdrop-filter:blur(6px)}
    .ws-btn-ghost:hover{background:rgba(255,255,255,.18);border-color:#fff;box-shadow:none}
    .ws-btn-outline{background:transparent;border-color:var(--primary);color:var(--primary)!important}
    .ws-btn-outline:hover{background:var(--primary);color:#fff!important}

    /* ── Navbar ─────────────────────────────────────────── */
    .ws-nav{position:fixed;top:0;left:0;right:0;z-index:100;transition:all .3s ease}
    .ws-nav-in{display:flex;align-items:center;gap:18px;padding:16px 0;transition:padding .3s ease}
    .ws-nav.is-solid,.ws-nav.scrolled{background:rgba(255,255,255,.94);backdrop-filter:blur(14px);box-shadow:0 6px 24px rgba(10,36,80,.09)}
    .ws-nav.is-solid .ws-nav-in,.ws-nav.scrolled .ws-nav-in{padding:10px 0}
    .ws-brand{display:flex;align-items:center;gap:13px;font-weight:800;font-size:1.06rem;line-height:1.25;color:#fff;letter-spacing:-.01em}
    .ws-brand small{display:block;font-weight:600;font-size:.72rem;letter-spacing:.12em;text-transform:uppercase;opacity:.75}
    .ws-nav.is-solid .ws-brand,.ws-nav.scrolled .ws-brand{color:var(--ink)}
    .ws-brand img{width:46px;height:46px;object-fit:contain;background:#fff;border-radius:12px;padding:3px;box-shadow:var(--shadow-sm);flex:none}
    .ws-brand .ws-badge{display:flex;align-items:center;justify-content:center;width:46px;height:46px;border-radius:12px;background:linear-gradient(135deg,var(--primary),var(--navy-800));color:#fff;flex:none}
    .ws-brand .ws-badge svg{width:24px;height:24px}
    .ws-links{display:flex;gap:6px;margin-left:auto;align-items:center}
    .ws-links a{color:rgba(255,255,255,.88);font-weight:600;font-size:.93rem;padding:8px 13px;border-radius:999px;transition:all .2s}
    .ws-links a:hover{background:rgba(255,255,255,.14);color:#fff}
    .ws-nav.is-solid .ws-links a,.ws-nav.scrolled .ws-links a{color:var(--text)}
    .ws-nav.is-solid .ws-links a:hover,.ws-nav.scrolled .ws-links a:hover{background:#eef3fb;color:var(--primary)}
    .ws-links a.aktif{background:rgba(255,255,255,.16);color:#fff}
    .ws-nav.is-solid .ws-links a.aktif,.ws-nav.scrolled .ws-links a.aktif{background:#eef3fb;color:var(--primary)}
    .ws-mobile a.aktif{color:var(--primary)}
    .ws-links a.ws-btn{padding:10px 22px;margin-left:8px}
    .ws-links a.ws-btn,.ws-links a.ws-btn:hover{color:#fff!important;background:var(--primary)}
    .ws-burger{display:none;margin-left:auto;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);border-radius:10px;width:44px;height:44px;align-items:center;justify-content:center;cursor:pointer;color:#fff}
    .ws-nav.is-solid .ws-burger,.ws-nav.scrolled .ws-burger{background:#eef3fb;border-color:var(--line);color:var(--ink)}
    .ws-burger svg{width:22px;height:22px}
    .ws-mobile{display:none;background:#fff;border-top:1px solid var(--line);box-shadow:var(--shadow-md);padding:10px 24px 18px}
    .ws-mobile.open{display:block}
    .ws-mobile a{display:block;padding:11px 6px;font-weight:600;color:var(--text);border-bottom:1px solid #f0f3f9}
    .ws-mobile a:last-child{border-bottom:0}
    @media(max-width:900px){.ws-links{display:none}.ws-burger{display:flex}}

    /* ── Hero ───────────────────────────────────────────── */
    .ws-hero{position:relative;color:#fff;overflow:hidden;background:linear-gradient(120deg,var(--navy-950),var(--navy-900) 55%,var(--navy-800))}
    .ws-hero-bg{position:absolute;inset:0}
    .ws-hero-bg img{width:100%;height:100%;object-fit:cover}
    .ws-hero-bg::after{content:"";position:absolute;inset:0;background:linear-gradient(100deg,rgba(6,22,48,.95) 25%,rgba(10,36,80,.82) 58%,rgba(29,92,191,.45) 100%)}
    .ws-hero-glow{position:absolute;width:560px;height:560px;border-radius:50%;background:radial-gradient(circle,rgba(79,150,255,.35),transparent 65%);top:-180px;right:-120px;pointer-events:none}
    .ws-hero-in{position:relative;z-index:2;padding:172px 0 128px;max-width:760px}
    .ws-hero .ws-eyebrow{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.28);backdrop-filter:blur(6px);padding:7px 15px;border-radius:999px;font-size:.8rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;margin-bottom:22px}
    .ws-hero .ws-eyebrow i{width:7px;height:7px;border-radius:50%;background:var(--accent);display:inline-block}
    .ws-hero h1{font-size:clamp(2.35rem,5.4vw,4.1rem);line-height:1.08;font-weight:800;letter-spacing:-.025em;margin-bottom:18px}
    .ws-hero h1 .ws-hl{color:var(--accent)}
    .ws-hero .ws-tagline{font-size:clamp(1.02rem,1.6vw,1.22rem);line-height:1.65;opacity:.92;max-width:620px;margin-bottom:26px}
    .ws-hero-chips{display:flex;gap:9px;flex-wrap:wrap;margin-bottom:34px}
    .ws-chip{display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.09);border:1px solid rgba(255,255,255,.2);padding:7px 14px;border-radius:999px;font-size:.85rem;font-weight:600}
    .ws-chip svg{width:15px;height:15px;opacity:.85}
    .ws-hero-cta{display:flex;gap:13px;flex-wrap:wrap}
    .ws-hero-wave{position:absolute;left:0;right:0;bottom:-1px;z-index:2;line-height:0}
    .ws-hero-wave svg{width:100%;height:56px;display:block}

    /* ── Kepala halaman (berita, direktori) ─────────────── */
    .ws-pagehead{position:relative;color:#fff;background:linear-gradient(120deg,var(--navy-950),var(--navy-900) 55%,var(--navy-800));overflow:hidden}
    .ws-pagehead .ws-hero-glow{width:420px;height:420px;top:-220px}
    .ws-pagehead-in{position:relative;z-index:2;padding:158px 0 74px;max-width:760px}
    .ws-pagehead h1{font-size:clamp(1.9rem,4vw,2.9rem);font-weight:800;letter-spacing:-.02em;line-height:1.15;margin-bottom:12px}
    .ws-pagehead p{opacity:.88;font-size:1.05rem;max-width:620px}
    .ws-crumb{display:flex;gap:8px;align-items:center;font-size:.88rem;font-weight:600;margin-bottom:18px;opacity:.85}
    .ws-crumb a{color:#fff}
    .ws-crumb a:hover{text-decoration:underline}

    /* ── Seksi ──────────────────────────────────────────── */
    .ws-section{padding:92px 0}
    .ws-section-alt{background:#fff}
    .ws-sec-head{max-width:680px;margin-bottom:44px}
    .ws-sec-head.center{margin-left:auto;margin-right:auto;text-align:center}
    .ws-eyebrow-sm{display:inline-flex;align-items:center;gap:9px;color:var(--primary);font-weight:800;font-size:.78rem;letter-spacing:.15em;text-transform:uppercase;margin-bottom:13px}
    .ws-eyebrow-sm::before{content:"";width:26px;height:2px;background:var(--accent);border-radius:2px}
    .ws-sec-head.center .ws-eyebrow-sm::after{content:"";width:26px;height:2px;background:var(--accent);border-radius:2px}
    .ws-sec-head h2{font-size:clamp(1.65rem,3.2vw,2.3rem);color:var(--ink);font-weight:800;letter-spacing:-.02em;line-height:1.2;margin-bottom:10px}
    .ws-sec-head p{color:var(--muted);font-size:1.02rem}
    .ws-sec-head .ws-more{font-weight:700;white-space:nowrap}

    /* ── Statistik ──────────────────────────────────────── */
    .ws-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-top:-58px;position:relative;z-index:5}
    @media(max-width:900px){.ws-stats{grid-template-columns:repeat(2,1fr)}}
    .ws-stat{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:26px 22px;box-shadow:var(--shadow-md);position:relative;overflow:hidden}
    .ws-stat::before{content:"";position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,var(--primary),var(--sky))}
    .ws-stat .ws-num{font-size:2.15rem;font-weight:800;color:var(--ink);letter-spacing:-.02em;line-height:1.1}
    .ws-stat .ws-lbl{color:var(--muted);font-size:.9rem;font-weight:600;margin-top:3px}
    .ws-stat-note{text-align:center;color:var(--muted);font-size:.87rem;margin-top:16px}

    /* ── Kartu berita ───────────────────────────────────── */
    .ws-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
    @media(max-width:900px){.ws-grid{grid-template-columns:1fr}}
    .ws-card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);overflow:hidden;display:flex;flex-direction:column;transition:transform .3s ease,box-shadow .3s ease}
    .ws-card:hover{transform:translateY(-6px);box-shadow:var(--shadow-lg)}
    .ws-card-media{position:relative;overflow:hidden;aspect-ratio:16/10;background:linear-gradient(135deg,var(--navy-800),var(--primary))}
    .ws-card-media img{width:100%;height:100%;object-fit:cover;transition:transform .5s ease}
    .ws-card:hover .ws-card-media img{transform:scale(1.07)}
    .ws-card-media .ws-datechip{position:absolute;left:14px;bottom:14px;background:rgba(255,255,255,.95);color:var(--navy-900);font-size:.76rem;font-weight:800;letter-spacing:.04em;padding:6px 12px;border-radius:999px;text-transform:uppercase}
    .ws-card-media .ws-nofoto{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.65)}
    .ws-card-media .ws-nofoto svg{width:52px;height:52px}
    .ws-card-body{padding:22px;display:flex;flex-direction:column;gap:9px;flex:1}
    .ws-card h3{font-size:1.06rem;color:var(--ink);line-height:1.4;letter-spacing:-.01em;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .ws-card h3 a{color:inherit}
    .ws-card h3 a:hover{color:var(--primary)}
    .ws-card p{color:var(--muted);font-size:.93rem;flex:1;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
    .ws-readmore{display:inline-flex;align-items:center;gap:7px;font-weight:700;font-size:.92rem}
    .ws-readmore svg{width:16px;height:16px;transition:transform .25s ease}
    .ws-readmore:hover svg{transform:translateX(4px)}
    .ws-date{color:var(--accent-dark);font-weight:800;font-size:.8rem;text-transform:uppercase;letter-spacing:.05em}

    /* ── Sambutan & visi misi ───────────────────────────── */
    .ws-sambutan{display:grid;grid-template-columns:.85fr 1.6fr;gap:26px;align-items:stretch}
    @media(max-width:900px){.ws-sambutan{grid-template-columns:1fr}}
    .ws-kepsek{background:linear-gradient(150deg,var(--navy-900),var(--navy-800));color:#fff;border-radius:var(--radius);padding:32px;position:relative;overflow:hidden;display:flex;flex-direction:column;gap:14px}
    .ws-kepsek::after{content:"";position:absolute;width:300px;height:300px;border-radius:50%;background:radial-gradient(circle,rgba(79,150,255,.4),transparent 65%);bottom:-140px;right:-100px}
    .ws-kepsek .ws-quote{width:42px;height:42px;color:var(--accent)}
    .ws-kepsek .ws-jabatan{font-size:.8rem;font-weight:800;letter-spacing:.13em;text-transform:uppercase;color:var(--sky)}
    .ws-kepsek .ws-nama{font-size:1.45rem;font-weight:800;line-height:1.3;letter-spacing:-.01em;position:relative;z-index:1}
    .ws-kepsek .ws-nip{opacity:.8;font-size:.92rem;position:relative;z-index:1}
    .ws-sambutan-teks{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:34px;box-shadow:var(--shadow-sm)}
    .ws-sambutan-teks .ws-pre{font-size:1.03rem;line-height:1.85;color:var(--text)}
    .ws-pre{white-space:pre-line}
    .ws-visimisi{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-top:22px}
    @media(max-width:900px){.ws-visimisi{grid-template-columns:1fr}}
    .ws-vm{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:30px;box-shadow:var(--shadow-sm)}
    .ws-vm .ws-vm-ikon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:16px;background:linear-gradient(135deg,#eaf1fd,#dbe8fc);color:var(--primary)}
    .ws-vm .ws-vm-ikon svg{width:26px;height:26px}
    .ws-vm h3{color:var(--ink);font-size:1.18rem;margin-bottom:9px;letter-spacing:-.01em}
    .ws-vm .ws-pre{color:var(--muted);font-size:.97rem;line-height:1.8}

    /* ── Teks kaya (hasil editor) & lipat "Lihat Selengkapnya" ── */
    .ws-kaya p{margin:0 0 .9em}
    .ws-kaya p:last-child{margin-bottom:0}
    .ws-kaya ul,.ws-kaya ol{margin:.2em 0 1em;padding-left:1.35em}
    .ws-kaya li{margin:.25em 0}
    .ws-kaya h3,.ws-kaya h4{color:var(--ink);margin:.6em 0 .35em;line-height:1.35}
    .ws-kaya h4{font-size:1.06rem}
    .ws-kaya a{color:var(--primary);text-decoration:underline}
    .ws-kaya blockquote{border-left:3px solid var(--sky);padding-left:14px;margin:.6em 0;color:var(--muted)}
    .ws-sambutan-teks .ws-kaya{font-size:1.03rem;line-height:1.85;color:var(--text)}
    .ws-vm .ws-kaya{color:var(--muted);font-size:.97rem;line-height:1.8}
    .ws-lipat{position:relative;max-height:250px;overflow:hidden}
    .ws-lipat::after{content:"";position:absolute;left:0;right:0;bottom:0;height:64px;background:linear-gradient(transparent,var(--card));pointer-events:none}
    .ws-lipat.terbuka{max-height:none}
    .ws-lipat.terbuka::after{display:none}
    .ws-lipat-tombol{margin-top:12px;background:none;border:none;color:var(--primary);font-weight:700;cursor:pointer;padding:0;font-size:.95rem;font-family:inherit}
    .ws-lipat-tombol:hover{text-decoration:underline}

    /* ── Spanduk aplikasi MySchool Mobile ───────────────── */
    .ws-aplikasi{background:linear-gradient(140deg,var(--navy-900),var(--navy-800));border-radius:calc(var(--radius) + 6px);color:#fff;padding:34px 38px;display:grid;grid-template-columns:1.6fr .8fr;gap:28px;align-items:center;position:relative;overflow:hidden;box-shadow:var(--shadow-sm)}
    .ws-aplikasi::after{content:"";position:absolute;width:340px;height:340px;border-radius:50%;background:radial-gradient(circle,rgba(79,150,255,.35),transparent 65%);top:-150px;right:-100px;pointer-events:none}
    .ws-aplikasi h2{font-size:1.5rem;line-height:1.25;margin:.3em 0 .45em;color:#fff;letter-spacing:-.01em}
    .ws-aplikasi-teks{position:relative;z-index:1}
    .ws-aplikasi-teks p{color:rgba(255,255,255,.86);line-height:1.7;max-width:56ch;margin:0}
    .ws-aplikasi-fitur{list-style:none;margin:14px 0;padding:0;display:grid;gap:8px}
    .ws-aplikasi-fitur li{position:relative;padding-left:28px;color:rgba(255,255,255,.94);font-size:.95rem}
    .ws-aplikasi-fitur li::before{content:"\2713";position:absolute;left:0;top:1px;width:18px;height:18px;border-radius:50%;background:var(--sky);color:#06203f;font-size:.64rem;font-weight:800;text-align:center;line-height:18px}
    .ws-aplikasi-catatan{font-size:.85rem;opacity:.75}
    .ws-aplikasi-visual{display:flex;justify-content:center;position:relative;z-index:1}
    .ws-phone{width:152px;height:304px;background:#0b1c33;border:3px solid rgba(255,255,255,.25);border-radius:30px;padding:8px;box-shadow:0 22px 44px rgba(2,10,24,.45);transform:rotate(4deg)}
    .ws-phone-layar{background:linear-gradient(170deg,#f4f8ff,#e3edfd);border-radius:23px;height:100%;padding:14px 11px;display:flex;flex-direction:column;gap:9px;overflow:hidden}
    .ws-phone-kepala{display:flex;gap:8px;align-items:center}
    .ws-phone-logo{width:28px;height:28px;border-radius:8px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.85rem;flex-shrink:0}
    .ws-phone-kepala strong{display:block;color:var(--ink);font-size:.8rem;line-height:1.1}
    .ws-phone-kepala small{color:var(--muted);font-size:.6rem;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:82px}
    .ws-phone-kartu{background:var(--primary);color:#fff;border-radius:12px;padding:10px}
    .ws-phone-kartu small{display:block;opacity:.85;font-size:.56rem;text-transform:uppercase;letter-spacing:.06em}
    .ws-phone-kartu strong{font-size:.72rem;line-height:1.35;display:block;margin-top:2px}
    .ws-phone-grid{display:grid;grid-template-columns:1fr 1fr;gap:7px}
    .ws-phone-grid span{background:#fff;border:1px solid var(--line);border-radius:10px;height:40px}
    .ws-phone-baris{background:#fff;border:1px solid var(--line);border-radius:9px;height:28px}
    @media(max-width:900px){.ws-aplikasi{grid-template-columns:1fr;padding:28px 22px}.ws-phone{transform:none;height:280px}}

    /* ── Galeri ─────────────────────────────────────────── */
    .ws-galeri{display:grid;grid-template-columns:repeat(4,1fr);gap:16px}
    @media(max-width:900px){.ws-galeri{grid-template-columns:repeat(2,1fr)}}
    .ws-galeri figure{border-radius:16px;overflow:hidden;position:relative;aspect-ratio:4/3;cursor:zoom-in;background:var(--navy-900)}
    .ws-galeri img{width:100%;height:100%;object-fit:cover;transition:transform .5s ease}
    .ws-galeri figure:hover img{transform:scale(1.08)}
    .ws-galeri figcaption{position:absolute;left:0;right:0;bottom:0;background:linear-gradient(transparent,rgba(6,22,48,.88));color:#fff;font-size:.86rem;font-weight:600;padding:30px 14px 12px;opacity:0;transform:translateY(6px);transition:all .3s ease}
    .ws-galeri figure:hover figcaption{opacity:1;transform:none}
    .ws-galeri figure:focus-visible{outline:3px solid var(--sky);outline-offset:2px}
    .ws-album-kartu{display:block;border-radius:16px;overflow:hidden;position:relative;aspect-ratio:4/3;background:var(--navy-900);text-decoration:none}
    .ws-album-kartu img{width:100%;height:100%;object-fit:cover;transition:transform .5s ease}
    .ws-album-kartu:hover img{transform:scale(1.08)}
    .ws-album-lencana{position:absolute;top:12px;left:12px;background:var(--sky);color:#06203f;font-size:.72rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;padding:5px 10px;border-radius:999px;z-index:2}
    .ws-album-info{position:absolute;left:0;right:0;bottom:0;background:linear-gradient(transparent,rgba(6,22,48,.9));color:#fff;padding:34px 14px 12px;display:block}
    .ws-album-info strong{display:block;font-size:1rem;line-height:1.3}
    .ws-album-info small{opacity:.88;font-weight:600}

    /* ── Layanan ────────────────────────────────────────── */
    .ws-layanan{display:grid;grid-template-columns:repeat(3,1fr);gap:22px}
    @media(max-width:900px){.ws-layanan{grid-template-columns:1fr}}
    .ws-layanan .ws-card{padding:30px;gap:0}
    .ws-layanan .ws-ikon{width:58px;height:58px;border-radius:16px;display:flex;align-items:center;justify-content:center;margin-bottom:18px;color:#fff}
    .ws-layanan .ws-ikon svg{width:28px;height:28px}
    .ws-ikon-biru{background:linear-gradient(135deg,var(--primary),var(--sky))}
    .ws-ikon-ambar{background:linear-gradient(135deg,var(--accent),#f7bc45)}
    .ws-ikon-navy{background:linear-gradient(135deg,var(--navy-800),var(--navy-950))}
    .ws-layanan h3{font-size:1.12rem;color:var(--ink);margin-bottom:8px}
    .ws-layanan p{color:var(--muted);font-size:.95rem;margin-bottom:16px;flex:1}

    /* ── Kontak & CTA ───────────────────────────────────── */
    .ws-kontak{display:grid;grid-template-columns:1.25fr .9fr;gap:22px;align-items:stretch}
    @media(max-width:900px){.ws-kontak{grid-template-columns:1fr}}
    .ws-kontak-list{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:32px;box-shadow:var(--shadow-sm);display:flex;flex-direction:column;gap:20px}
    .ws-kontak-item{display:flex;gap:16px;align-items:flex-start}
    .ws-kontak-item .ws-ikon{width:48px;height:48px;flex:none;border-radius:13px;background:#eef3fb;color:var(--primary);display:flex;align-items:center;justify-content:center}
    .ws-kontak-item .ws-ikon svg{width:22px;height:22px}
    .ws-kontak-item h4{color:var(--ink);font-size:.98rem;margin-bottom:2px}
    .ws-kontak-item p{color:var(--muted);font-size:.95rem}
    .ws-cta{background:linear-gradient(140deg,var(--navy-900),var(--primary));border-radius:var(--radius);color:#fff;padding:34px;position:relative;overflow:hidden;display:flex;flex-direction:column;gap:12px}
    .ws-cta::after{content:"";position:absolute;width:280px;height:280px;border-radius:50%;background:radial-gradient(circle,rgba(246,166,9,.35),transparent 65%);top:-120px;right:-80px}
    .ws-cta h3{font-size:1.5rem;font-weight:800;letter-spacing:-.01em;line-height:1.3;position:relative;z-index:1}
    .ws-cta p{opacity:.9;position:relative;z-index:1}
    .ws-cta .ws-btn{align-self:flex-start;position:relative;z-index:1;margin-top:6px}

    /* ── Artikel & paginasi ─────────────────────────────── */
    .ws-article{background:#fff;border:1px solid var(--line);border-radius:20px;padding:clamp(26px,4.5vw,52px);box-shadow:var(--shadow-sm)}
    .ws-article h1{font-size:clamp(1.7rem,3.6vw,2.5rem);color:var(--ink);font-weight:800;letter-spacing:-.02em;line-height:1.22;margin:10px 0 14px}
    .ws-article .ws-meta-artikel{display:flex;gap:16px;flex-wrap:wrap;color:var(--muted);font-size:.9rem;font-weight:600;margin-bottom:24px}
    .ws-article img.ws-cover{width:100%;max-height:440px;object-fit:cover;border-radius:14px;margin-bottom:28px}
    .ws-article .ws-isi{white-space:pre-line;font-size:1.06rem;line-height:1.9;color:var(--text)}
    .ws-pagination{display:flex;gap:8px;justify-content:center;margin-top:34px;flex-wrap:wrap}
    .ws-pagination a,.ws-pagination span{padding:9px 17px;border:1px solid var(--line);border-radius:999px;background:#fff;font-weight:700;font-size:.92rem;color:var(--text)}
    .ws-pagination a:hover{border-color:var(--primary);color:var(--primary)}
    .ws-pagination .active{background:var(--primary);color:#fff;border-color:var(--primary)}
    .ws-pagination .mati{opacity:.45}
    .ws-empty{color:var(--muted);background:#fff;border:1.5px dashed var(--line);border-radius:var(--radius);padding:38px 22px;text-align:center;font-weight:600}

    /* ── Direktori ──────────────────────────────────────── */
    .ws-dir-card{padding:28px;align-items:flex-start;gap:14px}
    .ws-dir-card img{width:64px;height:64px;object-fit:contain}
    .ws-dir-card h3{font-size:1.15rem}
    .ws-dir-card p{-webkit-line-clamp:unset}

    /* ── Footer ─────────────────────────────────────────── */
    .ws-footer{background:var(--navy-950);color:#aebdd6;padding:74px 0 0;margin-top:0}
    .ws-footer-grid{display:grid;grid-template-columns:1.5fr 1fr 1fr 1.25fr;gap:38px;padding-bottom:48px}
    @media(max-width:900px){.ws-footer-grid{grid-template-columns:1fr 1fr}}
    @media(max-width:560px){.ws-footer-grid{grid-template-columns:1fr}}
    .ws-footer h4{color:#fff;font-size:1rem;margin-bottom:16px;letter-spacing:-.01em}
    .ws-footer p{font-size:.93rem;line-height:1.75}
    .ws-footer ul{list-style:none;display:flex;flex-direction:column;gap:10px;font-size:.93rem}
    .ws-footer a{color:#aebdd6;transition:color .2s}
    .ws-footer a:hover{color:#fff}
    .ws-footer .ws-brand{color:#fff;margin-bottom:16px}
    .ws-footer .ws-brand small{opacity:.65}
    .ws-footer-kontak li{display:flex;gap:10px;align-items:flex-start}
    .ws-footer-kontak svg{width:17px;height:17px;flex:none;margin-top:4px;color:var(--sky)}
    .ws-copy{border-top:1px solid rgba(255,255,255,.1);padding:20px 0;font-size:.87rem;color:#7e90b3;display:flex;gap:10px;flex-wrap:wrap;justify-content:space-between}

    /* ── Dokumen publik ─────────────────────────────────── */
    .ws-doc-group{margin-bottom:30px}
    .ws-doc-group:last-child{margin-bottom:0}
    .ws-doc-title{font-size:1.05rem;color:var(--ink);margin-bottom:13px;display:flex;align-items:center;gap:10px}
    .ws-doc-title::after{content:"";flex:1;height:1px;background:var(--line)}
    .ws-doc-list{display:flex;flex-direction:column;gap:10px}
    .ws-doc-item{display:flex;align-items:center;gap:15px;background:var(--card);border:1px solid var(--line);border-radius:15px;padding:15px 18px;box-shadow:var(--shadow-sm)}
    .ws-doc-icon{flex:none;width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center}
    .ws-doc-icon svg{width:22px;height:22px}
    .ws-doc-icon.is-pdf{background:#fdecec;color:#c23333}
    .ws-doc-icon.is-img{background:#e8f1fd;color:var(--primary)}
    .ws-doc-main{flex:1;min-width:0}
    .ws-doc-main strong{display:block;color:var(--ink);font-size:.99rem;line-height:1.35}
    .ws-doc-main small{color:var(--muted);font-size:.86rem}
    .ws-doc-actions{flex:none;display:flex;gap:8px}
    .ws-doc-btn{display:inline-flex;align-items:center;padding:8px 16px;border-radius:999px;border:1.5px solid var(--line);color:var(--text);font-weight:700;font-size:.87rem;text-decoration:none;transition:all .2s ease}
    .ws-doc-btn:hover{border-color:var(--primary);color:var(--primary)}
    .ws-doc-btn.is-primary{background:var(--primary);border-color:var(--primary);color:#fff}
    .ws-doc-btn.is-primary:hover{background:var(--navy-900);border-color:var(--navy-900);color:#fff}
    @media (max-width:640px){.ws-doc-item{flex-wrap:wrap}.ws-doc-actions{width:100%}.ws-doc-btn{flex:1;justify-content:center}}

    /* ── Jadwal hari ini ────────────────────────────────── */
    .ws-jam-chip{display:inline-flex;align-items:center;gap:8px;background:#eef3fb;color:var(--navy-900);font-weight:800;font-size:.88rem;padding:8px 15px;border-radius:999px}
    .ws-jam-chip i{width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;animation:wsKedip 1.6s infinite}
    @keyframes wsKedip{0%,100%{opacity:1}50%{opacity:.3}}
    .ws-jadwal{background:var(--card);border:1px solid var(--line);border-radius:20px;box-shadow:var(--shadow-md);overflow:hidden}
    .ws-pills{display:flex;gap:9px;flex-wrap:wrap;padding:20px 22px;border-bottom:1px solid var(--line);background:#fbfcfe}
    .ws-pill{border:1.5px solid var(--line);background:#fff;color:var(--text);font-family:inherit;font-weight:700;font-size:.9rem;padding:9px 18px;border-radius:999px;cursor:pointer;transition:all .2s ease}
    .ws-pill:hover{border-color:var(--primary);color:var(--primary)}
    .ws-pill.aktif{background:var(--primary);border-color:var(--primary);color:#fff;box-shadow:0 8px 18px -8px rgba(29,92,191,.6)}
    .ws-panel{display:none;padding:8px 22px 20px}
    .ws-panel.aktif{display:block}
    .ws-panel-head{display:flex;gap:10px;flex-wrap:wrap;align-items:baseline;padding:16px 2px 10px}
    .ws-panel-head h3{color:var(--ink);font-size:1.12rem;letter-spacing:-.01em}
    .ws-panel-head span{color:var(--muted);font-size:.9rem;font-weight:600}
    .ws-row{display:flex;gap:16px;align-items:center;padding:12px 14px;border-radius:13px;border-left:4px solid transparent}
    .ws-row + .ws-row{border-top:1px solid #f0f3f9}
    .ws-row .ws-waktu{flex:none;width:118px;font-weight:800;color:var(--navy-900);font-size:.92rem;font-variant-numeric:tabular-nums}
    .ws-row .ws-titik{flex:none;width:10px;height:10px;border-radius:50%;background:var(--sky)}
    .ws-row .ws-isi{flex:1;min-width:0}
    .ws-row .ws-isi strong{color:var(--ink);font-size:.99rem;display:block;line-height:1.35}
    .ws-row .ws-isi span{color:var(--muted);font-size:.87rem}
    .ws-row.istirahat{background:#faf7ec}
    .ws-row.istirahat .ws-titik{background:var(--accent)}
    .ws-row.istirahat .ws-isi strong{color:#8a6108;font-weight:700}
    .ws-row.kegiatan .ws-titik{background:#22c55e}
    .ws-row.berlangsung{background:linear-gradient(90deg,#eaf3ff,#f4f9ff);border-left-color:var(--primary)}
    .ws-lencana-live{flex:none;background:#dc2626;color:#fff;font-size:.7rem;font-weight:800;letter-spacing:.07em;text-transform:uppercase;padding:5px 11px;border-radius:999px}
    @media(max-width:560px){
      .ws-panel{padding:6px 12px 16px}.ws-pills{padding:16px 14px}
      .ws-row{flex-wrap:wrap;gap:4px 12px;align-items:flex-start}
      .ws-row .ws-titik{order:1;margin-top:6px}
      .ws-row .ws-isi{order:2}
      .ws-row .ws-lencana-live{order:3}
      .ws-row .ws-waktu{order:4;flex:0 0 100%;width:auto;padding-left:22px;font-size:.85rem;color:var(--muted)}
    }
    .ws-libur{background:linear-gradient(140deg,var(--navy-900),var(--primary));border-radius:20px;color:#fff;padding:44px 34px;text-align:center;box-shadow:var(--shadow-md)}
    .ws-libur svg{width:54px;height:54px;margin:0 auto 16px;color:var(--accent)}
    .ws-libur h3{font-size:1.55rem;font-weight:800;letter-spacing:-.01em;margin-bottom:8px}
    .ws-libur p{opacity:.9;max-width:520px;margin:0 auto}

    /* ── Keunggulan ─────────────────────────────────────── */
    .ws-unggul{display:grid;grid-template-columns:repeat(4,1fr);gap:18px}
    @media(max-width:900px){.ws-unggul{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:560px){.ws-unggul{grid-template-columns:1fr}}
    .ws-unggul-item{display:flex;gap:15px;align-items:flex-start;background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:22px;box-shadow:var(--shadow-sm)}
    .ws-unggul-item .ws-ikon{width:48px;height:48px;flex:none;border-radius:13px;background:linear-gradient(135deg,#eaf1fd,#dbe8fc);color:var(--primary);display:flex;align-items:center;justify-content:center}
    .ws-unggul-item .ws-ikon svg{width:23px;height:23px}
    .ws-unggul-item h3{color:var(--ink);font-size:1rem;line-height:1.35;margin-bottom:3px}
    .ws-unggul-item p{color:var(--muted);font-size:.88rem;line-height:1.55}

    /* ── Agenda ─────────────────────────────────────────── */
    .ws-agenda{display:flex;flex-direction:column;gap:14px}
    .ws-agenda-item{display:flex;gap:20px;align-items:center;background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:18px 24px;box-shadow:var(--shadow-sm);transition:transform .25s ease,box-shadow .25s ease}
    .ws-agenda-item:hover{transform:translateX(5px);box-shadow:var(--shadow-md)}
    .ws-agenda-tgl{flex:none;width:74px;text-align:center;background:linear-gradient(150deg,var(--navy-900),var(--primary));color:#fff;border-radius:14px;padding:11px 4px}
    .ws-agenda-tgl .ws-d{font-size:1.55rem;font-weight:800;line-height:1}
    .ws-agenda-tgl .ws-m{font-size:.74rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;opacity:.85;margin-top:3px}
    .ws-agenda-isi{flex:1;min-width:0}
    .ws-agenda-isi h3{color:var(--ink);font-size:1.03rem;line-height:1.4;margin-bottom:2px}
    .ws-agenda-isi p{color:var(--muted);font-size:.9rem}
    .ws-lencana{flex:none;font-size:.74rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;padding:6px 13px;border-radius:999px}
    .ws-lencana-libur{background:#fdecec;color:#b3261e}
    .ws-lencana-kegiatan{background:#e8f5e9;color:#1e7d32}
    @media(max-width:560px){.ws-agenda-item{flex-wrap:wrap;gap:14px}}

    /* ── Dewan guru ─────────────────────────────────────── */
    .ws-guru{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
    @media(max-width:900px){.ws-guru{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:480px){.ws-guru{grid-template-columns:1fr}}
    .ws-guru-card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:26px 20px;text-align:center;box-shadow:var(--shadow-sm);transition:transform .3s ease,box-shadow .3s ease}
    .ws-guru-card:hover{transform:translateY(-5px);box-shadow:var(--shadow-md)}
    .ws-guru-card img,.ws-guru-card .ws-inisial{width:88px;height:88px;border-radius:50%;margin:0 auto 14px;object-fit:cover}
    .ws-guru-card .ws-inisial{display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--primary),var(--sky));color:#fff;font-size:1.7rem;font-weight:800}
    .ws-guru-card h3{color:var(--ink);font-size:1rem;line-height:1.4}
    .ws-guru-card .ws-jbt{color:var(--primary);font-weight:700;font-size:.87rem;margin-top:3px}
    .ws-guru-card .ws-peg{color:var(--muted);font-size:.82rem}

    /* ── Tombol melayang ────────────────────────────────── */
    .ws-float-wa{position:fixed;right:22px;bottom:22px;z-index:120;width:56px;height:56px;border-radius:50%;background:#25d366;color:#fff;display:flex;align-items:center;justify-content:center;box-shadow:0 12px 28px -8px rgba(37,211,102,.65);transition:transform .25s ease}
    .ws-float-wa:hover{transform:scale(1.08)}
    .ws-float-wa svg{width:29px;height:29px}
    .ws-keatas{position:fixed;right:22px;bottom:90px;z-index:120;width:46px;height:46px;border-radius:50%;background:var(--navy-900);color:#fff;border:0;display:flex;align-items:center;justify-content:center;cursor:pointer;box-shadow:var(--shadow-md);opacity:0;pointer-events:none;transition:all .3s ease}
    .ws-keatas.tampil{opacity:1;pointer-events:auto}
    .ws-keatas svg{width:20px;height:20px}

    /* ── Lightbox & animasi ─────────────────────────────── */
    .ws-lightbox{position:fixed;inset:0;z-index:200;background:rgba(4,13,30,.92);backdrop-filter:blur(6px);display:none;align-items:center;justify-content:center;padding:26px}
    .ws-lightbox.open{display:flex}
    .ws-lightbox figure{max-width:min(1000px,94vw);text-align:center}
    .ws-lightbox img{max-height:80vh;width:auto;max-width:100%;margin:0 auto;border-radius:12px;box-shadow:var(--shadow-lg)}
    .ws-lightbox figcaption{color:#dbe6f7;margin-top:14px;font-weight:600}
    .ws-lightbox .ws-tutup{position:absolute;top:20px;right:24px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.3);color:#fff;width:46px;height:46px;border-radius:50%;font-size:1.25rem;cursor:pointer}
    [data-reveal]{opacity:0;transform:translateY(26px);transition:opacity .7s ease,transform .7s cubic-bezier(.22,.8,.32,1)}
    [data-reveal].in{opacity:1;transform:none}
    @media(prefers-reduced-motion:reduce){[data-reveal]{opacity:1;transform:none;transition:none}html{scroll-behavior:auto}}
  </style>
  @yield('page-style')
</head>
<body>
  <nav class="ws-nav @yield('nav-modifier')" id="wsNav">
    <div class="ws-container ws-nav-in">
      @isset($web)
        {{-- Di landing sendiri, tautan seksi memakai jangkar murni (#...) agar
             bergulir halus TANPA memuat ulang halaman; dari halaman lain
             (berita) tautan harus alamat penuh + jangkar. --}}
        @php $diLanding = request()->routeIs('web-sekolah.tampil'); @endphp
        <a class="ws-brand" href="{{ route('web-sekolah.tampil', $web->slug) }}">
          @if ($punyaLogo ?? false)
            <img src="{{ route('web-sekolah.logo', $web->slug) }}" alt="Logo {{ $namaSekolah }}">
          @else
            <span class="ws-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 9.5 12 4 2 9.5l10 5.5 10-5.5z"/><path d="M6 11.8V16c0 1.7 2.7 3 6 3s6-1.3 6-3v-4.2"/><path d="M22 9.5V16"/></svg></span>
          @endif
          <span>{{ $namaSekolah }}<small>{{ $sekolah->npsn ? 'NPSN ' . $sekolah->npsn : $sekolah->jenjang . ' · Laman Sekolah' }}</small></span>
        </a>
        <div class="ws-links">
          <a href="{{ $diLanding ? '#beranda' : route('web-sekolah.tampil', $web->slug) }}">Beranda</a>
          <a href="{{ $diLanding ? '#sambutan' : route('web-sekolah.tampil', $web->slug) . '#sambutan' }}">Profil</a>
          <a href="{{ $diLanding ? '#berita' : route('web-sekolah.tampil', $web->slug) . '#berita' }}">Berita</a>
          <a href="{{ $diLanding ? '#galeri' : route('web-sekolah.tampil', $web->slug) . '#galeri' }}">Galeri</a>
          <a href="{{ $diLanding ? '#jadwal' : route('web-sekolah.tampil', $web->slug) . '#jadwal' }}">Jadwal</a>
          <a href="{{ $diLanding ? '#dokumen' : route('web-sekolah.tampil', $web->slug) . '#dokumen' }}">Dokumen</a>
          <a href="{{ $diLanding ? '#kontak' : route('web-sekolah.tampil', $web->slug) . '#kontak' }}">Kontak</a>
          @auth
            <a class="ws-btn" href="{{ route('home') }}">Dashboard</a>
          @else
            <a class="ws-btn" href="{{ route('login') }}">Masuk</a>
          @endauth
        </div>
        <button class="ws-burger" id="wsBurger" aria-label="Buka menu" aria-expanded="false">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
      @else
        <span class="ws-brand">
          <span class="ws-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 9.5 12 4 2 9.5l10 5.5 10-5.5z"/><path d="M6 11.8V16c0 1.7 2.7 3 6 3s6-1.3 6-3v-4.2"/><path d="M22 9.5V16"/></svg></span>
          <span>Web Sekolah<small>Portal Website Sekolah</small></span>
        </span>
        <div class="ws-links">
          @auth
            <a class="ws-btn" href="{{ route('home') }}">Dashboard</a>
          @else
            <a class="ws-btn" href="{{ route('login') }}">Masuk</a>
          @endauth
        </div>
      @endisset
        <button class="ws-tema" id="wsTema" type="button" aria-label="Aktifkan mode gelap atau terang" title="Mode gelap / terang">
          <svg class="ikon-bulan" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
          <svg class="ikon-matahari" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/></svg>
        </button>
    </div>
    @isset($web)
      <div class="ws-mobile" id="wsMobile">
        <a href="{{ $diLanding ? '#beranda' : route('web-sekolah.tampil', $web->slug) }}">Beranda</a>
        <a href="{{ $diLanding ? '#sambutan' : route('web-sekolah.tampil', $web->slug) . '#sambutan' }}">Profil</a>
        <a href="{{ $diLanding ? '#berita' : route('web-sekolah.tampil', $web->slug) . '#berita' }}">Berita</a>
        <a href="{{ $diLanding ? '#galeri' : route('web-sekolah.tampil', $web->slug) . '#galeri' }}">Galeri</a>
        <a href="{{ $diLanding ? '#jadwal' : route('web-sekolah.tampil', $web->slug) . '#jadwal' }}">Jadwal</a>
        <a href="{{ $diLanding ? '#dokumen' : route('web-sekolah.tampil', $web->slug) . '#dokumen' }}">Dokumen</a>
        <a href="{{ $diLanding ? '#kontak' : route('web-sekolah.tampil', $web->slug) . '#kontak' }}">Kontak</a>
        @auth
          <a href="{{ route('home') }}">Dashboard</a>
        @else
          <a href="{{ route('login') }}">Masuk</a>
        @endauth
      </div>
    @endisset
  </nav>

  @yield('content')

  <footer class="ws-footer">
    <div class="ws-container">
      @isset($web)
        <div class="ws-footer-grid">
          <div>
            <span class="ws-brand">
              @if ($punyaLogo ?? false)
                <img src="{{ route('web-sekolah.logo', $web->slug) }}" alt="Logo {{ $namaSekolah }}">
              @else
                <span class="ws-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 9.5 12 4 2 9.5l10 5.5 10-5.5z"/><path d="M6 11.8V16c0 1.7 2.7 3 6 3s6-1.3 6-3v-4.2"/><path d="M22 9.5V16"/></svg></span>
              @endif
              <span>{{ $namaSekolah }}<small>{{ $sekolah->npsn ? 'NPSN ' . $sekolah->npsn : $sekolah->jenjang . ' · Laman Sekolah' }}</small></span>
            </span>
            <p>{{ $web->tagline ?? 'Website resmi ' . $namaSekolah . ' — pusat informasi, pengumuman, dan layanan daring sekolah.' }}</p>
            <p style="margin-top:12px">NPSN {{ $sekolah->npsn }}
              @if ($web->akreditasi) &middot; Akreditasi {{ $web->akreditasi }} @endif
              @if ($web->tahun_berdiri) &middot; Berdiri {{ $web->tahun_berdiri }} @endif
            </p>
          </div>
          <div>
            <h4>Navigasi</h4>
            <ul>
              <li><a href="{{ ($diLanding ?? false) ? '#beranda' : route('web-sekolah.tampil', $web->slug) }}">Beranda</a></li>
              <li><a href="{{ ($diLanding ?? false) ? '#sambutan' : route('web-sekolah.tampil', $web->slug) . '#sambutan' }}">Sambutan Kepala Sekolah</a></li>
              <li><a href="{{ ($diLanding ?? false) ? '#visi-misi' : route('web-sekolah.tampil', $web->slug) . '#visi-misi' }}">Visi &amp; Misi</a></li>
              <li><a href="{{ ($diLanding ?? false) ? '#berita' : route('web-sekolah.tampil', $web->slug) . '#berita' }}">Berita &amp; Pengumuman</a></li>
              <li><a href="{{ ($diLanding ?? false) ? '#galeri' : route('web-sekolah.tampil', $web->slug) . '#galeri' }}">Galeri Kegiatan</a></li>
              <li><a href="{{ ($diLanding ?? false) ? '#jadwal' : route('web-sekolah.tampil', $web->slug) . '#jadwal' }}">Jadwal Hari Ini</a></li>
              <li><a href="{{ ($diLanding ?? false) ? '#dokumen' : route('web-sekolah.tampil', $web->slug) . '#dokumen' }}">Dokumen Publik</a></li>
            </ul>
          </div>
          <div>
            <h4>Layanan</h4>
            <ul>
              <li><a href="{{ route('validasi-kelulusan.index') }}">Cek Kelulusan / SKHU</a></li>
              <li><a href="{{ route('web-sekolah.berita.index', $web->slug) }}">Berita Sekolah</a></li>
              <li><a href="{{ route('login') }}">Portal myschool</a></li>
            </ul>
          </div>
          <div>
            <h4>Kontak</h4>
            <ul class="ws-footer-kontak">
              @if ($detail?->alamat_sekolah)
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg><span>{{ $detail->alamat_sekolah }}@if ($detail->village), {{ $detail->village->name ?? '' }}@endif @if ($detail->district), Kec. {{ $detail->district->name ?? '' }}@endif @if ($detail->regency), {{ $detail->regency->name ?? '' }}@endif</span></li>
              @endif
              @if ($detail?->no_telp)
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.13.96.36 1.9.7 2.8a2 2 0 0 1-.45 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.45c.9.34 1.84.57 2.8.7A2 2 0 0 1 22 16.9z"/></svg><span>{{ $detail->no_telp }}</span></li>
              @endif
              @if ($detail?->email)
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg><span>{{ $detail->email }}</span></li>
              @endif
              @if ($detail?->kepala_sekolah)
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg><span>Kepala Sekolah: {{ $detail->kepala_sekolah }}</span></li>
              @endif
            </ul>
          </div>
        </div>
      @else
        <div style="padding-bottom:40px">
          <h4 style="color:#fff;margin-bottom:8px">Web Sekolah</h4>
          <p>Portal publik sekolah-sekolah pengguna myschool.</p>
        </div>
      @endisset
      <div class="ws-copy">
        <span>&copy; {{ date('Y') }} {{ $namaSekolah ?? 'Web Sekolah' }}. Hak cipta dilindungi.</span>
        <span>Didukung oleh <a href="{{ route('login') }}" style="color:#cfe0f7;font-weight:700">myschool</a></span>
      </div>
    </div>
  </footer>

  @isset($web)
    @if ($detail?->no_telp)
      @php $waNomor = preg_replace('/\D+/', '', $detail->no_telp); if (str_starts_with($waNomor, '0')) $waNomor = '62' . substr($waNomor, 1); @endphp
      <a class="ws-float-wa" href="https://wa.me/{{ $waNomor }}" target="_blank" rel="noopener" aria-label="Hubungi sekolah via WhatsApp" title="Hubungi via WhatsApp">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.5 14.4c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.48-1.77-1.65-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.5 0 1.47 1.07 2.9 1.22 3.1.15.2 2.11 3.22 5.1 4.51.71.31 1.27.49 1.7.63.72.23 1.37.2 1.88.12.57-.09 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35zM12.04 21.79h-.01a9.87 9.87 0 0 1-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.85 9.85 0 0 1-1.51-5.26c0-5.45 4.44-9.88 9.9-9.88a9.83 9.83 0 0 1 9.88 9.89c0 5.45-4.44 9.88-9.89 9.88zm8.42-18.3A11.82 11.82 0 0 0 12.04 0C5.5 0 .16 5.34.16 11.9c0 2.1.55 4.14 1.59 5.95L.06 24l6.3-1.65a11.9 11.9 0 0 0 5.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.9 0-3.18-1.24-6.16-3.48-8.4z"/></svg>
      </a>
    @endif
  @endisset
  <button class="ws-keatas" id="wsKeatas" aria-label="Kembali ke atas">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M6 11l6-6 6 6"/></svg>
  </button>

  <div class="ws-lightbox" id="wsLightbox" role="dialog" aria-modal="true" aria-label="Pratinjau foto galeri">
    <button class="ws-tutup" id="wsLightboxTutup" aria-label="Tutup">&times;</button>
    <figure>
      <img id="wsLightboxImg" src="" alt="">
      <figcaption id="wsLightboxCap"></figcaption>
    </figure>
  </div>

  <script>
    (function () {
      var nav = document.getElementById('wsNav');
      function onScroll() { if (nav) nav.classList.toggle('scrolled', window.scrollY > 30); }
      window.addEventListener('scroll', onScroll, { passive: true }); onScroll();

      var burger = document.getElementById('wsBurger');
      var mobile = document.getElementById('wsMobile');
      if (burger && mobile) {
        burger.addEventListener('click', function () {
          var open = mobile.classList.toggle('open');
          burger.setAttribute('aria-expanded', open ? 'true' : 'false');
          if (open && nav) nav.classList.add('scrolled');
        });
        mobile.querySelectorAll('a').forEach(function (a) {
          a.addEventListener('click', function () { mobile.classList.remove('open'); });
        });
      }

      var revealIO = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (e.isIntersecting) { e.target.classList.add('in'); revealIO.unobserve(e.target); }
        });
      }, { threshold: 0.12 });
      document.querySelectorAll('[data-reveal]').forEach(function (el) { revealIO.observe(el); });

      function formatId(n) { return n.toLocaleString('id-ID'); }
      var countIO = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (!e.isIntersecting) return;
          countIO.unobserve(e.target);
          var target = parseInt(e.target.getAttribute('data-count'), 10) || 0;
          var mulai = null, durasi = 1400;
          function langkah(ts) {
            if (!mulai) mulai = ts;
            var p = Math.min((ts - mulai) / durasi, 1);
            var nilai = Math.round(target * (1 - Math.pow(1 - p, 3)));
            e.target.textContent = formatId(nilai);
            if (p < 1) requestAnimationFrame(langkah);
          }
          requestAnimationFrame(langkah);
        });
      }, { threshold: 0.4 });
      document.querySelectorAll('[data-count]').forEach(function (el) { countIO.observe(el); });

      // Scrollspy: tandai tautan nav yang seksinya sedang terlihat.
      // Hanya tautan jangkar murni (#...) yang dipetakan — tautan itu
      // hanya ada di landing, jadi di halaman lain blok ini diam.
      var petaSpy = {};
      document.querySelectorAll('.ws-links a[href^="#"], .ws-mobile a[href^="#"]').forEach(function (a) {
        var id = a.getAttribute('href').slice(1);
        if (id) (petaSpy[id] = petaSpy[id] || []).push(a);
      });
      var idSpy = Object.keys(petaSpy);
      if (idSpy.length) {
        var spyIO = new IntersectionObserver(function (entries) {
          entries.forEach(function (e) {
            if (!e.isIntersecting) return;
            idSpy.forEach(function (id) {
              petaSpy[id].forEach(function (a) { a.classList.toggle('aktif', id === e.target.id); });
            });
          });
        }, { rootMargin: '-38% 0px -55% 0px' });
        idSpy.forEach(function (id) {
          var el = document.getElementById(id);
          if (el) spyIO.observe(el);
        });
      }

      var lb = document.getElementById('wsLightbox');
      var lbImg = document.getElementById('wsLightboxImg');
      var lbCap = document.getElementById('wsLightboxCap');
      function tutupLb() { lb.classList.remove('open'); lbImg.src = ''; }
      document.querySelectorAll('.ws-galeri figure').forEach(function (fig) {
        fig.setAttribute('tabindex', '0');
        function buka() {
          var img = fig.querySelector('img');
          if (!img) return;
          lbImg.src = img.src; lbImg.alt = img.alt || '';
          var cap = fig.querySelector('figcaption');
          lbCap.textContent = cap ? cap.textContent : (img.alt || '');
          lb.classList.add('open');
        }
        fig.addEventListener('click', buka);
        fig.addEventListener('keydown', function (ev) { if (ev.key === 'Enter') buka(); });
      });
      document.getElementById('wsLightboxTutup').addEventListener('click', tutupLb);
      lb.addEventListener('click', function (ev) { if (ev.target === lb) tutupLb(); });
      document.addEventListener('keydown', function (ev) { if (ev.key === 'Escape') tutupLb(); });
    })();
  </script>
  <script>
    (function () {
      var btn = document.getElementById('wsKeatas');
      if (!btn) return;
      window.addEventListener('scroll', function () {
        btn.classList.toggle('tampil', window.scrollY > 600);
      }, { passive: true });
      btn.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
    })();
  </script>
  @yield('page-script')
  <script>
    // Saklar mode gelap/terang: simpan pilihan pengunjung.
    (function () {
      var tombol = document.getElementById('wsTema');
      if (!tombol) return;
      tombol.addEventListener('click', function () {
        var gelap = document.documentElement.getAttribute('data-tema') === 'dark';
        if (gelap) {
          document.documentElement.removeAttribute('data-tema');
          try { localStorage.setItem('ws-tema', 'light'); } catch (e) { /* abaikan */ }
        } else {
          document.documentElement.setAttribute('data-tema', 'dark');
          try { localStorage.setItem('ws-tema', 'dark'); } catch (e) { /* abaikan */ }
        }
      });
    })();
  </script>
  <script>
    // Lipat teks panjang (sambutan/visi/misi): bila isi melewati batas
    // tinggi, potong dengan gradasi + tombol "Lihat Selengkapnya".
    // Isi pendek tidak disentuh sama sekali.
    (function () {
      document.querySelectorAll('[data-lipat]').forEach(function (el) {
        if (el.scrollHeight <= 265) return;
        el.classList.add('ws-lipat');
        var tombol = document.createElement('button');
        tombol.type = 'button';
        tombol.className = 'ws-lipat-tombol';
        tombol.textContent = 'Lihat Selengkapnya';
        tombol.addEventListener('click', function () {
          var terbuka = el.classList.toggle('terbuka');
          tombol.textContent = terbuka ? 'Tutup' : 'Lihat Selengkapnya';
        });
        el.insertAdjacentElement('afterend', tombol);
      });
    })();
  </script>
</body>
</html>
