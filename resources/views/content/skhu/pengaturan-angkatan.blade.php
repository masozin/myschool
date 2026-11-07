@extends('layouts/layoutMaster')

@section('title', 'Pengaturan Angkatan SKHU')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')
  <div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ url('/skhu') }}" class="btn btn-label-secondary btn-sm">
      <i class="icon-base ri ri-arrow-left-line me-1"></i>Kembali
    </a>
    <div>
      <h4 class="mb-0">Pengaturan Angkatan SKHU</h4>
      <small class="text-muted">
        {{ $sekolah->detailTerbaru->nama_sekolah ?? '' }} — Kelas {{ $tingkatAkhir }} ({{ $jenjang }}),
        Tahun Pelajaran {{ $semester->tahun_pelajaran }} {{ $semester->semester }}
      </small>
    </div>
  </div>

  @if ($jumlahFinal > 0)
    <div class="alert alert-warning">
      <i class="icon-base ri ri-lock-line me-1"></i>
      Ada <strong>{{ $jumlahFinal }} SKHU berstatus final</strong> pada angkatan ini.
      Pengaturan di halaman ini <strong>tidak mengubah</strong> SKHU final — batalkan finalisasinya dulu bila memang perlu diubah.
    </div>
  @endif

  <div class="row g-4">
    <div class="col-12 col-lg-5">
      {{-- Tanggal angkatan --}}
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">Tanggal Angkatan</h5>
          <small class="text-muted">Satu tanggal untuk seluruh angkatan — diterapkan ke semua siswa kelas akhir.</small>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label" for="tanggal_mulai_ujian">Tanggal Mulai Ujian</label>
            <input type="date" class="form-control" id="tanggal_mulai_ujian"
              value="{{ $prefill['tanggal_mulai_ujian']?->format('Y-m-d') }}">
          </div>
          <div class="mb-3">
            <label class="form-label" for="tanggal_selesai_ujian">Tanggal Selesai Ujian</label>
            <input type="date" class="form-control" id="tanggal_selesai_ujian"
              value="{{ $prefill['tanggal_selesai_ujian']?->format('Y-m-d') }}">
          </div>
          <div class="mb-3">
            <label class="form-label" for="tanggal_terbit">Tanggal Terbit SKHU</label>
            <input type="date" class="form-control" id="tanggal_terbit"
              value="{{ $prefill['tanggal_terbit']?->format('Y-m-d') }}">
          </div>
          <div>
            <label class="form-label" for="tempat_terbit">Tempat Terbit</label>
            <input type="text" class="form-control" id="tempat_terbit"
              placeholder="Kosongkan untuk memakai nama desa sekolah"
              value="{{ $prefill['tempat_terbit'] }}">
            <small class="text-muted">Tercetak sebelum tanggal terbit pada baris tanda tangan.</small>
          </div>
        </div>
      </div>

      {{-- Penomoran --}}
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">Generate Nomor</h5>
          <small class="text-muted">Kosongkan pola bila nomor tidak ingin dibuat dari sini.</small>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label" for="pola_nomor_surat">Pola Nomor SKHU (Nomor Surat)</label>
            <input type="text" class="form-control" id="pola_nomor_surat"
              placeholder="Contoh: 800/{no:3}/SDN-01/{tahun}">
          </div>
          <div class="mb-3">
            <label class="form-label" for="pola_nomor_ujian">Pola Nomor Ujian</label>
            <input type="text" class="form-control" id="pola_nomor_ujian"
              placeholder="Contoh: {no:3}">
          </div>
          <div class="alert alert-secondary small">
            <strong>Cara membaca pola:</strong> teks di luar kurung kurawal tercetak apa adanya.
            <code>{no}</code> = nomor urut biasa (1, 2, 3…), <code>{no:3}</code> atau <code>{no;3}</code> = nomor urut
            3 digit dengan nol di depan (001, 002, 003…), <code>{tahun}</code> = tahun tanggal terbit SKHU.
          </div>
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label" for="nomor_awal">Nomor Awal</label>
              <input type="number" class="form-control" id="nomor_awal" value="1" min="0">
            </div>
            <div class="col-6">
              <label class="form-label d-block">Urutan Siswa</label>
              <div class="form-check form-check-inline mt-2">
                <input class="form-check-input" type="radio" name="urutan" id="urutan-nama" value="nama" checked>
                <label class="form-check-label" for="urutan-nama">Nama A–Z</label>
              </div>
              <div class="form-check form-check-inline mt-2">
                <input class="form-check-input" type="radio" name="urutan" id="urutan-nis" value="nis">
                <label class="form-check-label" for="urutan-nis">NIS</label>
              </div>
            </div>
          </div>
          <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" id="timpa_nomor">
            <label class="form-check-label" for="timpa_nomor">
              Timpa nomor yang sudah ada (bila tidak dicentang, hanya siswa yang nomornya masih kosong yang diberi nomor)
            </label>
          </div>
        </div>
      </div>

      <button class="btn btn-primary w-100 mt-4" id="btn-simpan">
        <i class="icon-base ri ri-save-line me-1"></i>Terapkan ke Seluruh Angkatan
      </button>
    </div>

    <div class="col-12 col-lg-7">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">Pratinjau Hasil</h5>
          <small class="text-muted">Urutan dan nomor yang akan diterima setiap siswa. Berubah mengikuti pola &amp; urutan di samping.</small>
        </div>
        <div class="table-responsive" style="max-height: 640px; overflow-y: auto;">
          <table class="table table-bordered table-hover mb-0">
            <thead>
              <tr>
                <th style="width: 48px;">No</th>
                <th>Nama Siswa</th>
                <th>NIS</th>
                <th>Nomor SKHU</th>
                <th>Nomor Ujian</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="tbody-pratinjau"></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const siswaList = @json($siswaList);

      const el = id => document.getElementById(id);
      const polaSurat = el('pola_nomor_surat');
      const polaUjian = el('pola_nomor_ujian');
      const nomorAwal = el('nomor_awal');
      const timpa = el('timpa_nomor');
      const tglTerbit = el('tanggal_terbit');

      function formatNomor(pola, nomor) {
        if (!pola) return null;
        const tahun = tglTerbit.value ? new Date(tglTerbit.value).getFullYear() : new Date().getFullYear();
        return pola
          .replace(/\{no(?:[:;](\d+))?\}/g, (_, digit) =>
            digit ? String(nomor).padStart(parseInt(digit), '0') : String(nomor))
          .replaceAll('{tahun}', String(tahun));
      }

      function urutkan(list) {
        const mode = document.querySelector('input[name="urutan"]:checked').value;
        const arr = [...list];
        if (mode === 'nis') {
          arr.sort((a, b) => (a.nis ?? '').localeCompare(b.nis ?? '', undefined, {
              numeric: true
            }) ||
            a.nama.localeCompare(b.nama));
        } else {
          arr.sort((a, b) => a.nama.localeCompare(b.nama));
        }
        return arr;
      }

      function renderPratinjau() {
        const awal = parseInt(nomorAwal.value || '1', 10);
        const urut = urutkan(siswaList);
        el('tbody-pratinjau').innerHTML = urut.map((s, i) => {
          const seq = awal + i;
          let surat = s.nomor_surat ?? '-';
          let ujian = s.nomor_ujian ?? '-';
          if (s.status !== 'final') {
            if (polaSurat.value && (timpa.checked || !s.nomor_surat)) {
              surat = formatNomor(polaSurat.value, seq);
            }
            if (polaUjian.value && (timpa.checked || !s.nomor_ujian)) {
              ujian = formatNomor(polaUjian.value, seq);
            }
          }
          const status = s.status === 'final' ?
            '<span class="badge bg-label-success">Final</span>' :
            (s.status === 'draft' ?
              '<span class="badge bg-label-warning">Draft</span>' :
              '<span class="badge bg-label-secondary">Belum ada</span>');
          return `<tr>
            <td>${i + 1}</td>
            <td>${s.nama}<br><small class="text-muted">${s.rombel ?? ''}</small></td>
            <td>${s.nis ?? '-'}</td>
            <td>${surat}</td>
            <td>${ujian}</td>
            <td>${status}</td>
          </tr>`;
        }).join('');
      }

      [polaSurat, polaUjian, nomorAwal, tglTerbit].forEach(input =>
        input.addEventListener('input', renderPratinjau));
      timpa.addEventListener('change', renderPratinjau);
      document.querySelectorAll('input[name="urutan"]').forEach(r =>
        r.addEventListener('change', renderPratinjau));
      renderPratinjau();

      el('btn-simpan').addEventListener('click', async function() {
        const payload = {
          tanggal_mulai_ujian: el('tanggal_mulai_ujian').value || null,
          tanggal_selesai_ujian: el('tanggal_selesai_ujian').value || null,
          tanggal_terbit: tglTerbit.value || null,
          tempat_terbit: el('tempat_terbit').value.trim() || null,
          pola_nomor_surat: polaSurat.value.trim() || null,
          pola_nomor_ujian: polaUjian.value.trim() || null,
          nomor_awal: parseInt(nomorAwal.value || '1', 10),
          urutan: document.querySelector('input[name="urutan"]:checked').value,
          timpa_nomor: timpa.checked,
        };

        const konfirm = await Swal.fire({
          icon: 'question',
          title: 'Terapkan ke seluruh angkatan?',
          text: 'Tanggal dan nomor akan ditulis ke semua SKHU draft siswa kelas akhir semester ini. SKHU final tidak berubah.',
          showCancelButton: true,
          confirmButtonText: 'Ya, Terapkan',
          cancelButtonText: 'Batal',
          customClass: {
            confirmButton: 'btn btn-primary me-3',
            cancelButton: 'btn btn-outline-secondary'
          },
          buttonsStyling: false
        });
        if (!konfirm.isConfirmed) return;

        this.disabled = true;
        try {
          const res = await fetch('/skhu/pengaturan-angkatan', {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json',
              'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
          });
          const data = await res.json();
          if (!res.ok) throw new Error(data.message ?? 'Gagal menyimpan pengaturan.');

          await Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: data.message,
            timer: 3000,
            showConfirmButton: false
          });
          location.reload();
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: err.message
          });
        } finally {
          this.disabled = false;
        }
      });
    });
  </script>
@endsection
