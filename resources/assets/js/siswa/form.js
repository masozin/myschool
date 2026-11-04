/**
 * siswa/form.js
 * Logika form tambah & edit siswa (3-step wizard).
 */

const FORM_ID = 'form-siswa';
const JUDUL_ID = 'judul-form';
const SECTION_WALI = 'section-wali';

/** Ambil elemen form utama. */
const getForm = () => document.getElementById(FORM_ID);

/** Set form ke mode Tambah. */
export function setModeAdd() {
  const form = getForm();
  const tabel = document.getElementById('tabel-siswa');

  document.getElementById(JUDUL_ID).textContent = 'Formulir Data Siswa';
  form.setAttribute('action', tabel.dataset.storeUrl);
  form.querySelector('[name="_method"]').value = 'POST';
  form.reset();
  document.getElementById(SECTION_WALI).classList.add('d-none');
}

/** Set form ke mode Edit, isi field dari data API. */
export async function setModeEdit(id) {
  const form = getForm();
  const tabel = document.getElementById('tabel-siswa');

  document.getElementById(JUDUL_ID).textContent = 'Edit Data Siswa';

  const updateUrl = tabel.dataset.updateUrl.replace('ID', id);
  form.setAttribute('action', updateUrl);
  form.querySelector('[name="_method"]').value = 'PUT';

  const response = await fetch(`/siswa/${id}/edit`);
  const res = await response.json();

  if (res.status !== 'success') throw new Error('Gagal memuat data siswa.');

  const { data, is_locked: isLocked } = res;

  // Sembunyikan pilihan sekolah saat edit (Admin tidak perlu ganti sekolah)
  if (window.hasRole?.('Admin')) {
    form.querySelector('.input-sekolah')?.classList.add('d-none');
  }

  // Step 1 — Identitas
  fillFields(form, {
    nama_siswa: data.nama_siswa,
    nik: data.nik,
    jenis_kelamin: data.jenis_kelamin,
    agama: data.agama,
    tempat_lahir: data.tempat_lahir,
    tgl_lahir: data.tgl_lahir,
    alamat: data.alamat,
    disabilitas: data.disabilitas
  });

  // Step 2 — Orang Tua
  fillFields(form, {
    nama_ayah: data.nama_ayah,
    nik_ayah: data.nik_ayah,
    tahun_ayah: data.tahun_ayah,
    pekerjaan_ayah: data.pekerjaan_ayah,
    no_hp: data.no_hp,
    nama_ibu: data.nama_ibu,
    nik_ibu: data.nik_ibu,
    tahun_ibu: data.tahun_ibu,
    pekerjaan_ibu: data.pekerjaan_ibu
  });

  // Wali
  setWaliSection(form, data);

  // Step 3 — Registrasi
  if (data.siswa_registrasi?.length) {
    const reg = data.siswa_registrasi[0];
    fillFields(form, {
      nisn: data.nisn,
      nis: reg.nis,
      jenis_pendaftaran: reg.jenis_pendaftaran,
      tingkat: reg.tingkat,
      tanggal_diterima: reg.tanggal_diterima
    });

    // Kunci field jika siswa sudah masuk rombel
    setFieldLock(form, '[name="tingkat"]', isLocked);
    setFieldLock(form, '[name="tanggal_diterima"]', isLocked);
  }
}

// ─── Helpers ────────────────────────────────────────────────────────────────

/** Isi beberapa field form sekaligus dari objek { name: value }. */
function fillFields(form, fields) {
  Object.entries(fields).forEach(([name, value]) => {
    const el = form.querySelector(`[name="${name}"]`);
    if (el) el.value = value ?? '';
  });
}

/** Tentukan & isi section wali berdasarkan relasi ke ayah/ibu. */
function setWaliSection(form, data) {
  const sectionWali = document.getElementById(SECTION_WALI);

  if (data.nama_wali === data.nama_ayah) {
    form.querySelector('#wali-ayah').checked = true;
    sectionWali.classList.add('d-none');
  } else if (data.nama_wali === data.nama_ibu) {
    form.querySelector('#wali-ibu').checked = true;
    sectionWali.classList.add('d-none');
  } else {
    form.querySelector('#wali-lainnya').checked = true;
    sectionWali.classList.remove('d-none');
    fillFields(form, {
      nama_wali: data.nama_wali,
      nik_wali: data.nik_wali,
      tahun_wali: data.tahun_wali,
      pekerjaan_wali: data.pekerjaan_wali
    });
  }
}

/** Lock / unlock field + visual feedback. */
function setFieldLock(form, selector, isLocked) {
  const el = form.querySelector(selector);
  if (!el) return;
  el.readOnly = isLocked;
  el.classList.toggle('bg-light', isLocked);
  if (isLocked) el.title = 'Dikunci karena siswa sudah masuk rombel.';
}
