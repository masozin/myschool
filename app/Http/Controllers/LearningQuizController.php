<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LearningTopic;
use App\Models\LearningQuiz;
use App\Models\LearningQuestion;
use App\Models\LearningOption;
use App\Models\LearningStudentQuizResult;
use Illuminate\Support\Facades\Auth;

class LearningQuizController extends Controller
{
  /**
   * Tampilkan Halaman Pengerjaan Kuis
   */
  public function show($topicId)
  {
    $topic = LearningTopic::with(['quiz.questions.options'])->findOrFail($topicId);

    if (!$topic->quiz) {
      return redirect()->back()->with('error', 'Kuis belum tersedia untuk topik ini.');
    }

    return view('learning.quiz.show', compact('topic'));
  }

  /**
   * Proses & Hitung Hasil Kuis
   */
  public function submit(Request $request, $quizId)
  {
    $quiz = LearningQuiz::with('questions.options')->findOrFail($quizId);
    $userAnswers = $request->input('answers', []);

    $totalQuestions = $quiz->questions->count();
    $correctCount = 0;

    foreach ($quiz->questions as $question) {
      $selectedOptionId = $userAnswers[$question->id] ?? null;

      if ($selectedOptionId) {
        $correctOption = $question->options->where('is_correct', true)->first();
        if ($correctOption && $correctOption->id == $selectedOptionId) {
          $correctCount++;
        }
      }
    }

    // Hitung Skor (Skala 100)
    $score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100) : 0;

    // Simpan Hasil ke Database (Gunakan ID user login atau fallback ID 1 untuk testing)
    $userId = Auth::id() ?? 1;

    $result = LearningStudentQuizResult::create([
      'user_id'       => $userId,
      'quiz_id'       => $quiz->id,
      'score'         => $score,
      'total_correct' => $correctCount,
      'completed_at'  => now(),
    ]);

    return view('learning.quiz.result', compact('quiz', 'score', 'correctCount', 'totalQuestions', 'result'));
  }
}
