<?php

use App\Console\Commands\SendPegawaiReminderNotifications;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(SendPegawaiReminderNotifications::class)->dailyAt('07:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
