@extends('layouts/layoutMaster')

@section('title', 'Cetak Kartu Pelajar')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
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

  {{-- Pemilih konteks sekolah untuk Admin --}}
  @hasanyrole('Admin')
    <div class="card mb-4">
      <div class="card-body py-3 d-flex align-items-center gap-3 flex-wrap">
        <span class="text-muted"><i class="icon-base ri ri-school-line me-1"></i>Anda masuk sebagai Admin — konteks
          sekolah:</span>
        <form method="GET" action="{{ url('/kartu-pelajar') }}">
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

  @if (!$sekolahId)
    <div class="alert alert-warning">Pilih sekolah dulu lewat pemilih di atas — daftar rombel dan siswa mengikuti
      sekolah yang dipilih.</div>
  @endif

  {{-- =========================================================== --}}
  {{-- KEPALA HALAMAN + LANGKAH                                    --}}
  {{-- =========================================================== --}}
  <div class="card mb-4">
    <div class="card-body py-4">
      <div class="d-flex align-items-center gap-4 flex-wrap">
        <div class="avatar avatar-xl flex-shrink-0">
          <span class="avatar-initial rounded-circle bg-label-primary">
            <i class="icon-base ri ri-bank-card-2-line ri-28px"></i>
          </span>
        </div>
        <div class="flex-grow-1">
          <h4 class="mb-1">Cetak Kartu Pelajar</h4>
          <div class="text-muted small">
            <i class="icon-base ri ri-ruler-line me-1"></i>Ukuran kartu 8,6 × 5,4 cm
            <span class="mx-1">•</span>
            <i class="icon-base ri ri-file-paper-2-line me-1"></i>Dicetak di kertas A4
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- KELAS & PENCARIAN                                           --}}
  {{-- =========================================================== --}}
  <div class="card mb-4">
    <div class="card-header pb-0">
      <h5 class="mb-0">Kelas &amp; Pencarian</h5>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-12 col-md-6">
          <div class="form-floating form-floating-outline">
            <select id="filter-rombel" class="form-select">
              <option value="">-- Semua Kelas --</option>
              @foreach ($rombels as $r)
                <option value="{{ $r->id }}">{{ $r->nama_rombel }}</option>
              @endforeach
            </select>
            <label for="filter-rombel">Kelas / Rombel</label>
          </div>
        </div>
        <div class="col-12 col-md-6">
          <div class="form-floating form-floating-outline">
            <input type="text" id="filter-search" class="form-control" placeholder="Cari nama / NISN">
            <label for="filter-search">Cari Nama / NISN Siswa</label>
          </div>
        </div>
      </div>

    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- TEMA & MODE SISI (satu kartu)                               --}}
  {{-- =========================================================== --}}
  <div class="card mb-4">
    <div class="card-header pb-0">
      <h5 class="mb-0">Tema &amp; Mode Cetak</h5>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-12 col-lg-8">
          <label class="form-label d-block">Tema Kartu</label>
          <div class="row g-2">
            @foreach ($temaList as $key => $label)
              <div class="col-6 col-md-4">
                <input type="radio" class="btn-check" name="tema_kartu" id="tema-{{ $key }}"
                  value="{{ $key }}" {{ $loop->first ? 'checked' : '' }}>
                <label class="btn btn-outline-primary w-100 text-start py-2" for="tema-{{ $key }}">
                  <span class="d-flex align-items-center gap-2">
                    <span class="rounded-circle flex-shrink-0"
                      style="width:16px;height:16px;background:
                    @if ($key === 'navy-gold') #063b82
                    @elseif($key === 'emerald-modern') #0a6958
                    @else #4a0c19 @endif
                  "></span>
                    <small>{{ ['navy-gold' => 'Navy Gold', 'emerald-modern' => 'Emerald', 'maroon-elegant' => 'Maroon'][$key] ?? $label }}</small>
                  </span>
                </label>
              </div>
            @endforeach
          </div>
        </div>
        <div class="col-12 col-lg-4">
          <label class="form-label d-block">Mode Cetak</label>
          <div class="btn-group w-100" role="group">
            <input type="radio" class="btn-check" name="mode_sisi" id="sisi-1" value="1" checked>
            <label class="btn btn-outline-primary" for="sisi-1">
              <i class="ri ri-file-line me-1"></i>1 Sisi
            </label>
            <input type="radio" class="btn-check" name="mode_sisi" id="sisi-2" value="2">
            <label class="btn btn-outline-primary" for="sisi-2">
              <i class="ri ri-file-copy-2-line me-1"></i>2 Sisi
            </label>
          </div>
          <small class="text-muted d-block mt-2" id="info-mode-sisi">1 baris = 2 siswa berdampingan</small>
        </div>
      </div>
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- LANGKAH 2: PILIH SISWA                                      --}}
  {{-- =========================================================== --}}
  <div class="card mb-4">
    <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h5 class="mb-0">Pilih Siswa</h5>
      <small class="text-muted" id="info-jumlah-siswa">0 siswa tampil</small>
    </div>
    <div class="card-body py-3 d-flex align-items-center flex-wrap gap-3">
      <div class="form-check mb-0">
        <input type="checkbox" class="form-check-input" id="check-all">
        <label class="form-check-label" for="check-all">Pilih semua yang tampil</label>
      </div>
      <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-bersihkan">
        <i class="ri ri-eraser-line me-1"></i>Bersihkan pilihan
      </button>
      <small class="text-muted ms-auto">Klik baris siswa untuk memilih / membatalkan. Pilihan tetap tersimpan
        walau kelas atau pencarian diganti.</small>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th width="40" class="text-center"></th>
            <th>Siswa</th>
            <th>NISN</th>
            <th class="text-center">JK</th>
          </tr>
        </thead>
        <tbody id="tbody-siswa">
          <tr>
            <td colspan="4" class="text-center py-4 text-muted">
              <span class="spinner-border spinner-border-sm me-2"></span> Memuat data...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- LANGKAH 3: BILAH CETAK (menempel di bawah layar)            --}}
  {{-- =========================================================== --}}
  <div class="card border-primary shadow" style="position:sticky;bottom:1rem;z-index:10">
    <div class="card-body py-3 d-flex align-items-center gap-3 flex-wrap">
      <h5 class="mb-0">Cetak</h5>
      <span class="text-muted" id="ringkasan-cetak">Belum ada siswa dipilih.</span>
      <button type="button" id="btn-cetak" class="btn btn-primary ms-auto" disabled>
        <i class="ri ri-printer-line me-1"></i> Cetak PDF (<span id="info-jumlah-dipilih">0</span> siswa)
      </button>
    </div>
  </div>

  {{-- Form tersembunyi untuk submit cetak PDF (buka tab baru) --}}
  <form id="form-cetak" action="{{ route('kartu-pelajar.cetak') }}" method="POST" target="_blank" class="d-none">
    @csrf
    <input type="hidden" name="sisi" id="input-sisi">
    <div id="wrapper-siswa-ids"></div>
  </form>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {

      // ── State ───────────────────────────────────────────────────
      let allSiswa = [];
      const selectedIds = new Set();

      const AVATAR_PALETTE = [
        '#696cff', '#03c3ec', '#71dd37', '#ff3e1d',
        '#ffab00', '#20c997', '#f26cf9', '#8592a3',
      ];

      function getInitials(name) {
        return (name || '?').trim().split(/\s+/).slice(0, 2)
          .map(w => w.charAt(0)).join('').toUpperCase();
      }

      function avatarColor(name) {
        let h = 0;
        for (const c of (name || '')) h = (h * 31 + c.charCodeAt(0)) >>> 0;
        return AVATAR_PALETTE[h % AVATAR_PALETTE.length];
      }

      function renderSiswaCell(s) {
        const initials = getInitials(s.nama_siswa);
        const color = avatarColor(s.nama_siswa);
        const foto = s.photo ?
          `<img src="/storage/${s.photo}" alt=""
             class="rounded-circle flex-shrink-0" width="36" height="36"
             style="object-fit:cover"
             onerror="this.outerHTML = window.__avatarCadangan('${color}', '${initials}')">` :
          `<span class="d-flex align-items-center justify-content-center rounded-circle
                   text-white fw-semibold flex-shrink-0"
               style="width:36px;height:36px;background:${color};font-size:12px">${initials}</span>`;
        return `
          <div class="d-flex align-items-center gap-2">
            ${foto}
            <span>${s.nama_siswa}</span>
          </div>`;
      }

      // Cadangan avatar inisial bila berkas foto gagal dimuat.
      window.__avatarCadangan = function(color, initials) {
        return `<span class="d-flex align-items-center justify-content-center rounded-circle
                 text-white fw-semibold flex-shrink-0"
             style="width:36px;height:36px;background:${color};font-size:12px">${initials}</span>`;
      };

      // ── Mode sisi: update keterangan ────────────────────────────
      document.querySelectorAll('input[name="mode_sisi"]').forEach(radio => {
        radio.addEventListener('change', function() {
          document.getElementById('info-mode-sisi').textContent = this.value === '1' ?
            '1 baris = 2 siswa berdampingan' :
            '1 baris = depan-belakang untuk 1 siswa yang sama';
          syncRingkasan();
        });
      });
      document.querySelectorAll('input[name="tema_kartu"]').forEach(radio => {
        radio.addEventListener('change', syncRingkasan);
      });

      // ── Muat data siswa sesuai filter ───────────────────────────
      async function loadSiswa() {
        const tbody = document.getElementById('tbody-siswa');
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">
          <span class="spinner-border spinner-border-sm me-2"></span> Memuat data...</td></tr>`;

        const params = new URLSearchParams({
          rombel_id: document.getElementById('filter-rombel').value,
          search: document.getElementById('filter-search').value
        });

        try {
          const res = await fetch(`{{ route('kartu-pelajar.data') }}?${params.toString()}`, {
            headers: {
              Accept: 'application/json'
            }
          });
          if (!res.ok) {
            throw new Error('Server menjawab ' + res.status + ' — muat ulang halaman, lalu coba lagi.');
          }
          allSiswa = await res.json();
          renderTabelSiswa(allSiswa);
        } catch (err) {
          tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-danger">
            <i class="ri ri-error-warning-line me-1"></i> Gagal memuat data. ${err.message}</td></tr>`;
          document.getElementById('info-jumlah-siswa').textContent = 'gagal memuat';
        }
      }

      function renderTabelSiswa(data) {
        const tbody = document.getElementById('tbody-siswa');
        document.getElementById('info-jumlah-siswa').textContent = `${data.length} siswa tampil`;

        if (!data.length) {
          const rombelDipilih = document.getElementById('filter-rombel').value !== '';
          tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">
            <i class="ri ri-inbox-line me-1"></i> ${rombelDipilih
              ? 'Tidak ada siswa aktif di kelas ini pada semester yang sedang dibuka.'
              : 'Tidak ada siswa yang cocok dengan pencarian.'}</td></tr>`;
          syncPilihan();
          return;
        }

        tbody.innerHTML = data.map(s => `
          <tr class="baris-siswa ${selectedIds.has(String(s.id)) ? 'table-primary' : ''}"
              data-id="${s.id}" style="cursor:pointer">
            <td class="text-center">
              <input type="checkbox" class="form-check-input chk-siswa" value="${s.id}"
                ${selectedIds.has(String(s.id)) ? 'checked' : ''}>
            </td>
            <td>${renderSiswaCell(s)}</td>
            <td><small class="text-muted">${s.nisn ?? '-'}</small></td>
            <td class="text-center">
              ${s.jenis_kelamin === 'L'
                ? '<span class="badge bg-label-primary">L</span>'
                : '<span class="badge bg-label-danger">P</span>'}
            </td>
          </tr>`).join('');

        syncPilihan();
      }

      // ── Sinkronkan status pilih-semua, hitungan, dan bilah cetak ──
      function syncPilihan() {
        const boxes = [...document.querySelectorAll('.chk-siswa')];
        const tercentang = boxes.filter(b => b.checked).length;
        const checkAll = document.getElementById('check-all');
        checkAll.checked = boxes.length > 0 && tercentang === boxes.length;
        checkAll.indeterminate = tercentang > 0 && tercentang < boxes.length;
        document.getElementById('info-jumlah-dipilih').textContent = selectedIds.size;
        syncRingkasan();
      }

      function syncRingkasan() {
        const n = selectedIds.size;
        const temaLabel = document.querySelector('input[name="tema_kartu"]:checked')
          ?.closest('div')?.querySelector('label small')?.textContent?.trim() ?? '';
        const mode = document.querySelector('input[name="mode_sisi"]:checked')?.value === '2' ?
          '2 sisi (depan-belakang)' : '1 sisi';
        document.getElementById('ringkasan-cetak').textContent = n === 0 ?
          'Belum ada siswa dipilih.' :
          `${n} siswa dipilih • tema ${temaLabel} • mode ${mode}.`;
        document.getElementById('btn-cetak').disabled = n === 0;
      }

      function setBarisTerpilih(tr, id, pilih) {
        if (pilih) selectedIds.add(id);
        else selectedIds.delete(id);
        const cb = tr.querySelector('.chk-siswa');
        if (cb) cb.checked = pilih;
        tr.classList.toggle('table-primary', pilih);
      }

      // Klik di mana pun pada baris = pilih / batalkan (klik langsung
      // pada kotak centang ditangani peramban, jangan digandakan).
      document.getElementById('tbody-siswa').addEventListener('click', function(e) {
        const tr = e.target.closest('tr.baris-siswa');
        if (!tr) return;
        if (e.target.classList.contains('chk-siswa')) {
          setBarisTerpilih(tr, tr.dataset.id, e.target.checked);
        } else {
          const cb = tr.querySelector('.chk-siswa');
          setBarisTerpilih(tr, tr.dataset.id, !(cb && cb.checked));
        }
        syncPilihan();
      });

      document.getElementById('check-all').addEventListener('change', function() {
        document.querySelectorAll('tr.baris-siswa').forEach(tr => {
          setBarisTerpilih(tr, tr.dataset.id, this.checked);
        });
        syncPilihan();
      });

      document.getElementById('btn-bersihkan').addEventListener('click', function() {
        selectedIds.clear();
        document.querySelectorAll('tr.baris-siswa').forEach(tr => setBarisTerpilih(tr, tr.dataset.id, false));
        syncPilihan();
      });

      // ── Filter ──────────────────────────────────────────────────
      document.getElementById('filter-rombel').addEventListener('change', loadSiswa);
      document.getElementById('filter-search').addEventListener('input', function() {
        clearTimeout(window._searchTimer);
        window._searchTimer = setTimeout(loadSiswa, 400);
      });

      // ── Cetak ───────────────────────────────────────────────────
      document.getElementById('btn-cetak').addEventListener('click', function() {
        if (selectedIds.size === 0) {
          Swal.fire({
            icon: 'warning',
            title: 'Belum Ada Siswa Dipilih',
            text: 'Silakan centang minimal 1 siswa untuk dicetak.'
          });
          return;
        }

        document.getElementById('input-sisi').value =
          document.querySelector('input[name="mode_sisi"]:checked').value;
        const tema = document.querySelector('input[name="tema_kartu"]:checked').value;

        const wrapper = document.getElementById('wrapper-siswa-ids');
        wrapper.innerHTML = `<input type="hidden" name="tema" value="${tema}">`;
        selectedIds.forEach(id => {
          wrapper.innerHTML += `<input type="hidden" name="siswa_ids[]" value="${id}">`;
        });

        document.getElementById('form-cetak').submit();
      });

      loadSiswa();
    });
  </script>
@endsection
