<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReactionType;
use Carbon\CarbonImmutable;
use Database\Factories\ReactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One reaction per member per item, enforced by a unique constraint.
 *
 * @property int $id
 * @property string $reactable_type
 * @property int $reactable_id
 * @property int $member_id
 * @property ReactionType $type
 * @property CarbonImmutable|null $created_at
 */
#[Fillable([
    'reactable_type', 'reactable_id', 'member_id', 'type',
])]
class Reaction extends Model
{
    /** @use HasFactory<ReactionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ReactionType::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reactable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
