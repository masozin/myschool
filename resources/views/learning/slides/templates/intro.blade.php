@php
  $theme = $slide->content_data['theme'] ?? 'primary';
  $hasImage = (bool) $slide->media_url;
  $initial = mb_substr(trim($slide->title ?? ''), 0, 1, 'UTF-8');
@endphp

{{--
  ===========================================================================
  INTRO SLIDE
  ---------------------------------------------------------------------------
  Konsep: layout dua-kolom editorial yang sungguhan (bukan overlay+blur di
  atas foto), dengan SATU signature element — monogram huruf pertama judul,
  dicetak besar & pudar di display font sebagai watermark. Elemen ini
  diambil dari konten asli (judul slide itu sendiri), bukan bentuk dekoratif
  yang tidak berarti apa-apa.

  Tema (tech/dark/astronaut/natural) dibedakan lewat WARNA & satu pilihan
  tekstur latar yang statis — bukan lewat animasi berputar/berkelap-kelip
  yang berjalan terus-menerus. Motion cuma dipakai sekali di awal (masuknya
  konten), lalu slide diam — supaya terasa tenang & profesional, bukan ramai.
  ===========================================================================
--}}

<div class="anim-up intro-slide {{ $hasImage ? 'has-image' : 'no-image' }}">
  <div class="intro-texture" aria-hidden="true"></div>

  <div class="intro-grid">
    <div class="intro-col-text">
      <span class="intro-monogram" aria-hidden="true">{{ $initial }}</span>

      <div class="intro-col-text-inner">
        @if ($slide->content_data['badge'] ?? null)
          <div class="intro-badge anim-stagger" style="--stagger-delay:.05s">
            {{ $slide->content_data['badge'] }}
          </div>
        @endif

        <h2 class="intro-title anim-stagger" style="--stagger-delay:.15s">{{ $slide->title }}</h2>

        @if ($slide->content_data['subtitle'] ?? null)
          <p class="intro-subtitle anim-stagger" style="--stagger-delay:.25s">
            {{ $slide->content_data['subtitle'] }}</p>
        @endif

        @if ($slide->content_text)
          <div class="lp-rich-content mt-2 anim-stagger" style="--stagger-delay:.35s">
            {!! $slide->content_text !!}</div>
        @endif
      </div>
    </div>

    @if ($hasImage)
      <div class="intro-col-media anim-stagger" style="--stagger-delay:.1s">
        <img src="{{ asset($slide->media_url) }}" alt="{{ $slide->title }}">
      </div>
    @endif
  </div>
</div>

