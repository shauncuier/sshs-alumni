<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AssignmentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\VolunteerAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A volunteer assigned to a team, optionally for a specific event.
 *
 * @property int $id
 * @property int $volunteer_id
 * @property int|null $volunteer_team_id
 * @property int|null $event_id
 * @property string|null $responsibility
 * @property CarbonImmutable|null $shift_start
 * @property CarbonImmutable|null $shift_end
 * @property AssignmentStatus $status
 * @property int|null $assigned_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'volunteer_id', 'volunteer_team_id', 'event_id', 'responsibility', 'shift_start', 'shift_end',
    'status', 'assigned_by',
])]
class VolunteerAssignment extends Model
{
    /** @use HasFactory<VolunteerAssignmentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AssignmentStatus::class,
            'shift_start' => 'datetime',
            'shift_end' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Volunteer, $this>
     */
    public function volunteer(): BelongsTo
    {
        return $this->belongsTo(Volunteer::class);
    }

    /**
     * @return BelongsTo<VolunteerTeam, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(VolunteerTeam::class, 'volunteer_team_id');
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
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
