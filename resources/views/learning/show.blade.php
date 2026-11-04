@extends('layouts.learningLayout')

@php
  $subjectName = $topic->subject->name . ' - ' . $topic->subject->grade->name;
  $topicTitle = $topic->title;
  $backUrl = url('/dashboard');
  $exitUrl = url('/dashboard');
  $totalSlides = $topic->slides->count();
@endphp

@section('page-style')
  <style>
    .slides-viewport {
      position: relative;
    }

    .slide-item {
      display: none;
      opacity: 0;
      visibility: hidden;
      transform: translateX(0) scale(1);
      transition: opacity .38s cubic-bezier(.4, 0, .2, 1),
        transform .38s cubic-bezier(.4, 0, .2, 1);
    }

    .slide-item.active-slide {
      display: block;
      opacity: 1;
      visibility: visible;
      transform: translateX(0) scale(1);
    }

    /* selama transisi, kedua slide (lama & baru) ditumpuk saling
           menimpa lewat position:absolute, supaya benar-benar terlihat
           "bertukar tempat", bukan cuma bergantian tampil */
    .slide-item.is-transitioning {
      display: block;
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      visibility: visible;
    }

    .slide-item.is-leaving {
      opacity: 0;
    }

    .slide-item.is-leaving.dir-next {
      transform: translateX(-28px) scale(.985);
    }

    .slide-item.is-leaving.dir-prev {
      transform: translateX(28px) scale(.985);
    }

    .slide-item.is-entering-start {
      opacity: 0;
      transition: none;
    }

    .slide-item.is-entering-start.dir-next {
      transform: translateX(28px) scale(.985);
    }

    .slide-item.is-entering-start.dir-prev {
      transform: translateX(-28px) scale(.985);
    }

    .active-slide .anim-up {
      animation: fadeInUp .5s ease forwards;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px)
      }

      to {
        opacity: 1;
        transform: translateY(0)
      }
    }

    .slide-stage {
      position: relative;
      z-index: 1;
      max-width: 1200px;
      margin: 0 auto;
    }

    /* ==========================================================
           DEKORASI AMBIENT — pola hexagon (tech/natural), starfield
           berkelap-kelip (dark/astronaut), bulatan blur mengambang,
           dan cincin outline, semua warnanya ikut token tema aktif
           (--lp-accent, --lp-highlight, --lp-line). Fixed di belakang
           semua konten, pointer-events:none supaya tidak mengganggu
           interaksi apa pun.
           ========================================================== */
    .stage-ambient {
      position: fixed;
      inset: 0;
      z-index: 0;
      pointer-events: none;
      overflow: hidden;
    }

    .amb-hex {
      position: absolute;
      inset: -20px;
      opacity: 0;
      transition: opacity .4s ease;
      background-image:
        linear-gradient(30deg, var(--lp-line) 12%, transparent 12.5%, transparent 87%, var(--lp-line) 87.5%, var(--lp-line)),
        linear-gradient(150deg, var(--lp-line) 12%, transparent 12.5%, transparent 87%, var(--lp-line) 87.5%, var(--lp-line)),
        linear-gradient(30deg, var(--lp-line) 12%, transparent 12.5%, transparent 87%, var(--lp-line) 87.5%, var(--lp-line)),
        linear-gradient(150deg, var(--lp-line) 12%, transparent 12.5%, transparent 87%, var(--lp-line) 87.5%, var(--lp-line)),
        linear-gradient(60deg, var(--lp-accent) 25%, transparent 25.5%, transparent 75%, var(--lp-accent) 75%, var(--lp-accent)),
        linear-gradient(60deg, var(--lp-accent) 25%, transparent 25.5%, transparent 75%, var(--lp-accent) 75%, var(--lp-accent));
      background-size: 64px 112px;
      background-position: 0 0, 0 0, 32px 56px, 32px 56px, 0 0, 32px 56px;
    }

    [data-theme="tech"] .amb-hex,
    [data-theme="natural"] .amb-hex {
      opacity: .5;
    }

    .amb-stars {
      position: absolute;
      inset: 0;
      opacity: 0;
      transition: opacity .4s ease;
    }

    .amb-stars::before,
    .amb-stars::after {
      content: '';
      position: absolute;
      inset: 0;
      background-repeat: repeat;
    }

    .amb-stars::before {
      background-image:
        radial-gradient(1.6px 1.6px at 40px 60px, rgba(255, 255, 255, .9) 50%, transparent 51%),
        radial-gradient(1.2px 1.2px at 180px 140px, rgba(255, 255, 255, .7) 50%, transparent 51%),
        radial-gradient(1.6px 1.6px at 300px 40px, rgba(255, 255, 255, .85) 50%, transparent 51%),
        radial-gradient(1.2px 1.2px at 120px 220px, rgba(255, 255, 255, .6) 50%, transparent 51%),
        radial-gradient(2px 2px at 260px 260px, rgba(255, 255, 255, .9) 50%, transparent 51%);
      background-size: 340px 320px;
      animation: amb-twinkle-a 5s ease-in-out infinite alternate;
    }

    .amb-stars::after {
      background-image:
        radial-gradient(1.2px 1.2px at 90px 30px, rgba(255, 255, 255, .65) 50%, transparent 51%),
        radial-gradient(1.6px 1.6px at 220px 90px, rgba(255, 255, 255, .85) 50%, transparent 51%),
        radial-gradient(1.2px 1.2px at 40px 180px, rgba(255, 255, 255, .55) 50%, transparent 51%),
        radial-gradient(1.6px 1.6px at 310px 200px, rgba(255, 255, 255, .8) 50%, transparent 51%);
      background-size: 360px 280px;
      animation: amb-twinkle-b 6.5s ease-in-out infinite alternate;
    }

    [data-theme="dark"] .amb-stars,
    [data-theme="astronaut"] .amb-stars {
      opacity: 1;
    }

    @keyframes amb-twinkle-a {
      from {
        opacity: .35;
      }

      to {
        opacity: 1;
      }
    }

    @keyframes amb-twinkle-b {
      from {
        opacity: .9;
      }

      to {
        opacity: .3;
      }
    }

    .amb-blob {
      position: absolute;
      border-radius: 50%;
      filter: blur(60px);
      opacity: .16;
    }

    .amb-blob-a {
      width: 340px;
      height: 340px;
      top: -100px;
      left: -80px;
      background: var(--lp-accent);
      animation: amb-float-a 22s ease-in-out infinite;
    }

    .amb-blob-b {
      width: 280px;
      height: 280px;
      bottom: -80px;
      right: -60px;
      background: var(--lp-highlight);
      animation: amb-float-b 26s ease-in-out infinite;
    }

    .amb-blob-c {
      width: 220px;
      height: 220px;
      top: 40%;
      right: 15%;
      background: var(--lp-accent);
      opacity: .10;
      animation: amb-float-c 30s ease-in-out infinite;
    }

    @keyframes amb-float-a {

      0%,
      100% {
        transform: translate(0, 0) scale(1);
      }

      50% {
        transform: translate(40px, 30px) scale(1.08);
      }
    }

    @keyframes amb-float-b {

      0%,
      100% {
        transform: translate(0, 0) scale(1);
      }

      50% {
        transform: translate(-30px, -40px) scale(1.05);
      }
    }

    @keyframes amb-float-c {

      0%,
      100% {
        transform: translate(0, 0);
      }

      50% {
        transform: translate(-25px, 35px);
      }
    }

    .amb-ring {
      position: absolute;
      border-radius: 50%;
      border: 1.5px solid var(--lp-accent);
      opacity: .12;
    }

    .amb-ring-a {
      width: 160px;
      height: 160px;
      top: 12%;
      right: 8%;
      animation: amb-spin-a 40s linear infinite;
    }

    .amb-ring-b {
      width: 90px;
      height: 90px;
      bottom: 18%;
      left: 6%;
      border-color: var(--lp-highlight);
      animation: amb-spin-b 32s linear infinite;
    }

    @keyframes amb-spin-a {
      from {
        transform: rotate(0deg) translateX(6px) rotate(0deg);
      }

      to {
        transform: rotate(360deg) translateX(6px) rotate(-360deg);
      }
    }

    @keyframes amb-spin-b {
      from {
        transform: rotate(0deg) translateX(-8px) rotate(0deg);
      }

      to {
        transform: rotate(-360deg) translateX(-8px) rotate(360deg);
      }
    }

    #slideCard {
      position: relative;
      z-index: 1;
    }

    .stage-nav-btn {
      position: fixed;
      top: 50%;
      transform: translateY(-50%);
      width: 48px;
      height: 48px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: color-mix(in srgb, var(--lp-panel) 60%, transparent);
      backdrop-filter: blur(8px);
      border: 1px solid var(--lp-line);
      color: var(--lp-ink);
      font-size: 1.3rem;
      opacity: .45;
      cursor: pointer;
      transition: all .25s ease;
      z-index: 1050;
      box-shadow: 0 6px 16px rgba(0, 0, 0, .08);
    }

    .stage-nav-btn:hover {
      opacity: 1;
      background: var(--lp-panel);
      transform: translateY(-50%) scale(1.06);
    }

    .stage-nav-btn:disabled {
      opacity: .15;
      cursor: not-allowed;
    }

    .stage-nav-prev {
      left: 24px;
    }

    .stage-nav-next {
      right: 24px;
    }

    @media (max-width: 991px) {
      .stage-nav-prev {
        left: 6px;
      }

      .stage-nav-next {
        right: 6px;
      }
    }

    .stage-nav-btn.stage-nav-finish {
      background: color-mix(in srgb, #28a745 55%, transparent);
      border-color: #28a745;
      color: #fff;
      opacity: .85;
    }

    /* ==========================================================
           INDIKATOR SLIDE — "melayang" (floating) di pojok kanan-bawah
           LAYAR, bukan lagi baris statis di footer card. Pakai
           position:fixed (pola sama seperti .stage-nav-btn di atas)
           supaya selalu menempel ke viewport, baik di layout biasa
           maupun saat #slideStage direparent ke <body> untuk fallback
           fullscreen (lihat enterFallbackFullscreen di script bawah).
           ========================================================== */
    .stage-slide-indicator {
      position: fixed;
      right: 24px;
      bottom: 22px;
      z-index: 1050;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 16px;
      border-radius: 999px;
      background: color-mix(in srgb, var(--lp-panel) 65%, transparent);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border: 1px solid var(--lp-line);
      font-family: var(--lp-font-mono, monospace);
      font-size: .78rem;
      font-weight: 600;
      letter-spacing: .03em;
      color: var(--lp-ink-soft);
      box-shadow: 0 6px 16px rgba(0, 0, 0, .08);
      opacity: .85;
      transition: opacity .2s ease, transform .2s ease;
      pointer-events: none;
      /* murni info, tidak perlu ditangkap klik */
    }

    .stage-slide-indicator:hover {
      opacity: 1;
    }

    @media (max-width: 991px) {
      .stage-slide-indicator {
        right: 10px;
        bottom: 10px;
        font-size: .72rem;
        padding: 6px 12px;
      }
    }

    .slide-stage.is-fullscreen .stage-slide-indicator {
      right: 28px;
      bottom: 28px;
    }

    .stage-fullscreen-btn {
      position: absolute;
      top: 14px;
      right: 14px;
      width: 38px;
      height: 38px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      background: color-mix(in srgb, var(--lp-panel) 70%, transparent);
      backdrop-filter: blur(6px);
      border: 1px solid var(--lp-line);
      color: var(--lp-ink-soft);
      cursor: pointer;
      z-index: 20;
      opacity: .55;
      transition: all .2s ease;
    }

    .stage-fullscreen-btn:hover {
      opacity: 1;
      background: var(--lp-panel);
    }

    /* ===== Mode fullscreen: CARD-nya sendiri yang mengisi penuh layar =====
           PENTING: kalau elemen ini masih berada di dalam DOM tree layout admin
           (bukan dipindah jadi anak <body> langsung), position:fixed bisa "terjebak"
           di dalam ancestor yang punya CSS transform (banyak template admin pakai
           transform di wrapper untuk animasi sidebar/transisi) — itu membuat
           containing block baru sehingga inset:0 tidak lagi relatif ke layar penuh,
           melainkan ke ukuran wrapper tsb. Makanya saat toggle fullscreen (lihat
           script di bawah), elemen ini SENGAJA dipindah jadi anak langsung <body>
           dulu sebelum class .is-fullscreen ditambahkan. */
    .slide-stage.is-fullscreen {
      position: fixed;
      inset: 0;
      width: 100vw;
      height: 100vh;
      height: 100dvh;
      /* lebih akurat di browser mobile (address bar naik-turun) */
      max-width: none;
      margin: 0;
      z-index: 9999;
      background: var(--lp-panel);
      display: flex;
    }

    .slide-stage.is-fullscreen #slideCard {
      width: 100%;
      height: 100%;
      max-width: none;
      max-height: none;
      min-height: 0 !important;
      /* timpa .min-vh-75 supaya height:100% yang berlaku */
      border-radius: 0;
      margin: 0;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 48px 8vw;
    }

    .slide-stage.is-fullscreen .stage-nav-prev {
      left: 24px;
    }

    .slide-stage.is-fullscreen .stage-nav-next {
      right: 24px;
    }

    .slide-stage.is-fullscreen .stage-fullscreen-btn {
      top: 24px;
      right: 24px;
    }
  </style>
