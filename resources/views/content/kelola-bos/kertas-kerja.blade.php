@extends('layouts/layoutMaster')

@section('title', 'Kertas Kerja BOS')

@section('content')

  {{-- Pemilih konteks sekolah untuk Admin --}}
  @hasanyrole('Admin')
    <div class="card mb-4">
      <div class="card-body py-3 d-flex align-items-center gap-3 flex-wrap">
        <span class="text-muted"><i class="icon-base ri ri-school-line me-1"></i>Anda masuk sebagai Admin — konteks
          sekolah:</span>
        <form method="GET" action="{{ url('/kelola-bos/kertas-kerja') }}">
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
            <i class="icon-base ri ri-file-list-3-line ri-28px"></i>
          </span>
        </div>
        <div class="flex-grow-1">
          <h5 class="mb-1">Kertas Kerja BOS</h5>
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
          <div class="text-muted small">Total sesuai saringan</div>
          <h5 class="mb-0">Rp {{ number_format($totalJumlah, 0, ',', '.') }}</h5>
        </div>
      </div>
    </div>

    {{-- Kartu ringkasan anggaran, jadwal & realisasi (Fase 1.6, tiga tampilan Fase 1.8) --}}
    @php
      $labelPotong =
          $bulanPotong >= 1 ? 's.d. ' . $daftarBulan[$bulanPotong] : '(belum ada bulan berjalan)';
    @endphp
    <div class="row g-3 mb-4">
      <div class="col-6 col-md-3">
        <div class="card h-100">
          <div class="card-body py-3">
            <div class="text-muted small">Total Anggaran (versi terpilih)</div>
            <div class="fw-semibold">Rp {{ number_format($totalJumlah, 0, ',', '.') }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card h-100">
          <div class="card-body py-3">
            @if ($tampilan === 'rencana')
              <div class="text-muted small">Total Rencana {{ $daftarBulan[$bulan] }} {{ $tahunAcuan }}</div>
              <div class="fw-semibold">Rp {{ number_format($totalRencana, 0, ',', '.') }}</div>
            @else
              <div class="text-muted small">Terjadwal {{ $labelPotong }}</div>
              <div class="fw-semibold">Rp {{ number_format($kartu['terjadwal'], 0, ',', '.') }}</div>
            @endif
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card h-100">
          <div class="card-body py-3">
            <div class="text-muted small">Realisasi BKU</div>
            <div class="fw-semibold">Rp {{ number_format($kartu['realisasi'], 0, ',', '.') }}</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="card h-100">
          <div class="card-body py-3">
            @if ($tampilan === 'belanja')
              <div class="text-muted small">Bisa Dibelanjakan</div>
            @elseif ($tampilan === 'rencana')
              <div class="text-muted small">Belum Dibelanjakan (Rencana − Realisasi)</div>
            @else
              <div class="text-muted small">Sisa (Terjadwal − Realisasi)</div>
            @endif
            <div class="fw-semibold">Rp {{ number_format($kartu['sisa'], 0, ',', '.') }}</div>
          </div>
        </div>
      </div>
      <div class="col-12">
        <div class="text-muted small"><i class="icon-base ri ri-information-line me-1"></i>Realisasi terhubung ke
          versi tempat BKU diinput (umumnya versi yang aktif saat belanja dicatat) — baris pada versi lain bisa
          tampak belum terealisasi walau belanjanya sudah dicatat di BKU.</div>
        @if ($tampilan === 'posisi')
          <div class="text-muted small mt-1"><i class="icon-base ri ri-information-line me-1"></i>Posisi
            s.d. {{ $bulanPotong >= 1 ? $daftarBulan[$bulanPotong] : '—' }} {{ $tahunAcuan }}: status
            membandingkan jadwal Januari s.d. bulan ini dengan seluruh realisasi BKU baris ini — realisasi dapat
            berasal dari bulan-bulan sebelumnya. Baris yang jadwalnya baru mulai setelah bulan ini berstatus
            Belum Terjadwal.</div>
        @elseif ($tampilan === 'belanja')
          <div class="text-muted small mt-1"><i class="icon-base ri ri-information-line me-1"></i>Daftar kerja:
            hanya baris yang terjadwal s.d. {{ $bulanPotong >= 1 ? $daftarBulan[$bulanPotong] : '—' }}
            {{ $tahunAcuan }} dan masih punya sisa — termasuk tunggakan bulan-bulan lalu yang belum masuk BKU.
            Kolom Tertua Belum Lunas menunjukkan bulan jadwal paling awal yang belum lunas pada baris itu.</div>
        @else
          <div class="text-muted small mt-1"><i class="icon-base ri ri-information-line me-1"></i>Rencana
            anggaran bulan {{ $daftarBulan[$bulan] }} {{ $tahunAcuan }}: hanya baris yang punya jadwal di bulan
            ini. Tandanya mengikuti pembayaran yang terhubung ke jadwal bulan tersebut — jadwal bulan lalu boleh
            dibayar terlambat, jadwal bulan depan tidak bisa dibayar lebih dulu.</div>
        @endif
      </div>
    </div>

    {{-- Ringkasan per sumber dana --}}
    @if ($ringkasan->isNotEmpty())
      <div class="row g-3 mb-4">
        @foreach ($ringkasan as $r)
          <div class="col-12 col-md-4">
            <div class="card h-100">
              <div class="card-body py-3">
                <div class="text-muted small">{{ $r->nama_sumber_dana ?? 'Sumber dana #' . $r->id_ref_sumber_dana }}</div>
                <div class="fw-semibold">Rp {{ number_format($r->total, 0, ',', '.') }}</div>
                <div class="text-muted small">{{ number_format($r->baris, 0, ',', '.') }} baris kertas kerja</div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endif

    {{-- Pengalih tiga tampilan (Fase 1.8) --}}
    @php
      $queryDasar = request()->only(['sekolah_id', 'tahun', 'sumber_dana', 'versi', 'kegiatan', 'cari']);
      $tabTampilan = [
          'posisi' => 'Posisi s.d. Bulan Ini',
          'belanja' => 'Bisa Dibelanjakan',
          'rencana' => 'Rencana Bulanan',
      ];
    @endphp
    <div class="btn-group mb-4" role="group" aria-label="Tampilan kertas kerja">
      @foreach ($tabTampilan as $kunciTab => $labelTab)
        <a href="{{ url('/kelola-bos/kertas-kerja') . '?' . http_build_query($queryDasar + ['tampilan' => $kunciTab]) }}"
          class="btn {{ $tampilan === $kunciTab ? 'btn-primary' : 'btn-outline-primary' }}">{{ $labelTab }}</a>
      @endforeach
    </div>

    {{-- Saringan --}}
    <div class="card mb-4">
      <div class="card-body py-3">
        <form method="GET" action="{{ url('/kelola-bos/kertas-kerja') }}" class="row g-3 align-items-end">
          <input type="hidden" name="tampilan" value="{{ $tampilan }}">
          <div class="col-6 col-md-2">
            <label class="form-label">Tahun Anggaran</label>
            <select name="tahun" class="form-select">
              <option value="">Semua tahun</option>
              @foreach ($daftarTahun as $th)
                <option value="{{ $th }}" {{ ($tahunTerpilih ?? null) === $th ? 'selected' : '' }}>{{ $th }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label">Sumber Dana</label>
            <select name="sumber_dana" class="form-select">
              <option value="">Semua</option>
              @foreach ($daftarSumberDana as $sd)
                <option value="{{ $sd->id_ref_sumber_dana }}"
                  {{ ($sumberTerpilih ?? null) === (int) $sd->id_ref_sumber_dana ? 'selected' : '' }}>
                  {{ $sd->nama_sumber_dana }}</option>
              @endforeach
            </select>
          </div>
          @if (($sumberTerpilih ?? null) !== null)
            <div class="col-6 col-md-2">
              <label class="form-label">Versi RKAS</label>
              <select name="versi" class="form-select">
                <option value="" {{ !request('versi') ? 'selected' : '' }}>Terakhir disetujui (bawaan)</option>
                @foreach ($daftarVersi as $v)
                  <option value="{{ $v['id_arkas'] }}" {{ request('versi') === $v['id_arkas'] ? 'selected' : '' }}>
                    {{ $v['label'] }} ({{ $v['disetujui'] ? 'Disetujui' : 'Draf' }})</option>
                @endforeach
                <option value="semua" {{ request('versi') === 'semua' ? 'selected' : '' }}>Semua versi</option>
              </select>
            </div>
          @endif
          @if ($tampilan === 'rencana')
            <div class="col-6 col-md-2">
              <label class="form-label">Bulan Rencana</label>
              <select name="bulan" class="form-select">
                @foreach ($daftarBulan as $angkaBulan => $namaBulan)
                  <option value="{{ $angkaBulan }}" {{ $bulan === $angkaBulan ? 'selected' : '' }}>{{ $namaBulan }}
                  </option>
                @endforeach
              </select>
            </div>
          @endif
          @if ($tampilan === 'posisi')
            <div class="col-6 col-md-2">
              <label class="form-label">Status BKU</label>
              <select name="status" class="form-select">
                <option value="" {{ $statusFilter === '' ? 'selected' : '' }}>Semua status</option>
                <option value="sudah" {{ $statusFilter === 'sudah' ? 'selected' : '' }}>Sudah Masuk BKU</option>
                <option value="sebagian" {{ $statusFilter === 'sebagian' ? 'selected' : '' }}>Sebagian</option>
                <option value="belum" {{ $statusFilter === 'belum' ? 'selected' : '' }}>Belum Masuk BKU</option>
                <option value="belum_terjadwal" {{ $statusFilter === 'belum_terjadwal' ? 'selected' : '' }}>Belum
                  Terjadwal</option>
              </select>
            </div>
          @elseif ($tampilan === 'rencana')
            <div class="col-6 col-md-2">
              <label class="form-label">Tanda Belanja</label>
              <select name="status" class="form-select">
                <option value="" {{ $statusFilter === '' ? 'selected' : '' }}>Semua tanda</option>
                <option value="sudah" {{ $statusFilter === 'sudah' ? 'selected' : '' }}>Sudah Dibelanjakan</option>
                <option value="sebagian" {{ $statusFilter === 'sebagian' ? 'selected' : '' }}>Sebagian</option>
                <option value="belum" {{ $statusFilter === 'belum' ? 'selected' : '' }}>Belum Dibelanjakan</option>
              </select>
            </div>
          @endif
          <div class="col-12 col-md-3">
            <label class="form-label">Kegiatan</label>
            <select name="kegiatan" class="form-select">
              <option value="">Semua kegiatan</option>
              @foreach ($daftarKegiatan as $k)
                <option value="{{ $k->id_ref_kode }}" {{ request('kegiatan') === $k->id_ref_kode ? 'selected' : '' }}>
                  {{ $k->id_kode }} {{ $k->uraian_kode }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-3">
            <label class="form-label">Pencarian</label>
            <input type="text" name="cari" class="form-control" value="{{ request('cari') }}"
              placeholder="Uraian, rekening, kegiatan…">
          </div>
          <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="icon-base ri ri-filter-3-line me-1"></i>Saring</button>
            <a href="{{ url('/kelola-bos/kertas-kerja') }}" class="btn btn-outline-secondary">Atur Ulang</a>
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
                  $url = url('/kelola-bos/kertas-kerja') . '?' . http_build_query(request()->except(['page', 'urut', 'dir']) + ['urut' => $kunci, 'dir' => $dirBaru]);
                  return '<a href="' . e($url) . '" class="text-reset text-decoration-none">' . e($label) . $panah . '</a>';
                };
              @endphp
              <th>{!! $kepala('kegiatan', 'Kegiatan') !!}</th>
              <th>{!! $kepala('rekening', 'Rekening') !!}</th>
              <th>{!! $kepala('uraian', 'Uraian') !!}</th>
              <th class="text-end">{!! $kepala('volume', 'Volume') !!}</th>
              <th>Satuan</th>
              <th class="text-end">{!! $kepala('harga', 'Harga Satuan') !!}</th>
              <th class="text-end">{!! $kepala('jumlah', 'Jumlah') !!}</th>
              <th class="text-end">{!! $kepala('jadwal', 'Jadwal') !!}</th>
              <th class="text-end">{!! $kepala('realisasi', 'Realisasi BKU') !!}</th>
              <th class="text-end">{!! $kepala('sisa', 'Sisa') !!}</th>
              @if ($tampilan === 'belanja')
                <th>{!! $kepala('tertua', 'Tertua Belum Lunas') !!}</th>
              @endif
              <th>{{ $tampilan === 'rencana' ? 'Status Belanja' : 'Status BKU' }}</th>
              <th>Rincian Tahap</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($baris as $b)
              <tr>
                <td>
                  <span title="{{ $b->nama_kegiatan ?? '' }}"
                    style="text-decoration: underline dotted; text-underline-offset: 3px; cursor: help;">{{ $b->kode_kegiatan ?? '-' }}</span>
                </td>
                <td>
                  <span title="{{ $b->nama_rekening ?? '' }}"
                    style="text-decoration: underline dotted; text-underline-offset: 3px; cursor: help;">{{ $b->kode_rekening }}</span>
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
                <td class="text-end">{{ $b->volume == (int) $b->volume ? (int) $b->volume : $b->volume }}</td>
                <td>{{ $b->satuan }}</td>
                <td class="text-end">{{ number_format($b->harga_satuan, 0, ',', '.') }}</td>
                <td class="text-end fw-semibold">{{ number_format($b->jumlah, 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format($b->jadwal, 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format($b->realisasi, 0, ',', '.') }}</td>
                <td class="text-end">{{ number_format($b->sisa, 0, ',', '.') }}</td>
                @if ($tampilan === 'belanja')
                  <td>{{ $b->tertua_periode ? $daftarBulan[$b->tertua_periode - 80] : '-' }}</td>
                @endif
                <td>
                  @php
                    if ($tampilan === 'rencana') {
                        $lencana = [
                            'sudah' => ['bg-label-success', 'Sudah Dibelanjakan'],
                            'sebagian' => ['bg-label-warning', 'Sebagian'],
                            'belum' => ['bg-label-danger', 'Belum Dibelanjakan'],
                            'belum_terjadwal' => ['bg-label-secondary', 'Belum Dibelanjakan'],
                        ][$b->status_kelas] ?? ['bg-label-secondary', $b->status_kelas];
                    } elseif ($tampilan === 'belanja') {
                        $lencana = [
                            'sebagian' => ['bg-label-warning', 'Sebagian'],
                            'belum' => ['bg-label-danger', 'Belum Masuk BKU'],
                        ][$b->status_kelas] ?? ['bg-label-secondary', $b->status_kelas];
                    } else {
                        $lencana = [
                            'sudah' => ['bg-label-success', 'Sudah Masuk BKU'],
                            'sebagian' => ['bg-label-warning', 'Sebagian'],
                            'belum' => ['bg-label-danger', 'Belum Masuk BKU'],
                            'belum_terjadwal' => ['bg-label-secondary', 'Belum Terjadwal'],
                        ][$b->status_kelas] ?? ['bg-label-secondary', $b->status_kelas];
                    }
                  @endphp
                  <span class="badge {{ $lencana[0] }}">{{ $lencana[1] }}</span>
                  @if ($b->realisasi_terakhir)
                    <div class="small text-muted mt-1">{{ $tampilan === 'rencana' ? 'dibayar terakhir: ' : 'terakhir: ' }}
                      {{ \Carbon\Carbon::parse($b->realisasi_terakhir)->locale('id')->translatedFormat('d M Y') }}
                    </div>
                  @endif
                </td>
                <td class="small text-muted">
                  @php $tahap = collect([1 => $b->v1, 2 => $b->v2, 3 => $b->v3, 4 => $b->v4])->filter(fn($v) => $v !== null); @endphp
                  @if ($tahap->isEmpty())
                    -
                  @else
                    @foreach ($tahap as $t => $v)
                      T{{ $t }}: {{ $v == (int) $v ? (int) $v : $v }}{{ !$loop->last ? ' · ' : '' }}
                    @endforeach
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="{{ $tampilan === 'belanja' ? 13 : 12 }}" class="text-center text-muted py-4">Tidak ada
                  baris kertas kerja yang cocok dengan
                  saringan ini.</td>
              </tr>
            @endforelse
          </tbody>
          <tfoot>
            <tr>
              <td colspan="6" class="text-end fw-semibold">Total sesuai saringan</td>
              <td class="text-end fw-bold">Rp {{ number_format($totalJumlah, 0, ',', '.') }}</td>
              <td class="text-end fw-bold">Rp {{ number_format($kartu['terjadwal'], 0, ',', '.') }}</td>
              <td class="text-end fw-bold">Rp {{ number_format($kartu['realisasi'], 0, ',', '.') }}</td>
              <td class="text-end fw-bold">Rp {{ number_format($kartu['sisa'], 0, ',', '.') }}</td>
              <td colspan="{{ $tampilan === 'belanja' ? 3 : 2 }}"></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="text-muted small">Menampilkan {{ $baris->count() }} dari {{ number_format($baris->total(), 0, ',', '.') }} baris</span>
        {{ $baris->links('pagination::bootstrap-5') }}
      </div>
    </div>

    @if ($bisaTulisAlias)
      @include('content.kelola-bos.partials.modal-alias')
    @endif
  @endif

@endsection
