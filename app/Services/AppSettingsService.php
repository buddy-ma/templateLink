<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Throwable;

class AppSettingsService
{
    private const CACHE_KEY = 'app_settings_all';

    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get all settings as a flat key => value array (cached).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            try {
                return AppSetting::all()
                    ->pluck('value', 'key')
                    ->map(fn ($v) => $this->decode($v))
                    ->toArray();
            } catch (Throwable) {
                return [];
            }
        });
    }

    /**
     * Get a single setting value with an optional fallback.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Get all settings that belong to a group as an associative array
     * using only the part after the dot as the sub-key.
     *
     * @return array<string, mixed>
     */
    public function group(string $group): array
    {
        $prefix = $group.'.';
        $result = [];

        foreach ($this->all() as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $result[substr($key, strlen($prefix))] = $value;
            }
        }

        return $result;
    }

    /**
     * Persist a single setting and invalidate the cache.
     */
    public function set(string $key, mixed $value): void
    {
        [$group] = explode('.', $key, 2) + ['general'];

        AppSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $this->encode($value),
                'group' => $group,
            ]
        );

        $this->flush();
    }

    /**
     * Persist multiple settings at once and invalidate the cache once.
     *
     * @param  array<string, mixed>  $settings
     */
    public function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            [$group] = explode('.', $key, 2) + ['general'];

            AppSetting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $this->encode($value),
                    'group' => $group,
                ]
            );
        }

        $this->flush();
    }

    /**
     * Clear the settings cache.
     */
    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Encode a value for storage (JSON-encodes non-scalars).
     */
    private function encode(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }

    /**
     * Decode a stored value (tries JSON decode first).
     */
    private function decode(?string $value): mixed
    {
        if (is_null($value)) {
            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
            return $decoded;
        }

        return $value;
    }
}
