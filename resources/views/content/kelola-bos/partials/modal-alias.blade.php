{{--
  Modal editor nama alias Kelola BOS (Fase 1.7) — dipakai
  bersama Kertas Kerja, BKU, dan Referensi Barang.
  Tombol pemicu: elemen apa pun dengan atribut
  data-alias-edit, data-nama-asli="...", data-alias="...".
  Mengirim ke POST /kelola-bos/alias: alias terisi disimpan,
  alias kosong menghapus alias (kembali ke nama asli ARKAS).
--}}
<div class="modal fade" id="modal-alias" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" action="{{ route('kelola-bos.alias.simpan') }}" class="modal-content" id="form-alias">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri ri-pencil-line me-2"></i>Nama Alias</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nama asli (dari ARKAS)</label>
          <div class="border rounded px-2 py-2 small bg-light text-break" id="alias-nama-asli">—</div>
          <input type="hidden" name="nama_sumber" id="alias-input-sumber">
        </div>
        <div>
          <label class="form-label" for="alias-input">Alias (nama pendek tampilan)</label>
          <input type="text" name="alias" id="alias-input" class="form-control" maxlength="255"
            placeholder="mis. Honor Ekskul" autocomplete="off">
          <div class="form-text">Alias hanya mengubah tampilan di myschool — data ARKAS tidak berubah.
            Nama asli tetap terlihat saat nama disorot. Kosongkan untuk menghapus alias.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger me-auto" id="alias-btn-hapus">
          <i class="ri ri-delete-bin-line me-1"></i>Hapus Alias</button>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
  document.addEventListener('click', function(e) {
    var btn = e.target.closest('[data-alias-edit]');
    if (!btn) return;
    document.getElementById('alias-nama-asli').textContent = btn.getAttribute('data-nama-asli') || '';
    document.getElementById('alias-input-sumber').value = btn.getAttribute('data-nama-asli') || '';
    document.getElementById('alias-input').value = btn.getAttribute('data-alias') || '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-alias')).show();
  });
  document.getElementById('alias-btn-hapus').addEventListener('click', function() {
    document.getElementById('alias-input').value = '';
    document.getElementById('form-alias').submit();
  });
</script>
