<style>
  @page {
    margin: 12mm 10mm;
    size: A4 portrait;
  }

  body {
    margin: 0;
    font-family: Arial, Helvetica, sans-serif;
    color: #12233f;
  }

  /* Kisi kartu memakai TABEL (2 kolom x 4 baris per halaman A4),
     sama seperti Kartu Pelajar — float tidak bisa diandalkan
     di DomPDF untuk banyak kartu. */
  .grid {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
  }

  .grid tr {
    page-break-inside: avoid;
  }

  .grid td.sel {
    width: 95mm;
    padding: 0 4.7mm 6mm 4.7mm;
    vertical-align: top;
  }

  .kartu {
    width: 85.6mm;
    height: 53.98mm;
    margin: 0;
    position: relative;
    overflow: hidden;
    page-break-inside: avoid;
    border-radius: 2.5mm;
    border: 0.3mm solid #8fa9c7;
    background: #ffffff;
  }

  .kop-img {
    width: 100%;
    height: 11mm;
    object-fit: cover;
    display: block;
  }

  .kepala-teks {
    text-align: center;
    padding: 2.2mm 4mm 1.6mm 4mm;
    border-bottom: 0.55mm solid #0a3c6e;
  }

  .kepala-teks .nama {
    font-size: 9.5pt;
    font-weight: bold;
    color: #0a3c6e;
    letter-spacing: 0.3pt;
  }

  .kepala-teks .npsn {
    font-size: 6pt;
    color: #4a5b70;
    margin-top: 0.4mm;
  }

  .judul {
    text-align: center;
    margin-top: 1.4mm;
    padding: 0 4mm;
  }

  .judul .label {
    font-size: 5.6pt;
    letter-spacing: 2.2pt;
    color: #7a8aa0;
  }

  .judul .teks {
    font-size: 10pt;
    font-weight: bold;
    color: #0a3c6e;
    text-transform: uppercase;
    line-height: 1.15;
    margin-top: 0.4mm;
  }

  .nomor {
    margin: 1.4mm auto 0 auto;
    width: 44mm;
    text-align: center;
    background: #eef3fa;
    border: 0.25mm solid #b9cde6;
    border-radius: 1.2mm;
    padding: 1.1mm 0 1.3mm 0;
  }

  .nomor .label {
    font-size: 5.2pt;
    letter-spacing: 1.6pt;
    color: #5a718c;
  }

  .nomor .nilai {
    font-size: 11.5pt;
    font-weight: bold;
    color: #0a3c6e;
    letter-spacing: 0.6pt;
    line-height: 1.1;
  }
</style>
<style>
  .isi {
    margin-top: 1.6mm;
    padding: 0 4mm;
  }

  .isi table {
    width: 100%;
    border-collapse: collapse;
  }

  .isi td {
    vertical-align: top;
    padding: 0;
  }

  .td-foto {
    width: 18mm;
  }

  .foto {
    width: 16mm;
    height: 20mm;
    border-radius: 1mm;
    object-fit: cover;
    background: #e6edf5;
    border: 0.3mm solid #b9cde6;
  }

  .foto-kosong {
    width: 16mm;
    height: 20mm;
    border-radius: 1mm;
    background: #e6edf5;
    border: 0.3mm dashed #b9cde6;
  }

  .rincian {
    padding-left: 2.5mm;
  }

  .nama-siswa {
    font-size: 8.6pt;
    font-weight: bold;
    color: #0a2c50;
    text-transform: uppercase;
    line-height: 1.15;
    margin-bottom: 1mm;
  }

  .field-table {
    width: 100%;
  }

  .field-table td {
    font-size: 6.4pt;
    line-height: 1.45;
    padding: 0;
    vertical-align: top;
  }

  .f-label {
    width: 15mm;
    color: #4a5b70;
  }

  .f-colon {
    width: 2mm;
  }

  .f-value {
    font-weight: 600;
    color: #111e30;
  }

  .signature {
    position: absolute;
    right: 4mm;
    bottom: 2.2mm;
    width: 31mm;
    text-align: center;
    z-index: 5;
  }

  .signature .titimangsa,
  .signature .jabatan {
    font-size: 5.8pt;
    color: #2d425c;
    display: block;
    line-height: 1.3;
  }

  .signature .ttd-img {
    height: 10mm;
    width: auto;
    display: block;
    margin: 0.5mm auto -3mm auto;
    position: relative;
    z-index: 2;
  }

  .signature .ttd-space {
    height: 5mm;
  }

  .signature strong {
    display: block;
    font-size: 6.4pt;
    text-decoration: underline;
    position: relative;
    z-index: 1;
  }

  .signature .nip {
    font-size: 5.8pt;
    color: #2d425c;
    display: block;
  }

  .stempel-wrap {
    position: absolute;
    right: 19mm;
    bottom: 1.8mm;
    width: 16mm;
    height: 16mm;
    z-index: 4;
  }

  .stempel-img {
    width: 16mm;
    height: 16mm;
    opacity: 0.85;
    transform: rotate(-8deg);
    object-fit: contain;
  }
