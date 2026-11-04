@php
  use Illuminate\Support\Str;
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Katalog Materi Pembelajaran')

@section('content')
  <div class="container-xxl flex-grow-1 container-p-y">

    <!-- HEADER DASHBOARD -->
    <div class="card mb-4 overflow-hidden border-0" style="background: linear-gradient(135deg, #696cff 0%, #8f92ff 100%);">
      <div class="card-body p-4 p-md-5 position-relative">
        <div class="row align-items-center">
          <div class="col-lg-8">
            <span class="badge bg-white text-primary fw-semibold mb-2 px-3 py-2 rounded-pill">
              <i class="ri ri-sparkling-2-line me-1"></i> Belajar Interaktif 3D
            </span>
            <h3 class="fw-bold text-white mb-2">Modul Pembelajaran Interaktif</h3>
            <p class="text-white opacity-75 mb-md-0 mb-3" style="max-width: 520px;">
              Kelola dan jelajahi koleksi materi pembelajaranmu. Setiap modul dirancang untuk
              memberikan pengalaman belajar yang imersif dan menyenangkan.
            </p>
          </div>
          <div class="col-lg-4 text-lg-end">
            <a href="{{ route('learning.topics.create') }}" class="btn btn-white fw-semibold shadow-sm px-4 py-2">
              <i class="ri ri-add-line me-1 align-middle"></i> Buat Modul Baru
            </a>
          </div>
        </div>
      </div>
    </div>

    @if (session('success'))
      <div class="alert alert-success d-flex align-items-center alert-dismissible fade show" role="alert">
        <i class="ri ri-checkbox-circle-fill me-2 fs-5"></i>
        <div class="flex-grow-1">{{ session('success') }}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <!-- RINGKASAN CEPAT -->
    @if ($topics->count() > 0)
      <div class="row g-3 mb-4">
        <div class="col-sm-4">
          <div class="card border h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
              <div class="avatar avatar-md flex-shrink-0">
                <span class="avatar-initial rounded-circle bg-label-primary">
                  <i class="ri ri-book-2-line fs-4"></i>
                </span>
              </div>
              <div>
                <h5 class="mb-0 fw-bold">{{ $topics->count() }}</h5>
                <small class="text-muted">Total Modul</small>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="card border h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
              <div class="avatar avatar-md flex-shrink-0">
                <span class="avatar-initial rounded-circle bg-label-success">
                  <i class="ri ri-checkbox-circle-line fs-4"></i>
                </span>
              </div>
              <div>
                <h5 class="mb-0 fw-bold">{{ $topics->where('is_active', true)->count() }}</h5>
                <small class="text-muted">Modul Aktif</small>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-4">
          <div class="card border h-100">
            <div class="card-body d-flex align-items-center gap-3 py-3">
              <div class="avatar avatar-md flex-shrink-0">
                <span class="avatar-initial rounded-circle bg-label-warning">
                  <i class="ri ri-draft-line fs-4"></i>
                </span>
              </div>
              <div>
                <h5 class="mb-0 fw-bold">{{ $topics->where('is_active', false)->count() }}</h5>
                <small class="text-muted">Draft</small>
              </div>
            </div>
          </div>
        </div>
      </div>
    @endif

    <!-- DAFTAR CARD MODUL -->
    <div class="row g-4">
      @forelse($topics as $topic)
        <div class="col-md-6 col-lg-4">
          <div class="card h-100 border module-card">
            <div class="card-body d-flex flex-column">

              <div class="flex-grow-1">
                <!-- Badge Kelas & Mapel -->
                <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
                  <span class="badge bg-label-primary">
                    <i class="bx bx-book me-1"></i>
                    {{ $topic->mapelMaster->nama ?? 'Mapel dihapus' }} - Kelas {{ $topic->kelas }}
                  </span>
                  <span class="badge {{ $topic->is_active ? 'bg-label-success' : 'bg-label-secondary' }} flex-shrink-0">
                    <i
                      class="ri {{ $topic->is_active ? 'ri-checkbox-circle-line' : 'ri-draft-line' }} me-1 align-middle"></i>
                    {{ $topic->is_active ? 'Aktif' : 'Draft' }}
                  </span>
                </div>

                <!-- Judul & Deskripsi -->
                <h5 class="card-title fw-bold mb-2">{{ $topic->title }}</h5>
                <p class="card-text text-muted small mb-0">
                  {{ Str::limit($topic->description ?? 'Tidak ada deskripsi singkat.', 100) }}
                </p>
              </div>

              <div class="mt-4 pt-3 border-top">
                <!-- Info Slide & Kuis -->
                <div class="d-flex justify-content-between align-items-center mb-3 text-muted small">
                  <span class="d-flex align-items-center">
                    <i class="ri ri-slideshow-3-line me-1 text-info fs-6"></i>
                    {{ $topic->slides->count() }} Slide Interaktif
                  </span>
                  <span class="d-flex align-items-center">
                    <i class="ri ri-question-answer-line me-1 text-warning fs-6"></i>
                    {{ $topic->quiz ? 'Ada Kuis' : 'Tanpa Kuis' }}
                  </span>
                </div>

                <!-- Tombol Aksi -->
                <div class="d-flex align-items-center gap-2">
                  @if ($topic->is_active)
                    <a href="{{ route('learning.topic.show', $topic->id) }}" class="btn btn-primary flex-grow-1"
                      data-bs-toggle="tooltip" data-bs-placement="top" title="Mulai Belajar">
                      <i class="ri ri-play-circle-line align-middle"></i>
                    </a>
                    <a href="{{ route('learning.topics.edit', $topic->id) }}" class="btn btn-label-secondary"
                      data-bs-toggle="tooltip" data-bs-placement="top" title="Edit Modul">
                      <i class="ri ri-edit-line align-middle"></i>
                    </a>
                    <form action="{{ route('learning.topics.destroy', $topic->id) }}" method="POST"
                      onsubmit="return confirm('Yakin ingin menghapus modul \'{{ $topic->title }}\' beserta semua slide di dalamnya? Tindakan ini tidak bisa dibatalkan.');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-outline-danger" data-bs-toggle="tooltip"
                        data-bs-placement="top" title="Hapus Modul">
                        <i class="ri ri-delete-bin-line align-middle"></i>
                      </button>
                    </form>
                  @else
                    <a href="{{ route('learning.topics.edit', $topic->id) }}" class="btn btn-warning flex-grow-1"
                      data-bs-toggle="tooltip" data-bs-placement="top" title="Lanjutkan Mengisi (Draft)">
                      <i class="ri ri-edit-2-line align-middle"></i>
                    </a>
                    <form action="{{ route('learning.topics.destroy', $topic->id) }}" method="POST"
                      onsubmit="return confirm('Yakin ingin menghapus modul \'{{ $topic->title }}\' beserta semua slide di dalamnya? Tindakan ini tidak bisa dibatalkan.');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-outline-danger" data-bs-toggle="tooltip"
                        data-bs-placement="top" title="Hapus Modul">
                        <i class="ri ri-delete-bin-line align-middle"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </div>

            </div>
          </div>
        </div>
      @empty
        <div class="col-12">
          <div class="card border">
            <div class="card-body text-center py-5">
              <div class="avatar avatar-xl mx-auto mb-3">
                <span class="avatar-initial rounded-circle bg-label-primary">
                  <i class="ri ri-folder-open-line fs-1"></i>
                </span>
              </div>
              <h4 class="fw-bold mb-2">Belum Ada Modul Pembelajaran</h4>
              <p class="text-muted mb-4">Klik tombol di bawah untuk membuat modul pembelajaran interaktif pertama kamu!
              </p>
              <a href="{{ route('learning.topics.create') }}" class="btn btn-primary">
                <i class="ri ri-add-line me-1 align-middle"></i> Buat Modul Sekarang
              </a>
            </div>
          </div>
        </div>
      @endforelse
    </div>

  </div>

  @push('page-style')
    <style>
      .module-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }

      .module-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 0.5rem 1.5rem rgba(67, 89, 113, 0.12) !important;
      }

      .btn-white {
        background-color: #fff;
        color: #696cff;
        border: none;
      }

      .btn-white:hover {
        background-color: #f5f5f9;
        color: #696cff;
      }
    </style>
  @endpush

  @push('page-script')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(function(el) {
          new bootstrap.Tooltip(el);
        });
      });
    </script>
  @endpush
@endsection
