@extends('layouts/layoutMaster')

@section('title', 'Berita Web Sekolah')

@section('content')
  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  @endif

  <div class="card">
    <div class="card-header border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
      <div>
        <h5 class="card-title mb-0">Berita &amp; Pengumuman</h5>
        <small class="text-muted">{{ $sekolah->detailTerbaru->nama_sekolah ?? '' }} — berita berstatus Terbit tampil di web publik.</small>
      </div>
      <a href="{{ route('web-sekolah.kelola.berita.create') }}" class="btn btn-primary">
        <i class="icon-base ri ri-add-line me-1"></i> Tulis Berita
      </a>
    </div>
    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr><th>Judul</th><th>Status</th><th>Diterbitkan</th><th>Terakhir Diubah</th><th class="text-end">Aksi</th></tr>
        </thead>
        <tbody>
          @forelse ($berita as $b)
            <tr>
              <td>
                <strong>{{ $b->judul }}</strong>
                @if ($b->status === 'terbit' && $sekolah->webSekolah)
                  <div><a href="{{ route('web-sekolah.berita.tampil', [$sekolah->webSekolah->slug, $b->slug]) }}" target="_blank" rel="noopener"><small>Lihat di web publik</small></a></div>
                @endif
              </td>
              <td><span class="badge {{ $b->status === 'terbit' ? 'bg-label-success' : 'bg-label-warning' }}">{{ $b->status === 'terbit' ? 'Terbit' : 'Draf' }}</span></td>
              <td>{{ $b->diterbitkan_pada?->format('d-m-Y H:i') ?? '—' }}</td>
              <td>{{ $b->updated_at->format('d-m-Y H:i') }}</td>
              <td class="text-end">
                <a href="{{ route('web-sekolah.kelola.berita.edit', $b) }}" class="btn btn-sm btn-outline-primary">Ubah</a>
                <form method="POST" action="{{ route('web-sekolah.kelola.berita.destroy', $b) }}" class="d-inline"
                  onsubmit="return confirm('Hapus berita ini?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada berita. Klik “Tulis Berita” untuk membuat yang pertama.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if ($berita->hasPages())
      <div class="card-footer d-flex justify-content-center">{{ $berita->links() }}</div>
    @endif
  </div>
@endsection
