@extends('layouts/layoutMaster')

@section('title', 'Jadwal Pelajaran')

@section('content')

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  {{-- Pemilih konteks sekolah untuk Admin --}}
  @hasanyrole('Admin')
    <div class="card mb-4">
      <div class="card-body py-3 d-flex align-items-center gap-3 flex-wrap">
        <span class="text-muted"><i class="icon-base ri ri-school-line me-1"></i>Anda masuk sebagai Admin — konteks
          sekolah:</span>
        <form method="GET" action="{{ url('/jadwal-pelajaran') }}">
          <select name="sekolah_id" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach ($daftarSekolah as $ds)
              <option value="{{ $ds->id }}" {{ $ds->id === $sekolahId ? 'selected' : '' }}>
                {{ $ds->detailTerbaru->nama_sekolah ?? 'Sekolah #' . $ds->id }}</option>
            @endforeach
          </select>
        </form>
      </div>
    </div>
  @endhasanyrole

  @if (!$sekolahId)
    <div class="alert alert-warning">Pilih sekolah dulu lewat pemilih di atas — daftar rombel mengikuti sekolah yang
      dipilih.</div>
  @else
    <div class="card mb-4">
      <div class="card-body py-4 d-flex align-items-center gap-4 flex-wrap">
        <div class="avatar avatar-xl flex-shrink-0">
          <span class="avatar-initial rounded-circle bg-label-primary">
            <i class="icon-base ri ri-calendar-schedule-line ri-28px"></i>
          </span>
        </div>
        <div class="flex-grow-1">
          <h4 class="mb-1">Jadwal Pelajaran</h4>
          <div class="text-muted small">
            {{ $sekolah->detailTerbaru->nama_sekolah ?? '-' }}
            @if ($semester)
              <span class="mx-1">•</span> Semester {{ $semester->semester }} {{ $semester->tahun_pelajaran }}
            @endif
          </div>
        </div>
        @if ($bolehSusun)
          <a href="{{ route('jadwal-pelajaran.pengaturan') }}" class="btn btn-outline-primary">
            <i class="ri ri-time-line me-1"></i> Pengaturan Jam
          </a>
        @endif
      </div>
    </div>

    @if (!$pengaturanAda)
      <div class="alert alert-info">
        Kerangka jam belum diatur untuk semester ini — jadwal memakai pola bawaan (Senin–Kamis 8 jam, Jumat 6, Sabtu 7,
        mulai 07.00, 35 menit per jam).
        @if ($bolehSusun)
          Atur lewat tombol <strong>Pengaturan Jam</strong> di atas bila pola sekolah berbeda.
        @endif
      </div>
    @endif

    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Rombel</th>
              <th>Wali Kelas</th>
              <th class="text-center">Pengampu</th>
              <th class="text-center">Kotak Terisi</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($rombels as $r)
              <tr>
                <td class="fw-semibold">{{ $r->nama_rombel }}</td>
                <td>{{ $r->ptk->nama_ptk ?? '-' }}</td>
                <td class="text-center">{{ $pengampuPerRombel[$r->id] ?? 0 }} mapel</td>
                <td class="text-center">{{ $jadwalPerRombel[$r->id] ?? 0 }} / {{ $totalKotak }} kotak</td>
                <td class="text-end">
                  @if ($bolehSusun)
                    <a href="{{ route('jadwal-pelajaran.pengampu', $r) }}"
                      class="btn btn-sm btn-outline-secondary me-1">
                      <i class="ri ri-user-star-line me-1"></i>Pengampu
                    </a>
                  @endif
                  <a href="{{ route('jadwal-pelajaran.editor', $r) }}" class="btn btn-sm btn-primary">
                    <i class="ri ri-calendar-2-line me-1"></i>{{ $bolehSusun ? 'Editor' : 'Lihat Jadwal' }}
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                  <i class="ri ri-inbox-line me-1"></i> Belum ada rombel di sekolah ini pada semester yang sedang
                  dibuka.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  @endif

@endsection
