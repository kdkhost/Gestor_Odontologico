<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class SettingService
{
    public function get(string $group, string $key, mixed $default = null, ?int $unitId = null): mixed
    {
        if (! Schema::hasTable('system_settings')) {
            return $default;
        }

        $cacheKey = "setting:{$unitId}:{$group}:{$key}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($group, $key, $default, $unitId) {
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
        });
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
}
