@extends('layouts/layoutMaster')

@section('title', 'Profil Pengguna')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')
  <div class="row">

    <!-- Tampilkan Pesan Sukses -->
    @if (session('success'))
      <div class="col-12 mb-4">
        <div class="alert alert-success alert-dismissible" role="alert">
          {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      </div>
    @endif

    <!-- Tampilkan Pesan Error -->
    @if ($errors->any())
      <div class="col-12 mb-4">
        <div class="alert alert-danger alert-dismissible" role="alert">
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      </div>
    @endif

    <!-- User Sidebar -->
    <div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0">
      <!-- User Card -->
      <div class="card mb-6">
        <div class="card-body pt-12">
          <div class="user-avatar-section">
            <div class=" d-flex align-items-center flex-column">
              <!-- Foto Profil dinamis -->
              @php
                $avatarPath =
                    $user->Ptk && $user->Ptk->photo
                        ? asset('storage/' . $user->Ptk->photo)
                        : asset('assets/img/avatars/1.png');
              @endphp
              <img class="img-fluid rounded mb-4 object-fit-cover" src="{{ $avatarPath }}" height="120" width="120"
                alt="User avatar" style="height: 120px; width: 120px;" />
              <div class="user-info text-center">
                <h5>{{ $user->Ptk->nama_ptk ?? $user->name }}</h5>
                <span class="badge bg-label-primary rounded-pill">
                  {{ $user->roles->pluck('name')->first() ?? 'User' }}
                </span>
              </div>
            </div>
          </div>

          <h5 class="pb-4 border-bottom mb-4 mt-6">Detail Profil</h5>
          <div class="info-container">
            <ul class="list-unstyled mb-6">
              <li class="mb-2">
                <span class="fw-medium text-heading me-2">Nama Akun:</span>
                <span>{{ $user->name }}</span>
              </li>
              <li class="mb-2">
                <span class="fw-medium text-heading me-2">Username:</span>
                <span>{{ $user->user_name }}</span>
              </li>
              <li class="mb-2">
                <span class="fw-medium text-heading me-2">Email:</span>
                <span>{{ $user->email }}</span>
              </li>
              <li class="mb-2">
                <span class="fw-medium text-heading me-2">Status:</span>
                <span class="badge bg-label-success rounded-pill">Aktif</span>
              </li>

              @if ($user->Ptk)
                <hr>
                <li class="mb-2">
                  <span class="fw-medium text-heading me-2">NIP/NUPTK:</span>
                  <span>{{ $user->Ptk->nip ?? '-' }}</span>
                </li>
                <li class="mb-2">
                  <span class="fw-medium text-heading me-2">Gender:</span>
                  <span>{{ $user->Ptk->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' }}</span>
                </li>
                <li class="mb-2">
                  <span class="fw-medium text-heading me-2">Jabatan:</span>
                  <span>{{ $user->Ptk->jabatan ?? '-' }}</span>
                </li>
                <li class="mb-2">
                  <span class="fw-medium text-heading me-2">Kepegawaian:</span>
                  <span>{{ $user->Ptk->jenis_kepegawaian ?? '-' }}</span>
                </li>
              @endif
            </ul>
          </div>
        </div>
      </div>
      <!-- /User Card -->
    </div>
    <!--/ User Sidebar -->

    <!-- User Content -->
    <div class="col-xl-8 col-lg-7 col-md-7 order-0 order-md-1">

      <!-- Edit Profil Card -->
      <div class="card mb-6">
        <h5 class="card-header">Edit Informasi Profil</h5>
        <div class="card-body">
          <form method="POST" action="{{ route('user.profil.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row">
              <!-- Data Akun User -->
              <div class="col-md-12 mb-4">
                <h6 class="fw-medium">Data Akun Aplikasi</h6>
              </div>

              <div class="col-md-6 mb-4">
                <div class="form-floating form-floating-outline">
                  <input class="form-control" type="text" id="name" name="name"
                    value="{{ old('name', $user->name) }}" required />
                  <label for="name">Nama Lengkap (Akun)</label>
                </div>
              </div>
              <div class="col-md-6 mb-4">
                <div class="form-floating form-floating-outline">
                  <input class="form-control" type="text" id="user_name" name="user_name"
                    value="{{ old('user_name', $user->user_name) }}" required />
                  <label for="user_name">Username</label>
                </div>
              </div>
              <div class="col-md-6 mb-4">
                <div class="form-floating form-floating-outline">
                  <input class="form-control" type="email" id="email" name="email"
                    value="{{ old('email', $user->email) }}" required />
                  <label for="email">Email</label>
                </div>
              </div>

              @if ($user->Ptk)
                <!-- Data PTK -->
                <div class="col-md-12 mt-3 mb-4">
                  <hr>
                  <h6 class="fw-medium">Data Kepegawaian (PTK)</h6>
                </div>

                <div class="col-md-6 mb-4">
                  <div class="form-floating form-floating-outline">
                    <input class="form-control" type="text" id="nama_ptk" name="nama_ptk"
                      value="{{ old('nama_ptk', $user->Ptk->nama_ptk) }}" required />
                    <label for="nama_ptk">Nama PTK (Beserta Gelar)</label>
                  </div>
                </div>

                <div class="col-md-6 mb-4">
                  <div class="form-floating form-floating-outline">
                    <input class="form-control" type="text" id="nip" name="nip"
                      value="{{ old('nip', $user->Ptk->nip) }}" />
                    <label for="nip">NIP / NUPTK</label>
                  </div>
                </div>

                <div class="col-md-6 mb-4">
                  <div class="form-floating form-floating-outline">
                    <select id="jenis_kelamin" name="jenis_kelamin" class="form-select">
                      <option value="L"
                        {{ old('jenis_kelamin', $user->Ptk->jenis_kelamin) == 'L' ? 'selected' : '' }}>Laki-laki</option>
                      <option value="P"
                        {{ old('jenis_kelamin', $user->Ptk->jenis_kelamin) == 'P' ? 'selected' : '' }}>Perempuan</option>
                    </select>
                    <label for="jenis_kelamin">Jenis Kelamin</label>
                  </div>
                </div>

                <div class="col-md-6 mb-4">
                  <div class="form-floating form-floating-outline">
                    <input class="form-control" type="text" id="jabatan" name="jabatan"
                      value="{{ old('jabatan', $user->Ptk->jabatan) }}" />
                    <label for="jabatan">Jabatan</label>
                  </div>
                </div>

                <div class="col-md-6 mb-4">
                  <div class="form-floating form-floating-outline">
                    <input class="form-control" type="text" id="jenis_kepegawaian" name="jenis_kepegawaian"
                      value="{{ old('jenis_kepegawaian', $user->Ptk->jenis_kepegawaian) }}" />
                    <label for="jenis_kepegawaian">Jenis Kepegawaian</label>
                  </div>
                </div>

                <div class="col-md-6 mb-4">
                  <label for="photo" class="form-label">Upload Foto Baru (Opsional)</label>
                  <input class="form-control" type="file" id="photo" name="photo"
                    accept="image/png, image/jpeg, image/jpg" />
                  <small class="text-muted">Maksimal 2MB. Format: JPG, PNG.</small>
                </div>
              @else
                <div class="col-md-12 mb-4">
                  <div class="alert alert-warning" role="alert">
                    Akun ini belum ditautkan dengan data PTK.
                  </div>
                </div>
              @endif

              <div class="col-12 mt-2">
                <button type="submit" class="btn btn-primary me-2">Simpan Perubahan</button>
                <button type="reset" class="btn btn-outline-secondary">Reset</button>
              </div>
            </div>
          </form>
        </div>
      </div>
      <!--/ Edit Profil Card -->

      <!-- Change Password -->
      <div class="card mb-6">
        <h5 class="card-header">Ganti Password</h5>
        <div class="card-body">
          <form method="POST" action="{{ route('user.profil.password') }}">
            @csrf
            @method('PUT')
            <div class="alert alert-warning alert-dismissible" role="alert">
              <h5 class="alert-heading mb-1">Penting!</h5>
              <span>Pastikan password Anda panjang, acak, dan unik untuk menjaga keamanan akun.</span>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            <div class="row gx-5">
              <div class="mb-4 col-12 col-sm-12 form-password-toggle">
                <div class="input-group input-group-merge">
                  <div class="form-floating form-floating-outline">
                    <input class="form-control" type="password" id="current_password" name="current_password"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required />
                    <label for="current_password">Password Saat Ini</label>
                  </div>
                  <span class="input-group-text cursor-pointer"><i
                      class="icon-base ri ri-eye-off-line icon-20px"></i></span>
                </div>
              </div>

              <div class="mb-4 col-12 col-sm-6 form-password-toggle">
                <div class="input-group input-group-merge">
                  <div class="form-floating form-floating-outline">
                    <input class="form-control" type="password" id="password" name="password"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required />
                    <label for="password">Password Baru</label>
                  </div>
                  <span class="input-group-text cursor-pointer"><i
                      class="icon-base ri ri-eye-off-line icon-20px"></i></span>
                </div>
              </div>

              <div class="mb-4 col-12 col-sm-6 form-password-toggle">
                <div class="input-group input-group-merge">
                  <div class="form-floating form-floating-outline">
                    <input class="form-control" type="password" name="password_confirmation"
                      id="password_confirmation"
                      placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" required />
                    <label for="password_confirmation">Konfirmasi Password Baru</label>
                  </div>
                  <span class="input-group-text cursor-pointer"><i
                      class="icon-base ri ri-eye-off-line icon-20px"></i></span>
                </div>
              </div>

              <div>
                <button type="submit" class="btn btn-primary me-2">Update Password</button>
              </div>
            </div>
          </form>
        </div>
      </div>
      <!--/ Change Password -->

    </div>
    <!--/ User Content -->
  </div>
@endsection
