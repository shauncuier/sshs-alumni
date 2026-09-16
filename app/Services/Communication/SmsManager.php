<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Services\Communication\Contracts\SmsChannel;
use App\Services\Communication\Sms\BulkSmsBdChannel;
use App\Services\Communication\Sms\BulkSmsBdCode;
use App\Services\Communication\Sms\LogSmsChannel;
use App\Services\Communication\Sms\SmsMessage;
use App\Services\Communication\Sms\SmsResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

/**
 * Resolves the configured SMS driver and enforces the guards that sit above
 * any individual transport: the master switch and the daily spend cap.
 *
 * @see docs/05-modules.md section 13
 */
class SmsManager
{
    private ?SmsChannel $driver = null;

    public function driver(?string $name = null): SmsChannel
    {
        if ($name === null && $this->driver !== null) {
            return $this->driver;
        }

        $name ??= (string) config('sms.driver', 'log');

        /** @var array<string, mixed>|null $config */
        $config = config('sms.drivers.'.$name);

        if ($config === null) {
            throw new InvalidArgumentException(
                "SMS driver [{$name}] is not configured. Check config/sms.php."
            );
        }

        $channel = match ($name) {
            'bulksmsbd' => new BulkSmsBdChannel($config),
            'log' => new LogSmsChannel($config),
            default => throw new InvalidArgumentException("Unknown SMS driver [{$name}]."),
        };

        if ($name === (string) config('sms.driver', 'log')) {
            $this->driver = $channel;
        }

        return $channel;
    }

    /**
     * Send one message, subject to the master switch and the daily cap.
     */
    public function send(string $to, string $message): SmsResult
    {
        if (! $this->isEnabled()) {
            return SmsResult::failure(
                to: $to,
                code: null,
                message: 'SMS sending is disabled (SMS_ENABLED=false).',
                halt: true,
            );
        }

        if ($this->capReached()) {
            return SmsResult::failure(
                to: $to,
                code: null,
                message: 'Daily SMS cap reached.',
                halt: true,
            );
        }

        $result = $this->driver()->send($to, $message);

        if ($result->ok) {
            $this->recordSent($result->segments);
        }

        return $result;
    }

    /**
     * Send many, stopping the moment the provider reports a condition where
     * continuing is pointless — insufficient balance, a disabled sender id, a
     * bad API key. Remaining recipients are returned as halted rather than
     * being failed one expensive call at a time.
     *
     * @param  array<int, array{to: string, message: string}>  $messages
     * @return Collection<int, SmsResult>
     */
    public function sendMany(array $messages): Collection
    {
        $results = new Collection;

        foreach ($messages as $message) {
            $result = $this->send($message['to'], $message['message']);
            $results->push($result);

            if ($result->halt) {
                break;
            }
        }

        return $results;
    }

    public function balance(): ?float
    {
        return $this->driver()->balance();
    }

    public function isEnabled(): bool
    {
        return (bool) config('sms.enabled', false);
    }

    /**
     * What a campaign body will cost before anything is sent.
     *
     * Bangla is Unicode at 70 characters per segment against 160 for Latin, so
     * an administrator must see this before committing to a send.
     *
     * @return array{is_unicode: bool, characters: int, segments: int, recipients: int, total_segments: int}
     */
    public function estimate(string $body, int $recipients): array
    {
        $isUnicode = SmsMessage::requiresUnicode($body);
        $segments = SmsMessage::countSegments($body, $isUnicode);

        return [
            'is_unicode' => $isUnicode,
            'characters' => mb_strlen($body),
            'segments' => $segments,
            'recipients' => $recipients,
            'total_segments' => $segments * $recipients,
        ];
    }

    /**
     * Human description of a provider code, for the admin UI.
     */
    public function describeCode(?string $code): string
    {
        return BulkSmsBdCode::describe($code);
    }

    public function sentToday(): int
    {
        return (int) Cache::get($this->capKey(), 0);
    }

    public function remainingToday(): int
    {
        return max(0, $this->dailyCap() - $this->sentToday());
    }

    private function capReached(): bool
    {
        return $this->sentToday() >= $this->dailyCap();
    }

    private function dailyCap(): int
    {
        return (int) config('sms.daily_cap', 2000);
    }

    private function recordSent(int $segments): void
    {
        // Segments, not messages: a three-segment Bangla message costs three.
        Cache::put(
            $this->capKey(),
            $this->sentToday() + max(1, $segments),
            now()->endOfDay(),
        );
    }

    private function capKey(): string
    {
        return 'sms.sent.'.now()->toDateString();
    }
}
