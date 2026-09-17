<?php

declare(strict_types=1);

use App\Services\Settings\SettingsService;

if (! function_exists('setting')) {
    /**
     * Read a cached setting as `group.key`, e.g. `school.eiin`.
     *
     * Called with no arguments, returns the service itself for writes.
     *
     * @see SettingsService
     */
    function setting(?string $path = null, mixed $default = null): mixed
    {
        $settings = app(SettingsService::class);

        if ($path === null) {
            return $settings;
        }

        return $settings->get($path, $default);
    }
}
