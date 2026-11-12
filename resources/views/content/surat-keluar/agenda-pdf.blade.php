<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Buku Agenda Surat Keluar</title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 10px;
      color: #222;
    }

    h2 {
      margin: 0 0 2px 0;
      font-size: 14px;
    }

    .sub {
      margin: 0 0 12px 0;
      color: #555;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      border: 1px solid #888;
      padding: 4px 5px;
      vertical-align: top;
    }

    th {
      background: #eee;
      text-align: left;
    }

    .tengah {
      text-align: center;
    }

    .batal {
      color: #b00;
    }
  </style>
</head>

<body>
  <h2>BUKU AGENDA SURAT KELUAR</h2>
  <p class="sub">{{ $sekolah->detailTerbaru->nama_sekolah ?? 'Sekolah' }}
    {{ $tahun ? '— Tahun ' . $tahun : '' }} &middot; Dicetak {{ now()->format('d-m-Y H:i') }}</p>

  <table>
    <thead>
      <tr>
        <th style="width:28px" class="tengah">No</th>
        <th style="width:130px">Nomor Surat</th>
        <th style="width:70px">Tanggal</th>
        <th style="width:150px">Tujuan</th>
        <th>Perihal</th>
        <th style="width:110px">Kategori</th>
        <th style="width:90px">Penandatangan</th>
        <th style="width:60px" class="tengah">Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($surats as $i => $s)
        <tr>
          <td class="tengah">{{ $i + 1 }}</td>
          <td>{{ $s->nomor_surat ?? '(draft)' }}</td>
          <td>{{ $s->tanggal_surat?->format('d-m-Y') }}</td>
          <td>{{ $s->tujuan }}</td>
          <td>{{ $s->perihal }}</td>
          <td>{{ $s->kategori?->nama ?? '-' }}</td>
          <td>{{ $s->penandatangan?->nama_lengkap ?? '-' }}</td>
          <td class="tengah {{ $s->status === 'batal' ? 'batal' : '' }}">{{ $s->labelStatus() }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="8" class="tengah">Tidak ada surat pada saringan ini.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</body>

</html>
