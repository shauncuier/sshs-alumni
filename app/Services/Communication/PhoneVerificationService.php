<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Services\Communication\Sms\OtpMessage;
use App\Services\Communication\Sms\SmsMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Handles generating, dispatching, and verifying one-time passwords (OTP)
 * for phone verification during member onboarding.
 *
 * Implements security safeguards:
 * - Constant-time comparison via Hash::check to avoid timing attacks.
 * - Rate limiting on dispatch (cooldown + window limit) to avoid SMS spam/cost drainage.
 * - Max verification attempts per code to prevent brute-force attacks.
 *
 * @see docs/05-modules.md section 13
 */
class PhoneVerificationService
{
    private const CACHE_PREFIX = 'phone_otp:';

    private const SEND_LIMIT_PER_WINDOW = 4;

    private const SEND_WINDOW_SECONDS = 600; // 10 minutes

    private const COOLDOWN_SECONDS = 60; // 1 minute between resends

    private const MAX_VERIFY_ATTEMPTS = 5;

    public function __construct(
        private readonly SmsManager $sms,
    ) {}

    /**
     * Normalise phone number to Bangladeshi mobile format (8801XXXXXXXXX).
     */
    public function normalise(string $phone): string
    {
        return SmsMessage::normaliseNumber($phone);
    }

    /**
     * Check if a phone number is valid for sending an OTP.
     */
    public function isValid(string $phone): bool
    {
        return $this->normalise($phone) !== '';
    }

    /**
     * Number of seconds until the user can request another OTP.
     */
    public function getCooldownRemaining(string $phone): int
    {
        $normalized = $this->normalise($phone);
        if ($normalized === '') {
            return 0;
        }

        $cooldownKey = "otp_cooldown:{$normalized}";

        return (int) RateLimiter::availableIn($cooldownKey);
    }

    /**
     * Send an OTP code to the given phone number.
     *
     * @return array{
     *     success: bool,
     *     message: string,
     *     cooldown: int,
     *     expires_in: int,
     *     debug_code?: string
     * }
     */
    public function send(string $phone): array
    {
        $normalized = $this->normalise($phone);

        if ($normalized === '') {
            return [
                'success' => false,
                'message' => __('public.join.otp.invalid_number', ['default' => 'Please provide a valid Bangladeshi mobile number.']),
                'cooldown' => 0,
                'expires_in' => 0,
            ];
        }

        // 1. Check cooldown (e.g., 60 seconds between resends)
        $cooldownKey = "otp_cooldown:{$normalized}";
        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            $seconds = RateLimiter::availableIn($cooldownKey);

            return [
                'success' => false,
                'message' => __('public.join.otp.cooldown_active', [
                    'seconds' => $seconds,
                    'default' => "Please wait {$seconds} seconds before requesting another code.",
                ]),
                'cooldown' => $seconds,
                'expires_in' => $this->ttl(),
            ];
        }

        // 2. Check total requests in window (max 4 per 10 minutes)
        $throttleKey = "otp_throttle:{$normalized}";
        if (RateLimiter::tooManyAttempts($throttleKey, self::SEND_LIMIT_PER_WINDOW)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return [
                'success' => false,
                'message' => __('public.join.otp.too_many_attempts', [
                    'default' => 'Too many requests. Please try again in a few minutes.',
                ]),
                'cooldown' => $seconds,
                'expires_in' => 0,
            ];
        }

        // 3. Generate secure numeric code
        $length = (int) config('sms.otp.length', 6);
        $min = (int) (10 ** ($length - 1));
        $max = (int) ((10 ** $length) - 1);
        $code = (string) random_int($min, $max);

        // 4. Store hashed code in cache with expiration
        $ttl = $this->ttl();
        Cache::put(self::CACHE_PREFIX.$normalized, [
            'hash' => Hash::make($code),
            'attempts' => 0,
            'sent_at' => now()->timestamp,
        ], $ttl);

        // Record rate limiters
        RateLimiter::hit($cooldownKey, self::COOLDOWN_SECONDS);
        RateLimiter::hit($throttleKey, self::SEND_WINDOW_SECONDS);

        // 5. Dispatch SMS
        $brand = (string) config('sms.otp.brand', 'SSHS Alumni');
        $otpMessage = OtpMessage::make($code, $brand);
        $result = $this->sms->send($normalized, $otpMessage->body);

        $response = [
            'success' => true,
            'message' => __('public.join.otp.sent_success', ['default' => 'Verification code sent via SMS.']),
            'cooldown' => self::COOLDOWN_SECONDS,
            'expires_in' => $ttl,
        ];

        // In testing or local environment, expose debug_code if SMS driver is log
        if (config('sms.driver') === 'log' || config('app.debug')) {
            $response['debug_code'] = $code;
        }

        if (! $result->ok && config('sms.driver') !== 'log') {
            return [
                'success' => false,
                'message' => __('public.join.otp.send_failed', ['default' => 'Failed to send verification SMS. Please try again.']),
                'cooldown' => 0,
                'expires_in' => 0,
            ];
        }

        return $response;
    }

    /**
     * Verify a submitted OTP code for a phone number.
     *
     * @return array{success: bool, message: string}
     */
    public function verify(string $phone, string $code): array
    {
        $normalized = $this->normalise($phone);

        if ($normalized === '') {
            return [
                'success' => false,
                'message' => __('public.join.otp.invalid_number', ['default' => 'Invalid phone number.']),
            ];
        }

        $code = trim($code);
        $cacheKey = self::CACHE_PREFIX.$normalized;
        /** @var array{hash: string, attempts: int, sent_at: int}|null $data */
        $data = Cache::get($cacheKey);

        if ($data === null) {
            return [
                'success' => false,
                'message' => __('public.join.otp.expired_or_missing', ['default' => 'Verification code has expired. Please request a new one.']),
            ];
        }

        // Enforce max attempts
        if ($data['attempts'] >= self::MAX_VERIFY_ATTEMPTS) {
            Cache::forget($cacheKey);

            return [
                'success' => false,
                'message' => __('public.join.otp.max_attempts_reached', ['default' => 'Too many failed attempts. Please request a new code.']),
            ];
        }

        if (! Hash::check($code, $data['hash'])) {
            $data['attempts']++;
            Cache::put($cacheKey, $data, $this->ttl());

            $remaining = self::MAX_VERIFY_ATTEMPTS - $data['attempts'];

            return [
                'success' => false,
                'message' => __('public.join.otp.invalid_code', [
                    'remaining' => $remaining,
                    'default' => "Invalid code. {$remaining} attempts remaining.",
                ]),
            ];
        }

        // Successfully verified: forget OTP from cache
        Cache::forget($cacheKey);

        return [
            'success' => true,
            'message' => __('public.join.otp.verify_success', ['default' => 'Phone number verified successfully.']),
        ];
    }

    /**
     * Invalidate any pending OTP for the given phone number.
     */
    public function clear(string $phone): void
    {
        $normalized = $this->normalise($phone);
        if ($normalized !== '') {
            Cache::forget(self::CACHE_PREFIX.$normalized);
            RateLimiter::clear("otp_cooldown:{$normalized}");
        }
    }

    private function ttl(): int
    {
        return (int) config('sms.otp.ttl_seconds', 300);
    }
}
