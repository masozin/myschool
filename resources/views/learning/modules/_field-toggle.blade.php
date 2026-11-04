{{--
  Parameter yang dikirim lewat @include:
  - name: nama field (untuk name="" input)
  - label: label yang ditampilkan
  - placeholder: placeholder textarea manual
--}}
<div class="mb-4 border rounded p-3">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <label class="form-label mb-0 fw-bold">{{ $label }}</label>
    <div class="btn-group btn-group-sm" role="group">
      <input type="radio" class="btn-check mode-toggle" name="mode_{{ $name }}" id="mode_auto_{{ $name }}"
        value="auto" data-target="{{ $name }}" checked>
      <label class="btn btn-outline-primary" for="mode_auto_{{ $name }}">
        <i class="ri ri-magic-line me-1"></i>Auto AI
      </label>

      <input type="radio" class="btn-check mode-toggle" name="mode_{{ $name }}"
        id="mode_manual_{{ $name }}" value="manual" data-target="{{ $name }}">
      <label class="btn btn-outline-primary" for="mode_manual_{{ $name }}">
        <i class="ri ri-edit-line me-1"></i>Manual
      </label>
    </div>
  </div>

  <div class="manual-input d-none" data-target-input="{{ $name }}">
    <textarea class="form-control" name="manual_{{ $name }}" rows="3"
      placeholder="{{ $placeholder ?? 'Tulis poin-poin draft kamu di sini, AI akan merapikan bahasanya' }}"></textarea>
    <small class="text-muted">AI akan merapikan bahasa & strukturnya, isi/substansinya tetap dari draft kamu.</small>
  </div>

  <small class="text-muted auto-caption" data-target-caption="{{ $name }}">
    AI akan menyusun bagian ini sepenuhnya berdasarkan data lain di form.
  </small>
</div>
