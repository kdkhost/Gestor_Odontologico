<?php

namespace App\Http\Middleware;

use App\Models\AccessScheduleRule;
use App\Services\GreetingService;
use App\Support\DeviceFingerprint;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceScheduledAccess
{
    public function __construct(private readonly GreetingService $greetings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->user_type === 'patient' || $user->hasRole('superadmin')) {
            return $next($request);
        }

        $roles = $user->getRoleNames()->all();

        $rules = AccessScheduleRule::query()
            ->where('is_active', true)
            ->where(function ($query) use ($user, $roles) {
                $query->where('user_id', $user->id);

                if ($roles !== []) {
                    $query->orWhereIn('role_name', $roles);
                }
            })
            ->get();

        if ($rules->isEmpty()) {
            return $next($request);
        }

        $now = now(config('app.timezone'));
        $weekday = $now->dayOfWeek;
        $currentTime = $now->format('H:i:s');
        $fingerprint = DeviceFingerprint::fromRequest($request);

        $allowed = $rules->contains(function (AccessScheduleRule $rule) use ($user, $weekday, $currentTime, $request, $fingerprint) {
            if ($rule->unit_id && $user->unit_id && $rule->unit_id !== $user->unit_id) {
                return false;
            }

            if ($rule->weekday !== $weekday) {
                return false;
            }

            $ipAllowed = in_array($request->ip(), $rule->allowed_ip_list ?? [], true);
            $deviceAllowed = in_array($fingerprint, $rule->allowed_device_hashes ?? [], true);

            if ($rule->allow_outside_window && ($ipAllowed || $deviceAllowed)) {
                return true;
            }

            return $currentTime >= $rule->starts_at && $currentTime <= $rule->ends_at;
        });

        if ($allowed) {
            return $next($request);
        }

        return response()->view('errors.access-window', [
            'greeting' => $this->greetings->current(),
        ], 403);
    }
}
