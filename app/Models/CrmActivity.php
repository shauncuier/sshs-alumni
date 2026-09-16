<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CrmActivityType;
use Carbon\CarbonImmutable;
use Database\Factories\CrmActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One polymorphic timeline over members AND contacts.
 *
 * `system` rows are written automatically by observers on verification, payment
 * and registration events, which is what makes the contact timeline a single
 * ordered query rather than a four-way union.
 *
 * @see docs/02-database-schema.md section 1.2
 *
 * @property int $id
 * @property string $subject_type
 * @property int $subject_id
 * @property CrmActivityType $type
 * @property string|null $subject_line
 * @property string|null $body
 * @property string|null $outcome
 * @property CarbonImmutable $occurred_at
 * @property int|null $user_id
 * @property array<array-key, mixed>|null $meta
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'subject_type', 'subject_id', 'type', 'subject_line', 'body', 'outcome', 'occurred_at',
    'user_id', 'meta',
])]
class CrmActivity extends Model
{
    /** @use HasFactory<CrmActivityFactory> */
    use HasFactory;

    protected $table = 'crm_activities';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CrmActivityType::class,
            'occurred_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
