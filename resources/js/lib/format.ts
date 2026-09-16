import type { Locale } from '@/types/shared';

const BANGLA_DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

/**
 * Render digits in the active locale — ০১২৩ under বাংলা.
 *
 * Deliberately NOT for identifiers. Membership numbers, receipt numbers,
 * transaction references and phone numbers stay in Latin digits in both
 * languages so they can be quoted over the phone, searched and typed
 * reliably.
 *
 * @see docs/06-localization.md section 7
 */
export function toBanglaDigits(value: string | number): string {
    return String(value).replace(
        /\d/g,
        (digit) => BANGLA_DIGITS[Number(digit)],
    );
}

export function localizeNumber(value: string | number, locale: Locale): string {
    return locale === 'bn' ? toBanglaDigits(value) : String(value);
}

export function formatNumber(
    value: number,
    locale: Locale,
    decimals = 0,
): string {
    const formatted = value.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });

    return localizeNumber(formatted, locale);
}

/**
 * Taka sign under বাংলা, ISO code under English.
 */
export function formatCurrency(amount: number, locale: Locale): string {
    const formatted = formatNumber(amount, locale, 2);

    return locale === 'bn' ? `৳${formatted}` : `BDT ${formatted}`;
}

/**
 * A date in the active locale, with Bengali month names and numerals under
 * বাংলা.
 */
export function formatDate(
    value: string | Date | null | undefined,
    locale: Locale,
    options: Intl.DateTimeFormatOptions = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    },
): string {
    if (!value) {
        return '';
    }

    const date = typeof value === 'string' ? new Date(value) : value;

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return new Intl.DateTimeFormat(
        locale === 'bn' ? 'bn-BD' : 'en-GB',
        options,
    ).format(date);
}
