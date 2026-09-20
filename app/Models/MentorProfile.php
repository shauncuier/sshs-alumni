<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUlid;
use Carbon\CarbonImmutable;
use Database\Factories\MentorProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An alumnus volunteering to provide career or academic mentorship.
 *
 * @property int $id
 * @property string $ulid
 * @property int $member_id
 * @property string $title
 * @property string $company_or_institution
 * @property array<string> $expertise
 * @property string $bio
 * @property int $years_of_experience
 * @property int $max_mentees
 * @property bool $is_available
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'ulid', 'member_id', 'title', 'company_or_institution',
    'expertise', 'bio', 'years_of_experience', 'max_mentees', 'is_available',
])]
class MentorProfile extends Model
{
    /** @use HasFactory<MentorProfileFactory> */
    use HasFactory, HasUlid, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expertise' => 'array',
            'years_of_experience' => 'integer',
            'max_mentees' => 'integer',
            'is_available' => 'boolean',
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
     * @return HasMany<MentorshipRequest, $this>
     */
    public function requests(): HasMany
    {
        return $this->hasMany(MentorshipRequest::class, 'mentor_id', 'member_id');
    }

    /**
     * @param  Builder<static>  $query
     */
    public function scopeAvailable(Builder $query): void
    {
        $query->where('is_available', true);
    }
}
