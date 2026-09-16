<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\EventRegistrationGuestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A guest accompanying a registration.
 *
 * @property int $id
 * @property int $event_registration_id
 * @property string $name
 * @property string|null $relation
 * @property string|null $age_group
 * @property string|null $notes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'event_registration_id', 'name', 'relation', 'age_group', 'notes',
])]
class EventRegistrationGuest extends Model
{
    /** @use HasFactory<EventRegistrationGuestFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<EventRegistration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'event_registration_id');
    }
}
