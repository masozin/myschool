@extends('layouts/layoutMaster')

@section('title', 'Pembaruan Aplikasi')

@section('content')
  {{-- ── Kepala halaman ─────────────────────────────────────────── --}}
  <div class="card mb-4">
    <div class="card-body d-flex flex-wrap align-items-center gap-3 py-3">
      <span class="bg-label-primary rounded p-2 d-inline-flex">
        <i class="icon-base ri ri-refresh-line icon-28px"></i>
      </span>
      <div class="flex-grow-1">
        <h4 class="mb-0">Pembaruan Aplikasi</h4>
        <small class="text-body-secondary">Terapkan paket pembaruan tanpa SSH — khusus Admin. File lama dibackup otomatis sebelum ditimpa.</small>
      </div>
      <div class="text-md-end">
        <div class="text-body-secondary small">Versi terpasang</div>
        @if ($terpasang)
          <span class="badge bg-primary">{{ $terpasang['versi'] }}</span>
          <div class="small text-body-secondary">diterapkan {{ $terpasang['diterapkan_pada'] }}</div>
        @else
          <span class="badge bg-label-secondary">Pemasangan awal (manual)</span>
          <div class="small text-body-secondary">belum pernah ada pembaruan lewat halaman ini</div>
        @endif
      </div>
    </div>
  </div>

  {{-- ── Laporan hasil ──────────────────────────────────────────── --}}
  @if (session('laporan'))
    @php($laporan = session('laporan'))
    <div class="alert alert-success" role="alert">
      <h6 class="alert-heading mb-1"><i class="icon-base ri ri-checkbox-circle-line me-1"></i>Pembaruan selesai: {{ $laporan['versi'] }}</h6>
      <div class="small">
        {{ $laporan['file'] }} file ditulis, {{ $laporan['dihapus'] }} file dihapus.
        Backup file lama: <code>{{ $laporan['backup'] }}</code> (tersedia untuk dipulihkan di bawah).
        @if ($laporan['migrasi'])
          <br>Migrasi: {{ $laporan['migrasi'] }}
        @endif
      </div>
      @if (!empty($laporan['peringatan']))
        <ul class="mb-0 mt-2 small">
          @foreach ($laporan['peringatan'] as $p)
            <li>{{ $p }}</li>
          @endforeach
        </ul>
      @endif
    </div>
  @endif

  @if (session('gagal'))
    <div class="alert alert-danger" role="alert">
      <i class="icon-base ri ri-error-warning-line me-1"></i>{{ session('gagal') }}
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger" role="alert">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row">
    {{-- ── Unggah paket ──────────────────────────────────────────── --}}
    <div class="col-lg-7">
      <div class="card mb-4">
        <h5 class="card-header">Terapkan Paket Pembaruan</h5>
        <div class="card-body">
          <form method="POST" action="{{ route('pembaruan.terapkan') }}" enctype="multipart/form-data"
                onsubmit="return confirm('Terapkan pembaruan ini sekarang? File lama dibackup otomatis terlebih dahulu.')">
            @csrf
            <div class="mb-3">
              <label for="paket" class="form-label">File paket (.zip)</label>
              <input type="file" class="form-control" id="paket" name="paket" accept=".zip" required>
              <div class="form-text">Paket resmi selalu memuat <code>update-manifest.json</code> di dalamnya. Paket tanpa manifest akan ditolak.</div>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="konfirmasi_backup_db" name="konfirmasi_backup_db" value="1" required>
              <label class="form-check-label" for="konfirmasi_backup_db">
                Saya paham backup otomatis di sini hanya mencakup <strong>file</strong>. Untuk pembaruan besar, saya sudah mengekspor <strong>database</strong> lewat phpMyAdmin sebagai cadangan.
              </label>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="icon-base ri ri-upload-cloud-2-line me-1"></i>Terapkan Pembaruan
            </button>
          </form>

          <hr>
          <h6 class="mb-2">Yang terjadi saat pembaruan diterapkan</h6>
          <ol class="small text-body-secondary mb-0 ps-3">
            <li>Paket diperiksa: manifest, path menyusup (<code>..</code>), dan area terlarang.</li>
            <li>File yang akan ditimpa/dihapus dibackup ke ZIP di server (daftar di bawah).</li>
            <li>File baru disalin, file usang dihapus, migrasi database dijalankan, cache dibersihkan.</li>
            <li>Versi dan riwayat dicatat di halaman ini.</li>
          </ol>
          <div class="alert alert-warning small mt-3 mb-0">
            Area <strong>tidak pernah disentuh</strong>: <code>.env</code>, folder <code>vendor</code>, folder <code>storage</code>.
            Paket yang mengubah area itu ditolak dan harus dipasang manual — termasuk pembaruan yang menambah library Composer baru.
          </div>
        </div>
      </div>

      {{-- ── Backup & pemulihan ──────────────────────────────────── --}}
      <div class="card mb-4">
        <h5 class="card-header">Backup File &amp; Pemulihan</h5>
        <div class="card-body">
          @if ($daftarBackup->isEmpty())
            <p class="text-body-secondary small mb-0">Belum ada backup. Backup pertama dibuat otomatis pada pembaruan berikutnya.</p>
          @else
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th>Backup</th>
                    <th class="text-end">Ukuran</th>
                    <th>Dibuat</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($daftarBackup as $b)
                    <tr>
                      <td><code>{{ $b['nama'] }}</code></td>
                      <td class="text-end">{{ number_format($b['ukuran'] / 1024, 0, ',', '.') }} KB</td>
                      <td>{{ $b['waktu'] }}</td>
                      <td class="text-end">
                        <form method="POST" action="{{ route('pembaruan.pulihkan') }}" class="d-inline"
                              onsubmit="return confirm('Pulihkan file dari backup ini? File saat ini akan ditimpa oleh isi backup. Migrasi database TIDAK ikut dibatalkan.')">
                          @csrf
                          <input type="hidden" name="backup" value="{{ $b['nama'] }}">
                          <button type="submit" class="btn btn-sm btn-outline-warning">
                            <i class="icon-base ri ri-history-line me-1"></i>Pulihkan
                          </button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <p class="small text-body-secondary mt-2 mb-0">Pemulihan hanya mengembalikan <strong>file</strong>; perubahan struktur database (migrasi) yang sudah berjalan tidak dibatalkan.</p>
          @endif
        </div>
      </div>
    </div>

    {{-- ── Riwayat ───────────────────────────────────────────────── --}}
    <div class="col-lg-5">
      <div class="card mb-4">
        <h5 class="card-header">Riwayat Pembaruan</h5>
        <div class="card-body">
          @if (empty($riwayat))
            <p class="text-body-secondary small mb-0">Belum ada riwayat. Pembaruan pertama yang diterapkan lewat halaman ini akan tercatat di sini.</p>
          @else
            <ul class="list-unstyled mb-0">
              @foreach ($riwayat as $r)
                <li class="d-flex gap-3 pb-3 mb-3 border-bottom">
                  <span class="{{ ($r['status'] ?? '') === 'rollback' ? 'bg-label-warning' : 'bg-label-success' }} rounded p-2 d-inline-flex h-100">
                    <i class="icon-base ri {{ ($r['status'] ?? '') === 'rollback' ? 'ri-history-line' : 'ri-checkbox-circle-line' }} icon-20px"></i>
                  </span>
                  <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                      <strong>{{ $r['versi'] }}</strong>
                      <small class="text-body-secondary text-nowrap">{{ $r['diterapkan_pada'] }}</small>
                    </div>
                    @if (!empty($r['deskripsi']))
                      <div class="small text-body-secondary">{{ $r['deskripsi'] }}</div>
                    @endif
                    <div class="small text-body-secondary">
                      {{ $r['file'] ?? 0 }} file ditulis, {{ $r['dihapus'] ?? 0 }} dihapus
                      @if (!empty($r['backup']))
                        · backup <code>{{ $r['backup'] }}</code>
                      @endif
                    </div>
                  </div>
                </li>
              @endforeach
            </ul>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection
