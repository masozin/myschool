<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningSubject extends Model
{
  protected $fillable = ['grade_id', 'name', 'icon'];

  public function grade()
  {
    return $this->belongsTo(LearningGrade::class, 'grade_id');
  }
  public function topics()
  {
    return $this->hasMany(LearningTopic::class, 'subject_id');
  }
}
