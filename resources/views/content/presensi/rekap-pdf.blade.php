<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Rekap Presensi — {{ $labelPeriode }}</title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 10px;
      color: #222;
    }

    h1 {
      font-size: 16px;
      margin: 0 0 2px;
    }

    .sub {
      color: #555;
      margin-bottom: 12px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 16px;
    }

    th,
    td {
      border: 1px solid #bbb;
      padding: 4px 6px;
    }

    th {
      background: #eef1f6;
      text-align: left;
    }

    td.c,
    th.c {
      text-align: center;
    }

    tfoot td {
      font-weight: bold;
      background: #f6f7f9;
    }

    h2 {
      font-size: 12px;
      margin: 14px 0 6px;
    }

    .catatan {
      color: #555;
      font-size: 9px;
    }
  </style>
</head>

<body>
  <h1>Rekap Presensi</h1>
  <div class="sub">
    Periode: {{ $labelPeriode }} • Hari efektif: {{ $hariEfektif }} hari •
    {{ $total['jumlah_siswa'] }} siswa • Kehadiran keseluruhan: {{ $total['persen'] }}%
  </div>

  <h2>Per Kelas</h2>
  <table>
    <thead>
      <tr>
        <th>Kelas</th>
        <th class="c">Siswa</th>
        <th class="c">Hadir</th>
        <th class="c">Sakit</th>
        <th class="c">Izin</th>
        <th class="c">Alpa</th>
        <th class="c">Belum Absen</th>
        <th class="c">% Hadir</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($perKelas as $k)
        <tr>
          <td>{{ $k['rombel']->nama_rombel }}</td>
          <td class="c">{{ $k['jumlah_siswa'] }}</td>
          <td class="c">{{ $k['hadir'] }}</td>
          <td class="c">{{ $k['sakit'] }}</td>
          <td class="c">{{ $k['izin'] }}</td>
          <td class="c">{{ $k['alpa'] }}</td>
          <td class="c">{{ $k['belum'] }}</td>
          <td class="c">{{ $k['persen'] }}%</td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr>
        <td>Total</td>
        <td class="c">{{ $total['jumlah_siswa'] }}</td>
        <td class="c">{{ $total['hadir'] }}</td>
        <td class="c">{{ $total['sakit'] }}</td>
        <td class="c">{{ $total['izin'] }}</td>
        <td class="c">{{ $total['alpa'] }}</td>
        <td class="c">{{ $total['belum'] }}</td>
        <td class="c">{{ $total['persen'] }}%</td>
      </tr>
    </tfoot>
  </table>

  <h2>Per Siswa</h2>
  <table>
    <thead>
      <tr>
        <th class="c">#</th>
        <th>Nama Siswa</th>
        <th>NISN</th>
        <th>Kelas</th>
        <th class="c">Hadir</th>
        <th class="c">Sakit</th>
        <th class="c">Izin</th>
        <th class="c">Alpa</th>
        <th class="c">Belum Absen</th>
        <th class="c">% Hadir</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($perSiswa as $idx => $r)
        <tr>
          <td class="c">{{ $idx + 1 }}</td>
          <td>{{ $r['siswa']->nama_siswa }}</td>
          <td>{{ $r['siswa']->nisn ?? '-' }}</td>
          <td>{{ $r['rombel']->nama_rombel }}</td>
          <td class="c">{{ $r['hadir'] }}</td>
          <td class="c">{{ $r['sakit'] }}</td>
          <td class="c">{{ $r['izin'] }}</td>
          <td class="c">{{ $r['alpa'] }}</td>
          <td class="c">{{ $r['belum'] }}</td>
          <td class="c">{{ $r['persen'] }}%</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="catatan">
    "Belum absen" = hari efektif (tanggal yang benar-benar memiliki isian presensi pada cakupan ini)
    dalam masa registrasi siswa, dikurangi hari yang sudah tercatat.
    Dicetak {{ now()->format('d M Y H:i') }}.
  </div>
</body>

</html>
