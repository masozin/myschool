<?php

namespace App\Exports;

use App\Models\LearningModule;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Settings;

class LearningModuleDocxExporter
{
  private PhpWord $phpWord;

  public function __construct(private LearningModule $modul)
  {
    Settings::setOutputEscapingEnabled(true);

    $this->phpWord = new PhpWord();
    $this->setupStyles();
  }

    // ============================================================
    // XML / STRING SANITIZER
    // ============================================================

  /**
   * Membersihkan string agar aman untuk Word / OpenXML.
   */
  private function sanitizeText(mixed $value): string
  {
    if ($value === null) {
      return '';
    }

    // Jika bukan string, ubah menjadi string
    if (!is_string($value)) {
      if (is_scalar($value)) {
        $value = (string) $value;
      } else {
        return '';
      }
    }

    // --------------------------------------------------------
    // 1. Pastikan encoding UTF-8 valid
    // --------------------------------------------------------
    if (!mb_check_encoding($value, 'UTF-8')) {
      $converted = @mb_convert_encoding($value, 'UTF-8', 'UTF-8');
      if ($converted !== false) {
        $value = $converted;
      } else {
        $value = @iconv('UTF-8', 'UTF-8//IGNORE', $value) ?: '';
      }
    }

    // --------------------------------------------------------
    // 2. Hapus karakter kontrol yang dilarang XML 1.0
    // --------------------------------------------------------
    $value = preg_replace(
      '/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u',
      '',
      $value
    ) ?? '';

    // --------------------------------------------------------
    // 3. Hapus BOM & Zero-Width Space
    // --------------------------------------------------------
    $value = str_replace(
      [
        "\xEF\xBB\xBF",
        "\u{200B}", // Zero Width Space
        "\u{200C}", // Zero Width Non-Joiner
        "\u{200D}", // Zero Width Joiner
        "\u{FEFF}", // Zero Width No-Break Space
      ],
      '',
      $value
    );

    // --------------------------------------------------------
    // 4. Normalisasi Line Endings
    // --------------------------------------------------------
    $value = str_replace(["\r\n", "\r"], "\n", $value);

    return trim($value);
  }

  /**
   * Sanitasi array secara rekursif.
   */
  private function sanitizeArray(mixed $data): mixed
  {
    if (is_array($data)) {
      $result = [];

      foreach ($data as $key => $value) {
        $result[$key] = $this->sanitizeArray($value);
      }

      return $result;
    }

    if ($data === null) {
      return null;
    }

    return $this->sanitizeText($data);
  }

  /**
   * Sanitasi value yang berasal dari model.
   */
  private function safe(mixed $value): string
  {
    return $this->sanitizeText($value);
  }

  /**
   * Sanitasi array/list dan memastikan hasil berupa array.
   */
  private function safeList(mixed $value): array
  {
    $value = $this->sanitizeArray($value);

    if (!is_array($value)) {
      return [];
    }

    return array_values(
      array_filter(
        $value,
        function ($item) {
          return is_string($item)
            ? trim($item) !== ''
            : !empty($item);
        }
      )
    );
  }

  // ============================================================
  // EXPORT
  // ============================================================

  public function export(): string
  {
    $this->addCoverPage();
    $this->addContentSection();

    $fileName = 'modul-ajar-'
      . $this->safe($this->modul->id)
      . '-'
      . now()->format('YmdHis')
      . '.docx';

    $path = storage_path(
      'app/public/exports/' . $fileName
    );

    if (!is_dir(dirname($path))) {
      mkdir(dirname($path), 0755, true);
    }

    $writer = IOFactory::createWriter(
      $this->phpWord,
      'Word2007'
    );

    $writer->save($path);

    return 'exports/' . $fileName;
  }

  // ============================================================
  // PHPWORD STYLE
  // ============================================================

