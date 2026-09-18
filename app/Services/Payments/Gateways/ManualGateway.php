<?php

declare(strict_types=1);

namespace App\Services\Payments\Gateways;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payments\Contracts\PaymentGateway;
use App\Services\Payments\PaymentIntent;
use Illuminate\Http\Request;

/**
 * Money the association took in person.
 *
 * Cash at the office, a bank transfer, a bKash send settled outside the
 * platform, a cheque. This is NOT a placeholder for a real gateway — it is how
 * the association is actually paid, and treating it as a first-class flow is
 * the difference between a usable ledger and a spreadsheet nobody trusts.
 *
 * What makes it trustworthy is not this class but PaymentRecorder, which
 * writes the audit row naming whoever took the money. Untraceable offline cash
 * handling is the single largest financial risk in a volunteer-run
 * organization.
 *
 * @see docs/09-payments.md section 3
 */
class ManualGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'manual';
    }

    /**
     * Nothing to charge: an administrator is recording money already received.
     *
     * @param  array<string, mixed>  $context
     */
    public function charge(Payment $payment, array $context = []): PaymentIntent
    {
        return PaymentIntent::settled([
            'method' => $context['method'] ?? null,
        ]);
    }

    /**
     * There is no provider to ask. The recorded status is the truth, because
     * a human looked at the money.
     */
    public function verify(Payment $payment): PaymentStatus
    {
        return $payment->status;
    }

    /**
     * The money goes back the way it came — in cash, or by transfer. This
     * records that it happened; it does not pretend the platform moved it.
     */
    public function refund(Payment $payment, ?string $reason = null): PaymentStatus
    {
        return PaymentStatus::Refunded;
    }

    public function handleWebhook(Request $request): ?Payment
    {
        return null;
    }

    public function supportsRefund(): bool
    {
        return true;
    }
}
