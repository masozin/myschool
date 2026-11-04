@extends('layouts/layoutMaster')

@section('title', 'Aset Sekolah')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
  <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
@endsection

@section('content')

  {{-- Header info sekolah --}}
  <div class="card mb-4">
    <div class="card-body d-flex align-items-center gap-4 py-4">
      <div class="avatar avatar-xl">
        <span class="avatar-initial rounded-circle bg-label-primary">
          <i class="ri ri-image-2-line ri-28px"></i>
        </span>
      </div>
      <div>
        <h4 class="mb-1">Aset Dokumen Sekolah</h4>
        <p class="text-muted mb-0">{{ $sekolah->detailTerbaru->nama_sekolah ?? '-' }}</p>
        <small class="text-muted">
          Aset ini digunakan untuk dokumen resmi seperti SKHU, SKL, dan dokumen lainnya.
        </small>
      </div>
    </div>
  </div>

  {{-- Grid Aset --}}
  <div class="row g-4">
    @foreach ($asets as $tipe => $info)
      <div class="col-12 col-md-6">
        <div class="card h-100">
          <div class="card-header d-flex align-items-center gap-3">
            <div class="avatar avatar-sm">
              <span class="avatar-initial rounded-circle bg-label-info">
                <i class="{{ $info['icon'] }}"></i>
              </span>
            </div>
            <div>
              <h6 class="mb-0">{{ $info['label'] }}</h6>
              <small class="text-muted">{{ $info['keterangan'] }}</small>
            </div>
          </div>

          <div class="card-body">
            {{-- Preview aset --}}
            <div class="preview-area mb-4 d-flex align-items-center justify-content-center"
              style="min-height: 140px; background: #f8f8f8; border: 2px dashed #ddd; border-radius: 8px;">
              @if ($info['url'])
                <img src="{{ $info['url'] }}" id="preview-{{ $tipe }}"
                  style="max-height: 120px; max-width: 100%; object-fit: contain;" alt="{{ $info['label'] }}">
              @else
                <div id="preview-{{ $tipe }}" class="text-center text-muted">
                  <i class="icon-base ri ri-image-add-line ri-36px mb-2 d-block"></i>
                  <small>Belum ada aset</small>
                </div>
              @endif
            </div>

            {{-- Form upload --}}
            <div class="d-flex gap-2">
              <label class="btn btn-primary btn-sm flex-grow-1 mb-0" style="cursor:pointer;">
                <i class="icon-base ri ri-upload-line me-1"></i>
                {{ $info['url'] ? 'Ganti' : 'Upload' }}
                <input type="file" class="d-none file-upload" data-tipe="{{ $tipe }}" accept=".png,.jpg,.jpeg">
              </label>

              @if ($info['url'])
                <button class="btn btn-outline-danger btn-sm btn-hapus-aset" data-tipe="{{ $tipe }}"
                  data-label="{{ $info['label'] }}">
                  <i class="icon-base ri ri-delete-bin-line"></i>
                </button>
              @endif
            </div>

            {{-- Status --}}
            <div class="mt-2">
              @if ($info['aset'])
                <small class="text-success">
                  <i class="icon-base ri ri-checkbox-circle-line me-1"></i>
                  Terupload — {{ $info['aset']->updated_at->diffForHumans() }}
                </small>
              @else
                <small class="text-warning">
                  <i class="icon-base ri ri-error-warning-line me-1"></i>
                  Belum diupload
                </small>
              @endif
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>

  {{-- Modal Crop --}}
  <div class="modal fade" id="modalCrop" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Sesuaikan Gambar</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div style="max-height: 60vh; overflow: hidden;">
            <img id="crop-image" src="" style="max-width: 100%;">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-primary" id="btn-crop-simpan">
            <i class="icon-base ri ri-check-line me-1"></i> Simpan & Upload
          </button>
        </div>
      </div>
    </div>
  </div>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

      const aspectRatioMap = {
        ttd_ks: NaN,
        stempel: 1,
        logo_sekolah: 1,
        logo_kabupaten: 1,
        kop_potrait: 5,
        kop_landscape: 8,
        kop_kartu: 6,
      };

      let cropper = null;
      let tipeAktif = null;
      const modalCropEl = document.getElementById('modalCrop');
      const modalCrop = new bootstrap.Modal(modalCropEl);
      const cropImageEl = document.getElementById('crop-image');

      // ── Pilih file -> buka modal crop ──────────────────────────────────
      document.querySelectorAll('.file-upload').forEach(input => {
        input.addEventListener('change', function() {
          const file = this.files[0];
          if (!file) return;

          // Validasi tipe file dasar (ukuran dicek ulang setelah crop)
          if (!['image/png', 'image/jpeg'].includes(file.type)) {
            Swal.fire({
              icon: 'warning',
              title: 'Format tidak didukung',
              text: 'Gunakan PNG atau JPG.'
            });
            this.value = '';
            return;
          }

          tipeAktif = this.dataset.tipe;

          const reader = new FileReader();
          reader.onload = (e) => {
            cropImageEl.src = e.target.result;
            modalCrop.show();
          };
          reader.readAsDataURL(file);

          // reset input agar bisa pilih file yang sama lagi nanti
          this.value = '';
        });
      });

      // Init cropper setiap modal dibuka
      modalCropEl.addEventListener('shown.bs.modal', function() {
        if (cropper) cropper.destroy();
        cropper = new Cropper(cropImageEl, {
          aspectRatio: aspectRatioMap[tipeAktif] ?? NaN,
          viewMode: 1,
          autoCropArea: 1,
          responsive: true,
          background: false,
        });
      });

      modalCropEl.addEventListener('hidden.bs.modal', function() {
        if (cropper) {
          cropper.destroy();
          cropper = null;
        }
      });

      // ── Simpan hasil crop -> upload ─────────────────────────────────────
      document.getElementById('btn-crop-simpan').addEventListener('click', function() {
        if (!cropper) return;

        cropper.getCroppedCanvas().toBlob(async (blob) => {
          if (!blob) return;

          if (blob.size > 2 * 1024 * 1024) {
            Swal.fire({
              icon: 'warning',
              title: 'File terlalu besar',
              text: 'Maksimal 2MB setelah crop.'
            });
            return;
          }

          modalCrop.hide();

          // Preview lokal
          const previewUrl = URL.createObjectURL(blob);
          const previewEl = document.getElementById('preview-' + tipeAktif);
          if (previewEl.tagName === 'IMG') {
            previewEl.src = previewUrl;
          } else {
            const img = document.createElement('img');
            img.id = 'preview-' + tipeAktif;
            img.src = previewUrl;
            img.style = 'max-height:120px; max-width:100%; object-fit:contain;';
            previewEl.replaceWith(img);
          }

          // Upload ke server
          const formData = new FormData();
          formData.append('tipe', tipeAktif);
          formData.append('file', blob, `${tipeAktif}.png`);

          try {
            const response = await fetch('/logo/upload', {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
              },
              body: formData,
            });

            const res = await response.json();

            if (response.ok) {
              Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: res.message,
                timer: 2000,
                showConfirmButton: false
              });
              setTimeout(() => location.reload(), 2100);
            } else {
              throw new Error(res.message ?? 'Gagal upload');
            }
          } catch (err) {
            Swal.fire({
              icon: 'error',
              title: 'Gagal',
              text: err.message
            });
          }
        }, 'image/png');
      });

      // ── Hapus aset (tidak berubah) ────────────────────────────────────
      document.querySelectorAll('.btn-hapus-aset').forEach(btn => {
        btn.addEventListener('click', async function() {
          const tipe = this.dataset.tipe;
          const label = this.dataset.label;

          const konfirm = await Swal.fire({
            icon: 'warning',
            title: 'Hapus aset?',
            text: `${label} akan dihapus dari sistem.`,
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            customClass: {
              confirmButton: 'btn btn-danger me-3',
              cancelButton: 'btn btn-outline-secondary',
            },
            buttonsStyling: false,
          });

          if (!konfirm.isConfirmed) return;

          try {
            const response = await fetch('/logo/hapus', {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
              },
              body: JSON.stringify({
                tipe
              }),
            });

            const res = await response.json();

            if (response.ok) {
              Swal.fire({
                icon: 'success',
                title: 'Terhapus',
                text: res.message,
                timer: 2000,
                showConfirmButton: false
              });
              setTimeout(() => location.reload(), 2100);
            } else {
              throw new Error(res.message ?? 'Gagal hapus');
            }
          } catch (err) {
            Swal.fire({
              icon: 'error',
              title: 'Gagal',
              text: err.message
            });
          }
        });
      });
    });
  </script>
@endsection
