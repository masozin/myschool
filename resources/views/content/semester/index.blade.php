@extends('layouts/layoutMaster')

@section('title', 'Data semester')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">Data Semester</h5>
      <button class="tambah-semester btn btn-primary waves-effect waves-light" data-bs-toggle="offcanvas"
        data-bs-target="#offcanvas_semester">
        <i class="icon-base ri ri-add-line icon-sm me-sm-2"></i>
        <span class="d-sm-inline-block">Tambah</span>
      </button>
    </div>
    <div class="card-body">
      <div class="table-responsive text-nowrap">
        <table class="table table-sm table-hover mb-0" id="tabel_semester">
          <thead>
            <tr>
              <th>Kode Semester</th>
              <th>Semester</th>
              <th>Tahun Pelajaran</th>
              <th>Tanggal Mulai</th>
              <th>Tanggal Selesai</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="tbody_semester">
            <tr>
              <td colspan="7" class="text-center py-4">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>Memuat data...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas_semester" aria-labelledby="offcanvas-semester-label">
    <div class="offcanvas-header border-bottom">
      <h5 id="offcanvas_label" class="offcanvas-title">Tambah Semester Baru</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 h-100">
      <form class="add-new-semester pt-0" id="form_semester">
        <input type="hidden" name="id" id="semester_id">

        <div class="form-floating form-floating-outline mb-5">
          <input type="text" class="form-control" id="tahun_pelajaran" name="tahun_pelajaran" maxlength="9"
            placeholder="contoh: 2025/2026" />
          <label for="tahun_pelajaran">Tahun Pelajaran</label>
        </div>

        <div class="form-floating form-floating-outline mb-5">
          <select class="form-select" id="semester" name="semester">
            <option value="">Pilih Semester</option>
            <option value="Ganjil" data-code="1">Ganjil</option>
            <option value="Genap" data-code="2">Genap</option>
          </select>
          <label for="semester">Semester</label>
        </div>

        <div class="form-floating form-floating-outline mb-5">
          <input type="text" class="form-control" id="kode_semester" name="kode_semester" readonly tabindex="-1" />
          <label for="kode_semester">Id Semester</label>
        </div>

        <div class="row">
          <div class="col-sm-6 mb-5">
            <div class="form-floating form-floating-outline">
              <input type="text" class="form-control" id="preview_tanggal_mulai" readonly tabindex="-1" />
              <label for="preview_tanggal_mulai">Tanggal Mulai (otomatis)</label>
            </div>
          </div>
          <div class="col-sm-6 mb-5">
            <div class="form-floating form-floating-outline">
              <input type="text" class="form-control" id="preview_tanggal_selesai" readonly tabindex="-1" />
              <label for="preview_tanggal_selesai">Tanggal Selesai (otomatis)</label>
            </div>
          </div>
        </div>

        <div class="col-sm-6 mb-8">
          <label class="switch">
            <input type="checkbox" class="switch-input" id="is_active" name="is_active" value="1" />
            <span class="switch-toggle-slider">
              <span class="switch-on"><i class="icon-base ri ri-check-line"></i></span>
              <span class="switch-off"><i class="icon-base ri ri-close-line"></i></span>
            </span>
            <span class="switch-label" id="is_active_label">Aktif</span>
          </label>
        </div>

        <button type="submit" id="submit" class="btn btn-primary me-sm-3 me-1">Simpan</button>
        <button type="reset" class="btn btn-outline-danger" data-bs-dismiss="offcanvas">Batal</button>
      </form>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {

      const offcanvasElement = document.getElementById('offcanvas_semester');
      const formSemester = document.getElementById('form_semester');
      const tbody = document.getElementById('tbody_semester');

      let saveMode = 'add';
      let globalId = null;
      let tableData = [];

      // ─── Formatter tanggal Indonesia ───────────────────────────────
      function formatTanggal(isoString) {
        if (!isoString) return '-';
        const d = new Date(isoString);
        if (isNaN(d)) return '-';
        return d.toLocaleDateString('id-ID', {
          day: 'numeric',
          month: 'long',
          year: 'numeric'
        });
      }

      // ─── 1. RENDER TABEL ────────────────────────────────────────────
      function renderTable(data) {
        if (!data.length) {
          tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Belum ada data semester.</td></tr>';
          return;
        }

        tbody.innerHTML = data.map(row => `
          <tr>
            <td>${row.kode_semester ?? '-'}</td>
            <td>${row.semester ?? '-'}</td>
            <td>${row.tahun_pelajaran ?? '-'}</td>
            <td>${formatTanggal(row.tanggal_mulai)}</td>
            <td>${formatTanggal(row.tanggal_selesai)}</td>
            <td>
              <span class="badge ${row.is_active == 1 ? 'bg-label-success' : 'bg-label-secondary'}">
                ${row.is_active == 1 ? 'Aktif' : 'Tidak Aktif'}
              </span>
              ${row.is_active == 1 ? '' : (row.kunci_dibuka == 1
                ? '<span class="badge bg-label-warning">Kunci Dibuka</span>'
                : '<span class="badge bg-label-danger">Terkunci</span>')}
            </td>
            <td>
              ${row.is_active == 1 ? '' : `
                <button class="btn btn-icon btn-text-warning btn-sm rounded-pill toggle-kunci"
                  data-id="${row.id}" data-dibuka="${row.kunci_dibuka == 1 ? '1' : '0'}"
                  title="${row.kunci_dibuka == 1 ? 'Terapkan kunci lagi' : 'Buka kunci semester ini'}">
                  <i class="icon-base ri ${row.kunci_dibuka == 1 ? 'ri-lock-line' : 'ri-lock-unlock-line'} icon-22px"></i>
                </button>`}
              <button class="btn btn-icon btn-text-secondary btn-sm rounded-pill edit-data"
                data-id="${row.id}"
                data-bs-toggle="offcanvas"
                data-bs-target="#offcanvas_semester">
                <i class="icon-base ri ri-edit-box-line icon-22px"></i>
              </button>
            </td>
          </tr>
        `).join('');
      }

      // ─── 2. LOAD DATA ────────────────────────────────────────────────
      async function loadData() {
        try {
          const res = await fetch('/semester/table', {
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              Accept: 'application/json'
            }
          });
          tableData = await res.json();
          renderTable(tableData);
        } catch (err) {
          tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Gagal memuat data.</td></tr>';
        }
      }

      loadData();

      // ─── 3. FORM VALIDATION ──────────────────────────────────────────
      const fv = FormValidation.formValidation(formSemester, {
        fields: {
          semester: {
            validators: {
              notEmpty: {
                message: 'Semester harus diisi'
              }
            }
          },
          tahun_pelajaran: {
            validators: {
              notEmpty: {
                message: 'Tahun pelajaran harus diisi'
              },
              regexp: {
                regexp: /^\d{4}\/\d{4}$/,
                message: 'Format harus YYYY/YYYY (contoh: 2024/2025)'
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

        const formData = new FormData(formSemester);
        const url = saveMode === 'add' ? '/semester' : `/semester/${globalId}`;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const submitBtn = document.getElementById('submit');

        if (saveMode !== 'add') formData.append('_method', 'PUT');

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
            }
          });

          const res = await response.json();

          if (response.ok) {
            Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              text: res.message
            });
            bootstrap.Offcanvas.getInstance(offcanvasElement).hide();
            formSemester.reset();
            fv.resetForm(true);
            loadData();
          } else if (response.status === 422) {
            const errors = res.errors;
            let errorList = '';

            Object.keys(errors).forEach(key => {
              errorList += `<li>${errors[key][0]}</li>`;
              if (fv.fields[key]) {
                const firstValidator = Object.keys(fv.fields[key].validators)[0];
                fv.updateFieldStatus(key, 'Invalid', firstValidator);
                const container = formSemester.querySelector(
                  `[data-field="${key}"][data-validator="${firstValidator}"]`
                );
                if (container) {
                  container.innerText = errors[key][0];
                  container.style.display = 'block';
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
          submitBtn.innerHTML = 'Simpan'; // ← fix: was 'Submit'
        }
      });

      // ─── 4. GENERATE KODE & NAMA SEMESTER ───────────────────────────
      const tahunInput = document.getElementById('tahun_pelajaran');
      const semesterInput = document.getElementById('semester');
      const kodeSemesterInput = document.getElementById('kode_semester');
      const checkboxActive = document.getElementById('is_active');
      const labelStatus = document.getElementById('is_active_label');

      checkboxActive.addEventListener('change', function() {
        labelStatus.innerText = this.checked ? 'Aktif' : 'Tidak Aktif';
      });

      function generateSemesterData() {
        const tahunVal = tahunInput.value;
        const semesterVal = semesterInput.value;
        const semesterCode = semesterInput.options[semesterInput.selectedIndex]?.getAttribute('data-code');

        if (tahunVal && semesterVal) {
          const parts = tahunVal.split('/');
          if (parts.length === 2) {
            const codeYear = parts[0].substring(2, 4) + parts[1].substring(2, 4);
            kodeSemesterInput.value = codeYear + semesterCode;

            // Preview tanggal, mirror aturan di Semester::rentangTanggalSemester()
            const tahunAwal = parseInt(parts[0]);
            const tahunAkhir = parseInt(parts[1]);

            if (semesterVal === 'Ganjil') {
              document.getElementById('preview_tanggal_mulai').value = formatTanggal(`${tahunAwal}-07-01`);
              document.getElementById('preview_tanggal_selesai').value = formatTanggal(`${tahunAwal}-12-31`);
            } else if (semesterVal === 'Genap') {
              document.getElementById('preview_tanggal_mulai').value = formatTanggal(`${tahunAkhir}-01-01`);
              document.getElementById('preview_tanggal_selesai').value = formatTanggal(`${tahunAkhir}-06-30`);
            }
          }
        } else {
          kodeSemesterInput.value = '';
          document.getElementById('preview_tanggal_mulai').value = '';
          document.getElementById('preview_tanggal_selesai').value = '';
        }

        fv.revalidateField('tahun_pelajaran');
      }

      tahunInput.addEventListener('input', function(e) {
        const value = e.target.value.replace(/\D/g, '');
        if (value.length === 4) {
          const tahunMulai = parseInt(value);
          e.target.value = tahunMulai + '/' + (tahunMulai + 1);
          generateSemesterData();
        }
      });

      tahunInput.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && this.value.length === 9) {
          this.value = '';
          generateSemesterData();
        }
      });

      tahunInput.addEventListener('blur', generateSemesterData);
      semesterInput.addEventListener('change', generateSemesterData);

      // ─── 5. EVENT DELEGATION ─────────────────────────────────────────
      document.addEventListener('click', async function(e) {
        const judulForm = document.getElementById('offcanvas_label');

        // Tombol Tambah
        if (e.target.closest('.tambah-semester')) {
          saveMode = 'add';
          globalId = null;
          formSemester.reset();
          fv.resetForm(true);
          labelStatus.innerText = 'Aktif';
          checkboxActive.checked = false;
          judulForm.innerHTML = 'Tambah Semester Baru';
          document.getElementById('preview_tanggal_mulai').value = '';
          document.getElementById('preview_tanggal_selesai').value = '';
        }

        // Tombol Buka / Terapkan Kunci Semester
        if (e.target.closest('.toggle-kunci')) {
          const btn = e.target.closest('.toggle-kunci');
          const buka = btn.dataset.dibuka !== '1';
          try {
            const res = await fetch(`/semester/${btn.dataset.id}/kunci`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
              },
              body: JSON.stringify({
                aksi: buka ? 'buka' : 'tutup'
              })
            });
            const hasil = await res.json();
            if (!res.ok) throw new Error(hasil.message || 'Gagal mengubah kunci semester.');
            Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              text: hasil.message,
              timer: 1800,
              showConfirmButton: false
            });
            loadData();
          } catch (err) {
            Swal.fire({
              icon: 'error',
              title: 'Gagal',
              text: err.message
            });
          }
          return;
        }

        // Tombol Edit
        if (e.target.closest('.edit-data')) {
          const id = e.target.closest('.edit-data').dataset.id;
          const data = tableData.find(r => String(r.id) === String(id));

          if (!data) {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Data tidak ditemukan.'
            });
            return;
          }

          saveMode = 'edit';
          globalId = data.id;
          judulForm.innerHTML = 'Edit Data Semester';
          fv.resetForm(true);

          document.getElementById('semester_id').value = data.id;
          document.getElementById('semester').value = data.semester ?? '';
          document.getElementById('tahun_pelajaran').value = data.tahun_pelajaran;
          document.getElementById('kode_semester').value = data.kode_semester ?? '';
          document.getElementById('preview_tanggal_mulai').value = formatTanggal(data.tanggal_mulai);
          document.getElementById('preview_tanggal_selesai').value = formatTanggal(data.tanggal_selesai);

          const isActive = data.is_active == 1 || data.is_active === true;
          checkboxActive.checked = isActive;
          labelStatus.innerText = isActive ? 'Aktif' : 'Tidak Aktif';
        }
      });

    });
  </script>
@endsection
