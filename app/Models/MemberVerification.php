<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MemberStatus;
use Carbon\CarbonImmutable;
use Database\Factories\MemberVerificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per status transition. Append-only.
 *
 * `note` is internal and is never shown to the member; `correction_requested`
 * is the message that is.
 *
 * @property int $id
 * @property int $member_id
 * @property int|null $actor_id
 * @property MemberStatus|null $from_status
 * @property MemberStatus $to_status
 * @property string|null $note
 * @property string|null $correction_requested
 * @property CarbonImmutable|null $created_at
 */
#[Fillable([
    'member_id', 'actor_id', 'from_status', 'to_status', 'note', 'correction_requested',
])]
class MemberVerification extends Model
{
    /** @use HasFactory<MemberVerificationFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => MemberStatus::class,
            'to_status' => MemberStatus::class,
            'created_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
