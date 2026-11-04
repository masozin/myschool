<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LearningSlide extends Model
{
  protected $fillable = [
    'topic_id',
    'slide_number',
    'title',
    'type',
    'content_data',
    'content_text',
    'media_url'
  ];
  protected $casts = [
    'content_data' => 'array',
  ]; // Otomatis mengonversi JSON ke Array PHP

  public function topic()
  {
    // Sebutkan 'topic_id'
    return $this->belongsTo(LearningTopic::class, 'topic_id');
  }
}
