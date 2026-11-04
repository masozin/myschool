@extends('layouts/layoutMaster')

@section('title', 'Buat Modul Ajar')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('content')

  <div class="mb-4">
    <a href="{{ route('learning-modules.index') }}" class="btn btn-outline-secondary btn-sm">
      <i class="ri ri-arrow-left-line me-1"></i> Kembali ke Daftar Modul
    </a>
  </div>

  <form id="form_modul">
    <div class="row">
      <div class="col-12 col-lg-8">

        {{-- =================================================== --}}
        {{-- A. IDENTITAS MODUL                                    --}}
        {{-- =================================================== --}}
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="ri ri-information-line me-2"></i>A. Identitas Modul</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                  <select id="mapel_master_id" name="mapel_master_id" class="form-select" required>
                    <option value="">Pilih Mata Pelajaran</option>
                    @foreach ($mapelList as $mapel)
                      <option value="{{ $mapel->id }}">{{ $mapel->nama }}</option>
                    @endforeach
                  </select>
                  <label for="mapel_master_id">Mata Pelajaran</label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-floating form-floating-outline">
                  <select id="fase" name="fase" class="form-select">
                    <option value="">-</option>
                    @foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $f)
                      <option value="{{ $f }}">Fase {{ $f }}</option>
                    @endforeach
                  </select>
                  <label for="fase">Fase</label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-floating form-floating-outline">
                  <input type="text" class="form-control" id="kelas" name="kelas" placeholder="Kelas">
                  <label for="kelas">Kelas</label>
                </div>
              </div>

              <div class="col-md-4">
                <div class="form-floating form-floating-outline">
                  <input type="text" class="form-control" id="bab" name="bab" placeholder="Bab">
                  <label for="bab">Bab (mis. Bab 6)</label>
                </div>
              </div>
              <div class="col-md-8">
                <div class="form-floating form-floating-outline">
                  <input type="text" class="form-control" id="materi_pokok" name="materi_pokok"
                    placeholder="Topik / Materi Pokok" required>
                  <label for="materi_pokok">Topik / Materi Pokok</label>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                  <input type="number" class="form-control" id="jumlah_jp" name="jumlah_jp" min="1"
                    placeholder="Jumlah JP" required>
                  <label for="jumlah_jp">Jumlah JP</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                  <input type="number" class="form-control" id="jumlah_pertemuan" name="jumlah_pertemuan" min="1"
                    placeholder="Jumlah Pertemuan" required>
                  <label for="jumlah_pertemuan">Jumlah Pertemuan</label>
                </div>
              </div>
            </div>
          </div>
        </div>

        {{-- =================================================== --}}
        {{-- E. DESAIN PEMBELAJARAN                                 --}}
        {{-- =================================================== --}}
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="ri ri-flag-line me-2"></i>Desain Pembelajaran</h5>
          </div>
          <div class="card-body">
            <div class="form-floating form-floating-outline mb-4">
              <textarea class="form-control" id="capaian_pembelajaran" name="capaian_pembelajaran" style="height: 110px"
                placeholder="Capaian Pembelajaran" required></textarea>
              <label for="capaian_pembelajaran">Capaian Pembelajaran (CP)</label>
            </div>

            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                  <select id="model_pembelajaran" name="model_pembelajaran" class="form-select">
                    <option value="">Pilih Model Pembelajaran</option>
                    <option value="Problem Based Learning">Problem Based Learning (PBL)</option>
                    <option value="Project Based Learning">Project Based Learning (PjBL)</option>
                    <option value="Discovery Learning">Discovery Learning</option>
                    <option value="Inquiry Learning">Inquiry Learning</option>
                    <option value="Cooperative Learning">Cooperative Learning</option>
                  </select>
                  <label for="model_pembelajaran">Model Pembelajaran</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-floating form-floating-outline">
                  <select id="target_peserta_didik" name="target_peserta_didik" class="form-select">
                    <option value="Reguler">Reguler</option>
                    <option value="Cerdas Istimewa">Cerdas Istimewa</option>
                    <option value="Kesulitan Belajar">Kesulitan Belajar</option>
                  </select>
                  <label for="target_peserta_didik">Target Peserta Didik</label>
                </div>
              </div>
            </div>

            <div class="mt-4">
              <label class="form-label">Metode Pembelajaran</label>
              <select id="metode_pembelajaran" name="metode_pembelajaran[]" class="form-select" multiple>
                <option value="Diskusi">Diskusi</option>
                <option value="Ceramah">Ceramah</option>
                <option value="Tanya Jawab">Tanya Jawab</option>
                <option value="Penugasan">Penugasan</option>
                <option value="Eksperimen">Eksperimen</option>
                <option value="Demonstrasi">Demonstrasi</option>
                <option value="Presentasi">Presentasi</option>
              </select>
              <small class="text-muted">AI akan mengembangkan pendekatan Meaningful, Joyful, dan Mindful Learning
                berdasarkan pilihan ini.</small>
            </div>
          </div>
        </div>

        <div class="card mb-4">
          <div class="card-header">
            <h5 class="mb-0"><i class="ri ri-list-settings-line me-2"></i>Detail Tambahan</h5>
            <small class="text-muted">Pilih Auto AI untuk digenerate otomatis, atau Manual untuk menulis draft sendiri
              (AI akan merapikan).</small>
          </div>
          <div class="card-body">

            @include('learning.modules._field-toggle', [
                'name' => 'tujuan_pembelajaran',
                'label' => 'Tujuan Pembelajaran',
                'placeholder' => "Contoh:\n- Siswa mampu menjelaskan X\n- Siswa mampu menganalisis Y",
            ])

            @include('learning.modules._field-toggle', [
                'name' => 'dimensi_profil_lulusan',
                'label' => 'Dimensi Profil Lulusan',
                'placeholder' =>
                    "Contoh:\n- Bernalar Kritis: siswa menganalisis...\n- Gotong Royong: siswa berkolaborasi...",
            ])

            @include('learning.modules._field-toggle', [
                'name' => 'lintas_disiplin_ilmu',
                'label' => 'Lintas Disiplin Ilmu',
            ])

            @include('learning.modules._field-toggle', [
                'name' => 'praktik_pedagogis',
                'label' => 'Praktik Pedagogis (Pendekatan Deep Learning)',
            ])

            @include('learning.modules._field-toggle', [
                'name' => 'pemanfaatan_teknologi',
                'label' => 'Pemanfaatan Teknologi Digital',
            ])

          </div>
        </div>

      </div>

      {{-- =================================================== --}}
      {{-- SIDEBAR: INFO PENYUSUN & AKSI                          --}}
      {{-- =================================================== --}}
      <div class="col-12 col-lg-4">
        <div class="card mb-4">
          <div class="card-header">
            <h6 class="mb-0"><i class="ri ri-user-line me-2"></i>Penyusun</h6>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <small class="text-muted d-block">Nama Penyusun</small>
              <strong>{{ $ptk->nama_ptk }}</strong>
            </div>
            <div class="mb-0">
              <small class="text-muted d-block">Satuan Pendidikan</small>
              <strong>{{ $sekolah->nama_sekolah ?? ($sekolah->nama ?? '-') }}</strong>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <p class="text-muted small mb-3">
              <i class="ri ri-information-line me-1"></i>
              AI akan menyusun Identifikasi Murid, Dimensi Profil Lulusan, Blok Pembelajaran per pertemuan, Asesmen,
              Pengayaan/Remedial, dan Refleksi secara otomatis berdasarkan data di atas.
            </p>
            <button type="submit" id="submit_modul" class="btn btn-primary w-100 mb-2">
              <i class="ri ri-magic-line me-1"></i> Buat dengan AI
            </button>
            <a href="{{ route('learning-modules.index') }}" class="btn btn-outline-secondary w-100">Batal</a>
          </div>
        </div>
      </div>
    </div>
  </form>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      if (window.jQuery) {
        $('#metode_pembelajaran').select2({
          placeholder: 'Pilih metode pembelajaran',
          width: '100%'
        });
      }

      const $form = document.getElementById('form_modul');
      const $submitBtn = document.getElementById('submit_modul');

      $form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData($form);

        $submitBtn.disabled = true;
        $submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengirim...';

        fetch('{{ route('learning-modules.store') }}', {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
          })
          .then(async res => {
            const data = await res.json();
            if (!res.ok) throw data;
            return data;
          })
          .then(data => {
            window.location.href = data.redirect;
          })
          .catch(err => {
            let message = err.message || 'Terjadi kesalahan saat membuat modul ajar.';
            if (err.errors) {
              message = Object.values(err.errors).flat().join('<br>');
            }
            Swal.fire({
              icon: 'error',
              title: 'Gagal',
              html: message
            });
            $submitBtn.disabled = false;
            $submitBtn.innerHTML = '<i class="ri ri-magic-line me-1"></i> Buat dengan AI';
          });
      });

      document.querySelectorAll('.mode-toggle').forEach(function(radio) {
        radio.addEventListener('change', function() {
          const target = this.dataset.target;
          const inputBox = document.querySelector(`.manual-input[data-target-input="${target}"]`);
          const caption = document.querySelector(`.auto-caption[data-target-caption="${target}"]`);

          if (this.value === 'manual') {
            inputBox.classList.remove('d-none');
            caption.classList.add('d-none');
          } else {
            inputBox.classList.add('d-none');
            caption.classList.remove('d-none');
          }
        });
      });
    });
  </script>
@endsection
