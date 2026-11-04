{{-- resources/views/ijazah/komponen/index.blade.php --}}
@extends('layouts/layoutMaster')

@section('title', 'Konfigurasi Komponen Nilai Ijazah')

@section('vendor-style')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
  @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')

  {{-- Filter --}}
  <div class="card mb-4">
    <div class="card-body py-3 d-flex align-items-center flex-wrap gap-3">
      <form method="GET" action="{{ route('ijazah.komponen.index') }}" class="d-flex align-items-center gap-2 flex-wrap">

        <select name="jenjang" class="form-select form-select-sm" style="width:auto">
          @foreach (['SD', 'SMP', 'SMA', 'SMK'] as $j)
            <option value="{{ $j }}" {{ $jenjang === $j ? 'selected' : '' }}>{{ $j }}</option>
          @endforeach
        </select>

        <select name="tahun_pelajaran" class="form-select form-select-sm" style="width:auto">
          @foreach (['2024/2025', '2025/2026', '2026/2027'] as $tp)
            <option value="{{ $tp }}" {{ $tahunPelajaran === $tp ? 'selected' : '' }}>{{ $tp }}
            </option>
          @endforeach
        </select>

        <button type="submit" class="btn btn-primary btn-sm">
          <i class="icon-base ri ri-filter-line me-1"></i>Tampilkan
        </button>
      </form>

      <div class="ms-auto">
        <a href="{{ route('ijazah.komponen.create') }}?jenjang={{ $jenjang }}&tahun_pelajaran={{ $tahunPelajaran }}"
          class="btn btn-primary btn-sm">
          <i class="icon-base ri ri-add-line me-1"></i>Tambah Komponen
        </a>
      </div>
    </div>
  </div>

  {{-- Alert total bobot --}}
  @if ($totalBobot !== 100 && $komponens->isNotEmpty())
    <div class="alert alert-{{ $totalBobot > 100 ? 'danger' : 'warning' }} d-flex gap-2 mb-4">
      <i class="icon-base ri ri-error-warning-line mt-1 flex-shrink-0"></i>
      <div>
        Total bobot saat ini: <strong>{{ $totalBobot }}%</strong>.
        @if ($totalBobot > 100)
          Melebihi 100%, harap perbaiki sebelum digunakan.
        @else
          Belum mencapai 100%. Sisa: <strong>{{ 100 - $totalBobot }}%</strong>.
        @endif
      </div>
    </div>
  @elseif ($totalBobot === 100)
    <div class="alert alert-success d-flex gap-2 mb-4">
      <i class="icon-base ri ri-checkbox-circle-line mt-1 flex-shrink-0"></i>
      <div>Total bobot <strong>100%</strong> — konfigurasi siap digunakan.</div>
    </div>
  @endif

  <div class="card">
    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
      <h5 class="mb-0">
        Komponen Nilai Ijazah
        <span class="text-muted fw-normal fs-6 ms-2">{{ $jenjang }} / {{ $tahunPelajaran }}</span>
      </h5>
      <span class="badge bg-label-{{ $totalBobot === 100 ? 'success' : 'warning' }} fs-6">
        Total Bobot: {{ $totalBobot }}%
      </span>
    </div>

    @if ($komponens->isEmpty())
      <div class="card-body text-center py-5">
        <i class="icon-base ri ri-inbox-line ri-48px text-muted mb-3 d-block"></i>
        <p class="text-muted mb-3">Belum ada komponen untuk {{ $jenjang }} / {{ $tahunPelajaran }}.</p>
        <a href="{{ route('ijazah.komponen.create') }}?jenjang={{ $jenjang }}&tahun_pelajaran={{ $tahunPelajaran }}"
          class="btn btn-primary btn-sm">
          <i class="icon-base ri ri-add-line me-1"></i>Tambah Komponen Pertama
        </a>
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-bordered table-hover mb-0">
          <thead>
            <tr>
              <th width="50" class="text-center">No</th>
              <th>Kode</th>
              <th>Label</th>
              <th width="100" class="text-center">Bobot (%)</th>
              <th>Keterangan</th>
              <th width="60" class="text-center">Urutan</th>
              <th width="100" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($komponens as $i => $komp)
              <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td>
                  <code>{{ $komp->kode }}</code>
                  @if ($komp->subKomponen->isNotEmpty())
                    <span class="badge bg-label-info ms-1" style="font-size:9px">
                      {{ $komp->subKomponen->count() }} sub
                    </span>
                  @endif
                </td>
                <td>{{ $komp->label }}</td>
                <td class="text-center">
                  <span class="badge bg-label-primary">{{ $komp->bobot }}%</span>
                </td>
                <td class="text-muted small">{{ $komp->keterangan ?? '-' }}</td>
                <td class="text-center">{{ $komp->urutan }}</td>
                <td>
                  <div class="d-flex gap-1 justify-content-center">
                    <a href="{{ route('ijazah.komponen.sub.index', $komp) }}"
                      class="btn btn-sm btn-icon btn-text-info rounded-pill" title="Sub-komponen">
                      <i class="icon-base ri ri-list-unordered icon-18px"></i>
                    </a>
                    <a href="{{ route('ijazah.komponen.edit', $komp) }}"
                      class="btn btn-sm btn-icon btn-text-primary rounded-pill" title="Edit">
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
        </table>
      </div>
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
          text: `Komponen "${btn.dataset.label}" akan dihapus.`,
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

        const res = await fetch(`/ijazah/komponen/${btn.dataset.id}`, {
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
