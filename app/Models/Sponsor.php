<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUlid;
use App\Enums\SponsorKind;
use App\Enums\SponsorStatus;
use App\Services\Payments\Contracts\Payable;
use Carbon\CarbonImmutable;
use Database\Factories\SponsorFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A sponsor of an event.
 *
 * `agreement_path` points at the PRIVATE disk and is never served by a direct
 * URL — only through a controller that runs a policy check.
 *
 * @property int $id
 * @property string $ulid
 * @property int|null $event_id
 * @property int|null $sponsorship_package_id
 * @property SponsorKind $kind
 * @property string $name
 * @property string|null $contact_name
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string|null $logo_path
 * @property string|null $website
 * @property string|null $amount
 * @property string $currency
 * @property string|null $agreement_path
 * @property SponsorStatus $status
 * @property bool $is_public
 * @property int $display_order
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'event_id', 'sponsorship_package_id', 'kind', 'name', 'contact_name',
    'contact_email', 'contact_phone', 'logo_path', 'website', 'amount', 'currency',
    'agreement_path', 'status', 'is_public', 'display_order', 'notes',
])]
class Sponsor extends Model implements Payable
{
    /** @use HasFactory<SponsorFactory> */
    use HasFactory, HasUlid, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => SponsorKind::class,
            'status' => SponsorStatus::class,
            'amount' => 'decimal:2',
            'is_public' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<SponsorshipPackage, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(SponsorshipPackage::class, 'sponsorship_package_id');
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
        // A sponsor may pay a negotiated figure rather than the package
        // price, and a custom sponsorship has neither.
        if ($this->amount !== null) {
            return (float) $this->amount;
        }

        return (float) ($this->package->amount ?? 0);
    }

    public function paymentCurrency(): string
    {
        return $this->currency;
    }

    /**
     * A sponsor is an organisation or an individual outside the membership.
     * Where one happens to be an alumnus, the link lives on their CRM contact.
     */
    public function payerMember(): ?Member
    {
        return null;
    }

    public function payerName(): string
    {
        return $this->name;
    }

    public function paymentDescription(): string
    {
        return __('admin.sponsors.description', [
            'package' => $this->package->name ?? __('admin.sponsors.custom'),
        ]);
    }

    public function markPaid(Payment $payment): void
    {
        $this->forceFill(['status' => SponsorStatus::Paid])->save();
    }

    /**
     * Back to confirmed, not pending: the agreement still stands even when the
     * money has been returned.
     */
    public function markUnpaid(Payment $payment): void
    {
        $this->forceFill(['status' => SponsorStatus::Confirmed])->save();
    }
}
