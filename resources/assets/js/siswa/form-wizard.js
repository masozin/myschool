/**
 * siswa/form-wizard.js
 * Wizard validasi 3-step untuk form tambah / edit siswa.
 * Bergantung pada: bs-stepper, @form-validation, flatpickr, select2, sweetalert2
 */

'use strict';

// ─── Konfigurasi validasi per field ──────────────────────────────────────────

const VALIDATION_STEP1 = {
  nama_siswa: { validators: { notEmpty: { message: 'Nama siswa wajib diisi' } } },
  nik: {
    validators: {
      notEmpty: { message: 'NIK wajib diisi' },
      stringLength: { min: 16, max: 16, message: 'NIK harus 16 digit' },
      digits: { message: 'NIK harus berupa angka' }
    }
  },
  jenis_kelamin: { validators: { notEmpty: { message: 'Pilih jenis kelamin' } } },
  tempat_lahir: { validators: { notEmpty: { message: 'Tempat lahir wajib diisi' } } },
  tgl_lahir: { validators: { notEmpty: { message: 'Tanggal lahir wajib diisi' } } },
  agama: { validators: { notEmpty: { message: 'Pilih agama' } } },
  alamat: { validators: { notEmpty: { message: 'Alamat wajib diisi' } } }
};

const VALIDATION_STEP2 = {
  nama_ayah: { validators: { notEmpty: { message: 'Nama Ayah wajib diisi' } } },
  nama_ibu: { validators: { notEmpty: { message: 'Nama Ibu wajib diisi' } } },
  nik_ayah: {
    validators: { digits: { message: 'Hanya angka' }, stringLength: { min: 16, max: 16, message: 'Harus 16 digit' } }
  },
  nik_ibu: {
    validators: { digits: { message: 'Hanya angka' }, stringLength: { min: 16, max: 16, message: 'Harus 16 digit' } }
  },
  nik_wali: {
    validators: { digits: { message: 'Hanya angka' }, stringLength: { min: 16, max: 16, message: 'Harus 16 digit' } }
  },
  tahun_ayah: {
    validators: { digits: { message: 'Hanya angka' }, stringLength: { min: 4, max: 4, message: 'Harus 4 digit' } }
  },
  tahun_ibu: {
    validators: { digits: { message: 'Hanya angka' }, stringLength: { min: 4, max: 4, message: 'Harus 4 digit' } }
  },
  tahun_wali: {
    validators: { digits: { message: 'Hanya angka' }, stringLength: { min: 4, max: 4, message: 'Harus 4 digit' } }
  }
};

const VALIDATION_STEP3 = {
  nisn: {
    validators: {
      notEmpty: { message: 'NISN wajib diisi' },
      digits: { message: 'NISN harus berupa angka' },
      stringLength: { min: 10, max: 10, message: 'NISN harus 10 digit' }
    }
  },
  nis: { validators: { notEmpty: { message: 'NIS Sekolah wajib diisi' } } },
  jenis_pendaftaran: { validators: { notEmpty: { message: 'Pilih jenis pendaftaran' } } },
  tingkat: { validators: { notEmpty: { message: 'Tingkat/Kelas wajib diisi' } } },
  tanggal_diterima: { validators: { notEmpty: { message: 'Tanggal diterima wajib diisi' } } }
};

// ─── Plugin FV yang dipakai di setiap step ───────────────────────────────────

function buildFvPlugins() {
  return {
    trigger: new FormValidation.plugins.Trigger(),
    bootstrap5: new FormValidation.plugins.Bootstrap5({ eleValidClass: '', rowSelector: '.form-control-validation' }),
    autoFocus: new FormValidation.plugins.AutoFocus(),
    submitButton: new FormValidation.plugins.SubmitButton()
  };
}

