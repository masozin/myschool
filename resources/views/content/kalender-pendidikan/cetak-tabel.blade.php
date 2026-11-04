<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Kalender Pendidikan {{ $tahunPelajaran }}</title>
  <style>
    @page {
      margin-top: 1cm;
      margin-bottom: 0.5cm;
      margin-left: 2cm;
      margin-right: 2cm;
    }

    body {
      font-family: "DejaVu Sans", sans-serif;
      font-size: 9px;
      color: #212529;
      margin: 0;
      padding: 0;
    }

    .halaman {
      page-break-after: always;
    }

    .halaman:last-child {
      page-break-after: auto;
    }

    /* ================= KOP SURAT (HALAMAN 1) ================= */
    .kop-surat {
      margin-bottom: 8px;
      text-align: center;
    }

    .kop-gambar {
      width: 100%;
      max-height: 32mm;
      object-fit: contain;
    }

    .kop-placeholder {
      width: 100%;
      height: 28mm;
      border: 1px dashed #6c757d;
      color: #6c757d;
      font-size: 10px;
      font-weight: bold;
      text-align: center;
      line-height: 14mm;
      letter-spacing: 1px;
    }

    .kop-garis {
      border-top: 2px solid #000;
      border-bottom: 0.5px solid #000;
      height: 2px;
      margin-top: 4px;
      margin-bottom: 8px;
    }

    /* ================= JUDUL KALENDER ================= */
    .judul-kalender {
      text-align: center;
      margin-bottom: 8px;
    }

    .judul-kalender h1 {
      font-size: 13px;
      margin: 0 0 2px;
      text-transform: uppercase;
    }

    .judul-kalender h2 {
      font-size: 10px;
      margin: 0;
      font-weight: normal;
      color: #444;
    }

    /* ================= TABEL KALENDER ================= */
    table.tabel-kalender {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    table.tabel-kalender th,
    table.tabel-kalender td {
      border: 0.5px solid #adb5bd;
      padding: 2px 1px;
      vertical-align: middle;
      text-align: center;
      box-sizing: border-box;
      overflow: hidden;
      word-wrap: break-word;
    }

    table.tabel-kalender thead th {
      font-size: 9px;
      font-weight: bold;
      background-color: #f1f3f5;
      padding: 3px 1px;
    }

    /* Baris pengunci lebar kolom */
    tr.baris-pengunci-lebar,
    tr.baris-pengunci-lebar th {
      height: 0 !important;
      line-height: 0 !important;
      font-size: 0 !important;
      padding: 0 !important;
      margin: 0 !important;
      border: none !important;
    }

    .sel-bulan {
      font-weight: bold;
      text-align: center;
      line-height: 1.2;
      font-size: 9px;
    }

    .sel-tgl-keg {
      text-align: center;
      font-size: 9px;
    }

    .sel-keterangan {
      text-align: left !important;
      padding-left: 5px !important;
      padding-right: 3px !important;
      font-size: 9px;
    }

    .baris-jumlah td {
      font-weight: bold;
      background-color: #f8f9fa;
    }

    .baris-rekap td {
      font-weight: bold;
      background-color: #e9ecef;
      font-size: 9px;
    }

    .baris-rekap .label {
      text-align: right;
      padding-right: 6px;
    }

    .teks-libur {
      color: #dc3545;
      font-weight: bold;
    }

    /* ================= TANDA TANGAN (HALAMAN 2) ================= */
    .area-ttd {
      margin-top: 14px;
      page-break-inside: avoid;
    }

    .tabel-ttd {
      width: 100%;
      border-collapse: collapse;
      border: none;
    }

    .tabel-ttd td {
      border: none !important;
      padding: 0;
      vertical-align: top;
    }

    .ttd-tgl,
    .ttd-jabatan,
    .ttd-nama,
    .ttd-nip {
      margin: 1px 0;
      font-size: 9px;
      line-height: 1.3;
    }

    .ttd-space {
      height: 48px;
    }
  </style>
</head>

<body>

  @foreach ([['Ganjil', $bulanGanjil, $totalGanjil], ['Genap', $bulanGenap, $totalGenap]] as [$labelSemester, $daftarBulan, $totalSemester])
    <div class="halaman">

      {{-- 1. Kop Surat (Khusus Halaman Pertama) --}}
      @if ($loop->first)
        <div class="kop-surat">
          @if ($kopUrl && file_exists($kopUrl))
            <img src="{{ $kopUrl }}" class="kop-gambar">
          @else
            <div class="kop-placeholder">KOP SEKOLAH</div>
            <div class="kop-garis"></div>
          @endif
        </div>
      @endif

      {{-- 2. Judul Dokumen --}}
      <div class="judul-kalender">
        <h1>Kalender Pendidikan</h1>
        <h2>Tahun Pelajaran {{ $tahunPelajaran }} &mdash; Semester {{ $labelSemester }}</h2>
      </div>

      {{-- 3. Tabel Kalender --}}
      <table class="tabel-kalender">
        <thead>
          {{-- Baris Pengunci Lebar Kolom (Total Pas 100%) --}}
          <tr class="baris-pengunci-lebar">
            <th style="width: 10.0%;"></th> {{-- 1. Bulan --}}
            <th style="width: 3.5%;"></th> {{-- 2. M --}}
            <th style="width: 3.5%;"></th> {{-- 3. S --}}
            <th style="width: 3.5%;"></th> {{-- 4. S --}}
            <th style="width: 3.5%;"></th> {{-- 5. R --}}
            <th style="width: 3.5%;"></th> {{-- 6. K --}}
            <th style="width: 3.5%;"></th> {{-- 7. J --}}
            <th style="width: 3.5%;"></th> {{-- 8. S --}}
            <th style="width: 6.2%;"></th> {{-- 9. LM --}}
            <th style="width: 6.2%;"></th> {{-- 10. Libur --}}
            <th style="width: 6.2%;"></th> {{-- 11. Efektif --}}
            <th style="width: 6.2%;"></th> {{-- 12. Jumlah --}}
            <th style="width: 6.2%;"></th> {{-- 13. Tgl --}}
            <th style="width: auto;"></th> {{-- 14. Keterangan --}}
          </tr>

          {{-- Header Tingkat 1 --}}
          <tr>
            <th rowspan="2" style="width: 10.0%;">Bulan</th>
            <th colspan="7" style="width: 22.4%;">Hari / Tanggal</th>
            <th colspan="4" style="width: 21.4%;">Jumlah Hari</th>
            <th colspan="2" style="width: auto;">Uraian Kegiatan</th>
          </tr>

          {{-- Header Tingkat 2 --}}
          <tr>
            <th style="width: 3.5%;">M</th>
            <th style="width: 3.5%;">S</th>
            <th style="width: 3.5%;">S</th>
            <th style="width: 3.5%;">R</th>
            <th style="width: 3.5%;">K</th>
            <th style="width: 3.5%;">J</th>
            <th style="width: 3.5%;">S</th>
            <th style="width: 6.2%;">LM</th>
            <th style="width: 6.2%;">Libur</th>
            <th style="width: 6.2%;">Efektif</th>
            <th style="width: 6.2%;">Jumlah</th>
            <th style="width: 6.2%;">Tgl</th>
            <th style="width: auto;">Keterangan</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($daftarBulan as $bulan)
            @php
              $labelBulanNormal = strtoupper($bulan['label']);
            @endphp

            @for ($i = 0; $i < $bulan['baris_total']; $i++)
              <tr>
                @if ($i === 0)
                  <td rowspan="{{ $bulan['baris_total'] }}" class="sel-bulan">
                    {{ $labelBulanNormal }}<br>{{ $bulan['tahun'] }}
                  </td>
                @endif

                @if ($i < 6)
                  {{-- Baris Kalender Minggu ke-(i+1) --}}
                  @foreach ($bulan['minggu'][$i]['sel'] as $sel)
                    @if (is_null($sel))
                      <td></td>
                    @else
                      @php
                        $warnaBg =
                            $sel['jenis'] && isset($warnaJenis[$sel['jenis']])
                                ? $warnaJenis[$sel['jenis']]['bg']
                                : null;
                      @endphp
                      <td style="@if ($warnaBg) background-color: {{ $warnaBg }}; @endif"
                        class="{{ $sel['is_libur_mingguan'] ? 'teks-libur' : '' }}">
                        {{ $sel['angka'] }}
                      </td>
                    @endif
                  @endforeach
                  <td>{{ $bulan['minggu'][$i]['lm'] }}</td>
                  <td>{{ $bulan['minggu'][$i]['libur'] }}</td>
                  <td>{{ $bulan['minggu'][$i]['efektif'] }}</td>
                  <td>{{ $bulan['minggu'][$i]['jumlah'] }}</td>
                @elseif ($i === 6)
                  {{-- Baris Rekap Subtotal Bulan --}}
                  <td colspan="7"
                    style="text-align:right; font-weight:bold; padding-right:6px; background-color:#f8f9fa;">Jumlah</td>
                  <td style="font-weight:bold; background-color:#f8f9fa;">{{ $bulan['total']['lm'] }}</td>
                  <td style="font-weight:bold; background-color:#f8f9fa;">{{ $bulan['total']['libur'] }}</td>
                  <td style="font-weight:bold; background-color:#f8f9fa;">{{ $bulan['total']['efektif'] }}</td>
                  <td style="font-weight:bold; background-color:#f8f9fa;">{{ $bulan['total']['jumlah'] }}</td>
                @else
                  <td colspan="11"></td>
                @endif

                {{-- Kolom Uraian Kegiatan --}}
                @php $keg = $bulan['kegiatan'][$i] ?? null; @endphp
                @if ($keg)
                  @php $warnaBgKeg = isset($warnaJenis[$keg['jenis']]) ? $warnaJenis[$keg['jenis']]['bg'] : null; @endphp
                  <td class="sel-tgl-keg"
                    style="@if ($warnaBgKeg) background-color: {{ $warnaBgKeg }}; @endif">
                    <span class="{{ $keg['is_libur'] ?? false ? 'teks-libur' : '' }}">{{ $keg['tgl'] }}</span>
                  </td>
                  <td class="sel-keterangan">{{ $keg['keterangan'] }}</td>
                @else
                  <td class="sel-tgl-keg"></td>
                  <td class="sel-keterangan" style="text-align: left; padding-left: 5px"></td>
                @endif
              </tr>
            @endfor
          @endforeach

          {{-- Baris Rekap Semester --}}
          <tr class="baris-rekap">
            <td colspan="8" class="label">Jumlah Hari Semester {{ $labelSemester }}</td>
            <td>{{ $totalSemester['lm'] }}</td>
            <td>{{ $totalSemester['libur'] }}</td>
            <td>{{ $totalSemester['efektif'] }}</td>
            <td>{{ $totalSemester['jumlah'] }}</td>
            <td colspan="2"></td>
          </tr>
          <tr class="baris-rekap">
            <td colspan="8" class="label">Persentase</td>
            <td>
              {{ $totalSemester['jumlah'] > 0 ? number_format(($totalSemester['lm'] / $totalSemester['jumlah']) * 100, 1) : 0 }}%
            </td>
            <td>
              {{ $totalSemester['jumlah'] > 0 ? number_format(($totalSemester['libur'] / $totalSemester['jumlah']) * 100, 1) : 0 }}%
            </td>
            <td>
              {{ $totalSemester['jumlah'] > 0 ? number_format(($totalSemester['efektif'] / $totalSemester['jumlah']) * 100, 1) : 0 }}%
            </td>
            <td>100%</td>
            <td colspan="2"></td>
          </tr>
        </tbody>
      </table>

      {{-- 4. Tanda Tangan (Khusus Halaman Kedua) --}}
      @if ($loop->last)
        <div class="area-ttd">
          <table class="tabel-ttd">
            <tr>
              <td style="width: 60%;"></td>
              <td style="width: 40%; text-align: center;">
                <p class="ttd-tgl">{{ $tempatTtd ?: '…………………………' }}, {{ $tanggalTtd }}</p>
                <p class="ttd-jabatan">Kepala Sekolah / Madrasah,</p>
                <div class="ttd-space"></div>
                <p class="ttd-nama"><strong><u>{{ $kepalaSekolah }}</u></strong></p>
                <p class="ttd-nip">NIP. {{ $nipKepsek }}</p>
              </td>
            </tr>
          </table>
        </div>
      @endif

    </div>
  @endforeach

</body>

</html>
