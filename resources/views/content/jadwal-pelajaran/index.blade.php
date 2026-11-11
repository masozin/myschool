@extends('layouts/layoutMaster')

@section('title', 'Jadwal Pelajaran')

@section('content')

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  {{-- Pemilih konteks sekolah untuk Admin --}}
  @hasanyrole('Admin')
    <div class="card mb-4">
      <div class="card-body py-3 d-flex align-items-center gap-3 flex-wrap">
        <span class="text-muted"><i class="icon-base ri ri-school-line me-1"></i>Anda masuk sebagai Admin — konteks
          sekolah:</span>
        <form method="GET" action="{{ url('/jadwal-pelajaran') }}">
          <select name="sekolah_id" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach ($daftarSekolah as $ds)
              <option value="{{ $ds->id }}" {{ $ds->id === $sekolahId ? 'selected' : '' }}>
                {{ $ds->detailTerbaru->nama_sekolah ?? 'Sekolah #' . $ds->id }}</option>
            @endforeach
          </select>
        </form>
      </div>
    </div>
  @endhasanyrole

  @if (!$sekolahId)
    <div class="alert alert-warning">Pilih sekolah dulu lewat pemilih di atas — daftar rombel mengikuti sekolah yang
      dipilih.</div>
  @else
    <div class="card mb-4">
      <div class="card-body py-4 d-flex align-items-center gap-4 flex-wrap">
        <div class="avatar avatar-xl flex-shrink-0">
          <span class="avatar-initial rounded-circle bg-label-primary">
            <i class="icon-base ri ri-calendar-schedule-line ri-28px"></i>
          </span>
        </div>
        <div class="flex-grow-1">
          <h4 class="mb-1">Jadwal Pelajaran</h4>
          <div class="text-muted small">
            {{ $sekolah->detailTerbaru->nama_sekolah ?? '-' }}
            @if ($semester)
              <span class="mx-1">•</span> Semester {{ $semester->semester }} {{ $semester->tahun_pelajaran }}
            @endif
          </div>
        </div>
        @if ($bolehSusun)
          <button type="button" class="btn btn-outline-success" data-bs-toggle="modal"
            data-bs-target="#modal-impor-jadwal">
            <i class="ri ri-upload-2-line me-1"></i> Impor Jadwal
          </button>
          <a href="{{ route('jadwal-pelajaran.pengaturan') }}" class="btn btn-outline-primary">
            <i class="ri ri-time-line me-1"></i> Model Jadwal
          </a>
        @endif
      </div>
    </div>

    @if (!$pengaturanAda)
      <div class="alert alert-info">
        Model jadwal belum dibuat untuk semester ini — jadwal memakai pola bawaan (Senin–Kamis 8 jam, Jumat 6, Sabtu 7,
        mulai 07.00, 35 menit per jam).
        @if ($bolehSusun)
          Buat lewat tombol <strong>Model Jadwal</strong> di atas bila pola sekolah berbeda, atau bila kelas bawah dan
          kelas atas beda jamnya.
        @endif
      </div>
    @endif

    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th>Rombel</th>
              <th>Wali Kelas</th>
              <th class="text-center">Pengampu</th>
              <th class="text-center">Kotak Terisi</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($rombels as $r)
              <tr>
                <td class="fw-semibold">{{ $r->nama_rombel }}
                  <div class="text-muted fw-normal" style="font-size: 11px;">
                    Model: {{ $r->jadwalPengaturan?->nama ?? 'Belum dipilih' }}</div>
                </td>
                <td>{{ $r->ptk->nama_ptk ?? '-' }}</td>
                <td class="text-center">{{ $pengampuPerRombel[$r->id] ?? 0 }} mapel</td>
                <td class="text-center">{{ $jadwalPerRombel[$r->id] ?? 0 }} /
                  {{ $modelPerRombel[$r->id]['total'] ?? $totalKotak }} kotak</td>
                <td class="text-end">
                  @if ($bolehSusun && $daftarModel->isNotEmpty())
                    <form method="POST" action="{{ route('jadwal-pelajaran.rombel.model', $r) }}"
                      class="d-inline-block me-1">
                      @csrf
                      <select name="pengaturan_id" class="form-select form-select-sm d-inline-block w-auto"
                        onchange="this.form.submit()" title="Pilih model jadwal rombel ini">
                        <option value="" disabled {{ !$r->jadwal_pengaturan_id ? 'selected' : '' }}>Pilih Model…
                        </option>
                        @foreach ($daftarModel as $m)
                          <option value="{{ $m->id }}" {{ $r->jadwal_pengaturan_id === $m->id ? 'selected' : '' }}>
                            {{ $m->nama }}</option>
                        @endforeach
                      </select>
                    </form>
                  @endif
                  @if ($bolehSusun)
                    <a href="{{ route('jadwal-pelajaran.pengampu', $r) }}"
                      class="btn btn-sm btn-outline-secondary me-1">
                      <i class="ri ri-user-star-line me-1"></i>Pengampu
                    </a>
                  @endif
                  <a href="{{ route('jadwal-pelajaran.editor', $r) }}" class="btn btn-sm btn-primary">
                    <i class="ri ri-calendar-2-line me-1"></i>{{ $bolehSusun ? 'Editor' : 'Lihat Jadwal' }}
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                  <i class="ri ri-inbox-line me-1"></i> Belum ada rombel di sekolah ini pada semester yang sedang
                  dibuka.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  @endif

  @if ($bolehSusun)
    <div class="modal fade" id="modal-impor-jadwal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Impor Jadwal dari Excel</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            @if (session('impor_errors'))
              <div class="alert alert-danger">
                <div class="fw-semibold mb-1">{{ session('error') ?? 'Impor dibatalkan — tidak ada data yang ditulis.' }}
                </div>
                <ul class="mb-0">
                  @foreach (session('impor_errors') as $err)
                    <li>{{ $err }}</li>
                  @endforeach
                </ul>
              </div>
            @endif
            @if ($daftarModel->isEmpty())
              <div class="alert alert-warning mb-0">Belum ada model jadwal di semester ini — buat dulu lewat tombol
                <strong>Model Jadwal</strong>, lalu pilih modelnya untuk rombel dari daftar atau editor.</div>
            @else
              <div class="mb-3">
                <label class="form-label" for="impor-model">Model Jadwal</label>
                <select id="impor-model" class="form-select">
                  @foreach ($daftarModel as $m)
                    <option value="{{ $m->id }}" {{ session('impor_model') == $m->id ? 'selected' : '' }}>
                      {{ $m->nama }}</option>
                  @endforeach
                </select>
              </div>
              <div class="mb-4">
                <a href="#" id="impor-template" class="btn btn-outline-success w-100">
                  <i class="ri ri-file-excel-2-line me-1"></i> Unduh Template
                </a>
                <small class="text-muted">Template berisi rombel-rombel yang sudah memilih model ini. Isi kode
                  mapelnya, lalu unggah kembali berkasnya di bawah.</small>
              </div>
              <form method="POST" id="impor-form" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                  <label class="form-label" for="impor-file">Berkas hasil isian (.xlsx)</label>
                  <input type="file" id="impor-file" name="file" class="form-control" accept=".xlsx" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">
                  <i class="ri ri-upload-2-line me-1"></i> Unggah &amp; Impor
                </button>
              </form>
            @endif
          </div>
        </div>
      </div>
    </div>
  @endif

@endsection

@section('page-script')
  <script>
    (function() {
      const sel = document.getElementById('impor-model');
      if (sel) {
        const link = document.getElementById('impor-template');
        const form = document.getElementById('impor-form');
        const perbarui = () => {
          const dasar = '{{ url('/jadwal-pelajaran/pengaturan') }}/' + sel.value;
          link.href = dasar + '/template-impor';
          form.action = dasar + '/impor';
        };
        sel.addEventListener('change', perbarui);
        perbarui();
      }
      @if (session('impor_errors'))
        const modalEl = document.getElementById('modal-impor-jadwal');
        if (modalEl) {
          new bootstrap.Modal(modalEl).show();
        }
      @endif
    })();
  </script>
@endsection
