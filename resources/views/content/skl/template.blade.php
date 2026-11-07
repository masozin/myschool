@extends('layouts/layoutMaster')

@section('title', 'Kelola Template SKL')

@section('content')
  <div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ url('/skl') }}" class="btn btn-label-secondary btn-sm">
      <i class="icon-base ri ri-arrow-left-line me-1"></i>Kembali ke SKL
    </a>
    <div>
      <h4 class="mb-0">Kelola Template Cetak SKL</h4>
      <small class="text-muted">
        Template cetak adalah file Blade di <code>resources/views/templates/skl-template/</code> yang didaftarkan
        untuk satu jenjang + semester. Preview/cetak SKL memakai file bawaan bila belum ada pendaftaran aktif; pendaftaran di sini menggantikannya bila ada pendaftaran aktif dan file-nya ada.
      </small>
    </div>
  </div>

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

  <div class="row g-4">
    <div class="col-12 col-lg-4">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0" id="judul-form">Daftarkan Template</h5>
          <button type="button" class="btn btn-label-secondary btn-sm d-none" id="btn-batal-edit">
            <i class="icon-base ri ri-close-line me-1"></i>Batal Edit
          </button>
        </div>
        <div class="card-body">
          <form method="POST" action="{{ url('/skl/template') }}" id="form-template">
            @csrf
            <input type="hidden" name="_method" id="form-method" value="POST" disabled>

            <div class="mb-3">
              <label class="form-label" for="jenjang">Jenjang</label>
              <select class="form-select" id="jenjang" name="jenjang" required>
                @foreach (['SD', 'SMP', 'SMA', 'SMK'] as $j)
                  <option value="{{ $j }}">{{ $j }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label" for="semester_id">Semester</label>
              <select class="form-select" id="semester_id" name="semester_id" required>
                @foreach ($semesters as $s)
                  <option value="{{ $s->id }}">{{ $s->tahun_pelajaran }} — {{ $s->semester }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label" for="kurikulum_id">Kurikulum</label>
              <select class="form-select" id="kurikulum_id" name="kurikulum_id" required>
                @foreach ($kurikulums as $k)
                  <option value="{{ $k->id }}">{{ $k->nama }}</option>
                @endforeach
              </select>
              <small class="text-muted">Dicatat sebagai keterangan template; pemilihan template saat cetak berdasarkan jenjang + semester.</small>
            </div>
            <div class="mb-3">
              <label class="form-label" for="nama_file">File Template</label>
              <select class="form-select" id="nama_file" name="nama_file" required>
                @forelse ($fileList as $f)
                  <option value="{{ $f }}">{{ $f }}.blade.php</option>
                @empty
                  <option value="">— tidak ada file di folder template —</option>
                @endforelse
              </select>
              @if ($fileList)
                <small class="text-muted">File yang terdeteksi di folder <code>templates/skl-template/</code>.</small>
              @else
                <small class="text-danger">Belum ada file Blade di folder template. Tambahkan file-nya dulu lewat kode sumber, lalu daftarkan di sini.</small>
              @endif
            </div>
            <div class="mb-3">
              <label class="form-label" for="keterangan">Keterangan</label>
              <input type="text" class="form-control" id="keterangan" name="keterangan" maxlength="255"
                placeholder="Contoh: Template SKL SD 2025/2026 (Kurikulum Merdeka)">
            </div>
            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
              <label class="form-check-label" for="is_active">Aktif (dipakai untuk preview &amp; cetak)</label>
            </div>
            <button type="submit" class="btn btn-primary w-100" id="btn-simpan-form">
              <i class="icon-base ri ri-save-line me-1"></i>Simpan
            </button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-8">
      <div class="card">
        <div class="card-header">
          <h5 class="card-title mb-0">Template Terdaftar</h5>
        </div>
        <div class="table-responsive">
          <table class="table table-bordered table-hover mb-0">
            <thead>
              <tr>
                <th>Jenjang</th>
                <th>Semester</th>
                <th>Kurikulum</th>
                <th>File</th>
                <th>Status</th>
                <th>Keterangan</th>
                <th style="width: 120px;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($templates as $t)
                <tr>
                  <td>{{ $t->jenjang }}</td>
                  <td>{{ $t->semester?->tahun_pelajaran }} — {{ $t->semester?->semester }}</td>
                  <td>{{ $t->kurikulum?->nama ?? '-' }}</td>
                  <td><code>{{ $t->nama_file }}.blade.php</code></td>
                  <td>
                    @if ($t->templateAda())
                      <span class="badge bg-label-success">File ada</span>
                    @else
                      <span class="badge bg-label-danger">File hilang</span>
                    @endif
                    @if ($t->is_active)
                      <span class="badge bg-label-primary">Aktif</span>
                    @else
                      <span class="badge bg-label-secondary">Nonaktif</span>
                    @endif
                  </td>
                  <td>{{ $t->keterangan ?? '-' }}</td>
                  <td>
                    <button type="button" class="btn btn-sm btn-label-primary btn-edit-template"
                      data-id="{{ $t->id }}" data-jenjang="{{ $t->jenjang }}"
                      data-semester="{{ $t->semester_id }}" data-kurikulum="{{ $t->kurikulum_id }}"
                      data-file="{{ $t->nama_file }}" data-keterangan="{{ $t->keterangan }}"
                      data-aktif="{{ $t->is_active ? 1 : 0 }}" title="Edit">
                      <i class="icon-base ri ri-pencil-line"></i>
                    </button>
                    <form method="POST" action="{{ url('/skl/template/' . $t->id) }}" class="d-inline"
                      onsubmit="return confirm('Hapus pendaftaran template ini? File Blade-nya tidak ikut terhapus.')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-label-danger" title="Hapus">
                        <i class="icon-base ri ri-delete-bin-line"></i>
                      </button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">
                    Belum ada template terdaftar. Daftarkan file yang tersedia lewat formulir di samping —
                    untuk SD saat ini tersedia file <code>sd/2024-2025.blade.php</code>.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.getElementById('form-template');
      const methodField = document.getElementById('form-method');
      const judul = document.getElementById('judul-form');
      const btnBatal = document.getElementById('btn-batal-edit');
      const baseUrl = "{{ url('/skl/template') }}";

      document.querySelectorAll('.btn-edit-template').forEach(btn => {
        btn.addEventListener('click', function() {
          form.action = baseUrl + '/' + this.dataset.id;
          methodField.value = 'PUT';
          methodField.disabled = false;
          document.getElementById('jenjang').value = this.dataset.jenjang;
          document.getElementById('semester_id').value = this.dataset.semester;
          document.getElementById('kurikulum_id').value = this.dataset.kurikulum;
          document.getElementById('nama_file').value = this.dataset.file;
          document.getElementById('keterangan').value = this.dataset.keterangan ?? '';
          document.getElementById('is_active').checked = this.dataset.aktif === '1';
          judul.textContent = 'Edit Template';
          btnBatal.classList.remove('d-none');
          window.scrollTo({
            top: 0,
            behavior: 'smooth'
          });
        });
      });

      btnBatal.addEventListener('click', function() {
        form.reset();
        form.action = baseUrl;
        methodField.disabled = true;
        judul.textContent = 'Daftarkan Template';
        btnBatal.classList.add('d-none');
      });
    });
  </script>
@endsection
