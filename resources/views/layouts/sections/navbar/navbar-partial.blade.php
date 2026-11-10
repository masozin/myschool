@php
  use Illuminate\Support\Facades\Auth;
  use Illuminate\Support\Facades\Route;
@endphp

<!--  Brand demo (display only for navbar-full and hide on below xl) -->
@if (isset($navbarFull))
  <div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-6">
    <a href="{{ url('/') }}" class="app-brand-link gap-2">
      <span class="app-brand-logo demo">@include('_partials.macros')</span>
      <span class="app-brand-text demo menu-text fw-semibold ms-1">{{ config('variables.templateName') }}</span>
    </a>

    <!-- Display menu close icon only for horizontal-menu with navbar-full -->
    @if (isset($menuHorizontal))
      <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-xl-none">
        <i class="icon-base ri ri-close-line icon-sm"></i>
      </a>
    @endif
  </div>
@endif

<!-- ! Not required for layout-without-menu -->
@if (!isset($navbarHideToggle))
  <div
    class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0 {{ isset($menuHorizontal) ? ' d-xl-none ' : '' }} {{ isset($contentNavbar) ? ' d-xl-none ' : '' }}">
    <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
      <i class="icon-base ri ri-menu-line icon-md"></i>
    </a>
  </div>
@endif

<div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
  @if ($configData['hasCustomizer'] == true)
    <div class="navbar-nav align-items-center">
      @if (Auth::check())
        <div class="nav-item me-3 d-none d-md-flex align-items-center">
          <div class="text-end me-3">
            {{-- Nama Sekolah --}}
            <div class="h4 mt-3 text-truncate">
              {{ Auth::user()->hasRole('Admin') ? 'Super Admin Panel' : Auth::user()->ptk->sekolah->detailTerbaru->nama_sekolah ?? '-' }}
              | {{ $namaSemester ?? '-' }}
            </div>
          </div>
        </div>
      @endif
    </div>
  @endif
  <ul class="navbar-nav flex-row align-items-center ms-auto">
    <li class="nav-item dropdown me-2 me-xl-0">
      <a class="nav-link dropdown-toggle hide-arrow btn btn-icon btn-text-secondary rounded-pill" id="nav-theme"
        href="javascript:void(0);" data-bs-toggle="dropdown">
        <i class="icon-base ri ri-sun-line icon-md theme-icon-active"></i>
        <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-theme-text">
        <li>
          <button type="button" class="dropdown-item align-items-center active" data-bs-theme-value="light"
            aria-pressed="false">
            <span><i class="icon-base ri ri-sun-line icon-22px me-3" data-icon="sun-line"></i>Light</span>
          </button>
        </li>
        <li>
          <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark"
            aria-pressed="true">
            <span><i class="icon-base ri ri-moon-clear-line icon-22px me-3" data-icon="moon-clear-line"></i>Dark</span>
          </button>
        </li>
        <li>
          <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="system"
            aria-pressed="false">
            <span><i class="icon-base ri ri-computer-line icon-22px me-3" data-icon="computer-line"></i>System</span>
          </button>
        </li>
      </ul>
    </li>
    {{-- Nama + jabatan pengguna di samping avatar; dua baris
         berline-height rapat agar tinggi navbar tidak berubah --}}
    @auth
      <li class="nav-item d-none d-md-flex align-items-center me-2">
        <div class="text-end lh-sm">
          <div class="fw-semibold text-truncate" style="max-width: 200px;">{{ Auth::user()->name }}</div>
          <small class="text-body-secondary d-block text-truncate" style="max-width: 200px;">
            {{ Auth::user()?->ptk?->jabatan ?? (Auth::user()->getRoleNames()->first() ?: 'Pengguna') }}
          </small>
        </div>
      </li>
    @endauth
    <li class="nav-item navbar-dropdown dropdown-user dropdown">
      <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
        <div class="avatar avatar-online">
          @php
            $photo = Auth::user()?->ptk?->photo;
          @endphp
          <img src="{{ $photo ? asset('storage/' . $photo) : asset('assets/img/avatars/1.png') }}" alt="avatar"
            class="rounded-circle" />
        </div>
      </a>
      <ul class="dropdown-menu dropdown-menu-end mt-3 py-2">
        <li>
          <a class="dropdown-item"
            href="{{ Route::has('user.profil.index') ? route('user.profil.index') : 'javascript:void(0);' }}">
            <div class="d-flex align-items-center">
              <div class="flex-shrink-0 me-2">
                <div class="avatar avatar-online">
                  @php
                    $photo = Auth::user()?->ptk?->photo;
                  @endphp
                  <img src="{{ $photo ? asset('storage/' . $photo) : asset('assets/img/avatars/1.png') }}"
                    alt="avatar" class="rounded-circle" />
                </div>
              </div>
              <div class="flex-grow-1">
                <h6 class="mb-0 small">
                  @if (Auth::check())
                    {{ Auth::user()->name }}
                  @else
                    John Doe
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
        @if (Auth::check() && Laravel\Jetstream\Jetstream::hasApiFeatures())
          <li>
            <a class="dropdown-item" href="{{ route('api-tokens.index') }}"> <i
                class="icon-base ri ri-settings-4-line icon-22px me-3"></i><span class="align-middle">Settings</span>
            </a>
          </li>
        @endif
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
        @else
          <li>
            <div class="d-grid px-4 pt-2 pb-1">
              <a class="btn btn-danger d-flex"
                href="{{ Route::has('login') ? route('login') : url('auth/login-basic') }}">
                <small class="align-middle">Login</small>
                <i class="icon-base ri ri-logout-box-r-line ms-2 icon-16px"></i>
              </a>
            </div>
          </li>
        @endif
      </ul>
    </li>
    <!--/ User -->
  </ul>
</div>
