@php
  use Illuminate\Support\Facades\Route;
  use Illuminate\Support\Facades\Auth;
  $currentRouteName = Route::currentRouteName();
  $activeRoutes = ['front-pages-pricing', 'front-pages-payment', 'front-pages-checkout', 'front-pages-help-center'];
  $activeClass = in_array($currentRouteName, $activeRoutes) ? 'active' : '';
@endphp
<!-- Navbar: Start -->
<nav class="layout-navbar shadow-none py-0">
  <div class="container">
    <div class="navbar navbar-expand-lg landing-navbar px-3 px-md-8">
      <!-- Menu logo wrapper: Start -->
      <div class="navbar-brand app-brand demo d-flex py-0 me-4 me-xl-8">
        {{-- <!-- Mobile menu toggle: Start-->
        <button class="navbar-toggler border-0 px-0 me-4" type="button" data-bs-toggle="collapse"
          data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
          aria-label="Toggle navigation">
          <i class="icon-base ri ri-menu-fill icon-lg align-middle text-heading fw-medium"></i>
        </button>
        <!-- Mobile menu toggle: End--> --}}
        <a href="javascript:;" class="app-brand-link">
          <span class="app-brand-logo demo">@include('_partials.macros')</span>
          <span class="app-brand-text demo menu-text fw-semibold ms-2 ps-1">{{ config('variables.templateName') }}</span>
        </a>
      </div>
      <!-- Menu logo wrapper: End -->
      <!-- Menu wrapper: Start -->
      <div class="collapse navbar-collapse landing-nav-menu" id="navbarSupportedContent">
        {{-- <button class="navbar-toggler border-0 text-heading position-absolute end-0 top-0 p-2" type="button"
          data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
          aria-expanded="false" aria-label="Toggle navigation">
          <i class="icon-base ri ri-close-fill"></i>
        </button>
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <a class="nav-link fw-medium" aria-current="page"
              href="{{ url('front-pages/landing') }}#landingHero">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-medium" href="{{ url('/cek-skhu') }}">Cek SKHU</a>
          </li>
        </ul> --}}
      </div>
      <div class="landing-menu-overlay d-lg-none"></div>
      <!-- Menu wrapper: End -->
      <!-- Toolbar: Start -->
      @if (Auth::check())
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
          <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
            <div class="avatar avatar-online">
              <img
                src="{{ Auth::user()->ptk && Auth::user()->ptk->photo ? asset('storage/' . Auth::user()->ptk->photo) : asset('assets/img/avatars/1.png') }}"
                alt="avatar" class="rounded-circle" />
            </div>
          </a>
          <ul class="dropdown-menu dropdown-menu-end mt-3 py-2">
            <li>
              <a class="dropdown-item"
                href="{{ Route::has('user.profil.index') ? route('user.profil.index') : 'javascript:void(0);' }}">
                <div class="d-flex align-items-center">
                  <div class="flex-shrink-0 me-2">
                    <div class="avatar avatar-online">
                      <img
                        src="{{ Auth::user()->ptk && Auth::user()->ptk->photo ? asset('storage/' . Auth::user()->ptk->photo) : asset('assets/img/avatars/1.png') }}"
                        alt="alt" class="w-px-40 h-auto rounded-circle" />
                    </div>
                  </div>
                  <div class="flex-grow-1">
                    <h6 class="mb-0 small">
                      @if (Auth::check())
                        {{ Auth::user()->name }}
                      @else
                        User
                      @endif
                    </h6>
                    <small class="text-body-secondary">{{ Auth::user()->ptk->jabatan ?? 'Admin' }}</small>
                  </div>
                </div>
              </a>
            </li>
            <li>
              <div class="dropdown-divider"></div>
            </li>
            <li>
              <a class="dropdown-item"
                href="{{ Route::has('user.profil.index') ? route('user.profil.index') : 'javascript:void(0);' }}">
                <i class="icon-base ri ri-user-3-line icon-22px me-2"></i> <span class="align-middle">My
                  Profile</span> </a>
            </li>
            <li>
              <div class="dropdown-divider my-1"></div>
            </li>
            @if (Auth::check())
              <li>
                <div class="d-grid px-4 pt-2 pb-1">
                  <a class="btn btn-danger d-flex" href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <small class=" align-middle">Logout</small>
                    <i class="icon-base ri ri-logout-box-r-line ms-2 icon-16px"></i>
                  </a>
                </div>
              </li>
              <form method="POST" id="logout-form" action="{{ route('logout') }}">
                @csrf
              </form>
            @endif
          </ul>
        </li>
      @else
        <li>
          <a href="{{ Route::has('login') ? route('login') : url('auth/login-basic') }}"
            class="btn btn-primary px-2 px-sm-4 px-lg-2 px-xl-4">
            <span class="icon-base ri ri-user-line me-md-1"></span><span class="d-none d-md-block">Login</span>
          </a>
        </li>
      @endif
      <!-- Toolbar: End -->
    </div>
  </div>
</nav>
<!-- Navbar: End -->
