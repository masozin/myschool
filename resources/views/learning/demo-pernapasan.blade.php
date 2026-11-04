@extends('layouts.learningLayout')

@php
  $subjectName = 'IPAS Kelas 5 SD';
  $topicTitle = 'Sistem Pernapasan Manusia';
  $backUrl = url('/dashboard');
  $exitUrl = url('/dashboard');
@endphp

@section('content')
  <div class="card learning-card p-4">

    <!-- ==================== SLIDE 1: PENGENALAN ORGAN ==================== -->
    <div id="slide-1" class="slide-content">
      <div class="row align-items-center">
        <!-- Kolom Kiri: 3D Viewer -->
        <div class="col-md-6 mb-3 mb-md-0 text-center">
          <div class="badge bg-label-info mb-2">💡 Putar & Zoom Model 3D dengan Mouse/Jari</div>

          <!-- Contoh Model 3D Sample (Bisa diganti asset lokal nanti: src="{{ asset('assets/models/paru_paru.glb') }}") -->
          <model-viewer src="https://modelviewer.dev/shared-assets/models/Astronaut.glb" alt="Model 3D Pernapasan"
            auto-rotate camera-controls>
          </model-viewer>
        </div>

        <!-- Kolom Kanan: Teks Penjelasan -->
        <div class="col-md-6">
          <span class="badge bg-primary mb-2">Slide 1 dari 2</span>
          <h3 class="fw-bold text-primary">Apa itu Pernapasan?</h3>
          <p class="fs-5 text-secondary">
            Bernapas adalah proses menghirup gas <span class="text-success fw-bold">Oksigen (O₂)</span> dan menghembuskan
            gas <span class="text-danger fw-bold">Karbondioksida (CO₂)</span>.
          </p>

          <div class="alert alert-primary d-flex align-items-center" role="alert">
            <i class="bx bx-info-circle fs-4 me-2"></i>
            <div>
              <b>Tahukah Kamu?</b> Paru-paru manusia dewasa dapat menampung hingga <b>6 liter udara</b>!
            </div>
          </div>

          <ul class="list-group list-group-flush mb-3">
            <li class="list-group-item">🫁 <b>Organ Utama:</b> Hidung, Tenggorokan, dan Paru-Paru.</li>
            <li class="list-group-item">⚡ <b>Fungsi Utama:</b> Menyediakan energi untuk seluruh tubuh.</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- ==================== SLIDE 2: DETAIL ORGAN & HOTSPOT ==================== -->
    <div id="slide-2" class="slide-content d-none">
      <div class="row align-items-center">
        <!-- Kolom Kiri: 3D Viewer dengan Hotspot Klik -->
        <div class="col-md-6 mb-3 mb-md-0 text-center">
          <div class="badge bg-label-warning mb-2">👇 Klik Angka 1 atau 2 untuk Detail Organ</div>

          <model-viewer id="3d-viewer-slide2" src="https://modelviewer.dev/shared-assets/models/Astronaut.glb"
            alt="Model 3D Paru-Paru Detail" camera-controls>

            <!-- Hotspot 1 -->
            <button class="hotspot-btn" slot="hotspot-1" data-position="0m 1.5m 0.2m"
              onclick="showOrganDetail('Bagian Atas Paru-Paru', 'Tempat masuknya udara dari saluran tenggorokan.')">
              1
            </button>

            <!-- Hotspot 2 -->
            <button class="hotspot-btn" slot="hotspot-2" data-position="0m 0.8m 0.2m"
              onclick="showOrganDetail('Alveolus (Kantung Udara)', 'Tempat bertukarnya Oksigen dan Karbondioksida secara langsung dengan darah.')">
              2
            </button>
          </model-viewer>
        </div>

        <!-- Kolom Kanan: Teks & Card Detail Hotspot -->
        <div class="col-md-6">
          <span class="badge bg-primary mb-2">Slide 2 dari 2</span>
          <h3 class="fw-bold text-primary">Struktur Dalam Paru-Paru</h3>
          <p class="text-secondary">
            Klik titik nomor pada objek 3D di sebelah kiri untuk melihat fungsi spesifik dari setiap bagian organ!
          </p>

          <!-- Dynamic Detail Card dari Hotspot -->
          <div id="organ-detail-card" class="card bg-label-primary border-primary border p-3 mt-3">
            <h5 id="organ-title" class="fw-bold text-primary mb-1">👈 Klik Titik 3D</h5>
            <p id="organ-desc" class="mb-0 text-dark">Pilih angka 1 atau 2 pada gambar 3D untuk melihat penjelasan
              detailnya.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ==================== FOOTER NAVIGASI SLIDE ==================== -->
    <div class="card-footer bg-transparent border-top pt-3 mt-4 d-flex justify-content-between align-items-center px-0">
      <!-- Tombol Prev -->
      <button id="btn-prev" class="btn btn-outline-secondary" onclick="changeSlide(1)" disabled>
        <i class="bx bx-chevron-left me-1"></i> Sebelumnya
      </button>

      <!-- Indikator Slide -->
      <div class="d-flex align-items-center gap-2">
        <span id="dot-1" class="badge rounded-pill bg-primary p-2 cursor-pointer" onclick="changeSlide(1)"
          style="width: 12px; height: 12px;"> </span>
        <span id="dot-2" class="badge rounded-pill bg-secondary p-2 cursor-pointer" onclick="changeSlide(2)"
          style="width: 12px; height: 12px;"> </span>
        <span id="slide-text-indicator" class="ms-2 fw-bold text-muted small">Slide 1 dari 2</span>
      </div>

      <!-- Tombol Next / Kuis -->
      <button id="btn-next" class="btn btn-primary" onclick="changeSlide(2)">
        Selanjutnya <i class="bx bx-chevron-right ms-1"></i>
      </button>
    </div>

  </div>

  <!-- JavaScript Logika Navigasi Slide -->
  <script>
    let currentSlide = 1;
    const totalSlides = 2;

    function changeSlide(slideNumber) {
      currentSlide = slideNumber;

      // Sembunyikan semua slide
      document.querySelectorAll('.slide-content').forEach(el => el.classList.add('d-none'));

      // Tampilkan slide yang dipilih
      document.getElementById(`slide-${currentSlide}`).classList.remove('d-none');

      // Update Status Tombol Navigasi
      const btnPrev = document.getElementById('btn-prev');
      const btnNext = document.getElementById('btn-next');
      const textIndicator = document.getElementById('slide-text-indicator');

      if (currentSlide === 1) {
        btnPrev.disabled = true;
        btnNext.className = "btn btn-primary";
        btnNext.innerHTML = 'Selanjutnya <i class="bx bx-chevron-right ms-1"></i>';
        btnNext.setAttribute('onclick', 'changeSlide(2)');

        document.getElementById('dot-1').className = 'badge rounded-pill bg-primary p-2 cursor-pointer';
        document.getElementById('dot-2').className = 'badge rounded-pill bg-secondary p-2 cursor-pointer';
      } else {
        btnPrev.disabled = false;
        btnNext.className = "btn btn-success";
        btnNext.innerHTML = 'Mulai Kuis 📝';
        btnNext.setAttribute('onclick', "alert('Selamat! Kamu telah menyelesaikan slide. Kuis akan dimulai!')");

        document.getElementById('dot-1').className = 'badge rounded-pill bg-secondary p-2 cursor-pointer';
        document.getElementById('dot-2').className = 'badge rounded-pill bg-primary p-2 cursor-pointer';
      }

      textIndicator.innerText = `Slide ${currentSlide} dari ${totalSlides}`;
    }

    // Fungsi untuk menampilkan detail organ saat hotspot 3D diklik
    function showOrganDetail(title, description) {
      document.getElementById('organ-title').innerText = title;
      document.getElementById('organ-desc').innerText = description;
    }
  </script>
@endsection