  private function setupStyles(): void
  {
    $this->phpWord->setDefaultFontName('Calibri');
    $this->phpWord->setDefaultFontSize(11);

    $this->phpWord->addTitleStyle(
      1,
      [
        'bold' => true,
        'size' => 14,
        'color' => '1F4E79',
      ],
      [
        'spaceBefore' => 240,
        'spaceAfter' => 120,
      ]
    );

    $this->phpWord->addTitleStyle(
      2,
      [
        'bold' => true,
        'size' => 12,
        'color' => '2E74B5',
      ],
      [
        'spaceBefore' => 180,
        'spaceAfter' => 100,
      ]
    );

    $this->phpWord->addFontStyle(
      'label',
      [
        'bold' => true,
        'size' => 11,
      ]
    );

    $this->phpWord->addFontStyle(
      'muted',
      [
        'size' => 11,
        'color' => '444444',
      ]
    );

    $this->phpWord->addTableStyle(
      'IdentityTable',
      [
        'borderSize' => 6,
        'borderColor' => 'CCCCCC',
        'cellMargin' => 80,
      ]
    );
  }

  // ============================================================
  // COVER PAGE
  // ============================================================

  private function addCoverPage(): void
  {
    $section = $this->phpWord->addSection([
      'marginTop' => 1600,
      'marginBottom' => 1600,
    ]);

    $sekolah = $this->modul->sekolah;
    $ptk = $this->modul->ptk;
    $mapel = $this->modul->mapel;

    $section->addTextBreak(6);

    $section->addText(
      'MODUL AJAR',
      [
        'bold' => true,
        'size' => 20,
        'color' => '1F4E79',
      ],
      [
        'alignment' => Jc::CENTER,
      ]
    );

    $section->addText(
      'RENCANA PEMBELAJARAN MENDALAM',
      [
        'bold' => true,
        'size' => 14,
        'color' => '2E74B5',
      ],
      [
        'alignment' => Jc::CENTER,
        'spaceAfter' => 400,
      ]
    );

    $section->addTextBreak(2);

    $section->addText(
      strtoupper(
        $this->safe($mapel->nama ?? '-')
      ),
      [
        'bold' => true,
        'size' => 16,
      ],
      [
        'alignment' => Jc::CENTER,
      ]
    );

    $section->addText(
      'Kelas '
        . $this->safe($this->modul->kelas ?? '-')
        . ' — Fase '
        . $this->safe($this->modul->fase ?? '-'),
      [
        'size' => 13,
      ],
      [
        'alignment' => Jc::CENTER,
        'spaceAfter' => 100,
      ]
    );

    if ($this->safe($this->modul->bab)) {
      $section->addText(
        $this->safe($this->modul->bab),
        [
          'italic' => true,
          'size' => 12,
        ],
        [
          'alignment' => Jc::CENTER,
        ]
      );
    }

    $section->addText(
      $this->safe($this->modul->materi_pokok),
      [
        'italic' => true,
        'size' => 12,
      ],
      [
        'alignment' => Jc::CENTER,
        'spaceAfter' => 100,
      ]
    );

    $section->addTextBreak(8);

    $table = $section->addTable([
      'alignment' => Jc::CENTER,
      'width' => 60 * 50,
      'unit' => 'pct',
    ]);

    $rows = [
      [
        'Disusun oleh',
        $ptk->nama_ptk ?? '-',
      ],
      [
        'Satuan Pendidikan',
        $sekolah->nama_sekolah
          ?? $sekolah->nama
          ?? '-',
      ],
      [
        'Tahun Ajaran',
        now()->format('Y')
          . '/'
          . (now()->format('Y') + 1),
      ],
    ];

    foreach ($rows as $row) {
      $table->addRow();

      $table->addCell(3000)->addText(
        $this->safe($row[0]),
        ['size' => 11],
        ['alignment' => Jc::CENTER]
      );

      $table->addCell(3000)->addText(
        $this->safe($row[1]),
        [
          'bold' => true,
          'size' => 11,
        ],
        [
          'alignment' => Jc::CENTER,
        ]
      );
    }

    $section->addPageBreak();
  }

  // ============================================================
  // CONTENT SECTION
  // ============================================================

