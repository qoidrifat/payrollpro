<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'group', 'type'];

    /**
     * Cache TTL for settings (1 hour).
     */
    private const CACHE_TTL = 3600;

    /**
     * Get a setting value with caching.
     * Reduces database queries for frequently accessed settings.
     */
    public static function getValue(string $key, $default = null)
    {
        return Cache::remember("setting:value:{$key}", self::CACHE_TTL, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function setValue(string $key, $value, string $group = 'general', string $type = 'text'): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'type' => $type]
        );

        Cache::forget("setting:value:{$key}");
    }

    /**
     * Clear all setting caches.
     */
    public static function clearCache(?string $key = null): void
    {
        if ($key !== null) {
            Cache::forget("setting:value:{$key}");

            return;
        }

        // Forget ONLY setting caches. Never Cache::flush() here — the app cache
        // store is shared with sessions, queues, and every other cached value,
        // so a global flush on a settings change would log users out and drop
        // unrelated cached data. Each setting is keyed by its own row key.
        static::query()->pluck('key')->each(
            fn ($settingKey) => Cache::forget("setting:value:{$settingKey}")
        );
    }
}
