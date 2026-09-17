<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CommitteeMemberStatus;
use Carbon\CarbonImmutable;
use Database\Factories\CommitteeMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A seat on a committee.
 *
 * `member_id` is nullable because historic committee members predate the
 * platform and must still be displayable — name and photo fall back to free text.
 *
 * @property int $id
 * @property int $committee_id
 * @property int|null $member_id
 * @property string $name
 * @property string $role
 * @property string|null $designation
 * @property string|null $photo_path
 * @property string|null $bio
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property int $display_order
 * @property CarbonImmutable|null $start_date
 * @property CarbonImmutable|null $end_date
 * @property CommitteeMemberStatus $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'committee_id', 'member_id', 'name', 'role', 'designation', 'photo_path', 'bio', 'contact_email', 'contact_phone', 'display_order', 'start_date',
    'end_date', 'status',
])]
class CommitteeMember extends Model
{
    /** @use HasFactory<CommitteeMemberFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CommitteeMemberStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Committee, $this>
     */
    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
