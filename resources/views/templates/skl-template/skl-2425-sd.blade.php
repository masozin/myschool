<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>SKL - {{ $skl->nama_cetak }}</title>
  <style>
    /* Reset dasar untuk PDF */
    * {
      box-sizing: border-box;
    }

    @page {
      size: A4 portrait;
      /* Margin: Top 5cm, Right 2.5cm, Bottom 2cm, Left 2.5cm */
      margin: 5cm 3cm 1cm 3cm;
    }

    body {
      font-family: 'Arial Narow', Times, serif;
      font-size: 11pt;
      color: #000;
      line-height: 1.1;
      margin: 0;
      padding: 0;
    }

    /* ── Header ── */
    .header {
      text-align: center;
      margin-bottom: 20px;
    }

    .header h1 {
      font-size: 13pt;
      font-weight: bold;
      margin: 0;
      text-transform: uppercase;
    }

    .header h2 {
      font-size: 13pt;
      font-weight: bold;
      margin: 0;
    }

    .header .nomor {
      font-size: 12pt;
      margin-top: 2px;
    }

    /* ── Teks Narasi ── */
    .pembuka,
    .penutup {
      text-align: justify;
      margin-bottom: 15px;
      line-height: 1.2;
    }

    /* ── Putusan Kelulusan ── */
    .putusan {
      text-align: center;
      margin: 4px 0 6px;
      font-size: 14pt;
      letter-spacing: 1px;
    }

    /* ── Identitas Siswa ── */
    table.identitas {
      margin: 0 0 15px 30px;
      /* Indentasi ke kanan sedikit */
      border-collapse: collapse;
    }

    table.identitas td {
      padding: 1px 4px;
      vertical-align: top;
      line-height: 1.2;
    }

    table.identitas td:first-child {
      width: 250px;
    }

    table.identitas td:nth-child(2) {
      width: 15px;
      text-align: center;
    }

    /* ── Tabel Nilai ── */
    table.nilai {
      width: 100%;
      border-collapse: collapse;
      padding: 15px 30px 5px 30px;
    }

    table.nilai th,
    table.nilai td {
      border: 1px solid #000;
      padding: 4px 8px;
      line-height: 1;
    }

    table.nilai thead th {
      text-align: center;
      background-color: #fff;
    }

    table.nilai td.center {
      text-align: center;
    }

    table.nilai tr.kelompok td {
      font-weight: bold;
    }

    table.nilai tr.rata-rata td {
      font-weight: bold;
    }

    /* ── Area Bawah (QR, Foto, TTD) ── */
    .footer-section {
      width: 100%;
    }

    .footer-table {
      width: 100%;
      border: none;
    }

    .footer-table td {
      border: none;
      vertical-align: middle;
    }

    /* Kotak Foto */
    .foto-box {
      border: 1px solid #202020;
      width: 2.6cm;
      height: 3.4cm;
      margin: 0 auto;
    }

    /* QR Code */
    .qr-code img {
      width: 160px;
      height: 160px;
    }

    /* TTD Area */
    .ttd-container {
      text-align: left;
      padding-left: 10px;
    }

    .nama-pejabat {
      margin-top: 60px;
      /* Ruang tanda tangan */
      font-weight: bold;
      text-decoration: none;
      /* Berdasarkan gambar tidak ada underline */
    }
  </style>
</head>

