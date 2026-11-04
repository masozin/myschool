@extends('layouts/layoutMaster')

@section('title', 'Input Nilai Ijazah - ' . $ijazah->siswa->nama_siswa)

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')

  <div class="mb-4">
    <a href="{{ route('ijazah.show', $ijazah) }}" class="btn btn-sm btn-outline-secondary">
      <i class="icon-base ri ri-arrow-left-line me-1"></i>Kembali ke Detail
    </a>
  </div>

  @php
    $isLocked = !in_array($ijazah->status, ['draft', 'ditolak']);
    $siswa = $ijazah->siswa;
  @endphp

  {{-- Banner terkunci --}}
  @if ($isLocked)
    <div class="alert alert-info d-flex align-items-center gap-3 mb-4">
      <i class="icon-base ri ri-lock-line ri-24px flex-shrink-0"></i>
      <div>
        <strong>Nilai tidak dapat diubah.</strong>
        Status ijazah saat ini: <strong>{{ ucfirst($ijazah->status) }}</strong>.
      </div>
    </div>
  @endif

  {{-- Banner catatan penolakan --}}
  @if ($ijazah->isDitolak() && $ijazah->catatan)
    <div class="alert alert-danger d-flex align-items-center gap-3 mb-4">
      <i class="icon-base ri ri-error-warning-line ri-24px flex-shrink-0"></i>
      <div>
        <strong>Ditolak:</strong> {{ $ijazah->catatan }}
      </div>
    </div>
  @endif

  <div class="row g-4">

    {{-- ── Kolom Kiri: Identitas ────────────────────────────────────────────── --}}
    <div class="col-12 col-lg-4">
      <div class="card mb-4">
        <div class="card-header border-bottom">
          <h6 class="mb-0">Identitas Siswa</h6>
        </div>
        <div class="card-body">
          <table class="table table-sm table-borderless mb-0">
            <tr>
              <td class="text-muted" width="110">Nama</td>
              <td><strong>{{ $siswa->nama_siswa }}</strong></td>
            </tr>
            <tr>
              <td class="text-muted">NISN</td>
              <td>{{ $siswa->nisn }}</td>
            </tr>
            <tr>
              <td class="text-muted">NIS</td>
              <td>{{ $ijazah->nis ?? '-' }}</td>
            </tr>
            <tr>
              <td class="text-muted">Tempat Lahir</td>
              <td>{{ $siswa->tempat_lahir }}</td>
            </tr>
            <tr>
              <td class="text-muted">Tanggal Lahir</td>
              <td>{{ $siswa->tgl_lahir->translatedFormat('d F Y') }}</td>
            </tr>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-header border-bottom">
          <h6 class="mb-0">Info Ijazah</h6>
        </div>
        <div class="card-body">
          <table class="table table-sm table-borderless mb-0">
            <tr>
              <td class="text-muted" width="110">Mode</td>
              <td>
                @if ($ijazah->mode_input === 'jadi')
                  <span class="badge bg-label-info">Nilai Jadi</span>
                @else
                  <span class="badge bg-label-warning">Manual Berbobot</span>
                @endif
              </td>
            </tr>
            <tr>
              <td class="text-muted">Status</td>
              <td>
                @php
                  $statusMap = [
                      'draft' => 'secondary',
                      'pengajuan' => 'warning',
                      'disetujui' => 'success',
                      'ditolak' => 'danger',
                  ];
                @endphp
                <span class="badge bg-label-{{ $statusMap[$ijazah->status] ?? 'secondary' }}">
                  {{ ucfirst($ijazah->status) }}
                </span>
              </td>
            </tr>
            <tr>
              <td class="text-muted">Rata-rata</td>
              <td><strong id="sidebar-rata">{{ number_format($ijazah->nilaiRataRata(), 2) }}</strong></td>
            </tr>
          </table>

          {{-- Info komponen (mode manual) --}}
          @if ($ijazah->mode_input === 'manual')
            <hr>
            <h6 class="small fw-semibold text-muted mb-2">KOMPONEN NILAI</h6>
            @php
              $komponens = $ijazah->nilais->first()?->komponens ?? collect();
            @endphp
            @foreach ($komponens as $kn)
              <div class="d-flex justify-content-between align-items-center mb-1">
                <small class="text-muted">{{ $kn->komponen->label }}</small>
                <span class="badge bg-label-primary">{{ $kn->komponen->bobot }}%</span>
              </div>
            @endforeach
            <div class="alert alert-info py-2 mt-2 mb-0 small">
              <i class="icon-base ri ri-information-line me-1"></i>
              Nilai akhir dihitung otomatis saat simpan.
            </div>
          @endif
        </div>
      </div>
    </div>

    {{-- ── Kolom Kanan: Tabel Nilai ─────────────────────────────────────────── --}}
    <div class="col-12 col-lg-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h6 class="mb-0">Input Nilai Ijazah</h6>
          <small class="text-muted">
            Nilai rata-rata: <strong id="nilai-rata-rata">{{ number_format($ijazah->nilaiRataRata(), 2) }}</strong>
          </small>
        </div>

        <div class="card-body p-0">
          <form id="form-nilai" method="POST" action="{{ route('ijazah.nilai.update', $ijazah) }}">
            @csrf
            @method('PUT')

            @php
              // $nilais sudah dikirim dari controller via nilaisLengkap()
              // Group per kelompok mapel di sini
              $nilaisGrouped = $nilais->groupBy(
                  fn($n) => $n->mapelMapping?->masterMapel?->kelompok?->nama ?? 'Lainnya',
              );
            @endphp

            {{-- ── MODE: NILAI JADI ──────────────────────────────────────── --}}
            @if ($ijazah->mode_input === 'jadi')
              <table class="table table-sm table-bordered mb-0">
                <thead>
                  <tr>
                    <th width="50" class="text-center">No</th>
                    <th>Mata Pelajaran</th>
                    <th width="150" class="text-center">Nilai (0–100)</th>
                  </tr>
                </thead>
                <tbody>
                  @php $no = 1; @endphp
                  @foreach ($nilaisGrouped as $namaKelompok => $items)
                    <tr class="table-light">
                      <td colspan="3" class="fw-bold py-2 px-3 small text-uppercase text-muted">
                        {{ $namaKelompok }}
                      </td>
                    </tr>
                    @foreach ($items as $ijazahNilai)
                      <tr>
                        <td class="text-center">{{ $no++ }}</td>
                        <td>
                          {{ $ijazahNilai->nama_mapel }}
                          @if ($ijazahNilai->nama_sub_mapel)
                            <br><small class="text-muted ps-2">{{ $ijazahNilai->nama_sub_mapel }}</small>
                          @endif
                          @if ($ijazahNilai->mapelMapping?->jenis === 'tambahan')
                            <span class="badge bg-label-info ms-1" style="font-size:9px">Tambahan</span>
                          @endif
                        </td>
                        <td class="p-1">
                          <input type="number" class="form-control form-control-sm text-center input-nilai"
                            name="nilai[{{ $ijazahNilai->id }}]" min="0" max="100" step="0.01"
                            value="{{ number_format($ijazahNilai->nilai_akhir ?? 0, 2, '.', '') }}"
                            data-id="{{ $ijazahNilai->id }}" {{ $isLocked ? 'disabled' : '' }}>
                        </td>
                      </tr>
                    @endforeach
                  @endforeach
                </tbody>
              </table>

              {{-- ── MODE: MANUAL BERBOBOT ─────────────────────────────────── --}}
            @else
              @php
                // Ambil header komponen dari baris pertama
                $firstNilai = $ijazah->nilais->first();
                $komponenHeaders = $firstNilai
                    ? $firstNilai->komponens->map(fn($kn) => $kn->komponen)->values()
                    : collect();
              @endphp
              <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                  <thead>
                    <tr>
                      <th width="40" class="text-center">No</th>
                      <th>Mata Pelajaran</th>
                      @foreach ($komponenHeaders as $komp)
                        <th class="text-center" width="110" title="{{ $komp->label }}">
                          <div>{{ Str::limit($komp->label, 15) }}</div>
                          <small class="text-muted fw-normal">{{ $komp->bobot }}%</small>
                        </th>
                      @endforeach
                      <th class="text-center" width="90">Nilai Akhir</th>
                    </tr>
                  </thead>
                  <tbody>
                    @php $no = 1; @endphp
                    @foreach ($nilaisGrouped as $namaKelompok => $items)
                      <tr class="table-light">
                        <td colspan="{{ 3 + $komponenHeaders->count() }}"
                          class="fw-bold py-2 px-3 small text-uppercase text-muted">
                          {{ $namaKelompok }}
                        </td>
                      </tr>
                      @foreach ($items as $ijazahNilai)
                        @php
                          // Index komponen by komponen_id untuk lookup mudah
                          $knMap = $ijazahNilai->komponens->keyBy('komponen_id');
                        @endphp
                        <tr>
                          <td class="text-center">{{ $no++ }}</td>
                          <td>
                            {{ $ijazahNilai->nama_mapel }}
                            @if ($ijazahNilai->nama_sub_mapel)
                              <br><small class="text-muted ps-2">{{ $ijazahNilai->nama_sub_mapel }}</small>
                            @endif
                          </td>
                          @foreach ($komponenHeaders as $komp)
                            @php $kn = $knMap[$komp->id] ?? null; @endphp
                            <td class="p-1">
                              <input type="number" class="form-control form-control-sm text-center input-komponen"
                                name="nilai[{{ $ijazahNilai->id }}][{{ $komp->id }}]" min="0" max="100"
                                step="0.01" value="{{ number_format($kn?->nilai ?? 0, 2, '.', '') }}"
                                data-nilai-id="{{ $ijazahNilai->id }}" data-bobot="{{ $komp->bobot }}"
                                {{ $isLocked ? 'disabled' : '' }}>
                            </td>
                          @endforeach
                          <td class="text-center fw-semibold" id="hasil-{{ $ijazahNilai->id }}">
                            {{ number_format($ijazahNilai->nilai_akhir ?? 0, 2) }}
                          </td>
                        </tr>
                      @endforeach
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif

          </form>
        </div>

        {{-- Footer Aksi --}}
        <div class="card-footer d-flex justify-content-between align-items-center">
          <div>
            <a href="{{ route('ijazah.show', $ijazah) }}" class="btn btn-outline-secondary btn-sm">
              <i class="icon-base ri ri-eye-line me-1"></i>Lihat Detail
            </a>
          </div>
          @if (!$isLocked)
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-outline-primary btn-sm" id="btn-simpan">
                <i class="icon-base ri ri-save-line me-1"></i>
                <span class="d-none d-md-inline">Simpan</span>
              </button>
            </div>
          @endif
        </div>

      </div>
    </div>
  </div>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const isManual = {{ $ijazah->mode_input === 'manual' ? 'true' : 'false' }};

      // ── Kalkulasi realtime untuk mode manual ────────────────────────────────
      function hitungBaris(nilaiId) {
        const inputs = document.querySelectorAll(`.input-komponen[data-nilai-id="${nilaiId}"]`);
        let total = 0;
        inputs.forEach(input => {
          const val = parseFloat(input.value) || 0;
          const bobot = parseFloat(input.dataset.bobot) || 0;
          total += val * bobot / 100;
        });
        const hasilEl = document.getElementById(`hasil-${nilaiId}`);
        if (hasilEl) hasilEl.textContent = total.toFixed(2);
        return total;
      }

      // ── Rata-rata semua baris ────────────────────────────────────────────────
      function hitungRataRata() {
        let total = 0,
          count = 0;

        if (isManual) {
          // Kumpulkan semua hasil per baris
          document.querySelectorAll('[id^="hasil-"]').forEach(el => {
            const val = parseFloat(el.textContent) || 0;
            total += val;
            count++;
          });
        } else {
          document.querySelectorAll('.input-nilai').forEach(input => {
            const val = parseFloat(input.value) || 0;
            if (val > 0) {
              total += val;
              count++;
            }
          });
        }

        const rata = count > 0 ? (total / count).toFixed(2) : '0.00';
        document.getElementById('nilai-rata-rata').textContent = rata;
        document.getElementById('sidebar-rata').textContent = rata;
      }

      if (isManual) {
        document.querySelectorAll('.input-komponen').forEach(input => {
          // Hitung saat load
          hitungBaris(input.dataset.nilaiId);

          input.addEventListener('input', function() {
            hitungBaris(this.dataset.nilaiId);
            hitungRataRata();
          });
        });
      } else {
        document.querySelectorAll('.input-nilai').forEach(i =>
          i.addEventListener('input', hitungRataRata)
        );
      }

      hitungRataRata();

      // ── Simpan ───────────────────────────────────────────────────────────────
      document.getElementById('btn-simpan')?.addEventListener('click', async function() {
        const btn = this;
        const oriTxt = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

        // Kumpulkan data dari form
        const formData = new FormData(document.getElementById('form-nilai'));

        try {
          const res = await fetch(document.getElementById('form-nilai').action, {
            method: 'POST', // PUT di-spoof via _method di FormData
            headers: {
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json',
            },
            body: formData,
          });
          const data = await res.json();

          if (res.ok) {
            Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              text: data.message ?? 'Nilai berhasil disimpan.',
              timer: 2000,
              showConfirmButton: false,
            });
            setTimeout(() => location.reload(), 2100);
          } else {
            throw new Error(data.message ?? JSON.stringify(data.errors ?? 'Gagal menyimpan.'));
          }
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: err.message
          });
        } finally {
          btn.disabled = false;
          btn.innerHTML = oriTxt;
        }
      });

    });
  </script>
@endsection