</style>

@php $kolom = 0; @endphp
<table class="grid">
  @foreach ($siswaList as $siswa)
    @php
      $fotoPath = $siswa->photo ? storage_path('app/public/' . $siswa->photo) : null;
      $ada_foto = $fotoPath && file_exists($fotoPath);
    @endphp

    @if ($kolom % 2 === 0)
      <tr>
    @endif
    <td class="sel">
      <div class="kartu">
        @if ($pengaturan['pakai_kop'] && $pengaturan['kopPath'])
          <img src="{{ $pengaturan['kopPath'] }}" class="kop-img">
        @else
          <div class="kepala-teks">
            <div class="nama">{{ $sekolahGlobal['nama_sekolah'] }}</div>
            <div class="npsn">NPSN: {{ $sekolahGlobal['npsn'] }}</div>
          </div>
        @endif

        <div class="judul">
          <div class="label">KARTU PESERTA</div>
          <div class="teks">{{ $pengaturan['judul'] }}</div>
        </div>

        <div class="nomor">
          <div class="label">NOMOR PESERTA</div>
          <div class="nilai">{{ $siswa->nomor_peserta }}</div>
        </div>

        <div class="isi">
          <table>
            <tr>
              @if ($pengaturan['pakai_foto'])
                <td class="td-foto">
                  @if ($ada_foto)
                    <img src="{{ $fotoPath }}" class="foto">
                  @else
                    <div class="foto-kosong"></div>
                  @endif
                </td>
              @endif
              <td class="rincian">
                <div class="nama-siswa">{{ $siswa->nama_siswa }}</div>
                <table class="field-table">
                  @if ($pengaturan['tampil']['ttl'])
                    <tr>
                      <td class="f-label">TTL</td>
                      <td class="f-colon">:</td>
                      <td class="f-value">{{ $siswa->ttl_label }}</td>
                    </tr>
                  @endif
                  @if ($pengaturan['tampil']['nis'])
                    <tr>
                      <td class="f-label">NIS</td>
                      <td class="f-colon">:</td>
                      <td class="f-value">{{ $siswa->nis_label }}</td>
                    </tr>
                  @endif
                  @if ($pengaturan['tampil']['nisn'])
                    <tr>
                      <td class="f-label">NISN</td>
                      <td class="f-colon">:</td>
                      <td class="f-value">{{ $siswa->nisn ?? '-' }}</td>
                    </tr>
                  @endif
                  @if ($pengaturan['tampil']['kelas'])
                    <tr>
                      <td class="f-label">Kelas</td>
                      <td class="f-colon">:</td>
                      <td class="f-value">{{ $siswa->kelas_label }}</td>
                    </tr>
                  @endif
                  @if ($pengaturan['tampil']['jk'])
                    <tr>
                      <td class="f-label">Jenis Kelamin</td>
                      <td class="f-colon">:</td>
                      <td class="f-value">{{ $siswa->jk_label }}</td>
                    </tr>
                  @endif
                  @if ($pengaturan['tampil']['agama'])
                    <tr>
                      <td class="f-label">Agama</td>
                      <td class="f-colon">:</td>
                      <td class="f-value">{{ $siswa->agama ?? '-' }}</td>
                    </tr>
                  @endif
                </table>
              </td>
            </tr>
          </table>
        </div>

        @if ($pengaturan['pakai_stempel'] && $pengaturan['stempelPath'])
          <div class="stempel-wrap">
            <img src="{{ $pengaturan['stempelPath'] }}" class="stempel-img">
          </div>
        @endif

        <div class="signature">
          <span class="titimangsa">{{ $pengaturan['titimangsa'] }}</span>
          <span class="jabatan">Kepala Sekolah</span>
          @if ($pengaturan['pakai_ttd'] && $pengaturan['ttdPath'])
            <img src="{{ $pengaturan['ttdPath'] }}" class="ttd-img">
          @else
            <div class="ttd-space"></div>
          @endif
          <strong>{{ $pengaturan['penanda_tangan'] }}</strong>
          @if ($pengaturan['nip_penanda'])
            <span class="nip">NIP. {{ $pengaturan['nip_penanda'] }}</span>
          @endif
        </div>
      </div>
    </td>
    @php $kolom++; @endphp
    @if ($kolom % 2 === 0)
      </tr>
    @endif
  @endforeach
  @if ($kolom % 2 === 1)
    <td class="sel"></td>
    </tr>
  @endif
</table>
