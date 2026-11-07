<?php

namespace App\Providers;

use App\Models\Rombel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class MenuServiceProvider extends ServiceProvider
{
  public function register(): void
  {
    //
  }

  public function boot(): void
  {
    View::composer('*', function ($view) {
      $verticalMenuData   = json_decode(file_get_contents(base_path('resources/menu/verticalMenu.json')));
      $horizontalMenuData = json_decode(file_get_contents(base_path('resources/menu/horizontalMenu.json')));

      if (Auth::check()) {
        // Filter menu berdasarkan fitur DAN role, termasuk submenu (rekursif)
        $verticalMenuData->menu   = $this->filterMenu($verticalMenuData->menu);
        $horizontalMenuData->menu = $this->filterMenu($horizontalMenuData->menu);

        // Menu Dokumen Kelulusan (SKHU/SKL) hanya untuk Guru yang
        // menjadi wali kelas tingkat AKHIR di semester kelulusan;
        // Guru lain tidak memerlukannya (dan halamannya memang
        // kosong baginya). Peran lain tidak disentuh.
        $user = Auth::user();
        if (
          $user->hasRole('Guru')
          && !$user->hasAnyRole(['Admin', 'Operator Sekolah', 'Kepala Sekolah'])
          && !$this->guruPunyaKelasAkhir($user)
        ) {
          $verticalMenuData->menu   = $this->cabutMenuKelulusan($verticalMenuData->menu);
          $horizontalMenuData->menu = $this->cabutMenuKelulusan($horizontalMenuData->menu);
        }

        // Inject menu rombel dinamis untuk Guru
        if (Auth::user()->hasRole('Guru') && Auth::user()->ptk) {
          $semesterId = Session::get('semester_id');

          if ($semesterId) {
            $rombels = Rombel::where('ptk_id', Auth::user()->ptk->id)
              ->where('semester_id', $semesterId)
              ->get();

            if ($rombels->isNotEmpty()) {
              $verticalMenuData->menu   = $this->injectMenuRombel($verticalMenuData->menu, $rombels);
              $horizontalMenuData->menu = $this->injectMenuRombel($horizontalMenuData->menu, $rombels);
            }
          }
        }
      }

      $view->with('menuData', [$verticalMenuData, $horizontalMenuData]);
    });
  }

  /**
   * Filter menu berdasarkan fitur sekolah & role user.
   * Menangani menuHeader (hilangkan header jika section-nya kosong)
   * dan submenu (filter rekursif).
   */
  private function filterMenu(array $menu): array
  {
    $user = Auth::user();

    // 1. Kelompokkan item menjadi section berdasarkan menuHeader
    $sections = [];
    foreach ($menu as $item) {
      if (isset($item->menuHeader)) {
        $sections[] = ['header' => $item, 'items' => []];
      } else {
        if (empty($sections)) {
          $sections[] = ['header' => null, 'items' => []];
        }
        $sections[count($sections) - 1]['items'][] = $item;
      }
    }

    // 2. Filter tiap item dalam section
    $menuBaru = [];

    foreach ($sections as $section) {
      $filteredItems = array_values(array_filter($section['items'], function ($item) use ($user) {
        return $this->isMenuAllowed($item, $user);
      }));

      // Filter submenu secara rekursif untuk item yang lolos
      foreach ($filteredItems as $item) {
        if (isset($item->submenu) && is_array($item->submenu)) {
          $item->submenu = $this->filterMenu($item->submenu);
        }
      }

      // Kalau section punya header tapi semua isinya hilang, sembunyikan headernya juga
      if ($section['header'] && empty($filteredItems)) {
        continue;
      }

      if ($section['header']) {
        $menuBaru[] = $section['header'];
      }

      $menuBaru = array_merge($menuBaru, $filteredItems);
    }

    return $menuBaru;
  }

  /**
   * Cek apakah satu item menu boleh ditampilkan untuk user saat ini,
   * berdasarkan kombinasi fitur (per sekolah) dan role.
   */
  private function isMenuAllowed(object $item, $user): bool
  {
    // Cek fitur
    if (isset($item->fitur) && !\App\Helpers\FiturHelper::punya($item->fitur)) {
      return false;
    }

    // Cek role
    if (isset($item->roles) && (!$user || !$user->hasAnyRole($item->roles))) {
      return false;
    }

    return true;
  }

  private function injectMenuRombel(array $menu, $rombels): array
  {
    // Section Wali Kelas tepat di BAWAH Home: item pertama menu
    // (tautan Home) tetap paling atas, section kelas-kelas yang
    // diampu Guru menyusul sesudahnya.
    $section = [(object) ['menuHeader' => 'Wali Kelas']];

    foreach ($rombels as $rombel) {
      $section[] = $this->buildMenuRombel($rombel);
    }

    if (!empty($menu) && !isset($menu[0]->menuHeader)) {
      $pertama = array_shift($menu);
      return array_merge([$pertama], $section, $menu);
    }

    return array_merge($section, $menu);
  }

  /** Apakah Guru ini wali kelas tingkat akhir pada semester kelulusan? */
  private function guruPunyaKelasAkhir($user): bool
  {
    if (!$user->ptk) {
      return false;
    }

    $sekolah = \App\Models\Sekolah::find($user->ptk->sekolah_id);
    if (!$sekolah) {
      return false;
    }

    $tingkatAkhir = [
      'SD'  => 6,
      'SMP' => 9,
      'SMA' => 12,
      'SMK' => 12,
    ][strtoupper($sekolah->jenjang ?? '')] ?? null;

    if ($tingkatAkhir === null) {
      return false;
    }

    // Kelas akhir dinilai pada DUA semester kerja guru: semester
    // kelulusan (dokumen yang sedang difinalisasi — saat sesi
    // Ganjil itu kelas akhir tahun lalu) dan semester sesi saat
    // ini (kelas akhir yang sedang ia bina menuju kelulusan).
    // Wali kelas salah satu di antaranya memang mengurus
    // SKHU/SKL, jadi menunya tampil.
    $semesterIds = array_unique(array_filter([
      (int) \App\Models\Semester::smtIdKelulusan(),
      (int) Session::get('semester_id'),
    ]));

    return Rombel::where('ptk_id', $user->ptk->id)
      ->whereIn('semester_id', $semesterIds)
      ->where('tingkat', $tingkatAkhir)
      ->exists();
  }

  /**
   * Buang item SKHU/SKL (slug skhu/skl) dari menu, termasuk induk
   * yang submenunya menjadi kosong dan header section yang
   * kehilangan seluruh isinya.
   */
  private function cabutMenuKelulusan(array $menu): array
  {
    $menu = array_values(array_filter(
      $menu,
      fn($item) => !in_array($item->slug ?? '', ['skhu', 'skl'], true)
    ));

    foreach ($menu as $item) {
      if (isset($item->submenu) && is_array($item->submenu)) {
        $item->submenu = $this->cabutMenuKelulusan($item->submenu);
      }
    }

    // Induk tanpa url yang submenunya kosong ikut dibuang
    $menu = array_values(array_filter(
      $menu,
      fn($item) => !(isset($item->submenu) && is_array($item->submenu)
        && empty($item->submenu) && empty($item->url))
    ));

    // Header yang section-nya kosong ikut dibuang
    $hasil = [];
    $total = count($menu);
    foreach ($menu as $i => $item) {
      if (isset($item->menuHeader)) {
        $berikut = $menu[$i + 1] ?? null;
        if ($berikut === null || isset($berikut->menuHeader)) {
          continue;
        }
      }
      $hasil[] = $item;
    }

    return $hasil;
  }

  private function buildMenuRombel(Rombel $rombel): object
  {
    return (object) [
      'icon'    => 'menu-icon icon-base ri ri-door-open-line',
      'name'    => $rombel->nama_rombel,
      'slug'    => 'wali.kelas',
      'roles'   => ['Guru'],
      'submenu' => [
        (object) [
          'url'       => '/wali-kelas/' . $rombel->id . '/siswa',
          'name'      => 'Data Siswa',
          'slug'      => 'wali.kelas.siswa',
          'url_check' => '/wali-kelas/' . $rombel->id . '/siswa',
        ],
        (object) [
          'url'       => '/wali-kelas/' . $rombel->id . '/absensi',
          'name'      => 'Presensi',
          'slug'      => 'wali.kelas.absensi',
          'url_check' => '/wali-kelas/' . $rombel->id . '/absensi',
        ],
      ],
    ];
  }
}
