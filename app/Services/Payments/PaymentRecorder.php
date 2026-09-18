<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\CrmContact;
use App\Models\Payment;
use App\Models\User;
use App\Services\Crm\ActivityLogger;
use App\Services\Payments\Contracts\Payable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Writing money into the ledger.
 *
 * Every path that takes money goes through here, so the six things that must
 * happen together cannot be half-done:
 *
 *   1. the `payments` row
 *   2. a receipt number
 *   3. the payable's own status
 *   4. an audit row naming whoever recorded it
 *   5. a `system` entry on the payer's CRM timeline
 *   6. (Phase 8) a notification to the payer
 *
 * All of it inside one transaction. Skipping step 4 would make offline cash
 * handling untraceable, which is the single largest financial risk in a
 * volunteer-run organization — so it is not the caller's job to remember.
 *
 * PAYMENTS ARE NEVER EDITED INTO A DIFFERENT AMOUNT AND NEVER DELETED. A
 * mistake is corrected by refunding and re-recording, and both are audited.
 * That is what makes the ledger worth reading.
 *
 * @see docs/09-payments.md sections 3 and 4
 */
class PaymentRecorder
{
    public function __construct(
        private readonly PaymentManager $gateways,
        private readonly DocumentNumberGenerator $numbers,
        private readonly ActivityLogger $activities,
    ) {}

    /**
     * Record money already received — cash, transfer, cheque, mobile money
     * settled outside the platform.
     *
     * @param  array<string, mixed>  $meta
     */
    public function recordManual(
        Payable&Model $payable,
        User $recordedBy,
        ?float $amount = null,
        ?string $method = null,
        ?string $reference = null,
        ?\DateTimeInterface $paidAt = null,
        ?string $notes = null,
        array $meta = [],
    ): Payment {
        return DB::transaction(function () use (
            $payable,
            $recordedBy,
            $amount,
            $method,
            $reference,
            $paidAt,
            $notes,
            $meta,
        ): Payment {
            $member = $payable->payerMember();

            $payment = new Payment;

            // `receipt_no`, `invoice_no` and `refunded_at` are not fillable:
            // they are issued by this service, never posted.
            $payment->forceFill([
                'payable_type' => $payable->getMorphClass(),
                'payable_id' => $payable->getKey(),
                'payer_member_id' => $member?->id,
                'crm_contact_id' => $this->contactFor($payable)?->id,
                'payer_name' => $payable->payerName(),
                'gateway' => 'manual',
                'gateway_txn_id' => $reference,
                'amount' => $amount ?? $payable->amountDue(),
                'currency' => $payable->paymentCurrency(),
                'status' => PaymentStatus::Paid,
                'paid_at' => $paidAt ?? now(),
                'recorded_by' => $recordedBy->id,
                'reference' => (string) Str::ulid(),
                'receipt_no' => $this->numbers->receipt(),
                'notes' => $notes,
                'meta' => [...$meta, 'method' => $method],
            ]);

            $payment->save();

            // The payable marks ITSELF settled, so adding a fifth kind of
            // payable does not mean editing this method.
            $payable->markPaid($payment);

            $this->recordOnTimeline($payable, $payment);

            return $payment->refresh();
        });
    }

    /**
     * Raise a payment that has not been settled yet — an invoice for a
     * sponsorship, or a pending online charge.
     */
    public function raise(
        Payable&Model $payable,
        ?User $raisedBy = null,
        ?float $amount = null,
        bool $withInvoice = false,
    ): Payment {
        return DB::transaction(function () use ($payable, $raisedBy, $amount, $withInvoice): Payment {
            $member = $payable->payerMember();

            $payment = new Payment;

            $payment->forceFill([
                'payable_type' => $payable->getMorphClass(),
                'payable_id' => $payable->getKey(),
                'payer_member_id' => $member?->id,
                'crm_contact_id' => $this->contactFor($payable)?->id,
                'payer_name' => $payable->payerName(),
                'gateway' => $this->gateways->defaultDriver(),
                'amount' => $amount ?? $payable->amountDue(),
                'currency' => $payable->paymentCurrency(),
                'status' => PaymentStatus::Pending,
                'recorded_by' => $raisedBy?->id,
                'reference' => (string) Str::ulid(),
                'invoice_no' => $withInvoice ? $this->numbers->invoice() : null,
            ]);

            $payment->save();

            return $payment->refresh();
        });
    }

