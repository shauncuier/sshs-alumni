<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ContentReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A member report against a post, comment or story.
 *
 * @property int $id
 * @property string $reportable_type
 * @property int $reportable_id
 * @property int|null $reporter_member_id
 * @property ReportReason $reason
 * @property string|null $note
 * @property ReportStatus $status
 * @property int|null $resolved_by
 * @property CarbonImmutable|null $resolved_at
 * @property string|null $resolution_note
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'reportable_type', 'reportable_id', 'reporter_member_id', 'reason', 'note',
])]
class ContentReport extends Model
{
    /** @use HasFactory<ContentReportFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => ReportReason::class,
            'status' => ReportStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'reporter_member_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
