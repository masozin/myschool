<style>
  @page {
    margin: 0;
    size: A4 portrait;
  }

  body {
    margin: 15mm 13mm;
    font-family: Arial, Helvetica, sans-serif;
    color: #12233f;
  }

  .kartu {
    width: 85.6mm;
    height: 53.98mm;
    float: left;
    margin: 0 4mm 6mm 0;
    position: relative;
    overflow: hidden;
    border-radius: 3mm;
    background-image: url('{{ $bgDepan }}');
    background-size: cover;
    background-repeat: no-repeat;
    border: 0.25mm solid #ccc;
  }

  .kartu.back {
    background-image: url('{{ $bgBelakang }}');
  }

  .school-head {
    position: absolute;
    top: 2.2mm;
    left: 4mm;
    right: 0;
    /* full width supaya bisa center */
    z-index: 3;
    text-align: left;
    /* kunci utama supaya isinya center */
  }

  /* Wrapper ini dijadikan "unit" yang lebarnya menyesuaikan isi, lalu di-center */
  .school-head-inner {
    display: inline-block;
    /* penting: biar text-align:center di parent berlaku */
  }

  .logo,
  .logo-placeholder {
    display: inline-block;
    vertical-align: middle;
    width: 9mm;
    height: 9mm;
    border-radius: 50%;
  }

  .logo {
    background: #fff;
    border: 0.35mm solid #d4af37;
    object-fit: contain;
    padding: 1pt;
  }

  .logo-placeholder {
    background: rgba(255, 255, 255, 0.25);
    border: 0.35mm dashed rgba(255, 255, 255, 0.7);
  }

  .school-title {
    display: inline-block;
    vertical-align: middle;
    text-align: left;
    margin-left: 3mm;
    max-width: 55mm;
  }

  .school-title .main {
    font-size: 9pt;
    font-weight: bold;
    color: #fff;
    /* line-height: 1; */
    letter-spacing: 0.3pt;
  }

  .school-title .school {
    font-size: 7.5pt;
    font-weight: bold;
    color: #fff;
    margin-top: 0.6mm;
  }

  .school-title .npsn {
    font-size: 6pt;
    color: #dbe6f4;
    margin-top: 0.3mm;
  }

  .student-content {
    position: absolute;
    top: 18mm;
    left: 4mm;
    right: 4mm;
    z-index: 3;
  }

  .student-content table {
    width: 100%;
    border-collapse: collapse;
  }

  .student-content td {
    vertical-align: top;
    padding: 0;
  }

  .td-photo {
    width: 19mm;
  }

  .photo {
    width: 17mm;
    height: 21mm;
    border-radius: 1mm;
    object-fit: cover;
    background: #dcefff;
    border: 0.35mm solid #fff;
  }

  .photo-kosong {
    width: 17mm;
    height: 21mm;
    border-radius: 1mm;
    background: #dcefff;
    border: 0.35mm solid #fff;
  }

  .barcode-img {
    display: block;
    width: 18mm;
    /* samakan lebar dengan .photo */
    height: 5mm;
    margin-top: 5mm;
  }

  .details {
    padding-left: 2.5mm;
  }

  .detail-table {
    width: 100%;
  }

  .detail-table td {
    font-size: 5.6pt;
    line-height: 1.35;
    padding: 0.2mm 0;
    vertical-align: top;
  }

  .dt-label {
    width: 16mm;
    font-weight: bold;
    color: #243c5d;
  }

  .dt-colon {
    width: 2mm;
    font-weight: bold;
  }

  .dt-value {
    color: #111e30;
    font-weight: 600;
  }

  .nama-siswa {
    font-size: 9pt;
    font-weight: bold;
    color: {{ $warna['primary_dark'] }};
    margin-bottom: 1.5mm;
    line-height: 1.15;
    text-transform: uppercase;
  }

  .signature {
    position: absolute;
    right: 4mm;
    bottom: 2.5mm;
    text-align: center;
    width: 26mm;
    z-index: 5;
  }

  .signature small {
    font-size: 4.6pt;
    display: block;
    color: #2d425c;
    position: relative;
    z-index: 1;
  }

  .signature .ttd-img {
    height: 12mm;
    /* dari 6mm, sedikit dibesarkan */
    width: auto;
    margin: 0mm auto -4mm auto;
    /* margin-bottom negatif = menumpuk ke nama di bawahnya */
    display: block;
    position: relative;
    z-index: 2;
    transform: rotate(-3deg);
    /* opsional: kesan tanda tangan natural, hapus kalau tidak mau */
  }

  /* ── Stempel: berdiri sendiri di kiri area TTD ── */
  .stempel-wrap {
    position: absolute;
    right: 15mm;
    /* geser ke kiri dari .signature */
    bottom: 2mm;
    width: 18mm;
    height: 18mm;
    z-index: 4;
    /* di bawah ttd (z-index 5) supaya kalau ada overlap, ttd tetap di depan */
  }

  .stempel-img {
    width: 18mm;
    height: 18mm;
    opacity: 0.85;
    /* khas stempel asli, sedikit transparan/pudar */
    transform: rotate(-8deg);
    object-fit: contain;
  }

  .signature strong {
    display: block;
    font-size: 4.8pt;
    position: relative;
    z-index: 1;
    /* di bawah gambar ttd, supaya ttd "menutupi" sebagian teks */
  }

  .signature .ttd-space {
    height: 6mm;
  }

  /* ── SISI BELAKANG ─────────────────────────────────────── */
  .back-header {
    position: absolute;
    top: 3mm;
    left: 4mm;
    width: 55mm;
    /* dari "right: 4mm" (full lebar) → dibatasi 55mm, stop sebelum area QR */
    z-index: 3;
  }

  .back-header .judul {
    font-size: 8pt;
    font-weight: bold;
    color: #ffffff;
    line-height: 1.15;
    letter-spacing: 0.2pt;
  }

  .back-header .sub {
    font-size: 5.6pt;
    color: #dfe9f6;
    margin-top: 0.6mm;
  }

  .back-header .alamat {
    font-size: 5pt;
    color: #eef3fa;
    line-height: 1.35;
    margin-top: 1.2mm;
  }

  .back-rules {
    position: absolute;
    top: 24mm;
    left: 6mm;
    z-index: 3;
  }

  .back-rules .judul {
    font-size: 6pt;
    font-weight: bold;
    color: {{ $warna['primary_dark'] }};
    letter-spacing: 0.3pt;
    padding-bottom: 5pt;
    text-transform: uppercase;
  }

  .back-rules table {
    width: 100%;
    border-collapse: collapse;
  }

  .back-rules td {
    font-size: 4.9pt;
    line-height: 1.3;
    padding: 0.4mm 0;
    vertical-align: top;
  }

  .rule-no {
    width: 4mm;
    font-weight: bold;
    color: #0962b7;
  }

  .rule-text {
    color: #243c5d;
  }

  /* ── QR di sisi belakang ── */
  .back-qr {
    position: absolute;
    top: 3mm;
    right: 4mm;
    width: 13mm;
    height: 13mm;
    background: #fff;
    padding: 0.8mm;
    border-radius: 1mm;
    z-index: 4;
  }

  .back-qr img {
    width: 100%;
    height: 100%;
    display: block;
  }

  /* Sesuaikan lebar rules supaya tidak nabrak QR */
  .back-rules {
    width: 55mm;
  }

  /* ── Footer kontak (icon bullet + teks, dompdf-safe) ── */
  .back-footer {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0.5mm;
    /* height: mm; */
    color: #fff;
    z-index: 5;
    padding: 1mm 3mm;
    box-sizing: border-box;
  }

  .back-footer table {
    width: 100%;
    border-collapse: collapse;
  }

  .back-footer td {
    font-size: 3.8pt;
    line-height: 1.3;
    text-align: center;
    vertical-align: middle;
    padding: 0 1mm;
  }

  .kontak-icon {
    display: inline-block;
    width: 2.2mm;
    height: 2.2mm;
    border-radius: 50%;
    background: #d4af37;
    margin-right: 0.8mm;
    vertical-align: middle;
  }
