<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUlid;
use App\Concerns\Publishable;
use App\Enums\ContentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\JobPostingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An alumni job or career opportunity.
 *
 * @property int $id
 * @property string $ulid
 * @property int $member_id
 * @property string $title
 * @property string $company_name
 * @property string|null $location
 * @property string $workplace_type
 * @property string $employment_type
 * @property string|null $experience_level
 * @property string|null $salary_range
 * @property string $description
 * @property string|null $requirements
 * @property string $application_url_or_email
 * @property CarbonImmutable|null $deadline_at
 * @property ContentStatus $status
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'ulid', 'member_id', 'title', 'company_name', 'location',
    'workplace_type', 'employment_type', 'experience_level', 'salary_range',
    'description', 'requirements', 'application_url_or_email', 'deadline_at',
    'status', 'published_at',
])]
class JobPosting extends Model
{
    /** @use HasFactory<JobPostingFactory> */
    use HasFactory, HasUlid, Publishable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'deadline_at' => 'date',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
