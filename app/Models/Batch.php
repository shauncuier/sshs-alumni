<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BatchStatus;
use Carbon\CarbonImmutable;
use Database\Factories\BatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A cohort, keyed by SSC year.
 *
 * `members_count` is a counter cache maintained by MemberObserver — the batch
 * index would otherwise issue one COUNT(*) per batch.
 *
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property int $ssc_year
 * @property string|null $description
 * @property string|null $cover_path
 * @property int $members_count
 * @property BatchStatus $status
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'slug', 'name', 'ssc_year', 'description', 'cover_path', 'status',
])]
class Batch extends Model
{
    /** @use HasFactory<BatchFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BatchStatus::class,
            'ssc_year' => 'integer',
            'members_count' => 'integer',
        ];
    }

    /**
     * @return HasMany<Member, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * @return HasMany<Announcement, $this>
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    /**
     * @return HasMany<GalleryAlbum, $this>
     */
    public function galleryAlbums(): HasMany
    {
        return $this->hasMany(GalleryAlbum::class);
    }

    /**
     * @return BelongsToMany<Member, $this>
     */
    public function coordinators(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'batch_coordinators')
            ->withPivot(['assigned_at', 'assigned_by'])
            ->withTimestamps();
    }
}
