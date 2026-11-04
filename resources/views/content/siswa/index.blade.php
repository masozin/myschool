@extends('layouts/layoutMaster')

@section('title', 'Manajemen Siswa')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')

{{-- Info header --}}
<div class="mb-4">
  <div class="card h-100">
    <div class="card-body d-flex align-items-center gap-4 py-4">
      <div class="avatar avatar-xl flex-shrink-0">
        <span class="avatar-initial rounded-circle bg-label-primary">
          <i class="icon-base ri ri-group-line ri-28px"></i>
        </span>
      </div>
      <div>
        <h4 class="mb-1">
          @isset($rombel)
          Data Siswa {{ $rombel->nama_rombel }}
          @else
          Data Siswa
          @endisset
        </h4>
        <div class="d-flex gap-4 flex-wrap">
          <span class="text-muted"><i class="icon-base ri ri-team-line me-1"></i><span id="info-total-siswa">0</span>
            Siswa</span>
          <span class="text-muted"><i class="icon-base ri ri-men-line me-1 text-primary"></i><span
              id="info-laki">0</span>
            <span class="d-none d-lg-inline-block">Laki-laki</span></span>
          <span class="text-muted"><i class="icon-base ri ri-women-line me-1 text-danger"></i><span
              id="info-perempuan">0</span>
            <span class="d-none d-lg-inline-block">Perempuan</span></span>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Toolbar --}}
<div class="card mb-4">
  <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div class="d-flex gap-2 flex-wrap">
      @hasrole('Admin')
      <select id="filter_sekolah" class="form-select form-select-sm" style="width:200px">
        <option value="">Semua Sekolah</option>
        @foreach ($sekolahs as $s)
        <option value="{{ $s->id }}">{{ $s->detailTerbaru->nama_sekolah ?? 'Sekolah ' . $s->id }}</option>
        @endforeach
      </select>
      @endhasrole

      @hasrole('Operator Sekolah')
      <select id="filter_rombel" class="form-select form-select-sm" style="width:150px">
        <option value="">Semua Rombel</option>
        @foreach ($rombels as $r)
        <option value="{{ $r->id }}">{{ $r->nama_rombel }}</option>
        @endforeach
        <option value="Tanpa Rombel">Tanpa Rombel</option>
      </select>
      @endhasrole
    </div>

    @unless (isset($rombel))
    <!-- Tampilan Layar Sedang & Besar (sm ke atas) -->
    <div class="d-none d-sm-flex gap-2">
      <button id="tambah-siswa" class="btn btn-primary btn-sm" data-bs-toggle="modal"
        data-bs-target="#modal-form-siswa">
        <i class="ri ri-add-line me-1"></i>
        <span class="d-none d-lg-inline-block">Tambah Siswa</span>
      </button>
      <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#modal-import-siswa">
        <i class="ri ri-file-excel-line me-1"></i>
        <span class="d-none d-lg-inline-block">Impor Siswa</span>
      </button>
      <button class="btn btn-secondary btn-sm" id="btn-export-excel">
        <i class="ri ri-download-2-line me-1"></i>
        <span class="d-none d-lg-inline-block">Download</span>
      </button>
    </div>

    <!-- Tampilan Layar Kecil (Dibawah sm / Mobile) -->
    <div class="dropdown d-sm-none">
      <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown"
        aria-expanded="false">
        <i class="ri ri-more-2-fill me-1"></i> Menu
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li>
          <button class="dropdown-item text-primary btn-tambah-siswa" data-bs-toggle="modal"
            data-bs-target="#modal-form-siswa">
            <i class="ri ri-add-line me-2"></i> Tambah Siswa
          </button>
        </li>
        <li>
          <button class="dropdown-item text-success" data-bs-toggle="modal" data-bs-target="#modal-import-siswa">
            <i class="ri ri-file-excel-line me-2"></i> Impor Siswa
          </button>
        </li>
        <li>
          <hr class="dropdown-divider">
        </li>
        <li>
          <button class="dropdown-item text-secondary btn-export-excel">
            <i class="ri ri-download-2-line me-2"></i> Download
          </button>
        </li>
      </ul>
    </div>
    @endunless
  </div>
</div>

<div class="card">
  <div class="card-datatable text-nowrap">
    <table class="datatables-basic table table-bordered" id="tabel-siswa" data-rombel-id="{{ $rombel->id ?? '' }}">
      <thead>
        <tr>
          <th>No</th>
          <th>Nama Siswa</th>
          <th>TTL</th>
          <th>Usia</th>
          <th>L/P</th>
          <th>Sekolah</th>
          <th>Rombel</th>
          <th>Aksi</th>
        </tr>
      </thead>
    </table>
  </div>
</div>


