@extends('layouts/layoutMaster')

@section('title', 'Tanggal & Penomoran SKL')

@section('content')

  <div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('skl.index') }}" class="btn btn-label-secondary btn-sm">
      <i class="icon-base ri ri-arrow-left-line me-1"></i>Kembali
    </a>
    <div>
      <h4 class="mb-0">Tanggal &amp; Penomoran SKL</h4>
      <small class="text-muted">
        Kelas akhir, Tahun Pelajaran {{ $semester->tahun_pelajaran }} {{ $semester->semester }}
      </small>
    </div>
  </div>

  @php $jumlahDisetujui = $baris->filter(fn($b) => $b['skl']?->status === 'disetujui')->count(); @endphp
  @if ($jumlahDisetujui > 0)
    <div class="alert alert-warning">
      <i class="icon-base ri ri-lock-line me-1"></i>
      Ada <strong>{{ $jumlahDisetujui }} SKL berstatus disetujui</strong> pada angkatan ini.
      Pengaturan di halaman ini <strong>tidak mengubah</strong> SKL yang sudah disetujui.
    </div>
  @endif

  <div class="row g-4">
    <div class="col-12 col-lg-5">
      <form id="form-pengaturan-nomor">
        {{-- Tanggal & Tempat Terbit --}}
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0">Tanggal &amp; Tempat Terbit</h5>
            <small class="text-muted">Satu tanggal untuk seluruh angkatan — diterapkan ke semua SKL yang belum disetujui.</small>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label" for="input-tempat">Tempat Terbit</label>
              <input type="text" name="tempat_terbit" id="input-tempat" class="form-control"
                placeholder="misal: Totorejo" value="{{ $konfigurasi?->tempat_terbit }}">
              <small class="text-muted">Tercetak sebelum tanggal pada baris tanda tangan SKL.</small>
            </div>
            <div>
              <label class="form-label" for="input-tanggal">Tanggal Terbit (massal)</label>
              <input type="date" name="tanggal_terbit" id="input-tanggal" class="form-control"
                value="{{ $konfigurasi?->tanggal_terbit?->format('Y-m-d') }}">
              <small class="text-muted">Diterapkan ke semua SKL yang belum disetujui, dan disimpan sebagai tanggal
                default. Kosongkan bila tidak ingin mengubah tanggal.</small>
            </div>
          </div>
        </div>

        {{-- Penomoran --}}
        <div class="card">
          <div class="card-header">
            <h5 class="card-title mb-0">Penomoran</h5>
            <small class="text-muted">Nomor massal berpola untuk seluruh angkatan.</small>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label" for="input-pola">Pola Nomor</label>
              <input type="text" name="pola_nomor" id="input-pola" class="form-control"
                value="SKL/{no:3}/{tahun}">
              <small class="text-muted">
                Penanda: <code>{no}</code> nomor urut, <code>{no:3}</code> atau <code>{no;3}</code> urut dengan nol di
                depan (3 digit), <code>{tahun}</code> tahun tanggal terbit. Teks lain di luar penanda tercetak
                apa adanya — awalan seperti "SKL" cukup ditulis langsung di pola. Contoh hasil: <code>SKL/007/{{ now()->year }}</code>
              </small>
            </div>
            <div class="mb-3">
              <label class="form-label" for="input-awal">Nomor Awal</label>
              <input type="number" name="nomor_awal" id="input-awal" class="form-control" value="1" min="0">
            </div>
            <div class="mb-3">
              <label class="form-label d-block">Urutan Penomoran</label>
              <div class="form-check form-check-inline mt-2">
                <input class="form-check-input" type="radio" name="urutan" id="urutan-nama" value="nama" checked>
                <label class="form-check-label" for="urutan-nama">Nama siswa (A–Z)</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="urutan" id="urutan-nis" value="nis">
                <label class="form-check-label" for="urutan-nis">NIS</label>
              </div>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" name="kelompok_rombel" id="input-kelompok" value="1">
                <label class="form-check-label" for="input-kelompok">
                  Kelompokkan per rombel — urutan berjalan rombel demi rombel (mis. semua 6A dulu, lalu 6B);
                  nomor tetap berlanjut, tidak mulai dari awal
                </label>
              </div>
            </div>

            <div class="form-check mt-3">
              <input class="form-check-input" type="checkbox" name="timpa" id="input-timpa" value="1">
              <label class="form-check-label" for="input-timpa">
                Timpa nomor yang sudah terisi (bila tidak dicentang, hanya SKL yang nomornya masih kosong yang diisi)
              </label>
            </div>

            <div class="mt-4">
              <button type="submit" class="btn btn-primary" id="btn-simpan">
                <i class="icon-base ri ri-save-line me-1"></i>Simpan Pengaturan
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <div class="col-12 col-lg-7">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">Pratinjau Hasil</h5>
          <small class="text-muted">Urutan dan nomor yang akan diterima setiap siswa. Berubah mengikuti pola &amp; urutan di samping.</small>
        </div>
        <div class="table-responsive" style="max-height: 640px; overflow-y: auto;">
          <table class="table table-hover mb-0" id="tabel-pratinjau">
            <thead>
              <tr>
                <th style="width: 60px">No</th>
                <th>Siswa</th>
                <th>Rombel</th>
                <th>NIS</th>
                <th>Status SKL</th>
                <th>Nomor Saat Ini</th>
                <th>Nomor Hasil</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <div class="card-footer text-muted small">
          Nomor mengikuti posisi siswa pada urutan yang dipilih — termasuk siswa yang belum memiliki SKL. Siswa yang
          belum punya SKL tidak diisi nomornya sekarang, tetapi posisinya dicadangkan: menjalankan halaman ini lagi
          setelah SKL-nya dibuat akan memberinya nomor pada posisi yang sama.
        </div>
      </div>
    </div>
  </div>

