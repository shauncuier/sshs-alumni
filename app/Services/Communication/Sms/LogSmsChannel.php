<?php

declare(strict_types=1);

namespace App\Services\Communication\Sms;

use App\Services\Communication\Contracts\SmsChannel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Writes messages to the log and sends nothing.
 *
 * This is the default driver outside production, which is what guarantees no
 * test run, seeder or local experiment can spend real SMS balance.
 *
 * @see docs/14-testing.md section 6
 */
final class LogSmsChannel implements SmsChannel
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config = []) {}

    public function name(): string
    {
        return 'log';
    }

    public function send(string $to, string $message): SmsResult
    {
        $sms = SmsMessage::make($to, $message);

        if (! $sms->isDeliverable()) {
            return SmsResult::failure(
                to: $to,
                code: BulkSmsBdCode::INVALID_NUMBER,
                message: BulkSmsBdCode::describe(BulkSmsBdCode::INVALID_NUMBER),
            );
        }

        Log::channel($this->config['channel'] ?? null)->info('SMS (not sent — log driver)', [
            'to' => $sms->to,
            'type' => $sms->type(),
            'segments' => $sms->segments,
            'body' => $sms->body,
        ]);

        return SmsResult::success($sms->to, $sms->segments, 'log-'.uniqid());
    }

    public function sendMany(array $messages): Collection
    {
        return Collection::make($messages)
            ->map(fn (array $message): SmsResult => $this->send($message['to'], $message['message']))
            ->values();
    }

    public function balance(): ?float
    {
        return null;
    }
}
