<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningTopic extends Model
{
  protected $table = 'learning_topics';
  protected $fillable = ['mapel_master_id', 'kelas', 'sekolah_id', 'ptk_id', 'title', 'description', 'order', 'is_active'];

  public function mapelMaster(): BelongsTo
  {
    return $this->belongsTo(MapelMaster::class, 'mapel_master_id');
  }

  public function slides()
  {
    // Sebutkan 'topic_id' sebagai parameter ke-2
    return $this->hasMany(LearningSlide::class, 'topic_id')->orderBy('slide_number');
  }

  public function quiz()
  {
    // Sebutkan 'topic_id' sebagai parameter ke-2
    return $this->hasOne(LearningQuiz::class, 'topic_id');
  }
}
