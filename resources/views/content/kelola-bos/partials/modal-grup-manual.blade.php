{{--
  Modal grup manual SPJ (gabung / pecah nota) — lapisan
  pengelompokan SPJ di atas pengelompokan bawaan ARKAS:
  - Modal Gabung Nota: beberapa grup (nota / bukti /
    grup manual) digabung menjadi satu grup manual
    bernama alias.
  - Modal Pecah Nota: satu grup dipecah menjadi
    beberapa grup manual, tiap bagian bernama alias.
  - Modal Isi Grup: daftar baris anggota satu grup
    manual (dibuka dari nama alias di tabel BKU), ganti
    nama alias, dan kembalikan ke pengelompokan ARKAS.
  Skrip di partial ini juga mengelola centang baris
  (.centang-baris) dan bilah aksi #bilah-grup-manual di
  tabel BKU. Semua data lewat endpoint JSON
  /kelola-bos/bku/... (grup-anggota & grup-manual) —
  konteks sekolah berasal dari baris BKU di sisi server.
  Kepala Sekolah & Bendahara boleh MELIHAT isi grup;
  aksi tulis hanya untuk Admin & Operator Sekolah
  ($bisaTulisPenerima) dan divalidasi ulang di server.
--}}

{{-- Modal Gabung Nota --}}
<div class="modal fade" id="modal-gabung-nota" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri ri-link me-2"></i>Gabung Nota</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" id="gabung-galat"></div>
        <p class="small text-muted">Seluruh baris dari setiap nota/grup yang tersentuh pilihan akan ikut digabung
          menjadi satu grup SPJ. Penerima dari grup-grup asal ikut digabung.</p>
        <p class="small" id="gabung-ringkasan"></p>
        <div class="mb-2">
          <label class="form-label" for="gabung-nama">Nama alias grup gabungan</label>
          <input type="text" class="form-control" id="gabung-nama" maxlength="120"
            placeholder="cth. Belanja ATK gabungan Januari">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="btn-simpan-gabung">Gabungkan</button>
      </div>
    </div>
  </div>
</div>

{{-- Modal Pecah Nota --}}
<div class="modal fade" id="modal-pecah-nota" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri ri-scissors-line me-2"></i>Pecah Nota</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" id="pecah-galat"></div>
        <p class="small text-muted" id="pecah-info">Memuat isi grup…</p>
        <div id="pecah-daftar"></div>
        <div id="pecah-nama-wadah" class="mt-3"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-warning" id="btn-simpan-pecah">Pecahkan</button>
      </div>
    </div>
  </div>
</div>

