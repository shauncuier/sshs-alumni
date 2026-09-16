<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Locale as LocaleEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Locale resolution and translation delivery to the frontend.
 *
 * Only the ACTIVE locale's translations are shipped, so the Inertia payload
 * never carries both languages.
 *
 * @see docs/06-localization.md
 */
final class Locale
{
    /**
     * Locale files that are delivered to the frontend. `validation` is shipped
     * because client-side messages need it; nothing else server-only is.
     *
     * @var array<int, string>
     */
    private const FRONTEND_FILES = [
        'common',
        'public',
        'jubilee',
        'member',
        'admin',
        'enums',
    ];

    /**
     * Every supported locale, with its own native label — a language picker
     * that says "Bengali" in English is useless to a Bangla-first reader.
     *
     * @return array<string, string>
     */
    public static function available(): array
    {
        return [
            LocaleEnum::Bn->value => 'বাংলা',
            LocaleEnum::En->value => 'English',
        ];
    }

    public static function isSupported(string $locale): bool
    {
        return array_key_exists($locale, self::available());
    }

    public static function current(): string
    {
        return App::getLocale();
    }

    /**
     * The flattened translation map for a locale, as `file.key.subkey`.
     *
     * Cached: reading and flattening six PHP files on every request is wasted
     * work, and translations only change on deploy or an `optimize:clear`.
     *
     * @return array<string, string>
     */
    public static function flattenedFor(string $locale): array
    {
        /** @var array<string, string> $flat */
        $flat = Cache::rememberForever("translations.{$locale}", static function () use ($locale): array {
            $flat = [];

            foreach (self::FRONTEND_FILES as $file) {
                $path = lang_path("{$locale}/{$file}.php");

                if (! File::exists($path)) {
                    continue;
                }

                /** @var array<string, mixed> $contents */
                $contents = require $path;

                foreach (Arr::dot($contents) as $key => $value) {
                    if (is_scalar($value)) {
                        $flat["{$file}.{$key}"] = (string) $value;
                    }
                }
            }

            return $flat;
        });

        return $flat;
    }

    public static function flushCache(): void
    {
        foreach (array_keys(self::available()) as $locale) {
            Cache::forget("translations.{$locale}");
        }
    }
}
