@extends('layouts/layoutMaster')

@section('title', 'Pembaruan Aplikasi')

@section('content')
  {{-- ── Kepala halaman ─────────────────────────────────────────── --}}
  <div class="card mb-4">
    <div class="card-body d-flex flex-wrap align-items-center gap-3 py-3">
      <span class="bg-label-primary rounded p-2 d-inline-flex">
        <i class="icon-base ri ri-refresh-line icon-28px"></i>
      </span>
      <div class="flex-grow-1">
        <h4 class="mb-0">Pembaruan Aplikasi</h4>
        <small class="text-body-secondary">Terapkan paket pembaruan tanpa SSH — khusus Admin. File lama dibackup otomatis sebelum ditimpa.</small>
      </div>
      <div class="text-md-end">
        <div class="text-body-secondary small">Versi terpasang</div>
        @if ($terpasang)
          <span class="badge bg-primary">{{ $terpasang['versi'] }}</span>
          <div class="small text-body-secondary">diterapkan {{ $terpasang['diterapkan_pada'] }}</div>
        @else
          <span class="badge bg-label-secondary">Pemasangan awal (manual)</span>
          <div class="small text-body-secondary">belum pernah ada pembaruan lewat halaman ini</div>
        @endif
      </div>
    </div>
  </div>

  {{-- ── Laporan hasil ──────────────────────────────────────────── --}}
  @if (session('laporan'))
    @php($laporan = session('laporan'))
    <div class="alert alert-success" role="alert">
      <h6 class="alert-heading mb-1"><i class="icon-base ri ri-checkbox-circle-line me-1"></i>Pembaruan selesai: {{ $laporan['versi'] }}</h6>
      <div class="small">
        {{ $laporan['file'] }} file ditulis, {{ $laporan['dihapus'] }} file dihapus.
        Backup file lama: <code>{{ $laporan['backup'] }}</code> (tersedia untuk dipulihkan di bawah).
        @if ($laporan['migrasi'])
          <br>Migrasi: {{ $laporan['migrasi'] }}
        @endif
      </div>
      @if (!empty($laporan['peringatan']))
        <ul class="mb-0 mt-2 small">
          @foreach ($laporan['peringatan'] as $p)
            <li>{{ $p }}</li>
          @endforeach
        </ul>
      @endif
    </div>
  @endif

  @if (session('gagal'))
    <div class="alert alert-danger" role="alert">
      <i class="icon-base ri ri-error-warning-line me-1"></i>{{ session('gagal') }}
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger" role="alert">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row">
    {{-- ── Unggah paket ──────────────────────────────────────────── --}}
    <div class="col-lg-7">
      <div class="card mb-4">
        <h5 class="card-header">Terapkan Paket Pembaruan</h5>
        <div class="card-body">
          <form method="POST" action="{{ route('pembaruan.terapkan') }}" enctype="multipart/form-data" id="form-terapkan"
                onsubmit="return confirm('Terapkan pembaruan ini sekarang? File lama dibackup otomatis terlebih dahulu.')">
            @csrf
            <div class="mb-3">
              <label for="paket" class="form-label">File paket (.zip)</label>
              <input type="file" class="form-control" id="paket" name="paket" accept=".zip" required>
              <div class="form-text">Paket resmi selalu memuat <code>update-manifest.json</code> di dalamnya. Paket tanpa manifest akan ditolak.</div>
              <div class="form-text d-none" id="info-berkas"></div>
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="konfirmasi_backup_db" name="konfirmasi_backup_db" value="1" required>
              <label class="form-check-label" for="konfirmasi_backup_db">
                Saya paham backup otomatis di sini hanya mencakup <strong>file</strong>. Untuk pembaruan besar, saya sudah mengekspor <strong>database</strong> lewat phpMyAdmin sebagai cadangan.
              </label>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="icon-base ri ri-upload-cloud-2-line me-1"></i>Terapkan Pembaruan
            </button>
          </form>

          <hr>
          <h6 class="mb-2">Yang terjadi saat pembaruan diterapkan</h6>
          <ol class="small text-body-secondary mb-0 ps-3">
            <li>Paket diperiksa: manifest, path menyusup (<code>..</code>), dan area terlarang.</li>
            <li>File yang akan ditimpa/dihapus dibackup ke ZIP di server (daftar di bawah).</li>
            <li>File baru disalin, file usang dihapus, migrasi database dijalankan, cache dibersihkan.</li>
            <li>Versi dan riwayat dicatat di halaman ini.</li>
          </ol>
          <div class="alert alert-warning small mt-3 mb-0">
            Area <strong>tidak pernah disentuh</strong>: <code>.env</code>, folder <code>vendor</code>, folder <code>storage</code>.
            Paket yang mengubah area itu ditolak dan harus dipasang manual — termasuk pembaruan yang menambah library Composer baru.
          </div>
        </div>
      </div>

      {{-- ── Backup & pemulihan ──────────────────────────────────── --}}
      <div class="card mb-4">
        <h5 class="card-header">Backup File &amp; Pemulihan</h5>
        <div class="card-body">
          @if ($daftarBackup->isEmpty())
            <p class="text-body-secondary small mb-0">Belum ada backup. Backup pertama dibuat otomatis pada pembaruan berikutnya.</p>
          @else
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead>
                  <tr>
                    <th>Backup</th>
                    <th class="text-end">Ukuran</th>
                    <th>Dibuat</th>
                    <th class="text-end">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($daftarBackup as $b)
                    <tr>
                      <td><code>{{ $b['nama'] }}</code></td>
                      <td class="text-end">{{ number_format($b['ukuran'] / 1024, 0, ',', '.') }} KB</td>
                      <td>{{ $b['waktu'] }}</td>
                      <td class="text-end">
                        <form method="POST" action="{{ route('pembaruan.pulihkan') }}" class="d-inline form-pulihkan"
                              onsubmit="return confirm('Pulihkan file dari backup ini? File saat ini akan ditimpa oleh isi backup. Migrasi database TIDAK ikut dibatalkan.')">
                          @csrf
                          <input type="hidden" name="backup" value="{{ $b['nama'] }}">
                          <button type="submit" class="btn btn-sm btn-outline-warning btn-pulihkan" data-backup="{{ $b['nama'] }}">
                            <i class="icon-base ri ri-history-line me-1"></i>Pulihkan
                          </button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <p class="small text-body-secondary mt-2 mb-0">Pemulihan hanya mengembalikan <strong>file</strong>; perubahan struktur database (migrasi) yang sudah berjalan tidak dibatalkan.</p>
          @endif
        </div>
      </div>
    </div>

    {{-- ── Riwayat ───────────────────────────────────────────────── --}}
    <div class="col-lg-5">
      <div class="card mb-4">
        <h5 class="card-header">Riwayat Pembaruan</h5>
        <div class="card-body">
          @if (empty($riwayat))
            <p class="text-body-secondary small mb-0">Belum ada riwayat. Pembaruan pertama yang diterapkan lewat halaman ini akan tercatat di sini.</p>
          @else
            <ul class="list-unstyled mb-0">
              @foreach ($riwayat as $r)
                <li class="d-flex gap-3 pb-3 mb-3 border-bottom">
                  <span class="{{ ($r['status'] ?? '') === 'rollback' ? 'bg-label-warning' : 'bg-label-success' }} rounded p-2 d-inline-flex h-100">
                    <i class="icon-base ri {{ ($r['status'] ?? '') === 'rollback' ? 'ri-history-line' : 'ri-checkbox-circle-line' }} icon-20px"></i>
                  </span>
                  <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                      <strong>{{ $r['versi'] }}</strong>
                      <small class="text-body-secondary text-nowrap">{{ $r['diterapkan_pada'] }}</small>
                    </div>
                    @if (!empty($r['deskripsi']))
                      <div class="small text-body-secondary">{{ $r['deskripsi'] }}</div>
                    @endif
                    <div class="small text-body-secondary">
                      {{ $r['file'] ?? 0 }} file ditulis, {{ $r['dihapus'] ?? 0 }} dihapus
                      @if (!empty($r['backup']))
                        · backup <code>{{ $r['backup'] }}</code>
                      @endif
                    </div>
                  </div>
                </li>
              @endforeach
            </ul>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- ── Modal konfirmasi terapkan ──────────────────────────────── --}}
  <div class="modal fade" id="modal-konfirmasi-terapkan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="icon-base ri ri-upload-cloud-2-line me-1"></i>Terapkan Pembaruan?</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <table class="table table-sm table-borderless mb-2">
            <tr>
              <td class="text-body-secondary" style="width: 140px">File paket</td>
              <td><code id="ringkas-nama">—</code></td>
            </tr>
            <tr>
              <td class="text-body-secondary">Ukuran</td>
              <td id="ringkas-ukuran">—</td>
            </tr>
            <tr>
              <td class="text-body-secondary">Versi terpasang</td>
              <td>{{ $terpasang['versi'] ?? 'Pemasangan awal (manual)' }}</td>
            </tr>
          </table>
          <ol class="small text-body-secondary ps-3 mb-3">
            <li>Paket diperiksa (manifest, path menyusup, area terlarang).</li>
            <li>File yang akan ditimpa dibackup otomatis ke ZIP di server.</li>
            <li>File baru dipasang, migrasi database dijalankan, cache dibersihkan.</li>
          </ol>
          <div class="alert alert-warning small mb-0">
            Backup otomatis hanya mencakup <strong>file</strong> — migrasi database yang sudah berjalan
            <strong>tidak bisa dibatalkan</strong> dari halaman ini.
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-primary" id="btn-konfirmasi-terapkan">
            <i class="icon-base ri ri-check-line me-1"></i>Ya, Terapkan Sekarang
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Modal konfirmasi pulihkan ──────────────────────────────── --}}
  <div class="modal fade" id="modal-konfirmasi-pulih" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="icon-base ri ri-history-line me-1"></i>Pulihkan dari Backup?</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <p class="mb-2">File saat ini akan ditimpa oleh isi backup <code id="nama-backup-pulih">—</code>,
            dan file yang dibuat oleh pembaruan itu akan dihapus lagi.</p>
          <div class="alert alert-warning small mb-0">Pemulihan hanya mengembalikan <strong>file</strong>;
            migrasi database <strong>TIDAK</strong> ikut dibatalkan.</div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-warning" id="btn-konfirmasi-pulih">
            <i class="icon-base ri ri-history-line me-1"></i>Ya, Pulihkan
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Modal proses & hasil (progres unggah + status) ─────────── --}}
  <div class="modal fade" id="modal-proses" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="proses-judul">Menerapkan Pembaruan</h5>
        </div>
        <div class="modal-body">
          <div id="proses-berjalan">
            <div class="progress mb-2" style="height: 10px">
              <div id="proses-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
            </div>
            <div class="d-flex justify-content-between small text-body-secondary mb-3">
              <span id="proses-persen">0%</span>
              <span id="proses-ukuran"></span>
            </div>
            <ul class="list-unstyled small mb-2">
              <li id="langkah-unggah" class="mb-1">
                <span class="spinner-border spinner-border-sm me-2" data-putar></span><span data-teks>Mengunggah paket ke server…</span>
              </li>
              <li id="langkah-pasang" class="text-body-secondary">
                <i class="icon-base ri ri-time-line me-2" data-putar></i><span data-teks>Memasang file &amp; menjalankan migrasi…</span>
              </li>
            </ul>
            <div class="small text-body-secondary">Berjalan <span id="proses-detik">0</span> detik — jangan tutup halaman ini.</div>
          </div>
          <div id="proses-hasil" class="d-none"></div>
        </div>
        <div class="modal-footer d-none" id="proses-kaki">
          <button type="button" class="btn btn-primary" id="proses-tombol">Selesai</button>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    (function() {
      'use strict';

      function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
          '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
      }

      function formatUkuran(b) {
        if (!b && b !== 0) return '—';
        if (b >= 1048576) return (b / 1048576).toFixed(2).replace('.', ',') + ' MB';
        return Math.max(1, Math.round(b / 1024)) + ' KB';
      }

      const modalProsesEl = document.getElementById('modal-proses');
      const modalProses = new bootstrap.Modal(modalProsesEl);
      const el = id => document.getElementById(id);
      let timerDetik = null;
      let modeSelesai = 'muat-ulang';

      function setLangkah(id, keadaan) {
        // keadaan: 'jalan' (spinner) | 'selesai' (centang) | 'tunggu' (jam)
        const li = el(id);
        const ikon = li.querySelector('[data-putar]');
        li.classList.toggle('text-body-secondary', keadaan === 'tunggu');
        if (keadaan === 'jalan') {
          ikon.outerHTML = '<span class="spinner-border spinner-border-sm me-2" data-putar></span>';
        } else if (keadaan === 'selesai') {
          ikon.outerHTML = '<i class="icon-base ri ri-checkbox-circle-line me-2 text-success" data-putar></i>';
        } else {
          ikon.outerHTML = '<i class="icon-base ri ri-time-line me-2" data-putar></i>';
        }
      }

      function resetProses(judul, labelPasang) {
        el('proses-judul').textContent = judul;
        el('proses-berjalan').classList.remove('d-none');
        el('proses-hasil').classList.add('d-none');
        el('proses-hasil').innerHTML = '';
        el('proses-kaki').classList.add('d-none');
        el('proses-bar').style.width = '0%';
        el('proses-bar').className = 'progress-bar';
        el('proses-persen').textContent = '0%';
        el('proses-ukuran').textContent = '';
        el('proses-detik').textContent = '0';
        el('langkah-pasang').querySelector('[data-teks]').textContent = labelPasang;
        setLangkah('langkah-unggah', 'jalan');
        setLangkah('langkah-pasang', 'tunggu');
      }

      function mulaiTimer() {
        const mulai = Date.now();
        clearInterval(timerDetik);
        timerDetik = setInterval(() => {
          el('proses-detik').textContent = Math.floor((Date.now() - mulai) / 1000);
        }, 500);
      }

      function tampilBerhasil(laporan) {
        clearInterval(timerDetik);
        el('proses-berjalan').classList.add('d-none');
        let html = '<div class="alert alert-success mb-2"><strong><i class="icon-base ri ri-checkbox-circle-line me-1"></i>' +
          'Selesai: ' + esc(laporan.versi) + '</strong><br><span class="small">' +
          esc(laporan.file) + ' file ditulis, ' + esc(laporan.dihapus) + ' file dihapus. Backup file lama: <code>' +
          esc(laporan.backup) + '</code></span></div>';
        if (laporan.migrasi) {
          html += '<details class="mb-2"><summary class="small fw-medium">Output migrasi database</summary>' +
            '<pre class="small bg-body-tertiary rounded p-2 mt-1 mb-0" style="max-height: 180px; overflow: auto; white-space: pre-wrap;">' +
            esc(laporan.migrasi) + '</pre></details>';
        }
        if (Array.isArray(laporan.peringatan) && laporan.peringatan.length) {
          html += '<div class="alert alert-warning small mb-0"><strong>Ada peringatan:</strong><ul class="mb-0 ps-3">' +
            laporan.peringatan.map(p => '<li>' + esc(p) + '</li>').join('') + '</ul></div>';
        }
        el('proses-hasil').innerHTML = html;
        el('proses-hasil').classList.remove('d-none');
        modeSelesai = 'muat-ulang';
        el('proses-tombol').textContent = 'Selesai — Muat Ulang Halaman';
        el('proses-kaki').classList.remove('d-none');
      }

      function tampilGagal(pesan) {
        clearInterval(timerDetik);
        el('proses-berjalan').classList.add('d-none');
        el('proses-hasil').innerHTML = '<div class="alert alert-danger mb-0"><strong>' +
          '<i class="icon-base ri ri-error-warning-line me-1"></i>Gagal.</strong><br><span class="small">' +
          esc(pesan) + '</span></div>';
        el('proses-hasil').classList.remove('d-none');
        modeSelesai = 'tutup';
        el('proses-tombol').textContent = 'Tutup';
        el('proses-kaki').classList.remove('d-none');
      }

      el('proses-tombol').addEventListener('click', () => {
        if (modeSelesai === 'muat-ulang') {
          location.reload();
        } else {
          modalProses.hide();
        }
      });

      // Kirim formulir via XHR: progres unggah nyata dari browser,
      // lalu fase proses (server memasang + migrasi) sampai jawaban tiba.
      function kirimForm(formEl, opsi) {
        resetProses(opsi.judul, opsi.labelPasang);
        if (!opsi.unggah) {
          el('langkah-unggah').querySelector('[data-teks]').textContent = 'Menghubungi server…';
          el('proses-bar').style.width = '100%';
          el('proses-bar').classList.add('progress-bar-striped', 'progress-bar-animated');
          el('proses-persen').textContent = '';
        }
        modalProses.show();
        mulaiTimer();

        const xhr = new XMLHttpRequest();
        xhr.open('POST', formEl.action);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');

        if (opsi.unggah) {
          xhr.upload.addEventListener('progress', e => {
            if (!e.lengthComputable) return;
            const persen = Math.round(e.loaded / e.total * 100);
            el('proses-bar').style.width = persen + '%';
            el('proses-persen').textContent = persen + '%';
            el('proses-ukuran').textContent = formatUkuran(e.loaded) + ' dari ' + formatUkuran(e.total);
          });
          xhr.upload.addEventListener('load', () => {
            setLangkah('langkah-unggah', 'selesai');
            setLangkah('langkah-pasang', 'jalan');
            el('proses-bar').style.width = '100%';
            el('proses-bar').classList.add('progress-bar-striped', 'progress-bar-animated');
            el('proses-persen').textContent = 'Terunggah — server sedang memasang';
          });
        } else {
          setLangkah('langkah-unggah', 'selesai');
          setLangkah('langkah-pasang', 'jalan');
        }

        xhr.addEventListener('load', () => {
          let data = null;
          try { data = JSON.parse(xhr.responseText); } catch (e) { /* bukan JSON */ }
          if (xhr.status === 200 && data && data.ok) {
            tampilBerhasil(data.laporan || {});
          } else if (data && data.ok === false && data.pesan) {
            tampilGagal(data.pesan);
          } else if (data && data.errors) {
            const semua = Object.values(data.errors).flat();
            tampilGagal(semua.join(' ') || data.message || 'Validasi gagal.');
          } else if (xhr.status === 419) {
            tampilGagal('Sesi login berakhir (419). Muat ulang halaman, masuk lagi, lalu coba kembali.');
          } else {
            tampilGagal('Server menjawab ' + xhr.status + '. Pembaruan mungkin sebenarnya sudah terpasang ' +
              'bila putusnya di tengah migrasi — muat ulang halaman ini dan periksa Versi Terpasang & Riwayat sebelum mencoba lagi.');
          }
        });
        xhr.addEventListener('error', () => {
          tampilGagal('Koneksi terputus di tengah jalan. Muat ulang halaman dan periksa Versi Terpasang & ' +
            'Riwayat terlebih dahulu — pembaruan mungkin sudah terpasang sebagian atau seluruhnya.');
        });

        xhr.send(new FormData(formEl));
      }

      // ── Terapkan: konfirmasi dulu, baru kirim ───────────────────
      const form = document.getElementById('form-terapkan');
      if (form) {
        form.removeAttribute('onsubmit');
        const inputPaket = document.getElementById('paket');
        const infoBerkas = document.getElementById('info-berkas');
        const ceklis = document.getElementById('konfirmasi_backup_db');
        const modalKonfirmasi = new bootstrap.Modal(document.getElementById('modal-konfirmasi-terapkan'));

        inputPaket.addEventListener('change', () => {
          const f = inputPaket.files[0];
          if (f) {
            infoBerkas.textContent = 'Terpilih: ' + f.name + ' (' + formatUkuran(f.size) + ')';
            infoBerkas.classList.remove('d-none');
            el('ringkas-nama').textContent = f.name;
            el('ringkas-ukuran').textContent = formatUkuran(f.size);
          } else {
            infoBerkas.classList.add('d-none');
          }
        });

        form.addEventListener('submit', e => {
          e.preventDefault();
          if (!inputPaket.files.length) {
            inputPaket.focus();
            return;
          }
          if (!ceklis.checked) {
            ceklis.focus();
            ceklis.classList.add('is-invalid');
            setTimeout(() => ceklis.classList.remove('is-invalid'), 1600);
            return;
          }
          modalKonfirmasi.show();
        });

        el('btn-konfirmasi-terapkan').addEventListener('click', () => {
          modalKonfirmasi.hide();
          kirimForm(form, {
            judul: 'Menerapkan Pembaruan',
            labelPasang: 'Memasang file & menjalankan migrasi…',
            unggah: true
          });
        });
      }

      // ── Pulihkan: konfirmasi dulu, baru kirim ───────────────────
      const modalPulih = new bootstrap.Modal(document.getElementById('modal-konfirmasi-pulih'));
      let formPulihAktif = null;
      document.querySelectorAll('.form-pulihkan').forEach(f => {
        f.removeAttribute('onsubmit');
        f.addEventListener('submit', e => {
          e.preventDefault();
          formPulihAktif = f;
          const tombol = f.querySelector('.btn-pulihkan');
          el('nama-backup-pulih').textContent = tombol ? tombol.dataset.backup : '';
          modalPulih.show();
        });
      });
      el('btn-konfirmasi-pulih').addEventListener('click', () => {
        if (!formPulihAktif) return;
        modalPulih.hide();
        kirimForm(formPulihAktif, {
          judul: 'Memulihkan Backup',
          labelPasang: 'Mengembalikan file dari backup…',
          unggah: false
        });
      });
    })();
  </script>
@endsection
