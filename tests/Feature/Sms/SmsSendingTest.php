<?php

declare(strict_types=1);

use App\Services\Communication\Contracts\SmsChannel;
use App\Services\Communication\Sms\BulkSmsBdChannel;
use App\Services\Communication\Sms\BulkSmsBdCode;
use App\Services\Communication\Sms\LogSmsChannel;
use App\Services\Communication\Sms\OtpMessage;
use App\Services\Communication\SmsManager;
use Illuminate\Support\Facades\Http;

describe('test safety', function (): void {
    it('never resolves the real gateway during tests', function (): void {
        expect(app(SmsChannel::class))->toBeInstanceOf(LogSmsChannel::class);
    });

    it('defaults to the log driver outside production', function (): void {
        expect(config('sms.driver'))->toBe('log');
    });
});

describe('the OTP format BulkSMSBD requires', function (): void {
    it('builds the exact vendor-mandated body', function (): void {
        $otp = OtpMessage::make('123456', 'SSHS Alumni');

        expect($otp->body)->toBe('Your SSHS Alumni OTP is 123456');
    });

    it('stays ASCII so it is GSM-7 and not Unicode', function (): void {
        $otp = OtpMessage::make('4321', 'SSHS Alumni');

        // A Unicode OTP would cost 70 characters per segment and break the
        // required English format.
        expect(preg_match('/[^\x{0000}-\x{007F}]/u', $otp->body))->toBe(0);
    });

    it('refuses a Bangla brand name, which would break the format', function (): void {
        config(['sms.otp.brand' => 'প্রাক্তন ছাত্র-ছাত্রী পরিষদ']);

        expect(fn () => OtpMessage::make('123456'))
            ->toThrow(InvalidArgumentException::class);
    });

    it('strips characters that would corrupt the format', function (): void {
        expect(OtpMessage::make('123456', 'SSHS  Alumni!!! ✨')->body)
            ->toBe('Your SSHS Alumni OTP is 123456');
    });

    it('rejects a code that is not 4 to 8 alphanumeric characters', function (string $code): void {
        expect(fn () => OtpMessage::make($code, 'SSHS Alumni'))
            ->toThrow(InvalidArgumentException::class);
    })->with(['', '12', '1234567890', 'abc def', '12-34']);

    it('is deliberately not translated', function (): void {
        // The vendor format is English. Localising it would get every OTP
        // rejected at the gateway.
        app()->setLocale('bn');

        expect(OtpMessage::make('123456', 'SSHS Alumni')->body)
            ->toBe('Your SSHS Alumni OTP is 123456');
    });
});

describe('the log driver', function (): void {
    it('reports success without contacting anyone', function (): void {
        Http::fake();

        $result = app(SmsManager::class)->send('01712345678', 'Hello');

        expect($result->ok)->toBeTrue()
            ->and($result->to)->toBe('8801712345678');

        Http::assertNothingSent();
    });

    it('fails an unusable number locally instead of paying to send it', function (): void {
        $result = app(SmsManager::class)->send('12345', 'Hello');

        expect($result->ok)->toBeFalse()
            ->and($result->code)->toBe(BulkSmsBdCode::INVALID_NUMBER);
    });
});

