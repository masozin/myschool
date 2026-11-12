@extends('layouts/layoutMaster')

@section('title', $surat ? 'Ubah Surat Keluar' : 'Buat Surat Keluar')

@section('content')

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @php
    $bacaSaja = !$bolehKelola || ($surat && $surat->status === 'batal');
    $modeAwal = old('mode_nomor', $surat && $surat->nomor_manual ? 'manual' : 'otomatis');
  @endphp

  <div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h5 class="mb-0">
        <a href="{{ route('surat-keluar.index') }}" class="btn btn-sm btn-outline-secondary me-2">
          <i class="icon-base ri ri-arrow-left-line"></i></a>
        {{ $surat ? 'Surat Keluar' : 'Buat Surat Keluar' }}
        @if ($surat)
          @if ($surat->status === 'terbit')
            <span class="badge bg-label-success ms-2">Terbit</span>
          @elseif ($surat->status === 'batal')
            <span class="badge bg-label-danger ms-2">Dibatalkan</span>
          @else
            <span class="badge bg-label-secondary ms-2">Draft</span>
          @endif
        @endif
      </h5>
      @if ($surat && $surat->nomor_surat)
        <span class="fw-medium">Nomor: {{ $surat->nomor_surat }}</span>
      @endif
    </div>
    <div class="card-body">
      @if ($surat && $surat->status === 'batal')
        <div class="alert alert-danger">Surat ini sudah dibatalkan dan tidak dapat diubah.
          {{ $surat->catatan ? 'Catatan: ' . $surat->catatan : '' }}</div>
      @elseif (!$bolehKelola)
        <div class="alert alert-info">Mode lihat — hanya petugas (Operator Sekolah / Tata Usaha) yang dapat mengubah
          data surat.</div>
      @endif

      <form method="POST" action="{{ $surat ? route('surat-keluar.update', $surat) : route('surat-keluar.store') }}"
        enctype="multipart/form-data">
        @csrf
        @if ($surat)
          @method('PUT')
        @endif
        <fieldset {{ $bacaSaja ? 'disabled' : '' }}>
          <div class="row g-4">
            <div class="col-md-3">
              <label class="form-label">Tanggal Surat <span class="text-danger">*</span></label>
              <input type="date" name="tanggal_surat" class="form-control"
                value="{{ old('tanggal_surat', $surat ? $surat->tanggal_surat?->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Kategori</label>
              <select name="kategori_id" class="form-select">
                <option value="">— Tanpa kategori —</option>
                @foreach ($kategoris as $k)
                  <option value="{{ $k->id }}"
                    {{ (int) old('kategori_id', $surat?->kategori_id) === $k->id ? 'selected' : '' }}>
                    {{ $k->kode }} — {{ $k->nama }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Sifat <span class="text-danger">*</span></label>
              <select name="sifat" class="form-select" required>
                @foreach (['Biasa', 'Penting', 'Segera', 'Rahasia'] as $sft)
                  <option value="{{ $sft }}" {{ old('sifat', $surat?->sifat ?? 'Biasa') === $sft ? 'selected' : '' }}>
                    {{ $sft }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Lampiran</label>
              <input type="text" name="lampiran" class="form-control" placeholder="Mis. 1 berkas"
                value="{{ old('lampiran', $surat?->lampiran) }}">
            </div>

            <div class="col-md-6">
              <label class="form-label">Tujuan <span class="text-danger">*</span></label>
              <input type="text" name="tujuan" class="form-control" maxlength="200"
                placeholder="Kepada siapa surat ini dikirim" value="{{ old('tujuan', $surat?->tujuan) }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Penandatangan</label>
              <select name="penandatangan_ptk_id" class="form-select">
                <option value="">— Belum ditentukan —</option>
                @foreach ($ptks as $p)
                  <option value="{{ $p->id }}"
                    {{ (int) old('penandatangan_ptk_id', $surat?->penandatangan_ptk_id ?? $kepsekId) === $p->id ? 'selected' : '' }}>
                    {{ $p->nama_lengkap }} — {{ $p->jabatan }}</option>
                @endforeach
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Perihal <span class="text-danger">*</span></label>
              <input type="text" name="perihal" class="form-control" maxlength="250"
                value="{{ old('perihal', $surat?->perihal) }}" required>
            </div>

            <div class="col-12">
              <label class="form-label">Isi Ringkas</label>
              <textarea name="isi_ringkas" class="form-control" rows="3"
                placeholder="Ringkasan isi surat, untuk memudahkan pencarian">{{ old('isi_ringkas', $surat?->isi_ringkas) }}</textarea>
            </div>

            {{-- Penomoran --}}
            <div class="col-12">
              <label class="form-label d-block">Penomoran</label>
              @if ($surat && $surat->status === 'terbit' && !$surat->nomor_manual)
                <input type="hidden" name="mode_nomor" value="otomatis">
                <div class="alert alert-success mb-0">
                  Nomor resmi <strong>{{ $surat->nomor_surat }}</strong> sudah ditetapkan dari penghitung dan
                  <strong>dibekukan</strong> — tidak berubah walau tanggal/kategori surat diubah.
                </div>
              @elseif ($surat && $surat->status === 'terbit' && $surat->nomor_manual)
                <input type="hidden" name="mode_nomor" value="manual">
                <input type="text" name="nomor_surat" class="form-control" maxlength="150"
                  value="{{ old('nomor_surat', $surat->nomor_surat) }}">
                <div class="form-text">Nomor manual dari petugas — masih boleh dikoreksi.</div>
              @else
                <div class="form-check form-check-inline">
                  <input class="form-check-input mode-nomor" type="radio" name="mode_nomor" id="modeOtomatis"
                    value="otomatis" {{ $modeAwal === 'otomatis' ? 'checked' : '' }}>
                  <label class="form-check-label" for="modeOtomatis">Otomatis dari penghitung</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input mode-nomor" type="radio" name="mode_nomor" id="modeManual"
                    value="manual" {{ $modeAwal === 'manual' ? 'checked' : '' }}>
                  <label class="form-check-label" for="modeManual">Manual (mencatat surat lama)</label>
                </div>
                <div id="kotakNomorManual" class="mt-2" style="display:none">
                  <input type="text" name="nomor_surat" class="form-control" maxlength="150"
                    placeholder="Tulis nomor surat persis seperti di suratnya"
                    value="{{ old('nomor_surat', $surat?->nomor_surat) }}">
                </div>
                <div id="kotakNomorOtomatis" class="form-text mt-2">
                  Nomor berikutnya dari penghitung, kurang lebih: <strong>{{ $contohNomor }}</strong> —
                  ditetapkan persis saat surat diterbitkan.
                </div>
              @endif
            </div>

            @if (!$surat)
              <div class="col-12">
                <label class="form-label">Berkas Arsip (scan surat, opsional)</label>
                <input type="file" name="berkas_arsip" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                <div class="form-text">PDF/JPG/PNG maks 5 MB. Berkas tambahan dapat diunggah setelah surat tersimpan.
                </div>
              </div>
            @endif

            <div class="col-12">
              <label class="form-label">Catatan Internal</label>
              <textarea name="catatan" class="form-control" rows="2"
                placeholder="Catatan petugas, tidak ikut tercetak di agenda">{{ old('catatan', $surat?->catatan) }}</textarea>
            </div>
          </div>
        </fieldset>

        @if (!$bacaSaja)
          <div class="d-flex gap-2 mt-4">
            @if (!$surat)
              <button type="submit" name="aksi" value="draft" class="btn btn-outline-primary">
                <i class="icon-base ri ri-draft-line me-1"></i>Simpan Draft</button>
              <button type="submit" name="aksi" value="terbit" class="btn btn-primary">
                <i class="icon-base ri ri-send-plane-line me-1"></i>Simpan &amp; Terbitkan</button>
            @else
              <button type="submit" class="btn btn-primary">
                <i class="icon-base ri ri-save-line me-1"></i>Simpan Perubahan</button>
              @if ($surat->status === 'draft')
                <button type="submit" form="formTerbitkan" class="btn btn-success">
                  <i class="icon-base ri ri-send-plane-line me-1"></i>Terbitkan Surat Ini</button>
              @endif
            @endif
          </div>
        @endif
      </form>

      @if ($surat && $surat->status === 'draft')
        <form method="POST" action="{{ route('surat-keluar.terbitkan', $surat) }}" id="formTerbitkan"
          onsubmit="return confirm('Terbitkan surat ini? Nomor resmi akan ditetapkan dan dibekukan.')">@csrf</form>
      @endif
    </div>
  </div>

  {{-- Berkas arsip & lampiran (hanya pada surat yang sudah tersimpan) --}}
  @if ($surat)
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0"><i class="icon-base ri ri-attachment-2 me-2"></i>Berkas Surat</h5>
      </div>
      <div class="card-body">
        @if ($surat->berkas->count())
          <div class="table-responsive">
            <table class="table table-hover mb-4">
              <thead>
                <tr>
                  <th>Jenis</th>
                  <th>Nama Berkas</th>
                  <th>Ukuran</th>
                  <th>Diunggah Oleh</th>
                  <th class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($surat->berkas as $b)
                  <tr>
                    <td>
                      @if ($b->jenis === 'arsip')
                        <span class="badge bg-label-success">Arsip Bertandatangan</span>
                      @else
                        <span class="badge bg-label-secondary">Lampiran</span>
                      @endif
                    </td>
                    <td>{{ $b->nama_asli }}</td>
                    <td>{{ $b->ukuranLabel() }}</td>
                    <td>{{ $b->pengunggah?->name ?? '-' }}</td>
                    <td class="text-end text-nowrap">
                      <a href="{{ route('surat-keluar.berkas.lihat', $b) }}" target="_blank"
                        class="btn btn-sm btn-outline-primary"><i class="icon-base ri ri-eye-line"></i> Lihat</a>
                      @if ($bolehKelola)
                        <form method="POST" action="{{ route('surat-keluar.berkas.hapus', $b) }}" class="d-inline"
                          onsubmit="return confirm('Hapus berkas ini?')">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="icon-base ri ri-delete-bin-line"></i></button>
                        </form>
                      @endif
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <p class="text-muted">Belum ada berkas. Unggah hasil scan surat yang sudah ditandatangani agar arsipnya
            lengkap.</p>
        @endif

        @if ($bolehKelola && $surat->status !== 'batal')
          <form method="POST" action="{{ route('surat-keluar.berkas.unggah', $surat) }}" enctype="multipart/form-data"
            class="row g-3 align-items-end">
            @csrf
            <div class="col-md-3">
              <label class="form-label">Jenis Berkas</label>
              <select name="jenis" class="form-select">
                <option value="arsip">Arsip Bertandatangan</option>
                <option value="lampiran">Lampiran Pendukung</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Berkas (PDF/JPG/PNG, maks 5 MB)</label>
              <input type="file" name="berkas" class="form-control" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
            <div class="col-md-3">
              <button type="submit" class="btn btn-primary w-100">
                <i class="icon-base ri ri-upload-2-line me-1"></i>Unggah</button>
            </div>
          </form>
        @endif
      </div>
    </div>
  @endif

@endsection

@section('page-script')
  <script>
    (function() {
      const radios = document.querySelectorAll('.mode-nomor');
      if (!radios.length) return;
      const kotakManual = document.getElementById('kotakNomorManual');
      const kotakOtomatis = document.getElementById('kotakNomorOtomatis');
      const perbarui = function() {
        const manual = document.getElementById('modeManual').checked;
        kotakManual.style.display = manual ? '' : 'none';
        kotakOtomatis.style.display = manual ? 'none' : '';
      };
      radios.forEach(function(r) {
        r.addEventListener('change', perbarui);
      });
      perbarui();
    })();
  </script>
@endsection
