{{--
  Modal editor penerima SPJ (Fase 2) — daftar penerima
  per grup transaksi BKU (grup yang sama dengan kwitansi:
  per nota / nomor bukti). Editor kini berupa TABEL
  INLINE: semua isian penerima diisi langsung pada sel
  tabel dan setiap baris tersimpan OTOMATIS per baris
  lewat endpoint JSON yang sama. Nama penerima berupa
  combobox pencarian dari tiga sumber: PTK, tabel
  penerima non-PTK, dan TOKO (nama_toko dari nota-nota
  sekolah ini). Kolom jenis membedakan penerima 'orang'
  dari 'toko' — baris toko tidak memakai isian
  perorangan (NIP, pangkat, NIK). Semuanya tersimpan
  sebagai snapshot di bos_spj_penerima.
  Tombol pemicu: elemen dengan atribut data-penerima-baris
  dan data-id (id baris BKU). Semua data lewat endpoint
  JSON /kelola-bos/bku/{id}/penerima... — konteks sekolah
  berasal dari baris itu di sisi server.
  Kepala Sekolah & Bendahara boleh membuka (baca), tetapi
  panel tulis hanya dirender untuk Admin & Operator
  Sekolah ($bisaTulisPenerima).
--}}
<div class="modal fade" id="modal-penerima" tabindex="-1" data-bisa-tulis="{{ $bisaTulisPenerima ? '1' : '0' }}">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri ri-team-line me-2"></i>Penerima SPJ</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-secondary py-2 px-3" id="penerima-info-grup">
          <span class="text-muted">Memuat…</span>
        </div>

        {{-- Meta grup SPJ: saklar status Belum/Selesai
             (tersimpan otomatis, hanya peran tulis) dan nomor
             VA untuk grup SIPLah — isian manual karena data
             ARKAS/SIPLah hanya menyediakan nomor invoice.
             Peran baca melihatnya sebagai lencana teks. --}}
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
              @if ($bisaTulisPenerima)
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" id="meta-status-saklar">
                  <label class="form-check-label" for="meta-status-saklar">Selesai</label>
                </div>
              @endif
              <span id="meta-status-baca"></span>
            </div>
            <div>
              <div class="small text-muted mb-1">Kategori</div>
              @if ($bisaTulisPenerima)
                <select class="form-select form-select-sm" id="meta-kategori-pilih" style="min-width: 230px;">
                  <option value="">Otomatis (ikut deteksi)</option>
                  <option value="barang">Barang</option>
                  <option value="barang_konsumsi">Barang Konsumsi</option>
                  <option value="non_barang">Non-barang</option>
                </select>
                <div class="form-text" id="meta-kategori-info"></div>
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
              Status grup tanpa penerima tersimpan otomatis begitu diganti; grup yang punya penerima
              mengikuti saklar selesai per penerima di tabel bawah.
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
                <th class="text-end" style="width: 6rem;">Vol</th>
                <th class="text-end" style="width: 9rem;">Satuan</th>
                <th class="text-end" style="width: 10rem;">Jumlah</th>
                <th class="text-end" style="width: 9rem;" data-kolom-pajak="ppn">PPN Rp</th>
                <th class="text-end" style="width: 9rem;" data-kolom-pajak="pph">PPh Rp</th>
                <th class="text-end" style="width: 9rem;" data-kolom-pajak="sspd">SSPD Rp</th>
                <th class="text-end" style="width: 9rem;" data-kolom-pajak="admin">Biaya Admin</th>
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
                <td colspan="{{ $bisaTulisPenerima ? 13 : 12 }}" class="text-center text-muted">Memuat…</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="fw-semibold">
                <td colspan="4" class="text-end">Total</td>
                <td class="text-end" id="tot-jumlah">Rp 0</td>
                <td class="text-end" id="tot-ppn" data-kolom-pajak="ppn"></td>
                <td class="text-end" id="tot-pph" data-kolom-pajak="pph"></td>
                <td class="text-end" id="tot-sspd" data-kolom-pajak="sspd"></td>
                <td class="text-end" id="tot-admin" data-kolom-pajak="admin"></td>
                <td class="text-end" id="tot-diterima">Rp 0</td>
                <td colspan="{{ $bisaTulisPenerima ? 3 : 2 }}"></td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div class="small mb-3" id="penerima-selisih"></div>

        @if ($bisaTulisPenerima)
          <div class="mb-3">
            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-baris-penerima-baru">
              <i class="ri ri-add-line me-1"></i>Tambah Penerima</button>
            <span class="form-text ms-2">Baris tersimpan otomatis saat diisi. Nama bisa dipilih dari daftar
              (PTK, tabel penerima, toko) atau diketik baru.</span>
          </div>
        @endif

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>
  (function() {
    var modalEl = document.getElementById('modal-penerima');
    if (!modalEl) return;

    var bisaTulis = modalEl.getAttribute('data-bisa-tulis') === '1';
    var urlDasar = @json(url('/kelola-bos/bku'));
    var barisId = null;
    var data = null;

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

    function berhasil(res) {
      Swal.fire('Berhasil', (res && res.message) || 'Tersimpan.', 'success');
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

    // Bidang potongan yang tampil menurut kategori grup
    // efektif: barang -> PPN; barang konsumsi -> PPh &
    // SSPD; non-barang -> PPh. Biaya admin selalu tampil.
    function bidangPajakTampil() {
      var kat = (data && data.grup && data.grup.kategori) || 'barang';
      return {
        ppn: kat === 'barang',
        pph: kat === 'barang_konsumsi' || kat === 'non_barang',
        sspd: kat === 'barang_konsumsi',
        admin: true
      };
    }

    // Tampilkan / sembunyikan semua elemen ber-atribut
    // data-kolom-pajak (kepala & kaki tabel, sel baris
    // penerima, dan pembungkus input formulir).
    function terapkanKolomPajak() {
      var tampil = bidangPajakTampil();
      ['ppn', 'pph', 'sspd', 'admin'].forEach(function(bidang) {
        modalEl.querySelectorAll('[data-kolom-pajak="' + bidang + '"]').forEach(function(el) {
          el.classList.toggle('d-none', !tampil[bidang]);
        });
      });
    }

    // Jumlah kolom tabel yang SEDANG tampil: 8 kolom
    // dasar (No, Nama, Vol, Satuan, Jumlah, Diterima,
    // Rekening, Status) + bidang pajak tampil + kolom
    // Aksi untuk peran tulis. Untuk colspan baris
    // kosong / memuat.
    function jumlahKolomTampil() {
      var tampil = bidangPajakTampil();
      var n = 8;
      ['ppn', 'pph', 'sspd', 'admin'].forEach(function(bidang) {
        if (tampil[bidang]) n++;
      });
      return n + (bisaTulis ? 1 : 0);
    }

    // ── Meta grup: saklar status & nomor VA SIPLah ──
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

      // Status: bila grup punya penerima, status grup
      // DITURUNKAN dari penanda selesai tiap penerima —
      // saklar grup disembunyikan dan #meta-status-baca
      // menampilkan teks turunannya (juga untuk peran
      // baca-saja). Bila belum ada penerima, saklar meta
      // berfungsi seperti biasa. Saklar yang tersembunyi
      // tetap diselaraskan ke status meta tersimpan agar
      // pengiriman meta lain (VA, kategori) tidak
      // mengirim status basi.
      var saklarStatusMeta = $('meta-status-saklar');
      if (saklarStatusMeta) saklarStatusMeta.checked = (g.status === 'selesai');
      var bungkusSaklarStatus = saklarStatusMeta ? saklarStatusMeta.closest('.form-check') : null;
      if (Number(g.jumlah_penerima || 0) > 0) {
        if (bungkusSaklarStatus) bungkusSaklarStatus.classList.add('d-none');
        var stGrup = g.status_tampil || 'belum';
        var teksStatusGrup;
        if (stGrup === 'selesai') {
          teksStatusGrup = 'Selesai — semua penerima selesai';
        } else if (stGrup === 'sebagian') {
          teksStatusGrup = 'Selesai Sebagian — ' + Number(g.jumlah_selesai || 0) +
            ' dari ' + Number(g.jumlah_penerima || 0) + ' penerima';
        } else {
          teksStatusGrup = 'Belum Selesai — ' + Number(g.jumlah_selesai || 0) +
            ' dari ' + Number(g.jumlah_penerima || 0) + ' penerima';
        }
        $('meta-status-baca').innerHTML = lolosHtml(teksStatusGrup) +
          (bisaTulis ?
            '<div class="form-text">Status diatur per penerima lewat saklar di tabel penerima.</div>' :
            '');
      } else {
        if (bungkusSaklarStatus) bungkusSaklarStatus.classList.remove('d-none');
        if (bisaTulis) {
          $('meta-status-baca').innerHTML = '';
        } else {
          var labelStatus = {
            belum: ['bg-label-secondary', 'Belum'],
            selesai: ['bg-success', 'Selesai']
          };
          var kunciStatus = (g.status === 'selesai') ? 'selesai' : 'belum';
          var label = labelStatus[kunciStatus];
          $('meta-status-baca').innerHTML =
            '<span class="badge ' + label[0] + '">' + label[1] + '</span>';
        }
      }

      // Kategori grup: pilihan manual (select) menang
      // atas deteksi otomatis; tanpa pilihan manual,
      // kategori efektif = hasil deteksi (barang vs
      // non-barang dari kode rekening). 'barang_konsumsi'
      // hanya bisa dipilih manual.
      if (bisaTulis) {
        var pilihKat = $('meta-kategori-pilih');
        if (pilihKat) pilihKat.value = g.kategori_manual ?? '';
        var infoKat = $('meta-kategori-info');
        if (infoKat) {
          infoKat.textContent = 'Deteksi otomatis: ' + labelKategori(g.kategori_deteksi) +
            ' (dari kode rekening).' +
            ((g.kategori_manual !== null && g.kategori_manual !== undefined) ?
              ' Sedang diatur manual.' :
              '');
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
    // baris BKU menurut status_tampil grup: selesai ->
    // hijau 'Selesai', sebagian -> kuning 'Selesai
    // Sebagian', belum -> abu 'Belum Selesai'. Lencana
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

    // Perbarui lencana status & VA pada sel baris BKU yang
    // membuka modal ini, tanpa memuat ulang halaman.
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
        gambarMeta(); // kembalikan saklar ke nilai yang tersimpan
        gagal(e);
      }
    }

    // Simpan kategori grup lewat endpoint meta yang sama
    // (status saat ini ikut dikirim karena status wajib
    // di server; nomor VA tidak dikirim agar tidak
    // tertimpa). kategori: 'barang' | 'barang_konsumsi' |
    // 'non_barang', atau null (pilihan "Otomatis") untuk
    // kembali ikut deteksi.
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
        gambarMeta(); // kembalikan pilihan ke nilai yang tersimpan
        gagal(e);
      }
    }

    // ── Tabel penerima: baris inline (mode tulis) ──

    // Markup satu baris penerima yang bisa diisi
    // langsung di sel tabel. p adalah objek snapshot;
    // id kosong berarti baris baru yang belum tersimpan.
    function barisPenerimaHtml(p, i) {
      var jumlah = Math.round((Number(p.volume || 0) * Number(p.satuan || 0)) * 100) / 100;
      var diterima = jumlah - Number(p.ppn || 0) - Number(p.pph || 0) -
        Number(p.sspd || 0) - Number(p.biaya_admin || 0);
      var statusSel;
      if (p.id) {
        statusSel = '<td><div class="form-check form-switch mb-0">' +
          '<input class="form-check-input saklar-selesai-penerima" type="checkbox" data-selesai-id="' +
          p.id + '"' + (p.selesai ? ' checked' : '') + '>' +
          '<label class="form-check-label small">Selesai</label></div></td>';
      } else {
        statusSel = '<td><div class="form-check form-switch mb-0">' +
          '<input class="form-check-input saklar-selesai-penerima" type="checkbox" disabled>' +
          '<label class="form-check-label small">Selesai</label></div></td>';
      }
      return '<tr data-baris-penerima data-id="' + (p.id || '') +
        '" data-ptk-id="' + (p.ptk_id || '') +
        '" data-penerima-id="' + (p.penerima_id || '') +
        '" data-jenis="' + lolosHtml(p.jenis || 'orang') + '">' +
        '<td class="baris-no">' + (i + 1) + '</td>' +
        '<td>' +
        '<div class="position-relative">' +
        '<input type="text" class="form-control form-control-sm inp-nama" value="' +
        lolosHtml(p.nama || '') +
        '" placeholder="Ketik nama atau pilih dari daftar" autocomplete="off">' +
        '<div class="list-group daftar-nama d-none shadow" style="position: fixed; z-index: 1080; max-height: 230px; overflow-y: auto;"></div>' +
        '</div>' +
        '<div class="sub-orang mt-1">' +
        '<input type="text" class="form-control form-control-sm inp-nip mb-1" placeholder="NIP" value="' +
        lolosHtml(p.nip || '') + '">' +
        '<input type="text" class="form-control form-control-sm inp-pangkat mb-1" placeholder="Pangkat / Golongan" value="' +
        lolosHtml(p.pangkat_gol || '') + '">' +
        '<input type="text" class="form-control form-control-sm inp-nik" placeholder="NIK" value="' +
        lolosHtml(p.nik || '') + '">' +
        '</div>' +
        '<div class="sub-toko mt-1 small text-muted d-none"></div>' +
        '<div class="d-flex align-items-center gap-2 mt-1">' +
        '<span class="lencana-asal"></span>' +
        '<select class="form-select form-select-sm inp-jenis" style="width: auto;">' +
        '<option value="orang">Orang</option>' +
        '<option value="toko">Toko</option>' +
        '</select>' +
        '<label class="form-check small mb-0 wrap-simpan-induk">' +
        '<input type="checkbox" class="form-check-input inp-simpan-induk"> simpan ke tabel penerima' +
        '</label>' +
        '</div>' +
        '</td>' +
        '<td><input type="number" class="form-control form-control-sm text-end inp-volume" min="0" step="0.01" value="' +
        Number(p.volume || 0) + '"></td>' +
        '<td><input type="number" class="form-control form-control-sm text-end inp-satuan" min="0" step="0.01" value="' +
        Number(p.satuan || 0) + '"></td>' +
        '<td class="text-end sel-jumlah">' + fmtRp(jumlah) + '</td>' +
        '<td data-kolom-pajak="ppn"><input type="number" class="form-control form-control-sm text-end inp-ppn" min="0" step="0.01" value="' +
        Number(p.ppn || 0) + '"></td>' +
        '<td data-kolom-pajak="pph"><input type="number" class="form-control form-control-sm text-end inp-pph" min="0" step="0.01" value="' +
        Number(p.pph || 0) + '"></td>' +
        '<td data-kolom-pajak="sspd"><input type="number" class="form-control form-control-sm text-end inp-sspd" min="0" step="0.01" value="' +
        Number(p.sspd || 0) + '"></td>' +
        '<td data-kolom-pajak="admin"><input type="number" class="form-control form-control-sm text-end inp-admin" min="0" step="0.01" value="' +
        Number(p.biaya_admin || 0) + '"></td>' +
        '<td class="text-end fw-semibold sel-diterima">' + fmtRp(diterima) + '</td>' +
        '<td><input type="text" class="form-control form-control-sm inp-rekening mb-1" placeholder="No. Rekening" value="' +
        lolosHtml(p.no_rekening || '') + '">' +
        '<input type="text" class="form-control form-control-sm inp-bank" placeholder="Nama Bank" value="' +
        lolosHtml(p.nama_bank || '') + '"></td>' +
        statusSel +
        '<td class="text-nowrap"><button type="button" class="btn btn-sm btn-icon btn-text-danger" data-aksi-hapus-baris title="Hapus baris"><i class="ri ri-delete-bin-line"></i></button></td>' +
        '</tr>';
    }

    // Segarkan tampilan sub-bidang nama menurut jenis
    // baris & tautan sumbernya: penerima tertaut PTK /
    // induk ber-lencana asal dan pemilih jenisnya
    // disembunyikan; toko menyembunyikan isian
    // perorangan dan menampilkan NPWP dari daftar toko
    // bila namanya cocok persis.
    function segarkanSubJenis(tr) {
      var jenis = tr.dataset.jenis || 'orang';
      var inpNama = tr.querySelector('.inp-nama');
      var subOrang = tr.querySelector('.sub-orang');
      var subToko = tr.querySelector('.sub-toko');
      if (subOrang) subOrang.classList.toggle('d-none', jenis === 'toko');
      if (subToko) {
        subToko.classList.toggle('d-none', jenis !== 'toko');
        if (jenis === 'toko') {
          var namaKini = (inpNama.value || '').trim().toLowerCase();
          var cocok = sumberGabungan.find(function(s) {
            return s.sumber === 'Toko' && s.nama.toLowerCase() === namaKini;
          });
          subToko.innerHTML = 'Toko' +
            (cocok && cocok.npwp ? ' &bull; NPWP ' + lolosHtml(cocok.npwp) : '');
        }
      }
      var pilihJenis = tr.querySelector('.inp-jenis');
      if (pilihJenis) pilihJenis.value = jenis;
      var tertautPtk = !!tr.dataset.ptkId;
      var tertautInduk = !!tr.dataset.penerimaId;
      var lencana = tr.querySelector('.lencana-asal');
      if (lencana) {
        if (tertautPtk) {
          lencana.innerHTML = '<span class="badge bg-label-primary">PTK</span>';
        } else if (tertautInduk) {
          lencana.innerHTML = '<span class="badge bg-label-info">Induk</span>';
        } else {
          lencana.innerHTML = '';
        }
      }
      if (pilihJenis) {
        pilihJenis.classList.toggle('d-none', tertautPtk || tertautInduk);
      }
      var bungkusInduk = tr.querySelector('.wrap-simpan-induk');
      if (bungkusInduk) {
        bungkusInduk.classList.toggle('d-none',
          (tertautPtk || tertautInduk) || jenis !== 'orang');
      }
    }

    // Hitung jumlah & diterima satu baris dari inputnya
    // (jumlah = volume × satuan, dibulatkan 2 desimal
    // seperti model) lalu tulis ke sel tampilannya.
    function hitungBaris(tr) {
      function nilai(sel) {
        var el = tr.querySelector(sel);
        return el ? (parseFloat(el.value) || 0) : 0;
      }
      var jumlah = Math.round((nilai('.inp-volume') * nilai('.inp-satuan')) * 100) / 100;
      var hasil = {
        jumlah: jumlah,
        ppn: nilai('.inp-ppn'),
        pph: nilai('.inp-pph'),
        sspd: nilai('.inp-sspd'),
        admin: nilai('.inp-admin')
      };
      hasil.diterima = jumlah - hasil.ppn - hasil.pph - hasil.sspd - hasil.admin;
      tr.querySelector('.sel-jumlah').textContent = fmtRp(jumlah);
      tr.querySelector('.sel-diterima').textContent = fmtRp(hasil.diterima);
      return hasil;
    }

    // Total kaki tabel & selisih dihitung dari baris
    // yang sedang tampil di DOM (termasuk baris baru
    // yang belum tersimpan), bukan dari model.
    function segarkanTotalDariDom() {
      var tbody = $('penerima-tbody');
      var baris = tbody.querySelectorAll('tr[data-baris-penerima]');
      var tot = {
        jumlah: 0,
        ppn: 0,
        pph: 0,
        sspd: 0,
        admin: 0,
        diterima: 0
      };
      var daftarNama = [];
      baris.forEach(function(tr) {
        var h = hitungBaris(tr);
        tot.jumlah += h.jumlah;
        tot.ppn += h.ppn;
        tot.pph += h.pph;
        tot.sspd += h.sspd;
        tot.admin += h.admin;
        tot.diterima += h.diterima;
        daftarNama.push(tr.querySelector('.inp-nama').value.trim());
      });
      $('tot-jumlah').textContent = fmtRp(tot.jumlah);
      $('tot-ppn').textContent = fmtRp(tot.ppn);
      $('tot-pph').textContent = fmtRp(tot.pph);
      $('tot-sspd').textContent = fmtRp(tot.sspd);
      $('tot-admin').textContent = fmtRp(tot.admin);
      $('tot-diterima').textContent = fmtRp(tot.diterima);

      var el = $('penerima-selisih');
      if (!baris.length) {
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

      segarkanLencana(baris.length, daftarNama);
    }

    function gambarTabel() {
      if (!bisaTulis) {
      var tbody = $('penerima-tbody');
      var daftar = data.penerima || [];
      if (!daftar.length) {
        tbody.innerHTML = '<tr><td colspan="' + jumlahKolomTampil() +
          '" class="text-center text-muted">Belum ada penerima untuk grup ini.</td></tr>';
      } else {
        tbody.innerHTML = daftar.map(function(p, i) {
          var sub = subInfo([
            p.nip ? 'NIP ' + p.nip : null,
            p.pangkat_gol,
            p.nik ? 'NIK ' + p.nik : null
          ]);
          var rek = subInfo([p.no_rekening, p.nama_bank]) || '-';
          var asal = p.ptk_id ?
            ' <span class="badge bg-label-primary">PTK</span>' :
            (p.penerima_id ? ' <span class="badge bg-label-info">Induk</span>' : '');
          var aksi = '';
          if (bisaTulis) {
            aksi = '<td class="text-nowrap">' +
              '<button type="button" class="btn btn-sm btn-icon btn-text-primary" data-aksi-edit="' + p.id +
              '" title="Ubah"><i class="ri ri-pencil-line"></i></button>' +
              '<button type="button" class="btn btn-sm btn-icon btn-text-danger" data-aksi-hapus="' + p.id +
              '" title="Hapus"><i class="ri ri-delete-bin-line"></i></button></td>';
          }
          var statusSel;
          if (bisaTulis) {
            statusSel = '<td><div class="form-check form-switch mb-0">' +
              '<input class="form-check-input saklar-selesai-penerima" type="checkbox" data-selesai-id="' +
              p.id + '"' + (p.selesai ? ' checked' : '') + '>' +
              '<label class="form-check-label small">Selesai</label></div></td>';
          } else {
            statusSel = '<td>' + (p.selesai ?
              '<span class="badge bg-success">Selesai</span>' :
              '<span class="badge bg-secondary">Belum</span>') + '</td>';
          }
          return '<tr>' +
            '<td>' + (i + 1) + '</td>' +
            '<td><div class="fw-semibold">' + lolosHtml(p.nama) + asal + '</div>' +
            (sub ? '<div class="small text-muted">' + sub + '</div>' : '') + '</td>' +
            '<td class="text-end">' + fmtVol(p.volume) + '</td>' +
            '<td class="text-end">' + fmtRp(p.satuan) + '</td>' +
            '<td class="text-end">' + fmtRp(p.jumlah) + '</td>' +
            '<td class="text-end" data-kolom-pajak="ppn">' + (Number(p.ppn || 0) > 0 ? fmtRp(p.ppn) : '-') + '</td>' +
            '<td class="text-end" data-kolom-pajak="pph">' + (Number(p.pph || 0) > 0 ? fmtRp(p.pph) : '-') + '</td>' +
            '<td class="text-end" data-kolom-pajak="sspd">' + (Number(p.sspd || 0) > 0 ? fmtRp(p.sspd) : '-') + '</td>' +
            '<td class="text-end" data-kolom-pajak="admin">' + (Number(p.biaya_admin || 0) > 0 ? fmtRp(p.biaya_admin) : '-') + '</td>' +
            '<td class="text-end fw-semibold">' + fmtRp(p.diterima) + '</td>' +
            '<td class="small">' + rek + '</td>' + statusSel + aksi + '</tr>';
        }).join('');
      }

      var totJumlah = daftar.reduce(function(s, p) {
        return s + Number(p.jumlah || 0);
      }, 0);
      var totPpn = daftar.reduce(function(s, p) {
        return s + Number(p.ppn || 0);
      }, 0);
      var totPph = daftar.reduce(function(s, p) {
        return s + Number(p.pph || 0);
      }, 0);
      var totSspd = daftar.reduce(function(s, p) {
        return s + Number(p.sspd || 0);
      }, 0);
      var totAdmin = daftar.reduce(function(s, p) {
        return s + Number(p.biaya_admin || 0);
      }, 0);
      var totDiterima = daftar.reduce(function(s, p) {
        return s + Number(p.diterima || 0);
      }, 0);
      $('tot-jumlah').textContent = fmtRp(totJumlah);
      $('tot-ppn').textContent = fmtRp(totPpn);
      $('tot-pph').textContent = fmtRp(totPph);
      $('tot-sspd').textContent = fmtRp(totSspd);
      $('tot-admin').textContent = fmtRp(totAdmin);
      $('tot-diterima').textContent = fmtRp(totDiterima);

      var el = $('penerima-selisih');
      if (!daftar.length) {
        el.innerHTML = '';
      } else {
        var selisih = Math.round((totJumlah - Number(data.grup.total || 0)) * 100) / 100;
        if (Math.abs(selisih) < 0.005) {
          el.innerHTML = '<span class="text-success"><i class="ri ri-check-line me-1"></i>' +
            'Total jumlah penerima sesuai dengan total grup.</span>';
        } else {
          el.innerHTML = '<span class="text-danger"><i class="ri ri-error-warning-line me-1"></i>' +
            'Selisih ' + fmtRp(Math.abs(selisih)) + (selisih > 0 ? ' di atas' : ' di bawah') +
            ' total grup.</span>';
        }
      }

      segarkanLencana(daftar.length, daftar.map(function(p) { return p.nama; }));

      // Baris-baris baru saja dirender: terapkan lagi
      // visibilitas kolom potongan menurut kategori.
      terapkanKolomPajak();
        return;
      }

      var tbody = $('penerima-tbody');
      var daftar = data.penerima || [];
      if (!daftar.length) {
        tbody.innerHTML = '<tr><td colspan="' + jumlahKolomTampil() +
          '" class="text-center text-muted">Belum ada penerima untuk grup ini.</td></tr>';
        $('tot-jumlah').textContent = fmtRp(0);
        $('tot-ppn').textContent = fmtRp(0);
        $('tot-pph').textContent = fmtRp(0);
        $('tot-sspd').textContent = fmtRp(0);
        $('tot-admin').textContent = fmtRp(0);
        $('tot-diterima').textContent = fmtRp(0);
        $('penerima-selisih').innerHTML = '';
        segarkanLencana(0, []);
        terapkanKolomPajak();
        return;
      }
      tbody.innerHTML = daftar.map(function(p, i) {
        return barisPenerimaHtml(p, i);
      }).join('');
      var baris = tbody.querySelectorAll('tr[data-baris-penerima]');
      baris.forEach(function(tr, i) {
        tr.dataset.namaTertaut = daftar[i].nama || '';
        segarkanSubJenis(tr);
        hitungBaris(tr);
      });
      segarkanTotalDariDom();
      terapkanKolomPajak();
    }

    // ── Combobox nama penerima ──
    var panelAktif = null;

    function sembunyikanPanelNama() {
      if (panelAktif) panelAktif.classList.add('d-none');
      panelAktif = null;
    }

    // Tampilkan panel pilihan nama untuk satu baris:
    // sumber gabungan (PTK, tabel penerima, toko)
    // disaring menurut ketikan, dikelompokkan per
    // sumber, maksimal 40 pilihan. Panel diposisikan
    // fixed mengikuti kotak nama.
    function tampilkanPanelNama(tr) {
      var inp = tr.querySelector('.inp-nama');
      var panel = tr.querySelector('.daftar-nama');
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
      var rect = inp.getBoundingClientRect();
      panel.style.left = rect.left + 'px';
      panel.style.top = (rect.bottom + 2) + 'px';
      panel.style.width = Math.max(rect.width, 260) + 'px';
      if (panelAktif && panelAktif !== panel) panelAktif.classList.add('d-none');
      panel.classList.remove('d-none');
      panelAktif = panel;
    }

    // Terapkan satu sumber dari panel ke baris: isian
    // snapshot disalin dari sumbernya (toko mengosongkan
    // isian perorangan), tautan & jenis dipasang, lalu
    // baris langsung disimpan.
    function pilihSumber(tr, s) {
      if (!s) return;
      tr.querySelector('.inp-nama').value = s.nama || '';
      tr.querySelector('.inp-nip').value = s.nip || '';
      tr.querySelector('.inp-pangkat').value = s.pangkat_gol || '';
      tr.querySelector('.inp-nik').value = s.nik || '';
      tr.querySelector('.inp-rekening').value = s.no_rekening || '';
      tr.querySelector('.inp-bank').value = s.nama_bank || '';
      tr.dataset.ptkId = s.sumber === 'PTK' ? s.id : '';
      tr.dataset.penerimaId = s.sumber === 'Penerima' ? s.id : '';
      tr.dataset.jenis = s.jenis;
      tr.dataset.namaTertaut = s.nama || '';
      segarkanSubJenis(tr);
      sembunyikanPanelNama();
      simpanBaris(tr);
    }

    // ── Simpan otomatis per baris ──

    // Kumpulkan payload snapshot dari input satu baris.
    function kumpulkanPayload(tr) {
      function teks(sel) {
        var el = tr.querySelector(sel);
        return el ? el.value.trim() : '';
      }

      function angka(sel) {
        var el = tr.querySelector(sel);
        return el ? (parseFloat(el.value) || 0) : 0;
      }
      return {
        nama: teks('.inp-nama'),
        nip: teks('.inp-nip') || null,
        pangkat_gol: teks('.inp-pangkat') || null,
        nik: teks('.inp-nik') || null,
        no_rekening: teks('.inp-rekening') || null,
        nama_bank: teks('.inp-bank') || null,
        volume: angka('.inp-volume'),
        satuan: angka('.inp-satuan'),
        ppn_rp: angka('.inp-ppn'),
        pph_rp: angka('.inp-pph'),
        sspd_rp: angka('.inp-sspd'),
        admin_rp: angka('.inp-admin'),
        jenis: tr.dataset.jenis || 'orang',
        ptk_id: tr.dataset.ptkId ? Number(tr.dataset.ptkId) : null,
        penerima_id: tr.dataset.penerimaId ? Number(tr.dataset.penerimaId) : null
      };
    }

    // Status tampil grup diturunkan ulang dari model
    // penerima lokal — dipakai sesudah baris ditambah /
    // dihapus agar blok Keterangan & lencana baris BKU
    // segar tanpa memuat ulang data dari server.
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

    // Simpan satu baris penerima: baris baru lewat POST,
    // baris tersimpan lewat PUT. Panggilan saat
    // penyimpanan sedang berjalan diantrekan satu kali
    // agar isian terakhir tetap terkirim.
    async function simpanBaris(tr) {
      if (!data) return;
      var inpNama = tr.querySelector('.inp-nama');
      if (!inpNama.value.trim()) {
        inpNama.classList.add('is-invalid');
        return;
      }
      inpNama.classList.remove('is-invalid');
      if (tr.dataset.menyimpan === '1') {
        tr.dataset.antre = '1';
        return;
      }
      tr.dataset.menyimpan = '1';
      tr.classList.add('opacity-50');
      var payload = kumpulkanPayload(tr);
      var idBaris = tr.dataset.id || '';
      try {
        var res;
        if (idBaris) {
          res = await kirim(urlDasar + '/' + barisId + '/penerima/' + idBaris, 'PUT', payload);
        } else {
          var centangInduk = tr.querySelector('.inp-simpan-induk');
          if (centangInduk && centangInduk.checked && (tr.dataset.jenis || 'orang') === 'orang') {
            payload.simpan_master = 1;
          }
          res = await kirim(urlDasar + '/' + barisId + '/penerima', 'POST', payload);
        }
        var d = res.data;
        if (d) {
          if (!idBaris) {
            tr.dataset.id = d.id;
            var saklarBaru = tr.querySelector('.saklar-selesai-penerima');
            if (saklarBaru) {
              saklarBaru.setAttribute('data-selesai-id', d.id);
              saklarBaru.disabled = false;
              saklarBaru.checked = !!d.selesai;
            }
            data.penerima.push(d);
            segarkanStatusTurunan();
          } else {
            var idxModel = (data.penerima || []).findIndex(function(p) {
              return Number(p.id) === Number(d.id);
            });
            if (idxModel >= 0) data.penerima[idxModel] = d;
          }
          tr.dataset.ptkId = d.ptk_id || '';
          tr.dataset.penerimaId = d.penerima_id || '';
          tr.dataset.jenis = d.jenis || 'orang';
          tr.dataset.namaTertaut = d.nama || '';
        }
        segarkanSubJenis(tr);
        segarkanTotalDariDom();
      } catch (e) {
        gagal(e);
      } finally {
        delete tr.dataset.menyimpan;
        tr.classList.remove('opacity-50');
        if (tr.dataset.antre) {
          delete tr.dataset.antre;
          simpanBaris(tr);
        }
      }
    }

    // Tambah satu baris penerima baru di bawah tabel.
    // Isian bawaan dari sisa BKU: satuan = sisa total
    // grup, potongan = sisa pajak nota — dihitung dari
    // baris yang sudah TERSIMPAN di DOM.
    function tambahBarisBaru(awal) {
      if (!data || !data.grup) return;
      var tbody = $('penerima-tbody');
      if (!tbody.querySelector('tr[data-baris-penerima]')) {
        tbody.innerHTML = '';
      }
      var jumlahTerpakai = 0;
      var alokasi = {
        ppn: 0,
        pph: 0,
        sspd: 0
      };
      tbody.querySelectorAll('tr[data-baris-penerima]').forEach(function(tr) {
        if (!tr.dataset.id) return;
        var h = hitungBaris(tr);
        jumlahTerpakai += h.jumlah;
        alokasi.ppn += h.ppn;
        alokasi.pph += h.pph;
        alokasi.sspd += h.sspd;
      });
      var pajakBku = data.grup.pajak_bku || {};
      var p = {
        id: null,
        nama: (awal && awal.nama) || '',
        nip: null,
        pangkat_gol: null,
        nik: null,
        no_rekening: null,
        nama_bank: null,
        volume: 1,
        satuan: Math.max(0, Number(data.grup.total || 0) - jumlahTerpakai),
        ppn: Math.max(0, Number(pajakBku.ppn || 0) - alokasi.ppn),
        pph: Math.max(0, Number(pajakBku.pph || 0) - alokasi.pph),
        sspd: Math.max(0, Number(pajakBku.sspd || 0) - alokasi.sspd),
        biaya_admin: 0,
        jenis: (awal && awal.jenis) || 'orang',
        selesai: false,
        ptk_id: null,
        penerima_id: null
      };
      var idx = tbody.querySelectorAll('tr[data-baris-penerima]').length;
      tbody.insertAdjacentHTML('beforeend', barisPenerimaHtml(p, idx));
      var trBaru = tbody.querySelectorAll('tr[data-baris-penerima]')[idx];
      trBaru.dataset.namaTertaut = '';
      segarkanSubJenis(trBaru);
      hitungBaris(trBaru);
      terapkanKolomPajak();
      nomorUlangBaris();
      segarkanTotalDariDom();
      var inpNamaBaru = trBaru.querySelector('.inp-nama');
      if (inpNamaBaru) inpNamaBaru.focus();
    }

    function nomorUlangBaris() {
      $('penerima-tbody').querySelectorAll('tr[data-baris-penerima]').forEach(function(tr, i) {
        var selNo = tr.querySelector('.baris-no');
        if (selNo) selNo.textContent = i + 1;
      });
    }

    // Hapus satu baris: baris tersimpan lewat DELETE ke
    // server (dengan konfirmasi), baris baru yang belum
    // tersimpan cukup dibuang dari DOM tanpa request.
    async function hapusBaris(tr) {
      var idBaris = tr.dataset.id || '';
      if (idBaris) {
        if (!confirm('Hapus penerima ini dari grup?')) return;
        try {
          await kirim(urlDasar + '/' + barisId + '/penerima/' + idBaris, 'DELETE');
        } catch (e) {
          gagal(e);
          return;
        }
        data.penerima = (data.penerima || []).filter(function(p) {
          return Number(p.id) !== Number(idBaris);
        });
      }
      tr.remove();
      if (idBaris) segarkanStatusTurunan();
      var tbody = $('penerima-tbody');
      if (!tbody.querySelector('tr[data-baris-penerima]')) {
        gambarTabel();
      } else {
        nomorUlangBaris();
        segarkanTotalDariDom();
      }
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
        sumberGabungan.push({
          sumber: 'Penerima',
          jenis: 'orang',
          id: p.id,
          nama: p.nama,
          nip: p.nip || null,
          pangkat_gol: p.pangkat_gol || null,
          nik: p.nik || null,
          no_rekening: p.no_rekening || null,
          nama_bank: p.nama_bank || null,
          npwp: null
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
      // Grup yang belum punya penerima tersimpan langsung diberi
      // satu baris bawaan (belum tersimpan): nama toko dari nota
      // BKU bila ada + nominal sisa grup, supaya penerima bawaan
      // dan nominalnya langsung terlihat tanpa input manual.
      // Baris tersimpan sendiri begitu pengguna mengubah isinya;
      // menutup modal tanpa mengubah apa pun tidak menyimpan apa-apa.
      if (bisaTulis && data && data.grup && (!data.penerima || !data.penerima.length)) {
        var namaToko = (data.grup.nama_toko || '').trim();
        tambahBarisBaru(namaToko ? { nama: namaToko, jenis: 'toko' } : null);
      }
    }

    // ── Buka modal dari tombol di tabel BKU ──
    document.addEventListener('click', function(e) {
      var btn = e.target.closest('[data-penerima-baris]');
      if (!btn) return;
      barisId = btn.getAttribute('data-id');
      data = null;
      sembunyikanPanelNama();
      $('penerima-info-grup').innerHTML = '<span class="text-muted">Memuat…</span>';
      $('meta-siplah-info').innerHTML = '';
      $('meta-status-baca').innerHTML = '';
      $('meta-kategori-baca').innerHTML = '';
      $('meta-va-wrap').classList.add('d-none');
      $('penerima-tbody').innerHTML = '<tr><td colspan="' + jumlahKolomTampil() +
        '" class="text-center text-muted">Memuat…</td></tr>';
      $('penerima-selisih').innerHTML = '';
      bootstrap.Modal.getOrCreateInstance(modalEl).show();
      muat().catch(gagal);
    });

    if (bisaTulis) {
      // Tombol tambah baris penerima baru di bawah tabel.
      $('btn-baris-penerima-baru').addEventListener('click', tambahBarisBaru);

      // Delegasi peristiwa pada baris-baris penerima di
      // tbody: combobox nama (fokus & ketik), hitung
      // ulang saat angka berubah, dan simpan otomatis
      // per baris saat isian berubah (change).
      var tbodyPenerima = $('penerima-tbody');

      tbodyPenerima.addEventListener('focusin', function(e) {
        if (e.target.classList.contains('inp-nama')) {
          var trFokus = e.target.closest('tr[data-baris-penerima]');
          if (trFokus) tampilkanPanelNama(trFokus);
        }
      });

      tbodyPenerima.addEventListener('input', function(e) {
        var tr = e.target.closest('tr[data-baris-penerima]');
        if (!tr) return;
        if (e.target.classList.contains('inp-nama')) {
          tampilkanPanelNama(tr);
          // Ketikan yang menyimpang dari nama sumber
          // terpilih melepaskan tautan PTK / induknya.
          if ((tr.dataset.ptkId || tr.dataset.penerimaId) &&
            e.target.value.trim() !== (tr.dataset.namaTertaut || '')) {
            tr.dataset.ptkId = '';
            tr.dataset.penerimaId = '';
            segarkanSubJenis(tr);
          }
        }
        if (e.target.matches('.inp-volume, .inp-satuan, .inp-ppn, .inp-pph, .inp-sspd, .inp-admin')) {
          hitungBaris(tr);
          segarkanTotalDariDom();
        }
      });

      tbodyPenerima.addEventListener('change', function(e) {
        if (e.target.closest('.saklar-selesai-penerima')) return;
        var tr = e.target.closest('tr[data-baris-penerima]');
        if (!tr || !data) return;
        if (e.target.classList.contains('inp-jenis')) {
          tr.dataset.jenis = e.target.value;
          segarkanSubJenis(tr);
        }
        simpanBaris(tr);
      });

      tbodyPenerima.addEventListener('click', function(e) {
        var btnPilih = e.target.closest('[data-pilih-sumber]');
        if (btnPilih) {
          var trPilih = btnPilih.closest('tr[data-baris-penerima]');
          if (trPilih) {
            pilihSumber(trPilih, sumberGabungan[Number(btnPilih.getAttribute('data-pilih-sumber'))]);
          }
          return;
        }
        var btnHapusBaris = e.target.closest('[data-aksi-hapus-baris]');
        if (btnHapusBaris) {
          var trHapus = btnHapusBaris.closest('tr[data-baris-penerima]');
          if (trHapus) hapusBaris(trHapus);
        }
      });

      tbodyPenerima.addEventListener('keydown', function(e) {
        if (e.target.classList.contains('inp-nama') && e.key === 'Escape') {
          sembunyikanPanelNama();
        }
      });

      // Klik di luar baris pemilik panel menutup panel
      // pilihan nama yang sedang terbuka.
      document.addEventListener('click', function(e) {
        if (panelAktif) {
          var trPanel = panelAktif.closest('tr[data-baris-penerima]');
          if (!trPanel || !trPanel.contains(e.target)) {
            sembunyikanPanelNama();
          }
        }
      });


      // Saklar status meta grup: tersimpan otomatis begitu
      // diganti (PUT penerima-grup, membawa VA saat ini).
      var saklarStatus = $('meta-status-saklar');
      if (saklarStatus) {
        saklarStatus.addEventListener('change', function() {
          if (!data || !data.grup) return;
          var va = data.grup.siplah ?
            $('meta-nomor-va').value.trim() :
            (data.grup.nomor_va || '');
          simpanMeta(saklarStatus.checked ? 'selesai' : 'belum', va || null);
        });
      }

      // Pilihan kategori grup: tersimpan otomatis begitu
      // diganti; pilihan "Otomatis" mengirim kategori
      // null agar server kembali ke deteksi otomatis.
      var pilihKategori = $('meta-kategori-pilih');
      if (pilihKategori) {
        pilihKategori.addEventListener('change', function() {
          if (!data || !data.grup) return;
          simpanKategori(pilihKategori.value);
        });
      }

      $('btn-simpan-va').addEventListener('click', function() {
        if (!data || !data.grup) return;
        var saklarVa = $('meta-status-saklar');
        simpanMeta(
          saklarVa ? (saklarVa.checked ? 'selesai' : 'belum') : ((data.grup.status === 'selesai') ? 'selesai' : 'belum'),
          $('meta-nomor-va').value.trim() || null
        );
      });

      // Saklar selesai per penerima di tabel penerima
      // (delegasi change pada tbody): status tampil grup
      // diturunkan ulang di server dan dikembalikan,
      // lalu teks turunan di blok Keterangan serta
      // lencana status di baris BKU diperbarui.
      $('penerima-tbody').addEventListener('change', async function(e) {
        var saklarSelesai = e.target.closest('.saklar-selesai-penerima');
        if (!saklarSelesai) return;
        var pidSelesai = saklarSelesai.getAttribute('data-selesai-id');
        try {
          var resSelesai = await kirim(
            urlDasar + '/' + barisId + '/penerima/' + pidSelesai + '/selesai', 'PUT', {
              selesai: saklarSelesai.checked
            });
          var targetSelesai = (data.penerima || []).find(function(p) {
            return Number(p.id) === Number(pidSelesai);
          });
          if (targetSelesai && resSelesai.data) {
            targetSelesai.selesai = !!resSelesai.data.selesai;
          }
          data.grup.status_tampil = resSelesai.status_tampil;
          data.grup.jumlah_penerima = resSelesai.jumlah_penerima;
          data.grup.jumlah_selesai = resSelesai.jumlah_selesai;
          gambarMeta();
          var btnBaris = document.querySelector('[data-penerima-baris][data-id="' + barisId + '"]');
          var selBaris = btnBaris ? btnBaris.closest('td') : null;
          gambarLencanaStatus(selBaris ? selBaris.querySelector('.meta-grup-baris') : null,
            resSelesai.status_tampil);
        } catch (errSelesai) {
          saklarSelesai.checked = !saklarSelesai.checked;
          gagal(errSelesai);
        }
      });

    }
  })();
</script>
