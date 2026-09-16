<?php

declare(strict_types=1);

namespace App\Services\Communication\Sms;

use InvalidArgumentException;

/**
 * Builds OTP message bodies in the exact format BulkSMSBD requires.
 *
 *     Your {Brand/Company Name} OTP is XXXX
 *
 * This is a vendor CONTENT POLICY, not a style preference: an OTP message that
 * does not match the format is rejected or blocked at the gateway, so the
 * format is enforced in code rather than left to whoever writes the template.
 *
 * Two consequences worth knowing:
 *
 *  1. The body is ENGLISH and must stay English. It is deliberately NOT passed
 *     through the translator — a Bangla OTP would break the required format.
 *     This is the one user-facing string in the platform that is not localised,
 *     and it is not an oversight.
 *
 *  2. Because it is Latin-only, the message is GSM-7 and bills at 160
 *     characters per segment rather than Unicode's 70 — so a correctly
 *     formatted OTP is also the cheapest one.
 *
 * @see docs/05-modules.md section 13
 */
final readonly class OtpMessage
{
    /**
     * The vendor-mandated template. `:brand` and `:code` are the only
     * substitutions; the surrounding words are fixed.
     */
    public const TEMPLATE = 'Your :brand OTP is :code';

    private function __construct(
        public string $brand,
        public string $code,
        public string $body,
    ) {}

    public static function make(string $code, ?string $brand = null): self
    {
        $code = trim($code);

        if ($code === '' || preg_match('/^[A-Za-z0-9]{4,8}$/', $code) !== 1) {
            throw new InvalidArgumentException(
                'An OTP code must be 4-8 alphanumeric characters.'
            );
        }

        $brand = self::sanitiseBrand($brand ?? (string) config('sms.otp.brand', config('app.name')));

        $body = str_replace(
            [':brand', ':code'],
            [$brand, $code],
            self::TEMPLATE,
        );

        // A Bangla or emoji-bearing brand name would silently push the message
        // to Unicode and break the required English format. Fail loudly here
        // rather than having the gateway reject every OTP in production.
        if (SmsMessage::requiresUnicode($body)) {
            throw new InvalidArgumentException(
                'OTP messages must be ASCII-only to satisfy the BulkSMSBD format. '
                .'Set SMS_OTP_BRAND to a Latin-script brand name.'
            );
        }

        return new self($brand, $code, $body);
    }

    /**
     * Strip anything that would break the format or the encoding: the brand
     * segment allows letters, digits, spaces, hyphens, ampersands and dots.
     */
    private static function sanitiseBrand(string $brand): string
    {
        $clean = preg_replace('/[^A-Za-z0-9 .&-]+/', '', $brand) ?? '';
        $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? '', " \t\n\r.&-");

        // A Bangla brand strips down to its punctuation ("-"), not to an empty
        // string, so requiring an actual alphanumeric character is what
        // catches it.
        if (preg_match('/[A-Za-z0-9]/', $clean) !== 1) {
            throw new InvalidArgumentException(
                'The OTP brand name is empty after sanitisation. '
                .'Set SMS_OTP_BRAND to a Latin-script brand name.'
            );
        }

        return $clean;
    }

    public function __toString(): string
    {
        return $this->body;
    }
}
