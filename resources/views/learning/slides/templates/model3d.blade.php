@php
  $hasMateri = !empty($slide->content_text);
  $hasInfo = !empty($slide->content_data['info_penting'] ?? null);
  $hotspots = $slide->content_data['hotspots'] ?? [];
  $hasHotspots = count($hotspots) > 0;

  // tab pertama yang tersedia jadi tab default
  $defaultTab = $hasMateri ? 'materi' : ($hasInfo ? 'info' : ($hasHotspots ? 'hotspot' : null));
@endphp

<div class="anim-up m3d-wrap-{{ $slide->id }} m3d-wrap">
  <div class="m3d-eyebrow">Model 3D Interaktif</div>
  <h3 class="m3d-title">{{ $slide->title }}</h3>
  @if ($slide->content_data['top_description'] ?? null)
    <p class="m3d-desc">{!! $slide->content_data['top_description'] !!}</p>
  @endif

  <div class="row g-3">
    {{-- ============ KIRI: MODEL 3D ============ --}}
    <div class="col-lg-8">
      <div class="m3d-stage">
        <span class="m3d-stage-tag"><i class='bx bx-cube-alt'></i> MODEL-3D</span>
        <span class="m3d-stage-hint"><i class='bx bx-rotate-right'></i> Seret untuk memutar</span>
        <button type="button" class="m3d-rotate-toggle" onclick="toggleAutoRotate(this)" title="Putar otomatis">
          <i class='bx bx-play'></i>
        </button>

        <model-viewer id="model3d-{{ $slide->id }}"
          src="{{ $slide->media_url ? asset($slide->media_url) : $slide->media_url }}" camera-controls
          shadow-intensity="1" interpolation-decay="200">
          @foreach ($hotspots as $h)
            <button class="Hotspot" slot="hotspot-{{ $loop->index }}"
              data-position="{{ $h['position'] ?? '0m 0m 0m' }}" data-normal="{{ $h['normal'] ?? '0m 1m 0m' }}"
              data-camera-orbit="{{ $h['camera_orbit'] ?? '' }}" data-camera-target="{{ $h['camera_target'] ?? '' }}"
              data-fov="{{ $h['field_of_view'] ?? '' }}" data-idx="{{ $loop->index }}"
              data-slide="{{ $slide->id }}" onclick="focusModelHotspot(this)">
              <div class="HotspotAnnotation">{{ $h['title'] ?? '' }}</div>
            </button>
          @endforeach
        </model-viewer>
      </div>
    </div>

    {{-- ============ KANAN: PANEL KETERANGAN ============ --}}
    <div class="col-lg-4">
      <div class="m3d-panel">
        @if ($hasMateri || $hasInfo || $hasHotspots)
          <div class="m3d-tabs">
            @if ($hasMateri)
              <button class="m3d-tab-btn {{ $defaultTab === 'materi' ? 'active' : '' }}"
                onclick="m3dSwitchTab(this, '{{ $slide->id }}', 'materi')">
                <i class='bx bx-book-open'></i> Materi
              </button>
            @endif
            @if ($hasInfo)
              <button class="m3d-tab-btn {{ $defaultTab === 'info' ? 'active' : '' }}"
                onclick="m3dSwitchTab(this, '{{ $slide->id }}', 'info')">
                <i class='bx bx-bulb'></i> Info
              </button>
            @endif
            @if ($hasHotspots)
              <button class="m3d-tab-btn {{ $defaultTab === 'hotspot' ? 'active' : '' }}"
                onclick="m3dSwitchTab(this, '{{ $slide->id }}', 'hotspot')">
                <i class='bx bx-map-pin'></i> Hotspot
              </button>
            @endif
          </div>
        @endif

        <div class="m3d-tab-panes">
          @if ($hasMateri)
            <div class="m3d-pane {{ $defaultTab === 'materi' ? 'active' : '' }}" data-slide="{{ $slide->id }}"
              data-pane="materi">
              <div class="lp-rich-content">{!! $slide->content_text !!}</div>
            </div>
          @endif

          @if ($hasInfo)
            <div class="m3d-pane {{ $defaultTab === 'info' ? 'active' : '' }}" data-slide="{{ $slide->id }}"
              data-pane="info">
              <div class="m3d-info-box">
                <i class='bx bx-bulb'></i>
                <div class="lp-rich-content">{!! $slide->content_data['info_penting'] !!}</div>
              </div>
            </div>
          @endif

          @if ($hasHotspots)
            <div class="m3d-pane {{ $defaultTab === 'hotspot' ? 'active' : '' }}" data-slide="{{ $slide->id }}"
              data-pane="hotspot">
              @foreach ($hotspots as $h)
                <div class="m3d-hs-item" data-idx="{{ $loop->index }}" data-slide="{{ $slide->id }}"
                  onclick="focusModelHotspotByIdx('{{ $slide->id }}', {{ $loop->index }})">
                  <span class="m3d-hs-dot"></span>
                  <div>
                    <strong>{{ $h['title'] ?? '' }}</strong>
                    @if (!empty($h['text']))
                      <p>{{ $h['text'] }}</p>
                    @endif
                  </div>
                </div>
              @endforeach
            </div>
          @endif

          @if (!$hasMateri && !$hasInfo && !$hasHotspots)
            <p class="text-muted small mb-0">Belum ada keterangan untuk model ini.</p>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>

