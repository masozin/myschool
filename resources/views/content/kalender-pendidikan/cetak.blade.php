<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Kalender Pendidikan {{ $tahunPelajaran }}</title>

  <style>
    @page {
      margin: 10mm 10mm;
    }

    * {
      box-sizing: border-box;
    }

    body {
      font-family: "DejaVu Sans", sans-serif;
      font-size: 8px;
      color: #263238;
      margin: 0;
      background: #ffffff;
    }

    /* =========================================================
       HEADER
    ========================================================= */

    .header {
      text-align: center;
      margin-bottom: 10px;
    }

    .header-label {
      display: inline-block;
      font-size: 7px;
      font-weight: bold;
      color: #607d8b;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 2px;
    }

    .header-title {
      font-size: 16px;
      font-weight: bold;
      color: #173f35;
      margin: 0;
    }

    .header-subtitle {
      font-size: 8px;
      color: #78909c;
      margin-top: 2px;
    }

    .header-line {
      width: 55px;
      height: 2px;
      background: #2e7d64;
      margin: 5px auto 0;
    }


    /* =========================================================
       SEMESTER
    ========================================================= */

    .semester-section {
      margin-top: 12px;
    }

    .semester-header {
      width: 96%;
      margin-bottom: 6px;
      background: #edf5f2;
      border-left: 4px solid #2e7d64;
      padding: 5px 8px;
    }

    .semester-number {
      font-size: 8px;
      font-weight: bold;
      color: #78909c;
      text-transform: uppercase;
      letter-spacing: .5px;
    }

    .semester-title {
      font-size: 11px;
      font-weight: bold;
      color: #1b4338;
      margin-top: 1px;
    }


    /* =========================================================
       GRID BULAN
    ========================================================= */

    table.grid-bulan {
      width: 100%;
      border-collapse: separate;
      border-spacing: 5px 5px;
      table-layout: fixed;
      margin: -5px;
    }

    table.grid-bulan>tbody>tr>td {
      width: 33.33%;
      vertical-align: top;
      padding: 0;
    }


    /* =========================================================
       CARD BULAN
    ========================================================= */

    .kotak-bulan {
      border: 1px solid #dce5e1;
      background: #ffffff;
      padding: 5px;
      height: 45mm;
    }

    .bulan-header {
      background: #f4f8f6;
      border-bottom: 1px solid #dce5e1;
      padding: 4px 5px;
      margin-bottom: 4px;
    }

    .judul-bulan {
      text-align: left;
      font-weight: bold;
      font-size: 8.5px;
      color: #245b4d;
      text-transform: uppercase;
      letter-spacing: .2px;
    }


    /* =========================================================
       MINI CALENDAR
    ========================================================= */

    table.mini-kalender {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 4px;
      table-layout: fixed;
    }

    table.mini-kalender th {
      font-size: 5.8px;
      color: #78909c;
      font-weight: bold;
      padding: 1.5px 0;
      text-align: center;
    }

    table.mini-kalender th:first-child {
      color: #c62828;
    }

    table.mini-kalender td {
      text-align: center;
      font-size: 6.7px;
      padding: 1.4px 0;
      width: 14.28%;
      height: 12px;
      color: #37474f;
    }

    /* Minggu */
    table.mini-kalender tr td:first-child {
      color: #c62828;
    }

    /* Tanggal dengan kegiatan */
    table.mini-kalender td[style*="background"] {
      border-radius: 2px;
      color: #263238;
    }


    /* =========================================================
       KEGIATAN
    ========================================================= */

    .kegiatan-container {
      border-top: 1px solid #edf1ef;
      padding-top: 3px;
    }

    .daftar-item {
      position: relative;
      font-size: 6.1px;
      line-height: 1.2;
      padding: 1.5px 2px 1.5px 10px;
      color: #455a64;
      margin-bottom: 1px;
    }

    .daftar-item .dot {
      position: absolute;
      left: 1px;
      top: 4px;
      display: block;
      width: 5px;
      height: 5px;
      border-radius: 50%;
    }

    .daftar-item strong {
      color: #263238;
      font-weight: bold;
    }

    .teks-libur {
      color: #c62828;
    }

    .teks-libur strong {
      color: #b71c1c;
    }

    .empty-event {
      font-size: 6.2px;
      color: #b0bec5;
      font-style: italic;
      padding: 3px 2px;
    }


    /* =========================================================
       LEGEND
    ========================================================= */

    .legend {
      margin-top: 9px;
      padding: 6px 8px;
      border-top: 1px solid #dce5e1;
      background: #f8faf9;
      text-align: center;
    }

    .legend-judul {
      font-size: 7.5px;
      font-weight: bold;
      color: #455a64;
      margin-bottom: 4px;
      text-transform: uppercase;
      letter-spacing: .4px;
    }

    .legend-item {
      display: inline-block;
      margin: 0 8px 2px;
      font-size: 6.8px;
      color: #546e7a;
    }

    .legend-item .dot {
      display: inline-block;
      width: 6px;
      height: 6px;
      border-radius: 50%;
      margin-right: 3px;
      vertical-align: middle;
    }


    /* =========================================================
       FOOTER
    ========================================================= */

    .footer {
      text-align: center;
      font-size: 6px;
      color: #90a4ae;
      margin-top: 5px;
    }
  </style>
