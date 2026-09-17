import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';
import type { Translations } from '@/types/shared';

type Replacements = Record<string, string | number>;

/**
 * Copy lookup.
 *
 * Keys are `file.section.key`, matching `lang/en/{file}.php`. The platform is
 * English-only, so this resolves one language — but the indirection stays,
 * because it is what keeps user-facing strings out of components and in files
 * the committee can edit without touching JSX.
 *
 * A missing key returns the key itself rather than an empty string — a gap is
 * then visible on screen and in a screenshot, instead of silently rendering
 * nothing.
 *
 * @see docs/06-localization.md
 */
export function useTranslation() {
    const page = usePage();
    const translations = (page.props.translations ?? {}) as Translations;

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

    /**
     * Pick a plural form, using Laravel's pipe syntax:
     *
     *   'No members|:count member|:count members'
     *
     * Three parts: zero, one, many. Two parts drops the zero case. English
     * needs this — "1 members" reads as a bug to anyone looking at it.
     */
    const choice = useCallback(
        (key: string, count: number, replacements?: Replacements): string => {
            const line = translations[key];

            if (line === undefined) {
                return key;
            }

            const parts = line.split('|');

            let form: string;

            if (parts.length >= 3) {
                form =
                    count === 0 ? parts[0] : count === 1 ? parts[1] : parts[2];
            } else if (parts.length === 2) {
                form = count === 1 ? parts[0] : parts[1];
            } else {
                form = parts[0];
            }

            // The chosen form still goes through `t`'s replacement rules, so
            // `:count` and any other token behave identically.
            let resolved = form;

            const tokens = Object.entries({ count, ...replacements }).sort(
                ([a], [b]) => b.length - a.length,
            );

            for (const [token, value] of tokens) {
                resolved = resolved.replaceAll(`:${token}`, String(value));
            }

            return resolved;
        },
        [translations],
    );

    return { t, choice };
}
