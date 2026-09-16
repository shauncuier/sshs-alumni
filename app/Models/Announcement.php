<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Translatable;
use App\Enums\AnnouncementKind;
use App\Enums\AnnouncementLevel;
use App\Enums\AudienceScope;
use App\Enums\ContentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
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
 * @property string|null $title_bn
 * @property string $body
 * @property string|null $body_bn
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
    'kind', 'title', 'title_bn', 'body', 'body_bn', 'level', 'audience', 'batch_id', 'starts_at',
    'ends_at', 'is_pinned', 'attachment_path', 'published_by', 'status',
])]
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory, SoftDeletes, Translatable;

    /**
     * @return array<int, string>
     */
    public function translatableFields(): array
    {
        return ['title', 'body'];
    }

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
}
