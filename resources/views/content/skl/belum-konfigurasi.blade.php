@extends('layouts/layoutMaster')

@section('title', 'SKL — Konfigurasi Belum Diatur')

@section('content')
  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
      <div class="card text-center py-4">
        <div class="card-body">
          <div class="avatar avatar-xl mx-auto mb-3">
            <span class="avatar-initial rounded-circle bg-label-warning">
              <i class="icon-base ri ri-settings-3-line ri-30px"></i>
            </span>
          </div>
          <h4 class="mb-2">Konfigurasi SKL belum diatur</h4>
          <p class="text-muted mb-1">
            Konfigurasi SKL untuk semester {{ $semester->kode_semester ?? '' }} belum diatur.
          </p>
          <p class="mb-0">
            Silakan hubungi <strong>Operator Sekolah</strong> untuk mengatur konfigurasi SKL
            (mode input nilai) terlebih dahulu — setelah itu menu ini dapat digunakan.
          </p>
        </div>
      </div>
    </div>
  </div>
@endsection
