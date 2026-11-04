@php
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
$configData = Helper::appClasses();
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/blankLayout')

@section('title', 'Verify Email')

@section('page-style')
<!-- Page -->
@vite('resources/assets/vendor/scss/pages/page-auth.scss')
@endsection

@section('content')
<div class="authentication-wrapper authentication-cover">
  <!-- Logo -->
  <a href="{{url('/')}}" class="auth-cover-brand d-flex align-items-center gap-2">
    <span class="app-brand-logo demo">@include('_partials.macros')</span>
    <span class="app-brand-text demo text-heading fw-semibold">{{ config('variables.templateName') }}</span>
  </a>
  <!-- /Logo -->
  <div class="authentication-inner row m-0">
    <!-- /Left Section -->
    <div class="d-none d-lg-flex col-lg-7 col-xl-8 align-items-center justify-content-center p-12 pb-2">
      <img src="{{asset('assets/img/illustrations/auth-verify-email-illustration-'.$configData['theme'].'.png')}}"
        class="auth-cover-illustration w-100" alt="auth-illustration"
        data-app-light-img="illustrations/auth-verify-email-illustration-light.png"
        data-app-dark-img="illustrations/auth-verify-email-illustration-dark.png" />
      <img alt="mask" src="{{asset('assets/img/illustrations/auth-basic-login-mask-'.$configData['theme'].'.png')}}"
        class="authentication-image d-none d-lg-block"
        data-app-light-img="illustrations/auth-basic-login-mask-light.png"
        data-app-dark-img="illustrations/auth-basic-login-mask-dark.png" />
    </div>
    <!-- /Left Section -->

    <!--  Verify email -->
    <div
      class="d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg position-relative py-sm-12 px-12 py-6">
      <div class="w-px-400 mx-auto pt-5 pt-lg-0">
        <h4 class="mb-1">Verify your email ✉️</h4>
        @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success" role="alert">
          <div class="alert-body">
            A new verification link has been sent to the email address you provided during registration.
          </div>
        </div>
        @endif
        <p class="text-start mb-0">Account activation link sent to your email address: <span
            class="h6">{{ Auth::user()->email }}</span> Please follow the link inside to continue.</p>
        <div class="mt-5 d-flex flex-column gap-2">
          <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="w-100 btn btn-label-secondary">Click here to request
              another</button>
          </form>

          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-100 btn btn-danger">Log Out</button>
          </form>
        </div>
      </div>
    </div>
    <!-- / Verify email -->
  </div>
</div>
@endsection