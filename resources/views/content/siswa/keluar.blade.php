{{-- resources/views/content/siswa/keluar.blade.php --}}
@extends('layouts/contentNavbarLayout')

@section('title', 'Siswa Keluar')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="fw-bold mb-1">Siswa Keluar</h4>
  <p class="text-muted mb-4">
    Daftar siswa yang registrasinya di sekolah ini telah berakhir <strong>bukan karena lulus</strong> —
    keluar, mutasi ke sekolah lain, atau meninggal. Seperti alumni, halaman ini adalah tampilan
    tersaring dari data registrasi siswa, bukan tabel terpisah. Siswa yang lulus ada di halaman
    <a href="{{ route('siswa.alumni', ['sekolah_id' => $sekolahId]) }}">Alumni</a>.
  </p>

  @if (session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('siswa.keluar') }}" class="row g-3 align-items-end">
        <input type="hidden" name="sekolah_id" value="{{ $sekolahId }}">
        <div class="col-md-4">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="semua" {{ $statusDipilih === 'semua' ? 'selected' : '' }}>Semua</option>
            <option value="keluar" {{ $statusDipilih === 'keluar' ? 'selected' : '' }}>Keluar</option>
            <option value="mutasi" {{ $statusDipilih === 'mutasi' ? 'selected' : '' }}>Mutasi</option>
            <option value="meninggal" {{ $statusDipilih === 'meninggal' ? 'selected' : '' }}>Meninggal</option>
          </select>
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn btn-primary">Tampilkan</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <h6 class="mb-0">Siswa Keluar ({{ $siswaKeluar->count() }})</h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Nama Siswa</th>
            <th>NIS</th>
            <th>Status</th>
            <th>Tanggal Keluar</th>
            <th>Alasan / Tujuan</th>
            <th>Rombel Terakhir</th>
            <th style="width: 90px">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($siswaKeluar as $siswa)
          @php
          $reg = $siswa->registrasiTerbaru;
          $badge = [
            'keluar' => 'bg-label-warning',
            'mutasi' => 'bg-label-info',
            'meninggal' => 'bg-label-dark',
          ][$reg?->status] ?? 'bg-label-secondary';
          $terakhir = $siswa->rombelAnggota->first();
          @endphp
          <tr>
            <td>
              <strong>{{ $siswa->nama_siswa }}</strong>
              <small class="text-muted d-block">NISN {{ $siswa->nisn ?: '-' }}</small>
            </td>
            <td>{{ $reg?->nis ?: '-' }}</td>
            <td><span class="badge {{ $badge }}">{{ ucfirst($reg?->status ?? '-') }}</span></td>
            <td>
              {{ $reg?->tanggal_keluar
                ? \Carbon\Carbon::parse($reg->tanggal_keluar)->format('d M Y')
                : '-' }}
            </td>
            <td>
              {{ $reg?->alasan ?: '-' }}
              @if ($reg?->sekolah_tujuan)
              <small class="text-muted d-block">Tujuan: {{ $reg->sekolah_tujuan }}</small>
              @endif
            </td>
            <td>
              @if ($terakhir)
              {{ $terakhir->rombel->nama_rombel }} — Tingkat {{ $terakhir->rombel->tingkat }}
              @else
              <span class="text-muted">-</span>
              @endif
            </td>
            <td>
              <a href="{{ route('siswa.show', $siswa->id) }}" class="btn btn-sm btn-outline-primary">
                Detail
              </a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="7" class="text-center text-muted py-4">
              Tidak ada siswa keluar dengan status ini.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
