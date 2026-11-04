@php $data = $slide->content_data ?? []; @endphp
<div class="anim-up" id="quiz-slide-{{ $slide->id }}">
  <h3 class="fw-bold mb-3">{{ $slide->title }}</h3>

  @if (($data['quiz_mode'] ?? 'pilihan_ganda') === 'pilihan_ganda')
    <p class="fs-5 fw-semibold mb-3">{{ $data['question'] ?? '' }}</p>
    <div class="d-flex flex-column gap-2" id="quiz-options-{{ $slide->id }}">
      @foreach ($data['options'] ?? [] as $opt)
        <button type="button" class="btn btn-outline-primary text-start quiz-option-btn"
          data-correct="{{ $opt['correct'] ? '1' : '0' }}" onclick="checkQuizAnswer(this, {{ $slide->id }}, false)">
          {{ $opt['text'] }}
        </button>
      @endforeach
    </div>
  @else
    <div class="d-flex flex-column gap-3" id="quiz-options-{{ $slide->id }}">
      @foreach ($data['statements'] ?? [] as $st)
        <div class="p-3 border rounded d-flex justify-content-between align-items-center flex-wrap gap-2"
          data-answer="{{ $st['answer'] ? '1' : '0' }}">
          <span class="fw-semibold">{{ $st['text'] }}</span>
          <div class="btn-group">
            <button type="button" class="btn btn-sm btn-outline-success"
              onclick="checkQuizAnswer(this, {{ $slide->id }}, true, '1')">Benar</button>
            <button type="button" class="btn btn-sm btn-outline-danger"
              onclick="checkQuizAnswer(this, {{ $slide->id }}, true, '0')">Salah</button>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  <div id="quiz-feedback-{{ $slide->id }}" class="alert d-none mt-3"></div>
</div>

<script>
  function checkQuizAnswer(btn, slideId, isStatement, chosenValue) {
    const feedback = document.getElementById(`quiz-feedback-${slideId}`);
    let isCorrect;

    if (isStatement) {
      const wrapper = btn.closest('[data-answer]');
      isCorrect = (chosenValue === wrapper.dataset.answer);
      wrapper.classList.add(isCorrect ? 'border-success' : 'border-danger');
      wrapper.querySelectorAll('button').forEach(b => b.disabled = true);
    } else {
      isCorrect = btn.dataset.correct === '1';
      document.querySelectorAll(`#quiz-options-${slideId} .quiz-option-btn`).forEach(b => {
        b.disabled = true;
        b.classList.remove('btn-outline-primary');
        if (b.dataset.correct === '1') b.classList.add('btn-success', 'text-white');
        else if (b === btn) b.classList.add('btn-danger', 'text-white');
      });
    }

    feedback.classList.remove('d-none', 'alert-success', 'alert-danger');
    feedback.classList.add(isCorrect ? 'alert-success' : 'alert-danger');
    feedback.innerHTML = (isCorrect ? '✅ Benar! ' : '❌ Kurang tepat. ') + @json($data['explanation'] ?? '');
  }
</script>
