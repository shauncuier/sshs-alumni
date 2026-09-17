import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';
import type { Locale, Translations } from '@/types/shared';

type Replacements = Record<string, string | number>;

/**
 * Translation lookup for the active locale.
 *
 * Keys are `file.section.key`, matching `lang/{locale}/{file}.php`. Only the
 * active locale is shipped, so this never has to choose between languages.
 *
 * A missing key returns the key itself rather than an empty string — a gap is
 * then visible on screen and in a screenshot, instead of silently rendering
 * nothing.
 *
 * @see docs/06-localization.md section 4
 */
export function useTranslation() {
    const page = usePage();
    const translations = (page.props.translations ?? {}) as Translations;
    const locale = (page.props.locale ?? 'bn') as Locale;

    const t = useCallback(
        (key: string, replacements?: Replacements): string => {
            let line = translations[key];

            if (line === undefined) {
                return key;
            }

            if (replacements) {
                // Longest token first, or `:to` eats the `:to` inside
                // `:total` and both placeholders come out wrong. Laravel's
                // own translator sorts the same way.
                const tokens = Object.entries(replacements).sort(
                    ([a], [b]) => b.length - a.length,
                );

                for (const [token, value] of tokens) {
                    // replaceAll, because a line may use a placeholder twice.
                    line = line.replaceAll(`:${token}`, String(value));
                }
            }

            return line;
        },
        [translations],
    );

    return { t, locale, isBangla: locale === 'bn' };
}
