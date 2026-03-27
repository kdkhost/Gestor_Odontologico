<?php

namespace App\Http\Middleware;

use App\Models\MaintenanceWhitelist;
use App\Services\GreetingService;
use App\Services\InstallerService;
use App\Services\SettingService;
use App\Support\DeviceFingerprint;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceAccess
{
    public function __construct(
        private readonly InstallerService $installer,
        private readonly SettingService $settings,
        private readonly GreetingService $greetings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (app()->runningInConsole() || ! $this->installer->isInstalled()) {
            return $next($request);
        }

        if ($request->routeIs('webhooks.*') || $request->routeIs('install.*')) {
            return $next($request);
        }

        $enabled = $this->settings->get('maintenance', 'enabled', false);

        if (! $enabled) {
            return $next($request);
        }

        if ($this->isAllowed($request)) {
            return $next($request);
        }

        $releaseAt = $this->settings->get('maintenance', 'release_at');

        return response()->view('maintenance.index', [
            'releaseAt' => $releaseAt,
            'greeting' => $this->greetings->current(),
            'appName' => $this->settings->get('branding', 'app_name', config('app.name')),
        ], 503);
    }

    private function isAllowed(Request $request): bool
    {
        if (in_array($request->ip(), config('clinic.maintenance_exceptions', []), true)) {
            return true;
        }

        if ($request->user()?->hasRole('superadmin')) {
            return true;
        }

        if (! class_exists(MaintenanceWhitelist::class)) {
            return false;
        }

        $fingerprint = DeviceFingerprint::fromRequest($request);
        $userId = $request->user()?->id;

        return MaintenanceWhitelist::query()
            ->where('is_active', true)
            ->where(function ($query) use ($request, $fingerprint, $userId) {
                $query
                    ->orWhere(fn ($builder) => $builder->where('type', 'ip')->where('value', $request->ip()))
                    ->orWhere(fn ($builder) => $builder->where('type', 'device')->where('value', $fingerprint));

                if ($userId) {
                    $query->orWhere(fn ($builder) => $builder->where('type', 'user')->where('user_id', $userId));
                }
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }
}
