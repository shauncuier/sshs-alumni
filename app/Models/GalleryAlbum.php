<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Translatable;
use App\Enums\ContentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\GalleryAlbumFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A photo album, optionally tied to an event or a batch.
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string|null $title_bn
 * @property string|null $description
 * @property string|null $description_bn
 * @property string|null $cover_path
 * @property int|null $event_id
 * @property int|null $batch_id
 * @property ContentStatus $status
 * @property CarbonImmutable|null $published_at
 * @property int $images_count
 * @property int $display_order
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'slug', 'title', 'title_bn', 'description', 'description_bn', 'cover_path', 'event_id',
    'batch_id', 'status', 'published_at', 'display_order',
])]
class GalleryAlbum extends Model
{
    /** @use HasFactory<GalleryAlbumFactory> */
    use HasFactory, SoftDeletes, Translatable;

    /**
     * @return array<int, string>
     */
    public function translatableFields(): array
    {
        return ['title', 'description'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'images_count' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<Batch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * @return HasMany<GalleryImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(GalleryImage::class);
    }
}
