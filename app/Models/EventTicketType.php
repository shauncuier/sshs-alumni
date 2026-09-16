<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\EventTicketTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A ticket tier for an event.
 *
 * @property int $id
 * @property int $event_id
 * @property string $name
 * @property string|null $name_bn
 * @property string|null $description
 * @property string $price
 * @property string $currency
 * @property int|null $quantity
 * @property int $sold_count
 * @property int $per_person_limit
 * @property bool $is_active
 * @property int $display_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'event_id', 'name', 'name_bn', 'description', 'price', 'currency', 'quantity',
    'per_person_limit', 'is_active', 'display_order',
])]
class EventTicketType extends Model
{
    /** @use HasFactory<EventTicketTypeFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'quantity' => 'integer',
            'sold_count' => 'integer',
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
     * @return HasMany<EventRegistration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class, 'ticket_type_id');
    }
}