describe('the BulkSMSBD driver', function (): void {
    beforeEach(function (): void {
        config([
            'sms.driver' => 'bulksmsbd',
            'sms.enabled' => true,
            'sms.drivers.bulksmsbd.api_key' => 'test-key',
            'sms.drivers.bulksmsbd.sender_id' => 'TESTSENDER',
            'sms.drivers.bulksmsbd.retry_times' => 1,
        ]);
    });

    it('treats 202 as success', function (): void {
        Http::fake(['bulksmsbd.net/*' => Http::response(['response_code' => 202], 200)]);

        $result = (new SmsManager)->send('01712345678', 'Hello');

        expect($result->ok)->toBeTrue()
            ->and($result->code)->toBe('202');
    });

    it('sends Bangla as unicode', function (): void {
        Http::fake(['bulksmsbd.net/*' => Http::response(['response_code' => 202], 200)]);

        (new SmsManager)->send('01712345678', 'আপনার সদস্যপদ অনুমোদিত');

        Http::assertSent(fn ($request) => $request['type'] === 'unicode');
    });

    it('sends Latin as text', function (): void {
        Http::fake(['bulksmsbd.net/*' => Http::response(['response_code' => 202], 200)]);

        (new SmsManager)->send('01712345678', 'Membership approved');

        Http::assertSent(fn ($request) => $request['type'] === 'text');
    });

    it('halts the campaign on insufficient balance rather than burning retries', function (): void {
        Http::fake(['bulksmsbd.net/*' => Http::response(['response_code' => 1007], 200)]);

        $result = (new SmsManager)->send('01712345678', 'Hello');

        expect($result->ok)->toBeFalse()
            ->and($result->halt)->toBeTrue()
            ->and($result->retryable)->toBeFalse();
    });

    it('halts on a disabled sender id, which is a configuration error', function (): void {
        Http::fake(['bulksmsbd.net/*' => Http::response(['response_code' => 1002], 200)]);

        expect((new SmsManager)->send('01712345678', 'Hello')->halt)->toBeTrue();
    });

    it('marks an internal provider error as retryable', function (): void {
        Http::fake(['bulksmsbd.net/*' => Http::response(['response_code' => 1005], 200)]);

        $result = (new SmsManager)->send('01712345678', 'Hello');

        expect($result->retryable)->toBeTrue()
            ->and($result->halt)->toBeFalse();
    });

    it('stops a bulk send at the first halting response', function (): void {
        Http::fake(['bulksmsbd.net/*' => Http::response(['response_code' => 1007], 200)]);

        $results = (new SmsManager)->sendMany([
            ['to' => '01712345678', 'message' => 'One'],
            ['to' => '01712345679', 'message' => 'Two'],
            ['to' => '01712345670', 'message' => 'Three'],
        ]);

        expect($results)->toHaveCount(1);
    });

    it('parses a bare response code as well as JSON', function (): void {
        Http::fake(['bulksmsbd.net/*' => Http::response('202', 200)]);

        expect((new SmsManager)->send('01712345678', 'Hello')->ok)->toBeTrue();
    });

    it('never leaks the API key into the result', function (): void {
        Http::fake(['bulksmsbd.net/*' => Http::response(['response_code' => 1007], 200)]);

        $result = (new SmsManager)->send('01712345678', 'Hello');

        expect(json_encode($result->toArray()))->not->toContain('test-key');
    });

    it('is the driver the manager resolves when configured', function (): void {
        expect((new SmsManager)->driver())->toBeInstanceOf(BulkSmsBdChannel::class);
    });
});

describe('guards above the transport', function (): void {
    it('refuses to send when SMS_ENABLED is false', function (): void {
        config(['sms.enabled' => false]);

        $result = (new SmsManager)->send('01712345678', 'Hello');

        expect($result->ok)->toBeFalse()
            ->and($result->halt)->toBeTrue();
    });

    it('halts once the daily cap is reached', function (): void {
        config(['sms.enabled' => true, 'sms.daily_cap' => 2]);

        $manager = new SmsManager;
        $manager->send('01712345678', str_repeat('a', 10));
        $manager->send('01712345679', str_repeat('a', 10));

        expect($manager->send('01712345670', 'Third')->halt)->toBeTrue();
    });

    it('estimates cost before sending, showing Bangla as the more expensive option', function (): void {
        $manager = new SmsManager;

        $latin = $manager->estimate(str_repeat('a', 200), 100);
        $bangla = $manager->estimate(str_repeat('অ', 200), 100);

        expect($latin['is_unicode'])->toBeFalse()
            ->and($bangla['is_unicode'])->toBeTrue()
            ->and($bangla['total_segments'])->toBeGreaterThan($latin['total_segments'])
            ->and($latin['recipients'])->toBe(100);
    });

    it('throws clearly on an unknown driver', function (): void {
        expect(fn () => (new SmsManager)->driver('carrier-pigeon'))
            ->toThrow(InvalidArgumentException::class);
    });
});
