<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\EventCheckinFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attendance. There is no separate event_attendance table — attendance IS a
 * check-in record, and a UNIQUE constraint on event_registration_id is what
 * prevents duplicate check-ins at the gate.
 *
 * @see docs/02-database-schema.md section 1.3
 *
 * @property int $id
 * @property int $event_registration_id
 * @property int $event_id
 * @property CarbonImmutable $checked_in_at
 * @property int $operator_id
 * @property string|null $gate
 * @property string|null $device
 * @property CarbonImmutable|null $created_at
 */
#[Fillable([
    'event_registration_id', 'event_id', 'checked_in_at', 'operator_id', 'gate', 'device',
])]
class EventCheckin extends Model
{
    /** @use HasFactory<EventCheckinFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<EventRegistration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'event_registration_id');
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_id');
    }
}
