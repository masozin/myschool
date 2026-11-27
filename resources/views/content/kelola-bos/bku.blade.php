@extends('layouts/layoutMaster')

@section('title', 'BKU BOS')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

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

    {{-- Kartu dana & anggaran --}}
    @php
      $namaBulanKartu = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
      $rp = fn($v) => 'Rp ' . number_format($v, 0, ',', '.');
      $merah = fn($v) => $v < 0 ? 'text-danger' : '';
      $bulanDana = $kartuDana['bulan'] ?? null;
      $labelDana = $kartuDana['tahun'] === null
          ? 'belum ada transaksi'
          : ($bulanDana
              ? 's.d. akhir ' . $namaBulanKartu[$bulanDana] . ' ' . $kartuDana['tahun']
              : ($kartuDana['tahun'] < (int) now()->format('Y') ? 'akhir tahun ' : 'posisi terakhir tahun ') . $kartuDana['tahun']);
      $bulanAng = $kartuAnggaran['bulan'];
      $labelAng = $bulanAng >= 1 ? 's.d. ' . $namaBulanKartu[$bulanAng] . ' ' . $kartuAnggaran['tahun'] : $kartuAnggaran['tahun'];
    @endphp

    <h6 class="mb-2">Posisi Dana <span class="text-muted fw-normal">&middot; {{ $labelDana }}</span></h6>
    <div class="row g-3 mb-2">
      <div class="col-6 col-md-3">
        <div class="card h-100">
          <div class="card-body py-3">
            <div class="text-muted small">Total Dana Tersedia</div>
            <div class="fw-bold {{ $merah($kartuDana['tersedia']) ?: 'text-primary' }}">{{ $rp($kartuDana['tersedia']) }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card h-100">
          <div class="card-body py-3">
            <div class="text-muted small">Dana Tunai</div>
            <div class="fw-semibold {{ $merah($kartuDana['tunai']) }}">{{ $rp($kartuDana['tunai']) }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card h-100">
          <div class="card-body py-3">
            <div class="text-muted small">Dana Non Tunai (Bank)</div>
            <div class="fw-semibold {{ $merah($kartuDana['bank']) }}">{{ $rp($kartuDana['bank']) }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card h-100">
          <div class="card-body py-3">
            <div class="text-muted small">Jumlah Pajak Belanja</div>
            <div class="fw-semibold {{ $merah($kartuDana['pajak']) }}">{{ $rp($kartuDana['pajak']) }}</div>
          </div>
        </div>
      </div>
      <div class="col-12">
        <div class="text-muted small"><i class="icon-base ri ri-information-line me-1"></i>Dana tersedia = tunai + non
          tunai. Dihitung per anggaran dari saldo awal bulan terakhir (saldo penutup bulan sebelumnya) ditambah
          transaksi sesudahnya: tunai + pergeseran tunai − kas keluar; non tunai + terima dana + bunga bank − pajak
          bunga − tarik tunai − kas keluar non tunai. Jumlah pajak = pajak belanja yang tercatat sejak Januari s.d.
          posisi ini, tidak memengaruhi dana. Tidak ikut saringan jenis, rekening, dan pencarian.</div>
        @if (!empty($kartuDana['takDikenal']['jml']))
          <div class="small text-warning mt-1"><i class="icon-base ri ri-error-warning-line me-1"></i>{{ $kartuDana['takDikenal']['jml'] }}
            transaksi dengan jenis BKU #{{ implode(', #', $kartuDana['takDikenal']['ids']) }} belum dipetakan dan tidak
            ikut dihitung pada kartu dana.</div>
        @endif
      </div>
    </div>

    <h6 class="mb-2 mt-4">Anggaran <span class="text-muted fw-normal">&middot; {{ $labelAng }}</span></h6>
    <div class="row g-3 mb-4">
      <div class="col-12 col-sm-4">
        <div class="card h-100">
          <div class="card-body py-3">
            <div class="text-muted small">Bisa Dibelanjakan</div>
            <div class="fw-semibold">{{ $rp($kartuAnggaran['bisa']) }}</div>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-4">
        <div class="card h-100">
          <div class="card-body py-3">
            <div class="text-muted small">Sudah Dibelanjakan</div>
            <div class="fw-semibold">{{ $rp($kartuAnggaran['sudah']) }}</div>
          </div>
        </div>
      </div>
      <div class="col-12 col-sm-4">
        <div class="card h-100">
          <div class="card-body py-3">
            <div class="text-muted small">Anggaran Bulan Ini{{ $bulanAng >= 1 ? ' (' . $namaBulanKartu[$bulanAng] . ')' : '' }}</div>
            <div class="fw-semibold">{{ $rp($kartuAnggaran['bulanIni']) }}</div>
          </div>
        </div>
      </div>
      <div class="col-12">
        @if ($bulanAng < 1)
          <div class="text-muted small"><i class="icon-base ri ri-information-line me-1"></i>Belum ada bulan berjalan
            pada tahun {{ $kartuAnggaran['tahun'] }}.</div>
        @elseif (!$kartuAnggaran['ada'])
          <div class="text-muted small"><i class="icon-base ri ri-information-line me-1"></i>Belum ada RKAS untuk tahun
            {{ $kartuAnggaran['tahun'] }}.</div>
        @else
          <div class="text-muted small"><i class="icon-base ri ri-information-line me-1"></i>Terjadwal s.d. bulan ini
            {{ $rp($kartuAnggaran['terjadwal']) }}. Bisa dibelanjakan = sisa jadwal baris yang belum terpenuhi; sudah
            dibelanjakan = realisasi BKU belanja. Memakai versi RKAS disetujui terakhir, semua sumber dana — sama dengan
            Kertas Kerja.</div>
        @endif
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
                <option value="{{ $th }}" {{ ($tahunTerpilih ?? null) === $th ? 'selected' : '' }}>{{ $th }}</option>
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
            <a href="{{ route('kelola-bos.bku.kwitansi-massal', request()->query()) }}" target="_blank"
              class="btn btn-outline-primary"><i class="icon-base ri ri-receipt-line me-1"></i>Cetak Kwitansi</a>
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
                  @if (in_array((int) $b->id_ref_bku, [4, 15, 24, 35], true))
                    <a href="{{ route('kelola-bos.bku.kwitansi', $b->id) }}" target="_blank"
                      class="btn btn-sm btn-icon btn-text-secondary p-0 ms-1 align-baseline"
                      title="Cetak kwitansi (per nota)"><i class="icon-base ri ri-receipt-line"></i></a>
                  @endif
                </td>
                <td>
                  @php
                    $namaGrupBaris = $namaPenerima[$b->id] ?? [];
                  @endphp
                  <span class="nama-tampil-baris" data-nama-toko="{{ $b->nama_toko ?? '' }}">
                    @if ($namaGrupBaris)
                      {{ $namaGrupBaris[0] }}
                      @if (count($namaGrupBaris) > 1)
                        <span class="text-muted small">+{{ count($namaGrupBaris) - 1 }} lainnya</span>
                      @endif
                    @else
                      {{ $b->nama_toko ?? '-' }}
                    @endif
                  </span>
                  @if ($namaGrupBaris && $b->nama_toko && !in_array($b->nama_toko, $namaGrupBaris, true))
                    <div class="small text-muted nama-toko-baris">Toko: {{ $b->nama_toko }}</div>
                  @endif
                  @if (in_array((int) $b->id_ref_bku, [4, 15, 24, 35], true))
                    @php
                      $metaBaris = $metaGrup[$b->id] ?? ['status' => 'belum', 'nomor_va' => null];
                    @endphp
                    {{-- Lencana meta grup: SIPLah (dari nota), status
                         (Belum tidak ditampilkan), dan nomor VA. Wadah
                         selalu dirender untuk baris belanja agar JS
                         modal bisa memperbaruinya tanpa muat ulang. --}}
                    <div class="meta-grup-baris mt-1 d-flex flex-wrap align-items-center gap-1">
                      @if (!empty($b->is_beli_di_siplah))
                        <span class="badge bg-info">SIPLah</span>
                      @endif
                      @if ($metaBaris['status'] === 'lunas')
                        <span class="badge bg-success lencana-status-grup">Lunas</span>
                      @elseif ($metaBaris['status'] === 'selesai')
                        <span class="badge bg-primary lencana-status-grup">Selesai</span>
                      @endif
                      @if (!empty($metaBaris['nomor_va']))
                        <span class="small text-muted lencana-va-grup">VA: {{ $metaBaris['nomor_va'] }}</span>
                      @endif
                    </div>
                    @if (!empty($b->is_beli_di_siplah))
                      {{-- Baris SIPLah tidak memakai editor penerima:
                           status & nomor VA diisi langsung (inline). --}}
                      @if ($bisaTulisPenerima)
                        <div class="meta-inline mt-1 d-flex flex-wrap align-items-center gap-1" data-meta-inline data-id="{{ $b->id }}">
                          <select class="form-select form-select-sm py-0 meta-inline-status" style="width: auto;" title="Status SPJ">
                            <option value="belum" @selected($metaBaris['status'] === 'belum')>Belum</option>
                            <option value="lunas" @selected($metaBaris['status'] === 'lunas')>Lunas</option>
                            <option value="selesai" @selected($metaBaris['status'] === 'selesai')>Selesai</option>
                          </select>
                          <input type="text" class="form-control form-control-sm py-0 meta-inline-va" style="width: 140px;"
                            placeholder="Nomor VA" value="{{ $metaBaris['nomor_va'] }}">
                          <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 meta-inline-simpan">Simpan</button>
                        </div>
                      @endif
                    @else
                      <div class="mt-1">
                        <button type="button" class="btn btn-sm btn-outline-success py-0 px-2"
                          title="Penerima SPJ (per nota)" data-penerima-baris data-id="{{ $b->id }}">
                          <i class="icon-base ri ri-team-line me-1"></i>Penerima
                          @if (($jumlahPenerima[$b->id] ?? 0) > 0)
                            <span class="badge rounded-pill bg-success lencana-penerima"
                              style="font-size: .6rem; vertical-align: super;">{{ $jumlahPenerima[$b->id] }}</span>
                          @endif
                        </button>
                      </div>
                    @endif
                  @endif
                </td>
                <td class="small">{{ $b->labelPajak() ?: '-' }}</td>
                <td class="text-end fw-semibold">{{ number_format($b->saldo, 0, ',', '.') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted py-4">Tidak ada transaksi yang cocok dengan saringan
                  ini.</td>
              </tr>
            @endforelse
          </tbody>
          <tfoot>
            <tr>
              <td colspan="6" class="text-end fw-semibold">Total sesuai saringan</td>
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

    @include('content.kelola-bos.partials.modal-penerima')
  @endif

@endsection
