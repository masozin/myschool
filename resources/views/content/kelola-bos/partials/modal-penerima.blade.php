{{--
  Modal editor penerima SPJ (Fase 2) — daftar penerima
  per grup transaksi BKU (grup yang sama dengan kwitansi:
  per nota / nomor bukti). Penerima diambil dari PTK,
  dari tabel penerima non-PTK, atau diketik manual;
  semuanya tersimpan sebagai snapshot di bos_spj_penerima.
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

        {{-- Meta grup SPJ: saklar status Belum/Lunas/Selesai
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
                <div class="btn-group btn-group-sm" role="group" aria-label="Status grup SPJ">
                  <input type="radio" class="btn-check" name="meta-status" id="meta-status-belum" value="belum"
                    autocomplete="off">
                  <label class="btn btn-outline-secondary" for="meta-status-belum">Belum</label>
                  <input type="radio" class="btn-check" name="meta-status" id="meta-status-lunas" value="lunas"
                    autocomplete="off">
                  <label class="btn btn-outline-success" for="meta-status-lunas">Lunas</label>
                  <input type="radio" class="btn-check" name="meta-status" id="meta-status-selesai" value="selesai"
                    autocomplete="off">
                  <label class="btn btn-outline-primary" for="meta-status-selesai">Selesai</label>
                </div>
              @endif
              <span id="meta-status-baca"></span>
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
              Status tersimpan otomatis begitu diganti.
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
                <th class="text-end" style="width: 6rem;">PPh %</th>
                <th class="text-end" style="width: 9rem;">PPh Rp</th>
                <th class="text-end" style="width: 10rem;">Diterima</th>
                <th style="width: 11rem;">Rekening</th>
                @if ($bisaTulisPenerima)
                  <th style="width: 6rem;">Aksi</th>
                @endif
              </tr>
            </thead>
            <tbody id="penerima-tbody">
              <tr>
                <td colspan="{{ $bisaTulisPenerima ? 10 : 9 }}" class="text-center text-muted">Memuat…</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="fw-semibold">
                <td colspan="4" class="text-end">Total</td>
                <td class="text-end" id="tot-jumlah">Rp 0</td>
                <td></td>
                <td class="text-end" id="tot-pph">Rp 0</td>
                <td class="text-end" id="tot-diterima">Rp 0</td>
                <td colspan="{{ $bisaTulisPenerima ? 2 : 1 }}"></td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div class="small mb-3" id="penerima-selisih"></div>

        @if ($bisaTulisPenerima)
          <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
              <button class="nav-link active" id="tabbtn-ptk" data-bs-toggle="tab" data-bs-target="#tab-ptk"
                type="button" role="tab">Dari PTK</button>
            </li>
            <li class="nav-item">
              <button class="nav-link" id="tabbtn-master" data-bs-toggle="tab" data-bs-target="#tab-master"
                type="button" role="tab">Dari Tabel Penerima</button>
            </li>
            <li class="nav-item">
              <button class="nav-link" id="tabbtn-manual" data-bs-toggle="tab" data-bs-target="#tab-manual"
                type="button" role="tab">Manual</button>
            </li>
          </ul>
          <div class="tab-content pt-3">
            <div class="tab-pane fade show active" id="tab-ptk" role="tabpanel">
              <div id="daftar-sumber-ptk" style="max-height: 280px; overflow-y: auto;"></div>
              <button type="button" class="btn btn-primary btn-sm mt-2" id="btn-tambah-ptk">
                <i class="ri ri-add-line me-1"></i>Tambahkan yang Dicentang</button>
              <div class="form-text">Data penerima disalin dari PTK saat ini (nama, NIP, pangkat/golongan
                terakhir, NIK, rekening). Volume bawaan 1 dan satuan 0 — sesuaikan lewat tombol ubah pada
                baris penerima.</div>
            </div>
            <div class="tab-pane fade" id="tab-master" role="tabpanel">
              <div class="d-flex gap-2 mb-2">
                <button type="button" class="btn btn-primary btn-sm" id="btn-tambah-master-pilihan">
                  <i class="ri ri-add-line me-1"></i>Tambahkan yang Dicentang</button>
                <button type="button" class="btn btn-outline-primary btn-sm" id="btn-master-baru">
                  <i class="ri ri-user-add-line me-1"></i>Penerima Baru</button>
              </div>
              <div id="daftar-sumber-master" style="max-height: 240px; overflow-y: auto;"></div>
              <div id="form-master-wrap" class="border rounded p-3 mt-2 d-none">
                <h6 class="mb-3" id="judul-form-master">Penerima Baru (Tabel Penerima)</h6>
                <input type="hidden" id="m-id">
                <div class="row g-2">
                  <div class="col-12 col-md-6">
                    <label class="form-label" for="m-nama">Nama *</label>
                    <input type="text" class="form-control form-control-sm" id="m-nama" maxlength="255">
                  </div>
                  <div class="col-6 col-md-3">
                    <label class="form-label" for="m-nik">NIK</label>
                    <input type="text" class="form-control form-control-sm" id="m-nik" maxlength="16">
                  </div>
                  <div class="col-6 col-md-3">
                    <label class="form-label" for="m-nip">NIP</label>
                    <input type="text" class="form-control form-control-sm" id="m-nip" maxlength="18">
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label" for="m-pangkat">Pangkat / Golongan</label>
                    <input type="text" class="form-control form-control-sm" id="m-pangkat" maxlength="100"
                      placeholder="mis. Penata / III/c">
                  </div>
                  <div class="col-6 col-md-3">
                    <label class="form-label" for="m-rekening">Nomor Rekening</label>
                    <input type="text" class="form-control form-control-sm" id="m-rekening" maxlength="30">
                  </div>
                  <div class="col-6 col-md-3">
                    <label class="form-label" for="m-bank">Nama Bank</label>
                    <input type="text" class="form-control form-control-sm" id="m-bank" maxlength="100">
                  </div>
                  <div class="col-12">
                    <label class="form-label" for="m-alamat">Alamat</label>
                    <textarea class="form-control form-control-sm" id="m-alamat" rows="2" maxlength="1000"></textarea>
                  </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                  <button type="button" class="btn btn-primary btn-sm" id="btn-simpan-master">Simpan</button>
                  <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-batal-master">Batal</button>
                </div>
              </div>
            </div>
            <div class="tab-pane fade" id="tab-manual" role="tabpanel">
              <input type="hidden" id="f-id">
              <div class="row g-2">
                <div class="col-12 col-md-6">
                  <label class="form-label" for="f-nama">Nama *</label>
                  <input type="text" class="form-control form-control-sm" id="f-nama" maxlength="255">
                </div>
                <div class="col-6 col-md-3">
                  <label class="form-label" for="f-nip">NIP</label>
                  <input type="text" class="form-control form-control-sm" id="f-nip" maxlength="18">
                </div>
                <div class="col-6 col-md-3">
                  <label class="form-label" for="f-nik">NIK</label>
                  <input type="text" class="form-control form-control-sm" id="f-nik" maxlength="16">
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label" for="f-pangkat">Pangkat / Golongan</label>
                  <input type="text" class="form-control form-control-sm" id="f-pangkat" maxlength="100"
                    placeholder="mis. Penata / III/c">
                </div>
                <div class="col-6 col-md-3">
                  <label class="form-label" for="f-rekening">Nomor Rekening</label>
                  <input type="text" class="form-control form-control-sm" id="f-rekening" maxlength="30">
                </div>
                <div class="col-6 col-md-3">
                  <label class="form-label" for="f-bank">Nama Bank</label>
                  <input type="text" class="form-control form-control-sm" id="f-bank" maxlength="100">
                </div>
                <div class="col-4 col-md-2">
                  <label class="form-label" for="f-volume">Volume *</label>
                  <input type="number" class="form-control form-control-sm" id="f-volume" min="0" step="0.01"
                    value="1">
                </div>
                <div class="col-8 col-md-4">
                  <label class="form-label" for="f-satuan">Satuan (Rp) *</label>
                  <input type="number" class="form-control form-control-sm" id="f-satuan" min="0" step="0.01"
                    value="0">
                </div>
                <div class="col-4 col-md-2">
                  <label class="form-label" for="f-pph">PPh % *</label>
                  <input type="number" class="form-control form-control-sm" id="f-pph" min="0" max="100"
                    step="0.01" value="0">
                </div>
                <div class="col-8 col-md-4 d-flex align-items-end" id="wrap-simpan-master">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="f-simpan-master">
                    <label class="form-check-label" for="f-simpan-master">Simpan juga ke tabel penerima</label>
                  </div>
                </div>
              </div>
              <div class="mt-3 d-flex gap-2">
                <button type="button" class="btn btn-primary btn-sm" id="btn-simpan-snapshot">
                  <i class="ri ri-add-line me-1"></i><span id="label-simpan-snapshot">Tambah Penerima</span></button>
                <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="btn-batal-edit">Batal
                  Edit</button>
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

<script>
  (function() {
    var modalEl = document.getElementById('modal-penerima');
    if (!modalEl) return;

    var bisaTulis = modalEl.getAttribute('data-bisa-tulis') === '1';
    var urlDasar = @json(url('/kelola-bos/bku'));
    var barisId = null;
    var data = null;
    var editId = null;

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

    function fmtPersen(v) {
      return (Number(v) || 0).toLocaleString('id-ID', {
        maximumFractionDigits: 2
      }) + '%';
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

    function segarkanLencana(jml) {
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

      if (bisaTulis) {
        var radio = modalEl.querySelector(
          'input[name="meta-status"][value="' + (g.status || 'belum') + '"]');
        if (radio) radio.checked = true;
      } else {
        var labelStatus = {
          belum: ['bg-label-secondary', 'Belum'],
          lunas: ['bg-success', 'Lunas'],
          selesai: ['bg-primary', 'Selesai']
        }[g.status || 'belum'];
        $('meta-status-baca').innerHTML =
          '<span class="badge ' + labelStatus[0] + '">' + labelStatus[1] + '</span>';
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

      var lencanaStatus = wadah.querySelector('.lencana-status-grup');
      if (g.status === 'lunas' || g.status === 'selesai') {
        if (!lencanaStatus) {
          lencanaStatus = document.createElement('span');
          var vaEl = wadah.querySelector('.lencana-va-grup');
          if (vaEl) {
            wadah.insertBefore(lencanaStatus, vaEl);
          } else {
            wadah.appendChild(lencanaStatus);
          }
        }
        lencanaStatus.className = 'badge lencana-status-grup ' +
          (g.status === 'lunas' ? 'bg-success' : 'bg-primary');
        lencanaStatus.textContent = g.status === 'lunas' ? 'Lunas' : 'Selesai';
      } else if (lencanaStatus) {
        lencanaStatus.remove();
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
        gambarMeta();
        segarkanMetaBaris();
        tandaiMetaTersimpan();
      } catch (e) {
        gambarMeta(); // kembalikan saklar ke nilai yang tersimpan
        gagal(e);
      }
    }

    function gambarTabel() {
      var tbody = $('penerima-tbody');
      var daftar = data.penerima || [];
      if (!daftar.length) {
        tbody.innerHTML = '<tr><td colspan="' + (bisaTulis ? 10 : 9) +
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
          return '<tr>' +
            '<td>' + (i + 1) + '</td>' +
            '<td><div class="fw-semibold">' + lolosHtml(p.nama) + asal + '</div>' +
            (sub ? '<div class="small text-muted">' + sub + '</div>' : '') + '</td>' +
            '<td class="text-end">' + fmtVol(p.volume) + '</td>' +
            '<td class="text-end">' + fmtRp(p.satuan) + '</td>' +
            '<td class="text-end">' + fmtRp(p.jumlah) + '</td>' +
            '<td class="text-end">' + fmtPersen(p.pph_persen) + '</td>' +
            '<td class="text-end">' + fmtRp(p.pph) + '</td>' +
            '<td class="text-end fw-semibold">' + fmtRp(p.diterima) + '</td>' +
            '<td class="small">' + rek + '</td>' + aksi + '</tr>';
        }).join('');
      }

      var totJumlah = daftar.reduce(function(s, p) {
        return s + Number(p.jumlah || 0);
      }, 0);
      var totPph = daftar.reduce(function(s, p) {
        return s + Number(p.pph || 0);
      }, 0);
      var totDiterima = daftar.reduce(function(s, p) {
        return s + Number(p.diterima || 0);
      }, 0);
      $('tot-jumlah').textContent = fmtRp(totJumlah);
      $('tot-pph').textContent = fmtRp(totPph);
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

      segarkanLencana(daftar.length);
    }

    function gambarSumberPtk() {
      var wadah = $('daftar-sumber-ptk');
      if (!wadah) return;
      var sudah = {};
      (data.penerima || []).forEach(function(p) {
        if (p.ptk_id) sudah[Number(p.ptk_id)] = true;
      });
      var daftar = data.sumber_ptk || [];
      if (!daftar.length) {
        wadah.innerHTML = '<div class="text-muted small">Tidak ada PTK aktif di sekolah ini.</div>';
        return;
      }
      wadah.innerHTML = daftar.map(function(p) {
        var sub = subInfo([
          p.nip ? 'NIP ' + p.nip : null,
          p.pangkat_golongan,
          p.nama_bank ? p.nama_bank + ' ' + (p.no_rekening || '') : null
        ]);
        var ada = !!sudah[Number(p.id)];
        return '<label class="d-flex gap-2 align-items-start border rounded px-2 py-2 mb-2' +
          (ada ? ' opacity-50' : '') + '">' +
          '<input type="checkbox" class="form-check-input mt-1 pilih-ptk" value="' + p.id + '"' +
          (ada ? ' disabled' : '') + '>' +
          '<span><span class="fw-semibold">' + lolosHtml(p.nama) + '</span>' +
          (ada ? ' <span class="badge bg-label-success">sudah ditambahkan</span>' : '') +
          (sub ? '<br><span class="small text-muted">' + sub + '</span>' : '') + '</span></label>';
      }).join('');
    }

    function gambarSumberMaster() {
      var wadah = $('daftar-sumber-master');
      if (!wadah) return;
      var sudah = {};
      (data.penerima || []).forEach(function(p) {
        if (p.penerima_id) sudah[Number(p.penerima_id)] = true;
      });
      var daftar = data.sumber_penerima || [];
      if (!daftar.length) {
        wadah.innerHTML =
          '<div class="text-muted small">Tabel penerima masih kosong — buat lewat tombol "Penerima Baru".</div>';
        return;
      }
      wadah.innerHTML = daftar.map(function(p) {
        var sub = subInfo([
          p.nip ? 'NIP ' + p.nip : null,
          p.pangkat_gol,
          p.nik ? 'NIK ' + p.nik : null,
          p.nama_bank ? p.nama_bank + ' ' + (p.no_rekening || '') : null
        ]);
        var ada = !!sudah[Number(p.id)];
        return '<div class="d-flex gap-2 align-items-start border rounded px-2 py-2 mb-2' +
          (ada ? ' opacity-50' : '') + '">' +
          '<label class="d-flex gap-2 align-items-start flex-grow-1 mb-0">' +
          '<input type="checkbox" class="form-check-input mt-1 pilih-master" value="' + p.id + '"' +
          (ada ? ' disabled' : '') + '>' +
          '<span><span class="fw-semibold">' + lolosHtml(p.nama) + '</span>' +
          (ada ? ' <span class="badge bg-label-success">sudah ditambahkan</span>' : '') +
          (sub ? '<br><span class="small text-muted">' + sub + '</span>' : '') + '</span></label>' +
          '<span class="text-nowrap">' +
          '<button type="button" class="btn btn-sm btn-icon btn-text-primary" data-master-edit="' + p.id +
          '" title="Ubah data induk"><i class="ri ri-pencil-line"></i></button>' +
          '<button type="button" class="btn btn-sm btn-icon btn-text-danger" data-master-hapus="' + p.id +
          '" title="Hapus dari tabel penerima"><i class="ri ri-delete-bin-line"></i></button>' +
          '</span></div>';
      }).join('');
    }

    function gambarSemua() {
      gambarInfo();
      gambarMeta();
      gambarTabel();
      gambarSumberPtk();
      gambarSumberMaster();
    }

    async function muat() {
      data = await kirim(urlDasar + '/' + barisId + '/penerima', 'GET');
      gambarSemua();
    }

    // ── Form snapshot (manual / edit) ──
    function kosongkanFormSnapshot() {
      if (!bisaTulis) return;
      editId = null;
      $('f-id').value = '';
      $('f-nama').value = '';
      $('f-nip').value = '';
      $('f-nik').value = '';
      $('f-pangkat').value = '';
      $('f-rekening').value = '';
      $('f-bank').value = '';
      $('f-volume').value = 1;
      $('f-satuan').value = 0;
      $('f-pph').value = 0;
      $('f-simpan-master').checked = false;
      $('wrap-simpan-master').style.display = '';
      $('label-simpan-snapshot').textContent = 'Tambah Penerima';
      $('btn-batal-edit').classList.add('d-none');
    }

    function isiFormSnapshot(p) {
      editId = p.id;
      $('f-id').value = p.id;
      $('f-nama').value = p.nama || '';
      $('f-nip').value = p.nip || '';
      $('f-nik').value = p.nik || '';
      $('f-pangkat').value = p.pangkat_gol || '';
      $('f-rekening').value = p.no_rekening || '';
      $('f-bank').value = p.nama_bank || '';
      $('f-volume').value = p.volume;
      $('f-satuan').value = p.satuan;
      $('f-pph').value = p.pph_persen;
      $('wrap-simpan-master').style.display = 'none';
      $('label-simpan-snapshot').textContent = 'Simpan Perubahan';
      $('btn-batal-edit').classList.remove('d-none');
      var tabBtn = $('tabbtn-manual');
      if (tabBtn) bootstrap.Tab.getOrCreateInstance(tabBtn).show();
    }

    async function simpanSnapshot() {
      var payload = {
        nama: $('f-nama').value.trim(),
        nip: $('f-nip').value.trim() || null,
        pangkat_gol: $('f-pangkat').value.trim() || null,
        nik: $('f-nik').value.trim() || null,
        no_rekening: $('f-rekening').value.trim() || null,
        nama_bank: $('f-bank').value.trim() || null,
        volume: parseFloat($('f-volume').value) || 0,
        satuan: parseFloat($('f-satuan').value) || 0,
        pph_persen: parseFloat($('f-pph').value) || 0
      };
      try {
        var res;
        if (editId) {
          res = await kirim(urlDasar + '/' + barisId + '/penerima/' + editId, 'PUT', payload);
        } else {
          payload.simpan_master = $('f-simpan-master').checked ? 1 : 0;
          res = await kirim(urlDasar + '/' + barisId + '/penerima', 'POST', payload);
        }
        berhasil(res);
        kosongkanFormSnapshot();
        await muat();
      } catch (e) {
        gagal(e);
      }
    }

    // ── Form induk penerima ──
    function sembunyikanFormMaster() {
      if (!bisaTulis) return;
      $('form-master-wrap').classList.add('d-none');
      $('m-id').value = '';
    }

    function bukaFormMaster(master) {
      $('m-id').value = master ? master.id : '';
      $('m-nama').value = master ? (master.nama || '') : '';
      $('m-nik').value = master ? (master.nik || '') : '';
      $('m-nip').value = master ? (master.nip || '') : '';
      $('m-pangkat').value = master ? (master.pangkat_gol || '') : '';
      $('m-rekening').value = master ? (master.no_rekening || '') : '';
      $('m-bank').value = master ? (master.nama_bank || '') : '';
      $('m-alamat').value = master ? (master.alamat || '') : '';
      $('judul-form-master').textContent = master ?
        'Ubah Penerima (Tabel Penerima)' :
        'Penerima Baru (Tabel Penerima)';
      $('form-master-wrap').classList.remove('d-none');
      $('m-nama').focus();
    }

    async function simpanMaster() {
      var payload = {
        nama: $('m-nama').value.trim(),
        nik: $('m-nik').value.trim() || null,
        nip: $('m-nip').value.trim() || null,
        pangkat_gol: $('m-pangkat').value.trim() || null,
        alamat: $('m-alamat').value.trim() || null,
        no_rekening: $('m-rekening').value.trim() || null,
        nama_bank: $('m-bank').value.trim() || null
      };
      var mid = $('m-id').value;
      try {
        var res = mid ?
          await kirim(urlDasar + '/' + barisId + '/penerima-master/' + mid, 'PUT', payload) :
          await kirim(urlDasar + '/' + barisId + '/penerima-master', 'POST', payload);
        berhasil(res);
        sembunyikanFormMaster();
        await muat();
      } catch (e) {
        gagal(e);
      }
    }

    function idTercentang(kelas) {
      return Array.prototype.map.call(
        modalEl.querySelectorAll('.' + kelas + ':checked'),
        function(el) {
          return Number(el.value);
        }
      );
    }

    // ── Buka modal dari tombol di tabel BKU ──
    document.addEventListener('click', function(e) {
      var btn = e.target.closest('[data-penerima-baris]');
      if (!btn) return;
      barisId = btn.getAttribute('data-id');
      data = null;
      editId = null;
      if (bisaTulis) {
        kosongkanFormSnapshot();
        sembunyikanFormMaster();
      }
      $('penerima-info-grup').innerHTML = '<span class="text-muted">Memuat…</span>';
      $('meta-siplah-info').innerHTML = '';
      $('meta-status-baca').innerHTML = '';
      $('meta-va-wrap').classList.add('d-none');
      $('penerima-tbody').innerHTML = '<tr><td colspan="' + (bisaTulis ? 10 : 9) +
        '" class="text-center text-muted">Memuat…</td></tr>';
      $('penerima-selisih').innerHTML = '';
      bootstrap.Modal.getOrCreateInstance(modalEl).show();
      muat().catch(gagal);
    });

    if (bisaTulis) {
      $('btn-tambah-ptk').addEventListener('click', async function() {
        var ids = idTercentang('pilih-ptk');
        if (!ids.length) {
          Swal.fire('Perhatian', 'Centang dulu PTK yang akan ditambahkan.', 'warning');
          return;
        }
        try {
          var res = await kirim(urlDasar + '/' + barisId + '/penerima/ptk', 'POST', {
            ptk_ids: ids
          });
          berhasil(res);
          await muat();
        } catch (e) {
          gagal(e);
        }
      });

      $('btn-tambah-master-pilihan').addEventListener('click', async function() {
        var ids = idTercentang('pilih-master');
        if (!ids.length) {
          Swal.fire('Perhatian', 'Centang dulu penerima yang akan ditambahkan.', 'warning');
          return;
        }
        try {
          var res = await kirim(urlDasar + '/' + barisId + '/penerima/master', 'POST', {
            penerima_ids: ids
          });
          berhasil(res);
          await muat();
        } catch (e) {
          gagal(e);
        }
      });

      $('btn-master-baru').addEventListener('click', function() {
        bukaFormMaster(null);
      });
      $('btn-batal-master').addEventListener('click', sembunyikanFormMaster);
      $('btn-simpan-master').addEventListener('click', simpanMaster);
      $('btn-simpan-snapshot').addEventListener('click', simpanSnapshot);
      $('btn-batal-edit').addEventListener('click', kosongkanFormSnapshot);

      // Saklar status meta grup: tersimpan otomatis begitu
      // diganti (PUT penerima-grup, membawa VA saat ini).
      modalEl.querySelectorAll('input[name="meta-status"]').forEach(function(radio) {
        radio.addEventListener('change', function() {
          if (!data || !data.grup) return;
          var va = data.grup.siplah ?
            $('meta-nomor-va').value.trim() :
            (data.grup.nomor_va || '');
          simpanMeta(radio.value, va || null);
        });
      });

      $('btn-simpan-va').addEventListener('click', function() {
        if (!data || !data.grup) return;
        var terpilih = modalEl.querySelector('input[name="meta-status"]:checked');
        simpanMeta(
          terpilih ? terpilih.value : (data.grup.status || 'belum'),
          $('meta-nomor-va').value.trim() || null
        );
      });

      // Aksi per baris di dalam modal (delegasi klik)
      modalEl.addEventListener('click', async function(e) {
        var btnEdit = e.target.closest('[data-aksi-edit]');
        if (btnEdit) {
          var target = (data.penerima || []).find(function(p) {
            return Number(p.id) === Number(btnEdit.getAttribute('data-aksi-edit'));
          });
          if (target) isiFormSnapshot(target);
          return;
        }

        var btnHapus = e.target.closest('[data-aksi-hapus]');
        if (btnHapus) {
          var pid = btnHapus.getAttribute('data-aksi-hapus');
          if (await konfirmasiHapus('Hapus penerima ini?',
              'Penerima akan dihapus dari daftar grup transaksi ini.')) {
            try {
              var res = await kirim(urlDasar + '/' + barisId + '/penerima/' + pid, 'DELETE');
              berhasil(res);
              if (editId && Number(editId) === Number(pid)) kosongkanFormSnapshot();
              await muat();
            } catch (err) {
              gagal(err);
            }
          }
          return;
        }

        var btnMasterEdit = e.target.closest('[data-master-edit]');
        if (btnMasterEdit) {
          var m = (data.sumber_penerima || []).find(function(p) {
            return Number(p.id) === Number(btnMasterEdit.getAttribute('data-master-edit'));
          });
          if (m) bukaFormMaster(m);
          return;
        }

        var btnMasterHapus = e.target.closest('[data-master-hapus]');
        if (btnMasterHapus) {
          var mid = btnMasterHapus.getAttribute('data-master-hapus');
          if (await konfirmasiHapus('Hapus dari tabel penerima?',
              'Data induk penerima dihapus. Penerima yang sudah masuk ke grup transaksi (snapshot) tidak ikut terhapus.')) {
            try {
              var res2 = await kirim(urlDasar + '/' + barisId + '/penerima-master/' + mid, 'DELETE');
              berhasil(res2);
              await muat();
            } catch (err2) {
              gagal(err2);
            }
          }
        }
      });
    }
  })();
</script>
