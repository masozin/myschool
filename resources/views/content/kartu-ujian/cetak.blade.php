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
     di DomPDF. Tinggi kartu TETAP (bukan min-height): blok tanda
     tangan memakai position absolute yang di DomPDF hanya
     terjangkar benar pada kotak bertinggi pasti — dengan tinggi
     menyesuaikan, blok itu hilang dari hasil cetak. */
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
    padding: 0 1mm 2mm 1mm;
    vertical-align: top;
  }

  .kartu {
    width: 93mm;
    height: 60mm;
    margin: 0;
    position: relative;
    overflow: hidden;
    page-break-inside: avoid;
    border-radius: 2.5mm;
    border: 0.3mm solid #8fa9c7;
    background: #ffffff;
  }

  /* ── Kepala kartu: logo — judul/sekolah/tahun — logo ── */
  .kepala {
    width: 100%;
    border-collapse: collapse;
    border-bottom: 0.7mm double #0a3c6e;
  }

  .kepala td {
    vertical-align: middle;
    padding: 1.8mm 0 1.4mm 0;
  }

  .kepala-logo {
    width: 12.5mm;
    text-align: center;
  }

  .logo-kepala {
    width: 9.5mm;
    height: 9.5mm;
    object-fit: contain;
  }

  .kepala-teks {
    text-align: center;
  }

  .kepala-teks .judul-ujian {
    font-size: 7pt;
    font-weight: bold;
    color: #0a3c6e;
    text-transform: uppercase;
    line-height: 1.15;
  }

  .kepala-teks .nama-sekolah {
    font-size: 8.6pt;
    font-weight: bold;
    color: #12233f;
    text-transform: uppercase;
    margin-top: 0.5mm;
    line-height: 1.15;
  }

  .kepala-teks .tahun {
    font-size: 6.4pt;
    color: #4a5b70;
    margin-top: 0.4mm;
  }

  /* ── Nama siswa: besar, di tengah, tanpa label ── */
  .nama-besar {
    text-align: center;
    font-size: 9.6pt;
    font-weight: bold;
    color: #0a2c50;
    text-transform: uppercase;
    margin-top: 2.2mm;
    padding: 0 4mm;
    line-height: 1.15;
  }

  /* ── Isi: foto kiri sejajar baris-baris field ── */
  .isi {
    margin-top: 1.8mm;
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

  .field-table {
    width: 100%;
  }

  .field-table td {
    font-size: 7pt;
    line-height: 1.55;
    padding: 0;
    vertical-align: top;
  }

  .f-label {
    width: 16mm;
    color: #4a5b70;
  }

  .f-colon {
    width: 2mm;
  }

  .f-value {
    font-weight: 600;
    color: #111e30;
  }

  .f-nomor {
    font-size: 8pt;
    font-weight: bold;
    color: #0a3c6e;
    letter-spacing: 0.4pt;
  }
</style>
<style>
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
        <table class="kepala">
          <tr>
            <td class="kepala-logo">
              @if ($pengaturan['logoKiriPath'])
                <img src="{{ $pengaturan['logoKiriPath'] }}" class="logo-kepala">
              @endif
            </td>
            <td class="kepala-teks">
              <div class="judul-ujian">{{ $pengaturan['judul'] }}</div>
              <div class="nama-sekolah">{{ $sekolahGlobal['nama_sekolah'] }}</div>
              <div class="tahun">Tahun Pelajaran {{ $pengaturan['tahun_pelajaran'] }}</div>
            </td>
            <td class="kepala-logo">
              @if ($pengaturan['logoKananPath'])
                <img src="{{ $pengaturan['logoKananPath'] }}" class="logo-kepala">
              @endif
            </td>
          </tr>
        </table>

        <div class="nama-besar">{{ $siswa->nama_siswa }}</div>

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
                <table class="field-table">
                  @foreach ($pengaturan['urutan_field'] as $fk)
                    <tr>
                      <td class="f-label">{{ $pengaturan['label_field'][$fk] }}</td>
                      <td class="f-colon">:</td>
                      <td class="f-value {{ $fk === 'nomor' ? 'f-nomor' : '' }}">{{ $siswa->nilai_field[$fk] }}</td>
                    </tr>
                  @endforeach
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
