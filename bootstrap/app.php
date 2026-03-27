<?php

use App\Http\Middleware\CheckMaintenanceAccess;
use App\Http\Middleware\EnforceScheduledAccess;
use App\Http\Middleware\EnsureApplicationInstalled;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            EnsureApplicationInstalled::class,
            CheckMaintenanceAccess::class,
        ]);

        $middleware->alias([
            'installed' => EnsureApplicationInstalled::class,
            'maintenance.whitelist' => CheckMaintenanceAccess::class,
            'scheduled.access' => EnforceScheduledAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
