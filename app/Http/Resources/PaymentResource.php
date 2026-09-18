<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Payment;
use App\Services\Payments\Contracts\Payable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the ledger.
 *
 * `meta` is passed through because it is written by our own services, never by
 * a request — it carries the method note ("bKash 01712xxxxxx, trx ABC123").
 *
 * @mixin Payment
 */
class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'ulid' => $this->ulid,
            'reference' => $this->reference,
            'receipt_no' => $this->receipt_no,
            'invoice_no' => $this->invoice_no,

            'payer_name' => $this->payer_name,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,

            'gateway' => $this->gateway,
            'gateway_txn_id' => $this->gateway_txn_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),

            'paid_at' => $this->paid_at?->toIso8601String(),
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'refund_reason' => $this->refund_reason,
            'notes' => $this->notes,
            'meta' => $this->meta,
            'created_at' => $this->created_at?->toIso8601String(),

            // What the money was for, in words, rather than a class name.
            'for' => $this->whenLoaded('payable', fn (): ?string => $this->payable instanceof Payable
                ? $this->payable->paymentDescription()
                : null),

            'payer_member_ulid' => $this->whenLoaded(
                'payer',
                fn (): ?string => $this->payer?->ulid,
            ),

            'recorded_by' => $this->whenLoaded(
                'recorder',
                fn (): ?string => $this->recorder?->name,
            ),
        ];
    }
}
