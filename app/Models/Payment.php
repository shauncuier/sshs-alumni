<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Auditable;
use App\Concerns\HasUlid;
use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * The single ledger. Membership fees, event registrations, donations and
 * sponsorships all resolve through `payable`, so income can never disagree with
 * itself.
 *
 * NO SOFT DELETES: a mistake is corrected by refunding and re-recording, both
 * audited. That is what makes the ledger trustworthy.
 *
 * @see docs/09-payments.md
 *
 * @property int $id
 * @property string $ulid
 * @property string $reference
 * @property string $payable_type
 * @property int $payable_id
 * @property int|null $payer_member_id
 * @property int|null $crm_contact_id
 * @property string $payer_name
 * @property string $gateway
 * @property string|null $gateway_txn_id
 * @property string $amount
 * @property string $currency
 * @property PaymentStatus $status
 * @property CarbonImmutable|null $paid_at
 * @property string|null $receipt_no
 * @property string|null $invoice_no
 * @property int|null $recorded_by
 * @property CarbonImmutable|null $refunded_at
 * @property string|null $refund_reason
 * @property string|null $notes
 * @property array<array-key, mixed>|null $meta
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'payable_type', 'payable_id', 'payer_member_id', 'crm_contact_id', 'payer_name', 'gateway',
    'gateway_txn_id', 'amount', 'currency', 'status', 'paid_at', 'recorded_by', 'notes', 'meta',
    'reference',
])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use Auditable, HasFactory, HasUlid;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function payer(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'payer_member_id');
    }

    /**
     * @return BelongsTo<CrmContact, $this>
     */
    public function crmContact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
