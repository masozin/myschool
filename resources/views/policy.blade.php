@php
$configData = Helper::appClasses();
$customizerHidden = 'customizer-hide';
@endphp

@extends('layouts/blankLayout')

@section('title', 'Privacy Policy')

@section('page-style')
<!-- Page -->
@vite('resources/assets/vendor/scss/pages/page-auth.scss')
@endsection

@section('content')
<div class="container-xxl">
  <div class="authentication-wrapper authentication-basic container-p-y">
    <div class="authentication-inner">
      <div class="card">
        <div class="card-body">
          <!-- Logo -->
          <div class="app-brand justify-content-center">
            <a href="{{ url('/') }}" class="app-brand-link gap-2">
              <span class="app-brand-logo demo">@include('_partials.macros')</span>
              <span class="app-brand-text demo text-heading fw-bold">{{ config('variables.templateName') }}</span>
            </a>
          </div>
          <!-- /Logo -->
          {!! $policy !!}
        </div>
      </div>
    </div>
    <img alt="mask" src="{{asset('assets/img/illustrations/auth-basic-login-mask-'.$configData['style'].'.png') }}"
      class="authentication-image d-none d-lg-block" data-app-light-img="illustrations/auth-basic-login-mask-light.png"
      data-app-dark-img="illustrations/auth-basic-login-mask-dark.png" />
  </div>
</div>
@endsection