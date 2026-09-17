<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Delivers the language files to the frontend as one flat `file.key.subkey`
 * map.
 *
 * The platform is English-only. What Bangla remains — the organisation's and
 * the school's names, and the Golden Jubilee's title and theme line — is
 * identity rather than translation, and lives in the `settings` table as
 * ordinary values.
 *
 * @see docs/06-localization.md
 */
final class Translations
{
    /**
     * Language files delivered to the frontend. `validation` is server-side
     * only; nothing here is secret, but shipping a file nothing reads is
     * payload on every page load.
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
     * The flattened translation map, as `file.key.subkey`.
     *
     * Cached: reading and flattening six PHP files on every request is wasted
     * work, and the copy only changes on deploy.
     *
     * Outside production the cache key carries a fingerprint of the files'
     * modification times, so editing a language file takes effect on the next
     * request. Without it a developer edits a string, sees the old one, and
     * has no reason to suspect a cache.
     *
     * @return array<string, string>
     */
    public static function flattened(): array
    {
        $key = 'translations';

        if (! App::environment('production')) {
            $key .= '.'.self::fingerprint();
        }

        /** @var array<string, string> $flat */
        $flat = Cache::rememberForever($key, static function (): array {
            $flat = [];

            foreach (self::FRONTEND_FILES as $file) {
                $path = lang_path("en/{$file}.php");

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
        Cache::forget('translations');
        Cache::forget('translations.'.self::fingerprint());
    }

    /**
     * A short hash of the language files and their modification times.
     */
    private static function fingerprint(): string
    {
        $stamps = [];

        foreach (self::FRONTEND_FILES as $file) {
            $path = lang_path("en/{$file}.php");
            $stamps[] = File::exists($path) ? (string) File::lastModified($path) : '0';
        }

        return substr(md5(implode('|', $stamps)), 0, 12);
    }
}
