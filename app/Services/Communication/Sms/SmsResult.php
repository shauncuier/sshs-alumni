<?php

declare(strict_types=1);

namespace App\Services\Communication\Sms;

/**
 * The outcome of one send attempt.
 *
 * `code` is the provider's raw response code, surfaced to administrators
 * unchanged so a vendor-side problem is diagnosable without reading logs.
 *
 * @see docs/16-troubleshooting.md section 6
 */
final readonly class SmsResult
{
    public function __construct(
        public bool $ok,
        public string $to,
        public ?string $code = null,
        public ?string $message = null,
        public ?string $providerMessageId = null,
        public int $segments = 0,
        public bool $retryable = false,
        public bool $halt = false,
    ) {}

    public static function success(string $to, int $segments, ?string $providerMessageId = null): self
    {
        return new self(
            ok: true,
            to: $to,
            code: BulkSmsBdCode::SUBMITTED,
            message: 'Submitted successfully',
            providerMessageId: $providerMessageId,
            segments: $segments,
        );
    }

    public static function failure(
        string $to,
        ?string $code,
        ?string $message,
        bool $retryable = false,
        bool $halt = false,
    ): self {
        return new self(
            ok: false,
            to: $to,
            code: $code,
            message: $message,
            retryable: $retryable,
            halt: $halt,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'to' => $this->to,
            'code' => $this->code,
            'message' => $this->message,
            'provider_message_id' => $this->providerMessageId,
            'segments' => $this->segments,
            'retryable' => $this->retryable,
            'halt' => $this->halt,
        ];
    }
}
