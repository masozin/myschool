@extends('layouts/layoutMaster')

@section('title', 'Model Jadwal — Jadwal Pelajaran')

@section('content')

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  <div class="card mb-4">
    <div class="card-body py-4 d-flex align-items-center gap-4 flex-wrap">
      <div class="avatar avatar-xl flex-shrink-0">
        <span class="avatar-initial rounded-circle bg-label-primary">
          <i class="icon-base ri ri-time-line ri-28px"></i>
        </span>
      </div>
      <div class="flex-grow-1">
        <h4 class="mb-1">Model Jadwal</h4>
        <div class="text-muted small">
          Kerangka waktu {{ $sekolah->detailTerbaru->nama_sekolah ?? '-' }}
          @if ($semester)
            <span class="mx-1">•</span> Semester {{ $semester->semester }} {{ $semester->tahun_pelajaran }}
          @endif
        </div>
      </div>
      <a href="{{ route('jadwal-pelajaran.index') }}" class="btn btn-outline-secondary">
        <i class="ri ri-arrow-left-line me-1"></i> Kembali
      </a>
      <a href="{{ route('jadwal-pelajaran.pengaturan.model.baru') }}" class="btn btn-primary">
        <i class="ri ri-add-line me-1"></i> Tambah Model
      </a>
    </div>
  </div>

  <div class="alert alert-info">
    Model jadwal digunakan untuk kerangka pembuatan jadwal dan template impor jadwal.
  </div>

  <div class="card mb-4">
    <div class="table-responsive">
      <table class="table mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th>Nama Model</th>
            <th>Kerangka</th>
            <th class="text-center">Dipakai</th>
            <th class="text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($models as $m)
            @php
              $hariModel = is_array($m->kerangka) ? $m->kerangka : [];
              $totalJp = array_sum(array_map(fn($f) => (int) ($f['jumlah_jam'] ?? 0), $hariModel));
            @endphp
            <tr>
              <td class="fw-semibold">{{ $m->nama }}</td>
              <td>{{ count($hariModel) }} hari aktif · {{ $totalJp }} jam/minggu</td>
              <td class="text-center">{{ $pemakai[$m->id] ?? 0 }} rombel</td>
              <td class="text-end">
                <a href="{{ route('jadwal-pelajaran.pengaturan.model', $m->id) }}" class="btn btn-sm btn-primary">
                  <i class="ri ri-settings-3-line me-1"></i> Atur Kerangka
                </a>
                <form method="POST" action="{{ route('jadwal-pelajaran.pengaturan.model.hapus', $m->id) }}"
                  class="d-inline" onsubmit="return confirm('Hapus model &quot;{{ $m->nama }}&quot;? Rombel pemakainya akan mengikuti model lain / pola bawaan.');">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="ri ri-delete-bin-line"></i>
                  </button>
                </form>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center text-muted py-4">Belum ada model jadwal — semua rombel mengikuti
                pola bawaan. Buat model pertama lewat tombol Tambah Model di atas.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

@endsection
