@extends('layouts/layoutMaster')

@section('title', 'Referensi Kode Kegiatan BOS')

@section('content')

  @hasanyrole('Admin')
    <div class="card mb-4">
      <div class="card-body py-3 d-flex align-items-center gap-3 flex-wrap">
        <span class="text-muted"><i class="icon-base ri ri-school-line me-1"></i>Anda masuk sebagai Admin — konteks
          sekolah:</span>
        <form method="GET" action="{{ url('/kelola-bos/referensi/kegiatan') }}">
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
            <i class="icon-base ri ri-node-tree ri-28px"></i>
          </span>
        </div>
        <div class="flex-grow-1">
          <h5 class="mb-1">Referensi Kode Kegiatan</h5>
          <p class="mb-0 text-muted">{{ $sekolah->detailTerbaru->nama_sekolah ?? 'Sekolah #' . $sekolah->id }}
            &middot; struktur kegiatan ARKAS (hanya baca)
            @if ($sinkronTerakhir)
              &middot; Data per sinkron terakhir: {{ $sinkronTerakhir->created_at->format('d-m-Y H:i') }}
            @endif
          </p>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-body py-3">
        <form method="GET" action="{{ url('/kelola-bos/referensi/kegiatan') }}" class="row g-3 align-items-end">
          @if (request('semua') === '1' || request('jenjang') === 'semua')
            <input type="hidden" name="semua" value="1">
          @endif
          <div class="col-6 col-md-2">
            <label class="form-label">Tahun</label>
            <select name="tahun" class="form-select">
              <option value="">Semua tahun</option>
              @foreach ($daftarTahun as $th)
                <option value="{{ $th }}" {{ ($tahunTerpilih ?? null) === $th ? 'selected' : '' }}>{{ $th }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label">Pencarian</label>
            <input type="text" name="cari" class="form-control" value="{{ request('cari') }}"
              placeholder="Uraian kegiatan atau kode kegiatan…">
          </div>
          <div class="col-12 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="icon-base ri ri-filter-3-line me-1"></i>Saring</button>
            <a href="{{ url('/kelola-bos/referensi/kegiatan') }}" class="btn btn-outline-secondary">Atur Ulang</a>
          </div>
        </form>
      </div>
    </div>

    {{-- Keterangan pembatasan jenjang & sumber dana (Fase 1.10 + 1.11) --}}
    @if ($terbatasLingkup ?? false)
      <div class="alert alert-info d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <span><i class="icon-base ri ri-filter-3-line me-1"></i>Menampilkan kode sesuai jenjang &amp; sumber dana
          sekolah ini.</span>
        <a class="alert-link" href="{{ request()->fullUrlWithQuery(['semua' => '1', 'jenjang' => null, 'page' => null]) }}">Tampilkan
          semua</a>
      </div>
    @elseif ($bisaDibatasi ?? false)
      <div class="alert alert-secondary d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <span><i class="icon-base ri ri-filter-off-line me-1"></i>Menampilkan semua kode (semua jenjang &amp; sumber
          dana).</span>
        <a class="alert-link" href="{{ request()->fullUrlWithQuery(['semua' => null, 'jenjang' => null, 'page' => null]) }}">Hanya
          yang bisa dibelanjakan sekolah ini</a>
      </div>
    @endif

    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th style="width: 140px">Kode Kegiatan</th>
              <th>Uraian Kegiatan</th>
              <th style="width: 90px">Tahun</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($baris as $b)
              <tr>
                <td>{{ $b->id_kode }}</td>
                <td>
                  <span style="display:inline-block; width: {{ max(0, ((int) ($b->id_level_kode ?? 1) - 1)) * 24 }}px"></span>{{ $b->uraian_kode }}
                </td>
                <td>{{ $b->tahun }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="3" class="text-center text-muted py-4">Tidak ada kegiatan yang cocok dengan saringan ini.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="text-muted small">Menampilkan {{ $baris->count() }} dari {{ number_format($baris->total(), 0, ',', '.') }} kegiatan</span>
        {{ $baris->links('pagination::bootstrap-5') }}
      </div>
    </div>
  @endif

@endsection
