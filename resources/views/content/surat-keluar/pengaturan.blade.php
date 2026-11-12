@extends('layouts/layoutMaster')

@section('title', 'Pengaturan Surat Keluar')

@section('content')

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row g-4">
    {{-- Format nomor --}}
    <div class="col-lg-5">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0"><i class="icon-base ri ri-hashtag me-2"></i>Format Nomor Surat</h5>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ route('surat-keluar.pengaturan.simpan') }}">
            @csrf
            <label class="form-label">Pola Format</label>
            <input type="text" name="format_nomor" class="form-control" maxlength="150"
              value="{{ old('format_nomor', $pengaturan->format_nomor) }}" required>
            <div class="form-text">
              Token yang tersedia: <code>{no}</code> nomor urut, <code>{no:3}</code> urut dengan nol di depan
              (001), <code>{kode}</code> kode kategori, <code>{bulan_romawi}</code>, <code>{bulan}</code>,
              <code>{tahun}</code>, <code>{tahun2}</code>.
            </div>
            <div class="alert alert-info mt-3 mb-3">
              Contoh nomor berikutnya: <strong>{{ $contohNomor }}</strong><br>
              <small>Penghitung berjalan per tahun kalender tanggal surat dan kembali ke 1 setiap tahun baru.
                Nomor terakhir terpakai: {{ $pengaturan->nomor_terakhir }}
                {{ $pengaturan->tahun_berjalan ? '(tahun ' . $pengaturan->tahun_berjalan . ')' : '' }}.
                Mengubah format tidak mengubah nomor yang sudah terbit.</small>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="icon-base ri ri-save-line me-1"></i>Simpan Format</button>
            <a href="{{ route('surat-keluar.index') }}" class="btn btn-outline-secondary ms-2">Kembali ke Agenda</a>
          </form>
        </div>
      </div>
    </div>

    {{-- Kategori --}}
    <div class="col-lg-7">
      <div class="card h-100">
        <div class="card-header">
          <h5 class="mb-0"><i class="icon-base ri ri-folder-3-line me-2"></i>Kategori Surat</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th style="width:80px">Kode</th>
                  <th>Nama</th>
                  <th class="text-center" style="width:90px">Dipakai</th>
                  <th class="text-end" style="width:130px">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($kategoris as $k)
                  <tr>
                    <td><span class="badge bg-label-primary">{{ $k->kode }}</span></td>
                    <td>{{ $k->nama }}</td>
                    <td class="text-center">{{ $k->surat_count }} surat</td>
                    <td class="text-end text-nowrap">
                      <button type="button" class="btn btn-sm btn-outline-primary btn-ubah-kategori"
                        data-id="{{ $k->id }}" data-kode="{{ $k->kode }}" data-nama="{{ $k->nama }}"
                        data-urutan="{{ $k->urutan }}">
                        <i class="icon-base ri ri-edit-line"></i></button>
                      <form method="POST" action="{{ route('surat-keluar.kategori.hapus', $k) }}" class="d-inline"
                        onsubmit="return confirm('Hapus kategori {{ $k->kode }}? Surat lama tetap tercatat, kategorinya menjadi kosong.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                          <i class="icon-base ri ri-delete-bin-line"></i></button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <hr>
          <h6 id="judulFormKategori">Tambah Kategori</h6>
          <form method="POST" action="{{ route('surat-keluar.kategori.simpan') }}" id="formKategori"
            class="row g-3 align-items-end">
            @csrf
            <input type="hidden" name="kategori_id" id="kategoriId" value="">
            <div class="col-md-3">
              <label class="form-label">Kode <span class="text-danger">*</span></label>
              <input type="text" name="kode" id="kategoriKode" class="form-control" maxlength="20"
                placeholder="Mis. KS" required>
            </div>
            <div class="col-md-5">
              <label class="form-label">Nama <span class="text-danger">*</span></label>
              <input type="text" name="nama" id="kategoriNama" class="form-control" maxlength="100"
                placeholder="Mis. Kesiswaan" required>
            </div>
            <div class="col-md-2">
              <label class="form-label">Urutan</label>
              <input type="number" name="urutan" id="kategoriUrutan" class="form-control" min="0" value="0">
            </div>
            <div class="col-md-2 d-flex gap-2">
              <button type="submit" class="btn btn-primary flex-grow-1">Simpan</button>
              <button type="button" class="btn btn-outline-secondary" id="btnBatalUbah" style="display:none">Batal</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

@endsection

@section('page-script')
  <script>
    (function() {
      const idEl = document.getElementById('kategoriId');
      const kodeEl = document.getElementById('kategoriKode');
      const namaEl = document.getElementById('kategoriNama');
      const urutanEl = document.getElementById('kategoriUrutan');
      const judul = document.getElementById('judulFormKategori');
      const btnBatal = document.getElementById('btnBatalUbah');
      document.querySelectorAll('.btn-ubah-kategori').forEach(function(btn) {
        btn.addEventListener('click', function() {
          idEl.value = btn.dataset.id;
          kodeEl.value = btn.dataset.kode;
          namaEl.value = btn.dataset.nama;
          urutanEl.value = btn.dataset.urutan;
          judul.textContent = 'Ubah Kategori';
          btnBatal.style.display = '';
          kodeEl.focus();
        });
      });
      btnBatal.addEventListener('click', function() {
        idEl.value = '';
        kodeEl.value = '';
        namaEl.value = '';
        urutanEl.value = 0;
        judul.textContent = 'Tambah Kategori';
        btnBatal.style.display = 'none';
      });
    })();
  </script>
@endsection
