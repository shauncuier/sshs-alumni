<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Facades\App;

/**
 * Resolves paired bilingual columns (`title` / `title_bn`) by active locale.
 *
 * Content authored by admins is bilingual by paired columns rather than a
 * translations table: exactly two locales are in scope and both are authored
 * in the same form, so a translations table would add a join to every content
 * read and an EAV shape to every write.
 *
 * This trait is the seam. If a third language is ever needed, its resolution
 * moves to a content_translations table and no controller, resource or
 * component changes.
 *
 * Fallback is per-field and non-empty aware: a blank `title_bn` falls back to
 * `title` rather than rendering an empty heading.
 *
 * @see docs/06-localization.md section 5
 */
trait Translatable
{
    /**
     * Fields with a `_bn` twin. Models override this method to declare theirs.
     *
     * @return array<int, string>
     */
    public function translatableFields(): array
    {
        return [];
    }

    /**
     * Read a translatable field in the given locale, falling back to the other
     * language when the preferred column is empty.
     */
    public function getTranslation(string $field, ?string $locale = null): ?string
    {
        $locale ??= App::getLocale();

        $bangla = $this->getAttributeValue($field.'_bn');
        $base = $this->getAttributeValue($field);

        $preferred = $locale === 'bn' ? $bangla : $base;
        $fallback = $locale === 'bn' ? $base : $bangla;

        $value = filled($preferred) ? $preferred : $fallback;

        return is_string($value) ? $value : null;
    }

    /**
     * Render every translatable field for the active locale, for API
     * Resources and Inertia props.
     *
     * @return array<string, string|null>
     */
    public function translated(?string $locale = null): array
    {
        $output = [];

        foreach ($this->translatableFields() as $field) {
            $output[$field] = $this->getTranslation($field, $locale);
        }

        return $output;
    }
}
