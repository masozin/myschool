@extends('layouts/layoutMaster')

@section('title', 'Surat Keluar')

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
        <form method="GET" action="{{ url('/surat-keluar') }}">
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
    <div class="alert alert-warning">Pilih sekolah dulu lewat pemilih di atas — agenda surat mengikuti sekolah yang
      dipilih.</div>
  @else
    {{-- Kepala halaman: identitas + statistik ringkas + aksi --}}
    <div class="card mb-4">
      <div class="card-body py-4 d-flex align-items-center gap-4 flex-wrap">
        <div class="avatar avatar-xl flex-shrink-0">
          <span class="avatar-initial rounded-circle bg-label-primary">
            <i class="icon-base ri ri-mail-send-line ri-28px"></i>
          </span>
        </div>
        <div class="flex-grow-1">
          <h5 class="mb-1">Buku Agenda Surat Keluar</h5>
          <p class="mb-0 text-muted">{{ $sekolah->detailTerbaru->nama_sekolah ?? 'Sekolah #' . $sekolah->id }} —
            {{ $statistik['total'] }} surat tahun {{ now()->format('Y') }} &middot; {{ $statistik['terbit'] }} terbit
            &middot; {{ $statistik['draft'] }} draft &middot; {{ $statistik['batal'] }} dibatalkan</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <a href="{{ route('surat-keluar.ekspor.excel', request()->only(['tahun', 'status', 'kategori_id'])) }}"
            class="btn btn-outline-success"><i class="icon-base ri ri-file-excel-2-line me-1"></i>Ekspor Excel</a>
          <a href="{{ route('surat-keluar.cetak.agenda', request()->only(['tahun', 'status', 'kategori_id'])) }}"
            class="btn btn-outline-danger"><i class="icon-base ri ri-file-pdf-2-line me-1"></i>Cetak Agenda</a>
          @if ($bolehKelola)
            <a href="{{ route('surat-keluar.pengaturan') }}" class="btn btn-outline-secondary">
              <i class="icon-base ri ri-settings-3-line me-1"></i>Pengaturan Nomor</a>
            <a href="{{ route('surat-keluar.create') }}" class="btn btn-primary">
              <i class="icon-base ri ri-add-line me-1"></i>Buat Surat</a>
          @endif
        </div>
      </div>
    </div>

    {{-- Saringan --}}
    <div class="card mb-4">
      <div class="card-body py-3">
        <form method="GET" action="{{ url('/surat-keluar') }}" class="row g-3 align-items-end">
          <div class="col-6 col-md-2">
            <label class="form-label">Tahun</label>
            <select name="tahun" class="form-select">
              <option value="">Semua tahun</option>
              @foreach ($daftarTahun as $th)
                <option value="{{ $th }}" {{ (int) request('tahun') === $th ? 'selected' : '' }}>{{ $th }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="">Semua status</option>
              <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
              <option value="terbit" {{ request('status') === 'terbit' ? 'selected' : '' }}>Terbit</option>
              <option value="batal" {{ request('status') === 'batal' ? 'selected' : '' }}>Dibatalkan</option>
            </select>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Kategori</label>
            <select name="kategori_id" class="form-select">
              <option value="">Semua kategori</option>
              @foreach ($kategoris as $k)
                <option value="{{ $k->id }}" {{ (int) request('kategori_id') === $k->id ? 'selected' : '' }}>
                  {{ $k->kode }} — {{ $k->nama }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="icon-base ri ri-filter-3-line me-1"></i>Saring</button>
            <a href="{{ url('/surat-keluar') }}" class="btn btn-outline-secondary">Reset</a>
          </div>
        </form>
      </div>
    </div>

    {{-- Tabel agenda --}}
    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th style="width:110px">Tanggal</th>
              <th>Nomor Surat</th>
              <th>Tujuan</th>
              <th>Perihal</th>
              <th>Kategori</th>
              <th>Status</th>
              <th class="text-center" style="width:70px">Berkas</th>
              <th class="text-end" style="width:190px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($surats as $s)
              <tr>
                <td class="text-nowrap">{{ $s->tanggal_surat?->format('d-m-Y') }}</td>
                <td>
                  @if ($s->nomor_surat)
                    <span class="fw-medium">{{ $s->nomor_surat }}</span>
                    @if ($s->nomor_manual)
                      <span class="badge bg-label-warning ms-1">manual</span>
                    @endif
                  @else
                    <span class="text-muted">— belum bernomor —</span>
                  @endif
                </td>
                <td>{{ $s->tujuan }}</td>
                <td>{{ $s->perihal }}</td>
                <td>{{ $s->kategori ? $s->kategori->kode . ' — ' . $s->kategori->nama : '-' }}</td>
                <td>
                  @if ($s->status === 'terbit')
                    <span class="badge bg-label-success">Terbit</span>
                  @elseif ($s->status === 'batal')
                    <span class="badge bg-label-danger">Dibatalkan</span>
                  @else
                    <span class="badge bg-label-secondary">Draft</span>
                  @endif
                  @if ($s->sifat !== 'Biasa')
                    <span class="badge bg-label-info ms-1">{{ $s->sifat }}</span>
                  @endif
                </td>
                <td class="text-center">
                  @if ($s->berkas->count())
                    <span class="badge bg-label-primary"><i class="icon-base ri ri-attachment-2 me-1"></i>{{ $s->berkas->count() }}</span>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
                <td class="text-end text-nowrap">
                  <a href="{{ route('surat-keluar.edit', $s) }}" class="btn btn-sm btn-outline-primary">
                    <i class="icon-base ri ri-eye-line"></i> {{ $bolehKelola && $s->status !== 'batal' ? 'Ubah' : 'Lihat' }}</a>
                  @if ($bolehKelola && $s->status === 'draft')
                    <form method="POST" action="{{ route('surat-keluar.terbitkan', $s) }}" class="d-inline"
                      onsubmit="return confirm('Terbitkan surat ini? Nomor resmi akan ditetapkan dan dibekukan.')">
                      @csrf
                      <button type="submit" class="btn btn-sm btn-success">
                        <i class="icon-base ri ri-send-plane-line"></i> Terbitkan</button>
                    </form>
                    <form method="POST" action="{{ route('surat-keluar.hapus', $s) }}" class="d-inline"
                      onsubmit="return confirm('Hapus draft surat ini?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="icon-base ri ri-delete-bin-line"></i></button>
                    </form>
                  @endif
                  @if ($bolehKelola && $s->status === 'terbit')
                    <button type="button" class="btn btn-sm btn-outline-danger btn-batalkan"
                      data-url="{{ route('surat-keluar.batalkan', $s) }}"
                      data-nomor="{{ $s->nomor_surat }}">
                      <i class="icon-base ri ri-close-circle-line"></i> Batalkan</button>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-muted py-4">Belum ada surat pada saringan ini.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Modal batalkan --}}
    <div class="modal fade" id="modalBatalkan" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <form method="POST" id="formBatalkan" class="modal-content">
          @csrf
          <div class="modal-header">
            <h5 class="modal-title">Batalkan Surat</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <p class="mb-2">Surat <strong id="labelNomorBatal"></strong> akan ditandai <strong>dibatalkan</strong>.
              Nomornya tetap tercatat di agenda dan <strong>tidak dipakai ulang</strong>.</p>
            <label class="form-label">Alasan pembatalan <span class="text-danger">*</span></label>
            <textarea name="alasan_batal" class="form-control" rows="3" required
              placeholder="Mis. salah perihal, diganti surat baru"></textarea>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Kembali</button>
            <button type="submit" class="btn btn-danger">Ya, Batalkan Surat</button>
          </div>
        </form>
      </div>
    </div>
  @endif

@endsection

@section('page-script')
  <script>
    (function() {
      const modalEl = document.getElementById('modalBatalkan');
      if (!modalEl) return;
      const form = document.getElementById('formBatalkan');
      const label = document.getElementById('labelNomorBatal');
      const modal = new bootstrap.Modal(modalEl);
      document.querySelectorAll('.btn-batalkan').forEach(function(btn) {
        btn.addEventListener('click', function() {
          form.action = btn.dataset.url;
          label.textContent = btn.dataset.nomor || '';
          modal.show();
        });
      });
    })();
  </script>
@endsection
