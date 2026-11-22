@extends('layouts/layoutMaster')

@section('title', 'Perbandingan Versi Kertas Kerja BOS')

@section('content')

  @hasanyrole('Admin')
    <div class="card mb-4">
      <div class="card-body py-3 d-flex align-items-center gap-3 flex-wrap">
        <span class="text-muted"><i class="icon-base ri ri-school-line me-1"></i>Anda masuk sebagai Admin — konteks
          sekolah:</span>
        <form method="GET" action="{{ url('/kelola-bos/perbandingan') }}">
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
            <i class="icon-base ri ri-git-compare-line ri-28px"></i>
          </span>
        </div>
        <div class="flex-grow-1">
          <h5 class="mb-1">Perbandingan Versi Kertas Kerja</h5>
          <p class="mb-0 text-muted">{{ $sekolah->detailTerbaru->nama_sekolah ?? 'Sekolah #' . $sekolah->id }}
            &middot; lembar kerja pergeseran: selisih dua versi RKAS per kegiatan, rekening, dan uraian
            @if ($sinkronTerakhir)
              &middot; Data per sinkron terakhir: {{ $sinkronTerakhir->created_at->format('d-m-Y H:i') }}
            @endif
          </p>
        </div>
        @if ($hasil)
          <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="icon-base ri ri-printer-line me-1"></i>Cetak</button>
        @endif
      </div>
    </div>

    {{-- Pilihan versi --}}
    <div class="card mb-4">
      <div class="card-body py-3">
        <form method="GET" action="{{ url('/kelola-bos/perbandingan') }}" class="row g-3 align-items-end">
          <div class="col-6 col-md-2">
            <label class="form-label">Tahun Anggaran</label>
            <select name="tahun" class="form-select">
              <option value="">Pilih tahun</option>
              @foreach ($daftarTahun as $th)
                <option value="{{ $th }}" {{ ($tahunTerpilih ?? null) === $th ? 'selected' : '' }}>{{ $th }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label">Sumber Dana</label>
            <select name="sumber_dana" class="form-select">
              <option value="">Pilih sumber</option>
              @foreach ($daftarSumberDana as $sd)
                <option value="{{ $sd->id_ref_sumber_dana }}"
                  {{ ($sumberTerpilih ?? null) === (int) $sd->id_ref_sumber_dana ? 'selected' : '' }}>
                  {{ $sd->nama_sumber_dana }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Versi A (sebelum)</label>
            <select name="versi_a" class="form-select">
              <option value="">Pilih versi</option>
              @foreach ($daftarVersi as $v)
                <option value="{{ $v['id_arkas'] }}" {{ ($versiATerpilih ?? '') === $v['id_arkas'] ? 'selected' : '' }}>
                  {{ $v['label'] }} ({{ $v['disetujui'] ? 'Disetujui' : 'Draf' }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Versi B (sesudah)</label>
            <select name="versi_b" class="form-select">
              <option value="">Pilih versi</option>
              @foreach ($daftarVersi as $v)
                <option value="{{ $v['id_arkas'] }}" {{ ($versiBTerpilih ?? '') === $v['id_arkas'] ? 'selected' : '' }}>
                  {{ $v['label'] }} ({{ $v['disetujui'] ? 'Disetujui' : 'Draf' }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="icon-base ri ri-git-compare-line me-1"></i>Bandingkan</button>
          </div>
        </form>
        <p class="text-muted small mb-0 mt-2">Bawaan: tahun berjalan, BOS Reguler, Versi A = versi terakhir
          disetujui, Versi B = versi terbaru (termasuk draf yang sedang dikerjakan). Ubah pilihan di atas lalu
          klik Bandingkan bila perlu.</p>
      </div>
    </div>

    @if (!$hasil)
      <div class="alert alert-info">Belum ada perbandingan. Pilih dua versi kertas kerja di atas untuk melihat
        baris yang berubah, baru, dan hilang — bahan menyusun pergeseran berikutnya.</div>
    @else
      @php
        $rp = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
        $rpTanda = fn($n) => ($n > 0 ? '+' : '') . 'Rp ' . number_format($n, 0, ',', '.');
      @endphp

      {{-- Ringkasan --}}
      <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
          <div class="card h-100"><div class="card-body py-3">
            <div class="text-muted small">Total Versi A — {{ $hasil['versiA']->label_versi ?? '' }}</div>
            <div class="fw-semibold">{{ $rp($hasil['totalA']) }}</div>
          </div></div>
        </div>
        <div class="col-12 col-md-3">
          <div class="card h-100"><div class="card-body py-3">
            <div class="text-muted small">Total Versi B — {{ $hasil['versiB']->label_versi ?? '' }}</div>
            <div class="fw-semibold">{{ $rp($hasil['totalB']) }}</div>
          </div></div>
        </div>
        <div class="col-12 col-md-3">
          <div class="card h-100"><div class="card-body py-3">
            <div class="text-muted small">Selisih (B − A)</div>
            <div class="fw-semibold {{ $hasil['selisih'] > 0 ? 'text-success' : ($hasil['selisih'] < 0 ? 'text-danger' : '') }}">
              {{ $rpTanda($hasil['selisih']) }}</div>
          </div></div>
        </div>
        <div class="col-12 col-md-3">
          <div class="card h-100"><div class="card-body py-3">
            <div class="text-muted small">Baris berubah / baru / hilang / tetap</div>
            <div class="fw-semibold">{{ $hasil['hitung']['berubah'] }} / {{ $hasil['hitung']['baru'] }} /
              {{ $hasil['hitung']['hilang'] }} / {{ $hasil['hitung']['tetap'] }}</div>
          </div></div>
        </div>
      </div>

      {{-- Subtotal per kegiatan --}}
      <div class="card mb-4">
        <div class="card-header py-3"><h6 class="mb-0">Subtotal per Kegiatan</h6></div>
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead>
              <tr>
                <th>Kegiatan</th>
                <th class="text-end">Total A</th>
                <th class="text-end">Total B</th>
                <th class="text-end">Selisih</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($hasil['perKegiatan'] as $k)
                <tr>
                  <td><span class="text-muted small">{{ $k['id_kode'] }}</span> {{ $k['uraian_kode'] }}</td>
                  <td class="text-end">{{ number_format($k['totalA'], 0, ',', '.') }}</td>
                  <td class="text-end">{{ number_format($k['totalB'], 0, ',', '.') }}</td>
                  <td class="text-end {{ $k['totalB'] - $k['totalA'] > 0 ? 'text-success' : ($k['totalB'] - $k['totalA'] < 0 ? 'text-danger' : '') }}">
                    {{ number_format($k['totalB'] - $k['totalA'], 0, ',', '.') }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      {{-- Rincian perubahan per kegiatan --}}
      @foreach ($hasil['perKegiatan'] as $k)
        @if (!empty($k['baris']))
          <div class="card mb-4">
            <div class="card-header py-3">
              <h6 class="mb-0"><span class="text-muted small">{{ $k['id_kode'] }}</span> {{ $k['uraian_kode'] }}</h6>
            </div>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>
                    <th>Uraian</th>
                    <th>Rekening</th>
                    <th>Status</th>
                    <th class="text-end">Volume A → B</th>
                    <th class="text-end">Jumlah A</th>
                    <th class="text-end">Jumlah B</th>
                    <th class="text-end">Selisih</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($k['baris'] as $r)
                    <tr>
                      <td>{{ $r['uraian'] }}</td>
                      <td>
                        <div class="small text-muted">{{ $r['kode_rekening'] }}</div>
                        <div>{{ $r['nama_rekening'] ?? '-' }}</div>
                      </td>
                      <td>
                        @if ($r['kelas'] === 'berubah')
                          <span class="badge bg-label-warning">Berubah</span>
                        @elseif ($r['kelas'] === 'baru')
                          <span class="badge bg-label-success">Baru</span>
                        @else
                          <span class="badge bg-label-danger">Hilang</span>
                        @endif
                      </td>
                      <td class="text-end">
                        {{ $r['volume_a'] == (int) $r['volume_a'] ? (int) $r['volume_a'] : $r['volume_a'] }}
                        →
                        {{ $r['volume_b'] == (int) $r['volume_b'] ? (int) $r['volume_b'] : $r['volume_b'] }}
                      </td>
                      <td class="text-end">{{ number_format($r['jumlah_a'], 0, ',', '.') }}</td>
                      <td class="text-end">{{ number_format($r['jumlah_b'], 0, ',', '.') }}</td>
                      <td class="text-end fw-semibold {{ $r['selisih'] > 0 ? 'text-success' : ($r['selisih'] < 0 ? 'text-danger' : '') }}">
                        {{ number_format($r['selisih'], 0, ',', '.') }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endif
      @endforeach
    @endif
  @endif

@endsection
