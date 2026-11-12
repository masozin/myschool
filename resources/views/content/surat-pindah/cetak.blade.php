{{-- resources/views/content/surat-pindah/cetak.blade.php --}}
{{-- Paket cetak Surat Pindah: halaman 1 Surat Keterangan Pindah
     Sekolah (bernomor resmi), halaman 2 Surat Permohonan Pindah
     Sekolah dari orang tua/wali (diformat untuk ditandatangani
     tangan). Semua isi dibaca dari snapshot data_cetak surat,
     jadi cetak ulang identik dengan saat terbit. --}}
@php
  $fmtTgl = fn($t) => $t ? \Carbon\Carbon::parse($t)->translatedFormat('d F Y') : '-';
  $sk = $d['sekolah'] ?? [];
  $sw = $d['siswa'] ?? [];
  $pm = $d['pemohon'] ?? [];
  $ks = $d['kepsek'] ?? [];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
  @page { margin: 2cm 2cm 2cm 2.5cm; }
  body { font-family: 'Times New Roman', Times, serif; font-size: 12pt; line-height: 1.45; color: #000; }
  .kop-img { width: 100%; }
  .kop-teks { text-align: center; }
  .kop-teks .nama { font-size: 15pt; font-weight: bold; text-transform: uppercase; }
  .kop-teks .alamat { font-size: 10pt; }
  .garis { border-bottom: 3px double #000; margin: 4px 0 14px 0; }
  h1.judul { font-size: 13pt; text-align: center; text-decoration: underline; margin: 6px 0 0 0; }
  .nomor { text-align: center; margin: 0 0 14px 0; }
  table.ident { border-collapse: collapse; margin: 4px 0 10px 0; }
  table.ident td { padding: 1px 3px; vertical-align: top; }
  table.ident td.label { width: 165px; }
  table.ident td.titik { width: 12px; }
  p { margin: 0 0 10px 0; text-align: justify; }
  .ttd { width: 100%; margin-top: 18px; }
  .ttd .kanan { width: 45%; margin-left: 55%; }
  .ttd .ruang { height: 78px; position: relative; }
  .ttd img.ttd-img { height: 70px; }
  .ttd img.stempel-img { height: 92px; position: absolute; left: 92px; top: -8px; opacity: 0.85; }
  .nama-ttd { font-weight: bold; text-decoration: underline; }
  .putus { page-break-before: always; }
  .stempel-batal { border: 2px solid #c00; color: #c00; font-weight: bold;
    text-align: center; padding: 6px; margin-bottom: 12px; font-size: 11pt; }
</style>
</head>
<body>

@if (($surat->status ?? null) === 'batal')
<div class="stempel-batal">SURAT INI TELAH DIBATALKAN — NOMOR {{ $d['nomor'] ?? '' }} TIDAK BERLAKU</div>
@endif

@if ($kopPath)
<img class="kop-img" src="{{ $kopPath }}" alt="Kop Sekolah">
@else
<div class="kop-teks">
  <div class="nama">{{ $sk['nama'] ?? '' }}</div>
  <div class="alamat">
    {{ $sk['alamat'] ?? '' }}{{ !empty($sk['desa']) ? ', ' . $sk['desa'] : '' }}{{ !empty($sk['kecamatan']) ? ', Kec. ' . $sk['kecamatan'] : '' }}{{ !empty($sk['kabupaten']) ? ', Kab. ' . $sk['kabupaten'] : '' }}{{ !empty($sk['provinsi']) ? ', Prov. ' . $sk['provinsi'] : '' }}
  </div>
</div>
<div class="garis"></div>
@endif

<h1 class="judul">SURAT KETERANGAN PINDAH SEKOLAH</h1>
<div class="nomor">Nomor: {{ $d['nomor'] ?? '-' }}</div>

<p>Yang bertanda tangan di bawah ini Kepala {{ $sk['nama'] ?? '' }}:</p>
<table class="ident">
  <tr><td class="label">NPSN</td><td class="titik">:</td><td>{{ $sk['npsn'] ?? '-' }}</td></tr>
  <tr><td class="label">Kecamatan</td><td class="titik">:</td><td>{{ $sk['kecamatan'] ?? '-' }}</td></tr>
  <tr><td class="label">Kabupaten</td><td class="titik">:</td><td>{{ $sk['kabupaten'] ?? '-' }}</td></tr>
  <tr><td class="label">Propinsi</td><td class="titik">:</td><td>{{ $sk['provinsi'] ?? '-' }}</td></tr>
</table>

<p>Menerangkan bahwa:</p>
<table class="ident">
  <tr><td class="label">Nama</td><td class="titik">:</td><td><strong>{{ strtoupper($sw['nama'] ?? '-') }}</strong></td></tr>
  <tr><td class="label">Tempat Tanggal Lahir</td><td class="titik">:</td>
    <td>{{ $sw['tempat_lahir'] ?? '-' }}, {{ $fmtTgl($sw['tanggal_lahir'] ?? null) }}</td></tr>
  <tr><td class="label">Nomor Induk / NISN</td><td class="titik">:</td>
    <td>{{ $sw['nis'] ?? '-' }} / {{ $sw['nisn'] ?? '-' }}</td></tr>
  <tr><td class="label">Jenis Kelamin</td><td class="titik">:</td><td>{{ $sw['jenis_kelamin'] ?? '-' }}</td></tr>
  <tr><td class="label">Siswa Kelas</td><td class="titik">:</td>
    <td>{{ $sw['kelas_romawi'] ?? '-' }} ({{ $sw['kelas_kata'] ?? '-' }})</td></tr>
</table>

<p>Sesuai surat permohonan pindah sekolah oleh orang tua / wali murid:</p>
<table class="ident">
  <tr><td class="label">Nama</td><td class="titik">:</td><td>{{ $pm['nama'] ?? '-' }}</td></tr>
  <tr><td class="label">Pekerjaan</td><td class="titik">:</td><td>{{ $pm['pekerjaan'] ?? '-' }}</td></tr>
  <tr><td class="label">Alamat</td><td class="titik">:</td><td>{{ $pm['alamat'] ?? '-' }}</td></tr>
</table>

<p>Telah mengajukan pindah sekolah ke {{ $d['tujuan_teks'] ?? '-' }}@if (!empty($d['alasan'])) dengan alasan {{ $d['alasan'] }}@endif.
Bersama ini kami sertakan Buku Laporan Pendidikan (Raport) siswa yang bersangkutan dan Surat
Permohonan Pindah Sekolah dari orang tua/wali serta Surat Pindah dari Data Pokok Pendidikan (Dapodik).</p>

@if (!empty($d['catatan']))
<p>Catatan: {{ $d['catatan'] }}</p>
@endif

<p>Demikian surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.</p>

<table class="ttd">
  <tr>
    <td class="kanan">
      {{ $sk['desa'] ?? '' }}, {{ $fmtTgl($d['tanggal'] ?? null) }}<br>
      Kepala {{ $sk['nama'] ?? '' }}
      <div class="ruang">
        @if ($ttdPath)<img class="ttd-img" src="{{ $ttdPath }}" alt="Tanda tangan">@endif
        @if ($stempelPath)<img class="stempel-img" src="{{ $stempelPath }}" alt="Stempel">@endif
      </div>
      <span class="nama-ttd">{{ $ks['nama'] ?? '-' }}</span><br>
      NIP. {{ $ks['nip'] ?? '-' }}
    </td>
  </tr>
</table>

{{-- ================= HALAMAN 2: SURAT PERMOHONAN ================= --}}
<div class="putus"></div>

<h1 class="judul" style="text-decoration: none;">SURAT PERMOHONAN PINDAH SEKOLAH</h1>
<div style="height: 12px;"></div>

<p style="text-align: left;">
  Kepada:<br>
  Yth. Kepala {{ $sk['nama'] ?? '' }}<br>
  Di<br>
  &nbsp;&nbsp;&nbsp;&nbsp;{{ $sk['desa'] ?? '' }}
</p>

<p>Dengan hormat,<br>Yang bertanda tangan di bawah ini:</p>
<table class="ident">
  <tr><td class="label">Nama</td><td class="titik">:</td><td>{{ $pm['nama'] ?? '-' }}</td></tr>
  <tr><td class="label">Pekerjaan</td><td class="titik">:</td><td>{{ $pm['pekerjaan'] ?? '-' }}</td></tr>
  <tr><td class="label">Alamat</td><td class="titik">:</td><td>{{ $pm['alamat'] ?? '-' }}</td></tr>
</table>

<p>{{ $pm['relasi'] ?? 'Wali' }} dari siswa:</p>
<table class="ident">
  <tr><td class="label">Nama</td><td class="titik">:</td><td><strong>{{ strtoupper($sw['nama'] ?? '-') }}</strong></td></tr>
  <tr><td class="label">Tempat Tanggal Lahir</td><td class="titik">:</td>
    <td>{{ $sw['tempat_lahir'] ?? '-' }}, {{ $fmtTgl($sw['tanggal_lahir'] ?? null) }}</td></tr>
  <tr><td class="label">Nomor Induk / NISN</td><td class="titik">:</td>
    <td>{{ $sw['nis'] ?? '-' }} / {{ $sw['nisn'] ?? '-' }}</td></tr>
  <tr><td class="label">Jenis Kelamin</td><td class="titik">:</td><td>{{ $sw['jenis_kelamin'] ?? '-' }}</td></tr>
  <tr><td class="label">Siswa Kelas</td><td class="titik">:</td>
    <td>{{ $sw['kelas_romawi'] ?? '-' }} ({{ $sw['kelas_kata'] ?? '-' }})</td></tr>
</table>

<p>Mengajukan permohonan pindah belajar untuk siswa tersebut di atas dari {{ $sk['nama'] ?? '' }}
Kec. {{ $sk['kecamatan'] ?? '-' }} Kab. {{ $sk['kabupaten'] ?? '-' }} Prov. {{ $sk['provinsi'] ?? '-' }}
ke {{ $d['tujuan_teks'] ?? '-' }}@if (!empty($d['alasan'])) dengan alasan {{ $d['alasan'] }}@endif.</p>

<p>Atas perhatian dan kerja sama saudara, kami ucapkan terima kasih.</p>

<table class="ttd">
  <tr>
    <td class="kanan">
      {{ $sk['desa'] ?? '' }}, {{ $fmtTgl($d['tanggal'] ?? null) }}<br>
      Hormat Kami
      <div class="ruang"></div>
      (.................................................)
    </td>
  </tr>
</table>

</body>
</html>
