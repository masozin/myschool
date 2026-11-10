@extends('layouts/layoutMaster')

@section('title', 'Pengampu — ' . $rombel->nama_rombel)

@section('content')

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if (session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <div class="card mb-4">
    <div class="card-body py-4 d-flex align-items-center gap-4 flex-wrap">
      <div class="avatar avatar-xl flex-shrink-0">
        <span class="avatar-initial rounded-circle bg-label-primary">
          <i class="icon-base ri ri-user-star-line ri-28px"></i>
        </span>
      </div>
      <div class="flex-grow-1">
        <h4 class="mb-1">Pengampu — {{ $rombel->nama_rombel }}</h4>
        <div class="text-muted small">
          Tetapkan guru untuk setiap mata pelajaran rombel ini
          @if ($semester)
            <span class="mx-1">•</span> Semester {{ $semester->semester }} {{ $semester->tahun_pelajaran }}
          @endif
        </div>
      </div>
      <a href="{{ route('jadwal-pelajaran.index') }}" class="btn btn-outline-secondary">
        <i class="ri ri-arrow-left-line me-1"></i> Kembali
      </a>
      <a href="{{ route('jadwal-pelajaran.editor', $rombel) }}" class="btn btn-primary">
        <i class="ri ri-calendar-2-line me-1"></i> Ke Editor Jadwal
      </a>
    </div>
  </div>

  <form method="POST" action="{{ route('jadwal-pelajaran.pengampu.simpan', $rombel) }}">
    @csrf
    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Mata Pelajaran</th>
              <th style="width: 380px;">Guru Pengampu</th>
              <th class="text-center" style="width: 130px;">Sudah Terplot</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($mappings as $m)
              @php
                $p = $terpilih[$m->id] ?? null;
              @endphp
              <tr>
                <td>
                  <div class="fw-semibold">{{ $m->masterMapel->nama ?? '-' }}</div>
                  <small class="text-muted">{{ $m->kelompokTampil()?->nama ?? '' }}</small>
                </td>
                <td>
                  <select name="pengampu[{{ $m->id }}]" class="form-select">
                    <option value="">— Belum ditetapkan —</option>
                    @foreach ($gurus as $g)
                      <option value="{{ $g->id }}" {{ $p && $p->ptk_id === $g->id ? 'selected' : '' }}>
                        {{ $g->nama_ptk }}{{ $g->jabatan ? ' (' . $g->jabatan . ')' : '' }}
                      </option>
                    @endforeach
                  </select>
                </td>
                <td class="text-center">
                  {{ $p ? $terplot[$p->id] ?? 0 : 0 }} jam
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="3" class="text-center py-4 text-muted">
                  <i class="ri ri-inbox-line me-1"></i> Tidak ada mapel yang berlaku untuk tingkat rombel ini pada
                  semester yang sedang dibuka. Periksa Mapping Mapel.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="card-body border-top d-flex align-items-center gap-3 flex-wrap">
        <button type="submit" class="btn btn-primary">
          <i class="ri ri-save-line me-1"></i> Simpan Pengampu
        </button>
        <small class="text-muted">Mengosongkan pilihan guru akan menghapus pengampu itu beserta kotak jadwal yang
          sudah terisi untuknya.</small>
      </div>
    </div>
  </form>

@endsection