<style>
  /* ============ ANIMASI MASUK — sekali saja, lalu diam ============ */
  .anim-stagger {
    opacity: 0;
    transform: translateY(12px);
    animation: introStagger .5s cubic-bezier(.22, 1, .36, 1) forwards;
    animation-delay: var(--stagger-delay, 0s);
  }

  @keyframes introStagger {
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  @media (prefers-reduced-motion: reduce) {
    .anim-stagger {
      animation: none;
      opacity: 1;
      transform: none;
    }
  }

  /* ============ STRUKTUR ============ */
  .intro-slide {
    position: relative;
    border-radius: var(--lp-radius, 16px);
    overflow: hidden;
    background: var(--lp-panel);
    border: 1px solid var(--lp-line);
  }

  .intro-grid {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    align-items: stretch;
    min-height: 440px;
  }

  .intro-slide.no-image .intro-grid {
    grid-template-columns: 1fr;
    min-height: auto;
  }

  @media (max-width: 767px) {
    .intro-grid {
      grid-template-columns: 1fr;
      min-height: auto;
    }
  }

  .intro-col-text {
    position: relative;
    display: flex;
    align-items: center;
    padding: clamp(1.75rem, 1.2rem + 2.2vw, 3.5rem);
    overflow: hidden;
  }

  .intro-col-text-inner {
    position: relative;
    z-index: 1;
  }

  .intro-col-media {
    position: relative;
    min-height: 220px;
  }

  .intro-col-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  @media (max-width: 767px) {
    .intro-col-media {
      min-height: 200px;
      order: -1;
    }
  }

  /* monogram — SATU signature element, diambil dari judul asli, bukan
     bentuk generik. Dicetak besar & pudar sebagai tekstur latar teks. */
  .intro-monogram {
    position: absolute;
    top: -.12em;
    left: clamp(.5rem, 1vw, 1.5rem);
    font-family: var(--lp-font-display);
    font-weight: 700;
    font-size: clamp(9rem, 6rem + 12vw, 16rem);
    line-height: 1;
    color: var(--lp-accent);
    opacity: .07;
    pointer-events: none;
    user-select: none;
    z-index: 0;
  }

  /* ============ TIPOGRAFI FLUID ============ */
  .intro-badge {
    display: inline-block;
    font-family: var(--lp-font-mono);
    font-size: .72rem;
    letter-spacing: .12em;
    text-transform: uppercase;
    font-weight: 600;
    color: var(--lp-accent);
    padding-bottom: 6px;
    border-bottom: 2px solid var(--lp-accent);
    margin-bottom: 18px;
  }

  .intro-title {
    font-family: var(--lp-font-display);
    font-weight: 700;
    font-size: clamp(1.9rem, 1.15rem + 2.9vw, 3.6rem);
    line-height: 1.2;
    letter-spacing: -.02em;
    color: var(--lp-ink);
    margin-bottom: .55rem;
    text-wrap: balance;
  }

  .intro-subtitle {
    color: var(--lp-ink-soft);
    font-size: clamp(1rem, .85rem + .6vw, 1.4rem);
    line-height: 1.55;
    margin-bottom: .4rem;
    max-width: 54ch;
  }

  .intro-col-text .lp-rich-content {
    font-size: clamp(.95rem, .88rem + .3vw, 1.08rem);
    color: var(--lp-ink);
    max-width: 60ch;
  }

  /* ============ SKALA .lp-rich-content ============ */
  .lp-rich-content {
    line-height: 1.6;
  }

  .lp-rich-content p {
    margin: 0 0 .6em;
  }

  .lp-rich-content p:last-child {
    margin-bottom: 0;
  }

  .lp-rich-content strong {
    color: var(--lp-ink);
    font-weight: 600;
  }

  .lp-rich-content ul,
  .lp-rich-content ol {
    margin: 0 0 .6em;
    padding-left: 1.25em;
  }

  .lp-rich-content li+li {
    margin-top: .25em;
  }

  .lp-rich-content h1,
  .lp-rich-content h2,
  .lp-rich-content h3,
  .lp-rich-content h4 {
    font-family: var(--lp-font-display);
    color: var(--lp-ink);
    line-height: 1.25;
    margin: .8em 0 .4em;
  }

  .lp-rich-content h1:first-child,
  .lp-rich-content h2:first-child,
  .lp-rich-content h3:first-child,
  .lp-rich-content h4:first-child {
    margin-top: 0;
  }

  .lp-rich-content a {
    color: var(--lp-accent);
    text-decoration-color: color-mix(in srgb, var(--lp-accent) 45%, transparent);
    text-underline-offset: 2px;
  }

  /* ==========================================================
       TEKSTUR PER-TEMA — satu treatment statis, bukan animasi
       berulang. Beda tema = beda warna & pola dasar, titik.
       ========================================================== */
  .intro-texture {
    position: absolute;
    inset: 0;
    z-index: 0;
    pointer-events: none;
  }

  /* tech: grid titik halus */
  [data-theme="tech"] .intro-texture {
    background-image: radial-gradient(circle, var(--lp-line) 1px, transparent 1.4px);
    background-size: 24px 24px;
    -webkit-mask-image: linear-gradient(120deg, black, transparent 55%);
    mask-image: linear-gradient(120deg, black, transparent 55%);
  }

  /* natural: gradasi organik lembut, dua titik radial saja */
  [data-theme="natural"] .intro-texture {
    background:
      radial-gradient(420px 320px at -5% -10%, color-mix(in srgb, var(--lp-accent) 10%, transparent), transparent 70%),
      radial-gradient(360px 300px at 105% 110%, color-mix(in srgb, var(--lp-highlight) 8%, transparent), transparent 70%);
  }

  /* dark: satu glow lembut di sudut, tanpa animasi */
  [data-theme="dark"] .intro-texture {
    background: radial-gradient(60% 90% at 0% 0%, color-mix(in srgb, var(--lp-accent) 14%, transparent), transparent 60%);
  }

  /* astronaut: taburan bintang statis */
  [data-theme="astronaut"] .intro-texture {
    background-image:
      radial-gradient(1.3px 1.3px at 12% 18%, rgba(255, 255, 255, .55), transparent 60%),
      radial-gradient(1.1px 1.1px at 32% 55%, rgba(255, 255, 255, .4), transparent 60%),
      radial-gradient(1.5px 1.5px at 58% 22%, rgba(255, 255, 255, .6), transparent 60%),
      radial-gradient(1.1px 1.1px at 78% 65%, rgba(255, 255, 255, .35), transparent 60%),
      radial-gradient(1.3px 1.3px at 90% 30%, rgba(255, 255, 255, .5), transparent 60%);
  }
</style>
