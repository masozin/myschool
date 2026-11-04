<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningGrade extends Model
{
  protected $fillable = ['name', 'level'];
  public function subjects()
  {
    return $this->hasMany(LearningSubject::class);
  }
}
