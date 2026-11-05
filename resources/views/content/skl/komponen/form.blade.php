@extends('layouts/layoutMaster')

@section('title', isset($komponen) ? 'Edit Komponen SKL' : 'Tambah Komponen SKL')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')

  @php
    $isEdit = isset($komponen);
    $jenjang = $isEdit ? $komponen->jenjang : $jenjang ?? request('jenjang', 'SD');
  @endphp

  <div class="mb-4">
    <a href="{{ route('skl.komponen.index', ['jenjang' => $jenjang]) }}" class="btn btn-sm btn-outline-secondary">
      <i class="icon-base ri ri-arrow-left-line me-1"></i>Kembali
    </a>
  </div>

  <div class="row justify-content-center">
    <div class="col-12 col-lg-6">

      {{-- Info semester (read-only, dari session) --}}
      <div class="alert alert-light border d-flex gap-3 mb-4 py-3">
        <i class="icon-base ri ri-calendar-check-line text-primary mt-1 flex-shrink-0"></i>
        <div class="small">
          Komponen ini akan dibuat untuk semester
          <strong>{{ $semester->tahun_pelajaran }}</strong>
          ({{ $semester->tahun_pelajaran . ' - ' . $semester->semester }}) —
          Jenjang <strong>{{ $jenjang }}</strong>.
          <br>
          <span class="text-muted">Semester diambil dari sesi aktif dan tidak dapat diubah di sini.</span>
        </div>
      </div>

      <div class="card">
        <div class="card-header border-bottom">
          <h5 class="mb-0">
            <i class="icon-base ri ri-{{ $isEdit ? 'edit' : 'add' }}-line me-2"></i>
            {{ $isEdit ? 'Edit Komponen: ' . $komponen->label : 'Tambah Komponen Nilai SKL' }}
          </h5>
        </div>
        <div class="card-body">

          @if ($errors->any())
            <div class="alert alert-danger py-2 mb-4">
              <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $err)
                  <li>{{ $err }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form action="{{ $isEdit ? route('skl.komponen.update', $komponen) : route('skl.komponen.store') }}"
            method="POST" id="form-komponen">
            @csrf
            @if ($isEdit)
              @method('PUT')
            @endif

            {{-- Jenjang — tampil saat tambah baru, hidden saat edit --}}
            @if (!$isEdit)
              <div class="mb-4">
                <label class="form-label fw-semibold">
                  Jenjang <span class="text-danger">*</span>
                </label>
                <div class="d-flex gap-2 flex-wrap">
                  @foreach (['SD', 'SMP', 'SMA', 'SMK'] as $j)
                    <div class="form-check form-check-inline">
                      <input class="form-check-input" type="radio" name="jenjang" id="jenjang-{{ $j }}"
                        value="{{ $j }}" {{ old('jenjang', $jenjang) === $j ? 'checked' : '' }}>
                      <label class="form-check-label" for="jenjang-{{ $j }}">{{ $j }}</label>
                    </div>
                  @endforeach
                </div>
                @error('jenjang')
                  <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
              </div>
            @else
              {{-- Saat edit: jenjang tidak bisa diubah, tampilkan saja --}}
              <div class="alert alert-light border py-2 mb-4 d-flex gap-3">
                <i class="icon-base ri ri-information-line text-muted mt-1 flex-shrink-0"></i>
                <div class="small text-muted">
                  Komponen ini berlaku untuk jenjang <strong>{{ $komponen->jenjang }}</strong>.
                  Jenjang tidak dapat diubah.
                </div>
              </div>
            @endif

            {{-- Kode — hanya saat tambah --}}
            @if (!$isEdit)
              <div class="mb-4">
                <label class="form-label fw-semibold">
                  Kode <span class="text-danger">*</span>
                </label>
                <input type="text" name="kode" class="form-control @error('kode') is-invalid @enderror"
                  placeholder="misal: rapor, ujian_sekolah, nilai_harian" value="{{ old('kode') }}">
                <div class="form-text">
                  Huruf kecil, angka, underscore. Contoh: <code>rapor</code>, <code>ujian_sekolah</code>
                </div>
                @error('kode')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            @endif

            {{-- Label --}}
            <div class="mb-4">
              <label class="form-label fw-semibold">
                Label <span class="text-danger">*</span>
              </label>
              <input type="text" name="label" class="form-control @error('label') is-invalid @enderror"
                placeholder="misal: Nilai Rapor, Nilai Ujian Sekolah" value="{{ old('label', $komponen->label ?? '') }}">
              <div class="form-text">Nama yang ditampilkan sebagai tab di form input nilai.</div>
              @error('label')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Bobot --}}
            <div class="mb-4">
              <label class="form-label fw-semibold">
                Bobot (%) <span class="text-danger">*</span>
              </label>
              <div class="input-group" style="max-width:200px">
                <input type="number" name="bobot" id="input-bobot"
                  class="form-control @error('bobot') is-invalid @enderror" min="1" max="100" step="1"
                  value="{{ old('bobot', $komponen->bobot ?? '') }}" placeholder="0">
                <span class="input-group-text">%</span>
                @error('bobot')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>

              {{-- Visual progress bobot --}}
              <div class="mt-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <small class="text-muted">Bobot komponen ini</small>
                  <small id="label-bobot" class="fw-semibold">0%</small>
                </div>
                <div class="progress" style="height:8px">
                  <div id="bar-bobot" class="progress-bar bg-primary" style="width:0%;transition:width .3s"></div>
                </div>
                <div class="mt-2 d-flex gap-3 flex-wrap">
                  <small class="text-muted">
                    Sudah terpakai:
                    <span class="fw-semibold text-warning">{{ $totalBobot }}%</span>
                  </small>
                  <small class="text-muted">
                    Sisa:
                    <span id="bobot-sisa" class="fw-semibold text-{{ 100 - $totalBobot > 0 ? 'success' : 'danger' }}">
                      {{ 100 - $totalBobot }}%
                    </span>
                  </small>
                </div>
              </div>
            </div>

            {{-- Keterangan --}}
            <div class="mb-4">
              <label class="form-label fw-semibold">Keterangan</label>
              <input type="text" name="keterangan" class="form-control @error('keterangan') is-invalid @enderror"
                placeholder="misal: Rata-rata nilai rapor 5 semester"
                value="{{ old('keterangan', $komponen->keterangan ?? '') }}">
              @error('keterangan')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Urutan --}}
            <div class="mb-5">
              <label class="form-label fw-semibold">
                Urutan <span class="text-danger">*</span>
              </label>
              <input type="number" name="urutan" class="form-control @error('urutan') is-invalid @enderror"
                min="0" step="1" style="max-width:120px"
                value="{{ old('urutan', $komponen->urutan ?? 0) }}">
              <div class="form-text">Angka lebih kecil tampil lebih awal (tab paling kiri).</div>
              @error('urutan')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- Preview formula --}}
            <div class="alert alert-light border mb-5" id="preview-formula">
              <h6 class="small fw-semibold text-muted mb-1">PREVIEW FORMULA NILAI AKHIR</h6>
              <div id="formula-text" class="small text-muted fst-italic">
                Isi bobot dan label di atas untuk melihat preview...
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
              <a href="{{ route('skl.komponen.index', ['jenjang' => $jenjang]) }}"
                class="btn btn-outline-secondary">Batal</a>
              <button type="submit" class="btn btn-primary">
                <i class="icon-base ri ri-save-line me-1"></i>
                {{ $isEdit ? 'Perbarui Komponen' : 'Simpan Komponen' }}
              </button>
            </div>

          </form>
        </div>
      </div>

      {{-- Contoh referensi komponen --}}
      @if (!$isEdit)
        <div class="card mt-4">
          <div class="card-header border-bottom">
            <h6 class="mb-0 small text-muted">
              <i class="icon-base ri ri-lightbulb-line me-1"></i>Contoh Konfigurasi Umum
            </h6>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm table-bordered mb-0">
              <thead class="table-light">
                <tr>
                  <th>Kode</th>
                  <th>Label</th>
                  <th class="text-center">Bobot</th>
                </tr>
              </thead>
              <tbody>
                @foreach ([['rapor', 'Nilai Rapor (Rata-rata)', '60%'], ['ujian_sekolah', 'Nilai Ujian Sekolah', '40%'], ['harian', 'Nilai Harian + Tugas', '20%'], ['ujian', 'Nilai Ujian Akhir', '40%'], ['praktik', 'Nilai Praktik / Portofolio', '20%']] as [$kode, $label, $bobot])
                  <tr class="contoh-row" style="cursor:pointer" title="Klik untuk isi form">
                    <td><code>{{ $kode }}</code></td>
                    <td>{{ $label }}</td>
                    <td class="text-center">
                      <span class="badge bg-label-primary">{{ $bobot }}</span>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
            <div class="px-3 py-2">
              <small class="text-muted">
                <i class="icon-base ri ri-mouse-line me-1"></i>
                Klik baris untuk mengisi form secara otomatis.
              </small>
            </div>
          </div>
        </div>
      @endif

    </div>
  </div>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const isEdit = {{ $isEdit ? 'true' : 'false' }};
      const bobotTerpakai = {{ $totalBobot }};

      const inputBobot = document.getElementById('input-bobot');
      const barBobot = document.getElementById('bar-bobot');
      const labelBobot = document.getElementById('label-bobot');

      function updateBar(val) {
        const n = Math.min(Math.max(parseInt(val) || 0, 0), 100);
        const total = isEdit ? n : (bobotTerpakai + n);

        barBobot.style.width = n + '%';
        labelBobot.textContent = n + '%';
        barBobot.className = 'progress-bar ' + (
          total > 100 ? 'bg-danger' : total === 100 ? 'bg-success' : 'bg-primary'
        );

        updateFormula(n);
      }

      function updateFormula(bobotBaru) {
        const formulaEl = document.getElementById('formula-text');
        if (!formulaEl) return;

        if (bobotBaru <= 0) {
          formulaEl.textContent = 'Isi bobot dan label di atas untuk melihat preview...';
          formulaEl.className = 'small text-muted fst-italic';
          return;
        }

        const labelEl = document.querySelector('input[name="label"]');
        const label = labelEl?.value?.trim() || 'Komponen ini';
        const sisa = (100 - bobotTerpakai) - bobotBaru;

        let teks = `${label} (${bobotBaru}%)`;
        if (bobotTerpakai > 0) teks = `[Komponen lain] (${bobotTerpakai}%) + ${teks}`;
        if (sisa > 0) teks += ` + [Komponen berikutnya] (${sisa}%)`;
        teks += ' = 100%';

        formulaEl.textContent = sisa < 0 ?
          `⚠️ Bobot melebihi 100% (sisa tersedia: ${100 - bobotTerpakai}%)` :
          teks;
        formulaEl.className = sisa < 0 ?
          'small text-danger fst-italic' :
          'small text-muted fst-italic';
      }

      inputBobot?.addEventListener('input', function() {
        updateBar(this.value);
      });
      document.querySelector('input[name="label"]')?.addEventListener('input', function() {
        updateFormula(parseInt(inputBobot?.value) || 0);
      });

      if (inputBobot?.value) updateBar(inputBobot.value);

      // Klik baris contoh → isi form
      document.querySelectorAll('.contoh-row').forEach(row => {
        row.addEventListener('click', function() {
          const cells = this.querySelectorAll('td');
          const kode = cells[0].textContent.trim();
          const label = cells[1].textContent.trim();
          const bobot = parseInt(cells[2].textContent);

          const inputKode = document.querySelector('input[name="kode"]');
          const inputLabel = document.querySelector('input[name="label"]');
          if (inputKode) inputKode.value = kode;
          if (inputLabel) inputLabel.value = label;
          if (inputBobot) {
            inputBobot.value = bobot;
            updateBar(bobot);
          }

          document.querySelectorAll('.contoh-row').forEach(r => r.classList.remove('table-primary'));
          this.classList.add('table-primary');
          document.getElementById('form-komponen')?.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        });
      });

      document.addEventListener("wheel", function(event) {
        if (document.activeElement.type === "number") {
          document.activeElement.blur();
        }
      });
    });
  </script>
@endsection
