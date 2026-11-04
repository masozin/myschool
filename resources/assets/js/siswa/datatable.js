/**
 * siswa/datatable.js
 * Konfigurasi dan inisialisasi DataTable untuk halaman Manajemen Siswa.
 */

/** Ekstrak teks bersih dari konten HTML (untuk export). */
function extractText(inner) {
  if (!inner?.length) return inner;
  const doc = new DOMParser().parseFromString(inner, 'text/html');
  const el = doc.querySelector('.fw-medium, .d-block');
  return (el?.textContent ?? doc.body.textContent ?? '').trim();
}

/** Konfigurasi kolom export (Excel / PDF). */
const exportColumnDef = {
  columns: [1, 2, 3, 4, 5],
  format: { body: inner => extractText(inner) }
};

/** Opsi layout DataTable. */
const layoutOptions = {
  topStart: {
    rowClass: 'row m-3 my-0 justify-content-between',
    features: [{ pageLength: { menu: [7, 10, 20, 50, 70, 100], text: '_MENU_' } }]
  },
  topEnd: {
    features: [
      { search: { placeholder: 'Cari siswa...', text: '_INPUT_' } },
      {
        buttons: [
          {
            extend: 'collection',
            className: 'btn btn-label-secondary dropdown-toggle',
            text: '<i class="icon-base ri ri-download-2-line me-2 icon-sm"></i><span class="d-none d-sm-inline-block">Download</span>',
            buttons: [
              {
                extend: 'excel',
                text: '<i class="icon-base ri ri-file-excel-line me-2"></i>Excel',
                className: 'dropdown-item',
                exportOptions: exportColumnDef
              },
              {
                extend: 'pdf',
                text: '<i class="icon-base ri ri-file-pdf-line me-2"></i>PDF',
                className: 'dropdown-item',
                exportOptions: exportColumnDef
              }
            ]
          }
        ]
      }
    ]
  },
  bottomStart: {
    rowClass: 'row mx-3 justify-content-between',
    features: [{ info: { text: 'Showing _START_ to _END_ of _TOTAL_ entries' } }]
  },
  bottomEnd: 'paging'
};

/** Inisialisasi DataTable siswa. */
export function initDataTable() {
  const tabelEl = document.getElementById('tabel-siswa');
  if (!tabelEl) return null;

  const rombelId = tabelEl.dataset.rombelId || null;

  const dt = new DataTable(tabelEl, {
    processing: true,
    serverSide: true,
    scrollX: true,
    displayLength: 7,

    ajax: {
      url: '/siswa/tabel',
      data(d) {
        if (rombelId) d.rombel_id = rombelId;
        const filterSekolah = document.getElementById('filter-sekolah-siswa');
        if (filterSekolah?.value) d.sekolah_id = filterSekolah.value;
      },
      dataSrc(json) {
        json.recordsTotal = typeof json.recordsTotal === 'number' ? json.recordsTotal : 0;
        json.recordsFiltered = typeof json.recordsFiltered === 'number' ? json.recordsFiltered : 0;
        json.data = Array.isArray(json.data) ? json.data : [];
        return json.data;
      }
    },

    columns: [
      { data: null },
      { data: 'fake_id' },
      { data: 'nama_siswa' },
      { data: 'nisn' },
      { data: 'jenis_kelamin' },
      { data: 'nama_sekolah', visible: window.hasRole?.('Admin') ?? false },
      { data: 'nama_rombel' },
      { data: 'action' }
    ],

    columnDefs: [
      {
        targets: 0,
        className: 'control',
        searchable: false,
        orderable: false,
        responsivePriority: 2,
        render: () => ''
      },
      {
        targets: 1,
        searchable: false,
        orderable: false,
        render: (_d, _t, row) => `<span>${row.fake_id}</span>`
      },
      {
        targets: -1,
        title: 'Aksi',
        searchable: false,
        orderable: false,
        render: (_d, _t, row) => `
          <div class="d-flex align-items-center gap-4">
            <button class="btn btn-icon btn-text-secondary btn-sm rounded-pill btn-edit-siswa sem-lock"
                    data-id="${row.id}"
                    data-bs-toggle="modal"
                    data-bs-target="#modal-form-siswa">
              <i class="icon-base ri ri-edit-box-line icon-22px"></i>
            </button>
            <button class="btn btn-icon btn-text-secondary btn-sm rounded-pill dropdown-toggle hide-arrow sem-lock"
                    data-bs-toggle="dropdown">
              <i class="icon-base ri ri-more-2-line icon-22px"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end m-0">
              <a href="#" class="dropdown-item btn-update-status"
                 data-id="${row.id}"
                 data-status="${row.status ?? ''}"
                 data-tgl-keluar="${row.tgl_keluar ?? ''}"
                 data-bs-toggle="modal"
                 data-bs-target="#modal-status-siswa">
                Status
              </a>
            </div>
          </div>
        `
      }
    ],

    order: [[2, 'desc']],
    layout: layoutOptions,

    language: {
      paginate: {
        next: '<i class="icon-base ri ri-arrow-right-s-line scaleX-n1-rtl icon-22px"></i>',
        previous: '<i class="icon-base ri ri-arrow-left-s-line scaleX-n1-rtl icon-22px"></i>',
        first: '<i class="icon-base ri ri-skip-back-mini-line scaleX-n1-rtl icon-22px"></i>',
        last: '<i class="icon-base ri ri-skip-forward-mini-line scaleX-n1-rtl icon-22px"></i>'
      }
    },

    responsive: {
      details: {
        display: DataTable.Responsive.display.modal({
          header: row => 'Detail ' + row.data().nama_siswa
        }),
        type: 'column',
        renderer(api, rowIdx, columns) {
          const rows = columns
            .filter(col => col.title !== '')
            .map(
              col => `
              <tr data-dt-row="${col.rowIndex}" data-dt-column="${col.columnIndex}">
                <td>${col.title}:</td><td>${col.data}</td>
              </tr>`
            )
            .join('');

          if (!rows) return false;

          const div = document.createElement('div');
          div.classList.add('table-responsive');
          div.innerHTML = `<table class="table"><tbody>${rows}</tbody></table>`;
          return div;
        }
      }
    },

    initComplete() {
      applyDtLayoutFixes();
      if (window.hasRole?.('Admin')) initFilterSekolah(this.api());
    }
  });

  return dt;
}

