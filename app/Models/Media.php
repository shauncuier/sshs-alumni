<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\HasUlid;
use App\Enums\MediaCollection;
use Carbon\CarbonImmutable;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A stored file, polymorphic and optionally unattached so the admin media
 * library can hold files before they are used.
 *
 * `is_public = false` routes the file to the private disk; private files are
 * never served by a direct URL.
 *
 * @property int $id
 * @property string $ulid
 * @property string|null $model_type
 * @property int|null $model_id
 * @property MediaCollection $collection
 * @property string $disk
 * @property string $path
 * @property string|null $thumb_path
 * @property string $original_name
 * @property string $mime_type
 * @property string $extension
 * @property int $size
 * @property int|null $width
 * @property int|null $height
 * @property string|null $alt
 * @property bool $is_public
 * @property int|null $uploaded_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 * @property-read int|null $gallery_images_count Loaded by withCount() in the media library.
 */
#[Fillable([
    'model_type', 'model_id', 'collection', 'disk', 'path', 'thumb_path', 'original_name',
    'mime_type', 'extension', 'size', 'width', 'height', 'alt', 'is_public', 'uploaded_by',
])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory, HasUlid, SoftDeletes;

    protected $table = 'media';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'collection' => MediaCollection::class,
            'is_public' => 'boolean',
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Gallery rows pointing at this file.
     *
     * The media library refuses to delete a file something still uses: a
     * gallery image whose file vanished leaves a broken image on a public
     * page, and whoever deleted it is never the person who finds out.
     *
     * @return HasMany<GalleryImage, $this>
     */
    public function galleryImages(): HasMany
    {
        return $this->hasMany(GalleryImage::class, 'media_id');
    }
}
