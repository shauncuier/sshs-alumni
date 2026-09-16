<?php

declare(strict_types=1);

namespace App\Services\Communication\Contracts;

use App\Services\Communication\Sms\SmsResult;
use Illuminate\Support\Collection;

/**
 * An SMS transport.
 *
 * BulkSmsBdChannel is the production implementation; LogSmsChannel is the
 * default everywhere else. Adding another Bangladeshi gateway, or WhatsApp
 * Business, means one new class and one config entry — no caller changes.
 *
 * @see docs/09-payments.md for the same pattern applied to payment gateways
 */
interface SmsChannel
{
    /**
     * The driver name, for logging and audit rows.
     */
    public function name(): string;

    /**
     * Send one message. Never throws for a provider-level rejection — the
     * failure is returned so the caller can record it against the recipient.
     */
    public function send(string $to, string $message): SmsResult;

    /**
     * Send many messages in as few provider calls as the transport allows.
     *
     * @param  array<int, array{to: string, message: string}>  $messages
     * @return Collection<int, SmsResult>
     */
    public function sendMany(array $messages): Collection;

    /**
     * Remaining account balance, or null when the provider cannot report it.
     */
    public function balance(): ?float;
}
