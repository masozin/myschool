@extends('layouts/layoutMaster')

@section('title', 'Referensi Kode Barang BOS')

@section('content')

  @hasanyrole('Admin')
    <div class="card mb-4">
      <div class="card-body py-3 d-flex align-items-center gap-3 flex-wrap">
        <span class="text-muted"><i class="icon-base ri ri-school-line me-1"></i>Anda masuk sebagai Admin — konteks
          sekolah:</span>
        <form method="GET" action="{{ url('/kelola-bos/referensi/barang') }}">
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
    <div class="alert alert-warning">Pilih sekolah dulu lewat pemilih di atas — data BOS mengikuti sekolah yang
      dipilih.</div>
  @else
    <div class="card mb-4">
      <div class="card-body py-4 d-flex align-items-center gap-4 flex-wrap">
        <div class="avatar avatar-xl flex-shrink-0">
          <span class="avatar-initial rounded-circle bg-label-primary">
            <i class="icon-base ri ri-barcode-line ri-28px"></i>
          </span>
        </div>
        <div class="flex-grow-1">
          <h5 class="mb-1">Referensi Kode Barang</h5>
          <p class="mb-0 text-muted">{{ $sekolah->detailTerbaru->nama_sekolah ?? 'Sekolah #' . $sekolah->id }}
            &middot; acuan barang &amp; harga SSH dari ARKAS (hanya baca)
            @if ($sinkronTerakhir)
              &middot; Data per sinkron terakhir: {{ $sinkronTerakhir->created_at->format('d-m-Y H:i') }}
            @endif
          </p>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-body py-3">
        <form method="GET" action="{{ url('/kelola-bos/referensi/barang') }}" class="row g-3 align-items-end">
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
            <label class="form-label">Blok Rekening</label>
            <select name="blok" class="form-select">
              <option value="">Semua blok</option>
              @foreach ($daftarBlok as $bl)
                <option value="{{ $bl }}" {{ request('blok') === $bl ? 'selected' : '' }}>{{ $bl }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-5">
            <label class="form-label">Pencarian</label>
            <input type="text" name="cari" class="form-control" value="{{ request('cari') }}"
              placeholder="Nama barang, kode barang, kode rekening…">
          </div>
          <div class="col-12 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="icon-base ri ri-filter-3-line me-1"></i>Saring</button>
            <a href="{{ url('/kelola-bos/referensi/barang') }}" class="btn btn-outline-secondary">Atur Ulang</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              @php
                $kepala = function ($kunci, $label) use ($urut, $arah) {
                  $dirBaru = $urut === $kunci && $arah === 'asc' ? 'desc' : 'asc';
                  $panah = $urut === $kunci ? ($arah === 'asc' ? ' ↑' : ' ↓') : '';
                  $url = url('/kelola-bos/referensi/barang') . '?' . http_build_query(request()->except(['page', 'urut', 'dir']) + ['urut' => $kunci, 'dir' => $dirBaru]);
                  return '<a href="' . e($url) . '" class="text-reset text-decoration-none">' . e($label) . $panah . '</a>';
                };
              @endphp
              <th>Kode Barang</th>
              <th>{!! $kepala('nama', 'Nama Barang') !!}</th>
              <th>Kode Rekening</th>
              <th>Satuan</th>
              <th class="text-end">{!! $kepala('harga', 'Harga Acuan') !!}</th>
              <th class="text-end">Batas Bawah – Atas</th>
              <th>Tahun</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($baris as $b)
              <tr>
                <td class="small">{{ $b->id_barang ?? '-' }}</td>
                <td>{{ $b->nama_barang ?? '-' }}</td>
                <td>{{ $b->kode_rekening ?? '-' }}</td>
                <td>{{ $b->satuan ?? '-' }}</td>
                <td class="text-end fw-semibold">{{ $b->harga_barang !== null ? number_format($b->harga_barang, 0, ',', '.') : '-' }}</td>
                <td class="text-end text-muted small">
                  {{ $b->batas_bawah !== null ? number_format($b->batas_bawah, 0, ',', '.') : '–' }}
                  –
                  {{ $b->batas_atas !== null ? number_format($b->batas_atas, 0, ',', '.') : '–' }}
                </td>
                <td>{{ $b->tahun }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">Tidak ada barang yang cocok dengan saringan ini.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="text-muted small">Menampilkan {{ $baris->count() }} dari {{ number_format($baris->total(), 0, ',', '.') }} barang</span>
        {{ $baris->links('pagination::bootstrap-5') }}
      </div>
    </div>
  @endif

@endsection
