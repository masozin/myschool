<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Kwitansi SPJ BOS</title>
  <style>
    @page {
      margin: 18mm 16mm;
    }

    body {
      font-family: 'DejaVu Sans', sans-serif;
      font-size: 11pt;
      color: #000;
    }

    .kwitansi {
      page-break-after: always;
    }

    .kwitansi.terakhir {
      page-break-after: auto;
    }

    table.frame {
      width: 100%;
      border: 1.5px solid #000;
      border-collapse: collapse;
    }

    table.frame>tr>td,
    table.frame>tbody>tr>td {
      padding: 10px 12px;
      vertical-align: top;
    }

    .judul {
      font-size: 16pt;
      font-weight: bold;
      text-align: center;
      letter-spacing: 2px;
    }

    .info {
      font-size: 10pt;
      width: 33%;
    }

    .info-kanan {
      text-align: right;
    }

    table.isian {
      width: 100%;
      border-collapse: collapse;
      margin-top: 6px;
    }

    table.isian td {
      padding: 3px 2px;
      vertical-align: top;
    }

    table.isian td.label {
      width: 150px;
    }

    table.isian td.titik-dua {
      width: 12px;
    }

    table.rincian {
      width: 100%;
      border-collapse: collapse;
      margin: 8px 0 4px 0;
      font-size: 10pt;
    }

    table.rincian th,
    table.rincian td {
      border: 1px solid #000;
      padding: 4px 6px;
      text-align: left;
    }

    table.rincian th {
      text-align: center;
    }

    table.rincian td.angka,
    table.rincian th.angka {
      text-align: right;
    }

    table.rincian td.tengah {
      text-align: center;
    }

    .baris-bawah {
      width: 100%;
      border-collapse: collapse;
      margin-top: 12px;
    }

    .baris-bawah td {
      vertical-align: top;
    }

    .kotak-nominal {
      display: inline-block;
      border: 1.5px solid #000;
      padding: 8px 14px;
      font-size: 13pt;
      font-weight: bold;
    }

    .diterima {
      text-align: right;
      font-size: 10.5pt;
    }

    table.ttd {
      width: 100%;
      border-collapse: collapse;
      margin-top: 22px;
      font-size: 10.5pt;
    }

    table.ttd td {
      width: 33%;
      text-align: center;
      vertical-align: top;
      padding: 2px 6px;
    }

    .ruang-ttd td {
      height: 64px;
    }

    .nama-ttd {
      text-decoration: underline;
      font-weight: bold;
    }
  </style>
</head>

<body>
  @php
    $namaSekolah = $sekolah->detailTerbaru->nama_sekolah ?? 'Sekolah #' . $sekolah->id;
    $rp = fn($v) => number_format((float) $v, 0, ',', '.');
  @endphp

  @foreach ($daftarKwitansi as $k)
    <div class="kwitansi{{ $loop->last ? ' terakhir' : '' }}">
      <table class="frame">
        <tr>
          <td class="info">
            No. {{ $k['nomor'] }}<br>
            No. Bukti : {{ $k['no_bukti'] ?? '-' }}
          </td>
          <td class="judul">KWITANSI</td>
          <td class="info info-kanan">
            Kode Rekening<br>
            {{ $k['kode_rekening'] ?? '-' }}
          </td>
        </tr>
        <tr>
          <td colspan="3">
            <table class="isian">
              <tr>
                <td class="label">Sudah diterima dari</td>
                <td class="titik-dua">:</td>
                <td>Bendahara BOS {{ $namaSekolah }}</td>
              </tr>
              <tr>
                <td class="label">Sebanyak</td>
                <td class="titik-dua">:</td>
                <td><i>{{ $k['terbilang'] }}</i></td>
              </tr>
              <tr>
                <td class="label">Untuk Pembayaran</td>
                <td class="titik-dua">:</td>
                <td>{{ $k['uraian'] }}</td>
              </tr>
            </table>

            @if (count($k['rincian']) > 1)
              <table class="rincian">
                <thead>
                  <tr>
                    <th style="width: 30px;">No</th>
                    <th>Uraian</th>
                    <th style="width: 110px;">Kode Rekening</th>
                    <th style="width: 50px;">Vol</th>
                    <th class="angka" style="width: 120px;">Jumlah (Rp)</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($k['rincian'] as $r)
                    <tr>
                      <td class="tengah">{{ $loop->iteration }}</td>
                      <td>{{ $r['uraian'] }}</td>
                      <td class="tengah">{{ $r['kode_rekening'] ?? '-' }}</td>
                      <td class="tengah">{{ $r['volume'] ?? '-' }}</td>
                      <td class="angka">{{ $rp($r['saldo']) }}</td>
                    </tr>
                  @endforeach
                  <tr>
                    <td colspan="4" style="text-align: right;"><strong>Total</strong></td>
                    <td class="angka"><strong>{{ $rp($k['total']) }}</strong></td>
                  </tr>
                </tbody>
              </table>
            @endif

            <table class="baris-bawah">
              <tr>
                <td><span class="kotak-nominal">Rp {{ $rp($k['total']) }},-</span></td>
                <td class="diterima">
                  Uang tersebut telah diterima<br>
                  tanggal : {{ $k['tanggal'] ?? '-' }}
                </td>
              </tr>
            </table>

            <table class="ttd">
              <tr>
                <td>Menyetujui<br>Kepala Sekolah,</td>
                <td>Lunas dibayar oleh<br>Bendahara,</td>
                <td>Penerima,</td>
              </tr>
              <tr class="ruang-ttd">
                <td></td>
                <td></td>
                <td></td>
              </tr>
              <tr>
                <td>
                  @if ($k['ttd']['kepala']['nama'])
                    <span class="nama-ttd">{{ $k['ttd']['kepala']['nama'] }}</span>
                    @if ($k['ttd']['kepala']['nip'])
                      <br>NIP. {{ $k['ttd']['kepala']['nip'] }}
                    @endif
                  @else
                    ...................................
                  @endif
                </td>
                <td>
                  @if ($k['ttd']['bendahara']['nama'])
                    <span class="nama-ttd">{{ $k['ttd']['bendahara']['nama'] }}</span>
                    @if ($k['ttd']['bendahara']['nip'])
                      <br>NIP. {{ $k['ttd']['bendahara']['nip'] }}
                    @endif
                  @else
                    ...................................
                  @endif
                </td>
                <td>{{ $k['penerima'] ?? '...................................' }}</td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
    </div>
  @endforeach
</body>

</html>
