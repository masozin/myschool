{{-- resources/views/operator/profil-sekolah/edit.blade.php --}}
@extends('layouts/layoutMaster')
@section('title', 'Profil Sekolah')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
  <div class="card">
    <div class="card-header border-bottom">
      <h5 class="card-title mb-0">Profil Sekolah</h5>
      <small class="text-muted">NPSN: {{ $sekolah->npsn }} — Jenjang: {{ $sekolah->jenjang }}</small>
    </div>
    <div class="card-body">
      <form id="form-profil-sekolah" class="row g-5">
        @csrf
        <div class="col-12 form-control-validation">
          <div class="form-floating form-floating-outline">
            <input type="text" id="nama_sekolah" name="nama_sekolah" class="form-control"
              value="{{ old('nama_sekolah', $detail->nama_sekolah ?? '') }}" placeholder="SDN 01 Totorejo" />
            <label for="nama_sekolah">Nama Sekolah</label>
          </div>
        </div>

        <div class="fi-hr"></div>

        @foreach ([['province', 'Provinsi'], ['regency', 'Kabupaten'], ['district', 'Kecamatan'], ['village', 'Desa/Kelurahan']] as [$id, $label])
          <div class="col-12 col-md-6">
            <div class="form-floating form-floating-outline">
              <select id="{{ $id }}" name="{{ $id }}" class="select2 form-select">
                <option value="">Pilih {{ $label }}</option>
                @if ($id === 'province')
                  @foreach ($provinces as $p)
                    <option value="{{ $p->code }}" {{ ($detail->province_id ?? '') == $p->code ? 'selected' : '' }}>
                      {{ $p->name }}
                    </option>
                  @endforeach
                @endif
              </select>
              <label for="{{ $id }}">{{ $label }}</label>
            </div>
          </div>
        @endforeach

        <div class="col-12 form-control-validation">
          <div class="form-floating form-floating-outline">
            <input type="text" id="alamat_sekolah" name="alamat_sekolah" class="form-control"
              value="{{ old('alamat_sekolah', $detail->alamat_sekolah ?? '') }}" placeholder="Jl. Raya No. 123" />
            <label for="alamat_sekolah">Alamat</label>
          </div>
        </div>

        <div class="fi-hr"></div>

        <div class="col-12 col-md-6 form-control-validation">
          <div class="form-floating form-floating-outline">
            <input type="text" id="kepala_sekolah" name="kepala_sekolah" class="form-control"
              value="{{ old('kepala_sekolah', $detail->kepala_sekolah ?? '') }}" placeholder="Nama Kepala Sekolah" />
            <label for="kepala_sekolah">Kepala Sekolah</label>
          </div>
        </div>
        <div class="col-12 col-md-6 form-control-validation">
          <div class="form-floating form-floating-outline">
            <input type="text" id="nip" name="nip" class="form-control"
              value="{{ old('nip', $detail->nip ?? '') }}" placeholder="NIP" />
            <label for="nip">NIP</label>
          </div>
        </div>

        <div class="col-12 col-md-6">
          <div class="form-floating form-floating-outline">
            <input type="text" id="no_telp" name="no_telp" class="form-control"
              value="{{ old('no_telp', $detail->no_telp ?? '') }}" placeholder="08xxxxxxxxxx" />
            <label for="no_telp">No. Telp</label>
          </div>
        </div>
        <div class="col-12 col-md-6">
          <div class="form-floating form-floating-outline">
            <input type="email" id="email" name="email" class="form-control"
              value="{{ old('email', $detail->email ?? '') }}" placeholder="sekolah@email.com" />
            <label for="email">Email</label>
          </div>
        </div>

        <div class="fi-hr"></div>

        {{-- Libur Mingguan --}}
        <div class="col-12">
          <label class="form-label fw-medium">Hari Libur Mingguan</label>
          <div class="d-flex flex-wrap gap-4">
            @php $liburTerpilih = old('libur_mingguan', $detail->libur_mingguan ?? []); @endphp
            @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $hari)
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="libur_mingguan[]" value="{{ $hari }}"
                  id="libur-{{ $hari }}" {{ in_array($hari, $liburTerpilih) ? 'checked' : '' }}>
                <label class="form-check-label" for="libur-{{ $hari }}">{{ $hari }}</label>
              </div>
            @endforeach
          </div>
          <small class="text-muted">Dipakai otomatis saat generate kalender pendidikan, presensi, dan leger.</small>
        </div>

        <div class="col-12">
          <button type="submit" id="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const csrf = () => document.querySelector('meta[name="csrf-token"]').content;

      $('.select2').select2({
        placeholder: '-- pilih --'
      });

      async function loadDropdown(url, id, selectedValue = null) {
        const el = document.getElementById(id);
        const data = await fetch(url).then(r => r.json());
        el.innerHTML = '<option value="">Pilih...</option>';
        data.forEach(({
          code: val,
          name
        }) => {
          el.appendChild(new Option(name, val, false, val == selectedValue));
        });
        $(el).trigger('change');
      }

      // Load cascade awal jika sudah ada data tersimpan
      (async () => {
        const province = "{{ $detail->province_id ?? '' }}";
        const regency = "{{ $detail->regency_id ?? '' }}";
        const district = "{{ $detail->district_id ?? '' }}";
        const village = "{{ $detail->village_id ?? '' }}";

        if (province) {
          await loadDropdown(`/get-kabupaten/${province}`, 'regency', regency);
          if (regency) await loadDropdown(`/get-kecamatan/${regency}`, 'district', district);
          if (district) await loadDropdown(`/get-desa/${district}`, 'village', village);
        }
      })();

      $('#province').on('change', function() {
        const id = $(this).val();
        if (id) loadDropdown(`/get-kabupaten/${id}`, 'regency');
      });
      $('#regency').on('change', function() {
        const id = $(this).val();
        if (id) loadDropdown(`/get-kecamatan/${id}`, 'district');
      });
      $('#district').on('change', function() {
        const id = $(this).val();
        if (id) loadDropdown(`/get-desa/${id}`, 'village');
      });

      const formEl = document.getElementById('form-profil-sekolah');
      const submitBtn = document.getElementById('submit');

      formEl.addEventListener('submit', async function(e) {
        e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

        const formData = new FormData(formEl);
        formData.append('_method', 'PUT');

        try {
          const res = await fetch("{{ route('profil-sekolah.update') }}", {
            method: 'POST',
            body: formData,
            headers: {
              'X-CSRF-TOKEN': csrf(),
              'X-Requested-With': 'XMLHttpRequest'
            }
          });
          const data = await res.json();

          if (res.ok) {
            Swal.fire({
              icon: 'success',
              title: 'Berhasil!',
              text: data.message,
              customClass: {
                confirmButton: 'btn btn-success'
              }
            });
          } else if (res.status === 422) {
            const list = Object.values(data.errors).map(e => `<li>${e[0]}</li>`).join('');
            Swal.fire({
              icon: 'error',
              title: 'Gagal Validasi',
              html: `<ul style="text-align:left">${list}</ul>`,
              customClass: {
                confirmButton: 'btn btn-primary'
              }
            });
          } else {
            throw new Error(data.message || 'Terjadi kesalahan sistem');
          }
        } catch (err) {
          Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: err.message,
            customClass: {
              confirmButton: 'btn btn-primary'
            }
          });
        } finally {
          submitBtn.disabled = false;
          submitBtn.innerHTML = 'Simpan Perubahan';
        }
      });
    });
  </script>
@endsection
