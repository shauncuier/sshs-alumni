<?php

declare(strict_types=1);

namespace App\Services\Communication\Sms;

use App\Services\Communication\Contracts\SmsChannel;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * BulkSMSBD (bulksmsbd.net) transport.
 *
 * Plain HTTP API, so no composer package is required — this uses Laravel's own
 * HTTP client.
 *
 * Endpoints (vendor documentation):
 *   /smsapi        one message, one or more comma-separated recipients
 *   /smsapimany    a different message per recipient
 *   /getBalanceApi remaining balance
 *
 * The API key is read from config and NEVER logged, never returned in a result,
 * and never written into campaign_recipients.error.
 *
 * @see docs/05-modules.md section 13
 */
final class BulkSmsBdChannel implements SmsChannel
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(private readonly array $config) {}

    public function name(): string
    {
        return 'bulksmsbd';
    }

    public function send(string $to, string $message): SmsResult
    {
        $sms = SmsMessage::make($to, $message);

        if (! $sms->isDeliverable()) {
            // Never pay to send to a number that cannot be valid.
            return SmsResult::failure(
                to: $to,
                code: BulkSmsBdCode::INVALID_NUMBER,
                message: BulkSmsBdCode::describe(BulkSmsBdCode::INVALID_NUMBER),
            );
        }

        $result = $this->dispatch($sms);

        // The vendor rejects Bangla sent as `text`. Retry once as unicode
        // rather than failing a message that is merely mis-encoded.
        if (BulkSmsBdCode::needsUnicodeRetry($result->code) && ! $sms->isUnicode) {
            return $this->dispatch($sms, forceUnicode: true);
        }

        return $result;
    }

    public function sendMany(array $messages): Collection
    {
        return Collection::make($messages)
            ->map(fn (array $message): SmsResult => $this->send($message['to'], $message['message']))
            ->values();
    }

    public function balance(): ?float
    {
        $seconds = (int) ($this->config['balance_cache_seconds'] ?? 300);

        return Cache::remember('sms.bulksmsbd.balance', $seconds, function (): ?float {
            try {
                $response = $this->client()->get($this->url('getBalanceApi'), [
                    'api_key' => $this->config['api_key'],
                ]);

                if ($response->failed()) {
                    return null;
                }

                $body = trim($response->body());
                $json = json_decode($body, true);

                if (is_array($json)) {
                    $value = $json['balance'] ?? $json['response_code'] ?? null;

                    return is_numeric($value) ? (float) $value : null;
                }

                return is_numeric($body) ? (float) $body : null;
            } catch (Throwable $e) {
                $this->logFailure('balance lookup failed', $e);

                return null;
            }
        });
    }

    /**
     * One provider call.
     */
    private function dispatch(SmsMessage $sms, bool $forceUnicode = false): SmsResult
    {
        try {
            $response = $this->client()->asForm()->post($this->url('smsapi'), [
                'api_key' => $this->config['api_key'],
                'senderid' => $this->config['sender_id'],
                'number' => $sms->to,
                'message' => $sms->body,
                'type' => $forceUnicode ? 'unicode' : $sms->type(),
            ]);
        } catch (Throwable $e) {
            $this->logFailure('send failed', $e);

            return SmsResult::failure(
                to: $sms->to,
                code: BulkSmsBdCode::INTERNAL_ERROR,
                message: 'Transport error',
                retryable: true,
            );
        }

        if ($response->failed()) {
            return SmsResult::failure(
                to: $sms->to,
                code: BulkSmsBdCode::INTERNAL_ERROR,
                message: 'HTTP '.$response->status(),
                retryable: true,
            );
        }

        [$code, $providerMessageId] = $this->parse($response->body());

        if (BulkSmsBdCode::isSuccess($code)) {
            return SmsResult::success($sms->to, $sms->segments, $providerMessageId);
        }

        return SmsResult::failure(
            to: $sms->to,
            code: $code,
            message: BulkSmsBdCode::describe($code),
            retryable: BulkSmsBdCode::isRetryable($code),
            halt: BulkSmsBdCode::shouldHalt($code),
        );
    }

    /**
     * The vendor returns JSON in the documented case and a bare code in
     * others, so both shapes are handled.
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function parse(string $body): array
    {
        $body = trim($body);
        $json = json_decode($body, true);

        if (is_array($json)) {
            $code = $json['response_code'] ?? null;
            $messageId = $json['message_id'] ?? null;

            return [
                $code === null ? null : (string) $code,
                $messageId === null ? null : (string) $messageId,
            ];
        }

        if (preg_match('/\b(202|1\d{3})\b/', $body, $match) === 1) {
            return [$match[1], null];
        }

        return [null, null];
    }

    private function client(): PendingRequest
    {
        return Http::timeout((int) ($this->config['timeout'] ?? 15))
            ->retry(
                (int) ($this->config['retry_times'] ?? 2),
                (int) ($this->config['retry_sleep_ms'] ?? 500),
                throw: false,
            );
    }

    private function url(string $endpoint): string
    {
        return rtrim((string) $this->config['base_url'], '/').'/'.$endpoint;
    }

    /**
     * Logs the failure without the API key. Only the exception class and
     * message are recorded — never the request payload.
     */
    private function logFailure(string $context, Throwable $e): void
    {
        Log::warning('BulkSMSBD '.$context, [
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ]);
    }
}
