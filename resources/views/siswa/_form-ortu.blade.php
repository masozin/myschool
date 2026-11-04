{{--
  Partial: _form-ortu.blade.php
  Params:
    $prefix  : 'ayah' | 'ibu' | 'wali'
    $label   : 'Ayah' | 'Ibu' | 'Wali'
    $showHp  : true | false
--}}
<div class="col-sm-6 form-control-validation">
  <div class="form-floating form-floating-outline">
    <input type="text" name="nama_{{ $prefix }}" id="nama-{{ $prefix }}" class="form-control"
      placeholder="Nama {{ $label }}" />
    <label for="nama-{{ $prefix }}">Nama {{ $label }}</label>
  </div>
</div>

<div class="col-sm-6 form-control-validation">
  <div class="form-floating form-floating-outline">
    <input type="text" name="nik_{{ $prefix }}" id="nik-{{ $prefix }}" class="form-control"
      maxlength="16" placeholder="1602xxxxxxx" />
    <label for="nik-{{ $prefix }}">NIK {{ $label }}</label>
  </div>
</div>

<div class="col-sm-6 form-control-validation">
  <div class="form-floating form-floating-outline">
    <input type="number" name="tahun_{{ $prefix }}" id="tahun-{{ $prefix }}" class="form-control"
      placeholder="2025" />
    <label for="tahun-{{ $prefix }}">Tahun Lahir</label>
  </div>
</div>

<div class="col-sm-6 form-control-validation">
  <div class="form-floating form-floating-outline">
    <input type="text" name="pekerjaan_{{ $prefix }}" id="pekerjaan-{{ $prefix }}" class="form-control"
      placeholder="Pekerjaan {{ $label }}" />
    <label for="pekerjaan-{{ $prefix }}">Pekerjaan {{ $label }}</label>
  </div>
</div>

@if ($showHp)
  <div class="col-sm-6 form-control-validation">
    <div class="form-floating form-floating-outline">
      <input type="text" name="no_hp" id="no-hp" class="form-control" placeholder="08xxx" />
      <label for="no-hp">No. Handphone</label>
    </div>
  </div>
@endif
