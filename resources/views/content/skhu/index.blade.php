@extends('layouts/layoutMaster')

@section('title', 'SKHU - ' . $jenjang)

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

@section('content')

  {{-- Info header --}}
  <div class="row mb-4 g-4">
    <div class="col-12 col-md-8">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-4 py-4">
          <div class="avatar avatar-xl flex-shrink-0">
            <span class="avatar-initial rounded-circle bg-label-primary">
              <i class="icon-base ri ri-file-text-line ri-28px"></i>
            </span>
          </div>
          <div>
            <h4 class="mb-1">SKHU — {{ $sekolah->detailTerbaru->nama_sekolah ?? '-' }}</h4>
            <div class="d-flex gap-4 flex-wrap">
              <span class="text-muted"><i class="icon-base ri ri-stack-line me-1"></i>Kelas {{ $tingkatAkhir }}
                ({{ $jenjang }})</span>
              <span class="text-muted"><i
                  class="icon-base ri ri-calendar-line me-1"></i>{{ $semester->tahun_pelajaran }}</span>
              <span class="text-muted"><i class="icon-base ri ri-group-line me-1"></i>{{ $siswaAkhir->count() }}
                Siswa</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card h-100">
        <div class="card-body py-4">
          <h6 class="mb-3">Status Template</h6>
          @if ($template && $template->templateAda())
            <div class="d-flex align-items-center gap-2 text-success mb-2">
              <i class="icon-base ri ri-checkbox-circle-line"></i>
              <span>Template tersedia</span>
            </div>
            <small class="text-muted">{{ $template->keterangan }}</small>
          @else
            <div class="d-flex align-items-center gap-2 text-danger mb-2">
              <i class="icon-base ri ri-error-warning-line"></i>
              <span>Template belum tersedia</span>
            </div>
            <small class="text-muted">Hubungi administrator untuk membuat template.</small>
          @endif
          @hasanyrole('Admin')
            <div class="mt-2">
              <a href="{{ url('/skhu/template') }}" class="btn btn-label-primary btn-sm">
                <i class="icon-base ri ri-layout-line me-1"></i>Kelola Template
              </a>
            </div>
          @endhasanyrole
        </div>
      </div>
    </div>
  </div>

  {{-- Toolbar --}}
  <div class="card mb-4">
    <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div class="d-flex gap-2 flex-wrap">
        {{-- Tombol Mapel (buka modal daftar mapel) --}}
        <a href="{{ route('mapel-mapping.index') }}" class="btn btn-label-primary btn-sm">
          <i class="icon-base ri ri-book-2-line me-1"></i>Mapel
        </a>
      </div>
      <div class="d-flex gap-2">
        {{-- Import Nilai --}}
        @hasanyrole('Admin|Operator Sekolah')
          <button class="btn btn-label-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#modal-import">
            <i class="icon-base ri ri-upload-2-line me-1"></i><span class="d-none d-md-block">Import Nilai</span>
          </button>
        @endhasanyrole
        {{-- Pengaturan Angkatan (tanggal & nomor massal) --}}
        @hasanyrole('Operator Sekolah')
          <a href="{{ url('/skhu/pengaturan-angkatan') }}" class="btn btn-label-info btn-sm">
            <i class="icon-base ri ri-calendar-settings-line me-1"></i><span class="d-none d-md-block">Pengaturan
              Angkatan</span>
          </a>
        @endhasanyrole
        {{-- Finalisasi Semua --}}
        @hasanyrole('Admin|Operator Sekolah')
          <button class="btn btn-warning btn-sm" id="btn-finalisasi-semua">
            <i class="icon-base ri ri-checkbox-circle-line me-1"></i><span class="d-none d-md-block">Finalisasi Semua</span>
          </button>
        @endhasanyrole
        {{-- Download Bulk --}}
        <button class="btn btn-success btn-sm" id="btn-download-bulk">
          <i class="ri ri-download-2-line me-1"></i><span class="d-none d-md-block">Download Semua PDF</span>
        </button>
      </div>
    </div>
  </div>

  {{-- Tabel Siswa --}}
  <div class="card">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">Daftar Siswa Kelas {{ $tingkatAkhir }}</h5>
    </div>

    <div class="card-datatable text-nowrap">
      <table class="table table-bordered table-hover" id="tabel-skhu">
        <thead>
          <tr>
            <th>#</th>
            <th>Nama Siswa</th>
            <th>Rombel</th>
            <th>Status SKHU</th>
            <th>Nomor Surat</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($siswaAkhir as $i => $anggota)
            @php
              $siswa = $anggota->siswa;
              $skhu = $siswa->skhu->first();
            @endphp
            <tr>
              <td></td>
              <td>
                <div class="d-flex align-items-center">
                  <!-- Kontainer Foto -->
                  <div class="avatar avatar-sm me-3">
                    @if ($siswa->photo)
                      <img src="{{ asset('storage/' . $siswa->photo) }}" class="rounded-circle"
                        style="object-fit: cover; width:32px; height:32px;">
                    @else
                      @php
                        // Pecah nama menjadi array berdasarkan spasi
                        $words = explode(' ', $siswa->nama_siswa);
                        $firstLetter = substr($words[0], 0, 1); // Ambil huruf pertama dari kata pertama
                        $lastLetter = count($words) > 1 ? substr(end($words), 0, 1) : ''; // Ambil huruf pertama dari kata terakhir (jika ada lebih dari 1 kata)

                        $initials = strtoupper($firstLetter . $lastLetter);
                        $color = $siswa->jenis_kelamin == 'L' ? 'primary' : 'danger';
                      @endphp
                      <span class="avatar-initial rounded-circle bg-label-{{ $color }}">{{ $initials }}</span>
                    @endif
                  </div>
                  <!-- Nama dan NISN -->
                  <div>
                    <span class="fw-medium d-block text-heading">{{ $siswa->nama_siswa }}</span>
                    <small class="text-muted">{{ $siswa->nisn }}</small>
                  </div>
                </div>
              </td>
              <td>{{ $anggota->rombel->nama_rombel ?? '-' }}</td>
              <td>
                @if ($skhu)
                  <div class="form-check form-switch d-flex align-items-center gap-2 mb-0">
                    <input type="checkbox" class="form-check-input btn-toggle-status" role="switch"
                      id="statusToggle{{ $skhu->id }}" data-skhu-id="{{ $skhu->id }}"
                      data-status="{{ $skhu->status }}" {{ $skhu->status === 'final' ? 'checked' : '' }}
                      style="cursor:pointer;">
                    <label class="form-check-label status-label" for="statusToggle{{ $skhu->id }}"
                      style="cursor:pointer;">
                      <span
                        class="badge rounded-pill {{ $skhu->status === 'final' ? 'bg-label-success' : 'bg-label-warning' }}">
                        {{ $skhu->status === 'final' ? 'Final' : 'Draft' }}
                      </span>
                    </label>
                  </div>
                @else
                  <span class="badge bg-label-secondary">Belum diisi</span>
                @endif
              </td>
              <td>{{ $skhu?->nomor_surat ?? '-' }}</td>
              <td>
                <div class="d-flex gap-2">
                  <a href="{{ url('/skhu/siswa/' . $siswa->id) }}"
                    class="btn btn-sm btn-icon btn-text-primary rounded-pill"
                    title="{{ $skhu ? 'Edit SKHU' : 'Input SKHU' }}">
                    <i class="icon-base ri ri-edit-line icon-18px"></i>
                  </a>
                  @if ($skhu)
                    <button class="btn btn-sm btn-icon btn-text-success rounded-pill btn-download-pdf"
                      data-skhu-id="{{ $skhu->id }}" data-nama="{{ $siswa->nama_siswa }}" title="Download PDF">
                      <i class="icon-base ri ri-download-line icon-18px"></i>
                    </button>
                    <button class="btn btn-sm btn-icon btn-text-success rounded-pill btn-view-pdf"
                      data-skhu-id="{{ $skhu->id }}" data-nama="{{ $siswa->nama_siswa }}" title="View PDF">
                      <i class="icon-base ri ri-eye-line icon-18px"></i>
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

  </div>

  {{-- ════════════════════════════════════════════════════════════════════════
       MODAL: Import Nilai SKHU
  ═══════════════════════════════════════════════════════════════════════════ --}}
  @hasanyrole('Admin|Operator Sekolah')
    <div class="modal fade" id="modal-import" tabindex="-1" data-bs-backdrop="static">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

          <div class="modal-header">
            <h5 class="modal-title"><i class="icon-base ri ri-upload-2-line me-1"></i>Import Nilai SKHU</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body">

            <div class="alert alert-info d-flex gap-2 py-2 mb-4">
              <i class="icon-base ri ri-information-line mt-1 flex-shrink-0"></i>
              <div class="small">
                <strong>Cara import:</strong>
                Download template → isi kolom nilai &amp; nomor surat/ujian → upload kembali.<br>
                Kolom <em>No, Nama Siswa, NISN, Rombel</em> tidak perlu diubah (terkunci).<br>
                Nilai kosong tidak menghapus data lama. Jika sudah ada data SKHU, nilai <strong>ditimpa</strong>.
              </div>
            </div>

            {{-- Download Template --}}
            <div class="mb-6">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                  <div class="fw-semibold mb-1">
                    <i class="icon-base ri ri-file-excel-2-line me-2"></i>Step 1 — Download Template Excel
                  </div>
                  <small class="text-muted">
                    Template berisi siswa kelas {{ $tingkatAkhir }} + kolom nilai per mapel + kolom nomor surat/ujian.
                  </small>
                </div>
                <button type="button" class="btn btn-outline-success btn-sm" id="btn-download-template">
                  <i class="icon-base ri ri-download-2-line me-1"></i>Download Template
                </button>
              </div>
            </div>

            {{-- Tanggal --}}
            <div class="mb-6">
              <div class="fw-semibold mb-2">
                <i class="icon-base ri ri-calendar-line me-2"></i>Step 2 — Isi Tanggal
                <small class="text-muted fw-normal">(akan diset ke semua SKHU yang diimport)</small>
              </div>
              <div class="row g-3">
                <div class="col-12 col-md-4">
                  <label class="form-label form-label-sm">Tanggal Mulai Ujian</label>
                  <input type="text" id="import-tgl-mulai" class="form-control form-control-sm"
                    placeholder="YYYY-MM-DD" autocomplete="off">
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label form-label-sm">Tanggal Selesai Ujian</label>
                  <input type="text" id="import-tgl-selesai" class="form-control form-control-sm"
                    placeholder="YYYY-MM-DD" autocomplete="off">
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label form-label-sm">Tanggal Terbit SKHU</label>
                  <input type="text" id="import-tgl-terbit" class="form-control form-control-sm"
                    placeholder="YYYY-MM-DD" autocomplete="off">
                </div>
              </div>
            </div>

            {{-- Upload --}}
            <div class="mb-3">
              <div class="fw-semibold mb-2">
                <i class="icon-base ri ri-file-upload-line me-2"></i>Step 3 — Upload File Excel
              </div>
              <div class="upload-area-import" id="upload-area-import">
                <input type="file" id="import-file-excel" accept=".xlsx,.xls" class="d-none">
                <div id="import-placeholder" class="text-center py-3">
                  <i class="icon-base ri ri-file-excel-2-line ri-28px text-success mb-1"></i>
                  <p class="mb-0 small fw-semibold">Klik atau drag file ke sini</p>
                  <small class="text-muted">.xlsx / .xls — Maks. 10 MB</small>
                </div>
                <div id="import-preview" class="d-none d-flex align-items-center gap-3 py-2 px-3">
                  <i class="icon-base ri ri-file-excel-2-line ri-24px text-success flex-shrink-0"></i>
                  <div class="flex-grow-1 overflow-hidden">
                    <div id="import-filename" class="fw-semibold text-truncate"></div>
                    <small id="import-filesize" class="text-muted"></small>
                  </div>
                  <button type="button" class="btn btn-sm btn-icon btn-text-danger flex-shrink-0" id="btn-hapus-import">
                    <i class="icon-base ri ri-close-line"></i>
                  </button>
                </div>
              </div>
            </div>

            {{-- Panel hasil --}}
            <div id="import-hasil" class="d-none">
              <hr>
              <div id="import-sukses" class="d-none">
                <div class="alert alert-success py-2">
                  <i class="icon-base ri ri-checkbox-circle-line me-1"></i>
                  <span id="import-sukses-msg"></span>
                </div>
                <small class="text-muted">Status SKHU diset ke <strong>Draft</strong>. Lengkapi dan finalisasi di form per
                  siswa.</small>
              </div>
              <div id="import-error" class="d-none">
                <div class="alert alert-danger py-2">
                  <i class="icon-base ri ri-error-warning-line me-1"></i>
                  <span id="import-error-msg"></span>
                </div>
                <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
                  <table class="table table-sm table-bordered mb-0">
                    <thead class="table-danger sticky-top">
                      <tr>
                        <th width="55">Baris</th>
                        <th width="120">Kolom</th>
                        <th width="100">Nilai</th>
                        <th>Pesan</th>
                      </tr>
                    </thead>
                    <tbody id="import-tbody-error"></tbody>
                  </table>
                </div>
              </div>
            </div>

          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            <button type="button" class="btn btn-primary" id="btn-proses-import" disabled>
              <i class="icon-base ri ri-upload-2-line me-1"></i>Proses Import
            </button>
          </div>

        </div>
      </div>
    </div>

    <style>
      .upload-area-import {
        border: 2px dashed #cbd5e1;
        border-radius: .6rem;
        cursor: pointer;
        transition: border-color .2s, background .2s;
      }

      .upload-area-import:hover,
      .upload-area-import.dragover {
        border-color: var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), .04);
      }

      .form-check-input.btn-toggle-status:checked {
        background-color: #71dd37;
        /* hijau, samakan dengan warna success template Anda */
        border-color: #71dd37;
      }
    </style>
  @endhasanyrole

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;

      // ── Helper fetch ────────────────────────────────────────────────────────
      async function apiFetch(url, method = 'POST', body = null) {
        const opts = {
          method,
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
        };
        if (body) opts.body = JSON.stringify(body);
        return fetch(url, opts);
      }

      // ── DataTable ───────────────────────────────────────────────────────────
      new DataTable('#tabel-skhu', {
        scrollX: true, // Menambahkan scroll horizontal bawaan DataTables
        order: [
          [1, 'asc']
        ],
        columnDefs: [{
          targets: 0,
          orderable: false,
          searchable: false,
        }],
        drawCallback: function() {
          const api = this.api();
          const start = api.page.info().start;
          api.rows({
            page: 'current'
          }).nodes().each(function(row, i) {
            row.cells[0].innerHTML = start + i + 1;
          });
        },
        layout: {
          topStart: {
            features: [{
              pageLength: {
                menu: [10, 25, 50],
                text: '_MENU_'
              }
            }],
          },
          topEnd: {
            rowClass: 'row ms-1 me-2 my-0 py-0 justify-content-between',
            features: [{
              search: {
                text: '',
                placeholder: 'Cari siswa...'
              }
            }],
          },
          bottomStart: {
            rowClass: 'row mx-3 justify-content-between',
            features: [{
              info: {
                text: '_END_ dari _TOTAL_ siswa'
              }
            }],
          },
          bottomEnd: 'paging',
        },
      });

      // ── Toggle Status per Siswa ────────────────────────────────────────────

      function renderToggle(el) {
        const status = el.dataset.status;
        const label = el.closest('.form-check').querySelector('.status-label .badge');

        if (status === 'final') {
          el.checked = true;
          label.className = 'badge rounded-pill bg-label-success';
          label.textContent = 'Final';
          el.closest('.form-check').querySelector('label').title = 'Klik untuk batalkan finalisasi';
        } else {
          el.checked = false;
          label.className = 'badge rounded-pill bg-label-warning';
          label.textContent = 'Draft';
          el.closest('.form-check').querySelector('label').title = 'Klik untuk finalisasi';
        }
      }

      // Render semua badge saat load
      // document.querySelectorAll('.btn-toggle-status').forEach(el => renderBadge(el));

      //toogle Finalisasi SKHU
      // Handler perubahan toggle
      document.addEventListener('change', async function(e) {
        const el = e.target.closest('.btn-toggle-status');
        if (!el) return;

        const skhuId = el.dataset.skhuId;
        const statusSaat = el.dataset.status;
        const isDraft = statusSaat === 'draft';

        const konfirm = await Swal.fire({
          icon: 'question',
          title: isDraft ? 'Finalisasi SKHU?' : 'Batalkan Finalisasi?',
          text: isDraft ?
            'SKHU akan dikunci dan tampil di pencarian.' : 'Status SKHU akan dikembalikan ke Draft.',
          showCancelButton: true,
          confirmButtonText: isDraft ? 'Ya, Finalisasi' : 'Ya, Batalkan',
          cancelButtonText: 'Batal',
          customClass: {
            confirmButton: `btn ${isDraft ? 'btn-warning' : 'btn-danger'} me-3`,
            cancelButton: 'btn btn-outline-secondary'
          },
          buttonsStyling: false,
        });

        if (!konfirm.isConfirmed) {
          // Batal → kembalikan toggle ke posisi semula
          el.checked = !el.checked;
          return;
        }

        // Optimistic UI
        el.dataset.status = isDraft ? 'final' : 'draft';
        renderToggle(el);

        try {
          const url = `/skhu/siswa/${skhuId}/updateStatus`;

          const res = await fetch(url, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json',
              'Content-Type': 'application/json',
            },
          });
          const data = await res.json();

          if (!res.ok) {
            if (Array.isArray(data.errors) && data.errors.length > 0) {
              const listHtml = `<ul style="text-align:left; font-size: 16px; margin:0; padding-left:2rem;">${
          data.errors.map(err => `<li>${err}</li>`).join('')
        }</ul>`;
              throw {
                html: `<div style="margin-bottom: 1rem; font-size: 20 px;">${data.message ?? 'SKHU belum bisa difinalisasi'}</div>
                        ${listHtml}`,
                title: 'Gagal'
              };
            }
            throw new Error(data.message ?? 'Gagal mengubah status.');
          }

          Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: data.message,
            timer: 2000,
            showConfirmButton: false,
          });

        } catch (err) {
          // Rollback jika gagal
          el.dataset.status = statusSaat;
          renderToggle(el);

          if (err && err.html) {
            Swal.fire({
              icon: 'error',
              title: err.title,
              html: err.html
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Gagal',
              text: err.message
            });
          }
        }
      });

      // ── Download PDF per siswa ──────────────────────────────────────────────
      document.querySelectorAll('.btn-download-pdf').forEach(btn => {
        btn.addEventListener('click', function() {
          window.open(`/skhu/pdf/${this.dataset.skhuId}`, '_blank');
        });
      });

      document.querySelectorAll('.btn-view-pdf').forEach(btn => {
        btn.addEventListener('click', function() {
          window.open(`/skhu/view/${this.dataset.skhuId}`, '_blank');
        });
      });

      // ── Download Bulk ───────────────────────────────────────────────────────
      document.getElementById('btn-download-bulk')?.addEventListener('click', async function() {
        const konfirm = await Swal.fire({
          icon: 'question',
          title: 'Download Semua SKHU?',
          text: 'Semua SKHU yang sudah memiliki data nilai akan didownload dalam format ZIP.',
          showCancelButton: true,
          confirmButtonText: 'Ya, Download',
          cancelButtonText: 'Batal',
          customClass: {
            confirmButton: 'btn btn-success me-3',
            cancelButton: 'btn btn-outline-secondary'
          },
          buttonsStyling: false,
        });
        if (!konfirm.isConfirmed) return;
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';
        window.open('/skhu/download-bulk', '_blank');
        setTimeout(() => {
          this.disabled = false;
          this.innerHTML = '<i class="ri-download-2-line me-1"></i>Download Semua PDF';
        }, 3000);
      });

      // ══════════════════════════════════════════════════════════════════════════
      // IMPORT NILAI
      // ══════════════════════════════════════════════════════════════════════════

      // Init flatpickr SETELAH modal ditampilkan agar appendTo bisa pakai body
      document.getElementById('modal-import')?.addEventListener('shown.bs.modal', function() {
        const opts = {
          dateFormat: 'Y-m-d',
          allowInput: true,
          appendTo: document.body,
          static: true,
        };
        ['import-tgl-mulai', 'import-tgl-selesai', 'import-tgl-terbit'].forEach(id => {
          const el = document.getElementById(id);
          if (el && !el._flatpickr) flatpickr(el, opts);
        });
      });

      // Reset modal import saat ditutup
      document.getElementById('modal-import')?.addEventListener('hidden.bs.modal', () => {
        document.getElementById('import-file-excel').value = '';
        document.getElementById('import-placeholder').classList.remove('d-none');
        document.getElementById('import-preview').classList.add('d-none');
        document.getElementById('import-hasil').classList.add('d-none');
        document.getElementById('import-sukses').classList.add('d-none');
        document.getElementById('import-error').classList.add('d-none');
        document.getElementById('btn-proses-import').disabled = true;
        ['import-tgl-mulai', 'import-tgl-selesai', 'import-tgl-terbit'].forEach(id => {
          const el = document.getElementById(id);
          if (el?._flatpickr) el._flatpickr.clear();
        });
      });

      // Download Template
      document.getElementById('btn-download-template')?.addEventListener('click', async function() {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
        try {
          const res = await fetch('/skhu/import/template', {
            headers: {
              'X-CSRF-TOKEN': csrf
            }
          });
          if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            throw new Error(data.message ?? 'Gagal generate template.');
          }
          const blob = await res.blob();
          const url = URL.createObjectURL(blob);
          const a = document.createElement('a');
          const cd = res.headers.get('Content-Disposition') ?? '';
          const filename = cd.match(/filename="?([^"]+)"?/)?.[1] ?? 'template-skhu.xlsx';
          a.href = url;
          a.download = filename;
          a.click();
          URL.revokeObjectURL(url);
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: err.message
          });
        } finally {
          this.disabled = false;
          this.innerHTML = '<i class="icon-base ri ri-download-2-line me-1"></i>Download Template';
        }
      });

      // Upload area
      const uploadArea = document.getElementById('upload-area-import');
      const fileInput = document.getElementById('import-file-excel');
      const placeholder = document.getElementById('import-placeholder');
      const preview = document.getElementById('import-preview');
      const btnHapus = document.getElementById('btn-hapus-import');
      const btnProses = document.getElementById('btn-proses-import');

      uploadArea?.addEventListener('click', e => {
        if (!e.target.closest('#btn-hapus-import')) fileInput.click();
      });
      uploadArea?.addEventListener('dragover', e => {
        e.preventDefault();
        uploadArea.classList.add('dragover');
      });
      uploadArea?.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
      uploadArea?.addEventListener('drop', e => {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        if (e.dataTransfer.files.length) setFile(e.dataTransfer.files[0]);
      });
      fileInput?.addEventListener('change', () => {
        if (fileInput.files.length) setFile(fileInput.files[0]);
      });
      btnHapus?.addEventListener('click', () => {
        fileInput.value = '';
        placeholder.classList.remove('d-none');
        preview.classList.add('d-none');
        btnProses.disabled = true;
      });

      function setFile(file) {
        document.getElementById('import-filename').textContent = file.name;
        document.getElementById('import-filesize').textContent = (file.size / 1024).toFixed(1) + ' KB';
        placeholder.classList.add('d-none');
        preview.classList.remove('d-none');
        btnProses.disabled = false;
        document.getElementById('import-hasil').classList.add('d-none');
        document.getElementById('import-sukses').classList.add('d-none');
        document.getElementById('import-error').classList.add('d-none');
      }

      // Proses Import
      btnProses?.addEventListener('click', async function() {
        if (!fileInput.files.length) return;

        const konfirm = await Swal.fire({
          icon: 'question',
          title: 'Mulai Import?',
          text: 'Sistem akan memvalidasi seluruh data terlebih dahulu. Jika ada error, tidak ada yang disimpan.',
          showCancelButton: true,
          confirmButtonText: 'Ya, Import',
          cancelButtonText: 'Batal',
          customClass: {
            confirmButton: 'btn btn-primary me-3',
            cancelButton: 'btn btn-outline-secondary'
          },
          buttonsStyling: false,
        });
        if (!konfirm.isConfirmed) return;

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';

        const formData = new FormData();
        formData.append('tanggal_mulai_ujian', document.getElementById('import-tgl-mulai').value);
        formData.append('tanggal_selesai_ujian', document.getElementById('import-tgl-selesai').value);
        formData.append('tanggal_terbit', document.getElementById('import-tgl-terbit').value);
        formData.append('file_excel', fileInput.files[0]);
        formData.append('_token', csrf);

        try {
          const res = await fetch('/skhu/import', {
            method: 'POST',
            body: formData
          });
          const data = await res.json();

          document.getElementById('import-hasil').classList.remove('d-none');
          document.getElementById('import-sukses').classList.add('d-none');
          document.getElementById('import-error').classList.add('d-none');

          if (res.ok) {
            document.getElementById('import-sukses').classList.remove('d-none');
            document.getElementById('import-sukses-msg').textContent = data.message;
            setTimeout(() => location.reload(), 2500);
          } else {
            document.getElementById('import-error').classList.remove('d-none');
            document.getElementById('import-error-msg').textContent = data.message;
            document.getElementById('import-tbody-error').innerHTML = (data.errors ?? []).map(e => `
          <tr>
            <td class="text-center">${e.baris}</td>
            <td>${e.kolom}</td>
            <td><code>${e.nilai}</code></td>
            <td class="text-danger small">${e.pesan}</td>
          </tr>
        `).join('');
          }
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: 'Gagal menghubungi server: ' + err.message
          });
        } finally {
          this.disabled = false;
          this.innerHTML = '<i class="icon-base ri ri-upload-2-line me-1"></i>Proses Import';
        }
      });

      // ── Finalisasi Semua ───────────────────────────────────────────────────
      document.getElementById('btn-finalisasi-semua')?.addEventListener('click', async function() {
        const konfirm = await Swal.fire({
          icon: 'warning',
          title: 'Finalisasi Semua SKHU?',
          html: 'SKHU berstatus final akan dikunci dan ditampilkan dilaman pencarian SKHU.<br><small class="text-muted">anda masih bisa mengeditnya dengan Batal Final</small>',
          showCancelButton: true,
          confirmButtonText: 'Ya, Finalisasi Semua',
          cancelButtonText: 'Batal',
          customClass: {
            confirmButton: 'btn btn-warning me-3',
            cancelButton: 'btn btn-outline-secondary'
          },
          buttonsStyling: false,
        });
        if (!konfirm.isConfirmed) return;

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';

        try {
          const res = await fetch('/skhu/finalisasi-semua', {
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
              timer: 2500,
              showConfirmButton: false
            });
            setTimeout(() => location.reload(), 2600);
          } else {
            throw new Error(data.message ?? 'Gagal finalisasi.');
          }
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: err.message
          });
        } finally {
          this.disabled = false;
          this.innerHTML = '<i class="icon-base ri ri-checkbox-circle-line me-1"></i>Finalisasi Semua';
        }
      });

    }); // end DOMContentLoaded
  </script>
@endsection
