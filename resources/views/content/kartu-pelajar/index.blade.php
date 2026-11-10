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
  {{-- HEADER INFO                                                 --}}
  {{-- =========================================================== --}}
  <div class="row mb-4 g-4">
    <div class="col-12 col-md-8">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-4 py-4">
          <div class="avatar avatar-xl flex-shrink-0">
            <span class="avatar-initial rounded-circle bg-label-primary">
              <i class="icon-base ri ri-bank-card-2-line ri-28px"></i>
            </span>
          </div>
          <div>
            <h4 class="mb-1">Cetak Kartu Pelajar</h4>
            <div class="d-flex gap-4 flex-wrap">
              <span class="text-muted"><i class="icon-base ri ri-ruler-line me-1"></i>Ukuran 8,6 x 5,4 cm</span>
              <span class="text-muted"><i class="icon-base ri ri-file-paper-2-line me-1"></i>Kertas A4</span>
              <span class="text-muted"><i class="icon-base ri ri-user-line me-1"></i><span
                  id="info-jumlah-dipilih">0</span> dipilih</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card h-100">
        <div class="card-body py-4">
          <label class="form-label mb-2 d-block">Mode Cetak</label>
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
          <small class="text-muted d-block mt-2" id="info-mode-sisi">
            1 baris = 2 siswa berdampingan
          </small>
        </div>
      </div>
    </div>
  </div>
  <div class="card mb-4">
    <div class="card-body py-3">
      <label class="form-label mb-2 d-block">Pilih Tema Kartu</label>
      <div class="row g-3">
        @foreach ($temaList as $key => $label)
          <div class="col-6 col-md-4">
            <input type="radio" class="btn-check" name="tema_kartu" id="tema-{{ $key }}"
              value="{{ $key }}" {{ $loop->first ? 'checked' : '' }}>
            <label class="btn btn-outline-primary w-100 text-start py-2" for="tema-{{ $key }}">
              <div class="d-flex align-items-center gap-2">
                <span class="rounded-circle flex-shrink-0"
                  style="width:16px;height:16px;background:
                @if ($key === 'navy-gold') #063b82
                @elseif($key === 'emerald-modern') #0a6958
                @else #4a0c19 @endif
              "></span>
                <small>{{ $label }}</small>
              </div>
            </label>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- TOOLBAR FILTER                                              --}}
  {{-- =========================================================== --}}
  <div class="card mb-4">
    <div class="card-body py-3">
      <div class="row g-3 align-items-end">
        <div class="col-12 col-md-4">
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
        <div class="col-12 col-md-5">
          <div class="form-floating form-floating-outline">
            <input type="text" id="filter-search" class="form-control" placeholder="Cari nama / NISN">
            <label for="filter-search">Cari Nama / NISN Siswa</label>
          </div>
        </div>
        <div class="col-12 col-md-3">
          <button type="button" id="btn-cetak" class="btn btn-primary w-100">
            <i class="ri ri-printer-line me-1"></i> Cetak PDF
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- =========================================================== --}}
  {{-- TABEL PILIH SISWA                                           --}}
  {{-- =========================================================== --}}
  <div class="card">
    <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div class="form-check">
        <input type="checkbox" class="form-check-input" id="check-all">
        <label class="form-check-label" for="check-all">Pilih Semua (sesuai filter saat ini)</label>
      </div>
      <small class="text-muted" id="info-jumlah-siswa">0 siswa</small>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th width="40" class="text-center"></th>
            <th>Siswa</th>
            <th>NIS / NISN</th>
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

      // ── State ─────────────────────────────────────────────────────────────
      let allSiswa = [];
      let selectedIds = new Set();

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

      function renderSiswaCell(nama) {
        const initials = getInitials(nama);
        const color = avatarColor(nama);
        return `
          <div class="d-flex align-items-center gap-2">
            <span class="d-flex align-items-center justify-content-center rounded-circle
                   text-white fw-semibold flex-shrink-0"
               style="width:32px;height:32px;background:${color};font-size:12px">
              ${initials}
            </span>
            <span>${nama}</span>
          </div>`;
      }

      // ── Mode sisi: update keterangan ─────────────────────────────────────
      document.querySelectorAll('input[name="mode_sisi"]').forEach(radio => {
        radio.addEventListener('change', function() {
          const info = document.getElementById('info-mode-sisi');
          info.textContent = this.value === '1' ?
            '1 baris = 2 siswa berdampingan' :
            '1 baris = depan-belakang untuk 1 siswa yang sama';
        });
      });

      // ── Load data siswa (AJAX plain, sesuai filter) ──────────────────────
      async function loadSiswa() {
        const tbody = document.getElementById('tbody-siswa');
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">
          <span class="spinner-border spinner-border-sm me-2"></span> Memuat data...</td></tr>`;

        const rombelId = document.getElementById('filter-rombel').value;
        const search = document.getElementById('filter-search').value;
        const params = new URLSearchParams({
          rombel_id: rombelId,
          search: search
        });

        try {
          const res = await fetch(`{{ route('kartu-pelajar.data') }}?${params.toString()}`, {
            headers: {
              Accept: 'application/json'
            }
          });
          const data = await res.json();
          allSiswa = data;
          renderTabelSiswa(allSiswa);
        } catch (err) {
          tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-danger">
            <i class="ri ri-error-warning-line me-1"></i> Gagal memuat data</td></tr>`;
        }
      }

      function renderTabelSiswa(data) {
        const tbody = document.getElementById('tbody-siswa');
        document.getElementById('info-jumlah-siswa').textContent = `${data.length} siswa`;

        if (!data.length) {
          tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">
            <i class="ri ri-inbox-line me-1"></i> Tidak ada data.</td></tr>`;
          document.getElementById('check-all').checked = false;
          return;
        }

        tbody.innerHTML = data.map(s => `
          <tr>
            <td class="text-center">
              <input type="checkbox" class="form-check-input chk-siswa" value="${s.id}"
                ${selectedIds.has(String(s.id)) ? 'checked' : ''}>
            </td>
            <td>${renderSiswaCell(s.nama_siswa)}</td>
            <td><small class="text-muted">${s.nisn ?? '-'}</small></td>
            <td class="text-center">
              ${s.jenis_kelamin === 'L'
                ? '<span class="badge bg-label-primary">L</span>'
                : '<span class="badge bg-label-danger">P</span>'}
            </td>
          </tr>`).join('');

        syncCheckAllState();
      }

      function syncCheckAllState() {
        const boxes = document.querySelectorAll('.chk-siswa');
        const checkedCount = [...boxes].filter(b => b.checked).length;
        document.getElementById('check-all').checked = boxes.length > 0 && checkedCount === boxes.length;
        document.getElementById('info-jumlah-dipilih').textContent = selectedIds.size;
      }

      // ── Checkbox individu: simpan state global (agar tetap terpilih walau filter berubah) ──
      document.getElementById('tbody-siswa').addEventListener('change', function(e) {
        if (!e.target.classList.contains('chk-siswa')) return;
        const id = e.target.value;
        if (e.target.checked) selectedIds.add(id);
        else selectedIds.delete(id);
        syncCheckAllState();
      });

      document.getElementById('check-all').addEventListener('change', function() {
        document.querySelectorAll('.chk-siswa').forEach(cb => {
          cb.checked = this.checked;
          if (this.checked) selectedIds.add(cb.value);
          else selectedIds.delete(cb.value);
        });
        document.getElementById('info-jumlah-dipilih').textContent = selectedIds.size;
      });

      // ── Filter events ─────────────────────────────────────────────────────
      document.getElementById('filter-rombel').addEventListener('change', loadSiswa);
      document.getElementById('filter-search').addEventListener('input', function() {
        clearTimeout(window._searchTimer);
        window._searchTimer = setTimeout(loadSiswa, 400);
      });

      // ── Tombol Cetak ──────────────────────────────────────────────────────
      document.getElementById('btn-cetak').addEventListener('click', function() {
        if (selectedIds.size === 0) {
          Swal.fire({
            icon: 'warning',
            title: 'Belum Ada Siswa Dipilih',
            text: 'Silakan centang minimal 1 siswa untuk dicetak.'
          });
          return;
        }

        const sisi = document.querySelector('input[name="mode_sisi"]:checked').value;
        const tema = document.querySelector('input[name="tema_kartu"]:checked').value; // ⬅️ tambahan
        document.getElementById('input-sisi').value = sisi;

        const wrapper = document.getElementById('wrapper-siswa-ids');
        wrapper.innerHTML = '';
        wrapper.innerHTML += `<input type="hidden" name="tema" value="${tema}">`; // ⬅️ tambahan
        selectedIds.forEach(id => {
          wrapper.innerHTML += `<input type="hidden" name="siswa_ids[]" value="${id}">`;
        });

        document.getElementById('form-cetak').submit();
      });

      loadSiswa();
    });
  </script>
@endsection
