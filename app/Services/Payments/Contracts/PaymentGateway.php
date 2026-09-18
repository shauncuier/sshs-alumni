<?php

declare(strict_types=1);

namespace App\Services\Payments\Contracts;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\Payments\PaymentIntent;
use Illuminate\Http\Request;

/**
 * What every payment gateway must do.
 *
 * `ManualGateway` is the only implementation today. Adding bKash, Nagad or
 * SSLCommerz later is one class here plus one entry in `config/payments.php` —
 * no controller, service or component changes, because nothing outside this
 * namespace knows which driver is in use.
 *
 * @see docs/09-payments.md section 2
 */
interface PaymentGateway
{
    /**
     * The driver's key in `config/payments.php`, and what lands in
     * `payments.gateway`.
     */
    public function name(): string;

    /**
     * Begin a charge.
     *
     * Returns either a completed result (manual entry, where the money is
     * already in hand) or a redirect/instruction payload for a provider that
     * takes the payer away and brings them back.
     *
     * @param  array<string, mixed>  $context
     */
    public function charge(Payment $payment, array $context = []): PaymentIntent;

    /**
     * Ask the provider what it thinks the payment's state is.
     *
     * Never trust a callback alone: a webhook can be replayed, spoofed or
     * simply lost, and this is the authoritative read.
     */
    public function verify(Payment $payment): PaymentStatus;

    public function refund(Payment $payment, ?string $reason = null): PaymentStatus;

    /**
     * Resolve a provider callback to a payment, or null when it refers to
     * nothing this platform issued.
     */
    public function handleWebhook(Request $request): ?Payment;

    /**
     * Whether a refund can be issued through the provider at all.
     *
     * Manual payments were taken in cash or by transfer, so the money goes
     * back the same way — the platform records that it happened rather than
     * pretending it moved it.
     */
    public function supportsRefund(): bool;
}
