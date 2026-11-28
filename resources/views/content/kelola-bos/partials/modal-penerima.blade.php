{{--
  Modal editor penerima SPJ (Fase 2) — daftar penerima
  per grup transaksi BKU (grup yang sama dengan kwitansi:
  per nota / nomor bukti).

  Bentuk baru (2026-11-28, atas rancangan user): tabel
  penerima HANYA TAMPILAN — kolom Nama Penerima,
  Jumlah, Pajak, Biaya Admin, Diterima, Rekening,
  Status, dan Aksi Edit/Hapus; tidak ada isian
  langsung di tabel karena terlalu sempit. Isian dan
  ubahan lewat MODAL FORMULIR tersendiri
  (#modal-penerima-form) dengan nama combobox dari
  tiga sumber (PTK, tabel penerima, TOKO). Kategori
  grup berupa tiga saklar tombol (Barang / Konsumsi /
  Non-barang) di blok Keterangan SPJ. Status di
  kepala modal hanya LABEL turunan (Selesai /
  Selesai Sebagian / Belum Selesai) sesuai status di
  tabel BKU; saklar status grup untuk grup tanpa
  penerima tetap ada di modal Isi Grup (klik uraian
  nota). Penyempurnaan berikutnya di hari yang sama:
  modal berganti nama Penerimaan SPJ; kolom Status
  tabel berupa SAKLAR selesai per penerima (peran
  tulis); formulir kehilangan saklar Selesai, jenis
  menjadi grup tombol radio di bagian atas, dan di
  samping nama ada ikon tambah penerima ke database
  (tabel penerima). Ditambah modal Daftar Penerima
  (tombol di modal ini): tab PTK hanya tampil, tab
  Orang dan tab Toko adalah induk yang bisa ditambah
  / diubah / dihapus — toko kini induk penuh seperti
  non-PTK (kolom jenis + pemilik / NPWP / no HP di
  tabel_penerima), dan induk toko masuk kelompok
  Toko pada combobox formulir. Baris bawaan (nama toko + nominal grup)
  tampil sebagai penanda "Belum tersimpan" bila grup
  belum punya penerima; tombol Edit-nya membuka
  formulir berisi bawaan itu, Hapus hanya membuang
  penandanya tanpa menyimpan apa pun.

  Semua data lewat endpoint JSON
  /kelola-bos/bku/{id}/penerima... — konteks sekolah
  berasal dari baris itu di sisi server. Endpoint
  server dan data tersimpan tidak berubah: selesai
  per penerima tetap lewat endpoint khusus
  .../penerima/{pid}/selesai karena bukan bagian
  aturan snapshot. Kepala Sekolah boleh membuka
  (baca); Bendahara boleh membuka dan mengubah
  penanda status selesai saja. Panel tulis penuh
  hanya dirender untuk Admin & Operator Sekolah
  ($bisaTulisPenerima); izin status terpisah di
  $bisaUbahStatus.
--}}
<div class="modal fade" id="modal-penerima" tabindex="-1" data-bisa-tulis="{{ $bisaTulisPenerima ? '1' : '0' }}"
  data-bisa-status="{{ $bisaUbahStatus ? '1' : '0' }}">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri ri-team-line me-2"></i>Penerimaan SPJ</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-secondary py-2 px-3" id="penerima-info-grup">
          <span class="text-muted">Memuat…</span>
        </div>

        {{-- Meta grup SPJ: status hanya LABEL turunan,
             kategori tiga saklar tombol, dan nomor VA
             untuk grup SIPLah (isian manual karena data
             ARKAS/SIPLah hanya menyediakan nomor
             invoice). Peran baca melihat lencana teks. --}}
        <div class="border rounded p-3 mb-3" id="penerima-meta-grup">
          <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="fw-semibold">Keterangan SPJ</span>
            <span id="meta-siplah-info"></span>
            <span class="small text-success d-none" id="meta-simpan-info"><i
                class="ri ri-check-line me-1"></i>Tersimpan</span>
          </div>
          <div class="d-flex flex-wrap gap-4 mt-2">
            <div>
              <div class="small text-muted mb-1">Status</div>
              <span id="meta-status-baca"></span>
              <div class="form-text" id="meta-status-info"></div>
            </div>
            <div>
              <div class="small text-muted mb-1">Kategori</div>
              @if ($bisaTulisPenerima)
                <div class="btn-group btn-group-sm" id="meta-kategori-saklar" role="group" aria-label="Kategori SPJ">
                  <button type="button" class="btn btn-outline-secondary" data-kategori="barang">Barang</button>
                  <button type="button" class="btn btn-outline-secondary" data-kategori="barang_konsumsi">Konsumsi</button>
                  <button type="button" class="btn btn-outline-secondary" data-kategori="non_barang">Non-barang</button>
                </div>
                <div class="form-text" id="meta-kategori-info"></div>
                <button type="button" class="btn btn-link btn-sm p-0 d-none" id="meta-kategori-otomatis">Ikuti deteksi otomatis</button>
              @endif
              <span id="meta-kategori-baca"></span>
            </div>
            <div id="meta-va-wrap" class="d-none" style="min-width: 280px;">
              <label class="form-label small text-muted mb-1" for="meta-nomor-va">Nomor Virtual Account</label>
              @if ($bisaTulisPenerima)
                <div class="input-group input-group-sm">
                  <input type="text" class="form-control" id="meta-nomor-va" maxlength="50"
                    placeholder="Nomor VA pembayaran SIPLah">
                  <button type="button" class="btn btn-primary" id="btn-simpan-va">Simpan VA</button>
                </div>
              @else
                <div class="fw-semibold" id="meta-va-baca">-</div>
              @endif
            </div>
          </div>
          <div class="form-text mt-2">
            @if ($bisaTulisPenerima)
              Status grup mengikuti penanda selesai tiap penerima di tabel bawah; untuk grup tanpa penerima,
              status diatur lewat Isi Grup (klik uraian nota).
            @endif
            Nomor VA diisi manual — data ARKAS/SIPLah hanya menyediakan nomor invoice.
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle mb-2">
            <thead>
              <tr>
                <th style="width: 3rem;">No</th>
                <th>Nama Penerima</th>
                <th class="text-end" style="width: 10rem;">Jumlah</th>
                <th class="text-end" style="width: 10rem;">Pajak</th>
                <th class="text-end" style="width: 9rem;">Biaya Admin</th>
                <th class="text-end" style="width: 10rem;">Diterima</th>
                <th style="width: 11rem;">Rekening</th>
                <th style="width: 8rem;">Status</th>
                @if ($bisaTulisPenerima)
                  <th style="width: 6rem;">Aksi</th>
                @endif
              </tr>
            </thead>
            <tbody id="penerima-tbody">
              <tr>
                <td colspan="{{ $bisaTulisPenerima ? 9 : 8 }}" class="text-center text-muted">Memuat…</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="fw-semibold">
                <td colspan="2" class="text-end">Total</td>
                <td class="text-end" id="tot-jumlah">Rp 0</td>
                <td class="text-end" id="tot-pajak">Rp 0</td>
                <td class="text-end" id="tot-admin">Rp 0</td>
                <td class="text-end" id="tot-diterima">Rp 0</td>
                <td colspan="{{ $bisaTulisPenerima ? 3 : 2 }}"></td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div class="small mb-3" id="penerima-selisih"></div>

        <div class="mb-3">
          <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-daftar-penerima">
            <i class="ri ri-book-2-line me-1"></i>Daftar Penerima</button>
          @if ($bisaTulisPenerima)
            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-baris-penerima-baru">
              <i class="ri ri-add-line me-1"></i>Tambah Penerimaan</button>
            <span class="form-text ms-2">Isian lewat formulir sendiri — klik Tambah atau ikon Edit pada baris.</span>
          @endif
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

{{--
  Modal formulir penerimaan tersendiri: dibuka dari
  tombol Tambah Penerimaan atau ikon Edit pada baris
  tampilan. Formulirnya lega (bukan isian di sel tabel
  yang sempit): jenis berupa grup tombol radio
  Orang/Toko di bagian atas, nama combobox dengan
  ikon tambah ke database (tabel penerima) di
  sampingnya, isian perorangan, volume & satuan,
  potongan menurut kategori grup, rekening & bank.
  Penanda selesai TIDAK diatur di sini — saklarnya
  ada di kolom Status tabel tampilan. Disimpan lewat
  tombol Simpan — endpoint POST / PUT yang sama
  seperti sebelumnya, tanpa migrasi baru.
--}}
<div class="modal fade" id="modal-penerima-form" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="form-judul">Tambah Penerimaan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label d-block">Jenis Penerima</label>
            <div class="btn-group" role="group" id="form-jenis-grup" aria-label="Jenis penerima">
              <button type="button" class="btn btn-outline-primary" data-jenis="orang"><i
                  class="ri ri-user-line me-1"></i>Orang</button>
              <button type="button" class="btn btn-outline-primary" data-jenis="toko"><i
                  class="ri ri-store-2-line me-1"></i>Toko</button>
            </div>
            <span id="form-lencana-asal" class="ms-2"></span>
          </div>
          <div class="col-12">
            <label class="form-label" for="form-nama">Nama Penerima</label>
            <div class="position-relative">
              <div class="input-group">
                <input type="text" class="form-control" id="form-nama"
                  placeholder="Ketik nama atau pilih dari daftar (PTK, tabel penerima, toko)" autocomplete="off">
                <button type="button" class="btn btn-outline-success d-none" id="form-tambah-induk"
                  title="Tambah penerima baru ke daftar penerima"><i class="ri ri-user-add-line"></i></button>
              </div>
              <div class="list-group shadow d-none" id="form-daftar-nama"
                style="position: absolute; top: 100%; left: 0; right: 0; margin-top: 2px; z-index: 1056; max-height: 230px; overflow-y: auto;"></div>
            </div>
            <div class="small text-danger d-none" id="form-nama-galat">Nama penerima wajib diisi.</div>
          </div>
          <div class="col-12" id="form-sub-orang">
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label" for="form-nip">NIP</label>
                <input type="text" class="form-control" id="form-nip" maxlength="18" placeholder="NIP">
              </div>
              <div class="col-md-4">
                <label class="form-label" for="form-pangkat">Pangkat / Golongan</label>
                <input type="text" class="form-control" id="form-pangkat" placeholder="Pangkat / Golongan">
              </div>
              <div class="col-md-4">
                <label class="form-label" for="form-nik">NIK</label>
                <input type="text" class="form-control" id="form-nik" maxlength="16" placeholder="NIK (16 digit)">
              </div>
            </div>
          </div>
          <div class="col-12 d-none" id="form-sub-toko">
            <div class="small text-muted" id="form-toko-info">Toko</div>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="form-volume">Volume</label>
            <input type="number" class="form-control text-end" id="form-volume" min="0" step="0.01" value="1">
          </div>
          <div class="col-md-4">
            <label class="form-label" for="form-satuan">Harga Satuan (Rp)</label>
            <input type="number" class="form-control text-end" id="form-satuan" min="0" step="0.01" value="0">
          </div>
          <div class="col-md-4">
            <label class="form-label">Jumlah</label>
            <div class="form-control-plaintext fw-semibold text-end" id="form-jumlah-tampil">Rp 0</div>
          </div>
          <div class="col-md-3" data-kolom-pajak="ppn">
            <label class="form-label" for="form-ppn">PPN (Rp)</label>
            <input type="number" class="form-control text-end" id="form-ppn" min="0" step="0.01" value="0">
          </div>
          <div class="col-md-3" data-kolom-pajak="pph">
            <label class="form-label" for="form-pph">PPh (Rp)</label>
            <input type="number" class="form-control text-end" id="form-pph" min="0" step="0.01" value="0">
          </div>
          <div class="col-md-3" data-kolom-pajak="sspd">
            <label class="form-label" for="form-sspd">SSPD (Rp)</label>
            <input type="number" class="form-control text-end" id="form-sspd" min="0" step="0.01" value="0">
          </div>
          <div class="col-md-3" data-kolom-pajak="admin">
            <label class="form-label" for="form-admin">Biaya Admin (Rp)</label>
            <input type="number" class="form-control text-end" id="form-admin" min="0" step="0.01" value="0">
          </div>
          <div class="col-12">
            <div class="d-flex justify-content-between border-top pt-2">
              <span class="fw-semibold">Diterima</span>
              <span class="fw-semibold" id="form-diterima-tampil">Rp 0</span>
            </div>
            <div class="form-text">Diterima = Jumlah &minus; PPN &minus; PPh &minus; SSPD &minus; Biaya Admin.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="form-rekening">No. Rekening</label>
            <input type="text" class="form-control" id="form-rekening" maxlength="30" placeholder="No. Rekening">
          </div>
          <div class="col-md-6">
            <label class="form-label" for="form-bank">Nama Bank</label>
            <input type="text" class="form-control" id="form-bank" placeholder="Nama Bank">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="form-btn-simpan">Simpan Penerimaan</button>
      </div>
    </div>
  </div>
</div>

{{--
  Modal Daftar Penerima: satu tempat melihat semua
  sumber penerima. Tiga tab — PTK (hanya tampil,
  dikelola di menu PTK; peran tulis mendapat tautan
  ubah), Orang non-PTK (induk orang; tambah / ubah /
  hapus penuh), dan Toko (induk jenis toko; tambah /
  ubah / hapus penuh dengan rincian pemilik, NPWP,
  alamat, no HP) — plus daftar nama toko dari nota
  ARKAS yang hanya tampil. Perubahan induk
  menyegarkan sumber combobox formulir penerimaan;
  snapshot yang sudah tersimpan di grup tidak ikut
  berubah.
--}}
<div class="modal fade" id="modal-daftar-penerima" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri ri-book-2-line me-2"></i>Daftar Penerima</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <ul class="nav nav-tabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-daftar-ptk"
              type="button" role="tab">PTK</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-daftar-orang"
              type="button" role="tab">Orang (Non-PTK)</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-daftar-toko"
              type="button" role="tab">Toko</button>
          </li>
        </ul>
        <div class="tab-content pt-3">
          <div class="tab-pane fade show active" id="tab-daftar-ptk" role="tabpanel">
            <p class="small text-muted">Data guru / pegawai dikelola di menu PTK — di sini hanya tampil
              sebagai sumber penerima.</p>
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle">
                <thead>
                  <tr>
                    <th>Nama</th>
                    <th>NIP</th>
                    <th>Pangkat/Gol</th>
                    <th>NIK</th>
                    <th>Rekening</th>
                    @if ($bisaTulisPenerima)
                      <th class="text-end">Aksi</th>
                    @endif
                  </tr>
                </thead>
                <tbody id="daftar-ptk-tbody"></tbody>
              </table>
            </div>
          </div>
          <div class="tab-pane fade" id="tab-daftar-orang" role="tabpanel">
            @if ($bisaTulisPenerima)
              <div class="d-flex justify-content-end mb-2">
                <button type="button" class="btn btn-sm btn-primary" id="btn-tambah-induk-orang">
                  <i class="ri ri-add-line me-1"></i>Tambah Penerima</button>
              </div>
            @endif
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle">
                <thead>
                  <tr>
                    <th>Nama</th>
                    <th>NIK</th>
                    <th>NIP</th>
                    <th>Pangkat/Gol</th>
                    <th>Rekening</th>
                    @if ($bisaTulisPenerima)
                      <th class="text-end">Aksi</th>
                    @endif
                  </tr>
                </thead>
                <tbody id="daftar-orang-tbody"></tbody>
              </table>
            </div>
          </div>
          <div class="tab-pane fade" id="tab-daftar-toko" role="tabpanel">
            @if ($bisaTulisPenerima)
              <div class="d-flex justify-content-end mb-2">
                <button type="button" class="btn btn-sm btn-primary" id="btn-tambah-induk-toko">
                  <i class="ri ri-add-line me-1"></i>Tambah Toko</button>
              </div>
            @endif
            <div class="table-responsive">
              <table class="table table-sm table-hover align-middle">
                <thead>
                  <tr>
                    <th>Nama Toko</th>
                    <th>Pemilik</th>
                    <th>NPWP</th>
                    <th>Alamat</th>
                    <th>No HP</th>
                    <th>Rekening</th>
                    @if ($bisaTulisPenerima)
                      <th class="text-end">Aksi</th>
                    @endif
                  </tr>
                </thead>
                <tbody id="daftar-toko-tbody"></tbody>
              </table>
            </div>
          </div>
        </div>
        @if ($bisaTulisPenerima)
          <div class="card border mt-3 d-none" id="induk-form-wrap">
            <div class="card-body">
              <h6 id="induk-form-judul" class="mb-3">Tambah Penerima</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="induk-nama" id="induk-nama-label">Nama</label>
                  <input type="text" class="form-control" id="induk-nama" autocomplete="off">
                  <div class="small text-danger d-none" id="induk-nama-galat">Nama wajib diisi.</div>
                </div>
                <div class="col-md-6 induk-khusus-toko">
                  <label class="form-label" for="induk-pemilik">Pemilik</label>
                  <input type="text" class="form-control" id="induk-pemilik" autocomplete="off">
                </div>
                <div class="col-md-4 induk-khusus-orang">
                  <label class="form-label" for="induk-nik">NIK</label>
                  <input type="text" class="form-control" id="induk-nik" maxlength="16"
                    inputmode="numeric" autocomplete="off">
                </div>
                <div class="col-md-4 induk-khusus-orang">
                  <label class="form-label" for="induk-nip">NIP</label>
                  <input type="text" class="form-control" id="induk-nip" autocomplete="off">
                </div>
                <div class="col-md-4 induk-khusus-orang">
                  <label class="form-label" for="induk-pangkat">Pangkat / Gol</label>
                  <input type="text" class="form-control" id="induk-pangkat" autocomplete="off">
                </div>
                <div class="col-md-4 induk-khusus-toko">
                  <label class="form-label" for="induk-npwp">NPWP</label>
                  <input type="text" class="form-control" id="induk-npwp" autocomplete="off">
                </div>
                <div class="col-md-4 induk-khusus-toko">
                  <label class="form-label" for="induk-nohp">No HP</label>
                  <input type="text" class="form-control" id="induk-nohp" autocomplete="off">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="induk-alamat">Alamat</label>
                  <input type="text" class="form-control" id="induk-alamat" autocomplete="off">
                </div>
                <div class="col-md-3">
                  <label class="form-label" for="induk-rekening">No Rekening</label>
                  <input type="text" class="form-control" id="induk-rekening" autocomplete="off">
                </div>
                <div class="col-md-3">
                  <label class="form-label" for="induk-bank">Nama Bank</label>
                  <input type="text" class="form-control" id="induk-bank" autocomplete="off">
                </div>
              </div>
              <div class="d-flex justify-content-end gap-2 mt-3">
                <button type="button" class="btn btn-outline-secondary" id="induk-btn-batal">Batal</button>
                <button type="button" class="btn btn-primary" id="induk-btn-simpan">Simpan</button>
              </div>
            </div>
          </div>
        @endif
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<style>
  /* Formulir penerima & daftar penerima bertumpuk di
     atas modal penerima: naikkan lapisannya agar tidak
     tertutup backdrop modal di bawahnya. */
  #modal-penerima-form,
  #modal-daftar-penerima {
    z-index: 1090;
  }
