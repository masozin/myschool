{{--
  Pratinjau foto SEBELUM diunggah: begitu pengguna memilih berkas dari
  file manager, thumbnail-nya langsung tampil supaya bisa diperiksa
  dulu sebelum menekan tombol unggah.

  Cara pakai: beri atribut data-pratinjau="#id-wadah" pada
  <input type="file" multiple>, siapkan <div id="...">, lalu @include
  partial ini SEKALI di halaman itu. Berjalan berdampingan dengan
  kompres-foto-js (pratinjau dari berkas pilihan; kompresi mengganti
  isi input tanpa mengubah tampilan pratinjau).
--}}
<script>
  (function() {
    if (window.__pratinjauFotoSiap) return;
    window.__pratinjauFotoSiap = true;

    document.querySelectorAll('input[data-pratinjau]').forEach(function(input) {
      input.addEventListener('change', function() {
        const wadah = document.querySelector(input.getAttribute('data-pratinjau'));
        if (!wadah) return;
        wadah.innerHTML = '';
        Array.from(input.files || []).forEach(function(berkas) {
          if (!berkas.type || berkas.type.indexOf('image/') !== 0) return;
          const kotak = document.createElement('div');
          kotak.style.cssText = 'width:96px';
          const img = document.createElement('img');
          img.src = URL.createObjectURL(berkas);
          img.alt = berkas.name;
          img.style.cssText = 'width:96px;height:96px;object-fit:cover;border-radius:10px;display:block';
          const ket = document.createElement('small');
          ket.className = 'text-muted';
          ket.style.cssText = 'display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis';
          ket.textContent = (berkas.size / 1048576).toFixed(2) + ' MB';
          ket.title = berkas.name;
          kotak.appendChild(img);
          kotak.appendChild(ket);
          wadah.appendChild(kotak);
        });
      });
    });
  })();
</script>
