<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\App;

/**
 * Bengali numeral rendering.
 *
 * Applied to dates, counters, statistics, currency, the ১৯৭৬ — ২০২৬ milestone
 * and the countdown.
 *
 * NOT applied to identifiers — membership numbers, transaction references,
 * receipt numbers, phone numbers. Those stay in Latin digits in both locales so
 * they can be quoted over the phone, searched and typed reliably.
 *
 * @see docs/06-localization.md section 7
 */
final class BanglaNumber
{
    /** @var array<int, string> */
    private const BANGLA = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    /** @var array<int, string> */
    private const LATIN = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    /**
     * Convert Latin digits in a value to Bengali.
     */
    public static function toBangla(string|int|float $value): string
    {
        return str_replace(self::LATIN, self::BANGLA, (string) $value);
    }

    /**
     * Convert Bengali digits back to Latin — for parsing user input.
     */
    public static function toLatin(string $value): string
    {
        return str_replace(self::BANGLA, self::LATIN, $value);
    }

    /**
     * Render for a locale, leaving Latin digits alone under `en`.
     */
    public static function localize(string|int|float $value, ?string $locale = null): string
    {
        $locale ??= App::getLocale();

        return $locale === 'bn' ? self::toBangla($value) : (string) $value;
    }

    /**
     * A thousands-separated number in the active locale.
     */
    public static function format(int|float $value, int $decimals = 0, ?string $locale = null): string
    {
        return self::localize(number_format($value, $decimals), $locale);
    }

    /**
     * Currency, with the taka sign under `bn` and the ISO code under `en`.
     */
    public static function currency(int|float $amount, ?string $locale = null): string
    {
        $locale ??= App::getLocale();
        $formatted = self::format($amount, 2, $locale);

        return $locale === 'bn' ? '৳'.$formatted : 'BDT '.$formatted;
    }
}
