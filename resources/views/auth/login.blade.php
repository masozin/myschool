@php
  use Illuminate\Support\Facades\Route;
  $configData = Helper::appClasses();
  $customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/blankLayout')

@section('title', 'Login - Administrasi Sekolah')

@section('page-style')
  @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('content')
  <div class="authentication-wrapper authentication-cover">
    <!-- Logo -->
    <a href="{{ url('/') }}" class="auth-cover-brand d-flex align-items-center gap-2">
      <span class="app-brand-logo demo">@include('_partials.macros')</span>
      <span class="app-brand-text demo text-heading fw-bold fs-4">{{ config('variables.templateName') }}</span>
    </a>
    <!-- /Logo -->
    <div class="authentication-inner row m-0">
      <!-- /Left Section -->
      <div class="d-none d-lg-flex col-lg-7 col-xl-8 align-items-center justify-content-center p-12 pb-2">
        <img src="{{ asset('assets/img/illustrations/auth-login-illustration-' . $configData['theme'] . '.png') }}"
          class="auth-cover-illustration w-100" alt="auth-illustration"
          data-app-light-img="illustrations/auth-login-illustration-light.png"
          data-app-dark-img="illustrations/auth-login-illustration-dark.png" />
        <img alt="mask"
          src="{{ asset('assets/img/illustrations/auth-basic-login-mask-' . $configData['theme'] . '.png') }}"
          class="authentication-image d-none d-lg-block"
          data-app-light-img="illustrations/auth-basic-login-mask-light.png"
          data-app-dark-img="illustrations/auth-basic-login-mask-dark.png" />
      </div>
      <!-- /Left Section -->

      <!-- Login Form Section -->
      <div
        class="d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg position-relative py-sm-12 px-12 py-6">
        <div class="w-px-400 mx-auto pt-12 pt-lg-0">

          <!-- Header Teks -->
          <div class="text-center text-lg-start mb-6">
            <h4 class="mb-2 fw-bold">MY School</h4>
            <p class="text-muted mb-0">Silakan login untuk mengelola data sekolah.</p>
          </div>

          <!-- Alert Pesan Sukses -->
          @if (session('status'))
            <div class="alert alert-success d-flex align-items-center mb-5 rounded-3 shadow-sm" role="alert">
              <i class="ri-checkbox-circle-fill me-2 fs-5"></i>
              <div>{{ session('status') }}</div>
            </div>
          @endif

          <form id="formAuthentication" class="mb-5" action="{{ route('login') }}" method="POST">
            @csrf

            <!-- Input User/Email -->
            <div class="form-floating form-floating-outline mb-5">
              <input type="text" class="form-control @error('email') is-invalid @enderror" id="login-email"
                name="email" placeholder="admin@sekolah.com / NIP" autofocus value="{{ old('email') }}">
              <label for="login-email" class="form-label">Email atau Username</label>
              @error('email')
                <span class="invalid-feedback" role="alert">
                  <span class="fw-medium">{{ $message }}</span>
                </span>
              @enderror
            </div>

            <!-- Input Password -->
            <div class="mb-5">
              <div class="form-password-toggle">
                <div class="input-group input-group-merge @error('password') is-invalid @enderror">
                  <div class="form-floating form-floating-outline">
                    <input type="password" id="login-password"
                      class="form-control @error('password') is-invalid @enderror" name="password"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                      aria-describedby="password" />
                    <label class="form-label" for="login-password">Password</label>
                  </div>
                  <span class="input-group-text cursor-pointer"><i class="ri ri-eye-off-line icon-20px"></i></span>
                </div>
                @error('password')
                  <span class="invalid-feedback" role="alert">
                    <span class="fw-medium">{{ $message }}</span>
                  </span>
                @enderror
              </div>
            </div>

            <!-- Dropdown Semester -->
            <div class="form-floating form-floating-outline mb-5">
              <select name="semester_id" id="semester_id" class="form-select fw-medium">
                @foreach ($semesters as $sem)
                  <option value="{{ $sem->id }}" {{ $sem->is_active ? 'selected' : '' }}>
                    {{ $sem->tahun_pelajaran }} - {{ $sem->semester }}
                    {{ $sem->is_active ? '(Aktif)' : '' }}
                  </option>
                @endforeach
              </select>
              <label for="semester_id">Tahun Ajaran</label>
            </div>

            <!-- Checkbox & Forgot Password -->
            <div class="mb-5 d-flex justify-content-between flex-wrap py-2 gap-2">
              <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="remember-me" name="remember"
                  {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label text-muted" for="remember-me"> Ingat saya </label>
              </div>
              @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="fw-medium text-primary mb-1">
                  Lupa Password?
                </a>
              @endif
            </div>

            <!-- Button Submit -->
            <button class="btn btn-primary d-grid w-100 shadow-sm" type="submit">
              <span class="d-flex align-items-center justify-content-center gap-2">
                Masuk <i class="ri-login-box-line"></i>
              </span>
            </button>
          </form>

          <!-- Register Link -->
          <p class="text-center text-muted">
            <span>Belum memiliki akun? </span>
            @if (Route::has('register'))
              <a href="{{ url('register') }}" class="fw-medium text-primary">
                <span>Registrasi</span>
              </a>
            @endif
          </p>

          <!-- Footer/Divider -->
          <div class="divider my-5">
            <div class="divider-text text-muted">
              <small>Created with <i class="ri ri-heart-fill text-danger align-middle"></i> by <span
                  class="fw-bold">OzinStq</span></small>
            </div>
          </div>

        </div>
      </div>
      <!-- /Login Form Section -->
    </div>
  </div>
@endsection
