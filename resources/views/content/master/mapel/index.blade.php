@extends('layouts/layoutMaster')

@section('title', 'Data Master — Mata Pelajaran')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')

  {{-- Header --}}
  <div class="row mb-4 g-4">
    <div class="col-12">
      <div class="card">
        <div class="card-body d-flex align-items-center gap-4 py-4">
          <div class="avatar avatar-xl">
            <span class="avatar-initial rounded-circle bg-label-primary">
              <i class="icon-base ri ri-book-2-line ri-28px"></i>
            </span>
          </div>
          <div>
            <h4 class="mb-1">Data Mata Pelajaran</h4>
            <p class="text-muted mb-0">
              @if ($isAdmin)
                Kelola mapel pokok per jenjang dan tahun pelajaran. Mapel muatan lokal dikelola langsung oleh operator
                sekolah.
              @else
                Kelola mapel muatan lokal (tambahan) untuk sekolah Anda. Mata pelajaran pokok bersifat bawaan/read-only.
              @endif
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- Filter Jenjang & Semester --}}
  <div class="card mb-4">
    <div class="card-body py-3">
      <form method="GET" action="{{ url('/master/mapel') }}" id="form-filter"
        class="d-flex gap-3 flex-wrap align-items-end">

        {{-- Filter Jenjang (Admin only) --}}
        @if ($isAdmin)
          <div>
            <label class="form-label mb-1">Jenjang</label>
            <div class="d-flex gap-2">

              @foreach ($jenjangList as $j)
                <a href="{{ url('/master/mapel') }}?jenjang={{ $j }}&semester_id={{ $semester->id }}"
                  class="btn btn-sm {{ $jenjangDipilih === $j ? 'btn-primary' : 'btn-outline-secondary' }}">
                  {{ $j }}
                </a>
              @endforeach
            </div>
          </div>
        @endif

        <div>
          <label class="form-label mb-1">Tingkat / Kelas</label>
          <select name="tingkat" class="form-select form-select-sm" onchange="this.form.submit()"
            style="min-width: 140px;">
            <option value="">Semua Tingkat</option>
            @for ($i = 1; $i <= 12; $i++)
              <option value="{{ $i }}" {{ $tingkatDipilih === $i ? 'selected' : '' }}>
                Kelas {{ $i }}
              </option>
            @endfor
          </select>
        </div>

        {{-- Filter Semester --}}
        <div>
          <label class="form-label mb-1">Semester</label>
          <select name="semester_id" class="form-select form-select-sm" onchange="this.form.submit()"
            style="min-width: 220px;">
            @foreach ($semesterList as $s)
              <option value="{{ $s->id }}" {{ $semester->id == $s->id ? 'selected' : '' }}>
                {{ $s->tahun_pelajaran }} - {{ $s->semester }}
              </option>
            @endforeach
          </select>
          {{-- Pertahankan jenjang saat ganti semester --}}
          @if ($isAdmin)
            <input type="hidden" name="jenjang" value="{{ $jenjangDipilih }}">
            <input type="hidden" id="mapel-semester-id" value="{{ $semester->id }}">
          @endif
        </div>

        <div class="ms-auto d-flex gap-2">
          @if ($isAdmin)
            <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal"
              data-bs-target="#modal-salin">
              <i class="icon-base ri ri-file-copy-line me-1"></i>Salin Semester
            </button>
          @endif

          <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-mapel"
            id="btn-tambah-mapel">
            <i class="icon-base ri ri-add-line me-1"></i>Tambah Mapel {{ $isOperator ? 'Lokal' : '' }}
          </button>
        </div>

      </form>
    </div>
  </div>

  {{-- Tabel Mapel per Kelompok --}}
  @forelse ($kelompokList as $kelompok)
    @php
      $items = $mapelPerKelompok[$kelompok->nama] ?? collect();
    @endphp
    <div class="card mb-4">
      <div class="card-header border-bottom d-flex justify-content-between align-items-center">
        <div>
          <h6 class="mb-0">{{ $kelompok->nama }}</h6>
          <small class="text-muted">{{ $items->count() }} mata pelajaran</small>
        </div>
        <span class="badge bg-label-primary">{{ $jenjangDipilih }}</span>
      </div>
      <div class="card-datatable text-nowrap">
        <table class="table table-sm table-bordered table-hover mb-0">
          <thead>
            <tr>
              <th width="60" class="text-center">Urutan</th>
              <th>Nama Mata Pelajaran</th>
              <th>Sub Nama</th>
              <th width="140" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($items as $i => $mapel)
              <tr data-mapel-id="{{ $mapel->id }}">
                <td class="text-center">
                  <span class="badge bg-label-secondary">{{ $mapel->urutan }}</span>
                </td>
                <td>
                  {{-- Nama mapel: dari kolom nama_mapel di MapelMapping --}}
                  {{ $mapel->nama_mapel }}
                  @if ($mapel->jenis === 'tambahan')
                    <span class="badge bg-label-info ms-2" style="font-size: 0.7rem;">Lokal</span>
                  @endif
                </td>
                <td>
                  @if ($mapel->nama_mapel_sub)
                    <span class="text-muted">{{ $mapel->nama_mapel_sub }}</span>
                  @else
                    <span class="text-muted fst-italic">—</span>
                  @endif
                </td>
                <td>
                  <div class="d-flex gap-1 justify-content-center">
                    {{-- Hak akses: Admin → pokok | Operator → tambahan --}}
                    @if (($isAdmin && $mapel->jenis === 'pokok') || ($isOperator && $mapel->jenis === 'tambahan'))
                      {{-- Naik --}}
                      <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill btn-urutan-naik"
                        data-id="{{ $mapel->id }}" {{ $i === 0 ? 'disabled' : '' }}>
                        <i class="icon-base ri ri-arrow-up-line icon-18px"></i>
                      </button>
                      {{-- Turun --}}
                      <button class="btn btn-sm btn-icon btn-text-secondary rounded-pill btn-urutan-turun"
                        data-id="{{ $mapel->id }}" {{ $i === $items->count() - 1 ? 'disabled' : '' }}>
                        <i class="icon-base ri ri-arrow-down-line icon-18px"></i>
                      </button>
                      {{-- Edit --}}
                      <button class="btn btn-sm btn-icon btn-text-primary rounded-pill btn-edit"
                        data-id="{{ $mapel->id }}"
                        data-kelompok="{{ $mapel->mapel_kelompok_id ?? $mapel->masterMapel?->mapel_kelompok_id }}"
                        data-nama="{{ $mapel->nama_mapel }}" data-sub="{{ $mapel->nama_mapel_sub }}">
                        <i class="icon-base ri ri-edit-line icon-18px"></i>
                      </button>
                      {{-- Hapus --}}
                      <button class="btn btn-sm btn-icon btn-text-danger rounded-pill btn-hapus"
                        data-id="{{ $mapel->id }}" data-nama="{{ $mapel->nama_mapel }}">
                        <i class="icon-base ri ri-delete-bin-line icon-18px"></i>
                      </button>
                    @else
                      <span class="text-muted" title="Read-Only" data-bs-toggle="tooltip">
                        <i class="icon-base ri ri-lock-line ri-18px"></i>
                      </span>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">
                  <i class="icon-base ri ri-inbox-line ri-24px mb-2 d-block"></i>
                  Belum ada mapel untuk kelompok ini.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  @empty
    <div class="card">
      <div class="card-body text-center py-5 text-muted">
        <i class="icon-base ri ri-error-warning-line ri-48px mb-3 d-block"></i>
        <p>Belum ada kelompok mapel untuk jenjang <strong>{{ $jenjangDipilih }}</strong>.<br>
          Tambahkan kelompok mapel melalui seeder atau database secara langsung.</p>
      </div>
    </div>
  @endforelse

  {{-- ═══ MODAL: Tambah / Edit Mapel ═══════════════════════════════════════ --}}
  <div class="modal fade" id="modal-mapel" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modal-mapel-title">Tambah Mata Pelajaran</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="mapel-id">
          <input type="hidden" id="mapel-jenjang" value="{{ $jenjangDipilih }}">

          <div class="mb-4">
            <label class="form-label">Kelompok Mata Pelajaran <span class="text-danger">*</span></label>
            <select id="mapel-kelompok" class="form-select" required>
              <option value="">— Pilih Kelompok —</option>
              @foreach ($kelompokList as $kelompok)
                <option value="{{ $kelompok->id }}">{{ $kelompok->nama }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-4">
            <label class="form-label">Nama Mata Pelajaran <span class="text-danger">*</span></label>
            <input type="text" id="mapel-nama" class="form-control" placeholder="contoh: Pendidikan Agama Islam"
              required>
          </div>

          <div class="mb-2">
            <label class="form-label">
              Sub Nama
              <small class="text-muted">(opsional)</small>
            </label>
            <input type="text" id="mapel-sub" class="form-control" placeholder="contoh: a. Budaya Komering">
            <div class="form-text">Dipakai jika mapel punya sub-item di dokumen SKHU.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-primary" id="btn-simpan-mapel">
            <i class="icon-base ri ri-save-line me-1"></i>Simpan
          </button>
        </div>
      </div>
    </div>
  </div>

  {{-- ═══ MODAL: Salin Tahun Pelajaran (Hanya Admin) ══════════════════════ --}}
  @if ($isAdmin)
    <div class="modal fade" id="modal-salin" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Salin Mapel ke Semester Lain</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="alert alert-info d-flex gap-2 mb-4">
              <i class="icon-base ri ri-information-line mt-1"></i>
              <span>Semua mapel pokok dari semester sumber akan diduplikat ke semester tujuan.
                Mapel muatan lokal (tambahan) <strong>tidak</strong> ikut disalin.</span>
            </div>

            <div class="mb-4">
              <label class="form-label">Jenjang</label>
              <input type="text" class="form-control" value="{{ $jenjangDipilih }}" readonly>
              <input type="hidden" id="salin-jenjang" value="{{ $jenjangDipilih }}">
            </div>

            <div class="mb-2">
              <label class="form-label">Ke Semester <span class="text-danger">*</span></label>
              <input type="text" class="form-control"
                value="{{ $semester->tahun_pelajaran }} — Semester {{ $semester->semester }}" readonly>
              <input type="hidden" id="salin-ke" value="{{ $semester->id }}">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-info text-white" id="btn-salin">
              <i class="icon-base ri ri-file-copy-line me-1"></i>Salin Sekarang
            </button>
          </div>
        </div>
      </div>
    </div>
  @endif

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;

      // Deteksi Base URL dinamis berdasarkan role user
      const isOperator = @json($isOperator);
      const baseUrl = '/master/mapel';

      // ── Helper: kirim fetch ───────────────────────────────────────────────
      async function apiFetch(url, method = 'POST', body = null) {
        const opts = {
          method,
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
        };
        if (body) opts.body = JSON.stringify(body);
        return await fetch(url, opts);
      }

      function toastSukses(msg) {
        Swal.fire({
          icon: 'success',
          title: 'Berhasil',
          text: msg,
          timer: 2000,
          showConfirmButton: false
        });
      }

      function toastError(msg) {
        Swal.fire({
          icon: 'error',
          title: 'Gagal',
          text: msg
        });
      }

      // ── Buka modal Tambah ─────────────────────────────────────────────────
      document.getElementById('btn-tambah-mapel')?.addEventListener('click', function() {
        document.getElementById('modal-mapel-title').textContent = isOperator ? 'Tambah Mata Pelajaran Lokal' :
          'Tambah Mata Pelajaran';
        document.getElementById('mapel-id').value = '';
        document.getElementById('mapel-kelompok').value = '';
        document.getElementById('mapel-nama').value = '';
        document.getElementById('mapel-sub').value = '';
        document.getElementById('btn-simpan-mapel').dataset.mode = 'tambah';
      });

      // ── Buka modal Edit ───────────────────────────────────────────────────
      document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
          document.getElementById('modal-mapel-title').textContent = isOperator ?
            'Edit Mata Pelajaran Lokal' : 'Edit Mata Pelajaran';
          document.getElementById('mapel-id').value = this.dataset.id;
          document.getElementById('mapel-kelompok').value = this.dataset.kelompok;
          document.getElementById('mapel-nama').value = this.dataset.nama;
          document.getElementById('mapel-sub').value = this.dataset.sub ?? '';
          document.getElementById('btn-simpan-mapel').dataset.mode = 'edit';

          const modal = new bootstrap.Modal(document.getElementById('modal-mapel'));
          modal.show();
        });
      });

      // ── Simpan (tambah / edit) ────────────────────────────────────────────
      document.getElementById('btn-simpan-mapel')?.addEventListener('click', async function() {
        const mode = this.dataset.mode ?? 'tambah';
        const mapelId = document.getElementById('mapel-id').value;
        const kelompok = document.getElementById('mapel-kelompok').value;
        const nama = document.getElementById('mapel-nama').value.trim();
        const sub = document.getElementById('mapel-sub').value.trim();
        const jenjang = document.getElementById('mapel-jenjang').value;

        if (!kelompok || !nama) {
          toastError('Kelompok dan nama mata pelajaran wajib diisi.');
          return;
        }

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

        try {
          let res;
          if (mode === 'tambah') {
            res = await apiFetch(baseUrl, 'POST', {
              kelompok_mapel_id: kelompok,
              nama_mapel: nama,
              nama_mapel_sub: sub || null,
              jenjang,
            });
          } else {
            res = await apiFetch(`${baseUrl}/${mapelId}`, 'PUT', {
              kelompok_mapel_id: kelompok,
              nama_mapel: nama,
              nama_mapel_sub: sub || null,
            });
          }

          const data = await res.json();
          if (res.ok) {
            toastSukses(data.message);
            bootstrap.Modal.getInstance(document.getElementById('modal-mapel')).hide();
            setTimeout(() => location.reload(), 1500);
          } else {
            throw new Error(data.message ?? 'Gagal menyimpan.');
          }
        } catch (err) {
          toastError(err.message);
        } finally {
          this.disabled = false;
          this.innerHTML = '<i class="icon-base ri ri-save-line me-1"></i>Simpan';
        }
      });

      // ── Hapus ─────────────────────────────────────────────────────────────
      document.querySelectorAll('.btn-hapus').forEach(btn => {
        btn.addEventListener('click', async function() {
          const id = this.dataset.id;
          const nama = this.dataset.nama;

          const konfirm = await Swal.fire({
            icon: 'warning',
            title: 'Hapus Mata Pelajaran?',
            html: `Mata pelajaran <strong>${nama}</strong> akan dihapus permanen.<br>
                   <small class="text-muted">Data nilai SKHU yang sudah ada akan ikut terhapus.</small>`,
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            customClass: {
              confirmButton: 'btn btn-danger me-3',
              cancelButton: 'btn btn-outline-secondary',
            },
            buttonsStyling: false,
          });

          if (!konfirm.isConfirmed) return;

          try {
            const res = await apiFetch(`${baseUrl}/${id}`, 'DELETE');
            const data = await res.json();
            if (res.ok) {
              toastSukses(data.message);
              setTimeout(() => location.reload(), 1500);
            } else {
              throw new Error(data.message);
            }
          } catch (err) {
            toastError(err.message);
          }
        });
      });

      // ── Ubah urutan: naik ─────────────────────────────────────────────────
      document.querySelectorAll('.btn-urutan-naik').forEach(btn => {
        btn.addEventListener('click', async function() {
          const id = this.dataset.id;
          this.disabled = true;
          try {
            const res = await apiFetch(`${baseUrl}/${id}/urutan-naik`, 'POST');
            const data = await res.json();
            if (res.ok) {
              location.reload();
            } else {
              throw new Error(data.message);
            }
          } catch (err) {
            toastError(err.message);
            this.disabled = false;
          }
        });
      });

      // ── Ubah urutan: turun ────────────────────────────────────────────────
      document.querySelectorAll('.btn-urutan-turun').forEach(btn => {
        btn.addEventListener('click', async function() {
          const id = this.dataset.id;
          this.disabled = true;
          try {
            const res = await apiFetch(`${baseUrl}/${id}/urutan-turun`, 'POST');
            const data = await res.json();
            if (res.ok) {
              location.reload();
            } else {
              throw new Error(data.message);
            }
          } catch (err) {
            toastError(err.message);
            this.disabled = false;
          }
        });
      });

      // ── Salin Tahun Pelajaran ─────────────────────────────────────────────
      document.getElementById('btn-salin')?.addEventListener('click', async function() {
        const jenjang = document.getElementById('salin-jenjang').value;
        const dariTahun = document.getElementById('salin-dari').value;
        const keTahun = document.getElementById('salin-ke').value.trim();

        if (!keTahun) {
          toastError('Tahun pelajaran tujuan wajib diisi.');
          return;
        }

        if (!/^\d{4}\/\d{4}$/.test(keTahun)) {
          toastError('Format tahun pelajaran tidak valid. Gunakan format YYYY/YYYY, contoh: 2025/2026.');
          return;
        }

        if (dariTahun === keTahun) {
          toastError('Tahun pelajaran sumber dan tujuan tidak boleh sama.');
          return;
        }

        const konfirm = await Swal.fire({
          icon: 'question',
          title: 'Salin Mapel?',
          html: `Semua mapel pokok <strong>${jenjang}</strong> dari <strong>${dariTahun}</strong>
                 akan disalin ke <strong>${keTahun}</strong>.`,
          showCancelButton: true,
          confirmButtonText: 'Ya, Salin',
          cancelButtonText: 'Batal',
          customClass: {
            confirmButton: 'btn btn-info text-white me-3',
            cancelButton: 'btn btn-outline-secondary',
          },
          buttonsStyling: false,
        });

        if (!konfirm.isConfirmed) return;

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyalin...';

        try {
          const res = await apiFetch('/master/mapel/salin', 'POST', {
            jenjang,
            dari_tahun: dariTahun,
            ke_tahun: keTahun,
          });
          const data = await res.json();

          if (res.ok) {
            toastSukses(data.message);
            bootstrap.Modal.getInstance(document.getElementById('modal-salin')).hide();
            setTimeout(() => {
              window.location.href =
                `/master/mapel?jenjang=${jenjang}&tahun_pelajaran=${encodeURIComponent(keTahun)}`;
            }, 1500);
          } else {
            throw new Error(data.message);
          }
        } catch (err) {
          toastError(err.message);
        } finally {
          this.disabled = false;
          this.innerHTML = '<i class="icon-base ri ri-file-copy-line me-1"></i>Salin Sekarang';
        }
      });
    });
  </script>
@endsection