@endsection

@section('content')
  <div class="stage-ambient" aria-hidden="true">
    <div class="amb-hex"></div>
    <div class="amb-stars"></div>
    <div class="amb-blob amb-blob-a"></div>
    <div class="amb-blob amb-blob-b"></div>
    <div class="amb-blob amb-blob-c"></div>
    <div class="amb-ring amb-ring-a"></div>
    <div class="amb-ring amb-ring-b"></div>
  </div>

  <div class="slide-stage" id="slideStage">

    <button type="button" class="stage-fullscreen-btn" id="btnFullscreen" onclick="toggleFullscreen()"
      title="Layar Penuh">
      <i class="ri ri-fullscreen-line" id="fullscreenIcon"></i>
    </button>

    <button type="button" class="stage-nav-btn stage-nav-prev" id="btn-prev" onclick="changeSlide(-1)"
      title="Sebelumnya">
      <i class="ri ri-arrow-left-s-line"></i>
    </button>

    <div class="card shadow-sm border-0 p-12 min-vh-75 position-relative" id="slideCard">
      <div class="slides-viewport" id="slidesViewport">
        @foreach ($topic->slides as $index => $slide)
          <div class="slide-item {{ $index === 0 ? 'active-slide' : '' }}" id="slide-wrapper-{{ $index + 1 }}">
            @switch($slide->type)
              @case('intro')
                @include('learning.slides.templates.intro', ['slide' => $slide])
              @break

              @case('timeline')
                @include('learning.slides.templates.timeline', ['slide' => $slide])
              @break

              @case('article')
                @include('learning.slides.templates.article', ['slide' => $slide])
              @break

              @case('gambar')
                @include('learning.slides.templates.gambar', ['slide' => $slide])
              @break

              @case('3d')
                @include('learning.slides.templates.model3d', ['slide' => $slide])
              @break

              @case('html')
                @include('learning.slides.templates.html', ['slide' => $slide])
              @break

              @case('quiz')
                @include('learning.slides.templates.quiz', ['slide' => $slide])
              @break

              @default
                <div class="p-3">{!! $slide->content_text !!}</div>
            @endswitch
          </div>
        @endforeach
      </div>
    </div>

    <button type="button" class="stage-nav-btn stage-nav-next" id="btn-next" onclick="changeSlide(1)"
      title="Selanjutnya">
      <i class="ri ri-arrow-right-s-line"></i>
    </button>

    {{-- Indikator "Slide X dari N" — melayang di pojok kanan-bawah LAYAR
         (position:fixed), lepas dari alur/footer card. Tetap anak langsung
         #slideStage supaya ikut terbawa saat reparent ke <body> di mode
         fallback fullscreen. --}}
    <div class="stage-slide-indicator" id="slide-indicator-wrap">
      <i class="ri ri-layout-grid-line" style="font-size:.85rem;"></i>
      <span id="slide-indicator">Slide 1 dari {{ $topic->slides->count() }}</span>
    </div>

  </div>

  <script>
    let currentSlide = 1;
    let isSlideAnimating = false;
    const totalSlides = {{ $topic->slides->count() }};
    const TRANSITION_MS = 380; // harus sama dengan durasi transition di CSS

    function updateSlideNavUI() {
      document.getElementById('slide-indicator').innerText = `Slide ${currentSlide} dari ${totalSlides}`;
      document.getElementById('btn-prev').disabled = (currentSlide === 1);

      const btnNext = document.getElementById('btn-next');
      if (currentSlide === totalSlides) {
        btnNext.innerHTML = '<i class="ri ri-file-edit-line"></i>';
        btnNext.title = 'Mulai Kuis Akhir';
        btnNext.classList.add('stage-nav-finish');
        btnNext.onclick = function() {
          window.location.href = "{{ route('learning.quiz.show', $topic->id) }}";
        };
      } else {
        btnNext.innerHTML = '<i class="ri ri-arrow-right-s-line"></i>';
        btnNext.title = 'Selanjutnya';
        btnNext.classList.remove('stage-nav-finish');
        btnNext.onclick = function() {
          changeSlide(1);
        };
      }
    }

    function changeSlide(direction) {
      if (isSlideAnimating) return; // cegah klik beruntun merusak animasi yang sedang jalan

      const nextIndex = currentSlide + direction;
      if (nextIndex < 1 || nextIndex > totalSlides) return;

      isSlideAnimating = true;
      const dirClass = direction > 0 ? 'dir-next' : 'dir-prev';
      const outgoing = document.getElementById(`slide-wrapper-${currentSlide}`);
      const incoming = document.getElementById(`slide-wrapper-${nextIndex}`);
      const viewport = document.getElementById('slidesViewport');

      // Kunci tinggi viewport dulu (pakai tinggi slide yang sedang tampil)
      // supaya layout tidak "kolaps" sesaat karena kedua slide akan sama-sama
      // jadi position:absolute selama transisi berlangsung.
      viewport.style.minHeight = outgoing.offsetHeight + 'px';

      // Siapkan slide baru: taruh di posisi "mulai" (di luar area, sesuai arah)
      // tanpa transisi dulu, supaya tidak ikut ke-animasikan saat baru muncul.
      incoming.classList.add('is-transitioning', 'is-entering-start', dirClass);
      outgoing.classList.add('is-transitioning', dirClass);

      void incoming.offsetWidth; // paksa reflow, supaya posisi awal di atas benar-benar diterapkan browser

      // Jalankan animasi: slide lama geser+fade pergi, slide baru geser+fade masuk ke tengah
      outgoing.classList.remove('active-slide');
      outgoing.classList.add('is-leaving');
      incoming.classList.remove('is-entering-start');
      incoming.classList.add('active-slide');

      setTimeout(() => {
        outgoing.classList.remove('is-transitioning', 'is-leaving', dirClass);
        incoming.classList.remove('is-transitioning', dirClass);
        viewport.style.minHeight = '';

        currentSlide = nextIndex;
        updateSlideNavUI();
        isSlideAnimating = false;
      }, TRANSITION_MS);
    }

    // ===== Fullscreen =====
    // Menyimpan posisi asli #slideStage di DOM, supaya bisa dikembalikan
    // persis seperti semula saat keluar dari mode fallback fullscreen.
    let fsOriginalParent = null;
    let fsOriginalNextSibling = null;

    function enterFallbackFullscreen(stage) {
      // Pindahkan #slideStage jadi anak LANGSUNG dari <body> — ini kunci
      // perbaikannya. Kalau elemen ini dibiarkan di dalam struktur layout
      // admin (yang mungkin punya ancestor ber-transform), position:fixed
      // bisa jadi tidak benar-benar relatif ke layar, sehingga tidak penuh
      // secara vertikal. Dengan dipindah ke <body>, masalah itu hilang total.
      fsOriginalParent = stage.parentNode;
      fsOriginalNextSibling = stage.nextSibling;
      document.body.appendChild(stage);
      document.body.style.overflow = 'hidden';
      stage.classList.add('is-fullscreen');
      updateFullscreenIcon(true);
    }

    function exitFallbackFullscreen(stage) {
      stage.classList.remove('is-fullscreen');
      document.body.style.overflow = '';
      if (fsOriginalParent) {
        if (fsOriginalNextSibling) {
          fsOriginalParent.insertBefore(stage, fsOriginalNextSibling);
        } else {
          fsOriginalParent.appendChild(stage);
        }
        fsOriginalParent = null;
        fsOriginalNextSibling = null;
      }
      updateFullscreenIcon(false);
    }

    function toggleFullscreen() {
      const stage = document.getElementById('slideStage');
      const isNativeFs = document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement;
      const isFallbackFs = stage.classList.contains('is-fullscreen');

      if (!isNativeFs && !isFallbackFs) {
        // Coba native Fullscreen API dulu (paling andal — dirender di top
        // layer browser, tidak terpengaruh ancestor manapun)
        const request = stage.requestFullscreen || stage.webkitRequestFullscreen || stage.msRequestFullscreen;
        if (request) {
          request.call(stage).catch(() => {
            // Native gagal/diblokir -> pakai fallback yang aman (reparent ke body)
            enterFallbackFullscreen(stage);
          });
        } else {
          // Browser tidak mendukung native Fullscreen sama sekali (banyak
          // terjadi di browser mobile/webview) -> langsung fallback
          enterFallbackFullscreen(stage);
        }
      } else {
        if (isNativeFs) {
          const exit = document.exitFullscreen || document.webkitExitFullscreen || document.msExitFullscreen;
          if (exit) exit.call(document);
        }
        if (isFallbackFs) {
          exitFallbackFullscreen(stage);
        }
      }
    }

    function syncFullscreenUI() {
      const stage = document.getElementById('slideStage');
      const isNativeFs = !!(document.fullscreenElement || document.webkitFullscreenElement || document
        .msFullscreenElement);
      // Hanya sinkronkan status native di sini — status fallback dikelola
      // langsung oleh enterFallbackFullscreen()/exitFallbackFullscreen().
      if (isNativeFs || !stage.classList.contains('is-fullscreen')) {
        stage.classList.toggle('is-fullscreen', isNativeFs);
        updateFullscreenIcon(isNativeFs);
      }
    }

    function updateFullscreenIcon(isActive) {
      const icon = document.getElementById('fullscreenIcon');
      if (icon) {
        icon.className = isActive ? 'ri ri-fullscreen-exit-line' : 'ri ri-fullscreen-line';
      }
    }

    document.addEventListener('fullscreenchange', syncFullscreenUI);
    document.addEventListener('webkitfullscreenchange', syncFullscreenUI);
    document.addEventListener('MSFullscreenChange', syncFullscreenUI);

    document.addEventListener('DOMContentLoaded', () => {
      document.getElementById('btn-prev').disabled = true;
    });
  </script>
@endsection
