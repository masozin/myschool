@extends('layouts/layoutMaster')

@section('title', 'Cetak Kartu Peserta Ujian')

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
        <form method="GET" action="{{ url('/kartu-ujian') }}">
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

  <div class="card mb-4">
    <div class="card-body py-4 d-flex align-items-center gap-4">
      <div class="avatar avatar-xl flex-shrink-0">
        <span class="avatar-initial rounded-circle bg-label-primary">
          <i class="icon-base ri ri-file-list-line ri-28px"></i>
        </span>
      </div>
      <div>
        <h4 class="mb-1">Cetak Kartu Peserta Ujian</h4>
        <div class="text-muted small">
          <i class="icon-base ri ri-ruler-line me-1"></i>Ukuran kartu 9,3 × 6 cm
          <span class="mx-1">•</span>
          <i class="icon-base ri ri-file-paper-2-line me-1"></i>Dicetak di kertas A4, 8 kartu per halaman
        </div>
      </div>
    </div>
  </div>

  <form id="form-cetak" action="{{ route('kartu-ujian.cetak') }}" method="POST" target="_blank">
    @csrf

    <div class="row g-4">
      {{-- ============================================= --}}
      {{-- KIRI: PENGATURAN KARTU                        --}}
      {{-- ============================================= --}}
      <div class="col-12 col-lg-5">
        <div class="card">
          <div class="card-header pb-3">
            <h5 class="mb-0">Pengaturan Kartu</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-12">
                <div class="form-floating form-floating-outline">
                  <input type="text" name="judul" id="input-judul" class="form-control" required maxlength="45"
                    placeholder="Penilaian Akhir Semester" value="{{ old('judul') }}">
                  <label for="input-judul">Judul Ujian (maks. 45 karakter)</label>
                </div>
              </div>
              <div class="col-6">
                <div class="form-floating form-floating-outline">
                  <input type="text" name="tahun_pelajaran" class="form-control" required maxlength="20"
                    value="{{ old('tahun_pelajaran', $bawaan['tahun_pelajaran']) }}">
                  <label for="tahun_pelajaran">Tahun Pelajaran</label>
                </div>
              </div>
              <div class="col-6">
                <div class="form-floating form-floating-outline">
                  <select name="penomoran" class="form-select">
                    <option value="per_tingkat" {{ old('penomoran') === 'per_tingkat' ? 'selected' : '' }}>Per tingkat
                    </option>
                    <option value="semua" {{ old('penomoran') === 'semua' ? 'selected' : '' }}>Semua siswa</option>
                  </select>
                  <label for="penomoran">Cara Penomoran</label>
                </div>
              </div>
              <div class="col-6">
                <div class="form-floating form-floating-outline">
                  <input type="text" name="pola_nomor" class="form-control" required maxlength="60"
                    value="{{ old('pola_nomor', '{t}-{no:3}') }}">
                  <label for="pola_nomor">Pola Nomor Peserta</label>
                </div>
                <small class="text-muted">Token: {t} tingkat, {no:3:1} nomor urut 3 digit mulai dari 1.</small>
              </div>
              <div class="col-6">
                <div class="form-floating form-floating-outline">
                  <select name="urutan_kelas" class="form-select">
                    <option value="nama" {{ old('urutan_kelas', 'nama') === 'nama' ? 'selected' : '' }}>Nama siswa
                    </option>
                    <option value="nis" {{ old('urutan_kelas') === 'nis' ? 'selected' : '' }}>NIS</option>
                  </select>
                  <label for="urutan_kelas">Urutan dalam Kelas</label>
                </div>
              </div>
              <div class="col-6">
                <div class="form-floating form-floating-outline">
                  <select name="logo_kiri" class="form-select">
                    <option value="pemda" {{ old('logo_kiri', 'pemda') === 'pemda' ? 'selected' : '' }}>Logo Pemda
                    </option>
                    <option value="sekolah" {{ old('logo_kiri') === 'sekolah' ? 'selected' : '' }}>Logo Sekolah
                    </option>
                    <option value="twh" {{ old('logo_kiri') === 'twh' ? 'selected' : '' }}>Logo Tut Wuri Handayani
                    </option>
                    <option value="kosong" {{ old('logo_kiri') === 'kosong' ? 'selected' : '' }}>— Kosong —</option>
                  </select>
                  <label for="logo_kiri">Logo Kiri</label>
                </div>
              </div>
              <div class="col-6">
                <div class="form-floating form-floating-outline">
                  <select name="logo_kanan" class="form-select">
                    <option value="pemda" {{ old('logo_kanan') === 'pemda' ? 'selected' : '' }}>Logo Pemda</option>
                    <option value="sekolah" {{ old('logo_kanan', 'sekolah') === 'sekolah' ? 'selected' : '' }}>Logo
                      Sekolah</option>
                    <option value="twh" {{ old('logo_kanan') === 'twh' ? 'selected' : '' }}>Logo Tut Wuri Handayani
                    </option>
                    <option value="kosong" {{ old('logo_kanan') === 'kosong' ? 'selected' : '' }}>— Kosong —</option>
                  </select>
                  <label for="logo_kanan">Logo Kanan</label>
                </div>
              </div>
              <div class="col-12">
                <label class="form-label d-block">Urutan Field pada Kartu</label>
                <ul class="list-group" id="daftar-urutan">
                  @foreach ([['nomor', 'Nomor'], ['rombel', 'Rombel'], ['ttl', 'TTL'], ['nis_nisn', 'NIS/NISN']] as [$fk, $fl])
                    <li class="list-group-item d-flex align-items-center gap-2 py-2" data-field="{{ $fk }}">
                      <span class="badge bg-label-secondary nomor-urut">1</span>
                      <span class="flex-grow-1">{{ $fl }}</span>
                      <button type="button" class="btn btn-sm btn-outline-secondary btn-naik" title="Naikkan">
                        <i class="ri ri-arrow-up-line"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-outline-secondary btn-turun" title="Turunkan">
                        <i class="ri ri-arrow-down-line"></i>
                      </button>
                    </li>
                  @endforeach
                </ul>
                <input type="hidden" name="urutan_nomor" id="urutan_nomor" value="1">
                <input type="hidden" name="urutan_rombel" id="urutan_rombel" value="2">
                <input type="hidden" name="urutan_ttl" id="urutan_ttl" value="3">
                <input type="hidden" name="urutan_nis_nisn" id="urutan_nis_nisn" value="4">
              </div>
              <div class="col-12">
                <label class="form-label d-block">Tampilan Kartu</label>
                <div class="d-flex gap-4 flex-wrap">
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="pakai_foto" id="pakai_foto" value="1"
                      checked>
                    <label class="form-check-label" for="pakai_foto">Foto siswa</label>
                  </div>
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="pakai_ttd" id="pakai_ttd" value="1" checked>
                    <label class="form-check-label" for="pakai_ttd">Tanda tangan</label>
                  </div>
                  <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="pakai_stempel" id="pakai_stempel"
                      value="1" checked>
                    <label class="form-check-label" for="pakai_stempel">Stempel</label>
                  </div>
                </div>
              </div>
              <div class="col-6">
                <div class="form-floating form-floating-outline">
                  <input type="text" name="titimangsa_tempat" class="form-control" required maxlength="100"
                    value="{{ old('titimangsa_tempat', $bawaan['titimangsa_tempat']) }}">
                  <label for="titimangsa_tempat">Titimangsa — Tempat</label>
                </div>
              </div>
              <div class="col-6">
                <div class="form-floating form-floating-outline">
                  <input type="date" name="titimangsa_tanggal" class="form-control" required
                    value="{{ old('titimangsa_tanggal', $bawaan['titimangsa_tanggal']) }}">
                  <label for="titimangsa_tanggal">Titimangsa — Tanggal</label>
                </div>
              </div>
              <div class="col-12">
                <div class="form-floating form-floating-outline">
                  <input type="text" name="penanda_tangan" class="form-control" required maxlength="100"
                    value="{{ old('penanda_tangan', $bawaan['penanda_tangan']) }}">
                  <label for="penanda_tangan">Penanda Tangan (Kepala Sekolah)</label>
                </div>
              </div>
              <div class="col-6">
                <div class="form-floating form-floating-outline">
                  <input type="text" name="nip_penanda" class="form-control" maxlength="30"
                    value="{{ old('nip_penanda', $bawaan['nip_penanda']) }}">
                  <label for="nip_penanda">NIP Penanda Tangan</label>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ============================================= --}}
      {{-- KANAN: KELAS, PENCARIAN & DAFTAR SISWA        --}}
      {{-- ============================================= --}}
      <div class="col-12 col-lg-7">
        <div class="card mb-4">
          <div class="card-header pb-3">
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

        <div class="card">
          <div class="card-header pb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Pilih Siswa</h5>
            <small class="text-muted" id="info-jumlah-siswa">0 siswa tampil</small>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th colspan="4">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-pilih-semua">
                      <i class="ri ri-check-double-line me-1"></i>Pilih Semua
                    </button>
                  </th>
                </tr>
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
      </div>
    </div>

    {{-- ========================================================= --}}
    {{-- BILAH CETAK                                               --}}
    {{-- ========================================================= --}}
    <div class="card border-primary shadow mt-4" style="position:sticky;bottom:1rem;z-index:10">
      <div class="card-body py-3 d-flex align-items-center gap-3 flex-wrap">
        <h5 class="mb-0">Cetak</h5>
        <span class="text-muted" id="ringkasan-cetak">Belum ada siswa dipilih.</span>
        <button type="submit" id="btn-cetak" class="btn btn-primary ms-auto" disabled>
          <i class="ri ri-printer-line me-1"></i> Cetak PDF (<span id="info-jumlah-dipilih">0</span> siswa)
        </button>
      </div>
    </div>

    <div id="wrapper-siswa-ids"></div>
  </form>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {

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

      window.__avatarCadangan = function(color, initials) {
        return `<span class="d-flex align-items-center justify-content-center rounded-circle
                 text-white fw-semibold flex-shrink-0"
             style="width:36px;height:36px;background:${color};font-size:12px">${initials}</span>`;
      };

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

      async function loadSiswa() {
        const tbody = document.getElementById('tbody-siswa');
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">
          <span class="spinner-border spinner-border-sm me-2"></span> Memuat data...</td></tr>`;

        const params = new URLSearchParams({
          rombel_id: document.getElementById('filter-rombel').value,
          search: document.getElementById('filter-search').value
        });

        try {
          const res = await fetch(`{{ route('kartu-ujian.data') }}?${params.toString()}`, {
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

      function syncPilihan() {
        document.getElementById('info-jumlah-dipilih').textContent = selectedIds.size;
        syncRingkasan();
      }

      function syncRingkasan() {
        const n = selectedIds.size;
        const judul = document.getElementById('input-judul').value.trim();
        document.getElementById('ringkasan-cetak').textContent = n === 0 ?
          'Belum ada siswa dipilih.' :
          `${n} siswa dipilih${judul ? ' • ' + judul : ''}.`;
        document.getElementById('btn-cetak').disabled = n === 0;
      }

      function setBarisTerpilih(tr, id, pilih) {
        if (pilih) selectedIds.add(id);
        else selectedIds.delete(id);
        const cb = tr.querySelector('.chk-siswa');
        if (cb) cb.checked = pilih;
        tr.classList.toggle('table-primary', pilih);
      }

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

      // Tombol Pilih Semua di kepala tabel: memilih semua baris
      // yang tampil; bila semuanya sudah terpilih, klik berikutnya
      // membatalkan pilihan pada baris yang tampil itu.
      document.getElementById('btn-pilih-semua').addEventListener('click', function() {
        const rows = [...document.querySelectorAll('tr.baris-siswa')];
        const semuaTerpilih = rows.length > 0 &&
          rows.every(tr => tr.querySelector('.chk-siswa')?.checked);
        rows.forEach(tr => setBarisTerpilih(tr, tr.dataset.id, !semuaTerpilih));
        syncPilihan();
      });

      document.getElementById('filter-rombel').addEventListener('change', loadSiswa);
      document.getElementById('filter-search').addEventListener('input', function() {
        clearTimeout(window._searchTimer);
        window._searchTimer = setTimeout(loadSiswa, 400);
      });
      document.getElementById('input-judul').addEventListener('input', syncRingkasan);

      // ── Urutan field: tombol panah naik / turun ─────────────────
      const daftarUrutan = document.getElementById('daftar-urutan');

      function segarkanUrutan() {
        const items = [...daftarUrutan.querySelectorAll('li')];
        items.forEach((li, i) => {
          li.querySelector('.nomor-urut').textContent = i + 1;
          li.querySelector('.btn-naik').disabled = i === 0;
          li.querySelector('.btn-turun').disabled = i === items.length - 1;
          document.getElementById('urutan_' + li.dataset.field).value = i + 1;
        });
      }

      daftarUrutan.addEventListener('click', function(e) {
        const btnNaik = e.target.closest('.btn-naik');
        const btnTurun = e.target.closest('.btn-turun');
        if (btnNaik) {
          const li = btnNaik.closest('li');
          if (li.previousElementSibling) {
            daftarUrutan.insertBefore(li, li.previousElementSibling);
            segarkanUrutan();
          }
        } else if (btnTurun) {
          const li = btnTurun.closest('li');
          if (li.nextElementSibling) {
            daftarUrutan.insertBefore(li.nextElementSibling, li);
            segarkanUrutan();
          }
        }
      });

      segarkanUrutan();

      // ── Kirim: sisipkan id siswa terpilih ke formulir ───────────
      document.getElementById('form-cetak').addEventListener('submit', function(e) {
        e.preventDefault();
        if (selectedIds.size === 0) {
          Swal.fire({
            icon: 'warning',
            title: 'Belum Ada Siswa Dipilih',
            text: 'Silakan centang minimal 1 siswa untuk dicetak.'
          });
          return;
        }
        segarkanUrutan();
        const wrapper = document.getElementById('wrapper-siswa-ids');
        wrapper.innerHTML = '';
        selectedIds.forEach(id => {
          wrapper.innerHTML += `<input type="hidden" name="siswa_ids[]" value="${id}">`;
        });
        e.target.submit();
      });

      loadSiswa();
    });
  </script>
@endsection