// ─── Init utama ──────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
  const wizardEl = document.querySelector('#wizard-siswa');
  if (!wizardEl) return;

  const form = wizardEl.querySelector('#form-siswa');
  const modalEl = document.getElementById('modal-form-siswa');
  const sectionWali = document.getElementById('section-wali');

  const stepEls = {
    step1: form.querySelector('#data-siswa'),
    step2: form.querySelector('#data-orang-tua'),
    step3: form.querySelector('#registrasi-siswa')
  };

  // ── BS Stepper ────────────────────────────────────────────────────────
  const stepper = new Stepper(wizardEl, { linear: true });

  // ── Flatpickr ─────────────────────────────────────────────────────────
  form.querySelectorAll('.flatpickr-date').forEach(el => {
    flatpickr(el, { dateFormat: 'Y-m-d', static: true });
  });

  // ── Select2 ───────────────────────────────────────────────────────────
  if (typeof $.fn.select2 !== 'undefined') {
    form.querySelectorAll('.select2').forEach(el => {
      const $el = $(el);
      $el.select2({ placeholder: 'Pilih opsi', dropdownParent: $el.parent() });

      // Trigger revalidasi saat nilai Select2 berubah
      $el.on('change', function () {
        const name = $(this).attr('name');
        if (stepEls.step1.contains(this)) fv1.revalidateField(name);
        if (stepEls.step2.contains(this)) fv2.revalidateField(name);
        if (stepEls.step3.contains(this)) fv3.revalidateField(name);
      });
    });
  }

  // ── Form Validation instances ─────────────────────────────────────────
  const fv1 = FormValidation.formValidation(stepEls.step1, { fields: VALIDATION_STEP1, plugins: buildFvPlugins() }).on(
    'core.form.valid',
    () => stepper.next()
  );

  const fv2 = FormValidation.formValidation(stepEls.step2, { fields: VALIDATION_STEP2, plugins: buildFvPlugins() }).on(
    'core.form.valid',
    () => stepper.next()
  );

  const fv3 = FormValidation.formValidation(stepEls.step3, { fields: VALIDATION_STEP3, plugins: buildFvPlugins() }).on(
    'core.form.valid',
    () => handleSubmit(form, modalEl, [fv1, fv2, fv3], stepper)
  );

  const allFv = [fv1, fv2, fv3];

  // ── Tombol Next / Prev ────────────────────────────────────────────────
  form.querySelectorAll('.btn-next').forEach(btn => {
    btn.addEventListener('click', () => {
      const validators = [fv1, fv2, fv3];
      validators[stepper._currentIndex]?.validate();
    });
  });

  form.querySelectorAll('.btn-prev').forEach(btn => {
    btn.addEventListener('click', () => stepper.previous());
  });

  // ── Toggle section Wali ───────────────────────────────────────────────
  const WALI_VALIDATION = {
    validators: { notEmpty: { message: 'Nama wali wajib diisi jika memilih opsi lainnya' } }
  };

  form.querySelectorAll('input[name="wali_status"]').forEach(radio => {
    radio.addEventListener('change', function () {
      const isLainnya = this.value === 'lainnya';
      sectionWali?.classList.toggle('d-none', !isLainnya);
      if (isLainnya) {
        fv2.addField('nama_wali', WALI_VALIDATION);
      } else {
        fv2.removeField('nama_wali');
      }
    });
  });

  // ── Reset saat modal ditutup ──────────────────────────────────────────
  modalEl?.addEventListener('hide.bs.modal', () => {
    form.reset();
    allFv.forEach(fv => fv?.resetForm(true));
    sectionWali?.classList.add('d-none');
    stepper.to(1);

    if (typeof $.fn.select2 !== 'undefined') {
      $(form).find('.select2').val(null).trigger('change');
    }
  });
});

// ─── Submit handler (dipanggil saat step 3 valid) ────────────────────────────

async function handleSubmit(form, modalEl, allFv, stepper) {
  const submitBtn = form.querySelector('button[type="submit"]');
  const originalHtml = submitBtn.innerHTML;

  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Menyimpan...';

  try {
    const formData = new FormData(form);
    const response = await fetch(form.action, {
      method: 'POST',
      body: formData,
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': formData.get('_token')
      }
    });

    const res = await response.json();

    if (response.ok) {
      await Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: res.message,
        customClass: { confirmButton: 'btn btn-success' }
      });

      bootstrap.Modal.getInstance(modalEl)?.hide();
      window.siswa?.dt?.ajax.reload(null, false);
    } else if (response.status === 422 && res.errors) {
      handleValidationErrors(res.errors, allFv, stepper, form);

      const errorList = Object.values(res.errors)
        .map(err => `<li>${err[0]}</li>`)
        .join('');

      Swal.fire({
        icon: 'error',
        title: 'Gagal Validasi',
        html: `<ul style="text-align:left">${errorList}</ul>`,
        customClass: { confirmButton: 'btn btn-primary' }
      });
    } else {
      throw new Error(res.message ?? 'Terjadi kesalahan sistem.');
    }
  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'Oops...',
      text: err.message,
      customClass: { confirmButton: 'btn btn-primary' }
    });
  } finally {
    submitBtn.innerHTML = originalHtml;
    submitBtn.disabled = false;
  }
}

// ─── Petakan error 422 dari Laravel ke step yang tepat ───────────────────────

function handleValidationErrors(errors, allFv, stepper, form) {
  let firstErrorStep = null;

  Object.keys(errors).forEach(fieldName => {
    allFv.forEach((fv, stepIndex) => {
      if (!fv.fields[fieldName]) return;

      // Tandai field sebagai invalid
      const firstRule = Object.keys(fv.fields[fieldName].validators)[0];
      fv.updateFieldStatus(fieldName, 'Invalid', firstRule);

      // Isi pesan error dari server ke elemen validasi
      const msgEl = form.querySelector(`[data-field="${fieldName}"][data-validator="${firstRule}"]`);
      if (msgEl) {
        msgEl.innerText = errors[fieldName][0];
        msgEl.style.display = 'block';
      }

      // Catat step pertama yang punya error agar bisa di-navigate
      if (firstErrorStep === null) firstErrorStep = stepIndex + 1;
    });
  });

  if (firstErrorStep !== null) stepper.to(firstErrorStep);
}
