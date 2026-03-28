<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureSessionDriverIsAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('session.driver') !== 'database') {
            return $next($request);
        }

        if (! $this->sessionTableIsAvailable()) {
            config([
                'session.driver' => 'file',
                'session.connection' => null,
            ]);
        }

        return $next($request);
    }

    private function sessionTableIsAvailable(): bool
    {
        try {
            $connection = config('session.connection') ?: config('database.default');

            return Schema::connection($connection)->hasTable(config('session.table', 'sessions'));
        } catch (Throwable) {
            return false;
        }
    }
}
