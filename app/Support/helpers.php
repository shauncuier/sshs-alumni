<?php

declare(strict_types=1);

use App\Services\Settings\SettingsService;
use App\Support\BanglaNumber;

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

if (! function_exists('bn_number')) {
    /**
     * Render a number in the active locale — ০১২৩ under বাংলা.
     *
     * Deliberately NOT for identifiers: membership numbers, receipt numbers
     * and phone numbers stay in Latin digits in both languages so they can be
     * quoted, searched and typed reliably.
     */
    function bn_number(string|int|float $value, ?string $locale = null): string
    {
        return BanglaNumber::localize($value, $locale);
    }
}