/** Perbaikan kelas CSS layout DataTable setelah render. */
function applyDtLayoutFixes() {
  const fixes = [
    { selector: '.dt-buttons .btn', remove: 'btn-secondary' },
    { selector: '.dt-length .form-select', add: 'ms-0' },
    { selector: '.dt-length', add: 'mb-md-5 mb-0' },
    {
      selector: '.dt-layout-end',
      remove: 'justify-content-between',
      add: 'd-flex gap-md-4 justify-content-md-between justify-content-center flex-wrap mt-0'
    },
    { selector: '.dt-layout-start', add: 'mt-md-0 mt-5' },
    { selector: '.dt-layout-start .dt-buttons', add: 'd-md-flex d-block gap-4 justify-content-center' },
    { selector: '.dt-layout-end .dt-buttons', add: 'd-md-flex d-block gap-4 mb-md-0 mb-5 justify-content-center' },
    { selector: '.dt-layout-table', remove: 'row mt-2' },
    { selector: '.dt-layout-full', remove: 'col-md col-12' }
  ];

  setTimeout(() => {
    fixes.forEach(({ selector, remove, add }) => {
      document.querySelectorAll(selector).forEach(el => {
        remove?.split(' ').forEach(c => el.classList.remove(c));
        add?.split(' ').forEach(c => el.classList.add(c));
      });
    });
  }, 100);
}

/** Inisialisasi filter dropdown Sekolah khusus Admin. */
function initFilterSekolah(api) {
  const wrapper = document.createElement('div');
  wrapper.className = 'd-flex align-items-center ms-2';
  wrapper.innerHTML = `
    <select id="filter-sekolah-siswa" class="form-select form-select-sm" style="width:220px;">
      <option value="">Semua Sekolah</option>
    </select>`;

  document.querySelector('.dt-layout-end')?.prepend(wrapper);

  const select = document.getElementById('filter-sekolah-siswa');

  // Isi opsi dari endpoint
  fetch('/tabel-sekolah', { headers: { Accept: 'application/json' } })
    .then(r => r.json())
    .then(res => {
      (res.data ?? res).forEach(s => {
        const opt = new Option(`${s.nama_sekolah ?? s.npsn} (${s.npsn})`, s.id);
        select.appendChild(opt);
      });
    })
    .catch(() => {
      // Fallback: ambil dari data kolom tabel
      const names = new Set();
      api
        .column(5, { page: 'all' })
        .data()
        .each(val => {
          const text = val?.replace(/<[^>]*>/g, '').trim();
          if (text && text !== 'Detail Belum Terisi') names.add(text);
        });
      names.forEach(nama => select.appendChild(new Option(nama, nama)));
    });

  select.addEventListener('change', () => window.siswa.dt?.ajax.reload());
}
