@extends('layouts.learningLayout')

@section('content')
  <div class="container py-4">
    <div class="card shadow-sm border-0">
      <div class="card-header bg-primary text-white py-3">
        <h4 class="mb-0 text-white fw-bold"><i class="bx bx-edit me-2"></i>{{ $topic->quiz->title }}</h4>
        <small class="text-white-50">Jawablah pertanyaan berikut dengan benar!</small>
      </div>

      <div class="card-body p-4">
        <form action="{{ route('learning.quiz.submit', $topic->quiz->id) }}" method="POST">
          @csrf

          @foreach ($topic->quiz->questions as $index => $question)
            <div class="mb-4 p-3 border rounded bg-light">
              <h5 class="fw-bold text-dark">
                <span class="badge bg-primary me-2">{{ $index + 1 }}</span> {{ $question->question_text }}
              </h5>

              @if ($question->image_url)
                <img src="{{ asset($question->image_url) }}" class="img-fluid rounded mb-3 max-vh-30">
              @endif

              <div class="mt-3">
                @foreach ($question->options as $option)
                  <div class="form-check custom-option mb-2 p-2 bg-white rounded border">
                    <input class="form-check-input ms-1" type="radio" name="answers[{{ $question->id }}]"
                      id="opt-{{ $option->id }}" value="{{ $option->id }}" required>
                    <label class="form-check-label ms-2 w-100 cursor-pointer" for="opt-{{ $option->id }}">
                      {{ $option->option_text }}
                    </label>
                  </div>
                @endforeach
              </div>
            </div>
          @endforeach

          <div class="d-flex justify-content-between align-items-center mt-4">
            <a href="{{ route('learning.topic.show', $topic->id) }}" class="btn btn-outline-secondary">
              <i class="bx bx-arrow-back me-1"></i> Kembali ke Slide
            </a>
            <button type="submit" class="btn btn-success btn-lg">
              Kirim Jawaban <i class="bx bx-paper-plane ms-1"></i>
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
@endsection
