@extends('layouts/layoutMaster')

@section('title', 'Rekap Presensi')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('content')

  {{-- =========================================================== --}}
  {{-- HEADER                                                       --}}
  {{-- =========================================================== --}}
  <div class="card mb-4">
    <div class="card-body d-flex align-items-center gap-4 py-4 flex-wrap">
      <div class="avatar avatar-xl flex-shrink-0">
        <span class="avatar-initial rounded-circle bg-label-primary">
          <i class="icon-base ri ri-bar-chart-2-line ri-28px"></i>
        </span>
      </div>
      <div class="flex-grow-1">
        <h4 class="mb-1">Rekap Presensi</h4>
        <span class="text-muted">
          {{ $labelPeriode }} • {{ $hariEfektif }} hari efektif •
          {{ $total['jumlah_siswa'] }} siswa dalam cakupan
        </span>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('presensi.rekap.excel', request()->query()) }}" class="btn btn-label-success">
          <i class="ri ri-file-excel-2-line me-1"></i> Excel
        </a>
        <a href="{{ route('presensi.rekap.pdf', request()->query()) }}" class="btn btn-label-danger">
          <i class="ri ri-file-pdf-2-line me-1"></i> PDF
        </a>
      </div>
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- FILTER                                                       --}}
  {{-- =========================================================== --}}
  <div class="card mb-4">
    <div class="card-body">
      <form method="GET" action="{{ route('presensi.rekap') }}" class="row g-3 align-items-end">
        <div class="col-12 col-md-2">
          <label for="mode_periode" class="form-label">Periode</label>
          <select id="mode_periode" name="mode" class="form-select">
            <option value="semester" {{ $mode === 'semester' ? 'selected' : '' }}>Per Semester</option>
            <option value="tahun" {{ $mode === 'tahun' ? 'selected' : '' }}>Per Tahun Pelajaran</option>
          </select>
        </div>
        <div class="col-12 col-md-3" id="bungkus_semester">
          <label for="semester_id" class="form-label">Semester</label>
          <select id="semester_id" name="semester_id" class="form-select">
            @foreach ($daftarSemester as $s)
              <option value="{{ $s->id }}" {{ $semesterDipilih?->id === $s->id ? 'selected' : '' }}>
                {{ $s->tahun_pelajaran }} — {{ $s->semester }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-3" id="bungkus_tahun">
          <label for="tahun" class="form-label">Tahun Pelajaran</label>
          <select id="tahun" name="tahun" class="form-select">
            @foreach ($daftarTahun as $tp)
              <option value="{{ $tp }}" {{ $tpDipilih === $tp ? 'selected' : '' }}>{{ $tp }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-4">
          <label for="rombel_id" class="form-label">Kelas</label>
          <select id="rombel_id" name="rombel_id" class="form-select">
            <option value="">Seluruh kelas dalam cakupan</option>
            @foreach ($rombelCakupan as $r)
              <option value="{{ $r->id }}" {{ $rombelDipilih === $r->id ? 'selected' : '' }}>
                {{ $r->nama_rombel }} ({{ $r->semester->semester }} {{ $r->semester->tahun_pelajaran }})
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-3">
          <button type="submit" class="btn btn-primary w-100">
            <i class="ri ri-filter-3-line me-1"></i> Terapkan
          </button>
        </div>
      </form>
      <div class="form-text mt-2">
        "Belum absen" dihitung dari hari efektif — tanggal-tanggal yang benar-benar memiliki isian
        presensi pada cakupan Anda — dikurangi hari yang sudah tercatat untuk siswa itu, hanya
        dalam masa registrasinya di sekolah.
      </div>
    </div>
  </div>

  @if ($perSiswa->isEmpty())
    <div class="alert alert-warning">
      Belum ada data presensi pada cakupan dan periode ini.
    </div>
  @else
    {{-- =========================================================== --}}
    {{-- RINGKASAN                                                    --}}
    {{-- =========================================================== --}}
    <div class="row g-4 mb-4">
      <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100">
          <div class="card-body text-center">
            <div class="text-muted small">Kehadiran</div>
            <h3 class="mb-0 text-primary">{{ $total['persen'] }}%</h3>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100">
          <div class="card-body text-center">
            <div class="text-muted small">Hadir</div>
            <h3 class="mb-0 text-success">{{ $total['hadir'] }}</h3>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100">
          <div class="card-body text-center">
            <div class="text-muted small">Sakit</div>
            <h3 class="mb-0 text-warning">{{ $total['sakit'] }}</h3>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100">
          <div class="card-body text-center">
            <div class="text-muted small">Izin</div>
            <h3 class="mb-0 text-info">{{ $total['izin'] }}</h3>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100">
          <div class="card-body text-center">
            <div class="text-muted small">Alpa</div>
            <h3 class="mb-0 text-danger">{{ $total['alpa'] }}</h3>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100">
          <div class="card-body text-center">
            <div class="text-muted small">Belum Absen</div>
            <h3 class="mb-0 text-secondary">{{ $total['belum'] }}</h3>
          </div>
        </div>
      </div>
    </div>

    {{-- =========================================================== --}}
    {{-- GRAFIK                                                       --}}
    {{-- =========================================================== --}}
    <div class="row g-4 mb-4">
      <div class="col-12 col-xl-4">
        <div class="card h-100">
          <div class="card-header">
            <h5 class="mb-0">Komposisi Kehadiran</h5>
          </div>
          <div class="card-body">
            <div id="chart_donat"></div>
          </div>
        </div>
      </div>
      @if ($perKelas->count() > 1)
        <div class="col-12 col-xl-8">
          <div class="card h-100">
            <div class="card-header">
              <h5 class="mb-0">% Kehadiran per Kelas</h5>
            </div>
            <div class="card-body">
              <div id="chart_kelas"></div>
            </div>
          </div>
        </div>
      @endif
    </div>

    {{-- =========================================================== --}}
    {{-- TABEL PER KELAS                                              --}}
    {{-- =========================================================== --}}
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="mb-0">Rekap per Kelas</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Kelas</th>
              <th class="text-center">Siswa</th>
              <th class="text-center">Hadir</th>
              <th class="text-center">Sakit</th>
              <th class="text-center">Izin</th>
              <th class="text-center">Alpa</th>
              <th class="text-center">Belum Absen</th>
              <th style="min-width:160px;">% Hadir</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($perKelas as $k)
              <tr>
                <td class="fw-medium">{{ $k['rombel']->nama_rombel }}</td>
                <td class="text-center">{{ $k['jumlah_siswa'] }}</td>
                <td class="text-center">{{ $k['hadir'] }}</td>
                <td class="text-center">{{ $k['sakit'] }}</td>
                <td class="text-center">{{ $k['izin'] }}</td>
                <td class="text-center">{{ $k['alpa'] }}</td>
                <td class="text-center">{{ $k['belum'] }}</td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:8px;">
                      <div class="progress-bar bg-success" style="width:{{ $k['persen'] }}%"></div>
                    </div>
                    <small>{{ $k['persen'] }}%</small>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
          <tfoot class="table-light">
            <tr class="fw-bold">
              <td>Total</td>
              <td class="text-center">{{ $total['jumlah_siswa'] }}</td>
              <td class="text-center">{{ $total['hadir'] }}</td>
              <td class="text-center">{{ $total['sakit'] }}</td>
              <td class="text-center">{{ $total['izin'] }}</td>
              <td class="text-center">{{ $total['alpa'] }}</td>
              <td class="text-center">{{ $total['belum'] }}</td>
              <td>{{ $total['persen'] }}%</td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    {{-- =========================================================== --}}
    {{-- TABEL PER SISWA                                              --}}
    {{-- =========================================================== --}}
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Rekap per Siswa</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:40px;" class="text-center">#</th>
              <th>Nama Siswa</th>
              <th>Kelas</th>
              <th class="text-center">Hadir</th>
              <th class="text-center">Sakit</th>
              <th class="text-center">Izin</th>
              <th class="text-center">Alpa</th>
              <th class="text-center">Belum Absen</th>
              <th style="min-width:160px;">% Hadir</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($perSiswa as $idx => $r)
              <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td>
                  <a href="{{ route('siswa.show', $r['siswa']->id) }}" class="text-decoration-none text-reset fw-medium">
                    {{ $r['siswa']->nama_siswa }}
                  </a>
                  <br><small class="text-muted">{{ $r['siswa']->nisn ?? '-' }}</small>
                </td>
                <td>{{ $r['rombel']->nama_rombel }}</td>
                <td class="text-center">{{ $r['hadir'] }}</td>
                <td class="text-center">{{ $r['sakit'] }}</td>
                <td class="text-center">{{ $r['izin'] }}</td>
                <td class="text-center">{{ $r['alpa'] }}</td>
                <td class="text-center">{{ $r['belum'] }}</td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="progress flex-grow-1" style="height:8px;">
                      <div class="progress-bar bg-success" style="width:{{ $r['persen'] }}%"></div>
                    </div>
                    <small>{{ $r['persen'] }}%</small>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // ── Tampilkan pemilih semester / tahun sesuai mode ──────────
      const mode = document.getElementById('mode_periode');
      const bungkusSemester = document.getElementById('bungkus_semester');
      const bungkusTahun = document.getElementById('bungkus_tahun');

      function sesuaikanMode() {
        const tahun = mode.value === 'tahun';
        bungkusSemester.classList.toggle('d-none', tahun);
        bungkusTahun.classList.toggle('d-none', !tahun);
      }
      mode.addEventListener('change', sesuaikanMode);
      sesuaikanMode();

      // ── Grafik ────────────────────────────────────────────────────
      if (typeof ApexCharts !== 'undefined') {
        const total = @json([
            'hadir' => $total['hadir'],
            'sakit' => $total['sakit'],
            'izin' => $total['izin'],
            'alpa' => $total['alpa'],
            'belum' => $total['belum'],
        ]);

        const elDonat = document.getElementById('chart_donat');
        if (elDonat) {
          new ApexCharts(elDonat, {
            chart: {
              type: 'donut',
              height: 300
            },
            series: [total.hadir, total.sakit, total.izin, total.alpa, total.belum],
            labels: ['Hadir', 'Sakit', 'Izin', 'Alpa', 'Belum Absen'],
            colors: ['#28c76f', '#ff9f43', '#00cfe8', '#ea5455', '#a8aaae'],
            legend: {
              position: 'bottom'
            },
          }).render();
        }

        const elKelas = document.getElementById('chart_kelas');
        if (elKelas) {
          const perKelas = @json($perKelas->map(fn($k) => ['kelas' => $k['rombel']->nama_rombel, 'persen' => $k['persen']]));
          new ApexCharts(elKelas, {
            chart: {
              type: 'bar',
              height: 300
            },
            series: [{
              name: '% Hadir',
              data: perKelas.map(k => k.persen)
            }],
            xaxis: {
              categories: perKelas.map(k => k.kelas)
            },
            yaxis: {
              max: 100
            },
            colors: ['#696cff'],
            dataLabels: {
              enabled: true,
              formatter: v => v + '%'
            },
          }).render();
        }
      }
    });
  </script>
@endsection
