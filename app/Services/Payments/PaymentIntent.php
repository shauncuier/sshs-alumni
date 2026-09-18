<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\PaymentStatus;

/**
 * What a gateway hands back from `charge()`.
 *
 * Two shapes, one type: a payment that is already settled (manual entry, where
 * the cash is in hand) and one that needs the payer to go somewhere. Callers
 * branch on `redirectUrl`, not on which driver they happen to be using.
 *
 * @see docs/09-payments.md section 2
 */
final readonly class PaymentIntent
{
    /**
     * @param  array<string, mixed>  $meta
     */
    private function __construct(
        public PaymentStatus $status,
        public ?string $redirectUrl = null,
        public ?string $instructions = null,
        public array $meta = [],
    ) {}

    /**
     * The money is already in hand.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function settled(array $meta = []): self
    {
        return new self(status: PaymentStatus::Paid, meta: $meta);
    }

    /**
     * The payer has to go to the provider and come back.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function redirect(string $url, array $meta = []): self
    {
        return new self(
            status: PaymentStatus::Pending,
            redirectUrl: $url,
            meta: $meta,
        );
    }

    /**
     * The payer is told how to pay and somebody confirms it later — a bank
     * transfer, a cheque, cash at the office.
     *
     * @param  array<string, mixed>  $meta
     */
    public static function awaiting(string $instructions, array $meta = []): self
    {
        return new self(
            status: PaymentStatus::Pending,
            instructions: $instructions,
            meta: $meta,
        );
    }

    public static function failed(?string $reason = null): self
    {
        return new self(
            status: PaymentStatus::Failed,
            meta: $reason === null ? [] : ['reason' => $reason],
        );
    }

    public function isSettled(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }
}
