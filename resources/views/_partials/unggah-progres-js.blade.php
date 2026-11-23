{{--
  Unggah foto dengan INDIKASI PROGRES (XHR): bar progres + persentase
  selama berkas terkirim, pesan jelas saat selesai/gagal — menggantikan
  kirim form biasa yang membuat halaman terasa menggantung (dan di
  koneksi lambat bisa putus tanpa penjelasan, mis. ERR_QUIC_GOAWAY).

  Cara pakai: beri class "form-unggah-progres" pada <form> unggahan,
  lalu @include partial ini SEKALI di halaman itu. Form tetap punya
  action/method/enctype seperti biasa; bila JS mati, form terkirim
  dengan cara standar (progresif).

  Perilaku:
  - Bila kompresi browser (kompres-foto-js) masih berjalan, kirim
    ditunda dengan pesan tunggu.
  - Selama unggah: tombol kirim nonaktif, bar progres tampil.
  - Sukses: server membalas redirect (diikuti XHR) — halaman
    berpindah ke alamat tujuan redirect itu (mis. edit album).
  - Validasi gagal (422 JSON khas AJAX Laravel): pesan galat
    ditampilkan di dalam form.
  - Koneksi putus: pesan ramah + tombol aktif lagi; berkas yang
    sudah dipilih TIDAK hilang, pengguna tinggal menekan lagi.
--}}
<script>
  (function() {
    if (window.__unggahProgresSiap) return;
    window.__unggahProgresSiap = true;

    document.querySelectorAll('form.form-unggah-progres').forEach(function(form) {
      // Wadah progres disiapkan sekali, disembunyikan sampai dipakai.
      const wadah = document.createElement('div');
      wadah.className = 'unggah-progres mt-3';
      wadah.style.display = 'none';
      wadah.innerHTML =
        '<div class="progress" style="height:14px">' +
        '<div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width:0%"></div>' +
        '</div>' +
        '<small class="unggah-progres-teks text-muted d-block mt-1"></small>' +
        '<div class="unggah-progres-galat alert alert-danger mt-2 mb-0" style="display:none"></div>';
      form.appendChild(wadah);
      const bar = wadah.querySelector('.progress-bar');
      const teks = wadah.querySelector('.unggah-progres-teks');
      const galat = wadah.querySelector('.unggah-progres-galat');
      const tombol = form.querySelectorAll('button[type="submit"], input[type="submit"]');

      form.addEventListener('submit', function(ev) {
        const sedangKompres = form.querySelector('input.kompres-foto[data-kompres-berjalan]');
        if (sedangKompres) {
          ev.preventDefault();
          wadah.style.display = '';
          teks.textContent = 'Tunggu — kompresi foto di browser masih berjalan…';
          return;
        }
        ev.preventDefault();

        wadah.style.display = '';
        galat.style.display = 'none';
        galat.innerHTML = '';
        bar.style.width = '0%';
        teks.textContent = 'Mengunggah… 0%';
        tombol.forEach(function(t) { t.disabled = true; });

        const xhr = new XMLHttpRequest();
        xhr.open(form.method || 'POST', form.action);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.addEventListener('progress', function(e) {
          if (!e.lengthComputable) {
            teks.textContent = 'Mengunggah… (ukuran tidak terukur)';
            return;
          }
          const persen = Math.round(e.loaded / e.total * 100);
          bar.style.width = persen + '%';
          teks.textContent = 'Mengunggah… ' + persen + '% (' +
            (e.loaded / 1048576).toFixed(2) + ' dari ' + (e.total / 1048576).toFixed(2) + ' MB terkirim)';
        });

        xhr.addEventListener('load', function() {
          if (xhr.status >= 200 && xhr.status < 300) {
            bar.style.width = '100%';
            teks.textContent = 'Unggahan selesai — membuka halaman tujuan…';
            window.location.href = xhr.responseURL || window.location.href;
            return;
          }
          tombol.forEach(function(t) { t.disabled = false; });
          if (xhr.status === 422) {
            let pesan = 'Ada isian yang belum benar.';
            try {
              const data = JSON.parse(xhr.responseText);
              if (data && data.errors) {
                pesan = Object.values(data.errors).flat().join(' ');
              }
            } catch (e) { /* biarkan pesan umum */ }
            galat.textContent = pesan;
          } else {
            galat.textContent = 'Server menjawab dengan kode ' + xhr.status + '. Coba lagi sebentar lagi — berkas yang sudah dipilih tidak hilang.';
          }
          galat.style.display = '';
          teks.textContent = 'Unggahan belum berhasil.';
        });

        xhr.addEventListener('error', function() {
          tombol.forEach(function(t) { t.disabled = false; });
          galat.textContent = 'Koneksi terputus di tengah unggahan (internet lambat / server menutup sambungan). Berkas yang sudah dipilih tidak hilang — periksa koneksi lalu tekan tombol kirim sekali lagi. Bila tadi membuat album baru, cek dulu daftar album: albumnya mungkin sudah terbentuk dan tinggal ditambahi foto.';
          galat.style.display = '';
          teks.textContent = 'Unggahan terputus.';
        });

        xhr.addEventListener('timeout', function() {
          tombol.forEach(function(t) { t.disabled = false; });
          galat.textContent = 'Unggahan melewati batas waktu. Coba lagi dengan foto yang lebih sedikit per kiriman.';
          galat.style.display = '';
          teks.textContent = 'Unggahan kehabisan waktu.';
        });

        xhr.send(new FormData(form));
      });
    });
  })();
</script>
