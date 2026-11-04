<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningStudentQuizResult extends Model
{
  protected $fillable = ['user_id', 'quiz_id', 'score', 'total_correct', 'completed_at'];
  public function user()
  {
    return $this->belongsTo(User::class);
  }
  public function quiz()
  {
    return $this->belongsTo(LearningQuiz::class);
  }
}
