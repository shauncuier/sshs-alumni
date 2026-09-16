<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\MemberEmploymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Career history.
 *
 * `members.occupation` / `organization` / `industry` hold the CURRENT values,
 * denormalised for directory filtering; this table holds the history. The
 * observer keeps them in sync when `is_current` changes.
 *
 * @property int $id
 * @property int $member_id
 * @property string $organization
 * @property string|null $job_title
 * @property string|null $industry
 * @property CarbonImmutable|null $start_date
 * @property CarbonImmutable|null $end_date
 * @property bool $is_current
 * @property int $display_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'member_id', 'organization', 'job_title', 'industry', 'start_date', 'end_date', 'is_current',
    'display_order',
])]
class MemberEmployment extends Model
{
    /** @use HasFactory<MemberEmploymentFactory> */
    use HasFactory;

    protected $table = 'member_employment';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_current' => 'boolean',
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
