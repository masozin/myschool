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
                {{ $lk->pengampu->mapping->masterMapel->nama ?? '-' }}
                ({{ $lk->pengampu->ptk->nama_ptk ?? '-' }})</span>
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
              @for ($jam = 1; $jam <= $maxJam; $jam++)
                <tr>
                  <td class="fw-semibold text-center align-middle">{{ $jam }}</td>
                  @foreach ($hariAktif as $hari)
                    @php
                      $frame = $hariFrame[$hari];
                      $isi = $sel[$hari][$jam] ?? null;
                    @endphp
                    <td class="p-1">
                      @if ($jam <= (int) $frame['jumlah_jam'])
                        <div class="sel-jadwal {{ $isi ? 'sel-terisi' : '' }}" data-hari="{{ $hari }}"
                          data-jam="{{ $jam }}" data-pengampu="{{ $isi?->pengampu_id ?? '' }}"
                          data-waktu="{{ $waktu[$hari][$jam][0] }}–{{ $waktu[$hari][$jam][1] }}">
                          @if ($isi)
                            <div class="fw-semibold" style="font-size: 12px;">
                              {{ $isi->pengampu->mapping->masterMapel->nama_singkat ?: $isi->pengampu->mapping->masterMapel->nama }}
                            </div>
                            <div class="text-muted" style="font-size: 11px;">{{ $isi->pengampu->ptk->nama_ptk }}
                            </div>
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
            <label class="form-label" for="sel-pengampu">Pelajaran / Pengampu</label>
            <select id="sel-pengampu" class="form-select">
              @foreach ($pengampuList as $p)
                <option value="{{ $p->id }}" data-ptk="{{ $p->ptk_id }}"
                  data-teks="{{ $p->mapping->masterMapel->nama ?? '-' }} — {{ $p->ptk->nama_ptk ?? '-' }}">
                  {{ $p->mapping->masterMapel->nama ?? '-' }} — {{ $p->ptk->nama_ptk ?? '-' }}
                </option>
              @endforeach
            </select>
            <small class="text-muted d-block mt-2">Pilihan yang bertanda bentrok sedang mengajar di rombel lain pada
              waktu ini dan tidak bisa dipilih.</small>
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
        let aktif = null;

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
          if (!data) {
            el.dataset.pengampu = '';
            el.classList.remove('sel-terisi');
            el.innerHTML = `<div class="text-muted" style="font-size: 12px;">+ Isi</div>
              <div class="text-muted" style="font-size: 10px;">${waktu}</div>`;
          } else {
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

        document.querySelectorAll('.sel-jadwal').forEach(el => {
          el.addEventListener('click', function() {
            if (!select.options.length) {
              Swal.fire({
                icon: 'info',
                title: 'Belum Ada Pengampu',
                text: 'Tetapkan pengampu rombel ini dulu sebelum mengisi jadwal.'
              });
              return;
            }
            aktif = {
              hari: this.dataset.hari,
              jam: this.dataset.jam,
              el: this
            };
            document.getElementById('modal-sel-judul').textContent =
              `${NAMA_HARI[this.dataset.hari]}, Jam ke-${this.dataset.jam} (${this.dataset.waktu})`;

            const sibukDiSlot = (SIBUK[this.dataset.hari] || {})[this.dataset.jam] || [];
            const petaSibuk = {};
            sibukDiSlot.forEach(s => {
              petaSibuk[s.ptk_id] = s.rombel;
            });
            [...select.options].forEach(opt => {
              opt.disabled = false;
              opt.textContent = opt.dataset.teks;
              const bentrokDi = petaSibuk[opt.dataset.ptk];
              if (bentrokDi && String(opt.value) !== String(this.dataset.pengampu)) {
                opt.disabled = true;
                opt.textContent = opt.dataset.teks + ` — bentrok (mengajar di ${bentrokDi})`;
              }
            });
            select.value = this.dataset.pengampu || select.options[0].value;
            document.getElementById('btn-hapus-sel').classList.toggle('d-none', !this.dataset.pengampu);
            modal.show();
          });
        });

        document.getElementById('btn-simpan-sel').addEventListener('click', async function() {
          if (!aktif) return;
          try {
            const hasil = await kirim('{{ route('jadwal-pelajaran.sel.simpan') }}', {
              rombel_id: ROMBEL_ID,
              pengampu_id: select.value,
              hari: aktif.hari,
              jam_ke: aktif.jam
            });
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
