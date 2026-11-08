@isset($pageConfigs)
{!! Helper::updatePageConfig($pageConfigs) !!}
@endisset
@php
$configData = Helper::appClasses();
@endphp
@extends('layouts/commonMaster')

@php
/* Display elements */
$contentNavbar = $contentNavbar ?? true;
$containerNav = $containerNav ?? 'container-xxl';
$isNavbar = $isNavbar ?? true;
$isMenu = $isMenu ?? true;
$isFlex = $isFlex ?? false;
$isFooter = $isFooter ?? true;
$customizerHidden = $customizerHidden ?? '';

/* HTML Classes */
$navbarDetached = 'navbar-detached';
$menuFixed = isset($configData['menuFixed']) ? $configData['menuFixed'] : '';
if (isset($navbarType)) {
$configData['navbarType'] = $navbarType;
}
$navbarType = isset($configData['navbarType']) ? $configData['navbarType'] : '';
$footerFixed = isset($configData['footerFixed']) ? $configData['footerFixed'] : '';
$menuCollapsed = isset($configData['menuCollapsed']) ? $configData['menuCollapsed'] : '';

/* Content classes */
$container =
isset($configData['contentLayout']) && $configData['contentLayout'] === 'compact'
? 'container-xxl'
: 'container-fluid';

@endphp

@section('layoutContent')
<div class="layout-wrapper layout-content-navbar {{ $isMenu ? '' : 'layout-without-menu' }}">
    <div class="layout-container">

        @if ($isMenu)
        @include('layouts/sections/menu/verticalMenu')
        @endif


        <!-- Layout page -->
        <div class="layout-page">

            {{-- Below commented code read by artisan command while installing jetstream. !! Do not remove if you want to use jetstream. --}}
            <x-banner />

            <!-- BEGIN: Navbar-->
            @if ($isNavbar)
            @include('layouts/sections/navbar/navbar')
            @endif
            <!-- END: Navbar-->


            <!-- Content wrapper -->
            <div class="content-wrapper">

                <!-- Content -->
                @if ($isFlex)
                <div class="{{ $container }} d-flex align-items-stretch flex-grow-1 p-0">
                    @else
                    <div class="{{ $container }} flex-grow-1 container-p-y">
                        @endif

                        {{-- Spanduk kunci semester: tampil di semua halaman bila semester
                             sesi bukan semester aktif (SKHU/SKL punya spanduk angkatannya
                             sendiri, jadi dikecualikan di sini) --}}
                        @auth
                            @if (!str_starts_with(request()->path(), 'skhu') && !str_starts_with(request()->path(), 'skl'))
                                @php
                                    $semSesiBanner = session('semester_id')
                                        ? \App\Models\Semester::find(session('semester_id'))
                                        : null;
                                @endphp
                                @if ($semSesiBanner && !$semSesiBanner->is_active && !$semSesiBanner->kunci_dibuka)
                                    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" role="alert">
                                        <i class="icon-base ri ri-lock-line"></i>
                                        <span><strong>Mode lihat saja.</strong> Anda sedang membuka semester
                                            {{ $semSesiBanner->tahun_pelajaran }} {{ $semSesiBanner->semester }} yang bukan
                                            semester aktif — data tidak dapat diubah atau dihapus. Admin dapat membuka
                                            kunci semester ini dari halaman Semester.</span>
                                    </div>
                                @elseif($semSesiBanner && !$semSesiBanner->is_active && $semSesiBanner->kunci_dibuka)
                                    <div class="alert alert-warning d-flex align-items-center gap-2 mb-4" role="alert">
                                        <i class="icon-base ri ri-lock-unlock-line"></i>
                                        <span>Semester {{ $semSesiBanner->tahun_pelajaran }} {{ $semSesiBanner->semester }}
                                            bukan semester aktif, tetapi kuncinya sedang <strong>dibuka oleh Admin</strong>
                                            — perubahan data diizinkan.</span>
                                    </div>
                                @endif
                            @endif
                        @endauth

                        @yield('content')

                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
                    @if ($isFooter)
                    @include('layouts/sections/footer/footer')
                    @endif
                    <!-- / Footer -->
                    <div class="content-backdrop fade"></div>
                </div>
                <!--/ Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        @if ($isMenu)
        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
        @endif
        <!-- Drag Target Area To SlideIn Menu On Small Screens -->
        <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->
    @endsection
