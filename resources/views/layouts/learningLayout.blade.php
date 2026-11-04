@isset($pageConfigs)
  {!! Helper::updatePageConfig($pageConfigs) !!}
@endisset

@php
  $configData = Helper::appClasses();
  /* Pakai layout front/blank style agar tampilan bersih dari sidebar admin */
  $isFront = true;
@endphp

@extends('layouts/commonMaster')

@section('title', $title ?? 'Media Pembelajaran IPAS')

<head>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">

  <!-- Google Model Viewer untuk Render 3D -->
  <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.4.0/model-viewer.min.js"></script>

  <!-- Font: Fraunces (judul/display, serif ekspresif) + Inter (body) + JetBrains Mono (label kecil) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500&display=swap"
    rel="stylesheet">

  {{-- Token tema — WAJIB tampil sebelum konten dirender, dan sebelum @yield('content') --}}
  @include('learning.partials._theme-tokens')

  {{-- Typography untuk output editor Quill (materi, info, dsb) --}}
  @include('learning.partials._rich-content')

  {{--
    Set atribut data-theme SEDINI mungkin (inline, bukan di file JS terpisah)
    supaya tidak ada "flash" warna default sebelum tema tersimpan diterapkan.
  --}}
  <script>
    (function() {
      var saved = localStorage.getItem('lp-theme') || 'tech';
      document.documentElement.setAttribute('data-theme', saved);
    })();
  </script>

  <style>
    .learning-navbar {
      background: var(--lp-panel);
      border-bottom: 2px solid var(--lp-line);
      padding: 12px 24px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    }

    .learning-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px 15px;
    }

    .learning-card {
      border: none;
      border-radius: var(--lp-radius);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
      background: var(--lp-panel);
      overflow: hidden;
    }

    /* ---- Theme switcher control ---- */
    .theme-switcher .btn {
      border-radius: 999px;
      border-color: var(--lp-line);
      color: var(--lp-ink);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .theme-switcher .dropdown-menu {
      border-radius: 14px;
      border: 1px solid var(--lp-line);
      padding: 8px;
      min-width: 190px;
    }

    .theme-swatch-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 8px 10px;
      border-radius: 10px;
      cursor: pointer;
      font-size: .875rem;
    }

    .theme-swatch-item:hover {
      background: var(--lp-accent-soft);
    }

    .theme-swatch-item.active {
      background: var(--lp-accent-soft);
      font-weight: 600;
    }

    .theme-swatch-dot {
      width: 16px;
      height: 16px;
      border-radius: 50%;
      border: 2px solid #fff;
      box-shadow: 0 0 0 1px var(--lp-line);
      flex-shrink: 0;
    }

    .theme-swatch-dot.dot-tech {
      background: linear-gradient(135deg, #0EA5A4, #F5A623);
    }

    .theme-swatch-dot.dot-dark {
      background: linear-gradient(135deg, #171D2C, #4CC9C0);
    }

    .theme-swatch-dot.dot-astronaut {
      background: linear-gradient(135deg, #0B0E1A, #7C9CFF);
    }

    .theme-swatch-dot.dot-natural {
      background: linear-gradient(135deg, #5B8C5A, #C97A3D);
    }
  </style>
</head>

@section('layoutContent')
  <!-- TOP NAVBAR KHUSUS SISWA -->
  <nav class="learning-navbar sticky-top">
    <div class="d-flex justify-content-between align-items-center max-width-1200 mx-auto">

      <!-- Sisi Kiri: Tombol Kembali & Judul Materi -->
      <div class="d-flex align-items-center gap-3">
        <a href="{{ $backUrl ?? url('/dashboard') }}" class="btn btn-icon btn-outline-secondary rounded-circle"
          data-bs-toggle="tooltip" title="Keluar ke Menu Utama">
          <i class="bx bx-arrow-back fs-4"></i>
        </a>
        <div>
          <span class="badge bg-label-primary mb-1">{{ $subjectName ?? 'IPAS Kelas 5' }}</span>
          <h5 class="mb-0 fw-bold" style="color:var(--lp-ink)">{{ $topicTitle ?? 'Judul Materi Pembelajaran' }}</h5>
        </div>
      </div>

      <!-- Sisi Kanan: Tema, Progress Belajar & Tombol Keluar -->
      <div class="d-flex align-items-center gap-3">

        {{-- ============ THEME SWITCHER ============ --}}
        <div class="dropdown theme-switcher">
          <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"
            aria-expanded="false">
            <i class='bx bx-palette'></i> Tema
          </button>
          <ul class="dropdown-menu dropdown-menu-end" id="theme-menu">
            <li>
              <div class="theme-swatch-item" data-theme-value="tech" onclick="setLearningTheme('tech')">
                <span class="theme-swatch-dot dot-tech"></span> Tech
              </div>
            </li>
            <li>
              <div class="theme-swatch-item" data-theme-value="dark" onclick="setLearningTheme('dark')">
                <span class="theme-swatch-dot dot-dark"></span> Gelap
              </div>
            </li>
            <li>
              <div class="theme-swatch-item" data-theme-value="astronaut" onclick="setLearningTheme('astronaut')">
                <span class="theme-swatch-dot dot-astronaut"></span> Astronot
              </div>
            </li>
            <li>
              <div class="theme-swatch-item" data-theme-value="natural" onclick="setLearningTheme('natural')">
                <span class="theme-swatch-dot dot-natural"></span> Natural
              </div>
            </li>
          </ul>
        </div>

        @isset($currentSlide)
          <div class="d-none d-md-block text-end" style="min-width: 150px;">
            <small class="text-muted d-block mb-1">Kemajuan Belajar</small>
            <div class="progress" style="height: 8px;">
              <div class="progress-bar bg-success" role="progressbar"
                style="width: {{ ($currentSlide / ($totalSlides ?? 1)) * 100 }}%" aria-valuenow="{{ $currentSlide }}"
                aria-valuemin="1" aria-valuemax="{{ $totalSlides ?? 1 }}">
              </div>
            </div>
          </div>
          <span class="fw-bold text-primary fs-6">{{ $currentSlide }}/{{ $totalSlides ?? 1 }}</span>
        @endisset

        <a href="{{ $exitUrl ?? url('/dashboard') }}" class="btn btn-danger btn-sm rounded-pill px-3">
          <i class="bx bx-x me-1"></i> Keluar
        </a>
      </div>

    </div>
  </nav>

  <!-- MAIN CONTENT AREA -->
  <main class="learning-container">
    @yield('content')
  </main>

  <script>
    function setLearningTheme(name) {
      document.documentElement.setAttribute('data-theme', name);
      localStorage.setItem('lp-theme', name);
      document.querySelectorAll('.theme-swatch-item').forEach(el => {
        el.classList.toggle('active', el.dataset.themeValue === name);
      });
    }
    document.addEventListener('DOMContentLoaded', () => {
      setLearningTheme(localStorage.getItem('lp-theme') || 'tech');
    });
  </script>
@endsection
