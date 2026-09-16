<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUlid;
use App\Enums\PostCategory;
use App\Enums\PostStatus;
use Carbon\CarbonImmutable;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
}
