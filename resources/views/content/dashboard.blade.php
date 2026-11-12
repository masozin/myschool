@php
  $configData = Helper::appClasses();
  $isAdmin = $isAdmin ?? false;
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Dashboard')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js', 'resources/assets/vendor/libs/chartjs/chartjs.js'])
@endsection

@section('content')

  {{-- ── INFO HARI INI : ucapan untuk saya, kaldik, ulang tahun ── --}}
  @if ($ucapanSaya)
    <div class="alert alert-primary d-flex align-items-start gap-3 mb-6" role="alert">
      <span class="avatar avatar-md flex-shrink-0">
        <span class="avatar-initial rounded-circle bg-primary">
          <i class="ri ri-cake-2-line ri-24px text-white"></i>
        </span>
      </span>
      <div>
        <h5 class="alert-heading mb-1">{{ $ucapanSaya['judul'] }}</h5>
        <p class="mb-0">{{ $ucapanSaya['doa'] }}</p>
      </div>
    </div>
  @endif

  @if ($kaldikHariIni->isNotEmpty() || $ultahHariIni->isNotEmpty())
    <div class="row g-6 mb-6">
      @if ($kaldikHariIni->isNotEmpty())
        <div class="col-12 {{ $ultahHariIni->isNotEmpty() ? 'col-lg-6' : '' }}">
          <div class="card h-100">
            <div class="card-body">
              <h6 class="card-title d-flex align-items-center gap-2 mb-3">
                <i class="ri ri-calendar-event-line text-warning ri-20px"></i>
                Kalender Pendidikan Hari Ini
              </h6>
              @foreach ($kaldikHariIni as $k)
                <div class="d-flex justify-content-between align-items-center gap-2">
                  <span class="fw-medium">{{ $k->kegiatan }}</span>
                  @if ($k->is_libur)
                    <span class="badge bg-label-danger flex-shrink-0">Libur</span>
                  @else
                    <span class="badge bg-label-info flex-shrink-0">{{ $k->jenis }}</span>
                  @endif
                </div>
                <small class="text-muted d-block mb-2">
                  {{ $k->tanggal_mulai->translatedFormat('d F Y') }} &ndash;
                  {{ $k->tanggal_selesai->translatedFormat('d F Y') }}
                </small>
              @endforeach
            </div>
          </div>
        </div>
      @endif

      @if ($ultahHariIni->isNotEmpty())
        <div class="col-12 {{ $kaldikHariIni->isNotEmpty() ? 'col-lg-6' : '' }}">
          <div class="card h-100">
            <div class="card-body">
              <h6 class="card-title d-flex align-items-center gap-2 mb-3">
                <i class="ri ri-gift-line text-primary ri-20px"></i>
                Ulang Tahun Hari Ini
              </h6>
              @foreach ($ultahHariIni as $pUltah)
                <div class="d-flex align-items-center gap-3 py-1">
                  <span class="avatar flex-shrink-0">
                    <span
                      class="avatar-initial rounded-circle bg-label-primary">{{ collect(explode(' ', $pUltah->nama_ptk))->filter()->map(fn($w) => Str::substr($w, 0, 1))->take(2)->implode('') }}</span>
                  </span>
                  <div class="flex-grow-1">
                    <div class="fw-medium">
                      {{ $pUltah->nama_lengkap }}
                      @if ($ptk && $pUltah->id === $ptk->id)
                        <span class="badge bg-primary ms-1">Anda</span>
                      @endif
                    </div>
                    <small class="text-muted">
                      {{ $pUltah->jabatan }} &bull; ulang tahun
                      ke-{{ \App\Support\InfoHariIni::usiaPada($pUltah, now()) }}
                    </small>
                  </div>
                  <i class="ri ri-cake-2-line text-warning ri-20px"></i>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      @endif
    </div>
  @endif

  {{-- ── ROW 1 : Selamat Datang + Statistik utama ── --}}
  <div class="row g-6 mb-6">
    {{-- Card Selamat Datang --}}
    <div class="col-12 col-md-6 col-lg-7">
      <div class="card h-100">
        <div class="card-body text-nowrap">
          <div class="mb-0">
            Selamat Datang,
          </div>
          <h4 class="text-primary fw-bold mb-1">{{ $ptk->nama_ptk ?? $user->name }}</h4>
          <p class="mb-6 text-muted small">
            @if ($isAdmin)
              <span class="badge bg-label-danger">Administrator</span>
            @else
              <span class="badge bg-label-warning">{{ $ptk->jabatan }}</span>
            @endif
          </p>
          <p class="card-subtitle mb-0 text-muted" id="realtimeDate">—</p>
          <h4 class="d-none mb-0 text-dark fw-bold font-monospace" id="realtimeClock">00:00:00</h4>
          <a href="{{ url('user/profile') }}" class="btn btn-sm btn-primary mt-3">
            <i class="ri ri-user-3-line me-1"></i> Lihat Profil
          </a>
        </div>
        <img src="{{ asset('assets/img/illustrations/man-with-laptop.png') }}"
          class="position-absolute bottom-0 end-0 me-3" height="130" alt="welcome illustration"
          onerror="this.style.display='none'" />
      </div>
    </div>

    @if (!$isAdmin)
      @if ($sekolah && $sekolahDetail)
        {{-- Card Profil Sekolah --}}
        <div class="col-12 col-md-6 col-lg-5">
          <div class="card h-80">
            <div class="card-header d-flex justify-content-between align-items-start">
              <div class="d-flex">
                @if ($logo)
                  <img src="{{ asset('storage/' . $logo->path) }}" height="40" alt="logo sekolah"
                    class="rounded border" onerror="this.style.display='none'" />
                @else
                  <div class="avatar avatar-md me-4">
                    <div class="avatar-initial bg-label-primary rounded">
                      <i class="ri ri-school-line icon-24px"></i>
                    </div>
                  </div>
                @endif
                <div>
                  <h5 class="fw-bold text-primary mb-2">{{ $sekolahDetail->nama_sekolah ?? '-' }}</h5>
                  <p class="mb-0 card-subtitle">{{ $sekolah->npsn }}</p>
                </div>
              </div>
              <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge bg-label-primary rounded-pill">{{ strtoupper($sekolah->jenjang) }}</span>
              </div>
            </div>

            <div class="card-body">


              <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
                <li class="d-flex align-items-start gap-2">
                  <i class="ri ri-user-line text-primary mt-1"></i>
                  <div>
                    <span class="fw-medium d-block">{{ $sekolahDetail->kepala_sekolah ?? '-' }}</span>
                    @if ($sekolahDetail->nip)
                      <small class="text-muted">NIP: {{ $sekolahDetail->nip }}</small>
                    @endif
                  </div>
                </li>

                <li class="d-flex align-items-start gap-2">
                  <i class="ri ri-map-pin-line text-primary mt-1"></i>
                  <small class="text-muted">
                    {{ $sekolahDetail->alamat_sekolah ?? '-' }}
                    @if ($sekolahDetail->village || $sekolahDetail->regency)
                      <br>
                      {{ collect([
                          $sekolahDetail->village->name ?? null,
                          $sekolahDetail->district->name ?? null,
                          $sekolahDetail->regency->name ?? null,
                          $sekolahDetail->province->name ?? null,
                      ])->filter()->implode(', ') }}
                    @endif
                  </small>
                </li>
              </ul>
            </div>
          </div>
        </div>
      @else
        <div class="col-12 col-md-6">
          <div class="card">
            <div class="card-body text-center py-8">
              <i class="ri ri-school-line icon-48px text-muted d-block mb-3"></i>
              <h6 class="text-muted">Data sekolah belum tersedia.</h6>
              <p class="text-muted small">Hubungi administrator untuk mengatur data sekolah Anda.</p>
            </div>
          </div>
        </div>
      @endif
    @else
      <div class="col-md-6 col-lg-5">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-1">Distribusi Sekolah</h5>
            <p class="mb-0 card-subtitle">Berdasarkan Jenjang Pendidikan</p>
          </div>
          <div class="card-body d-flex align-items-center justify-content-center">
            <div id="chartJenjang" style="width:100%"></div>
          </div>
        </div>
      </div>
    @endif
  </div>
  {{-- /ROW 1 --}}

  {{-- ── ROW 2 ── --}}
  <div class="row g-6 mb-6">
    @if ($isAdmin)
      {{-- ── ADMIN: 5 stat cards ── --}}

      {{-- Jumlah Sekolah --}}
      <div class="col-md-3 col-sm-6">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
              <div class="avatar">
                <div class="avatar-initial bg-label-primary rounded-3">
                  <i class="icon-base ri ri-building-line icon-24px"></i>
                </div>
              </div>
            </div>
            <div class="card-info mt-5">
              <h4 class="mb-1 fw-bold">{{ number_format($jumlahSekolah) }}</h4>
              <p class="mb-2 text-muted">Total Sekolah</p>
              <div class="badge bg-label-primary rounded-pill">Semua Jenjang</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Jumlah Siswa --}}
      <div class="col-md-3 col-sm-6">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
              <div class="avatar">
                <div class="avatar-initial bg-label-success rounded-3">
                  <i class="icon-base ri ri-group-line icon-24px"></i>
                </div>
              </div>
            </div>
            <div class="card-info mt-5">
              <h4 class="mb-1 fw-bold">{{ number_format($jumlahSiswa) }}</h4>
              <p class="mb-2 text-muted">Total Siswa Aktif</p>
              <div class="badge bg-label-success rounded-pill">Seluruh Sekolah</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Jumlah Rombel --}}
      <div class="col-md-3 col-sm-6">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
              <div class="avatar">
                <div class="avatar-initial bg-label-warning rounded-3">
                  <i class="icon-base ri ri-door-open-line icon-24px"></i>
                </div>
              </div>
            </div>
            <div class="card-info mt-5">
              <h4 class="mb-1 fw-bold">{{ number_format($jumlahRombel) }}</h4>
              <p class="mb-2 text-muted">Total Rombel</p>
              <div class="badge bg-label-warning rounded-pill">Seluruh Sekolah</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Jumlah PTK --}}
      <div class="col-md-3 col-sm-6">
        <div class="card h-100">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
              <div class="avatar">
                <div class="avatar-initial bg-label-info rounded-3">
                  <i class="icon-base ri ri-user-star-line icon-24px"></i>
                </div>
              </div>
            </div>
            <div class="card-info mt-5">
              <h4 class="mb-1 fw-bold">{{ number_format($jumlahPtk) }}</h4>
              <p class="mb-2 text-muted">Total PTK</p>
              <div class="badge bg-label-info rounded-pill">Seluruh Sekolah</div>
            </div>
          </div>
        </div>
      </div>
    @else
      {{-- ── OPERATOR: 4 stat cards sekolah sendiri ── --}}
      @if ($sekolah)
        {{-- Siswa --}}
        <div class="col-xxl col-md-3 col-sm-6">
          <div class="card h-100">
            <div class="card-body">
              <div class="avatar mb-4">
                <div class="avatar-initial bg-label-success rounded-3">
                  <i class="icon-base ri ri-group-line icon-24px"></i>
                </div>
              </div>
              <h4 class="mb-1 fw-bold">{{ number_format($jumlahSiswa) }}</h4>
              <p class="mb-2 text-muted">Siswa Aktif</p>
              <div class="badge bg-label-success rounded-pill">Semester Ini</div>
            </div>
          </div>
        </div>

        {{-- Rombel --}}
        <div class="col-xxl col-md-3 col-sm-6">
          <div class="card h-100">
            <div class="card-body">
              <div class="avatar mb-4">
                <div class="avatar-initial bg-label-warning rounded-3">
                  <i class="icon-base ri ri-door-open-line icon-24px"></i>
                </div>
              </div>
              <h4 class="mb-1 fw-bold">{{ number_format($jumlahRombel) }}</h4>
              <p class="mb-2 text-muted">Rombel</p>
              <div class="badge bg-label-warning rounded-pill">Semester Ini</div>
            </div>
          </div>
        </div>

        {{-- Guru --}}
        <div class="col-xxl col-md-3 col-sm-6">
          <div class="card h-100">
            <div class="card-body">
              <div class="avatar mb-4">
                <div class="avatar-initial bg-label-info rounded-3">
                  <i class="icon-base ri ri-user-star-line icon-24px"></i>
                </div>
              </div>
              <h4 class="mb-1 fw-bold">{{ number_format($jumlahGuru) }}</h4>
              <p class="mb-2 text-muted">Guru</p>
              <div class="badge bg-label-info rounded-pill">Aktif</div>
            </div>
          </div>
        </div>

        {{-- Tendik --}}
        <div class="col-md-3 col-sm-6">
          <div class="card h-100">
            <div class="card-body">
              <div class="avatar mb-4">
                <div class="avatar-initial bg-label-secondary rounded-3">
                  <i class="icon-base ri ri-briefcase-line icon-24px"></i>
                </div>
              </div>
              <h4 class="mb-1 fw-bold">{{ number_format($jumlahTendik) }}</h4>
              <p class="mb-2 text-muted">Tendik</p>
              <div class="badge bg-label-secondary rounded-pill">Aktif</div>
            </div>
          </div>
        </div>
      @endif
    @endif

  </div>
  {{-- /ROW 2 --}}


  {{-- ── ROW 3 ── --}}
  <div class="row g-6">
    @if ($isAdmin)
      {{-- ADMIN: Chart Sekolah per Jenjang + Tabel ringkasan --}}
      <div class="col-lg-7 col-md-6">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div>
              <h5 class="mb-1">Rekap Data Nasional</h5>
              <p class="mb-0 card-subtitle">Ringkasan seluruh data pendidikan</p>
            </div>
          </div>
          <div class="card-body pt-4">
            <div class="table-responsive">
              <table class="table table-borderless">
                <tbody>
                  <tr>
                    <td class="py-2 ps-0">
                      <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-sm">
                          <div class="avatar-initial bg-label-primary rounded">
                            <i class="ri ri-building-line"></i>
                          </div>
                        </div>
                        <span class="fw-medium">Total Sekolah</span>
                      </div>
                    </td>
                    <td class="py-2 text-end fw-bold">{{ number_format($jumlahSekolah) }}</td>
                  </tr>
                  <tr>
                    <td class="py-2 ps-0">
                      <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-sm">
                          <div class="avatar-initial bg-label-success rounded">
                            <i class="ri ri-group-line"></i>
                          </div>
                        </div>
                        <span class="fw-medium">Siswa Aktif</span>
                      </div>
                    </td>
                    <td class="py-2 text-end fw-bold">{{ number_format($jumlahSiswa) }}</td>
                  </tr>
                  <tr>
                    <td class="py-2 ps-0">
                      <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-sm">
                          <div class="avatar-initial bg-label-warning rounded">
                            <i class="ri ri-door-open-line"></i>
                          </div>
                        </div>
                        <span class="fw-medium">Rombongan Belajar</span>
                      </div>
                    </td>
                    <td class="py-2 text-end fw-bold">{{ number_format($jumlahRombel) }}</td>
                  </tr>
                  <tr>
                    <td class="py-2 ps-0">
                      <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-sm">
                          <div class="avatar-initial bg-label-info rounded">
                            <i class="ri ri-user-star-line"></i>
                          </div>
                        </div>
                        <span class="fw-medium">Guru</span>
                      </div>
                    </td>
                    <td class="py-2 text-end fw-bold">{{ number_format($jumlahGuru) }}</td>
                  </tr>
                  <tr>
                    <td class="py-2 ps-0">
                      <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-sm">
                          <div class="avatar-initial bg-label-secondary rounded">
                            <i class="ri ri-briefcase-line"></i>
                          </div>
                        </div>
                        <span class="fw-medium">Tenaga Kependidikan</span>
                      </div>
                    </td>
                    <td class="py-2 text-end fw-bold">{{ number_format($jumlahTendik) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    @else
      {{-- OPERATOR: data siswa --}}

      {{-- Chart gender siswa --}}
      <div class="col-lg-3 col-md-5 col-sm-6">
        <div class="card h-100">
          <div class="card-body d-flex flex-column align-items-center justify-content-center">
            <div id="chartGender" style="height:100%"></div>
            <div class="d-flex gap-4 mt-2">
              <div class="text-center">
                <h5 class="mb-0 fw-bold text-primary">{{ number_format($siswaLaki) }}</h5>
                <small class="text-muted">Laki-laki</small>
              </div>
              <div class="vr"></div>
              <div class="text-center">
                <h5 class="mb-0 fw-bold text-danger">{{ number_format($siswaPerempuan) }}</h5>
                <small class="text-muted">Perempuan</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Distribusi gender siswa --}}
      <div class="col-lg-3 col-md-7 col-sm-6">
        <div class="card h-100">
          <div class="card-header">
            <p class="mb-0 card-subtitle">Distribusi Gender</p>
          </div>
          <div class="card-body">
            <div style="position: relative; height: 100%;">
              <canvas id="chart-siswa-tingkat"></canvas>
            </div>
          </div>
        </div>
      </div>

      {{-- ── Pivot Agama ── --}}
      <div class="col-lg-6">
        <div class="card h-100">
          <div class="card-body">
            <div id="pivot-tingkat-agama" class="table-responsive">
              <div class="text-center text-muted py-4">Memuat data...</div>
            </div>
          </div>
        </div>
      </div>
    @endif

  </div>
  {{-- /ROW 3 --}}

  {{-- ── ROW 4 ── --}}
  <div class="row g-6 mt-0">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">Pivot Siswa: Tingkat × Usia</h5>
        </div>
        <div class="card-body">
          <div id="pivot-tingkat-usia" class="table-responsive">
            <div class="text-center text-muted py-4">Memuat data...</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ── ROW 5 : Riwayat Login ── --}}
  <div class="row g-6 mt-0">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-1">Riwayat Login Terakhir</h5>
            <p class="mb-0 card-subtitle">
              {{ $isAdmin ? 'Seluruh pengguna sistem' : 'Pengguna di sekolah ini' }}
            </p>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>#</th>
                <th>Nama</th>
                <th>Email</th>
                @if ($isAdmin)
                  <th>Sekolah</th>
                @endif
                <th>Jabatan</th>
                <th>Login Terakhir</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($riwayatLogin as $i => $u)
                <tr>
                  <td>{{ $i + 1 }}</td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="avatar avatar-sm">
                        <div class="avatar-initial bg-label-primary rounded-circle">
                          {{ strtoupper(substr($u->name, 0, 1)) }}
                        </div>
                      </div>
                      <span class="fw-medium">{{ $u->name }}</span>
                    </div>
                  </td>
                  <td>{{ $u->email }}</td>
                  @if ($isAdmin)
                    <td>{{ $u->ptk?->sekolah?->detailTerbaru?->nama_sekolah ?? '-' }}</td>
                  @endif
                  <td>
                    <span class="badge bg-label-secondary">
                      {{ $u->ptk?->jabatan ?? 'Admin' }}
                    </span>
                  </td>
                  <td>
                    <span title="{{ $u->last_login_at->format('d/m/Y H:i:s') }}">
                      {{ $u->last_login_at->diffForHumans() }}
                    </span>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="{{ $isAdmin ? 6 : 5 }}" class="text-center text-muted py-5">
                    <i class="ri ri-time-line d-block mb-2 icon-24px"></i>
                    Belum ada riwayat login
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', async function() {
      const container = document.getElementById('pivot-tingkat-usia');
      if (!container) return;

      function esc(str) {
        return String(str ?? '').replace(/[&<>"']/g, c => ({
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#39;'
        } [c]));
      }

      try {
        const response = await fetch("{{ route('siswa.stats-pivot-usia') }}");
        const json = await response.json();

        if (!json.rows.length) {
          container.innerHTML = '<div class="text-center text-muted py-4">Belum ada data.</div>';
          return;
        }

        let thead = '<th>Tingkat</th>' + json.usiaList.map(u => `<th class="text-center">${esc(u)} th</th>`).join(
          '') + '<th class="text-center fw-bold">Total</th>';

        let tbody = json.rows.map(row => {
          const cells = json.usiaList.map(u => {
            const val = row.cells[u] ?? 0;
            return `<td class="text-center">${val || '-'}</td>`;
          }).join('');
          return `
        <tr>
          <td class="fw-medium">Tingkat ${esc(row.tingkat)}</td>
          ${cells}
          <td class="text-center fw-bold">${row.total}</td>
        </tr>`;
        }).join('');

        let tfoot = '<td class="fw-bold">Total</td>' +
          json.usiaList.map(u => `<td class="text-center fw-bold">${json.colTotals[u] || 0}</td>`).join('') +
          `<td class="text-center fw-bold">${json.grandTotal}</td>`;

        container.innerHTML = `
          <table class="table table-bordered table-sm">
            <thead class="table-light"><tr>${thead}</tr></thead>
            <tbody>${tbody}</tbody>
            <tfoot><tr>${tfoot}</tr></tfoot>
          </table>`;
      } catch (error) {
        console.error('Gagal memuat pivot:', error);
        container.innerHTML = '<div class="text-center text-danger py-4">Gagal memuat data.</div>';
      }

      async function loadPivotAgama() {
        const container = document.getElementById('pivot-tingkat-agama');
        if (!container) return;

        try {
          const response = await fetch("{{ route('siswa.stats-pivot-agama') }}");
          const json = await response.json();

          if (!json.rows.length) {
            container.innerHTML = '<div class="text-center text-muted py-4">Belum ada data.</div>';
            return;
          }
          const thAgama = ["Isl", "Kri", "Kat", "Hin", "Bud", "Kon", "Lain"];

          let thead = '<th>Tingkat</th>' + thAgama.map(a => `<th class="text-center">${esc(a)}</th>`)
            .join(
              '') +
            '<th class="text-center fw-bold">Total</th>';

          let tbody = json.rows.map(row => {
            const cells = json.agamaList.map(a => {
              const val = row.cells[a] ?? 0;
              return `<td class="text-center">${val || '-'}</td>`;
            }).join('');
            return `<tr><td class="fw-medium text-center">Tk ${esc(row.tingkat)}</td>${cells}<td class="text-center fw-bold">${row.total}</td></tr>`;
          }).join('');

          let tfoot = '<td class="fw-bold">Total</td>' +
            json.agamaList.map(a => `<td class="text-center fw-bold">${json.colTotals[a] || 0}</td>`).join('') +
            `<td class="text-center fw-bold">${json.grandTotal}</td>`;

          container.innerHTML = `
            <table class="table table-bordered table-sm">
              <thead class="table-light"><tr>${thead}</tr></thead>
              <tbody>${tbody}</tbody>
              <tfoot><tr>${tfoot}</tr></tfoot>
            </table>`;
        } catch (error) {
          console.error('Gagal memuat pivot agama:', error);
          container.innerHTML = '<div class="text-center text-danger py-4">Gagal memuat data.</div>';
        }
      }
      loadPivotAgama();

      // Jam realtime
      function updateClock() {
        const now = new Date();
        const opt = {
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit',
          hour12: false,
          timeZone: 'Asia/Jakarta'
        };
        document.getElementById('realtimeClock').textContent = now.toLocaleTimeString('id-ID', opt) + ' WIB';
        const optDate = {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric',
          timeZone: 'Asia/Jakarta'
        };
        document.getElementById('realtimeDate').textContent = now.toLocaleDateString('id-ID', optDate);
      }
      updateClock();
      setInterval(updateClock, 1000);

      @if ($isAdmin)
        // Chart – Sekolah per Jenjang (Donut)
        document.addEventListener('DOMContentLoaded', function() {
          const jenjangData = @json($sekolahPerJenjang ?? []);
          const jenjangLabel = Object.keys(jenjangData);
          const jenjangVal = Object.values(jenjangData);

          const isDark = document.documentElement.classList.contains('dark-style');
          const textColor = isDark ? '#cdd0dd' : '#566a7f';

          if (document.getElementById('chartJenjang')) {
            new ApexCharts(document.getElementById('chartJenjang'), {
              chart: {
                type: 'donut',
                height: 220,
                toolbar: {
                  show: false
                }
              },
              series: jenjangVal,
              labels: jenjangLabel,
              colors: ['#696cff', '#03c3ec', '#71dd37', '#ff3e1d', '#ffab00'],
              legend: {
                position: 'bottom',
                labels: {
                  colors: textColor
                }
              },
              dataLabels: {
                style: {
                  fontSize: '11px'
                }
              },
              plotOptions: {
                pie: {
                  donut: {
                    size: '65%'
                  }
                }
              },
            }).render();
          }
        });
      @else
        // Chart – Siswa per Gender (Donut kecil)
        const laki = {{ $siswaLaki ?? 0 }};
        const perempuan = {{ $siswaPerempuan ?? 0 }};
        const isDark = document.documentElement.classList.contains('dark-style');
        const textColor = isDark ? '#cdd0dd' : '#566a7f';

        if (document.getElementById('chartGender')) {
          new ApexCharts(document.getElementById('chartGender'), {
            chart: {
              type: 'donut',
              height: 200,
              toolbar: {
                show: false
              }
            },
            series: [laki, perempuan],
            labels: ['Laki-laki', 'Perempuan'],
            colors: ['#696cff', '#ff3e1d'],
            legend: {
              position: 'bottom',
              labels: {
                colors: textColor
              }
            },
            plotOptions: {
              pie: {
                donut: {
                  size: '65%'
                }
              }
            },
            dataLabels: {
              style: {
                fontSize: '11px'
              }
            },
          }).render();
        }

        let chartSiswaTingkat = null;
        const jenjangSekolah = window.Sekolah?.jenjang;
        console.log(jenjangSekolah);
        const statistikTingkatDashboard = @json($statistikTingkat ?? []);
        const jenjangMap = {
          sd: [1, 2, 3, 4, 5, 6],
          smp: [7, 8, 9],
          sma: [10, 11, 12],
          smk: [10, 11, 12]
        };
        const tingkatListDashboard = jenjangMap[jenjangSekolah] ?? [];

        // ─── DEBUG SEMENTARA ───────────────────────────────
        console.log('1. jenjangSekolah:', jenjangSekolah);
        console.log('2. statistikTingkatDashboard (raw dari server):', statistikTingkatDashboard);
        console.log('3. Jumlah key statistikTingkatDashboard:', Object.keys(statistikTingkatDashboard).length);
        console.log('4. tingkatListDashboard:', tingkatListDashboard);
        console.log('5. Chart.js tersedia?', typeof Chart !== 'undefined');
        console.log('6. Canvas ditemukan?', document.getElementById('chart-siswa-tingkat'));
        // ────────────────────────────────────────────────────

        function renderChartSiswaTingkat(statistikTingkat, tingkatList) {
          const map = Object.fromEntries(statistikTingkat.map(s => [s.tingkat, s]));

          const labels = tingkatList.map(t => `Tk ${t}`);
          const dataL = tingkatList.map(t => map[t]?.L || 0);
          const dataP = tingkatList.map(t => map[t]?.P || 0);

          // ─── DEBUG SEMENTARA ─────────────────────
          console.log('7. map (by tingkat):', map);
          console.log('8. labels:', labels);
          console.log('9. dataL:', dataL);
          console.log('10. dataP:', dataP);
          // ──────────────────────────────────────────

          const ctx = document.getElementById('chart-siswa-tingkat');

          if (chartSiswaTingkat) {
            chartSiswaTingkat.data.labels = labels;
            chartSiswaTingkat.data.datasets[0].data = dataL;
            chartSiswaTingkat.data.datasets[1].data = dataP;
            chartSiswaTingkat.update();
            return;
          }

          chartSiswaTingkat = new Chart(ctx, {
            type: 'bar',
            data: {
              labels,
              datasets: [{
                  label: 'Laki-laki',
                  data: dataL,
                  backgroundColor: '#2196F3',
                  borderRadius: 4,
                  maxBarThickness: 28
                },
                {
                  label: 'Perempuan',
                  data: dataP,
                  backgroundColor: '#E91E63',
                  borderRadius: 4,
                  maxBarThickness: 28
                }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: 'bottom',
                  labels: {
                    boxWidth: 10,
                    font: {
                      size: 11
                    }
                  }
                },
                tooltip: {
                  mode: 'index',
                  intersect: false
                }
              },
              scales: {
                x: {
                  grid: {
                    display: false
                  }
                },
                y: {
                  beginAtZero: true,
                  ticks: {
                    precision: 0
                  }
                }
              }
            }
          });
        }

        renderChartSiswaTingkat(Object.values(statistikTingkatDashboard), tingkatListDashboard);
      @endif
    });
  </script>
@endsection
