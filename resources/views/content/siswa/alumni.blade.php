{{-- resources/views/content/siswa/alumni.blade.php --}}
@extends('layouts/contentNavbarLayout')

@section('title', 'Alumni')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="fw-bold mb-1">Alumni</h4>
  <p class="text-muted mb-4">
    Daftar siswa yang registrasinya telah ditutup sebagai <strong>lulus</strong> di sekolah ini.
    Alumni bukan tabel terpisah — halaman ini adalah tampilan tersaring dari data registrasi siswa.
    Kelulusan tahun pelajaran tepat di bawah tahun pelajaran sekarang masih bisa dibatalkan
    bila ada kekeliruan, supaya siswanya dapat dimasukkan ke rombel kembali.
  </p>

  @if (session('success'))
  <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if (session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('siswa.alumni') }}" class="row g-3 align-items-end">
        <input type="hidden" name="sekolah_id" value="{{ $sekolahId }}">
        <div class="col-md-4">
          <label class="form-label">Tahun Pelajaran Kelulusan</label>
          <select name="tp" class="form-select">
            @forelse ($tpList as $tp)
            <option value="{{ $tp }}" {{ $tp === $tpDipilih ? 'selected' : '' }}>
              {{ $tp }}
            </option>
            @empty
            <option value="">Belum ada alumni</option>
            @endforelse
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
      <h6 class="mb-0">
        Alumni Tahun Pelajaran {{ $tpDipilih ?: '-' }} ({{ $alumni->count() }})
      </h6>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Nama Siswa</th>
            <th>NIS</th>
            <th>Rombel Terakhir</th>
            <th>Tanggal Lulus</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($alumni as $siswa)
          <tr>
            <td>
              <strong>{{ $siswa->nama_siswa }}</strong>
              <small class="text-muted d-block">NISN {{ $siswa->nisn ?: '-' }}</small>
            </td>
            <td>{{ $siswa->registrasiTerbaru?->nis ?: '-' }}</td>
            <td>
              @php $terakhir = $siswa->rombelAnggota->first(); @endphp
              @if ($terakhir)
              {{ $terakhir->rombel->nama_rombel }} — Tingkat {{ $terakhir->rombel->tingkat }}
              <small class="text-muted d-block">
                {{ $terakhir->rombel->semester->tahun_pelajaran }}
                Semester {{ $terakhir->rombel->semester->semester }}
              </small>
              @else
              <span class="text-muted">-</span>
              @endif
            </td>
            <td>
              {{ $siswa->registrasiTerbaru?->tanggal_keluar
                ? \Carbon\Carbon::parse($siswa->registrasiTerbaru->tanggal_keluar)->format('d M Y')
                : '-' }}
            </td>
            <td>
              <a href="{{ route('siswa.show', $siswa->id) }}" class="btn btn-sm btn-outline-primary">
                Detail
              </a>
              @if ($siswa->bisa_batal)
              <form action="{{ route('siswa.alumni.batal-lulus', $siswa->id) }}" method="POST"
                class="d-inline"
                onsubmit="return confirm('Batalkan kelulusan siswa ini? Registrasinya dibuka kembali sebagai aktif agar bisa dimasukkan ke rombel.')">
                @csrf
                <input type="hidden" name="sekolah_id" value="{{ $sekolahId }}">
                <button type="submit" class="btn btn-sm btn-outline-warning">
                  Batalkan Kelulusan
                </button>
              </form>
              @endif
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="5" class="text-center text-muted py-4">
              Belum ada alumni pada tahun pelajaran ini.
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
