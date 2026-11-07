@extends('layouts/layoutMaster')

@section('title', 'Input SKHU - ' . $siswa->nama_siswa)

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

@section('content')

  {{-- Breadcrumb / back --}}
  <div class="mb-4">
    <a href="{{ url('/skhu') }}" class="btn btn-sm btn-outline-secondary">
      <i class="icon-base ri ri-arrow-left-line me-1"></i>Kembali ke Daftar
    </a>
  </div>

  @php
    $isFinal = $skhu?->status === 'final';
    // $canEdit: true hanya untuk Operator dan Guru wali kelas siswa
    // ini (Guru bukan wali sudah ditolak controller sebelum sampai
    // ke form). Admin/Kepala Sekolah mode lihat. Fallback ke peran
    // langsung supaya form ini benar sendiri tanpa bergantung pada
    // variabel dari controller.
    $canEdit = $canEdit ?? auth()->user()?->hasAnyRole(['Operator Sekolah', 'Guru']) ?? false;
  @endphp

  {{-- Banner mode lihat (Admin / Kepala Sekolah) --}}
  @if (!$canEdit)
    <div class="alert alert-info d-flex align-items-center gap-3 mb-4">
      <i class="icon-base ri ri-eye-line ri-24px flex-shrink-0"></i>
      <div>
        <strong>Mode lihat.</strong>
        Akun ini hanya boleh melihat SKHU — pengisian dan finalisasi dilakukan Operator Sekolah atau wali kelas siswa ini.
      </div>
    </div>
  @endif

  {{-- Banner terkunci jika final --}}
  @if ($isFinal)
    <div class="alert alert-success d-flex align-items-center gap-3 mb-4">
      <i class="icon-base ri ri-lock-line ri-24px flex-shrink-0"></i>
      <div class="flex-grow-1">
        <strong>SKHU ini sudah difinalisasi.</strong>
        Semua field tidak dapat diedit. Klik <em>Batalkan Finalisasi</em> untuk membuka kunci.
      </div>
      @if ($canEdit)
        <button type="button" class="btn btn-warning btn-sm flex-shrink-0" id="btn-batal-final">
          <i class="icon-base ri ri-lock-unlock-line me-1"></i>Batalkan Finalisasi
        </button>
      @endif
    </div>
  @endif

  <div class="row g-4">

    {{-- Kolom kiri: Identitas Siswa + Header SKHU --}}
    <div class="col-12 col-lg-4">

      {{-- Identitas Siswa --}}
      <div class="card mb-4">
        <div class="card-header border-bottom">
          <h6 class="mb-0">Identitas Siswa</h6>
        </div>
        <div class="card-body">
          <table class="table table-sm table-borderless mb-0">
            <tr>
              <td class="text-muted" width="120">Nama</td>
              <td><strong>{{ $siswa->nama_siswa }}</strong></td>
            </tr>
            <tr>
              <td class="text-muted">NISN</td>
              <td>{{ $siswa->nisn }}</td>
            </tr>
            <tr>
              <td class="text-muted">NIS</td>
              <td>{{ $siswa->registrasiTerbaru->nis ?? '-' }}</td>
            </tr>
            <tr>
              <td class="text-muted">Tempat Lahir</td>
              <td>{{ $siswa->tempat_lahir }}</td>
            </tr>
            <tr>
              <td class="text-muted">Tanggal Lahir</td>
              <td>{{ $siswa->tgl_lahir->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
              <td class="text-muted">Nama Ayah</td>
              <td>{{ $siswa->nama_ayah }}</td>
            </tr>
          </table>
        </div>
      </div>

      {{-- Header SKHU --}}
      <div class="card">
        <div class="card-header border-bottom">
          <h6 class="mb-0">Data SKHU</h6>
        </div>
        <div class="card-body">

          <div class="mb-4">
            <label class="form-label">Nomor Surat</label>
            <input type="text" id="nomor_surat" class="form-control form-control-sm" placeholder="nomor skhu"
              value="{{ $skhu?->nomor_surat }}" {{ ($isFinal || !$canEdit) ? 'disabled' : '' }}>
          </div>

          <div class="mb-4">
            <label class="form-label">Nomor Ujian Sekolah</label>
            <input type="text" id="nomor_ujian" class="form-control form-control-sm" placeholder="nomor ujian"
              value="{{ $skhu?->nomor_ujian }}" {{ ($isFinal || !$canEdit) ? 'disabled' : '' }}>
          </div>

          <div class="mb-4">
            <label class="form-label">Tanggal Mulai Ujian</label>
            <input type="text" id="tanggal_mulai_ujian" class="form-control form-control-sm flatpickr-date"
              value="{{ $skhu?->tanggal_mulai_ujian?->format('Y-m-d') }}" {{ ($isFinal || !$canEdit) ? 'disabled' : '' }}>
          </div>

          <div class="mb-4">
            <label class="form-label">Tanggal Selesai Ujian</label>
            <input type="text" id="tanggal_selesai_ujian" class="form-control form-control-sm flatpickr-date"
              value="{{ $skhu?->tanggal_selesai_ujian?->format('Y-m-d') }}" {{ ($isFinal || !$canEdit) ? 'disabled' : '' }}>
          </div>

          <div class="mb-4">
            <label class="form-label">Tanggal Terbit</label>
            <input type="text" id="tanggal_terbit" class="form-control form-control-sm flatpickr-date"
              value="{{ $skhu?->tanggal_terbit?->format('Y-m-d') }}" {{ ($isFinal || !$canEdit) ? 'disabled' : '' }}>
          </div>

          <div class="mb-4">
            <label class="form-label">Tempat Terbit</label>
            <input type="text" id="tempat_terbit" class="form-control form-control-sm"
              placeholder="Kosongkan untuk memakai nama desa sekolah"
              value="{{ $skhu?->tempat_terbit }}" {{ ($isFinal || !$canEdit) ? 'disabled' : '' }}>
          </div>

          @if ($skhu)
            <div class="alert alert-{{ $isFinal ? 'success' : 'warning' }} py-2 mb-0">
              <i class="ri-information-line me-1"></i>
              Status: <strong>{{ $isFinal ? 'Final' : 'Draft' }}</strong>
              @if ($skhu->generated_at)
                <br><small>Terakhir disimpan: {{ $skhu->generated_at->diffForHumans() }}</small>
              @endif
            </div>
          @endif
        </div>
      </div>
    </div>

    {{-- Kolom kanan: Tabel Input Nilai --}}
    <div class="col-12 col-lg-8">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Input Nilai Ujian Sekolah</h6>
          <small class="text-muted">
            Nilai rata-rata: <strong id="nilai-rata-rata">0.00</strong>
          </small>
        </div>
        <div class="card-body">
          <table class="table table-sm table-bordered">
            <thead>
              <tr>
                <th width="50" class="text-center">No</th>
                <th>Mata Pelajaran</th>
                <th width="140" class="text-center">Nilai (0–100)</th>
              </tr>
            </thead>
            <tbody>
              @php $no = 1; @endphp
              @foreach ($mapels as $namaKelompok => $items)
                <tr>
                  <td colspan="3" class="fw-bold py-2 px-3">{{ $namaKelompok }}</td>
                </tr>
                @foreach ($items as $mapel)
                  <tr>
                    <td class="text-center">{{ $no++ }}</td>
                    <td>
                      {{ $mapel->masterMapel->nama ?? '-' }}
                      @if ($mapel->masterMapel->nama_sub_mapel)
                        <br><small class="text-muted ps-3">{{ $mapel->masterMapel->nama_sub_mapel }}</small>
                      @endif
                      @if ($mapel->jenis === 'tambahan')
                        <span class="badge bg-label-info ms-1" style="font-size:9px">Tambahan</span>
                      @endif
                    </td>
                    <td class="p-1">
                      <input type="number" class="form-control form-control-sm text-center input-nilai"
                        name="nilai[{{ $mapel->id }}]" id="nilai-{{ $mapel->id }}" min="0" max="100"
                        step="1" value="{{ number_format($nilaiExisting[$mapel->id] ?? 0, 2, '.', '') }}"
                        {{ ($isFinal || !$canEdit) ? 'disabled' : '' }}>
                    </td>
                  </tr>
                @endforeach
              @endforeach
            </tbody>
          </table>
        </div>

        {{-- Tombol aksi --}}
        <div class="card-footer d-flex justify-content-between align-items-center mt-2">
          <div>
            @if ($skhu)
              <a href="{{ url('/skhu/pdf/' . $skhu->id) }}" target="_blank" class="btn btn-outline-success btn-sm">
                <i class="icon-base ri ri-file-pdf-line me-1"></i>Preview PDF
              </a>
            @endif
          </div>

          @if (!$isFinal && $canEdit)
            <div class="d-flex gap-2">
              <button class="btn btn-outline-primary btn-sm" id="btn-simpan-draft">
                <i class="icon-base ri ri-save-line me-1"></i>
                <span class="d-none d-md-inline">Simpan Draft</span>
              </button>
            </div>
          @elseif (!$canEdit)
            {{-- Mode lihat — tidak ada tombol simpan --}}
            <span class="text-muted fw-semibold small">
              <i class="icon-base ri ri-eye-line me-1"></i>Mode lihat
            </span>
          @else
            {{-- Sudah final — hanya tampilkan info --}}
            <span class="text-success fw-semibold small">
              <i class="icon-base ri ri-shield-check-line me-1"></i>SKHU telah difinalisasi
            </span>
          @endif
        </div>
      </div>
    </div>
  </div>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const siswaId = {{ $siswa->id }};
      const skhuId = {{ $skhu?->id ?? 'null' }};
      const isFinal = {{ $isFinal ? 'true' : 'false' }};
      const canEdit = {{ $canEdit ? 'true' : 'false' }};

      // ── Init Flatpickr (hanya jika bisa edit dan tidak final) ─────────────
      if (!isFinal && canEdit) {
        document.querySelectorAll('.flatpickr-date').forEach(el => {
          flatpickr(el, {
            dateFormat: 'Y-m-d',
            allowInput: true
          });
        });
      }

      // ── Hitung rata-rata nilai secara realtime ────────────────────────────
      function hitungRataRata() {
        const inputs = document.querySelectorAll('.input-nilai');
        let total = 0,
          count = 0;
        inputs.forEach(input => {
          const val = parseFloat(input.value);
          if (!isNaN(val) && val > 0) {
            total += val;
            count++;
          }
        });
        document.getElementById('nilai-rata-rata').textContent =
          count > 0 ? (total / count).toFixed(2) : '0.00';
      }

      document.querySelectorAll('.input-nilai').forEach(i => i.addEventListener('input', hitungRataRata));
      hitungRataRata();

      // ── Kumpulkan data form ───────────────────────────────────────────────
      function kumpulkanData(finalisasi = false) {
        const nilai = {};
        document.querySelectorAll('.input-nilai').forEach(input => {
          const match = input.name.match(/nilai\[(\d+)\]/);
          if (match) nilai[match[1]] = parseFloat(input.value) || 0;
        });

        return {
          nomor_surat: document.getElementById('nomor_surat').value,
          nomor_ujian: document.getElementById('nomor_ujian').value,
          tanggal_mulai_ujian: document.getElementById('tanggal_mulai_ujian').value,
          tanggal_selesai_ujian: document.getElementById('tanggal_selesai_ujian').value,
          tanggal_terbit: document.getElementById('tanggal_terbit').value,
          tempat_terbit: document.getElementById('tempat_terbit').value,
          nilai,
          finalisasi: finalisasi ? 1 : 0,
        };
      }

      // ── Fungsi simpan ─────────────────────────────────────────────────────
      async function simpan(finalisasi = false) {
        const btn = document.getElementById('btn-simpan-draft');
        const oriTxt = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

        try {
          const res = await fetch(`/skhu/siswa/${siswaId}/simpan`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json'
            },
            body: JSON.stringify(kumpulkanData(finalisasi)),
          });
          const data = await res.json();

          if (res.ok) {
            Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              text: data.message,
              timer: 2000,
              showConfirmButton: false
            });
            setTimeout(() => location.reload(), 2100);
          } else {
            throw new Error(data.message ?? 'Gagal menyimpan');
          }
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: err.message
          });
        } finally {
          btn.disabled = false;
          btn.innerHTML = oriTxt;
        }
      }

      // ── Tombol Simpan Draft ───────────────────────────────────────────────
      document.getElementById('btn-simpan-draft')?.addEventListener('click', () => simpan(false));


      // ── Tombol Batalkan Finalisasi ────────────────────────────────────────
      document.getElementById('btn-batal-final')?.addEventListener('click', async () => {
        const konfirm = await Swal.fire({
          icon: 'warning',
          title: 'Batalkan Finalisasi?',
          text: 'SKHU akan dikembalikan ke status Draft dan bisa diedit kembali.',
          showCancelButton: true,
          confirmButtonText: 'Ya, Batalkan',
          cancelButtonText: 'Tidak',
          customClass: {
            confirmButton: 'btn btn-warning me-3',
            cancelButton: 'btn btn-outline-secondary'
          },
          buttonsStyling: false,
        });
        if (!konfirm.isConfirmed) return;

        const btn = document.getElementById('btn-batal-final');
        const oriTxt = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

        try {
          const res = await fetch(`/skhu/siswa/${skhuId}/updateStatus`, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json',
              'Content-Type': 'application/json'
            },
          });
          const data = await res.json();
          if (res.ok) {
            Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              text: data.message,
              timer: 2000,
              showConfirmButton: false
            });
            setTimeout(() => location.reload(), 2100);
          } else {
            throw new Error(data.message ?? 'Gagal membatalkan finalisasi.');
          }
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: err.message
          });
        } finally {
          btn.disabled = false;
          btn.innerHTML = oriTxt;
        }
      });

    });
  </script>
@endsection
