@extends('layouts/layoutMaster')

@section('title', 'Referensi Kode Rekening BOS')

@section('content')

  @hasanyrole('Admin')
    <div class="card mb-4">
      <div class="card-body py-3 d-flex align-items-center gap-3 flex-wrap">
        <span class="text-muted"><i class="icon-base ri ri-school-line me-1"></i>Anda masuk sebagai Admin — konteks
          sekolah:</span>
        <form method="GET" action="{{ url('/kelola-bos/referensi/rekening') }}">
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
            <i class="icon-base ri ri-book-2-line ri-28px"></i>
          </span>
        </div>
        <div class="flex-grow-1">
          <h5 class="mb-1">Referensi Kode Rekening</h5>
          <p class="mb-0 text-muted">{{ $sekolah->detailTerbaru->nama_sekolah ?? 'Sekolah #' . $sekolah->id }}
            &middot; bagan akun belanja ARKAS (hanya baca)
            @if ($sinkronTerakhir)
              &middot; Data per sinkron terakhir: {{ $sinkronTerakhir->created_at->format('d-m-Y H:i') }}
            @endif
          </p>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-body py-3">
        <form method="GET" action="{{ url('/kelola-bos/referensi/rekening') }}" class="row g-3 align-items-end">
          <div class="col-6 col-md-2">
            <label class="form-label">Tahun</label>
            <select name="tahun" class="form-select">
              <option value="">Semua tahun</option>
              @foreach ($daftarTahun as $th)
                <option value="{{ $th }}" {{ (int) request('tahun') === $th ? 'selected' : '' }}>{{ $th }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Pencarian</label>
            <input type="text" name="cari" class="form-control" value="{{ request('cari') }}"
              placeholder="Kode rekening atau nama rekening…">
          </div>
          <div class="col-12 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="icon-base ri ri-filter-3-line me-1"></i>Saring</button>
            <a href="{{ url('/kelola-bos/referensi/rekening') }}" class="btn btn-outline-secondary">Atur Ulang</a>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th style="width: 180px">Kode Rekening</th>
              <th>Nama Rekening</th>
              <th style="width: 90px">Tahun</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($baris as $b)
              <tr>
                <td>{{ $b->kode_rekening }}</td>
                <td>{{ $b->nama_rekening }}</td>
                <td>{{ $b->tahun }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="3" class="text-center text-muted py-4">Tidak ada rekening yang cocok dengan saringan ini.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="text-muted small">Menampilkan {{ $baris->count() }} dari {{ number_format($baris->total(), 0, ',', '.') }} rekening</span>
        {{ $baris->links('pagination::bootstrap-5') }}
      </div>
    </div>
  @endif

@endsection
