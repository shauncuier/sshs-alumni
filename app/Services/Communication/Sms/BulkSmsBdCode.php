<?php

declare(strict_types=1);

namespace App\Services\Communication\Sms;

/**
 * BulkSMSBD response codes and what the platform does about each.
 *
 * Source: the vendor's developer documentation at
 * https://bulksmsbd.com/bulksms-api-bangladesh.php
 *
 * The distinction that matters operationally:
 *
 *  - RETRYABLE  — a transient vendor fault; retry with backoff.
 *  - HALT       — money or configuration is wrong. Stop the whole campaign and
 *                 alert an administrator rather than burning retries, or in the
 *                 case of insufficient balance, rather than failing every
 *                 remaining recipient one at a time.
 *  - otherwise  — fail this recipient and continue.
 *
 * @see docs/16-troubleshooting.md section 6
 */
final class BulkSmsBdCode
{
    public const SUBMITTED = '202';

    public const INVALID_NUMBER = '1001';

    public const SENDER_ID_INVALID = '1002';

    public const MISSING_FIELDS = '1003';

    public const INTERNAL_ERROR = '1005';

    public const BALANCE_VALIDITY_UNAVAILABLE = '1006';

    public const INSUFFICIENT_BALANCE = '1007';

    public const USER_NOT_FOUND = '1011';

    public const BANGLA_MASKING_REQUIRED = '1012';

    /**
     * Transient vendor-side faults worth retrying.
     *
     * @var array<int, string>
     */
    private const RETRYABLE = [
        self::INTERNAL_ERROR,
    ];

    /**
     * Conditions where continuing is pointless or expensive: the account, the
     * sender id, the balance or the gateway configuration is wrong.
     *
     * 1013–1021 are the vendor's gateway, pricing and account configuration
     * errors; they are all account-level, so they halt too.
     *
     * @var array<int, string>
     */
    private const HALTING = [
        self::SENDER_ID_INVALID,
        self::BALANCE_VALIDITY_UNAVAILABLE,
        self::INSUFFICIENT_BALANCE,
        self::USER_NOT_FOUND,
        '1013', '1014', '1015', '1016', '1017',
        '1018', '1019', '1020', '1021',
    ];

    public static function isSuccess(?string $code): bool
    {
        return $code === self::SUBMITTED;
    }

    public static function isRetryable(?string $code): bool
    {
        return $code !== null && in_array($code, self::RETRYABLE, true);
    }

    public static function shouldHalt(?string $code): bool
    {
        return $code !== null && in_array($code, self::HALTING, true);
    }

    /**
     * A Bangla message was sent as `text`. Resending as `unicode` fixes it.
     */
    public static function needsUnicodeRetry(?string $code): bool
    {
        return $code === self::BANGLA_MASKING_REQUIRED;
    }

    /**
     * Human-readable description, resolved through the active locale.
     */
    public static function describe(?string $code): string
    {
        if ($code === null) {
            return (string) __('sms.codes.unknown');
        }

        $key = 'sms.codes.'.$code;
        $described = __($key);

        return is_string($described) && $described !== $key
            ? $described
            : (string) __('sms.codes.unknown_code', ['code' => $code]);
    }
}
