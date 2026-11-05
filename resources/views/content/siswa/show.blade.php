@extends('layouts/layoutMaster')

@section('title', 'Detail Siswa — ' . $siswa->nama_siswa)

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')

  {{-- ── Tombol Kembali ─────────────────────────────────────────────────────── --}}
  <div class="mb-4">
    <button class="btn btn-outline-secondary" onclick="history.back()">
      <i class="ri ri-arrow-left-line me-1"></i> Kembali
    </button>
  </div>

  {{-- ── Header Card: Avatar + Info Utama ──────────────────────────────────── --}}
  <div class="card mb-4">
    <div class="card-body">
      <div class="d-flex align-items-center gap-4 flex-wrap">

        {{-- Avatar Inisial --}}
        @php
          $namaParts = explode(' ', trim($siswa->nama_siswa));
          $initials = strtoupper(
              substr($namaParts[0], 0, 1) . (isset($namaParts[1]) ? substr($namaParts[1], 0, 1) : ''),
          );

          // Warna deterministik berdasarkan nama
          $palette = ['#696cff', '#03c3ec', '#71dd37', '#ff3e1d', '#ffab00', '#20c997', '#f26cf9', '#8592a3'];
          $hash = 0;
          foreach (str_split($siswa->nama_siswa) as $c) {
              $hash = ($hash * 31 + ord($c)) & 0x7fffffff;
          }
          $avatarColor = $palette[$hash % count($palette)];

          $reg = $siswa->registrasiTerbaru;
          $status = $reg?->status ?? 'tidak diketahui';
          $statusClass = match ($status) {
              'aktif' => 'bg-label-success',
              'alumni' => 'bg-label-info',
              'keluar' => 'bg-label-danger',
              default => 'bg-label-secondary',
          };
        @endphp

        <div class="d-flex align-items-center justify-content-center rounded-circle text-white fw-bold flex-shrink-0"
          style="width:80px;height:80px;background:{{ $avatarColor }};font-size:28px">
          {{ $initials }}
        </div>

        {{-- Info ringkas --}}
        <div class="flex-grow-1">
          <h4 class="mb-1">{{ $siswa->nama_siswa }}</h4>
          <div class="d-flex flex-wrap gap-3 text-muted small">
            <span><i class="ri ri-barcode-line me-1"></i>NISN: <strong
                class="text-body">{{ $reg?->nisn ?? '-' }}</strong></span>
            <span><i class="ri ri-hashtag me-1"></i>NIS: <strong class="text-body">{{ $reg?->nis ?? '-' }}</strong></span>
            <span><i class="ri ri-school-line me-1"></i>Tingkat: <strong
                class="text-body">{{ $reg?->tingkat ?? '-' }}</strong></span>
          </div>
        </div>

        {{-- Badge status --}}
        <span class="badge {{ $statusClass }} fs-6 px-3 py-2 text-capitalize">{{ $status }}</span>
      </div>
    </div>
  </div>

  <div class="row g-4">

    {{-- ── Kolom Kiri ─────────────────────────────────────────────────────── --}}
    <div class="col-lg-5">

      {{-- Card Informasi Pribadi --}}
      <div class="card mb-4">
        <div class="card-header border-bottom">
          <h6 class="card-title mb-0">
            <i class="ri ri-user-line me-2 text-primary"></i>Informasi Pribadi
          </h6>
        </div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <tbody>
              <tr>
                <td class="text-muted ps-3" width="40%">Nama Lengkap</td>
                <td class="fw-medium">{{ $siswa->nama_siswa }}</td>
              </tr>
              <tr>
                <td class="text-muted ps-3">Jenis Kelamin</td>
                <td>
                  @if ($siswa->jenis_kelamin === 'L')
                    <span class="badge bg-label-primary">Laki-laki</span>
                  @else
                    <span class="badge bg-label-danger">Perempuan</span>
                  @endif
                </td>
              </tr>
              <tr>
                <td class="text-muted ps-3">Tempat Lahir</td>
                <td>{{ $siswa->tempat_lahir ?? '-' }}</td>
              </tr>
              <tr>
                <td class="text-muted ps-3">Tanggal Lahir</td>
                <td>
                  {{ $siswa->tanggal_lahir ? \Carbon\Carbon::parse($siswa->tanggal_lahir)->translatedFormat('d F Y') : '-' }}
                </td>
              </tr>
              <tr>
                <td class="text-muted ps-3">Agama</td>
                <td>{{ $siswa->agama ?? '-' }}</td>
              </tr>
              <tr>
                <td class="text-muted ps-3">Kewarganegaraan</td>
                <td>{{ $siswa->kewarganegaraan ?? '-' }}</td>
              </tr>
              <tr>
                <td class="text-muted ps-3">Anak Ke</td>
                <td>{{ $siswa->anak_ke ?? '-' }}</td>
              </tr>
              <tr>
                <td class="text-muted ps-3">Jumlah Saudara</td>
                <td>{{ $siswa->jumlah_saudara ?? '-' }}</td>
              </tr>
              <tr>
                <td class="text-muted ps-3">Alamat</td>
                <td>{{ $siswa->alamat ?? '-' }}</td>
              </tr>
              <tr>
                <td class="text-muted ps-3">No. Telepon</td>
                <td>{{ $siswa->no_telepon ?? ($siswa->telepon ?? '-') }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      {{-- Card Data Orang Tua --}}
      <div class="card">
        <div class="card-header border-bottom">
          <h6 class="card-title mb-0">
            <i class="ri ri-parent-line me-2 text-primary"></i>Data Orang Tua / Wali
          </h6>
        </div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <tbody>
              <tr class="table-light">
                <td colspan="2" class="ps-3 fw-semibold small text-uppercase text-muted">Ayah</td>
              </tr>
              <tr>
                <td class="text-muted ps-3" width="40%">Nama</td>
                <td>{{ $siswa->nama_ayah ?? '-' }}</td>
              </tr>
              <tr>
                <td class="text-muted ps-3">Pekerjaan</td>
                <td>{{ $siswa->pekerjaan_ayah ?? '-' }}</td>
              </tr>
              <tr>
                <td class="text-muted ps-3">Penghasilan</td>
                <td>{{ $siswa->penghasilan_ayah ?? '-' }}</td>
              </tr>
              <tr class="table-light">
                <td colspan="2" class="ps-3 fw-semibold small text-uppercase text-muted">Ibu</td>
              </tr>
              <tr>
                <td class="text-muted ps-3">Nama</td>
                <td>{{ $siswa->nama_ibu ?? '-' }}</td>
              </tr>
              <tr>
                <td class="text-muted ps-3">Pekerjaan</td>
                <td>{{ $siswa->pekerjaan_ibu ?? '-' }}</td>
              </tr>
              <tr>
                <td class="text-muted ps-3">Penghasilan</td>
                <td>{{ $siswa->penghasilan_ibu ?? '-' }}</td>
              </tr>
              <tr class="table-light">
                <td colspan="2" class="ps-3 fw-semibold small text-uppercase text-muted">Wali</td>
              </tr>
              <tr>
                <td class="text-muted ps-3">Nama</td>
                <td>{{ $siswa->nama_wali ?? '-' }}</td>
              </tr>
              <tr>
                <td class="text-muted ps-3">Telepon</td>
                <td>{{ $siswa->telepon_wali ?? '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    {{-- ── Kolom Kanan ─────────────────────────────────────────────────────── --}}
    <div class="col-lg-7">

      {{-- Card Riwayat Registrasi --}}
      <div class="card mb-4">
        <div class="card-header border-bottom">
          <h6 class="card-title mb-0">
            <i class="ri ri-file-list-3-line me-2 text-primary"></i>Riwayat Registrasi
          </h6>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th class="text-center">#</th>
                <th>NIS / NISN</th>
                <th class="text-center">Tingkat</th>
                <th>Tahun Masuk</th>
                <th class="text-center">Status</th>
                <th>Tanggal Diterima</th>
              </tr>
            </thead>
            <tbody>
              @forelse($siswa->siswaRegistrasi->sortByDesc('created_at') as $i => $reg)
                @php
                  $regStatusClass = match ($reg->status) {
                      'aktif' => 'bg-label-success',
                      'alumni' => 'bg-label-info',
                      'keluar' => 'bg-label-danger',
                      default => 'bg-label-secondary',
                  };
                @endphp
                <tr>
                  <td class="text-center">{{ $loop->iteration }}</td>
                  <td><small>{{ $reg->nisn ?? '-' }} / {{ $reg->nis ?? '-' }}</small></td>
                  <td class="text-center">{{ $reg->tingkat }}</td>
                  <td>{{ $reg->tanggal_diterima ? \Carbon\Carbon::parse($reg->tanggal_diterima)->format('Y') : '-' }}</td>
                  <td class="text-center">
                    <span class="badge {{ $regStatusClass }} text-capitalize">{{ $reg->status }}</span>
                  </td>
                  <td><small class="text-muted">{{ $reg->tanggal_diterima ? \Carbon\Carbon::parse($reg->tanggal_diterima)->format('d/m/Y') : '-' }}</small></td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="text-center py-3 text-muted">
                    <i class="ri ri-inbox-line me-1"></i> Belum ada data registrasi.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        @if ($siswa->siswaRegistrasi->count() > 1)
          <div class="card-footer py-2">
            <small class="text-muted">
              <i class="ri ri-information-line me-1"></i>
              Baris aktif (diarsir) adalah registrasi terbaru.
            </small>
          </div>
        @endif
      </div>

      {{-- Card Riwayat Rombel (AJAX) --}}
      <div class="card">
        <div class="card-header border-bottom">
          <h6 class="card-title mb-0">
            <i class="ri ri-group-line me-2 text-primary"></i>Riwayat Rombel
          </h6>
        </div>
        <div class="table-responsive">
          <table class="table table-sm mb-0" id="tabel-riwayat-rombel">
            <thead>
              <tr>
                <th class="text-center">#</th>
                <th>Rombel</th>
                <th class="text-center">Tingkat</th>
                <th>Semester</th>
                <th>Masuk</th>
                <th>Keluar</th>
                <th class="text-center">Status</th>
              </tr>
            </thead>
            <tbody id="tbody-riwayat-rombel">
              <tr>
                <td colspan="7" class="text-center py-3 text-muted">
                  <span class="spinner-border spinner-border-sm me-2"></span> Memuat riwayat...
                </td>
              </tr>
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

      // ── Load Riwayat Rombel via AJAX ──────────────────────────────────────────
      const siswaId = {{ $siswa->id }};

      fetch(`/anggota-rombel/${siswaId}/riwayat`, {
          headers: {
            Accept: 'application/json'
          }
        })
        .then(r => r.json())
        .then(data => {
          const tbody = document.getElementById('tbody-riwayat-rombel');
          const riwayat = data.riwayat || [];

          if (!riwayat.length) {
            tbody.innerHTML = `
              <tr>
                <td colspan="7" class="text-center py-3 text-muted">
                  <i class="ri ri-inbox-line me-1"></i> Belum pernah terdaftar di rombel manapun.
                </td>
              </tr>`;
            return;
          }

          tbody.innerHTML = riwayat.map((item, i) => {
            const isAktif = item.status === 'Aktif';
            const badgeClass = isAktif ? 'bg-label-success' : 'bg-label-secondary';
            const masuk = item.start_date ?
              new Date(item.start_date).toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
              }) :
              '-';
            const keluar = item.end_date ?
              new Date(item.end_date).toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
              }) :
              '-';

            return `
              <tr>
                <td class="text-center">${i + 1}</td>
                <td class="fw-medium">${item.rombel}</td>
                <td class="text-center">${item.tingkat}</td>
                <td><small>${item.semester}</small></td>
                <td><small>${masuk}</small></td>
                <td><small>${keluar}</small></td>
                <td class="text-center">
                  <span class="badge ${badgeClass}">${item.status}</span>
                </td>
              </tr>`;
          }).join('');
        })
        .catch(err => {
          document.getElementById('tbody-riwayat-rombel').innerHTML = `
            <tr>
              <td colspan="7" class="text-center py-3 text-danger">
                <i class="ri ri-error-warning-line me-1"></i> Gagal memuat riwayat rombel.
              </td>
            </tr>`;
        });

    });
  </script>
@endsection
