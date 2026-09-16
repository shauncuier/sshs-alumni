<?php

declare(strict_types=1);

use App\Services\Communication\Sms\SmsMessage;

describe('number normalisation', function (): void {
    it('normalises every common Bangladeshi mobile format to 88 plus ten digits', function (string $input): void {
        expect(SmsMessage::normaliseNumber($input))->toBe('8801712345678');
    })->with([
        '01712345678',
        '8801712345678',
        '+8801712345678',
        '+880 1712 345678',
        '017-1234-5678',
        ' 01712345678 ',
    ]);

    it('rejects anything that cannot be a Bangladeshi mobile number', function (string $input): void {
        expect(SmsMessage::normaliseNumber($input))->toBe('');
    })->with([
        '',
        'not a number',
        '12345',
        '02123456789',      // landline, does not start with 1 after the trunk zero
        '0171234567',       // too short
        '017123456789',     // too long
    ]);

    it('marks a message to an unusable number as undeliverable', function (): void {
        expect(SmsMessage::make('12345', 'hello')->isDeliverable())->toBeFalse()
            ->and(SmsMessage::make('01712345678', 'hello')->isDeliverable())->toBeTrue();
    });
});

describe('encoding detection', function (): void {
    it('sends Latin text as GSM-7', function (): void {
        $message = SmsMessage::make('01712345678', 'Your membership is approved.');

        expect($message->isUnicode)->toBeFalse()
            ->and($message->type())->toBe('text');
    });

    it('sends Bangla as unicode, because sending it as text produces mojibake', function (): void {
        $message = SmsMessage::make('01712345678', 'আপনার সদস্যপদ অনুমোদিত হয়েছে।');

        expect($message->isUnicode)->toBeTrue()
            ->and($message->type())->toBe('unicode');
    });

    it('treats a mixed Bangla and Latin message as unicode', function (): void {
        $message = SmsMessage::make('01712345678', 'Golden Jubilee — সুবর্ণজয়ন্তী ২০২৬');

        expect($message->type())->toBe('unicode');
    });
});

describe('segment counting', function (): void {
    it('bills Latin at 160 characters for a single segment', function (): void {
        expect(SmsMessage::countSegments(str_repeat('a', 160)))->toBe(1)
            ->and(SmsMessage::countSegments(str_repeat('a', 161)))->toBe(2);
    });

    it('bills Bangla at 70 characters for a single segment, not 160', function (): void {
        expect(SmsMessage::countSegments(str_repeat('অ', 70)))->toBe(1)
            ->and(SmsMessage::countSegments(str_repeat('অ', 71)))->toBe(2);
    });

    it('uses the shorter concatenated limits once a message spans segments', function (): void {
        // Latin: 153 per part once concatenated, so 306 fits in exactly two.
        expect(SmsMessage::countSegments(str_repeat('a', 306)))->toBe(2)
            ->and(SmsMessage::countSegments(str_repeat('a', 307)))->toBe(3);

        // Unicode: 67 per part, so 134 fits in exactly two.
        expect(SmsMessage::countSegments(str_repeat('অ', 134)))->toBe(2)
            ->and(SmsMessage::countSegments(str_repeat('অ', 135)))->toBe(3);
    });

    it('counts nothing for an empty body', function (): void {
        expect(SmsMessage::countSegments(''))->toBe(0);
    });

    it('makes Bangla measurably more expensive than the same length of Latin', function (): void {
        $length = 200;

        $latin = SmsMessage::countSegments(str_repeat('a', $length));
        $bangla = SmsMessage::countSegments(str_repeat('অ', $length));

        expect($bangla)->toBeGreaterThan($latin);
    });
});
