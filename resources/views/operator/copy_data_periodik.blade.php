{{-- resources/views/operator/copy_data_periodik.blade.php --}}
@extends('layouts/contentNavbarLayout')

@section('title', 'Salin Data Periodik')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  <h4 class="fw-bold mb-1">Salin Data Periodik</h4>

  @if ($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
  @endif

  @if (session('warning'))
  <div class="alert alert-warning">{{ session('warning') }}</div>
  @endif
  @if (session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  @if (empty($sumber))
  <div class="alert alert-info mt-3">
    Belum ada data semester sebelumnya yang bisa disalin.
    Silakan lengkapi data secara manual melalui menu Profil Sekolah dan Kelola Rombel.
  </div>
  <a href="{{ route('sekolah.index') }}" class="btn btn-outline-primary me-2">Profil Sekolah</a>
  <a href="{{ route('rombel.index') }}" class="btn btn-primary">Kelola Rombel</a>
  @else
  <p class="text-muted mb-4">
    Data periodik untuk semester aktif belum lengkap. Pilih semester sumber dan data
    yang ingin disalin, lalu klik <strong>Salin Data</strong>.
  </p>

  <form action="{{ route('periodic.copy') }}" method="POST">
    @csrf

    {{-- Pilihan semester sumber --}}
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0">Semester Sumber</h6>
      </div>
      <div class="card-body">
        <select name="semester_asal" id="semester_asal" class="form-select" style="max-width: 420px">
          @foreach ($sumber as $s)
          <option value="{{ $s['id'] }}" {{ $s['id'] === $defaultAsalId ? 'selected' : '' }}>
            {{ $s['label'] }}
          </option>
          @endforeach
        </select>
        <small class="text-muted">Bawaan terpilih: semester sebelumnya. Ganti bila ingin menyalin
          dari semester lain.</small>
      </div>
    </div>

    {{-- Pratinjau detail sekolah dari semester sumber --}}
    <div class="card mb-4" id="card-detail">
      <div class="card-header">
        <h6 class="mb-0">Detail Sekolah di Semester Sumber</h6>
      </div>
      <div class="card-body">
        <div id="pv-detail-kosong" class="text-muted d-none">
          Semester sumber ini tidak memiliki detail sekolah.
        </div>
        <div class="row g-3" id="pv-detail-isi">
          <div class="col-md-6">
            <label class="form-label text-muted">Nama Sekolah</label>
            <input type="text" class="form-control" id="pv-nama" readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label text-muted">Kepala Sekolah</label>
            <input type="text" class="form-control" id="pv-kepala" readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label text-muted">NIP Kepala Sekolah</label>
            <input type="text" class="form-control" id="pv-nip" readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label text-muted">No. Telp</label>
            <input type="text" class="form-control" id="pv-telp" readonly>
          </div>
          <div class="col-12">
            <label class="form-label text-muted">Alamat Sekolah</label>
            <textarea class="form-control" rows="2" id="pv-alamat" readonly></textarea>
          </div>
          <div class="col-12">
            <label class="form-label text-muted">Wilayah</label>
            <input type="text" class="form-control" id="pv-wilayah" readonly>
          </div>
        </div>
      </div>
    </div>

    {{-- Pratinjau rombel dari semester sumber --}}
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0">Rombel di Semester Sumber (<span id="pv-rombel-jumlah">0</span>)</h6>
      </div>
      <div class="card-body">
        <div id="pv-rombel-kosong" class="text-muted d-none">
          Semester sumber ini tidak memiliki rombel.
        </div>
        <ul class="mb-0" id="pv-rombel"></ul>
      </div>
    </div>

    {{-- Pilihan data yang disalin --}}
    <div class="card mb-4">
      <div class="card-header">
        <h6 class="mb-0">Data yang Disalin</h6>
      </div>
      <div class="card-body">
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" name="salin[]" value="detail"
            id="salin-detail" checked>
          <label class="form-check-label" for="salin-detail">
            Detail Sekolah
            <small class="text-muted d-block" id="note-detail"></small>
          </label>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" name="salin[]" value="rombel"
            id="salin-rombel" checked>
          <label class="form-check-label" for="salin-rombel">
            Rombel
            <small class="text-muted d-block" id="note-rombel"></small>
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="salin[]" value="anggota"
            id="salin-anggota" checked>
          <label class="form-check-label" for="salin-anggota">
            Anggota Rombel
            <small class="text-muted d-block" id="note-anggota"></small>
          </label>
        </div>
      </div>
    </div>

    <button type="submit" class="btn btn-primary">
      <i class="ri-file-copy-line me-1"></i> Salin Data ke Semester Ini
    </button>
  </form>
  @endif
</div>
@endsection

@if (!empty($sumber))
@section('page-script')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const sumber = @json($sumber);
    const statusTarget = @json($statusTarget);
    const byId = id => document.getElementById(id);

    function aturCheckbox(cb, note, tersedia, sudahAda, teksTidakTersedia) {
      if (sudahAda) {
        cb.checked = false;
        cb.disabled = true;
        note.textContent = 'Sudah ada di semester ini — tidak akan disalin ulang.';
      } else if (!tersedia) {
        cb.checked = false;
        cb.disabled = true;
        note.textContent = teksTidakTersedia;
      } else {
        cb.disabled = false;
        cb.checked = true;
        note.textContent = 'Akan disalin dari semester sumber.';
      }
    }

    function renderSumber() {
      const id = parseInt(byId('semester_asal').value);
      const s = sumber.find(x => x.id === id);
      if (!s) return;

      // Pratinjau detail
      const adaDetail = !!s.detail;
      byId('pv-detail-kosong').classList.toggle('d-none', adaDetail);
      byId('pv-detail-isi').classList.toggle('d-none', !adaDetail);
      if (adaDetail) {
        byId('pv-nama').value = s.detail.nama_sekolah || '-';
        byId('pv-kepala').value = s.detail.kepala_sekolah || '-';
        byId('pv-nip').value = s.detail.nip || '-';
        byId('pv-telp').value = s.detail.no_telp || '-';
        byId('pv-alamat').value = s.detail.alamat_sekolah || '-';
        byId('pv-wilayah').value = s.detail.wilayah || '-';
      }

      // Pratinjau rombel
      const adaRombel = s.rombel.length > 0;
      byId('pv-rombel-jumlah').textContent = s.rombel.length;
      byId('pv-rombel-kosong').classList.toggle('d-none', adaRombel);
      byId('pv-rombel').innerHTML = s.rombel.map(r =>
        `<li>${r.nama_rombel} — Tingkat ${r.tingkat} (${r.jumlah_anggota} anggota aktif)</li>`
      ).join('');

      // Ketersediaan checkbox
      aturCheckbox(byId('salin-detail'), byId('note-detail'),
        adaDetail, statusTarget.hasDetail,
        'Tidak tersedia di semester sumber ini.');
      aturCheckbox(byId('salin-rombel'), byId('note-rombel'),
        adaRombel, statusTarget.hasRombel,
        'Tidak tersedia di semester sumber ini.');

      const cbAnggota = byId('salin-anggota');
      const noteAnggota = byId('note-anggota');
      if (!adaRombel || statusTarget.hasRombel) {
        cbAnggota.checked = false;
        cbAnggota.disabled = true;
        noteAnggota.textContent = 'Mengikuti rombel — hanya bisa disalin bila rombel ikut disalin.';
      } else {
        cbAnggota.disabled = false;
        cbAnggota.checked = true;
        const total = s.rombel.reduce((n, r) => n + r.jumlah_anggota, 0);
        noteAnggota.textContent =
          `${total} siswa aktif akan dimasukkan ke rombel barunya. ` +
          'Tanggal mulai anggota = tanggal mulai semester tujuan.';
      }
    }

    byId('semester_asal').addEventListener('change', renderSumber);
    renderSumber();
  });
</script>
@endsection
@endif