  private function addContentSection(): void
  {
    $section = $this->phpWord->addSection([
      'marginTop' => 1000,
      'marginBottom' => 1000,
      'headerHeight' => 600,
      'footerHeight' => 600,
    ]);

    $footer = $section->addFooter();

    $footer->addPreserveText(
      'Halaman {PAGE} dari {NUMPAGES}',
      [
        'size' => 9,
        'color' => '888888',
      ],
      [
        'alignment' => Jc::CENTER,
      ]
    );

    $this->addInformasiUmum($section);
    $this->addIdentifikasiMurid($section);
    $this->addMateriPelajaran($section);
    $this->addDimensiProfil($section);
    $this->addDesainPembelajaran($section);
    $this->addBlokPembelajaran($section);
    $this->addAsesmen($section);
    $this->addPengayaanRemedial($section);
    $this->addRefleksi($section);
  }

  // ============================================================
  // INFORMASI UMUM
  // ============================================================

  private function addInformasiUmum($section): void
  {
    $section->addTitle(
      $this->safe('INFORMASI UMUM'),
      1
    );

    $section->addTitle(
      $this->safe('A. Identitas Modul'),
      2
    );

    $table = $section->addTable('IdentityTable');

    $rows = [
      [
        'Nama Penyusun',
        $this->modul->ptk->nama_ptk ?? '-',
      ],
      [
        'Satuan Pendidikan',
        $this->modul->sekolah->nama_sekolah
          ?? $this->modul->sekolah->nama
          ?? '-',
      ],
      [
        'Tahun Ajaran',
        now()->format('Y')
          . '/'
          . (now()->format('Y') + 1),
      ],
      [
        'Mata Pelajaran',
        $this->modul->mapel->nama ?? '-',
      ],
      [
        'Kelas/Fase',
        ($this->modul->kelas ?? '-')
          . ' / '
          . ($this->modul->fase ?? '-'),
      ],
      [
        'Bab',
        $this->modul->bab ?? '-',
      ],
      [
        'Topik',
        $this->modul->materi_pokok ?? '-',
      ],
      [
        'Alokasi Waktu',
        ($this->modul->jumlah_jp ?? 0)
          . ' JP ('
          . ($this->modul->jumlah_pertemuan ?? 0)
          . ' pertemuan)',
      ],
    ];

    foreach ($rows as $row) {
      $table->addRow();

      $table->addCell(3000)->addText(
        $this->safe($row[0]),
        ['bold' => true]
      );

      $table->addCell(6000)->addText(
        $this->safe($row[1])
      );
    }
  }

  // ============================================================
  // IDENTIFIKASI MURID
  // ============================================================

  private function addIdentifikasiMurid($section): void
  {
    if (!$this->modul->identifikasi_murid) {
      return;
    }

    $section->addTitle(
      $this->safe('B. Identifikasi Murid'),
      2
    );

    $data = $this->sanitizeArray(
      $this->modul->identifikasi_murid
    );

    $labels = [
      'pengetahuan_awal' => 'Pengetahuan Awal',
      'minat' => 'Minat',
      'kebutuhan_belajar' => 'Kebutuhan Belajar',
    ];

    foreach ($labels as $key => $label) {
      if (empty($data[$key])) {
        continue;
      }

      $section->addText(
        $this->safe($label),
        ['bold' => true],
        ['spaceBefore' => 120]
      );

      $section->addText(
        $this->safe($data[$key]),
        [],
        ['spaceAfter' => 60]
      );
    }
  }

  // ============================================================
  // MATERI PELAJARAN
  // ============================================================

  private function addMateriPelajaran($section): void
  {
    if (!$this->modul->materi_pelajaran_list) {
      return;
    }

    $section->addTitle(
      $this->safe('C. Materi Pelajaran'),
      2
    );

    $items = $this->safeList(
      $this->modul->materi_pelajaran_list
    );

    foreach ($items as $materi) {
      $section->addListItem(
        $this->safe($materi),
        0,
        [],
        null,
        ['spaceAfter' => 40]
      );
    }
  }

  // ============================================================
  // DIMENSI PROFIL LULUSAN
  // ============================================================

