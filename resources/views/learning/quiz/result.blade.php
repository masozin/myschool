@extends('layouts.learningLayout')

@section('content')
  <div class="container py-5 text-center">
    <div class="card shadow-lg border-0 p-5 mx-auto" style="max-width: 600px;">

      @if ($score >= $quiz->passing_grade)
        <div class="display-1 text-success mb-3"><i class="bx bx-trophy"></i></div>
        <h2 class="fw-bold text-success">Selamat! Kamu Lulus! 🎉</h2>
      @else
        <div class="display-1 text-warning mb-3"><i class="bx bx-refresh"></i></div>
        <h2 class="fw-bold text-warning">Jangan Patah Semangat! 💪</h2>
      @endif

      <div class="my-4">
        <h1 class="display-2 fw-bold text-primary">{{ $score }}</h1>
        <p class="text-muted fs-5">Nilai Kelulusan minimal: <b>{{ $quiz->passing_grade }}</b></p>
        <p class="fs-6">Kamu menjawab benar <b>{{ $correctCount }}</b> dari <b>{{ $totalQuestions }}</b> soal.</p>
      </div>

      <div class="d-flex justify-content-center gap-3 mt-3">
        <a href="{{ route('learning.topic.show', $quiz->topic_id) }}" class="btn btn-outline-primary">
          <i class="bx bx-book-open me-1"></i> Pelajari Materi Lagi
        </a>
        <a href="{{ url('/dashboard') }}" class="btn btn-primary">
          Selesai & Ke Dashboard <i class="bx bx-home ms-1"></i>
        </a>
      </div>

    </div>
  </div>
@endsection
