@extends('layouts/layoutMaster')

@section('title', 'Data PTK')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/cleave-zen/cleave-zen.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('content')
  <div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">Manajemen Data PTK</h5>
      <button id="tambah-ptk" class="btn btn-primary btn-tambah" data-bs-toggle="offcanvas" data-bs-target="#offcanvasPtk">
        <i class="ri-add-line me-1"></i> Tambah PTK
      </button>
    </div>
    <div class="card-datatable text-nowrap">
      <table class="datatables-basic table table-bordered hover" id="tabel-ptk"
        data-role-admin="{{ auth()->user()->hasRole('Admin') ? 'true' : 'false' }}">
        <thead>
          <tr>
            <th>#</th>
            <th>Nama / NIP</th>
            <th>JK</th>
            <th>Jabatan</th>
            @hasrole('Admin')
              <th>Sekolah</th>
            @endhasrole
            <th>Action</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>

  <div class="offcanvas
        offcanvas-end" tabindex="-1" id="offcanvasPtk" aria-labelledby="offcanvasPtkLabel">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvas-label" class="offcanvas-title">Tambah PTK Baru</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 h-100">
      <form class="add-new-ptk pt-0" id="form-ptk">
        <input type="hidden" name="id" id="ptk-id">

        <div class="form-floating form-floating-outline mb-5">
          <input type="text" class="form-control" id="nama_ptk" name="nama_ptk" placeholder="Nama Lengkap" />
          <label for="nama_ptk">Nama Lengkap</label>
        </div>

        <div class="form-floating form-floating-outline mb-5">
          <input type="text" class="form-control" id="nip" name="nip" placeholder="NIP (18 Digit)"
            maxlength="18" />
          <label for="nip">NIP (Opsional)</label>
        </div>

        <div class="form-floating form-floating-outline mb-5">
          <select id="jenis_kelamin" name="jenis_kelamin" class="form-select">
            <option value="">Pilih Jenis Kelamin</option>
            <option value="L">Laki-laki</option>
            <option value="P">Perempuan</option>
          </select>
          <label for="jenis_kelamin">Jenis Kelamin</label>
        </div>

        <div class="form-floating form-floating-outline mb-5">
          <select id="jabatan" name="jabatan" class="form-select">
            <option value="">pilih jabatan</option>
            <option value="Kepala Sekolah">Kepala Sekolah</option>
            <option value="Operator Sekolah">Operator Sekolah</option>
            <option value="Guru">Guru</option>
            <option value="Tata Usaha">Tata Usaha</option>
            <option value="Pustakawan">Pustakawan</option>
          </select>
          <label for="jabatan">Jabatan</label>
        </div>

        <div class="form-floating form-floating-outline mb-5">
          <select id="jenis_kepegawaian" name="jenis_kepegawaian" class="form-select">
            <option value="">pilih jenis kepegawaian</option>
            <option value="PNS">PNS</option>
            <option value="PPPK">PPPK</option>
            <option value="Honorer">Honorer</option>

          </select>
          <label for="jenis_kepegawaian">Jenis kepegawaian</label>
        </div>

        @hasrole('Admin')
          <div class="form-floating form-floating-outline mb-5">
            <select id="sekolah_id" name="sekolah_id" class="select2 form-select">
              <option value="">pilih sekolah</option>
              @foreach ($dataSekolah as $sekolah)
                <option value="{{ $sekolah->id }}">
                  {{ $sekolah->detailPerSemester->nama_sekolah ?? 'Nama tidak diatur' }}
                  ({{ $sekolah->npsn }})
                </option>
              @endforeach
            </select>
            <label for="sekolah_id">Sekolah</label>
          </div>
        @endhasrole

        <button type="submit" id="submit" class="btn btn-primary me-sm-3 me-1 data-submit">Simpan</button>
        <button type="reset" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">Batal</button>
      </form>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {

      // --- 1. INISIALISASI SELECT2 (Tetap jQuery) ---
      const initSelect2 = () => {
        $('.select2').each(function() {
          const $this = $(this);
          $this.select2({
            placeholder: '-- pilih --',
            dropdownParent: $('#offcanvasPtk')
          });
        });
      };
      initSelect2();

      const tabelPtk = document.getElementById('tabel-ptk');
      const formPtk = document.getElementById('form-ptk');
      const offcanvasElement = document.getElementById('offcanvasPtk');

      let saveMode = 'add';
      let globalId = null;

      // Ambil isAdmin dari data-attribute tabel
      const isAdmin = @json(auth()->user()->hasRole('Admin'));
      // const isAdmin = window.hasRole('Admin');
      const judulForm = document.getElementById('offcanvas-label'); // Sesuaikan dengan ID di Blade

      // 1. Inisialisasi Kolom
      let columns = [{
        data: null
      }, {
        data: 'nama_ptk'
      }, {
        data: 'jenis_kelamin'
      }, {
        data: 'jabatan'
      }];

      if (isAdmin) {
        columns.push({
          data: 'sekolah.detail_terbaru.nama_sekolah',
          render: function(data, type, full) {
            // Validasi jika data kosong agar tidak error
            if (!full.sekolah) {
              return '-';
            }
            return full.sekolah.detail_terbaru ?
              full.sekolah.detail_terbaru.nama_sekolah + ' (' + full.sekolah.npsn + ')' :
              full.sekolah.npsn;
          }
        });
      }

      // Tambah kolom action
      columns.push({
        data: null
      });

      // 2. Inisialisasi DataTable
      if (tabelPtk) {
        window.dtPtk = new DataTable(tabelPtk, {
          processing: true,
          serverSide: false,
          ajax: {
            url: '/ptk/data',
            dataSrc: 'data'
          },
          columns: columns,
          columnDefs: [{
              targets: 0,
              orderable: false,
              searchable: false,
              data: null,
              defaultContent: ''
            },
            {
              targets: 1, // Gabungkan Nama & NIP
              render: function(data, type, full) {
                return `<div><span class="fw-medium text-uppercase">${full.nama_ptk}</span><br><small class="text-muted">${full.nip ||
      '-'}</small></div>`;
              }
            },
            {
              // Actions
              targets: -1,
              title: 'Actions',
              searchable: false,
              orderable: false,
              render: function(data, type, full, meta) {
                // Logika Kondisional untuk tombol reset password
                const canResetPassword = isAdmin || (full.jabatan !== 'Operator Sekolah');
                let akunBtn = '';

                if (full.user_id) {
                  if (canResetPassword) {
                    akunBtn = `
                      <a href="javascript:;" class="dropdown-item reset-password" data-id="${full.id}" data-name="${full.nama_ptk}">
                        <i class="icon-base ri ri-lock-password-line me-2"></i> Reset Password
                      </a>
                    `;
                  }
                } else {
                  // Jika belum punya akun
                  akunBtn = `<button class="dropdown-item generate-akun" data-id="${full['id']}" data-name="${full['nama_ptk']}"><i
                  class="icon-base ri ri-user-add-line icon-22px me-2"></i>Generate Akun</button>`;
                }

                return (
                  '<div class="d-flex align-items-center gap-4">' +
                  `<button class="btn btn-icon btn-text-secondary btn-sm rounded-pill edit-data" data-id="${full['id']}"
      data-bs-toggle="offcanvas" data-bs-target="#offcanvasPtk"><i
        class="icon-base ri ri-edit-box-line icon-22px"></i></button>` +
                  '<button class="btn btn-icon btn-text-secondary btn-sm rounded-pill dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base ri ri-more-2-line icon-22px"></i></button>' +
                  '<div class="dropdown-menu dropdown-menu-end m-0">' +
                  '<a href="#" class="dropdown-item">View</a>' +
                  `<button class="dropdown-item hapus-ptk" data-id="${full['id']}" data-name="${full['nama_ptk']}"><i
          class="icon-base ri ri-delete-bin-7-line icon-22px me-2"></i>Hapus</button>` +
                  akunBtn +
                  '</div>' +
                  '</div>'
                );
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
              features: [{}]
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
          displayLength: 7,
          language: {
            paginate: {
              next: '<i class="icon-base ri ri-arrow-right-s-line scaleX-n1-rtl icon-22px"></i>',
              previous: '<i class="icon-base ri ri-arrow-left-s-line scaleX-n1-rtl icon-22px"></i>',
              first: '<i class="icon-base ri ri-skip-back-mini-line scaleX-n1-rtl icon-22px"></i>',
              last: '<i class="icon-base ri ri-skip-forward-mini-line scaleX-n1-rtl icon-22px"></i>'
            }
          },
          initComplete: function() {
            const api = this.api();

            // --- FITUR DROPDOWN FILTER SEKOLAH ---
            // Asumsi: Kolom 'Nama Sekolah' ada di index ke-3 (sesuaikan index ini)
            const indexSekolah = 4;

            if (isAdmin) {
              const select = $(
                  '<select class="form-select form-select-sm ms-sm-2 mt-2 mt-sm-0" style="width: 200px;"><option value="">Semua Sekolah</option></select>'
                )
                .appendTo('.dt-layout-end:eq(0)') // Masukkan ke area filter kiri atas
                .on('change', function() {
                  const val = $.fn.dataTable.util.escapeRegex($(this).val());
                  api
                    .column(indexSekolah)
                    .search(val ? '^' + val + '$' : '', true, false)
                    .draw();
                });

              // Isi dropdown dengan data unik dari kolom sekolah
              api
                .column(indexSekolah, {
                  page: 'all'
                })
                .render('display')
                .unique()
                .sort()
                .each(function(d) {
                  if (d) {
                    const cleanText = d.replace(/<[^>]*>?/gm, '').trim();
                    select.append(`<option value="${cleanText}">${cleanText}</option>`);
                  }
                });
            }
            // Remove btn-secondary from export buttons
            document.querySelectorAll('.dt-buttons .btn').forEach(btn => {
              btn.classList.remove('btn-secondary');
            });
          }
        });

        if (window.dtPtk) {
          window.dtPtk.on('draw.dt', function() {
            let info = window.dtPtk.page.info();
            window.dtPtk
              .column(0, {
                search: 'applied',
                order: 'applied'
              })
              .nodes()
              .each(function(cell, i) {
                cell.innerHTML = i + 1 + info.start;
              });
          });
        }
      }

      // 3. Form Validation
      let fv;
      if (formPtk) {
        fv = FormValidation.formValidation(formPtk, {
          fields: {
            nama_ptk: {
              validators: {
                notEmpty: {
                  message: 'Nama harus diisi'
                }
              }
            },
            jenis_kelamin: {
              validators: {
                notEmpty: {
                  message: 'Pilih jenis kelamin'
                }
              }
            },
            jabatan: {
              validators: {
                notEmpty: {
                  message: 'Jabatan harus diisi'
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
              rowSelector: '.mb-5'
            }),
            submitButton: new FormValidation.plugins.SubmitButton(),
            autoFocus: new FormValidation.plugins.AutoFocus()
          }
        }).on('core.form.valid', async function() {
          const formData = new FormData(formPtk);
          // Gunakan POST untuk simpan, dan POST dengan _method=PUT untuk update jika diperlukan
          const url = saveMode === 'add' ? '/ptk' : `/ptk/${globalId}`;
          const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
          const submitBtn = document.getElementById('submit');
          if (saveMode !== 'add') {
            formData.append('_method', 'PUT');
          }

          // UI Loading
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

          try {
            const response = await fetch(url, {
              method: 'POST',
              body: formData,
              headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
              } // Tambahkan ini agar Laravel kirim JSON
            });

            const res = await response.json();

            if (response.ok) {
              Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: res.message
              });
              bootstrap.Offcanvas.getInstance(offcanvasElement).hide();
              // Reset Form & Validasi
              formPtk.reset();
              fv.resetForm(true);
              window.dtPtk.ajax.reload(null, false);
            } else if (response.status === 422) {
              const errors = res.errors;
              let errorList = '';

              Object.keys(errors).forEach(key => {
                errorList += `<li>${errors[key][0]}</li>`;
                // Periksa apakah field terdaftar di FormValidation
                if (fv.fields[key]) {
                  // Ambil semua validator yang terdaftar untuk field ini (regexp, notEmpty, dll)
                  const validators = Object.keys(fv.fields[key].validators);
                  // Gunakan validator pertama yang tersedia sebagai target pesan
                  const firstValidator = validators[0];

                  // 1. Tandai field sebagai Invalid
                  fv.updateFieldStatus(key, 'Invalid', firstValidator);

                  // 2. Cari elemen pesan error yang dibuat oleh fv
                  // fv biasanya membuat elemen dengan class .invalid-feedback atau di dalam container fv-plugins-message-container
                  const container = formPtk.querySelector(
                    `[data-field="${key}"][data-validator="${firstValidator}"]`);

                  if (container) {
                    container.innerText = errors[key][0]; // Isi dengan pesan dari Laravel
                    container.style.display = 'block'; // Pastikan terlihat
                  }
                }
              });
              Swal.fire({
                icon: 'error',
                title: 'Gagal Validasi',
                html: `<ul style="text-align:left;">${errorList}</ul>`,
                customClass: {
                  confirmButton: 'btn btn-primary'
                }
              });
            } else {
              throw new Error(res.message || 'Terjadi kesalahan sistem');
            }
          } catch (error) {
            Swal.fire({
              icon: 'error',
              title: 'Oops...',
              text: error.message,
              customClass: {
                confirmButton: 'btn btn-primary'
              }
            });
          } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Simpan';
          }
        });
      }

      // 4. Event Delegation
      document.addEventListener('click', async function(e) {
        // Tombol Tambah (Pastikan tombol di card-header punya class atau ID ini)
        if (e.target.closest('[data-bs-target="#offcanvasPtk"]') && !e.target.closest('.edit-data')) {
          saveMode = 'add';
          formPtk.reset();
          fv.resetForm(true);
          judulForm.innerHTML = 'Tambah PTK Baru';
        }

        // Tombol Edit
        const editBtn = e.target.closest('.edit-data');
        if (editBtn) {
          saveMode = 'edit';
          judulForm.innerHTML = 'Edit Data PTK';

          fv.resetForm(true);

          // 1. Dapatkan element <tr> terdekat dari tombol yang diklik
          const tr = e.target.closest('tr');

          // 2. Ambil data baris tersebut melalui instance DataTable
          const data = window.dtPtk.row(tr).data();

          if (data) {
            // 3. Masukkan data langsung ke form
            globalId = data.id; // Ambil ID dari objek data

            document.getElementById('ptk-id').value = data.id;
            document.getElementById('nama_ptk').value = data.nama_ptk;
            document.getElementById('nip').value = data.nip || '';
            document.getElementById('jenis_kelamin').value = data.jenis_kelamin || '';
            document.getElementById('jabatan').value = data.jabatan || '';
            document.getElementById('jenis_kepegawaian').value = data.jenis_kepegawaian || '';

            // Jika Admin, set value Select2/Select Sekolah
            if (isAdmin && data.sekolah_id) {
              $('#sekolah_id').val(data.sekolah_id).trigger('change');
            }

            // 4. Tampilkan offcanvas (Bootstrap 5)
            const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(offcanvasElement);
            offcanvas.show();
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Data tidak ditemukan di tabel'
            });
          }
        }

        // Tombol Hapus
        const hapusBtn = e.target.closest('.hapus-ptk');
        if (hapusBtn) {
          const id = hapusBtn.dataset.id;
          // Ambil nama PTK dari atribut data-nama pada tombol (opsional, fallback ke 'data ini')
          const namaPtk = hapusBtn.dataset.name || 'data ini';

          Swal.fire({
            title: `Hapus ${namaPtk}?`,
            html: `Data PTK <b>${namaPtk}</b> beserta <b>akun user terhubung</b> akan dihapus dari sistem.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            customClass: {
              confirmButton: 'btn btn-danger me-3',
              cancelButton: 'btn btn-outline-secondary'
            },
            buttonsStyling: false
          }).then(async result => {
            if (result.isConfirmed) {
              try {
                const response = await fetch(`/ptk/${id}`, {
                  method: 'DELETE',
                  headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                  }
                });
                const res = await response.json();

                if (!response.ok) {
                  // Lempar pesan dari backend (termasuk error 403 / 500)
                  throw new Error(res.message || 'Gagal menghapus data.');
                }

                Swal.fire({
                  icon: 'success',
                  title: 'Berhasil!',
                  text: res.message, // Menampilkan pesan dari controller
                  customClass: {
                    confirmButton: 'btn btn-success'
                  }
                });

                if (window.dtPtk) {
                  window.dtPtk.ajax.reload(null, false);
                }
              } catch (error) {
                Swal.fire({
                  icon: 'error',
                  title: 'Gagal Menghapus',
                  text: error.message ||
                    'Terjadi kesalahan saat menghubungi server.', // Menampilkan pesan error spesifik
                  customClass: {
                    confirmButton: 'btn btn-primary'
                  }
                });
              }
            }
          });
        }

        const akunBtn = e.target.closest('.generate-akun');
        if (akunBtn) {
          const id = akunBtn.dataset.id;

          const result = await Swal.fire({
            title: 'Generate Akun?',
            text: 'User akan dibuatkan akun login default.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Generate!'
          });

          if (result.isConfirmed) {
            try {
              const response = await fetch(`/ptk/${id}/generate-akun`, {
                method: 'POST',
                headers: {
                  'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                  Accept: 'application/json'
                }
              });

              const res = await response.json();

              if (response.ok) {
                Swal.fire({
                  icon: 'success',
                  title: 'Berhasil',
                  html: `Username: <b>${res.data.username}</b><br>Password: <b>${res.data.password}</b>`
                });
                window.dtPtk.ajax.reload(null, false); // Refresh tabel
              } else {
                Swal.fire('Gagal', res.message, 'error');
              }
            } catch (error) {
              Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            }
          }
        }

        const resetBtn = e.target.closest('.reset-password');
        if (resetBtn) {
          const id = resetBtn.dataset.id;
          const nama = resetBtn.dataset.name;

          Swal.fire({
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
                const response = await fetch(`/user/reset-password/${id}?type=ptk`, {
                  method: 'POST',
                  headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    Accept: 'application/json'
                  }
                });

                if (!response.ok) {
                  const errorData = await response.json();
                  throw new Error(errorData.message || 'Gagal mereset password');
                }

                return await response.json();
              } catch (error) {
                Swal.showValidationMessage(`Gagal: ${error.message}`);
              }
            }
          }).then(result => {
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
          });
        }
      });

      // Filter form control to default size
      // ? setTimeout used for multilingual table initialization
      setTimeout(() => {
        const elementsToModify = [{
            selector: '.dt-buttons .btn',
            classToRemove: 'btn-secondary'
          },
          {
            selector: '.dt-length .form-select',
            classToAdd: 'ms-0'
          },
          {
            selector: '.dt-length',
            classToAdd: 'mb-md-5 mb-0'
          },
          {
            selector: '.dt-layout-end',
            classToRemove: 'justify-content-between',
            classToAdd: 'd-flex gap-md-4 justify-content-md-between justify-content-center gap-md-2 flex-wrap mt-0'
          },
          {
            selector: '.dt-layout-start',
            classToAdd: 'mt-md-0 mt-5'
          },
          {
            selector: '.dt-layout-start .dt-buttons',
            classToAdd: 'd-md-flex d-block gap-4 justify-content-center'
          },
          {
            selector: '.dt-layout-end .dt-buttons',
            classToAdd: 'd-md-flex d-block gap-4 mb-md-0 mb-5 justify-content-center'
          },
          {
            selector: '.dt-layout-table',
            classToRemove: 'row mt-2'
          },
          {
            selector: '.dt-layout-full',
            classToRemove: 'col-md col-12'
          }
          // { selector: '.dt-layout-full .table', classToAdd: 'table-responsive' }
        ];

        // Delete record
        elementsToModify.forEach(({
          selector,
          classToRemove,
          classToAdd
        }) => {
          document.querySelectorAll(selector).forEach(element => {
            if (classToRemove) {
              classToRemove.split(' ').forEach(className => element.classList.remove(className));
            }
            if (classToAdd) {
              classToAdd.split(' ').forEach(className => element.classList.add(className));
            }
          });
        });
      }, 100);
    });
  </script>
@endsection