<body>

  <div class="header">
    <h1>SURAT KETERANGAN LULUS (SKL)</h1>
    <h2>TAHUN PELAJARAN {{ $skl->tahun_pelajaran }}</h2>
    <div class="nomor">Nomor : {{ $skl->nomor_skl }}</div>
  </div>

  @php
    $sekolah = $skl->sekolah->detailPerSemester;
    $namaSekolah = $sekolah->nama_sekolah ?? '';
    $desa = $sekolah->village->name ?? '';
    $konfigurasi = \App\Models\SklKonfigurasi::untukSekolah($skl->sekolah_id, $skl->semester_id);
    $tempatTerbit = $konfigurasi?->tempat_terbit ?: $desa;
    $kepalaSekolah = $sekolah->kepala_sekolah ?? '';
    $nipKs = $sekolah->nip ?? '';
  @endphp

  <p class="pembuka">
    Yang bertanda tangan di bawah ini, Kepala {{ $namaSekolah }}, menerangkan bahwa:
  </p>

  <table class="identitas">
    <tr>
      <td>Nama Siswa</td>
      <td>:</td>
      <td><strong>{{ strtoupper($skl->nama_cetak) }}</strong></td>
    </tr>
    <tr>
      <td>Tempat, Tanggal lahir</td>
      <td>:</td>
      <td>{{ $skl->tempat_lahir_cetak }}, {{ $skl->tgl_lahir_cetak->translatedFormat('d F Y') }}</td>
    </tr>
    <tr>
      <td>Nama Orang Tua</td>
      <td>:</td>
      <td>{{ $skl->nama_ayah_cetak }}</td>
    </tr>
    <tr>
      <td>NIS/NISN</td>
      <td>:</td>
      <td>{{ $skl->nis }} / {{ $skl->nisn_cetak }}</td>
    </tr>
    <tr>
      <td>Nomor Ujian Sekolah</td>
      <td>:</td>
      <td>{{ $skl->nomor_ujian ?? '-' }}</td>
    </tr>
  </table>

  <p class="pembuka">
    Berdasarkan Hasil Rapat Dewan Guru tentang Penetapan Kelulusan dan mengacu kepada Permendikbudristek Nomor 5 Tahun
    2022 tentang Standar Kompetensi Lulusan, Permendikbudristek Nomor 21 Tahun 2022 tentang Standar Penilaian
    Pendidikan, dan Keputusan Kepala Badan Standar, Kurikulum, dan Asesmen Pendidikan Kementerian Pendidikan,
    Kebudayaan, Riset, dan Teknologi Nomor 012.A Tahun 2024 tentang Pedoman Pengelolaan Blangko Ijazah Pendidikan Dasar
    dan Pendidikan Menengah Tahun Pelajaran 2024/2025, dengan ini siswa tersebut di atas ditetapkan:
  </p>

  {{-- Putusan kelulusan. Dokumen lama yang keterangannya belum
       terisi tetapi sudah disetujui tercetak LULUS. --}}
  <h3 class="putusan">{{ $skl->keterangan ?? ($skl->status === 'disetujui' ? 'LULUS' : '') }}</h3>

  <P>dari {{ $namaSekolah }} dengan nilai sebagai berikut:</P>

  <table class="nilai">
    <thead>
      <tr>
        <th width="10%">No</th>
        <th width="65%">Mata Pelajaran<br>({{ $skl->nama_kurikulum ?? 'Kurikulum 2013' }})</th>
        <th width="25%">Nilai Murni<br>Ujian Sekolah</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($skl->nilaiPerKelompok() as $namaKelompok => $mapels)
        <tr class="kelompok">
          <td colspan="3">{{ $namaKelompok }}</td>
        </tr>
        @foreach ($mapels as $index => $item)
          <tr>
            <td class="center">{{ $index + 1 }}</td>
            <td>{{ $item->mapelMapping->masterMapel->nama ?? '-' }}</td>
            <td class="center">{{ number_format($item->nilai_akhir, 2) }}</td>
          </tr>
        @endforeach
      @endforeach
      <tr class="rata-rata">
        <td colspan="2" class="center">Nilai Rata-rata</td>
        <td class="center">{{ number_format($skl->nilaiRataRata(), 2) }}</td>
      </tr>
    </tbody>
  </table>

  <p class="penutup">
    Demikian surat keterangan ini dibuat, untuk dapat dipergunakan sebagaimana mestinya.
  </p>

  <div style="padding: 10px"></div>

  <table class="footer-table">
    <tr>
      <!-- QR Code -->
      <td width="30%" class="center">
        @if (!empty($qrCode))
          <div class="qr-code">
            <img src="{{ $qrCode }}" alt="QR">
          </div>
        @endif
      </td>

      <!-- Foto 3x4 -->
      <td width="30%" class="center">
        <div class="foto-box"></div>
      </td>

      <!-- Tanda Tangan -->
      <td width="40%">
        <div class="ttd-container">
          {{ $tempatTerbit }}, {{ $skl->tanggal_terbit ? $skl->tanggal_terbit->translatedFormat('d F Y') : '' }}<br>
          Kepala Sekolah,<br><br>

          <div class="nama-pejabat">{{ $kepalaSekolah }}</div>
          NIP. {{ $nipKs }}
        </div>
      </td>
    </tr>
  </table>

  @if (!empty($watermark))
    <div style="position: fixed; top: 40%; left: -10%; width: 120%; text-align: center; transform: rotate(-30deg); font-size: 200px; font-weight: bold; letter-spacing: 24px; color: rgba(200, 0, 0, 0.18); z-index: 9999;">{{ $watermark }}</div>
  @endif
</body>

</html>
