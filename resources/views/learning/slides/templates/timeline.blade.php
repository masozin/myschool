@php
  $items = $slide->content_data['items'] ?? [];
  $count = count($items);
  // Batasi max 5 node per baris supaya track & tipografi tetap proporsional.
  // Kalau item lebih dari 5, dibagi jadi 2 baris otomatis lewat CSS grid wrap.
  $colClass = match (true) {
      $count <= 2 => 'tl-cols-2',
      $count === 3 => 'tl-cols-3',
      $count === 4 => 'tl-cols-4',
      default => 'tl-cols-5',
  };
@endphp

<div class="anim-up tl-wrap">
  <div class="tl-glow tl-glow-a"></div>
  <div class="tl-glow tl-glow-b"></div>

  <div class="tl-kicker"><i class="ri ri-time-line"></i> {{ $slide->content_data['kicker'] ?? 'Tahapan' }}</div>
  <h3 class="tl-title">{{ $slide->title }}</h3>
  @if ($slide->content_data['top_description'] ?? null)
    <p class="tl-desc">{!! $slide->content_data['top_description'] !!}</p>
  @endif

  <div class="tl-track-row {{ $colClass }}">
    <div class="tl-track"></div>

    @foreach ($items as $item)
      <div class="tl-node">
        <div class="tl-dot {{ $loop->iteration % 2 === 0 ? 'is-b' : '' }}">
          @if ($item['icon'] ?? null)
            <i class="{{ $item['icon'] }}"></i>
          @else
            {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
          @endif
        </div>

        @if ($item['image'] ?? null)
          <div class="tl-node-image">
            <img src="{{ asset($item['image']) }}" alt="{{ $item['heading'] ?? '' }}">
          </div>
        @endif

        <h4 class="tl-node-heading">{{ $item['heading'] ?? '' }}</h4>
        <p class="tl-node-text">{!! $item['text'] ?? '' !!}</p>
      </div>
    @endforeach
  </div>

  @if ($slide->content_data['info_box'] ?? null)
    <div class="lp-info-box is-accent mt-4">
      <i class='ri ri-information-line'></i>
      <div class="lp-rich-content">{!! $slide->content_data['info_box'] !!}</div>
    </div>
  @endif
</div>

<style>
  .tl-wrap {
    position: relative;
    overflow: hidden;
    padding: 4px;
    min-height: 100%;
    display: flex;
    flex-direction: column;
  }

  .tl-glow {
    position: absolute;
    width: 260px;
    height: 260px;
    border-radius: 50%;
    filter: blur(60px);
    pointer-events: none;
    z-index: 0;
  }

  .tl-glow-a {
    top: -90px;
    right: -80px;
    background: var(--lp-accent);
    opacity: .10;
  }

  .tl-glow-b {
    bottom: -100px;
    left: -70px;
    background: var(--lp-highlight);
    opacity: .08;
  }

  .tl-kicker {
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
    width: fit-content;
    box-shadow: 0 4px 12px color-mix(in srgb, var(--lp-accent) 35%, transparent);
  }

  .tl-kicker i {
    font-size: .9rem;
  }

  .tl-title {
    position: relative;
    z-index: 1;
    font-family: var(--lp-font-display);
    font-weight: 700;
    font-size: clamp(1.6rem, 2.6vw, 2.4rem);
    line-height: 1.1;
    letter-spacing: -.01em;
    color: var(--lp-ink);
    margin-bottom: 6px;
  }

  .tl-desc {
    position: relative;
    z-index: 1;
    color: var(--lp-ink-soft);
    font-size: 1rem;
    max-width: 640px;
    margin-bottom: 0;
  }

  /* ---- Baris timeline + garis penghubung ---- */
  .tl-track-row {
    position: relative;
    z-index: 1;
    display: grid;
    gap: 20px;
    margin-top: auto;
    padding-top: 44px;
  }

  .tl-cols-2 {
    grid-template-columns: repeat(2, 1fr);
  }

  .tl-cols-3 {
    grid-template-columns: repeat(3, 1fr);
  }

  .tl-cols-4 {
    grid-template-columns: repeat(4, 1fr);
  }

  .tl-cols-5 {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  }

  .tl-track {
    position: absolute;
    top: 27px;
    left: 8%;
    right: 8%;
    height: 3px;
    background-image: repeating-linear-gradient(90deg, var(--lp-line) 0 10px, transparent 10px 18px);
    z-index: 0;
  }

  .tl-node {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 0 10px;
  }

  .tl-dot {
    width: 54px;
    height: 54px;
    border-radius: 50%;
    background: var(--lp-panel);
    border: 3px solid var(--lp-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: var(--lp-font-display);
    font-weight: 700;
    font-size: 1.05rem;
    color: var(--lp-accent);
    box-shadow: 0 10px 22px rgba(0, 0, 0, .08);
    margin-bottom: 16px;
    flex-shrink: 0;
  }

  .tl-dot.is-b {
    border-color: var(--lp-highlight);
    color: var(--lp-highlight);
  }

  .tl-node-image {
    width: 100%;
    max-width: 160px;
    height: 90px;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 12px;
  }

  .tl-node-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }

  .tl-node-heading {
    font-family: var(--lp-font-display);
    font-weight: 700;
    font-size: 1rem;
    color: var(--lp-ink);
    margin-bottom: 6px;
  }

  .tl-node-text {
    color: var(--lp-ink-soft);
    font-size: .85rem;
    line-height: 1.5;
    max-width: 210px;
    margin: 0 auto;
  }

  /* ---- Responsif: di layar sempit, timeline jadi vertikal ---- */
  @media (max-width: 767px) {
    .tl-title {
      font-size: 1.5rem;
    }

    .tl-track-row {
      grid-template-columns: 1fr !important;
      gap: 28px;
      padding-top: 8px;
    }

    .tl-track {
      top: 0;
      bottom: 0;
      left: 27px;
      right: auto;
      width: 3px;
      height: auto;
      background-image: repeating-linear-gradient(180deg, var(--lp-line) 0 10px, transparent 10px 18px);
    }

    .tl-node {
      flex-direction: row;
      text-align: left;
      align-items: flex-start;
      gap: 14px;
      padding: 0;
    }

    .tl-dot {
      margin-bottom: 0;
    }

    .tl-node-text {
      max-width: none;
      margin: 0;
    }
  }
</style>