</head>

<body>

  {{-- =========================================================
       HEADER
  ========================================================== --}}

  <div class="header">

    <div class="header-label">
      Dokumen Akademik
    </div>

    <div class="header-title">
      Kalender Pendidikan
    </div>

    <div class="header-subtitle">
      Tahun Pelajaran {{ $tahunPelajaran }}
    </div>

    <div class="header-line"></div>

  </div>


  {{-- =========================================================
       SEMESTER
  ========================================================== --}}

  @foreach ([['Ganjil', $bulanGanjil], ['Genap', $bulanGenap]] as [$labelSemester, $daftarBulan])
    <div class="semester-section">

      <div class="semester-header">

        <div class="semester-number">
          Semester
        </div>

        <div class="semester-title">
          {{ $labelSemester }}
        </div>

      </div>


      <table class="grid-bulan">
        <tbody>

          @foreach (array_chunk($daftarBulan, 3) as $barisBulan)
            <tr>

              @foreach ($barisBulan as $bulan)
                <td>

                  <div class="kotak-bulan">

                    {{-- HEADER BULAN --}}
                    <div class="bulan-header">
                      <div class="judul-bulan">
                        {{ $bulan['label'] }}
                      </div>
                    </div>


                    {{-- MINI CALENDAR --}}
                    <table class="mini-kalender">

                      <thead>
                        <tr>
                          <th>M</th>
                          <th>S</th>
                          <th>S</th>
                          <th>R</th>
                          <th>K</th>
                          <th>J</th>
                          <th>S</th>
                        </tr>
                      </thead>

                      <tbody>

                        @foreach ($bulan['minggu'] as $minggu)
                          <tr>

                            @foreach ($minggu as $sel)
                              @if (is_null($sel))
                                <td></td>
                              @elseif ($sel['jenis'])
                                <td
                                  style="
                                    background-color:
                                    {{ $warnaJenis[$sel['jenis']]['bg'] ?? '#f1f1f1' }};
                                    font-weight: bold;
                                  ">
                                  {{ $sel['angka'] }}
                                </td>
                              @else
                                <td>
                                  {{ $sel['angka'] }}
                                </td>
                              @endif
                            @endforeach

                          </tr>
                        @endforeach

                      </tbody>

                    </table>


                    {{-- KEGIATAN --}}
                    <div class="kegiatan-container">

                      @forelse ($bulan['kegiatan'] as $keg)
                        <div class="daftar-item {{ $keg['is_libur'] ? 'teks-libur' : '' }}">

                          <span class="dot"
                            style="
                              background-color:
                              {{ $warnaJenis[$keg['jenis']]['solid'] ?? '#78909c' }};
                            "></span>

                          <strong>
                            {{ $keg['label'] }}
                          </strong>

                          {{ $keg['teks'] }}

                        </div>

                      @empty

                        <div class="empty-event">
                          Belum ada kegiatan
                        </div>
                      @endforelse

                    </div>

                  </div>

                </td>
              @endforeach


              {{-- Sel kosong --}}
              @for ($i = count($barisBulan); $i < 3; $i++)
                <td></td>
              @endfor

            </tr>
          @endforeach

        </tbody>
      </table>

    </div>
  @endforeach


  {{-- =========================================================
       LEGEND
  ========================================================== --}}

  <div class="legend">

    <div class="legend-judul">
      Keterangan Kegiatan
    </div>

    @foreach ($warnaJenis as $jenis => $warna)
      <span class="legend-item">

        <span class="dot" style="background-color: {{ $warna['solid'] }};"></span>

        {{ $jenis }}

      </span>
    @endforeach

  </div>


  <div class="footer">
    Kalender Pendidikan • Tahun Pelajaran {{ $tahunPelajaran }}
  </div>

</body>

</html>