@endsection

@section('page-script')
  <script>

    const DATA = [
      @foreach ($baris as $b)
        {
          nama: @json($b['siswa']->nama_siswa),
          nis: @json($b['nis']),
          rombel: @json($b['rombel']),
          nomorSaatIni: @json($b['skl']?->nomor_skl),
          status: @json($b['skl']?->status),
          punyaSkl: {{ $b['skl'] ? 'true' : 'false' }},
        },
      @endforeach
    ];

    const tbody = document.querySelector('#tabel-pratinjau tbody');
    const inputPola = document.getElementById('input-pola');
    const inputAwal = document.getElementById('input-awal');
    const inputTanggal = document.getElementById('input-tanggal');
    const inputTimpa = document.getElementById('input-timpa');
    const inputKelompok = document.getElementById('input-kelompok');

    function formatNomor(pola, urut, tahun) {
      return pola
        .replace(/\{no(?::(\d+)|;(\d+))?\}/g, (m, a, b) => {
          const lebar = parseInt(a ?? b ?? '0', 10);
          return String(urut).padStart(lebar, '0');
        })
        .replaceAll('{tahun}', String(tahun));
    }

    function renderPratinjau() {
      const urutan = document.querySelector('input[name="urutan"]:checked').value;
      const kelompok = inputKelompok.checked;
      const awal = parseInt(inputAwal.value || '0', 10);
      const tahun = inputTanggal.value ?
        new Date(inputTanggal.value + 'T00:00:00').getFullYear() :
        new Date().getFullYear();

      const terurut = [...DATA].sort((a, b) => {
        if (kelompok) {
          const perRombel = String(a.rombel ?? '').localeCompare(String(b.rombel ?? ''));
          if (perRombel !== 0) return perRombel;
        }
        if (urutan === 'nis') {
          return String(a.nis ?? '').localeCompare(String(b.nis ?? ''), undefined, {
            numeric: true
          });
        }
        return String(a.nama).toLowerCase().localeCompare(String(b.nama).toLowerCase());
      });

      tbody.innerHTML = terurut.map((s, i) => {
        const hasil = formatNomor(inputPola.value, awal + i, tahun);
        let nomorHasil, kelas = '';
        if (!s.punyaSkl) {
          nomorHasil = '<span class="text-muted">(belum ada SKL)</span>';
        } else if (s.status === 'disetujui') {
          nomorHasil = '<span class="text-muted">tetap (sudah disetujui)</span>';
        } else if (s.nomorSaatIni && !inputTimpa.checked) {
          nomorHasil = '<span class="text-muted">tetap (sudah terisi)</span>';
        } else {
          nomorHasil = '<strong>' + hasil + '</strong>';
          kelas = ' class="table-primary"';
        }
        const statusBadge = !s.punyaSkl ?
          '<span class="badge bg-label-secondary">Belum dibuat</span>' :
          '<span class="badge bg-label-info">' + s.status + '</span>';
        return '<tr' + kelas + '><td>' + (i + 1) + '</td><td>' + s.nama + '</td><td>' +
          (s.rombel ?? '-') + '</td><td>' + (s.nis ?? '-') + '</td><td>' + statusBadge +
          '</td><td>' + (s.nomorSaatIni ?? '<span class="text-muted">—</span>') + '</td><td>' +
          nomorHasil + '</td></tr>';
      }).join('');
    }

    [inputPola, inputAwal, inputTanggal, inputTimpa, inputKelompok].forEach(el =>
      el.addEventListener('input', renderPratinjau));
    document.querySelectorAll('input[name="urutan"]').forEach(el =>
      el.addEventListener('change', renderPratinjau));
    renderPratinjau();

    document.getElementById('form-pengaturan-nomor').addEventListener('submit', async function(e) {
      e.preventDefault();
      const btn = document.getElementById('btn-simpan');
      btn.disabled = true;

      const urutan = document.querySelector('input[name="urutan"]:checked').value;
      const res = await fetch('{{ route('skl.pengaturan-nomor.simpan') }}', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
          pola_nomor: inputPola.value,
          nomor_awal: parseInt(inputAwal.value || '0', 10),
          urutan: urutan,
          kelompok_rombel: inputKelompok.checked,
          timpa: inputTimpa.checked,
          tanggal_terbit: inputTanggal.value || null,
          tempat_terbit: document.getElementById('input-tempat').value.trim(),
        }),
      });
      const data = await res.json();
      btn.disabled = false;

      Swal.fire({
        icon: res.ok ? 'success' : 'error',
        title: res.ok ? 'Berhasil' : 'Gagal',
        text: data.message ?? 'Terjadi kesalahan.',
      }).then(() => {
        if (res.ok) location.reload();
      });
    });
  </script>
@endsection
