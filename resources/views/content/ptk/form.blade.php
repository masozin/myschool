@extends('layouts/layoutMaster')

@section('title', $ptk ? 'Ubah PTK' : 'Tambah PTK')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
  @php
    $modeUbah = $ptk !== null;
  @endphp

  {{-- ── Kepala Halaman ─────────────────────────────────────────── --}}
  <div class="d-flex align-items-center gap-3 flex-wrap mb-4">
    <a href="{{ route('ptk.index') }}" class="btn btn-icon btn-outline-secondary rounded-pill" title="Kembali ke daftar PTK">
      <i class="ri-arrow-left-line"></i>
    </a>
    <div>
      <h4 class="mb-0">{{ $modeUbah ? 'Ubah PTK' : 'Tambah PTK' }}</h4>
      <small class="text-muted">
        @if ($modeUbah)
          {{ $ptk->nama_lengkap }} — perbarui data PTK di bawah ini.
        @else
          Isi data PTK baru di bawah ini.
        @endif
      </small>
    </div>
  </div>

  <form id="form-ptk" enctype="multipart/form-data">
    {{-- ── Kartu Data Pribadi ───────────────────────────────────── --}}
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="ri-user-line me-2"></i>Data Pribadi</h5>
      </div>
      <div class="card-body">
        <div class="mb-4">
          <label class="form-label" for="photo">Foto PTK (opsional — bisa dipotong 2×3 / 3×4 / 4×6, otomatis dikompresi)</label>
          <div class="d-flex align-items-start gap-3 flex-wrap">
            <img id="photo-preview"
              src="{{ $modeUbah && $ptk->photo ? route('ptk.foto', $ptk->id) : asset('assets/img/avatars/1.png') }}"
              class="rounded object-fit-cover" width="96" height="96" alt="Pratinjau foto PTK" />
            <div class="flex-grow-1" style="min-width: 220px">
              <input type="file" class="form-control crop-foto" id="photo" name="photo" accept="image/png,image/jpeg" data-preview="#photo-preview" />
              <div class="form-check mt-2" id="hapus-photo-wrap" @if (!($modeUbah && $ptk->photo)) style="display: none" @endif>
                <input class="form-check-input" type="checkbox" id="hapus_photo" name="hapus_photo" value="1" />
                <label class="form-check-label" for="hapus_photo">Hapus foto saat ini saat disimpan</label>
              </div>
              <small class="text-muted">Foto tampil di profil pengguna, navbar, dan website sekolah.</small>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12 col-md-6 col-lg-4">
            <div class="form-floating form-floating-outline mb-4">
              <input type="text" class="form-control" id="nama_ptk" name="nama_ptk" placeholder="Nama (tanpa gelar)"
                value="{{ $ptk->nama_ptk ?? '' }}" />
              <label for="nama_ptk">Nama (tanpa gelar)</label>
            </div>
          </div>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="form-floating form-floating-outline mb-4">
              <input type="text" class="form-control" id="gelar_depan" name="gelar_depan" placeholder="Gelar Depan"
                value="{{ $ptk->gelar_depan ?? '' }}" />
              <label for="gelar_depan">Gelar Depan</label>
            </div>
          </div>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="form-floating form-floating-outline mb-4">
              <input type="text" class="form-control" id="gelar_belakang" name="gelar_belakang" placeholder="Gelar Belakang"
                value="{{ $ptk->gelar_belakang ?? '' }}" />
              <label for="gelar_belakang">Gelar Belakang</label>
            </div>
          </div>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="form-floating form-floating-outline mb-4">
              <input type="text" class="form-control" id="nik" name="nik" placeholder="NIK (16 Digit)"
                maxlength="16" inputmode="numeric" value="{{ $ptk->nik ?? '' }}" />
              <label for="nik">NIK (16 digit, opsional)</label>
            </div>
          </div>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="form-floating form-floating-outline mb-4">
              <input type="text" class="form-control" id="tempat_lahir" name="tempat_lahir"
                placeholder="Tempat Lahir" maxlength="100" value="{{ $ptk->tempat_lahir ?? '' }}" />
              <label for="tempat_lahir">Tempat Lahir (opsional)</label>
            </div>
          </div>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="form-floating form-floating-outline mb-4">
              <input type="date" class="form-control" id="tanggal_lahir" name="tanggal_lahir"
                value="{{ $ptk && $ptk->tanggal_lahir ? $ptk->tanggal_lahir->format('Y-m-d') : '' }}" />
              <label for="tanggal_lahir">Tanggal Lahir (opsional, untuk ucapan ulang tahun)</label>
            </div>
          </div>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="form-floating form-floating-outline mb-4">
              <select id="jenis_kelamin" name="jenis_kelamin" class="form-select">
                <option value="">Pilih Jenis Kelamin</option>
                <option value="L" @selected($ptk && $ptk->jenis_kelamin === 'L')>Laki-laki</option>
                <option value="P" @selected($ptk && $ptk->jenis_kelamin === 'P')>Perempuan</option>
              </select>
              <label for="jenis_kelamin">Jenis Kelamin</label>
            </div>
          </div>
          <div class="col-12">
            <div class="form-floating form-floating-outline mb-4">
              <textarea class="form-control" id="alamat" name="alamat" rows="2" maxlength="1000"
                placeholder="Alamat" style="height: 80px">{{ $ptk->alamat ?? '' }}</textarea>
              <label for="alamat">Alamat (opsional)</label>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- ── Kartu Kepegawaian ────────────────────────────────────── --}}
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="ri-briefcase-line me-2"></i>Kepegawaian</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-12 col-md-6 col-lg-4">
            <div class="form-floating form-floating-outline mb-4">
              <input type="text" class="form-control" id="nip" name="nip" placeholder="NIP (18 Digit)"
                maxlength="18" value="{{ $ptk->nip ?? '' }}" />
              <label for="nip">NIP (Opsional)</label>
            </div>
          </div>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="form-floating form-floating-outline mb-4">
              <input type="text" class="form-control" id="kode" name="kode"
                placeholder="Kode Guru (mis. GR-01)" maxlength="20" value="{{ $ptk->kode ?? '' }}" />
              <label for="kode">Kode Guru (untuk jadwal, opsional)</label>
            </div>
          </div>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="form-floating form-floating-outline mb-4">
              <select id="jabatan" name="jabatan" class="form-select">
                <option value="">pilih jabatan</option>
                <option value="Kepala Sekolah" @selected($ptk && $ptk->jabatan === 'Kepala Sekolah')>Kepala Sekolah</option>
                <option value="Operator Sekolah" @selected($ptk && $ptk->jabatan === 'Operator Sekolah')>Operator Sekolah</option>
                <option value="Guru" @selected($ptk && $ptk->jabatan === 'Guru')>Guru</option>
                <option value="Tata Usaha" @selected($ptk && $ptk->jabatan === 'Tata Usaha')>Tata Usaha</option>
                <option value="Pustakawan" @selected($ptk && $ptk->jabatan === 'Pustakawan')>Pustakawan</option>
              </select>
              <label for="jabatan">Jabatan</label>
            </div>
          </div>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="form-floating form-floating-outline mb-4">
              <select id="jenis_kepegawaian" name="jenis_kepegawaian" class="form-select">
                <option value="">pilih jenis kepegawaian</option>
                <option value="PNS" @selected($ptk && $ptk->jenis_kepegawaian === 'PNS')>PNS</option>
                <option value="PPPK" @selected($ptk && $ptk->jenis_kepegawaian === 'PPPK')>PPPK</option>
                <option value="Honorer" @selected($ptk && $ptk->jenis_kepegawaian === 'Honorer')>Honorer</option>
              </select>
              <label for="jenis_kepegawaian">Jenis kepegawaian</label>
            </div>
          </div>
          @hasrole('Admin')
            <div class="col-12 col-md-6 col-lg-4">
              <div class="form-floating form-floating-outline mb-4">
                <select id="sekolah_id" name="sekolah_id" class="select2 form-select">
                  <option value="">pilih sekolah</option>
                  @foreach ($dataSekolah as $sekolah)
                    <option value="{{ $sekolah->id }}" @selected($ptk && (int) $ptk->sekolah_id === (int) $sekolah->id)>
                      {{ $sekolah->detailPerSemester->nama_sekolah ?? 'Nama tidak diatur' }}
                      ({{ $sekolah->npsn }})
                    </option>
                  @endforeach
                </select>
                <label for="sekolah_id">Sekolah</label>
              </div>
            </div>
          @endhasrole
          <div class="col-12">
            <label class="form-label">Peran Tambahan</label>
            <div class="d-flex flex-wrap gap-3">
              @foreach (\App\Models\Ptk::PERAN_TERKELOLA as $peran)
                <div class="form-check">
                  <input class="form-check-input peran-tambahan-item" type="checkbox" name="peran_tambahan[]"
                    value="{{ $peran }}" id="peran-tambahan-{{ $loop->index }}"
                    @checked($ptk && in_array($peran, $ptk->peran_tambahan ?? [], true)) />
                  <label class="form-check-label" for="peran-tambahan-{{ $loop->index }}">{{ $peran }}</label>
                </div>
              @endforeach
            </div>
            <small class="text-muted d-block mt-2">Peran di luar jabatan utama — mis. guru yang juga operator atau bendahara
              sekolah. Boleh lebih dari satu.</small>
          </div>
        </div>
      </div>
    </div>

    {{-- ── Kartu Rekening untuk SPJ ─────────────────────────────── --}}
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="ri-bank-card-line me-2"></i>Rekening untuk SPJ</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-12 col-md-6">
            <div class="form-floating form-floating-outline mb-4">
              <input type="text" class="form-control" id="no_rekening" name="no_rekening"
                placeholder="Nomor Rekening" maxlength="30" value="{{ $ptk->no_rekening ?? '' }}" />
              <label for="no_rekening">Nomor Rekening (SPJ)</label>
            </div>
          </div>
          <div class="col-12 col-md-6">
            <div class="form-floating form-floating-outline mb-4">
              <input type="text" class="form-control" id="nama_bank" name="nama_bank"
                placeholder="Nama Bank" maxlength="100" value="{{ $ptk->nama_bank ?? '' }}" />
              <label for="nama_bank">Nama Bank (SPJ)</label>
            </div>
          </div>
        </div>
        <small class="text-muted">Dipakai untuk daftar penerima SPJ (honor ditransfer ke rekening ini).</small>
      </div>
    </div>

    {{-- ── Kartu Riwayat Kepangkatan ────────────────────────────── --}}
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0"><i class="ri-award-line me-2"></i>Riwayat Kepangkatan</h5>
      </div>
      <div class="card-body">
        @if ($modeUbah)
          <div class="table-responsive">
            <table class="table table-sm table-bordered mb-3">
              <thead>
                <tr>
                  <th>Pangkat</th>
                  <th>Golongan</th>
                  <th>TMT</th>
                  <th>Nomor SK</th>
                  <th>Tanggal SK</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="daftar-pangkat-body">
                <tr>
                  <td colspan="6" class="text-center text-muted">Memuat…</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="row g-2">
            <div class="col-12 col-md-6 col-lg-4">
              <label class="form-label" for="pangkat-baru">Pangkat</label>
              <input type="text" class="form-control form-control-sm" id="pangkat-baru"
                placeholder="mis. Penata" maxlength="100" />
            </div>
            <div class="col-12 col-md-6 col-lg-4">
              <label class="form-label" for="golongan-baru">Golongan</label>
              <input type="text" class="form-control form-control-sm" id="golongan-baru"
                placeholder="mis. III/c" maxlength="10" />
            </div>
            <div class="col-12 col-md-6 col-lg-4">
              <label class="form-label" for="tmt-baru">TMT</label>
              <input type="date" class="form-control form-control-sm" id="tmt-baru" />
            </div>
            <div class="col-12 col-md-6 col-lg-4">
              <label class="form-label" for="nomor-sk-baru">Nomor SK (opsional)</label>
              <input type="text" class="form-control form-control-sm" id="nomor-sk-baru"
                placeholder="Nomor SK" maxlength="100" />
            </div>
            <div class="col-12 col-md-6 col-lg-4">
              <label class="form-label" for="tanggal-sk-baru">Tanggal SK (opsional)</label>
              <input type="date" class="form-control form-control-sm" id="tanggal-sk-baru" />
            </div>
          </div>
          <button type="button" id="btn-tambah-pangkat" class="btn btn-outline-primary btn-sm mt-3">
            <i class="ri-add-line me-1"></i> Tambah Riwayat
          </button>
          <small class="text-muted d-block mt-2">Pangkat/golongan yang berlaku adalah entri dengan TMT
            terbaru — dipakai untuk daftar penerima SPJ.</small>
        @else
          <div class="alert alert-info mb-0">
            Riwayat kepangkatan dapat ditambahkan setelah PTK tersimpan, lewat halaman Ubah.
          </div>
        @endif
      </div>
    </div>

    {{-- ── Tombol Aksi ──────────────────────────────────────────── --}}
    <div class="card">
      <div class="card-body d-flex justify-content-end gap-2 flex-wrap">
        <a href="{{ route('ptk.index') }}" class="btn btn-outline-secondary">Batal</a>
        <button type="submit" id="submit" class="btn btn-primary">
          <i class="ri-save-line me-1"></i> Simpan PTK
        </button>
      </div>
    </div>
  </form>

  @include('_partials.crop-foto')
