{{-- resources/views/content/surat-pindah/form.blade.php --}}
@extends('layouts/contentNavbarLayout')

@section('title', 'Buat Surat Pindah')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="fw-bold mb-1">Surat Keterangan Pindah Sekolah</h4>
  <p class="text-muted mb-4">
    Surat terbit langsung dengan nomor resmi dari agenda Surat Keluar (kategori 420 — Pendidikan).
    Isi surat dibekukan saat terbit, jadi cetak ulang selalu sama persis.
  </p>

  @if (session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  @if ($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach ($errors->all() as $e)
      <li>{{ $e }}</li>
      @endforeach
    </ul>
  </div>
  @endif

  <div class="row">
    <div class="col-lg-5 mb-4">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0">Data Siswa & Mutasi</h5>
        </div>
        <div class="card-body">
          <table class="table table-sm table-borderless mb-0">
            <tr><td class="text-muted" style="width:42%">Nama</td><td class="fw-medium">{{ $siswa->nama_siswa }}</td></tr>
            <tr><td class="text-muted">NIS / NISN</td><td>{{ $registrasi->nis ?: '-' }} / {{ $siswa->nisn ?: '-' }}</td></tr>
            <tr><td class="text-muted">Tempat, Tgl Lahir</td>
              <td>{{ $siswa->tempat_lahir ?: '-' }}, {{ $siswa->tgl_lahir?->format('d-m-Y') ?? '-' }}</td></tr>
            <tr><td class="text-muted">Jenis Kelamin</td>
              <td>{{ $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}</td></tr>
            <tr><td class="text-muted">Kelas Terakhir</td>
              <td>{{ $rombel ?: '-' }} (Tingkat {{ $tingkat ?: '-' }}){{ $tahun_pelajaran ? ' · TP ' . $tahun_pelajaran : '' }}</td></tr>
            <tr><td class="text-muted">Tanggal Keluar</td>
              <td>{{ $registrasi->tanggal_keluar ? \Carbon\Carbon::parse($registrasi->tanggal_keluar)->format('d-m-Y') : '-' }}</td></tr>
            <tr><td class="text-muted">Sekolah Tujuan</td><td>{{ $registrasi->sekolah_tujuan ?: '-' }}</td></tr>
            <tr><td class="text-muted">Alasan</td><td>{{ $registrasi->alasan ?: '-' }}</td></tr>
          </table>
          <hr>
          <table class="table table-sm table-borderless mb-0">
            <tr><td class="text-muted" style="width:42%">Sekolah</td><td>{{ $sekolah['nama'] ?? '-' }}</td></tr>
            <tr><td class="text-muted">Penandatangan</td>
              <td>{{ $kepsek['nama'] ?? '-' }}{{ $kepsek['nip'] ? ' · NIP ' . $kepsek['nip'] : '' }}</td></tr>
            <tr><td class="text-muted">Perkiraan Nomor</td><td class="fw-medium">{{ $contohNomor }}</td></tr>
          </table>
          <p class="text-muted small mt-3 mb-0">
            Perkiraan nomor mengikuti format & penghitung Surat Keluar; nomor resmi
            ditetapkan saat tombol Terbitkan ditekan.
          </p>
        </div>
      </div>
    </div>

    <div class="col-lg-7 mb-4">
      <div class="card">
        <div class="card-header">
          <h5 class="mb-0">Isi Surat</h5>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('surat-keluar.pindah.store', $registrasi) }}">
            @csrf
            <div class="mb-3">
              <label class="form-label" for="tanggal_surat">Tanggal Surat</label>
              <input type="date" class="form-control" id="tanggal_surat" name="tanggal_surat"
                value="{{ old('tanggal_surat', now()->format('Y-m-d')) }}" required>
            </div>

            <div class="mb-3">
              <label class="form-label d-block">Orang Tua / Wali Pemohon</label>
              @forelse ($pemohon as $kunci => $p)
              <div class="form-check form-check-inline">
                <input class="form-check-input pemohon-radio" type="radio" name="pemohon"
                  id="pemohon_{{ $kunci }}" value="{{ $kunci }}"
                  data-nama="{{ $p['nama'] }}" data-pekerjaan="{{ $p['pekerjaan'] }}"
                  data-alamat="{{ $p['alamat'] }}"
                  {{ old('pemohon', array_key_first($pemohon)) === $kunci ? 'checked' : '' }}>
                <label class="form-check-label" for="pemohon_{{ $kunci }}">
                  {{ $p['relasi'] }} — {{ $p['nama'] }}</label>
              </div>
              @empty
              <p class="text-danger mb-0">Data ayah/ibu/wali siswa ini kosong — lengkapi dulu di biodata siswa.</p>
              @endforelse
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label" for="nama_pemohon">Nama Pemohon</label>
                <input type="text" class="form-control" id="nama_pemohon" name="nama_pemohon"
                  value="{{ old('nama_pemohon', $pemohon[array_key_first($pemohon)]['nama'] ?? '') }}" required>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label" for="pekerjaan_pemohon">Pekerjaan Pemohon</label>
                <input type="text" class="form-control" id="pekerjaan_pemohon" name="pekerjaan_pemohon"
                  value="{{ old('pekerjaan_pemohon', $pemohon[array_key_first($pemohon)]['pekerjaan'] ?? '') }}">
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label" for="alamat_pemohon">Alamat Pemohon</label>
              <input type="text" class="form-control" id="alamat_pemohon" name="alamat_pemohon"
                value="{{ old('alamat_pemohon', $pemohon[array_key_first($pemohon)]['alamat'] ?? '') }}">
            </div>

            <div class="mb-3">
              <label class="form-label" for="tujuan_teks">Sekolah Tujuan (teks lengkap untuk surat)</label>
              <input type="text" class="form-control" id="tujuan_teks" name="tujuan_teks"
                value="{{ old('tujuan_teks', $registrasi->sekolah_tujuan) }}" required
                placeholder="cth: SD Negeri Mendis Kec. Bayung Lencir Kab. Musi Banyu Asin">
              <div class="form-text">Tulis lengkap dengan kecamatan & kabupatennya — persis seperti yang akan tercetak.</div>
            </div>
            <div class="mb-3">
              <label class="form-label" for="alasan">Alasan Pindah</label>
              <input type="text" class="form-control" id="alasan" name="alasan"
                value="{{ old('alasan', $registrasi->alasan) }}"
                placeholder="cth: ikut orang tua">
            </div>
            <div class="mb-4">
              <label class="form-label" for="catatan">Catatan Tambahan (opsional, ikut tercetak)</label>
              <textarea class="form-control" id="catatan" name="catatan" rows="2">{{ old('catatan') }}</textarea>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary" @empty($pemohon) disabled @endempty
                onclick="return confirm('Terbitkan surat sekarang? Nomor resmi akan ditetapkan dan isi surat dibekukan.')">
                <i class="icon-base ri ri-printer-line me-1"></i> Terbitkan & Cetak
              </button>
              <a href="{{ route('siswa.keluar', ['sekolah_id' => $registrasi->sekolah_id, 'status' => 'mutasi']) }}"
                class="btn btn-outline-secondary">Kembali</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
  document.querySelectorAll('.pemohon-radio').forEach(function(radio) {
    radio.addEventListener('change', function() {
      document.getElementById('nama_pemohon').value = this.dataset.nama || '';
      document.getElementById('pekerjaan_pemohon').value = this.dataset.pekerjaan || '';
      document.getElementById('alamat_pemohon').value = this.dataset.alamat || '';
    });
  });
</script>
@endsection
