<?php

namespace Database\Seeders;

use App\Models\LearningGrade;
use App\Models\LearningOption;
use App\Models\LearningQuestion;
use App\Models\LearningQuiz;
use App\Models\LearningSlide;
use App\Models\LearningSubject;
use App\Models\LearningTopic;
use Illuminate\Database\Seeder;

class LearningSeeder extends Seeder
{
  public function run(): void
  {
    // 1. Buat Grade
    $grade5 = LearningGrade::create([
      'name' => 'Kelas 5 SD',
      'level' => 5,
    ]);

    // 2. Buat Subject
    $ipas = LearningSubject::create([
      'grade_id' => $grade5->id,
      'name' => 'IPAS',
      'icon' => 'bx-atom',
    ]);

    // -------------------------------------------------------------
    // BAB 1: SISTEM PERNAPASAN MANUSIA
    // -------------------------------------------------------------
    $topicPernapasan = LearningTopic::create([
      'subject_id' => $ipas->id,
      'title' => 'Sistem Pernapasan Manusia',
      'description' => 'Mengenal organ pernapasan dan cara kerjanya.',
      'order' => 1,
      'is_active' => true,
    ]);

    // Slide 1 - Pengenalan
    LearningSlide::create([
      'topic_id' => $topicPernapasan->id,
      'slide_number' => 1,
      'title' => 'Pengenalan Pernapasan',
      'type' => 'text',
      'content_text' => '<p class="fs-5">Bernapas adalah proses menghirup gas <b class="text-success">Oksigen (O₂)</b> dan menghembuskan gas <b class="text-danger">Karbondioksida (CO₂)</b>.</p>
<div class="alert alert-info">Organ utama pernapasan kita adalah Paru-Paru!</div>',
      'media_url' => null,
    ]);

    // Slide 2 - Model 3D dengan Hotspot
    LearningSlide::create([
      'topic_id' => $topicPernapasan->id,
      'slide_number' => 2,
      'title' => 'Organ Paru-Paru 3D',
      'type' => '3d',
      'content_text' => '<p>Klik titik nomor pada model 3D di samping untuk melihat bagian-bagian organ paru-paru!</p>',
      'media_url' => 'https://modelviewer.dev/shared-assets/models/Astronaut.glb',
      'hotspots_data' => [
        [
          'slot' => 'hotspot-1',
          'position' => '0m 1.5m 0.2m',
          'title' => 'Hidung & Tenggorokan',
          'description' => 'Pintu masuknya udara pernapasan.'
        ],
        [
          'slot' => 'hotspot-2',
          'position' => '0m 0.8m 0.2m',
          'title' => 'Alveolus',
          'description' => 'Tempat bertukarnya O2 dan CO2.'
        ]
      ],
    ]);

    // Kuis Bab Pernapasan
    $quizPernapasan = LearningQuiz::create([
      'topic_id' => $topicPernapasan->id,
      'title' => 'Kuis Sistem Pernapasan',
      'passing_grade' => 75,
    ]);

    $q1 = LearningQuestion::create([
      'quiz_id' => $quizPernapasan->id,
      'question_text' => 'Gas apa yang dihirup manusia saat bernapas?',
    ]);

    LearningOption::create(['question_id' => $q1->id, 'option_text' => 'Oksigen (O2)', 'is_correct' => true]);
    LearningOption::create(['question_id' => $q1->id, 'option_text' => 'Karbondioksida (CO2)', 'is_correct' => false]);
    LearningOption::create(['question_id' => $q1->id, 'option_text' => 'Nitrogen', 'is_correct' => false]);


    // -------------------------------------------------------------
    // BAB 2: SISTEM PENCERNAAN MANUSIA
    // -------------------------------------------------------------
    $topicPencernaan = LearningTopic::create([
      'subject_id' => $ipas->id,
      'title' => 'Sistem Pencernaan Manusia',
      'description' => 'Proses pengolahan makanan dalam tubuh.',
      'order' => 2,
      'is_active' => true,
    ]);

    // Slide 1 - Pencernaan
    LearningSlide::create([
      'topic_id' => $topicPencernaan->id,
      'slide_number' => 1,
      'title' => 'Alur Organ Pencernaan',
      'type' => 'text',
      'content_text' => '<p class="fs-5">Makanan bergerak melalui: <b>Mulut ➔ Kerongkongan ➔ Lambung ➔ Usus Halus ➔ Usus Besar ➔ Anus</b>.</p>',
    ]);
  }
}
