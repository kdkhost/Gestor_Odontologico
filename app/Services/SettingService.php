<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SettingService
{
    public function get(string $group, string $key, mixed $default = null, ?int $unitId = null): mixed
    {
        if (! $this->settingsTableIsAvailable()) {
            return $default;
        }

        $cacheKey = "setting:{$unitId}:{$group}:{$key}";

        $resolver = fn () => $this->resolveSettingValue($group, $key, $default, $unitId);

        try {
            return Cache::remember($cacheKey, now()->addMinutes(10), $resolver);
        } catch (Throwable) {
            return $resolver();
        }
    }

    public function put(string $group, string $key, mixed $value, string $type = 'string', ?int $unitId = null, bool $isPublic = false): SystemSetting
    {
        Cache::forget("setting:{$unitId}:{$group}:{$key}");

        return SystemSetting::query()->updateOrCreate(
            ['unit_id' => $unitId, 'group' => $group, 'key' => $key],
            ['type' => $type, 'value' => is_array($value) ? json_encode($value) : (string) $value, 'is_public' => $isPublic],
        );
    }

    private function castStoredValue(?string $type, mixed $value, mixed $default): mixed
    {
        if ($value === null) {
            return $default;
        }

        return match ($type) {
            'boolean' => in_array($value, ['1', 1, true, 'true'], true),
            'integer' => (int) $value,
            'float', 'decimal' => (float) $value,
            'array', 'json' => json_decode((string) $value, true) ?: [],
            default => $value,
        };
    }

    private function resolveSettingValue(string $group, string $key, mixed $default, ?int $unitId = null): mixed
    {
        try {
            $query = SystemSetting::query()
                ->where('group', $group)
                ->where('key', $key);

            if ($unitId !== null) {
                $query->where(function ($builder) use ($unitId) {
                    $builder->where('unit_id', $unitId)->orWhereNull('unit_id');
                })->orderByDesc('unit_id');
            } else {
                $query->whereNull('unit_id');
            }

            $setting = $query->first();

            return $this->castStoredValue($setting?->type, $setting?->value, $default);
        } catch (Throwable) {
            return $default;
        }
    }

    private function settingsTableIsAvailable(): bool
    {
        try {
            return Schema::hasTable('system_settings');
        } catch (Throwable) {
            return false;
        }
    }
}
