/**
 * siswa/index.js
 * Entry point — inisialisasi semua modul halaman Manajemen Siswa.
 */

import { initDataTable } from './datatable.js';
import { setModeAdd, setModeEdit } from './form.js';
import { initStatusModal, populateStatusModal } from './status.js';
import { initImport } from './import.js';
import './form-wizard.js'; // Self-initializing — cukup di-import

// ─── State global (diakses modul lain via window.siswa) ──────────────────────
window.siswa = { dt: null };

document.addEventListener('DOMContentLoaded', () => {
  // DataTable
  window.siswa.dt = initDataTable();

  // Modal Status
  initStatusModal();

  // Import Excel
  initImport();

  // Wali section — toggle tampilan saat radio berubah
  document.querySelectorAll('[name="wali_status"]').forEach(radio => {
    radio.addEventListener('change', () => {
      document.getElementById('section-wali')?.classList.toggle('d-none', radio.value !== 'lainnya');
    });
  });

  // ─── Delegasi klik untuk tombol-tombol di dalam tabel ──────────────
  document.addEventListener('click', async e => {
    // Tombol Tambah Siswa
    if (e.target.closest('#btn-tambah-siswa')) {
      setModeAdd();
      return;
    }

    // Tombol Edit Siswa
    const editBtn = e.target.closest('.btn-edit-siswa');
    if (editBtn) {
      try {
        await setModeEdit(editBtn.dataset.id);
      } catch (err) {
        console.error(err);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: err.message,
          customClass: { confirmButton: 'btn btn-primary' }
        });
      }
      return;
    }

    // Tombol Update Status
    const statusBtn = e.target.closest('.btn-update-status');
    if (statusBtn) {
      e.preventDefault();
      populateStatusModal(statusBtn);
    }
  });
});
