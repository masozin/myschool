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
    </div>
  </div>

  <div class="alert alert-info">
    Setiap rombel otomatis memakai model yang rentang tingkatnya mencakup tingkat rombel itu — misalnya model
    <strong>Kelas Bawah</strong> untuk tingkat 1–3 dan <strong>Kelas Atas</strong> untuk tingkat 4–6, masing-masing
    dengan durasi dan jumlah jamnya sendiri. Tingkat yang tidak tercakup model mana pun mengikuti model umum
    (rentang tingkat kosong); bila belum ada model sama sekali, yang berlaku pola bawaan.
  </div>

  <div class="card mb-4">
    <div class="table-responsive">
      <table class="table mb-0 align-middle">
        <thead class="table-light">
          <tr>
            <th>Nama Model</th>
            <th>Rentang Tingkat</th>
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
              <td>{{ $m->labelRentang() }}</td>
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
              <td colspan="5" class="text-center text-muted py-4">Belum ada model jadwal — semua rombel mengikuti
                pola bawaan. Buat model pertama di bawah ini.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header pb-3">
      <h5 class="mb-1">Tambah Model</h5>
      <small class="text-muted">Model baru dibuat dari pola bawaan; kerangkanya langsung bisa disesuaikan setelah
        dibuat.</small>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('jadwal-pelajaran.pengaturan.model.tambah') }}">
        @csrf
        <div class="row g-3 align-items-end">
          <div class="col-12 col-md-5">
            <label class="form-label" for="nama-model-baru">Nama Model</label>
            <input type="text" id="nama-model-baru" class="form-control" name="nama" value="{{ old('nama') }}"
              maxlength="50" placeholder="mis. Kelas Bawah" required>
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label" for="dari-baru">Tingkat Dari</label>
            <input type="number" id="dari-baru" class="form-control" name="tingkat_dari"
              value="{{ old('tingkat_dari') }}" min="1" max="12" placeholder="Semua">
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label" for="sampai-baru">Tingkat Sampai</label>
            <input type="number" id="sampai-baru" class="form-control" name="tingkat_sampai"
              value="{{ old('tingkat_sampai') }}" min="1" max="12" placeholder="Semua">
          </div>
          <div class="col-12 col-md-3">
            <button type="submit" class="btn btn-primary">
              <i class="ri ri-add-line me-1"></i> Buat Model
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

@endsection
