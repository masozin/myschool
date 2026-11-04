/**
 * siswa/status.js
 * Logika modal update status siswa.
 */

const STATUS_YANG_BUTUH_DETAIL = ['mutasi', 'keluar', 'meninggal'];

/** Inisialisasi event listener modal status. */
export function initStatusModal() {
  const selectStatus = document.getElementById('status-registrasi');
  const formUpdateStatus = document.getElementById('form-update-status');

  if (!selectStatus || !formUpdateStatus) return;

  // Init flatpickr pada field tanggal
  const flatpickrFields = formUpdateStatus.querySelectorAll('.flatpickr-date');
  flatpickrFields.forEach(el => flatpickr(el, { dateFormat: 'Y-m-d', monthSelectorType: 'static', static: true }));

  // Toggle container tambahan saat status berubah
  selectStatus.addEventListener('change', () => toggleStatusDetail(selectStatus.value));

  // Submit via AJAX
  formUpdateStatus.addEventListener('submit', handleStatusSubmit);
}

/** Tampilkan / sembunyikan field tanggal & alasan sesuai status. */
function toggleStatusDetail(status) {
  const show = STATUS_YANG_BUTUH_DETAIL.includes(status);
  document.getElementById('container-tgl-keluar')?.classList.toggle('d-none', !show);
  document.getElementById('container-alasan')?.classList.toggle('d-none', !show);
}

/** Isi modal status dari data tombol yang diklik. */
export function populateStatusModal(btn) {
  const id = btn.dataset.id;
  const status = btn.dataset.status ?? '';
  const tgl = btn.dataset.tglKeluar;

  document.getElementById('siswa-id-status').value = id;

  const selectStatus = document.getElementById('status-registrasi');
  selectStatus.value = status;
  selectStatus.dispatchEvent(new Event('change'));

  if (tgl && tgl !== 'null') {
    document.getElementById('tanggal-keluar-status')._flatpickr?.setDate(tgl);
  }
}

/** Submit form update status via fetch. */
async function handleStatusSubmit(e) {
  e.preventDefault();

  const id = document.getElementById('siswa-id-status').value;
  const formData = new FormData(this);

  try {
    const response = await fetch(`/siswa/${id}/update-status`, {
      method: 'POST',
      body: formData,
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    });

    const res = await response.json();

    if (res.status === 'success') {
      bootstrap.Modal.getInstance(document.getElementById('modal-status-siswa'))?.hide();
      window.siswa.dt?.ajax.reload(null, false);
    } else {
      throw new Error(res.message ?? 'Gagal menyimpan status.');
    }
  } catch (err) {
    Swal.fire({ icon: 'error', title: 'Gagal', text: err.message, customClass: { confirmButton: 'btn btn-primary' } });
  }
}
