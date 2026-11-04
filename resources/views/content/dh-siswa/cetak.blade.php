<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Daftar Hadir {{ $namaRombel }} - {{ $namaBulan }} {{ $tahun }}</title>
  <style>
    @page {
      margin: 8mm 8mm 8mm 17mm;
      /* top right bottom left */
    }

    body {
      font-family: "DejaVu Sans", sans-serif;
      font-size: 8pt;
      color: #212529;
      margin: 0;
    }

    .judul {
      text-align: center;
      margin-bottom: 6px;
    }

    .judul h1 {
      font-size: 13pt;
      margin: 0 0 2px;
      text-transform: uppercase;
    }

    .judul h2 {
      font-size: 10pt;
      margin: 0;
      font-weight: normal;
    }

    table.info-kelas {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 6px;
    }

    table.info-kelas td {
      border: none;
      padding: 0;
      font-size: 9pt;
    }

    table.tabel-hadir {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    table.tabel-hadir th,
    table.tabel-hadir td {
      border: 0.5px solid #6c757d;
      padding: 1px;
      text-align: center;
      vertical-align: middle;
      overflow: hidden;
      box-sizing: border-box;
      font-size: 8pt;
    }

    table.tabel-hadir thead th {
      font-weight: bold;
      background-color: #f1f3f5;
      padding: 2px 1px;
    }

    /* Baris pengunci lebar kolom — dompdf table-layout:fixed tidak selalu
       menghormati <col style="width">, jadi lebar dipaksa lewat baris <th>
       tak-terlihat ini (sama seperti trik di cetak-tabel.blade.php). */
    tr.baris-pengunci-lebar,
    tr.baris-pengunci-lebar th {
      height: 0 !important;
      line-height: 0 !important;
      font-size: 0 !important;
      padding: 0 !important;
      margin: 0 !important;
      border: none !important;
    }

    table.tabel-hadir td.nama {
      text-align: left;
      padding-left: 3px;
    }

    .baris-jumlah td {
      font-weight: bold;
      background-color: #f8f9fa;
    }

    .baris-jumlah .label {
      text-align: right;
      padding-right: 4px;
    }

    /* ================= BAGIAN BAWAH ================= */
    table.bawah {
      width: 100%;
      border-collapse: collapse;
      margin-top: 8px;
    }

    table.bawah>tbody>tr>td {
      border: none;
      vertical-align: top;
      padding: 0 4px;
    }

    .kol-kegiatan {
      width: 38%;
    }

    .kol-sia-rekap {
      width: 16%;
    }

    .kol-ttd {
      width: 23%;
    }

    .judul-bawah {
      font-size: 8.5pt;
      font-weight: bold;
      margin-bottom: 3px;
    }

    .daftar-item {
      font-size: 8pt;
      margin-top: 2px;
    }

    .teks-libur {
      color: #dc3545;
    }

    table.tabel-rekap-sia {
      width: 100%;
      border-collapse: collapse;
    }

    table.tabel-rekap-sia td {
      border: 0.5px solid #6c757d;
      padding: 3px;
      font-size: 8pt;
    }

    table.tabel-rekap-sia td.label {
      width: 40%;
      font-weight: bold;
    }

    .ttd-blok {
      text-align: center;
      font-size: 8.5pt;
      line-height: 1.4;
    }

    .ttd-space {
      height: 32px;
    }
  </style>
</head>

<body>

  @php
    // Lebar kolom tetap (%) — total 8 kolom non-tanggal = 50%, sisanya (50%)
    // dibagi rata ke semua kolom tanggal lewat $lebarKolomTanggal dari controller.
    $lebarNo = 3;
    $lebarNama = 20;
    $lebarNis = 6;
    $lebarNisn = 7;
    $lebarSia = 3;
    $lebarKeh = 5;
  @endphp

  <div class="judul">
    <h1>Daftar Hadir Murid {{ $namaSekolah }}</h1>
    <h2><strong> Pelajaran {{ $tahunPelajaran }}</strong></h2>
  </div>

  <table class="info-kelas">
    <tr>
      <td style="width:50%;">Kelas &nbsp; : &nbsp; <strong>{{ $namaRombel }}</strong></td>
      <td style="width:50%; text-align:right;">Bulan &nbsp; : &nbsp; <strong>{{ $namaBulan }}
          {{ $tahun }}</strong></td>
    </tr>
  </table>

  <table class="tabel-hadir">
    <thead>
      {{-- Baris pengunci lebar kolom (tak terlihat) --}}
      <tr class="baris-pengunci-lebar">
        <th style="width: {{ $lebarNo }}%;"></th>
        <th style="width: {{ $lebarNama }}%;"></th>
        <th style="width: {{ $lebarNis }}%;"></th>
        <th style="width: {{ $lebarNisn }}%;"></th>
        @foreach ($kolomTanggal as $k)
          <th style="width: {{ $lebarKolomTanggal }}%;"></th>
        @endforeach
        <th style="width: {{ $lebarSia }}%;"></th>
        <th style="width: {{ $lebarSia }}%;"></th>
        <th style="width: {{ $lebarSia }}%;"></th>
        <th style="width: {{ $lebarKeh }}%;"></th>
      </tr>

      {{-- Header baris 1: No/Nama/NIS/NISN/S/I/A/Kehadiran (rowspan 2) +
           angka tanggal per kolom (bukan label "Tanggal" yang di-marge) --}}
      <tr>
        <th rowspan="2" style="width: {{ $lebarNo }}%;">No</th>
        <th rowspan="2" style="width: {{ $lebarNama }}%;">Nama</th>
        <th rowspan="2" style="width: {{ $lebarNis }}%;">NIS</th>
        <th rowspan="2" style="width: {{ $lebarNisn }}%;">NISN</th>
        @foreach ($kolomTanggal as $k)
          <th
            style="width: {{ $lebarKolomTanggal }}%; @if ($k['warna_bg']) background-color: {{ $k['warna_bg'] }}; @endif  font-size: 6pt;">
            {{ $k['angka'] }}</th>
        @endforeach
        <th rowspan="2" style="width: {{ $lebarSia }}%;">S</th>
        <th rowspan="2" style="width: {{ $lebarSia }}%;">I</th>
        <th rowspan="2" style="width: {{ $lebarSia }}%;">A</th>
        <th rowspan="2" style="width: {{ $lebarKeh }}%;  font-size: 7pt;">Kehadiran<br>(%)</th>
      </tr>

      {{-- Header baris 2: huruf hari per kolom tanggal --}}
      <tr>
        @foreach ($kolomTanggal as $k)
          <th
            style="@if ($k['warna_bg']) background-color: {{ $k['warna_bg'] }}; @endif  font-size: 6pt;">
            {{ $k['huruf'] }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @forelse ($daftarSiswa as $i => $siswa)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td class="nama">{{ $siswa->nama_siswa }}</td>
          <td>{{ $siswa->registrasiTerbaru->nis ?? '-' }}</td>
          <td>{{ $siswa->nisn ?? '-' }}</td>
          @foreach ($kolomTanggal as $k)
            <td style="@if ($k['warna_bg']) background-color: {{ $k['warna_bg'] }}; @endif"></td>
          @endforeach
          <td></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
      @empty
        <tr>
          <td colspan="{{ 4 + count($kolomTanggal) + 4 }}">Belum ada siswa di kelas ini.</td>
        </tr>
      @endforelse

      {{-- 3 baris kosong cadangan untuk siswa tambahan --}}
      @for ($cad = 1; $cad <= 3; $cad++)
        <tr>
          <td>{{ count($daftarSiswa) + $cad }}</td>
          <td class="nama">&nbsp;</td>
          <td></td>
          <td></td>
          @foreach ($kolomTanggal as $k)
            <td style="@if ($k['warna_bg']) background-color: {{ $k['warna_bg'] }}; @endif"></td>
          @endforeach
          <td></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
      @endfor

      <tr class="baris-jumlah">
        <td colspan="4" class="label">Jumlah</td>
        @foreach ($kolomTanggal as $k)
          <td style="@if ($k['warna_bg']) background-color: {{ $k['warna_bg'] }}; @endif"></td>
        @endforeach
        <td></td>
        <td></td>
        <td></td>
        <td></td>
      </tr>
    </tbody>
  </table>

  <table class="bawah">
    <tr>
      <td class="kol-kegiatan">
        <div class="judul-bawah">Daftar Kegiatan {{ $namaBulan }} {{ $tahun }}</div>
        @forelse ($daftarKegiatan as $keg)
          <div class="daftar-item {{ $keg['is_libur'] ? 'teks-libur' : '' }}">
            <strong>{{ $keg['label'] }}</strong> : {{ $keg['teks'] }}
          </div>
        @empty
          <div class="daftar-item">Tidak ada kegiatan.</div>
        @endforelse
      </td>

      <td class="kol-sia-rekap">
        <table class="tabel-rekap-sia">
          <tr>
            <td class="label">Sakit</td>
            <td></td>
          </tr>
          <tr>
            <td class="label">Izin</td>
            <td></td>
          </tr>
          <tr>
            <td class="label">Alpa</td>
            <td></td>
          </tr>
        </table>
      </td>

      <td class="kol-ttd">
        <div class="ttd-blok">
          <p style="margin:0;">Mengetahui,</p>
          <p style="margin:0;">Kepala Sekolah</p>
          <div class="ttd-space"></div>
          <p style="margin:0;"><strong><u>{{ $kepalaSekolah }}</u></strong></p>
          <p style="margin:0;">NIP. {{ $nipKepsek }}</p>
        </div>
      </td>

      <td class="kol-ttd">
        <div class="ttd-blok">
          <p style="margin:0;">&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;&hellip;, {{ $tanggalTtd }}</p>
          <p style="margin:0;">Wali Kelas,</p>
          <div class="ttd-space"></div>
          <p style="margin:0;"><strong><u>{{ $waliKelas }}</u></strong></p>
          <p style="margin:0;">NIP. {{ $nipWaliKelas }}</p>
        </div>
      </td>
    </tr>
  </table>

</body>

</html>
