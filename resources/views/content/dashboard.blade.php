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

@section('page-style')
  <style>
    /* ── Dashboard modern: permukaan, hero, kartu statistik ───────────── */
    .db-hero {
      position: relative;
      overflow: hidden;
      border: none;
      border-radius: 1.1rem;
      background: linear-gradient(120deg, #5a54d1 0%, #696cff 45%, #03c3ec 115%);
      color: #fff;
      box-shadow: 0 12px 30px -12px rgba(105, 108, 255, .55);
    }

    .db-hero::before,
    .db-hero::after {
      content: '';
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, .13);
      pointer-events: none;
    }

    .db-hero::before {
      width: 300px;
      height: 300px;
      right: -90px;
      top: -140px;
    }

    .db-hero::after {
      width: 190px;
      height: 190px;
      right: 110px;
      bottom: -110px;
      background: rgba(255, 255, 255, .09);
    }

    .db-hero .db-hero-illu {
      position: absolute;
      right: 1.1rem;
      bottom: 0;
      height: 132px;
      z-index: 1;
      filter: drop-shadow(0 8px 14px rgba(0, 0, 0, .18));
    }

    @media (max-width: 575.98px) {
      .db-hero .db-hero-illu {
        display: none;
      }
    }

    .db-chip {
      display: inline-flex;
      align-items: center;
      gap: .45rem;
      padding: .32rem .8rem;
      border-radius: 999px;
      background: rgba(255, 255, 255, .16);
      border: 1px solid rgba(255, 255, 255, .28);
      color: #fff;
      font-size: .8rem;
      font-weight: 500;
      backdrop-filter: blur(4px);
    }

    .db-card {
      border: 1px solid rgba(67, 89, 113, .12);
      border-radius: 1.1rem;
      box-shadow: 0 6px 18px -8px rgba(67, 89, 113, .18);
    }

    html.dark-style .db-card {
      border-color: rgba(255, 255, 255, .08);
      box-shadow: 0 6px 18px -8px rgba(0, 0, 0, .5);
    }

    .db-stat {
      transition: transform .18s ease, box-shadow .18s ease;
    }

    .db-stat:hover {
      transform: translateY(-4px);
      box-shadow: 0 16px 28px -12px rgba(67, 89, 113, .35);
    }

    .db-stat .db-stat-icon {
      width: 52px;
      height: 52px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 1rem;
      color: #fff;
      font-size: 1.45rem;
      box-shadow: 0 8px 16px -6px rgba(67, 89, 113, .45);
    }

    .db-grad-primary {
      background: linear-gradient(135deg, #696cff, #8a8dff);
    }

    .db-grad-success {
      background: linear-gradient(135deg, #56ca00, #8ce99a);
    }

    .db-grad-warning {
      background: linear-gradient(135deg, #ffb400, #ffd43b);
    }

    .db-grad-info {
      background: linear-gradient(135deg, #03c3ec, #66d9f5);
    }

    .db-grad-secondary {
      background: linear-gradient(135deg, #8592a3, #a8b2c1);
    }

    .db-grad-danger {
      background: linear-gradient(135deg, #ff3e1d, #ff7a59);
    }

    .db-stat .db-stat-value {
      font-size: 1.85rem;
      line-height: 1.1;
      font-weight: 700;
      letter-spacing: -.02em;
    }

    .db-section-head {
      display: flex;
      align-items: center;
      gap: .8rem;
    }

    .db-section-head .db-section-icon {
      width: 42px;
      height: 42px;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: .8rem;
      font-size: 1.2rem;
    }

    .db-pivot table {
      margin-bottom: 0;
      font-size: .875rem;
    }

    .db-pivot thead th {
      background: rgba(105, 108, 255, .08);
      font-weight: 600;
      white-space: nowrap;
    }

    html.dark-style .db-pivot thead th {
      background: rgba(105, 108, 255, .18);
    }

    .db-pivot tfoot td {
      background: rgba(67, 89, 113, .06);
    }

    html.dark-style .db-pivot tfoot td {
      background: rgba(255, 255, 255, .05);
    }

    .db-birthday {
      border: none;
      border-radius: 1.1rem;
      background: linear-gradient(120deg, #ffb400, #ff3e1d 130%);
      color: #fff;
      box-shadow: 0 12px 26px -12px rgba(255, 171, 0, .65);
    }

    .db-login-table td {
      vertical-align: middle;
    }
  </style>
@endsection

@section('content')

  {{-- ── INFO HARI INI : ucapan untuk saya, kaldik, ulang tahun ── --}}
  @if ($ucapanSaya)
    <div class="card db-birthday mb-6">
      <div class="card-body d-flex align-items-start gap-3">
        <span class="avatar avatar-md flex-shrink-0">
          <span class="avatar-initial rounded-circle bg-white bg-opacity-25">
            <i class="ri ri-cake-2-line ri-24px text-white"></i>
          </span>
        </span>
        <div>
          <h5 class="mb-1 text-white">{{ $ucapanSaya['judul'] }}</h5>
          <p class="mb-0 text-white text-opacity-75">{{ $ucapanSaya['doa'] }}</p>
        </div>
      </div>
    </div>
  @endif

  @if ($kaldikHariIni->isNotEmpty() || $ultahHariIni->isNotEmpty())
    <div class="row g-6 mb-6">
      @if ($kaldikHariIni->isNotEmpty())
        <div class="col-12 {{ $ultahHariIni->isNotEmpty() ? 'col-lg-6' : '' }}">
          <div class="card db-card h-100">
            <div class="card-body">
              <div class="db-section-head mb-4">
                <span class="db-section-icon bg-label-warning text-warning">
                  <i class="ri ri-calendar-event-line"></i>
                </span>
                <div>
                  <h6 class="mb-0">Kalender Pendidikan Hari Ini</h6>
                  <small class="text-muted">Kegiatan yang mencakup hari ini</small>
                </div>
              </div>
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
          <div class="card db-card h-100">
            <div class="card-body">
              <div class="db-section-head mb-4">
                <span class="db-section-icon bg-label-primary text-primary">
                  <i class="ri ri-gift-line"></i>
                </span>
                <div>
                  <h6 class="mb-0">Ulang Tahun Hari Ini</h6>
                  <small class="text-muted">PTK aktif yang berulang tahun</small>
                </div>
              </div>
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

  {{-- ── ROW 1 : Hero Selamat Datang + Profil Sekolah / Distribusi ── --}}
  <div class="row g-6 mb-6">
    <div class="col-12 col-lg-7">
      <div class="card db-hero h-100">
        <div class="card-body position-relative" style="z-index: 2;">
          <span class="db-chip mb-4">
            <i class="ri ri-calendar-2-line"></i>
            <span id="realtimeDate">—</span>
            <span class="opacity-50">|</span>
            <i class="ri ri-time-line"></i>
            <span id="realtimeClock" class="font-monospace fw-semibold">00:00:00</span>
          </span>
          <div class="small text-white text-opacity-75 mb-1">Selamat Datang,</div>
          <h3 class="fw-bold mb-2 text-white">{{ $ptk->nama_ptk ?? $user->name }}</h3>
          <p class="mb-5">
            @if ($isAdmin)
              <span class="badge bg-white text-primary fw-semibold rounded-pill px-3 py-2">Administrator</span>
            @else
              <span
                class="badge bg-white text-primary fw-semibold rounded-pill px-3 py-2">{{ $ptk->jabatan ?? 'Pengguna' }}</span>
            @endif
            @if (!$isAdmin && isset($sekolah) && $sekolah)
              <span class="badge bg-white bg-opacity-25 text-white fw-medium rounded-pill px-3 py-2 ms-1">
                {{ $sekolahDetail->nama_sekolah ?? '' }}
              </span>
            @endif
          </p>
          <a href="{{ url('user/profile') }}" class="btn btn-light btn-sm fw-semibold shadow-sm">
            <i class="ri ri-user-3-line me-1"></i> Lihat Profil
          </a>
        </div>
        <img src="{{ asset('assets/img/illustrations/man-with-laptop.png') }}" class="db-hero-illu"
          alt="Ilustrasi selamat datang" onerror="this.style.display='none'" />
      </div>
    </div>

    @if (!$isAdmin)
      @if ($sekolah && $sekolahDetail)
        <div class="col-12 col-lg-5">
          <div class="card db-card h-100">
            <div class="card-body">
              <div class="d-flex align-items-start gap-3 mb-4">
                @if ($logo)
                  <img src="{{ asset('storage/' . $logo->path) }}" height="52" width="52" alt="Logo sekolah"
                    class="rounded-3 border object-fit-contain flex-shrink-0" onerror="this.style.display='none'" />
                @else
                  <span class="db-section-icon db-grad-primary text-white flex-shrink-0" style="width:52px;height:52px;">
                    <i class="ri ri-school-line"></i>
                  </span>
                @endif
                <div class="flex-grow-1 min-w-0">
                  <h5 class="fw-bold text-primary mb-1 text-truncate">{{ $sekolahDetail->nama_sekolah ?? '-' }}</h5>
                  <small class="text-muted">NPSN {{ $sekolah->npsn }}</small>
                </div>
                <span class="badge bg-label-primary rounded-pill flex-shrink-0">{{ strtoupper($sekolah->jenjang) }}</span>
              </div>
              <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
                <li class="d-flex align-items-start gap-3">
                  <span class="db-section-icon bg-label-primary text-primary" style="width:38px;height:38px;">
                    <i class="ri ri-user-line"></i>
                  </span>
                  <div>
                    <small class="text-muted d-block">Kepala Sekolah</small>
                    <span class="fw-medium">{{ $sekolahDetail->kepala_sekolah ?? '-' }}</span>
                    @if ($sekolahDetail->nip)
                      <small class="text-muted d-block">NIP: {{ $sekolahDetail->nip }}</small>
                    @endif
                  </div>
                </li>
                <li class="d-flex align-items-start gap-3">
                  <span class="db-section-icon bg-label-success text-success" style="width:38px;height:38px;">
                    <i class="ri ri-map-pin-line"></i>
                  </span>
                  <div>
                    <small class="text-muted d-block">Alamat</small>
                    <small class="text-body">
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
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
      @else
        <div class="col-12 col-lg-5">
          <div class="card db-card h-100">
            <div class="card-body text-center py-8">
              <i class="ri ri-school-line icon-48px text-muted d-block mb-3"></i>
              <h6 class="text-muted">Data sekolah belum tersedia.</h6>
              <p class="text-muted small mb-0">Hubungi administrator untuk mengatur data sekolah Anda.</p>
            </div>
          </div>
        </div>
      @endif
    @else
      <div class="col-12 col-lg-5">
        <div class="card db-card h-100">
          <div class="card-body">
            <div class="db-section-head mb-2">
              <span class="db-section-icon bg-label-primary text-primary">
                <i class="ri ri-pie-chart-line"></i>
              </span>
              <div>
                <h5 class="mb-0">Distribusi Sekolah</h5>
                <small class="text-muted">Berdasarkan jenjang pendidikan</small>
              </div>
            </div>
            <div id="chartJenjang" style="width:100%"></div>
          </div>
        </div>
      </div>
    @endif
  </div>
  {{-- /ROW 1 --}}

  {{-- ── ROW 2 : Kartu statistik ── --}}
  <div class="row g-6 mb-6">
    @if ($isAdmin)
      @php
        $statAdmin = [
            ['label' => 'Total Sekolah', 'value' => $jumlahSekolah, 'icon' => 'ri-building-line', 'grad' => 'db-grad-primary', 'badge' => 'Semua Jenjang', 'badgeClass' => 'bg-label-primary'],
            ['label' => 'Total Siswa Aktif', 'value' => $jumlahSiswa, 'icon' => 'ri-group-line', 'grad' => 'db-grad-success', 'badge' => 'Registrasi terbaru aktif', 'badgeClass' => 'bg-label-success'],
            ['label' => 'Total Rombel', 'value' => $jumlahRombel, 'icon' => 'ri-door-open-line', 'grad' => 'db-grad-warning', 'badge' => 'Semester tampilan', 'badgeClass' => 'bg-label-warning'],
            ['label' => 'Total PTK Aktif', 'value' => $jumlahPtk, 'icon' => 'ri-user-star-line', 'grad' => 'db-grad-info', 'badge' => 'Status aktif saja', 'badgeClass' => 'bg-label-info'],
        ];
      @endphp
      @foreach ($statAdmin as $s)
        <div class="col-12 col-sm-6 col-xl-3">
          <div class="card db-card db-stat h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-4">
                <span class="db-stat-icon {{ $s['grad'] }}"><i class="ri {{ $s['icon'] }}"></i></span>
                <span class="badge {{ $s['badgeClass'] }} rounded-pill">{{ $s['badge'] }}</span>
              </div>
              <div class="db-stat-value">{{ number_format($s['value']) }}</div>
              <p class="text-muted mb-0">{{ $s['label'] }}</p>
            </div>
          </div>
        </div>
      @endforeach
    @elseif($sekolah)
      @php
        $statSekolah = [
            ['label' => 'Siswa Aktif', 'value' => $jumlahSiswa, 'icon' => 'ri-group-line', 'grad' => 'db-grad-success', 'badge' => 'Registrasi terbaru aktif', 'badgeClass' => 'bg-label-success'],
            ['label' => 'Rombel', 'value' => $jumlahRombel, 'icon' => 'ri-door-open-line', 'grad' => 'db-grad-warning', 'badge' => 'Semester tampilan', 'badgeClass' => 'bg-label-warning'],
            ['label' => 'Guru', 'value' => $jumlahGuru, 'icon' => 'ri-user-star-line', 'grad' => 'db-grad-info', 'badge' => 'PTK aktif', 'badgeClass' => 'bg-label-info'],
            ['label' => 'Tendik', 'value' => $jumlahTendik, 'icon' => 'ri-briefcase-line', 'grad' => 'db-grad-secondary', 'badge' => 'PTK aktif', 'badgeClass' => 'bg-label-secondary'],
            ['label' => 'Total PTK', 'value' => $jumlahGuru + $jumlahTendik, 'icon' => 'ri-team-line', 'grad' => 'db-grad-primary', 'badge' => 'Guru + Tendik', 'badgeClass' => 'bg-label-primary'],
        ];
      @endphp
      @foreach ($statSekolah as $s)
        <div class="col-12 col-sm-6 col-lg-4 col-xxl">
          <div class="card db-card db-stat h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-4">
                <span class="db-stat-icon {{ $s['grad'] }}"><i class="ri {{ $s['icon'] }}"></i></span>
              </div>
              <div class="db-stat-value">{{ number_format($s['value']) }}</div>
              <p class="text-muted mb-2">{{ $s['label'] }}</p>
              <span class="badge {{ $s['badgeClass'] }} rounded-pill">{{ $s['badge'] }}</span>
            </div>
          </div>
        </div>
      @endforeach
    @endif
  </div>
  {{-- /ROW 2 --}}

  {{-- ── ROW 3 ── --}}
  <div class="row g-6 mb-6">
    @if ($isAdmin)
      <div class="col-12">
        <div class="card db-card h-100">
          <div class="card-body">
            <div class="db-section-head mb-4">
              <span class="db-section-icon bg-label-primary text-primary">
                <i class="ri ri-bar-chart-2-line"></i>
              </span>
              <div>
                <h5 class="mb-0">Rekap Data Keseluruhan</h5>
                <small class="text-muted">Ringkasan seluruh data pendidikan</small>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-borderless align-middle mb-0">
                <tbody>
                  @php
                    $rekapAdmin = [
                        ['label' => 'Total Sekolah', 'value' => $jumlahSekolah, 'icon' => 'ri-building-line', 'class' => 'bg-label-primary text-primary'],
                        ['label' => 'Siswa Aktif', 'value' => $jumlahSiswa, 'icon' => 'ri-group-line', 'class' => 'bg-label-success text-success'],
                        ['label' => 'Rombongan Belajar', 'value' => $jumlahRombel, 'icon' => 'ri-door-open-line', 'class' => 'bg-label-warning text-warning'],
                        ['label' => 'Guru', 'value' => $jumlahGuru, 'icon' => 'ri-user-star-line', 'class' => 'bg-label-info text-info'],
                        ['label' => 'Tenaga Kependidikan', 'value' => $jumlahTendik, 'icon' => 'ri-briefcase-line', 'class' => 'bg-label-secondary text-secondary'],
                    ];
                  @endphp
                  @foreach ($rekapAdmin as $r)
                    <tr>
                      <td class="py-2 ps-0">
                        <div class="d-flex align-items-center gap-3">
                          <span class="db-section-icon {{ $r['class'] }}" style="width:38px;height:38px;">
                            <i class="ri {{ $r['icon'] }}"></i>
                          </span>
                          <span class="fw-medium">{{ $r['label'] }}</span>
                        </div>
                      </td>
                      <td class="py-2 text-end fw-bold fs-5">{{ number_format($r['value']) }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    @else
      {{-- Chart gender siswa --}}
      <div class="col-12 col-md-6 col-xl-3">
        <div class="card db-card h-100">
          <div class="card-body d-flex flex-column">
            <div class="db-section-head mb-2">
              <span class="db-section-icon bg-label-success text-success">
                <i class="ri ri-pie-chart-line"></i>
              </span>
              <div>
                <h6 class="mb-0">Jenis Kelamin</h6>
                <small class="text-muted">Siswa aktif</small>
              </div>
            </div>
            <div id="chartGender" class="flex-grow-1" style="min-height:190px"></div>
            <div class="d-flex justify-content-center gap-4 mt-2">
              <div class="text-center">
                <h5 class="mb-0 fw-bold text-primary">{{ number_format($siswaLaki ?? 0) }}</h5>
                <small class="text-muted">Laki-laki</small>
              </div>
              <div class="vr"></div>
              <div class="text-center">
                <h5 class="mb-0 fw-bold text-danger">{{ number_format($siswaPerempuan ?? 0) }}</h5>
                <small class="text-muted">Perempuan</small>
              </div>
              <div class="vr"></div>
              <div class="text-center">
                <h5 class="mb-0 fw-bold">{{ number_format($jumlahSiswa ?? 0) }}</h5>
                <small class="text-muted">Total</small>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Distribusi gender per tingkat --}}
      <div class="col-12 col-md-6 col-xl-4">
        <div class="card db-card h-100">
          <div class="card-body d-flex flex-column">
            <div class="db-section-head mb-3">
              <span class="db-section-icon bg-label-info text-info">
                <i class="ri ri-bar-chart-grouped-line"></i>
              </span>
              <div>
                <h6 class="mb-0">Distribusi per Tingkat</h6>
                <small class="text-muted">Anggota rombel aktif, semester tampilan</small>
              </div>
            </div>
            <div class="flex-grow-1" style="position: relative; min-height:230px;">
              <canvas id="chart-siswa-tingkat"></canvas>
            </div>
          </div>
        </div>
      </div>

      {{-- Pivot Agama --}}
      <div class="col-12 col-xl-5">
        <div class="card db-card h-100">
          <div class="card-body">
            <div class="db-section-head mb-3">
              <span class="db-section-icon bg-label-warning text-warning">
                <i class="ri ri-table-line"></i>
              </span>
              <div>
                <h6 class="mb-0">Pivot Siswa: Tingkat &times; Agama</h6>
                <small class="text-muted">Anggota rombel aktif, semester tampilan</small>
              </div>
            </div>
            <div id="pivot-tingkat-agama" class="table-responsive db-pivot">
              <div class="text-center text-muted py-4">Memuat data...</div>
            </div>
          </div>
        </div>
      </div>
    @endif
  </div>
  {{-- /ROW 3 --}}

  {{-- ── ROW 4 : Pivot Usia ── --}}
  <div class="row g-6 mb-6">
    <div class="col-12">
      <div class="card db-card">
        <div class="card-body">
          <div class="db-section-head mb-3">
            <span class="db-section-icon bg-label-danger text-danger">
              <i class="ri ri-table-2"></i>
            </span>
            <div>
              <h5 class="mb-0">Pivot Siswa: Tingkat &times; Usia</h5>
              <small class="text-muted">Berdasarkan registrasi terbaru yang aktif. Siswa tanpa tanggal lahir tidak
                masuk pivot ini.</small>
            </div>
          </div>
          <div id="pivot-tingkat-usia" class="table-responsive db-pivot">
            <div class="text-center text-muted py-4">Memuat data...</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ── ROW 5 : Riwayat Login ── --}}
  <div class="row g-6">
    <div class="col-12">
      <div class="card db-card">
        <div class="card-body pb-0">
          <div class="db-section-head mb-2">
            <span class="db-section-icon bg-label-secondary text-secondary">
              <i class="ri ri-history-line"></i>
            </span>
            <div>
              <h5 class="mb-0">Riwayat Login Terakhir</h5>
              <small class="text-muted">
                {{ $isAdmin ? 'Seluruh pengguna sistem' : 'Pengguna di sekolah ini' }}
              </small>
            </div>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover db-login-table mb-0">
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
                      <span class="avatar avatar-sm">
                        <span class="avatar-initial bg-label-primary rounded-circle">
                          {{ strtoupper(substr($u->name, 0, 1)) }}
                        </span>
                      </span>
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
    document.addEventListener('DOMContentLoaded', function() {
      function esc(str) {
        return String(str ?? '').replace(/[&<>"']/g, c => ({
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#39;'
        } [c]));
      }

      // ── Jam realtime ────────────────────────────────────────────────
      function updateClock() {
        const elClock = document.getElementById('realtimeClock');
        const elDate = document.getElementById('realtimeDate');
        if (!elClock || !elDate) return;
        const now = new Date();
        elClock.textContent = now.toLocaleTimeString('id-ID', {
          hour: '2-digit',
          minute: '2-digit',
          second: '2-digit',
          hour12: false,
          timeZone: 'Asia/Jakarta'
        }) + ' WIB';
        elDate.textContent = now.toLocaleDateString('id-ID', {
          weekday: 'long',
          year: 'numeric',
          month: 'long',
          day: 'numeric',
          timeZone: 'Asia/Jakarta'
        });
      }
      updateClock();
      setInterval(updateClock, 1000);

      // ── Pivot Tingkat × Usia ────────────────────────────────────────
      async function loadPivotUsia() {
        const container = document.getElementById('pivot-tingkat-usia');
        if (!container) return;

        try {
          const response = await fetch("{{ route('siswa.stats-pivot-usia') }}");
          const json = await response.json();

          if (!json.rows.length) {
            container.innerHTML = '<div class="text-center text-muted py-4">Belum ada data.</div>';
            return;
          }

          const thead = '<th>Tingkat</th>' + json.usiaList.map(u =>
            `<th class="text-center">${esc(u)} th</th>`).join('') +
            '<th class="text-center fw-bold">Total</th>';

          const tbody = json.rows.map(row => {
            const cells = json.usiaList.map(u => {
              const val = row.cells[u] ?? 0;
              return `<td class="text-center">${val || '-'}</td>`;
            }).join('');
            return `<tr><td class="fw-medium">Tingkat ${esc(row.tingkat)}</td>${cells}` +
              `<td class="text-center fw-bold">${row.total}</td></tr>`;
          }).join('');

          const tfoot = '<td class="fw-bold">Total</td>' +
            json.usiaList.map(u => `<td class="text-center fw-bold">${json.colTotals[u] || 0}</td>`).join('') +
            `<td class="text-center fw-bold">${json.grandTotal}</td>`;

          container.innerHTML =
            `<table class="table table-bordered table-sm align-middle">` +
            `<thead><tr>${thead}</tr></thead><tbody>${tbody}</tbody>` +
            `<tfoot><tr>${tfoot}</tr></tfoot></table>`;
        } catch (error) {
          console.error('Gagal memuat pivot usia:', error);
          container.innerHTML = '<div class="text-center text-danger py-4">Gagal memuat data.</div>';
        }
      }
      loadPivotUsia();

      // ── Pivot Tingkat × Agama ───────────────────────────────────────
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

          const thead = '<th>Tingkat</th>' + thAgama.map(a =>
            `<th class="text-center">${esc(a)}</th>`).join('') +
            '<th class="text-center fw-bold">Total</th>';

          const tbody = json.rows.map(row => {
            const cells = json.agamaList.map(a => {
              const val = row.cells[a] ?? 0;
              return `<td class="text-center">${val || '-'}</td>`;
            }).join('');
            return `<tr><td class="fw-medium text-center">Tk ${esc(row.tingkat)}</td>${cells}` +
              `<td class="text-center fw-bold">${row.total}</td></tr>`;
          }).join('');

          const tfoot = '<td class="fw-bold">Total</td>' +
            json.agamaList.map(a => `<td class="text-center fw-bold">${json.colTotals[a] || 0}</td>`).join('') +
            `<td class="text-center fw-bold">${json.grandTotal}</td>`;

          container.innerHTML =
            `<table class="table table-bordered table-sm align-middle">` +
            `<thead><tr>${thead}</tr></thead><tbody>${tbody}</tbody>` +
            `<tfoot><tr>${tfoot}</tr></tfoot></table>`;
        } catch (error) {
          console.error('Gagal memuat pivot agama:', error);
          container.innerHTML = '<div class="text-center text-danger py-4">Gagal memuat data.</div>';
        }
      }
      loadPivotAgama();

      const isDark = document.documentElement.classList.contains('dark-style');
      const textColor = isDark ? '#cdd0dd' : '#566a7f';

      @if ($isAdmin)
        // ── Chart Sekolah per Jenjang (Donut) ─────────────────────────
        const jenjangData = @json($sekolahPerJenjang ?? []);
        if (document.getElementById('chartJenjang') && typeof ApexCharts !== 'undefined') {
          new ApexCharts(document.getElementById('chartJenjang'), {
            chart: {
              type: 'donut',
              height: 230,
              toolbar: {
                show: false
              }
            },
            series: Object.values(jenjangData),
            labels: Object.keys(jenjangData),
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
                  size: '65%',
                  labels: {
                    show: true,
                    total: {
                      show: true,
                      label: 'Sekolah'
                    }
                  }
                }
              }
            }
          }).render();
        }
      @else
        // ── Chart Siswa per Gender (Donut) ────────────────────────────
        const laki = {{ $siswaLaki ?? 0 }};
        const perempuan = {{ $siswaPerempuan ?? 0 }};

        if (document.getElementById('chartGender') && typeof ApexCharts !== 'undefined') {
          new ApexCharts(document.getElementById('chartGender'), {
            chart: {
              type: 'donut',
              height: 210,
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
                  size: '65%',
                  labels: {
                    show: true,
                    total: {
                      show: true,
                      label: 'Siswa Aktif'
                    }
                  }
                }
              }
            },
            dataLabels: {
              style: {
                fontSize: '11px'
              }
            }
          }).render();
        }

        // ── Chart Distribusi Gender per Tingkat (Bar) ─────────────────
        const statistikTingkatDashboard = @json($statistikTingkat ?? []);
        const jenjangSekolah = window.Sekolah?.jenjang;
        const jenjangMap = {
          sd: [1, 2, 3, 4, 5, 6],
          smp: [7, 8, 9],
          sma: [10, 11, 12],
          smk: [10, 11, 12, 13]
        };
        // Jenjang tak dikenal / data di luar peta: pakai tingkat yang
        // benar-benar ada di data supaya tidak ada bar yang hilang.
        let tingkatListDashboard = jenjangMap[jenjangSekolah] ?? [];
        if (!tingkatListDashboard.length) {
          tingkatListDashboard = Object.values(statistikTingkatDashboard)
            .map(s => Number(s.tingkat))
            .filter(n => !Number.isNaN(n))
            .sort((a, b) => a - b);
        }

        const canvasTingkat = document.getElementById('chart-siswa-tingkat');
        if (canvasTingkat && typeof Chart !== 'undefined') {
          const map = Object.fromEntries(
            Object.values(statistikTingkatDashboard).map(s => [s.tingkat, s])
          );
          new Chart(canvasTingkat, {
            type: 'bar',
            data: {
              labels: tingkatListDashboard.map(t => `Tk ${t}`),
              datasets: [{
                  label: 'Laki-laki',
                  data: tingkatListDashboard.map(t => map[t]?.L || 0),
                  backgroundColor: '#2196F3',
                  borderRadius: 4,
                  maxBarThickness: 28
                },
                {
                  label: 'Perempuan',
                  data: tingkatListDashboard.map(t => map[t]?.P || 0),
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
      @endif
    });
  </script>
@endsection