  private function addDimensiProfil($section): void
  {
    if (!$this->modul->dimensi_profil_lulusan) {
      return;
    }

    $section->addTitle(
      $this->safe('D. Dimensi Profil Lulusan'),
      2
    );

    $table = $section->addTable('IdentityTable');

    $table->addRow();

    $table->addCell(2500)->addText(
      'Dimensi',
      ['bold' => true]
    );

    $table->addCell(6500)->addText(
      'Elemen yang Dikembangkan',
      ['bold' => true]
    );

    $data = $this->sanitizeArray(
      $this->modul->dimensi_profil_lulusan
    );

    foreach ($data as $dim) {
      if (!is_array($dim)) {
        continue;
      }

      $table->addRow();

      $table->addCell(2500)->addText(
        $this->safe($dim['dimensi'] ?? '-'),
        ['bold' => true]
      );

      $table->addCell(6500)->addText(
        $this->safe($dim['elemen'] ?? '-')
      );
    }
  }

  // ============================================================
  // DESAIN PEMBELAJARAN
  // ============================================================

  private function addDesainPembelajaran($section): void
  {
    $section->addTitle(
      $this->safe('E. Desain Pembelajaran'),
      2
    );

    $section->addText(
      'Capaian Pembelajaran (CP)',
      ['bold' => true],
      ['spaceBefore' => 120]
    );

    $section->addText(
      $this->safe(
        $this->modul->capaian_pembelajaran
      ),
      [],
      ['spaceAfter' => 60]
    );

    if ($this->modul->lintas_disiplin_ilmu) {
      $section->addText(
        'Lintas Disiplin Ilmu',
        ['bold' => true],
        ['spaceBefore' => 120]
      );

      $section->addText(
        $this->safe(
          $this->modul->lintas_disiplin_ilmu
        ),
        [],
        ['spaceAfter' => 60]
      );
    }

    if ($this->modul->tujuan_pembelajaran) {
      $section->addText(
        'Tujuan Pembelajaran',
        ['bold' => true],
        ['spaceBefore' => 120]
      );

      $items = $this->safeList(
        $this->modul->tujuan_pembelajaran
      );

      foreach ($items as $tp) {
        $section->addListItem(
          $this->safe($tp),
          0,
          [],
          null,
          ['spaceAfter' => 40]
        );
      }
    }

    if ($this->modul->praktik_pedagogis) {
      $section->addText(
        'Praktik Pedagogis (Pendekatan Deep Learning)',
        ['bold' => true],
        ['spaceBefore' => 120]
      );

      $section->addText(
        $this->safe(
          $this->modul->praktik_pedagogis
        ),
        [],
        ['spaceAfter' => 60]
      );
    }

    if ($this->modul->pemanfaatan_teknologi) {
      $section->addText(
        'Pemanfaatan Teknologi Digital',
        ['bold' => true],
        ['spaceBefore' => 120]
      );

      $section->addText(
        $this->safe(
          $this->modul->pemanfaatan_teknologi
        ),
        [],
        ['spaceAfter' => 60]
      );
    }
  }

  // ============================================================
  // PENGALAMAN BELAJAR
  // ============================================================

  private function addBlokPembelajaran($section): void
  {
    if ($this->modul->meetings->isEmpty()) {
      return;
    }

    $section->addTitle(
      $this->safe(
        'PENGALAMAN BELAJAR (RINCIAN PER PERTEMUAN)'
      ),
      1
    );

    $groupedByBlok = $this->modul
      ->meetings
      ->groupBy('blok_judul');

    foreach ($groupedByBlok as $blokJudul => $meetings) {

      $section->addTitle(
        $this->safe(
          $blokJudul ?: 'Blok Pembelajaran'
        ),
        2
      );

      foreach ($meetings as $meeting) {

        $rentang = $meeting->rentang_pertemuan
          ?? 'Pertemuan ' . $meeting->pertemuan_ke;

        $judulPertemuan =
          $this->safe($rentang)
          . ' ('
          . $this->safe($meeting->jp ?? 0)
          . ' JP)';

        $section->addText(
          $judulPertemuan,
          [
            'bold' => true,
            'italic' => true,
          ],
          [
            'spaceBefore' => 120,
            'spaceAfter' => 60,
          ]
        );

        $this->addKegiatanList(
          $section,
          'Kegiatan Awal',
          $meeting->kegiatan_awal_list
        );

        $this->addKegiatanList(
          $section,
          'Kegiatan Inti',
          $meeting->kegiatan_inti_list
        );

        $this->addKegiatanList(
          $section,
          'Kegiatan Penutup',
          $meeting->kegiatan_penutup_list
        );
      }
    }
  }

