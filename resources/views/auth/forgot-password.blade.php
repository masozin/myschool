@php
  use Illuminate\Support\Facades\Route;
  $configData = Helper::appClasses();
  $customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/blankLayout')

@section('title', 'Forgot Password')

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
      <!-- /Left Section -->
      <div class="d-none d-lg-flex col-lg-7 col-xl-8 align-items-center justify-content-center p-12 pb-2">
        <img
          src="{{ asset('assets/img/illustrations/auth-forgot-password-illustration-' . $configData['theme'] . '.png') }}"
          class="auth-cover-illustration w-100" alt="auth-illustration"
          data-app-light-img="illustrations/auth-forgot-password-illustration-light.png"
          data-app-dark-img="illustrations/auth-forgot-password-illustration-dark.png" />
        <img
          src="{{ asset('assets/img/illustrations/auth-cover-forgot-password-mask-' . $configData['theme'] . '.png') }}"
          class="authentication-image" alt="mask"
          data-app-light-img="illustrations/auth-cover-forgot-password-mask-light.png"
          data-app-dark-img="illustrations/auth-cover-forgot-password-mask-dark.png" />
      </div>
      <!-- /Left Section -->

      <!-- Forgot Password -->
      <div class="d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg p-sm-12 p-6">
        <div class="w-px-400 mx-auto">
          <h4>Lupa Password?</h4>
          <div class="my-4"> Untuk akun PTK, silahkan hubungi Operator Sekolah masing-masing</div>
          <div> Untuk akun Operator Sekolah, silahkan hubungi <a href="https://wa.me/6281279373439"
              target="blank">Admin</a></div>
        </div>

        <div class=" d-none w-px-400 mx-auto">
          <h4 class="mb-1">Forgot Password? 🔒</h4>
          <p class="mb-5">Enter your email and we'll send you instructions to reset your password</p>
          @if (session('status'))
            <div class="mb-1 text-success">
              {{ session('status') }}
            </div>
          @endif
          <form id="formAuthentication" class="mb-5" action="{{ route('password.email') }}" method="POST">
            @csrf
            <div class="form-floating form-floating-outline mb-5">
              <input type="text" class="form-control @error('email') is-invalid @enderror" id="email"
                name="email" placeholder="john@example.com" autofocus>
              <label for="email" class="form-label">Email</label>
              @error('email')
                <span class="invalid-feedback" role="alert">
                  <span class="fw-medium">{{ $message }}</span>
                </span>
              @enderror
            </div>
            <button type="submit" class="btn btn-primary d-grid w-100 mb-5">Send Reset Link</button>
          </form>
          <div class="text-center">
            @if (Route::has('login'))
              <a href="{{ route('login') }}" class="d-flex align-items-center justify-content-center">
                <i class="icon-base ri ri-arrow-left-s-line scaleX-n1-rtl icon-20px me-1_5"></i>
                Back to login
              </a>
            @endif
          </div>
        </div>
      </div>
      <!-- /Forgot Password -->
    </div>
  </div>
@endsection
