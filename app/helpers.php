<?php

use App\Services\AppSettingsService;

if (! function_exists('settings')) {
    /**
     * Get the app settings service or a single value.
     */
    function settings(?string $key = null, mixed $default = null): mixed
    {
        $service = app(AppSettingsService::class);

        if ($key !== null) {
            return $service->get($key, $default);
        }

        return $service;
    }
}
