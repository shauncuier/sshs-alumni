<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MemberLinkType;
use Carbon\CarbonImmutable;
use Database\Factories\MemberLinkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A member social or web link.
 *
 * @property int $id
 * @property int $member_id
 * @property MemberLinkType $type
 * @property string $url
 * @property int $display_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'member_id', 'type', 'url', 'display_order',
])]
class MemberLink extends Model
{
    /** @use HasFactory<MemberLinkFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MemberLinkType::class,
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
