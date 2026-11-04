@extends('layouts/layoutMaster')
@section('title', 'sekolah')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/datatables-rowgroup-bs5/rowgroup.bootstrap5.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-style')
  <style>
    .ts-dropdown {
      z-index: 2000 !important;
    }
  </style>
@endsection

@section('content')

  {{-- ── TABEL ──────────────────────────────────────────────────────────────── --}}
  <div class="card">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">Data Sekolah</h5>
    </div>
    <div class="card-datatable text-nowrap">
      <table class="datatables-basic table table-bordered" id="tabel-sekolah">
        <thead>
          <tr>
            <th>#</th>
            <th>Sekolah</th>
            <th>Jenjang</th>
            <th>Alamat</th>
            <th>Kepala Sekolah</th>
            <th>Semester</th>
            <th>Action</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

  {{-- ── MODAL FORM SEKOLAH ─────────────────────────────────────────────────── --}}
  <div class="modal fade" id="modal-form-sekolah" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-simple">
      <div class="modal-content">
        <div class="modal-body p-0">
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          <div class="text-center mb-6">
            <h4 class="mb-4" id="judul_form">Tambah Data Sekolah</h4>
          </div>

          <form id="form-sekolah" class="row g-5">
            {{-- NPSN --}}
            <div class="col-12 col-md-6 form-control-validation">
              <div class="form-floating form-floating-outline">
                <input type="text" inputmode="numeric" pattern="[0-9]*" id="npsn" name="npsn"
                  class="form-control" placeholder="12345678" maxlength="8" />
                <label for="npsn">NPSN</label>
              </div>
            </div>

            {{-- Jenjang --}}
            <div class="col-12 col-md-6 form-control-validation">
              <div class="form-floating form-floating-outline">
                <select id="jenjang" name="jenjang" class="form-select select2">
                  <option value="">Pilih Jenjang</option>
                  @foreach (['SD', 'SMP', 'SMA', 'SMK'] as $j)
                    <option value="{{ $j }}">{{ $j }}</option>
                  @endforeach
                </select>
                <label for="jenjang">Jenjang</label>
              </div>
            </div>

            {{-- Nama Sekolah --}}
            <div class="col-12 form-control-validation">
              <div class="form-floating form-floating-outline">
                <input type="text" id="nama_sekolah" name="nama_sekolah" class="form-control"
                  placeholder="SDN 01 Totorejo" />
                <label for="nama_sekolah">Nama Sekolah</label>
              </div>
            </div>

            <div class="fi-hr"></div>

            {{-- Cascade Alamat --}}
            @foreach ([['province', 'Provinsi', 'col-12 col-md-6'], ['regency', 'Kabupaten', 'col-12 col-md-6'], ['district', 'Kecamatan', 'col-12 col-md-6'], ['village', 'Desa/Kelurahan', 'col-12 col-md-6']] as [$id, $label, $col])
              <div class="{{ $col }}">
                <div class="form-floating form-floating-outline">
                  <select id="{{ $id }}" name="{{ $id }}" class="select2 form-select">
                    <option value="">Pilih {{ $label }}</option>
                    @if ($id === 'province')
                      @foreach ($provinces as $p)
                        <option value="{{ $p->code }}">{{ $p->name }}</option>
                      @endforeach
                    @endif
                  </select>
                  <label for="{{ $id }}">{{ $label }}</label>
                </div>
              </div>
            @endforeach

            {{-- Alamat Lengkap --}}
            <div class="col-12 form-control-validation">
              <div class="form-floating form-floating-outline">
                <input type="text" id="alamat_sekolah" name="alamat_sekolah" class="form-control"
                  placeholder="Jl. Raya No. 123" />
                <label for="alamat_sekolah">Alamat</label>
              </div>
            </div>

            <div class="fi-hr"></div>

            {{-- Kepala Sekolah & NIP --}}
            <div class="col-12 col-md-6 form-control-validation">
              <div class="form-floating form-floating-outline">
                <input type="text" id="kepala_sekolah" name="kepala_sekolah" class="form-control"
                  placeholder="Nama Kepala Sekolah" />
                <label for="kepala_sekolah">Kepala Sekolah</label>
              </div>
            </div>
            <div class="col-12 col-md-6 form-control-validation">
              <div class="form-floating form-floating-outline">
                <input type="text" id="nip" name="nip" class="form-control" placeholder="NIP" />
                <label for="nip">NIP</label>
              </div>
            </div>

            <div class="col-12 text-center gap-4">
              <button type="submit" id="submit" class="btn btn-primary me-sm-3 me-1">Submit</button>
              <button type="reset" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
          </form>

          {{-- Card Fitur (muncul saat Edit) --}}
          <div class="card mt-4 d-none" id="card-fitur-sekolah">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h5 class="mb-0">Fitur Aktif</h5>
              <small class="text-muted">Perubahan langsung tersimpan</small>
            </div>
            <div class="card-body">
              @foreach ($fiturs as $fitur)
                <div class="form-check form-switch mb-3">
                  <input class="form-check-input toggle-fitur" type="checkbox" id="fitur-{{ $fitur->kode }}"
                    data-sekolah="" data-fitur="{{ $fitur->id }}">
                  <label class="form-check-label" for="fitur-{{ $fitur->kode }}">
                    <strong>{{ $fitur->nama }}</strong>
                    <small class="text-muted d-block">{{ $fitur->deskripsi }}</small>
                  </label>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ── MODAL KELOLA FITUR ─────────────────────────────────────────────────── --}}
  <div class="modal fade" id="modal-kelola-fitur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
      <div class="modal-content">
        <div class="modal-header border-bottom">
          <div>
            <h5 class="modal-title mb-0">Kelola Fitur</h5>
            <small class="text-muted" id="nama-sekolah-fitur">-</small>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body py-4">
          @forelse ($fiturs as $fitur)
            <div
              class="d-flex align-items-start justify-content-between py-3 {{ !$loop->last ? 'border-bottom' : '' }}">
              <div class="me-3">
                <div class="fw-semibold">{{ $fitur->nama }}</div>
                <small class="text-muted">{{ $fitur->deskripsi }}</small>
              </div>
              <div class="form-check form-switch mt-1 flex-shrink-0">
                <input class="form-check-input toggle-fitur" type="checkbox" role="switch"
                  id="fitur-toggle-{{ $fitur->kode }}" data-sekolah="" data-fitur="{{ $fitur->id }}"
                  data-kode="{{ $fitur->kode }}">
              </div>
            </div>
          @empty
            <div class="text-center py-4 text-muted">
              <i class="ri-settings-line ri-48px mb-2 d-block"></i>
              Belum ada fitur yang tersedia.
            </div>
          @endforelse
        </div>

        <div class="modal-footer border-top">
          <small class="text-muted me-auto">
            <i class="ri-information-line me-1"></i>Perubahan langsung tersimpan
          </small>
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
        </div>
      </div>
    </div>
  </div>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {

      // ════════════════════════════════════════════════════════════════
      // HELPERS
      // ════════════════════════════════════════════════════════════════

      const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

      const swalErr = (text, title = 'Error') =>
        Swal.fire({
          icon: 'error',
          title,
          text,
          customClass: {
            confirmButton: 'btn btn-primary'
          }
        });

      /** Isi dropdown dari URL, pilih selectedValue jika ada */
      async function loadDropdown(url, id, selectedValue = null) {
        const el = document.getElementById(id);
        const data = await fetch(url).then(r => r.json());
        el.innerHTML = '<option value="">Pilih...</option>';
        data.forEach(({
          code: val,
          name
        }) => {
          const opt = new Option(name, val, false, val == selectedValue);
          el.appendChild(opt);
        });
        $(el).trigger('change');
      }

      /** Reset dropdown dan turunannya */
      function resetDropdowns(...ids) {
        ids.forEach(id => {
          const el = document.getElementById(id);
          if (el) {
            el.innerHTML = '<option value="">Pilih...</option>';
            $(el).val(null).trigger('change.select2');
          }
        });
      }

      // ════════════════════════════════════════════════════════════════
      // DATATABLE
      // ════════════════════════════════════════════════════════════════

      const tabelEl = document.getElementById('tabel-sekolah');
      if (tabelEl) {
        window.dtSekolah = new DataTable(tabelEl, {
          processing: true,
          serverSide: false,
          scrollx: true,
          deferRender: true,
          scroller: true,
          ajax: {
            url: '/sekolah/tabel',
            dataSrc: ''
          },
          columns: [{
              data: null
            },
            {
              data: 'nama_sekolah'
            },
            {
              data: 'jenjang'
            },
            {
              data: 'alamat_sekolah'
            },
            {
              data: 'kepala_sekolah'
            },
            {
              data: 'semester_id'
            },
            {
              data: null
            }
          ],
          columnDefs: [{
              targets: 0,
              render: (_, __, _r, meta) => meta.row + 1
            },
            {
              targets: 1,
              render: (_, __, full) =>
                `<div class="d-flex flex-column">
                <span class="text-truncate h6 mb-0">${full.nama_sekolah}</span>
                <small class="text-truncate">${full.npsn}</small>
             </div>`
            },
            {
              targets: -1,
              searchable: false,
              orderable: false,
              render: (_, __, full) => {
                const btnAkun = full.has_operator_account ?
                  `<button class="dropdown-item reset-password" data-id="${full.id}" data-type="sekolah" data-name="${full.nama_sekolah}">
                   <i class="icon-base ri ri-lock-password-line me-2"></i>Reset Password</button>` :
                  `<button class="dropdown-item generate-akun" data-id="${full.id}" data-name="${full.nama_sekolah}">
                   <i class="icon-base ri ri-user-add-line me-2"></i>Generate</button>`;

                return `<div class="d-flex align-items-center gap-4">
              <button class="btn btn-icon btn-text-secondary btn-sm rounded-pill edit-data"
                data-id="${full.id}" data-bs-toggle="modal" data-bs-target="#modal-form-sekolah">
                <i class="icon-base ri ri-edit-box-line icon-22px"></i></button>
              <button class="btn btn-icon btn-text-secondary btn-sm rounded-pill dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                <i class="icon-base ri ri-more-2-line icon-22px"></i></button>
              <div class="dropdown-menu dropdown-menu-end m-0">
                <button class="dropdown-item hapus-sekolah" data-id="${full.id}">
                  <i class="icon-base ri ri-delete-bin-7-line me-2"></i>Hapus</button>
                <button class="dropdown-item kelola-fitur" data-id="${full.id}" data-nama="${full.nama_sekolah}">
                  <i class="icon-base ri ri-settings-3-line me-2"></i>Kelola Fitur</button>
                ${btnAkun}
              </div>
            </div>`;
              }
            }
          ],
          layout: {
            topStart: {
              rowClass: 'row m-3 my-0',
              features: [{
                  pageLength: {
                    menu: [7, 10, 20, 50, 70, 100],
                    text: '_MENU_'
                  }
                },
                {
                  search: {
                    placeholder: 'Search',
                    text: '_INPUT_'
                  }
                }
              ]
            },
            topEnd: {
              features: [{
                buttons: [{
                  text: '<i class="icon-base ri ri-add-line icon-sm me-0 me-sm-2"></i><span class="d-none d-sm-inline-block">Tambah Sekolah</span>',
                  className: 'add-new btn btn-primary',
                  attr: {
                    'data-bs-toggle': 'modal',
                    'data-bs-target': '#modal-form-sekolah'
                  }
                }]
              }]
            },
            bottomStart: {
              rowClass: 'row mx-3 justify-content-between',
              features: [{
                info: {
                  text: '_END_ dari _TOTAL_ data'
                }
              }]
            },
            bottomEnd: 'paging'
          },
          initComplete: () => document.querySelectorAll('.dt-buttons .btn').forEach(b => b.classList.remove(
            'btn-secondary'))
        });
      }

      // ════════════════════════════════════════════════════════════════
      // SELECT2 & CASCADE ALAMAT
      // ════════════════════════════════════════════════════════════════

      const initSelect2 = () => {
        $('.select2').each(function() {
          $(this).select2({
            placeholder: '-- pilih --',
            dropdownParent: $(this).parent()
          });
        });
      };
      initSelect2();

      $('#province').on('change', function() {
        const id = $(this).val();
        id ? loadDropdown(`/get-kabupaten/${id}`, 'regency').then(() => resetDropdowns('district', 'village')) :
          resetDropdowns('regency', 'district', 'village');
      });
      $('#regency').on('change', function() {
        const id = $(this).val();
        id ? loadDropdown(`/get-kecamatan/${id}`, 'district').then(() => resetDropdowns('village')) :
          resetDropdowns('district', 'village');
      });
      $('#district').on('change', function() {
        const id = $(this).val();
        id ? loadDropdown(`/get-desa/${id}`, 'village') : resetDropdowns('village');
      });

      // ════════════════════════════════════════════════════════════════
      // FORM VALIDATION
      // ════════════════════════════════════════════════════════════════

      let saveMode = 'add',
        globalId = null;
      const formEl = document.getElementById('form-sekolah');
      const submitBtn = document.getElementById('submit');
      const judulForm = document.getElementById('judul_form');
      const modalEl = document.getElementById('modal-form-sekolah');

      const fv = FormValidation.formValidation(formEl, {
        fields: {
          npsn: {
            validators: {
              notEmpty: {
                message: 'NPSN tidak boleh kosong'
              },
              stringLength: {
                min: 8,
                max: 8,
                message: 'NPSN harus 8 karakter'
              },
              regexp: {
                regexp: /^[0-9]+$/,
                message: 'NPSN hanya boleh angka'
              }
            }
          },
          nama_sekolah: {
            validators: {
              notEmpty: {
                message: 'Nama sekolah harus diisi'
              }
            }
          },
          jenjang: {
            validators: {
              notEmpty: {
                message: 'Pilih jenjang sekolah'
              }
            }
          },
          alamat_sekolah: {
            validators: {
              notEmpty: {
                message: 'Alamat lengkap harus diisi'
              }
            }
          },
          kepala_sekolah: {
            validators: {
              notEmpty: {
                message: 'Kepala sekolah harus diisi'
              }
            }
          },
          nip: {
            validators: {
              regexp: {
                regexp: /^[0-9]+$/,
                message: 'NIP hanya boleh angka'
              }
            }
          }
        },
        plugins: {
          trigger: new FormValidation.plugins.Trigger(),
          bootstrap5: new FormValidation.plugins.Bootstrap5({
            eleValidClass: '',
            rowSelector: '.form-control-validation'
          }),
          submitButton: new FormValidation.plugins.SubmitButton(),
          autoFocus: new FormValidation.plugins.AutoFocus()
        }
      }).on('core.form.valid', async function() {
        const formData = new FormData(formEl);
        const url = saveMode === 'add' ? '/sekolah' : `/sekolah/${globalId}`;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';
        if (saveMode !== 'add') formData.append('_method', 'PUT');

        try {
          const res = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
              'X-CSRF-TOKEN': csrf(),
              'X-Requested-With': 'XMLHttpRequest'
            }
          });
          const data = await res.json();

          if (res.ok) {
            Swal.fire({
              icon: 'success',
              title: 'Berhasil!',
              text: data.message,
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
            $(modalEl).modal('hide');
            window.dtSekolah?.ajax.reload(null, saveMode === 'add');
          } else if (res.status === 422) {
            const errorList = Object.values(data.errors).map(e => `<li>${e[0]}</li>`).join('');
            Object.entries(data.errors).forEach(([key, msgs]) => {
              if (fv.fields[key]) {
                const firstValidator = Object.keys(fv.fields[key].validators)[0];
                fv.updateFieldStatus(key, 'Invalid', firstValidator);
                const container = formEl.querySelector(
                  `[data-field="${key}"][data-validator="${firstValidator}"]`);
                if (container) {
                  container.innerText = msgs[0];
                  container.style.display = 'block';
                }
              }
            });
            Swal.fire({
              icon: 'error',
              title: 'Gagal Validasi',
              html: `<ul style="text-align:left">${errorList}</ul>`,
              customClass: {
                confirmButton: 'btn btn-primary'
              }
            });
          } else {
            throw new Error(data.message || 'Terjadi kesalahan sistem');
          }
        } catch (err) {
          swalErr(err.message, 'Oops...');
        } finally {
          submitBtn.disabled = false;
          submitBtn.innerHTML = 'Submit';
        }
      });

      // ════════════════════════════════════════════════════════════════
      // EVENT DELEGATION (klik pada tabel & modal)
      // ════════════════════════════════════════════════════════════════

      document.addEventListener('click', async function(e) {

        // ── Tambah Baru ──────────────────────────────────────────────
        if (e.target.closest('.add-new')) {
          saveMode = 'add';
          formEl.reset();
          judulForm.innerText = 'Tambah Data Sekolah';
          $('.select2').val(null).trigger('change');
        }

        // ── Edit ─────────────────────────────────────────────────────
        const editBtn = e.target.closest('.edit-data');
        if (editBtn) {
          saveMode = 'edit';
          globalId = editBtn.dataset.id;
          judulForm.innerText = 'Edit Data Sekolah';
          Swal.showLoading();

          try {
            const data = await fetch(`/sekolah/${globalId}/edit`).then(r => r.json());
            Swal.close();

            ['npsn', 'nama_sekolah', 'alamat_sekolah', 'kepala_sekolah', 'nip'].forEach(id => {
              const el = document.getElementById(id);
              if (el) el.value = data[id] ?? '';
            });
            if (data.jenjang) $('#jenjang').val(data.jenjang).trigger('change');

            if (data.province) {
              $('#province').val(data.province).trigger('change');
              await loadDropdown(`/get-kabupaten/${data.province}`, 'regency', data.regency);
              if (data.regency) await loadDropdown(`/get-kecamatan/${data.regency}`, 'district', data.district);
              if (data.district) await loadDropdown(`/get-desa/${data.district}`, 'village', data.village);
            }

            const cardFitur = document.getElementById('card-fitur-sekolah');
            if (cardFitur) {
              cardFitur.classList.remove('d-none');
              cardFitur.querySelectorAll('.toggle-fitur').forEach(t => {
                t.dataset.sekolah = data.id;
                t.checked = data.fitur_aktif?.includes(t.id.replace('fitur-', '')) ?? false;
              });
            }
          } catch {
            swalErr('Gagal mengambil data');
          }
        }

        // ── Kelola Fitur ──────────────────────────────────────────────
        const kelolaBtn = e.target.closest('.kelola-fitur');
        if (kelolaBtn) {
          const sekolahId = kelolaBtn.dataset.id;
          document.getElementById('nama-sekolah-fitur').textContent = kelolaBtn.dataset.nama;

          const toggles = document.querySelectorAll('#modal-kelola-fitur .toggle-fitur');
          toggles.forEach(t => {
            t.dataset.sekolah = sekolahId;
            t.checked = false;
            t.disabled = true;
          });

          new bootstrap.Modal(document.getElementById('modal-kelola-fitur')).show();

          try {
            const data = await fetch(`/sekolah/${sekolahId}/fitur`, {
              headers: {
                Accept: 'application/json'
              }
            }).then(r => r.json());
            toggles.forEach(t => {
              t.checked = data.fitur_aktif?.includes(t.dataset.kode) ?? false;
              t.disabled = false;
            });
          } catch {
            swalErr('Gagal memuat fitur sekolah.', 'Gagal');
          }
        }

        // ── Hapus ────────────────────────────────────────────────────
        const hapusBtn = e.target.closest('.hapus-sekolah');
        if (hapusBtn) {
          const id = hapusBtn.dataset.id;
          const result = await Swal.fire({
            title: 'Apakah anda yakin?',
            text: 'Data yang dihapus tidak dapat dikembalikan!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            customClass: {
              confirmButton: 'btn btn-primary me-3',
              cancelButton: 'btn btn-outline-secondary'
            },
            buttonsStyling: false
          });
          if (result.isConfirmed) {
            try {
              const res = await fetch(`/sekolah/${id}`, {
                method: 'DELETE',
                headers: {
                  'X-CSRF-TOKEN': csrf()
                }
              });
              const data = await res.json();
              Swal.fire({
                icon: 'success',
                title: 'Terhapus!',
                text: data.message,
                customClass: {
                  confirmButton: 'btn btn-success'
                }
              });
              window.dtSekolah?.ajax.reload(null, false);
            } catch {
              swalErr('Gagal menghapus data');
            }
          }
        }

        // ── Generate Akun ────────────────────────────────────────────
        const generateBtn = e.target.closest('.generate-akun');
        if (generateBtn) {
          const {
            id,
            name: nama
          } = generateBtn.dataset;
          const result = await Swal.fire({
            title: 'Generate Akun',
            html: `Apakah anda akan membuatkan akun untuk sekolah <b>${nama}</b>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, buat akun',
            cancelButtonText: 'Batal',
            customClass: {
              confirmButton: 'btn btn-primary me-3',
              cancelButton: 'btn btn-outline-secondary'
            },
            buttonsStyling: false,
            showLoaderOnConfirm: true,
            allowOutsideClick: () => !Swal.isLoading(),
            preConfirm: async () => {
              try {
                const res = await fetch(`/sekolah/${id}/generate-akun`, {
                  method: 'POST',
                  headers: {
                    'X-CSRF-TOKEN': csrf(),
                    'Content-Type': 'application/json',
                    Accept: 'application/json'
                  }
                });
                if (!res.ok) {
                  const d = await res.json();
                  throw new Error(d.message || 'Gagal membuat akun');
                }
                return res.json();
              } catch (err) {
                Swal.showValidationMessage(`Request failed: ${err}`);
              }
            }
          });
          if (result.isConfirmed) {
            const d = result.value.data;
            Swal.fire({
              icon: 'success',
              title: 'Berhasil!',
              html: `<p>${result.value.message}</p>
                 <div class="text-center bg-white p-3 rounded border">
                   <p class="mb-1">Username: <code>${d.username}</code></p>
                   <p class="mb-0">Password: <code>${d.password}</code></p>
                 </div>
                 <small class="text-muted mt-2 d-block">Silakan berikan akun ini kepada operator sekolah.</small>`,
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
            window.dtSekolah?.ajax.reload(null, false);
          }
        }

        // ── Reset Password ────────────────────────────────────────────
        const resetBtn = e.target.closest('.reset-password');
        if (resetBtn) {
          const {
            id,
            name: nama
          } = resetBtn.dataset;
          const result = await Swal.fire({
            title: 'Reset Password?',
            html: `Password untuk <b>${nama}</b> akan dikembalikan ke default.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Reset!',
            cancelButtonText: 'Batal',
            customClass: {
              confirmButton: 'btn btn-danger me-3',
              cancelButton: 'btn btn-outline-secondary'
            },
            buttonsStyling: false,
            showLoaderOnConfirm: true,
            allowOutsideClick: () => !Swal.isLoading(),
            preConfirm: async () => {
              try {
                const res = await fetch(`/user/reset-password/${id}?type=sekolah`, {
                  method: 'POST',
                  headers: {
                    'X-CSRF-TOKEN': csrf(),
                    Accept: 'application/json'
                  }
                });
                if (!res.ok) {
                  const d = await res.json();
                  throw new Error(d.message || 'Gagal mereset password');
                }
                return res.json();
              } catch (err) {
                Swal.showValidationMessage(`Gagal: ${err.message}`);
              }
            }
          });
          if (result.isConfirmed) {
            const res = result.value;
            Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              html: `${res.message}<br>Password baru: <code>${res.data.password}</code>`,
              customClass: {
                confirmButton: 'btn btn-primary'
              }
            });
          }
        }
      });

      // ════════════════════════════════════════════════════════════════
      // TOGGLE FITUR
      // ════════════════════════════════════════════════════════════════

      document.getElementById('modal-kelola-fitur')?.addEventListener('change', async function(e) {
        const toggle = e.target.closest('.toggle-fitur');
        if (!toggle) return;

        const {
          sekolah: sekolahId,
          fitur: fiturId
        } = toggle.dataset;
        const aktif = toggle.checked;
        toggle.disabled = true;

        try {
          const res = await fetch('/sekolah/fitur/toggle', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf()
            },
            body: JSON.stringify({
              sekolah_id: sekolahId,
              fitur_id: fiturId,
              aktif
            })
          });
          if (!res.ok) {
            const d = await res.json();
            toggle.checked = !aktif;
            swalErr(d.message ?? 'Gagal memperbarui fitur.', 'Gagal');
          }
        } catch {
          toggle.checked = !aktif;
          swalErr('Terjadi kesalahan jaringan.');
        } finally {
          toggle.disabled = false;
        }
      });

      // ════════════════════════════════════════════════════════════════
      // RESET MODAL SAAT DITUTUP
      // ════════════════════════════════════════════════════════════════

      modalEl?.addEventListener('hide.bs.modal', () => {
        formEl.reset();
        fv.resetForm(true);
        $('.select2').val(null).trigger('change');
      });

      document.getElementById('modal-kelola-fitur')?.addEventListener('hidden.bs.modal', () => {
        document.querySelectorAll('#modal-kelola-fitur .toggle-fitur').forEach(t => {
          t.checked = false;
          t.disabled = false;
          t.dataset.sekolah = '';
        });
        document.getElementById('nama-sekolah-fitur').textContent = '-';
      });

    });
  </script>
@endsection
