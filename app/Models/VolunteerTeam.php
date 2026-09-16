<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Translatable;
use Carbon\CarbonImmutable;
use Database\Factories\VolunteerTeamFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A volunteer team: Registration, Reception, Media, Logistics and so on.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $name_bn
 * @property string|null $description
 * @property string|null $description_bn
 * @property int|null $lead_member_id
 * @property int $display_order
 * @property bool $is_active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'slug', 'name', 'name_bn', 'description', 'description_bn', 'lead_member_id', 'display_order',
    'is_active',
])]
class VolunteerTeam extends Model
{
    /** @use HasFactory<VolunteerTeamFactory> */
    use HasFactory, Translatable;

    /**
     * @return array<int, string>
     */
    public function translatableFields(): array
    {
        return ['name', 'description'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'lead_member_id');
    }

    /**
     * @return HasMany<VolunteerAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(VolunteerAssignment::class);
    }
}