  // ============================================================
  // KEGIATAN
  // ============================================================

  private function addKegiatanList(
    $section,
    string $label,
    ?array $items
  ): void {
    if (empty($items)) {
      return;
    }

    $section->addText(
      $this->safe($label) . ':',
      [
        'bold' => true,
        'underline' => 'single',
      ],
      [
        'spaceBefore' => 80,
      ]
    );

    $items = $this->safeList($items);

    foreach ($items as $item) {
      $section->addListItem(
        $this->safe($item),
        0,
        [],
        null,
        ['spaceAfter' => 30]
      );
    }
  }

  // ============================================================
  // ASESMEN
  // ============================================================

  private function addAsesmen($section): void
  {
    if (!$this->modul->asesmen) {
      return;
    }

    $section->addTitle(
      $this->safe('ASESMEN'),
      1
    );

    $data = $this->sanitizeArray(
      $this->modul->asesmen
    );

    if (!empty($data['diagnostik'])) {

      $section->addText(
        'Asesmen Awal (Diagnostik)',
        ['bold' => true],
        ['spaceBefore' => 120]
      );

      $section->addText(
        $this->safe($data['diagnostik']),
        [],
        ['spaceAfter' => 60]
      );
    }

    if (!empty($data['formatif'])) {

      $section->addText(
        'Asesmen Formatif (Proses)',
        ['bold' => true],
        ['spaceBefore' => 120]
      );

      foreach (
        $this->safeList($data['formatif'])
        as $item
      ) {
        $section->addListItem(
          $this->safe($item),
          0,
          [],
          null,
          ['spaceAfter' => 30]
        );
      }
    }

    if (!empty($data['sumatif'])) {

      $section->addText(
        'Asesmen Sumatif (Akhir Bab)',
        ['bold' => true],
        ['spaceBefore' => 120]
      );

      foreach (
        $this->safeList($data['sumatif'])
        as $item
      ) {
        $section->addListItem(
          $this->safe($item),
          0,
          [],
          null,
          ['spaceAfter' => 30]
        );
      }
    }
  }

  // ============================================================
  // PENGAYAAN & REMEDIAL
  // ============================================================

  private function addPengayaanRemedial($section): void
  {
    if ($this->modul->pengayaan) {

      $section->addTitle(
        $this->safe('PENGAYAAN'),
        1
      );

      foreach (
        $this->safeList($this->modul->pengayaan)
        as $item
      ) {
        $section->addListItem(
          $this->safe($item),
          0,
          [],
          null,
          ['spaceAfter' => 30]
        );
      }
    }

    if ($this->modul->remedial) {

      $section->addTitle(
        $this->safe('REMEDIAL'),
        1
      );

      foreach (
        $this->safeList($this->modul->remedial)
        as $item
      ) {
        $section->addListItem(
          $this->safe($item),
          0,
          [],
          null,
          ['spaceAfter' => 30]
        );
      }
    }
  }

  // ============================================================
  // REFLEKSI
  // ============================================================

  private function addRefleksi($section): void
  {
    if ($this->modul->refleksi_peserta_didik) {

      $section->addTitle(
        $this->safe('REFLEKSI DIRI'),
        1
      );

      $section->addTitle(
        $this->safe('Refleksi Peserta Didik'),
        2
      );

      foreach (
        $this->safeList(
          $this->modul->refleksi_peserta_didik
        ) as $item
      ) {
        $section->addListItem(
          $this->safe($item),
          0,
          [],
          null,
          ['spaceAfter' => 30]
        );
      }
    }

    if ($this->modul->refleksi_pendidik) {

      $section->addTitle(
        $this->safe('Refleksi Pendidik'),
        2
      );

      foreach (
        $this->safeList(
          $this->modul->refleksi_pendidik
        ) as $item
      ) {
        $section->addListItem(
          $this->safe($item),
          0,
          [],
          null,
          ['spaceAfter' => 30]
        );
      }
    }
  }
}
