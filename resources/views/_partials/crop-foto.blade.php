{{--
  Potong foto (Cropper.js 1.6.2, dibundel lokal di public/assets/vendor/cropper).

  Cara pakai: beri class "crop-foto" pada <input type="file"> dan atribut
  data-preview="#id-img-pratinjau", lalu @include partial ini SEKALI di
  halaman itu. Saat pengguna memilih berkas, modal potong terbuka dengan
  pilihan rasio Bebas / 2x3 / 3x4 / 4x6; hasil potong (JPEG) menggantikan
  berkas di input, berkas asli tetap bisa dipilih lewat "Pakai Foto Asli".
  Kompresi ukuran berkas tetap dilakukan server (App\Support\KompresGambar).
--}}
<link rel="stylesheet" href="{{ asset('assets/vendor/cropper/cropper.min.css') }}">
<style>
  #modalCropFoto .cropper-container { margin: 0 auto; }
  #modalCropFoto .wadah-crop { max-height: 62vh; }
  #modalCropFoto .wadah-crop img { max-width: 100%; display: block; }
</style>

<div class="modal fade" id="modalCropFoto" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Potong Foto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
          <span class="text-muted me-1">Rasio:</span>
          <div class="btn-group" role="group" aria-label="Pilihan rasio potong">
            <button type="button" class="btn btn-outline-primary active" data-crop-rasio="bebas">Bebas</button>
            <button type="button" class="btn btn-outline-primary" data-crop-rasio="2x3">2&times;3</button>
            <button type="button" class="btn btn-outline-primary" data-crop-rasio="3x4">3&times;4</button>
            <button type="button" class="btn btn-outline-primary" data-crop-rasio="4x6">4&times;6</button>
          </div>
          <small class="text-muted">Geser / perbesar foto di dalam bingkai, lalu klik “Pakai Hasil Potong”.</small>
        </div>
        <div class="wadah-crop">
          <img id="gambarCropFoto" src="" alt="Foto yang akan dipotong">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger me-auto" id="tombolCropBatal">Batal</button>
        <button type="button" class="btn btn-outline-secondary" id="tombolCropAsli">Pakai Foto Asli</button>
        <button type="button" class="btn btn-primary" id="tombolCropPakai">Pakai Hasil Potong</button>
      </div>
    </div>
  </div>
</div>

<script src="{{ asset('assets/vendor/cropper/cropper.min.js') }}"></script>
<script>
  (function() {
    if (window.__cropFotoSiap) return;
    window.__cropFotoSiap = true;

    // Rasio + ukuran keluaran (piksel) per pilihan. 2x3 dan 4x6 bentuknya
    // sama (2:3) — bedanya ukuran hasil: 4x6 lebih besar untuk cetak.
    const PILIHAN = {
      bebas: { rasio: NaN, lebar: 1600, tinggi: 1600 },
      '2x3': { rasio: 2 / 3, lebar: 600, tinggi: 900 },
      '3x4': { rasio: 3 / 4, lebar: 900, tinggi: 1200 },
      '4x6': { rasio: 4 / 6, lebar: 1200, tinggi: 1800 }
    };

    let cropper = null;
    let inputAktif = null;
    let urlAktif = null;
    let sudahDiputuskan = false;
    let pilihanAktif = 'bebas';

    const modalEl = document.getElementById('modalCropFoto');
    const gambarEl = document.getElementById('gambarCropFoto');

    function modal() {
      return bootstrap.Modal.getOrCreateInstance(modalEl);
    }

    function aturPratinjau(fileAtauBlob) {
      if (!inputAktif) return;
      const sel = inputAktif.getAttribute('data-preview');
      if (!sel) return;
      const img = document.querySelector(sel);
      if (img) img.src = URL.createObjectURL(fileAtauBlob);
    }

    document.querySelectorAll('input.crop-foto').forEach(function(input) {
      input.addEventListener('change', function() {
        const berkas = input.files && input.files[0];
        if (!berkas) return;
        if (!berkas.type || berkas.type.indexOf('image/') !== 0) return;
        if (typeof Cropper === 'undefined' || typeof bootstrap === 'undefined') {
          // Pustaka gagal dimuat: jangan halangi unggahan, cukup pratinjau.
          const sel = input.getAttribute('data-preview');
          const img = sel ? document.querySelector(sel) : null;
          if (img) img.src = URL.createObjectURL(berkas);
          return;
        }
        inputAktif = input;
        sudahDiputuskan = false;
        pilihanAktif = 'bebas';
        modalEl.querySelectorAll('[data-crop-rasio]').forEach(function(b) {
          b.classList.toggle('active', b.getAttribute('data-crop-rasio') === 'bebas');
        });
        if (urlAktif) URL.revokeObjectURL(urlAktif);
        urlAktif = URL.createObjectURL(berkas);
        gambarEl.src = urlAktif;
        modal().show();
      });
    });

    modalEl.addEventListener('shown.bs.modal', function() {
      if (cropper) cropper.destroy();
      cropper = new Cropper(gambarEl, {
        viewMode: 1,
        autoCropArea: 0.85,
        responsive: true,
        background: false,
        aspectRatio: NaN
      });
    });

    modalEl.addEventListener('hidden.bs.modal', function() {
      if (cropper) {
        cropper.destroy();
        cropper = null;
      }
      if (!sudahDiputuskan && inputAktif && inputAktif.files && inputAktif.files[0]) {
        // Modal ditutup tanpa pilihan (tombol X / Esc): pakai foto asli.
        aturPratinjau(inputAktif.files[0]);
      }
      if (urlAktif) {
        URL.revokeObjectURL(urlAktif);
        urlAktif = null;
      }
      inputAktif = null;
    });

    modalEl.querySelectorAll('[data-crop-rasio]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        pilihanAktif = btn.getAttribute('data-crop-rasio');
        modalEl.querySelectorAll('[data-crop-rasio]').forEach(function(b) {
          b.classList.toggle('active', b === btn);
        });
        if (cropper) cropper.setAspectRatio(PILIHAN[pilihanAktif].rasio);
      });
    });

    document.getElementById('tombolCropPakai').addEventListener('click', function() {
      if (!cropper || !inputAktif) return;
      const p = PILIHAN[pilihanAktif];
      const kanvas = pilihanAktif === 'bebas' ?
        cropper.getCroppedCanvas({ maxWidth: p.lebar, maxHeight: p.tinggi, imageSmoothingQuality: 'high' }) :
        cropper.getCroppedCanvas({ width: p.lebar, height: p.tinggi, imageSmoothingQuality: 'high' });
      if (!kanvas) return;
      kanvas.toBlob(function(blob) {
        if (!blob) return;
        const berkasBaru = new File([blob], 'foto-potongan.jpg', { type: 'image/jpeg' });
        const dt = new DataTransfer();
        dt.items.add(berkasBaru);
        inputAktif.files = dt.files;
        sudahDiputuskan = true;
        aturPratinjau(blob);
        modal().hide();
      }, 'image/jpeg', 0.88);
    });

    document.getElementById('tombolCropAsli').addEventListener('click', function() {
      sudahDiputuskan = true;
      if (inputAktif && inputAktif.files && inputAktif.files[0]) {
        aturPratinjau(inputAktif.files[0]);
      }
      modal().hide();
    });

    document.getElementById('tombolCropBatal').addEventListener('click', function() {
      sudahDiputuskan = true;
      if (inputAktif) inputAktif.value = '';
      modal().hide();
    });
  })();
</script>