@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const formPtk = document.getElementById('form-ptk');
      const ptkId = @json($ptk?->id);
      const modeUbah = ptkId !== null && ptkId !== undefined;
      const urlSimpan = @json(route('ptk.store'));
      const urlIndeks = @json(route('ptk.index'));
      const csrfToken = () => document.querySelector('meta[name="csrf-token"]').getAttribute('content');

      // --- Select2 untuk pilihan sekolah (Admin) ---
      if (window.jQuery && $('.select2').length) {
        $('.select2').select2({
          placeholder: '-- pilih --'
        });
      }

      // --- Peran Tambahan (multi-role) ---
      // Checkbox yang sama dengan jabatan utama dinonaktifkan dan
      // dibersihkan: peran itu sudah tercakup sebagai peran utama.
      const segarkanPeranTambahan = () => {
        const jabatan = document.getElementById('jabatan').value;
        document.querySelectorAll('.peran-tambahan-item').forEach(cb => {
          if (cb.value === jabatan) {
            cb.checked = false;
            cb.disabled = true;
          } else {
            cb.disabled = false;
          }
        });
      };

      document.getElementById('jabatan').addEventListener('change', segarkanPeranTambahan);
      segarkanPeranTambahan();

      // --- Riwayat Kepangkatan (data SPJ, hanya mode ubah) ---
      // Input mini di sini SENGAJA tanpa atribut name agar tidak ikut
      // terkirim bersama form utama PTK.
      const badanPangkat = document.getElementById('daftar-pangkat-body');
      const idInputPangkat = ['pangkat-baru', 'golongan-baru', 'tmt-baru', 'nomor-sk-baru', 'tanggal-sk-baru'];
      const lolosHtml = teks => String(teks ?? '').replace(/[&<>"']/g, huruf => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
      }[huruf]));
      const tanggalSingkat = nilai => nilai ? String(nilai).slice(0, 10) : '-';

      const gambarPangkat = daftar => {
        if (!Array.isArray(daftar) || daftar.length === 0) {
          badanPangkat.innerHTML =
            '<tr><td colspan="6" class="text-center text-muted">Belum ada riwayat kepangkatan.</td></tr>';
          return;
        }
        badanPangkat.innerHTML = daftar.map(r => `
          <tr>
            <td>${lolosHtml(r.pangkat)}</td>
            <td>${lolosHtml(r.golongan)}</td>
            <td>${tanggalSingkat(r.tmt)}</td>
            <td>${lolosHtml(r.nomor_sk) || '-'}</td>
            <td>${r.tanggal_sk ? tanggalSingkat(r.tanggal_sk) : '-'}</td>
            <td class="text-center">
              <button type="button" class="btn btn-icon btn-text-danger btn-sm rounded-pill hapus-pangkat"
                data-id="${r.id}" title="Hapus riwayat">
                <i class="icon-base ri ri-delete-bin-7-line"></i>
              </button>
            </td>
          </tr>`).join('');
      };

      const muatPangkat = async id => {
        if (!badanPangkat) {
          return;
        }
        badanPangkat.innerHTML =
          '<tr><td colspan="6" class="text-center text-muted">Memuat…</td></tr>';
        try {
          const response = await fetch(`/ptk/${id}/pangkat`, {
            headers: {
              Accept: 'application/json'
            }
          });
          const res = await response.json();
          if (!response.ok) {
            throw new Error(res.message || 'Gagal memuat riwayat kepangkatan.');
          }
          gambarPangkat(res.data || []);
        } catch (error) {
          badanPangkat.innerHTML =
            '<tr><td colspan="6" class="text-center text-danger">Gagal memuat riwayat kepangkatan.</td></tr>';
        }
      };

      if (modeUbah && badanPangkat) {
        muatPangkat(ptkId);

        document.getElementById('btn-tambah-pangkat')?.addEventListener('click', async function() {
          const muatan = {
            pangkat: document.getElementById('pangkat-baru').value.trim(),
            golongan: document.getElementById('golongan-baru').value.trim(),
            tmt: document.getElementById('tmt-baru').value,
            nomor_sk: document.getElementById('nomor-sk-baru').value.trim() || null,
            tanggal_sk: document.getElementById('tanggal-sk-baru').value || null
          };
          if (!muatan.pangkat || !muatan.golongan || !muatan.tmt) {
            Swal.fire('Perhatian', 'Pangkat, golongan, dan TMT wajib diisi.', 'warning');
            return;
          }
          try {
            const response = await fetch(`/ptk/${ptkId}/pangkat`, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                Accept: 'application/json'
              },
              body: JSON.stringify(muatan)
            });
            const res = await response.json();
            if (!response.ok) {
              if (response.status === 422 && res.errors) {
                throw new Error(Object.values(res.errors).flat()[0]);
              }
              throw new Error(res.message || 'Gagal menambah riwayat kepangkatan.');
            }
            idInputPangkat.forEach(idInput => {
              document.getElementById(idInput).value = '';
            });
            Swal.fire('Berhasil', res.message, 'success');
            muatPangkat(ptkId);
          } catch (error) {
            Swal.fire('Gagal', error.message, 'error');
          }
        });

        badanPangkat.addEventListener('click', async function(e) {
          const pangkatBtn = e.target.closest('.hapus-pangkat');
          if (!pangkatBtn) {
            return;
          }
          const riwayatId = pangkatBtn.dataset.id;
          const konfirmasi = await Swal.fire({
            title: 'Hapus riwayat ini?',
            text: 'Entri riwayat kepangkatan ini akan dihapus.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            customClass: {
              confirmButton: 'btn btn-danger me-3',
              cancelButton: 'btn btn-outline-secondary'
            },
            buttonsStyling: false
          });

          if (konfirmasi.isConfirmed) {
            try {
              const response = await fetch(`/ptk/${ptkId}/pangkat/${riwayatId}`, {
                method: 'DELETE',
                headers: {
                  'X-CSRF-TOKEN': csrfToken(),
                  Accept: 'application/json'
                }
              });
              const res = await response.json();
              if (!response.ok) {
                throw new Error(res.message || 'Gagal menghapus riwayat kepangkatan.');
              }
              Swal.fire('Berhasil', res.message, 'success');
              muatPangkat(ptkId);
            } catch (error) {
              Swal.fire('Gagal', error.message, 'error');
            }
          }
        });
      }

      // --- Validasi sisi klien untuk pesan 422 dari server ---
      const bersihkanErrorValidasi = () => {
        formPtk.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        formPtk.querySelectorAll('.invalid-feedback[data-dibuat-js]').forEach(el => el.remove());
      };

      const tandaiInvalid = (kunci, pesan) => {
        const namaDasar = kunci.split('.')[0];
        const elemen = formPtk.querySelector(`[name="${kunci}"]`) ||
          document.getElementById(kunci) ||
          formPtk.querySelector(`[name="${namaDasar}[]"]`) ||
          document.getElementById(namaDasar);
        if (!elemen) {
          return;
        }
        elemen.classList.add('is-invalid');
        const umpanBalik = document.createElement('div');
        umpanBalik.className = 'invalid-feedback d-block';
        umpanBalik.dataset.dibuatJs = '1';
        umpanBalik.textContent = pesan;
        const wadah = elemen.closest('.form-floating') || elemen.closest('.form-check') || elemen.parentElement;
        wadah.appendChild(umpanBalik);
      };

      // --- Kirim Form (buat: POST; ubah: POST + _method=PUT, sama seperti form lama) ---
      formPtk.addEventListener('submit', async function(e) {
        e.preventDefault();
        bersihkanErrorValidasi();

        const formData = new FormData(formPtk);
        const url = modeUbah ? `/ptk/${ptkId}` : urlSimpan;
        if (modeUbah) {
          formData.append('_method', 'PUT');
        }

        const submitBtn = document.getElementById('submit');
        const isiTombolAsli = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan…';

        try {
          const response = await fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
              'X-CSRF-TOKEN': csrfToken(),
              'X-Requested-With': 'XMLHttpRequest',
              Accept: 'application/json'
            }
          });

          const res = await response.json();

          if (response.ok) {
            await Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              text: res.message,
              customClass: {
                confirmButton: 'btn btn-primary'
              }
            });
            window.location.href = urlIndeks;
          } else if (response.status === 422 && res.errors) {
            let errorList = '';
            Object.keys(res.errors).forEach(key => {
              errorList += `<li>${res.errors[key][0]}</li>`;
              tandaiInvalid(key, res.errors[key][0]);
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
          submitBtn.innerHTML = isiTombolAsli;
        }
      });
    });
  </script>
@endsection
