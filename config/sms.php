<?php

declare(strict_types=1);

return [

    /*
    |---------------------------------------------------------------------
    | Default driver
    |---------------------------------------------------------------------
    |
    | `log` writes messages to the log and sends nothing. It is the default
    | outside production, so no test run or local experiment can spend real
    | SMS balance.
    |
    */

    'driver' => env('SMS_DRIVER', env('APP_ENV') === 'production' ? 'bulksmsbd' : 'log'),

    /*
    |---------------------------------------------------------------------
    | Master switch and spend guard
    |---------------------------------------------------------------------
    |
    | `enabled` turns off sending regardless of driver. `daily_cap` is a hard
    | ceiling on messages per calendar day — a runaway campaign halts instead
    | of draining the account.
    |
    */

    'enabled' => (bool) env('SMS_ENABLED', false),

    'daily_cap' => (int) env('SMS_DAILY_CAP', 2000),

    /*
    |---------------------------------------------------------------------
    | Country
    |---------------------------------------------------------------------
    |
    | Bangladeshi numbers are normalised to 88 + 11 digits before dispatch.
    | Anything that does not normalise is failed locally and never sent.
    |
    */

    'country_code' => env('SMS_COUNTRY_CODE', '88'),

    /*
    |---------------------------------------------------------------------
    | OTP
    |---------------------------------------------------------------------
    |
    | BulkSMSBD mandates the OTP body format:
    |
    |     Your {Brand/Company Name} OTP is XXXX
    |
    | A message that does not match is rejected at the gateway, so the format
    | is enforced in App\Services\Communication\Sms\OtpMessage rather than
    | left to a template an admin could edit.
    |
    | The brand name must be Latin-script: the format is English, and a Bangla
    | brand would push the message to Unicode and break it.
    |
    */

    'otp' => [
        'brand' => env('SMS_OTP_BRAND', 'SSHS Alumni'),
        'length' => (int) env('SMS_OTP_LENGTH', 6),
        'ttl_seconds' => (int) env('SMS_OTP_TTL', 300),
    ],

    /*
    |---------------------------------------------------------------------
    | Drivers
    |---------------------------------------------------------------------
    */

    'drivers' => [

        'log' => [
            'channel' => env('SMS_LOG_CHANNEL'),
        ],

        'bulksmsbd' => [
            // https://bulksmsbd.com/bulksms-api-bangladesh.php
            'base_url' => env('BULKSMSBD_BASE_URL', 'http://bulksmsbd.net/api'),

            'api_key' => env('BULKSMSBD_API_KEY'),

            // Must be pre-approved by the vendor. An unapproved sender id
            // returns error 1002 and every message fails.
            'sender_id' => env('BULKSMSBD_SENDER_ID'),

            'timeout' => (int) env('BULKSMSBD_TIMEOUT', 15),

            'retry_times' => (int) env('BULKSMSBD_RETRY_TIMES', 2),

            'retry_sleep_ms' => (int) env('BULKSMSBD_RETRY_SLEEP_MS', 500),

            // Cached so the campaign screen does not hit the vendor on every
            // page load.
            'balance_cache_seconds' => (int) env('BULKSMSBD_BALANCE_CACHE', 300),
        ],

    ],

];
