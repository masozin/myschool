@php
  $customizerHidden = 'customizer-hide';
  $configData = Helper::appClasses();
@endphp
@extends('layouts/layoutMaster')
@section('title', 'Sekolah Belum Aktif')
@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-misc.scss'])
@endsection
@section('content')
  <div class="misc-wrapper">
    <h4 class="mb-2">Sekolah Anda Belum Aktif di Semester Ini 🔒</h4>
    <p class="mb-3 mx-2">
      Operator Sekolah belum mengaktifkan data untuk semester yang sedang berjalan.
      Silakan hubungi Operator Sekolah Anda untuk melengkapi data terlebih dahulu.
    </p>
    <div class="d-flex justify-content-center mt-12">
      <img src="{{ asset('assets/img/illustrations/misc-not-authorized-object.png') }}" alt="misc-not-authorized"
        class="img-fluid misc-object d-none d-lg-inline-block" width="190" />
      <img src="{{ asset('assets/img/illustrations/misc-bg-' . $configData['theme'] . '.png') }}" alt="misc-not-authorized"
        class="misc-bg d-none d-lg-inline-block" data-app-light-img="illustrations/misc-bg-light.png"
        data-app-dark-img="illustrations/misc-bg-dark.png" />
      <div class="d-flex flex-column align-items-center">
        <img src="{{ asset('assets/img/illustrations/misc-not-authorized-illustration.png') }}" alt="misc-not-authorized"
          class="img-fluid z-1" width="160" />
        <div>
          @auth
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="btn btn-primary text-center my-10">Logout</button>
            </form>
          @else
            <a href="{{ route('login') }}" class="btn btn-primary text-center my-10">Login</a>
          @endauth
        </div>
      </div>
    </div>
  </div>
@endsection
