@extends('layouts/layoutMaster')

@section('title', 'Token Sinkron BOS')

@section('content')

  @hasanyrole('Admin')
    <div class="card mb-4">
      <div class="card-body py-3 d-flex align-items-center gap-3 flex-wrap">
        <span class="text-muted"><i class="icon-base ri ri-school-line me-1"></i>Anda masuk sebagai Admin — konteks
          sekolah:</span>
        <form method="GET" action="{{ url('/kelola-bos/token') }}">
          <select name="sekolah_id" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach ($daftarSekolah as $ds)
              <option value="{{ $ds->id }}" {{ $ds->id === $sekolahId ? 'selected' : '' }}>
                {{ $ds->detailTerbaru->nama_sekolah ?? 'Sekolah #' . $ds->id }}</option>
            @endforeach
          </select>
        </form>
      </div>
    </div>
  @endhasanyrole

  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">{{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  @endif

  @if (!$sekolahId)
    <div class="alert alert-warning">Pilih sekolah dulu lewat pemilih di atas — token mengikuti sekolah yang
      dipilih.</div>
  @else
    <div class="card mb-4">
      <div class="card-body py-4 d-flex align-items-center gap-4 flex-wrap">
        <div class="avatar avatar-xl flex-shrink-0">
          <span class="avatar-initial rounded-circle bg-label-primary">
            <i class="icon-base ri ri-key-2-line ri-28px"></i>
          </span>
        </div>
        <div class="flex-grow-1">
          <h5 class="mb-1">Token Sinkron ARKAS</h5>
          <p class="mb-0 text-muted">{{ $sekolah->detailTerbaru->nama_sekolah ?? 'Sekolah #' . $sekolah->id }}
            &middot; kunci akses agen sinkron di laptop ARKAS
            @if ($sinkronTerakhir)
              &middot; Data per sinkron terakhir: {{ $sinkronTerakhir->created_at->format('d-m-Y H:i') }}
            @endif
          </p>
        </div>
      </div>
    </div>

    {{-- Token yang baru dibuat — tampil SEKALI dari flash session --}}
    @if (session('token_baru'))
      <div class="card border-success mb-4">
        <div class="card-body">
          <h6 class="text-success mb-2"><i class="icon-base ri ri-key-2-line me-1"></i>Token baru sekolah ini</h6>
          <div class="input-group mb-2">
            <input type="text" id="tokenBaru" class="form-control font-monospace" readonly
              value="{{ session('token_baru') }}">
            <button type="button" class="btn btn-outline-success" id="tombolSalinToken">
              <i class="icon-base ri ri-file-copy-line me-1"></i>Salin</button>
          </div>
          <p class="mb-0 text-danger"><strong>Simpan token ini sekarang.</strong> Token tidak disimpan dalam bentuk
            asli dan <strong>tidak akan ditampilkan lagi</strong> — kalau hilang, buat ulang dari halaman ini
            (token lama langsung berhenti bekerja).</p>
        </div>
      </div>
    @endif

    <div class="card mb-4">
      <div class="card-body">
        <h6 class="mb-3">Status Token</h6>
        @if ($tokenBaris)
          <p class="mb-2"><span class="badge bg-label-success">Token aktif</span></p>
          <p class="mb-2">Terakhir dipakai:
            <strong>{{ $tokenBaris->terakhir_dipakai ? $tokenBaris->terakhir_dipakai->format('d-m-Y H:i') : 'belum pernah' }}</strong>
          </p>
          <p class="mb-0 text-muted">Token yang aktif tidak bisa dilihat lagi — yang tersimpan di server hanya
            sidik jarinya (hash). Lupa token berarti buat ulang.</p>
        @else
          <p class="mb-2"><span class="badge bg-label-warning">Belum ada token</span></p>
          <p class="mb-0 text-muted">Sekolah ini belum punya token, jadi agen sinkron belum bisa mengirim data ke
            server ini. Buat token pertama dengan tombol di bawah.</p>
        @endif
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-body">
        <h6 class="mb-3">{{ $tokenBaris ? 'Buat Ulang Token' : 'Buat Token Baru' }}</h6>
        @if ($tokenBaris)
          <p class="text-muted">Membuat ulang akan <strong>mematikan token yang sekarang dipakai agen</strong>
            seketika. Sesudahnya, isi token baru ke aplikasi Sinkron ARKAS di laptop (atau perbarui
            <code>BOS_TOKEN</code> bila memakai perintah manual), lalu jalankan sinkron lagi.</p>
        @endif
        <form method="POST" action="{{ route('kelola-bos.token.buat') }}"
          @if ($tokenBaris) onsubmit="return confirm('Token lama akan berhenti bekerja seketika dan agen harus diisi token baru. Lanjutkan buat ulang?')" @endif>
          @csrf
          <button type="submit" class="btn btn-primary">
            <i class="icon-base ri ri-key-2-line me-1"></i>{{ $tokenBaris ? 'Buat Ulang Token' : 'Buat Token Baru' }}
          </button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h6 class="mb-3">Cara Memakai Token</h6>
        <ul class="mb-0">
          <li>Isi token ini di aplikasi <strong>Sinkron ARKAS</strong> pada kolom token tujuan yang sesuai dengan
            server ini — tujuan <strong>Lokal</strong> bila aplikasi ini berjalan di komputer lokal, tujuan
            <strong>Hosting</strong> bila ini server hosting. Bila memakai agen lewat perintah manual, isi sebagai
            nilai <code>BOS_TOKEN</code>.</li>
          <li><strong>Satu token per sekolah</strong> — token sekolah lain tidak berlaku di sini.</li>
          <li>Token <strong>lokal dan hosting berbeda</strong> karena databasenya berbeda; token halaman ini hanya
            berlaku untuk server yang sedang Anda buka.</li>
          <li>Jangan bagikan token ke pihak yang tidak berkepentingan — siapa pun yang memegangnya bisa mengirim
            data ARKAS ke sekolah ini.</li>
        </ul>
      </div>
    </div>
  @endif

@endsection

@section('page-script')
  <script>
    document.getElementById('tombolSalinToken')?.addEventListener('click', function () {
      var kolom = document.getElementById('tokenBaru');
      if (!kolom) return;
      kolom.select();
      kolom.setSelectionRange(0, kolom.value.length);
      var selesai = function (tombol) {
        var awal = tombol.innerHTML;
        tombol.innerHTML = '<i class="icon-base ri ri-check-line me-1"></i>Tersalin';
        setTimeout(function () { tombol.innerHTML = awal; }, 2000);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(kolom.value).then(() => selesai(this)).catch(() => {
          document.execCommand('copy');
          selesai(this);
        });
      } else {
        document.execCommand('copy');
        selesai(this);
      }
    });
  </script>
@endsection
