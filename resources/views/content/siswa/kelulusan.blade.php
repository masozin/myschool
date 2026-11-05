{{-- resources/views/content/siswa/kelulusan.blade.php --}}
@extends('layouts/contentNavbarLayout')

@section('title', 'Proses Kelulusan')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="fw-bold mb-1">Proses Kelulusan</h4>
  <p class="text-muted mb-4">
    Siswa di bawah ini berstatus registrasi <strong>aktif</strong> tetapi tidak memiliki rombel aktif
    di semester ini — umumnya kelas akhir setelah proses kenaikan kelas. Centang siswa yang akan
    diluluskan, tentukan tanggal kelulusannya, lalu proses sekaligus. Siswa yang diluluskan akan
    berpindah ke halaman <a href="{{ route('siswa.alumni', ['sekolah_id' => $sekolahId]) }}">Alumni</a>.
  </p>

  @if (session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if (session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  @if ($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
  @endif

  @if ($kandidat->isEmpty())
  <div class="alert alert-info">
    Tidak ada kandidat kelulusan saat ini. Semua siswa aktif sudah memiliki rombel
    di semester ini, atau belum ada siswa yang menunggu diluluskan.
  </div>
  @else
  <form action="{{ route('siswa.kelulusan.proses') }}" method="POST">
    @csrf
    <input type="hidden" name="sekolah_id" value="{{ $sekolahId }}">

    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Tanggal Kelulusan</label>
            <input type="date" name="tanggal_keluar" class="form-control"
              value="{{ old('tanggal_keluar', $defaultTanggal) }}" required>
            <small class="text-muted">Bawaan: akhir semester sebelumnya. Tanggal ini menjadi
              tanggal keluar pada registrasi siswa.</small>
          </div>
          <div class="col-md-5">
            <label class="form-label">Alasan / Keterangan (opsional)</label>
            <input type="text" name="alasan" class="form-control" maxlength="255"
              value="{{ old('alasan') }}" placeholder="cth. Lulus tahun pelajaran ini">
          </div>
          <div class="col-md-3 text-md-end">
            <button type="submit" class="btn btn-primary"
              onclick="return confirm('Luluskan siswa yang dicentang? Status registrasi mereka akan ditutup sebagai lulus.')">
              <i class="ri-graduation-cap-line me-1"></i> Luluskan Siswa Terpilih
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h6 class="mb-0">Kandidat Kelulusan ({{ $kandidat->count() }})</h6>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th style="width: 40px">
                <input type="checkbox" class="form-check-input" id="pilih-semua">
              </th>
              <th>Nama Siswa</th>
              <th>NIS</th>
              <th>Rombel Terakhir</th>
              <th>Keterangan</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($kandidat as $siswa)
            <tr>
              <td>
                <input type="checkbox" class="form-check-input kandidat-check"
                  name="siswa_ids[]" value="{{ $siswa->id }}"
                  {{ $siswa->kelas_akhir ? 'checked' : '' }}>
              </td>
              <td>
                <strong>{{ $siswa->nama_siswa }}</strong>
                <small class="text-muted d-block">NISN {{ $siswa->nisn ?: '-' }}</small>
              </td>
              <td>{{ $siswa->registrasiTerbaru?->nis ?: '-' }}</td>
              <td>
                @if ($siswa->anggota_terakhir)
                {{ $siswa->anggota_terakhir->rombel->nama_rombel }}
                — Tingkat {{ $siswa->anggota_terakhir->rombel->tingkat }}
                <small class="text-muted d-block">
                  {{ $siswa->anggota_terakhir->rombel->semester->tahun_pelajaran }}
                  Semester {{ $siswa->anggota_terakhir->rombel->semester->semester }}
                </small>
                @else
                <span class="text-muted">Belum pernah masuk rombel</span>
                @endif
              </td>
              <td>
                @if ($siswa->kelas_akhir)
                <span class="badge bg-label-primary">Kelas Akhir</span>
                @else
                <span class="badge bg-label-warning">Tanpa Rombel</span>
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </form>
  @endif
</div>
@endsection

@section('page-script')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const pilihSemua = document.getElementById('pilih-semua');
    if (!pilihSemua) return;
    pilihSemua.addEventListener('change', function() {
      document.querySelectorAll('.kandidat-check').forEach(cb => cb.checked = this.checked);
    });
  });
</script>
@endsection
