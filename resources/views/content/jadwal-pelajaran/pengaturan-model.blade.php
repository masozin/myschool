@extends('layouts/layoutMaster')

@section('title', 'Model Jadwal — ' . $model->nama)

@section('content')

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="card mb-4">
    <div class="card-body py-4 d-flex align-items-center gap-4 flex-wrap">
      <div class="avatar avatar-xl flex-shrink-0">
        <span class="avatar-initial rounded-circle bg-label-primary">
          <i class="icon-base ri ri-time-line ri-28px"></i>
        </span>
      </div>
      <div class="flex-grow-1">
        <h4 class="mb-1">Model Jadwal: {{ $model->nama }}</h4>
        <div class="text-muted small">
          {{ $model->labelRentang() }}
          <span class="mx-1">•</span> Kerangka waktu {{ $sekolah->detailTerbaru->nama_sekolah ?? '-' }}
          @if ($semester)
            <span class="mx-1">•</span> Semester {{ $semester->semester }} {{ $semester->tahun_pelajaran }}
          @endif
        </div>
      </div>
      <a href="{{ route('jadwal-pelajaran.pengaturan') }}" class="btn btn-outline-secondary">
        <i class="ri ri-arrow-left-line me-1"></i> Daftar Model
      </a>
    </div>
  </div>

  @if (!$tersimpan)
    <div class="alert alert-info">Model ini belum mempunyai kerangka tersimpan — nilai di bawah ini pola bawaan.
      Sesuaikan lalu simpan.</div>
  @endif

  <form method="POST" action="{{ route('jadwal-pelajaran.pengaturan.model.simpan', $model->id) }}">
    @csrf
    <div class="card mb-4">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-12 col-md-5">
            <label class="form-label" for="nama-model">Nama Model</label>
            <input type="text" id="nama-model" class="form-control" name="nama"
              value="{{ old('nama', $model->nama) }}" maxlength="50" required>
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label" for="tingkat-dari">Tingkat Dari</label>
            <input type="number" id="tingkat-dari" class="form-control" name="tingkat_dari"
              value="{{ old('tingkat_dari', $model->tingkat_dari) }}" min="1" max="12" placeholder="Semua">
          </div>
          <div class="col-6 col-md-2">
            <label class="form-label" for="tingkat-sampai">Tingkat Sampai</label>
            <input type="number" id="tingkat-sampai" class="form-control" name="tingkat_sampai"
              value="{{ old('tingkat_sampai', $model->tingkat_sampai) }}" min="1" max="12" placeholder="Semua">
          </div>
          <div class="col-12 col-md-3">
            <small class="text-muted">Kosongkan kedua tingkat untuk model umum (berlaku bagi tingkat yang tidak
              tercakup model lain). Rentang antar-model tidak boleh bertumpuk.</small>
          </div>
        </div>
      </div>
    </div>
    <div class="card">
      <div class="table-responsive">
        <table class="table mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Hari</th>
              <th style="width: 130px;">Jumlah Jam</th>
              <th style="width: 140px;">Jam Mulai</th>
              <th style="width: 130px;">Menit / Jam</th>
              <th>Istirahat 1 (sesudah jam ke- …, … menit)</th>
              <th>Istirahat 2 (sesudah jam ke- …, … menit)</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($namaHari as $hari => $nama)
              @php
                $f = $kerangka[$hari] ?? null;
                $j1 = $f['istirahat'][0] ?? null;
                $j2 = $f['istirahat'][1] ?? null;
              @endphp
              <tr>
                <td class="fw-semibold">{{ $nama }}</td>
                <td>
                  <input type="number" class="form-control" name="hari[{{ $hari }}][jumlah_jam]"
                    value="{{ $f['jumlah_jam'] ?? '' }}" min="0" max="12" placeholder="0">
                </td>
                <td>
                  <input type="time" class="form-control" name="hari[{{ $hari }}][mulai]"
                    value="{{ $f['mulai'] ?? '07:00' }}">
                </td>
                <td>
                  <input type="number" class="form-control" name="hari[{{ $hari }}][menit]"
                    value="{{ $f['menit'] ?? 35 }}" min="15" max="90">
                </td>
                <td>
                  <div class="d-flex gap-2">
                    <input type="number" class="form-control" name="hari[{{ $hari }}][istirahat][0][setelah]"
                      value="{{ $j1['setelah'] ?? '' }}" min="1" max="11" placeholder="Jam ke-">
                    <input type="number" class="form-control" name="hari[{{ $hari }}][istirahat][0][menit]"
                      value="{{ $j1['menit'] ?? '' }}" min="5" max="90" placeholder="Menit">
                  </div>
                </td>
                <td>
                  <div class="d-flex gap-2">
                    <input type="number" class="form-control" name="hari[{{ $hari }}][istirahat][1][setelah]"
                      value="{{ $j2['setelah'] ?? '' }}" min="1" max="11" placeholder="Jam ke-">
                    <input type="number" class="form-control" name="hari[{{ $hari }}][istirahat][1][menit]"
                      value="{{ $j2['menit'] ?? '' }}" min="5" max="90" placeholder="Menit">
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="card-body border-top d-flex align-items-center gap-3 flex-wrap">
        <button type="submit" class="btn btn-primary">
          <i class="ri ri-save-line me-1"></i> Simpan Kerangka
        </button>
        <small class="text-muted">Jumlah jam 0 atau kosong = tidak ada pelajaran hari itu. Mengubah kerangka tidak
          menghapus jadwal yang sudah terisi; kotak yang jatuh di luar kerangka baru akan ditandai di editor.</small>
      </div>
    </div>

    <div class="card mt-4">
      <div class="card-header pb-3">
        <h5 class="mb-1">Kegiatan Tetap dalam Sehari</h5>
        <small class="text-muted">Kegiatan tetap bagi rombel pemakai model ini, yang waktunya tetap dan durasinya tidak
          mengikuti jam pelajaran — misalnya Upacara 40 menit sebelum jam ke-1 hari Senin, Senam 30 menit sebelum jam ke-1 hari
          Jumat, atau Literasi 5 menit sebelum jam ke-1 setiap hari. Kegiatan ini menggeser jam mulai pelajaran
          sesudahnya secara otomatis. Kosongkan nama untuk menghapus kegiatan.</small>
      </div>
      <div class="table-responsive">
        <table class="table mb-0 align-middle">
          <tbody>
            @foreach ($namaHari as $hari => $nama)
              <tr>
                <td class="fw-semibold" style="width: 90px;">{{ $nama }}</td>
                <td>
                  @for ($i = 0; $i < 3; $i++)
                    @php
                      $keg = $kerangka[$hari]['kegiatan'][$i] ?? null;
                    @endphp
                    <div class="d-flex gap-2 {{ $i < 2 ? 'mb-2' : '' }} flex-wrap">
                      <input type="text" class="form-control" style="width: 200px;"
                        name="hari[{{ $hari }}][kegiatan][{{ $i }}][nama]"
                        value="{{ $keg['nama'] ?? '' }}" placeholder="Nama kegiatan (mis. Upacara)" maxlength="100">
                      <select class="form-select" style="width: 150px;"
                        name="hari[{{ $hari }}][kegiatan][{{ $i }}][posisi]">
                        <option value="sebelum" {{ ($keg['posisi'] ?? 'sebelum') === 'sebelum' ? 'selected' : '' }}>
                          Sebelum jam ke-</option>
                        <option value="sesudah" {{ ($keg['posisi'] ?? '') === 'sesudah' ? 'selected' : '' }}>
                          Sesudah jam ke-</option>
                      </select>
                      <input type="number" class="form-control" style="width: 90px;"
                        name="hari[{{ $hari }}][kegiatan][{{ $i }}][jam]" value="{{ $keg['jam'] ?? '' }}"
                        min="1" max="12" placeholder="Jam">
                      <input type="number" class="form-control" style="width: 100px;"
                        name="hari[{{ $hari }}][kegiatan][{{ $i }}][menit]"
                        value="{{ $keg['menit'] ?? '' }}" min="1" max="180" placeholder="Menit">
                    </div>
                  @endfor
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="card-body border-top">
        <button type="submit" class="btn btn-primary">
          <i class="ri ri-save-line me-1"></i> Simpan Kerangka
        </button>
      </div>
    </div>
  </form>

@endsection