<style>
  .m3d-wrap {
    --m3d-ink: var(--lp-ink);
    --m3d-ink-soft: var(--lp-ink-soft);
    --m3d-line: var(--lp-line);
    --m3d-accent: var(--lp-accent);
    --m3d-accent-soft: var(--lp-accent-soft);
    --m3d-amber: var(--lp-highlight);
    --m3d-amber-soft: var(--lp-highlight-soft);
  }

  .m3d-eyebrow {
    font-family: var(--lp-font-mono);
    font-size: .72rem;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--m3d-accent);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
  }

  .m3d-eyebrow::before {
    content: '';
    width: 16px;
    height: 1px;
    background: var(--m3d-accent);
  }

  .m3d-title {
    font-family: var(--lp-font-display);
    font-weight: 700;
    font-size: 1.5rem;
    margin-bottom: 4px;
    letter-spacing: -.01em;
    color: var(--m3d-ink);
  }

  .m3d-desc {
    color: var(--m3d-ink-soft);
    font-size: .92rem;
    max-width: 640px;
    margin-bottom: 18px;
  }

  .m3d-stage {
    position: relative;
    background: linear-gradient(180deg, var(--lp-stage-1) 0%, var(--lp-stage-2) 100%);
    border: 1px solid var(--m3d-line);
    border-radius: var(--lp-radius);
    overflow: hidden;
    height: min(70vh, 640px);
  }

  .m3d-stage::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: linear-gradient(var(--lp-grid-line) 1px, transparent 1px), linear-gradient(90deg, var(--lp-grid-line) 1px, transparent 1px);
    background-size: 28px 28px;
    pointer-events: none;
  }

  .m3d-stage model-viewer {
    width: 100%;
    height: 100%;
    position: relative;
    z-index: 1;
  }

  .m3d-stage-tag {
    position: absolute;
    top: 14px;
    left: 14px;
    z-index: 2;
    font-family: var(--lp-font-mono);
    font-size: .68rem;
    letter-spacing: .06em;
    color: var(--m3d-ink-soft);
    background: rgba(255, 255, 255, .75);
    backdrop-filter: blur(6px);
    border: 1px solid var(--m3d-line);
    padding: 4px 10px;
    border-radius: 20px;
  }

  .m3d-stage-hint {
    position: absolute;
    bottom: 14px;
    right: 14px;
    z-index: 2;
    font-size: .72rem;
    color: var(--m3d-ink-soft);
    background: rgba(255, 255, 255, .75);
    backdrop-filter: blur(6px);
    border: 1px solid var(--m3d-line);
    padding: 5px 10px;
    border-radius: 20px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .m3d-rotate-toggle {
    position: absolute;
    bottom: 14px;
    left: 14px;
    z-index: 2;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1px solid var(--m3d-line);
    background: rgba(255, 255, 255, .85);
    backdrop-filter: blur(6px);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--m3d-ink-soft);
    font-size: 1.05rem;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
  }

  .m3d-rotate-toggle:hover {
    color: var(--m3d-accent);
    border-color: var(--m3d-accent);
  }

  .m3d-rotate-toggle.is-active {
    background: var(--m3d-accent);
    color: #fff;
    border-color: var(--m3d-accent);
  }

  .Hotspot {
    position: relative;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid #fff;
    background: var(--m3d-accent);
    box-shadow: 0 0 0 4px rgba(14, 165, 164, .18), 0 2px 6px rgba(0, 0, 0, .25);
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform .18s ease, box-shadow .18s ease;
  }

  .Hotspot::after {
    content: '';
    position: absolute;
    inset: -8px;
    border-radius: 50%;
    border: 1.5px solid var(--m3d-accent);
    opacity: .55;
    animation: m3d-ping 2.2s ease-out infinite;
  }

  .Hotspot:hover {
    transform: scale(1.15);
  }

  .Hotspot.is-active {
    background: var(--m3d-amber);
    box-shadow: 0 0 0 5px rgba(245, 166, 35, .22), 0 2px 8px rgba(0, 0, 0, .3);
    transform: scale(1.2);
  }

  .Hotspot.is-active::after {
    border-color: var(--m3d-amber);
    animation: none;
    opacity: 0;
  }

  @keyframes m3d-ping {
    0% {
      transform: scale(.6);
      opacity: .7;
    }

    75% {
      transform: scale(1.9);
      opacity: 0;
    }

    100% {
      opacity: 0;
    }
  }

  .HotspotAnnotation {
    position: absolute;
    left: 50%;
    bottom: calc(100% + 12px);
    transform: translateX(-50%) translateY(6px) scale(.92);
    min-width: 150px;
    max-width: 200px;
    background: rgba(28, 35, 51, .92);
    backdrop-filter: blur(6px);
    color: #fff;
    font-size: .78rem;
    font-weight: 500;
    line-height: 1.3;
    padding: 8px 12px;
    border-radius: 10px;
    pointer-events: none;
    opacity: 0;
    visibility: hidden;
    transition: opacity .16s ease, transform .16s ease;
    box-shadow: 0 8px 20px rgba(20, 25, 40, .28);
    text-align: left;
    z-index: 5;
  }

  .HotspotAnnotation::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 6px solid transparent;
    border-top-color: rgba(28, 35, 51, .92);
  }

  .Hotspot.is-active .HotspotAnnotation {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0) scale(1);
  }

  /* Panel disamakan formula tingginya dengan stage supaya selalu sejajar
     persis, di ukuran layar apa pun. */
  .m3d-panel {
    background: var(--lp-panel);
    border: 1px solid var(--m3d-line);
    border-radius: var(--lp-radius);
    height: min(64vh, 640px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  .m3d-tabs {
    display: flex;
    gap: 4px;
    padding: 10px;
    border-bottom: 1px solid var(--m3d-line);
    flex-shrink: 0;
  }

  .m3d-tab-btn {
    flex: 1;
    border: none;
    background: transparent;
    font-size: .8rem;
    font-weight: 600;
    color: var(--m3d-ink-soft);
    padding: 8px 6px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    transition: background .15s ease, color .15s ease;
  }

  .m3d-tab-btn i {
    font-size: 1rem;
  }

  .m3d-tab-btn.active {
    background: var(--m3d-accent-soft);
    color: var(--m3d-accent);
  }

  .m3d-tab-panes {
    padding: 18px;
    overflow-y: auto;
    flex: 1;
  }

  .m3d-pane {
    display: none;
  }

  .m3d-pane.active {
    display: block;
    animation: m3d-fade .2s ease;
  }

  @keyframes m3d-fade {
    from {
      opacity: 0;
      transform: translateY(4px);
    }

    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .m3d-pane p {
    color: var(--m3d-ink-soft);
    font-size: .88rem;
    line-height: 1.6;
  }

  .m3d-info-box {
    background: var(--m3d-amber-soft);
    border: 1px solid #F3DBA0;
    border-radius: 12px;
    padding: 12px 14px;
    display: flex;
    gap: 10px;
    font-size: .85rem;
    color: #7A5A0E;
    line-height: 1.5;
  }

  .m3d-info-box i {
    font-size: 1.1rem;
    margin-top: 1px;
  }

  .m3d-hs-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 11px 12px;
    border: 1px solid var(--m3d-line);
    border-radius: 12px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: border-color .15s ease, background .15s ease;
  }

  .m3d-hs-item:hover {
    border-color: var(--m3d-accent);
    background: var(--m3d-accent-soft);
  }

  .m3d-hs-item.is-active {
    border-color: var(--m3d-amber);
    background: var(--m3d-amber-soft);
  }

  .m3d-hs-dot {
    flex-shrink: 0;
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: var(--m3d-accent);
    margin-top: 5px;
  }

  .m3d-hs-item.is-active .m3d-hs-dot {
    background: var(--m3d-amber);
  }

  .m3d-hs-item strong {
    font-size: .85rem;
  }

  .m3d-hs-item p {
    margin: 2px 0 0;
    font-size: .78rem;
    color: var(--m3d-ink-soft);
  }

  @media (max-width: 991px) {
    .m3d-stage {
      height: min(50vh, 380px);
    }

    .m3d-panel {
      height: auto;
      min-height: 260px;
      margin-top: 16px;
    }
  }
</style>

<script>
  // ---- tab switching (namespaced per slide id, aman untuk banyak slide di 1 halaman) ----
  function m3dSwitchTab(btn, slideId, pane) {
    const wrap = btn.closest('.m3d-panel');
    wrap.querySelectorAll('.m3d-tab-btn').forEach(b => b.classList.remove('active'));
    wrap.querySelectorAll('.m3d-pane').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    wrap.querySelector(`.m3d-pane[data-pane="${pane}"]`)?.classList.add('active');
  }

  // ---- tandai dot + card list yang aktif ----
  function setActiveHotspot(slideId, idx) {
    document.querySelectorAll(`.Hotspot[data-slide="${slideId}"]`)
      .forEach(h => h.classList.toggle('is-active', h.dataset.idx == idx));
    document.querySelectorAll(`.m3d-hs-item[data-slide="${slideId}"]`)
      .forEach(h => h.classList.toggle('is-active', h.dataset.idx == idx));
  }

  function focusModelHotspot(button) {
    const mv = button.closest('model-viewer');
    if (!mv) return;
    if (button.dataset.cameraOrbit) mv.cameraOrbit = button.dataset.cameraOrbit;
    if (button.dataset.cameraTarget) mv.cameraTarget = button.dataset.cameraTarget;
    if (button.dataset.fov) mv.fieldOfView = button.dataset.fov;
    setActiveHotspot(button.dataset.slide, button.dataset.idx);
  }

  // dipanggil dari daftar hotspot di tab "Hotspot"
  function focusModelHotspotByIdx(slideId, idx) {
    const btn = document.querySelector(`.Hotspot[data-slide="${slideId}"][data-idx="${idx}"]`);
    if (btn) focusModelHotspot(btn);
  }

  // toggle putar otomatis, default MATI (auto-rotate tidak ditulis di HTML)
  function toggleAutoRotate(btn) {
    const mv = btn.closest('.m3d-stage').querySelector('model-viewer');
    if (!mv) return;
    const isOn = mv.hasAttribute('auto-rotate');
    if (isOn) {
      mv.removeAttribute('auto-rotate');
      btn.innerHTML = "<i class='bx bx-play'></i>";
      btn.classList.remove('is-active');
      btn.title = 'Putar otomatis';
    } else {
      mv.setAttribute('auto-rotate', '');
      btn.innerHTML = "<i class='bx bx-pause'></i>";
      btn.classList.add('is-active');
      btn.title = 'Hentikan putar otomatis';
    }
  }
</script>
