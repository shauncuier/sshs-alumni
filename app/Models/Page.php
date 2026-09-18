<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Publishable;
use App\Enums\ContentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A static page. Privacy policy and terms are `is_system` and cannot be deleted.
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string|null $body
 * @property ContentStatus $status
 * @property bool $is_system
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $og_image_path
 * @property CarbonImmutable|null $published_at
 * @property int|null $updated_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'slug', 'title', 'body', 'status', 'meta_title', 'meta_description',
    'og_image_path', 'published_at',
])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, Publishable, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'is_system' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
