@extends('layouts/layoutMaster')

@section('title', 'Daftar Hadir Siswa')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')

  <div class="card">
    <div class="card-body">
      <h4 class="mb-1">Cetak Daftar Hadir Siswa</h4>
      <p class="text-muted mb-4">Pilih kelas dan bulan, lalu unduh lembar daftar hadir dalam format PDF (form kosong untuk
        diisi manual oleh guru).</p>

      <div class="row g-3">
        <div class="col-12 col-md-6">
          <label for="pilih-rombel" class="form-label">Kelas</label>
          <select id="pilih-rombel" class="form-select">
            <option value="">-- Pilih Kelas --</option>
            @foreach ($rombelList as $rombel)
              <option value="{{ $rombel->id }}" data-tahun-pelajaran="{{ $rombel->semester->tahun_pelajaran ?? '' }}"
                data-semester="{{ $rombel->semester->semester ?? '' }}">
                {{ $rombel->nama_rombel }}
                @if ($rombel->semester)
                  ({{ $rombel->semester->tahun_pelajaran }} - {{ $rombel->semester->semester }})
                @endif
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-md-6">
          <label for="pilih-bulan" class="form-label">Bulan</label>
          <select id="pilih-bulan" class="form-select" disabled>
            <option value="">-- Pilih Kelas dulu --</option>
          </select>
        </div>
      </div>

      <button id="btn-download-daftar-hadir" type="button" class="btn btn-primary mt-4" disabled>
        <i class="ri ri-file-pdf-2-line me-1"></i> Download PDF
      </button>
    </div>
  </div>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const NAMA_BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September',
        'Oktober', 'November', 'Desember'
      ];

      const pilihRombel = document.getElementById('pilih-rombel');
      const pilihBulan = document.getElementById('pilih-bulan');
      const btnDownload = document.getElementById('btn-download-daftar-hadir');

      pilihRombel.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        const tahunPelajaran = opt.dataset.tahunPelajaran;
        const semester = opt.dataset.semester;

        pilihBulan.innerHTML = '<option value="">-- Pilih Bulan --</option>';
        pilihBulan.disabled = true;
        btnDownload.disabled = true;

        if (!tahunPelajaran || !semester) return;

        const [tahunAwal, tahunAkhir] = tahunPelajaran.split('/').map(Number);
        const daftarBulan = semester.toLowerCase() === 'ganjil' ? [7, 8, 9, 10, 11, 12].map(b => ({
          bulan: b,
          tahun: tahunAwal
        })) : [1, 2, 3, 4, 5, 6].map(b => ({
          bulan: b,
          tahun: tahunAkhir
        }));

        daftarBulan.forEach(b => {
          const o = document.createElement('option');
          o.value = `${b.bulan}-${b.tahun}`;
          o.textContent = `${NAMA_BULAN[b.bulan - 1]} ${b.tahun}`;
          pilihBulan.appendChild(o);
        });

        pilihBulan.disabled = false;
      });

      pilihBulan.addEventListener('change', function() {
        btnDownload.disabled = !this.value;
      });

      const HARI_OPSI = [{
          value: 0,
          label: 'Minggu'
        },
        {
          value: 1,
          label: 'Senin'
        },
        {
          value: 2,
          label: 'Selasa'
        },
        {
          value: 3,
          label: 'Rabu'
        },
        {
          value: 4,
          label: 'Kamis'
        },
        {
          value: 5,
          label: "Jum'at"
        },
        {
          value: 6,
          label: 'Sabtu'
        },
      ];

      btnDownload.addEventListener('click', async function() {
        let liburMingguanTerpilih = [0];

        const checkboxHtml = HARI_OPSI.map(h => `
          <label class="d-flex align-items-center gap-2 mb-2" style="text-align:left;">
            <input type="checkbox" class="form-check-input chk-libur-mingguan" value="${h.value}" ${h.value === 0 ? 'checked' : ''}>
            <span>${h.label}</span>
          </label>`).join('');

        const result = await Swal.fire({
          title: 'Pilih Hari Libur Mingguan',
          html: `<div style="text-align:left;">${checkboxHtml}</div>`,
          showCancelButton: true,
          confirmButtonText: 'Download PDF',
          cancelButtonText: 'Batal',
          customClass: {
            confirmButton: 'btn btn-primary me-2',
            cancelButton: 'btn btn-outline-secondary'
          },
          buttonsStyling: false,
          didOpen: () => {
            document.querySelectorAll('.chk-libur-mingguan').forEach(chk => {
              chk.addEventListener('change', () => {
                liburMingguanTerpilih = Array.from(document.querySelectorAll(
                  '.chk-libur-mingguan:checked')).map(c => c.value);
              });
            });
          }
        });

        if (!result.isConfirmed) return;

        const [bulan, tahun] = pilihBulan.value.split('-');
        const params = new URLSearchParams({
          rombel_id: pilihRombel.value,
          bulan,
          tahun,
          libur_mingguan: liburMingguanTerpilih.join(',')
        });
        window.location.href = `/dh-siswa/cetak?${params.toString()}`;
      });
    });
  </script>
@endsection
