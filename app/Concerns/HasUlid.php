<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Str;

/**
 * Gives a model a public ULID alongside its integer primary key.
 *
 * Integer keys stay internal — smaller indexes, faster joins. The ULID is what
 * appears in URLs and QR payloads, so /directory/01JBX… cannot be walked by
 * incrementing a number.
 *
 * @see docs/01-architecture.md section 7
 *
 * @property string $ulid
 */
trait HasUlid
{
    public static function bootHasUlid(): void
    {
        static::creating(function (self $model): void {
            if (blank($model->ulid)) {
                $model->ulid = (string) Str::ulid();
            }
        });
    }

    /**
     * Bind {model:ulid} route parameters without exposing the integer key.
     */
    public function getRouteKeyName(): string
    {
        return 'ulid';
    }
}