</style>

<div>
  @foreach ($siswaList as $siswa)
    @php
      $rombel = $siswa->rombels->first();
      $fotoPath = $siswa->photo ? storage_path('app/public/' . $siswa->photo) : null;
      $ada_foto = $fotoPath && file_exists($fotoPath);
    @endphp

    {{-- ============ DEPAN ============ --}}
    <div class="kartu">
      <div class="school-head">
        <div class="school-head-inner">
          @if ($logoPath)
            <img src="{{ $logoPath }}" class="logo">
          @else
            <div class="logo-placeholder"></div>
          @endif
          <div class="school-title">
            <div class="main">KARTU PELAJAR</div>
            <div class="school">{{ $sekolahGlobal['nama_sekolah'] }}</div>
            <div class="npsn">NPSN: {{ $sekolahGlobal['npsn'] ?? '-' }}</div>
          </div>
        </div>
      </div>

      <div class="student-content">
        <table>
          <tr>
            <td class="td-photo">
              @if ($ada_foto)
                <img src="{{ $fotoPath }}" class="photo">
              @else
                <div class="photo-kosong"></div>
              @endif

              @if ($siswa->barcode_base64)
                <img src="{{ $siswa->barcode_base64 }}" class="barcode-img">
              @endif
            </td>
            <td class="details">
              <div class="nama-siswa">{{ $siswa->nama_siswa }}</div>
              <table class="detail-table">
                <tr>
                  <td class="dt-label">NIS/NISN</td>
                  <td class="dt-colon">:</td>
                  <td class="dt-value">{{ $siswa->nis_nisn_label }}</td>
                </tr>
                <tr>
                  <td class="dt-label">TTL</td>
                  <td class="dt-colon">:</td>
                  <td class="dt-value">{{ $siswa->ttl_label }}</td>
                </tr>
                <tr>
                  <td class="dt-label">Jns Kelamin</td>
                  <td class="dt-colon">:</td>
                  <td class="dt-value">{{ $siswa->jk_label }}</td>
                </tr>
                <tr>
                  <td class="dt-label">Agama</td>
                  <td class="dt-colon">:</td>
                  <td class="dt-value">{{ $siswa->agama ?? '-' }}</td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </div>

      @if ($stempelPath)
        <div class="stempel-wrap">
          <img src="{{ $stempelPath }}" class="stempel-img">
        </div>
      @endif

      <div class="signature">
        <small>Kepala Sekolah</small>
        @if ($ttdKsPath)
          <img src="{{ $ttdKsPath }}" class="ttd-img">
        @else
          <div class="ttd-space"></div>
        @endif
        <strong>{{ $sekolahGlobal['kepala_sekolah'] }}</strong>
        <small>NIP. {{ $sekolahGlobal['nip_kepsek'] }}</small>
      </div>
    </div>

    {{-- ============ BELAKANG ============ --}}
    @if ($sisi == 2)
      <div class="kartu back">
        <div class="back-header">
          <div class="judul">{{ $sekolahGlobal['nama_sekolah'] }}</div>
          <div class="sub">NPSN: {{ $sekolahGlobal['npsn'] ?? '-' }}</div>
          <div class="alamat">{{ $sekolahGlobal['alamat'] }}</div>

        </div>

        <div class="back-qr">
          <img src="{{ $siswa->qrcode_base64 }}">
        </div>

        <div class="back-rules">
          <div class="judul">Ketentuan &amp; Tata Tertib</div>
          <table>
            <tr>
              <td class="rule-no">1.</td>
              <td class="rule-text">Kartu ini adalah identitas resmi siswa yang wajib dibawa selama berada di lingkungan
                sekolah.</td>
            </tr>
            <tr>
              <td class="rule-no">2.</td>
              <td class="rule-text">Kartu ini tidak dapat dipindahtangankan kepada orang lain.</td>
            </tr>
            <tr>
              <td class="rule-no">3.</td>
              <td class="rule-text">Berlaku selama yang bersangkutan masih terdaftar sebagai siswa aktif.</td>
            </tr>
            <tr>
              <td class="rule-no">4.</td>
              <td class="rule-text">Kehilangan kartu wajib segera dilaporkan kepada pihak sekolah.</td>
            </tr>
          </table>
        </div>

        <div class="back-footer">
          <table>
            <tr>
              <td><span class="kontak-icon"></span>{{ $sekolahGlobal['no_telp'] }}</td>
              <td><span class="kontak-icon"></span>{{ $sekolahGlobal['email'] }}</td>
              <td><span class="kontak-icon"></span>{{ $sekolahGlobal['website'] }}</td>
            </tr>
          </table>
        </div>
      </div>
    @endif
  @endforeach
</div>
