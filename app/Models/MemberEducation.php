<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\MemberEducationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Higher-education history beyond the school.
 *
 * @property int $id
 * @property int $member_id
 * @property string $institution
 * @property string|null $degree
 * @property string|null $field_of_study
 * @property int|null $start_year
 * @property int|null $end_year
 * @property int $display_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'member_id', 'institution', 'degree', 'field_of_study', 'start_year', 'end_year',
    'display_order',
])]
class MemberEducation extends Model
{
    /** @use HasFactory<MemberEducationFactory> */
    use HasFactory;

    protected $table = 'member_education';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_year' => 'integer',
            'end_year' => 'integer',
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
