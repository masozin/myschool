<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <title>Jadwal Mengajar per Guru</title>
  <style>
    @page {
      margin: 10mm 9mm;
    }

    body {
      font-family: sans-serif;
      font-size: 9pt;
      color: #222;
    }

    .lembar {
      page-break-after: always;
    }

    .lembar.terakhir {
      page-break-after: auto;
    }

    .kepala {
      text-align: center;
      margin-bottom: 8px;
      border-bottom: 2px solid #7367F0;
      padding-bottom: 6px;
    }

    .sekolah {
      font-size: 13pt;
      font-weight: bold;
      text-transform: uppercase;
    }

    .judul {
      font-size: 12pt;
      font-weight: bold;
      margin-top: 2px;
    }

    .sub {
      font-size: 8.5pt;
      color: #555;
      margin-top: 2px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      table-layout: fixed;
    }

    th {
      background: #7367F0;
      color: #fff;
      padding: 4px 2px;
      font-size: 9.5pt;
      border: 0.5pt solid #8a84d6;
    }

    td {
      border: 0.5pt solid #aaa;
      vertical-align: top;
      padding: 3px 4px;
      height: 11mm;
    }

    .waktu {
      font-size: 7.5pt;
      color: #666;
    }

    .label {
      font-weight: bold;
      font-size: 9.5pt;
      line-height: 1.25;
    }

    .mapel {
      font-size: 8pt;
      color: #444;
    }

    .kosong {
      text-align: center;
      padding: 24px 0;
      color: #888;
      border: 0.5pt dashed #bbb;
    }
  </style>
</head>

<body>
  @forelse ($lembar as $d)
    <div class="lembar {{ $loop->last ? 'terakhir' : '' }}">
      <div class="kepala">
        <div class="sekolah">{{ $sekolah->detailTerbaru->nama_sekolah ?? '' }}</div>
        <div class="judul">JADWAL MENGAJAR — {{ $d['ptk']->nama_lengkap }}</div>
        <div class="sub">
          @if ($semester)
            Semester {{ $semester->semester }} Tahun Pelajaran {{ $semester->tahun_pelajaran }}
          @endif
          @if ($d['ptk']->jabatan)
            <span>&bull;</span> {{ $d['ptk']->jabatan }}
          @endif
        </div>
      </div>

      @if ($d['baris'] > 0)
        <table>
          <thead>
            <tr>
              @foreach ($d['hari'] as $h)
                <th>{{ $h['nama'] }}</th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @for ($i = 0; $i < $d['baris']; $i++)
              <tr>
                @foreach ($d['hari'] as $h)
                  @php $b = $h['blok'][$i] ?? null; @endphp
                  @if ($b)
                    <td>
                      <div class="waktu">{{ $b['mulai'] }}&ndash;{{ $b['selesai'] }}</div>
                      <div class="label">Kelas {{ $b['label'] }}</div>
                      <div class="mapel">{{ $b['sub'] }}</div>
                    </td>
                  @else
                    <td></td>
                  @endif
                @endforeach
              </tr>
            @endfor
          </tbody>
        </table>
      @else
        <div class="kosong">Guru ini belum mempunyai kotak jadwal pada semester ini.</div>
      @endif
    </div>
  @empty
    <div class="kosong">Belum ada guru yang mempunyai jadwal mengajar pada semester ini.</div>
  @endforelse
</body>

</html>
