<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

/**
 * Unique URL slugs.
 *
 * WHY THIS IS SHARED. Six entities are addressed by slug — events, batches,
 * news, pages, albums and stories — and the same three rules apply to all of
 * them: transliterate the title, fall back to something rather than nothing
 * when the title leaves no ASCII behind, and keep counting until the slug is
 * free.
 *
 * SOFT-DELETED ROWS STILL HOLD THEIR SLUG. The unique index does not know
 * about `deleted_at`, so a check that skipped trashed rows would hand out a
 * slug the database then refuses — and it would refuse at insert time, after
 * the form was filled in.
 *
 * @see app/Http/Controllers/Admin/NewsController.php
 */
final class SlugFactory
{
    /**
     * A slug for `$title` that no row of `$model` is using.
     *
     * @param  class-string<Model>  $model
     * @param  string  $fallback  Used when the title slugs to nothing — a
     *                            title written entirely in Bangla does, and an
     *                            empty slug would collide with itself.
     * @param  int|null  $ignoreId  The row being renamed, which is allowed to
     *                              keep the slug it already has.
     */
    public static function unique(
        string $model,
        string $title,
        string $fallback,
        ?int $ignoreId = null,
    ): string {
        $base = Str::slug($title) ?: $fallback;
        $slug = $base;
        $suffix = 1;

        while (self::taken($model, $slug, $ignoreId)) {
            $slug = $base.'-'.++$suffix;
        }

        return $slug;
    }

    /**
     * @param  class-string<Model>  $model
     */
    private static function taken(string $model, string $slug, ?int $ignoreId): bool
    {
        $query = $model::query()->where('slug', $slug);

        if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            // The same thing `withTrashed()` does, spelled without the
            // SoftDeletes macro: the model class is only known as
            // class-string<Model> here, so the macro is not on its type.
            $query->withoutGlobalScope(SoftDeletingScope::class);
        }

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }
}
