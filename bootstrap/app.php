<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('backup:shop')
            ->dailyAt('02:30')
            ->timezone('Asia/Jakarta');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->renderable(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('admin', 'admin/*') && ! $request->expectsJson()) {
                $home = $request->user()?->adminHomeRouteName() ?? 'admin.dashboard';

                return redirect()
                    ->route($home)
                    ->withErrors(['Kamu tidak punya akses ke halaman atau aksi ini.']);
            }
        });
    })->create();