{{-- Modal Isi Grup --}}
<div class="modal fade" id="modal-isi-grup" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="isi-grup-judul">Isi Grup</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-none" id="isi-grup-galat"></div>
        <p class="small" id="isi-grup-info">Memuat…</p>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>No Bukti</th>
                <th>Uraian</th>
                <th class="text-end">Nominal</th>
              </tr>
            </thead>
            <tbody id="isi-grup-tbody"></tbody>
            <tfoot>
              <tr>
                <td colspan="3" class="text-end fw-semibold">Total</td>
                <td class="text-end fw-bold" id="isi-grup-total"></td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div id="isi-grup-spj" class="mt-3 d-none">
          <div class="d-flex flex-wrap align-items-center gap-3">
            <span class="fw-semibold small">Keterangan SPJ</span>
            <span id="isi-grup-status-turunan" class="d-none"></span>
            <div class="form-check form-switch mb-0" id="isi-grup-status-wrap">
              <input class="form-check-input" type="checkbox" role="switch" id="isi-grup-status">
              <label class="form-check-label small" for="isi-grup-status">Selesai</label>
            </div>
          </div>
          <div id="isi-grup-va" class="mt-2 d-none">
            <label class="form-label small fw-semibold" for="isi-grup-va-input">Nomor VA SIPLah</label>
            <div class="d-flex gap-2">
              <input type="text" id="isi-grup-va-input" class="form-control form-control-sm"
                placeholder="Nomor virtual account pembayaran SIPLah" maxlength="50">
            </div>
            <div class="small text-muted mt-1">Nomor VA tidak tercatat di ARKAS — isian manual ini tersimpan
              sebagai keterangan SPJ untuk nota ini.</div>
          </div>
          <div class="mt-2">
            <button type="button" id="isi-grup-spj-simpan" class="btn btn-sm btn-primary">Simpan Keterangan</button>
          </div>
          <div class="alert alert-danger py-2 px-3 mt-2 d-none" id="isi-grup-spj-gagal"></div>
        </div>
        <div id="isi-grup-tulis" class="d-none mt-4">
          <hr>
          <div class="mb-3">
            <label class="form-label" for="isi-grup-nama">Nama alias grup</label>
            <div class="input-group">
              <input type="text" class="form-control" id="isi-grup-nama" maxlength="120">
              <button type="button" class="btn btn-outline-primary" id="btn-simpan-nama-grup">Simpan Nama</button>
            </div>
          </div>
          <button type="button" class="btn btn-outline-danger" id="btn-batal-grup">Kembalikan ke Pengelompokan
            ARKAS</button>
          <p class="small text-muted mt-2 mb-0">Mengembalikan ke pengelompokan ARKAS akan memindahkan penerima
            &amp; status grup ini ke grup nota baris pertamanya.</p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>
  (function() {
    var urlDasarGrup = @json(url('/kelola-bos/bku'));
    var bisaTulisGrup = @json($bisaTulisPenerima ?? false);

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

    async function kirimGrup(url, method, payload) {
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

    function tampilGalat(idEl, pesan) {
      var el = $(idEl);
      if (!el) return;
      el.textContent = pesan || '';
      el.classList.toggle('d-none', !pesan);
    }

    // ── Pilihan centang & bilah aksi ──────────────────
    // Tiap centang mewakili SATU grup tampilan: baris
    // biasa mewakili grup ARKAS-nya (data-anggota = id
    // sendiri), baris grup manual mewakili seluruh
    // anggotanya (data-anggota = daftar id anggota).
    function pilihanSaatIni() {
      var hasil = [];
      document.querySelectorAll('.centang-baris:checked').forEach(function(cb) {
        var anggota = (cb.getAttribute('data-anggota') || cb.value)
          .split(',')
          .map(function(s) {
            return parseInt(s, 10);
          })
          .filter(function(n) {
            return !isNaN(n);
          });
        hasil.push({
          id: parseInt(cb.value, 10),
          kunci: cb.getAttribute('data-kunci') || '',
          anggota: anggota
        });
      });
      return hasil;
    }

    function kunciUnikPilihan(pilihan) {
      var peta = {};
      pilihan.forEach(function(p) {
        if (p.kunci) peta[p.kunci] = true;
      });
      return Object.keys(peta);
    }

    function segarkanBilah() {
      var info = $('info-pilihan-grup');
      var btnGabung = $('btn-gabung-nota');
      var btnPecah = $('btn-pecah-nota');
      if (!info || !btnGabung || !btnPecah) return;
      var pilihan = pilihanSaatIni();
      var jmlGrup = kunciUnikPilihan(pilihan).length;
      if (!pilihan.length) {
        info.textContent = 'Centang baris belanja untuk menggabung atau memecah nota.';
      } else if (jmlGrup < 2) {
        info.textContent = '1 grup dipilih (' + pilihan.length + ' baris tampilan) — baris-baris dalam satu ' +
          'nota sudah menjadi satu grup, jadi Gabung Nota perlu centang dari minimal 2 nota/grup berbeda. ' +
          'Untuk membagi grup ini, gunakan Pecah Nota.';
      } else {
        info.textContent = jmlGrup + ' grup dipilih (' + pilihan.length +
          ' baris tampilan) — siap digabung menjadi satu grup.';
      }
      btnGabung.disabled = jmlGrup < 2;
      btnPecah.disabled = jmlGrup !== 1;
    }

    function ikatCentangBku() {
      document.querySelectorAll('.centang-baris').forEach(function(cb) {
        cb.addEventListener('change', segarkanBilah);
      });

      var centangSemua = $('centang-semua-baris');
      if (centangSemua && !centangSemua.dataset.terikat) {
        centangSemua.dataset.terikat = '1';
        centangSemua.addEventListener('change', function() {
          document.querySelectorAll('.centang-baris').forEach(function(cb) {
            cb.checked = centangSemua.checked;
          });
          segarkanBilah();
        });
      }
    }
    ikatCentangBku();
    document.addEventListener('bku:tabel-dimuat', ikatCentangBku);

    // ── Gabung nota ───────────────────────────────────
    var btnGabung = $('btn-gabung-nota');
    if (btnGabung) {
      btnGabung.addEventListener('click', function() {
        var pilihan = pilihanSaatIni();
        $('gabung-ringkasan').textContent = kunciUnikPilihan(pilihan).length +
          ' grup sumber dipilih — seluruh baris dari tiap grup ikut digabung, termasuk baris yang tidak tampil di halaman ini.';
        $('gabung-nama').value = '';
        tampilGalat('gabung-galat', '');
        bootstrap.Modal.getOrCreateInstance($('modal-gabung-nota')).show();
      });
    }

    var btnSimpanGabung = $('btn-simpan-gabung');
    if (btnSimpanGabung) {
      btnSimpanGabung.addEventListener('click', async function() {
        var nama = $('gabung-nama').value.trim();
        if (!nama) {
          tampilGalat('gabung-galat', 'Nama alias wajib diisi.');
          return;
        }
        var ids = pilihanSaatIni().map(function(p) {
          return p.id;
        });
        try {
          await kirimGrup(urlDasarGrup + '/grup-manual/gabung', 'POST', {
            nama: nama,
            baris_ids: ids
          });
          location.reload();
        } catch (e) {
          tampilGalat('gabung-galat', e.message);
        }
      });
    }

    // ── Pecah nota ────────────────────────────────────
    var pecahBarisId = null;
    var pecahNama = {}; // nomor bagian => nama yang diketik

    function bagianTerpakai() {
      var pakai = {};
      document.querySelectorAll('.pecah-bagian-sel').forEach(function(sel) {
        pakai[sel.value] = true;
      });
      return Object.keys(pakai).map(function(k) {
        return parseInt(k, 10);
      }).sort(function(a, b) {
        return a - b;
      });
    }

    function simpanNamaBagianDariInput() {
      document.querySelectorAll('.pecah-nama-input').forEach(function(inp) {
        pecahNama[inp.getAttribute('data-bagian')] = inp.value;
      });
    }

    function gambarNamaBagian() {
      var wadah = $('pecah-nama-wadah');
      if (!wadah) return;
      simpanNamaBagianDariInput();
      var html = '';
      bagianTerpakai().forEach(function(n) {
        html += '<div class="mb-2"><label class="form-label">Nama Bagian ' + n + '</label>' +
          '<input type="text" class="form-control pecah-nama-input" data-bagian="' + n +
          '" maxlength="120" value="' + lolosHtml(pecahNama[n] || '') + '"></div>';
      });
      wadah.innerHTML = html;
    }

    async function bukaPecah(barisId, idTerpilih) {
      pecahBarisId = barisId;
      pecahNama = {};
      tampilGalat('pecah-galat', '');
      $('pecah-info').textContent = 'Memuat isi grup…';
      $('pecah-daftar').innerHTML = '';
      $('pecah-nama-wadah').innerHTML = '';
      bootstrap.Modal.getOrCreateInstance($('modal-pecah-nota')).show();
      try {
        var res = await kirimGrup(urlDasarGrup + '/' + barisId + '/grup-anggota', 'GET');
        var g = res.grup || {};
        $('pecah-info').textContent = 'Grup berisi ' + (g.jumlah_baris || 0) + ' baris, total ' +
          fmtRp(g.total || 0) + '. Tentukan bagian tiap baris, lalu beri nama tiap bagian. ' +
          'Penerima & status grup asal diwariskan ke bagian yang memuat baris pertama.';
        var html = '';
        (res.baris || []).forEach(function(r) {
          // Penugasan awal: grup manual → semua Bagian 1;
          // grup ARKAS → baris tercentang Bagian 1, sisanya
          // Bagian 2.
          var awal = 1;
          if (!g.manual) {
            awal = idTerpilih.indexOf(r.id) >= 0 ? 1 : 2;
          }
          var opsi = '';
          [1, 2, 3, 4, 5].forEach(function(n) {
            opsi += '<option value="' + n + '"' + (n === awal ? ' selected' : '') + '>Bagian ' + n + '</option>';
          });
          html += '<div class="d-flex align-items-center gap-2 py-1 border-bottom">' +
            '<div class="flex-grow-1"><div>' + lolosHtml(r.uraian || '-') + '</div>' +
            '<div class="small text-muted">' + lolosHtml(r.no_bukti || '-') + ' &bull; ' +
            lolosHtml(r.tanggal || '') +
            (r.kode_rekening ? ' &bull; ' + lolosHtml(r.kode_rekening) : '') + '</div></div>' +
            '<div class="text-end fw-semibold text-nowrap">' + fmtRp(r.nominal) + '</div>' +
            '<select class="form-select form-select-sm pecah-bagian-sel" style="width: 128px;" data-baris-id="' +
            r.id + '">' + opsi + '</select></div>';
        });
        $('pecah-daftar').innerHTML = html;
        $('pecah-daftar').querySelectorAll('.pecah-bagian-sel').forEach(function(sel) {
          sel.addEventListener('change', gambarNamaBagian);
        });
        gambarNamaBagian();
      } catch (e) {
        tampilGalat('pecah-galat', e.message);
      }
    }

    var btnPecah = $('btn-pecah-nota');
    if (btnPecah) {
      btnPecah.addEventListener('click', function() {
        var pilihan = pilihanSaatIni();
        if (!pilihan.length) return;
        var idTerpilih = [];
        pilihan.forEach(function(p) {
          p.anggota.forEach(function(a) {
            if (idTerpilih.indexOf(a) < 0) idTerpilih.push(a);
          });
        });
        bukaPecah(pilihan[0].id, idTerpilih);
      });
    }

    var btnSimpanPecah = $('btn-simpan-pecah');
    if (btnSimpanPecah) {
      btnSimpanPecah.addEventListener('click', async function() {
        simpanNamaBagianDariInput();
        var perBagian = {};
        document.querySelectorAll('.pecah-bagian-sel').forEach(function(sel) {
          var n = sel.value;
          if (!perBagian[n]) perBagian[n] = [];
          perBagian[n].push(parseInt(sel.getAttribute('data-baris-id'), 10));
        });
        var nomor = Object.keys(perBagian);
        if (nomor.length < 2) {
          tampilGalat('pecah-galat', 'Pembagian harus menghasilkan minimal dua bagian.');
          return;
        }
        var bagian = [];
        for (var i = 0; i < nomor.length; i++) {
          var nama = (pecahNama[nomor[i]] || '').trim();
          if (!nama) {
            tampilGalat('pecah-galat', 'Nama Bagian ' + nomor[i] + ' wajib diisi.');
            return;
          }
          bagian.push({
            nama: nama,
            baris_ids: perBagian[nomor[i]]
          });
        }
        try {
          await kirimGrup(urlDasarGrup + '/grup-manual/pecah', 'POST', {
            baris_id: pecahBarisId,
            bagian: bagian
          });
          location.reload();
        } catch (e) {
          tampilGalat('pecah-galat', e.message);
        }
      });
    }

    // ── Isi grup ──────────────────────────────────────
    var isiGrupId = null; // id baris perwakilan grup yang sedang dibuka
    var isiGrupManualId = null;
    var isiGrupSpjBarisId = null; // id baris untuk simpan keterangan SPJ
    var isiGrupSpjStatus = 'belum'; // status meta terakhir dari payload grup
    var isiGrupSpjJumlahPenerima = 0; // jumlah penerima grup terakhir
    var isiGrupSpjSiplah = false; // grup terakhir SIPLah atau bukan

    async function bukaIsiGrup(barisId) {
      isiGrupId = barisId;
      isiGrupManualId = null;
      tampilGalat('isi-grup-galat', '');
      $('isi-grup-judul').textContent = 'Isi Grup';
      $('isi-grup-info').textContent = 'Memuat…';
      $('isi-grup-tbody').innerHTML = '';
      $('isi-grup-total').textContent = '';
      $('isi-grup-tulis').classList.add('d-none');
      $('isi-grup-spj').classList.add('d-none');
      isiGrupSpjBarisId = null;
      bootstrap.Modal.getOrCreateInstance($('modal-isi-grup')).show();
      try {
        var res = await kirimGrup(urlDasarGrup + '/' + barisId + '/grup-anggota', 'GET');
        var g = res.grup || {};
        isiGrupManualId = g.grup_manual_id || null;
        $('isi-grup-judul').textContent = g.nama_alias ||
          (res.baris && res.baris[0] ? res.baris[0].uraian : 'Isi Grup');
        var lencana = '';
        if (g.manual) {
          lencana = '<span class="badge bg-primary">Grup Manual</span> ' +
            '<span class="badge bg-label-secondary">' + (g.jenis === 'gabungan' ? 'Gabungan' : 'Pecahan') + '</span> ';
        }
        var infoTeks = (g.jumlah_baris || 0) + ' baris';
        if (!g.manual && res.baris && res.baris[0] && res.baris[0].no_bukti) {
          infoTeks += ' &bull; No Bukti ' + lolosHtml(res.baris[0].no_bukti);
        }
        infoTeks += ' &bull; total ' + fmtRp(g.total || 0);
        $('isi-grup-info').innerHTML = lencana + infoTeks;
        var html = '';
        (res.baris || []).forEach(function(r) {
          html += '<tr><td class="text-nowrap">' + lolosHtml(r.tanggal || '') + '</td>' +
            '<td>' + lolosHtml(r.no_bukti || '-') + '</td>' +
            '<td>' + lolosHtml(r.uraian || '-') +
            (r.nama_rekening ?
              '<div class="small text-muted">' + lolosHtml(r.kode_rekening || '') + ' — ' +
              lolosHtml(r.nama_rekening) + '</div>' :
              '') +
            '</td>' +
            '<td class="text-end">' + fmtRp(r.nominal) + '</td></tr>';
        });
        $('isi-grup-tbody').innerHTML = html;
        $('isi-grup-total').textContent = fmtRp(g.total || 0);
        // Keterangan SPJ (status & VA) hanya untuk peran
        // yang boleh menulis; peran baca-saja melihat
        // status dari lencana di tabel BKU. Status grup
        // berpenerima diturunkan dari penanda selesai
        // penerima — saklar diganti teks turunan; grup
        // tanpa penerima memakai saklar meta seperti
        // biasa. Isian VA hanya untuk grup SIPLah.
        isiGrupSpjStatus = g.status || 'belum';
        isiGrupSpjJumlahPenerima = Number(g.jumlah_penerima || 0);
        isiGrupSpjSiplah = !!g.siplah;
        var tampilSpj = !!res.bisa_tulis;
        $('isi-grup-spj').classList.toggle('d-none', !tampilSpj);
        if (tampilSpj) {
          isiGrupSpjBarisId = g.baris_id;
          $('isi-grup-spj-gagal').classList.add('d-none');
          var turunanSpj = $('isi-grup-status-turunan');
          var bungkusStatusSpj = $('isi-grup-status-wrap');
          if (isiGrupSpjJumlahPenerima > 0) {
            var stSpj = g.status_tampil || 'belum';
            var teksSpj = stSpj === 'selesai' ? 'Selesai' :
              (stSpj === 'sebagian' ?
                'Selesai Sebagian — ' + Number(g.jumlah_selesai || 0) + ' dari ' +
                isiGrupSpjJumlahPenerima + ' penerima' :
                'Belum Selesai');
            var kelasSpj = stSpj === 'selesai' ? 'bg-success' :
              (stSpj === 'sebagian' ? 'bg-warning text-dark' : 'bg-secondary');
            turunanSpj.innerHTML = '<span class="badge ' + kelasSpj + '">' + lolosHtml(teksSpj) + '</span>' +
              ' <span class="small text-muted">diatur per penerima</span>';
            turunanSpj.classList.remove('d-none');
            bungkusStatusSpj.classList.add('d-none');
          } else {
            turunanSpj.classList.add('d-none');
            turunanSpj.innerHTML = '';
            bungkusStatusSpj.classList.remove('d-none');
            $('isi-grup-status').checked = (isiGrupSpjStatus === 'selesai');
          }
          $('isi-grup-va').classList.toggle('d-none', !isiGrupSpjSiplah);
          if (isiGrupSpjSiplah) {
            $('isi-grup-va-input').value = g.nomor_va || '';
          }
          // Tombol simpan hanya berguna bila ada yang bisa
          // diubah: saklar status (grup tanpa penerima)
          // atau isian VA (grup SIPLah).
          var adaYangDisimpan = isiGrupSpjJumlahPenerima === 0 || isiGrupSpjSiplah;
          $('isi-grup-spj-simpan').classList.toggle('d-none', !adaYangDisimpan);
        } else {
          isiGrupSpjBarisId = null;
        }
        if (bisaTulisGrup && g.manual) {
          $('isi-grup-nama').value = g.nama_alias || '';
          $('isi-grup-tulis').classList.remove('d-none');
        }
      } catch (e) {
        tampilGalat('isi-grup-galat', e.message);
      }
    }

    document.addEventListener('click', function(e) {
      var btn = e.target.closest('[data-grup-anggota]');
      if (!btn) return;
      bukaIsiGrup(parseInt(btn.getAttribute('data-id'), 10));
    });

    // Perbarui lencana status & VA pada baris tabel grup
    // yang uraiannya diklik (tanpa muat ulang halaman):
    // status menurut status_tampil (Selesai / Selesai
    // Sebagian / Belum Selesai), VA seperti sebelumnya.
    function segarkanMetaDiBaris(barisId, statusTampil, nomorVa) {
      var btn = document.querySelector('[data-grup-anggota][data-id="' + barisId + '"]');
      var tr = btn ? btn.closest('tr') : null;
      var wadah = tr ? tr.querySelector('.meta-grup-baris') : null;
      if (!wadah) return;
      var lencanaStatus = wadah.querySelector('.lencana-status-grup');
      if (!lencanaStatus) {
        lencanaStatus = document.createElement('span');
        var vaAda = wadah.querySelector('.lencana-va-grup');
        if (vaAda) {
          wadah.insertBefore(lencanaStatus, vaAda);
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
      var lencanaVa = wadah.querySelector('.lencana-va-grup');
      if (nomorVa) {
        if (!lencanaVa) {
          lencanaVa = document.createElement('span');
          lencanaVa.className = 'small text-muted lencana-va-grup';
          wadah.appendChild(lencanaVa);
        }
        lencanaVa.textContent = 'VA: ' + nomorVa;
      } else if (lencanaVa) {
        lencanaVa.remove();
      }
    }

    // Simpan keterangan SPJ grup lewat endpoint meta grup:
    // status dari saklar (grup tanpa penerima) atau status
    // payload terakhir apa adanya (grup berpenerima —
    // status tampilnya diturunkan dari penerima, bukan
    // dari meta); nomor VA hanya dikirim bila grup SIPLah.
    $('isi-grup-spj-simpan').addEventListener('click', async function() {
      if (!isiGrupSpjBarisId) return;
      var btn = $('isi-grup-spj-simpan');
      var kotakGagal = $('isi-grup-spj-gagal');
      kotakGagal.classList.add('d-none');
      btn.disabled = true;
      try {
        var payloadSpj = {
          status: isiGrupSpjJumlahPenerima > 0 ?
            isiGrupSpjStatus :
            ($('isi-grup-status').checked ? 'selesai' : 'belum')
        };
        if (isiGrupSpjSiplah) {
          payloadSpj.nomor_va = $('isi-grup-va-input').value.trim() || null;
        }
        var res = await kirimGrup(urlDasarGrup + '/' + isiGrupSpjBarisId + '/penerima-grup', 'PUT', payloadSpj);
        isiGrupSpjStatus = res.status || isiGrupSpjStatus;
        segarkanMetaDiBaris(isiGrupSpjBarisId, res.status_tampil, res.nomor_va);
        btn.textContent = 'Tersimpan';
        setTimeout(function() { btn.textContent = 'Simpan Keterangan'; }, 1500);
      } catch (e) {
        kotakGagal.textContent = (e && e.message) || 'Gagal menyimpan keterangan SPJ.';
        kotakGagal.classList.remove('d-none');
      } finally {
        btn.disabled = false;
      }
    });

    var btnNamaGrup = $('btn-simpan-nama-grup');
    if (btnNamaGrup) {
      btnNamaGrup.addEventListener('click', async function() {
        if (!isiGrupManualId) return;
        var nama = $('isi-grup-nama').value.trim();
        if (!nama) {
          tampilGalat('isi-grup-galat', 'Nama alias wajib diisi.');
          return;
        }
        try {
          await kirimGrup(urlDasarGrup + '/grup-manual/' + isiGrupManualId + '/nama', 'POST', {
            nama: nama
          });
          location.reload();
        } catch (e) {
          tampilGalat('isi-grup-galat', e.message);
        }
      });
    }

    var btnBatalGrup = $('btn-batal-grup');
    if (btnBatalGrup) {
      btnBatalGrup.addEventListener('click', async function() {
        if (!isiGrupId) return;
        if (!confirm('Kembalikan grup ini ke pengelompokan ARKAS? Penerima & status grup ini akan dipindahkan ke grup nota baris pertamanya.')) return;
        try {
          await kirimGrup(urlDasarGrup + '/grup-manual/batal', 'POST', {
            baris_id: isiGrupId
          });
          location.reload();
        } catch (e) {
          tampilGalat('isi-grup-galat', e.message);
        }
      });
    }

    segarkanBilah();
  })();
</script>
