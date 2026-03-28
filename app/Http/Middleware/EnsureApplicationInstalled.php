<?php

namespace App\Http\Middleware;

use App\Services\InstallerService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationInstalled
{
    public function __construct(private readonly InstallerService $installer) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->installer->isInstalled()) {
            return $next($request);
        }

        if ($request->routeIs('install.*') || $request->routeIs('health-check')) {
            return $next($request);
        }

        return redirect()->route('install.index');
    }
}
