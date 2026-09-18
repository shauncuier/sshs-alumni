<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AnnouncementKind;
use App\Enums\AnnouncementLevel;
use App\Enums\AudienceScope;
use App\Enums\ContentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An announcement or notice.
 *
 * `notices` is not a separate table: it is the `kind` column, because the two
 * have an identical shape, admin UI and public rendering.
 *
 * @property int $id
 * @property AnnouncementKind $kind
 * @property string $title
 * @property string $body
 * @property AnnouncementLevel $level
 * @property AudienceScope $audience
 * @property int|null $batch_id
 * @property CarbonImmutable|null $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property bool $is_pinned
 * @property string|null $attachment_path
 * @property int|null $published_by
 * @property ContentStatus $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'kind', 'title', 'body', 'level', 'audience', 'batch_id', 'starts_at',
    'ends_at', 'is_pinned', 'attachment_path', 'published_by', 'status',
])]
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => AnnouncementKind::class,
            'level' => AnnouncementLevel::class,
            'audience' => AudienceScope::class,
            'status' => ContentStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_pinned' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Batch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * Announcements that are live right now.
     *
     * TIME-WINDOWED, which is why this model does not use the shared
     * Publishable trait: an announcement is not published on a date and then
     * permanent, it runs between two of them. "Registration closes on Friday"
     * is worse than useless on Saturday, and expecting somebody to remember to
     * take it down is expecting the wrong thing.
     *
     * Both ends are optional. No `starts_at` means it is live as soon as it is
     * published; no `ends_at` means it stays until somebody drafts it.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query
            ->where('status', ContentStatus::Published)
            ->where(fn (Builder $inner) => $inner
                ->whereNull('starts_at')
                ->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $inner) => $inner
                ->whereNull('ends_at')
                ->orWhere('ends_at', '>=', now()));
    }

    /**
     * Narrowed to what one reader is entitled to see.
     *
     * A guest gets `public` only. A signed-in member also gets `members`, and
     * the announcements aimed at their own batch. `role` audiences are
     * deliberately NOT resolved here — no announcement targets a role yet, and
     * a scope that guessed at the rule would be a guess nobody tested.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeForAudience(Builder $query, ?Member $reader): void
    {
        if ($reader === null || ! $reader->isApproved()) {
            $query->where('audience', AudienceScope::Public);

            return;
        }

        $batchId = $reader->batch_id;

        $query->where(function (Builder $inner) use ($batchId): void {
            $inner
                ->whereIn('audience', [AudienceScope::Public, AudienceScope::Members])
                ->orWhere(fn (Builder $batch) => $batch
                    ->where('audience', AudienceScope::Batch)
                    ->where('batch_id', $batchId));
        });
    }
}
