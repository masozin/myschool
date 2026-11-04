<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningQuiz extends Model
{
  protected $fillable = ['topic_id', 'title', 'passing_grade'];

  public function topic()
  {
    return $this->belongsTo(LearningTopic::class, 'topic_id');
  }
  public function questions()
  {
    return $this->hasMany(LearningQuestion::class, 'quiz_id');
  }
}
