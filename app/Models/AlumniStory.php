<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StoryStatus;
use Carbon\CarbonImmutable;
use Database\Factories\AlumniStoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A member-submitted story, reviewed before publication.
 *
 * @property int $id
 * @property string $slug
 * @property int|null $member_id
 * @property string $author_name
 * @property string $title
 * @property string $body
 * @property string|null $photo_path
 * @property int|null $batch_id
 * @property string|null $career_summary
 * @property bool $is_featured
 * @property StoryStatus $status
 * @property CarbonImmutable|null $published_at
 * @property int|null $reviewed_by
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $og_image_path
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'slug', 'member_id', 'author_name', 'title', 'body', 'photo_path',
    'batch_id', 'career_summary', 'is_featured', 'status', 'published_at', 'meta_title',
    'meta_description', 'og_image_path',
])]
class AlumniStory extends Model
{
    /** @use HasFactory<AlumniStoryFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StoryStatus::class,
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
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
     * @return BelongsTo<Batch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * @return MorphMany<Comment, $this>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
