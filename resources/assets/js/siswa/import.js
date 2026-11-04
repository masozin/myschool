/**
 * siswa/import.js
 * Logika import data siswa dari file Excel.
 */

const SELECTOR = {
  btnProses: '#btn-proses-import',
  form: '#form-import-siswa',
  fileInput: '#file-excel-input',
  sekolahSelect: '#import-sekolah-id',
  progress: '#import-progress',
  errorReport: '#import-error-report',
  errorBody: '#error-report-body',
  errorBadge: '#error-count-badge',
  successReport: '#import-success-report',
  successMessage: '#import-success-message',
  modal: '#modal-import-siswa',
  csrfToken: 'meta[name="csrf-token"]'
};

export function initImport() {
  const btn = document.querySelector(SELECTOR.btnProses);
  if (!btn) return;

  // Reset state saat modal dibuka
  document.querySelector(SELECTOR.modal)?.addEventListener('show.bs.modal', resetState);

  btn.addEventListener('click', handleImport);
}

// ─── Handler ────────────────────────────────────────────────────────────────

async function handleImport() {
  if (!validateInput()) return;

  const confirmed = await confirmDialog();
  if (!confirmed) return;

  setLoading(true);
  resetState();

  try {
    const formData = new FormData(document.querySelector(SELECTOR.form));
    const response = await fetch('/siswa/import', {
      method: 'POST',
      body: formData,
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': document.querySelector(SELECTOR.csrfToken).content
      }
    });

    const res = await response.json();

    if (response.ok && res.status === 'success') {
      onSuccess(res);
    } else if (response.status === 422 && res.errors) {
      onValidationError(res.errors, res.total_error);
    } else {
      throw new Error(res.message ?? 'Terjadi kesalahan sistem.');
    }
  } catch (err) {
    Swal.fire({ icon: 'error', title: 'Gagal', text: err.message, customClass: { confirmButton: 'btn btn-primary' } });
  } finally {
    setLoading(false);
  }
}

// ─── Helpers ────────────────────────────────────────────────────────────────

function validateInput() {
  const file = document.querySelector(SELECTOR.fileInput)?.files;
  const sekolah = document.querySelector(SELECTOR.sekolahSelect);

  if (!file?.length) {
    return warn('File Belum Dipilih', 'Silakan pilih file Excel terlebih dahulu.');
  }
  if (sekolah && !sekolah.value) {
    return warn('Sekolah Belum Dipilih', 'Silakan pilih sekolah terlebih dahulu.');
  }
  return true;
}

function warn(title, text) {
  Swal.fire({ icon: 'warning', title, text, customClass: { confirmButton: 'btn btn-primary' } });
  return false;
}

async function confirmDialog() {
  const result = await Swal.fire({
    icon: 'question',
    title: 'Proses Import?',
    text: 'Pastikan data di file Excel sudah benar sebelum diproses.',
    showCancelButton: true,
    confirmButtonText: 'Ya, Proses!',
    cancelButtonText: 'Batal',
    customClass: { confirmButton: 'btn btn-primary me-3', cancelButton: 'btn btn-outline-secondary' },
    buttonsStyling: false
  });
  return result.isConfirmed;
}

function setLoading(isLoading) {
  const btn = document.querySelector(SELECTOR.btnProses);
  document.querySelector(SELECTOR.progress)?.classList.toggle('d-none', !isLoading);
  btn.disabled = isLoading;
  btn.innerHTML = isLoading
    ? '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...'
    : '<i class="ri-upload-line me-1"></i> Proses Import';
}

function onSuccess(res) {
  const msgEl = document.querySelector(SELECTOR.successMessage);
  if (msgEl) {
    msgEl.innerHTML = `<strong>Import Berhasil!</strong> ${res.berhasil} data siswa berhasil ditambahkan.`;
  }
  document.querySelector(SELECTOR.successReport)?.classList.remove('d-none');
  document.querySelector(SELECTOR.form)?.reset();
  window.siswa?.dt?.ajax.reload(null, false);
}

function onValidationError(errors, totalError) {
  const badge = document.querySelector(SELECTOR.errorBadge);
  const body = document.querySelector(SELECTOR.errorBody);

  if (badge) badge.textContent = `${totalError} error`;
  if (body) {
    body.innerHTML = errors
      .map(
        err => `
      <tr>
        <td class="text-center fw-semibold text-danger">${err.baris}</td>
        <td>${err.kolom}</td>
        <td class="text-truncate" style="max-width:150px;" title="${err.nilai}">${err.nilai}</td>
        <td>${err.pesan}</td>
      </tr>`
      )
      .join('');
  }

  const reportEl = document.querySelector(SELECTOR.errorReport);
  reportEl?.classList.remove('d-none');
  reportEl?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function resetState() {
  [SELECTOR.progress, SELECTOR.errorReport, SELECTOR.successReport].forEach(sel => {
    document.querySelector(sel)?.classList.add('d-none');
  });
  const body = document.querySelector(SELECTOR.errorBody);
  if (body) body.innerHTML = '';
}
