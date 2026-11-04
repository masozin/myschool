@php
  $images = $slide->content_data['images'] ?? [];
@endphp

<div class="anim-up gal-wrap">
  <div class="gal-kicker">Ilustrasi</div>
  <h3 class="gal-title">{{ $slide->title }}</h3>
  @if ($slide->content_data['top_description'] ?? null)
    <p class="gal-desc">{!! $slide->content_data['top_description'] !!}</p>
  @endif

  <div class="gal-rows">
    @foreach ($images as $img)
      <div class="gal-row">
        @if ($img['media_url'] ?? null)
          <div class="gal-row-media">
            <div class="gal-media-offset"></div>
            <div class="gal-media">
              <img src="{{ asset($img['media_url']) }}" alt="{{ $img['title'] ?? $slide->title }}">
            </div>
          </div>
        @endif

        <div class="gal-row-text">
          <span class="gal-index">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
          @if ($img['title'] ?? null)
            <h6 class="gal-caption-title">{{ $img['title'] }}</h6>
          @endif
          @if ($img['text'] ?? null)
            <div class="lp-rich-content gal-caption-text {{ $loop->first ? 'has-dropcap' : '' }}">
              {!! $img['text'] !!}</div>
          @endif
        </div>
      </div>
    @endforeach
  </div>
</div>

<style>
  .gal-wrap {
    position: relative;
  }

  /* eyebrow bergaya cetak: label + garis tipis, bukan pil warna solid */
  .gal-kicker {
    display: flex;
    align-items: center;
    gap: 12px;
    font-family: var(--lp-font-mono, monospace);
    font-size: .72rem;
    letter-spacing: .18em;
    text-transform: uppercase;
    color: var(--lp-accent, #0EA5A4);
    font-weight: 600;
    margin-bottom: 18px;
  }

  .gal-kicker::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--lp-line, #E3E6EE);
  }

  .gal-title {
    font-family: var(--lp-font-display, serif);
    font-weight: 600;
    font-size: 2.3rem;
    letter-spacing: -.02em;
    line-height: 1.1;
    color: var(--lp-ink, #1C2333);
    margin-bottom: 14px;
  }

  .gal-desc {
    color: var(--lp-ink-soft, #5B6478);
    font-size: 1.02rem;
    line-height: 1.7;
    max-width: 58ch;
    margin-bottom: 0;
  }

  /* ---- Baris zig-zag ---- */
  .gal-rows {
    margin-top: 40px;
  }

  .gal-row {
    display: flex;
    align-items: center;
    gap: 52px;
    padding-bottom: 44px;
    margin-bottom: 44px;
    border-bottom: 1px solid var(--lp-line, #E3E6EE);
  }

  .gal-row:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
  }

  .gal-row:nth-child(even) {
    flex-direction: row-reverse;
  }

  .gal-row-media {
    position: relative;
    flex: 0 0 42%;
  }

  .gal-row-text {
    position: relative;
    flex: 1;
    min-width: 0;
  }

  /* kartu warna offset di belakang foto — pengganti blob blur generik,
     terasa lebih "didesain" karena menempel langsung ke foto */
  .gal-media-offset {
    position: absolute;
    z-index: 0;
    top: 18px;
    left: 18px;
    right: -18px;
    bottom: -18px;
    border-radius: var(--lp-radius, 16px);
    background: var(--lp-accent, #0EA5A4);
    opacity: .16;
  }

  .gal-row:nth-child(even) .gal-media-offset {
    left: -18px;
    right: 18px;
  }

  .gal-media {
    position: relative;
    z-index: 1;
    border-radius: var(--lp-radius, 16px);
    overflow: hidden;
    box-shadow: 0 20px 44px rgba(0, 0, 0, .14);
  }

  .gal-media img {
    width: 100%;
    height: auto;
    display: block;
  }

  /* nomor besar bergaya serif, transparan — device tipografis, bukan badge */
  .gal-index {
    display: block;
    font-family: var(--lp-font-display, serif);
    font-weight: 600;
    font-size: 3.6rem;
    line-height: 1;
    color: var(--lp-accent, #0EA5A4);
    opacity: .22;
    margin-bottom: -.65rem;
  }

  .gal-caption-title {
    position: relative;
    z-index: 1;
    font-family: var(--lp-font-display, serif);
    font-weight: 600;
    font-size: 1.55rem;
    letter-spacing: -.01em;
    line-height: 1.2;
    color: var(--lp-ink, #1C2333);
    margin-bottom: 12px;
  }

  .gal-caption-text {
    font-size: 1rem;
    line-height: 1.85;
    color: var(--lp-ink-soft, #5B6478);
  }

  .gal-caption-text p {
    margin-bottom: 12px;
  }

  /* drop cap di paragraf pertama teks pertama — sentuhan editorial klasik */
  .gal-caption-text.has-dropcap p:first-of-type::first-letter {
    font-family: var(--lp-font-display, serif);
    font-weight: 700;
    font-size: 3.1em;
    line-height: .78;
    float: left;
    margin: .03em .1em 0 0;
    color: var(--lp-accent, #0EA5A4);
  }

  @media (max-width:900px) {

    .gal-row,
    .gal-row:nth-child(even) {
      flex-direction: column;
      align-items: stretch;
      gap: 22px;
    }

    .gal-row-media {
      flex: 0 0 auto;
    }

    .gal-media-offset {
      display: none;
    }

    .gal-title {
      font-size: 1.8rem;
    }

    .gal-index {
      font-size: 2.6rem;
    }

    .gal-caption-title {
      font-size: 1.25rem;
    }
  }
</style>
