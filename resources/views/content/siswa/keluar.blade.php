{{-- resources/views/content/siswa/keluar.blade.php --}}
@extends('layouts/contentNavbarLayout')

@section('title', 'Siswa Keluar')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="fw-bold mb-1">Siswa Keluar</h4>
  <p class="text-muted mb-4">
    Siswa yang registrasinya di sekolah ini berakhir <strong>bukan karena lulus</strong> —
    keluar, mutasi, atau meninggal. Status keluar pada tahun pelajaran yang sama masih bisa
    dibatalkan bila siswa belum terdaftar di sekolah lain. Siswa yang lulus ada di halaman
    <a href="{{ route('siswa.alumni', ['sekolah_id' => $sekolahId]) }}">Alumni</a>.
  </p>

  @if (session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if (session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="card mb-4">
    <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
      <form method="GET" action="{{ route('siswa.keluar') }}" class="d-flex align-items-center gap-3">
        <input type="hidden" name="sekolah_id" value="{{ $sekolahId }}">
        <label for="filter_status" class="form-label mb-0 text-nowrap">Status</label>
        <select name="status" id="filter_status" class="form-select" onchange="this.form.submit()">
          <option value="semua" {{ $statusDipilih === 'semua' ? 'selected' : '' }}>Semua</option>
          <option value="keluar" {{ $statusDipilih === 'keluar' ? 'selected' : '' }}>Keluar</option>
          <option value="mutasi" {{ $statusDipilih === 'mutasi' ? 'selected' : '' }}>Mutasi</option>
          <option value="meninggal" {{ $statusDipilih === 'meninggal' ? 'selected' : '' }}>Meninggal</option>
        </select>
      </form>
      <span class="text-muted">{{ $siswaKeluar->count() }} siswa</span>
    </div>
  </div>

  <div class="card">
    <div class="card-datatable text-nowrap">
      <table class="datatables-basic table table-bordered" id="tabel-keluar">
        <thead>
          <tr>
            <th>No</th>
            <th>Nama Siswa</th>
            <th>L/P</th>
            <th>Status</th>
            <th>Tanggal Keluar</th>
            <th>Alasan / Tujuan</th>
            <th>Rombel Terakhir</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($siswaKeluar as $siswa)
          @php
          $reg = $siswa->registrasiTerbaru;
          $terakhir = $siswa->rombelAnggota->first();
          $parts = preg_split('/\s+/', trim($siswa->nama_siswa));
          $inisial = strtoupper(($parts[0][0] ?? '') . (count($parts) > 1 ? $parts[count($parts) - 1][0] : ''));
          $state = $siswa->jenis_kelamin === 'L' ? 'primary' : 'danger';
          $badge = [
            'keluar' => 'bg-label-warning',
            'mutasi' => 'bg-label-info',
            'meninggal' => 'bg-label-dark',
          ][$reg?->status] ?? 'bg-label-secondary';
          @endphp
          <tr>
            <td>{{ $loop->iteration }}</td>
            {{-- data-order: kunci urut nama asli — tanpa ini
                 DataTables mengurutkan dari teks sel yang
                 diawali inisial avatar --}}
            <td data-order="{{ $siswa->nama_siswa }}">
              <div class="d-flex justify-content-start align-items-center gap-3 user-name">
                <a href="{{ route('siswa.show', $siswa->id) }}" class="text-decoration-none" title="Detail {{ $siswa->nama_siswa }}">
                  <div class="avatar avatar-sm">
                    @if ($siswa->photo)
                    <img src="/storage/{{ $siswa->photo }}" alt="{{ $siswa->nama_siswa }}"
                      class="rounded-circle" width="38" height="38" style="object-fit:cover;">
                    @else
                    <span class="avatar-initial rounded-circle bg-label-{{ $state }}"
                      style="width:38px;height:38px;font-size:13px;display:flex;align-items:center;justify-content:center;">
                      {{ $inisial }}
                    </span>
                    @endif
                  </div>
                </a>
                <div class="d-flex flex-column">
                  <a href="{{ route('siswa.show', $siswa->id) }}"
                    class="text-heading text-truncate fw-medium text-decoration-none">
                    {{ $siswa->nama_siswa }}
                  </a>
                  <small class="text-muted">{{ $siswa->nisn ?: '-' }} - {{ $reg?->nis ?: '-' }}</small>
                </div>
              </div>
            </td>
            <td class="text-center">
              @if ($siswa->jenis_kelamin === 'L')
              <i class="ri ri-men-line text-primary icon-22px" title="Laki-laki"></i>
              @else
              <i class="ri ri-women-line text-danger icon-22px" title="Perempuan"></i>
              @endif
            </td>
            <td>
              <span class="badge {{ $badge }}">{{ ucfirst($reg?->status ?? '-') }}</span>
            </td>
            <td>
              {{ $reg?->tanggal_keluar
                ? \Carbon\Carbon::parse($reg->tanggal_keluar)->format('d M Y')
                : '-' }}
            </td>
            <td>
              <div class="d-flex flex-column">
                <span>{{ $reg?->alasan ?: '-' }}</span>
                @if ($reg?->sekolah_tujuan)
                <small class="text-muted">Tujuan: {{ $reg->sekolah_tujuan }}</small>
                @endif
              </div>
            </td>
            <td>
              @if ($terakhir)
              <div class="d-flex flex-column">
                <span>{{ $terakhir->rombel->nama_rombel }}</span>
                <small class="text-muted">
                  Tingkat {{ $terakhir->rombel->tingkat }} ·
                  {{ $terakhir->rombel->semester->tahun_pelajaran }}
                  Semester {{ $terakhir->rombel->semester->semester }}
                </small>
              </div>
              @else
              <span class="badge bg-label-warning">Tanpa Rombel</span>
              @endif
            </td>
            <td>
              <div class="d-flex align-items-center gap-0">
                <a href="{{ route('siswa.show', $siswa->id) }}"
                  class="btn btn-icon btn-text-secondary btn-sm rounded-pill" title="Detail Siswa">
                  <i class="ri ri-eye-line icon-18px"></i>
                </a>
                @if ($siswa->bisa_batal)
                <form action="{{ route('siswa.keluar.batal', $siswa->id) }}" method="POST"
                  onsubmit="return confirm('Batalkan status keluar siswa ini? Registrasinya dibuka kembali sebagai aktif agar bisa dimasukkan ke rombel.')">
                  @csrf
                  <input type="hidden" name="sekolah_id" value="{{ $sekolahId }}">
                  <button type="submit" class="btn btn-icon btn-text-warning btn-sm rounded-pill"
                    title="Batalkan Keluar">
                    <i class="ri ri-arrow-go-back-line icon-18px"></i>
                  </button>
                </form>
                @endif
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const el = document.querySelector('#tabel-keluar');
    if (el) {
      new DataTable(el, {
        scrollX: true,
        order: [
          [1, 'asc']
        ],
        columnDefs: [{
          targets: 0,
          orderable: false,
          searchable: false
        }]
      });
    }
  });
</script>
@endsection
