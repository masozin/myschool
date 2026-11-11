@extends('layouts/layoutMaster')

@section('title', 'Editor Jadwal — ' . $rombel->nama_rombel)

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
  <style>
    .sel-jadwal {
      border: 1px dashed #cfd4da;
      border-radius: 6px;
      padding: 4px 6px;
      min-height: 54px;
    }

    .sel-terisi {
      border-style: solid;
      border-color: #b9c2ff;
      background: #f2f4ff;
    }

    .sel-nonaktif {
      color: #c3cad1;
      text-align: center;
      padding: 10px 0;
    }

    .sel-kegiatan {
      border-style: solid;
      border-color: #ffd88a;
      background: #fff9ec;
    }

    @if ($bolehEdit)
      .sel-jadwal {
        cursor: pointer;
      }

      .sel-jadwal:hover {
        border-color: #696cff;
      }
    @endif
  </style>
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

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
          <i class="icon-base ri ri-calendar-2-line ri-28px"></i>
        </span>
      </div>
      <div class="flex-grow-1">
        <h4 class="mb-1">Jadwal — {{ $rombel->nama_rombel }}</h4>
        <div class="text-muted small">
          {{ $sekolah->detailTerbaru->nama_sekolah ?? '-' }}
          @if ($semester)
            <span class="mx-1">•</span> Semester {{ $semester->semester }} {{ $semester->tahun_pelajaran }}
          @endif
          @unless ($bolehEdit)
            <span class="mx-1">•</span> Mode lihat saja
          @endunless
        </div>
      </div>
      <a href="{{ route('jadwal-pelajaran.index') }}" class="btn btn-outline-secondary">
        <i class="ri ri-arrow-left-line me-1"></i> Kembali
      </a>
      @if ($bolehEdit)
        <a href="{{ route('jadwal-pelajaran.pengampu', $rombel) }}" class="btn btn-outline-primary">
          <i class="ri ri-user-star-line me-1"></i> Pengampu
        </a>
      @endif
    </div>
  </div>

  @if (!$tersimpan)
    <div class="alert alert-info">Jadwal ini memakai kerangka jam bawaan karena Pengaturan Jam semester ini belum
      disimpan.</div>
  @endif

  @if ($luarKerangka->isNotEmpty())
    <div class="card border-warning mb-4">
      <div class="card-body py-3">
        <div class="fw-semibold text-warning mb-2">
          <i class="ri ri-alert-line me-1"></i>{{ $luarKerangka->count() }} kotak berada di luar kerangka jam saat ini
        </div>
        <ul class="list-unstyled mb-0">
          @foreach ($luarKerangka as $lk)
            <li class="d-flex align-items-center gap-2 py-1 item-luar" data-hari="{{ $lk->hari }}"
              data-jam="{{ $lk->jam_ke }}">
              <span>{{ $namaHari[$lk->hari] ?? '?' }}, jam ke-{{ $lk->jam_ke }} —
                @if ($lk->pengampu)
                  {{ $lk->pengampu->mapping->masterMapel->nama ?? '-' }}
                  ({{ $lk->pengampu->ptk->nama_ptk ?? '-' }})
                @else
                  {{ $lk->nama_kegiatan }}{{ $lk->pembina ? ' (' . $lk->pembina->nama_ptk . ')' : '' }}
                @endif
              </span>
              @if ($bolehEdit)
                <button type="button" class="btn btn-sm btn-outline-danger py-0 btn-hapus-luar">
                  <i class="ri ri-delete-bin-line"></i> Hapus
                </button>
              @endif
            </li>
          @endforeach
        </ul>
        <small class="text-muted">Kotak-kotak ini tidak tampil di kisi karena harinya dipangkas atau jumlah jamnya
          dikurangi pada Pengaturan Jam. Hapus bila memang tidak dipakai lagi.</small>
      </div>
    </div>
  @endif

  <div class="row g-4">
    <div class="col-12 col-xl-9">
      <div class="card">
        <div class="table-responsive">
          <table class="table mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 64px;">Jam</th>
                @foreach ($hariAktif as $hari)
                  <th class="text-center">{{ $namaHari[$hari] }}</th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              @for ($jam = 1; $jam <= $maxJam + 1; $jam++)
                @if (in_array($jam - 1, $jangkarKegiatan))
                  <tr class="table-warning">
                    <td></td>
                    @foreach ($hariAktif as $hari)
                      <td class="p-1">
                        @foreach ($kegiatanWaktu[$hari][$jam - 1] ?? [] as $keg)
                          <div style="font-size: 12px;">
                            <span class="fw-semibold">{{ $keg['nama'] }}</span>
                            <span class="text-muted"
                              style="font-size: 10px;">{{ $keg['waktu'][0] }}–{{ $keg['waktu'][1] }}</span>
                          </div>
                        @endforeach
                      </td>
                    @endforeach
                  </tr>
                @endif
                @if ($jam <= $maxJam)
                  <tr>
                    <td class="fw-semibold text-center align-middle">{{ $jam }}</td>
                    @foreach ($hariAktif as $hari)
                      @php
                        $frame = $hariFrame[$hari];
                        $isi = $sel[$hari][$jam] ?? null;
                      @endphp
                      <td class="p-1">
                        @if ($jam <= (int) $frame['jumlah_jam'])
                          <div
                            class="sel-jadwal {{ $isi ? 'sel-terisi' : '' }} {{ $isi && !$isi->pengampu_id ? 'sel-kegiatan' : '' }}"
                            data-hari="{{ $hari }}" data-jam="{{ $jam }}"
                            data-pengampu="{{ $isi?->pengampu_id ?? '' }}"
                            data-tipe="{{ $isi ? ($isi->pengampu_id ? 'pelajaran' : 'kegiatan') : '' }}"
                            data-nama="{{ $isi?->nama_kegiatan ?? '' }}"
                            data-pembina="{{ $isi?->pembina_id ?? '' }}"
                            data-waktu="{{ $waktu[$hari][$jam][0] }}–{{ $waktu[$hari][$jam][1] }}">
                            @if ($isi && $isi->pengampu_id)
                              <div class="fw-semibold" style="font-size: 12px;">
                                {{ $isi->pengampu->mapping->masterMapel->nama_singkat ?: $isi->pengampu->mapping->masterMapel->nama }}
                              </div>
                              <div class="text-muted" style="font-size: 11px;">{{ $isi->pengampu->ptk->nama_ptk }}
                              </div>
                            @elseif ($isi)
                              <div class="fw-semibold" style="font-size: 12px;">{{ $isi->nama_kegiatan }}</div>
                              @if ($isi->pembina)
                                <div class="text-muted" style="font-size: 11px;">{{ $isi->pembina->nama_ptk }}</div>
                              @endif
                            @else
                              <div class="text-muted" style="font-size: 12px;">{{ $bolehEdit ? '+ Isi' : '—' }}</div>
                            @endif
                            <div class="text-muted" style="font-size: 10px;">
                              {{ $waktu[$hari][$jam][0] }}–{{ $waktu[$hari][$jam][1] }}</div>
                          </div>
                        @else
                          <div class="sel-nonaktif">—</div>
                        @endif
                      </td>
                    @endforeach
                  </tr>
                @endif
              @endfor
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-3">
      <div class="card">
        <div class="card-header pb-3">
          <h5 class="mb-0">Pengampu Rombel Ini</h5>
        </div>
        <div class="card-body py-3">
          @forelse ($pengampuList as $p)
            <div class="d-flex justify-content-between align-items-center py-1">
              <div>
                <div class="fw-semibold" style="font-size: 13px;">{{ $p->mapping->masterMapel->nama ?? '-' }}</div>
                <small class="text-muted">{{ $p->ptk->nama_ptk ?? '-' }}</small>
              </div>
              <span class="badge bg-label-primary hitung-pengampu" data-pengampu="{{ $p->id }}">
                {{ $terplot[$p->id] ?? 0 }} jam
              </span>
            </div>
          @empty
            <div class="text-muted small">
              Belum ada pengampu untuk rombel ini.
              @if ($bolehEdit)
                Tetapkan dulu lewat tombol <strong>Pengampu</strong> di atas, baru isi jadwalnya.
              @endif
            </div>
          @endforelse
          @if ($bolehEdit && $pengampuList->isNotEmpty())
            <small class="text-muted d-block mt-2">Klik kotak pada kisi untuk mengisi, mengganti, atau menghapus
              pelajaran. Guru yang sedang mengajar di rombel lain pada waktu yang sama tidak bisa dipilih.</small>
          @endif
        </div>
      </div>
    </div>
  </div>

  @if ($bolehEdit)
    {{-- Modal atur kotak --}}
    <div class="modal fade" id="modalSel" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modal-sel-judul">Atur Kotak</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="btn-group w-100 mb-3" role="group" aria-label="Jenis isi kotak">
              <input type="radio" class="btn-check" name="mode-sel" id="mode-pelajaran" value="pelajaran" checked>
              <label class="btn btn-outline-primary" for="mode-pelajaran">Pelajaran</label>
              <input type="radio" class="btn-check" name="mode-sel" id="mode-kegiatan" value="kegiatan">
              <label class="btn btn-outline-primary" for="mode-kegiatan">Kegiatan</label>
            </div>

            <div id="panel-pelajaran">
              <label class="form-label" for="sel-pengampu">Pelajaran / Pengampu</label>
              <select id="sel-pengampu" class="form-select">
                @foreach ($pengampuList as $p)
                  <option value="{{ $p->id }}" data-ptk="{{ $p->ptk_id }}"
                    data-teks="{{ $p->mapping->masterMapel->nama ?? '-' }} — {{ $p->ptk->nama_ptk ?? '-' }}">
                    {{ $p->mapping->masterMapel->nama ?? '-' }} — {{ $p->ptk->nama_ptk ?? '-' }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted d-block mt-2">Pilihan yang bertanda bentrok sedang bertugas di rombel lain
                pada waktu ini dan tidak bisa dipilih.</small>
            </div>

            <div id="panel-kegiatan" class="d-none">
              <label class="form-label" for="sel-nama-kegiatan">Nama kegiatan</label>
              <input type="text" id="sel-nama-kegiatan" class="form-control" list="usul-kegiatan" maxlength="100"
                placeholder="mis. Pramuka">
              <datalist id="usul-kegiatan">
                <option value="Pramuka"></option>
                <option value="Kokurikuler"></option>
                <option value="Literasi"></option>
                <option value="Upacara"></option>
                <option value="Senam"></option>
                <option value="Kamis Ceria"></option>
                <option value="Ekstrakurikuler"></option>
                <option value="Apel"></option>
              </datalist>
              <label class="form-label mt-3" for="sel-pembina">Pembina (boleh kosong)</label>
              <select id="sel-pembina" class="form-select">
                <option value="" data-teks="— Tanpa pembina —">— Tanpa pembina —</option>
                @foreach ($guruList as $g)
                  <option value="{{ $g->id }}" data-ptk="{{ $g->id }}" data-teks="{{ $g->nama_ptk }}">
                    {{ $g->nama_ptk }}</option>
                @endforeach
              </select>
              <small class="text-muted d-block mt-2">Bila pembina diisi, ia juga dijaga dari bentrok dengan rombel
                lain pada waktu yang sama.</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-danger me-auto d-none" id="btn-hapus-sel">
              <i class="ri ri-delete-bin-line me-1"></i>Hapus
            </button>
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-primary" id="btn-simpan-sel">
              <i class="ri ri-check-line me-1"></i>Simpan
            </button>
          </div>
        </div>
      </div>
    </div>
  @endif

@endsection

@if ($bolehEdit)
  @section('page-script')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const ROMBEL_ID = {{ $rombel->id }};
        const SIBUK = @json($sibuk);
        const NAMA_HARI = @json($namaHari);
        const modal = new bootstrap.Modal(document.getElementById('modalSel'));
        const select = document.getElementById('sel-pengampu');
        const selectPembina = document.getElementById('sel-pembina');
        const inputNama = document.getElementById('sel-nama-kegiatan');
        const radioPelajaran = document.getElementById('mode-pelajaran');
        const radioKegiatan = document.getElementById('mode-kegiatan');
        const panelPelajaran = document.getElementById('panel-pelajaran');
        const panelKegiatan = document.getElementById('panel-kegiatan');
        let aktif = null;

        function aturMode(mode) {
          const kegiatan = mode === 'kegiatan';
          if (kegiatan) {
            radioKegiatan.checked = true;
          } else {
            radioPelajaran.checked = true;
          }
          panelPelajaran.classList.toggle('d-none', kegiatan);
          panelKegiatan.classList.toggle('d-none', !kegiatan);
        }

        [radioPelajaran, radioKegiatan].forEach(r => {
          r.addEventListener('change', function() {
            aturMode(this.value);
          });
        });

        function segarkanHitungan() {
          const hitung = {};
          document.querySelectorAll('.sel-jadwal').forEach(el => {
            const pid = el.dataset.pengampu;
            if (pid) hitung[pid] = (hitung[pid] || 0) + 1;
          });
          document.querySelectorAll('.hitung-pengampu').forEach(b => {
            b.textContent = (hitung[b.dataset.pengampu] || 0) + ' jam';
          });
        }

        function gambarSel(el, data) {
          const waktu = el.dataset.waktu;
          el.dataset.pengampu = '';
          el.dataset.tipe = '';
          el.dataset.nama = '';
          el.dataset.pembina = '';
          el.classList.remove('sel-terisi', 'sel-kegiatan');
          if (!data) {
            el.innerHTML = `<div class="text-muted" style="font-size: 12px;">+ Isi</div>
              <div class="text-muted" style="font-size: 10px;">${waktu}</div>`;
          } else if (data.tipe === 'kegiatan') {
            el.dataset.tipe = 'kegiatan';
            el.dataset.nama = data.nama_kegiatan;
            el.dataset.pembina = data.pembina_id || '';
            el.classList.add('sel-terisi', 'sel-kegiatan');
            el.innerHTML = `<div class="fw-semibold" style="font-size: 12px;">${data.nama_kegiatan}</div>
              ${data.pembina ? `<div class="text-muted" style="font-size: 11px;">${data.pembina}</div>` : ''}
              <div class="text-muted" style="font-size: 10px;">${waktu}</div>`;
          } else {
            el.dataset.tipe = 'pelajaran';
            el.dataset.pengampu = data.pengampu_id;
            el.classList.add('sel-terisi');
            el.innerHTML = `<div class="fw-semibold" style="font-size: 12px;">${data.singkat}</div>
              <div class="text-muted" style="font-size: 11px;">${data.guru}</div>
              <div class="text-muted" style="font-size: 10px;">${waktu}</div>`;
          }
        }

        async function kirim(url, body) {
          const res = await fetch(url, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': csrf,
              'Content-Type': 'application/json',
              'Accept': 'application/json'
            },
            body: JSON.stringify(body)
          });
          const json = await res.json().catch(() => ({}));
          if (!res.ok) throw new Error(json.message || 'Terjadi kesalahan.');
          return json;
        }

        // Tandai pilihan yang gurunya sibuk di rombel lain pada slot
        // aktif. kunciOpt mengambil identitas yang dibandingkan
        // dengan isi kotak ini sendiri (pengampu: nilai option;
        // pembina: data-ptk option) supaya isi sekarang tidak ikut
        // terlarang.
        function tandaiBentrok(selEl, kunciOpt, milikIni) {
          const sibukDiSlot = (SIBUK[aktif.hari] || {})[aktif.jam] || [];
          const petaSibuk = {};
          sibukDiSlot.forEach(s => {
            petaSibuk[s.ptk_id] = s.rombel;
          });
          [...selEl.options].forEach(opt => {
            opt.disabled = false;
            if (opt.dataset.teks) opt.textContent = opt.dataset.teks;
            const bentrokDi = petaSibuk[opt.dataset.ptk];
            if (bentrokDi && String(kunciOpt(opt)) !== String(milikIni)) {
              opt.disabled = true;
              opt.textContent = opt.dataset.teks + ` — bentrok (bertugas di ${bentrokDi})`;
            }
          });
        }

        document.querySelectorAll('.sel-jadwal').forEach(el => {
          el.addEventListener('click', function() {
            const adaPengampu = select.options.length > 0;
            aktif = {
              hari: this.dataset.hari,
              jam: this.dataset.jam,
              el: this
            };
            document.getElementById('modal-sel-judul').textContent =
              `${NAMA_HARI[this.dataset.hari]}, Jam ke-${this.dataset.jam} (${this.dataset.waktu})`;

            radioPelajaran.disabled = !adaPengampu;
            aturMode(this.dataset.tipe === 'kegiatan' || !adaPengampu ? 'kegiatan' : 'pelajaran');

            tandaiBentrok(select, opt => opt.value, this.dataset.pengampu || '');
            tandaiBentrok(selectPembina, opt => opt.dataset.ptk, this.dataset.pembina || '');
            if (adaPengampu) {
              select.value = this.dataset.pengampu || select.options[0].value;
            }
            inputNama.value = this.dataset.nama || '';
            selectPembina.value = this.dataset.pembina || '';
            const terisi = !!(this.dataset.pengampu || this.dataset.nama);
            document.getElementById('btn-hapus-sel').classList.toggle('d-none', !terisi);
            modal.show();
          });
        });

        document.getElementById('btn-simpan-sel').addEventListener('click', async function() {
          if (!aktif) return;
          const body = {
            rombel_id: ROMBEL_ID,
            hari: aktif.hari,
            jam_ke: aktif.jam
          };
          if (radioKegiatan.checked) {
            const nama = inputNama.value.trim();
            if (!nama) {
              Swal.fire({
                icon: 'warning',
                title: 'Nama Kegiatan Kosong',
                text: 'Isi nama kegiatannya dulu, atau beralih ke mode Pelajaran.'
              });
              return;
            }
            body.nama_kegiatan = nama;
            body.pembina_id = selectPembina.value || null;
          } else {
            body.pengampu_id = select.value;
          }
          try {
            const hasil = await kirim('{{ route('jadwal-pelajaran.sel.simpan') }}', body);
            gambarSel(aktif.el, hasil);
            segarkanHitungan();
            modal.hide();
          } catch (err) {
            Swal.fire({
              icon: 'error',
              title: 'Tidak Bisa Disimpan',
              text: err.message
            });
          }
        });

        document.getElementById('btn-hapus-sel').addEventListener('click', async function() {
          if (!aktif) return;
          try {
            await kirim('{{ route('jadwal-pelajaran.sel.hapus') }}', {
              rombel_id: ROMBEL_ID,
              hari: aktif.hari,
              jam_ke: aktif.jam
            });
            gambarSel(aktif.el, null);
            segarkanHitungan();
            modal.hide();
          } catch (err) {
            Swal.fire({
              icon: 'error',
              title: 'Gagal Menghapus',
              text: err.message
            });
          }
        });

        document.querySelectorAll('.btn-hapus-luar').forEach(btn => {
          btn.addEventListener('click', async function() {
            const li = this.closest('.item-luar');
            try {
              await kirim('{{ route('jadwal-pelajaran.sel.hapus') }}', {
                rombel_id: ROMBEL_ID,
                hari: li.dataset.hari,
                jam_ke: li.dataset.jam
              });
              li.remove();
            } catch (err) {
              Swal.fire({
                icon: 'error',
                title: 'Gagal Menghapus',
                text: err.message
              });
            }
          });
        });
      });
    </script>
  @endsection
@endif
