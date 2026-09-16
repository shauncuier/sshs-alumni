<?php

declare(strict_types=1);

namespace App\Models;

use App\Concerns\Translatable;
use App\Enums\AudienceType;
use App\Enums\CampaignChannel;
use App\Enums\CampaignStatus;
use Carbon\CarbonImmutable;
use Database\Factories\CampaignFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An outbound campaign. Channel-agnostic: the same shape drives email through
 * Laravel Mail and SMS through BulkSMSBD.
 *
 * For SMS, Bangla sends as Unicode at 70 characters per segment against 160 for
 * Latin, so segment count and cost are estimated before sending.
 *
 * @property int $id
 * @property string $name
 * @property int|null $message_template_id
 * @property CampaignChannel $channel
 * @property string|null $subject
 * @property string|null $subject_bn
 * @property string $body
 * @property string|null $body_bn
 * @property AudienceType $audience_type
 * @property array<array-key, mixed>|null $audience_filters
 * @property CarbonImmutable|null $scheduled_at
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $completed_at
 * @property CampaignStatus $status
 * @property int $recipients_count
 * @property int $sent_count
 * @property int $failed_count
 * @property int $opened_count
 * @property int $segments_per_message
 * @property string|null $estimated_cost
 * @property int|null $created_by
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property CarbonImmutable|null $deleted_at
 */
#[Fillable([
    'name', 'message_template_id', 'channel', 'subject', 'subject_bn', 'body', 'body_bn',
    'audience_type', 'audience_filters', 'scheduled_at', 'status', 'created_by',
])]
class Campaign extends Model
{
    /** @use HasFactory<CampaignFactory> */
    use HasFactory, SoftDeletes, Translatable;

    /**
     * @return array<int, string>
     */
    public function translatableFields(): array
    {
        return ['subject', 'body'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => CampaignChannel::class,
            'status' => CampaignStatus::class,
            'audience_type' => AudienceType::class,
            'audience_filters' => 'array',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'estimated_cost' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<MessageTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'message_template_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<CampaignRecipient, $this>
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }
}
