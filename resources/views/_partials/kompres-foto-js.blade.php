{{--
  Kompresi foto di browser SEBELUM terkirim (penghemat untuk shared
  hosting: CPU server tidak bekerja keras, ukuran upload jauh lebih
  kecil, dan berkas lolos batas upload php.ini yang lebih rendah).

  Cara pakai: beri class "kompres-foto" pada <input type="file"> dan
  atribut data-maks="1600" (sisi terpanjang hasil, piksel), lalu
  @include partial ini SEKALI di halaman itu.

  Ini lapisan pertama saja — server tetap memvalidasi & mengompresi
  ulang lewat App\Support\KompresGambar sebagai pengaman (browser lama,
  JS gagal, orientasi EXIF, berkas yang lolos dari jalur lain).
--}}
<script>
  (function() {
    if (window.__kompresFotoSiap) return;
    window.__kompresFotoSiap = true;

    function muatGambar(berkas) {
      if (window.createImageBitmap) {
        return createImageBitmap(berkas, { imageOrientation: 'from-image' })
          .catch(function() { return muatLewatImg(berkas); });
      }
      return muatLewatImg(berkas);
    }

    function muatLewatImg(berkas) {
      return new Promise(function(resolve, reject) {
        const url = URL.createObjectURL(berkas);
        const img = new Image();
        img.onload = function() { resolve({ gambar: img, url: url, lebar: img.naturalWidth, tinggi: img.naturalHeight }); };
        img.onerror = function() { URL.revokeObjectURL(url); reject(new Error('gambar tidak terbaca')); };
        img.src = url;
      });
    }

    function kompresSatu(berkas, maks) {
      return muatGambar(berkas).then(function(hasil) {
        const lebar = hasil.lebar || hasil.gambar.width;
        const tinggi = hasil.tinggi || hasil.gambar.height;
        const url = hasil.url || null;
        const selesai = function(hasilAkhir) { if (url) URL.revokeObjectURL(url); return hasilAkhir; };

        // Sudah cukup kecil & dimensinya pas: kirim apa adanya.
        if (Math.max(lebar, tinggi) <= maks && berkas.size <= 2500000 && berkas.type === 'image/jpeg') {
          return selesai(berkas);
        }
        const skala = Math.min(1, maks / Math.max(lebar, tinggi));
        const kanvas = document.createElement('canvas');
        kanvas.width = Math.max(1, Math.round(lebar * skala));
        kanvas.height = Math.max(1, Math.round(tinggi * skala));
        const ctx = kanvas.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, kanvas.width, kanvas.height);
        ctx.drawImage(hasil.gambar, 0, 0, kanvas.width, kanvas.height);
        if (hasil.gambar.close) hasil.gambar.close();
        return new Promise(function(resolve) {
          kanvas.toBlob(function(blob) {
            if (!blob || blob.size >= berkas.size) { resolve(selesai(berkas)); return; }
            const namaBaru = berkas.name.replace(/\.[^.]+$/, '') + '.jpg';
            resolve(selesai(new File([blob], namaBaru, { type: 'image/jpeg' })));
          }, 'image/jpeg', 0.82);
        });
      }).catch(function() { return berkas; });
    }

    document.querySelectorAll('input.kompres-foto').forEach(function(input) {
      input.addEventListener('change', async function() {
        if (!input.files || input.files.length === 0) return;
        if (typeof DataTransfer === 'undefined') return;
        const maks = parseInt(input.getAttribute('data-maks') || '1600', 10);
        const berkasAwal = Array.from(input.files);
        if (!berkasAwal.some(function(f) { return f.type && f.type.indexOf('image/') === 0; })) return;

        const form = input.closest('form');
        const tombol = form ? form.querySelectorAll('button[type="submit"], input[type="submit"]') : [];
        let status = input.parentElement.querySelector('.status-kompres-js');
        if (!status) {
          status = document.createElement('small');
          status.className = 'text-muted d-block mt-1 status-kompres-js';
          input.insertAdjacentElement('afterend', status);
        }
        tombol.forEach(function(t) { t.disabled = true; });
        status.textContent = 'Mengompresi foto di browser…';

        let totalAwal = 0, totalBaru = 0;
        const hasil = [];
        for (const berkas of berkasAwal) {
          totalAwal += berkas.size;
          if (berkas.type && berkas.type.indexOf('image/') === 0) {
            const terkompres = await kompresSatu(berkas, maks);
            totalBaru += terkompres.size;
            hasil.push(terkompres);
          } else {
            totalBaru += berkas.size;
            hasil.push(berkas);
          }
        }

        const dt = new DataTransfer();
        hasil.forEach(function(f) { dt.items.add(f); });
        input.files = dt.files;
        tombol.forEach(function(t) { t.disabled = false; });
        status.textContent = totalBaru < totalAwal ?
          'Foto terkompresi: ' + (totalAwal / 1048576).toFixed(1) + ' MB → ' + (totalBaru / 1048576).toFixed(2) + ' MB. Klik tombol kirim untuk melanjutkan.' :
          '';
      });
    });
  })();
</script>
