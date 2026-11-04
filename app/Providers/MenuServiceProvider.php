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
    $menuBaru = [];

    foreach ($menu as $item) {
      $menuBaru[] = $item;

      if (isset($item->menuHeader) && $item->menuHeader === 'Wali Kelas') {
        foreach ($rombels as $rombel) {
          $menuBaru[] = $this->buildMenuRombel($rombel);
        }
      }
    }

    return $menuBaru;
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
          'name'      => 'Absensi',
          'slug'      => 'wali.kelas.absensi',
          'url_check' => '/wali-kelas/' . $rombel->id . '/absensi',
        ],
      ],
    ];
  }
}
