<?php

declare(strict_types=1);

namespace App\Services\Payments\Contracts;

use App\Models\Member;
use App\Models\Payment;

/**
 * Something money can be taken for.
 *
 * Four implementations: `MembershipFee`, `EventRegistration`, `Donation`,
 * `Sponsor`. Each knows what it costs, who owes it, and what to do about
 * itself once it is paid — so PaymentRecorder does not need a `match` over
 * every payable type that has to be edited each time one is added.
 *
 * @see docs/09-payments.md section 1
 */
interface Payable
{
    /**
     * What is owed. Used when a recorder is not given an explicit amount.
     */
    public function amountDue(): float;

    public function paymentCurrency(): string;

    /**
     * The alumni record that owes this, if any. A donation from a stranger and
     * a sponsorship from a company both have none.
     */
    public function payerMember(): ?Member;

    /**
     * The name that goes on the receipt when there is no member record.
     */
    public function payerName(): string;

    /**
     * What the payment is for, in words, for a receipt and a timeline entry.
     */
    public function paymentDescription(): string;

    /**
     * Mark this thing as settled.
     *
     * Called inside PaymentRecorder's transaction, so a failure here rolls the
     * payment back too — a fee must never read `paid` while the ledger row
     * that paid it is missing, and the reverse must never happen either.
     */
    public function markPaid(Payment $payment): void;

    /**
     * Undo the above when a payment is refunded.
     */
    public function markUnpaid(Payment $payment): void;
}
