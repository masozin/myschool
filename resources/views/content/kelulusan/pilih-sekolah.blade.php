@extends('layouts/layoutMaster')

@section('title', 'Pilih Sekolah — ' . $judul)

@section('content')
  <div class="mb-4">
    <h4 class="mb-1">Pilih Sekolah</h4>
    <p class="text-muted mb-0">
      Anda masuk sebagai <strong>Admin</strong> yang tidak terikat ke satu sekolah.
      Pilih sekolah yang ingin dibuka di menu {{ $judul }} — pilihan ini berlaku juga untuk menu kelulusan lainnya
      selama sesi ini.
    </p>
  </div>

  <div class="row g-4">
    @forelse ($daftarSekolah as $s)
      <div class="col-12 col-md-6 col-lg-4">
        <a href="{{ url($urlTujuan . '?sekolah_id=' . $s->id) }}" class="text-decoration-none">
          <div class="card h-100">
            <div class="card-body d-flex align-items-center gap-3 py-4">
              <div class="avatar avatar-lg flex-shrink-0">
                <span class="avatar-initial rounded-circle bg-label-primary">
                  <i class="icon-base ri ri-school-line ri-24px"></i>
                </span>
              </div>
              <div>
                <h5 class="mb-0">{{ $s->detailTerbaru->nama_sekolah ?? 'Sekolah #' . $s->id }}</h5>
                <small class="text-muted">Jenjang {{ strtoupper($s->jenjang ?? '-') }}</small>
              </div>
            </div>
          </div>
        </a>
      </div>
    @empty
      <div class="col-12">
        <div class="alert alert-warning mb-0">Belum ada data sekolah. Buat data sekolah terlebih dahulu.</div>
      </div>
    @endforelse
  </div>
@endsection
