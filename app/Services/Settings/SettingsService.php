<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Enums\SettingGroup;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Cached key/value settings, grouped.
 *
 * The whole table is one cached array: settings are read on nearly every
 * request (organization name, logo, footer, registration toggles) and there are
 * only a few dozen rows, so one cache entry beats per-key lookups.
 *
 * `organization` (the association, est. 2015) and `school` (est. 1976) are
 * deliberately separate groups. The two bodies must never be conflated.
 *
 * @see docs/05-modules.md section 18
 */
class SettingsService
{
    private const CACHE_KEY = 'settings.all';

    /** @var array<string, array<string, mixed>>|null */
    private ?array $loaded = null;

    /**
     * Read a setting as `group.key`, e.g. `school.eiin`.
     */
    public function get(string $path, mixed $default = null): mixed
    {
        [$group, $key] = $this->split($path);

        return $this->all()[$group][$key] ?? $default;
    }

    /**
     * Every setting in a group.
     *
     * @return array<string, mixed>
     */
    public function group(SettingGroup|string $group): array
    {
        $name = $group instanceof SettingGroup ? $group->value : $group;

        return $this->all()[$name] ?? [];
    }

    /**
     * Write a setting and invalidate the cache.
     */
    public function set(string $path, mixed $value, bool $isPublic = false): void
    {
        [$group, $key] = $this->split($path);

        Setting::query()->updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $value, 'is_public' => $isPublic],
        );

        $this->flush();
    }

    /**
     * Write many at once — one cache flush rather than one per key.
     *
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values, bool $isPublic = false): void
    {
        foreach ($values as $path => $value) {
            [$group, $key] = $this->split($path);

            Setting::query()->updateOrCreate(
                ['group' => $group, 'key' => $key],
                ['value' => $value, 'is_public' => $isPublic],
            );
        }

        $this->flush();
    }

    /**
     * Every setting, grouped.
     *
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        /** @var array<string, array<string, mixed>> $settings */
        $settings = Cache::rememberForever(self::CACHE_KEY, function (): array {
            $grouped = [];

            foreach (Setting::query()->get() as $setting) {
                $grouped[$setting->group->value][$setting->key] = $setting->value;
            }

            return $grouped;
        });

        return $this->loaded = $settings;
    }

    /**
     * Only the settings marked public — this is what reaches the frontend.
     *
     * Anything not explicitly public never leaves the server.
     *
     * @return array<string, array<string, mixed>>
     */
    public function publicSettings(): array
    {
        /** @var array<string, array<string, mixed>> $settings */
        $settings = Cache::rememberForever(self::CACHE_KEY.'.public', function (): array {
            $grouped = [];

            foreach (Setting::query()->where('is_public', true)->get() as $setting) {
                $grouped[$setting->group->value][$setting->key] = $setting->value;
            }

            return $grouped;
        });

        return $settings;
    }

    public function flush(): void
    {
        $this->loaded = null;
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY.'.public');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function split(string $path): array
    {
        if (! str_contains($path, '.')) {
            throw new \InvalidArgumentException(
                "Settings are addressed as 'group.key'; got '{$path}'."
            );
        }

        [$group, $key] = explode('.', $path, 2);

        return [$group, $key];
    }
}