    /**
     * Settle a payment that was raised earlier.
     */
    public function settle(Payment $payment, ?User $actor = null): Payment
    {
        if ($payment->status === PaymentStatus::Paid) {
            return $payment;
        }

        return DB::transaction(function () use ($payment, $actor): Payment {
            $payment->forceFill([
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
                // Issued on the transition to paid, and kept forever after —
                // including through a refund.
                'receipt_no' => $payment->receipt_no ?? $this->numbers->receipt(),
                'recorded_by' => $payment->recorded_by ?? $actor?->id,
            ])->save();

            $payable = $payment->payable;

            if ($payable instanceof Payable) {
                $payable->markPaid($payment);
                $this->recordOnTimeline($payable, $payment);
            }

            return $payment->refresh();
        });
    }

    /**
     * Return money.
     *
     * The original row is preserved — status becomes `refunded`, the receipt
     * number stays. A ledger that erases its mistakes cannot be audited.
     */
    public function refund(Payment $payment, User $actor, ?string $reason = null): Payment
    {
        if ($payment->status !== PaymentStatus::Paid) {
            throw new RuntimeException('Only a paid payment can be refunded.');
        }

        $driver = $this->gateways->driver($payment->gateway);

        if (! $driver->supportsRefund()) {
            throw new RuntimeException("The {$payment->gateway} gateway cannot issue refunds.");
        }

        return DB::transaction(function () use ($payment, $actor, $reason, $driver): Payment {
            $status = $driver->refund($payment, $reason);

            $payment->forceFill([
                'status' => $status,
                'refunded_at' => now(),
                'refund_reason' => $reason,
            ])->save();

            $payable = $payment->payable;

            if ($payable instanceof Payable) {
                $payable->markUnpaid($payment);
            }

            $subject = $payment->payer ?? $payment->crmContact;

            if ($subject !== null) {
                $this->activities->system(
                    subject: $subject,
                    subjectLine: __('admin.payments.system.refunded', [
                        'amount' => number_format((float) $payment->amount, 2),
                        'currency' => $payment->currency,
                    ]),
                    body: $reason,
                    meta: [
                        'payment_ulid' => $payment->ulid,
                        'receipt_no' => $payment->receipt_no,
                        'actor_id' => $actor->id,
                    ],
                );
            }

            return $payment->refresh();
        });
    }

    /**
     * Put the payment on the payer's CRM timeline.
     *
     * A payment with no member and no contact — an anonymous cash donation in
     * a bucket — has no timeline to write to. That is a real case.
     */
    private function recordOnTimeline(Payable&Model $payable, Payment $payment): void
    {
        $subject = $payment->payer ?? $this->contactFor($payable);

        if ($subject === null) {
            return;
        }

        $this->activities->system(
            subject: $subject,
            subjectLine: __('admin.payments.system.received', [
                'amount' => number_format((float) $payment->amount, 2),
                'currency' => $payment->currency,
                'for' => $payable->paymentDescription(),
            ]),
            meta: [
                'payment_ulid' => $payment->ulid,
                'receipt_no' => $payment->receipt_no,
            ],
        );
    }

    /**
     * The CRM contact behind a payable, where it has one.
     */
    private function contactFor(Payable&Model $payable): ?CrmContact
    {
        $contactId = $payable->getAttribute('crm_contact_id');

        if ($contactId !== null) {
            return CrmContact::query()->whereKey($contactId)->first();
        }

        $member = $payable->payerMember();

        return $member?->crmContact()->first();
    }
}
