<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VolunteerStatus;
use Carbon\CarbonImmutable;
use Database\Factories\VolunteerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A volunteer, linked to a member or a contact, with a name snapshot.
 *
 * @property int $id
 * @property int|null $member_id
 * @property int|null $crm_contact_id
 * @property string $name
 * @property string|null $phone
 * @property string|null $email
 * @property array<array-key, mixed>|null $skills
 * @property string|null $availability
 * @property string|null $location
 * @property VolunteerStatus $status
 * @property string|null $notes
 * @property CarbonImmutable|null $applied_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'member_id', 'crm_contact_id', 'name', 'phone', 'email', 'skills', 'availability', 'location',
    'status', 'notes', 'applied_at',
])]
class Volunteer extends Model
{
    /** @use HasFactory<VolunteerFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => VolunteerStatus::class,
            'skills' => 'array',
            'applied_at' => 'datetime',
        ];
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
     * @return HasMany<VolunteerAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(VolunteerAssignment::class);
    }
}
