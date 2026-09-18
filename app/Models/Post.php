<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUlid;
use App\Enums\PostCategory;
use App\Enums\PostStatus;
use Carbon\CarbonImmutable;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A community post.
 *
 * Mentions are stored inline in the body as `@member:{ulid}` and resolved at
 * render time — a join table would add write cost for something only ever read
 * with the post.
 *
 * @property int $id
 * @property string $ulid
 * @property int $author_member_id
 * @property PostCategory $category
 * @property int|null $batch_id
 * @property string|null $title
 * @property string $body
 * @property PostStatus $status
 * @property bool $is_pinned
 * @property bool $comments_enabled
 * @property int $comments_count
 * @property int $reactions_count
 * @property CarbonImmutable|null $last_activity_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read int|null $open_reports_count Loaded by withCount() in the moderation queue.
 */
#[Fillable([
    'author_member_id', 'category', 'batch_id', 'title', 'body', 'comments_enabled',
])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasUlid, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => PostCategory::class,
            'status' => PostStatus::class,
            'is_pinned' => 'boolean',
            'comments_enabled' => 'boolean',
            'last_activity_at' => 'datetime',
            'comments_count' => 'integer',
            'reactions_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'author_member_id');
    }

    /**
     * @return BelongsTo<Batch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * @return MorphMany<Comment, $this>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * @return MorphMany<Reaction, $this>
     */
    public function reactions(): MorphMany
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }

    /**
     * @return MorphMany<ContentReport, $this>
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(ContentReport::class, 'reportable');
    }

    /**
     * @return MorphMany<Media, $this>
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    /**
     * The feed as one member may see it.
     *
     * TWO RULES, AND THEY LIVE HERE so the list and the single post can never
     * disagree about them:
     *
     *   1. Only published posts, unless you wrote it. An author can still read
     *      their own hidden post — being moderated is not the same as being
     *      lied to about whether your words still exist.
     *   2. A post tied to a batch belongs to that batch. Batch discussion is
     *      the one place members expect a smaller room than the whole
     *      association, and `batch_id` is what makes the room.
     *
     * Moderators bypass this entirely; they read through the admin queue,
     * which is a different query with a different purpose.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeVisibleTo(Builder $query, ?Member $viewer): void
    {
        $viewerId = $viewer?->id;
        $viewerBatch = $viewer?->batch_id;

        $query->where(function (Builder $outer) use ($viewerId): void {
            $outer->where('status', PostStatus::Published);

            if ($viewerId !== null) {
                $outer->orWhere('author_member_id', $viewerId);
            }
        });

        $query->where(function (Builder $outer) use ($viewerBatch): void {
            $outer->whereNull('batch_id');

            if ($viewerBatch !== null) {
                $outer->orWhere('batch_id', $viewerBatch);
            }
        });
    }

    /**
     * Readable by this member right now, by the same two rules as the feed.
     *
     * The single-post page asks this rather than re-running the scope, because
     * a post reached by URL has already been loaded.
     */
    public function isVisibleTo(?Member $viewer): bool
    {
        $isAuthor = $viewer !== null && $viewer->id === $this->author_member_id;

        if ($this->status !== PostStatus::Published && ! $isAuthor) {
            return false;
        }

        return $this->batch_id === null || $this->batch_id === $viewer?->batch_id;
    }
}
