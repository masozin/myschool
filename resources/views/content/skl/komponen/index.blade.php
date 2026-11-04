@extends('layouts/layoutMaster')

@section('title', 'Komponen Nilai SKL')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')

  {{-- ── Header ─────────────────────────────────────────────────────────── --}}
  <div class="mb-4 d-flex align-items-center gap-2">
    <a href="{{ route('skl.index') }}" class="btn btn-sm btn-outline-secondary">
      <i class="icon-base ri ri-arrow-left-line me-1"></i>Kembali
    </a>
    <div>
      <h5 class="mb-0">Komponen Nilai SKL</h5>
      <small class="text-muted">
        Semester {{ $semester->tahun_pelajaran }} &mdash; Jenjang {{ $jenjang }}
      </small>
    </div>
  </div>

  {{-- ── Toolbar ──────────────────────────────────────────────────────────── --}}
  <div class="card mb-4">
    <div class="card-body py-3 d-flex justify-content-between align-items-center flex-wrap gap-3">

      {{-- Filter Jenjang (semester sudah fix dari session) --}}
      <div class="d-flex gap-2 align-items-center flex-wrap">
        <span class="text-muted small fw-semibold">Jenjang:</span>
        @foreach (['SD', 'SMP', 'SMA', 'SMK'] as $j)
          <a href="{{ route('skl.komponen.index', ['jenjang' => $j]) }}"
            class="btn btn-sm {{ $jenjang === $j ? 'btn-primary' : 'btn-outline-secondary' }}">
            {{ $j }}
          </a>
        @endforeach
      </div>

      <a href="{{ route('skl.komponen.create', ['jenjang' => $jenjang]) }}" class="btn btn-primary btn-sm">
        <i class="icon-base ri ri-add-line me-1"></i>Tambah Komponen
      </a>

    </div>
  </div>

  {{-- ── Info Bobot ───────────────────────────────────────────────────────── --}}
  <div class="row mb-4 g-3">
    <div class="col-12 col-md-6">
      <div class="card border-{{ $totalBobot === 100 ? 'success' : ($totalBobot > 100 ? 'danger' : 'warning') }}">
        <div class="card-body py-3 d-flex align-items-center gap-3">
          <div class="avatar">
            <span
              class="avatar-initial rounded-circle bg-label-{{ $totalBobot === 100 ? 'success' : ($totalBobot > 100 ? 'danger' : 'warning') }}">
              <i class="icon-base ri ri-percent-line"></i>
            </span>
          </div>
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <small class="text-muted">Total Bobot</small>
              <strong class="text-{{ $totalBobot === 100 ? 'success' : ($totalBobot > 100 ? 'danger' : 'warning') }}">
                {{ $totalBobot }}%
              </strong>
            </div>
            <div class="progress" style="height:6px">
              <div
                class="progress-bar bg-{{ $totalBobot === 100 ? 'success' : ($totalBobot > 100 ? 'danger' : 'warning') }}"
                style="width: {{ min($totalBobot, 100) }}%"></div>
            </div>
            <small class="text-muted">
              @if ($totalBobot === 100)
                <i class="ri ri-checkbox-circle-line text-success"></i> Bobot sudah tepat 100%.
              @elseif ($totalBobot > 100)
                <i class="ri ri-error-warning-line text-danger"></i> Bobot melebihi 100%! Harap diperbaiki.
              @else
                Sisa bobot: <strong>{{ 100 - $totalBobot }}%</strong>
              @endif
            </small>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ── Tabel Komponen ───────────────────────────────────────────────────── --}}
  <div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
      <h5 class="card-title mb-0">
        Daftar Komponen — {{ $jenjang }}
      </h5>
      <span class="badge bg-label-primary">{{ $komponens->count() }} komponen</span>
    </div>

    @if ($komponens->isEmpty())
      <div class="card-body text-center py-5">
        <i class="icon-base ri ri-inbox-line ri-48px text-muted d-block mb-3"></i>
        <p class="text-muted mb-3">Belum ada komponen untuk jenjang <strong>{{ $jenjang }}</strong>.</p>
        <a href="{{ route('skl.komponen.create', ['jenjang' => $jenjang]) }}" class="btn btn-primary btn-sm">
          <i class="icon-base ri ri-add-line me-1"></i>Tambah Komponen Pertama
        </a>
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-bordered table-hover mb-0">
          <thead>
            <tr>
              <th width="50" class="text-center">Urutan</th>
              <th>Kode</th>
              <th>Label</th>
              <th class="text-center" width="100">Bobot</th>
              <th>Keterangan</th>
              <th>Sub-Komponen</th>
              <th width="130">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($komponens as $komp)
              <tr>
                <td class="text-center text-muted">{{ $komp->urutan }}</td>
                <td><code class="small">{{ $komp->kode }}</code></td>
                <td class="fw-medium">{{ $komp->label }}</td>
                <td class="text-center">
                  <span class="badge bg-label-primary">{{ $komp->bobot }}%</span>
                </td>
                <td class="text-muted small">{{ $komp->keterangan ?? '-' }}</td>
                <td>
                  @php $subCount = $komp->subKomponen->count(); @endphp
                  @if ($subCount > 0)
                    <a href="{{ route('skl.komponen.sub.index', $komp) }}"
                      class="badge bg-label-info text-decoration-none">
                      {{ $subCount }} sub
                    </a>
                  @else
                    <a href="{{ route('skl.komponen.sub.index', $komp) }}"
                      class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2" style="font-size:11px">
                      <i class="ri ri-add-line"></i> Tambah Sub
                    </a>
                  @endif
                </td>
                <td>
                  <div class="d-flex gap-1">
                    <a href="{{ route('skl.komponen.edit', $komp) }}"
                      class="btn btn-sm btn-icon btn-text-warning rounded-pill" title="Edit">
                      <i class="icon-base ri ri-edit-line icon-18px"></i>
                    </a>
                    <button class="btn btn-sm btn-icon btn-text-danger rounded-pill btn-hapus-komponen"
                      data-id="{{ $komp->id }}" data-label="{{ $komp->label }}" title="Hapus">
                      <i class="icon-base ri ri-delete-bin-line icon-18px"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr class="table-light">
              <td colspan="3" class="fw-bold text-end">Total Bobot</td>
              <td
                class="text-center fw-bold text-{{ $totalBobot === 100 ? 'success' : ($totalBobot > 100 ? 'danger' : 'warning') }}">
                {{ $totalBobot }}%
              </td>
              <td colspan="3"></td>
            </tr>
          </tfoot>
        </table>
      </div>

      {{-- Contoh formula --}}
      @if ($komponens->isNotEmpty())
        <div class="card-body border-top py-3">
          <small class="fw-semibold text-muted d-block mb-2">FORMULA NILAI AKHIR SKL</small>
          <div class="d-flex flex-wrap gap-2 align-items-center small text-muted">
            @foreach ($komponens as $i => $komp)
              @if ($i > 0)
                <span>+</span>
              @endif
              <span class="badge bg-label-secondary">
                {{ $komp->label }} × {{ $komp->bobot }}%
              </span>
            @endforeach
            <span>= Nilai Akhir</span>
          </div>
        </div>
      @endif

    @endif
  </div>

@endsection

@section('page-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;

      document.addEventListener('click', async function(e) {
        const btn = e.target.closest('.btn-hapus-komponen');
        if (!btn) return;

        const konfirm = await Swal.fire({
          icon: 'warning',
          title: 'Hapus Komponen?',
          html: `Komponen <strong>${btn.dataset.label}</strong> akan dihapus.<br>
                 <span class="text-danger small">Data nilai SKL yang sudah terhubung juga akan terpengaruh.</span>`,
          showCancelButton: true,
          confirmButtonText: 'Ya, Hapus',
          cancelButtonText: 'Batal',
          customClass: {
            confirmButton: 'btn btn-danger me-3',
            cancelButton: 'btn btn-outline-secondary'
          },
          buttonsStyling: false,
        });
        if (!konfirm.isConfirmed) return;

        const res = await fetch(`/skl/komponen/${btn.dataset.id}`, {
          method: 'DELETE',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
          },
        });
        const data = await res.json();

        if (res.ok) {
          Swal.fire({
            icon: 'success',
            title: 'Dihapus',
            text: data.message,
            timer: 2000,
            showConfirmButton: false
          });
          setTimeout(() => location.reload(), 2100);
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: data.message
          });
        }
      });
    });
  </script>
@endsection
