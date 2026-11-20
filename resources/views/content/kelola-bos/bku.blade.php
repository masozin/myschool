@extends('layouts/layoutMaster')

@section('title', 'BKU BOS')

@section('content')

  {{-- Pemilih konteks sekolah untuk Admin --}}
  @hasanyrole('Admin')
    <div class="card mb-4">
      <div class="card-body py-3 d-flex align-items-center gap-3 flex-wrap">
        <span class="text-muted"><i class="icon-base ri ri-school-line me-1"></i>Anda masuk sebagai Admin — konteks
          sekolah:</span>
        <form method="GET" action="{{ url('/kelola-bos/bku') }}">
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

  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  @endif

  @if (!$sekolahId)
    <div class="alert alert-warning">Pilih sekolah dulu lewat pemilih di atas — data BOS mengikuti sekolah yang
      dipilih.</div>
  @else
    {{-- Kepala halaman --}}
    <div class="card mb-4">
      <div class="card-body py-4 d-flex align-items-center gap-4 flex-wrap">
        <div class="avatar avatar-xl flex-shrink-0">
          <span class="avatar-initial rounded-circle bg-label-primary">
            <i class="icon-base ri ri-book-2-line ri-28px"></i>
          </span>
        </div>
        <div class="flex-grow-1">
          <h5 class="mb-1">Buku Kas Umum (BKU) BOS</h5>
          <p class="mb-0 text-muted">{{ $sekolah->detailTerbaru->nama_sekolah ?? 'Sekolah #' . $sekolah->id }}
            &middot; cermin data ARKAS (hanya baca)
            @if ($sinkronTerakhir)
              &middot; Data per sinkron terakhir: {{ $sinkronTerakhir->created_at->format('d-m-Y H:i') }}
            @else
              &middot; <span class="text-warning">Belum pernah disinkronkan dari ARKAS</span>
            @endif
          </p>
        </div>
        <div class="text-end">
          <div class="text-muted small">Total nominal sesuai saringan</div>
          <h5 class="mb-0">Rp {{ number_format($totalNominal, 0, ',', '.') }}</h5>
        </div>
      </div>
    </div>

    {{-- Saringan --}}
    <div class="card mb-4">
      <div class="card-body py-3">
        <form method="GET" action="{{ url('/kelola-bos/bku') }}" class="row g-3 align-items-end">
          <div class="col-6 col-md-2">
            <label class="form-label">Tahun</label>
            <select name="tahun" class="form-select">
              <option value="">Semua tahun</option>
              @foreach ($daftarTahun as $th)
                <option value="{{ $th }}" {{ (int) request('tahun') === $th ? 'selected' : '' }}>{{ $th }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label">Bulan</label>
            <select name="bulan" class="form-select">
              <option value="">Semua bulan</option>
              @foreach (['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'] as $i => $namaBulan)
                <option value="{{ $i + 1 }}" {{ (int) request('bulan') === $i + 1 ? 'selected' : '' }}>{{ $namaBulan }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label">Jenis BKU</label>
            <select name="jenis" class="form-select">
              <option value="">Semua jenis</option>
              @foreach ($daftarJenis as $j)
                <option value="{{ $j->id_ref_bku }}" {{ (int) request('jenis') === (int) $j->id_ref_bku ? 'selected' : '' }}>
                  {{ $j->nama_bku }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-3">
            <label class="form-label">Rekening</label>
            <select name="rekening" class="form-select">
              <option value="">Semua rekening</option>
              @foreach ($daftarRekening as $r)
                <option value="{{ $r->kode_rekening }}" {{ request('rekening') === $r->kode_rekening ? 'selected' : '' }}>
                  {{ $r->kode_rekening }} — {{ $r->nama_rekening }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Pencarian</label>
            <input type="text" name="cari" class="form-control" value="{{ request('cari') }}"
              placeholder="Uraian, no bukti, nama toko…">
          </div>
          <div class="col-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="icon-base ri ri-filter-3-line me-1"></i>Saring</button>
            <a href="{{ url('/kelola-bos/bku') }}" class="btn btn-outline-secondary">Atur Ulang</a>
          </div>
        </form>
      </div>
    </div>

    {{-- Tabel --}}
    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              @php
                $kepala = function ($kunci, $label) use ($urut, $arah) {
                  $dirBaru = $urut === $kunci && $arah === 'asc' ? 'desc' : 'asc';
                  $panah = $urut === $kunci ? ($arah === 'asc' ? ' ↑' : ' ↓') : '';
                  $url = url('/kelola-bos/bku') . '?' . http_build_query(request()->except(['page', 'urut', 'dir']) + ['urut' => $kunci, 'dir' => $dirBaru]);
                  return '<a href="' . e($url) . '" class="text-reset text-decoration-none">' . e($label) . $panah . '</a>';
                };
              @endphp
              <th>{!! $kepala('tanggal', 'Tanggal') !!}</th>
              <th>{!! $kepala('bukti', 'No Bukti') !!}</th>
              <th>Jenis</th>
              <th>{!! $kepala('rekening', 'Rekening') !!}</th>
              <th>{!! $kepala('uraian', 'Uraian') !!}</th>
              <th>Toko / Penerima</th>
              <th>Pajak</th>
              <th class="text-end">{!! $kepala('nominal', 'Nominal') !!}</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($baris as $b)
              <tr>
                <td class="text-nowrap">{{ $b->tanggal_transaksi->format('d-m-Y') }}</td>
                <td>{{ $b->no_bukti ?? '-' }}</td>
                <td><span class="badge bg-label-secondary">{{ $b->nama_bku ?? 'BKU #' . $b->id_ref_bku }}</span></td>
                <td>
                  @if ($b->kode_rekening)
                    <span title="{{ $b->nama_rekening ?? '' }}"
                      style="text-decoration: underline dotted; text-underline-offset: 3px; cursor: help;">{{ $b->kode_rekening }}</span>
                  @else
                    -
                  @endif
                </td>
                <td>
                  @php $aliasUraian = $petaAlias[$b->uraian] ?? null; @endphp
                  <span @if ($aliasUraian) title="{{ $b->uraian }}" @endif>{{ $aliasUraian ?? $b->uraian }}</span>
                  @if ($bisaTulisAlias)
                    <button type="button" class="btn btn-sm btn-icon btn-text-secondary p-0 ms-1 align-baseline"
                      title="Beri nama alias" data-alias-edit data-nama-asli="{{ $b->uraian }}"
                      data-alias="{{ $aliasUraian ?? '' }}">
                      <i class="icon-base ri ri-pencil-line"></i></button>
                  @endif
                </td>
                <td>{{ $b->nama_toko ?? '-' }}</td>
                <td class="small">{{ $b->labelPajak() ?: '-' }}</td>
                <td class="text-end fw-semibold">{{ number_format($b->saldo, 0, ',', '.') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-muted py-4">Tidak ada transaksi yang cocok dengan saringan
                  ini.</td>
              </tr>
            @endforelse
          </tbody>
          <tfoot>
            <tr>
              <td colspan="7" class="text-end fw-semibold">Total sesuai saringan</td>
              <td class="text-end fw-bold">Rp {{ number_format($totalNominal, 0, ',', '.') }}</td>
            </tr>
          </tfoot>
        </table>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="text-muted small">Menampilkan {{ $baris->count() }} dari {{ number_format($baris->total(), 0, ',', '.') }} transaksi</span>
        {{ $baris->links('pagination::bootstrap-5') }}
      </div>
    </div>

    @if ($bisaTulisAlias)
      @include('content.kelola-bos.partials.modal-alias')
    @endif
  @endif

@endsection
