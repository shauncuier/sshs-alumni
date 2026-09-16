<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUlid;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use Carbon\CarbonImmutable;
use Database\Factories\EventRegistrationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One registration, and the ticket the QR code encodes.
 *
 * Registrant details are a snapshot so the record survives deletion of the
 * linked member or contact.
 *
 * @property int $id
 * @property string $ulid
 * @property int $event_id
 * @property int|null $member_id
 * @property int|null $crm_contact_id
 * @property int|null $ticket_type_id
 * @property string $registrant_name
 * @property string|null $registrant_email
 * @property string|null $registrant_phone
 * @property int $guests_count
 * @property string $amount_due
 * @property string $currency
 * @property PaymentStatus $payment_status
 * @property RegistrationStatus $status
 * @property string $qr_token
 * @property CarbonImmutable|null $registered_at
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'event_id', 'member_id', 'crm_contact_id', 'ticket_type_id', 'registrant_name',
    'registrant_email', 'registrant_phone', 'guests_count', 'amount_due', 'currency', 'status',
    'notes', 'registered_at',
])]
class EventRegistration extends Model
{
    /** @use HasFactory<EventRegistrationFactory> */
    use HasFactory, HasUlid, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payment_status' => PaymentStatus::class,
            'status' => RegistrationStatus::class,
            'registered_at' => 'datetime',
            'amount_due' => 'decimal:2',
            'guests_count' => 'integer',
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
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<CrmContact, $this>
     */
    public function crmContact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class);
    }

    /**
     * @return BelongsTo<EventTicketType, $this>
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(EventTicketType::class, 'ticket_type_id');
    }

    /**
     * @return HasMany<EventRegistrationGuest, $this>
     */
    public function guests(): HasMany
    {
        return $this->hasMany(EventRegistrationGuest::class);
    }

    /**
     * @return HasOne<EventCheckin, $this>
     */
    public function checkin(): HasOne
    {
        return $this->hasOne(EventCheckin::class);
    }

    /**
     * @return MorphMany<Payment, $this>
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
