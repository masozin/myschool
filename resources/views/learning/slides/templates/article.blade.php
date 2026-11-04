@php
  $hasMedia = !empty($slide->media_url);
  $paragraphs = $slide->content_data['paragraphs'] ?? [];
@endphp

<div class="anim-up art-wrap">
  <div class="art-blob"></div>

  <div class="art-kicker"><i class='bx bx-book-content'></i> Artikel</div>
  <h3 class="art-title">{{ $slide->title }}</h3>

  <div class="row g-4 mt-1 position-relative">
    {{-- ============ GAMBAR (kalau ada) ============ --}}
    @if ($hasMedia)
      <div class="col-md-5">
        <div class="art-media">
          <img src="{{ asset($slide->media_url) }}" alt="{{ $slide->title }}">
          <div class="art-media-overlay"></div>
          <span class="art-media-caption"><i class='ri ri-image-alt'></i> Ilustrasi</span>
        </div>
      </div>
    @endif

    {{-- ============ KONTEN ============ --}}
    <div class="{{ $hasMedia ? 'col-md-7' : 'col-12' }}">

      @if (($slide->content_data['heading'] ?? null) || ($slide->content_data['heading_description'] ?? null))
        <div class="art-intro">
          @if ($slide->content_data['heading'] ?? null)
            <h5 class="art-heading">{{ $slide->content_data['heading'] }}</h5>
          @endif
          @if ($slide->content_data['heading_description'] ?? null)
            <p class="art-heading-desc">{{ $slide->content_data['heading_description'] }}</p>
          @endif
        </div>
      @endif

      @if (count($paragraphs))
        <div class="art-timeline">
          @foreach ($paragraphs as $p)
            <div class="art-section" data-num="{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}">
              <span class="art-node">{{ $loop->iteration }}</span>
              @if ($p['heading'] ?? null)
                <h6 class="art-section-title">{{ $p['heading'] }}</h6>
              @endif
              <div class="lp-rich-content art-section-body">{!! $p['content'] ?? '' !!}</div>
            </div>
          @endforeach
        </div>
      @endif

      @if ($slide->content_data['info_penting'] ?? null)
        <div class="lp-info-box mt-3">
          <i class='bx bx-bulb'></i>
          <div class="lp-rich-content">{!! $slide->content_data['info_penting'] !!}</div>
        </div>
      @endif
    </div>
  </div>
</div>

<style>
  .art-wrap {
    position: relative;
    overflow: hidden;
    padding: 4px;
  }

  /* dekorasi blur di pojok — bikin card terasa "didesain" bukan generik */
  .art-blob {
    position: absolute;
    top: -60px;
    right: -60px;
    width: 220px;
    height: 220px;
    border-radius: 50%;
    background: var(--lp-accent);
    opacity: .10;
    filter: blur(50px);
    pointer-events: none;
    z-index: 0;
  }

  .art-kicker {
    position: relative;
    z-index: 1;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--lp-accent);
    color: #fff;
    padding: 6px 14px;
    border-radius: 999px;
    font-family: var(--lp-font-mono);
    font-size: .7rem;
    letter-spacing: .08em;
    text-transform: uppercase;
    font-weight: 600;
    margin-bottom: 14px;
    box-shadow: 0 4px 12px color-mix(in srgb, var(--lp-accent) 35%, transparent);
  }

  .art-kicker i {
    font-size: .9rem;
  }

  .art-title {
    position: relative;
    z-index: 1;
    font-family: var(--lp-font-display);
    font-weight: 700;
    font-size: 1.85rem;
    line-height: 1.18;
    letter-spacing: -.02em;
    color: var(--lp-ink);
    margin-bottom: 0;
  }

  /* ---- Media / gambar dengan overlay duotone ---- */
  .art-media {
    position: relative;
    height: 100%;
    min-height: 280px;
    border-radius: var(--lp-radius);
    overflow: hidden;
    box-shadow: 0 12px 30px rgba(0, 0, 0, .12);
  }

  .art-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    min-height: 280px;
  }

  .art-media-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(200deg, color-mix(in srgb, var(--lp-accent) 55%, transparent) 0%, transparent 55%),
      linear-gradient(0deg, rgba(0, 0, 0, .45) 0%, transparent 40%);
    mix-blend-mode: multiply;
  }

  .art-media-caption {
    position: absolute;
    bottom: 14px;
    left: 14px;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: .74rem;
    font-weight: 600;
    color: #fff;
    background: rgba(0, 0, 0, .35);
    backdrop-filter: blur(6px);
    padding: 6px 12px;
    border-radius: 20px;
  }

  @media (min-width:768px) {
    .art-media {
      position: sticky;
      top: 20px;
    }
  }

  /* ---- Intro (heading + deskripsi) ---- */
  .art-intro {
    border-left: 3px solid var(--lp-accent);
    padding-left: 16px;
    margin-bottom: 26px;
  }

  .art-heading {
    font-family: var(--lp-font-display);
    font-weight: 700;
    font-size: 1.15rem;
    color: var(--lp-ink);
    margin-bottom: 6px;
  }

  .art-heading-desc {
    color: var(--lp-ink-soft);
    font-size: .92rem;
    line-height: 1.65;
    margin-bottom: 0;
  }

  /* ---- Timeline vertikal untuk paragraf ---- */
  .art-timeline {
    position: relative;
    padding-left: 38px;
  }

  .art-timeline::before {
    content: '';
    position: absolute;
    left: 14px;
    top: 6px;
    bottom: 6px;
    width: 2px;
    background: linear-gradient(to bottom, var(--lp-accent), color-mix(in srgb, var(--lp-accent) 15%, transparent));
  }

  .art-section {
    position: relative;
    margin-bottom: 26px;
  }

  .art-section:last-child {
    margin-bottom: 0;
  }

  /* nomor raksasa transparan di belakang judul bagian — ciri khas slide premium */
  .art-section::before {
    content: attr(data-num);
    position: absolute;
    right: -4px;
    top: -14px;
    font-family: var(--lp-font-display);
    font-weight: 700;
    font-size: 3.4rem;
    color: var(--lp-ink);
    opacity: .045;
    line-height: 1;
    pointer-events: none;
    z-index: 0;
    user-select: none;
  }

  .art-node {
    position: absolute;
    left: -38px;
    top: 0;
    width: 29px;
    height: 29px;
    border-radius: 50%;
    background: var(--lp-accent);
    color: #fff;
    font-family: var(--lp-font-mono);
    font-weight: 700;
    font-size: .78rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 0 4px var(--lp-panel), 0 4px 10px color-mix(in srgb, var(--lp-accent) 40%, transparent);
    z-index: 1;
  }

  .art-section-title {
    position: relative;
    z-index: 1;
    font-family: var(--lp-font-display);
    font-weight: 700;
    font-size: 1.02rem;
    color: var(--lp-ink);
    margin-bottom: 6px;
  }

  .art-section-body {
    position: relative;
    z-index: 1;
    font-size: .92rem;
  }

  @media (max-width: 767px) {
    .art-title {
      font-size: 1.5rem;
    }

    .art-media {
      min-height: 200px;
    }

    .art-media img {
      min-height: 200px;
    }
  }
</style>
