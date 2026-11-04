@php
  $customizerHidden = 'customizer-hide';
  $configData = Helper::appClasses();
@endphp
@extends('layouts/layoutMaster')
@section('title', 'Fitur Tidak Tersedia')
@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection
@section('content')
  <div class="misc-wrapper">
    <h1 class="mb-2 mx-2" style="font-size: 6rem; line-height: 6rem;">🔒</h1>
    <h4 class="mb-2">Fitur Belum Aktif</h4>
    <p class="mb-1 mx-2">
      Fitur <strong>{{ ucwords(str_replace('-', ' ', $kodeFitur)) }}</strong> belum diaktifkan untuk sekolah Anda.
    </p>
    <p class="mb-3 mx-2 text-muted">
      Silakan hubungi Admin untuk mengaktifkan fitur ini.
    </p>
    <div class="d-flex justify-content-center mt-12">
      <img src="{{ asset('assets/img/illustrations/misc-not-authorized-object.png') }}" alt="fitur-nonaktif"
        class="img-fluid misc-object d-none d-lg-inline-block" width="190" />
      <img src="{{ asset('assets/img/illustrations/misc-bg-' . $configData['theme'] . '.png') }}" alt="fitur-nonaktif"
        class="misc-bg d-none d-lg-inline-block" data-app-light-img="illustrations/misc-bg-light.png"
        data-app-dark-img="illustrations/misc-bg-dark.png" />
      <div class="d-flex flex-column align-items-center">
        <img src="{{ asset('assets/img/illustrations/misc-not-authorized-illustration.png') }}" alt="fitur-nonaktif"
          class="img-fluid z-1" width="160" />
        <div class="d-flex gap-2 my-10">
          <a href="{{ url('/') }}" class="btn btn-primary text-center">Kembali ke Beranda</a>
          <a href="https://wa.me/62xxxxxxxxxx?text=Halo%20Admin%2C%20saya%20ingin%20mengaktifkan%20fitur%20{{ $kodeFitur }}"
            target="_blank" class="btn btn-outline-secondary text-center">
            Hubungi Admin
          </a>
        </div>
      </div>
    </div>
  </div>
@endsection
