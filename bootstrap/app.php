<?php

use App\Exceptions\FiturNonaktifException;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\SemesterSessionMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    api: __DIR__ . '/../routes/api.php',
    commands: __DIR__ . '/../routes/console.php',
    health: '/up',
  )
  ->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
      LocaleMiddleware::class,
      SemesterSessionMiddleware::class,
      \App\Http\Middleware\CekDataPeriodik::class,
      \App\Http\Middleware\CheckSemesterLock::class,
      \App\Http\Middleware\HandleInertiaRequests::class
    ]);
    $middleware->alias([
      'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
      'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
      'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
      'semester.session' => \App\Http\Middleware\SemesterSessionMiddleware::class,
      'semester.lock' => \App\Http\Middleware\CheckSemesterLock::class,
      'fitur' => \App\Http\Middleware\CheckFitur::class,
      'cek.periodik' => \App\Http\Middleware\CekDataPeriodik::class,
    ]);
  })
  ->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (FiturNonaktifException $e, $request) {
      if ($request->expectsJson()) {
        return response()->json([
          'message' => $e->getMessage(),
          'kode_fitur' => $e->kodeFitur,
        ], 403);
      }

      return response()->view('errors.fitur-nonaktif', [
        'kodeFitur' => $e->kodeFitur,
      ], 403);
    });
  })->create();
