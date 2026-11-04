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
  <!-- Google Model Viewer untuk Render 3D -->
  <script type="module" src="https://ajax.googleapis.com/ajax/libs/model-viewer/3.4.0/model-viewer.min.js"></script>

  <!-- Custom Style Khusus Ruang Belajar Siswa -->
  <style>
    body {
      background-color: #f4f6f9 !important;
      font-family: 'Public Sans', sans-serif;
    }

    /* Top Bar Pembelajaran */
    .learning-navbar {
      background: #ffffff;
      border-bottom: 2px solid #e9ecef;
      padding: 12px 24px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
    }

    /* Container Materi Utama */
    .learning-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px 15px;
    }

    /* Styling Card Slide Materi */
    .learning-card {
      border: none;
      border-radius: 16px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
      background: #ffffff;
      overflow: hidden;
    }

    /* Model Viewer 3D Container */
    model-viewer {
      width: 100%;
      height: 420px;
      background-color: #f8f9fa;
      border-radius: 12px;
      --poster-color: transparent;
    }

    /* Custom Hotspot 3D Badge */
    .hotspot-btn {
      background: #007bff;
      color: white;
      border: 2px solid white;
      border-radius: 50%;
      width: 32px;
      height: 32px;
      font-weight: bold;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
      transition: transform 0.2s ease;
    }

    .hotspot-btn:hover {
      transform: scale(1.2);
      background: #0056b3;
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
          <h5 class="mb-0 fw-bold text-dark">{{ $topicTitle ?? 'Judul Materi Pembelajaran' }}</h5>
        </div>
      </div>

      <!-- Sisi Kanan: Progress Belajar & Tombol Keluar -->
      <div class="d-flex align-items-center gap-3">
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
@endsection
