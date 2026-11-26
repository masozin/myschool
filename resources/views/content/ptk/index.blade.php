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
      <a href="{{ route('ptk.create') }}" id="tambah-ptk" class="btn btn-primary btn-tambah">
        <i class="ri-add-line me-1"></i> Tambah PTK
      </a>
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

  {{-- ── Modal Ubah Status PTK ─────────────────────────────────────── --}}
  <div class="modal fade" id="modal-status-ptk" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Ubah Status PTK</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="status_ptk_id">
          <p class="mb-3">Status untuk <strong id="status_ptk_nama">-</strong></p>
          <div class="mb-3">
            <label class="form-label" for="status_ptk_pilihan">Status</label>
            <select class="form-select" id="status_ptk_pilihan">
              <option value="aktif">Aktif</option>
              <option value="tidak_aktif">Tidak Aktif</option>
              <option value="pensiun">Pensiun</option>
            </select>
          </div>
          <div class="mb-1" id="status_ptk_tanggal_wrap">
            <label class="form-label" for="status_ptk_tanggal">Tanggal Keluar</label>
            <input type="date" class="form-control" id="status_ptk_tanggal">
            <small class="text-muted">Wajib diisi untuk status Tidak Aktif dan Pensiun.
              Mengubah kembali ke Aktif akan mengosongkan tanggal ini.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-primary btn-sm" id="btn-simpan-status">Simpan Status</button>
        </div>
      </div>
    </div>
  </div>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {

      const tabelPtk = document.getElementById('tabel-ptk');

      // Ambil isAdmin dari data-attribute tabel
      const isAdmin = @json(auth()->user()->hasRole('Admin'));

      // 1. Inisialisasi Kolom
      let columns = [{
        data: null
      }, {
        data: 'nama_ptk'
      }, {
        data: 'jenis_kelamin'
      }, {
        data: 'jabatan',
        render: function(data, type, full) {
          const tambahan = Array.isArray(full.peran_tambahan) ? full.peran_tambahan : [];
          if (type !== 'display') {
            return [data, ...tambahan].filter(Boolean).join(' ');
          }
          const petaStatus = {
            aktif: ['Aktif', 'bg-label-success'],
            tidak_aktif: ['Tidak Aktif', 'bg-label-secondary'],
            pensiun: ['Pensiun', 'bg-label-warning']
          };
          const status = petaStatus[full.status] ? full.status : 'aktif';
          const [labelStatus, kelasStatus] = petaStatus[status];
          let html = `<span class="badge bg-label-primary">${data ?? '-'}</span>`;
          tambahan.forEach(peran => {
            html += ` <span class="badge bg-label-info">${peran}</span>`;
          });
          html += `<br><span class="badge ${kelasStatus} mt-1">${labelStatus}</span>`;
          if (status !== 'aktif' && full.tanggal_keluar) {
            html += `<br><small class="text-muted">Keluar: ${String(full.tanggal_keluar).slice(0, 10)}</small>`;
          }
          return html;
        }
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
              targets: 1, // Gabungkan Foto/Inisial, Nama & NIP
              render: function(data, type, full) {
                const nama = full.nama_lengkap || full.nama_ptk || '-';
                if (type !== 'display') {
                  return nama;
                }
                let avatar;
                if (full.photo) {
                  avatar = `<img src="/ptk/${full.id}/foto" alt="" class="rounded-circle flex-shrink-0" style="width:38px;height:38px;object-fit:cover">`;
                } else {
                  const kata = (full.nama_ptk || '?').trim().split(/\s+/);
                  const inisial = ((kata[0] || '?').charAt(0) + (kata.length > 1 ? kata[kata.length - 1].charAt(0) : '')).toUpperCase();
                  const palet = ['#7367f0', '#28c76f', '#ff9f43', '#ea5455', '#00cfe8', '#1d5cbf'];
                  const warna = palet[(full.id || 0) % palet.length];
                  avatar = `<span class="flex-shrink-0" style="width:38px;height:38px;border-radius:50%;background:${warna};color:#fff;display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem">${inisial}</span>`;
                }
                return `<div class="d-flex align-items-center gap-2">${avatar}<div><span class="fw-medium">${nama}</span><br><small class="text-muted">${full.nip || '-'}</small></div></div>`;
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
                  `<a class="btn btn-icon btn-text-secondary btn-sm rounded-pill edit-data" href="{{ url('/ptk') }}/${full['id']}/ubah"><i
        class="icon-base ri ri-edit-box-line icon-22px"></i></a>` +
                  '<button class="btn btn-icon btn-text-secondary btn-sm rounded-pill dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base ri ri-more-2-line icon-22px"></i></button>' +
                  '<div class="dropdown-menu dropdown-menu-end m-0">' +
                  '<a href="#" class="dropdown-item">View</a>' +
                  `<button class="dropdown-item hapus-ptk" data-id="${full['id']}" data-name="${full['nama_ptk']}"><i
          class="icon-base ri ri-delete-bin-7-line icon-22px me-2"></i>Hapus</button>` +
                  `<button class="dropdown-item ubah-status" data-id="${full['id']}" data-name="${full['nama_ptk']}"
          data-status="${full.status ?? 'aktif'}" data-tanggal="${full.tanggal_keluar ? String(full.tanggal_keluar).slice(0, 10) : ''}"><i
          class="icon-base ri ri-user-follow-line icon-22px me-2"></i>Ubah Status</button>` +
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
            // Indeks dari column().nodes() sudah posisi global pada
            // urutan tampil — jangan ditambah info.start lagi
            window.dtPtk
              .column(0, {
                search: 'applied',
                order: 'applied'
              })
              .nodes()
              .each(function(cell, i) {
                cell.innerHTML = i + 1;
              });
          });
        }
      }

      // 3. Event Delegation
      document.addEventListener('click', async function(e) {
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

        const statusBtn = e.target.closest('.ubah-status');
        if (statusBtn) {
          document.getElementById('status_ptk_id').value = statusBtn.dataset.id;
          document.getElementById('status_ptk_nama').textContent = statusBtn.dataset.name;
          const pilihan = document.getElementById('status_ptk_pilihan');
          pilihan.value = statusBtn.dataset.status || 'aktif';
          document.getElementById('status_ptk_tanggal').value = statusBtn.dataset.tanggal || '';
          document.getElementById('status_ptk_tanggal_wrap').style.display =
            pilihan.value === 'aktif' ? 'none' : '';
          new bootstrap.Modal(document.getElementById('modal-status-ptk')).show();
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

      // ─── Ubah Status PTK: tanggal keluar hanya untuk non-aktif ────
      document.getElementById('status_ptk_pilihan')?.addEventListener('change', function() {
        document.getElementById('status_ptk_tanggal_wrap').style.display =
          this.value === 'aktif' ? 'none' : '';
      });

      document.getElementById('btn-simpan-status')?.addEventListener('click', async function() {
        const id = document.getElementById('status_ptk_id').value;
        const status = document.getElementById('status_ptk_pilihan').value;
        const tanggal = document.getElementById('status_ptk_tanggal').value;

        if (status !== 'aktif' && !tanggal) {
          Swal.fire('Perhatian', 'Tanggal keluar wajib diisi untuk status Tidak Aktif dan Pensiun.', 'warning');
          return;
        }

        try {
          const res = await fetch(`/ptk/${id}/status`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
              Accept: 'application/json'
            },
            body: JSON.stringify({
              status: status,
              tanggal_keluar: status === 'aktif' ? null : tanggal
            })
          });
          const data = await res.json();
          if (!res.ok) throw new Error(data.message || 'Gagal mengubah status.');

          bootstrap.Modal.getInstance(document.getElementById('modal-status-ptk'))?.hide();
          Swal.fire('Berhasil', data.message, 'success');
          window.dtPtk.ajax.reload(null, false);
        } catch (error) {
          Swal.fire('Gagal', error.message, 'error');
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
