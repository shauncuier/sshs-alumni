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
