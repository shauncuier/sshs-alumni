<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUlid;
use App\Enums\DonationStatus;
use App\Services\Payments\Contracts\Payable;
use Carbon\CarbonImmutable;
use Database\Factories\DonationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A donation, optionally anonymous and optionally tied to a campaign or event.
 *
 * @property int $id
 * @property string $ulid
 * @property int|null $donor_member_id
 * @property int|null $crm_contact_id
 * @property string $donor_name
 * @property string|null $donor_email
 * @property string|null $donor_phone
 * @property string|null $campaign
 * @property int|null $event_id
 * @property string $amount
 * @property string $currency
 * @property bool $is_anonymous
 * @property string|null $message
 * @property DonationStatus $status
 * @property bool $is_public
 * @property CarbonImmutable|null $received_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'donor_member_id', 'crm_contact_id', 'donor_name', 'donor_email', 'donor_phone', 'campaign',
    'event_id', 'amount', 'currency', 'is_anonymous', 'message', 'status',
    'is_public', 'received_at',
])]
class Donation extends Model implements Payable
{
    /** @use HasFactory<DonationFactory> */
    use HasFactory, HasUlid, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DonationStatus::class,
            'amount' => 'decimal:2',
            'is_anonymous' => 'boolean',
            'is_public' => 'boolean',
            'received_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function donor(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'donor_member_id');
    }

    /**
     * @return BelongsTo<CrmContact, $this>
     */
    public function crmContact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function amountDue(): float
    {
        return (float) $this->amount;
    }

    public function paymentCurrency(): string
    {
        return $this->currency;
    }

    public function payerMember(): ?Member
    {
        return $this->donor;
    }

    public function payerName(): string
    {
        return $this->donor_name;
    }

    public function paymentDescription(): string
    {
        return $this->campaign === null
            ? __('admin.donations.description')
            : __('admin.donations.description_campaign', ['campaign' => $this->campaign]);
    }

    public function markPaid(Payment $payment): void
    {
        $this->forceFill([
            'status' => DonationStatus::Received,
            'received_at' => $payment->paid_at ?? now(),
        ])->save();
    }

    public function markUnpaid(Payment $payment): void
    {
        $this->forceFill([
            'status' => DonationStatus::Refunded,
        ])->save();
    }
}
