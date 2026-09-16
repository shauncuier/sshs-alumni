<?php

declare(strict_types=1);

namespace App\Services\Communication\Sms;

use Illuminate\Support\Str;

/**
 * A message prepared for dispatch: normalised recipient, encoding and the
 * segment count it will actually cost.
 *
 * Bangla must be sent as Unicode, where a segment is 70 characters against 160
 * for GSM-7 Latin — roughly 2.3x the cost per character. That is computed here,
 * before anything is sent, so an administrator sees the real cost of a campaign
 * rather than discovering it on the invoice.
 *
 * @see docs/05-modules.md section 13
 */
final readonly class SmsMessage
{
    private function __construct(
        public string $to,
        public string $body,
        public bool $isUnicode,
        public int $segments,
    ) {}

    public static function make(string $to, string $body, ?string $countryCode = null): self
    {
        $normalised = self::normaliseNumber($to, $countryCode ?? (string) config('sms.country_code', '88'));
        $isUnicode = self::requiresUnicode($body);

        return new self(
            to: $normalised,
            body: $body,
            isUnicode: $isUnicode,
            segments: self::countSegments($body, $isUnicode),
        );
    }

    /**
     * `text` for GSM-7, `unicode` for anything containing Bengali or other
     * non-GSM characters. Sending Bangla as `text` produces mojibake at the
     * handset, so this is decided from the content, never from a global
     * setting.
     */
    public function type(): string
    {
        return $this->isUnicode ? 'unicode' : 'text';
    }

    public function isDeliverable(): bool
    {
        return $this->to !== '';
    }

    /**
     * Normalise to the format BulkSMSBD expects: `88` followed by the full
     * local 11-digit number INCLUDING its trunk zero — 8801712345678.
     *
     * The trunk zero is kept deliberately. Bangladesh's calling code is +880,
     * so `880` + `1712345678` and `88` + `01712345678` both spell the same
     * number; the vendor documents the latter, and that is what is sent.
     *
     * Accepts 01712345678, 8801712345678, +8801712345678, 1712345678 and
     * spaced or hyphenated variants. Returns an empty string when the input
     * cannot be a Bangladeshi mobile number, so the caller fails the recipient
     * locally rather than paying to send to a bad number.
     */
    public static function normaliseNumber(string $number, string $countryCode = '88'): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        if ($digits === '') {
            return '';
        }

        // Strip a leading country code, however the caller wrote it.
        if (strlen($digits) === 13 && str_starts_with($digits, $countryCode.'0')) {
            $digits = substr($digits, strlen($countryCode));
        } elseif (strlen($digits) === 12 && str_starts_with($digits, $countryCode)) {
            $digits = '0'.substr($digits, strlen($countryCode));
        }

        // Bare subscriber number, no trunk zero.
        if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0'.$digits;
        }

        // A Bangladeshi mobile number is 01 followed by an operator digit
        // (3-9) and eight more: 013…019 are the allocated prefixes.
        if (preg_match('/^01[3-9]\d{8}$/', $digits) !== 1) {
            return '';
        }

        return $countryCode.$digits;
    }

    /**
     * True when the body contains any character outside the GSM-7 alphabet —
     * which every Bengali character is.
     */
    public static function requiresUnicode(string $body): bool
    {
        return (bool) preg_match('/[^\x{0000}-\x{007F}]/u', $body);
    }

    /**
     * Segments this body will be billed as.
     *
     * Single: 160 GSM-7 / 70 Unicode.
     * Concatenated: 153 GSM-7 / 67 Unicode, because each part carries a
     * user-data header.
     */
    public static function countSegments(string $body, ?bool $isUnicode = null): int
    {
        $isUnicode ??= self::requiresUnicode($body);
        $length = Str::length($body);

        if ($length === 0) {
            return 0;
        }

        $single = $isUnicode ? 70 : 160;
        $multi = $isUnicode ? 67 : 153;

        if ($length <= $single) {
            return 1;
        }

        return (int) ceil($length / $multi);
    }
}
