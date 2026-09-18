/**
 * Display formatting.
 *
 * The platform reads in English and uses Latin digits throughout, including
 * inside the Bangla brand strings that survive on the header, footer and
 * Jubilee hero — those are set phrases, not a second locale.
 *
 * @see docs/06-localization.md
 */

export function formatNumber(value: number, decimals = 0): string {
    return value.toLocaleString('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
}

/**
 * A year, ungrouped.
 *
 * `formatNumber` would render 1976 as "1,976" — which is what shipped on the
 * footer, and reads as a quantity rather than a date. A year is an identifier,
 * not a count, so it never takes a thousands separator.
 */
export function formatYear(value: number | string | null | undefined): string {
    if (value === null || value === undefined || value === '') {
        return '';
    }

    return String(value);
}

export function formatCurrency(amount: number): string {
    return `BDT ${formatNumber(amount, 2)}`;
}

export function formatDate(
    value: string | Date | null | undefined,
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

    return new Intl.DateTimeFormat('en-GB', options).format(date);
}

/**
 * A date and time, for check-in logs and audit trails where the minute
 * matters.
 */
export function formatDateTime(
    value: string | Date | null | undefined,
): string {
    return formatDate(value, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
