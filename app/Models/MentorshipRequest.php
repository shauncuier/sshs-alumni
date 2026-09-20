<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUlid;
use Carbon\CarbonImmutable;
use Database\Factories\MentorshipRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A structured mentorship request connecting a mentee alumnus with a mentor alumnus.
 *
 * @property int $id
 * @property string $ulid
 * @property int $mentor_id
 * @property int $mentee_id
 * @property string $topic
 * @property string $message
 * @property string $status
 * @property string|null $response_note
 * @property CarbonImmutable|null $responded_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'ulid', 'mentor_id', 'mentee_id', 'topic', 'message',
    'status', 'response_note', 'responded_at',
])]
class MentorshipRequest extends Model
{
    /** @use HasFactory<MentorshipRequestFactory> */
    use HasFactory, HasUlid, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'mentor_id');
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function mentee(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'mentee_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }
}
