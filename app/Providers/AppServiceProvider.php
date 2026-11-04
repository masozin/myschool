<?php

namespace App\Providers;

use App\Http\Composers\SekolahComposer;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use App\Models\Semester;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    // Override LoginResponse Jetstream
    $this->app->singleton(LoginResponseContract::class, LoginResponse::class);

    // Override LogoutResponse
    $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    Vite::useStyleTagAttributes(function (?string $src, string $url, ?array $chunk, ?array $manifest) {
      if ($src !== null) {
        return [
          'class' => preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?core)-?.*/i", $src) ? 'template-customizer-core-css' : (preg_match("/(resources\/assets\/vendor\/scss\/(rtl\/)?theme)-?.*/i", $src) ? 'template-customizer-theme-css' : '')
        ];
      }
      return [];
    });

    View::composer('auth.login', function ($view) {
      $view->with('semesters', \App\Models\Semester::orderBy('id', 'desc')->get());
    });

    // Kirim $nama_semester dan $isEditable ke semua view
    View::composer('*', function ($view) {
      if (Auth::check()) {
        $user       = Auth::user();
        $semesterId = session('semester_id');
        $semester   = Semester::find($semesterId);

        $isEditable = $user->hasRole('admin')
          || ($semester && $semester->is_active == 1);

        $view->with([
          'namaSemester' => isset($semester)
            ? $semester->tahun_pelajaran . ' - ' . $semester->semester
            : '-',
          'isEditable'     => $isEditable,
        ]);
      }
    });

    View::composer('layouts.layoutMaster', SekolahComposer::class);

    Event::listen(Login::class, function ($event) {
      $event->user->update(['last_login_at' => now()]);
    });

    \Carbon\Carbon::setLocale('id');
  }

  protected $policies = [
    \App\Models\Rombel::class => \App\Policies\RombelPolicy::class,
  ];
}
