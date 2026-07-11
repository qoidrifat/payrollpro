<?php

namespace App\Repositories;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingRepository
{
    private const CACHE_TTL = 3600; // 1 hour

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting:{$key}", self::CACHE_TTL, function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();
            return $setting?->value ?? $default;
        });
    }

    public function set(string $key, mixed $value, string $group = 'general', string $type = 'text'): void
    {
        $previousGroup = Setting::where('key', $key)->value('group');

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'type' => $type]
        );

        // Clear both cache key patterns used across the app
        Cache::forget("setting:{$key}");
        Cache::forget("setting:value:{$key}");
        Cache::forget("settings:group:{$group}");

        if ($previousGroup && $previousGroup !== $group) {
            Cache::forget("settings:group:{$previousGroup}");
        }
    }

    public function getByGroup(string $group): array
    {
        return Cache::remember("settings:group:{$group}", self::CACHE_TTL, function () use ($group) {
            return Setting::where('group', $group)->pluck('value', 'key')->toArray();
        });
    }

    public function forget(string $key): void
    {
        Cache::forget("setting:{$key}");
    }

    public function flush(): void
    {
        Cache::flush();
    }
}
