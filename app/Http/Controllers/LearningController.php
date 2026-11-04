<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LearningTopic;
use App\Models\LearningSlide;

class LearningController extends Controller
{
  /**
   * Menampilkan Player Pembelajaran berdasarkan ID Topic / Bab
   */
  public function showTopic($id)
  {
    // Ambil topic beserta relasi subject, grade, slides (diurutkan), dan quiz
    $topic = LearningTopic::with([
      'subject.grade',
      'slides' => function ($query) {
        $query->orderBy('slide_number', 'asc');
      },
      'quiz'
    ])->findOrFail($id);

    return view('learning.show', compact('topic'));
  }
}
