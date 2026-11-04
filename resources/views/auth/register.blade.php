@php
  use Illuminate\Support\Facades\Route;
  $configData = Helper::appClasses();
  $customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/blankLayout')

@section('title', 'Register Pages')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
  <div class="authentication-wrapper authentication-cover">
    <!-- Logo -->
    <a href="{{ url('/') }}" class="auth-cover-brand d-flex align-items-center gap-2">
      <span class="app-brand-logo demo">@include('_partials.macros')</span>
      <span class="app-brand-text demo text-heading fw-semibold">{{ config('variables.templateName') }}</span>
    </a>
    <!-- /Logo -->
    <div class="authentication-inner row m-0">
      <!-- Left Text -->
      <div class="d-none d-lg-flex col-lg-7 col-xl-8 align-items-center justify-content-center p-12 pb-2">
        <img src="{{ asset('assets/img/illustrations/auth-register-illustration-' . $configData['theme'] . '.png') }}"
          class="auth-cover-illustration w-100" alt="auth-illustration"
          data-app-light-img="illustrations/auth-register-illustration-light.png"
          data-app-dark-img="illustrations/auth-register-illustration-dark.png" />
        <img src="{{ asset('assets/img/illustrations/auth-cover-register-mask-' . $configData['theme'] . '.png') }}"
          class="authentication-image" alt="mask"
          data-app-light-img="illustrations/auth-cover-register-mask-light.png"
          data-app-dark-img="illustrations/auth-cover-register-mask-dark.png" />
      </div>
      <!-- /Left Text -->

      <!-- Register -->
      <div
        class="d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg position-relative py-sm-12 px-12 py-6">
        <div class="w-px-400 mx-auto pt-12 pt-lg-0">
          <h4 class="mb-1">Registrasi Sekolah 🚀</h4>
          <p class="mb-5">Silakan lengkapi data di bawah ini untuk mendaftar via WhatsApp.</p>

          <!-- Form diubah ID nya agar mudah ditangkap oleh JavaScript, hapus method POST dan action -->
          <form id="formRegistrasiWA" class="mb-5">

            <!-- Field Sekolah -->
            <div class="form-floating form-floating-outline mb-4">
              <input type="text" class="form-control" id="sekolah" required placeholder="Contoh: SDN 1 Jakarta" />
              <label for="sekolah">Nama Sekolah</label>
            </div>

            <!-- Field NPSN -->
            <div class="form-floating form-floating-outline mb-4">
              <input type="number" class="form-control" id="npsn" required placeholder="Masukkan 8 Digit NPSN" />
              <label for="npsn">NPSN</label>
            </div>

            <!-- Field Jenjang (Menggunakan Select agar seragam) -->
            <div class="form-floating form-floating-outline mb-4">
              <select id="jenjang" class="form-select" required>
                <option value="" disabled selected>Pilih Jenjang...</option>
                <option value="SD">SD</option>
                <option value="SMP" disabled>SMP</option>
                <option value="SMA" disabled>SMA</option>
                <option value="SMK" disabled>SMK</option>
              </select>
              <label for="jenjang">Jenjang</label>
            </div>

            <!-- Field Alamat (Menggunakan Textarea) -->
            <div class="form-floating form-floating-outline mb-4">
              <textarea class="form-control" id="alamat" style="height: 100px" required placeholder="Jalan..."></textarea>
              <label for="alamat">Alamat Lengkap</label>
            </div>

            <!-- Field Kepala Sekolah -->
            <div class="form-floating form-floating-outline mb-4">
              <input type="text" class="form-control" id="kepsek" required
                placeholder="Nama Lengkap Kepala Sekolah" />
              <label for="kepsek">Nama Kepala Sekolah</label>
            </div>

            <!-- Field NIP KS -->
            <div class="form-floating form-floating-outline mb-5">
              <input type="number" class="form-control" id="nip" placeholder="Masukkan NIP Kepala Sekolah" />
              <label for="nip">NIP Kepala Sekolah</label>
            </div>

            <button type="submit" class="btn btn-primary w-100">
              <i class="ri ri-whatsapp-line me-2"></i> Kirim via WhatsApp
            </button>
          </form>

          <p class="text-center mb-5">
            <span>Sudah punya akun?</span>
            @if (Route::has('login'))
              <a href="{{ route('login') }}">
                <span>Masuk di sini</span>
              </a>
            @endif
          </p>

        </div>
      </div>
      <!-- /Register -->
    </div>
  </div>
@endsection

<!-- Script untuk memproses data ke WhatsApp -->
@section('page-script')
  <script>
    document.getElementById('formRegistrasiWA').addEventListener('submit', function(e) {
      // Mencegah form melakukan refresh halaman (submit bawaan)
      e.preventDefault();

      // 1. Ambil nilai dari masing-masing input
      let sekolah = document.getElementById('sekolah').value;
      let npsn = document.getElementById('npsn').value;
      let jenjang = document.getElementById('jenjang').value;
      let alamat = document.getElementById('alamat').value;
      let kepsek = document.getElementById('kepsek').value;
      let nip = document.getElementById('nip').value;

      // 2. GANTI DENGAN NOMOR WA ADMIN ANDA
      // Format wajib menggunakan kode negara (62 untuk Indonesia) tanpa tanda + atau angka 0 di depan
      let nomorWAAdmin = "6281279373439";

      // 3. Rangkai pesan WhatsApp dengan format yang rapi (menggunakan Markdown WA seperti *tebal*)
      let pesanWA =
        `Kepada Yth Admin My School, kami dari sekolah *${sekolah}*, ingin mengajukan akun My School sebagai berikut:\n\n` +
        `Nama Sekolah: *${sekolah}*\n` +
        `NPSN: *${npsn}*\n` +
        `Jenjang: *${jenjang}*\n` +
        `Alamat: *${alamat}*\n` +
        `Kepala Sekolah: *${kepsek}*\n` +
        `NIP: *${nip}*\n\n` +
        `Mohon informasi lebih lanjut untuk proses pendaftaran. Terima kasih.\n\n`;

      // 4. Ubah teks pesan menjadi format URL yang valid
      let pesanEncode = encodeURIComponent(pesanWA);

      // 5. Buat Link API WhatsApp
      let linkWA = `https://wa.me/${nomorWAAdmin}?text=${pesanEncode}`;

      // 6. Buka link WhatsApp di tab baru
      window.open(linkWA, '_blank');
    });
  </script>
@endsection
