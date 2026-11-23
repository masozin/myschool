{{--
  Editor teks kaya RINGAN tanpa pustaka luar (contenteditable +
  execCommand): untuk textarea ber-class "editor-kaya" — dipakai
  isi berita, sambutan, visi & misi Web Sekolah.

  Bilah alat: Tebal, Miring, Garis Bawah, Judul kecil, Paragraf,
  Daftar Poin, Daftar Nomor, Tautan, Bersihkan Format. Hasilnya
  HTML terbatas yang di server disaring lagi oleh
  App\Support\TeksKaya (allowlist) sebelum disimpan & ditampilkan.

  Perilaku:
  - Textarea asli disembunyikan; isinya menjadi benih editor.
    Teks LAMA yang masih polos diubah ke paragraf HTML untuk
    ditampilkan di editor (tampilan publiknya sama).
  - Isi editor disinkronkan kembali ke textarea setiap kali
    berubah dan saat form dikirim — dari sisi server tidak ada
    perbedaan: yang terkirim tetap field textarea biasa.
  - Tempel (paste) selalu sebagai teks polos agar format asing
    dari Word/Google Docs tidak mengotori hasil.
  - Bila JS mati, textarea tampil biasa seperti sebelumnya.
--}}
<style>
  .tk-bungkus{border:1px solid #d9dee3;border-radius:.5rem;background:#fff;overflow:hidden}
  .tk-bungkus:focus-within{border-color:#696cff;box-shadow:0 0 0 .2rem rgba(105,108,255,.14)}
  .tk-bilah{display:flex;flex-wrap:wrap;gap:.3rem;padding:.45rem .5rem;border-bottom:1px solid #e7e9ed;background:#f8f9fb}
  .tk-tombol{border:1px solid #d9dee3;background:#fff;border-radius:.375rem;padding:.22rem .6rem;font-size:.85rem;line-height:1.45;cursor:pointer;color:#3a4452}
  .tk-tombol:hover{background:#eef0ff;border-color:#b7baff}
  .tk-pemisah{width:1px;background:#dfe3e8;margin:.15rem .2rem}
  .tk-sunting{min-height:150px;max-height:440px;overflow:auto;padding:.75rem .95rem;line-height:1.7;color:#212529}
  .tk-sunting:focus{outline:none}
  .tk-sunting p{margin:0 0 .8em}
  .tk-sunting ul,.tk-sunting ol{padding-left:1.45rem;margin:.2em 0 .9em}
  .tk-sunting h4{font-size:1.05rem;margin:.5em 0 .3em}
  .tk-sunting blockquote{border-left:3px solid #b7baff;padding-left:12px;margin:.6em 0;color:#5d6b7f}
</style>
<script>
  (function() {
    if (window.__editorKayaSiap) return;
    window.__editorKayaSiap = true;

    function teksKeHtml(teks) {
      return teks.split(/\n{2,}/).map(function(p) {
        p = p.trim();
        if (!p) return '';
        return '<p>' + p.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>') + '</p>';
      }).join('');
    }

    var DAFTAR_TOMBOL = [
      { perintah: 'bold', label: '<b>B</b>', judul: 'Tebal' },
      { perintah: 'italic', label: '<i>I</i>', judul: 'Miring' },
      { perintah: 'underline', label: '<u>U</u>', judul: 'Garis bawah' },
      { pemisah: true },
      { perintah: 'formatBlock', arg: 'H4', label: 'Judul', judul: 'Jadikan judul kecil' },
      { perintah: 'formatBlock', arg: 'P', label: 'Paragraf', judul: 'Paragraf biasa' },
      { pemisah: true },
      { perintah: 'insertUnorderedList', label: '&bull; Daftar', judul: 'Daftar poin' },
      { perintah: 'insertOrderedList', label: '1. Daftar', judul: 'Daftar nomor' },
      { pemisah: true },
      { perintah: 'createLink', label: 'Tautan', judul: 'Sisipkan tautan (pilih teks dulu)' },
      { perintah: 'removeFormat', label: 'Bersihkan', judul: 'Hapus format pada teks terpilih' }
    ];

    document.querySelectorAll('textarea.editor-kaya').forEach(function(area) {
      var bungkus = document.createElement('div');
      bungkus.className = 'tk-bungkus';
      var bilah = document.createElement('div');
      bilah.className = 'tk-bilah';
      var sunting = document.createElement('div');
      sunting.className = 'tk-sunting';
      sunting.contentEditable = 'true';

      DAFTAR_TOMBOL.forEach(function(def) {
        if (def.pemisah) {
          var garis = document.createElement('span');
          garis.className = 'tk-pemisah';
          bilah.appendChild(garis);
          return;
        }
        var tombol = document.createElement('button');
        tombol.type = 'button';
        tombol.className = 'tk-tombol';
        tombol.innerHTML = def.label;
        tombol.title = def.judul;
        // mousedown (bukan click) + preventDefault: pilihan teks di
        // editor tidak hilang saat tombol ditekan.
        tombol.addEventListener('mousedown', function(ev) {
          ev.preventDefault();
          if (def.perintah === 'createLink') {
            var url = window.prompt('Alamat tautan (https://…):', 'https://');
            if (!url) return;
            document.execCommand('createLink', false, url);
          } else if (def.arg) {
            document.execCommand(def.perintah, false, def.arg);
          } else {
            document.execCommand(def.perintah, false, null);
          }
          sunting.focus();
          sinkron();
        });
        bilah.appendChild(tombol);
      });

      var awal = area.value || '';
      sunting.innerHTML = /<[a-z][\s\S]*>/i.test(awal) ? awal : teksKeHtml(awal);

      function sinkron() {
        area.value = sunting.textContent.trim() === '' ? '' : sunting.innerHTML.trim();
      }
      sunting.addEventListener('input', sinkron);
      sunting.addEventListener('paste', function(ev) {
        ev.preventDefault();
        var teks = (ev.clipboardData || window.clipboardData).getData('text/plain');
        document.execCommand('insertText', false, teks);
      });
      if (area.form) area.form.addEventListener('submit', sinkron);

      bungkus.appendChild(bilah);
      bungkus.appendChild(sunting);
      area.removeAttribute('required');
      area.style.display = 'none';
      area.insertAdjacentElement('afterend', bungkus);
    });
  })();
</script>
