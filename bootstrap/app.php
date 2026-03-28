<?php

use App\Http\Middleware\CheckMaintenanceAccess;
use App\Http\Middleware\EnforceScheduledAccess;
use App\Http\Middleware\EnsureApplicationInstalled;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\ErrorHandler\ErrorRenderer\HtmlErrorRenderer;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! config('app.debug') || $request->expectsJson()) {
                return null;
            }

            $renderer = new HtmlErrorRenderer(true);

            return response(
                $renderer->render($exception)->getAsString(),
                $exception->getStatusCode(),
                $exception->getHeaders(),
            );
        });
    })->create();
