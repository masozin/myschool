<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Tanda Terima SPJ BOS</title>
  <style>
    @page {
      margin: 16mm 14mm;
    }

    body {
      font-family: 'DejaVu Sans', sans-serif;
      font-size: 11pt;
      color: #000;
    }

    .kop {
      text-align: center;
    }

    .kop .nama-sekolah {
      font-size: 14pt;
      font-weight: bold;
      text-transform: uppercase;
    }

    .kop .baris-kecil {
      font-size: 9.5pt;
    }

    .garis-kop {
      border-top: 3px solid #000;
      border-bottom: 1px solid #000;
      height: 2px;
      margin-top: 6px;
    }

    .judul {
      font-size: 14pt;
      font-weight: bold;
      text-align: center;
      letter-spacing: 2px;
      margin-top: 14px;
    }

    .kegiatan {
      text-align: center;
      font-size: 11pt;
      margin-top: 2px;
    }

    table.daftar {
      width: 100%;
      border-collapse: collapse;
      margin-top: 12px;
      font-size: 10pt;
    }

    table.daftar th,
    table.daftar td {
      border: 1px solid #000;
      padding: 4px 6px;
      text-align: left;
      vertical-align: top;
    }

    table.daftar th {
      text-align: center;
    }

    table.daftar td.angka,
    table.daftar th.angka {
      text-align: right;
    }

    table.daftar td.tengah,
    table.daftar th.tengah {
      text-align: center;
    }

    table.daftar tr.jumlah td {
      font-weight: bold;
    }

    table.ttd {
      width: 100%;
      border-collapse: collapse;
      margin-top: 26px;
      font-size: 10.5pt;
    }

    table.ttd td {
      width: 50%;
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
    $detail = $sekolah->detailTerbaru ?? null;
    $namaSekolah = $detail->nama_sekolah ?? 'Sekolah #' . $sekolah->id;
    $rp = fn($v) => number_format((float) $v, 0, ',', '.');
    $namaKecamatan = $detail?->district?->name;
    $tempat = $namaKecamatan ? ucwords(strtolower($namaKecamatan)) : null;
    $tanggalTtd = ($tandaTerima['tanggal'] ?? null)
      ? \Carbon\Carbon::parse($tandaTerima['tanggal'])->translatedFormat('d F Y')
      : now()->translatedFormat('d F Y');
  @endphp

  <div class="kop">
    <div class="nama-sekolah">{{ $namaSekolah }}</div>
    @if ($sekolah->npsn ?? null)
      <div class="baris-kecil">NPSN {{ $sekolah->npsn }}</div>
    @endif
    @if ($detail?->alamat_sekolah)
      <div class="baris-kecil">
        {{ $detail->alamat_sekolah }}@if ($detail->email) &bull; Email: {{ $detail->email }}@endif
      </div>
    @endif
  </div>
  <div class="garis-kop"></div>

  <div class="judul">TANDA TERIMA</div>
  <div class="kegiatan">{{ $tandaTerima['kegiatan'] ?? '' }}</div>

  <table class="daftar">
    <thead>
      <tr>
        <th class="tengah" style="width: 28px;">No</th>
        <th>Nama Penerima</th>
        <th style="width: 105px;">Pangkat/Gol</th>
        <th style="width: 105px;">NIK</th>
        <th class="tengah" style="width: 38px;">Vol</th>
        <th class="angka" style="width: 88px;">Satuan (Rp)</th>
        <th class="angka" style="width: 98px;">Jumlah (Rp)</th>
        <th class="angka" style="width: 88px;">PPh (Rp)</th>
        <th class="angka" style="width: 98px;">Jml Diterima (Rp)</th>
        <th class="tengah" style="width: 86px;">Tanda Tangan</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($tandaTerima['baris'] as $r)
        <tr>
          <td class="tengah">{{ $loop->iteration }}</td>
          <td>{{ $r['nama'] }}</td>
          <td>{{ $r['pangkat_gol'] ?: '-' }}</td>
          <td>{{ $r['nik'] ?: '-' }}</td>
          <td class="tengah">{{ $r['volume'] ?: '-' }}</td>
          <td class="angka">{{ $rp($r['satuan']) }}</td>
          <td class="angka">{{ $rp($r['jumlah']) }}</td>
          <td class="angka">{{ $rp($r['pph']) }}</td>
          <td class="angka">{{ $rp($r['diterima']) }}</td>
          <td class="tengah">{{ $loop->iteration }}</td>
        </tr>
      @endforeach
      <tr class="jumlah">
        <td colspan="6" style="text-align: right;">JUMLAH</td>
        <td class="angka">{{ $rp($tandaTerima['total_jumlah']) }}</td>
        <td class="angka">{{ $rp($tandaTerima['total_pph']) }}</td>
        <td class="angka">{{ $rp($tandaTerima['total_diterima']) }}</td>
        <td></td>
      </tr>
    </tbody>
  </table>

  <table class="ttd">
    <tr>
      <td>Kepala Sekolah</td>
      <td>
        @if ($tempat)
          {{ $tempat }}, {{ $tanggalTtd }}
        @else
          {{ $tanggalTtd }}
        @endif
        <br>Bendahara/Penanggung Jawab Kegiatan
      </td>
    </tr>
    <tr class="ruang-ttd">
      <td></td>
      <td></td>
    </tr>
    <tr>
      <td>
        @if ($tandaTerima['ttd']['kepala']['nama'])
          <span class="nama-ttd">{{ $tandaTerima['ttd']['kepala']['nama'] }}</span>
          @if ($tandaTerima['ttd']['kepala']['nip'])
            <br>NIP. {{ $tandaTerima['ttd']['kepala']['nip'] }}
          @endif
        @else
          ...................................
        @endif
      </td>
      <td>
        @if ($tandaTerima['ttd']['bendahara']['nama'])
          <span class="nama-ttd">{{ $tandaTerima['ttd']['bendahara']['nama'] }}</span>
          @if ($tandaTerima['ttd']['bendahara']['nip'])
            <br>NIP. {{ $tandaTerima['ttd']['bendahara']['nip'] }}
          @endif
        @else
          ...................................
        @endif
      </td>
    </tr>
  </table>
</body>

</html>