{{-- =========================================================== --}}
{{-- MODAL: Form Tambah / Edit Siswa (wizard stepper)            --}}
{{-- =========================================================== --}}
<div class="modal fade" id="modal-form-siswa" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-simple">
    <div class="modal-content">
      <div class="modal-body p-0">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        <div class="text-center mb-6">
          <h4 class="mb-2" id="judul_form">Formulir Data Siswa</h4>
          <p>Lengkapi data siswa, orang tua, dan registrasi sekolah.</p>
        </div>

        <div class="col-12 mb-6">
          <div id="wizard-validation" class="bs-stepper mt-2">
            <div class="bs-stepper-header">
              <div class="step" data-target="#data-siswa">
                <button type="button" class="step-trigger">
                  <span class="bs-stepper-circle"><i class="ri ri-check-line"></i></span>
                  <span class="bs-stepper-label">
                    <span class="bs-stepper-number">01</span>
                    <span class="d-flex flex-column ms-2 d-none d-sm-block">
                      <span class="bs-stepper-subtitle fs-6">Identitas Siswa</span>
                    </span>
                  </span>
                </button>
              </div>
              <div class="line"></div>
              <div class="step" data-target="#data-orang-tua">
                <button type="button" class="step-trigger">
                  <span class="bs-stepper-circle"><i class="ri ri-check-line"></i></span>
                  <span class="bs-stepper-label">
                    <span class="bs-stepper-number">02</span>
                    <span class="d-flex flex-column ms-2 d-none d-sm-block">
                      <span class="bs-stepper-subtitle fs-6">Orang Tua</span>
                    </span>
                  </span>
                </button>
              </div>
              <div class="line"></div>
              <div class="step" data-target="#registrasi-siswa">
                <button type="button" class="step-trigger">
                  <span class="bs-stepper-circle"><i class="ri ri-check-line"></i></span>
                  <span class="bs-stepper-label">
                    <span class="bs-stepper-number">03</span>
                    <span class="d-flex flex-column ms-2 d-none d-sm-block">
                      <span class="bs-stepper-subtitle fs-6">Registrasi</span>
                    </span>
                  </span>
                </button>
              </div>
            </div>

            <div class="bs-stepper-content">
              <form id="form-tambah-siswa" enctype="multipart/form-data"
                data-siswa-store="{{ route('siswa.store') }}"
                data-siswa-update="{{ route('siswa.update', ['id' => 'ID']) }}" onSubmit="return false">
                @csrf
                <input type="hidden" name="_method" />

                {{-- Step 1: Identitas Siswa --}}
                <div id="data-siswa" class="content">
                  <div class="row g-4">
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="nama_siswa" id="nama_siswa" class="form-control"
                          placeholder="Nama Lengkap" />
                        <label for="nama_siswa">Nama Siswa</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="nik" id="nik" class="form-control"
                          placeholder="16 Digit" maxlength="16" />
                        <label for="nik">NIK</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <select name="jenis_kelamin" id="jenis_kelamin" class="form-select">
                          <option value="">Pilih</option>
                          <option value="L">Laki-laki</option>
                          <option value="P">Perempuan</option>
                        </select>
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <select name="agama" id="agama" class="form-select">
                          <option value="Islam">Islam</option>
                          <option value="Kristen">Kristen</option>
                          <option value="Katolik">Katolik</option>
                          <option value="Hindu">Hindu</option>
                          <option value="Buddha">Buddha</option>
                          <option value="Khonghucu">Khonghucu</option>
                        </select>
                        <label for="agama">Agama</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="tempat_lahir" id="tempat_lahir" class="form-control"
                          placeholder="Kota Lahir" />
                        <label for="tempat_lahir">Tempat Lahir</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="tgl_lahir" id="tgl_lahir" class="form-control flatpickr-date"
                          placeholder="YYYY-MM-DD" />
                        <label for="tgl_lahir">Tanggal Lahir</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="alamat" id="alamat" class="form-control"
                          placeholder="Jl. ..." />
                        <label for="alamat">Alamat</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="disabilitas" id="disabilitas" class="form-control"
                          placeholder="Jika ada" />
                        <label for="disabilitas">Disabilitas</label>
                      </div>
                    </div>

                    {{-- Foto --}}
                    <div class="col-12 d-flex justify-content-center mb-2">
                      <img src="/assets/img/avatars/1.png" id="preview-photo" class="rounded shadow" width="120"
                        height="120" style="object-fit:cover;">
                    </div>
                    <div class="col-12 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="file" name="photo" id="photo" class="form-control" accept="image/*"
                          onchange="document.getElementById('preview-photo').src = window.URL.createObjectURL(this.files[0])" />
                        <label for="photo">Foto Siswa</label>
                      </div>
                    </div>

                    <div class="col-12 d-flex justify-content-between">
                      <button type="button" class="btn btn-outline-secondary btn-prev" disabled>
                        <i class="ri ri-arrow-left-line me-1"></i> Previous
                      </button>
                      <button type="button" class="btn btn-primary btn-next">
                        Next <i class="ri ri-arrow-right-line ms-1"></i>
                      </button>
                    </div>
                  </div>
                </div>

                {{-- Step 2: Orang Tua --}}
                <div id="data-orang-tua" class="content">
                  <h6 class="fw-bold mb-4">1. Data Ayah</h6>
                  <div class="row g-4">
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="nama_ayah" id="nama_ayah" class="form-control"
                          placeholder="Nama Ayah" />
                        <label for="nama_ayah">Nama Ayah</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="nik_ayah" id="nik_ayah" class="form-control" maxlength="16"
                          placeholder="16 digit" />
                        <label for="nik_ayah">NIK Ayah</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="number" name="tahun_ayah" id="tahun_ayah" class="form-control"
                          placeholder="1980" />
                        <label for="tahun_ayah">Tahun Lahir</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="pekerjaan_ayah" id="pekerjaan_ayah" class="form-control"
                          placeholder="Pekerjaan" />
                        <label for="pekerjaan_ayah">Pekerjaan Ayah</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="no_hp" id="no_hp" class="form-control"
                          placeholder="08..." />
                        <label for="no_hp">No. Handphone</label>
                      </div>
                    </div>
                  </div>

                  <hr class="my-6 mx-n4" />
                  <h6 class="fw-bold mb-4">2. Data Ibu</h6>
                  <div class="row g-4">
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="nama_ibu" id="nama_ibu" class="form-control"
                          placeholder="Nama Ibu" />
                        <label for="nama_ibu">Nama Ibu</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="nik_ibu" id="nik_ibu" class="form-control" maxlength="16"
                          placeholder="16 digit" />
                        <label for="nik_ibu">NIK Ibu</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="number" name="tahun_ibu" id="tahun_ibu" class="form-control"
                          placeholder="1982" />
                        <label for="tahun_ibu">Tahun Lahir</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="pekerjaan_ibu" id="pekerjaan_ibu" class="form-control"
                          placeholder="Pekerjaan" />
                        <label for="pekerjaan_ibu">Pekerjaan Ibu</label>
                      </div>
                    </div>
                  </div>

                  <hr class="my-6 mx-n4" />
                  <h6 class="fw-bold mb-4">3. Wali Siswa</h6>
                  <div class="d-sm-flex gap-3 mb-3">
                    <div class="form-check">
                      <input name="wali_status" class="form-check-input" type="radio" value="ayah"
                        id="waliAyah" checked />
                      <label class="form-check-label" for="waliAyah">Sama dengan Ayah</label>
                    </div>
                    <div class="form-check">
                      <input name="wali_status" class="form-check-input" type="radio" value="ibu"
                        id="waliIbu" />
                      <label class="form-check-label" for="waliIbu">Sama dengan Ibu</label>
                    </div>
                    <div class="form-check">
                      <input name="wali_status" class="form-check-input" type="radio" value="lainnya"
                        id="waliLainnya" />
                      <label class="form-check-label" for="waliLainnya">Lainnya</label>
                    </div>
                  </div>

                  <div id="section-wali" class="row g-4 d-none">
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="nama_wali" id="nama_wali" class="form-control"
                          placeholder="Nama Wali" />
                        <label for="nama_wali">Nama Wali</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="nik_wali" id="nik_wali" class="form-control" maxlength="16"
                          placeholder="16 digit" />
                        <label for="nik_wali">NIK Wali</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="number" name="tahun_wali" id="tahun_wali" class="form-control"
                          placeholder="1975" />
                        <label for="tahun_wali">Tahun Lahir</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="pekerjaan_wali" id="pekerjaan_wali" class="form-control"
                          placeholder="Pekerjaan" />
                        <label for="pekerjaan_wali">Pekerjaan Wali</label>
                      </div>
                    </div>
                  </div>

                  <div class="col-12 d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-outline-secondary btn-prev">
                      <i class="ri ri-arrow-left-line me-1"></i> Previous
                    </button>
                    <button type="button" class="btn btn-primary btn-next">
                      Next <i class="ri ri-arrow-right-line ms-1"></i>
                    </button>
                  </div>
                </div>

                {{-- Step 3: Registrasi --}}
                <div id="registrasi-siswa" class="content">
                  <div class="row g-4">
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="nisn" id="nisn" class="form-control"
                          placeholder="10 Digit" maxlength="10" />
                        <label for="nisn">NISN</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="nis" id="nis" class="form-control" />
                        <label for="nis">NIS (Nomor Induk Sekolah)</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <select name="jenis_pendaftaran" id="jenis_pendaftaran" class="form-select">
                          <option value="Baru">Baru</option>
                          <option value="Pindahan">Pindahan</option>
                        </select>
                        <label for="jenis_pendaftaran">Jenis Pendaftaran</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="number" name="tingkat" id="tingkat" class="form-control" max="12"
                          placeholder="1, 7, 10..." />
                        <label for="tingkat">Tingkat / Kelas</label>
                      </div>
                    </div>
                    <div class="col-sm-6 form-control-validation">
                      <div class="form-floating form-floating-outline">
                        <input type="text" name="tanggal_diterima" id="tanggal_diterima"
                          class="form-control flatpickr-date" />
                        <label for="tanggal_diterima">Tanggal Diterima</label>
                      </div>
                    </div>

                    @hasrole('Admin')
                    <div class="col-sm-6 form-control-validation input-sekolah">
                      <div class="form-floating form-floating-outline">
                        <select name="sekolah_id" id="sekolah_id" class="form-select select2">
                          <option value="">Pilih Sekolah</option>
                          @foreach ($sekolahs as $sekolah)
                          <option value="{{ $sekolah->id }}">{{ $sekolah->detailTerbaru->nama_sekolah }}</option>
                          @endforeach
                        </select>
                        <label for="sekolah_id">Sekolah</label>
                      </div>
                    </div>
                    @endhasrole

                    <div class="col-12 d-flex justify-content-between mt-4">
                      <button type="button" class="btn btn-outline-secondary btn-prev">
                        <i class="ri ri-arrow-left-line me-1"></i> Previous
                      </button>
                      <button type="submit" class="btn btn-primary">
                        <i class="ri ri-save-line me-1"></i> Simpan
                      </button>
                    </div>
                  </div>
                </div>

              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- =========================================================== --}}
{{-- MODAL: Update Status Siswa                                   --}}
{{-- =========================================================== --}}
<div class="modal fade" id="modal-status-siswa" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Status Siswa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="form-update-status">
        @csrf
        <input type="hidden" name="siswa_id_status" id="siswa_id_status">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <div class="form-floating form-floating-outline">
                <select id="status_registrasi" name="status" class="form-select">
                  <option value="aktif">Aktif</option>
                  <option value="mutasi">Mutasi</option>
                  <option value="lulus">Lulus</option>
                  <option value="keluar">Keluar</option>
                  <option value="meninggal">Meninggal</option>
                </select>
                <label for="status_registrasi">Status Siswa</label>
              </div>
            </div>
            <div id="container-tgl-keluar" class="col-12 d-none">
              <div class="form-floating form-floating-outline">
                <input type="text" name="tanggal_keluar" id="tanggal_keluar_status"
                  class="form-control flatpickr-date" placeholder="YYYY-MM-DD" />
                <label for="tanggal_keluar_status">Tanggal Status</label>
              </div>
            </div>
            <div id="container-alasan" class="col-12 d-none">
              <div class="form-floating form-floating-outline">
                <input type="text" name="alasan" id="alasan" class="form-control" placeholder="Alasan" />
                <label for="alasan">Alasan</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan Status</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- =========================================================== --}}
{{-- MODAL: Import Siswa dari Excel                               --}}
{{-- =========================================================== --}}
<div class="modal fade" id="modal-import-siswa" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="ri ri-file-excel-line me-2 text-success"></i>Import Siswa dari Excel
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info d-flex align-items-start gap-3 mb-4">
          <i class="ri ri-information-line ri-24px mt-1 flex-shrink-0"></i>
          <div>
            <div class="fw-semibold mb-1">Langkah Pengisian</div>
            <ol class="mb-2 ps-3">
              <li>Download template Excel di bawah</li>
              <li>Isi data siswa sesuai format (lihat sheet <strong>Panduan</strong>)</li>
              <li>Hapus baris contoh (baris ke-3) sebelum upload</li>
              <li>Upload file yang sudah diisi</li>
            </ol>
            <a href="/siswa/download-template" class="btn btn-sm btn-success">
              <i class="ri ri-download-line me-1"></i> Download Template Excel
            </a>
          </div>
        </div>

        <form id="form-import-siswa" enctype="multipart/form-data">
          @csrf
          @hasrole('Admin')
          <div class="mb-4">
            <div class="form-floating form-floating-outline">
              <select name="sekolah_id" id="import-sekolah-id" class="form-select">
                <option value="">-- Pilih Sekolah --</option>
                @foreach ($sekolahs as $sekolah)
                <option value="{{ $sekolah->id }}">
                  {{ $sekolah->detailTerbaru->nama_sekolah ?? 'Sekolah ' . $sekolah->npsn }}
                  ({{ $sekolah->npsn }})
                </option>
                @endforeach
              </select>
              <label for="import-sekolah-id">Sekolah</label>
            </div>
          </div>
          @endhasrole

          <div class="mb-4">
            <label class="form-label fw-semibold">File Excel <span class="text-danger">*</span></label>
            <input type="file" name="file_excel" id="file-excel-input" class="form-control" accept=".xlsx,.xls">
            <div class="form-text text-muted">Format: .xlsx atau .xls | Maksimal: 5MB</div>
          </div>

          <div id="import-progress" class="d-none mb-4">
            <div class="d-flex justify-content-between mb-1">
              <small class="text-muted">Memproses file...</small>
            </div>
            <div class="progress" style="height:8px">
              <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary w-100"></div>
            </div>
          </div>
        </form>

        <div id="import-error-report" class="d-none">
          <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
            <i class="ri ri-error-warning-line ri-20px flex-shrink-0"></i>
            <div>
              Import dibatalkan. Perbaiki semua kesalahan berikut lalu upload ulang.
              <span class="badge bg-danger ms-2" id="error-count-badge">0 error</span>
            </div>
          </div>
          <div class="table-responsive" style="max-height:350px;overflow-y:auto">
            <table class="table table-sm table-bordered table-hover">
              <thead class="table-danger sticky-top">
                <tr>
                  <th width="70">Baris</th>
                  <th width="150">Kolom</th>
                  <th width="180">Nilai</th>
                  <th>Keterangan Error</th>
                </tr>
              </thead>
              <tbody id="error-report-body"></tbody>
            </table>
          </div>
        </div>

        <div id="import-success-report" class="d-none">
          <div class="alert alert-success d-flex align-items-center gap-2">
            <i class="ri ri-checkbox-circle-line ri-20px flex-shrink-0"></i>
            <div id="import-success-message"></div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-primary" id="btn-proses-import">
          <i class="ri ri-upload-line me-1"></i> Proses Import
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('page-script')
<script>
  // Escape HTML untuk mencegah XSS saat data dinamis disisipkan ke innerHTML
  function esc(str) {
    return String(str ?? '').replace(/[&<>"']/g, c => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#39;'
    } [c]));
  }

  window.siswa = {
    saveMode: 'add',
    id: null,
    dtSiswa: null
  };

  document.addEventListener('DOMContentLoaded', function(e) {

    // ── Flatpickr untuk form status ─────────────────────────────────────────
    const formStatusSiswa = document.getElementById('form-update-status');
    formStatusSiswa.querySelectorAll('.flatpickr-date').forEach(el => {
      flatpickr(el, {
        dateFormat: 'Y-m-d',
        monthSelectorType: 'static',
        static: true
      });
    });

    // Custom filter: cocokkan berdasarkan rombel_id, bukan teks nama rombel yang dirender
    $.fn.dataTable.ext.search.push(function(settings, searchData, dataIndex, rowData) {
      if (settings.nTable.id !== 'tabel-siswa') return true; // jangan pengaruhi tabel lain di halaman

      const selected = $('#filter_rombel').val();
      if (!selected) return true; // "Semua Rombel"

      if (selected === 'Tanpa Rombel') {
        return !rowData.rombel_id;
      }
      return String(rowData.rombel_id) === String(selected);
    });

    // ── DataTable Siswa ─────────────────────────────────────────────────────
    const tabelSiswa = document.querySelector('#tabel-siswa');

    if (tabelSiswa) {
      window.siswa.dtSiswa = new DataTable(tabelSiswa, {
        processing: true,
        serverSide: window.hasRole('Admin'),
        scrollX: true,
        ajax: {
          url: "{{ route('siswa.tabel') }}",
          data: function(d) {
            if (!window.hasRole('Admin')) d.length = -1;
            d.sekolah_id = $('#filter_sekolah').val();
            d.rombel_id = $('#filter_rombel').val();
          },
          dataSrc: function(json) {
            json.recordsTotal = typeof json.recordsTotal === 'number' ? json.recordsTotal : 0;
            json.recordsFiltered = typeof json.recordsFiltered === 'number' ? json.recordsFiltered : 0;
            json.data = Array.isArray(json.data) ? json.data : [];
            return json.data;
          }
        },
        columns: [
          // 0 – Nomor
          {
            data: 'fake_id',
            searchable: false,
            orderable: false,
            render: (data, type, full) =>
              window.hasRole('Admin') ? `<span>${full.fake_id}</span>` : ''
          },
          // 1 – Foto + Nama + NISN/NIS
          {
            data: 'nama_siswa',
            render: function(data, type, full) {
              const nama = esc(full.nama_siswa);
              const detailUrl = `/siswa/${full.id}/show`;
              const photo = full.photo;
              const jk = full.jenis_kelamin;

              const parts = full.nama_siswa.trim().split(/\s+/);
              const initials = ((parts[0]?.[0] ?? '') + (parts.length > 1 ? parts[parts.length - 1][0] :
                '')).toUpperCase();

              let avatar = '';
              if (photo) {
                avatar = `<img src="/storage/${esc(photo)}" alt="${nama}"
                    class="rounded-circle" width="38" height="38"
                    style="object-fit:cover;">`;
              } else {
                const state = (jk === 'L' || jk === 'Laki-laki') ? 'primary' : 'danger';
                avatar = `<span class="avatar-initial rounded-circle bg-label-${state}"
                    style="width:38px;height:38px;font-size:13px;display:flex;
                           align-items:center;justify-content:center;">
                    ${esc(initials)}
                  </span>`;
              }

              const nisnNis = `${esc(full.nisn ?? '-')} - ${esc(full.nis ?? '-')}`;

              return `
                <div class="d-flex justify-content-start align-items-center gap-3 user-name">
                  <a href="${detailUrl}" class="text-decoration-none" title="Detail ${nama}">
                    <div class="avatar avatar-sm">${avatar}</div>
                  </a>
                  <div class="d-flex flex-column">
                    <a href="${detailUrl}" class="text-heading text-truncate fw-medium text-decoration-none">
                      ${nama}
                    </a>
                    <small class="text-muted">${nisnNis}</small>
                  </div>
                </div>`;
            }
          },
          // 2 – Tempat Lahir / Tanggal Lahir (atas-bawah)
          {
            data: 'tempat_lahir',
            render: function(data, type, full) {
              const tempat = esc(full.tempat_lahir ?? '-');
              const tanggal = full.tgl_lahir ? moment(full.tgl_lahir).format('DD-MM-YYYY') : '-';
              return `
                <div class="d-flex flex-column">
                  <span>${tempat}</span>
                  <small class="text-muted">${esc(tanggal)}</small>
                </div>`;
            }
          },
          // 3 – Usia (dihitung dari tgl_lahir, format "X th Y bln")
          {
            data: 'tgl_lahir',
            searchable: false,
            render: function(data, type, full) {
              if (!full.tgl_lahir) return '-';
              const lahir = moment(full.tgl_lahir);
              const now = moment();
              const tahun = now.diff(lahir, 'years');
              lahir.add(tahun, 'years');
              const bulan = now.diff(lahir, 'months');
              return `${tahun} th ${bulan} bln`;
            }
          },
          // 4 – L/P sebagai ikon
          {
            data: 'jenis_kelamin',
            className: 'text-center',
            render: function(data) {
              return (data === 'L' || data === 'Laki-laki') ?
                '<i class="ri ri-men-line text-primary icon-22px" title="Laki-laki"></i>' :
                '<i class="ri ri-women-line text-danger icon-22px" title="Perempuan"></i>';
            }
          },
          // 5 – Sekolah (admin only)
          {
            data: 'nama_sekolah',
            visible: window.hasRole('Admin'),
            render: function(data) {
              return data ?
                esc(data) :
                '<span class="badge bg-label-secondary">Detail Belum Terisi</span>';
            }
          },
          // 6 – Rombel
          {
            data: 'nama_rombel',
            render: function(data) {
              return data ?
                esc(data) :
                '<span class="badge bg-label-warning">Tanpa Rombel</span>';
            }
          },
          // 7 – Aksi
          {
            data: 'action',
            title: 'Aksi',
            searchable: false,
            orderable: false,
            render: function(data, type, full) {
              const status = esc(full.status ?? 'aktif');
              const tglKeluar = esc(full.tgl_keluar ?? '');
              return `
                  <div class="d-flex align-items-center gap-0">
                    <a href="/siswa/${full.id}/show"
                      class="btn btn-icon btn-text-secondary btn-sm rounded-pill"
                      title="Detail Siswa">
                      <i class="ri ri-eye-line icon-18px"></i>
                    </a>
                    <button class="btn btn-icon btn-text-secondary btn-sm rounded-pill edit-record sem-lock"
                      data-id="${full.id}"
                      data-bs-toggle="modal" data-bs-target="#modal-form-siswa"
                      title="Edit Siswa">
                      <i class="ri ri-edit-box-line icon-18px text-primary"></i>
                    </button>
                    <button class="btn btn-icon btn-text-secondary btn-sm rounded-pill update-status-btn sem-lock"
                      title="status"
                      data-id="${full.id}"
                      data-status="${status}"
                      data-tgl-keluar="${tglKeluar}"
                      data-bs-toggle="modal" data-bs-target="#modal-status-siswa">
                      <i class="ri ri-logout-circle-r-line icon-18px text-danger"></i>
                    </button>
                  </div>`;
            }
          }
        ],
        drawCallback: function() {
          if (!window.hasRole('Admin')) {
            const api = this.api();
            const start = api.page.info().start;
            api.rows({
              page: 'current'
            }).nodes().each(function(row, i) {
              row.cells[0].innerHTML = start + i + 1;
            });
          }
        },
        order: [
          [2, 'asc']
        ],
        // Definisikan buttons di sini (tanpa menaruhnya di layout)
        buttons: [{
            extend: 'excel',
            exportOptions: {
              columns: [1, 2, 3, 4, 5],
              format: {
                body: function(inner) {
                  if (!inner.length) return inner;
                  const doc = new DOMParser().parseFromString(inner, 'text/html');
                  const el = doc.querySelector('.fw-medium');
                  return (el ? el.textContent : doc.body.textContent).trim();
                }
              }
            }
          },
          {
            extend: 'pdf',
            title: 'Data Siswa',
            exportOptions: {
              columns: [1, 2, 3, 4, 5],
              format: {
                body: function(inner) {
                  if (!inner.length) return inner;
                  const doc = new DOMParser().parseFromString(inner, 'text/html');
                  const el = doc.querySelector('.fw-medium');
                  return (el ? el.textContent : doc.body.textContent).trim();
                }
              }
            }
          }
        ],
        layout: {
          topStart: {
            rowClass: 'd-flex flex-nowrap justify-content-between align-items-center mx-3 my-2 gap-2',
            features: [{
              pageLength: {
                menu: [7, 10, 20, 50, 70, 100],
                text: '_MENU_'
              }
            }]
          },
          topEnd: {
            features: [{
              search: {
                placeholder: 'Cari siswa...',
                text: '_INPUT_'
              }
            }, ]
          },
          bottomStart: {
            rowClass: 'row mx-3 justify-content-between',
            features: [{
              info: {
                text: '_START_–_END_ dari _TOTAL_'
              }
            }]
          },
          bottomEnd: 'paging'
        },
        displayLength: 20,
        language: {
          paginate: {
            next: '<i class="ri ri-arrow-right-s-line icon-22px"></i>',
            previous: '<i class="ri ri-arrow-left-s-line icon-22px"></i>',
            first: '<i class="ri ri-skip-back-mini-line icon-22px"></i>',
            last: '<i class="ri ri-skip-forward-mini-line icon-22px"></i>',
          },
          emptyTable: '<span class="text-muted"><i class="ri ri-inbox-line me-1"></i>Belum ada data siswa.</span>',
          zeroRecords: '<span class="text-muted"><i class="ri ri-search-line me-1"></i>Tidak ada siswa yang cocok.</span>',
        },
        initComplete: function() {
          document.querySelectorAll('.dt-buttons .btn').forEach(btn => btn.classList.remove(
            'btn-secondary'));
          // 1. Hapus class mt-5 (margin top) bawaan pada container start & end
          document.querySelectorAll('.dt-layout-start, .dt-layout-end').forEach(el => {
            el.classList.remove('mt-5', 'mt-md-0', 'me-auto', 'ms-auto');
            el.classList.add('mt-3', 'mb-3');
          });

          // 2. Hapus class mb-md-5 pada dt-length
          document.querySelectorAll('.dt-length, .dt-search').forEach(el => {
            el.classList.remove('mb-md-5');
            el.classList.add('mb-0', 'mt-0');
          });
        }
      });

      document.getElementById('btn-export-excel')?.addEventListener('click', function() {
        const params = new URLSearchParams({
          sekolah_id: $('#filter_sekolah').val() || '',
          rombel_id: $('#filter_rombel').val() || ''
        });
        window.location = `{{ route('siswa.export') }}?${params.toString()}`;
      });

      // ── Filter Rombel (client-side column search) ─────────────────────────
      $('#filter_rombel').on('change', function() {
        window.siswa.dtSiswa.draw();
      });

      // ── Filter Sekolah (server-side reload) ───────────────────────────────
      $('#filter_sekolah').on('change', function() {
        window.siswa.dtSiswa.ajax.reload();
      });

      // Update kartu info (total, laki-laki, perempuan) sesuai mode data:
      // - Admin: server-side → andalkan angka dari server (xhr), karena rows() lokal
      //   cuma berisi 1 halaman.
      // - Non-Admin: semua data termuat sekaligus → hitung langsung dari rows()
      //   supaya ikut ter-update saat filter Rombel (client-side) berubah.
      function updateInfoSiswa(total, laki, perempuan) {
        document.getElementById('info-total-siswa').textContent = total;
        document.getElementById('info-laki').textContent = laki;
        document.getElementById('info-perempuan').textContent = perempuan;
      }

      if (window.hasRole('Admin')) {
        window.siswa.dtSiswa.on('xhr', function(e, settings, json) {
          updateInfoSiswa(json.recordsFiltered ?? 0, json.jumlah_laki ?? 0, json.jumlah_perempuan ?? 0);
        });
      } else {
        window.siswa.dtSiswa.on('draw', function() {
          const rows = window.siswa.dtSiswa.rows({
            search: 'applied'
          }).data().toArray();
          const laki = rows.filter(r => r.jenis_kelamin === 'Laki-laki').length;
          const perempuan = rows.filter(r => r.jenis_kelamin === 'Perempuan').length;
          updateInfoSiswa(rows.length, laki, perempuan);
        });
      }

      // ── Layout class tweaks ───────────────────────────────────────────────
      setTimeout(() => {
        const tweaks = [{
            selector: '.dt-buttons .btn',
            classToRemove: 'btn-secondary'
          },
          {
            selector: '.dt-length .form-select',
            classToAdd: 'ms-0'
          },
          {
            selector: '.dt-length',
            classToAdd: 'mb-md-5 mb-0'
          },
          {
            selector: '.dt-layout-end',
            classToRemove: 'justify-content-between',
            classToAdd: 'd-flex gap-md-4 justify-content-md-between justify-content-center flex-wrap mt-0'
          },
          {
            selector: '.dt-layout-start',
            classToAdd: 'mt-md-0 mt-5'
          },
          {
            selector: '.dt-layout-table',
            classToRemove: 'row mt-2'
          },
          {
            selector: '.dt-layout-full',
            classToRemove: 'col-md col-12'
          },
        ];
        tweaks.forEach(({
          selector,
          classToRemove,
          classToAdd
        }) => {
          document.querySelectorAll(selector).forEach(el => {
            classToRemove?.split(' ').forEach(c => el.classList.remove(c));
            classToAdd?.split(' ').forEach(c => el.classList.add(c));
          });
        });
      }, 100);
    }

    // ── Event Delegation ────────────────────────────────────────────────────
    document.addEventListener('click', async function(e) {
      const formTambahSiswa = document.getElementById('form-tambah-siswa');
      const judulForm = document.getElementById('judul_form');

      // Tambah siswa
      if (e.target.closest('#tambah-siswa')) {
        window.siswa.saveMode = 'add';
        judulForm.innerHTML = 'Formulir Data Siswa';
        const urlStore = formTambahSiswa.getAttribute('data-siswa-store');
        formTambahSiswa.setAttribute('action', urlStore);
        formTambahSiswa.querySelector('input[name="_method"]').value = 'POST';
        document.getElementById('section-wali').classList.add('d-none');
        document.getElementById('preview-photo').src = '/assets/img/avatars/1.png';
      }

      // Edit siswa
      const editBtn = e.target.closest('.edit-record');
      if (editBtn) {
        window.siswa.saveMode = 'edit';
        const id = editBtn.getAttribute('data-id');
        const urlUpdate = formTambahSiswa.getAttribute('data-siswa-update').replace('ID', id);
        judulForm.innerHTML = 'Edit Data Siswa';

        try {
          const response = await fetch(`/siswa/${id}/edit`);
          const res = await response.json();

          if (res.status === 'success') {
            const data = res.data;
            const isLocked = res.is_locked;

            if (window.hasRole('Admin')) {
              formTambahSiswa.querySelector('.input-sekolah')?.classList.add('d-none');
            }

            formTambahSiswa.setAttribute('action', urlUpdate);
            formTambahSiswa.querySelector('input[name="_method"]').value = 'PUT';

            // Foto
            document.getElementById('preview-photo').src = data.photo ?
              `/storage/${data.photo}` :
              '/assets/img/avatars/1.png';

            // Step 1 – Identitas
            formTambahSiswa.querySelector('[name="nama_siswa"]').value = data.nama_siswa ?? '';
            formTambahSiswa.querySelector('[name="nik"]').value = data.nik ?? '';
            formTambahSiswa.querySelector('[name="jenis_kelamin"]').value = data.jenis_kelamin ?? '';
            formTambahSiswa.querySelector('[name="agama"]').value = data.agama ?? '';
            formTambahSiswa.querySelector('[name="tempat_lahir"]').value = data.tempat_lahir ?? '';
            formTambahSiswa.querySelector('[name="tgl_lahir"]').value = data.tgl_lahir ?? '';
            formTambahSiswa.querySelector('[name="alamat"]').value = data.alamat ?? '';
            formTambahSiswa.querySelector('[name="disabilitas"]').value = data.disabilitas ?? '';

            // Step 2 – Orang tua
            formTambahSiswa.querySelector('[name="nama_ayah"]').value = data.nama_ayah ?? '';
            formTambahSiswa.querySelector('[name="nik_ayah"]').value = data.nik_ayah ?? '';
            formTambahSiswa.querySelector('[name="tahun_ayah"]').value = data.tahun_ayah ?? '';
            formTambahSiswa.querySelector('[name="pekerjaan_ayah"]').value = data.pekerjaan_ayah ?? '';
            formTambahSiswa.querySelector('[name="no_hp"]').value = data.no_hp ?? '';
            formTambahSiswa.querySelector('[name="nama_ibu"]').value = data.nama_ibu ?? '';
            formTambahSiswa.querySelector('[name="nik_ibu"]').value = data.nik_ibu ?? '';
            formTambahSiswa.querySelector('[name="tahun_ibu"]').value = data.tahun_ibu ?? '';
            formTambahSiswa.querySelector('[name="pekerjaan_ibu"]').value = data.pekerjaan_ibu ?? '';

            // Wali
            if (data.nama_wali === data.nama_ayah) {
              formTambahSiswa.querySelector('#waliAyah').checked = true;
              document.getElementById('section-wali').classList.add('d-none');
            } else if (data.nama_wali === data.nama_ibu) {
              formTambahSiswa.querySelector('#waliIbu').checked = true;
              document.getElementById('section-wali').classList.add('d-none');
            } else {
              formTambahSiswa.querySelector('#waliLainnya').checked = true;
              document.getElementById('section-wali').classList.remove('d-none');
              formTambahSiswa.querySelector('[name="nama_wali"]').value = data.nama_wali ?? '';
              formTambahSiswa.querySelector('[name="nik_wali"]').value = data.nik_wali ?? '';
              formTambahSiswa.querySelector('[name="tahun_wali"]').value = data.tahun_wali ?? '';
              formTambahSiswa.querySelector('[name="pekerjaan_wali"]').value = data.pekerjaan_wali ?? '';
            }

            // Step 3 – Registrasi
            if (data.siswa_registrasi?.[0]) {
              const reg = data.siswa_registrasi[0];
              formTambahSiswa.querySelector('[name="nisn"]').value = data.nisn ?? '';
              formTambahSiswa.querySelector('[name="nis"]').value = reg.nis ?? '';
              formTambahSiswa.querySelector('[name="jenis_pendaftaran"]').value = reg.jenis_pendaftaran ??
                '';
              formTambahSiswa.querySelector('[name="tingkat"]').value = reg.tingkat ?? '';
              formTambahSiswa.querySelector('[name="tanggal_diterima"]').value = reg.tanggal_diterima ?? '';

              const inputTingkat = formTambahSiswa.querySelector('[name="tingkat"]');
              const tglDiterima = formTambahSiswa.querySelector('[name="tanggal_diterima"]');
              if (isLocked) {
                inputTingkat.readOnly = true;
                inputTingkat.classList.add('bg-light');
                inputTingkat.setAttribute('title', 'Tingkat dikunci karena sudah masuk rombel');
                tglDiterima.readOnly = true;
                tglDiterima.classList.add('bg-light');
              } else {
                inputTingkat.readOnly = false;
                inputTingkat.classList.remove('bg-light');
                tglDiterima.readOnly = false;
                tglDiterima.classList.remove('bg-light');
              }
            }
          }
        } catch (error) {
          console.error('Gagal mengambil data:', error);
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: 'Terjadi kesalahan saat mengambil data siswa.'
          });
        }
      }

      // Update status siswa
      const statusBtn = e.target.closest('.update-status-btn');
      if (statusBtn) {
        const {
          id,
          status,
          tglKeluar
        } = statusBtn.dataset;
        const tglInput = document.getElementById('tanggal_keluar_status');

        document.getElementById('siswa_id_status').value = id;
        document.getElementById('status_registrasi').value = status;
        document.getElementById('status_registrasi').dispatchEvent(new Event('change'));
        document.getElementById('alasan').value = '';

        const tgl = (tglKeluar && tglKeluar !== 'null') ? tglKeluar : null;
        if (tglInput._flatpickr) tglInput._flatpickr.setDate(tgl, false);
        else tglInput.value = tgl ?? '';
      }
    });

    // ── Status: show/hide tgl keluar & alasan ──────────────────────────────
    document.getElementById('status_registrasi').addEventListener('change', function() {
      const showExtra = ['mutasi', 'lulus', 'keluar', 'meninggal'].includes(this.value);
      document.getElementById('container-tgl-keluar').classList.toggle('d-none', !showExtra);
      document.getElementById('container-alasan').classList.toggle('d-none', !showExtra);
    });

    // ── Submit Form Update Status ───────────────────────────────────────────
    document.getElementById('form-update-status').addEventListener('submit', async function(e) {
      e.preventDefault();
      const id = document.getElementById('siswa_id_status').value;
      const formData = new FormData(this);
      formData.append('_method', 'PUT'); // route-nya PUT, form dikirim lewat POST + method spoofing
      const btn = this.querySelector('[type="submit"]');
      btn.disabled = true;

      try {
        const url = "{{ route('siswa.registrasi', ['id' => 'ID']) }}".replace('ID', id);
        const response = await fetch(url, {
          method: 'POST',
          body: formData,
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json'
          }
        });
        const res = await response.json();

        if (response.ok && res.status === 'success') {
          bootstrap.Modal.getInstance(document.getElementById('modal-status-siswa'))?.hide();
          Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: res.message,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
          window.siswa.dtSiswa?.ajax.reload(null, false);
        } else {
          // Tampilkan detail error validasi Laravel (422) kalau ada
          const detail = res.errors ? Object.values(res.errors).flat().join('\n') : null;
          throw new Error(detail || res.message || 'Gagal mengupdate status');
        }
      } catch (error) {
        Swal.fire({
          icon: 'error',
          title: 'Gagal',
          text: error.message,
          customClass: {
            confirmButton: 'btn btn-primary'
          }
        });
      } finally {
        btn.disabled = false;
      }
    });

  }); // end DOMContentLoaded (main)

  // ── Import Siswa ──────────────────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', function() {
    const btnProsesImport = document.getElementById('btn-proses-import');
    const formImport = document.getElementById('form-import-siswa');
    const fileInput = document.getElementById('file-excel-input');
    const importProgress = document.getElementById('import-progress');
    const errorReport = document.getElementById('import-error-report');
    const errorReportBody = document.getElementById('error-report-body');
    const errorCountBadge = document.getElementById('error-count-badge');
    const successReport = document.getElementById('import-success-report');
    const successMessage = document.getElementById('import-success-message');
    const modalImport = document.getElementById('modal-import-siswa');

    if (!btnProsesImport) return;

    modalImport?.addEventListener('show.bs.modal', resetImportState);

    btnProsesImport.addEventListener('click', async function() {
      if (!fileInput?.files?.length) {
        return Swal.fire({
          icon: 'warning',
          title: 'File Belum Dipilih',
          text: 'Silakan pilih file Excel terlebih dahulu.',
          customClass: {
            confirmButton: 'btn btn-primary'
          }
        });
      }
      const sekolahSelect = document.getElementById('import-sekolah-id');
      if (sekolahSelect && !sekolahSelect.value) {
        return Swal.fire({
          icon: 'warning',
          title: 'Sekolah Belum Dipilih',
          text: 'Silakan pilih sekolah terlebih dahulu.',
          customClass: {
            confirmButton: 'btn btn-primary'
          }
        });
      }

      const confirm = await Swal.fire({
        icon: 'question',
        title: 'Proses Import?',
        text: 'Pastikan data di file Excel sudah benar sebelum diproses.',
        showCancelButton: true,
        confirmButtonText: 'Ya, Proses!',
        cancelButtonText: 'Batal',
        customClass: {
          confirmButton: 'btn btn-primary me-3',
          cancelButton: 'btn btn-outline-secondary'
        },
        buttonsStyling: false
      });
      if (!confirm.isConfirmed) return;

      resetImportState();
      importProgress?.classList.remove('d-none');
      btnProsesImport.disabled = true;
      btnProsesImport.innerHTML =
        '<span class="spinner-border spinner-border-sm me-1"></span> Memproses...';

      try {
        const response = await fetch('/siswa/import', {
          method: 'POST',
          body: new FormData(formImport),
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            Accept: 'application/json'
          }
        });
        const res = await response.json();
        importProgress?.classList.add('d-none');

        if (response.ok && res.status === 'success') {
          successMessage.innerHTML =
            `<strong>Import Berhasil!</strong> ${esc(res.berhasil)} data siswa berhasil ditambahkan.`;
          successReport?.classList.remove('d-none');
          window.siswa.dtSiswa?.ajax.reload(null, false);
          formImport?.reset();
        } else if (response.status === 422 && res.errors) {
          tampilkanErrorReport(res.errors, res.total_error);
        } else {
          throw new Error(res.message || 'Terjadi kesalahan sistem.');
        }
      } catch (error) {
        importProgress?.classList.add('d-none');
        Swal.fire({
          icon: 'error',
          title: 'Gagal',
          text: error.message,
          customClass: {
            confirmButton: 'btn btn-primary'
          }
        });
      } finally {
        btnProsesImport.disabled = false;
        btnProsesImport.innerHTML = '<i class="ri ri-upload-line me-1"></i> Proses Import';
      }
    });

    function tampilkanErrorReport(errors, totalError) {
      errorCountBadge.textContent = totalError + ' error';
      errorReportBody.innerHTML = errors.map(err => `
          <tr>
            <td class="text-center fw-semibold text-danger">${esc(err.baris)}</td>
            <td>${esc(err.kolom)}</td>
            <td class="text-truncate" style="max-width:150px;" title="${esc(err.nilai)}">${esc(err.nilai)}</td>
            <td>${esc(err.pesan)}</td>
          </tr>`).join('');
      errorReport?.classList.remove('d-none');
      errorReport?.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }

    function resetImportState() {
      importProgress?.classList.add('d-none');
      errorReport?.classList.add('d-none');
      successReport?.classList.add('d-none');
      if (errorReportBody) errorReportBody.innerHTML = '';
    }
  });

  // ── Wizard Form Tambah / Edit Siswa ───────────────────────────────────────
  document.addEventListener('DOMContentLoaded', function() {
    const wizardValidation = document.querySelector('#wizard-validation');
    if (!wizardValidation) return;

    const formTambahSiswa = wizardValidation.querySelector('#form-tambah-siswa');
    const modalElement = document.getElementById('modal-form-siswa');
    const step1 = formTambahSiswa.querySelector('#data-siswa');
    const step2 = formTambahSiswa.querySelector('#data-orang-tua');
    const step3 = formTambahSiswa.querySelector('#registrasi-siswa');

    const validationStepper = new Stepper(wizardValidation, {
      linear: true
    });

    // Flatpickr
    formTambahSiswa.querySelectorAll('.flatpickr-date').forEach(el => {
      flatpickr(el, {
        dateFormat: 'Y-m-d',
        static: true
      });
    });

    // Select2
    const $select2 = $('.select2');
    if ($select2.length) {
      $select2.each(function() {
        $(this).select2({
          placeholder: 'Pilih opsi',
          dropdownParent: $(this).parent()
        });
        $(this).on('change', function() {
          const name = $(this).attr('name');
          if (step1.contains(this)) fv1.revalidateField(name);
          if (step2.contains(this)) fv2.revalidateField(name);
          if (step3.contains(this)) fv3.revalidateField(name);
        });
      });
    }

    // ── FormValidation Step 1 ───────────────────────────────────────────────
    const fv1 = FormValidation.formValidation(step1, {
      fields: {
        nama_siswa: {
          validators: {
            notEmpty: {
              message: 'Nama siswa wajib diisi'
            }
          }
        },
        nik: {
          validators: {
            notEmpty: {
              message: 'NIK wajib diisi'
            },
            stringLength: {
              min: 16,
              max: 16,
              message: 'NIK harus 16 digit'
            },
            digits: {
              message: 'NIK harus angka'
            }
          }
        },
        jenis_kelamin: {
          validators: {
            notEmpty: {
              message: 'Pilih jenis kelamin'
            }
          }
        },
        tempat_lahir: {
          validators: {
            notEmpty: {
              message: 'Tempat lahir wajib diisi'
            }
          }
        },
        tgl_lahir: {
          validators: {
            notEmpty: {
              message: 'Tanggal lahir wajib diisi'
            }
          }
        },
        agama: {
          validators: {
            notEmpty: {
              message: 'Pilih agama'
            }
          }
        },
        alamat: {
          validators: {
            notEmpty: {
              message: 'Alamat wajib diisi'
            }
          }
        },
      },
      plugins: {
        trigger: new FormValidation.plugins.Trigger(),
        bootstrap5: new FormValidation.plugins.Bootstrap5({
          eleValidClass: '',
          rowSelector: '.form-control-validation'
        }),
        autoFocus: new FormValidation.plugins.AutoFocus(),
        submitButton: new FormValidation.plugins.SubmitButton(),
      }
    }).on('core.form.valid', () => validationStepper.next());

    // ── FormValidation Step 2 ───────────────────────────────────────────────
    const fv2 = FormValidation.formValidation(step2, {
      fields: {
        nama_ayah: {
          validators: {
            notEmpty: {
              message: 'Nama Ayah wajib diisi'
            }
          }
        },
        nama_ibu: {
          validators: {
            notEmpty: {
              message: 'Nama Ibu wajib diisi'
            }
          }
        },
        nik_ayah: {
          validators: {
            digits: {
              message: 'Hanya angka'
            },
            stringLength: {
              min: 16,
              max: 16,
              message: 'Harus 16 digit'
            }
          }
        },
        nik_ibu: {
          validators: {
            digits: {
              message: 'Hanya angka'
            },
            stringLength: {
              min: 16,
              max: 16,
              message: 'Harus 16 digit'
            }
          }
        },
        nik_wali: {
          validators: {
            digits: {
              message: 'Hanya angka'
            },
            stringLength: {
              min: 16,
              max: 16,
              message: 'Harus 16 digit'
            }
          }
        },
        tahun_ayah: {
          validators: {
            digits: {
              message: 'Hanya angka'
            },
            stringLength: {
              min: 4,
              max: 4,
              message: 'Harus 4 digit'
            }
          }
        },
        tahun_ibu: {
          validators: {
            digits: {
              message: 'Hanya angka'
            },
            stringLength: {
              min: 4,
              max: 4,
              message: 'Harus 4 digit'
            }
          }
        },
        tahun_wali: {
          validators: {
            digits: {
              message: 'Hanya angka'
            },
            stringLength: {
              min: 4,
              max: 4,
              message: 'Harus 4 digit'
            }
          }
        },
      },
      plugins: {
        trigger: new FormValidation.plugins.Trigger(),
        bootstrap5: new FormValidation.plugins.Bootstrap5({
          eleValidClass: '',
          rowSelector: '.form-control-validation'
        }),
        autoFocus: new FormValidation.plugins.AutoFocus(),
        submitButton: new FormValidation.plugins.SubmitButton(),
      }
    }).on('core.form.valid', () => validationStepper.next());

    // ── FormValidation Step 3 ───────────────────────────────────────────────
    const fv3 = FormValidation.formValidation(step3, {
      fields: {
        nisn: {
          validators: {
            notEmpty: {
              message: 'NISN wajib diisi'
            },
            digits: {
              message: 'NISN harus angka'
            },
            stringLength: {
              min: 10,
              max: 10,
              message: 'NISN harus 10 digit'
            }
          }
        },
        nis: {
          validators: {
            notEmpty: {
              message: 'NIS wajib diisi'
            }
          }
        },
        jenis_pendaftaran: {
          validators: {
            notEmpty: {
              message: 'Pilih jenis pendaftaran'
            }
          }
        },
        tingkat: {
          validators: {
            notEmpty: {
              message: 'Tingkat wajib diisi'
            }
          }
        },
        tanggal_diterima: {
          validators: {
            notEmpty: {
              message: 'Tanggal diterima wajib diisi'
            }
          }
        },
      },
      plugins: {
        trigger: new FormValidation.plugins.Trigger(),
        bootstrap5: new FormValidation.plugins.Bootstrap5({
          eleValidClass: '',
          rowSelector: '.form-control-validation'
        }),
        autoFocus: new FormValidation.plugins.AutoFocus(),
        submitButton: new FormValidation.plugins.SubmitButton(),
      }
    });

    const fv = [fv1, fv2, fv3];

    // ── Submit Step 3 ───────────────────────────────────────────────────────
    fv3.on('core.form.valid', async function() {
      const formData = new FormData(formTambahSiswa);
      const submitBtn = formTambahSiswa.querySelector('button[type="submit"]');
      const origText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

      try {
        const response = await fetch(formTambahSiswa.action, {
          method: 'POST',
          body: formData,
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
            'X-CSRF-TOKEN': formData.get('_token')
          }
        });
        const res = await response.json();

        if (response.ok) {
          Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: res.message,
            customClass: {
              confirmButton: 'btn btn-success'
            }
          });
          $(modalElement).modal('hide');
          window.siswa.dtSiswa?.ajax.reload(null, false);
        } else if (response.status === 422) {
          const errors = res.errors;
          const errorList = Object.values(errors).map(err => `<li>${err[0]}</li>`).join('');

          Object.keys(errors).forEach(key => {
            fv.forEach((v, index) => {
              if (v.fields[key]) {
                const firstValidator = Object.keys(v.fields[key].validators)[0];
                v.updateFieldStatus(key, 'Invalid', firstValidator);
                validationStepper.to(index + 1);
              }
            });
          });

          Swal.fire({
            icon: 'error',
            title: 'Gagal Validasi',
            html: `<ul style="text-align:left">${errorList}</ul>`,
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
        submitBtn.innerHTML = origText;
        submitBtn.disabled = false;
      }
    });

    // ── Wali logic ──────────────────────────────────────────────────────────
    const sectionWali = document.querySelector('#section-wali');
    formTambahSiswa.querySelectorAll('input[name="wali_status"]').forEach(radio => {
      radio.addEventListener('change', function() {
        if (this.value === 'lainnya') {
          sectionWali.classList.remove('d-none');
          fv2.addField('nama_wali', {
            validators: {
              notEmpty: {
                message: 'Nama wali wajib diisi'
              }
            }
          });
        } else {
          sectionWali.classList.add('d-none');
          fv2.removeField('nama_wali');
        }
      });
    });

    // ── Reset modal saat ditutup ────────────────────────────────────────────
    modalElement?.addEventListener('hide.bs.modal', function() {
      formTambahSiswa.reset();
      fv.forEach(instance => instance?.resetForm(true));
      if ($.fn.select2) $('.select2').val(null).trigger('change');
      validationStepper.to(1);
      document.getElementById('section-wali').classList.add('d-none');
      document.getElementById('preview-photo').src = '/assets/img/avatars/1.png';
    });

    // ── Next / Prev buttons ─────────────────────────────────────────────────
    formTambahSiswa.querySelectorAll('.btn-next').forEach(btn => {
      btn.addEventListener('click', () => {
        switch (validationStepper._currentIndex) {
          case 0:
            fv1.validate();
            break;
          case 1:
            fv2.validate();
            break;
          case 2:
            fv3.validate();
            break;
        }
      });
    });

    formTambahSiswa.querySelectorAll('.btn-prev').forEach(btn => {
      btn.addEventListener('click', () => validationStepper.previous());
    });

    const btnSimpan = step3.querySelector('button[type="submit"]');
    btnSimpan?.addEventListener('click', function(e) {
      e.preventDefault();
      fv3.validate();
    });

  });
</script>
@endsection