</style>

<script>
  (function() {
    var modalEl = document.getElementById('modal-penerima');
    if (!modalEl) return;
    var formEl = document.getElementById('modal-penerima-form');
    var daftarEl = document.getElementById('modal-daftar-penerima');

    var bisaTulis = modalEl.getAttribute('data-bisa-tulis') === '1';
    // Bendahara: hanya saklar status selesai yang
    // boleh digerakkan (tanpa tulis penuh).
    var bisaStatus = modalEl.getAttribute('data-bisa-status') === '1';
    var bolehSaklar = bisaTulis || bisaStatus;
    var urlDasar = @json(url('/kelola-bos/bku'));
    var urlPtkDasar = @json(url('/ptk'));
    var barisId = null;
    var data = null;

    // Baris bawaan (belum tersimpan) untuk grup yang
    // belum punya penerima: nama toko + nominal grup.
    // null bila tidak ada / sudah dibuang / sudah
    // tersimpan lewat formulir.
    var bawaan = null;

    // Keadaan formulir penerima yang sedang dibuka:
    // formEditId null = tambah baru; formBawaan true =
    // baris bawaan yang sedang diedit (disimpan lewat
    // POST seperti tambah baru).
    var formEditId = null;
    var formBawaan = false;

    // Penanda modal Daftar Penerima dibuka dari ikon
    // tambah di formulir penerimaan: setelah induk
    // baru tersimpan di sana, formulir langsung
    // tertaut ke induk tersebut.
    var indukDariForm = false;
    // Keadaan formulir induk pada modal Daftar
    // Penerima: id induk yang sedang diubah (null =
    // tambah baru) dan jenis tab aktifnya.
    var indukEditId = null;
    var indukJenis = 'orang';
    var formTautan = {
      ptkId: '',
      penerimaId: '',
      namaTertaut: ''
    };

    function $(id) {
      return document.getElementById(id);
    }

    function csrfToken() {
      return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    }

    function lolosHtml(teks) {
      return String(teks ?? '').replace(/[&<>"']/g, function(huruf) {
        return {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#39;'
        }[huruf];
      });
    }

    function fmtRp(v) {
      return 'Rp ' + Math.round(Number(v) || 0).toLocaleString('id-ID');
    }

    function fmtAngka(v) {
      return Math.round(Number(v) || 0).toLocaleString('id-ID');
    }

    function fmtVol(v) {
      return (Number(v) || 0).toLocaleString('id-ID', {
        maximumFractionDigits: 2
      });
    }

    function subInfo(bagian) {
      return bagian.filter(Boolean).map(lolosHtml).join(' &bull; ');
    }

    async function kirim(url, method, payload) {
      var res = await fetch(url, {
        method: method,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: payload === undefined ? undefined : JSON.stringify(payload)
      });
      var json = await res.json().catch(function() {
        return {};
      });
      if (!res.ok) {
        var pesan = json.message || 'Terjadi kesalahan.';
        if (json.errors) {
          var pertama = Object.values(json.errors)[0];
          if (Array.isArray(pertama) && pertama[0]) pesan = pertama[0];
        }
        throw new Error(pesan);
      }
      return json;
    }

    function gagal(e) {
      Swal.fire('Gagal', (e && e.message) || 'Terjadi kesalahan.', 'error');
    }

    async function konfirmasiHapus(judul, teks) {
      var r = await Swal.fire({
        title: judul,
        text: teks,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
      });
      return r.isConfirmed;
    }

    function segarkanLencana(jml, daftarNama) {
      var btn = document.querySelector('[data-penerima-baris][data-id="' + barisId + '"]');
      if (!btn) return;
      var lencana = btn.querySelector('.lencana-penerima');
      if (jml > 0) {
        if (!lencana) {
          lencana = document.createElement('span');
          lencana.className = 'badge rounded-pill bg-success lencana-penerima';
          lencana.style.cssText = 'font-size: .6rem; vertical-align: super;';
          btn.appendChild(lencana);
        }
        lencana.textContent = jml;
      } else if (lencana) {
        lencana.remove();
      }

      // Segarkan nama tampil pada sel yang sama (kolom
      // Toko/Penerima): bila grup punya penerima, tampilkan
      // nama penerimanya saja; nama toko asli BKU hanya
      // tampil bila belum ada penerima.
      var sel = btn.closest('td');
      var elNama = sel ? sel.querySelector('.nama-tampil-baris') : null;
      if (!elNama) return;
      var namaToko = elNama.getAttribute('data-nama-toko') || '';
      if (daftarNama && daftarNama.length) {
        elNama.innerHTML = lolosHtml(daftarNama[0]) +
          (daftarNama.length > 1
            ? ' <span class="text-muted small">+' + (daftarNama.length - 1) + ' lainnya</span>'
            : '');
      } else {
        elNama.textContent = namaToko || '-';
      }
    }

    function gambarInfo() {
      var g = data.grup;
      $('penerima-info-grup').innerHTML =
        '<div class="fw-semibold">' + lolosHtml(g.uraian || '-') + '</div>' +
        '<div class="small text-muted">No bukti: ' + lolosHtml(g.no_bukti || '-') +
        ' &middot; Tanggal: ' + lolosHtml(g.tanggal || '-') +
        ' &middot; Total grup (target): <span class="fw-semibold text-body">' + fmtRp(g.total) +
        '</span></div>';
    }

    // ── Kategori grup & kolom potongan dinamis ──
    function labelKategori(k) {
      if (k === 'non_barang') return 'Non-barang';
      if (k === 'barang_konsumsi') return 'Barang Konsumsi';
      return 'Barang';
    }

    function kelasKategori(k) {
      if (k === 'non_barang') return 'bg-warning text-dark';
      if (k === 'barang_konsumsi') return 'bg-info';
      return 'bg-label-secondary';
    }

    // Bidang potongan yang tampil di formulir menurut
    // kategori grup efektif: barang -> PPN; barang
    // konsumsi -> PPh & SSPD; non-barang -> PPh.
    // Biaya admin selalu tampil.
    function bidangPajakTampil() {
      var kat = (data && data.grup && data.grup.kategori) || 'barang';
      return {
        ppn: kat === 'barang',
        pph: kat === 'barang_konsumsi' || kat === 'non_barang',
        sspd: kat === 'barang_konsumsi',
        admin: true
      };
    }

    // Tampilkan / sembunyikan pembungkus isian potongan
    // di formulir menurut kategori grup efektif.
    function terapkanKolomPajak() {
      if (!formEl) return;
      var tampil = bidangPajakTampil();
      ['ppn', 'pph', 'sspd', 'admin'].forEach(function(bidang) {
        formEl.querySelectorAll('[data-kolom-pajak="' + bidang + '"]').forEach(function(el) {
          el.classList.toggle('d-none', !tampil[bidang]);
        });
      });
    }

    // Lencana status tampil grup: selesai -> hijau,
    // sebagian -> kuning, belum -> abu.
    function lencanaStatusHtml(statusTampil) {
      if (statusTampil === 'selesai') {
        return '<span class="badge bg-success">Selesai</span>';
      }
      if (statusTampil === 'sebagian') {
        return '<span class="badge bg-warning text-dark">Selesai Sebagian</span>';
      }
      return '<span class="badge bg-secondary">Belum Selesai</span>';
    }

    // ── Meta grup: label status, saklar kategori, VA ──
    function gambarMeta() {
      var g = data.grup || {};

      var info = '';
      if (g.siplah) {
        info = '<span class="badge bg-info">SIPLah</span>';
        if (g.invoice_siplah) {
          info += ' <span class="small text-muted">Invoice: ' + lolosHtml(g.invoice_siplah) + '</span>';
        }
      }
      $('meta-siplah-info').innerHTML = info;

      // Status kepala modal HANYA LABEL turunan — sama
      // dengan lencana di tabel BKU: semua penerima
      // selesai -> Selesai, sebagian -> Selesai
      // Sebagian, belum ada yang selesai -> Belum
      // Selesai. Grup tanpa penerima memakai status
      // meta tersimpan; saklarnya ada di modal Isi Grup.
      var stGrup = g.status_tampil || 'belum';
      $('meta-status-baca').innerHTML = lencanaStatusHtml(stGrup);
      var jml = Number(g.jumlah_penerima || 0);
      var sls = Number(g.jumlah_selesai || 0);
      $('meta-status-info').textContent = jml > 0 ?
        sls + ' dari ' + jml + ' penerima selesai' :
        (bolehSaklar ? 'Diatur lewat Isi Grup (klik uraian nota).' : 'Belum ada penerima.');

      // Kategori grup: tiga saklar tombol (peran tulis)
      // — yang aktif mengikuti kategori efektif; tautan
      // "Ikuti deteksi otomatis" hanya muncul bila
      // kategori sedang dioverride manual. Peran baca
      // melihat lencana.
      if (bisaTulis) {
        var katEfektif = g.kategori || 'barang';
        var saklar = $('meta-kategori-saklar');
        if (saklar) {
          saklar.querySelectorAll('[data-kategori]').forEach(function(btn) {
            var aktif = btn.getAttribute('data-kategori') === katEfektif;
            btn.classList.toggle('btn-primary', aktif);
            btn.classList.toggle('btn-outline-secondary', !aktif);
            btn.setAttribute('aria-pressed', aktif ? 'true' : 'false');
          });
        }
        var infoKat = $('meta-kategori-info');
        if (infoKat) {
          infoKat.textContent = 'Deteksi otomatis: ' + labelKategori(g.kategori_deteksi) +
            ' (dari kode rekening).' +
            ((g.kategori_manual !== null && g.kategori_manual !== undefined) ?
              ' Sedang diatur manual.' :
              '');
        }
        var tautanOtomatis = $('meta-kategori-otomatis');
        if (tautanOtomatis) {
          tautanOtomatis.classList.toggle('d-none',
            g.kategori_manual === null || g.kategori_manual === undefined);
        }
      } else {
        $('meta-kategori-baca').innerHTML =
          '<span class="badge ' + kelasKategori(g.kategori) + '">' +
          labelKategori(g.kategori) + '</span>';
      }

      // Kolom VA hanya untuk grup SIPLah.
      var vaWrap = $('meta-va-wrap');
      if (g.siplah) {
        vaWrap.classList.remove('d-none');
        if (bisaTulis) {
          $('meta-nomor-va').value = g.nomor_va || '';
        } else {
          $('meta-va-baca').textContent = g.nomor_va || '-';
        }
      } else {
        vaWrap.classList.add('d-none');
      }

      terapkanKolomPajak();
    }

    // Lukis lencana status tampil pada wadah meta satu
    // baris BKU menurut status_tampil grup. Lencana
    // selalu ada (dibuat bila belum ada di wadah).
    function gambarLencanaStatus(wadah, statusTampil) {
      if (!wadah) return;
      var lencanaStatus = wadah.querySelector('.lencana-status-grup');
      if (!lencanaStatus) {
        lencanaStatus = document.createElement('span');
        var vaEl = wadah.querySelector('.lencana-va-grup');
        if (vaEl) {
          wadah.insertBefore(lencanaStatus, vaEl);
        } else {
          wadah.appendChild(lencanaStatus);
        }
      }
      if (statusTampil === 'selesai') {
        lencanaStatus.className = 'badge lencana-status-grup bg-success';
        lencanaStatus.textContent = 'Selesai';
      } else if (statusTampil === 'sebagian') {
        lencanaStatus.className = 'badge lencana-status-grup bg-warning text-dark';
        lencanaStatus.textContent = 'Selesai Sebagian';
      } else {
        lencanaStatus.className = 'badge lencana-status-grup bg-secondary';
        lencanaStatus.textContent = 'Belum Selesai';
      }
    }

    // Perbarui lencana status, kategori & VA pada sel
    // baris BKU yang membuka modal ini, tanpa memuat
    // ulang halaman.
    function segarkanMetaBaris() {
      var btn = document.querySelector('[data-penerima-baris][data-id="' + barisId + '"]');
      if (!btn) return;
      var sel = btn.closest('td');
      var wadah = sel ? sel.querySelector('.meta-grup-baris') : null;
      if (!wadah) return;
      var g = data.grup || {};

      gambarLencanaStatus(wadah, g.status_tampil);

      var lencanaKat = wadah.querySelector('.lencana-kategori-grup');
      if ((g.kategori || 'barang') !== 'barang') {
        if (!lencanaKat) {
          lencanaKat = document.createElement('span');
          var statusEl = wadah.querySelector('.lencana-status-grup');
          var vaElKat = wadah.querySelector('.lencana-va-grup');
          if (statusEl) {
            wadah.insertBefore(lencanaKat, statusEl);
          } else if (vaElKat) {
            wadah.insertBefore(lencanaKat, vaElKat);
          } else {
            wadah.appendChild(lencanaKat);
          }
        }
        lencanaKat.className = 'badge lencana-kategori-grup ' + kelasKategori(g.kategori);
        lencanaKat.textContent = labelKategori(g.kategori);
      } else if (lencanaKat) {
        lencanaKat.remove();
      }

      var lencanaVa = wadah.querySelector('.lencana-va-grup');
      if (g.nomor_va) {
        if (!lencanaVa) {
          lencanaVa = document.createElement('span');
          lencanaVa.className = 'small text-muted lencana-va-grup';
          wadah.appendChild(lencanaVa);
        }
        lencanaVa.textContent = 'VA: ' + g.nomor_va;
      } else if (lencanaVa) {
        lencanaVa.remove();
      }
    }

    function tandaiMetaTersimpan() {
      var el = $('meta-simpan-info');
      if (!el) return;
      el.classList.remove('d-none');
      clearTimeout(tandaiMetaTersimpan._t);
      tandaiMetaTersimpan._t = setTimeout(function() {
        el.classList.add('d-none');
      }, 2000);
    }

    // Simpan meta grup (status saat ini + VA): status
    // wajib di server sehingga selalu ikut dikirim apa
    // adanya; nomor VA hanya disentuh bila kuncinya
    // dikirim. Dipakai tombol Simpan VA — saklar status
    // grup tidak lagi ada di modal ini.
    async function simpanMeta(status, nomorVa) {
      try {
        var res = await kirim(urlDasar + '/' + barisId + '/penerima-grup', 'PUT', {
          status: status,
          nomor_va: nomorVa || null
        });
        data.grup.status = res.status;
        data.grup.nomor_va = res.nomor_va;
        data.grup.status_tampil = res.status_tampil;
        data.grup.jumlah_penerima = res.jumlah_penerima;
        data.grup.jumlah_selesai = res.jumlah_selesai;
        gambarMeta();
        segarkanMetaBaris();
        tandaiMetaTersimpan();
      } catch (e) {
        gambarMeta(); // kembalikan isian ke nilai yang tersimpan
        gagal(e);
      }
    }

    // Simpan kategori grup lewat endpoint meta yang sama
    // (status saat ini ikut dikirim karena status wajib
    // di server; nomor VA tidak dikirim agar tidak
    // tertimpa). kategori: 'barang' |
    // 'barang_konsumsi' | 'non_barang', atau null
    // (tautan "Ikuti deteksi otomatis") untuk kembali
    // ikut deteksi.
    async function simpanKategori(kategori) {
      if (!data || !data.grup) return;
      try {
        var res = await kirim(urlDasar + '/' + barisId + '/penerima-grup', 'PUT', {
          status: data.grup.status,
          kategori: kategori || null
        });
        data.grup.kategori = res.kategori;
        data.grup.kategori_manual = res.kategori_manual;
        data.grup.kategori_deteksi = res.kategori_deteksi;
        data.grup.status_tampil = res.status_tampil;
        data.grup.jumlah_penerima = res.jumlah_penerima;
        data.grup.jumlah_selesai = res.jumlah_selesai;
        gambarMeta();
        segarkanMetaBaris();
        tandaiMetaTersimpan();
      } catch (e) {
        gambarMeta(); // kembalikan saklar ke nilai yang tersimpan
        gagal(e);
      }
    }

    // Status tampil grup diturunkan ulang dari model
    // penerima lokal — dipakai sesudah baris ditambah /
    // dihapus / diubah agar blok Keterangan & lencana
    // baris BKU segar tanpa memuat ulang data dari
    // server.
    function segarkanStatusTurunan() {
      var daftar = data.penerima || [];
      var jml = daftar.length;
      var selesai = daftar.filter(function(p) {
        return !!p.selesai;
      }).length;
      data.grup.jumlah_penerima = jml;
      data.grup.jumlah_selesai = selesai;
      data.grup.status_tampil = jml === 0 ?
        (data.grup.status || 'belum') :
        (selesai >= jml ? 'selesai' : (selesai > 0 ? 'sebagian' : 'belum'));
      gambarMeta();
      segarkanMetaBaris();
    }

    // ── Tabel penerima: HANYA TAMPILAN ──

    // Rincian pajak satu penerima (bagian yang
    // terisi saja), mis. "PPN 10.000 • PPh 5.000".
    function rincianPajak(p) {
      var bagian = [];
      if (Number(p.ppn || 0) > 0) bagian.push('PPN ' + fmtAngka(p.ppn));
      if (Number(p.pph || 0) > 0) bagian.push('PPh ' + fmtAngka(p.pph));
      if (Number(p.sspd || 0) > 0) bagian.push('SSPD ' + fmtAngka(p.sspd));
      return bagian.join(' &bull; ');
    }

    // NPWP toko dari daftar sumber toko bila nama
    // baris cocok persis (snapshot toko tidak
    // menyimpan NPWP).
    function npwpToko(nama) {
      var cari = (nama || '').trim().toLowerCase();
      if (!cari) return null;
      var cocok = sumberGabungan.find(function(s) {
        return s.sumber === 'Toko' && s.nama.toLowerCase() === cari;
      });
      return cocok ? (cocok.npwp || null) : null;
    }

    // Sel status satu baris tampilan: peran tulis
    // mendapat SAKLAR yang langsung menyimpan penanda
    // selesai penerima lewat endpoint khusus (permintaan
    // user: status di tabel tetap saklar, bukan hanya
    // label); baris bawaan yang belum tersimpan
    // saklarnya nonaktif; peran tanpa izin status
    // (mis. Kepala Sekolah) melihat lencana. Bendahara
    // termasuk bolehSaklar: saklar aktif, tanpa aksi
    // ubah/hapus.
    function selStatusHtml(p, isBawaan) {
      if (!bolehSaklar) {
        return p.selesai ?
          '<span class="badge bg-success">Selesai</span>' :
          '<span class="badge bg-secondary">Belum</span>';
      }
      return '<div class="form-check form-switch mb-0">' +
        '<input class="form-check-input" type="checkbox" role="switch" data-selesai-id="' +
        (isBawaan ? '' : p.id) + '"' + (p.selesai ? ' checked' : '') +
        (isBawaan ? ' disabled title="Simpan dulu lewat tombol Ubah untuk mengubah status"' : '') +
        '></div>';
    }

    // Markup satu baris tampilan penerima. p adalah
    // objek snapshot (atau baris bawaan yang belum
    // tersimpan — tanpa id, berpenanda "Belum
    // tersimpan"). Kolom sesuai permintaan user: Nama,
    // Jumlah, Pajak, Biaya Admin, Diterima, Rekening,
    // Status, Aksi Edit/Hapus.
    function barisTampilHtml(p, i, isBawaan) {
      var jumlah = Math.round((Number(p.volume || 0) * Number(p.satuan || 0)) * 100) / 100;
      var pajak = Number(p.ppn || 0) + Number(p.pph || 0) + Number(p.sspd || 0);
      var diterima = jumlah - pajak - Number(p.biaya_admin || 0);
      var subNama;
      if ((p.jenis || 'orang') === 'toko') {
        var npwp = npwpToko(p.nama);
        subNama = 'Toko' + (npwp ? ' &bull; NPWP ' + lolosHtml(npwp) : '');
      } else {
        subNama = subInfo([
          p.nip ? 'NIP ' + p.nip : null,
          p.pangkat_gol,
          p.nik ? 'NIK ' + p.nik : null
        ]);
      }
      var asal = p.ptk_id ?
        ' <span class="badge bg-label-primary">PTK</span>' :
        (p.penerima_id ? ' <span class="badge bg-label-info">Induk</span>' : '');
      var penandaBawaan = isBawaan ?
        ' <span class="badge bg-label-warning">Belum tersimpan</span>' : '';
      var rek = subInfo([p.no_rekening, p.nama_bank]) || '-';
      var kunciAksi = isBawaan ? 'bawaan' : p.id;
      var aksi = '';
      if (bisaTulis) {
        aksi = '<td class="text-nowrap">' +
          '<button type="button" class="btn btn-sm btn-icon btn-text-primary" data-aksi-edit="' + kunciAksi +
          '" title="Ubah"><i class="ri ri-pencil-line"></i></button>' +
          '<button type="button" class="btn btn-sm btn-icon btn-text-danger" data-aksi-hapus="' + kunciAksi +
          '" title="Hapus"><i class="ri ri-delete-bin-line"></i></button></td>';
      }
      return '<tr>' +
        '<td>' + (i + 1) + '</td>' +
        '<td><div class="fw-semibold">' + lolosHtml(p.nama || '-') + asal + penandaBawaan + '</div>' +
        (subNama ? '<div class="small text-muted">' + subNama + '</div>' : '') + '</td>' +
        '<td class="text-end"><div>' + fmtRp(jumlah) + '</div>' +
        '<div class="small text-muted">' + fmtVol(p.volume) + ' &times; ' + fmtRp(p.satuan) + '</div></td>' +
        '<td class="text-end"><div>' + (pajak > 0 ? fmtRp(pajak) : '-') + '</div>' +
        (rincianPajak(p) ? '<div class="small text-muted">' + rincianPajak(p) + '</div>' : '') + '</td>' +
        '<td class="text-end">' + (Number(p.biaya_admin || 0) > 0 ? fmtRp(p.biaya_admin) : '-') + '</td>' +
        '<td class="text-end fw-semibold">' + fmtRp(diterima) + '</td>' +
        '<td class="small">' + rek + '</td>' +
        '<td>' + selStatusHtml(p, isBawaan) + '</td>' +
        aksi + '</tr>';
    }

    function jumlahKolomTampil() {
      return bisaTulis ? 9 : 8;
    }

    // Gambar ulang tabel dari model lokal (penerima
    // tersimpan + baris bawaan bila ada), berikut
    // total kaki tabel, selisih terhadap total grup,
    // dan lencana jumlah di tombol BKU. Baris bawaan
    // ikut dihitung totalnya seperti sebelumnya.
    function gambarTabel() {
      var tbody = $('penerima-tbody');
      var daftar = data.penerima || [];
      var tampil = daftar.slice();
      var adaBawaan = bisaTulis && bawaan && daftar.length === 0;
      if (adaBawaan) tampil.push(bawaan);

      if (!tampil.length) {
        tbody.innerHTML = '<tr><td colspan="' + jumlahKolomTampil() +
          '" class="text-center text-muted">Belum ada penerimaan untuk grup ini.</td></tr>';
      } else {
        tbody.innerHTML = tampil.map(function(p, i) {
          return barisTampilHtml(p, i, adaBawaan && i === tampil.length - 1);
        }).join('');
      }

      var hitung = function(p) {
        var jumlah = Math.round((Number(p.volume || 0) * Number(p.satuan || 0)) * 100) / 100;
        return {
          jumlah: jumlah,
          pajak: Number(p.ppn || 0) + Number(p.pph || 0) + Number(p.sspd || 0),
          admin: Number(p.biaya_admin || 0),
          diterima: jumlah - (Number(p.ppn || 0) + Number(p.pph || 0) +
            Number(p.sspd || 0)) - Number(p.biaya_admin || 0)
        };
      };
      var tot = {
        jumlah: 0,
        pajak: 0,
        admin: 0,
        diterima: 0
      };
      tampil.forEach(function(p) {
        var h = hitung(p);
        tot.jumlah += h.jumlah;
        tot.pajak += h.pajak;
        tot.admin += h.admin;
        tot.diterima += h.diterima;
      });
      $('tot-jumlah').textContent = fmtRp(tot.jumlah);
      $('tot-pajak').textContent = fmtRp(tot.pajak);
      $('tot-admin').textContent = fmtRp(tot.admin);
      $('tot-diterima').textContent = fmtRp(tot.diterima);

      var el = $('penerima-selisih');
      if (!tampil.length) {
        el.innerHTML = '';
      } else {
        var selisih = Math.round((tot.jumlah - Number(data.grup.total || 0)) * 100) / 100;
        if (Math.abs(selisih) < 0.005) {
          el.innerHTML = '<span class="text-success"><i class="ri ri-check-line me-1"></i>' +
            'Total jumlah penerima sesuai dengan total grup.</span>';
        } else {
          el.innerHTML = '<span class="text-danger"><i class="ri ri-error-warning-line me-1"></i>' +
            'Selisih ' + fmtRp(Math.abs(selisih)) + (selisih > 0 ? ' di atas' : ' di bawah') +
            ' total grup.</span>';
        }
      }

      segarkanLencana(tampil.length, tampil.map(function(p) {
        return (p.nama || '').trim();
      }));
    }

    // Isian bawaan dari sisa BKU: satuan = sisa total
    // grup yang belum dialokasikan ke penerima
    // tersimpan, potongan = sisa pajak nota — dipakai
    // baris penanda maupun formulir tambah baru.
    function bawaanBaru() {
      var g = data.grup || {};
      var terpakai = 0;
      var alokasi = {
        ppn: 0,
        pph: 0,
        sspd: 0
      };
      (data.penerima || []).forEach(function(p) {
        terpakai += Math.round((Number(p.volume || 0) * Number(p.satuan || 0)) * 100) / 100;
        alokasi.ppn += Number(p.ppn || 0);
        alokasi.pph += Number(p.pph || 0);
        alokasi.sspd += Number(p.sspd || 0);
      });
      var pajakBku = g.pajak_bku || {};
      var namaToko = (g.nama_toko || '').trim();
      return {
        id: null,
        nama: namaToko,
        nip: null,
        pangkat_gol: null,
        nik: null,
        no_rekening: null,
        nama_bank: null,
        volume: 1,
        satuan: Math.max(0, Number(g.total || 0) - terpakai),
        ppn: Math.max(0, Number(pajakBku.ppn || 0) - alokasi.ppn),
        pph: Math.max(0, Number(pajakBku.pph || 0) - alokasi.pph),
        sspd: Math.max(0, Number(pajakBku.sspd || 0) - alokasi.sspd),
        biaya_admin: 0,
        jenis: namaToko ? 'toko' : 'orang',
        selesai: false,
        ptk_id: null,
        penerima_id: null
      };
    }

    // ── Formulir penerima tersendiri ──

    // Hitung jumlah & diterima di formulir dari
    // isiannya (jumlah = volume × satuan) lalu tulis
    // ke tampilan jumlah / diterima.
    function hitungForm() {
      function nilai(id) {
        var el = $(id);
        return el ? (parseFloat(el.value) || 0) : 0;
      }
      var jumlah = Math.round((nilai('form-volume') * nilai('form-satuan')) * 100) / 100;
      var diterima = jumlah - nilai('form-ppn') - nilai('form-pph') -
        nilai('form-sspd') - nilai('form-admin');
      $('form-jumlah-tampil').textContent = fmtRp(jumlah);
      $('form-diterima-tampil').textContent = fmtRp(diterima);
    }

    // Segarkan tampilan formulir menurut jenis &
    // tautan sumbernya: penerima tertaut PTK / induk
    // ber-lencana asal dan pemilih jenisnya
    // dinonaktifkan; toko menyembunyikan isian
    // perorangan dan menampilkan NPWP dari daftar toko
    // bila namanya cocok persis; centang simpan ke
    // tabel penerima hanya untuk baris baru orang yang
    // belum tertaut.
    // Jenis penerima dipilih lewat grup tombol radio
    // di bagian atas formulir (Orang / Toko).
    function jenisFormAktif() {
      var grup = $('form-jenis-grup');
      var aktif = grup ? grup.querySelector('[data-jenis].active') : null;
      return aktif ? aktif.getAttribute('data-jenis') : 'orang';
    }

    function pasangJenisForm(jenis) {
      var grup = $('form-jenis-grup');
      if (!grup) return;
      grup.querySelectorAll('[data-jenis]').forEach(function(btn) {
        btn.classList.toggle('active', btn.getAttribute('data-jenis') === jenis);
      });
    }

    // Ikon tambah di samping nama: tampil selama nama
    // belum tertaut ke PTK / induk mana pun. Kliknya
    // membuka modal tambah induk (orang / toko
    // mengikuti jenis formulir), bukan menyimpan
    // langsung.
    function segarkanTombolInduk() {
      var btn = $('form-tambah-induk');
      if (!btn) return;
      var tampil = !formTautan.ptkId && !formTautan.penerimaId;
      btn.classList.toggle('d-none', !tampil);
      if (tampil) {
        var toko = jenisFormAktif() === 'toko';
        btn.innerHTML = '<i class="ri ' +
          (toko ? 'ri-store-2-line' : 'ri-user-add-line') + '"></i>';
        btn.title = toko ?
          'Tambah toko baru ke daftar penerima' :
          'Tambah penerima baru (non-PTK) ke daftar penerima';
      }
    }

    function segarkanSubJenisForm() {
      var jenis = jenisFormAktif();
      var subOrang = $('form-sub-orang');
      var subToko = $('form-sub-toko');
      if (subOrang) subOrang.classList.toggle('d-none', jenis === 'toko');
      if (subToko) {
        subToko.classList.toggle('d-none', jenis !== 'toko');
        if (jenis === 'toko') {
          var npwp = npwpToko($('form-nama').value);
          $('form-toko-info').innerHTML = 'Toko' +
            (npwp ? ' &bull; NPWP ' + lolosHtml(npwp) : '');
        }
      }
      var tertaut = !!(formTautan.ptkId || formTautan.penerimaId);
      var grup = $('form-jenis-grup');
      if (grup) {
        grup.querySelectorAll('[data-jenis]').forEach(function(btn) {
          btn.disabled = tertaut;
        });
      }
      var lencana = $('form-lencana-asal');
      if (lencana) {
        if (formTautan.ptkId) {
          lencana.innerHTML = '<span class="badge bg-label-primary">PTK</span>';
        } else if (formTautan.penerimaId) {
          lencana.innerHTML = '<span class="badge bg-label-info">Induk</span>';
        } else {
          lencana.innerHTML = '';
        }
      }
      segarkanTombolInduk();
    }

    // Buka formulir: p null = tambah baru (isian
    // bawaan dari sisa BKU); p berisi snapshot =
    // ubah; isBawaan = baris penanda yang diedit
    // (disimpan lewat POST seperti tambah baru).
    function bukaForm(p, isBawaan) {
      var d = p || bawaanBaru();
      formEditId = d.id || null;
      formBawaan = !!isBawaan;
      formTautan = {
        ptkId: d.ptk_id ? String(d.ptk_id) : '',
        penerimaId: d.penerima_id ? String(d.penerima_id) : '',
        namaTertaut: d.nama || ''
      };
      $('form-judul').textContent = formEditId ? 'Ubah Penerimaan' : 'Tambah Penerimaan';
      $('form-nama').value = d.nama || '';
      $('form-nama').classList.remove('is-invalid');
      $('form-nama-galat').classList.add('d-none');
      pasangJenisForm(d.jenis || 'orang');
      $('form-nip').value = d.nip || '';
      $('form-pangkat').value = d.pangkat_gol || '';
      $('form-nik').value = d.nik || '';
      $('form-volume').value = Number(d.volume || 0);
      $('form-satuan').value = Number(d.satuan || 0);
      $('form-ppn').value = Number(d.ppn || 0);
      $('form-pph').value = Number(d.pph || 0);
      $('form-sspd').value = Number(d.sspd || 0);
      $('form-admin').value = Number(d.biaya_admin || 0);
      $('form-rekening').value = d.no_rekening || '';
      $('form-bank').value = d.nama_bank || '';
      segarkanSubJenisForm();
      terapkanKolomPajak();
      hitungForm();
      bootstrap.Modal.getOrCreateInstance(formEl).show();
      setTimeout(function() {
        $('form-nama').focus();
      }, 150);
    }

    // Kumpulkan payload snapshot dari formulir.
    function kumpulkanPayloadForm() {
      function teks(id) {
        var el = $(id);
        return el ? el.value.trim() : '';
      }

      function angka(id) {
        var el = $(id);
        return el ? (parseFloat(el.value) || 0) : 0;
      }
      var payload = {
        nama: teks('form-nama'),
        nip: teks('form-nip') || null,
        pangkat_gol: teks('form-pangkat') || null,
        nik: teks('form-nik') || null,
        no_rekening: teks('form-rekening') || null,
        nama_bank: teks('form-bank') || null,
        volume: angka('form-volume'),
        satuan: angka('form-satuan'),
        ppn_rp: angka('form-ppn'),
        pph_rp: angka('form-pph'),
        sspd_rp: angka('form-sspd'),
        admin_rp: angka('form-admin'),
        jenis: jenisFormAktif(),
        ptk_id: formTautan.ptkId ? Number(formTautan.ptkId) : null,
        penerima_id: formTautan.penerimaId ? Number(formTautan.penerimaId) : null
      };
      return payload;
    }

    // Simpan formulir: baris baru lewat POST, baris
    // tersimpan lewat PUT. Penanda selesai tidak lagi
    // diatur dari formulir — saklarnya ada di tabel
    // tampilan (endpoint selesai khusus).
    async function simpanForm() {
      var inpNama = $('form-nama');
      if (!inpNama.value.trim()) {
        inpNama.classList.add('is-invalid');
        $('form-nama-galat').classList.remove('d-none');
        inpNama.focus();
        return;
      }
      inpNama.classList.remove('is-invalid');
      $('form-nama-galat').classList.add('d-none');
      var btn = $('form-btn-simpan');
      btn.disabled = true;
      try {
        var payload = kumpulkanPayloadForm();
        var res;
        if (formEditId) {
          res = await kirim(urlDasar + '/' + barisId + '/penerima/' + formEditId, 'PUT', payload);
        } else {
          res = await kirim(urlDasar + '/' + barisId + '/penerima', 'POST', payload);
        }
        var d = res.data;
        if (d) {
          if (formEditId) {
            var idxModel = (data.penerima || []).findIndex(function(p) {
              return Number(p.id) === Number(d.id);
            });
            if (idxModel >= 0) data.penerima[idxModel] = d;
          } else {
            data.penerima.push(d);
          }
        }
        if (formBawaan) bawaan = null;
        bootstrap.Modal.getOrCreateInstance(formEl).hide();
        segarkanStatusTurunan();
        gambarTabel();
      } catch (e) {
        gagal(e);
      } finally {
        btn.disabled = false;
      }
    }

    // Hapus satu penerima tersimpan (dengan
    // konfirmasi); baris bawaan cukup dibuang dari
    // tampilan tanpa request.
    async function hapusPenerima(id) {
      if (!await konfirmasiHapus('Hapus penerima?', 'Penerima ini dihapus dari grup ini.')) return;
      try {
        await kirim(urlDasar + '/' + barisId + '/penerima/' + id, 'DELETE');
        data.penerima = (data.penerima || []).filter(function(p) {
          return Number(p.id) !== Number(id);
        });
        segarkanStatusTurunan();
        gambarTabel();
      } catch (e) {
        gagal(e);
      }
    }

    // Saklar status di tabel tampilan: ubah penanda
    // selesai satu penerima lewat endpoint khusus,
    // lalu segarkan model lokal, label status kepala
    // modal, dan lencana baris BKU. Gagal -> saklar
    // dikembalikan ke posisi semula.
    async function ubahSelesaiBaris(saklar) {
      var id = saklar.getAttribute('data-selesai-id');
      if (!id) return;
      var tujuan = saklar.checked;
      saklar.disabled = true;
      try {
        var res = await kirim(
          urlDasar + '/' + barisId + '/penerima/' + id + '/selesai', 'PUT', {
            selesai: tujuan
          });
        var target = (data.penerima || []).find(function(p) {
          return String(p.id) === String(id);
        });
        if (target && res.data) target.selesai = !!res.data.selesai;
        if (res.status_tampil !== undefined) {
          data.grup.status_tampil = res.status_tampil;
          data.grup.jumlah_penerima = res.jumlah_penerima;
          data.grup.jumlah_selesai = res.jumlah_selesai;
          gambarMeta();
          segarkanMetaBaris();
        } else {
          segarkanStatusTurunan();
        }
        gambarTabel();
      } catch (e) {
        saklar.checked = !tujuan;
        gagal(e);
      } finally {
        saklar.disabled = false;
      }
    }

    // Ikon tambah di samping nama: membuka modal
    // Daftar Penerima dengan formulir induk baru
    // terbuka — jenis mengikuti formulir (orang =
    // penerima non-PTK, toko = toko) dan isian yang
    // sudah diketik ikut terbawa. Setelah induk baru
    // tersimpan di sana, formulir penerimaan langsung
    // tertaut kepadanya (lihat simpanFormInduk).
    function bukaTambahIndukDariForm() {
      if (!daftarEl) return;
      var jenis = jenisFormAktif();
      indukDariForm = true;
      gambarDaftarPenerima();
      new bootstrap.Modal(daftarEl).show();
      var tombolTab = daftarEl.querySelector('[data-bs-target="' +
        (jenis === 'toko' ? '#tab-daftar-toko' : '#tab-daftar-orang') + '"]');
      if (tombolTab) bootstrap.Tab.getOrCreateInstance(tombolTab).show();
      bukaFormInduk(jenis, null);
      $('induk-nama').value = $('form-nama').value.trim();
      if (jenis === 'orang') {
        $('induk-nik').value = $('form-nik').value.trim();
        $('induk-nip').value = $('form-nip').value.trim();
        $('induk-pangkat').value = $('form-pangkat').value.trim();
      }
      $('induk-rekening').value = $('form-rekening').value.trim();
      $('induk-bank').value = $('form-bank').value.trim();
      $('induk-nama').focus();
    }

    // ── Modal Daftar Penerima ──
    // Tiga tab dari payload yang sama dengan combobox
    // formulir: PTK (hanya tampil + tautan ubah),
    // induk orang dan induk toko (tambah / ubah /
    // hapus lewat endpoint penerima-master). Setiap
    // perubahan induk membangun ulang sumber combobox
    // agar formulir penerimaan langsung memakainya.

    function selAksiInduk(p) {
      if (!bisaTulis) return '';
      return '<td class="text-end text-nowrap">' +
        '<button type="button" class="btn btn-sm btn-icon btn-outline-primary me-1" data-induk-edit="' +
        p.id + '" title="Ubah"><i class="ri ri-pencil-line"></i></button>' +
        '<button type="button" class="btn btn-sm btn-icon btn-outline-danger" data-induk-hapus="' +
        p.id + '" title="Hapus"><i class="ri ri-delete-bin-line"></i></button></td>';
    }

    function barisKosongDaftar(jumlahKolom, pesan) {
      return '<tr><td colspan="' + jumlahKolom +
        '" class="text-center text-muted">' + pesan + '</td></tr>';
    }

    function gambarDaftarPenerima() {
      var kolomAksi = bisaTulis ? 1 : 0;

      var ptk = data.sumber_ptk || [];
      $('daftar-ptk-tbody').innerHTML = ptk.length ? ptk.map(function(p) {
        var aksi = bisaTulis ?
          '<td class="text-end"><a class="btn btn-sm btn-outline-primary" target="_blank" href="' +
          urlPtkDasar + '/' + p.id + '/ubah"><i class="ri ri-external-link-line me-1"></i>Ubah di PTK</a></td>' :
          '';
        return '<tr><td class="fw-semibold">' + lolosHtml(p.nama) + '</td><td>' +
          lolosHtml(p.nip || '-') + '</td><td>' + lolosHtml(p.pangkat_golongan || '-') +
          '</td><td>' + lolosHtml(p.nik || '-') + '</td><td>' +
          subInfo([p.no_rekening, p.nama_bank]) + '</td>' + aksi + '</tr>';
      }).join('') : barisKosongDaftar(5 + kolomAksi, 'Tidak ada data PTK.');

      var induk = data.sumber_penerima || [];
      var orang = induk.filter(function(p) {
        return (p.jenis || 'orang') !== 'toko';
      });
      $('daftar-orang-tbody').innerHTML = orang.length ? orang.map(function(p) {
        return '<tr><td class="fw-semibold">' + lolosHtml(p.nama) + '</td><td>' +
          lolosHtml(p.nik || '-') + '</td><td>' + lolosHtml(p.nip || '-') +
          '</td><td>' + lolosHtml(p.pangkat_gol || '-') + '</td><td>' +
          subInfo([p.no_rekening, p.nama_bank]) + '</td>' + selAksiInduk(p) + '</tr>';
      }).join('') : barisKosongDaftar(5 + kolomAksi,
        'Belum ada penerima non-PTK.');

      var toko = induk.filter(function(p) {
        return (p.jenis || 'orang') === 'toko';
      });
      $('daftar-toko-tbody').innerHTML = toko.length ? toko.map(function(p) {
        return '<tr><td class="fw-semibold">' + lolosHtml(p.nama) + '</td><td>' +
          lolosHtml(p.pemilik || '-') + '</td><td>' + lolosHtml(p.npwp || '-') +
          '</td><td>' + lolosHtml(p.alamat || '-') + '</td><td>' +
          lolosHtml(p.no_hp || '-') + '</td><td>' +
          subInfo([p.no_rekening, p.nama_bank]) + '</td>' + selAksiInduk(p) + '</tr>';
      }).join('') : barisKosongDaftar(6 + kolomAksi,
        'Belum ada toko induk. Tambahkan lewat tombol Tambah Toko.');
    }

    function bukaFormInduk(jenis, master) {
      indukJenis = jenis;
      indukEditId = master ? master.id : null;
      $('induk-form-judul').textContent =
        (master ? 'Ubah ' : 'Tambah ') + (jenis === 'toko' ? 'Toko' : 'Penerima');
      $('induk-nama-label').textContent = jenis === 'toko' ? 'Nama Toko' : 'Nama';
      document.querySelectorAll('.induk-khusus-orang').forEach(function(el) {
        el.classList.toggle('d-none', jenis !== 'orang');
      });
      document.querySelectorAll('.induk-khusus-toko').forEach(function(el) {
        el.classList.toggle('d-none', jenis !== 'toko');
      });
      var d = master || {};
      $('induk-nama').value = d.nama || '';
      $('induk-nik').value = d.nik || '';
      $('induk-nip').value = d.nip || '';
      $('induk-pangkat').value = d.pangkat_gol || '';
      $('induk-pemilik').value = d.pemilik || '';
      $('induk-npwp').value = d.npwp || '';
      $('induk-nohp').value = d.no_hp || '';
      $('induk-alamat').value = d.alamat || '';
      $('induk-rekening').value = d.no_rekening || '';
      $('induk-bank').value = d.nama_bank || '';
      $('induk-nama').classList.remove('is-invalid');
      $('induk-nama-galat').classList.add('d-none');
      $('induk-form-wrap').classList.remove('d-none');
      $('induk-nama').focus();
    }

    function tutupFormInduk() {
      indukEditId = null;
      $('induk-form-wrap').classList.add('d-none');
    }

    async function simpanFormInduk() {
      var nama = $('induk-nama').value.trim();
      if (!nama) {
        $('induk-nama').classList.add('is-invalid');
        $('induk-nama-galat').classList.remove('d-none');
        $('induk-nama').focus();
        return;
      }

      function teks(id) {
        var el = $(id);
        return el && el.value.trim() ? el.value.trim() : null;
      }
      var payload = {
        nama: nama,
        jenis: indukJenis,
        nik: indukJenis === 'orang' ? teks('induk-nik') : null,
        nip: indukJenis === 'orang' ? teks('induk-nip') : null,
        pangkat_gol: indukJenis === 'orang' ? teks('induk-pangkat') : null,
        pemilik: indukJenis === 'toko' ? teks('induk-pemilik') : null,
        npwp: indukJenis === 'toko' ? teks('induk-npwp') : null,
        no_hp: indukJenis === 'toko' ? teks('induk-nohp') : null,
        alamat: teks('induk-alamat'),
        no_rekening: teks('induk-rekening'),
        nama_bank: teks('induk-bank')
      };
      var btn = $('induk-btn-simpan');
      btn.disabled = true;
      try {
        var res = indukEditId ?
          await kirim(urlDasar + '/' + barisId + '/penerima-master/' + indukEditId, 'PUT', payload) :
          await kirim(urlDasar + '/' + barisId + '/penerima-master', 'POST', payload);
        var m = res.data || Object.assign({}, payload, {
          id: indukEditId
        });
        var dariForm = indukDariForm && !indukEditId;
        if (indukEditId) {
          data.sumber_penerima = (data.sumber_penerima || []).map(function(p) {
            return String(p.id) === String(indukEditId) ? m : p;
          });
        } else {
          (data.sumber_penerima = data.sumber_penerima || []).push(m);
        }
        bangunSumberGabungan();
        tutupFormInduk();
        gambarDaftarPenerima();
        if (dariForm) {
          // Dibuka dari ikon tambah formulir
          // penerimaan: terapkan induk baru ke
          // formulir itu dan tutup modal daftar,
          // kembali ke formulir.
          indukDariForm = false;
          $('form-nama').value = m.nama || '';
          $('form-nip').value = m.nip || '';
          $('form-pangkat').value = m.pangkat_gol || '';
          $('form-nik').value = m.nik || '';
          $('form-rekening').value = m.no_rekening || '';
          $('form-bank').value = m.nama_bank || '';
          formTautan.ptkId = '';
          formTautan.penerimaId = m.id ? String(m.id) : '';
          formTautan.namaTertaut = m.nama || '';
          pasangJenisForm(m.jenis || indukJenis);
          segarkanSubJenisForm();
          var modalDaftar = bootstrap.Modal.getInstance(daftarEl);
          if (modalDaftar) modalDaftar.hide();
        }
        Swal.fire({
          icon: 'success',
          title: 'Tersimpan',
          text: res.message || '',
          timer: 1500,
          showConfirmButton: false
        });
      } catch (e) {
        gagal(e);
      } finally {
        btn.disabled = false;
      }
    }

    async function hapusInduk(id) {
      var m = (data.sumber_penerima || []).find(function(p) {
        return String(p.id) === String(id);
      });
      if (!await konfirmasiHapus('Hapus dari daftar penerima?',
          '"' + (m ? m.nama : '') + '" dihapus dari tabel penerima. ' +
          'Penerima yang sudah tersimpan di grup tidak ikut terhapus.')) return;
      try {
        await kirim(urlDasar + '/' + barisId + '/penerima-master/' + id, 'DELETE');
        data.sumber_penerima = (data.sumber_penerima || []).filter(function(p) {
          return String(p.id) !== String(id);
        });
        if (indukEditId && String(indukEditId) === String(id)) tutupFormInduk();
        bangunSumberGabungan();
        gambarDaftarPenerima();
      } catch (e) {
        gagal(e);
      }
    }

    // ── Combobox nama penerima (di formulir) ──
    var panelAktif = null;

    function sembunyikanPanelNama() {
      if (panelAktif) panelAktif.classList.add('d-none');
      panelAktif = null;
    }

    // Tampilkan panel pilihan nama untuk formulir:
    // sumber gabungan (PTK, tabel penerima, toko)
    // disaring menurut ketikan, dikelompokkan per
    // sumber, maksimal 40 pilihan. Panel melekat
    // (absolute) tepat di bawah kotak nama selebar
    // kotaknya, jadi selalu pas dan ikut bergulir
    // bersama isi modal.
    function tampilkanPanelNamaForm() {
      var inp = $('form-nama');
      var panel = $('form-daftar-nama');
      if (!inp || !panel) return;
      var query = inp.value.trim().toLowerCase();
      var cocok = sumberGabungan.filter(function(s) {
        return !query || s.nama.toLowerCase().indexOf(query) !== -1;
      }).slice(0, 40);
      if (!cocok.length) {
        panel.innerHTML = '<div class="list-group-item py-1 px-2 small text-muted">' +
          'Tidak ada yang cocok — nama baru bisa diketik bebas.</div>';
      } else {
        var html = '';
        var sumberKini = null;
        cocok.forEach(function(s) {
          if (s.sumber !== sumberKini) {
            sumberKini = s.sumber;
            html += '<div class="list-group-item py-1 px-2 small fw-semibold bg-light">' +
              lolosHtml(s.sumber) + '</div>';
          }
          var sub = s.jenis === 'toko' ?
            subInfo(['Toko', s.npwp ? 'NPWP ' + s.npwp : null]) :
            subInfo([
              s.nip ? 'NIP ' + s.nip : null,
              s.nama_bank ? s.nama_bank + ' ' + (s.no_rekening || '') : null
            ]);
          html += '<button type="button" class="list-group-item list-group-item-action py-1 px-2" ' +
            'data-pilih-sumber="' + sumberGabungan.indexOf(s) + '">' +
            '<span class="fw-semibold">' + lolosHtml(s.nama) + '</span>' +
            (sub ? '<br><span class="small text-muted">' + sub + '</span>' : '') + '</button>';
        });
        panel.innerHTML = html;
      }
      if (panelAktif && panelAktif !== panel) panelAktif.classList.add('d-none');
      panel.classList.remove('d-none');
      panelAktif = panel;
    }

    // Terapkan satu sumber dari panel ke formulir:
    // isian snapshot disalin dari sumbernya (toko
    // mengosongkan isian perorangan), tautan & jenis
    // dipasang; penyimpanan tetap lewat tombol Simpan
    // formulir.
    function pilihSumberForm(s) {
      if (!s) return;
      $('form-nama').value = s.nama || '';
      $('form-nip').value = s.nip || '';
      $('form-pangkat').value = s.pangkat_gol || '';
      $('form-nik').value = s.nik || '';
      $('form-rekening').value = s.no_rekening || '';
      $('form-bank').value = s.nama_bank || '';
      formTautan.ptkId = s.sumber === 'PTK' ? String(s.id) : '';
      formTautan.penerimaId = s.penerimaId ? String(s.penerimaId) : '';
      formTautan.namaTertaut = s.nama || '';
      pasangJenisForm(s.jenis);
      segarkanSubJenisForm();
      sembunyikanPanelNama();
    }

    // Daftar gabungan sumber nama untuk combobox:
    // dibangun ulang setiap muat() dari payload server.
    var sumberGabungan = [];

    function bangunSumberGabungan() {
      sumberGabungan = [];
      (data.sumber_ptk || []).forEach(function(p) {
        sumberGabungan.push({
          sumber: 'PTK',
          jenis: 'orang',
          id: p.id,
          nama: p.nama,
          nip: p.nip || null,
          pangkat_gol: p.pangkat_golongan || null,
          nik: p.nik || null,
          no_rekening: p.no_rekening || null,
          nama_bank: p.nama_bank || null,
          npwp: null
        });
      });
      (data.sumber_penerima || []).forEach(function(p) {
        var jenisInduk = (p.jenis || 'orang') === 'toko' ? 'toko' : 'orang';
        sumberGabungan.push({
          // Induk toko masuk kelompok Toko pada daftar
          // pilihan nama; induk orang tetap Penerima.
          // Keduanya membawa penerimaId agar formulir
          // tertaut ke induknya saat dipilih.
          sumber: jenisInduk === 'toko' ? 'Toko' : 'Penerima',
          jenis: jenisInduk,
          id: p.id,
          penerimaId: p.id,
          nama: p.nama,
          nip: p.nip || null,
          pangkat_gol: p.pangkat_gol || null,
          nik: p.nik || null,
          no_rekening: p.no_rekening || null,
          nama_bank: p.nama_bank || null,
          npwp: p.npwp || null
        });
      });
      (data.sumber_toko || []).forEach(function(t) {
        sumberGabungan.push({
          sumber: 'Toko',
          jenis: 'toko',
          id: null,
          nama: t.nama,
          nip: null,
          pangkat_gol: null,
          nik: null,
          no_rekening: null,
          nama_bank: null,
          npwp: t.npwp || null
        });
      });
    }

    function gambarSemua() {
      gambarInfo();
      gambarMeta();
      gambarTabel();
    }

    async function muat() {
      data = await kirim(urlDasar + '/' + barisId + '/penerima', 'GET');
      bangunSumberGabungan();
      gambarSemua();
      // Grup yang belum punya penerima tersimpan
      // langsung diberi satu baris bawaan (belum
      // tersimpan): nama toko dari nota BKU bila ada +
      // nominal sisa grup, supaya penerima bawaan dan
      // nominalnya langsung terlihat tanpa input
      // manual. Baris tersimpan lewat formulir begitu
      // pengguna menekan Simpan; menutup modal tanpa
      // mengubah apa pun tidak menyimpan apa-apa.
      bawaan = null;
      if (bisaTulis && data && data.grup && (!data.penerima || !data.penerima.length)) {
        var namaToko = (data.grup.nama_toko || '').trim();
        if (namaToko) {
          bawaan = bawaanBaru();
          gambarTabel();
        }
      }
    }

    // ── Buka modal dari tombol di tabel BKU ──
    document.addEventListener('click', function(e) {
      var btn = e.target.closest('[data-penerima-baris]');
      if (!btn) return;
      barisId = btn.getAttribute('data-id');
      data = null;
      bawaan = null;
      sembunyikanPanelNama();
      $('penerima-info-grup').innerHTML = '<span class="text-muted">Memuat…</span>';
      $('meta-siplah-info').innerHTML = '';
      $('meta-status-baca').innerHTML = '';
      $('meta-status-info').textContent = '';
      $('meta-kategori-baca').innerHTML = '';
      $('meta-va-wrap').classList.add('d-none');
      $('penerima-tbody').innerHTML = '<tr><td colspan="' + jumlahKolomTampil() +
        '" class="text-center text-muted">Memuat…</td></tr>';
      $('penerima-selisih').innerHTML = '';
      bootstrap.Modal.getOrCreateInstance(modalEl).show();
      muat().catch(gagal);
    });

    if (bisaTulis) {
      // Tombol tambah penerima baru: buka formulir
      // dengan isian bawaan dari sisa BKU.
      $('btn-baris-penerima-baru').addEventListener('click', function() {
        bukaForm(null, false);
      });

      // Delegasi klik pada tabel tampilan: ikon Edit
      // membuka formulir berisi baris itu, ikon Hapus
      // menghapus (baris bawaan hanya dibuang dari
      // tampilan).
      $('penerima-tbody').addEventListener('click', function(e) {
        var btnEdit = e.target.closest('[data-aksi-edit]');
        if (btnEdit) {
          var kunciEdit = btnEdit.getAttribute('data-aksi-edit');
          if (kunciEdit === 'bawaan') {
            if (bawaan) bukaForm(bawaan, true);
          } else {
            var targetEdit = (data.penerima || []).find(function(p) {
              return String(p.id) === kunciEdit;
            });
            if (targetEdit) bukaForm(targetEdit, false);
          }
          return;
        }
        var btnHapus = e.target.closest('[data-aksi-hapus]');
        if (btnHapus) {
          var kunciHapus = btnHapus.getAttribute('data-aksi-hapus');
          if (kunciHapus === 'bawaan') {
            bawaan = null;
            gambarTabel();
          } else {
            hapusPenerima(kunciHapus);
          }
        }
      });

      // Modal Daftar Penerima: tambah induk orang /
      // toko, simpan & batal formulir induk, serta
      // aksi ubah / hapus pada baris-baris induk.
      $('btn-tambah-induk-orang').addEventListener('click', function() {
        bukaFormInduk('orang', null);
      });
      $('btn-tambah-induk-toko').addEventListener('click', function() {
        bukaFormInduk('toko', null);
      });
      $('induk-btn-batal').addEventListener('click', tutupFormInduk);
      $('induk-btn-simpan').addEventListener('click', simpanFormInduk);
      daftarEl.addEventListener('click', function(e) {
        var btnEdit = e.target.closest ? e.target.closest('[data-induk-edit]') : null;
        if (btnEdit) {
          var mEdit = (data.sumber_penerima || []).find(function(p) {
            return String(p.id) === btnEdit.getAttribute('data-induk-edit');
          });
          if (mEdit) {
            bukaFormInduk((mEdit.jenis || 'orang') === 'toko' ? 'toko' : 'orang', mEdit);
          }
          return;
        }
        var btnHapus = e.target.closest ? e.target.closest('[data-induk-hapus]') : null;
        if (btnHapus) hapusInduk(btnHapus.getAttribute('data-induk-hapus'));
      });

      // Formulir: hitung ulang jumlah & diterima saat
      // angka berubah; jenis mengatur sub-bidang.
      ['form-volume', 'form-satuan', 'form-ppn', 'form-pph', 'form-sspd', 'form-admin']
        .forEach(function(id) {
          $(id).addEventListener('input', hitungForm);
        });
      // Grup tombol jenis di atas formulir: klik
      // mengganti jenis; memilih Toko melepaskan
      // tautan PTK / induk (toko bukan perorangan).
      $('form-jenis-grup').addEventListener('click', function(e) {
        var btnJenis = e.target.closest ? e.target.closest('[data-jenis]') : null;
        if (!btnJenis || btnJenis.disabled) return;
        pasangJenisForm(btnJenis.getAttribute('data-jenis'));
        if (btnJenis.getAttribute('data-jenis') === 'toko') {
          formTautan.ptkId = '';
          formTautan.penerimaId = '';
          formTautan.namaTertaut = $('form-nama').value.trim();
        }
        segarkanSubJenisForm();
      });

      // Ikon tambah di samping nama: membuka modal
      // tambah induk di Daftar Penerima.
      $('form-tambah-induk').addEventListener('click', bukaTambahIndukDariForm);

      // Combobox nama di formulir: tampilkan panel
      // saat fokus / mengetik; ketikan yang menyimpang
      // dari nama sumber terpilih melepaskan tautan
      // PTK / induknya; Escape menutup panel.
      $('form-nama').addEventListener('focus', tampilkanPanelNamaForm);
      $('form-nama').addEventListener('input', function() {
        tampilkanPanelNamaForm();
        if ((formTautan.ptkId || formTautan.penerimaId) &&
          $('form-nama').value.trim() !== formTautan.namaTertaut) {
          formTautan.ptkId = '';
          formTautan.penerimaId = '';
          segarkanSubJenisForm();
        }
      });
      $('form-nama').addEventListener('keydown', function(e) {
        if (e.key === 'Escape') sembunyikanPanelNama();
      });
      $('form-daftar-nama').addEventListener('click', function(e) {
        var btnPilih = e.target.closest('[data-pilih-sumber]');
        if (btnPilih) {
          pilihSumberForm(sumberGabungan[Number(btnPilih.getAttribute('data-pilih-sumber'))]);
        }
      });

      // Klik di luar panel nama menutup panelnya.
      document.addEventListener('click', function(e) {
        if (panelAktif && !$('form-daftar-nama').contains(e.target) &&
          e.target !== $('form-nama')) {
          sembunyikanPanelNama();
        }
      });

      // Simpan formulir.
      $('form-btn-simpan').addEventListener('click', simpanForm);

      // Saklar kategori grup: tiga tombol, diklik
      // langsung tersimpan; tautan di bawahnya
      // mengembalikan ke deteksi otomatis.
      var saklarKategori = $('meta-kategori-saklar');
      if (saklarKategori) {
        saklarKategori.addEventListener('click', function(e) {
          var btnKat = e.target.closest('[data-kategori]');
          if (!btnKat || !data || !data.grup) return;
          simpanKategori(btnKat.getAttribute('data-kategori'));
        });
      }
      var tautanOtomatis = $('meta-kategori-otomatis');
      if (tautanOtomatis) {
        tautanOtomatis.addEventListener('click', function() {
          if (!data || !data.grup) return;
          simpanKategori('');
        });
      }

      // Simpan nomor VA SIPLah: status grup saat ini
      // ikut dikirim apa adanya (wajib di server).
      $('btn-simpan-va').addEventListener('click', function() {
        if (!data || !data.grup) return;
        simpanMeta(
          (data.grup.status === 'selesai') ? 'selesai' : 'belum',
          $('meta-nomor-va').value.trim() || null
        );
      });

      // Formulir bertumpuk di atas modal penerima:
      // saat formulir ditutup, pastikan modal penerima
      // di bawahnya tetap bisa digulir (Bootstrap
      // melepas kelas modal-open dari body saat modal
      // teratas ditutup).
      formEl.addEventListener('hidden.bs.modal', function() {
        sembunyikanPanelNama();
        if (modalEl.classList.contains('show')) {
          document.body.classList.add('modal-open');
        }
      });

    }

    // Saklar status pada baris tampilan: perubahan
    // langsung disimpan lewat endpoint selesai. Penyetel
    // ini di luar blok tulis penuh karena Bendahara
    // (bisaStatus) juga boleh menggerakkan saklar,
    // walau tidak boleh mengubah isi penerima.
    if (bolehSaklar) {
      $('penerima-tbody').addEventListener('change', function(e) {
        var saklar = e.target.closest ? e.target.closest('[data-selesai-id]') : null;
        if (saklar) ubahSelesaiBaris(saklar);
      });
    }

    // Modal Daftar Penerima bisa dibuka semua peran
    // (peran baca hanya melihat): bertumpuk di atas
    // modal penerima, dan saat ditutup modal di
    // bawahnya harus tetap bisa digulir.
    if (daftarEl) {
      daftarEl.addEventListener('hidden.bs.modal', function() {
        indukDariForm = false;
        if (modalEl.classList.contains('show') ||
          (formEl && formEl.classList.contains('show'))) {
          document.body.classList.add('modal-open');
        }
      });
      $('btn-daftar-penerima').addEventListener('click', function() {
        gambarDaftarPenerima();
        new bootstrap.Modal(daftarEl).show();
      });
    }
  })();
</script>
