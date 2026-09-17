<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\NewsFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A news article.
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string|null $excerpt
 * @property string $body
 * @property string|null $cover_path
 * @property int|null $author_id
 * @property string|null $category
 * @property bool $is_featured
 * @property ContentStatus $status
 * @property CarbonImmutable|null $published_at
 * @property int $views_count
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $og_image_path
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'slug', 'title', 'excerpt', 'body', 'cover_path',
    'author_id', 'category', 'is_featured', 'status', 'published_at', 'meta_title',
    'meta_description', 'og_image_path',
])]
class News extends Model
{
    /** @use HasFactory<NewsFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'news';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'views_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return MorphMany<Comment, $this>
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
