<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningQuestion extends Model
{
  protected $fillable = ['quiz_id', 'question_text', 'image_url'];

  public function quiz()
  {
    return $this->belongsTo(LearningQuiz::class, 'quiz_id');
  }
  public function options()
  {
    return $this->hasMany(LearningOption::class, 'question_id');
  }
}
